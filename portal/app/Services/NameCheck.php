<?php

namespace App\Services;

/** Resultado de comprobar un nombre de buzón. */
final readonly class NameCheck
{
    public const AVAILABLE = 'available';

    public const INVALID = 'invalid';

    public const TAKEN = 'taken';

    public const UNAVAILABLE = 'unavailable';   // reservado (no se dice por qué)

    /**
     * @param  list<string>  $suggestions  local parts disponibles y sin sobrecoste
     */
    public function __construct(
        public string $status,
        public string $localPart,
        public string $domain,
        public ?string $message = null,
        public ?int $surchargeCents = null,
        public array $suggestions = [],
    ) {}

    public function isAvailable(): bool
    {
        return $this->status === self::AVAILABLE;
    }

    /** Un nombre con sobrecoste solo se puede coger con un plan de pago (D-005). */
    public function requiresPaidPlan(): bool
    {
        return $this->surchargeCents !== null;
    }

    public function toArray(): array
    {
        return [
            'status' => $this->status,
            'available' => $this->isAvailable(),
            'email' => $this->localPart.'@'.$this->domain,
            'local_part' => $this->localPart,
            'domain' => $this->domain,
            'message' => $this->message,
            'surcharge_cents' => $this->surchargeCents,
            'requires_paid_plan' => $this->requiresPaidPlan(),
            'suggestions' => $this->suggestions,
        ];
    }
}
