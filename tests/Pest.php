<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Simtabi\Laranail\AiCompliance\Enums\PolicyType;
use Simtabi\Laranail\AiCompliance\Policy\PolicyFileLoader;
use Simtabi\Laranail\AiCompliance\Policy\ValueObjects\PolicyFile;
use Simtabi\Laranail\AiCompliance\Tests\TestCase;

uses(TestCase::class)->in('Feature', 'Unit');

/**
 * Create a throwaway app-override policy directory, point the loader at it,
 * and return its path. $files maps 'locale/relative/path.md' => contents.
 * Lives in the system temp dir; the os owns cleanup.
 *
 * @param  array<string, string>  $files
 */
function overridePolicyDir(array $files): string
{
    $dir = sys_get_temp_dir() . '/ai-compliance-test-' . bin2hex(random_bytes(6));

    foreach ($files as $relative => $contents) {
        $path = $dir . '/' . $relative;
        File::ensureDirectoryExists(dirname($path));
        File::put($path, $contents);
    }

    config()->set('laranail.ai-compliance.policies.path', $dir);
    app(PolicyFileLoader::class)->flush();

    return $dir;
}

/**
 * Build an in-memory policy file for compiler-level tests.
 */
function policyFile(string $contents, string $slug = 'test', string $locale = 'en', PolicyType $type = PolicyType::Policy): PolicyFile
{
    return new PolicyFile(
        slug: $slug,
        locale: $locale,
        type: $type,
        relativePath: str_replace('.', '/', $slug) . '.md',
        absolutePath: '/virtual/' . $slug . '.md',
        contents: $contents,
        checksum: hash('sha256', $contents),
    );
}
