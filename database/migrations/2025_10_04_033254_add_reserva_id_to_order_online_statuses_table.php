<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('order_online_statuses', function (Blueprint $table) {
            // Agregamos el nuevo campo después de 'order_id'
            $table->unsignedBigInteger('reserva_id')->nullable()->after('order_id');

            // (Opcional) Si existe una tabla 'reservas', puedes agregar la relación foránea:
            // $table->foreign('reserva_id')->references('id')->on('reservas')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::table('order_online_statuses', function (Blueprint $table) {
            // Si agregaste la foreign key, primero elimínala:
            // $table->dropForeign(['reserva_id']);
            $table->dropColumn('reserva_id');
        });
    }
};
