# Checklist

Reference for the compliance checklist: the seeded items, classification, manual evidence, staleness, and the dashboard.

## The items

`ChecklistSeeder` (run by `laranail::ai-compliance.install`) writes every
checkbox item from the spec's sections 4–10: forty-plus items across seven
sections (governance, transparency, consent, privacy, decisions, logging,
vendors), each with a dotted key, a label, its evidence line as description,
and a staleness period. A fresh install shows the full checklist at `review`.

Statuses match the spec's model: `ok` (evidence exists), `review` (partial or
stale), `fail` (missing), `na` (switched off by classification, reason
recorded). Nine items are `auto`: the [check runner](checks.md) keeps them
honest; the rest are `manual` and carry human evidence.

## Classification

The section-2 intake switches sections on or off. Answers are stored
(`ai_classification_answers`) as evidence and re-derive `na` states:

```
PUT /ai-compliance/admin/classification
{ "answers": { "consequential_decisions": "no", "processes_personal_data": "yes" } }
```

An item is switched off when any of its `applies_when` rules has a mismatched
answer (the reason lands in `evidence_ref`); unanswered questions leave items
applicable, the conservative default. Flipping an answer back returns the
section to `review`. Question keys: `role`, `interacts_with_people`,
`generates_synthetic_content`, `consequential_decisions`,
`processes_personal_data`, `biometrics_emotion`, `markets`,
`minors_plausible`, `publishes_ai_content`, `trains_on_collected_data`.

## Manual evidence and staleness

```
POST /ai-compliance/admin/checklist/{key}/evidence
{ "evidence_ref": "https://drive.example/dpia-2026.pdf" }
```

Sets the item to `ok` with the verifier and timestamp; auto items refuse
manual evidence (409). Every run of the checks sweeps manual `ok` items whose
verification is older than their `staleness_months` (12 default, 6 for bias
audits) back to `review`, firing `ChecklistItemDegraded`.

## The dashboard

`GET /ai-compliance/admin/dashboard` serves the tiles: consent counts by
current state (latest row per subject and type; the log is history, the
dashboard is now), split by type; registered providers; logged events; and
the checklist summary per status.

## Providers and features

`/ai-compliance/admin/providers` is the registry CRUD (soft deletes, every
change logged as `provider_change`); `/ai-compliance/admin/features` reads
and throws the per-feature kill switches (`FeatureToggled` +
`setting_change`). The `ai.feature:{feature}` middleware and
`AiConsent::allows()` both respect the switch; when laravel/pennant is
installed, a defined pennant feature of the same name is consulted as well.

## See also

- [Checks](checks.md)
- [Consent](consent.md)

---

[← Docs index](../../README.md#documentation)
