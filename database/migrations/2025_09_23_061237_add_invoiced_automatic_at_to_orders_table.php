<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('invoiced_automatic')
                ->default(0)
                ->after('invoiced');
            $table->dateTime('confirmed_by_amovens')
                ->nullable()
                ->after('invoiced_automatic');

            $table->dateTime('cancelled_by_amovens')
                ->nullable()
                ->after('confirmed_by_amovens');

            $table->string('amovens_id')
                ->nullable()
                ->after('cancelled_by_amovens');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('invoiced_automatic');
            $table->dropColumn('confirmed_by_amovens');
            $table->dropColumn('cancelled_by_amovens');
            $table->dropColumn('amovens_id');
        });
    }
};
