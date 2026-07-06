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
```

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

### Recipes

- [Customizing policies](docs/recipes/customizing-policies.md) — publish and edit the shipped markdown
- [Translating policies](docs/recipes/translating-policies.md) — add locales and track re-translation work
- [Gating features by consent](docs/recipes/gating-features-by-consent.md) — allows() and the ai.consent middleware

## Contributing & security

See [CONTRIBUTING.md](CONTRIBUTING.md). Report vulnerabilities per [SECURITY.md](SECURITY.md).

## License

[MIT](LICENSE) © Simtabi LLC.
