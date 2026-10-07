<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Proveedor OAuth2 propio para el login único del webmail (D-010). Un solo cliente (Roundcube),
 * configurado en config/oauth.php. Los códigos y tokens se guardan solo como hash SHA-256.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('oauth_codes', function (Blueprint $table) {
            $table->id();
            $table->char('code_hash', 64)->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('mailbox_id');                 // mailserver.mailboxes.id
            $table->string('redirect_uri', 500);
            $table->string('code_challenge', 128)->nullable();      // PKCE (opcional)
            $table->string('code_challenge_method', 10)->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('used_at')->nullable();
            $table->timestamps();
        });

        Schema::create('oauth_tokens', function (Blueprint $table) {
            $table->id();
            $table->char('access_hash', 64)->unique();
            $table->char('refresh_hash', 64)->nullable()->unique();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('mailbox_id');
            $table->foreignId('code_id')->nullable()->constrained('oauth_codes')->nullOnDelete();
            $table->timestamp('access_expires_at');
            $table->timestamp('refresh_expires_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->timestamps();

            $table->index(['mailbox_id', 'revoked_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('oauth_tokens');
        Schema::dropIfExists('oauth_codes');
    }
};
