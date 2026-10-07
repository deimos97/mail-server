<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/** Nombre retenido durante el alta (15 minutos, se renueva en cada paso). */
class MailboxReservation extends Model
{
    public const MINUTES = 15;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['expires_at' => 'datetime'];
    }

    #[Scope]
    protected function current(Builder $query): void
    {
        $query->where('expires_at', '>', now());
    }

    public function domain()
    {
        return $this->belongsTo(Domain::class);
    }

    public function email(): string
    {
        return $this->local_part.'@'.$this->domain->name;
    }
}
