# Auditor handover

Produce the evidence bundle an auditor (or a customer's compliance team) asks for.

```bash
# 1. make sure the automated checks are fresh
php artisan laranail::ai-compliance.audit

# 2. the point-in-time report: statistics, checklist with evidence,
#    provider registry, classification, published policy versions
php artisan laranail::ai-compliance.report --path=handover/report.html

# 3. the logs, pseudonymized, scoped to the audit period
php artisan laranail::ai-compliance.export consents --from=2026-01-01 --path=handover/consents.csv
php artisan laranail::ai-compliance.export activity --from=2026-01-01 --path=handover/activity.csv

# 4. if the tamper-evidence tier is on, attach the chain verification
php artisan laranail::ai-compliance.verify-chain
```

Everything is pseudonymized by default; if a statutory request requires
identified data, `--identified` exists on the export command and its use is
itself visible in the activity log. Give auditors the `ai-compliance:audit`
gate for the read surfaces and reserve `ai-compliance:export` for whoever
owns the data.

See [Exports and reports](../tools/exports-and-reports.md) for the full
reference.

---

[← Docs index](../../README.md#documentation)
