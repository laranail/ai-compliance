<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

final class ProviderDueDiligenceLapsedNotification extends Notification
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
            ->subject(__('laranail-ai-compliance::ai-compliance.notifications.due_diligence_subject'))
            ->line($this->message);
    }
}
