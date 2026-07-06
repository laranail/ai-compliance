# Exports and reports

Reference for the consent/activity exports (FR-5), the compliance report (FR-7), and re-consent notifications.

## Exports

```
GET /ai-compliance/admin/exports/consents?format=csv|json&type=&status=&from=&to=
GET /ai-compliance/admin/exports/activity?format=csv|json&type=&from=&to=
```

Behind the **dedicated export gate** (`ai-compliance:export`): log exports
are the sensitive ability, so audit access alone is not enough. Rows emit
public ids only, scoped by date and type, and subjects are pseudonymized: a
stable keyed hash (`sub_…`), so the same subject lines up across exports
without identifying anyone. Guest keys inside activity context are
pseudonymized the same way. Every export logs an `export` activity event
(log, row count, filters).

The console command adds the one thing http never does:

```bash
php artisan laranail::ai-compliance.export consents --format=json --from=2026-01-01
php artisan laranail::ai-compliance.export consents --identified   # statutory requests only
```

The Filament consent log carries the same CSV export as a header action,
visible only to users holding the export gate.

## The compliance report

```
GET /ai-compliance/admin/report          # audit gate; print-ready html
php artisan laranail::ai-compliance.report --path=report.html
```

The point-in-time artifact an operator attaches to an audit: dashboard
statistics with per-type consent counts, the classification answers, the full
checklist with statuses and evidence, the provider registry, and every policy
document's published version. Self-contained html: print to PDF with the
browser or any html-to-pdf tool. Generation logs an `export` event.

## Re-consent notifications

```bash
php artisan laranail::ai-compliance.notify-reconsent            # notify affected users
php artisan laranail::ai-compliance.notify-reconsent --dry-run  # count them first
```

Run it after publishing a new version of a consent document. Exactly the
users whose **current granted** consent references a superseded version get
`ReconsentRequested` (channels via `reconsent.channels`, default mail, with
the affected types and a link to the settings path); everyone else's consent
stands. Guests are unreachable by mail; the boot payload's `reconsent` flag
prompts them on their next visit. Re-running notifies whoever is still
affected, so re-granting ends the reminders.

## See also

- [Consent](consent.md)
- [Activity log](activity-log.md)
- [Auditor handover](../recipes/auditor-handover.md)

---

[← Docs index](../../README.md#documentation)
