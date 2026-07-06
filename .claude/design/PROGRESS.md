# Progress

Per-milestone status, evidence, decisions, and backlog. Updated every working
session. Session protocol: start with an audit (run `composer test`, `composer
phpstan`, `composer pint`, diff completed milestones against PLAN.md acceptance
criteria; correct this file first if reality drifted), never mark a milestone done
without a same-session green run pasted below, never end a session red without
recording the exact failing state and next step.

## Status

| Milestone | Status | Date |
|---|---|---|
| M1 — skeleton + read-only policy pipeline | done | 2026-07-05 |
| M2 — policy versioning + editing API | not started | |
| M3 — consent core | not started | |
| M4 — Blade + Livewire | not started | |
| M5 — JS core + React + Vue | not started | |
| M6 — checklist + checks engine | not started | |
| M7 — activity log + provider enforcement | not started | |
| M8 — Filament plugin | not started | |
| M9 — exports, reports, re-consent notifications | not started | |

## M1 acceptance criteria

- [x] dependencies resolve (from Packagist — see decision log on path repos);
      Pest green; PHPStan level 8, Pint, Rector clean; 4 workflows committed
      (`tests.yml`, `static-analysis.yml`, `security.yml`, `release.yml`)
- [x] GET /ai-compliance/boot returns the contract-1 payload — tests
      `BootEndpointTest`: "serves the contract-1 boot payload", "describes every
      consent type…", "defaults every consent state…", "serves substituted
      disclosure texts per surface", "indexes the long-form policy documents…",
      "serves translatable component strings", "honours an explicit locale request"
- [x] GET /ai-compliance/policies/transparency returns compiled HTML — tests
      `PolicyEndpointTest`: "serves a compiled policy document" (placeholders
      substituted: title "How Acme App uses AI", html contains `<ai-c
      data-component=`, no `{{company}}` remains), "serves consent texts and
      disclosures as documents too", "returns 404 for unknown documents"
- [x] locale `de` falls back to `en` and reports served locale — tests
      `PolicyRepositoryFallbackTest` ("falls back to the default locale…", "walks
      the configured fallback chain for regional locales" [de-CH → de → en]) and
      `PolicyEndpointTest` ("reports the fallback when the requested locale is
      not translated")
- [x] app-published policies dir overrides package files — test
      `PolicyFileLoaderTest`: "prefers app-published files over the shipped
      defaults"
- [x] `policy.show` renders in the terminal — tests `PolicyShowCommandTest`
      (render, --locale fallback report, unknown-slug failure)
- [x] docs shipped: docs/installation.md, getting-started.md, configuration.md,
      architecture.md, release.md, tools/policy-pipeline.md,
      recipes/customizing-policies.md, slim README (house spine + 4 badges),
      CHANGELOG `## [Unreleased]` entry

Evidence (run 2026-07-05, PHP 8.5.3, prefer-stable):

```
== pest ==     Tests:    46 passed (201 assertions)   Duration: 0.72s
== phpstan ==  [OK] No errors            (level 8, larastan)
== pint ==     {"tool":"pint","result":"passed"}
== rector ==   [OK] Rector is done!
== audit ==    No security vulnerability advisories found.
```

prefer-lowest leg also verified locally (mirrors the CI matrix):

```
composer update --prefer-lowest --prefer-stable && vendor/bin/pest
  Tests:    2 deprecated, 44 passed (201 assertions)   (deprecations from lowest
  transitive deps, no failures)
```

## Decision log

| Date | Decision | Why |
|---|---|---|
| 2026-07-05 | laranail toolchain (package-tools ^1.3 + console ^1.0), PHP ^8.4.1, Laravel ^13 — not spatie | User decision; matches license-kit/toolkit and org CLAUDE.md; laranail:: command base for free |
| 2026-07-05 | All five UI stacks + npm workspaces in this repo | User decision; one repo keeps the shared contract in sync during 0.x |
| 2026-07-05 | `id()` PKs + `public_id` ULID on consent_records/activity_events only | User decision; preserves no-sequence-leak on exported surfaces, house consistency elsewhere |
| 2026-07-05 | league/commonmark ^2.7 + symfony/yaml approved as runtime deps | User decision; pipeline needs an owned CommonMark environment + frontmatter |
| 2026-07-05 | Lockstep versioning, one vX.Y.Z tag for composer + npm; `contract` int in payload | User decision |
| 2026-07-05 | laranail/database-tools ^1.0 adopted for schema macros (from M2) | User added it to spec §12.1; verified v1.0.0 is the only tag (spec's ^0.2 corrected) |
| 2026-07-05 | Hybrid policy storage; 3-table subsystem replaces spec's JSON table | RESEARCH.md A; consent rows need a stable policy_version_id |
| 2026-07-05 | Plain md + `[[shortcode]]` → `<ai-c>`; no MDX | RESEARCH.md C; MDX can't serve Blade/Livewire/Filament |
| 2026-07-05 | Milkdown for JS editing surfaces; Filament uses its native MarkdownEditor | RESEARCH.md B; byte-faithful md round-trip protects checksum-based staleness |
| 2026-07-05 | Per-locale directories; dual-checksum staleness | RESEARCH.md D |
| 2026-07-05 | `html_input => 'escape'` in the compiler | Admin-pasted raw HTML is escaped; shortcodes are the vocabulary; XSS posture documented |
| 2026-07-05 | Placeholders substituted at serve time, never stored | Config changes don't require republish; stored versions stay faithful to authored text |
| 2026-07-05 | Git history starts with the spec-docs baseline, then `docs(spec)` fixes; the M1 implementation commit is `Initial release` | Repo pre-existed with content; a reviewable fix diff beats a squashed genesis commit. Org "first commit = Initial release" rule read as applying to fresh-from-zero projects |
| 2026-07-05 | Umbrella policy publishes never force re-consent; only `consent.*` document publishes do | Re-consent = latest granted record references a superseded version of that consent doc; no diff machinery |
| 2026-07-05 | No path repositories in composer.json; deps resolve from Packagist (`package-tools ^1.3`, `console ^1.0`) | Verified empirically: composer hard-fails when a path repo dir is missing, so committed path repos would break CI and every consumer install (license-kit carries this latent bug). All laranail deps are published; local sibling development can add a path repo ad hoc without committing it. Deviates from PLAN's original "path repos" detail — plan updated here |
| 2026-07-05 | `laranail/package-tools` floor is `^1.3`, not `^1.0` | `publishDirectory()` and the translations alias arrived in 1.2/1.3; prefer-lowest CI leg must resolve a version that has them. Lowest set verified green locally |

## Backlog

- Consider `laranail/notifications` as an optional channel layer for M6 alert
  notifications (survey found it SSRF-guarded, standalone) — decide at M6.
- `laranail/toolkit` LLM module overlaps M7's provider wrappers — evaluate reuse vs
  own thin wrappers at M7.
- Filament major version pin (^4 assumed) — confirm against filament release state
  at M8.
- PDF engine for M9 reports (suggest dep dompdf vs browsershot) — decide at M9.
- Demo seeder (`--demo`) reproducing the plugin-screenshot state — spec'd, slot into
  M6 with the checklist seeder.
- Docsmith site: verify ai-compliance is picked up by `docsmith discover` once the
  repo is on GitHub with a docs/ dir (no action needed if org scan covers it).
