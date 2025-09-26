<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Google\Client as GoogleClient;
use Google\Service\Gmail;
use Exception;

class GenerateGmailAuthUrlCommand extends Command
{
    protected $signature = 'mail:generate-auth-url';
    protected $description = 'Genera la URL de autorización de Google para Gmail';

    public function handle()
    {
        try {
            $client = new GoogleClient();
            $client->setAuthConfig(storage_path('app/credentials.json'));
            $client->addScope(Gmail::GMAIL_READONLY);
            $client->setRedirectUri('https://rentacar.el-solitions.es/oauth2callback');
            $client->setAccessType('offline'); // Para obtener refresh token
            $client->setPrompt('consent');     // Forzar envío de refresh token

            $authUrl = $client->createAuthUrl();

            $this->info("Abre esta URL en tu navegador para autorizar la app:");
            $this->line($authUrl);
        } catch (Exception $e) {
            dd($e); // Muestra la excepción completa
        }
    }
}
