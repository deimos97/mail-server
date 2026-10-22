<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Correos enviados fuera y recibidos por un buzón en un día. Lo escribe `mail-stats` (root); la web solo lee. */
class StatsMailboxDay extends Model
{
    public $incrementing = false;

    public $timestamps = false;

    protected $guarded = [];

    protected $attributes = ['sent' => 0, 'received' => 0];

    protected function casts(): array
    {
        return ['day' => 'date', 'sent' => 'integer', 'received' => 'integer'];
    }
}
