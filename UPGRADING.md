# Upgrading

`laranail/ai-compliance` has no released versions yet; upgrade notes will be added here per release.

## Unreleased: vendor-scoped names

Nothing breaks: every old name below still works and announces its replacement. Move to the new
names before the next minor after 0.1, which may remove the old ones.

| Old | New |
|---|---|
| `->middleware('ai.consent:<type>')` | `->middleware('laranail-ai-compliance.consent:<type>')` |
| `->middleware('ai.feature:<feature>')` | `->middleware('laranail-ai-compliance.feature:<feature>')` |
| `<livewire:ai-compliance.consent-preferences />` | `<livewire:laranail-ai-compliance.consent-preferences />` |
| `<livewire:ai-compliance.reconsent-prompt />` | `<livewire:laranail-ai-compliance.reconsent-prompt />` |
| listening for `ai-compliance:consent-changed` | `laranail-ai-compliance:consent-changed` |
| Filament `/admin/<slug>` and `filament.<panel>.resources.<slug>.*` | `/admin/laranail-ai-compliance/<slug>` and `filament.<panel>.resources.laranail-ai-compliance.<slug>.*` |

The Filament slugs are `policy-documents`, `providers`, `consent-records`, `checklist-items` and,
for the page, `classification` (`filament.<panel>.pages.laranail-ai-compliance.classification`).
Old URLs redirect, so bookmarks keep working; links generated from the old route names redirect
too, but generate them from the new names.

### Translations also answer to `laranail/ai-compliance::`

The package's own strings now translate through `laranail/ai-compliance::`, which reads published
overrides from `lang/vendor/laranail/ai-compliance/`, the directory
`vendor:publish --tag=laranail::ai-compliance-translations` writes to. Overrides published there
now take effect; before, the package read through the hyphen namespace and never saw them.

Nothing you overrode before is lost. An override made against the hyphen namespace, a file in
`lang/vendor/laranail-ai-compliance/` or lines added with `addLines(…, 'laranail-ai-compliance')`,
still applies wherever the canonical namespace holds only the packaged line. If both namespaces
override the same line, the canonical one wins. `__('laranail-ai-compliance::…')` keeps
resolving in your own code. No change is required; move hand-placed files to
`lang/vendor/laranail/ai-compliance/` when convenient.

