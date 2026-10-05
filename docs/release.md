# Release

Versioning, tags, and the release pipeline.

## Versioning

Semantic Versioning 2.0.0. The boot payload carries a `contract` integer that
bumps only on breaking payload-shape changes; config keys only gain entries and
shipped migrations never change shape inside a major.

The composer package and the npm packages (`@laranail/ai-compliance`, `-react`,
`-vue`, from their milestone onward) release in lockstep from the same tag.

## Cutting a release

1. Confirm `main` is green (all four push/PR workflows).
2. Move the `## [Unreleased]` CHANGELOG section under a new `## [X.Y.Z] - date`
   heading.
3. Tag: `git tag vX.Y.Z && git push --tags`.

The `release.yml` workflow then runs two jobs: the npm job builds and tests the
workspaces, stamps the tag version on all three packages (and the bindings'
dependency on the core), and publishes them with provenance via npm trusted
publishing; the release job installs runtime dependencies, generates a
CycloneDX SBOM, extracts the tagged version's CHANGELOG section, and publishes
a GitHub release with that section as the body and the SBOM attached.
Packagist picks the tag up automatically.

The npm job publishes to the registry the repository variable `PUBLISH_REGISTRY`
names: npm, the default, or `github` for GitHub Packages (`npm.pkg.github.com`),
which publishes with the run's own `GITHUB_TOKEN` and needs no npm account. Both
stay behind the `NPM_PUBLISH_ENABLED` variable. With an `NPM_TOKEN` secret set,
the npm route uses the token instead of trusted publishing, without provenance.
A version the registry already has is reported, not failed, so the moving
`v0.1.0` tag stops failing the job once it is published.

### Publishing by hand: npm or GitHub Packages

One command publishes every release tag that is not out yet, oldest first, by
starting `release.yml` once per tag with `tag` and `registry` inputs. A
hand-started run publishes the packages and never touches the GitHub release.

```bash
.dev/tools/npm-release github        # GitHub Packages; needs no npm token
.dev/tools/npm-release npm           # npm, with a granular token (npm_…) on the clipboard
.dev/tools/npm-release               # npm if the clipboard holds a token npm accepts, GitHub Packages otherwise
```

It reads the core package's name, so `@laranail/ai-compliance` stands for all
three. `--dry-run` changes nothing, `--keep-token` uses the secret already set,
and `--help` lists the rest; it needs `gh` signed in as a maintainer. GitHub
Packages signs no provenance and asks for authentication even to install a
public package: a project installing from it adds
`@laranail:registry=https://npm.pkg.github.com` and
`//npm.pkg.github.com/:_authToken=${GITHUB_TOKEN}` to its `.npmrc`, with a token
that has `read:packages`.

## CI gates

Every push and pull request runs:

| Workflow | What |
|---|---|
| `tests.yml` | Pest on PHP 8.4/8.5 × prefer-lowest/prefer-stable |
| `static-analysis.yml` | Pint, PHPStan level 8, Rector dry-run |
| `js.yml` | TypeScript build + vitest across the npm workspaces |
| `security.yml` | `composer audit` (plus Mondays 06:00 UTC) |

Tag only from a green `main`; the tag itself is what triggers publishing.

## See also

- [Architecture](architecture.md)

---

[← Docs index](../README.md#documentation)
