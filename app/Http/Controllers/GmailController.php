<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Center;
use Google\Client as GoogleClient;

class GmailController extends Controller
{
    public function redirectToGoogle(Center $center)
    {
        try {
            $client = new GoogleClient();
            $client->setClientId($center->mail_client_id);
            $client->setClientSecret($center->mail_client_secret);

            // Detecta entorno para usar la URL correcta
            $redirectUri = route('google.callback');
            $client->setRedirectUri($redirectUri);

            $client->setScopes([\Google\Service\Gmail::GMAIL_READONLY]);
            $client->setAccessType('offline');
            $client->setPrompt('consent');

            $authUrl = $client->createAuthUrl();

            return redirect($authUrl);
        } catch (\Throwable $e) {
            dd('Error en redirectToGoogle:', $e->getMessage(), $e->getTraceAsString());
        }
    }

    public function handleGoogleCallback(Request $request)
    {
        try {
            $code = $request->get('code');
            if (!$code) {
                dd('No se recibió el código de Google');
            }

            $centerId = $request->get('state'); // opcional
            $center = Center::findOrFail($centerId);

            $client = new GoogleClient();
            $client->setClientId($center->mail_client_id);
            $client->setClientSecret($center->mail_client_secret);
            $client->setRedirectUri(route('google.callback'));

            $token = $client->fetchAccessTokenWithAuthCode($code);

            if (isset($token['error'])) {
                dd('Error al obtener tokens: ' . $token['error_description']);
            }

            $center->update([
                'mail_access_token'     => $token['access_token'],
                'mail_refresh_token'    => $token['refresh_token'],
                'mail_token_expires_at' => now()->addSeconds($token['expires_in']),
                'mail_enable_integration' => 1,
                'mail_integration_status' => 'authorized'
            ]);

            return "Gmail autorizado correctamente para el centro {$center->name}!";
        } catch (\Throwable $e) {
            dd('Error en handleGoogleCallback:', $e->getMessage(), $e->getTraceAsString());
        }
    }
}
