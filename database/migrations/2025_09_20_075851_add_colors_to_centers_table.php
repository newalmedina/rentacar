<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('centers', function (Blueprint $table) {
            $table->string('primary_color', 50)->after("nif")->default("#2D3748"); // Color hexadecimal con #
            $table->string('primary_color_soft', 50)->after("primary_color")->default("#b8b8b8"); // Color hexadecimal con #
            $table->string('secondary_color', 50)->after("primary_color_soft")->nullable();      // Color nullable
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('centers', function (Blueprint $table) {
            $table->dropColumn(['primary_color', 'secondary_color', 'primary_color_soft']);
        });
    }
};
