<?php

namespace App\Console\Commands;

use App\Jobs\VerifyDriverLicenses;
use Illuminate\Console\Command;

class CheckLicenseExpiry extends Command
{
    protected $signature = 'compliance:verify-licenses';

    protected $description = 'Verify driver license expiry dates and queue notifications at 30/15/7 days before expiry';

    public function handle(): int
    {
        $this->info('Dispatching license verification job to the queue...');

        VerifyDriverLicenses::dispatch();

        $this->info('License verification job dispatched successfully. Notifications will be sent via the queue.');

        return Command::SUCCESS;
    }
}
