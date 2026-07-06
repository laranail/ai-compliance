# Implementation plan

Nine PR-sized milestones. Order: M1 → M2 → M3 strict; M4/M5/M6 need M3; M7 needs M6;
M8 needs M2 + M6; M9 needs M3 + M6. Every milestone ships its docs pages and its
CHANGELOG entry in the same PR — docs are never batched. A milestone is done only
with a green `composer test` + `composer phpstan` + `composer pint` + `composer
rector` run performed in that session and pasted into PROGRESS.md.

## M1 — skeleton + read-only policy pipeline

Scope: full repo scaffold on the laranail toolchain; the file-based pipeline end to
end (loader → frontmatter → shortcodes → placeholders → cache → boot/policy
endpoints); the 14 policy md files (en) authored from `ai-policy-templates.md` (spec file deleted 2026-07-06 after the conformance audit; see git history); all
enums; CI; docs. No database.

Ships: composer.json (Packagist deps only — no path repos, see PROGRESS decision
log), pint/phpstan/rector/phpunit configs, root
hygiene files, 4 workflows, `config/ai-compliance.php`,
`resources/policies/en/**` (14 files), `resources/lang/en/ai-compliance.php`,
`routes/api.php`, service provider, `AiCompliance` manager + facade, 6 enums,
`src/Policy/*` (loader, compiler, shortcode extension, placeholder registry,
repository, cache, 3 VOs), `Payload/BootPayload`, Boot/Policy controllers,
`laranail::ai-compliance.policy.show` command, test suite, docs.

Acceptance criteria:
- [ ] `composer install` resolves (Packagist); Pest green on testbench ^11;
      PHPStan level 8, Pint, Rector clean; 4 workflows committed.
- [ ] `GET /ai-compliance/boot` returns the contract-1 payload (consent types from
      config defaults, empty state, version from files = null).
- [ ] `GET /ai-compliance/policies/transparency` returns compiled HTML with
      placeholders substituted from config and `[[…]]` → `<ai-c>` elements;
      frontmatter exposed as meta.
- [ ] Requesting locale `de` with only `en` files falls back and reports
      `locale: en`.
- [ ] An app-published policies dir overrides the package files.
- [ ] `policy.show transparency` renders in the terminal.
- [ ] Docs: `installation.md`, `getting-started.md`, `configuration.md`,
      `architecture.md`, `release.md`, `tools/policy-pipeline.md`,
      `recipes/customizing-policies.md` — house template + footers; slim README;
      CHANGELOG `## [Unreleased]` entry.

Tests: loader precedence (app over package); frontmatter → meta incl. `short`;
unknown-shortcode fallback + warning; every registered shortcode compiles;
placeholder substitution + unresolved reporting; locale fallback chain; cache hit
skips recompile / distinct per locale; boot payload shape; policy endpoint (html,
404 unknown slug); command output; ArchTest (strict types, string-backed enums, no
debug calls, final classes).

## M2 — policy versioning + editing API

Scope: 3 policy migrations (database-tools macros; `laranail/database-tools ^1.0`
dep lands here), models + factories, `PolicySyncCommand` + `InitialPolicySeeder`
(thin wrapper), `PolicyPublisher`, `routes/admin.php` behind the three gates (CRUD
drafts, edit translation, preview, publish, history, staleness report),
DB-first resolution in `PolicyRepository`, `PolicyPublished` + cache invalidation,
`InstallCommand` (publish + migrate + sync + placeholder prompts via
laranail/console prompter).

Acceptance: publish supersedes atomically (≤1 published invariant); hand-edited doc
survives file sync (flag, no overwrite); untouched doc auto-drafts on file change;
preview compiles without saving; translation drift flagged via `origin_checksum`;
authorization matrix (manage/audit/deny) enforced. Docs:
`tools/policy-versioning.md`, `recipes/translating-policies.md`; CHANGELOG.

Tests: sync idempotency; both drift signals; single-published invariant under
concurrency (transaction); draft → preview → publish flow; publish flushes cache;
admin routes 403 without gates; factories' `published()`/`superseded()`/`stale()`.

## M3 — consent core

Scope: `ai_consent_types` + `ai_consent_records` + minimal `ai_activity_events`
migrations; append-only `ConsentRecord` (public_id, policy_version_id, guards);
`ConsentTypeSeeder`; `AiConsent` facade (`allows`, `grant`, `withdraw`, `stateFor`,
`exportSubject`, `forgetSubject`); guest keys (server-issued signed cookie) +
`guest_merge` on login; `POST /ai-compliance/consents`; boot payload gains real
state + `reconsent`; `ai.consent:{type}` middleware; `ConsentRecorded`/`ConsentWithdrawn`.

Acceptance: append-only enforced at model + policy layers; current state = latest
row per (subject, type); re-consent flag appears when a consent doc version is
superseded; guest merge idempotent; `forgetSubject` anonymizes and logs
`dsr_action`. Docs: `tools/consent.md`, `recipes/gating-features-by-consent.md`.

Tests: exactly-one-of subject/guest_key; update/delete throw + policy false;
withdraw-then-query; policy_version_id stamped from the published version;
middleware 403; guest merge; export/forget round trip.

## M4 — Blade + Livewire stacks

Scope: Blade components (`<x-ai-compliance::disclosure>`, `::gate`, `::policy`,
`::preferences`) rendering from `BootPayload` in-process; server-side `<ai-c>`
replacement; Livewire `ConsentPreferences` + `ReconsentPrompt` (registered only when
livewire/livewire present — suggest dep); translatable strings throughout.

Acceptance: spec acceptance test 3 (anonymous chat surface shows disclosure before
first response); Livewire toggle writes an append-only row and re-renders; package
boots cleanly without livewire installed. Docs: `tools/blade-components.md`,
`tools/livewire.md`.

Tests: render snapshots per locale; gate on consent + feature state; Livewire
interaction tests; `<ai-c>` replacement per registered shortcode; no-livewire boot.

## M5 — JS core + React + Vue

Scope: npm workspaces `packages/{core,react,vue}` → `@laranail/ai-compliance`,
`-react`, `-vue`. Core: boot fetch, `granted()/set()/onChange()/require()`, `<ai-c>`
hydrator, CSRF, guest-cookie respect, contract check. React provider/hook/
`AiGate`/`AiDisclosure`/preferences panel; Vue equivalents; vitest; `js.yml` CI;
root package.json workspaces; lockstep release wiring in `release.yml`.

Acceptance: core has zero runtime framework deps; contract fixture (recorded boot
JSON from the Pest suite) pins `contract: 1` shape in vitest; hydrator mounts into
`<ai-c>`; re-consent prompt driven by boot flag. Docs: `tools/js-sdk.md`,
`tools/react.md`, `tools/vue.md`.

## M6 — checklist + checks engine + classification + dashboard

Scope: remaining migrations (`ai_checklist_items`, `ai_classification_answers`,
`ai_providers`, `ai_feature_states`); `ChecklistSeeder` (all §4–10 items with
`applies_when`); `Check` contract + built-ins (disclosure active, robots/llms.txt,
registry complete, logging alive, contact set, consent UI reachable, retention
scheduled, policy file drift, unresolved placeholders); scheduler; manual evidence +
staleness auto-degrade; dashboard/checklist/providers/feature admin endpoints;
`ai.feature` middleware + Pennant bridge; `laranail::ai-compliance.audit`;
`CheckFailed`/`ProviderDueDiligenceLapsed`/`ActivityLogSilent` notifications.

Acceptance: fresh install = full checklist at Review; classification flips items to
N/A with reason; spec acceptance test 6 (logging silence degrades + alerts). Docs:
`tools/checks.md`, `tools/checklist.md`, `recipes/writing-custom-checks.md`.

## M7 — activity log full + provider enforcement

Scope: full `ActivityType` coverage; hash-chain tamper tier; `MassPrunable`
retention + `prune` command (consents only behind explicit flag); auditor
read-logging; provider client wrapper injecting per-vendor do-not-train flags from
consent state + inference logging; DSR completeness.

Acceptance: spec acceptance tests 1 (consent flip → do-not-train flag observed) and
2 (DSR erasure + logged event); hash chain verifies; pruning logs a pruning event.
Docs: `tools/activity-log.md`, `recipes/do-not-train-enforcement.md`.

## M8 — Filament plugin

Scope: `src/Filament/` (suggest filament/filament ^4): resources for providers,
consent log (read-only + export action), checklist, classification wizard; policy
editor (Filament MarkdownEditor + M2 preview endpoint, draft/publish actions,
version history, staleness badges); FR-1 dashboard widgets.

Acceptance: package boots + full suite green without filament installed (arch
test); editor round-trips markdown byte-identically (checksum unchanged on no-op
save); publish from UI supersedes + flushes cache. Docs: `tools/filament.md`.

## M9 — exports, reports, re-consent notifications

Scope: CSV/JSON exports (consent + activity, pseudonymized by default, date/type
scoped); `export` command; report generator (HTML; PDF via suggest dep);
`notify-reconsent` command + `ReconsentRequested` notification; export events
logged.

Acceptance: spec acceptance test 5 (export matches screen, pseudonymized, emits
public_id only); report contains checklist + registry + consent stats + policy
versions. Docs: `tools/exports-and-reports.md`, `recipes/auditor-handover.md`.

## Definition of production-ready (gates v1.0, from the brief)

- Pest suite covering the policy pipeline, locale fallback, version snapshots, and
  one rendering test per UI stack; PHPStan ≥ 6 (we run 8) + Pint clean; CI committed.
- Publishable config, migrations, views, translations; no hardcoded strings.
- No breaking config/schema changes planned inside 1.x.
- Docs complete per CONVENTIONS.md structure; CHANGELOG entry per milestone.
- PROGRESS.md shows every acceptance criterion checked with evidence.
