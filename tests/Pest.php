<?php

declare(strict_types=1);

use Simtabi\Laranail\AiCompliance\Enums\PolicyType;
use Simtabi\Laranail\AiCompliance\Policy\ValueObjects\PolicyFile;
use Simtabi\Laranail\AiCompliance\Tests\TestCase;

uses(TestCase::class)->in('Feature', 'Unit');

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
