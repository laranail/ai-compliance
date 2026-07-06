# Changelog

All notable changes to `laranail/ai-compliance` will be documented in this file.

The format follows [Keep a Changelog 1.1.0](https://keepachangelog.com/en/1.1.0/)
and the project adheres to [Semantic Versioning 2.0.0](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- **Read-only policy pipeline** (`src/Policy/`): file loader with app-over-package
  precedence, CommonMark compiler with yaml frontmatter and escaped raw html,
  `[[shortcode]]` islands compiled to neutral `<ai-c>` elements, serve-time
  placeholder substitution with unresolved reporting, per-locale fallback chains,
  and a checksum-addressed compile cache.
- **Fourteen shipped policy documents** (`resources/policies/en/`): the AI
  transparency policy, training-data statement, automated-decisions notice, data
  protection addendum, acceptable-use, incident-response and vendor policies,
  four consent texts (training, chatbot, recommendations, personalization), and
  three disclosure lines (chat, content, decision) — publishable and editable via
  `--tag=laranail::ai-compliance-policies`.
- **Shared component contract** (`contract: 1`): `GET /ai-compliance/boot` and
  `GET /ai-compliance/policies/{slug}` serve the payload the Blade/Livewire/
  Filament renderers and the JS core consume alike.
- **`laranail::ai-compliance.policy.show`** artisan command rendering a compiled
  document (with fallback and unresolved-placeholder reporting) in the terminal.
- **Package skeleton** on `laranail/package-tools`: namespaced config
  (`laranail.ai-compliance.*`), publishable translations, Pest 4 suite on
  testbench 11, PHPStan level 8, Pint, Rector, and the four CI workflows
  (tests matrix, static analysis, security audit, release).
