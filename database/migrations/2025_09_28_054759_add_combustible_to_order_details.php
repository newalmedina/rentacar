<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('order_details', function (Blueprint $table) {
            $table->decimal('fuel_delivery', 5, 2)->nullable()->after('end_kilometers');
            $table->decimal('fuel_return', 5, 2)->nullable()->after('fuel_delivery');
        });
    }

    public function down(): void
    {
        Schema::table('order_details', function (Blueprint $table) {
            $table->dropColumn(['fuel_delivery', 'fuel_return']);
        });
    }
};
