---
title: "AI incident response policy"
type: policy
---

An AI incident is any event where an AI feature caused or plausibly could cause harm: wrong output that reached a user in a consequential context, personal data exposed through a prompt or completion, a decision tool producing discriminatory patterns, generated content that should have been blocked, or a provider-side breach affecting our data. Triage within {{hours}}: contain (feature flag off via the package's feature toggle, which is why decision-adjacent features must be flag-wrapped), assess scope from the activity log, classify severity. Notify: affected users where required, {{regulatorpath, e.g. "the DPA within 72 hours if personal data is involved"}}, and serious-incident reporting where the EU AI Act's post-market duties apply to the system. Every incident gets an `incident` activity event at detection and a written post-incident review within {{days}}, including whether a checklist item should have caught it.
