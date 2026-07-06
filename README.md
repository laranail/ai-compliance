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

### Recipes

- [Customizing policies](docs/recipes/customizing-policies.md) — publish and edit the shipped markdown

## Contributing & security

See [CONTRIBUTING.md](CONTRIBUTING.md). Report vulnerabilities per [SECURITY.md](SECURITY.md).

## License

[MIT](LICENSE) © Simtabi LLC.
