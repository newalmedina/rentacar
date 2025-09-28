<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Hacemos customer_id nullable
            $table->unsignedBigInteger('customer_id')->nullable()->change();

            // Eliminamos campos que ya no se necesitan
            $table->dropColumn([
                'confirmed_by_amovens',
                'cancelled_by_amovens',
                'amovens_id'
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Revertimos customer_id a NOT NULL
            $table->unsignedBigInteger('customer_id')->nullable(false)->change();

            // Volvemos a crear los campos eliminados
            $table->datetime('confirmed_by_amovens')->nullable();
            $table->datetime('cancelled_by_amovens')->nullable();
            $table->string('amovens_id', 255)->nullable();
        });
    }
};
