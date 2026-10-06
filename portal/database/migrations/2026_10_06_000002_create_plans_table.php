<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('features')->nullable();              // lista de textos para la tarjeta

            // Precio en céntimos, IVA incluido. 0 si es gratis.
            $table->boolean('is_free')->default(false);
            $table->unsignedInteger('price_cents')->default(0);
            $table->char('currency', 3)->default('EUR');
            $table->enum('interval', ['month', 'year'])->default('month');

            // Lo que da el plan al buzón
            $table->unsignedBigInteger('quota_bytes');
            $table->unsignedInteger('max_aliases')->default(0);
            $table->unsignedInteger('send_limit_per_hour');
            $table->string('tier', 16);                       // se copia a mailserver.mailboxes.tier

            $table->string('stripe_product_id')->nullable();
            $table->string('stripe_price_id')->nullable();

            // Visibilidad: absoluta (is_active) y programada (desde/hasta)
            $table->boolean('is_active')->default(true);
            $table->timestamp('available_from')->nullable();
            $table->timestamp('available_until')->nullable();

            $table->boolean('is_highlighted')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
