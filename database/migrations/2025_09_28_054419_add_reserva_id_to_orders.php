<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('reserva_id')->nullable()->after('is_renting');

            // Si tienes tabla reservas y quieres relación:
            // $table->foreign('reserva_id')
            //       ->references('id')
            //       ->on('reservas')
            //       ->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Si creaste foreign:
            // $table->dropForeign(['reserva_id']);
            $table->dropColumn('reserva_id');
        });
    }
};
