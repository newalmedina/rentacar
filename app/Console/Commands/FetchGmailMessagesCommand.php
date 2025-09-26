<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\GmailService;

class FetchGmailMessagesCommand extends Command
{
    protected $signature = 'mail:fetch-gmail';
    protected $description = 'Obtiene los correos recientes de Gmail';

    public function handle()
    {
        try {
            $gmailService = new GmailService();
            $messages = $gmailService->fetchRecentMessages();

            $this->info("Se encontraron " . count($messages) . " mensajes recientes:");

            foreach ($messages as $msg) {
                $this->line("{$msg['id']}: {$msg['snippet']}");
            }

            $this->info("-------");
            $this->info("Proceso terminado.");
        } catch (\Exception $e) {
            dd($e);
            $this->error("Error: " . $e->getMessage());
        }
    }
}
