<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fuel_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });

        Schema::table('items', function (Blueprint $table) {
            $table->foreignId('fuel_type_id')
                ->nullable()
                ->after('category_id')
                ->constrained('fuel_types')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropForeign(['fuel_type_id']);
            $table->dropColumn('fuel_type_id');
        });

        Schema::dropIfExists('fuel_types');
    }
};
