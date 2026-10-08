<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Uso de un buzón (BD `mailserver`). Lo escribe Dovecot (quota_clone) al cambiar el buzón; la web solo lee. */
class QuotaUsage extends Model
{
    protected $connection = 'mailserver';

    protected $table = 'quota_usage';

    protected $primaryKey = 'username';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['bytes' => 'integer', 'messages' => 'integer', 'updated_at' => 'datetime'];
    }
}
