# Activity log

Reference for the ai activity log: event coverage, the tamper-evidence chain, retention, and the read surface.

## Events

Every material ai event lands in `ai_activity_events` with a `public_id`
ULID (the only id exports ever emit), a typed `event_type`, actor and subject
morphs, an optional provider reference, and a `context` json that never
carries raw prompts or other sensitive content. Coverage: `inference`,
`consent_change`, `provider_change`, `setting_change` (feature toggles,
checklist evidence, pruning), `decision`, `override`, `dsr_action`, `export`,
`incident`, and `log_read` (added beyond the spec's set for FR-10).

## The hash chain

The optional tamper-evidence tier
(`laranail.ai-compliance.activity.hash_chain`): every event stores the hash
of its predecessor (identity, subject, context, timestamp, and the
predecessor's own link), so altering or deleting any historic row breaks
every link after it.

```bash
php artisan laranail::ai-compliance.verify-chain   # non-zero when broken
```

`GET /ai-compliance/admin/activity/chain` serves the same verification.
Enabling the chain on an existing log starts a fresh chain; pruning chained
events and DSR erasure both break it **by design** — erasure outranks tamper
evidence, and the break itself documents that an erasure happened.

## Retention and pruning

```bash
php artisan laranail::ai-compliance.prune              # activity events per retention config
php artisan laranail::ai-compliance.prune --consents   # + superseded consent history
```

Activity events prune past `retention.activity_events` (days; null keeps
everything — the model is also `MassPrunable` for hosts running
`model:prune`). Consent records are a legal decision: pruning them requires
the explicit flag AND `retention.consent_records` configured, and only
superseded history is removed — the current state per (subject, type) always
survives, whatever its age. Every prune writes its own `setting_change`
event (FR-9).

## Reading the log

`GET /ai-compliance/admin/activity?type=&from=&to=` (audit gate): filterable,
paginated, public ids only — and per FR-10 the read itself is logged as
`log_read` with the reader attributed, once per request.

## DSR

`AiConsent::exportSubject()` includes the subject's events;
`AiConsent::forgetSubject()` nulls the subject morphs on events and scrubs
guest keys out of event context, leaving anonymous history plus a
`dsr_action` event.

## See also

- [Do-not-train enforcement](../recipes/do-not-train-enforcement.md)
- [Consent](consent.md)
- [Checks](checks.md) — the alive check that watches this log

---

[← Docs index](../../README.md#documentation)
