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
| M2 — policy versioning + editing API | done | 2026-07-05 |
| M3 — consent core | done | 2026-07-05 |
| M4 — Blade + Livewire | done | 2026-07-05 |
| M5 — JS core + React + Vue | done | 2026-07-05 |
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

## M2 acceptance criteria

- [x] publish supersedes atomically, ≤1 published invariant — tests
      `PolicyPublisherTest`: "publishes a draft and supersedes the current
      published version atomically", "keeps the single-published invariant
      across repeated publish cycles", "refuses to publish anything that is
      not a draft" (CannotPublishVersion)
- [x] hand-edited doc survives a changed-file sync (flag, no overwrite) —
      `PolicySyncTest`: "flags and never overwrites a hand-edited translation
      when the file changes"
- [x] untouched doc auto-drafts on file change (and reuses the open draft) —
      `PolicySyncTest`: "drafts a new version when a file changes…", "updates
      an existing draft in place instead of stacking drafts"
- [x] preview compiles without saving — `AdminApiTest` editing-flow test
      (substituted html + unresolved placeholders returned, nothing persisted)
- [x] translation drift flagged via origin_checksum — `PolicyStalenessTest`:
      "reports translation drift when the default-locale source changes after
      a translation was made"; file drift + hand-edited variants covered too
- [x] authorization matrix — `AdminApiTest`: guests 403 everywhere, auditors
      read-only, managers full draft→edit→preview→publish flow
- [x] DB-first resolution — `DatabaseResolutionTest`: published version wins
      over files with its version string; deactivated documents serve nothing;
      draft-only documents stay file-served; unmigrated installs keep working
      (all M1 file-mode tests still pass without RefreshDatabase)
- [x] docs shipped: docs/tools/policy-versioning.md,
      docs/recipes/translating-policies.md, README index updated, CHANGELOG
      entry

Evidence (run 2026-07-05, PHP 8.5.3, prefer-stable):

```
== pest ==     Tests:    77 passed (313 assertions)   Duration: 1.24s
== phpstan ==  [OK] No errors            (level 8, larastan)
== pint ==     {"tool":"pint","result":"passed"}
== rector ==   [OK] Rector is done!
== audit ==    No security vulnerability advisories found.
```

## M3 acceptance criteria

- [x] append-only enforced at model + policy layers — `ConsentRecordTest`
      (update/delete throw LogicException, exactly-one-of subject/guest_key,
      public_id ULID, short morph alias) + `ConsentRecordPolicy`
      update/delete false, registered via Gate::policy
- [x] current state = latest row per (subject, type) — `ConsentManagerTest`
      ("treats the latest row as the current state and keeps full history",
      "answers withdraw-then-query correctly", default-state fallback)
- [x] policy_version_id stamped from the published version — "stamps new
      consents with the published policy version…" (1.0 after sync; null on
      never-published)
- [x] re-consent flag on superseded consent-doc version — "flags re-consent
      when the granted consent document version is superseded" (flag set,
      cleared by re-grant under 2.0); boot payload carries it
      (`ConsentEndpointTest`)
- [x] guest merge idempotent — "merges guest state into a user idempotently,
      preserving the version the guest saw" (source guest_merge, second merge
      no-op, guest history intact)
- [x] forgetSubject anonymizes + logs dsr_action — "forgets a subject…" +
      guest variant (rows kept but stripped, dsr_action event, activity rows
      de-identified)
- [x] middleware 403s denied subjects — `ConsentMiddlewareTest` (guests
      without key, users without grant, guest grant→withdraw round trip)
- [x] POST /ai-compliance/consents — `ConsentEndpointTest` (guest cookie
      minted/reused, user records, validation 422s)
- [x] docs: tools/consent.md, recipes/gating-features-by-consent.md, README
      index, CHANGELOG entry

Evidence (run 2026-07-05, PHP 8.5.3, prefer-stable):

```
== pest ==     Tests:    104 passed (400 assertions)   Duration: 1.39s
== phpstan ==  [OK] No errors            (level 8, larastan)
== pint ==     {"tool":"pint","result":"passed"}
== rector ==   [OK] Rector is done!
== audit ==    No security vulnerability advisories found.
```

## M4 acceptance criteria

- [x] spec acceptance test 3 (disclosure before first response, anonymous) —
      `BladeComponentsTest`: "renders the chat disclosure for an anonymous
      user before any model output"
- [x] render snapshots per locale — disclosure in de via override dir,
      fallback for untranslated locales, policy fallback notice
- [x] gate on consent + feature state — gate component tests (consent attr,
      feature attr, missing-attr ViewException)
- [x] `<ai-c>` replacement per registered shortcode — `IslandRendererTest`
      (consent-toggle → working form, consent-panel → preferences,
      policy-link → titled link, disclosure → component, provider-list →
      fallback text, shipped transparency fully resolved)
- [x] Livewire toggle writes an append-only row and re-renders —
      `LivewireComponentsTest` (row count grows, source 'livewire', state
      fresh; invalid status ignored); reconsent prompt shows/regrants/clears
- [x] boots without livewire — the registration guard requires
      `class_exists(Livewire::class) && app->bound('livewire')`; the same
      code path is exercised by the config disable flag. (True absence isn't
      testable with livewire in require-dev; guard documented.)
- [x] docs: tools/blade-components.md, tools/livewire.md, README index,
      CHANGELOG entry

Evidence (run 2026-07-05, PHP 8.5.3, prefer-stable):

```
== pest ==     Tests:    125 passed (458 assertions)   Duration: 2.05s
== phpstan ==  [OK] No errors            (level 8, larastan)
== pint ==     {"tool":"pint","result":"passed"}
== rector ==   [OK] Rector is done!
== audit ==    No security vulnerability advisories found.
```

## M5 acceptance criteria

- [x] core has zero runtime framework deps — packages/core has no
      `dependencies` at all; react/vue are peer deps of their bindings only
- [x] contract fixture pins `contract: 1` — `boot.json` recorded by the Pest
      suite (`AI_COMPLIANCE_EXPORT_FIXTURE=1` regenerates); vitest "pins
      contract 1 and the payload shape the pest suite serves" walks types,
      state, disclosures, documents, features, strings, endpoints
- [x] hydrator mounts into `<ai-c>` — hydrate tests: registered mounts get
      decoded props, unregistered keep fallback text, cleanups run, malformed
      props tolerated
- [x] re-consent prompt driven by boot flag — React + Vue
      `AiReconsentPrompt` tests (renders nothing without the flag, prompts
      with it)
- [x] client behavior — boot (same-origin credentials, locale, contract
      mismatch, not-booted guard), granted/allows/require, set (XSRF cookie /
      explicit token, server-authoritative state, onChange, failure leaves
      state untouched)
- [x] React and Vue bindings — provider/plugin boot, gate flips on consent
      change, preferences posts and re-renders, all 23 vitest tests green
- [x] `js.yml` CI + lockstep npm publish job in `release.yml` (tag stamped
      on every workspace, provenance publish; requires NPM_TOKEN — added to
      the release D-list)
- [x] docs: tools/js-sdk.md, tools/react.md, tools/vue.md, README index,
      CHANGELOG entry

Evidence (run 2026-07-05, PHP 8.5.3 / Node 24.17, prefer-stable):

```
== pest ==     Tests:    1 skipped, 126 passed (461 assertions)
               (skip = the env-gated fixture exporter)
== phpstan ==  [OK] No errors            (level 8, larastan)
== pint ==     {"tool":"pint","result":"passed"}
== rector ==   [OK] Rector is done!
== vitest ==   Test Files 4 passed · Tests 23 passed
== tsc ==      npm run build clean across the 3 workspaces
== audit ==    No security vulnerability advisories found.
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
| 2026-07-05 | First-ever import of a document publishes 1.0 (sync + seeder); later file changes only draft | "Ready-to-use editable defaults": endpoints serve versioned content right after install, while every subsequent change stays a human publish decision |
| 2026-07-05 | One open draft per document, addressed implicitly (`…/{slug}/draft`) | Removes draft-id bookkeeping from the api and makes sync + editor converge on the same draft; multiple parallel drafts had no use case |
| 2026-07-05 | Published DB version never falls back to files for missing locales; it serves its own default-locale translation | A file could otherwise shadow operator-published text with outdated shipped content |
| 2026-07-05 | Cache flush = generation bump (key prefix), not key enumeration | Store-agnostic (no tags needed); orphans age out. Verified by CompiledPolicyCacheTest |
| 2026-07-05 | Spec §12.2 macro calls corrected to `configuredNullableMorphs('x')` | The `nullable:` named-arg form I wrote into the spec earlier does not exist in database-tools v1.0 (verified in source) |
| 2026-07-05 | InstallCommand reports unresolved placeholders instead of prompting/writing .env | Writing env/config from a command is fragile; PLAN's "prompt for placeholders" adjusted — the report gives the operator the same worklist safely |
| 2026-07-05 | `policy_version_id` on consent records is nullable (spec said required) | File-mode installs (policies never synced/published) must still record consent; the version columns are null then, honestly marking "file-served text". Once synced, every record stamps |
| 2026-07-05 | Morph map registered non-enforcing (`Relation::morphMap`), not `enforceMorphMap` per spec | `enforceMorphMap` flips a GLOBAL requireMorphMap that would break unrelated host-app morphs; a package must not do that. Hosts wanting enforcement call it themselves |
| 2026-07-05 | DSR forget runs raw query-builder updates past the model's append-only guards | The guards protect against application code; deliberate anonymization is maintenance. Documented on the model. Guest keys inside activity `context` are scrubbed at M7 (backlogged) |
| 2026-07-05 | Guest merge appends new rows for the user instead of reassigning guest rows | Reassigning would mutate append-only history; the merge preserves the guest's policy_version and is idempotent by comparing current states |
| 2026-07-05 | First-ever sync import publishes 1.0 directly (also noted at M2); consent stamps require it | Fresh installs record versioned consent immediately after `install` |
| 2026-07-05 | Gate component class is `ConsentGate` with an explicit `ai-compliance::gate` alias | A class literally named `Gate` invites collision with the auth facade in imports; the alias keeps the spec's `<x-ai-compliance::gate>` tag working |
| 2026-07-05 | Blade preferences panel posts plain per-type forms; consents endpoint answers redirects for non-json | The blade stack must work with zero javascript; livewire/js layer the same payload interactively on top |
| 2026-07-05 | Targeted phpstan ignore for larastan's view-string rule on src/Livewire | Larastan validates view literals against a skeleton app that never registers the package's view namespace — a known false positive; every view is rendered by the pest suite |
| 2026-07-05 | Lang `strings` restructured to nested groups; BootPayload flattens with Arr::dot | Views need `__('…strings.policy.version')` nested lookups while the JS contract wants flat keys; nested + flatten serves both without duplication |
| 2026-07-05 | livewire/livewire ^4 as require-dev + suggest (registration guarded on class_exists + container binding) | ^4 is the Laravel-13-compatible major (verified on Packagist); the binding check keeps boot safe when livewire exists in vendor but its provider is not registered |
| 2026-07-05 | Boot payload gains a `features` map (additive; contract stays 1) | JS gates client-side without a round trip; matches the server's allows() semantics exactly |
| 2026-07-05 | Contract fixture recorded BY the Pest suite (env-gated test), pinned by vitest | One artifact both sides test against; regeneration is a documented one-liner, drift is a red build |
| 2026-07-05 | JS packages: ESM-only, plain tsc builds, render-function Vue components, npm workspaces + committed package-lock | No bundler/SFC toolchain to maintain; consumers' bundlers handle the rest; npm ci needs the lockfile |
| 2026-07-05 | npm publish versions stamped from the tag at release time (`0.0.0` in-repo) | Lockstep with the composer tag without version-bump commits; bindings pin the core at the same version during publish |

## Backlog

- Release D-list addition: create the `NPM_TOKEN` secret (or npm trusted
  publishing) before the first tag — the lockstep npm job needs it.

- M7: scrub guest keys from activity-event `context` json during
  `forgetSubject` (model-subject morphs are already nulled; json scrubbing
  needs store-portable handling).
- M7: add the `provider_id` FK constraint on `ai_activity_events` once the
  providers table exists (column ships unconstrained since M3).

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
