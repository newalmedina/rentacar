<?php

namespace App\Services;

use Google\Client as GoogleClient;
use Google\Service\Gmail;
use App\Models\Center;

class GmailService
{
    protected GoogleClient $client;
    protected ?Gmail $gmail = null;
    protected Center $center;

    public function __construct(Center $center)
    {
        $this->center = $center;

        $this->client = new GoogleClient();
        $this->client->setClientId($center->mail_client_id);
        $this->client->setClientSecret($center->mail_client_secret);
        $this->client->setAccessType('offline');
        $this->client->setScopes([Gmail::GMAIL_READONLY]);

        // Si existe refresh_token, intentamos refrescar
        if ($center->mail_refresh_token) {
            if (!$center->mail_token_expires_at || $center->mail_token_expires_at->lt(now()->addMinutes(1))) {
                // Refresca el token si está expirado o casi expirado
                $this->client->refreshToken($center->mail_refresh_token);
                $this->updateTokensInModel(); // actualizar en BD inmediatamente
            }
        } else {
            dump(" el centro " . $center->name . ", no tiene token asignalo por primera vez");
            // Sin refresh_token → necesita autenticación
            $this->center->update(['mail_integration_status' => 'needs_auth']);
        }
    }

    /**
     * Obtiene los mensajes de los últimos X minutos.
     */
    public function getMessagesLastMinutes(int $minutes = 20): array
    {
        if (! $this->gmail) return [];

        $after = strtotime("-{$minutes} minutes");
        $response = $this->gmail->users_messages->listUsersMessages('me', [
            'q' => "after:{$after}",
            'maxResults' => 50,
        ]);

        $messages = [];
        if ($response->getMessages()) {
            foreach ($response->getMessages() as $msg) {
                $messages[] = $this->gmail->users_messages->get('me', $msg->id);
            }
        }

        return $messages;
    }

    /**
     * Guarda los tokens actualizados en el modelo.
     */
    protected function updateTokensInModel(): void
    {
        $tokenData = $this->client->getAccessToken();

        if (! empty($tokenData['access_token'])) {
            $this->center->update([
                'mail_access_token'     => $tokenData['access_token'],
                'mail_token_expires_at' => now()->addSeconds($tokenData['expires_in'] ?? 3600),
                'mail_refresh_token'    => $tokenData['refresh_token'] ?? $this->center->mail_refresh_token,
            ]);
        }
    }
}
