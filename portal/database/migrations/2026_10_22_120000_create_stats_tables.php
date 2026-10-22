<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Estadísticas por día (hora de Madrid) para el panel del admin. Las escribe `mail-stats` (root, cada hora)
 * desde los logs de nginx y Postfix, que la web no puede leer; la web solo las lee.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stats_days', function (Blueprint $table) {
            $table->date('day')->primary();
            $table->unsignedInteger('visitors')->default(0);
            $table->unsignedInteger('pageviews')->default(0);
            $table->json('sources')->nullable();          // origen → visitantes (los 10 primeros)
            $table->unsignedInteger('sent')->default(0);       // entregados fuera
            $table->unsignedInteger('received')->default(0);   // entregados en buzones
            $table->unsignedInteger('rejected')->default(0);
            $table->unsignedInteger('bounced')->default(0);
            $table->unsignedBigInteger('disk_used_bytes')->nullable();   // foto de la última pasada del día
            $table->unsignedBigInteger('disk_total_bytes')->nullable();
            $table->unsignedBigInteger('vmail_bytes')->nullable();
            $table->timestamp('updated_at')->nullable();
        });

        Schema::create('stats_mailbox_days', function (Blueprint $table) {
            $table->date('day');
            $table->string('email', 320);
            $table->unsignedInteger('sent')->default(0);
            $table->unsignedInteger('received')->default(0);
            $table->primary(['day', 'email']);
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stats_mailbox_days');
        Schema::dropIfExists('stats_days');
    }
};
