<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

/** Nombre que no se puede coger: de sistema, de marca, ofensivo o premium (futura tienda). */
class ReservedName extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['price_cents' => 'integer'];
    }

    protected function localPart(): Attribute
    {
        return Attribute::make(set: fn (string $value) => mb_strtolower(trim($value)));
    }
}
