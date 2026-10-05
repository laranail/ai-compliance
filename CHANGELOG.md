# Changelog

All notable changes to `laranail/ai-compliance` are documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/)
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- **The npm packages publish to npm or GitHub Packages.** `release.yml` takes a
  `workflow_dispatch` with `tag` and `registry` inputs, publishes to GitHub
  Packages with the run's own `GITHUB_TOKEN`, follows the `PUBLISH_REGISTRY`
  variable on a tag push (npm when unset), uses an `NPM_TOKEN` secret instead of
  trusted publishing when one is set, and reports a version a registry already
  has instead of failing. A hand-started run leaves the GitHub release alone.
  `.dev/tools/npm-release github|npm` publishes every missing release; see
  `docs/release.md`. `.dev/` is export-ignored, so it never reaches the Composer
  dist.
- **The README says where the npm bindings install from.** They are on GitHub
  Packages, which needs a scoped `.npmrc` and a `read:packages` token; the
  Install section now shows both.

- **Vendor-scoped names for every surface the package registers into a shared registry.**
  Middleware aliases `laranail-ai-compliance.consent` and `laranail-ai-compliance.feature`;
  Livewire components `laranail-ai-compliance.consent-preferences` and
  `laranail-ai-compliance.reconsent-prompt`; the browser event
  `laranail-ai-compliance:consent-changed`; Filament slugs under `laranail-ai-compliance/`
  (`…/policy-documents`, `…/providers`, `…/consent-records`, `…/checklist-items`,
  `…/classification`). A bare name in one of these flat maps silently replaces, or is replaced
  by, a host's or another package's name. Every previous name keeps working; see *Deprecated*.
  `tests/Feature/NamingConventionTest.php` asserts the names against the live registries
  through package-tools' `AssertsRegisteredNames`, which raises the floor to
  `laranail/package-tools ^0.1.4`.

### Fixed

- **`tests.yml` no longer skips a markdown-only pull request.** The `pest`
  contexts it produces are required by branch protection, and
  `paths-ignore: ['**.md']` meant a docs-only change never produced them.

  A required check that never reports does not fail a pull request -- it blocks
  it indefinitely while every check that *did* run shows green, which is why
  this presented as "mergeable but blocked" rather than as a failure. The filter
  is gone and the reason it must not come back is now a comment in the workflow.

### Changed

- **`ai-compliance.export` resolves its filters with `strOption()`.** The four filter values were
  built as `stringOption('x') !== '' ? stringOption('x') : null`, which is `strOption('x')` written
  out — each one calling the accessor twice. Behaviour is unchanged; the intent is now legible, and
  the package asserts `assertNoNullOnlyOptionGuards()` over `src/`.

- **The package's own view calls use the `laranail/ai-compliance::` namespace.** The views are
  registered under that name and under `laranail-ai-compliance::` over the same paths, and a
  host override published to `resources/views/vendor/laranail-ai-compliance/` still wins under
  both (pinned by a test that renders a published override through the package's own call).
  Blade tags keep the hyphen form, which is the only one a tag can spell.

### Deprecated

- **The pre-0.1 bare names**, each still working and each earliest removed in the next minor
  after 0.1:
  - middleware aliases `ai.consent` and `ai.feature`: they enforce the same check through
    `DeprecatedConsentAlias` / `DeprecatedFeatureAlias` and log one warning per process naming
    the replacement;
  - Livewire names `ai-compliance.consent-preferences` and `ai-compliance.reconsent-prompt`:
    still registered (after the scoped names, so a class maps back to its scoped name), and
    mounting one raises one `E_USER_DEPRECATED` per name per process;
  - browser event `ai-compliance:consent-changed`: dispatched beside the scoped event with the
    same payload, and `ReconsentPrompt` listens to both;
  - Filament URLs and route names derived from the class names (`/admin/providers`,
    `filament.admin.resources.providers.edit`, …): each answers with a redirect to the scoped
    URL, keeping the record and the query string, and raises one `E_USER_DEPRECATED` per slug
    per process.

## [0.1.0] - 2026-07-11

Initial public release.

[Unreleased]: https://github.com/laranail/ai-compliance/compare/v0.1.0...HEAD
