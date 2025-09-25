<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Center;
use Google\Client as GoogleClient;

class GmailController extends Controller
{
    public function redirectToGoogle(Center $center)
    {
        $client = new GoogleClient();
        $client->setClientId($center->mail_client_id);
        $client->setClientSecret($center->mail_client_secret);
        $client->setRedirectUri(route('google.callback'));
        $client->setScopes([\Google\Service\Gmail::GMAIL_READONLY]);
        $client->setAccessType('offline'); // necesario para refresh_token
        $client->setPrompt('consent'); // fuerza a pedir refresh_token siempre

        $authUrl = $client->createAuthUrl();

        return redirect($authUrl);
    }

    public function handleGoogleCallback(Request $request)
    {
        $code = $request->get('code');
        if (!$code) {
            return "No se recibió el código de Google.";
        }

        // Aquí puedes decidir a qué centro pertenece el token.
        // Por ejemplo, puedes guardar un parámetro 'center_id' en la URL de autorización:
        $centerId = $request->get('state'); // opcional
        $center = Center::findOrFail($centerId);

        $client = new GoogleClient();
        $client->setClientId($center->mail_client_id);
        $client->setClientSecret($center->mail_client_secret);
        $client->setRedirectUri(route('google.callback'));

        $token = $client->fetchAccessTokenWithAuthCode($code);

        if (isset($token['error'])) {
            return "Error al obtener tokens: " . $token['error_description'];
        }

        $center->update([
            'mail_access_token'     => $token['access_token'],
            'mail_refresh_token'    => $token['refresh_token'],
            'mail_token_expires_at' => now()->addSeconds($token['expires_in']),
            'mail_enable_integration' => 1,
            'mail_integration_status' => 'authorized'
        ]);

        return "Gmail autorizado correctamente para el centro {$center->name}!";
    }
}
