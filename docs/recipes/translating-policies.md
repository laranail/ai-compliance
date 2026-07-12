# Translating policies

Add a locale to the policy documents and keep it honest when the source changes.

Create the locale directory next to the shipped one, mirroring the relative
paths, then import:

```bash
mkdir -p resources/policies/ai-compliance/de/consent
# write de/transparency.md, de/consent/ai_training.md, ... (same frontmatter shape)
php artisan laranail::ai-compliance.policy.sync
```

New locales land in a draft (never directly in the published version), each
anchored to the default-locale text it was translated from. Review and publish:

```bash
php artisan laranail::ai-compliance.policy.publish transparency
```

Untranslated documents keep falling back per
`config('laranail.ai-compliance.locales.fallbacks')`, and served content
reports the locale it actually resolved to (`isFallback()` / the `fallback`
field), so UIs can show a not-yet-translated hint.

When the default-locale text changes later, the staleness report
(`GET …/admin/policies/staleness`) lists every locale whose `origin_checksum`
no longer matches as `translation_drift`; that is your re-translation
worklist. See [Policy versioning](../tools/policy-versioning.md) for the full
rules.

---

[← Docs index](../../README.md#documentation)
