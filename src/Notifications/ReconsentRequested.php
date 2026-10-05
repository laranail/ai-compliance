<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Notifications;

use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;
use Simtabi\Laranail\AiCompliance\Support\Translations;

final class ReconsentRequested extends Notification
{
    /**
     * @param list<string> $consentTypes the affected consent type slugs
     */
    public function __construct(
        private readonly array $consentTypes,
    ) {}

    /**
     * @return list<string>
     */
    public function via(): array
    {
        $channels = config('laranail.ai-compliance.reconsent.channels', ['mail']);

        return array_values(array_filter(
            is_array($channels) ? $channels : ['mail'],
            is_string(...),
        ));
    }

    public function toMail(): MailMessage
    {
        $message = (new MailMessage)
            ->subject(Translations::get('ai-compliance.notifications.reconsent_subject'))
            ->line(Translations::get('ai-compliance.strings.reconsent.title'));

        foreach ($this->consentTypes as $type) {
            $message->line('- ' . Translations::get('ai-compliance.consent_types.' . $type . '.label'));
        }

        $settingsPath = config('laranail.ai-compliance.placeholders.settings_path');

        if (is_string($settingsPath) && $settingsPath !== '') {
            $message->action(Translations::get('ai-compliance.strings.reconsent.review'), url($settingsPath));
        }

        return $message;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return ['consent_types' => $this->consentTypes];
    }
}
