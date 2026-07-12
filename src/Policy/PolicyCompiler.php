<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Policy;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\FrontMatter\FrontMatterExtension;
use League\CommonMark\Extension\FrontMatter\Output\RenderedContentWithFrontMatter;
use League\CommonMark\MarkdownConverter;
use Psr\Log\LoggerInterface;
use Simtabi\Laranail\AiCompliance\Policy\Markdown\ShortcodeExtension;
use Simtabi\Laranail\AiCompliance\Policy\ValueObjects\CompiledPolicy;
use Simtabi\Laranail\AiCompliance\Policy\ValueObjects\PolicyFile;

/**
 * Compiles policy markdown to html: yaml frontmatter becomes meta,
 * [[shortcodes]] become <ai-c> elements, and raw html in the source is
 * escaped (admin-authored policies are data, not markup — the shortcode
 * vocabulary is the only interactive escape hatch). {{placeholders}} pass
 * through untouched; substitution happens at serve time.
 */
final class PolicyCompiler
{
    private ?MarkdownConverter $converter = null;

    public function __construct(
        private readonly ConfigRepository $config,
        private readonly LoggerInterface $logger,
    ) {}

    public function compile(PolicyFile $file): CompiledPolicy
    {
        $result = $this->converter()->convert($file->contents);

        $meta = $result instanceof RenderedContentWithFrontMatter
            ? (array) $result->getFrontMatter()
            : [];

        return new CompiledPolicy(
            html: trim($result->getContent()),
            meta: $meta,
            checksum: $file->checksum,
        );
    }

    /**
     * Compile a one-line markdown string (a consent short text) to html
     * without the wrapping paragraph element.
     */
    public function inline(string $markdown): string
    {
        $html = trim($this->converter()->convert($markdown)->getContent());

        if (str_starts_with($html, '<p>') && str_ends_with($html, '</p>')) {
            return substr($html, 3, -4);
        }

        return $html;
    }

    private function converter(): MarkdownConverter
    {
        if ($this->converter instanceof MarkdownConverter) {
            return $this->converter;
        }

        $environment = new Environment([
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
        ]);

        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new FrontMatterExtension);
        $environment->addExtension(new ShortcodeExtension($this->registeredShortcodes(), $this->logger));

        return $this->converter = new MarkdownConverter($environment);
    }

    /**
     * @return list<string>
     */
    private function registeredShortcodes(): array
    {
        $registered = $this->config->get('laranail.ai-compliance.shortcodes', []);

        return array_values(array_filter(
            is_array($registered) ? $registered : [],
            is_string(...),
        ));
    }
}
