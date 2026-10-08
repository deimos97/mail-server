<?php

namespace App\Support;

use App\Models\Domain;
use Illuminate\Validation\Rule;

/** Reglas del email de recuperación (alta y cambio): DNS válido, no desechable, no nuestro, no repetido. */
class RecoveryEmailRules
{
    public static function rules(): array
    {
        $ownDomains = Domain::query()->pluck('name')->all();

        return ['required', 'string', app()->runningUnitTests() ? 'email:rfc' : 'email:rfc,dns', 'max:255', 'indisposable',
            Rule::unique('users', 'email'),
            function (string $attribute, string $value, $fail) use ($ownDomains) {
                if (in_array(mb_strtolower(substr(strrchr($value, '@'), 1)), $ownDomains, true)) {
                    $fail('El email de recuperación tiene que ser de otro proveedor (Gmail, Outlook…).');
                }
            }];
    }

    public static function messages(string $field = 'email'): array
    {
        return [
            "$field.unique" => 'Ya existe una cuenta con este email.',
            "$field.indisposable" => 'No se admiten emails temporales. Usa tu email habitual.',
            "$field.email" => 'Revisa el email: no parece válido.',
        ];
    }
}
