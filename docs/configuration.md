# Configuration

Every key in `config/ai-compliance.php`, resolved under `config('laranail.ai-compliance.*')`.

## Placeholders

`placeholders` maps `{{key}}` tokens in policy markdown to values, substituted at
serve time (never baked into stored content, so changing a value takes effect
immediately). Defaults read from env (`AI_COMPLIANCE_COMPANY`,
`AI_COMPLIANCE_CONTACT_EMAIL`, …). Host apps can add runtime resolvers:

```php
use Simtabi\Laranail\AiCompliance\Policy\PlaceholderRegistry;

app(PlaceholderRegistry::class)->register('plan_name', fn () => auth()->user()?->plan?->name);
```

Unresolved placeholders (including the prose fill-me-in ones shipped in the
templates) stay visible in output and are reported per document.

## Locales

| Key | Meaning |
|---|---|
| `locales.default` | The locale policies ship in and the final fallback (`en`) |
| `locales.fallbacks` | Per-locale chains tried before the default, e.g. `['de-CH' => ['de']]` |

The app's `app.fallback_locale` is consulted between the configured chain and the
package default. Served content always reports the locale it actually resolved to.

## Policy sources

| Key | Meaning |
|---|---|
| `policies.path` | App-level directory scanned before the shipped files (default `resources/policies/ai-compliance`) |
| `policies.cache.enabled` | Cache compiled policies (`AI_COMPLIANCE_POLICY_CACHE`, default true) |
| `policies.cache.store` | Cache store name (null = default store) |

The cache is content-addressed by source checksum: editing a file is a natural
cache miss, no manual invalidation needed.

## Shortcodes

`shortcodes` lists the allowed `[[shortcode]]` names. Compiled output is a
neutral `<ai-c data-component data-props>` element; unknown shortcodes render
their `fallback` text and log a warning.

## Routes

| Key | Default |
|---|---|
| `routes.enabled` | `true` |
| `routes.prefix` | `ai-compliance` |
| `routes.middleware` | `['web']` |
| `routes.rate_limit` | `60,1` (per minute, per user/ip) |

## Consent types and disclosure surfaces

`consent_types` declares the granular consent switches (slug → `legal_basis`,
`default_state`); labels and descriptions live in the translation file. Each slug
needs a `consent/{slug}.md` policy file. `disclosure_surfaces` maps each surface
to `disclosures/{surface}.md`.

## Tables, user model, morph map

`tables.*` names the database tables used from the versioning milestone onward.
Rename before running migrations, never after. `user_model` defaults to the app's
auth provider model; `morph_map` adds host aliases to the enforced morph map.

## See also

- [Policy pipeline](tools/policy-pipeline.md)
- [Installation](installation.md)

---

[← Docs index](../README.md#documentation)
