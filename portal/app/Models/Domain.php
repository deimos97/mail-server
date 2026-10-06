<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Dominio de correo (BD `mailserver`, la que leen Postfix y Dovecot).
 * La web solo puede cambiar public_signup y sort_order; los dominios los da de alta root
 * (requieren DNS y DKIM).
 */
class Domain extends Model
{
    protected $connection = 'mailserver';

    public $timestamps = false;

    protected $fillable = ['public_signup', 'sort_order'];

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'public_signup' => 'boolean',
            'created_at' => 'datetime',
        ];
    }

    /** Dominios que se ofrecen en el alta. */
    #[Scope]
    protected function signup(Builder $query): void
    {
        $query->where('active', true)->where('public_signup', true)->orderBy('sort_order')->orderBy('name');
    }
}
