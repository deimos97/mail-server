<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Solo local/tests. En producción: server/sql/2026-10-15-aliases-dueno-y-cuarentena.sql */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('aliases', function (Blueprint $table) {
            $table->unsignedInteger('mailbox_id')->nullable()->index();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('deleted_at')->nullable();
            $table->timestamp('release_at')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('aliases', fn (Blueprint $table) => $table->dropColumn(['mailbox_id', 'created_at', 'deleted_at', 'release_at']));
    }
};
