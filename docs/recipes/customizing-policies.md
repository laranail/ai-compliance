# Customizing policies

Publish the shipped policy markdown and edit it as your own.

Publish the files:

```bash
php artisan vendor:publish --tag=laranail::ai-compliance-policies
```

Edit any file under `resources/policies/ai-compliance/en/`. Your copy shadows
the package copy file by file, so delete a published file to fall back to the
shipped default. Keep the frontmatter (`title`, and `short:` on consent texts),
keep `{{placeholders}}` for values that come from config, and check what still
needs filling:

```bash
php artisan laranail::ai-compliance.policy.show transparency
```

To add a locale, create a sibling locale directory with the same relative paths
(`de/transparency.md`, `de/consent/ai_training.md`, …); untranslated documents
fall back per the configured chain.

See [Policy pipeline](../tools/policy-pipeline.md) for the authoring format and
resolution rules.

---

[← Docs index](../../README.md#documentation)
