<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

final class ActivityLogSilentNotification extends Notification
{
    public function __construct(
        private readonly string $message,
    ) {}

    /**
     * @return list<string>
     */
    public function via(): array
    {
        return ['mail'];
    }

    public function toMail(): MailMessage
    {
        return (new MailMessage)
            ->subject(__('laranail-ai-compliance::ai-compliance.notifications.log_silent_subject'))
            ->line($this->message)
            ->line(__('laranail-ai-compliance::ai-compliance.notifications.log_silent_hint'));
    }
}
