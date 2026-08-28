<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Providers;

use Illuminate\Http\Client\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Client\Factory as HttpFactory;
use Simtabi\Laranail\AiCompliance\Models\Provider;
use Simtabi\Laranail\AiCompliance\Enums\ActivityType;
use Simtabi\Laranail\AiCompliance\Models\ActivityEvent;
use Simtabi\Laranail\AiCompliance\Consent\ConsentManager;
use Simtabi\Laranail\AiCompliance\Events\InferenceLogged;
use Simtabi\Laranail\AiCompliance\Activity\ActivityRecorder;
use Illuminate\Contracts\Config\Repository as ConfigRepository;

/**
 * One consent-aware call to an ai provider. The do-not-train flag is
 * whatever the vendor supports, mapped in config
 * (laranail.ai-compliance.providers.do_not_train); it is injected whenever
 * the subject has NOT granted ai_training — denied, withdrawn, or simply
 * absent all count as no. Every send logs an inference event (no raw
 * prompts) and fires InferenceLogged. Hosts on their own sdk use options()
 * for the prepared flags and record() for the logging half.
 */
final class PendingProviderCall
{
    private Model|Authenticatable|string|null $subject = null;

    public function __construct(
        private readonly Provider $provider,
        private readonly ConsentManager $consent,
        private readonly ActivityRecorder $activity,
        private readonly HttpFactory $http,
        private readonly ConfigRepository $config,
        private readonly Dispatcher $events,
    ) {}

    public function forSubject(Model|Authenticatable|string|null $subject): self
    {
        $this->subject = $subject;

        return $this;
    }

    /**
     * Whether this call must carry the do-not-train flag.
     */
    public function doNotTrain(): bool
    {
        if ($this->subject === null) {
            return true; // no subject means no consent; never train by default
        }

        return ! $this->consent->granted($this->subject, 'ai_training');
    }

    /**
     * The vendor-specific request adjustments: ['headers' => [...],
     * 'body' => [...]] merged over whatever the host prepared.
     *
     * @return array{headers: array<string, string>, body: array<string, mixed>}
     */
    public function options(): array
    {
        $options = ['headers' => [], 'body' => []];

        if (! $this->doNotTrain()) {
            return $options;
        }

        $mapping = $this->mappingFor($this->provider->vendor);

        if (isset($mapping['header']) && is_string($mapping['header'])) {
            $options['headers'][$mapping['header']] = is_string($mapping['header_value'] ?? null)
                ? $mapping['header_value']
                : 'true';
        }

        if (isset($mapping['body']) && is_string($mapping['body'])) {
            $options['body'][$mapping['body']] = $mapping['body_value'] ?? false;
        }

        return $options;
    }

    /**
     * Send a json request through the wrapper: flags injected, inference
     * logged, InferenceLogged fired.
     *
     * @param array<string, mixed> $payload
     */
    public function send(string $method, string $url, array $payload = [], string $purpose = 'inference'): Response
    {
        $options = $this->options();

        $response = $this->http
            ->withHeaders($options['headers'])
            ->connectTimeout(10)
            ->timeout($this->timeoutSeconds())
            ->send($method, $url, ['json' => [...$payload, ...$options['body']]]);

        $this->record($purpose, ['status' => $response->status()]);

        return $response;
    }

    /**
     * The logging half alone, for hosts calling through their own sdk.
     *
     * @param array<string, mixed> $context
     */
    public function record(string $purpose, array $context = []): ActivityEvent
    {
        $event = $this->activity->record(
            ActivityType::Inference,
            subject: $this->subject instanceof Authenticatable && ! $this->subject instanceof Model ? null : $this->subject,
            context: [
                'provider'     => $this->provider->name,
                'vendor'       => $this->provider->vendor,
                'model'        => $this->provider->model_name,
                'purpose'      => $purpose,
                'do_not_train' => $this->doNotTrain(),
                ...$context,
            ],
            providerId: (int) $this->provider->id,
        );

        $this->events->dispatch(new InferenceLogged($event));

        return $event;
    }

    private function timeoutSeconds(): int
    {
        $seconds = $this->config->get('laranail.ai-compliance.providers.timeout', 120);

        return is_int($seconds) && $seconds > 0 ? $seconds : 120;
    }

    /**
     * @return array<string, mixed>
     */
    private function mappingFor(string $vendor): array
    {
        $mappings = $this->config->get('laranail.ai-compliance.providers.do_not_train', []);
        $mapping = is_array($mappings) ? ($mappings[strtolower($vendor)] ?? null) : null;

        return is_array($mapping) ? $mapping : [];
    }
}
