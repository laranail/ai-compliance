<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Checks\Builtin;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Simtabi\Laranail\AiCompliance\Checks\Check;
use Simtabi\Laranail\AiCompliance\Checks\CheckResult;

/**
 * Probes the app's own robots.txt (and llms.txt) for a declared ai-crawler
 * stance. The files signal preference, not enforcement; this check only
 * verifies the operator has published one.
 */
final readonly class CrawlerSignalsCheck implements Check
{
    private const array AI_CRAWLERS = ['GPTBot', 'ClaudeBot', 'Google-Extended', 'CCBot'];

    public function __construct(
        private HttpFactory $http,
        private ConfigRepository $config,
    ) {}

    public function key(): string
    {
        return 'consent.crawler_signals';
    }

    public function run(): CheckResult
    {
        $base = rtrim((string) $this->config->get('app.url', ''), '/');

        try {
            $robots = $this->http->connectTimeout(5)->timeout(10)->get($base.'/robots.txt');
        } catch (ConnectionException) {
            return CheckResult::fail('robots.txt is unreachable at '.$base);
        }

        if (! $robots->successful()) {
            return CheckResult::fail('robots.txt is not served (status '.$robots->status().')');
        }

        $mentioned = array_values(array_filter(
            self::AI_CRAWLERS,
            static fn (string $agent): bool => stripos($robots->body(), $agent) !== false,
        ));

        $llms = '';

        try {
            $llmsResponse = $this->http->connectTimeout(5)->timeout(10)->get($base.'/llms.txt');
            $llms = $llmsResponse->successful() ? ' llms.txt is published.' : '';
        } catch (ConnectionException) {
            // optional file; unreachable is fine
        }

        if ($mentioned === []) {
            return CheckResult::review('robots.txt is served but states no ai-crawler policy (GPTBot, ClaudeBot, ...).'.$llms);
        }

        return CheckResult::ok('robots.txt declares a stance for: '.implode(', ', $mentioned).'.'.$llms);
    }
}
