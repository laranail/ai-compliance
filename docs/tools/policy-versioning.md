# Policy versioning

Reference for the versioned policy layer: documents, versions, translations, the file sync, the publisher, and the editing api.

## The three tables

| Table | One row per | Carries |
|---|---|---|
| `ai_policy_documents` | logical document (per tenant) | slug, type, surface / consent type link, source path, default locale, active |
| `ai_policy_versions` | version of a document | version string, status (`draft` / `published` / `superseded`), author morph, effective/published/superseded timestamps |
| `ai_policy_translations` | locale of a version | title, source markdown, compiled html, meta, and the three checksums |

Version strings are simple `major.minor` counters starting at `1.0`; every new
draft bumps the minor. At most one version per document is published at any
time, and a document has at most one open draft.

## Resolution

A published version is authoritative: the repository resolves its translations
through the locale fallback chain and never falls back to files for that
document. Documents without any published version (and installs that never
migrated) serve from files. A deactivated document (`active = false`) serves
nothing at all.

## The sync

```bash
php artisan laranail::ai-compliance.policy.sync
```

Imports every policy markdown file (app-published dir first, package second)
using three checksum rules:

| Situation | What happens |
|---|---|
| Document never imported | Published version `1.0` created — the shipped default goes live |
| File changed, database copy untouched (`checksum == file_checksum`) | A draft is created (or the open draft updated); publishing stays a human action |
| File changed, database copy hand-edited | Flagged, never overwritten — the admin's text wins until a human reconciles |

A locale arriving after first import lands in a draft with its
`origin_checksum` anchored to the default-locale translation it was made from.
`InitialPolicySeeder` is a thin wrapper over the same sync.

## Publishing

```bash
php artisan laranail::ai-compliance.policy.publish consent.ai_training
```

`PolicyPublisher::publish($draft)` runs in one transaction: the currently
published version becomes `superseded` (stamped `superseded_at`), the draft
becomes `published`, and `PolicyPublished` fires — which flushes the compiled
policy cache. Publishing anything that is not a draft throws
`CannotPublishVersion`. From the consent milestone, subjects whose latest
granted consent references a superseded version of a consent document are the
re-consent set.

## Staleness

Two signals, both computed on demand from checksums (`GET
…/admin/policies/staleness`):

- `file_drift` — the shipped file changed after import (`file_checksum` no
  longer matches the file); `hand_edited: true` means a human must reconcile.
- `translation_drift` — the default-locale source changed after a translation
  was made from it (`origin_checksum` mismatch); that locale needs
  re-translation.

## The editing api

All routes live under `config('laranail.ai-compliance.admin_routes')`
(default prefix `ai-compliance/admin`, middleware `['web', 'auth']`) and are
guarded by two gates the host app defines:

```php
Gate::define('ai-compliance:manage', fn ($user) => $user->hasRole('compliance-admin'));
Gate::define('ai-compliance:audit', fn ($user) => $user->hasRole('auditor'));
```

| Method | Route | Gate | What |
|---|---|---|---|
| GET | `policies` | audit | document list with published/draft version info |
| GET | `policies/{slug}` | audit | full version history with translation content |
| GET | `policies/staleness` | audit | the staleness report |
| POST | `policies/preview` | manage | compile markdown without saving (substituted html + unresolved placeholders) |
| POST | `policies/{slug}/draft` | manage | create the open draft (or return the existing one) |
| PUT | `policies/{slug}/draft/translations/{locale}` | manage | replace one locale's markdown; recompiles on save |
| POST | `policies/{slug}/draft/publish` | manage | publish the open draft |

Editing with no open draft returns `409`; the markdown body is the full
document including frontmatter (`title:`, and `short:` on consent texts).

## Events

`PoliciesSynced(SyncResult)`, `PolicyDraftCreated(PolicyVersion)`,
`PolicyPublished(PolicyVersion, ?PolicyVersion $superseded)`. The package
listens to the first and last to flush the compiled policy cache.

## See also

- [Policy pipeline](policy-pipeline.md)
- [Translating policies](../recipes/translating-policies.md)
- [Architecture](../architecture.md)

---

[← Docs index](../../README.md#documentation)
