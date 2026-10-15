<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mailbox_states', function (Blueprint $table) {
            $table->unsignedTinyInteger('quota_warned_level')->nullable()->after('inactivity_warned_at');   // 80 o 100 (D-029)
        });
    }

    public function down(): void
    {
        Schema::table('mailbox_states', function (Blueprint $table) {
            $table->dropColumn('quota_warned_level');
        });
    }
};
