<?php

namespace App\Models;

use App\Models\Concerns\OnPortalDatabase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/** Ajustes que se cambian desde el admin sin desplegar. Valor por defecto en config/support.php y similares. */
class Setting extends Model
{
    use OnPortalDatabase;

    protected $primaryKey = 'key';

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];

    protected function casts(): array
    {
        return ['value' => 'json'];
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        $stored = Cache::remember("setting:{$key}", 300, fn () => static::find($key)?->value ?? ['_missing' => true]);

        return is_array($stored) && array_key_exists('_missing', $stored) ? $default : $stored;
    }

    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("setting:{$key}");
    }
}
