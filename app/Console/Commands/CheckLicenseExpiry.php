<?php

namespace App\Console\Commands;

use App\Models\Driver;
use App\Notifications\LicenseExpiryNotification;
use Illuminate\Console\Command;

class CheckLicenseExpiry extends Command
{
    protected $signature = 'licenses:check-expiry';

    protected $description = 'Check driver license expiry dates and send notifications at 30/15/7 days before expiry';

    public function handle(): int
    {
        $this->info('Checking driver license expiry dates...');

        $alertDays = [30, 15, 7];
        $notified = 0;

        foreach ($alertDays as $days) {
            $targetDate = now()->addDays($days)->toDateString();

            $drivers = Driver::where('license_expiry_date', $targetDate)
                ->whereNotNull('user_id')
                ->with('user')
                ->get();

            foreach ($drivers as $driver) {
                if ($driver->user) {
                    $driver->user->notify(new LicenseExpiryNotification($driver, $days));
                    $notified++;
                    $this->line("  → Notified {$driver->name}: license expires in {$days} days ({$driver->license_expiry_date->toDateString()})");
                }
            }
        }

        // Also check already expired licenses
        $expiredDrivers = Driver::where('license_expiry_date', '<', now()->toDateString())
            ->where('status', '!=', 'Suspended')
            ->whereNotNull('user_id')
            ->with('user')
            ->get();

        foreach ($expiredDrivers as $driver) {
            if ($driver->user) {
                $driver->user->notify(new LicenseExpiryNotification($driver, 0));
                $notified++;
                $this->warn("  → EXPIRED: {$driver->name} license expired on {$driver->license_expiry_date->toDateString()}");
            }
        }

        $this->info("License expiry check complete. {$notified} notifications sent.");

        return Command::SUCCESS;
    }
}
