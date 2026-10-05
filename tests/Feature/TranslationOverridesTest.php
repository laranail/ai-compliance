<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Simtabi\Laranail\AiCompliance\Support\Translations;

/*
 * The package's own strings are looked up through Support\Translations, which reads the canonical
 * `laranail/ai-compliance::` namespace -- whose overrides live in lang/vendor/laranail/ai-compliance,
 * where `vendor:publish --tag=laranail::ai-compliance-translations` writes them -- and still honours
 * an override made against the `laranail-ai-compliance::` namespace the package translated through
 * before. Each test drives the real lookup paths: Translations itself, a rendered Blade component,
 * a notification, and the boot payload's whole-group `strings` and per-key consent labels.
 */

uses(RefreshDatabase::class);

/**
 * Write `ai-compliance` override files into lang/vendor/<dir>/en, run $test, and remove them.
 *
 * @param array<string, array<string, mixed>> $overrides lang/vendor sub-directory => lines
 */
function withAiComplianceLangOverrides(array $overrides, Closure $test): void
{
    foreach ($overrides as $dir => $lines) {
        $path = lang_path("vendor/{$dir}/en");
        File::ensureDirectoryExists($path);
        File::put($path . '/ai-compliance.php', '<?php return ' . var_export($lines, true) . ';');
    }

    app('translator')->setLoaded([]);

    try {
        $test();
    } finally {
        File::deleteDirectory(lang_path('vendor'));
        app('translator')->setLoaded([]);
    }
}

function renderedDisclosureBadge(): string
{
    return (string) test()->blade('<x-laranail-ai-compliance::disclosure surface="chat" />');
}

function bootPayload(): array
{
    return test()->getJson('/ai-compliance/boot')->assertOk()->json();
}

function bootConsentLabel(array $payload, string $slug): ?string
{
    foreach ($payload['consent']['types'] as $type) {
        if ($type['slug'] === $slug) {
            return $type['label'];
        }
    }

    return null;
}

afterEach(function (): void {
    File::deleteDirectory(lang_path('vendor'));
});

it('applies an override published by the translations tag', function (): void {
    expect(Artisan::call('vendor:publish', ['--tag' => 'laranail::ai-compliance-translations', '--force' => true]))->toBe(0);

    // the tag writes to the slash directory, which only the canonical namespace reads
    $file = lang_path('vendor/laranail/ai-compliance/en/ai-compliance.php');
    expect($file)->toBeFile()
        ->and(lang_path('vendor/laranail-ai-compliance'))->not->toBeDirectory();

    $lines = require $file;
    $lines['strings']['disclosure']['badge'] = 'Published badge';
    $lines['strings']['preferences']['save'] = 'Published save';
    $lines['consent_types']['ai_training']['label'] = 'Published training';
    $lines['notifications']['reconsent_subject'] = 'Published subject';
    File::put($file, '<?php return ' . var_export($lines, true) . ';');
    app('translator')->setLoaded([]);

    $payload = bootPayload();

    expect(Translations::get('ai-compliance.strings.disclosure.badge'))->toBe('Published badge')
        ->and(renderedDisclosureBadge())->toContain('Published badge')
        ->and($payload['strings']['preferences.save'])->toBe('Published save')
        ->and(bootConsentLabel($payload, 'ai_training'))->toBe('Published training')
        ->and(Translations::get('ai-compliance.notifications.reconsent_subject'))->toBe('Published subject');
});

it('still applies an override made against the hyphen namespace', function (): void {
    // a file in lang/vendor/laranail-ai-compliance, the directory the old namespace reads...
    withAiComplianceLangOverrides([
        'laranail-ai-compliance' => [
            'strings'       => ['disclosure' => ['badge' => 'Hyphen badge'], 'preferences' => ['save' => 'Hyphen save']],
            'consent_types' => ['ai_training' => ['label' => 'Hyphen training']],
        ],
    ], function (): void {
        $payload = bootPayload();

        expect(Translations::get('ai-compliance.strings.disclosure.badge'))->toBe('Hyphen badge')
            ->and(renderedDisclosureBadge())->toContain('Hyphen badge')
            ->and($payload['strings']['preferences.save'])->toBe('Hyphen save')
            ->and(bootConsentLabel($payload, 'ai_training'))->toBe('Hyphen training')
            // a line the host did not override keeps the packaged text
            ->and($payload['strings']['reconsent.review'])
            ->toBe(__('laranail/ai-compliance::ai-compliance.strings.reconsent.review'))
            ->and(Translations::get('ai-compliance.strings.reconsent.review'))
            ->toBe(__('laranail/ai-compliance::ai-compliance.strings.reconsent.review'));
    });

    // ...or lines added at runtime on that namespace
    app('translator')->get(Translations::key('ai-compliance.strings'));
    app('translator')->addLines(['ai-compliance.strings.reconsent.review' => 'Runtime review'], 'en', Translations::LEGACY_NAMESPACE);

    expect(Translations::get('ai-compliance.strings.reconsent.review'))->toBe('Runtime review')
        ->and(Translations::lines('ai-compliance.strings')['reconsent']['review'])->toBe('Runtime review');
});

it('prefers the canonical override when both namespaces are overridden', function (): void {
    withAiComplianceLangOverrides([
        'laranail/ai-compliance' => [
            'strings'       => ['disclosure' => ['badge' => 'Canonical badge'], 'preferences' => ['save' => 'Canonical save']],
            'consent_types' => ['ai_training' => ['label' => 'Canonical training']],
        ],
        'laranail-ai-compliance' => [
            'strings'       => ['disclosure' => ['badge' => 'Hyphen badge'], 'preferences' => ['save' => 'Hyphen save']],
            'consent_types' => ['ai_training' => ['label' => 'Hyphen training']],
        ],
    ], function (): void {
        $payload = bootPayload();

        expect(Translations::get('ai-compliance.strings.disclosure.badge'))->toBe('Canonical badge')
            ->and(renderedDisclosureBadge())->toContain('Canonical badge')
            ->and(renderedDisclosureBadge())->not->toContain('Hyphen badge')
            ->and($payload['strings']['preferences.save'])->toBe('Canonical save')
            ->and(bootConsentLabel($payload, 'ai_training'))->toBe('Canonical training');
    });
});

it('falls back to the packaged line, and to the canonical key for a missing one', function (): void {
    expect(Translations::get('ai-compliance.strings.disclosure.badge'))
        ->toBe(__('laranail/ai-compliance::ai-compliance.strings.disclosure.badge'))
        ->and(Translations::get('ai-compliance.strings.disclosure.badge'))
        ->not->toBe(Translations::key('ai-compliance.strings.disclosure.badge'))
        ->and(Translations::get('ai-compliance.no_such_line'))->toBe(Translations::key('ai-compliance.no_such_line'))
        ->and(Translations::lines('ai-compliance.no_such_group'))->toBe([])
        ->and(Translations::lines('ai-compliance.strings'))
        ->toBe(__('laranail/ai-compliance::ai-compliance.strings'));
});

it('keeps the package free of lookups through the hyphen namespace', function (): void {
    // every string the package renders goes through Translations; a direct __('laranail-ai-compliance::…')
    // would read only the old namespace and ignore a published override again
    $files = [
        ...File::allFiles(dirname(__DIR__, 2) . '/src'),
        ...File::allFiles(dirname(__DIR__, 2) . '/resources/views'),
    ];

    expect(count($files))->toBeGreaterThan(50);

    $offenders = [];

    foreach ($files as $file) {
        // the helper itself names the old namespace, in its docblock and its LEGACY_NAMESPACE constant
        if ($file->getFilename() === 'Translations.php') {
            continue;
        }

        if (preg_match('/(?:__|trans|trans_choice|->get|@lang)\(\s*[\'"]laranail-ai-compliance::/', $file->getContents()) === 1) {
            $offenders[] = $file->getRelativePathname();
        }
    }

    expect($offenders)->toBe([]);
});
