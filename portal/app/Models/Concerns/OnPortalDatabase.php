<?php

namespace App\Models\Concerns;

/**
 * Para modelos de la BD `portal` que se cargan como relación desde un modelo de `mailserver`
 * (p. ej. Mailbox → plan, Mailbox → user). Eloquent da a la relación la conexión del modelo
 * padre si el relacionado no declara la suya, y buscaría `plans` en `mailserver`.
 */
trait OnPortalDatabase
{
    public function getConnectionName(): ?string
    {
        return $this->connection ?? config('database.default');
    }
}
