<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Nombre retenido mientras alguien completa el alta, para que no se lo quite otro a mitad.
        Schema::create('mailbox_reservations', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('domain_id');
            $table->string('local_part', 64);
            $table->string('session_id', 255);   // token del alta (signup.token en la sesión), no el id de sesión
            $table->foreignId('user_id')->nullable()->constrained()->cascadeOnDelete();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->unique(['domain_id', 'local_part']);
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mailbox_reservations');
    }
};
