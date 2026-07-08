# laranail/ai-compliance

[![Packagist Version](https://img.shields.io/packagist/v/laranail/ai-compliance)](https://packagist.org/packages/laranail/ai-compliance)
[![Tests](https://github.com/laranail/ai-compliance/actions/workflows/tests.yml/badge.svg)](https://github.com/laranail/ai-compliance/actions/workflows/tests.yml)
[![Static analysis](https://github.com/laranail/ai-compliance/actions/workflows/static-analysis.yml/badge.svg)](https://github.com/laranail/ai-compliance/actions/workflows/static-analysis.yml)
[![License: MIT](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

> AI compliance for Laravel apps: markdown-authored policy documents with versioning and draft/publish, consent records, provider registry, disclosure surfaces, and automated compliance checks.

Requires PHP `^8.4.1 || ^8.5` and Laravel `^13.0`.

## Install

```bash
composer require laranail/ai-compliance
php artisan laranail::ai-compliance.install
```

The npm bindings for React/Vue apps ship in lockstep:
`npm install @laranail/ai-compliance-react` (or `-vue`, or the framework-agnostic core).

## Quick start

The install command publishes the editable policy markdown, migrates, seeds
the checklist, and imports every policy as published version 1.0. From there:

```blade
<x-ai-compliance::disclosure surface="chat" />   {{-- before any model output --}}
<x-ai-compliance::preferences />                 {{-- the consent panel --}}
```

```php
use Simtabi\Laranail\AiCompliance\Facades\AiConsent;

if (AiConsent::allows($user, 'smart_summaries')) {
    $response = AiConsent::provider('support-assistant')
        ->forSubject($user)
        ->send('POST', $endpoint, $payload);     // do-not-train flag + inference log
}
```

```bash
php artisan laranail::ai-compliance.audit        # run the compliance checks
```

See [Getting started](docs/getting-started.md) for the full tour.

## <a name="documentation"></a>Documentation

Full documentation lives at
**[opensource.simtabi.com/documentation/laranail/ai-compliance](https://opensource.simtabi.com/documentation/laranail/ai-compliance/)**.

### Guides

- [Installation](docs/installation.md) — requirements, install, publishing the policy files
- [Getting started](docs/getting-started.md) — the policy pipeline, endpoints, and facade in five minutes
- [Configuration](docs/configuration.md) — placeholders, locales, routes, shortcodes, tables
- [Architecture](docs/architecture.md) — how the pipeline works and why it is shaped this way
- [Release](docs/release.md) — versioning, tags, and the release pipeline

### Reference

- [Policy pipeline](docs/tools/policy-pipeline.md) — files, frontmatter, shortcodes, placeholders, fallback, cache
- [Policy versioning](docs/tools/policy-versioning.md) — documents, versions, draft/publish, sync, staleness, the editing api
- [Consent](docs/tools/consent.md) — the AiConsent facade, append-only records, guests, re-consent
- [Blade components](docs/tools/blade-components.md) — disclosure, gate, policy, preferences, server-side islands
- [Livewire](docs/tools/livewire.md) — the interactive preferences panel and re-consent prompt
- [JS SDK](docs/tools/js-sdk.md) — @laranail/ai-compliance: boot client, consent api, island hydrator
- [React](docs/tools/react.md) — @laranail/ai-compliance-react bindings
- [Vue](docs/tools/vue.md) — @laranail/ai-compliance-vue bindings
- [Checklist](docs/tools/checklist.md) — the seeded items, classification, evidence, staleness, dashboard
- [Checks](docs/tools/checks.md) — the automated checks, scheduling, and alerting
- [Activity log](docs/tools/activity-log.md) — event coverage, hash chain, retention, read auditing
- [Filament](docs/tools/filament.md) — the admin plugin: policy editor, registry, consent log, checklist
- [Exports and reports](docs/tools/exports-and-reports.md) — pseudonymized log exports, the auditor report, re-consent

### Recipes

- [Customizing policies](docs/recipes/customizing-policies.md) — publish and edit the shipped markdown
- [Translating policies](docs/recipes/translating-policies.md) — add locales and track re-translation work
- [Gating features by consent](docs/recipes/gating-features-by-consent.md) — allows() and the ai.consent middleware
- [Writing custom checks](docs/recipes/writing-custom-checks.md) — automate your own checklist items
- [Do-not-train enforcement](docs/recipes/do-not-train-enforcement.md) — consent-aware provider calls
- [Auditor handover](docs/recipes/auditor-handover.md) — the evidence bundle in four commands

## Stability

Pre-1.0: the API surface described in the docs is what 1.0 will ship; the boot
payload carries a `contract` integer so the JS packages fail loudly on
mismatch rather than misreading state. No breaking config or schema changes
are planned inside 1.x.

## Local development

`composer install` resolves everything from Packagist; `composer test`,
`composer lint`, `npm install && npx vitest run`. See
[CONTRIBUTING.md](CONTRIBUTING.md).

## Sister packages

Part of the [laranail](https://github.com/laranail) family:
[package-tools](https://github.com/laranail/package-tools) (the service
provider base this package builds on),
[console](https://github.com/laranail/console), and
[db-tools](https://github.com/laranail/db-tools).

## Community

Questions and ideas: [GitHub Issues](https://github.com/laranail/ai-compliance/issues).

## Contributing & security

See [CONTRIBUTING.md](CONTRIBUTING.md). Report vulnerabilities per [SECURITY.md](SECURITY.md).

## License

[MIT](LICENSE) © Simtabi LLC.
