<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_offers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained()->cascadeOnDelete();
            $table->string('label');                           // "-50 % lanzamiento"
            $table->enum('type', ['percent', 'amount']);
            $table->unsignedInteger('value');                  // % (1–100) o céntimos de descuento
            $table->enum('duration', ['once', 'repeating', 'forever'])->default('once');
            $table->unsignedSmallInteger('duration_months')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->unsignedInteger('max_redemptions')->nullable();
            $table->string('stripe_coupon_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['plan_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_offers');
    }
};
