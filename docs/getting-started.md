# Getting started

The policy pipeline, the two endpoints, and the facade in five minutes.

## What ships

Fourteen editable policy documents (an AI transparency policy, a training-data
statement, an automated-decisions notice, a data-protection addendum,
acceptable-use / incident-response / vendor policies, four consent texts, and
three disclosure lines), a compile pipeline that turns them into servable html,
and a shared JSON contract every UI stack renders from.

## Fill your placeholders

Policy texts contain `{{placeholders}}` substituted at serve time from config:

```php
// config/ai-compliance.php (published) or .env
'placeholders' => [
    'company' => 'Acme Ltd',
    'product' => 'Acme App',
    'contact_email' => 'privacy@acme.com',
    'settings_path' => '/settings/ai',
],
```

Placeholders without a value stay visible in the output and are reported as
unresolved. Run the show command to see what still needs filling:

```bash
php artisan laranail::ai-compliance.policy.show transparency
```

## Serve a policy

```php
use Simtabi\Laranail\AiCompliance\Facades\AiCompliance;

$policy = AiCompliance::policy('transparency');        // app locale
$policy = AiCompliance::policy('transparency', 'de');  // falls back if untranslated

$policy->title;                    // "How Acme App uses AI"
$policy->html;                     // compiled html, placeholders substituted
$policy->locale;                   // locale actually served
$policy->isFallback();             // true when the requested locale had no file
$policy->unresolvedPlaceholders;   // what the operator still needs to fill
```

Or over http (the same content the JS core fetches):

```
GET /ai-compliance/policies/transparency
GET /ai-compliance/policies/consent.ai_training?locale=de
```

## The boot payload

`GET /ai-compliance/boot` returns the shared contract: consent types with their
short texts, default consent state, disclosure texts per surface, the document
index, translated component strings, and endpoint urls. Blade, Livewire, and
Filament render the same payload in-process via `AiCompliance::bootPayload()`.

## Document slugs

| Slug | File |
|---|---|
| `transparency`, `training-data`, … | `resources/policies/en/{slug}.md` |
| `consent.ai_training`, … | `resources/policies/en/consent/{type}.md` |
| `disclosure.chat`, `disclosure.content`, `disclosure.decision` | `resources/policies/en/disclosures/{surface}.md` |

## See also

- [Policy pipeline](tools/policy-pipeline.md)
- [Customizing policies](recipes/customizing-policies.md)
- [Configuration](configuration.md)

---

[← Docs index](../README.md#documentation)
