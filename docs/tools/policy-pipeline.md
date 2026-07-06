# Policy pipeline

Reference for the six pipeline classes in `Simtabi\Laranail\AiCompliance\Policy`: loader, compiler, shortcodes, placeholders, repository, cache.

## Authoring format

CommonMark with yaml frontmatter:

```markdown
---
title: How {{product}} uses AI
type: policy                # policy | consent_text | disclosure
short: "…"                  # consent_text documents only
---

Body markdown. [[consent-toggle type="ai_training" fallback="Manage in settings."]]
```

| Frontmatter key | Meaning |
|---|---|
| `title` | Document title; may contain placeholders |
| `type` | Informational; the authoritative type derives from the directory |
| `short` | Consent texts: the one-liner rendered next to the toggle |

## File layout and slugs

`{root}/{locale}/{path}.md`, where root is the app-published directory first
(`policies.path`, default `resources/policies/ai-compliance`) and the package's
`resources/policies` second. The slug is the relative path with `/` → `.` and the
`disclosures/` directory singularized:

| Path | Slug | Type |
|---|---|---|
| `transparency.md` | `transparency` | `policy` |
| `consent/ai_training.md` | `consent.ai_training` | `consent_text` |
| `disclosures/chat.md` | `disclosure.chat` | `disclosure` |

## `PolicyFileLoader`

`find(slug, locale)` / `all(locale)` return `PolicyFile` value objects (contents +
sha256 checksum). App files shadow package files per (locale, relative path);
`flush()` clears the in-memory index.

## `PolicyCompiler`

league/commonmark with `FrontMatterExtension` and the shortcode extension.
`html_input => 'escape'` (raw html in sources is neutralized) and
`allow_unsafe_links => false`. `compile(PolicyFile): CompiledPolicy` yields html,
meta, checksum; `inline(string)` compiles a one-line short text without the
wrapping paragraph. Placeholders pass through untouched.

## Shortcodes

`[[name key="value" fallback="plain text"]]` compiles to:

```html
<ai-c data-component="name" data-props='{"key":"value"}'>plain text</ai-c>
```

The vocabulary is the `shortcodes` config list (`consent-toggle`, `consent-panel`,
`provider-list`, `policy-link`, `disclosure`). Unknown names render only their
fallback text and log a warning. The `fallback` prop is lifted out of the props
json and becomes the element body.

## `PlaceholderRegistry`

`substitute(text): SubstitutedText` replaces `{{key}}` for every configured
placeholder and registered runtime resolver, and reports what remained
unresolved (simple keys without values and the prose fill-me-in placeholders
shipped in the templates). Runs at serve time only.

## `PolicyRepository`

`find(slug, ?locale): ?PolicyContent` and `all(?locale)`. Resolution per slug:
each locale in `fallbackChain(locale)` — the requested locale, the configured
chain (`locales.fallbacks`), the app fallback locale, the package default —
against the loader. From the versioning milestone a published database version
wins over files. `PolicyContent` reports the served locale (`isFallback()`), the
version (null for file-only), and unresolved placeholders.

## `CompiledPolicyCache`

Wraps the configured cache store with keys of the form
`laranail.ai-compliance.policy.{slug}.{locale}.{checksum}` — content-addressed,
so file edits are natural misses. Disable per environment with
`AI_COMPLIANCE_POLICY_CACHE=false`.

## Endpoints and command

| Surface | What |
|---|---|
| `GET /ai-compliance/boot` | The shared contract payload |
| `GET /ai-compliance/policies/{slug}?locale=` | One compiled document |
| `laranail::ai-compliance.policy.show {slug} {--locale=}` | Terminal rendering with unresolved-placeholder report |

## See also

- [Architecture](../architecture.md)
- [Customizing policies](../recipes/customizing-policies.md)
- [Configuration](../configuration.md)

---

[← Docs index](../../README.md#documentation)
