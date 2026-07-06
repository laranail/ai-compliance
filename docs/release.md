# Release

Versioning, tags, and the release pipeline.

## Versioning

Semantic Versioning 2.0.0. The boot payload carries a `contract` integer that
bumps only on breaking payload-shape changes; config keys only gain entries and
shipped migrations never change shape inside a major.

The composer package and the npm packages (`@laranail/ai-compliance`, `-react`,
`-vue`, from their milestone onward) release in lockstep from the same tag.

## Cutting a release

1. Move the `## [Unreleased]` CHANGELOG section under a new `## [X.Y.Z] - date`
   heading.
2. Tag: `git tag vX.Y.Z && git push --tags`.

The `release.yml` workflow then installs runtime dependencies, generates a
CycloneDX SBOM, extracts the tagged version's CHANGELOG section, and publishes a
GitHub release with that section as the body and the SBOM attached. Packagist
picks the tag up automatically.

## CI gates

Every push and pull request runs:

| Workflow | What |
|---|---|
| `tests.yml` | Pest on PHP 8.4/8.5 × prefer-lowest/prefer-stable |
| `static-analysis.yml` | Pint, PHPStan level 8, Rector dry-run |
| `security.yml` | `composer audit` (plus Mondays 06:00 UTC) |

A release is only cut from a green `main`.

## See also

- [Architecture](architecture.md)

---

[← Docs index](../README.md#documentation)
