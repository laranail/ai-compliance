# Changelog

All notable changes to `laranail/ai-compliance` will be documented in this file.

The format follows [Keep a Changelog 1.1.0](https://keepachangelog.com/en/1.1.0/)
and the project adheres to [Semantic Versioning 2.0.0](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Changed

- About section migrated to package-tools 2.1's fluent
  `AboutSectionDefinition` (floor `^2.1`): the contract is a scalar field,
  the document count a per-field lazy closure.
- Provider migrated to the laranail/package-tools 2.0 declarative surface
  (floor `^2.0`): event listeners, middleware aliases, the consent-record
  policy, the morph map, scheduled commands, blade/livewire components,
  and the db:seed-time seeders are all declared fluently in
  `configurePackage()`; the hand-rolled `packageBooted()` wiring is gone.
  Config keys unchanged. One behavior improvement: `checks.schedule` now
  accepts any scheduler cadence string (`'hourly'`, `'dailyAt:02:30'`, or
  a raw cron expression), not only the literal `'daily'`.

- Spec-conformance audit fixes: `privacy.dpia` applies when personal data
  OR consequential decisions is yes (classification rules gained an
  `any_of` form); `transparency.sector_disclosures` and
  `consent.training_data_documentation` no longer switch off too eagerly;
  `governance.system_inventory` staleness matches its quarterly evidence
  line; the two internal policies (`acceptable-use`, `incident-response`)
  seed deactivated via `internal: true` frontmatter and never serve
  publicly.
- Added the `laranail::ai-compliance.feature` command (list/toggle kill
  switches), the `AiConsent` facade alias, `install --demo`, and a daily
  prune schedule when activity retention is configured.

- The ten table migrations merged into one dependency-ordered migration.
  Review fixes shipped with the merge: `tenant_id` is now NOT NULL with an
  empty-string single-tenant sentinel, because composite unique indexes
  over a nullable column never fire (sql NULLs do not collide) — the
  `(tenant_id, slug|key|feature|question_key)` constraints now actually
  constrain; `ai_activity_events.provider_id` gained a real foreign key
  with `nullOnDelete` (providers are created first now); the consent-log
  index names derive from the configured table name; activity events
  gained an `(event_type, recorded_at)` composite index matching the
  admin read filters.
- `AiComplianceServiceProvider` moved to
  `Simtabi\Laranail\AiCompliance\Providers` (update the class name in
  `dont-discover`/manual registration if you referenced it).
- Package seeders (checklist items, initial policy import) register with
  the package-tools seeder registry and run automatically with the host's
  `php artisan db:seed`; disable with `seeders.auto => false`. The demo
  seeder never auto-runs.

### Added

- **Log exports** (FR-5): `GET …/admin/exports/{consents,activity}` behind
  the dedicated `ai-compliance:export` gate: csv/json, scoped by date and
  type, public ids only, subjects pseudonymized with a stable keyed hash
  (guest keys inside activity context included); every export logs an
  `export` event. The console `export` command adds `--identified` for
  statutory requests; the Filament consent log gains the same CSV action.
- **Compliance report** (FR-7): `GET …/admin/report` and the `report`
  command: print-ready self-contained html with dashboard statistics,
  classification answers, the full checklist with evidence, the provider
  registry, and every document's published version.
- **Re-consent notifications**: `laranail::ai-compliance.notify-reconsent`
  (+ `--dry-run`) sends `ReconsentRequested` (configurable channels) to
  exactly the users whose current granted consent references a superseded
  version; guests are prompted by the boot payload instead.

- **Filament plugin** (`AiCompliancePlugin`, suggest dependency
  filament/filament ^5): policy documents with a markdown editor that stores
  drafts byte-for-byte through the shared `PolicyDrafts` service and
  publishes atomically from a header action; provider registry CRUD; a
  strictly read-only consent log (model policy enforced inside Filament);
  the checklist with evidence and run-checks actions; the classification
  intake page; and the FR-1 stats widget. An architecture test guarantees
  nothing outside `src/Filament` references Filament, so the package boots
  identically without it.
- `PolicyDrafts` and `DashboardStats` services extracted so the http api and
  Filament share one write path and one set of numbers.

- **Consent-aware provider calls** (`AiConsent::provider(...)->forSubject(...)`):
  the vendor's configurable do-not-train flag (header and/or body key) is
  injected whenever the subject has not granted `ai_training`; every send
  (or `record()` for host SDKs) logs an inference event (provider reference,
  purpose, `do_not_train`, never raw prompts) and fires `InferenceLogged`.
- **Hash-chain tamper tier** (`activity.hash_chain`): events link to their
  predecessor; `laranail::ai-compliance.verify-chain` and
  `GET …/admin/activity/chain` recompute the chain and name the first broken
  link. Pruning chained events and DSR erasure break it by design.
- **Retention + `laranail::ai-compliance.prune`**: activity events prune per
  config (model is `MassPrunable`); consent history only behind the explicit
  `--consents` flag with a configured policy, and only superseded rows; the
  current state always survives. Pruning logs its own event (FR-9).
- **Activity read surface** (`GET …/admin/activity`): filterable, paginated,
  public ids only; every read is logged as the new `log_read` activity type
  with the reader attributed (FR-10).
- **DSR completeness**: `forgetSubject` now also scrubs guest keys out of
  activity-event context (both M3 backlog items resolved; `provider_id`
  gained an index).

- **Compliance checklist** (`ai_checklist_items`): forty-plus items extracted
  from spec sections 4–10, seeded at `review` by `ChecklistSeeder` (wired
  into `install`); statuses ok/review/fail/na with evidence, verifier, and
  per-item staleness periods.
- **Classification intake** (`ai_classification_answers`): the section-2
  answers switch checklist sections to `na` with the reason recorded, and
  back; unanswered questions leave items applicable.
- **Checks engine**: a two-method `Check` contract, nine built-ins
  (disclosure surfaces, crawler signals, provider registry, vendor due
  diligence, activity-log alive, accountable owner, consent UI, retention,
  policy versioning), host checks via the `ai-compliance.checks` container
  tag, a runner that writes results to the checklist and auto-degrades stale
  manual verifications, the `laranail::ai-compliance.audit` command
  (non-zero exit on failures), and a daily schedule.
- **Alerting**: `CheckFailed` / `ChecklistItemDegraded` events; with
  `AI_COMPLIANCE_ALERT_MAIL` set, failures notify (log silence and lapsed
  due diligence have their own notifications).
- **Provider registry** (`ai_providers`, soft deletes) with admin CRUD and
  `provider_change` activity logging.
- **Feature kill switches** (`ai_feature_states`): default-on toggles honored
  by `AiConsent::allows()`, the new `ai.feature:{feature}` middleware, and a
  guarded laravel/pennant bridge; toggles fire `FeatureToggled` and log
  `setting_change`.
- **Dashboard endpoint** (FR-1): current-state consent tiles split by type,
  provider and event counts, checklist summary.

- **`@laranail/ai-compliance`** (npm workspace, zero runtime deps): typed
  boot client over the shared contract: `boot` / `granted` / `allows` /
  `set` / `require` / `onChange`, same-origin credentials, automatic CSRF
  (XSRF cookie, meta tag, or explicit token), server-authoritative state,
  and a `ContractMismatchError` when the payload contract differs, plus the
  `<ai-c>` island hydrator (unregistered islands keep their fallback).
- **`@laranail/ai-compliance-react`** and **`@laranail/ai-compliance-vue`**:
  provider/plugin, `useAiConsent`, `AiGate`, `AiDisclosure`, `AiPreferences`,
  and `AiReconsentPrompt` over the same client; react/vue are peers only.
- **Boot payload `features` map** (additive): the feature-gating config so JS
  surfaces gate without a round trip.
- **Contract fixture**: the Pest suite records `boot.json`
  (`AI_COMPLIANCE_EXPORT_FIXTURE=1`) and vitest pins its shape, so the PHP
  serializer and the TypeScript types cannot drift silently.
- **CI/release**: `js.yml` (typecheck + build + vitest) and a lockstep npm
  publish job in `release.yml` (tag version stamped on every workspace,
  `npm publish --provenance`).

- **Blade components** under the `ai-compliance` namespace: `disclosure`
  (the versioned, translated ai-disclosure line per surface), `gate`
  (slot gated on a feature's consents or one consent type), `policy` (full
  document with server-rendered islands, fallback notice, version footer),
  and `preferences` (the consent panel as plain forms: no javascript
  required; the consents endpoint redirects back for form posts).
- **Server-side islands**: `IslandRenderer` replaces the compiler's `<ai-c>`
  elements with publishable island views (consent-toggle, consent-panel,
  policy-link, disclosure); unknown islands keep their fallback text.
- **Livewire components** (suggest dependency, auto-registered when
  installed): `ai-compliance.consent-preferences` (every toggle appends a
  consent record and re-renders from the log) and
  `ai-compliance.reconsent-prompt` for superseded consent versions, wired
  together over the `ai-compliance:consent-changed` event.
- `CurrentSubject` resolver (user, else guest cookie) shared by components
  and middleware; component strings restructured as nested translation
  groups (flattened in the boot payload).

- **Consent core** (`ai_consent_types`, `ai_consent_records`, minimal
  `ai_activity_events`): append-only consent records with `public_id` ULIDs,
  the exactly-one-of subject/guest-key invariant, and the policy version the
  subject was shown (`policy_version_id` + readable string).
- **`AiConsent` facade** (`grant` / `deny` / `withdraw` / `granted` / `allows` /
  `stateFor` / `reconsentFor` / `mergeGuest` / `exportSubject` /
  `forgetSubject`): current state = latest row per (subject, type) with
  configured defaults; re-consent derives from superseded consent-document
  versions; DSR export emits public ids only and erasure anonymizes in place.
- **Guest identity**: opaque server-issued key in a long-lived http-only
  cookie; `POST /ai-compliance/consents` records for user or guest (minting
  the cookie), and login merges guest state idempotently (source
  `guest_merge`).
- **`ai.consent:{type}` middleware** and config-declared feature gating
  (`AiConsent::allows`).
- **Boot payload** now carries the subject's real consent state, the
  re-consent list, the guest key, and the consents endpoint.
- Consent changes and DSR actions mirror into the activity log; the short
  `user` morph alias registers from config (non-enforcing).

- **Policy versioning** (`ai_policy_documents` / `ai_policy_versions` /
  `ai_policy_translations`): every document carries a draft → published →
  superseded version stream with per-locale translations; at most one
  published version and one open draft per document, enforced transactionally
  by `PolicyPublisher`.
- **Policy file sync** (`laranail::ai-compliance.policy.sync`, wrapped by
  `InitialPolicySeeder`): first import publishes 1.0; a changed file whose
  database copy was never edited becomes a draft; a hand-edited copy is
  flagged and never overwritten. Checksum-based staleness report (file drift +
  translation drift) at `GET …/admin/policies/staleness`.
- **Editing api** (`routes/admin.php`, gates `ai-compliance:manage` /
  `ai-compliance:audit`): document list and version history, draft
  create/edit/publish, and a compile-only preview endpoint.
- **Database-first resolution**: a published version is authoritative for its
  document (files never shadow it); deactivated documents serve nothing;
  unmigrated installs keep working in pure file mode.
- **Commands**: `laranail::ai-compliance.install` (publish + migrate + import +
  unresolved-placeholder report) and `…policy.publish {slug}`.
- `laranail/db-tools ^1.0` dependency for the configured-morphs schema
  macros.

- **Read-only policy pipeline** (`src/Policy/`): file loader with app-over-package
  precedence, CommonMark compiler with yaml frontmatter and escaped raw html,
  `[[shortcode]]` islands compiled to neutral `<ai-c>` elements, serve-time
  placeholder substitution with unresolved reporting, per-locale fallback chains,
  and a checksum-addressed compile cache.
- **Fourteen shipped policy documents** (`resources/policies/en/`): the AI
  transparency policy, training-data statement, automated-decisions notice, data
  protection addendum, acceptable-use, incident-response and vendor policies,
  four consent texts (training, chatbot, recommendations, personalization), and
  three disclosure lines (chat, content, decision), all publishable and editable via
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
