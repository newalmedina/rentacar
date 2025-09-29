<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('centers', function (Blueprint $table) {
            // agregar después de 'nif'
            $table->text('start_message')->nullable()->after('nif');
            $table->text('end_message')->nullable()->after('start_message');
            $table->boolean('enable_start_message')->default(0)->after('start_message');
            $table->boolean('enable_end_message')->default(0)->after('end_message');

            // eliminar columnas no necesarias
            $table->dropColumn([
                'mail_client_id',
                'mail_client_secret',
                'mail_tenant_id',
                'mail_access_token',
                'mail_refresh_token',
                'mail_token_expires_at',
                'mail_source',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('centers', function (Blueprint $table) {
            // restaurar columnas eliminadas
            $table->string('mail_client_id')->nullable();
            $table->string('mail_client_secret')->nullable();
            $table->string('mail_tenant_id')->nullable();
            $table->text('mail_access_token')->nullable();
            $table->text('mail_refresh_token')->nullable();
            $table->timestamp('mail_token_expires_at')->nullable();
            $table->string('mail_source')->nullable();

            // eliminar columnas nuevas
            $table->dropColumn(['start_message', 'end_message', 'enable_start_message', 'enable_end_message']);
        });
    }
};
