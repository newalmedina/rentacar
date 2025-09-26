<?php

namespace App\Services;

use Google\Client as GoogleClient;
use Google\Service\Gmail;
use Carbon\Carbon;

class GmailService
{
    protected GoogleClient $client;
    protected Gmail $gmail;

    // Tiempo en minutos para filtrar correos recientes
    public int $minutesAgo = 20;

    public function __construct()
    {
        $this->initClient();
    }

    protected function initClient(): void
    {
        $this->client = new GoogleClient();
        $this->client->setAuthConfig(storage_path('app/credentials.json'));
        $this->client->addScope(Gmail::GMAIL_READONLY);

        // Archivo único para guardar el token
        $tokenPath = storage_path('app/token.json');

        if (!file_exists($tokenPath)) {
            throw new \Exception(
                "No se encontró token de Google. Primero autoriza la app en el navegador accediendo a /oauth2callback."
            );
        }

        $accessToken = json_decode(file_get_contents($tokenPath), true);
        $this->client->setAccessToken($accessToken);

        // Renovación automática usando refresh token
        if ($this->client->isAccessTokenExpired()) {
            if ($this->client->getRefreshToken()) {
                $newToken = $this->client->fetchAccessTokenWithRefreshToken($this->client->getRefreshToken());
                file_put_contents($tokenPath, json_encode($this->client->getAccessToken()));
            } else {
                throw new \Exception(
                    "El token de acceso ha expirado y no hay refresh token disponible. Reautoriza la app en /oauth2callback."
                );
            }
        }

        $this->gmail = new Gmail($this->client);
    }

    /**
     * Obtener correos recientes según minutesAgo
     *
     * @return array
     */
    public function fetchRecentMessages(): array
    {
        $timestamp = Carbon::now()->subMinutes($this->minutesAgo)->timestamp;
        $query = "after:$timestamp";

        $messagesList = $this->gmail->users_messages->listUsersMessages('me', [
            'q' => $query,
            'maxResults' => 50
        ]);

        $messages = [];

        if ($messagesList->getMessages()) {
            foreach ($messagesList->getMessages() as $message) {
                $msg = $this->gmail->users_messages->get('me', $message->getId());
                $messages[] = [
                    'id' => $msg->getId(),
                    'snippet' => $msg->getSnippet(),
                    'threadId' => $msg->getThreadId()
                ];
            }
        }

        return $messages;
    }
}
