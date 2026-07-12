# Do-not-train enforcement

Make a denied training consent actually reach the provider, the item most implementations fake.

Map each vendor's flag (whatever their api or contract supports) in config:

```php
// config/ai-compliance.php
'providers' => [
    'do_not_train' => [
        'openai' => ['body' => 'store', 'body_value' => false],
        'acme' => ['header' => 'X-No-Training'],
        'anthropic' => [], // no per-request flag; the contract position still gets audited
    ],
],
```

Call through the wrapper: the flag is injected whenever the subject has NOT
granted `ai_training` (denied, withdrawn, or simply never asked), and the
inference event records `do_not_train` either way:

```php
use Simtabi\Laranail\AiCompliance\Facades\AiConsent;

$response = AiConsent::provider('support-assistant')
    ->forSubject($user)
    ->send('POST', 'https://api.openai.com/v1/responses', $payload, purpose: 'support_chat');
```

On your own SDK, take the prepared adjustments and log the same way:

```php
$call = AiConsent::provider('support-assistant')->forSubject($user);
$options = $call->options();          // ['headers' => [...], 'body' => [...]]
// ... merge into your sdk request ...
$call->record('support_chat', ['tokens' => $usage->total]);
```

The provider must be in the registry (unregistered names throw; that is the
inventory rule doing its job). The spec's acceptance test (flip consent to
denied, observe the next outbound call carrying the flag) is exactly what
the package's own suite runs. See [Activity log](../tools/activity-log.md)
for what gets recorded.

---

[← Docs index](../../README.md#documentation)
