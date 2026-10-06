<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->after('password');
            // Autenticación en dos pasos del admin (Filament). Se guardan cifrados con APP_KEY.
            $table->text('app_authentication_secret')->nullable()->after('is_admin');
            $table->text('app_authentication_recovery_codes')->nullable()->after('app_authentication_secret');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_admin', 'app_authentication_secret', 'app_authentication_recovery_codes']);
        });
    }
};
