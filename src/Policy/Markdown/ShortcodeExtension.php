<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Policy\Markdown;

use Psr\Log\LoggerInterface;
use League\CommonMark\Extension\ExtensionInterface;
use League\CommonMark\Environment\EnvironmentBuilderInterface;

/**
 * Registers the [[shortcode]] inline parser and its <ai-c> renderer on a
 * CommonMark environment. The vocabulary comes from
 * config laranail.ai-compliance.shortcodes.
 */
final readonly class ShortcodeExtension implements ExtensionInterface
{
    /**
     * @param list<string> $registered
     */
    public function __construct(
        private array $registered,
        private LoggerInterface $logger,
    ) {}

    public function register(EnvironmentBuilderInterface $environment): void
    {
        $environment->addInlineParser(new ShortcodeParser, 100);
        $environment->addRenderer(ShortcodeNode::class, new ShortcodeRenderer($this->registered, $this->logger));
    }
}
