<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Solo local/tests. En producción: server/sql/2026-10-08-uso-de-buzones.sql (las escribe Dovecot). */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quota_usage', function (Blueprint $table) {
            $table->string('username')->primary();
            $table->unsignedBigInteger('bytes')->default(0);
            $table->unsignedInteger('messages')->default(0);
            $table->timestamp('updated_at')->useCurrent();
        });

        Schema::create('last_logins', function (Blueprint $table) {
            $table->string('username');
            $table->unsignedInteger('device')->default(0);
            $table->unsignedInteger('last_login');
            $table->primary(['username', 'device']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('last_logins');
        Schema::dropIfExists('quota_usage');
    }
};
