<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OauthToken extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'access_expires_at' => 'datetime',
            'refresh_expires_at' => 'datetime',
            'revoked_at' => 'datetime',
        ];
    }

    public function mailbox()
    {
        return $this->belongsTo(Mailbox::class);
    }
}
