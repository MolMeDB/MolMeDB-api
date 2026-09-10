<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Minishlink\WebPush\VAPID;

class GenerateWebPushVapidKeys extends Command
{
    protected $signature = 'webpush:vapid';

    protected $description = 'Generates a VAPID public/private key pair for web push and prints the .env lines to add.';

    public function handle(): int
    {
        $keys = VAPID::createVapidKeys();

        $this->line('Add these to your .env file:');
        $this->newLine();
        $this->line("VAPID_PUBLIC_KEY={$keys['publicKey']}");
        $this->line("VAPID_PRIVATE_KEY={$keys['privateKey']}");
        $this->newLine();
        $this->line('VAPID_PUBLIC_KEY also needs to be exposed to the frontend as NEXT_PUBLIC_VAPID_PUBLIC_KEY.');

        return self::SUCCESS;
    }
}
