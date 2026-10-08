<?php

namespace App\Models;

use App\Models\Concerns\OnPortalDatabase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Un Price de Stripe que ha tenido un plan (el actual y los antiguos, que conservan sus suscriptores). */
class PlanPrice extends Model
{
    use OnPortalDatabase;

    protected $guarded = ['id'];

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    /** El plan al que pertenece un Price de Stripe, sea el actual o uno antiguo. */
    public static function planFor(?string $stripePriceId): ?Plan
    {
        return $stripePriceId ? static::with('plan')->where('stripe_price_id', $stripePriceId)->first()?->plan : null;
    }
}
