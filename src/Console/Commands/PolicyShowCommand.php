<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Console\Commands;

use Simtabi\Laranail\AiCompliance\AiCompliance;
use Simtabi\Laranail\AiCompliance\Policy\ValueObjects\PolicyContent;
use Simtabi\Laranail\Package\Tools\Commands\Command;

/**
 * Renders a compiled policy document in the terminal: title, resolution
 * details, the html stripped to text, and any unresolved placeholders the
 * operator still needs to fill.
 */
final class PolicyShowCommand extends Command
{
    protected $signature = 'laranail::ai-compliance.policy.show
                            {slug : the document slug, e.g. transparency or consent.ai_training}
                            {--locale= : locale to resolve (defaults to the app locale)}';

    protected $description = 'Render a compiled policy document in the terminal';

    public function handle(AiCompliance $aiCompliance): int
    {
        $slug = $this->argument('slug');
        $locale = $this->stringOption('locale');

        if (! is_string($slug) || $slug === '') {
            $this->error('a document slug is required');

            return self::FAILURE;
        }

        $document = $aiCompliance->policy($slug, $locale === '' ? null : $locale);

        if (! $document instanceof PolicyContent) {
            $this->error(sprintf('policy document [%s] not found', $slug));

            return self::FAILURE;
        }

        $this->components->info(sprintf(
            '%s  (%s · locale %s%s · version %s)',
            $document->title,
            $document->slug,
            $document->locale,
            $document->isFallback() ? sprintf(', requested %s', $document->requestedLocale) : '',
            $document->version ?? 'file',
        ));

        $this->line($this->toText($document->html));

        if ($document->unresolvedPlaceholders !== []) {
            $this->newLine();
            $this->components->warn(sprintf(
                'unresolved placeholders: %s',
                implode(', ', $document->unresolvedPlaceholders),
            ));
        }

        return self::SUCCESS;
    }

    private function toText(string $html): string
    {
        $text = preg_replace('/<(h[1-6]|p|li|blockquote|pre)\b[^>]*>/', "\n", $html) ?? $html;
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5);

        return trim((string) preg_replace("/\n{3,}/", "\n\n", $text));
    }
}
