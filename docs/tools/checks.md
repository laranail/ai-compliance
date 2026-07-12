# Checks

Reference for the automated checks: the contract, the built-ins, the runner, scheduling, and alerting.

## Running

```bash
php artisan laranail::ai-compliance.audit   # exits non-zero while anything fails
```

The same run happens daily via the scheduler
(`laranail.ai-compliance.checks.schedule`, set null to opt out) and on demand
from `POST /ai-compliance/admin/checklist/run`. Every result is written to
its checklist item (status, message as evidence, timestamp, verifier
`check-runner`); items switched off by classification are skipped.

## The built-ins

| Check | Item key | Verifies |
|---|---|---|
| Disclosure surfaces | `transparency.first_contact_disclosure` | every configured surface resolves a disclosure document and the routes serve it |
| Crawler signals | `consent.crawler_signals` | robots.txt is served and states an ai-crawler stance (GPTBot, ClaudeBot, …); notes llms.txt |
| Provider registry | `governance.provider_registry` | non-empty registry, rows complete (dpa, region, purpose, due diligence) |
| Vendor due diligence | `vendors.due_diligence` | every provider complete and current; a dpa older than 12 months counts as lapsed |
| Activity log alive | `logging.activity_log_alive` | the log received an event within the silence window (`alerting.log_silence_hours`) |
| Accountable owner | `governance.accountable_owner` | the contact placeholders feeding the published policies are set |
| Consent UI reachable | `consent.granular_types` | routes enabled, types configured, a consent text resolvable per type |
| Retention scheduled | `privacy.retention_schedule` | retention periods configured |
| Policy versioning | `governance.policy_versioning` | documents imported, no staleness drift, no unresolved placeholders in served policies |

## Alerting

Failures fire `CheckFailed`; with `alerting.mail` configured
(`AI_COMPLIANCE_ALERT_MAIL`) the listener routes it to a notification: log
silence gets `ActivityLogSilentNotification` (the alarm the spec singles
out), due diligence gets `ProviderDueDiligenceLapsedNotification`, everything
else `CheckFailedNotification`. Without an address the events still fire for
host listeners.

## Writing your own

Implement the two-method contract and tag it:

```php
final class MarkingSurvivesPipelineCheck implements \Simtabi\Laranail\AiCompliance\Checks\Check
{
    public function key(): string
    {
        return 'transparency.machine_readable_marking';
    }

    public function run(): CheckResult
    {
        return $ok ? CheckResult::ok('sample outputs keep their C2PA manifest')
                   : CheckResult::fail('the resize pipeline strips provenance');
    }
}

// AppServiceProvider::register()
$this->app->tag([MarkingSurvivesPipelineCheck::class], 'ai-compliance.checks');
```

`key()` must name an existing checklist item. Point it at one of the manual
items to automate it, or seed your own. See
[Writing custom checks](../recipes/writing-custom-checks.md).

## See also

- [Checklist](checklist.md)

---

[← Docs index](../../README.md#documentation)
