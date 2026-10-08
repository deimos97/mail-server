<?php

namespace Tests\Support;

use Illuminate\Support\Str;
use Stripe\ApiRequestor;
use Stripe\HttpClient\ClientInterface;

/**
 * Stripe en memoria para los tests: guarda lo que se crea y lo devuelve al leerlo. Se engancha por debajo
 * del SDK oficial (ApiRequestor::setHttpClient), así que vale igual para Cashier que para nuestro código.
 */
class FakeStripe implements ClientInterface
{
    /** @var array<string, array<string, array>> recurso → id → objeto */
    public array $objects = [];

    /** @var list<array{0:string,1:string,2:array}> */
    public array $requests = [];

    private const PREFIX = ['products' => 'prod', 'prices' => 'price', 'coupons' => 'co', 'tax_rates' => 'txr',
        'customers' => 'cus', 'checkout/sessions' => 'cs_test', 'billing_portal/sessions' => 'bps', 'billing_portal/configurations' => 'bpc', 'subscriptions' => 'sub'];

    public static function install(): self
    {
        $fake = new self;
        ApiRequestor::setHttpClient($fake);
        config(['cashier.secret' => 'sk_test_fake', 'cashier.key' => 'pk_test_fake']);

        return $fake;
    }

    public static function uninstall(): void
    {
        ApiRequestor::setHttpClient(null);
    }

    public function request($method, $absUrl, $headers, $params, $hasFile, $apiMode = 'v1', $maxNetworkRetries = null)
    {
        $path = trim(parse_url($absUrl, PHP_URL_PATH), '/');
        $path = preg_replace('#^v1/#', '', $path);
        $query = [];
        parse_str((string) parse_url($absUrl, PHP_URL_QUERY), $query);
        // El SDK manda los booleanos como "true"/"false"
        $params = $this->decode(array_merge($query, (array) $params));
        $this->requests[] = [strtoupper($method), $path, $params];

        [$resource, $id] = $this->split($path);

        $body = match (true) {
            strtoupper($method) === 'GET' && $id === null => ['object' => 'list', 'data' => array_values($this->objects[$resource] ?? []), 'has_more' => false, 'url' => '/v1/'.$resource],
            strtoupper($method) === 'GET' => $this->objects[$resource][$id] ?? null,
            strtoupper($method) === 'DELETE' => tap(['id' => $id, 'deleted' => true], fn () => $this->forget($resource, $id)),
            $id === null => $this->create($resource, $params),
            default => $this->update($resource, $id, $params),
        };

        if ($body === null) {
            return [json_encode(['error' => ['type' => 'invalid_request_error', 'message' => "No such object: $id"]]), 404, []];
        }

        return [json_encode($body), 200, []];
    }

    /** Peticiones hechas a un recurso (p. ej. "POST", "prices"). */
    public function sent(string $method, string $resource): array
    {
        return array_values(array_map(fn ($r) => $r[2], array_filter($this->requests, fn ($r) => $r[0] === $method && $r[1] === $resource)));
    }

    private function decode(array $params): array
    {
        array_walk_recursive($params, function (&$v) {
            $v = match ($v) {
                'true' => true, 'false' => false, default => $v
            };
        });

        return $params;
    }

    private function split(string $path): array
    {
        foreach (['checkout/sessions', 'billing_portal/sessions', 'billing_portal/configurations'] as $nested) {
            if (str_starts_with($path, $nested)) {
                $rest = trim(substr($path, strlen($nested)), '/');

                return [$nested, $rest === '' ? null : explode('/', $rest)[0]];
            }
        }
        $parts = explode('/', $path);

        return [$parts[0], $parts[1] ?? null];
    }

    private function create(string $resource, array $params): array
    {
        $id = (self::PREFIX[$resource] ?? Str::singular($resource)).'_'.Str::random(14);
        $object = ['id' => $id] + $params + ['object' => Str::singular(str_replace('/', '.', $resource)), 'livemode' => false, 'metadata' => []];
        if ($resource === 'checkout/sessions') {
            $object += ['url' => 'https://checkout.stripe.test/'.$id, 'status' => 'open', 'payment_status' => 'unpaid'];
            $object['object'] = 'checkout.session';
        }
        if ($resource === 'billing_portal/sessions') {
            $object += ['url' => 'https://billing.stripe.test/'.$id];
            $object['object'] = 'billing_portal.session';
        }
        if ($resource === 'tax_rates') {
            $object['active'] = true;
        }

        return $this->objects[$resource][$id] = $object;
    }

    /** Crea una suscripción "como la de Stripe" (con sus items) para probar cambios de plan. */
    public function subscription(string $id, string $customer, array $prices, array $metadata = []): array
    {
        $this->objects['subscriptions'][$id] = ['id' => $id, 'object' => 'subscription', 'customer' => $customer,
            'status' => 'active', 'metadata' => $metadata, 'cancel_at_period_end' => false,
            'current_period_end' => now()->addMonth()->timestamp, 'items' => ['object' => 'list', 'data' => []]];
        $this->setItems($id, array_map(fn ($price) => ['price' => $price], $prices));

        return $this->objects['subscriptions'][$id];
    }

    /** Aplica a una suscripción los `items` de un update (nuevos, cambiados o borrados). */
    private function setItems(string $subscription, array $items): void
    {
        $current = collect($this->objects['subscriptions'][$subscription]['items']['data'])->keyBy('id');
        foreach ($items as $item) {
            if (! empty($item['deleted'])) {
                $current->forget($item['id']);
                unset($this->objects['subscription_items'][$item['id']]);

                continue;
            }
            $id = $item['id'] ?? 'si_'.Str::random(10);
            $price = $this->objects['prices'][$item['price']] ?? ['id' => $item['price'], 'product' => null];
            $object = ['id' => $id, 'object' => 'subscription_item', 'subscription' => $subscription, 'quantity' => 1,
                'current_period_end' => now()->addMonth()->timestamp, 'current_period_start' => now()->timestamp,
                'price' => ['id' => $price['id'], 'object' => 'price', 'product' => $price['product'] ?? null,
                    'recurring' => ['usage_type' => 'licensed'] + ($price['recurring'] ?? [])]];
            $current->put($id, $object);
            $this->objects['subscription_items'][$id] = $object;
        }
        $this->objects['subscriptions'][$subscription]['items']['data'] = $current->values()->all();
    }

    private function update(string $resource, string $id, array $params): ?array
    {
        if (! isset($this->objects[$resource][$id])) {
            return null;
        }
        if ($resource === 'subscriptions' && isset($params['items'])) {
            $this->setItems($id, $params['items']);
            unset($params['items']);
        }

        return $this->objects[$resource][$id] = array_replace_recursive($this->objects[$resource][$id], $params);
    }

    private function forget(string $resource, string $id): void
    {
        unset($this->objects[$resource][$id]);
    }
}
