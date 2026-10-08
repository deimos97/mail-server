<?php

namespace App\Models;

use App\Models\Concerns\OnPortalDatabase;
use Illuminate\Database\Eloquent\Model;

/** Estado del ciclo de vida de un buzón (D-009): impago, suspensión, aviso por inactividad. */
class MailboxState extends Model
{
    use OnPortalDatabase;

    protected $primaryKey = 'mailbox_id';

    public $incrementing = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return ['unpaid_since' => 'datetime', 'suspended_at' => 'datetime', 'inactivity_warned_at' => 'datetime'];
    }

    public static function for(int $mailboxId): self
    {
        return static::firstOrNew(['mailbox_id' => $mailboxId]);
    }
}
