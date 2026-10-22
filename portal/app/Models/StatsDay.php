<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Visitas, correo y disco de un día (hora de Madrid). Lo escribe `mail-stats` (root); la web solo lee. */
class StatsDay extends Model
{
    protected $primaryKey = 'day';

    protected $keyType = 'string';

    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];

    protected $attributes = ['visitors' => 0, 'pageviews' => 0, 'sent' => 0, 'received' => 0, 'rejected' => 0, 'bounced' => 0];

    protected function casts(): array
    {
        return [
            'day' => 'date', 'sources' => 'array', 'updated_at' => 'datetime',
            'visitors' => 'integer', 'pageviews' => 'integer', 'sent' => 'integer', 'received' => 'integer', 'rejected' => 'integer', 'bounced' => 'integer',
            'disk_used_bytes' => 'integer', 'disk_total_bytes' => 'integer', 'vmail_bytes' => 'integer',
        ];
    }
}
