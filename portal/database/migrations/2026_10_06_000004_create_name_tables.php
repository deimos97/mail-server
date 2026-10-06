<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Nombres que no se pueden coger (o que se reservan para la futura tienda)
        Schema::create('reserved_names', function (Blueprint $table) {
            $table->id();
            $table->string('local_part', 64)->unique();
            $table->enum('reason', ['sistema', 'marca', 'ofensivo', 'premium']);
            $table->unsignedInteger('price_cents')->nullable();   // futura tienda (premium)
            $table->string('notes')->nullable();
            $table->timestamps();
        });

        // Reglas de formato. Una fila global (domain_id NULL); en el futuro, una por dominio.
        Schema::create('name_rules', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('domain_id')->nullable()->unique();
            $table->unsignedTinyInteger('min_length')->default(1);
            $table->unsignedTinyInteger('max_length')->default(32);
            $table->string('allowed_symbols', 16)->default('._-'); // además de a-z y 0-9
            $table->boolean('forbid_edge_symbols')->default(true);    // ni al principio ni al final
            $table->boolean('forbid_consecutive_symbols')->default(true);
            $table->timestamps();
        });

        // Sobrecoste mensual por longitud del nombre (D-005): solo con planes de pago
        Schema::create('name_price_tiers', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('min_length');
            $table->unsignedTinyInteger('max_length');
            $table->unsignedInteger('price_cents');            // al mes, IVA incluido
            $table->string('stripe_price_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('name_price_tiers');
        Schema::dropIfExists('name_rules');
        Schema::dropIfExists('reserved_names');
    }
};
