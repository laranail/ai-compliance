<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Policy;

use FilesystemIterator;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Simtabi\Laranail\AiCompliance\Enums\PolicyType;
use Simtabi\Laranail\AiCompliance\Policy\ValueObjects\PolicyFile;
use SplFileInfo;

/**
 * Finds policy markdown files. The app-published directory (publish tag
 * laranail::ai-compliance-policies) is scanned before the package's shipped
 * files, so operator edits win per (locale, relative path). Layout is one
 * directory per locale: {root}/{locale}/consent/ai_training.md and so on.
 */
final class PolicyFileLoader
{
    /** @var array<string, array<string, PolicyFile>> */
    private array $index = [];

    public function __construct(
        private readonly ConfigRepository $config,
        private readonly string $packagePath,
    ) {}

    public function find(string $slug, string $locale): ?PolicyFile
    {
        return $this->indexFor($locale)[$slug] ?? null;
    }

    /**
     * @return list<PolicyFile>
     */
    public function all(string $locale): array
    {
        return array_values($this->indexFor($locale));
    }

    /**
     * Every locale that has at least one policy directory, across the app
     * and package roots.
     *
     * @return list<string>
     */
    public function locales(): array
    {
        $locales = [];

        foreach ([$this->packagePath, $this->overridePath()] as $root) {
            if (! is_dir($root)) {
                continue;
            }

            foreach (new FilesystemIterator($root, FilesystemIterator::SKIP_DOTS) as $entry) {
                if ($entry instanceof SplFileInfo && $entry->isDir()) {
                    $locales[] = $entry->getFilename();
                }
            }
        }

        $locales = array_values(array_unique($locales));
        sort($locales);

        return $locales;
    }

    /**
     * Forget the in-memory index (used by tests and the sync command after
     * files change mid-process).
     */
    public function flush(): void
    {
        $this->index = [];
    }

    /**
     * @return array<string, PolicyFile> keyed by slug
     */
    private function indexFor(string $locale): array
    {
        if (isset($this->index[$locale])) {
            return $this->index[$locale];
        }

        $files = [];

        // package files first so app files overwrite them in the merge below
        foreach ([$this->packagePath, $this->overridePath()] as $root) {
            $localeDir = $root . DIRECTORY_SEPARATOR . $locale;

            if (! is_dir($localeDir)) {
                continue;
            }

            foreach ($this->markdownFilesIn($localeDir) as $file) {
                $relative = $this->relativePath($localeDir, $file);
                $contents = (string) file_get_contents($file->getPathname());

                $files[$this->slugFor($relative)] = new PolicyFile(
                    slug: $this->slugFor($relative),
                    locale: $locale,
                    type: $this->typeFor($relative),
                    relativePath: $relative,
                    absolutePath: $file->getPathname(),
                    contents: $contents,
                    checksum: hash('sha256', $contents),
                );
            }
        }

        return $this->index[$locale] = $files;
    }

    /**
     * @return list<SplFileInfo>
     */
    private function markdownFilesIn(string $directory): array
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)
        );

        $files = [];

        foreach ($iterator as $file) {
            if ($file instanceof SplFileInfo && $file->isFile() && $file->getExtension() === 'md') {
                $files[] = $file;
            }
        }

        usort($files, static fn (SplFileInfo $a, SplFileInfo $b): int => strcmp($a->getPathname(), $b->getPathname()));

        return $files;
    }

    private function relativePath(string $localeDir, SplFileInfo $file): string
    {
        $relative = substr($file->getPathname(), strlen($localeDir) + 1);

        return str_replace(DIRECTORY_SEPARATOR, '/', $relative);
    }

    private function slugFor(string $relativePath): string
    {
        $withoutExtension = preg_replace('/\.md$/', '', $relativePath) ?? $relativePath;
        $segments = explode('/', $withoutExtension);

        // the disclosures/ directory maps to the singular disclosure. slug prefix
        if ($segments[0] === 'disclosures') {
            $segments[0] = 'disclosure';
        }

        return implode('.', $segments);
    }

    private function typeFor(string $relativePath): PolicyType
    {
        return match (explode('/', $relativePath)[0]) {
            'consent' => PolicyType::ConsentText,
            'disclosures' => PolicyType::Disclosure,
            default => PolicyType::Policy,
        };
    }

    private function overridePath(): string
    {
        $configured = $this->config->get('laranail.ai-compliance.policies.path');

        if (is_string($configured) && $configured !== '') {
            return $configured;
        }

        return resource_path('policies/ai-compliance');
    }
}
