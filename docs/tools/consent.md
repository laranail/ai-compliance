# Consent

Reference for the consent core: the `AiConsent` facade, append-only records, guest identity, the write endpoint, and re-consent.

## The model

Consent is append-only: a change writes a new `ai_consent_records` row, never
updates one, and the current state is the latest row per (subject, type). The
model throws on update or delete; the single sanctioned mutation is DSR
anonymization through `forgetSubject()`. Every record carries a `public_id`
ULID (the only id exports emit), the `source` that produced it, and the policy
version the subject was shown (`policy_version_id` + the readable
`policy_version` string; null when the consent text has never been published
to the database).

Subjects are either eloquent users (stored under the short `user` morph alias
the package registers from `config('laranail.ai-compliance.user_model')`) or
guests, identified by an opaque server-issued key in a long-lived http-only
cookie (`guest` config block).

## The facade

```php
use Simtabi\Laranail\AiCompliance\Facades\AiConsent;

AiConsent::grant($user, 'ai_training', source: 'settings_page');
AiConsent::deny($user, 'ai_recommendations');
AiConsent::withdraw($user, 'ai_training', source: 'settings_page');

AiConsent::granted($user, 'ai_training');      // bool; configured default when no record
AiConsent::allows($user, 'smart_summaries');   // feature gating, see below
AiConsent::stateFor($user);                    // full map for the boot payload
AiConsent::reconsentFor($user);                // types needing re-consent
AiConsent::mergeGuest($guestKey, $user);       // call at login
AiConsent::exportSubject($user);               // dsr access: history, public ids only
AiConsent::forgetSubject($user);               // dsr erasure: anonymize + log
```

Guests use the same api with the key string as the subject:
`AiConsent::granted($guestKey, 'ai_chatbot')`.

Consent type rows are created lazily from `config('laranail.ai-compliance.
consent_types')` on first use (or eagerly via `ConsentTypeSeeder`); unknown
types throw `UnknownConsentType`.

## Feature gating

`config('laranail.ai-compliance.features')` maps a feature to the consent
types that must all be granted; `AiConsent::allows($subject, $feature)` checks
them. Unlisted features are denied by default. Route-level:

```php
Route::middleware('ai.consent:ai_chatbot')->post('/chat', ...);
```

The middleware resolves the current subject (user, else guest cookie) and 403s
without the grant. Admin feature toggles and the pennant bridge arrive with
the checklist milestone.

## The write endpoint

`POST /ai-compliance/consents` with `{type, status}` (status `granted` /
`denied` / `withdrawn`) records for the authenticated user, or for the guest,
minting the guest cookie when absent. Responds `201` with the record's public
id, the refreshed state map, and the re-consent list. The boot payload serves
the same state (`consent.state`, `consent.reconsent`, `guest_key`).

## Re-consent

A subject needs re-consent for a type when their latest granted record
references a superseded version of that type's consent document. Publishing a
new version of `consent.{type}` is the only trigger; umbrella documents never
force it. Re-granting stamps the new version and clears the flag.

## Guest merge

At login, `AiConsent::mergeGuest($guestKey, $user)` appends the guest's
current state onto the user (source `guest_merge`, preserving the policy
version the guest actually saw), skipping types whose state already matches.
Guest rows stay as history; repeating the merge is a no-op.

## Activity log

Every consent change mirrors into `ai_activity_events` (`consent_change`) with
the record's public id, type, status, source, and policy version; DSR erasure
logs a `dsr_action` event. Full activity-log coverage (retention, hash chain,
inference logging) arrives in its own milestone.

## Events

`ConsentRecorded(ConsentRecord)` for grants and denials,
`ConsentWithdrawn(ConsentRecord)` for withdrawals.

## See also

- [Gating features by consent](../recipes/gating-features-by-consent.md)
- [Policy versioning](policy-versioning.md) — where re-consent comes from

---

[← Docs index](../../README.md#documentation)
