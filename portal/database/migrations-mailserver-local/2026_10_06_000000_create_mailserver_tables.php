<?php

// SOLO PARA DESARROLLO LOCAL. Replica la BD `mailserver` del servidor (ver
// server/config/_effective/mailserver-schema.sql y server/sql/) en database/mailserver.sqlite.
// En producción esas tablas las gestiona root, no Laravel (server/sql/).
//
//   php artisan migrate --database=mailserver --path=database/migrations-mailserver-local

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected $connection = 'mailserver';

    public function up(): void
    {
        if (app()->isProduction()) {
            throw new RuntimeException('Esta migración es solo para desarrollo local.');
        }

        Schema::create('domains', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->unique();
            $table->boolean('active')->default(true);
            $table->boolean('public_signup')->default(false);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('mailboxes', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('domain_id');
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('plan_id')->nullable();
            $table->string('local_part', 64);
            $table->string('email', 320)->unique();
            $table->string('password');
            $table->unsignedBigInteger('quota_bytes')->default(1073741824);
            $table->string('tier', 16)->default('free');
            $table->boolean('active')->default(true);
            $table->enum('status', ['pending', 'active', 'suspended', 'deleted'])->default('active');
            $table->boolean('can_send')->default(true);
            $table->timestamp('created_at')->useCurrent();
            $table->foreign('domain_id')->references('id')->on('domains')->cascadeOnDelete();
        });

        Schema::create('app_passwords', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('mailbox_id');
            $table->string('name', 64);
            $table->char('selector', 6);
            $table->string('password');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamp('revoked_at')->nullable();
            $table->unique(['mailbox_id', 'selector']);
            $table->foreign('mailbox_id')->references('id')->on('mailboxes')->cascadeOnDelete();
        });

        Schema::create('aliases', function (Blueprint $table) {
            $table->increments('id');
            $table->unsignedInteger('domain_id');
            $table->string('source', 320)->unique();
            $table->text('destination');
            $table->boolean('active')->default(true);
            $table->foreign('domain_id')->references('id')->on('domains')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aliases');
        Schema::dropIfExists('app_passwords');
        Schema::dropIfExists('mailboxes');
        Schema::dropIfExists('domains');
    }
};
