<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // En el alta solo se pide email de recuperación y contraseña
            $table->string('name')->nullable()->change();
            $table->timestamp('terms_accepted_at')->nullable()->after('email_verified_at');
            $table->string('signup_ip', 45)->nullable()->after('terms_accepted_at');
            // De dónde vino (UTMs, click IDs, referrer): App\Http\Middleware\CaptureAttribution
            $table->json('attribution')->nullable()->after('signup_ip');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['terms_accepted_at', 'signup_ip', 'attribution']);
            $table->string('name')->nullable(false)->change();
        });
    }
};
