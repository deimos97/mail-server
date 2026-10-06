<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Password;

use function Laravel\Prompts\password;
use function Laravel\Prompts\text;

/**
 * Crea (o promueve) un administrador del panel /admin. La contraseña se pide sin eco y nunca
 * pasa por la línea de comandos. En el primer acceso, Filament obliga a configurar la 2FA.
 *
 *   sudo -u portal php8.3 artisan portal:make-admin
 */
class MakeAdmin extends Command
{
    protected $signature = 'portal:make-admin';

    protected $description = 'Crea o promueve un administrador del panel /admin';

    public function handle(): int
    {
        $email = text('Email del administrador', required: true,
            validate: fn (string $v) => Validator::make(['e' => $v], ['e' => 'email'])->fails() ? 'Email no válido.' : null);

        $user = User::firstOrNew(['email' => mb_strtolower($email)]);

        if ($user->exists) {
            $this->components->info('El usuario ya existe; se le dará acceso de administrador.');
        } else {
            $user->name = text('Nombre', default: 'Administrador', required: true);
        }

        if (! $user->exists || $this->confirm('¿Cambiar también su contraseña?', false)) {
            $user->password = password('Contraseña (mínimo 12 caracteres)', required: true,
                validate: fn (string $v) => Validator::make(['p' => $v], ['p' => Password::min(12)])->fails()
                    ? 'Mínimo 12 caracteres.' : null);
        }

        $user->is_admin = true;
        $user->save();

        $this->components->info("Administrador listo: {$user->email}. En el primer acceso a /admin se pedirá configurar la autenticación en dos pasos.");

        return self::SUCCESS;
    }
}
