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
