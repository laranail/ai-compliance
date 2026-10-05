# Gating features by consent

Make an AI feature run only for subjects who agreed to what it does.

Declare what the feature needs:

```php
// config/ai-compliance.php
'features' => [
    'smart_summaries' => ['ai_training'],
    'chat_assistant' => ['ai_chatbot'],
],
```

Gate in code:

```php
use Simtabi\Laranail\AiCompliance\Facades\AiConsent;

if (AiConsent::allows($user, 'smart_summaries')) {
    // run the feature
}
```

Or on the route:

```php
Route::middleware(['web', 'laranail-ai-compliance.consent:ai_chatbot'])->post('/chat', ChatController::class);
```

> The bare `ai.consent` alias still works and enforces the same check, but it is deprecated:
> it logs one warning per process and may be removed in the next minor after 0.1.

Unlisted features and missing grants are denied by default, guests are
resolved through their cookie, and withdrawing consent blocks the very next
request. On login, merge the guest's choices so nothing is asked twice:

```php
AiConsent::mergeGuest($guestKey, $user); // e.g. in a Login event listener
```

See [Consent](../tools/consent.md) for the full api.

---

[← Docs index](../../README.md#documentation)
