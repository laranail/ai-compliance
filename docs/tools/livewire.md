# Livewire

Reference for the interactive stack: two components that register only when livewire is installed.

## Installing

Livewire is a suggest dependency:

```bash
composer require livewire/livewire
```

The components register automatically (`laranail.ai-compliance.livewire.enabled`
turns them off); without livewire the package boots exactly the same and the
blade components keep working.

| Component | Name |
|---|---|
| `ConsentPreferences` | `laranail-ai-compliance.consent-preferences` |
| `ReconsentPrompt` | `laranail-ai-compliance.reconsent-prompt` |

> The pre-0.1 names `ai-compliance.consent-preferences` and `ai-compliance.reconsent-prompt` are
> deprecated aliases of the same classes. They still render; mounting one raises a single
> `E_USER_DEPRECATED` per process naming the replacement. They may be removed in the next minor
> after 0.1.

## `<livewire:laranail-ai-compliance.consent-preferences />`

The interactive preferences panel. Every toggle calls
`AiConsent::record(..., source: 'livewire')` (an append-only row, never a
mutation) and re-renders from the fresh state, so the panel always shows
exactly what the consent log says. Toggles for the current subject: the
authenticated user, or the guest (minting the guest cookie on first use).

Each change dispatches `laranail-ai-compliance:consent-changed` (`type`, `status`)
for host components to react to.

> During the deprecation window the bare `ai-compliance:consent-changed` event is dispatched
> as well, with the same payload, so existing listeners keep working. Move them to the scoped
> name: the bare event may stop being dispatched in the next minor after 0.1.

## `<livewire:laranail-ai-compliance.reconsent-prompt />`

Renders only when the subject's granted consents reference superseded policy
versions, i.e. a new version of a consent document was published after they
agreed. Offers a one-click re-grant per affected type (source
`reconsent_prompt`) and refreshes itself on `laranail-ai-compliance:consent-changed`
(and on the deprecated `ai-compliance:consent-changed`, for hosts still dispatching it).

```blade
<livewire:laranail-ai-compliance.reconsent-prompt />
{{-- nothing renders for subjects with nothing to re-confirm --}}
```

## Testing your integration

```php
Livewire::test(ConsentPreferences::class)
    ->call('toggle', 'ai_training', 'granted')
    ->assertDispatched('laranail-ai-compliance:consent-changed');
```

## See also

- [Blade components](blade-components.md)
- [Consent](consent.md)

---

[← Docs index](../../README.md#documentation)
