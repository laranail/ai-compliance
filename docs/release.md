# Release

Versioning, tags, and the release pipeline.

## Versioning

Semantic Versioning 2.0.0. The boot payload carries a `contract` integer that
bumps only on breaking payload-shape changes; config keys only gain entries and
shipped migrations never change shape inside a major.

The composer package is on the laranail family's pre-1.0 model: **one `v0.1.0`
tag, moved on each change**. The npm packages (`@laranail/ai-compliance`,
`-react`, `-vue`) release in lockstep **with each other**, on their own version
from their manifests — *not* from the tag.

**They cannot share the Composer tag's version, and trying to froze them.** A
published registry version is immutable, so it cannot follow a tag that is
re-pointed: the first publish wins and every later one finds the version already
there. GitHub Packages holds `@laranail/ai-compliance@0.1.0` from an early tag
push for exactly that reason, and the runs all reported success while the tag
moved on without it. So the npm version lives in `packages/*/package.json`, is
bumped by hand, and `0.1.0` is spent — the next release is higher.

## Cutting a release

1. Confirm `main` is green (all four push/PR workflows).
2. Move the `## [Unreleased]` CHANGELOG section under a new `## [X.Y.Z] - date`
   heading.
3. Tag: `git tag vX.Y.Z && git push --tags`.

A tag push runs **one** job: the release job installs runtime dependencies,
generates a CycloneDX SBOM, extracts the tagged version's CHANGELOG section, and
publishes a GitHub release with that section as the body and the SBOM attached.
Packagist picks the tag up automatically. **Nothing is published to a registry.**

The npm job is `workflow_dispatch` only, and `tests/Feature/ReleaseWorkflowTest.php`
fails if it ever accepts a tag push again. Publishing the npm packages is a
separate, deliberate step (below): bump the manifests, then start the run.

It publishes to the registry the `registry` input names, or the repository
variable `PUBLISH_REGISTRY`: npm, the default, or `github` for GitHub Packages
(`npm.pkg.github.com`), which publishes with the run's own `GITHUB_TOKEN` and
needs no npm account. Both stay behind the `NPM_PUBLISH_ENABLED` variable. With
an `NPM_TOKEN` secret set, the npm route uses the token instead of trusted
publishing, without provenance. A version the registry already has is reported
rather than failed, so re-running after a partial publish is safe — but it is
reported as a **warning**, because a run that published nothing must not read as
a clean release.

### Publishing by hand: npm or GitHub Packages

**This is the only way the npm packages are published.** Bump the three
manifests first — the version published is theirs, and the registry refuses a
version it already has:

```bash
for p in core react vue; do npm version <X.Y.Z> --no-git-tag-version --workspace "packages/$p"; done
```

Land that through a pull request, then start the run. One command does it for
every release tag not out yet, oldest first, by starting `release.yml` once per
tag with `tag` and `registry` inputs; the `tag` says which commit to build, not
which version to publish. A hand-started run publishes the packages and never
touches the GitHub release.

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

Tag only from a green `main`. The tag triggers the GitHub release; it never
triggers a registry publish.

## See also

- [Architecture](architecture.md)

---

[← Docs index](../README.md#documentation)
