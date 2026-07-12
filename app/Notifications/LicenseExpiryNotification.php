<?php

namespace App\Notifications;

use App\Models\Driver;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LicenseExpiryNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Driver $driver,
        public int $daysRemaining
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = $this->daysRemaining > 0
            ? "License Expiry Warning: {$this->daysRemaining} days remaining"
            : "URGENT: Driver License Has Expired";

        $message = (new MailMessage)
            ->subject($subject)
            ->greeting("Hello {$notifiable->name},");

        if ($this->daysRemaining > 0) {
            $message->line("This is a reminder that driver **{$this->driver->name}**'s license will expire in **{$this->daysRemaining} days**.")
                    ->line("**License Number:** {$this->driver->license_number}")
                    ->line("**Expiry Date:** {$this->driver->license_expiry_date->toDateString()}")
                    ->line('Please ensure the license is renewed before expiry to avoid dispatch restrictions.');
        } else {
            $message->line("**URGENT:** Driver **{$this->driver->name}**'s license has **expired**.")
                    ->line("**License Number:** {$this->driver->license_number}")
                    ->line("**Expired On:** {$this->driver->license_expiry_date->toDateString()}")
                    ->line('This driver has been blocked from trip assignments. Please renew the license immediately.')
                    ->error();
        }

        return $message
            ->action('View Driver Profile', url('/api/drivers/' . $this->driver->id))
            ->line('Thank you for using TransitOps.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'driver_id' => $this->driver->id,
            'driver_name' => $this->driver->name,
            'license_number' => $this->driver->license_number,
            'license_expiry_date' => $this->driver->license_expiry_date->toDateString(),
            'days_remaining' => $this->daysRemaining,
            'type' => $this->daysRemaining > 0 ? 'license_expiry_warning' : 'license_expired',
        ];
    }
}
