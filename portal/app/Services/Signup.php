<?php

namespace App\Services;

use App\Models\Domain;
use App\Models\Mailbox;
use App\Models\MailboxReservation;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * El alta: reservar el nombre, crear la cuenta y crear el buzón.
 *
 * El buzón se crea con una contraseña interna aleatoria que nadie conoce (D-011: el usuario entra
 * con contraseñas por dispositivo y, en el webmail, con el login único). Recibe correo desde ya; envía
 * cuando el usuario verifica su email de recuperación (can_send, D-006).
 */
class Signup
{
    public function __construct(private NameAvailability $availability) {}

    /** Reserva el nombre para este alta (token) o renueva su reserva. Devuelve null si no está disponible. */
    public function reserve(string $input, Domain $domain, string $token, ?User $user = null): ?MailboxReservation
    {
        $check = $this->availability->check($input, $domain, withSuggestions: false, reservationOwner: $token);
        if (! $check->isAvailable()) {
            return null;
        }

        return DB::transaction(function () use ($check, $domain, $token, $user) {
            // Una sola reserva por alta: si cambia de nombre, se libera el anterior
            MailboxReservation::where('session_id', $token)->where('local_part', '!=', $check->localPart)->delete();
            MailboxReservation::where('domain_id', $domain->id)->where('local_part', $check->localPart)
                ->where('expires_at', '<=', now())->delete();

            try {
                return MailboxReservation::updateOrCreate(
                    ['domain_id' => $domain->id, 'local_part' => $check->localPart, 'session_id' => $token],
                    ['user_id' => $user?->id, 'expires_at' => now()->addMinutes(MailboxReservation::MINUTES)],
                );
            } catch (QueryException) {
                return null;   // otra alta lo ha reservado en este mismo instante
            }
        });
    }

    /** La reserva vigente de este alta, renovada otros 15 minutos. */
    public function currentReservation(string $token): ?MailboxReservation
    {
        $reservation = MailboxReservation::current()->with('domain')->where('session_id', $token)->latest('id')->first();
        $reservation?->update(['expires_at' => now()->addMinutes(MailboxReservation::MINUTES)]);

        return $reservation;
    }

    public function createUser(string $email, string $password, ?string $ip, ?array $attribution): User
    {
        $user = new User;
        $user->email = mb_strtolower(trim($email));
        $user->password = $password;
        $user->terms_accepted_at = now();
        $user->signup_ip = $ip;
        $user->attribution = $attribution;
        $user->save();

        return $user;
    }

    /**
     * Crea el buzón reservado con el plan elegido. Vuelve a comprobar todo justo antes del INSERT.
     *
     * @throws RuntimeException con un mensaje para el usuario si algo ya no cuadra
     */
    public function provision(User $user, MailboxReservation $reservation, Plan $plan, string $token): Mailbox
    {
        if (! $plan->isVisible() || ! $plan->is_free) {
            throw new RuntimeException('Ese plan no está disponible ahora mismo.');
        }

        $domain = $reservation->domain;
        $check = $this->availability->check($reservation->local_part, $domain, withSuggestions: false, reservationOwner: $token);
        if (! $check->isAvailable()) {
            throw new RuntimeException('Ese nombre ya no está disponible. Elige otro.');
        }
        if ($check->requiresPaidPlan() && $plan->is_free) {
            throw new RuntimeException('Los nombres cortos necesitan un plan de pago.');
        }

        try {
            $mailbox = Mailbox::create([
                'domain_id' => $domain->id,
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'local_part' => $check->localPart,
                'email' => $check->localPart.'@'.$domain->name,
                // Interna: nadie la conoce ni la usa (bcrypt en el formato que espera Dovecot)
                'password' => '{BLF-CRYPT}'.Hash::driver('bcrypt')->make(Str::random(48)),
                'quota_bytes' => $plan->quota_bytes,
                'tier' => $plan->tier,
                'active' => true,
                'status' => 'active',
                'can_send' => $user->hasVerifiedEmail(),
            ]);
        } catch (QueryException) {
            throw new RuntimeException('Ese nombre ya no está disponible. Elige otro.');
        }

        $reservation->delete();

        return $mailbox;
    }
}
