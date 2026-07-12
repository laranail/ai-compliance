# Writing custom checks

Automate a checklist item the package cannot verify for you.

```php
use Simtabi\Laranail\AiCompliance\Checks\Check;
use Simtabi\Laranail\AiCompliance\Checks\CheckResult;

final class PromptRedactionCheck implements Check
{
    public function key(): string
    {
        return 'privacy.prompt_minimization'; // an existing checklist item key
    }

    public function run(): CheckResult
    {
        return app(RedactionMiddleware::class)->isWired()
            ? CheckResult::ok('pii redaction sits in the outbound request path')
            : CheckResult::fail('no redaction middleware on the model request path');
    }
}
```

Register it by tag and it joins every audit run (command, schedule, and the
admin endpoint):

```php
// AppServiceProvider::register()
$this->app->tag([PromptRedactionCheck::class], 'ai-compliance.checks');
```

The result is written to the item like any built-in: status, message as
evidence, timestamp. Failures fire `CheckFailed` and alert when
`alerting.mail` is configured. See [Checks](../tools/checks.md) for the
built-ins and the runner's rules.

---

[← Docs index](../../README.md#documentation)
