# Changelog

All notable changes to `laranail/ai-compliance` are documented in this file.

The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/)
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed

- **`ai-compliance.export` resolves its filters with `strOption()`.** The four filter values were
  built as `stringOption('x') !== '' ? stringOption('x') : null`, which is `strOption('x')` written
  out — each one calling the accessor twice. Behaviour is unchanged; the intent is now legible, and
  the package asserts `assertNoNullOnlyOptionGuards()` over `src/`.

## [0.1.0] - 2026-07-11

Initial public release.
