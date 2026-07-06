# AI Compliance Checklist for Software Projects

A working checklist for building and shipping software that uses AI, written to serve two jobs at once: a requirements source when we build compliance tooling (like the WordPress AI Compliance plugin this grew out of), and a project checklist we and our customers run against any product that touches AI.

Accurate as of July 2026. Regulation in this space moves monthly; section 3 lists the dates that matter and section 12 lists sources to re-check. Nothing here is legal advice. It's an engineering-side compliance program that a lawyer should review for any specific product and market.

---

## 1. How to use this document

Three ways, depending on who's holding it:

As a **project intake gate**: run section 2's classification questions at project kickoff. The answers switch checklist sections on or off, so a marketing site with one chatbot doesn't carry the same load as an HR screening product.

As a **requirements source for tooling**: every checklist item in sections 4-10 has an evidence line. If an item's evidence can be produced or verified by software, it becomes a feature (the tool spec in section 11 does exactly that mapping). Items whose evidence is a document or a decision become tasks in the project tracker instead.

As a **customer-facing deliverable**: the checklist doubles as the thing we hand customers to show what "AI compliant" means concretely for their project, and what stays their responsibility after handover (marked "operator duty" where relevant).

Status model, matching the plugin dashboard: **OK** (implemented, evidence exists), **Review** (partially done or evidence stale), **Fail** (missing), **N/A** (switched off by classification, with the reason recorded). An item is only OK if its evidence line can be satisfied on demand.

---

## 2. Classify the project first

Answer these before touching the checklist. Record the answers; they're themselves evidence.

1. **Role.** Are we a *provider* (we build or substantially modify the AI system) or a *deployer* (we integrate someone else's model via API)? Usually both, in parts. EU AI Act duties split on this line, and so do most US state laws.
2. **Does the system interact directly with people?** Chatbots, voice agents, AI support, companion features. If yes: disclosure duties (section 5) apply.
3. **Does it generate synthetic content** (text, image, audio, video)? If yes: marking and labeling duties apply, including machine-readable provenance.
4. **Does it make or materially influence consequential decisions** about people (employment, credit, housing, insurance, education, healthcare, government services, legal)? If yes: the automated-decision block (section 8) applies, and the project is in the highest-scrutiny tier.
5. **Does it process personal data** for training, fine-tuning, RAG, or inference? If yes: the privacy block (section 7) applies in full.
6. **Does it touch biometrics or emotion recognition?** Screen against the EU AI Act Article 5 prohibitions before anything else; some uses are banned outright, permitted ones carry notice duties.
7. **Which markets?** EU/EEA, UK, US (which states' residents), Canada, Brazil, China. Duties are market-of-the-user, not company location.
8. **Who are the users?** Consumer-facing raises disclosure and consent weight; minors add companion-chatbot and content-safety duties (California SB 243, TRAIGA's CSAM bans, TAKE IT DOWN Act exposure).
9. **Is any content publicly published** from AI output (articles, product descriptions, images)? If yes: labeling and provenance duties apply to the publishing surface.
10. **Do we train or fine-tune on data we collect** (including customer content and site content)? If yes: training-data transparency, consent, and crawler-signal duties (section 6) apply.

---

## 3. Regulatory map (what binds, whom, and when)

| Regime | Applies to | Core obligations relevant here | Status / key dates |
|---|---|---|---|
| **EU AI Act** (Reg. 2024/1689) | Providers and deployers placing systems on the EU market or affecting EU users | Article 5 prohibitions (since Feb 2, 2025); GPAI model duties incl. training-content summary (since Aug 2, 2025); **Article 50 transparency from Aug 2, 2026**: disclose AI interaction at first contact, machine-readable marking of synthetic output, deepfake labeling, emotion-recognition notice; AI literacy duty | In force. Digital Omnibus (provisional agreement May 7, 2026, formal adoption pending): Annex III high-risk duties deferred to Dec 2, 2027; gen-AI systems already on market before Aug 2, 2026 get until Dec 2, 2026 for the machine-readable marking specifically. Enforcement powers and fines (up to EUR 15M / 3% turnover for these duties) active Aug 2, 2026 |
| **GDPR** + EDPB guidance | Anyone processing EU personal data in AI training or inference | Legal basis for training and inference, DPIA for high-risk processing, data-subject rights against AI systems, Art. 22 automated-decision limits, transparency (Arts. 13/14) | In force, actively enforced against AI use by DPAs |
| **California** | Developers/deployers reaching CA users | AB 2013: publish training-data documentation (since Jan 1, 2026); SB 942/AB 853 AI Transparency Act: detection tool + manifest and latent disclosures for large GenAI providers (from Aug 2, 2026; platforms 2027, capture devices 2028); SB 243 companion chatbots: AI disclosure + minor safeguards; AB 489 healthcare AI claims; SB 53 frontier-model transparency; CCPA ADMT regs (risk assessments 2026, notices/opt-outs Jan 2027) | Effective per-law as listed |
| **Colorado SB 26-189** | Developers/deployers of automated decision-making tech affecting CO consumers | Replaced SB 24-205 (repealed May 14, 2026, never took effect). Pre-use consumer notice, adverse-outcome explanation within 30 days, human review rights, developer documentation; AG enforcement, 60-day cure | Effective Jan 1, 2027 |
| **Texas TRAIGA** | Developers/deployers doing business in TX | Prohibited uses (behavioral manipulation, incitement, CSAM/deepfake sexual material, government social scoring); agency AI disclosure; healthcare AI disclosure | Effective Jan 1, 2026 (final law much narrower than drafts; no broad high-risk regime) |
| **Utah SB 149** (as amended) | Businesses using GenAI with UT consumers, esp. regulated professions | Disclose AI on clear consumer request; proactive disclosure in high-risk/regulated interactions; no "the AI did it" defense | In force since May 2024, amended 2025 |
| **Illinois HB 3773 / NYC LL144** | Employers using AI in employment decisions | IL: civil-rights protections and notice for AI in employment (since Jan 1, 2026); NYC: annual bias audits + candidate notice for automated employment decision tools | In force |
| **TAKE IT DOWN Act** (US federal) | Platforms hosting user content | Removal process for nonconsensual intimate imagery incl. AI-generated, 48-hour takedown | Removal-process duties enforced from May 2026 |
| **China labeling measures** | Services generating synthetic content for Chinese users | Explicit and implicit (metadata) labeling of AI-generated content | In force since Sept 1, 2025 |
| **Voluntary frameworks** | Everyone, as baseline and safe-harbor credit | NIST AI RMF (govern/map/measure/manage), ISO/IEC 42001 AI management systems, EU GPAI Code of Practice, EU Code of Practice on AI-generated content (final 2026) | Voluntary; adopting NIST AI RMF or ISO 42001 covers most overlap across regimes and is the cheapest way to run one program for many laws |

One US caveat: a federal executive order (and follow-on FTC/Commerce actions due through 2026) is pushing preemption of some state AI laws. Nothing is preempted yet; treat state laws as binding and keep the program flexible.

---

## 4. Governance, inventory, and accountability

The foundation everything else references. Cheap to do early, painful to retrofit.

- [ ] **AI system inventory exists and is current.** Every AI feature, model, and integration is registered: name, purpose, model/provider, version, role (provider/deployer), risk classification from section 2, markets, owner.
  Evidence: populated registry (the plugin's "AI Providers" screen is the seed of this), reviewed at least quarterly and on every release that adds or changes an AI feature.
- [ ] **At least one AI provider/vendor is registered per AI feature.** No "unknown model" entries. Record vendor, model name and version, endpoint region, DPA status, sub-processor list link.
  Evidence: registry rows with all fields filled; the dashboard "Review" flag clears only when count > 0 and fields are complete.
- [ ] **An accountable owner is named** for AI compliance overall and per project. Their contact is published where required (privacy notice, imprint).
  Evidence: data-protection contact set in the tool; name appears in the privacy notice.
- [ ] **AI policy versioning is in place.** Consent texts, disclosures, and the privacy notice carry a version number; recorded consents reference the version they were given against (the plugin's `Policy 1.0` column).
  Evidence: version field on every consent record; changelog of policy texts.
- [ ] **AI literacy / staff training done** for people who build, operate, or make decisions with AI systems (an explicit EU AI Act duty since Feb 2025, and the thing regulators ask about first).
  Evidence: training record with dates and attendees.
- [ ] **A risk framework is adopted** (NIST AI RMF or ISO/IEC 42001) and the project is mapped to it. This is voluntary but earns safe-harbor or mitigation credit in several regimes and collapses many laws into one program.
  Evidence: mapping doc, even a short one.
- [ ] **Prohibited-use screening done.** The project is checked against EU Article 5 prohibitions and TRAIGA's banned purposes (manipulation of vulnerable people, social scoring, untargeted face scraping, emotion recognition in work/education, CSAM/nonconsensual intimate deepfakes). This is a hard gate, not a mitigation exercise.
  Evidence: signed-off screening record; N/A only with the reasoning written down.

## 5. Transparency and disclosure (operator-facing surfaces)

The block behind the plugin's "AI disclosure banner is active" check. From Aug 2, 2026 most of this is EU law with real fines, and several US states already require pieces of it.

- [ ] **AI interaction disclosure at first contact.** Any chatbot, voice agent, or AI assistant tells the user it's AI no later than the first interaction, in clear, accessible form. Utah adds: also disclose whenever a consumer plainly asks. California SB 243 adds specific rules for companion-style chatbots, including recurring reminders for minors.
  Evidence: the disclosure is rendered in the UI (screenshot in the release record), can't be disabled below the legal minimum, and its presence is one of the tool's dashboard checks.
- [ ] **Machine-readable marking of synthetic output.** If the product generates audio, image, video, or text, outputs are marked as AI-generated in a machine-readable way (metadata/watermark per the emerging EU Code of Practice on AI-generated content and C2PA-style provenance). Deployer-side note: if we integrate a third-party model, the marking duty still lands on whoever provides the *system*; verify the model's marking survives our pipeline (resizing, transcoding, copy-paste).
  Evidence: sample outputs pass a marking/detection check in CI; vendor contract clause requiring marking support.
- [ ] **Visible labeling of deepfakes and AI-published content.** Content that depicts real people or events, or AI-generated text published on matters of public interest, carries a visible label in addition to the machine-readable mark.
  Evidence: labeling appears on the publishing surface; editorial workflow blocks unlabeled publication.
- [ ] **AI content detection tool (large CA providers).** If the product is a GenAI system with 1M+ monthly users reaching California, SB 942/AB 853 requires a free public detection tool plus manifest and latent disclosures from Aug 2, 2026.
  Evidence: tool live and linked; N/A for smaller products with the user-count recorded.
- [ ] **Emotion recognition / biometric categorization notice.** If (and only if) a permitted use survives the prohibition screening in section 4, exposed individuals get clear notice.
  Evidence: notice text and placement; screening record referenced.
- [ ] **Sector disclosures.** Healthcare interactions disclose AI involvement (California AB 489, Texas health provisions, Utah regulated professions); no AI output implies a human professional license it doesn't have.
  Evidence: sector checklist item switched on by classification question 8, with UI proof.
- [ ] **Disclosure is honest in both directions.** No "human" personas on bots, and no AI-washing (claiming AI where there is none); both draw consumer-protection enforcement.
  Evidence: copy review in the release checklist.

## 6. Consent, preferences, and training-data controls

The block behind the plugin's consent log and "AI training preferences are published" check.

- [ ] **Consent types are explicit and granular.** At minimum the four the plugin models, adapted per project: AI training permissions (may we use your data to train/fine-tune), AI chatbot interactions (may we process your conversations, and may they be retained), AI recommendation engines, AI personalization. Granular means separately grantable and deniable; no bundling into general ToS acceptance where a consent legal basis is claimed.
  Evidence: consent UI with independent toggles; denied-by-default for anything relying on consent.
- [ ] **Every consent event is logged** with: consent type, status (granted/denied/withdrawn), subject identifier (or "guest" plus a stable pseudonymous key), source (which UI or API produced it), policy version shown, and UTC timestamp. Withdrawal is as easy as granting and is logged the same way.
  Evidence: the consent log itself, exportable (CSV at minimum), filterable by type and status, exactly as the plugin screen does.
- [ ] **Denial has teeth.** A denied or withdrawn "training" consent actually excludes that subject's data from training pipelines and vendor data-sharing flags (e.g. API "do not train" parameters set per request). This is the item most implementations fake.
  Evidence: a traceable path from consent state to pipeline filter and to the vendor request flag; a test that flips consent and observes the flag change.
- [ ] **Crawler and training signals are published.** robots.txt declares the site's AI-crawler policy (GPTBot, ClaudeBot, Google-Extended, CCBot and peers, matching the operator's actual choice), llms.txt if the operator wants to state model-facing preferences, and for EU-relevant content a machine-readable TDM rights reservation where opting out of text-and-data mining is intended. These signal preference; they don't enforce it, and the doc should say so to customers.
  Evidence: files served with correct content; the tool verifies presence and parses the policy (the plugin's robots.txt/llms.txt check).
- [ ] **Training-data documentation published (developer duty).** If we develop a public GenAI system reaching California, AB 2013 requires a high-level published summary of training data sources. EU GPAI providers owe a training-content summary using the Commission template. As deployers, we instead collect the vendor's version of this.
  Evidence: published page, or vendor's summary filed in the provider registry.
- [ ] **Customer content is contractually protected.** Contracts and vendor settings state whether customer/user content may be used for provider training; default is no unless the customer opts in.
  Evidence: DPA/terms clause plus the vendor console setting recorded in the registry.

## 7. Privacy and data protection in AI processing

- [ ] **Legal basis identified per processing purpose.** Training, fine-tuning, RAG indexing, and inference each have a named basis (consent, contract, legitimate interest with a documented balancing test). EDPB guidance treats "we scraped it" skeptically; legitimate-interest claims for training need the balancing test written down.
  Evidence: record of processing activities (RoPA) rows for each AI purpose.
- [ ] **DPIA performed** where AI processing is likely high-risk (profiling, large-scale, sensitive data, consequential decisions). Colorado's replacement law dropped mandatory impact assessments, but GDPR still requires DPIAs and California's ADMT regs require risk assessments; do one whenever classification question 4 or 5 is yes.
  Evidence: DPIA document, reviewed on major model or purpose change.
- [ ] **Data minimization in prompts and logs.** PII is stripped or pseudonymized before it reaches third-party models where feasible; prompt/response logs have a retention period and a redaction policy.
  Evidence: middleware redaction in the request path; retention config; sampled log audit.
- [ ] **Data-subject rights work against AI features.** Access, deletion, and objection cover AI-held data: conversation logs, embeddings/RAG stores keyed to a person, personalization profiles. Deletion propagates to vector stores and caches, and the request is answered within statutory time.
  Evidence: a tested DSR runbook that includes the AI stores; deletion produces a verifiable absence.
- [ ] **Cross-border transfer position documented** for model endpoints (region of inference, SCCs/DPF status of the vendor).
  Evidence: registry field per provider; transfer assessment where needed.
- [ ] **Retention schedule set** for consent records (keep while relied upon plus limitation period), activity logs, prompts/outputs, and training snapshots.
  Evidence: retention config in the tool; scheduled pruning jobs.

## 8. Automated decisions and human oversight

Applies when classification question 4 is yes. The strictest block, and the one customers in HR, lending, insurance, and healthcare will be audited on.

- [ ] **Pre-use notice.** People are told, before or at the point of decision, that automated decision-making technology materially influences a consequential decision about them, and what its role is (GDPR Arts. 13/14 and 22; Colorado SB 26-189 from Jan 2027; California ADMT notices from 2027; Illinois employment notice now).
  Evidence: notice copy and placement per decision surface.
- [ ] **Human review is real.** A person with authority, competence, and the information needed can review and overturn the AI outcome; the UI supports it and the log records it. Rubber-stamp review counts as none.
  Evidence: review queue exists; override rate is monitored (0% overrides over a long period is a red flag, not a success metric).
- [ ] **Adverse-outcome explanation.** On a negative consequential decision, the person gets an explanation (Colorado: within 30 days, plus correction and human-review rights), including the principal factors involved.
  Evidence: templated explanation flow wired to decision records.
- [ ] **Bias testing and audits.** Employment tools serving NYC candidates get the LL144 annual bias audit with published results and candidate notice; other consequential-decision systems get documented pre-deployment and periodic disparate-impact testing.
  Evidence: audit reports with dates; test datasets and metrics retained.
- [ ] **Opt-out / alternative path** where law provides one (GDPR Art. 22 right not to be subject to solely automated decisions with legal effect; California ADMT opt-outs from 2027).
  Evidence: functioning opt-out that routes to a human process.

## 9. Logging, auditability, and evidence

The block behind the plugin's "Activity logging is enabled" check and the activity log screen. Logging is what turns every other section from claims into evidence.

- [ ] **AI activity log captures every material AI event**: inference calls (feature, provider, model, purpose), consent changes, provider registry changes, disclosure-setting changes, decision outcomes and overrides, DSR actions touching AI stores. Fields: event type, actor (user/system), subject where applicable, timestamp (UTC), and enough context to reconstruct what happened without storing raw sensitive content.
  Evidence: the log, with the "Logged AI events" counter on the dashboard nonzero and moving.
- [ ] **Logs are tamper-evident and access-controlled.** Append-only storage or hash chaining for compliance-relevant logs; role-based access; log access is itself logged.
  Evidence: storage design note; access-control config.
- [ ] **Exports work.** Consent log and activity log export to CSV/JSON on demand (the plugin's Export CSV), scoped by date and type, for auditors and customer requests.
  Evidence: export produced during the go-live test.
- [ ] **Reports are generatable.** A point-in-time compliance report: checklist status, consent statistics (granted/denied per type, like the dashboard tiles), provider registry, recent incidents. This is the artifact customers attach to their own audits.
  Evidence: the Generate Report output, dated and versioned.
- [ ] **Serious-incident process exists.** AI malfunctions with harm potential get logged, assessed, and where thresholds are met, reported (EU AI Act post-market duties for in-scope systems; contractual duties otherwise).
  Evidence: incident runbook plus at least one tabletop test.

## 10. Vendors, security, and content safety

- [ ] **Vendor due diligence per provider**: DPA signed, training-on-our-data position confirmed and configured, sub-processors reviewed, model/system card collected, marking-support and uptime/deprecation terms in contract, exit plan (can we swap the model without losing compliance state).
  Evidence: completed due-diligence record per registry row.
- [ ] **Prompt-injection and output-handling controls.** Untrusted content reaching the model is treated as data, not instructions; model output is treated as untrusted input to downstream systems (no blind tool execution, output encoding before rendering). OWASP LLM Top 10 is the working reference.
  Evidence: threat-model note; injection test cases in CI.
- [ ] **Secrets and access.** Model API keys in a secret manager, per-environment, rotated; least-privilege scopes; usage anomaly alerts (a leaked key shows up as a bill first).
  Evidence: secret-manager config; rotation record.
- [ ] **Content-safety controls proportionate to the surface.** Moderation for user-facing generation, minor-protection measures where minors are plausible users (SB 243, TRAIGA), NCII/CSAM prevention and the TAKE IT DOWN removal path if users can publish imagery.
  Evidence: moderation config; takedown workflow with the 48-hour clock tracked.
- [ ] **Rate limiting and abuse controls** on AI endpoints, both for cost and because abuse of your endpoint becomes your compliance problem.
  Evidence: limiter config and alerting.

---

## 11. Tool specification (turning the checklist into software)

This is the requirements set for a compliance tool, whether that's the WordPress plugin, a Laravel package, or a module inside a customer project. It's derived directly from the checklist evidence lines and the plugin screenshots.

### 11.1 Entities

Named abstractly here; the package in section 12 implements each one as a table with an `ai_` prefix (`consent_records` becomes `ai_consent_records`, and so on), with the exact columns and morph naming shown there.

**providers** (the AI Providers screen)
`id, organization_id/site_id, name, vendor, model_name, model_version, endpoint_region, role (provider|deployer), purpose, dpa_signed_at, trains_on_our_data (yes|no|configurable+setting), training_summary_url, sub_processors_url, marking_supported (bool), due_diligence_status, owner, created_at, updated_at`

**consent_records** (the Consent Log screen)
`id, organization_id/site_id, consent_type (slug from a configurable set; defaults: ai_training, ai_chatbot, ai_recommendations, ai_personalization), status (granted|denied|withdrawn), subjectable_type/subjectable_id (nullable morph to any authenticatable model), guest_key (pseudonymous key when there's no model), source (which form/API), policy_version, ip_hash (optional, jurisdiction-dependent), recorded_at (UTC)`
Append-only: a change writes a new row, never updates the old one. Current state = latest row per (subject, consent_type).

**consent_types**
`slug, label, description, legal_basis (consent|legitimate_interest|contract), default_state (denied unless basis != consent), active, created_at`

**activity_events** (the Activity Log screen)
`id, event_type (inference|consent_change|provider_change|setting_change|decision|override|dsr_action|export|incident), actorable_type/actorable_id (nullable morph; null means the system or a console command acted, with the source in context), subjectable_type/subjectable_id (nullable morph), provider_id (nullable), context (json, no raw sensitive content), recorded_at (UTC), hash_prev (tamper-evidence chain, optional tier)`

**checklist_items**
`key, section, label, description, applies_when (json rules against the classification answers), status (ok|review|fail|na), evidence_type (auto|manual), evidence_ref, last_verified_at, verified_by`

**classification_answers**
`project_id, question_key, answer, answered_by, answered_at` (the section 2 intake, stored so N/A statuses are explainable)

**policy documents / versions / translations**
Every user-facing text (long-form policy page, consent short/long text, disclosure line) is a markdown-authored *document* with a draft/published/superseded *version* stream and per-locale *translations* (source markdown, compiled html, checksums for staleness). What `policy_version` on consent rows points to is a specific published version of a specific document.

### 11.2 Functional requirements

FR-1. Dashboard shows: consents granted, consents denied (split by type on click), registered providers, logged AI events, and the checklist with OK/Review/Fail/N/A statuses. Mirrors the existing plugin dashboard, plus N/A.
FR-2. Automated checks run on a schedule and on demand: disclosure banner active (setting + a rendered-page probe), robots.txt and llms.txt present and parseable, provider registry non-empty with complete rows, activity logging enabled and receiving events, data-protection contact set, consent UI reachable, retention jobs scheduled. Each check writes its result and timestamp to `checklist_items`.
FR-3. Manual checklist items (DPIA done, training completed, vendor due diligence) accept an evidence upload or link plus a verifier and date, and auto-degrade from OK to Review after a configurable staleness period (default 12 months; 6 for bias audits).
FR-4. Consent API: record, withdraw, and query current state per subject; server-side enforcement hook so application code asks the tool "may I use this subject's data for X" instead of reading raw rows. Denied training consent must set the corresponding do-not-train flag on outbound provider calls where the provider supports one.
FR-5. Consent log UI: filter by type and status, paginate, Export CSV (and JSON), with the exact columns of the screenshot plus subject pseudonymization on export by default.
FR-6. Policy versioning: publishing a new consent text bumps the version; the tool can report which subjects consented under superseded versions and flag when re-consent is needed.
FR-7. Report generation: dated PDF/HTML snapshot of dashboard stats, checklist with evidence references, provider registry, and consent statistics, suitable to hand to an auditor or customer.
FR-8. Disclosure banner/notice component: configurable text per surface (chat, generation, decision), rendered from the tool so "banner active" is verifiable, with per-language variants.
FR-9. Retention and pruning: configurable retention per table, scheduled pruning, and pruning events written to the activity log.
FR-10. Roles: admin (configure), auditor (read + export), system (write events). Log reads by auditors are logged.
FR-11. Multi-project/multi-tenant: all entities carry the org/site key; exports and dashboards are scoped.
FR-12. Alerting: checklist item flips to Fail, consent-denial enforcement test fails, activity log goes silent for N hours, or a provider's DPA/due-diligence date lapses.

### 11.3 Acceptance tests that matter most

1. Flip a subject's ai_training consent to denied; observe the next outbound call to each provider carries the do-not-train flag and the training pipeline filter excludes the subject.
2. Delete a subject via DSR; verify their conversation logs, embeddings, and personalization rows are gone and the deletion event is in the activity log.
3. Render the chat surface as an anonymous user; the AI disclosure appears before the first model response.
4. Generate an image/text output; verify the machine-readable mark survives the product's own download/copy path.
5. Export the consent log; verify it matches the on-screen data and pseudonymizes subjects.
6. Kill the logging pipeline; verify the dashboard degrades the logging checklist item and alerts within the configured window.

---

## 12. Package concept: `laranail/ai-compliance` + `@laranail/ai-compliance`

The tool from section 11, shaped as installable packages instead of a standalone app. The Laravel package (`laranail/ai-compliance`, namespace `Simtabi\Laranail\AiCompliance`) owns the data, rules, and admin side. The JS package (`@laranail/ai-compliance`, with `@laranail/ai-compliance-react` bindings) owns everything the end user sees and connects to. Apps we build pull both in and get the whole program with a config file.

Why a package and not a SaaS: the consent log and activity log are exactly the data customers don't want leaving their infrastructure, and the enforcement hooks (blocking a training flag, gating a feature) have to run inside the app anyway. Prior art: the Laravel ecosystem has cookie-consent banners (spatie, whitecube, devrabiul) and generic GDPR consent records (foothing, Sellinnate), but nothing that ties consent to AI feature gating, provider registry, disclosure surfaces, checklist checks, and do-not-train enforcement in one place. That combination is the product.

### 12.1 Package skeleton

Built on the Laranail house toolchain, verified against Packagist on 2026-07-05:

- `laranail/package-tools` (v1.3.0): the service provider base. Its open API surface (`hasConfigFile()`, `hasViews()`, lifecycle hooks) is documented as intentionally compatible with spatie/laravel-package-tools, so the provider code below works as written. On top of that it adds attribute-driven discovery, `package-tools.*` artisan commands including `package-tools.doctor`, abstract HTTP controllers useful for the consumer/admin endpoints in this spec, a testing harness, and a consolidated fluent builder (config/views/components/assets/routes/middleware/events/commands/seeders) with array-batch forms plus `publishFile()`/`publishDirectory()`.
- `laranail/console` (v1.x): command UX. `Console\Tools` for output (formatter, spinners, progress, the enhanced Artisan command base this package's commands should extend) and `Console\Prompter` (fluent laravel/prompts wrapper with a form builder and 25 validators) for the install command's interactive steps. Note: `laranail/package-tools` already requires it, and re-exports the command base as `Simtabi\Laranail\Package\Tools\Commands\Command` with the `SupportsNamespacedNames` trait that makes `laranail::ai-compliance.*` names pass Symfony's validator.
- `laranail/database-tools` (v1.0, independent): worth adopting for the schema layer. Its `auditColumns()` and `configuredMorphs()` schema macros, audit observer, and soft-delete restore history overlap directly with the morph columns and audit logging specified below; prefer these macros over hand-rolled column definitions where they fit.

Toolchain consequence, stated plainly: `laranail/package-tools` requires PHP ^8.4.1 and Laravel 13 (illuminate ^13.0). Building on the house toolchain therefore sets this package's floor at **PHP 8.4.1+ / Laravel 13**, replacing the earlier PHP 8.2 / Laravel 11-13 target. Supporting older Laravel would mean the spatie fallback, which contradicts the house rule; take the newer floor. Tests on orchestra/testbench ^11 + Pest, Pint and PHPStan (larastan) in CI, matching the toolchain's own dev dependencies.

```json
{
    "name": "laranail/ai-compliance",
    "description": "ai consent, provider registry, disclosure, and compliance checks for laravel apps",
    "license": "MIT",
    "require": {
        "php": "^8.4.1 || ^8.5",
        "illuminate/contracts": "^13.0",
        "laranail/package-tools": "^1.3",
        "laranail/console": "^1.0",
        "laranail/database-tools": "^1.0",
        "league/commonmark": "^2.7",
        "symfony/yaml": "^7.0 || ^8.0"
    },
    "require-dev": {
        "larastan/larastan": "^3.0",
        "laravel/pint": "^1.18",
        "mockery/mockery": "^1.6",
        "orchestra/testbench": "^11.0",
        "pestphp/pest": "^4.0",
        "pestphp/pest-plugin-arch": "^4.0",
        "pestphp/pest-plugin-laravel": "^4.0",
        "phpstan/phpstan": "^2.0",
        "rector/rector": "^2.0"
    },
    "autoload": {
        "psr-4": { "Simtabi\\Laranail\\AiCompliance\\": "src/" }
    },
    "extra": {
        "laravel": {
            "providers": ["Simtabi\\Laranail\\AiCompliance\\AiComplianceServiceProvider"],
            "aliases": { "AiConsent": "Simtabi\\Laranail\\AiCompliance\\Facades\\AiConsent" }
        }
    }
}
```

```
laranail/ai-compliance
├── config/ai-compliance.php
├── database/
│   ├── migrations/            # plain dated anonymous-class migrations, discovered + run
│   ├── factories/
│   └── seeders/
├── packages/                  # npm workspaces, released in lockstep with the composer package
│   ├── core/                  # @laranail/ai-compliance (framework-agnostic)
│   ├── react/                 # @laranail/ai-compliance-react
│   └── vue/                   # @laranail/ai-compliance-vue
├── resources/
│   ├── lang/en/ai-compliance.php
│   ├── policies/en/           # the shipped, editable policy markdown (per-locale dirs)
│   │   ├── consent/           # ai_training.md, ai_chatbot.md, ai_recommendations.md, ai_personalization.md
│   │   ├── disclosures/       # chat.md, content.md, decision.md
│   │   └── *.md               # transparency.md, training-data.md, automated-decisions.md, ...
│   └── views/components/      # disclosure, gate, policy, preferences shell
├── routes/
│   ├── api.php                # consumer endpoints for the js sdk
│   └── admin.php              # admin json endpoints
├── src/
│   ├── AiComplianceServiceProvider.php
│   ├── AiCompliance.php       # manager bound to the container
│   ├── Facades/AiConsent.php
│   ├── Enums/                 # ConsentStatus, CheckStatus, LegalBasis, ActivityType, PolicyType, PolicyVersionStatus
│   ├── Models/
│   ├── Policies/              # laravel authorization policies for the admin api
│   ├── Policy/                # the markdown pipeline: loader, compiler, shortcodes, placeholders, repository, cache
│   ├── Http/{Controllers,Middleware,Resources,Requests}/
│   ├── Checks/                # Check contract + built-in checks
│   ├── Providers/             # ai provider client wrappers (openai, anthropic, custom)
│   ├── Filament/              # optional filament plugin module, no-ops unless filament is installed
│   ├── Livewire/              # optional livewire components, gated the same way
│   ├── Events/  Listeners/  Notifications/  Console/
│   └── Support/               # SubjectResolver, GuestKey
└── tests/
```

The service provider registers everything through package tools and adds the pieces it doesn't cover:

```php
namespace Simtabi\Laranail\AiCompliance;

use Simtabi\Laranail\Package\Tools\Package;
use Simtabi\Laranail\Package\Tools\Providers\PackageServiceProvider;

class AiComplianceServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('laranail/ai-compliance')
            ->setPublishTagId('ai-compliance')
            ->hasConfigFile()
            ->hasViews('ai-compliance')
            ->hasTranslations()
            ->hasRoutes('api', 'admin')
            ->discoversMigrations()
            ->runsMigrations()
            ->publishDirectory('resources/policies', 'resources/policies/ai-compliance')
            ->hasCommands([
                Console\InstallCommand::class,      // publish + migrate + sync + seed defaults
                Console\AuditCommand::class,        // run all checks now
                Console\FeatureCommand::class,      // enable/disable features per org
                Console\PolicyShowCommand::class,   // render a compiled policy in the terminal
                Console\PolicySyncCommand::class,   // import shipped/edited md into document rows
                Console\PolicyPublishCommand::class,
                Console\NotifyReconsentCommand::class,
                Console\ExportCommand::class,
                Console\PruneCommand::class,
            ]);
    }

    #[\Override]
    public function packageBooted(): void
    {
        $this->registerAuthorizationPolicies();  // Gate::policy() per model
        $this->registerMorphMap();
        $this->registerMiddlewareAliases();      // ai.feature, ai.consent
        $this->registerScheduledChecks();
        $this->registerBladeComponents();
    }

    protected function registerMorphMap(): void
    {
        // stored *_type morph values stay short and stable even if the host renames classes.
        // enforceMorphMap makes unmapped classes throw instead of silently writing fqcns.
        Relation::enforceMorphMap([
            'user'  => config('laranail.ai-compliance.user_model', config('auth.providers.users.model')),
            'guest' => Support\GuestSubject::class,
        ] + config('laranail.ai-compliance.morph_map', []));
    }
}
```

Config keys resolve under the vendor-namespaced `laranail.ai-compliance.*` (package-tools'
config namespacing), and migrations are discovered from `database/migrations` rather than
listed one by one — the table set is the ten tables in 12.2.

### 12.2 Database layer

Every table carries a nullable `tenant_id` (configurable column name and resolver, so it fits single-tenant apps and the multi-tenant setups from the integration guide without forcing either). Primary keys are house-standard auto-increment `id()`; the two externally-exposed append-only tables (`ai_consent_records`, `ai_activity_events`) additionally carry a unique `public_id` ULID, and exports, webhooks, and API resources emit only `public_id`, so sequence information never leaks where it matters while joins stay cheap. Morph column pairs use database-tools' `configuredMorphs()` schema macro, which reads the configured key type (int/ulid/uuid) instead of hand-rolled `nullableMorphs()` switches. Consent records are append-only by design: no `updated_at`, no updates, current state is the latest row per (subject, type).

```php
Schema::create('ai_consent_types', function (Blueprint $table) {
    $table->id();
    $table->string('slug')->unique();
    $table->string('label');
    $table->text('description')->nullable();
    $table->string('legal_basis');            // LegalBasis enum: consent, legitimate_interest, contract
    $table->string('default_state');          // ConsentStatus enum; denied when basis is consent
    $table->boolean('active')->default(true);
    $table->timestamps();
});

Schema::create('ai_consent_records', function (Blueprint $table) {
    $table->id();
    $table->ulid('public_id')->unique();       // the only id exports and webhooks ever emit
    $table->string('tenant_id')->nullable()->index();
    $table->foreignId('consent_type_id')->constrained('ai_consent_types');
    $table->configuredNullableMorphs('subjectable'); // database-tools macro; key type from config
    $table->string('guest_key')->nullable();   // server-issued pseudonymous key
    $table->string('status');                  // granted, denied, withdrawn
    $table->string('source');                  // which form, api client, or import
    $table->foreignId('policy_version_id')->constrained('ai_policy_versions');
    $table->string('policy_version');          // denormalized for export readability ("Policy 1.0")
    $table->string('ip_hash')->nullable();     // sha256 with app-level salt, jurisdiction dependent
    $table->timestamp('recorded_at')->index(); // utc
    $table->index(['subjectable_type', 'subjectable_id', 'consent_type_id', 'recorded_at']);
    $table->index(['guest_key', 'consent_type_id', 'recorded_at']);
});

Schema::create('ai_providers', function (Blueprint $table) {
    $table->id();
    $table->string('tenant_id')->nullable()->index();
    $table->string('name');
    $table->string('vendor');
    $table->string('model_name');
    $table->string('model_version')->nullable();
    $table->string('endpoint_region')->nullable();
    $table->string('role');                    // provider or deployer
    $table->text('purpose')->nullable();
    $table->timestamp('dpa_signed_at')->nullable();
    $table->string('trains_on_our_data');      // yes, no, configurable
    $table->string('training_summary_url')->nullable();
    $table->string('sub_processors_url')->nullable();
    $table->boolean('marking_supported')->default(false);
    $table->string('due_diligence_status')->default('pending');
    $table->string('owner')->nullable();
    $table->timestamps();
    $table->softDeletes();                     // deactivated vendors stay referenceable from the log
});

Schema::create('ai_activity_events', function (Blueprint $table) {
    $table->id();
    $table->ulid('public_id')->unique();       // same rule as consent records
    $table->string('tenant_id')->nullable()->index();
    $table->string('event_type')->index();     // ActivityType enum
    $table->configuredNullableMorphs('actorable');   // who acted; null for system/scheduler events, source goes in context
    $table->configuredNullableMorphs('subjectable'); // who it was about
    $table->foreignId('provider_id')->nullable()->constrained('ai_providers');
    $table->json('context')->nullable();       // no raw prompts or sensitive content
    $table->string('hash_prev', 64)->nullable(); // tamper-evidence chain, optional tier
    $table->timestamp('recorded_at')->index();
});

Schema::create('ai_checklist_items', function (Blueprint $table) {
    $table->id();
    $table->string('tenant_id')->nullable()->index();
    $table->string('key')->index();            // unique per tenant
    $table->string('section');
    $table->string('label');
    $table->text('description')->nullable();
    $table->json('applies_when')->nullable();  // rules against classification answers
    $table->string('status')->default('review'); // CheckStatus enum: ok, review, fail, na
    $table->string('evidence_type');           // auto or manual
    $table->string('evidence_ref')->nullable();
    $table->timestamp('last_verified_at')->nullable();
    $table->string('verified_by')->nullable();
    $table->unsignedSmallInteger('staleness_months')->default(12);
    $table->timestamps();
    $table->unique(['tenant_id', 'key']);
});

// the policy subsystem is three tables, not one json blob: every user-facing text
// (long-form policy, consent short/long text, disclosure line) is a *document* authored
// as markdown, with its own draft/published/superseded version stream and per-locale
// translations. a consent record points at the exact version of the exact document it
// was shown, so "who needs re-consent" is a query, not a diff.
Schema::create('ai_policy_documents', function (Blueprint $table) {
    $table->id();
    $table->string('tenant_id')->nullable()->index();
    $table->string('slug');                    // 'transparency', 'consent.ai_training', 'disclosure.chat', ...
    $table->string('type');                    // PolicyType enum: policy, consent_text, disclosure
    $table->string('surface')->nullable();     // disclosure docs: chat, content, decision
    $table->string('consent_type_slug')->nullable(); // links consent_text docs to ai_consent_types.slug
    $table->string('source_path')->nullable(); // relative path of the shipped md file it was imported from
    $table->string('default_locale');
    $table->boolean('active')->default(true);
    $table->timestamps();
    $table->unique(['tenant_id', 'slug']);
});

Schema::create('ai_policy_versions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('policy_document_id')->constrained('ai_policy_documents');
    $table->string('version');                 // '1.0', '1.1', auto-bumped
    $table->string('status')->default('draft'); // PolicyVersionStatus: draft, published, superseded
    $table->configuredNullableMorphs('authorable'); // who created/published; null = seeder/sync
    $table->timestamp('effective_at')->nullable();
    $table->timestamp('published_at')->nullable();
    $table->timestamp('superseded_at')->nullable();
    $table->timestamps();
    $table->unique(['policy_document_id', 'version']);
});
// invariant enforced by the PolicyPublisher service: at most one published version per
// document; publishing marks the prior one superseded in the same transaction.

Schema::create('ai_policy_translations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('policy_version_id')->constrained('ai_policy_versions');
    $table->string('locale');
    $table->string('title');
    $table->longText('source_markdown');       // raw markdown as authored (frontmatter body)
    $table->longText('compiled_html');         // commonmark output, shortcodes compiled, {{placeholders}} left intact
    $table->json('meta')->nullable();          // parsed frontmatter: short text for consent docs, summary, ...
    $table->char('checksum', 64);              // sha256 of source_markdown
    $table->char('file_checksum', 64)->nullable();   // sha256 of the shipped file at import (file-drift anchor)
    $table->char('origin_checksum', 64)->nullable(); // checksum of the default-locale source this translation was made from
    $table->timestamps();
    $table->unique(['policy_version_id', 'locale']);
});
// two staleness signals, both cheap checksum comparisons: the shipped file changed after
// import (file_checksum drift; sync auto-drafts only when the db copy was never hand-edited,
// otherwise it flags and never overwrites), and the default-locale source changed after a
// translation was made (origin_checksum drift; that locale needs re-translation).

Schema::create('ai_classification_answers', function (Blueprint $table) {
    $table->id();
    $table->string('tenant_id')->nullable()->index();
    $table->string('question_key');
    $table->string('answer');
    $table->string('answered_by');
    $table->timestamp('answered_at');
    $table->unique(['tenant_id', 'question_key']);
});

Schema::create('ai_feature_states', function (Blueprint $table) {
    $table->id();
    $table->string('tenant_id')->nullable()->index();
    $table->string('feature');                 // key from config features map
    $table->boolean('enabled')->default(false);
    $table->string('toggled_by')->nullable();
    $table->timestamp('toggled_at')->nullable();
    $table->unique(['tenant_id', 'feature']);
});
```

Models live in `Simtabi\Laranail\AiCompliance\Models`, declare casts through the `casts()` method (the Laravel 11+ style that carries through 13), and are swappable through config (`'models' => [ConsentRecord::class => ...]`) the way spatie packages do it. The two `public_id` tables generate their ULID in a `creating` hook (database-tools ships the trait). Table names come from `config('laranail.ai-compliance.tables.*')` the way package-management's migration does it. `ActivityEvent` uses `MassPrunable` against the configured retention; `ConsentRecord` deliberately does not (retention is a legal decision, so pruning it requires the explicit `prune --consents` flag with a config'd policy).

```php
namespace Simtabi\Laranail\AiCompliance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Simtabi\Laranail\AiCompliance\Database\Factories\ConsentRecordFactory;
use Simtabi\Laranail\AiCompliance\Enums\ConsentStatus;

class ConsentRecord extends Model
{
    public const UPDATED_AT = null;                 // append-only: no updated_at column exists

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'status'      => ConsentStatus::class,
            'recorded_at' => 'immutable_datetime',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new \LogicException(
            'consent records are append-only; write a new row instead'
        ));

        static::saving(function (self $record) {
            $hasSubject = $record->subjectable_type !== null && $record->subjectable_id !== null;
            if ($hasSubject === ($record->guest_key !== null)) {
                throw new \InvalidArgumentException('set exactly one of subject or guest_key');
            }
        });
    }

    public function subjectable(): MorphTo
    {
        return $this->morphTo();
    }

    protected static function newFactory(): ConsentRecordFactory
    {
        return ConsentRecordFactory::new();
    }
}
```

The `subjectable()` relation is a plain `morphTo()` (Laravel resolves the `subjectable_type`/`subjectable_id` pair from the method name), and because the provider registers `Relation::enforceMorphMap()`, `subjectable_type` stores `user` rather than the host's FQCN. That keeps exports readable, survives host-side class renames, and makes the morph column indexable at a fixed short length. Guests don't get a model row; the `guest` morph alias exists only so log tooling can label them, while the actual identity lives in `guest_key`.

If you scaffolded from the earlier draft of this spec (`subject_*` columns, string `actor_type`/`actor_id`), the rename is a data-preserving migration, not a rebuild. Renames keep the data; only the index needs a drop and recreate:

```php
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_consent_records', function (Blueprint $table) {
            $table->dropIndex(['subject_type', 'subject_id', 'consent_type_id', 'recorded_at']);
            $table->renameColumn('subject_type', 'subjectable_type');
            $table->renameColumn('subject_id', 'subjectable_id');
            $table->index(['subjectable_type', 'subjectable_id', 'consent_type_id', 'recorded_at']);
        });

        Schema::table('ai_activity_events', function (Blueprint $table) {
            $table->renameColumn('subject_type', 'subjectable_type');
            $table->renameColumn('subject_id', 'subjectable_id');
            $table->renameColumn('actor_type', 'actorable_type');
            $table->renameColumn('actor_id', 'actorable_id');
        });
    }

    public function down(): void
    {
        // mirror of up() with the names swapped back
    }
};
```

One honest caveat on the actor change: old rows may hold `actor_type = 'system'` or `'admin'`, which aren't morph classes. The migration ships a data pass that maps `admin` rows to the `user` morph alias where the id matches a user, and nulls the pair for `system` rows while writing `{"actor": "system"}` into `context`, so nothing is lost, it just moves to where the new schema keeps it.

### 12.3 Enums, factories, seeders

```php
namespace Simtabi\Laranail\AiCompliance\Enums;

enum ConsentStatus: string       { case Granted = 'granted'; case Denied = 'denied'; case Withdrawn = 'withdrawn'; }
enum CheckStatus: string         { case Ok = 'ok'; case Review = 'review'; case Fail = 'fail'; case NotApplicable = 'na'; }
enum LegalBasis: string          { case Consent = 'consent'; case LegitimateInterest = 'legitimate_interest'; case Contract = 'contract'; }
enum PolicyType: string          { case Policy = 'policy'; case ConsentText = 'consent_text'; case Disclosure = 'disclosure'; }
enum PolicyVersionStatus: string { case Draft = 'draft'; case Published = 'published'; case Superseded = 'superseded'; }
enum ActivityType: string        { case Inference = 'inference'; case ConsentChange = 'consent_change'; case ProviderChange = 'provider_change';
                                   case SettingChange = 'setting_change'; case Decision = 'decision'; case Override = 'override';
                                   case DsrAction = 'dsr_action'; case Export = 'export'; case Incident = 'incident'; }
```

Factories ship for every model and carry the states the tests and demos need:

```php
// database/factories/ConsentRecordFactory.php
public function definition(): array
{
    return [
        'consent_type_id' => ConsentType::factory(),
        'status'          => ConsentStatus::Denied,
        'source'          => 'save',
        'policy_version'  => '1.0',
        'recorded_at'     => now(),
    ];
}

public function granted(): static  { return $this->state(['status' => ConsentStatus::Granted]); }
public function withdrawn(): static{ return $this->state(['status' => ConsentStatus::Withdrawn]); }
public function guest(): static    { return $this->state(['guest_key' => 'g_'.Str::random(20)]); }
public function forUser(Model $user): static
{
    return $this->for($user, 'subjectable');
}
```

`ProviderFactory` has `dueDiligenceComplete()` and `trainsOnData()` states; `ChecklistItemFactory` has `ok()`, `fail()`, `stale()`; `ActivityEventFactory` has a state per `ActivityType`; `PolicyDocumentFactory`/`PolicyVersionFactory`/`PolicyTranslationFactory` have `published()`, `superseded()`, and `stale()` states.

Three seeders, split by intent. `ConsentTypeSeeder` inserts the four defaults from the config (training, chatbot, recommendations, personalization) idempotently by slug. `ChecklistSeeder` inserts every automatable and manual item from sections 4 through 10 of this document, keyed (`transparency.first_contact_disclosure`, `consent.crawler_signals`, `logging.activity_log_alive`, and so on), with `applies_when` rules wired to the section 2 classification keys, so a fresh install's dashboard is the full checklist with everything at Review. `InitialPolicySeeder` is a thin wrapper over the policy file sync: the template set from the companion document (ai-policy-templates.md) ships as markdown files under `resources/policies/en/`, and the seeder imports each file as a document row with a published version 1.0 whose translations carry the file's content verbatim — placeholders left intact so the install command can prompt for company name and contact. `DemoSeeder` (never run by `install`, only on demand) reproduces the screenshot state for local dev: eight consent rows across two timestamps, two granted, six denied, zero providers so the dashboard shows the Review flag, and a couple of activity events. `php artisan laranail::ai-compliance.install` runs migrations plus the first three; `--demo` adds the fourth.

### 12.4 Authorization policies and the consent policy model

Two different things called "policy," both improved here.

Laravel authorization policies guard the admin API. Every model gets one (`ConsentRecordPolicy`, `ProviderPolicy`, `ChecklistItemPolicy`, `PolicyDocumentPolicy`, `FeatureStatePolicy`), registered with `Gate::policy()` in the provider, and they all delegate to three gates the host app defines. `viewAny`/`view` map to the audit gate, mutations map to the manage gate, and `export` is its own gate because log exports are the sensitive ability. Consent records have no `update` or `delete` abilities at all. The class is short enough to show whole:

```php
namespace Simtabi\Laranail\AiCompliance\Policies;

use Illuminate\Contracts\Auth\Authenticatable;
use Simtabi\Laranail\AiCompliance\Models\ConsentRecord;

class ConsentRecordPolicy
{
    public function viewAny(Authenticatable $user): bool
    {
        return $user->can('ai-compliance:audit');
    }

    public function view(Authenticatable $user, ConsentRecord $record): bool
    {
        return $user->can('ai-compliance:audit');
    }

    public function create(Authenticatable $user): bool
    {
        // admin-side imports only; end users write through the consumer api, not this policy
        return $user->can('ai-compliance:manage');
    }

    public function export(Authenticatable $user): bool
    {
        return $user->can('ai-compliance:export');
    }

    public function update(Authenticatable $user, ConsentRecord $record): bool
    {
        return false; // append-only, matches the model guard and the missing updated_at column
    }

    public function delete(Authenticatable $user, ConsentRecord $record): bool
    {
        return false;
    }
}
```

```php
// host app, AppServiceProvider::boot
Gate::define('ai-compliance:manage', fn ($user) => $user->hasRole('compliance-admin'));
Gate::define('ai-compliance:audit',  fn ($user) => $user->hasRole('auditor') || $user->hasRole('compliance-admin'));
Gate::define('ai-compliance:export', fn ($user) => $user->hasRole('compliance-admin'));
```

The consent policy model got the real upgrade from the section 11 sketch: every text is its own document with its own version stream (12.2), so publishing a new version of one consent text supersedes only that document's prior version. Re-consent targets exactly the affected people with no diffing machinery — a subject needs re-consent when their latest granted record for a type references a superseded version of that type's document. Umbrella documents (the transparency page) never force re-consent by themselves; the operator forces it by publishing a new version of the relevant `consent.*` document:

```php
php artisan laranail::ai-compliance.policy.publish consent.ai_training
# publishes the current draft as 2.0, supersedes 1.x of this document only

php artisan laranail::ai-compliance.notify-reconsent
# queues notifications only to subjects whose latest granted consent references a
# superseded version of the consent document; everyone else's consent stands
```

Notifications are plain Laravel notification classes (`ReconsentRequested` to users over mail and database channels, `CheckFailed`, `ProviderDueDiligenceLapsed`, `ActivityLogSilent` to admins over the configured channels), all overridable via config the standard way.

### 12.5 Runtime API

Unchanged in spirit from the earlier draft, now under the real namespace. Application code never reads consent rows; it asks the facade, and the answer combines the admin's feature toggle (from `ai_feature_states`), the plan, and the subject's consent:

```php
use Simtabi\Laranail\AiCompliance\Facades\AiConsent;

if (AiConsent::allows($user, 'smart_summaries')) {
    // run the feature
}

AiConsent::grant($user, 'ai_personalization', source: 'settings_page');
AiConsent::withdraw($user, 'ai_training', source: 'settings_page');
AiConsent::stateFor($user);   // full map for the js sdk boot response

// dsr support: everything the package holds about a subject, and targeted erasure
AiConsent::exportSubject($user);   // consent history + activity events, json
AiConsent::forgetSubject($user);   // anonymizes subject refs, logs the dsr_action event
```

```blade
<x-ai-compliance::gate feature="chat_assistant">
    <x-chat-widget />
</x-ai-compliance::gate>

<x-ai-compliance::disclosure surface="chat" />
```

Route middleware `ai.feature:chat_assistant` and `ai.consent:ai_chatbot` guard endpoints. Feature resolution bridges into `laravel/pennant` when installed and falls back to its own resolver otherwise. Outbound provider calls go through the wrapper, which injects the vendor-specific do-not-train flag from the subject's consent state and writes the inference event in the same call:

```php
$response = AiConsent::provider('openai')->forSubject($user)->chat($messages);
```

The consumer routes (`routes/api.php`, prefix and middleware configurable, rate-limited by default) serve the JS SDK: `GET /ai-compliance/boot` (types, texts in locale, policy version, subject state, reconsent flag), `POST /ai-compliance/consents`, and nothing else. Admin routes expose the dashboard numbers, checklist, feature toggles, policy publishing, and exports as JSON behind the authorization policies above, so Filament, Nova, or a customer's own UI are equal citizens; the optional Filament plugin shipped in `src/Filament/` (active only when filament is installed) is just a consumer of them.

Checks stay one-method classes (`Simtabi\Laranail\AiCompliance\Checks\Check` contract, `run(): CheckResult`); the package ships the automatable ones from section 11 and host apps register more by tagging. Everything material fires an event (`ConsentRecorded`, `ConsentWithdrawn`, `FeatureToggled`, `PolicyPublished`, `CheckFailed`, `InferenceLogged`), which is also the hook for the webhook fan-out from the integration guide.

### 12.6 JS packages

```bash
npm install @laranail/ai-compliance          # framework-agnostic core
npm install @laranail/ai-compliance-react    # react bindings
npm install @laranail/ai-compliance-vue      # vue bindings
```

All three live in this repo as npm workspaces under `packages/` and release in lockstep with the composer package from the same `vX.Y.Z` tag; the boot payload carries a `contract` integer the core checks at boot. Blade, Livewire, and the Filament plugin render the same payload server-side, so all five UI stacks sit on one contract.

Same design as before under the new scope: boots from the host app's `/ai-compliance/boot` endpoint (same origin, CSRF-protected, no third-party calls), exposes `granted()`, `set()`, `onChange()`, `require()`, ships the preferences panel rendered from server config, the first-contact disclosure badge, a content label, and a re-consent prompt driven by the boot response's flag. Guest identity is the server-issued signed cookie; the JS never mints identity, and login merges guest rows into the user server-side with source `guest_merge`.

```jsx
import { AiConsentProvider, useAiConsent, AiGate, AiDisclosure } from '@laranail/ai-compliance-react';

function Chat() {
  const { granted } = useAiConsent();
  return (
    <AiGate consent="ai_chatbot" fallback={<ContactForm />}>
      <AiDisclosure surface="chat" />
      <ChatWindow personalized={granted('ai_personalization')} />
    </AiGate>
  );
}
```

### 12.7 Testing and build order

The package's own Pest suite (on testbench) implements the section 11.3 acceptance tests as feature tests, plus: append-only enforcement on consent records, re-consent targeting only subjects on superseded consent-document versions, locale fallback and both staleness signals in the policy pipeline, guest merge idempotency, tenancy isolation on every admin endpoint, and prune jobs respecting the consent-retention flag. Factories make all of these cheap to write, which is most of the reason they exist.

Build order (the working plan in `.claude/design/PLAN.md` breaks these into PR-sized milestones): v0.1 the policy pipeline (file loading, compile, locale fallback, boot/policy endpoints) then policy versioning and the editing API; v0.2 consent core plus the Blade/Livewire and JS/React/Vue surfaces; v0.3 checklist engine, checks, dashboard endpoints, feature gating with the Pennant bridge, notifications; v1.0 provider wrapper with do-not-train enforcement and inference logging, exports and reports, the Filament plugin, and webhook events wired to the integration surface.

---

## 13. Operator duties after handover

What stays with the customer, stated so it can go in the handover doc verbatim: keep the provider registry current when they add AI vendors; re-run the checklist on any new AI feature; respond to DSRs and takedown requests within statutory time; review checklist staleness flags; keep robots.txt/llms.txt aligned with their actual training-consent stance; renew bias audits annually where applicable; and re-check section 3's dates each quarter, since the Digital Omnibus adoption, the EU content-transparency Code of Practice, Colorado's Jan 2027 start, and the US preemption push are all in motion as of this writing.

## 14. Sources

- EU AI Act overview and timeline: https://digital-strategy.ec.europa.eu/en/policies/regulatory-framework-ai and https://artificialintelligenceact.eu/implementation-timeline/
- Article 50 transparency guide: https://artificialintelligenceact.eu/transparency-rules-article-50/
- EU Code of Practice on transparency of AI-generated content: https://digital-strategy.ec.europa.eu/en/policies/code-practice-ai-generated-content
- Digital Omnibus analysis (deadline changes): https://www.gibsondunn.com/eu-ai-act-omnibus-agreement-postponed-high-risk-deadlines-and-other-key-changes/
- US state law trackers: https://www.cooley.com/news/insight/2026/2026-04-24-state-ai-laws-where-are-they-now and https://efros.com/research/state-ai-law-tracker/
- California AB 2013, SB 942/AB 853, SB 243, AB 489; Colorado SB 26-189; Texas TRAIGA; Utah SB 149; Illinois HB 3773; NYC LL144 (see trackers above for chaptered text links)
- NIST AI RMF: https://www.nist.gov/itl/ai-risk-management-framework
- ISO/IEC 42001: https://www.iso.org/standard/42001
- OWASP Top 10 for LLM Applications: https://owasp.org/www-project-top-10-for-large-language-model-applications/
- C2PA content provenance: https://c2pa.org