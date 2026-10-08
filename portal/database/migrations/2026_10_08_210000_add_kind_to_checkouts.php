<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // signup: buzón nuevo (pendiente hasta pagar) · change: buzón que ya existe pasa de gratis a pago
        Schema::table('checkouts', fn (Blueprint $table) => $table->string('kind', 8)->default('signup')->after('plan_id'));
    }

    public function down(): void
    {
        Schema::table('checkouts', fn (Blueprint $table) => $table->dropColumn('kind'));
    }
};
