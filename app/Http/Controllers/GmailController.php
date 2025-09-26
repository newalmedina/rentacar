<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Google\Client as GoogleClient;
use Google\Service\Gmail;

class GmailController extends Controller
{
    public function oauthCallback(Request $request)
    {
        $code = $request->query('code');

        if (!$code) {
            return 'No se recibió código de autorización.';
        }

        $client = new GoogleClient();
        $client->setAuthConfig(storage_path('app/credentials.json'));
        $client->setRedirectUri('https://rentacar.el-solitions.es/oauth2callback');
        $client->addScope(Gmail::GMAIL_READONLY);
        $client->setAccessType('offline'); // Para obtener refresh token
        $client->setPrompt('consent');     // Forzar envío de refresh token

        try {
            $token = $client->fetchAccessTokenWithAuthCode($code);
            file_put_contents(storage_path('app/token.json'), json_encode($token));

            return 'Autorización completada. Token guardado correctamente.';
        } catch (\Exception $e) {
            dd($e); // Muestra toda la excepción
        }
    }
}
