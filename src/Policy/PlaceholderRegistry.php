<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Policy;

use Closure;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Simtabi\Laranail\AiCompliance\Policy\ValueObjects\SubstitutedText;

/**
 * Substitutes {{placeholder}} values into served policy text. Values come
 * from config laranail.ai-compliance.placeholders plus runtime resolvers
 * registered by the host app. Substitution never touches stored content, so
 * a config change takes effect without republishing. Placeholders without a
 * value — including the prose ones shipped in the templates, like
 * {{list the features, ...}} — stay in the output and are reported so the
 * operator (and later a checklist item) can see what still needs filling.
 */
final class PlaceholderRegistry
{
    /** @var array<string, Closure(): ?string> */
    private array $resolvers = [];

    public function __construct(private readonly ConfigRepository $config) {}

    /**
     * @param Closure(): ?string $resolver
     */
    public function register(string $key, Closure $resolver): void
    {
        $this->resolvers[$key] = $resolver;
    }

    public function substitute(string $text): SubstitutedText
    {
        $values = $this->values();

        $substituted = (string) preg_replace_callback(
            '/\{\{\s*([a-z][a-z0-9_]*)\s*\}\}/',
            static function (array $matches) use ($values): string {
                $value = $values[$matches[1]] ?? null;

                return $value ?? $matches[0];
            },
            $text,
        );

        return new SubstitutedText($substituted, $this->unresolvedIn($substituted));
    }

    /**
     * @return array<string, string|null>
     */
    private function values(): array
    {
        $configured = $this->config->get('laranail.ai-compliance.placeholders', []);
        $values = [];

        foreach (is_array($configured) ? $configured : [] as $key => $value) {
            if (is_string($key)) {
                $values[$key] = is_string($value) ? $value : null;
            }
        }

        foreach ($this->resolvers as $key => $resolver) {
            $values[$key] = $resolver();
        }

        return $values;
    }

    /**
     * @return list<string>
     */
    private function unresolvedIn(string $text): array
    {
        if (preg_match_all('/\{\{\s*([^{}]+?)\s*\}\}/', $text, $matches) === false) {
            return [];
        }

        return array_values(array_unique($matches[1]));
    }
}
