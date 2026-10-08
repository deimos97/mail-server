<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Solo local/tests. En producción: server/sql/2026-10-08-mailboxes-release-at.sql */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mailboxes', fn (Blueprint $table) => $table->timestamp('release_at')->nullable());
    }

    public function down(): void
    {
        Schema::table('mailboxes', fn (Blueprint $table) => $table->dropColumn('release_at'));
    }
};
