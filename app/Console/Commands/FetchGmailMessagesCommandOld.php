<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Center;
use App\Services\GmailService;

class FetchGmailMessagesCommandOld extends Command
{
    protected $signature = 'mail:fetch-gmail_old';
    protected $description = 'Obtiene los correos de Gmail de los centros con integración habilitada';

    public function handle(): int
    {
        $centers = Center::active()
            ->where('mail_enable_integration', true)
            ->where('mail_source', 'Gmail')
            ->get();

        if ($centers->isEmpty()) {
            $this->info('No hay centros con integración de Gmail habilitada.');
            return self::SUCCESS;
        }

        foreach ($centers as $center) {
            try {
                $gmailService = new GmailService($center);

                $messages = $gmailService->getMessagesLastMinutes(20);

                $this->info("{$center->name}: Se obtuvieron " . count($messages) . " mensajes");
            } catch (\Throwable $e) {
                $this->error("{$center->name}: " . $e->getMessage());
            }
        }


        return self::SUCCESS;
    }
}
