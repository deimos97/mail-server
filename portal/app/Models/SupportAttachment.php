<?php

namespace App\Models;

use App\Models\Concerns\OnPortalDatabase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Imagen adjunta a un mensaje. Vive en el disco privado (storage/app/private/support); solo la ve el admin. */
class SupportAttachment extends Model
{
    use OnPortalDatabase;

    protected $guarded = ['id'];

    public function message(): BelongsTo
    {
        return $this->belongsTo(SupportMessage::class, 'support_message_id');
    }
}
