<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Todos los Price de Stripe que ha tenido cada plan: un cambio de precio crea uno nuevo y los
        // suscriptores antiguos conservan el suyo (D-007), así que hace falta saber de qué plan es cada uno
        Schema::create('plan_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->string('stripe_price_id')->unique();
            $table->unsignedInteger('price_cents');
            $table->string('interval', 8);
            $table->timestamps();
        });

        // Sobrecoste de nombre corto: un Price mensual y otro anual (en una suscripción todo va al mismo intervalo)
        Schema::table('name_price_tiers', function (Blueprint $table) {
            $table->string('stripe_product_id')->nullable()->after('price_cents');
            $table->string('stripe_price_year_id')->nullable()->after('stripe_price_id');
        });

        // Webhooks ya procesados (Stripe puede repetirlos)
        Schema::create('stripe_events', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->string('type');
            $table->timestamp('created_at')->useCurrent();
        });

        // Pagos iniciados desde el alta: buzón pendiente ↔ sesión de Checkout
        Schema::create('checkouts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('mailbox_id');           // en mailserver, status = pending hasta pagar
            $table->foreignId('plan_id')->constrained();
            $table->string('stripe_session_id')->nullable()->unique();
            $table->string('status', 16)->default('open');      // open · completed · expired
            $table->timestamp('immediate_start_consent_at');    // desistimiento: pidió empezar ya
            $table->timestamps();
        });

        // Estado del ciclo de vida de cada buzón (D-009), en la BD de la web
        Schema::create('mailbox_states', function (Blueprint $table) {
            $table->unsignedBigInteger('mailbox_id')->primary();
            $table->timestamp('unpaid_since')->nullable();         // impago o cancelación que no cabe en gratis
            $table->timestamp('suspended_at')->nullable();
            $table->timestamp('inactivity_warned_at')->nullable(); // gratis sin uso: aviso enviado
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mailbox_states');
        Schema::dropIfExists('checkouts');
        Schema::dropIfExists('stripe_events');
        Schema::table('name_price_tiers', fn (Blueprint $table) => $table->dropColumn(['stripe_product_id', 'stripe_price_year_id']));
        Schema::dropIfExists('plan_prices');
    }
};
