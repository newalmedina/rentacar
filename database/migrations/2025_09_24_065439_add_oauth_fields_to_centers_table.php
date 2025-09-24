<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /*
    🔹 Campos agregados

        client_id → ID de la app (Google o Microsoft).

        client_secret → secreto de la app.

        tenant_id → solo aplica para Outlook (nullable para Gmail).

        access_token → token temporal.

        refresh_token → para renovar el access token.

        token_expires_at → guardar la expiración del access token.
    */
    public function up()
    {
        Schema::table('centers', function (Blueprint $table) {
            $table->string('mail_client_id')->nullable()->after('nif');
            $table->string('mail_client_secret')->nullable()->after('mail_client_id');
            $table->string('mail_tenant_id')->nullable()->after('mail_client_secret'); // Solo para Outlook
            $table->text('mail_access_token')->nullable()->after('mail_tenant_id');
            $table->text('mail_refresh_token')->nullable()->after('mail_access_token');
            $table->timestamp('mail_token_expires_at')->nullable()->after('mail_refresh_token');
            $table->boolean('mail_enable_integration')->default(0)->after('mail_token_expires_at');
            $table->enum('mail_source', ['Gmail', 'Outlook'])->default('Gmail')->after('mail_enable_integration');
        });
    }

    public function down()
    {
        Schema::table('centers', function (Blueprint $table) {
            $table->dropColumn([
                'mail_client_id',
                'mail_client_secret',
                'mail_tenant_id',
                'mail_access_token',
                'mail_refresh_token',
                'mail_enable_integration',
                'mail_token_expires_at',
                'mail_source',
            ]);
        });
    }
};
