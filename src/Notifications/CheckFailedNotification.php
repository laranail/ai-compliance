<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Simtabi\Laranail\AiCompliance\Support\Translations;

final class CheckFailedNotification extends Notification
{
    public function __construct(
        private readonly string $itemKey,
        private readonly string $label,
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
            ->subject(Translations::get('ai-compliance.notifications.check_failed_subject', ['item' => $this->label]))
            ->line($this->label . ' (' . $this->itemKey . ')')
            ->line($this->message);
    }
}
