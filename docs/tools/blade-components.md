# Blade components

Reference for the server-rendered UI stack: four components under the `ai-compliance` namespace plus the island renderer, all working without any javascript.

## `<x-laranail-ai-compliance::disclosure>`

The ai disclosure line for a surface, rendered before any model output:

```blade
<x-laranail-ai-compliance::disclosure surface="chat" />
<x-laranail-ai-compliance::disclosure surface="content" locale="de" />
```

The text is the `disclosure.{surface}` policy document: versioned, translated,
and editable like every other document. Renders nothing for unknown surfaces;
falls back per the locale chain.

## `<x-laranail-ai-compliance::gate>`

Renders its slot only when the current subject (user, else guest cookie) may
proceed; the `fallback` named slot renders otherwise:

```blade
<x-laranail-ai-compliance::gate feature="chat_assistant">
    <x-chat-widget />
    <x-slot:fallback>
        <x-laranail-ai-compliance::preferences />
    </x-slot:fallback>
</x-laranail-ai-compliance::gate>

<x-laranail-ai-compliance::gate consent="ai_personalization">…</x-laranail-ai-compliance::gate>
```

`feature` checks the feature's configured consent requirements
(`AiConsent::allows`); `consent` checks one type directly. At least one is
required. Anonymous subjects carry every default state, so denied-by-default
types gate them out.

## `<x-laranail-ai-compliance::policy>`

One compiled policy document (title, substituted body with its `<ai-c>`
islands replaced server-side, a not-yet-translated notice on fallback, and
the version footer once published):

```blade
<x-laranail-ai-compliance::policy slug="transparency" />
<x-laranail-ai-compliance::policy slug="consent.ai_training" :show-title="false" />
```

## `<x-laranail-ai-compliance::preferences>`

The consent panel: one block per configured type with its translated label,
short text, current state, and a plain form posting to the consents endpoint,
a full-page flow that needs no javascript (the endpoint redirects back with an
`ai-compliance.saved` flash for non-json requests). The livewire and js
components render the same payload interactively.

## Server-side islands

`IslandRenderer` replaces the `<ai-c>` elements the shortcode compiler emits
with island views (`resources/views/islands/{name}.blade.php`, publishable):

| Shortcode | Server rendering |
|---|---|
| `[[consent-toggle type="…"]]` | a working toggle form for that type |
| `[[consent-panel]]` | the preferences component |
| `[[policy-link slug="…"]]` | a titled link to the document |
| `[[disclosure surface="…"]]` | the disclosure component |
| `[[provider-list]]` | fallback text until the provider registry milestone |

Islands without a view keep their fallback text, so a policy document never
ships an inert custom element from a blade surface.

## Strings

Every visible string comes from `resources/lang/{locale}/ai-compliance.php`
(`strings.*`, nested), publishable via the translations tag.

## See also

- [Livewire](livewire.md)
- [Consent](consent.md)
- [Policy pipeline](policy-pipeline.md)

---

[← Docs index](../../README.md#documentation)
