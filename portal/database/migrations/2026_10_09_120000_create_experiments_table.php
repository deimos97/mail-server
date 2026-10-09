<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pruebas A/B (Fase 5). Variantes: [{key, weight, overrides: {"hero.title": "…"}}]; "control" no cambia nada
        Schema::create('experiments', function (Blueprint $table) {
            $table->id();
            $table->string('key', 40)->unique();      // va a PostHog como $feature/<key>
            $table->string('name');
            $table->text('description')->nullable();  // hipótesis y qué se mide
            $table->boolean('is_active')->default(false);
            $table->json('variants');
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('experiments');
    }
};
