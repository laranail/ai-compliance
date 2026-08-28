<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Listeners;

use Illuminate\Notifications\AnonymousNotifiable;
use Simtabi\Laranail\AiCompliance\Events\CheckFailed;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Simtabi\Laranail\AiCompliance\Notifications\CheckFailedNotification;
use Illuminate\Contracts\Notifications\Dispatcher as NotificationDispatcher;
use Simtabi\Laranail\AiCompliance\Notifications\ActivityLogSilentNotification;
use Simtabi\Laranail\AiCompliance\Notifications\ProviderDueDiligenceLapsedNotification;

/**
 * Routes failed checks to the configured alert inbox. Silence has its own
 * notification because it is the alarm the spec singles out; everything else
 * goes out as a generic check failure. Without an alert address the events
 * still fire — hosts can listen themselves.
 */
final readonly class SendCheckAlerts
{
    public function __construct(
        private ConfigRepository $config,
        private NotificationDispatcher $notifications,
    ) {}

    public function handle(CheckFailed $event): void
    {
        $mail = $this->config->get('laranail.ai-compliance.alerting.mail');

        if (! is_string($mail) || $mail === '') {
            return;
        }

        $notifiable = (new AnonymousNotifiable)->route('mail', $mail);

        $notification = match ($event->item->key) {
            'logging.activity_log_alive' => new ActivityLogSilentNotification($event->result->message),
            'vendors.due_diligence'      => new ProviderDueDiligenceLapsedNotification($event->result->message),
            default                      => new CheckFailedNotification($event->item->key, $event->item->label, $event->result->message),
        };

        $this->notifications->send($notifiable, $notification);
    }
}
