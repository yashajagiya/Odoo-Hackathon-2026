<?php

namespace App\Jobs;

use App\Models\Driver;
use App\Notifications\LicenseExpiryNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class VerifyDriverLicenses implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     */
    public int $timeout = 120;

    /**
     * Execute the job.
     *
     * Checks for driver licenses expiring at 30/15/7 day thresholds
     * and already-expired licenses, then queues notifications to the
     * associated user accounts.
     */
    public function handle(): void
    {
        $alertDays = [30, 15, 7];
        $notified = 0;

        // Check each threshold: 30, 15, and 7 days before expiry
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
                }
            }
        }

        // Check already-expired licenses (drivers not yet suspended)
        $expiredDrivers = Driver::where('license_expiry_date', '<', now()->toDateString())
            ->where('status', '!=', 'Suspended')
            ->whereNotNull('user_id')
            ->with('user')
            ->get();

        foreach ($expiredDrivers as $driver) {
            if ($driver->user) {
                $driver->user->notify(new LicenseExpiryNotification($driver, 0));
                $notified++;
            }
        }

        Log::info("VerifyDriverLicenses job complete. {$notified} notifications queued.");
    }
}
