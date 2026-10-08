<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Copias del correo pedidas desde Mi cuenta. El fichero lo prepara root (mail-provision process-exports)
        Schema::create('mailbox_exports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('mailbox_id');       // en la BD mailserver: sin clave foránea
            $table->string('status', 16)->default('pending'); // pending · ready · failed · expired
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->timestamp('ready_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->index(['mailbox_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mailbox_exports');
    }
};
