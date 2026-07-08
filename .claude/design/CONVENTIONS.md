# Conventions

House conventions extracted from the laranail reference implementations
(`laranail/package-tools` at `/opensource/laranail/package/tools`,
`laranail/package-management` at `/opensource/laranail/package/package-management`,
`laranail/license-kit` at `/opensource/laranail/licensing/kit`) plus the org
`CLAUDE.md` files. Binding for everything in this repo; cite this file instead of
re-deriving.

## Foundation

| Concern | Convention |
|---|---|
| Package base | `laranail/package-tools ^1.3` (the org's own fluent `Package` builder — NOT spatie/laravel-package-tools; the API surface is deliberately spatie-compatible) |
| Command base | `Simtabi\Laranail\Package\Tools\Commands\Command` (re-exported from `laranail/console`, ships `SupportsNamespacedNames`) |
| Schema layer | `laranail/db-tools ^1.0` — `configuredMorphs()`, `auditColumns()` schema macros, audit observer (adopted from M2 when the first migration lands) |
| PHP | `^8.4.1 \|\| ^8.5` |
| Laravel | `illuminate/* ^13.0` only |
| Local dev | deps resolve from Packagist; NO committed path repositories (composer hard-fails on a missing path-repo dir, breaking CI and consumer installs — verified 2026-07-05). To develop against a sibling checkout, add a path repo locally without committing it |
| Markdown | `league/commonmark ^2.7` + `symfony/yaml ^7.0 \|\| ^8.0` (frontmatter), explicit requires |

## Naming

- Composer name `laranail/ai-compliance`; npm `@laranail/ai-compliance`, `-react`, `-vue`.
- PSR-4: `Simtabi\Laranail\AiCompliance\` → `src/`, plus explicit
  `…\Database\Factories\` → `database/factories/` and `…\Database\Seeders\` →
  `database/seeders/` mappings.
- Artisan commands: `laranail::ai-compliance.<command>` (dot before the command,
  hyphen inside multi-word commands), via the package-tools `Command` base. A plain
  `ai-compliance:*` alias per command is allowed (package-management does this).
- Config resolves under the vendor-namespaced key `config('laranail.ai-compliance.*')`
  (package-tools config namespacing). Publish tag id `ai-compliance`.
- Tables: `ai_*` prefix, names read from `config('laranail.ai-compliance.tables.*')`.
- Blade components: `<x-ai-compliance::…>` namespace.
- Gate abilities: `ai-compliance:manage`, `ai-compliance:audit`, `ai-compliance:export`.

## Service provider

Extends `Simtabi\Laranail\Package\Tools\Providers\PackageServiceProvider`; everything
declared fluently in `configurePackage(Package $package)`
(`->name('laranail/ai-compliance')->setPublishTagId('ai-compliance')->hasConfigFile()
->hasViews('ai-compliance')->hasTranslations()->hasRoutes(...)->discoversMigrations()
->runsMigrations()->publishDirectory(...)->hasCommands([...])->hasAboutSection(...)`);
container bindings in `packageRegistered()`, boot-time registration (morph map,
middleware aliases, Blade components, scheduled checks) in `packageBooted()`, both
marked `#[Override]`.

## Code style

- `declare(strict_types=1);` on every file; `final` classes by default; `#[Override]`
  on overridden methods; constructor property promotion; typed everything.
- Models: casts via the `casts(): array` method, `@property` docblocks, `HasFactory`
  with `@use` generic + `newFactory()`, table/connection from config in the
  constructor.
- Migrations: plain dated `.php` (not stubs), anonymous `return new class extends
  Migration`, config-driven table names/connection, discovered + run (not published).
- Primary keys: `$table->id()`; the externally-exposed append-only tables
  (`ai_consent_records`, `ai_activity_events`) add `$table->ulid('public_id')->unique()`
  and exports/API resources emit only `public_id`.
- Plain lowercase comments and docblocks; no marketing language anywhere.

## Quality tooling

- PHPStan level 8 with larastan (`vendor/larastan/larastan/extension.neon`),
  `treatPhpDocTypesAsCertain: false`, ignore `missingType.iterableValue`.
- Pint: `laravel` preset + `declare_strict_types: true`,
  `ordered_imports.sort_algorithm: alpha`, `concat_space.spacing: one`.
- Rector: `withPhpSets(php84: true)` + `CODE_QUALITY`, `DEAD_CODE`,
  `TYPE_DECLARATION`, `EARLY_RETURN`, `withImportNames(removeUnusedImports: true)`.
- Pest ^4 (+ arch + laravel plugins) on `orchestra/testbench ^11`;
  `tests/Pest.php` with `uses(TestCase::class)->in(...)`; TestCase extends
  Orchestra's, `getPackageProviders()`, sqlite `:memory:`; an ArchTest enforcing
  strict types / no debug calls / string-backed enums.
- Composer scripts: `test`, `test-coverage`, `lint` = `[@pint, @phpstan, @rector]`,
  `pint` (`--test`), `pint-fix`, `phpstan` (`--no-progress`), `rector`
  (`--dry-run`), `rector-fix`, `audit`.

## CI

Four SHA-pinned workflows, push/PR to `main` + `workflow_dispatch`, concurrency with
cancel-in-progress:

- `tests.yml` — matrix `php: [8.4, 8.5]` × `dependency-versions:
  [prefer-lowest, prefer-stable]`, runs Pest.
- `static-analysis.yml` — single job on PHP 8.5: `pint --test`, `phpstan analyse
  --no-progress --error-format=github`, `rector process --dry-run`.
- `security.yml` — `composer audit`, plus Monday 06:00 UTC cron.
- `release.yml` — tags `v*.*.*`, CycloneDX SBOM, GitHub release with the matching
  `## [X.Y.Z]` CHANGELOG section as body.

M5 adds `js.yml` (vitest across the npm workspaces). Dependabot weekly, Monday
06:00, `America/New_York`.

## Docs structure (adopted; binding)

The org standard (global `CLAUDE.md` "Docs & README authoring standard", reconciled
in the org and laranail `CLAUDE.md`s) — not the flat per-topic list from the task
brief; where the two differ, per-topic pages land inside this tree (each UI stack
gets its page under `docs/tools/`, i18n is covered by `docs/tools/policy-pipeline.md`
+ `docs/recipes/translating-policies.md`, upgrade guidance goes in `UPGRADING.md`):

- `README.md` — slim pointer: `# laranail/ai-compliance` H1, 4 badges (Packagist
  version · Tests · Static analysis · License MIT, one per line), one-sentence `>`
  blockquote, compatibility line, then H2s `Install` →
  `## <a name="documentation"></a>Documentation` (grouped `### Guides` /
  `### Reference` / `### Recipes`, hosted-docs link
  `https://opensource.simtabi.com/documentation/laranail/ai-compliance/`) →
  `Contributing & security` → `License`.
- `docs/` concern pages: `installation.md`, `getting-started.md`,
  `configuration.md`, `architecture.md` (rationale prose; no `docs/adr/`),
  `release.md`.
- `docs/tools/` — one reference page per subsystem: `policy-pipeline.md`,
  `policy-versioning.md`, `consent.md`, `blade-components.md`, `livewire.md`,
  `js-sdk.md`, `react.md`, `vue.md`, `checks.md`, `checklist.md`,
  `activity-log.md`, `filament.md`, `exports-and-reports.md` (added milestone by
  milestone).
- `docs/recipes/` — one task page each, thin (framing line + snippet + link to
  reference).
- Page template: `# Title` → one-line summary → sentence-case `##` sections →
  `## See also` → `---` → footer `[← Docs index](../README.md#documentation)`
  (`../../` from `tools/` and `recipes/`). No `docs/README.md`. No decorative emoji.
- `CHANGELOG.md` keep-a-changelog 1.1.0 + SemVer, `## [X.Y.Z] - date` with
  `### Added/Changed/Fixed` and bold-lead bullets. Every milestone adds its entry.
- Root files: `README.md`, `LICENSE` (MIT © 2026 Simtabi LLC), `CHANGELOG.md`,
  `CONTRIBUTING.md`, `SECURITY.md` (→ opensource@simtabi.com),
  `CODE_OF_CONDUCT.md` (Covenant 2.1 → opensource@simtabi.com), `UPGRADING.md`,
  `.editorconfig`, `.gitattributes`, `.gitignore`,
  `.github/{workflows/,dependabot.yml,ISSUE_TEMPLATE/,PULL_REQUEST_TEMPLATE.md}`.
- Docs hosting: docsmith auto-discovers any laranail repo with a `docs/` dir — no
  site-side registration needed.

## Git

- `git config user.email "imanimanyara@users.noreply.github.com"`, user.name
  `Imani Manyara`. Branch `main`.
- Conventional commits with scopes, lowercase imperative subject ≤ 72 chars
  (`feat(policy): …`, `docs: …`, `chore(conventions): …`,
  `Release vX.Y.Z: …`); body explains why. No AI attribution, no emoji.
- Composer metadata: authors = Simtabi LLC (Organization, opensource@simtabi.com) +
  Imani Manyara (Maintainer, imani@simtabi.com); homepage
  `https://opensource.simtabi.com/documentation/laranail/ai-compliance/`; support
  block with issues/source/docs URLs; `minimum-stability: dev` +
  `prefer-stable: true`; `sort-packages: true`.

## Flagged conflicts and their resolutions

| Conflict | Resolution (user-confirmed) |
|---|---|
| Task brief said spatie/laravel-package-tools; house uses laranail/package-tools | laranail stack (package-tools + console), PHP ^8.4.1, Laravel ^13 |
| Spec §12.1 originally said PHP 8.2 / Laravel 11–13 | Superseded by the toolchain floor; spec updated in commit `00b85f1` |
| Spec §12.2 said ULID PKs everywhere; house uses `id()` | `id()` + `public_id` ULID on the two exported tables |
| Spec stored policy texts as JSON columns; brief requires editable md files | Hybrid: shipped md defaults + 3-table DB snapshot subsystem; spec updated |
| Spec referenced `laranail/console`/`db-tools` "v0.2.x" | Both are `^1.0` (verified against git tags and Packagist) |
| Brief's flat docs list vs org docs standard | Org standard adopted (this file, "Docs structure") |
| Brief said "ask before deps beyond spatie + editor" | Approved: laranail toolchain, league/commonmark, symfony/yaml, db-tools |
