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

## [0.1.0] - 2026-07-11

Initial public release.

[Unreleased]: https://github.com/laranail/ai-compliance/compare/v0.1.0...HEAD
