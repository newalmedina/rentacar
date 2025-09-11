<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    protected array $tables = [
        'customers',
        'items',
        'orders',
        'other_expense_items',
        'users',
        'other_expenses',
        'owners',
        'categories',
    ];

    public function up(): void
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) use ($tableName) {
                $table->unsignedBigInteger('center_id')->nullable()->after('id');
                $table->foreign('center_id')
                    ->references('id')
                    ->on('centers')
                    ->onDelete('set null');
            });
        }
    }

    public function down(): void
    {
        foreach ($this->tables as $tableName) {
            Schema::table($tableName, function (Blueprint $table) {
                $table->dropForeign([$table->getTable() . '_center_id_foreign'] ?? ['center_id']);
                $table->dropColumn('center_id');
            });
        }
    }
};
