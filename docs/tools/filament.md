# Filament

Reference for the Filament admin plugin: the fifth UI stack, over the same services as the admin json api.

## Installing

Filament is a suggest dependency:

```bash
composer require filament/filament
```

Add the plugin to a panel. Nothing outside `src/Filament` references
Filament classes, so the package boots identically without it (enforced by an
architecture test):

```php
use Simtabi\Laranail\AiCompliance\Filament\AiCompliancePlugin;

public function panel(Panel $panel): Panel
{
    return $panel->plugin(AiCompliancePlugin::make());
}
```

## What it adds

| Surface | What |
|---|---|
| AI policies | document list with published/draft versions, and the markdown editor |
| AI providers | full registry CRUD (soft deletes keep log references) |
| Consent log | strictly read-only, public ids only, guarded by `ConsentRecordPolicy`; the host's `ai-compliance:audit` gate applies inside Filament too |
| Compliance checklist | status badges, staleness flags, per-item manual evidence, run-checks action |
| AI classification | the section-2 intake form; saving re-derives the checklist |
| Dashboard stats | the FR-1 tiles as a `ComplianceStats` widget |

## URLs

Every page and resource sits under a `laranail-ai-compliance/` slug, so it cannot collide with a
host's own resource of the same name:

| Surface | Slug | Example URL (panel at `/admin`) |
|---|---|---|
| AI policies | `laranail-ai-compliance/policy-documents` | `/admin/laranail-ai-compliance/policy-documents` |
| AI providers | `laranail-ai-compliance/providers` | `/admin/laranail-ai-compliance/providers` |
| Consent log | `laranail-ai-compliance/consent-records` | `/admin/laranail-ai-compliance/consent-records` |
| Compliance checklist | `laranail-ai-compliance/checklist-items` | `/admin/laranail-ai-compliance/checklist-items` |
| AI classification | `laranail-ai-compliance/classification` | `/admin/laranail-ai-compliance/classification` |

Route names follow Filament's scheme, so they carry the same segment:
`filament.admin.resources.laranail-ai-compliance.providers.index`,
`filament.admin.pages.laranail-ai-compliance.classification`.

> Before this the slugs were derived from the class names (`/admin/providers`,
> `/admin/classification`, …). Those URLs and their route names
> (`filament.admin.resources.providers.edit`, …) are deprecated: each still answers, as a
> redirect to the scoped URL that keeps the record and the query string, and raises one
> `E_USER_DEPRECATED` per slug per process. They may be removed in the next minor after 0.1.
> The redirects are not registered for a resource or page you configure or cluster yourself,
> whose URL never was the derived slug.

## The policy editor

Editing a policy document opens its markdown (frontmatter included) in
Filament's markdown editor. Saving writes through the same `PolicyDrafts`
service as the http editing api: the open draft is created on first save,
translations recompile, and the markdown is stored **byte-for-byte**: a
no-op save changes no checksum. Publishing is an explicit header action that
supersedes the current version atomically and flushes the compiled policy
cache. Documents themselves are never created here; they come from the
shipped files via the sync.

## Authorization

Model policies apply as everywhere else: the consent log delegates to the
host's `ai-compliance:audit` / `manage` / `export` gates. Panel access is the
host's `FilamentUser` contract as usual.

## See also

- [Policy versioning](policy-versioning.md)
- [Checklist](checklist.md)
- [Consent](consent.md)

---

[← Docs index](../../README.md#documentation)
