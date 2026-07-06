# AI Policy Templates

Ready-to-fill policy documents for any project running the AI compliance checklist, and the source of the markdown files the package ships under `resources/policies/en/` (which `InitialPolicySeeder` imports and publishes as version 1.0 of each document — see section 9). Replace every `{{placeholder}}`, delete sections your classification answers switch off, and have counsel review before publishing. These are working templates written by engineers, not legal advice.

Placeholders used throughout: `{{company}}`, `{{product}}`, `{{contact_email}}`, `{{privacy_url}}`, `{{jurisdiction}}`, `{{dpo_or_contact_name}}`.

Document control applies to every policy here: each is its own document with a version and an effective date, changes go through the package's draft → publish flow (publishing supersedes only that document's prior version, which is what decides who needs re-consent), and superseded versions stay retrievable because consent records reference the exact document version they were given against.

---

## 1. AI transparency and use policy (public-facing)

Publish this at `{{privacy_url}}/ai` or as a section of the privacy policy. It's the umbrella document every disclosure and consent text links back to.

> **How {{product}} uses AI**
>
> Version 1.0, effective {{date}}.
>
> {{product}}, operated by {{company}}, uses artificial intelligence in the features described below. This page explains where AI is involved, what data it touches, what choices you have, and how to reach us.
>
> **Where AI is used.** We use AI for: {{list the features, e.g. "answering support questions in our chat assistant, generating content suggestions, recommending items based on your activity, and personalizing what you see"}}. Features not listed here do not use AI. When we add an AI feature, we update this page and, where the law or our policies require it, ask for your permission first.
>
> **You'll always know when you're talking to AI.** Any chat, voice, or messaging feature powered by AI tells you so before or at the start of the conversation. AI in {{product}} never presents itself as a human being.
>
> **AI-generated content is labeled.** Content that {{product}} generates with AI carries a machine-readable marker, and where it could be mistaken for real people or events, a visible label as well.
>
> **The AI can be wrong.** AI output in {{product}} can be inaccurate, incomplete, or outdated. Don't rely on it as professional advice. Where a feature touches health, money, or legal matters, we say so in the feature and point you to a qualified human.
>
> **Your choices.** You control, separately, whether we may: use your data to improve or train AI models; process your conversations with our AI assistant; use AI to recommend content or products to you; and use AI to personalize your experience. Manage these anytime at {{settings_path}}. Saying no to any of them never blocks you from the non-AI parts of {{product}}.
>
> **Human review.** Where AI contributes to a decision that meaningfully affects you, a human with authority to change the outcome reviews it, and you can ask for that review. See section 4 of this policy set if that applies to {{product}}.
>
> **The AI providers we work with.** We use models from: {{provider list, e.g. "Anthropic (Claude), OpenAI (GPT models)"}}. Our contracts with them state whether your content may be used to train their models; our default is that it may not unless you've opted in. Details per provider: {{provider_registry_url_or_summary}}.
>
> **Questions or complaints.** Contact {{dpo_or_contact_name}} at {{contact_email}}. You can also use the rights described in our privacy policy at {{privacy_url}}.

---

## 2. Consent notice texts (the four consent types)

Each type gets a short text (rendered next to the toggle, the `short:` frontmatter line of its file) and a long text (the expandable detail, the file's markdown body). Each ships as its own document file — see the mapping in section 9.

### 2.1 `ai_training` (AI training permissions)

Short: "Allow {{company}} to use my content and activity to improve and train AI models. Off by default. You can withdraw anytime and we'll stop using your data going forward."

Long:

> If you turn this on, {{company}} may use content you create in {{product}} and how you use its features to improve our AI features, including fine-tuning models. What this covers: {{concrete data list, e.g. "documents you create, prompts you write, and feature usage patterns"}}. What it never covers: {{exclusions, e.g. "payment details, private messages marked confidential"}}. We remove or pseudonymize direct identifiers before training use. If you withdraw, we stop including your data in future training runs; models already trained aren't retroactively changed, which is a technical limit we want you to know about upfront. Where we use third-party AI providers, this setting also controls the "do not train" flag we send them with your requests. Legal basis: consent. Withdraw at {{settings_path}} or by emailing {{contact_email}}.

### 2.2 `ai_chatbot` (AI chatbot interactions)

Short: "Allow the AI assistant to process my messages so it can respond. Required to use the assistant; the rest of {{product}} works without it."

Long:

> The assistant in {{product}} is AI, not a person. To answer you, it processes what you type and relevant context from your account ({{context list}}). Conversations are retained for {{retention period}} for quality and safety, then deleted; you can delete a conversation immediately from its menu. Your conversations are not used to train models unless you've separately turned on AI training permissions above. The assistant can make mistakes; for anything involving {{sensitive domains relevant to the product}}, talk to a qualified person. Provider: {{provider}}, processing in {{endpoint_region}}. Legal basis: consent (or performance of contract where the assistant is the service you signed up for; pick one, don't claim both).

### 2.3 `ai_recommendations` (AI recommendation engines)

Short: "Let {{product}} use AI to suggest {{items}} based on your activity. Turning this off gives you non-personalized defaults instead."

Long:

> With this on, we analyze your activity in {{product}} ({{signals list, e.g. "items viewed, saved, and purchased"}}) to rank and suggest {{items}}. With it off, you see {{fallback, e.g. "editorially curated or most-popular items"}}, and we don't build a recommendation profile about you. Profiles are kept while your account is active and deleted within {{period}} of account deletion. Legal basis: {{consent, or legitimate interest with the balancing test on file; if legitimate interest, this toggle is an objection control and the default may be on, say so honestly}}.

### 2.4 `ai_personalization` (AI personalization)

Short: "Let {{product}} use AI to adapt the interface and content to you. Off means everyone-sees-the-same defaults."

Long:

> This setting lets us tailor {{surfaces, e.g. "your home feed, notification timing, and suggested actions"}} using AI models that learn from your usage. It does not affect prices or credit, insurance, employment, or similar consequential decisions; those, where they exist in {{product}}, are governed by section 4 with their own notices. Data used: {{list}}. Retention: {{period}}. Legal basis: consent. Withdrawing resets your personalization profile within {{period}}.

---

## 3. AI training data and crawler policy (site operators)

Publish the statement, and keep the machine-readable files aligned with it. The package's `consent.crawler_signals` check verifies the files exist and parses them; keeping them truthful is on the operator.

> **{{company}} training-data statement.** Content on {{domain}} {{"may" / "may not"}} be used for AI model training by third parties. We express this preference in machine-readable form (robots.txt and llms.txt below) and, for EU purposes, as a reservation of rights under Article 4(3) of Directive (EU) 2019/790 where we've opted out of text and data mining. These signals state our preference; they aren't technical enforcement, and we pursue misuse through the terms of service at {{terms_url}}.

robots.txt block for a no-training stance (edit the agent list to the operator's actual choice, and drop this block entirely if the stance is "allowed"):

```
# ai training crawlers: adjust to your policy, this example opts out
User-agent: GPTBot
Disallow: /

User-agent: ClaudeBot
Disallow: /

User-agent: Google-Extended
Disallow: /

User-agent: CCBot
Disallow: /
```

llms.txt skeleton (optional, model-facing guidance rather than a standard with legal force):

```
# {{company}}
> {{one-line description of the site}}

## Policy
- Training use of this site's content: {{allowed | not allowed | contact us}}
- Contact: {{contact_email}}

## Key pages
- [{{page}}]({{url}}): {{what it is}}
```

If we (not a vendor) develop a public generative AI system reaching California users, add the AB 2013 training-data documentation page: sources and categories of training data at a high level, whether personal or copyrighted data is included, collection period, and cleaning/processing applied. As deployers only, we instead file each vendor's version of this in the provider registry.

---

## 4. Automated decisions and human review policy

Only ships when classification question 4 is yes. Publish the notice; keep the procedure internal.

Public notice template:

> **When {{product}} uses AI in decisions about you.** For {{decision types, e.g. "application screening"}}, {{product}} uses automated tools to {{role, e.g. "score and rank applications"}}. The tool's output {{"is reviewed by a person before any decision takes effect" / "materially influences the decision"}}. You have the right to: know this is happening (this notice), receive an explanation of the main factors behind an adverse outcome within {{30 days or your stricter commitment}}, have the outcome reviewed by a person with authority to change it, and correct inaccurate data we used. To use any of these, contact {{contact_email}} with the subject "decision review". Where you're in a jurisdiction that grants you an opt-out from solely automated decisions, {{describe the alternative human process}}.

Internal procedure (keep with the runbook, not published):

> Reviewers must have training on the tool's known failure modes, access to the underlying inputs, and authority to overturn. Every review and override is logged as an `override` activity event. Override rates are reported monthly; a sustained 0% rate triggers a process audit, since it usually means rubber-stamping. Bias testing runs {{cadence}} against {{protected attributes and metrics}}; NYC-scope employment tools additionally get the annual independent LL144 audit with published results. Model or prompt changes to decision-adjacent systems require re-running the test suite before release.

---

## 5. AI data protection addendum (extends the privacy policy)

> **AI processing details.** Beyond the uses described in our privacy policy, AI features in {{product}} involve: sending relevant data to the AI providers listed in section 1 to generate responses (processing regions listed per provider); retaining AI conversation logs for {{period}}; and, where you've consented, including your data in model improvement as described in the AI training consent text. We minimize what reaches third-party models: {{measures, e.g. "direct identifiers are stripped or pseudonymized from prompts where the feature allows it"}}.
>
> **Your rights work against AI data too.** Access, correction, deletion, and objection requests cover AI-held data about you: conversation logs, recommendation and personalization profiles, and document embeddings linked to your account. Deletion propagates to derived stores within {{period}}. Request via {{privacy_url}} or {{contact_email}}; we respond within the time your jurisdiction's law sets.
>
> **Retention.** Consent records are kept while we rely on them plus the limitation period of {{jurisdiction}}. AI activity logs are kept {{period}}. Conversation logs: {{period}}. Training snapshots that include personal data: {{period and review cadence}}.

---

## 6. Internal acceptable AI use policy (staff-facing)

> Staff may use approved AI tools (the provider registry is the approved list) for {{permitted uses}}. Never paste into any AI tool: customer personal data beyond what the tool's DPA covers, credentials or keys, unreleased financials, or anything under NDA, unless the tool is on the registry with a "customer data permitted" flag. AI output shipped to customers or published follows the labeling rules in policy 1 and gets human review appropriate to the stakes; the person who ships it owns it, "the AI wrote it" isn't a defense (and in Utah, statutorily isn't one). Prompt-injection hygiene applies when building with AI: treat retrieved or user-supplied content as data, never as instructions, and treat model output as untrusted input. Suspected AI-related incidents (leaked data in a prompt, harmful output that reached a user, a decision tool misbehaving) go to {{incident_channel}} immediately under policy 7.

---

## 7. AI incident response policy

> An AI incident is any event where an AI feature caused or plausibly could cause harm: wrong output that reached a user in a consequential context, personal data exposed through a prompt or completion, a decision tool producing discriminatory patterns, generated content that should have been blocked, or a provider-side breach affecting our data. Triage within {{hours}}: contain (feature flag off via the package's feature toggle, which is why decision-adjacent features must be flag-wrapped), assess scope from the activity log, classify severity. Notify: affected users where required, {{regulatorpath, e.g. "the DPA within 72 hours if personal data is involved"}}, and serious-incident reporting where the EU AI Act's post-market duties apply to the system. Every incident gets an `incident` activity event at detection and a written post-incident review within {{days}}, including whether a checklist item should have caught it.

---

## 8. AI vendor policy

> No AI provider is used in production before its registry row is complete: DPA signed, training-on-our-data position confirmed and configured (default: our customer content is not used for provider training), sub-processor list reviewed, processing regions recorded, model/system documentation filed, output-marking support confirmed for generative features, and deprecation/notice terms in the contract. Rows are re-reviewed every {{cadence}} and on any material vendor change; the package flags lapsed reviews. Each provider needs an exit note: what breaks if we swap it, and where consent state and logs live (answer: with us, which is the point).

---

## 9. File mapping

The sections of this document ship as per-locale markdown files under the package's `resources/policies/en/` (publishable into the app, where edits survive package updates). Each file carries YAML frontmatter (`title`, `type`, and for consent texts a `short:` line rendered next to the toggle); the body is the long text. `{{placeholders}}` stay intact so the install command can prompt for company name and contact.

| Section | File | Document slug | Type |
|---|---|---|---|
| 1 AI transparency and use policy | `transparency.md` | `transparency` | `policy` |
| 2.1 AI training permissions | `consent/ai_training.md` | `consent.ai_training` | `consent_text` |
| 2.2 AI chatbot interactions | `consent/ai_chatbot.md` | `consent.ai_chatbot` | `consent_text` |
| 2.3 AI recommendation engines | `consent/ai_recommendations.md` | `consent.ai_recommendations` | `consent_text` |
| 2.4 AI personalization | `consent/ai_personalization.md` | `consent.ai_personalization` | `consent_text` |
| 3 Training data and crawler policy | `training-data.md` | `training-data` | `policy` |
| 4 Automated decisions and human review | `automated-decisions.md` | `automated-decisions` | `policy` |
| 5 AI data protection addendum | `data-protection.md` | `data-protection` | `policy` |
| 6 Internal acceptable AI use | `acceptable-use.md` | `acceptable-use` | `policy` |
| 7 AI incident response | `incident-response.md` | `incident-response` | `policy` |
| 8 AI vendor policy | `vendor.md` | `vendor` | `policy` |
| — disclosure lines (below) | `disclosures/{chat,content,decision}.md` | `disclosure.{chat,content,decision}` | `disclosure` |

The three disclosure texts:

- `chat`: "You're chatting with an AI assistant, not a person. It can make mistakes."
- `content`: "Generated with AI by {{product}}."
- `decision`: "This outcome was informed by an automated tool. You can request human review at {{contact_email}}."

`InitialPolicySeeder` is a thin wrapper over the policy file sync: every file becomes an `ai_policy_documents` row with a published version 1.0 whose translation stores the file's markdown, compiled HTML, and checksums. All fourteen documents are seeded — the consent and disclosure texts because the consent UI and disclosure components render them, and the long-form policies because the same versioning, editing, and staleness machinery serves them at `GET /ai-compliance/policies/{slug}`. Sections 6 and 7 are internal procedures; they seed as `active = false` so they version and render in the admin without appearing on any public surface unless the operator switches them on.
