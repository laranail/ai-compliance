# Design

Architecture for `laranail/ai-compliance`. Conventions per
[CONVENTIONS.md](CONVENTIONS.md); storage/editor/format/i18n decisions per
[RESEARCH.md](RESEARCH.md); requirements from `ai-compliance-checklist.md` §11–12
(as corrected in commit `00b85f1`) and `ai-policy-templates.md`. Milestone slicing
in [PLAN.md](PLAN.md).

## Package layout

```
laranail/ai-compliance
├── composer.json                 # laranail/package-tools ^1.3, laranail/console ^1.0,
│                                 # league/commonmark ^2.7, symfony/yaml; database-tools ^1.0 from M2
├── config/ai-compliance.php
├── database/{migrations,factories,seeders}/       # from M2
├── packages/                     # npm workspaces (from M5): core/ react/ vue/
├── resources/
│   ├── lang/{locale}/ai-compliance.php            # component strings
│   ├── policies/{locale}/…                        # shipped policy markdown (14 docs in en)
│   └── views/components/                          # from M4
├── routes/api.php                # + routes/admin.php from M2
├── src/
│   ├── AiComplianceServiceProvider.php
│   ├── AiCompliance.php          # manager; container singleton, facade root
│   ├── Facades/AiCompliance.php  # + Facades/AiConsent.php from M3
│   ├── Enums/
│   ├── Policy/                   # the pipeline (below)
│   │   ├── Markdown/             # ShortcodeExtension, node, renderer
│   │   └── ValueObjects/         # PolicyFile, CompiledPolicy, PolicyContent
│   ├── Payload/BootPayload.php   # the shared contract assembler
│   ├── Http/Controllers/
│   ├── Console/Commands/
│   ├── Models/  Policies/  Checks/  Providers/  Livewire/  Filament/   # later milestones
│   └── Support/
└── tests/
```

## Data model (10 tables, all `ai_`-prefixed, names config-driven)

Full column lists live in the spec §12.2; this section records shape and rationale.

**Policy subsystem** (M2):

- `ai_policy_documents` — one row per logical document per tenant. `slug` (unique per
  tenant: `transparency`, `consent.ai_training`, `disclosure.chat`), `type`
  (`PolicyType`: policy | consent_text | disclosure), `surface` (disclosures),
  `consent_type_slug` (consent texts), `source_path` (shipped file it was imported
  from), `default_locale`, `active`.
- `ai_policy_versions` — `policy_document_id`, `version` ('1.0', auto-bumped),
  `status` (`PolicyVersionStatus`: draft | published | superseded), `authorable`
  morphs (null = seeder/sync), `effective_at/published_at/superseded_at`.
  Invariant (enforced by `PolicyPublisher`, transactional): ≤ 1 published version
  per document; publishing supersedes the prior one in the same transaction.
- `ai_policy_translations` — `policy_version_id`, `locale`, `title`,
  `source_markdown`, `compiled_html` (shortcodes compiled, placeholders NOT
  substituted), `meta` json (frontmatter: `short`, summary…), `checksum`
  (sha256 of source_markdown), `file_checksum` (shipped file at import),
  `origin_checksum` (default-locale source a translation was made from).
  Unique (version, locale). The two checksum pairs are the staleness signals
  (RESEARCH.md D).

**Compliance engine** (M3/M6/M7): `ai_consent_types`, `ai_consent_records`
(append-only, `public_id` ULID, `policy_version_id` FK + denormalized
`policy_version` string, exactly-one-of subject/guest_key guard, no `updated_at`),
`ai_activity_events` (`public_id`, `hash_prev` chain tier), `ai_providers`,
`ai_checklist_items`, `ai_classification_answers`, `ai_feature_states`.

Keys: `$table->id()` everywhere; `public_id` ULIDs only on the two exported
append-only tables; morphs via database-tools `configuredMorphs()`;
`Relation::enforceMorphMap(['user' => …, 'guest' => GuestSubject::class])` in the
provider.

**Re-consent rule**: a subject needs re-consent for type T when their latest
`granted` record for T references a version whose status is `superseded`. Umbrella
documents never trigger re-consent; only publishing a new version of a
`consent.*` document does. No text diffing.

## Policy pipeline (`src/Policy/`)

Authoring format — CommonMark + YAML frontmatter:

```markdown
---
title: How {{product}} uses AI
type: policy                  # policy | consent_text | disclosure
short: "…"                    # consent_text docs only; rendered next to the toggle
---
Body markdown. [[consent-toggle type="ai_training"]] shortcodes allowed.
```

Stages (each an injectable, individually testable class):

1. **`PolicyFileLoader`** — scans the app-published dir
   (`resources/policies/ai-compliance/`, path configurable) first, then the package
   dir; first hit per (relative path, locale) wins. Yields `PolicyFile` VOs
   (slug from frontmatter or path, locale from directory, type, raw md, sha256).
2. **`PolicyCompiler`** — league/commonmark pipeline: `FrontMatterExtension` +
   custom `ShortcodeExtension`, `html_input => 'escape'` (operator-pasted raw HTML
   is escaped; the escape hatch is registering a shortcode). Returns
   `CompiledPolicy` (html, meta, checksum).
3. **Shortcodes** — `[[component key="value"]]` → `<ai-c data-component="…"
   data-props="…">fallback</ai-c>`. Registry in config; initial vocabulary:
   `consent-toggle`, `consent-panel`, `provider-list`, `policy-link`, `disclosure`.
   Unknown → fallback text + warning log. Server stacks replace `<ai-c>` nodes while
   rendering; the JS core hydrates them in the browser.
4. **`PlaceholderRegistry`** — `{{company}}`, `{{contact_email}}`, `{{settings_path}}`…
   from `config('laranail.ai-compliance.placeholders')` + closure resolvers for
   runtime values. Substituted at serve time, never baked into stored
   `compiled_html` — config changes don't require republishing and stored versions
   stay faithful to what was authored. Unresolved placeholders in served output are
   reported (`PolicyContent::$unresolved`) and, from M6, degrade a checklist item.
5. **`PolicyRepository`** — resolution per (slug, locale): DB published version →
   locale fallback chain → file loader (docs never published; M1's only source).
   Returns `PolicyContent` (html, title, meta, version string/id or null for
   file-only, locale actually served, staleness flags).
6. **`CompiledPolicyCache`** — Laravel cache; key
   `laranail.ai-compliance.policy.{slug}.{locale}.{checksum}`; listener flushes on
   `PolicyPublished` / `PoliciesSynced`.
7. **`BootPayload`** — the shared contract (below), consumed in-process by
   Blade/Livewire/Filament and serialized by the boot endpoint for JS.

## The shared component contract

One payload; five thin renderers. `contract` bumps only on breaking shape changes.

```json
{
  "contract": 1,
  "locale": "de", "fallback_locale": "en",
  "consent": {
    "types": [{ "slug": "ai_training", "label": "…", "legal_basis": "consent",
                "default_state": "denied", "short_html": "…",
                "policy_version": "1.0", "policy_version_id": null }],
    "state": { "ai_training": { "status": "denied", "recorded_at": null,
                                "policy_version": null } },
    "reconsent": []
  },
  "disclosures": { "chat": { "html": "…", "version": "1.0" }, "content": {}, "decision": {} },
  "documents": { "transparency": { "title": "…", "url": "…", "version": "1.0" } },
  "strings": { "preferences.save": "…" },
  "endpoints": { "boot": "…", "consents": "…", "policy": "…" },
  "guest_key": null
}
```

Routes (`routes/api.php`, prefix + middleware + rate limit configurable, names
`laranail.ai-compliance.*`):

- `GET  /ai-compliance/boot` — the payload above.
- `GET  /ai-compliance/policies/{slug}` — one compiled document (html, title, meta,
  version, locale, staleness).
- `POST /ai-compliance/consents` — M3.

Admin JSON routes (`routes/admin.php`, M2+) sit behind the three gates; every admin
UI (Filament plugin included) is a consumer of them.

## Editing flow (M2) and re-consent hooks

1. Sync/import: `laranail::ai-compliance.policy.sync` walks the loader, upserts
   documents, creates version 1.0 (published) on first import; on later file changes
   applies the checksum rules (auto-draft if untouched, flag if hand-edited).
2. Draft: admin API clones the latest version's translations into a new draft
   version; edits update `source_markdown` (+ recompile preview via
   `POST …/preview`, compile-only, no save).
3. Publish: `PolicyPublisher::publish($document, $version)` — transaction: mark
   prior published version superseded, stamp timestamps, fire `PolicyPublished`.
4. Re-consent: `ReconsentRequired` derived state (query, not table); boot payload
   `consent.reconsent[]` lists affected type slugs;
   `laranail::ai-compliance.notify-reconsent` (M9) queues `ReconsentRequested`
   notifications to affected subjects only.

## Events

`PoliciesSynced`, `PolicyDraftCreated`, `PolicyPublished` (M2); `ConsentRecorded`,
`ConsentWithdrawn` (M3); `FeatureToggled`, `CheckFailed`, `ChecklistItemDegraded`
(M6); `InferenceLogged`, `ActivityRecorded` (M7); `ExportGenerated` (M9). All are
plain event classes carrying models/VOs; the future webhook fan-out subscribes to
these.

## Config surface (`config/ai-compliance.php`, resolved as `laranail.ai-compliance.*`)

```php
return [
    'placeholders' => [ 'company' => env('APP_NAME'), 'product' => …,
                        'contact_email' => …, 'privacy_url' => …, 'settings_path' => … ],
    'locales'      => [ 'default' => 'en', 'fallbacks' => [] ],
    'policies'     => [ 'path' => null /* app override dir */, 'cache' => [ 'enabled' => true, 'store' => null ] ],
    'shortcodes'   => [ 'consent-toggle' => …, /* component registry */ ],
    'routes'       => [ 'prefix' => 'ai-compliance', 'middleware' => ['web'], 'rate_limit' => '60,1' ],
    'tables'       => [ 'policy_documents' => 'ai_policy_documents', /* … all 10 */ ],
    'user_model'   => null,       // defaults to auth provider model
    'morph_map'    => [],
    'consent_types'=> [ /* the four defaults, slug => label/basis/default_state */ ],
    'features'     => [],         // feature => required consent types (M3/M6)
    'retention'    => [ /* per-table periods, consent pruning opt-in */ ],
];
```

No hardcoded user-facing strings anywhere: component strings come from
`resources/lang`, policy texts from the pipeline, identities from placeholders.

## Semver posture

Everything above is designed to hold inside 1.x: the `contract` integer guards the
payload; config keys only gain entries; migrations only add (no renames) after 1.0;
the 3-table policy schema replaces the spec's JSON table *before* first release so
no shipped schema ever changes shape.
