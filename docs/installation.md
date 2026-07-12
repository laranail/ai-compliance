# Installation

Requirements, the Composer install, and publishing the editable policy files.

## Requirements

| Requirement | Version |
|---|---|
| PHP | `^8.4.1 \|\| ^8.5` |
| Laravel | `^13.0` |

The package builds on `laranail/package-tools` (service provider base and
`laranail::` command naming) and uses `league/commonmark` + `symfony/yaml` for the
policy pipeline; all are installed automatically.

## Install

```bash
composer require laranail/ai-compliance
```

The service provider is auto-discovered. Nothing else is required to serve the
shipped policies: `GET /ai-compliance/boot` and
`GET /ai-compliance/policies/transparency` work immediately.

## Publish the config

```bash
php artisan vendor:publish --tag=laranail::ai-compliance-config
```

Config resolves under `config('laranail.ai-compliance.*')`. At minimum, fill the
`placeholders` block (company, product, contact email); see
[Configuration](configuration.md).

## Seeding

The package's idempotent seeders (the checklist items and the initial
policy import) also run with your app's `php artisan db:seed`, via the
package-tools seeder registry. Set `seeders.auto => false` in the config to
opt out. Demo data never auto-runs:

```bash
php artisan db:seed --class="Simtabi\\Laranail\\AiCompliance\\Database\\Seeders\\DemoSeeder"
```

## Publish the policy files

The fourteen shipped policy documents are markdown files you are meant to edit:

```bash
php artisan vendor:publish --tag=laranail::ai-compliance-policies
```

They land in `resources/policies/ai-compliance/{locale}/…` (path configurable via
`policies.path`) and take precedence over the package copies file by file.

## Publish the translations

Component strings (labels, buttons, notices) are ordinary Laravel translations:

```bash
php artisan vendor:publish --tag=laranail::ai-compliance-translations
```

## See also

- [Getting started](getting-started.md)
- [Configuration](configuration.md)
- [Customizing policies](recipes/customizing-policies.md)

---

[← Docs index](../README.md#documentation)
