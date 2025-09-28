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
    // public int $minutesAgo = 9000;
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

        // Filtro: últimos X minutos y remitente específico
        // $query = "after:$timestamp from:ing.newal.medina@gmail.com";
        $query = "after:$timestamp from:noreply@amovens.com";

        $messagesList = $this->gmail->users_messages->listUsersMessages('me', [
            'q' => $query,
            'maxResults' => 50
        ]);

        $messages = [];

        if ($messagesList->getMessages()) {
            foreach ($messagesList->getMessages() as $message) {
                $msg = $this->gmail->users_messages->get('me', $message->getId());

                // Obtener 'To' desde los headers
                $headers = $msg->getPayload()->getHeaders();
                $to = null;
                $subject = null;
                foreach ($headers as $header) {
                    if ($header->getName() == 'To') {
                        $to = $header->getValue();
                    }
                    if ($header->getName() == 'Delivered-To') {
                        $to = $header->getValue();
                    }

                    if ($header->getName() == 'Subject') {
                        $subject = $header->getValue();
                    }
                }

                // Obtener el cuerpo del mensaje (texto plano)
                $body = $this->getMessageBody($msg->getPayload());
                $messages[] = (object)[
                    'id' => $msg->getId(),
                    'to' => $to,
                    'subject' => $subject,
                    'body' => $body['body'],
                    'clean_body' => $body['clean_body'],
                    'snippet' => $msg->getSnippet(),
                    'threadId' => $msg->getThreadId(),
                    'received_at' => Carbon::createFromTimestampMs($msg->getInternalDate()), // ← aquí
                ];
            }
        }

        return $messages;
    }


    /**
     * Extrae y limpia el cuerpo del mensaje recursivamente
     * Devuelve HTML completo y texto plano conservando saltos de línea
     */
    protected function getMessageBody($payload): array
    {
        $html = '';

        // Recorrer partes recursivamente para obtener HTML
        if ($payload->getMimeType() === 'text/html' && $payload->getBody() && $payload->getBody()->getData()) {
            $html = base64_decode(strtr($payload->getBody()->getData(), '-_', '+/'));
        } elseif ($payload->getParts()) {
            foreach ($payload->getParts() as $part) {
                $html = $this->getMessageBody($part)['body'];
                if (!empty($html)) {
                    break;
                }
            }
        }

        // Si no hay HTML, intentar texto plano
        if (empty($html) && $payload->getMimeType() === 'text/plain' && $payload->getBody() && $payload->getBody()->getData()) {
            $html = nl2br(base64_decode(strtr($payload->getBody()->getData(), '-_', '+/')));
        }

        // Texto limpio (sin HTML)
        $cleanText = '';
        if (!empty($html)) {
            libxml_use_internal_errors(true);
            $dom = new \DOMDocument();
            $dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));

            $bodyNode = $dom->getElementsByTagName('body')->item(0);
            $cleanText = $bodyNode ? $bodyNode->textContent : '';
            $cleanText = preg_replace("/[ \t]+/", ' ', $cleanText); // espacios múltiples → 1
            $cleanText = trim($cleanText);                           // quitar espacios al inicio y final
        }

        return [
            'body' => $html,      // TODO HTML completo, sin filtrar atributos
            'clean_body' => $cleanText,
        ];
    }
}
