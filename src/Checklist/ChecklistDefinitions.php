<?php

declare(strict_types=1);

namespace Simtabi\Laranail\AiCompliance\Checklist;

/**
 * the compliance checklist, extracted from sections 4-10 of the spec
 * every checkbox item of the compliance spec becomes one definition;
 * the seeder writes them, the check runner keeps the auto ones honest, and
 * classification answers switch sections on or off via applies_when.
 */
final class ChecklistDefinitions
{
    /**
     * @return list<array{key: string, section: string, label: string, description: string, evidence_type: string, staleness_months: int, applies_when: array<string, string>|array{any_of: list<array<string, string>>}|null}>
     */
    public static function all(): array
    {
        return [
            // section 4: governance, inventory, and accountability
            [
                'key'              => 'governance.system_inventory',
                'section'          => 'governance',
                'label'            => 'AI system inventory exists and is current',
                'description'      => 'Every AI feature, model, and integration is registered with name, purpose, model/provider, version, role, risk classification, markets, and owner. Evidence: a populated registry reviewed at least quarterly and on every release that adds or changes an AI feature.',
                'evidence_type'    => 'manual',
                'staleness_months' => 3, // the evidence line says reviewed at least quarterly
                'applies_when'     => null,
            ],
            [
                'key'              => 'governance.provider_registry',
                'section'          => 'governance',
                'label'            => 'At least one AI provider/vendor is registered per AI feature',
                'description'      => 'No unknown-model entries: record vendor, model name and version, endpoint region, DPA status, and sub-processor list link. Evidence: registry rows with all fields filled; the review flag clears only when the count is above zero and rows are complete.',
                'evidence_type'    => 'auto',
                'staleness_months' => 3,
                'applies_when'     => null,
            ],
            [
                'key'              => 'governance.accountable_owner',
                'section'          => 'governance',
                'label'            => 'An accountable owner is named',
                'description'      => 'A named owner for AI compliance overall and per project, with their contact published where required. Evidence: the data-protection contact set in the tool and the name appearing in the privacy notice.',
                'evidence_type'    => 'auto',
                'staleness_months' => 12,
                'applies_when'     => null,
            ],
            [
                'key'              => 'governance.policy_versioning',
                'section'          => 'governance',
                'label'            => 'AI policy versioning is in place',
                'description'      => 'Consent texts, disclosures, and the privacy notice carry a version number, and recorded consents reference the version they were given against. Evidence: a version field on every consent record plus a changelog of policy texts.',
                'evidence_type'    => 'auto',
                'staleness_months' => 12,
                'applies_when'     => null,
            ],
            [
                'key'              => 'governance.staff_training',
                'section'          => 'governance',
                'label'            => 'AI literacy / staff training done',
                'description'      => 'People who build, operate, or make decisions with AI systems have completed AI literacy training, an explicit EU AI Act duty since February 2025. Evidence: a training record with dates and attendees.',
                'evidence_type'    => 'manual',
                'staleness_months' => 12,
                'applies_when'     => null,
            ],
            [
                'key'              => 'governance.risk_framework',
                'section'          => 'governance',
                'label'            => 'A risk framework is adopted',
                'description'      => 'A risk framework (NIST AI RMF or ISO/IEC 42001) is adopted and the project is mapped to it, earning safe-harbor or mitigation credit in several regimes. Evidence: a mapping document, even a short one.',
                'evidence_type'    => 'manual',
                'staleness_months' => 12,
                'applies_when'     => null,
            ],
            [
                'key'              => 'governance.prohibited_use_screening',
                'section'          => 'governance',
                'label'            => 'Prohibited-use screening done',
                'description'      => 'The project is checked against EU AI Act Article 5 prohibitions and TRAIGA banned purposes such as manipulation, social scoring, face scraping, and workplace emotion recognition; a hard gate, not a mitigation exercise. Evidence: a signed-off screening record; N/A only with reasoning written down.',
                'evidence_type'    => 'manual',
                'staleness_months' => 12,
                'applies_when'     => null,
            ],

            // section 5: transparency and disclosure
            [
                'key'              => 'transparency.first_contact_disclosure',
                'section'          => 'transparency',
                'label'            => 'AI interaction disclosure at first contact',
                'description'      => 'Any chatbot, voice agent, or AI assistant tells the user it is AI no later than the first interaction, in clear, accessible form. Evidence: the disclosure renders in the UI, cannot be disabled below the legal minimum, and is one of the dashboard checks.',
                'evidence_type'    => 'auto',
                'staleness_months' => 12,
                'applies_when'     => ['interacts_with_people' => 'yes'],
            ],
            [
                'key'              => 'transparency.machine_readable_marking',
                'section'          => 'transparency',
                'label'            => 'Machine-readable marking of synthetic output',
                'description'      => 'Generated audio, image, video, or text is marked as AI-generated in a machine-readable way, and third-party model marking survives the pipeline. Evidence: sample outputs pass a marking/detection check in CI plus a vendor contract clause requiring marking support.',
                'evidence_type'    => 'manual',
                'staleness_months' => 12,
                'applies_when'     => ['generates_synthetic_content' => 'yes'],
            ],
            [
                'key'              => 'transparency.deepfake_labeling',
                'section'          => 'transparency',
                'label'            => 'Visible labeling of deepfakes',
                'description'      => 'Generated content that depicts real people or events carries a visible label in addition to the machine-readable mark. Evidence: labeling appears on the publishing surface and the editorial workflow blocks unlabeled publication.',
                'evidence_type'    => 'manual',
                'staleness_months' => 12,
                'applies_when'     => ['generates_synthetic_content' => 'yes'],
            ],
            [
                'key'              => 'transparency.published_content_labeling',
                'section'          => 'transparency',
                'label'            => 'Visible labeling of published AI content',
                'description'      => 'AI-generated text published on matters of public interest carries a visible label in addition to the machine-readable mark. Evidence: labeling appears on the publishing surface and the editorial workflow blocks unlabeled publication.',
                'evidence_type'    => 'manual',
                'staleness_months' => 12,
                'applies_when'     => ['publishes_ai_content' => 'yes'],
            ],
            [
                'key'              => 'transparency.ca_detection_tool',
                'section'          => 'transparency',
                'label'            => 'AI content detection tool (large CA providers)',
                'description'      => 'GenAI systems with one million or more monthly users reaching California provide a free public detection tool plus manifest and latent disclosures from August 2026. Evidence: the tool is live and linked, or N/A for smaller products with the user count recorded.',
                'evidence_type'    => 'manual',
                'staleness_months' => 12,
                'applies_when'     => ['generates_synthetic_content' => 'yes'],
            ],
            [
                'key'              => 'transparency.emotion_recognition_notice',
                'section'          => 'transparency',
                'label'            => 'Emotion recognition / biometric categorization notice',
                'description'      => 'If a permitted emotion-recognition or biometric-categorization use survives the prohibition screening, exposed individuals get clear notice. Evidence: the notice text and placement, referencing the screening record.',
                'evidence_type'    => 'manual',
                'staleness_months' => 12,
                'applies_when'     => ['biometrics_emotion' => 'yes'],
            ],
            [
                'key'              => 'transparency.sector_disclosures',
                'section'          => 'transparency',
                'label'            => 'Sector disclosures',
                'description'      => 'Healthcare and regulated-profession interactions disclose AI involvement, and no AI output implies a human professional license it does not have. Evidence: UI proof, or the verifier records why no regulated sector applies.',
                'evidence_type'    => 'manual',
                'staleness_months' => 12,
                'applies_when'     => null, // no sector question exists; the verifier decides applicability
            ],
            [
                'key'              => 'transparency.honest_disclosure',
                'section'          => 'transparency',
                'label'            => 'Disclosure is honest in both directions',
                'description'      => 'No human personas on bots and no AI-washing that claims AI where there is none; both draw consumer-protection enforcement. Evidence: a copy review in the release checklist.',
                'evidence_type'    => 'manual',
                'staleness_months' => 12,
                'applies_when'     => null,
            ],

            // section 6: consent, preferences, and training-data controls
            [
                'key'              => 'consent.granular_types',
                'section'          => 'consent',
                'label'            => 'Consent types are explicit and granular',
                'description'      => 'At minimum four consent types (training, chatbot, recommendations, personalization), separately grantable and deniable, never bundled into general terms acceptance. Evidence: a consent UI with independent toggles, denied by default for anything relying on consent.',
                'evidence_type'    => 'auto',
                'staleness_months' => 12,
                'applies_when'     => null,
            ],
            [
                'key'              => 'consent.event_logging',
                'section'          => 'consent',
                'label'            => 'Every consent event is logged',
                'description'      => 'Every consent event records type, status, subject or guest key, source, policy version shown, and UTC timestamp; withdrawal is as easy as granting and logged the same way. Evidence: the consent log itself, exportable to CSV at minimum and filterable by type and status.',
                'evidence_type'    => 'manual',
                'staleness_months' => 12,
                'applies_when'     => null,
            ],
            [
                'key'              => 'consent.denial_enforcement',
                'section'          => 'consent',
                'label'            => 'Denial has teeth',
                'description'      => 'A denied or withdrawn training consent actually excludes the subject from training pipelines and sets vendor do-not-train flags per request. Evidence: a traceable path from consent state to pipeline filter and vendor flag, plus a test that flips consent and observes the flag change.',
                'evidence_type'    => 'manual',
                'staleness_months' => 12,
                'applies_when'     => null,
            ],
            [
                'key'              => 'consent.crawler_signals',
                'section'          => 'consent',
                'label'            => 'Crawler and training signals are published',
                'description'      => 'robots.txt declares the AI-crawler policy, llms.txt states model-facing preferences if wanted, and EU-relevant content carries a machine-readable TDM reservation where intended. Evidence: files served with correct content; the tool verifies presence and parses the policy.',
                'evidence_type'    => 'auto',
                'staleness_months' => 12,
                'applies_when'     => null,
            ],
            [
                'key'              => 'consent.training_data_documentation',
                'section'          => 'consent',
                'label'            => 'Training-data documentation published (developer duty)',
                'description'      => 'Developers of public GenAI systems reaching California publish a training-data summary (AB 2013); EU GPAI providers owe a training-content summary; deployers collect the vendor version instead. Evidence: a published page or the vendor summary filed in the provider registry.',
                'evidence_type'    => 'manual',
                'staleness_months' => 12,
                'applies_when'     => null, // deployers owe the vendor summary even when not training themselves
            ],
            [
                'key'              => 'consent.customer_content_protection',
                'section'          => 'consent',
                'label'            => 'Customer content is contractually protected',
                'description'      => 'Contracts and vendor settings state whether customer or user content may be used for provider training, defaulting to no unless the customer opts in. Evidence: a DPA or terms clause plus the vendor console setting recorded in the registry.',
                'evidence_type'    => 'manual',
                'staleness_months' => 12,
                'applies_when'     => null,
            ],

            // section 7: privacy and data protection in ai processing
            [
                'key'              => 'privacy.legal_basis',
                'section'          => 'privacy',
                'label'            => 'Legal basis identified per processing purpose',
                'description'      => 'Training, fine-tuning, RAG indexing, and inference each have a named legal basis, with a documented balancing test for legitimate-interest claims. Evidence: record of processing activities rows for each AI purpose.',
                'evidence_type'    => 'manual',
                'staleness_months' => 12,
                'applies_when'     => ['processes_personal_data' => 'yes'],
            ],
            [
                'key'              => 'privacy.dpia',
                'section'          => 'privacy',
                'label'            => 'DPIA performed',
                'description'      => 'A DPIA is performed where AI processing is likely high-risk, such as profiling, large scale, sensitive data, or consequential decisions. Evidence: the DPIA document, reviewed on major model or purpose change.',
                'evidence_type'    => 'manual',
                'staleness_months' => 12,
                // required when personal data OR consequential decisions
                'applies_when' => ['any_of' => [
                    ['processes_personal_data' => 'yes'],
                    ['consequential_decisions' => 'yes'],
                ]],
            ],
            [
                'key'              => 'privacy.data_minimization',
                'section'          => 'privacy',
                'label'            => 'Data minimization in prompts and logs',
                'description'      => 'PII is stripped or pseudonymized before it reaches third-party models where feasible, and prompt/response logs have a retention period and a redaction policy. Evidence: middleware redaction in the request path, retention config, and a sampled log audit.',
                'evidence_type'    => 'manual',
                'staleness_months' => 12,
                'applies_when'     => ['processes_personal_data' => 'yes'],
            ],
            [
                'key'              => 'privacy.dsr_support',
                'section'          => 'privacy',
                'label'            => 'Data-subject rights work against AI features',
                'description'      => 'Access, deletion, and objection cover AI-held data such as conversation logs, embeddings, and personalization profiles; deletion propagates to vector stores and caches within statutory time. Evidence: a tested DSR runbook that includes the AI stores; deletion produces a verifiable absence.',
                'evidence_type'    => 'manual',
                'staleness_months' => 12,
                'applies_when'     => ['processes_personal_data' => 'yes'],
            ],
            [
                'key'              => 'privacy.cross_border_transfers',
                'section'          => 'privacy',
                'label'            => 'Cross-border transfer position documented',
                'description'      => 'The transfer position is documented per model endpoint, covering the region of inference and the vendor SCC or DPF status. Evidence: a registry field per provider plus a transfer assessment where needed.',
                'evidence_type'    => 'manual',
                'staleness_months' => 12,
                'applies_when'     => ['processes_personal_data' => 'yes'],
            ],
            [
                'key'              => 'privacy.retention_schedule',
                'section'          => 'privacy',
                'label'            => 'Retention schedule set',
                'description'      => 'Retention periods are set for consent records, activity logs, prompts and outputs, and training snapshots. Evidence: retention config in the tool plus scheduled pruning jobs.',
                'evidence_type'    => 'auto',
                'staleness_months' => 12,
                'applies_when'     => ['processes_personal_data' => 'yes'],
            ],

            // section 8: automated decisions and human oversight
            [
                'key'              => 'decisions.pre_use_notice',
                'section'          => 'decisions',
                'label'            => 'Pre-use notice',
                'description'      => 'People are told, before or at the point of decision, that automated decision-making technology materially influences a consequential decision about them and what its role is. Evidence: notice copy and placement per decision surface.',
                'evidence_type'    => 'manual',
                'staleness_months' => 12,
                'applies_when'     => ['consequential_decisions' => 'yes'],
            ],
            [
                'key'              => 'decisions.human_review',
                'section'          => 'decisions',
                'label'            => 'Human review is real',
                'description'      => 'A person with authority, competence, and the needed information can review and overturn the AI outcome; the UI supports it and the log records it. Evidence: a review queue exists and the override rate is monitored; rubber-stamp review counts as none.',
                'evidence_type'    => 'manual',
                'staleness_months' => 12,
                'applies_when'     => ['consequential_decisions' => 'yes'],
            ],
            [
                'key'              => 'decisions.adverse_outcome_explanation',
                'section'          => 'decisions',
                'label'            => 'Adverse-outcome explanation',
                'description'      => 'On a negative consequential decision the person gets an explanation including the principal factors, within 30 days in Colorado, plus correction and human-review rights. Evidence: a templated explanation flow wired to decision records.',
                'evidence_type'    => 'manual',
                'staleness_months' => 12,
                'applies_when'     => ['consequential_decisions' => 'yes'],
            ],
            [
                'key'              => 'decisions.bias_audits',
                'section'          => 'decisions',
                'label'            => 'Bias testing and audits',
                'description'      => 'Employment tools serving NYC candidates get the LL144 annual bias audit with published results and candidate notice; other consequential-decision systems get documented pre-deployment and periodic disparate-impact testing. Evidence: audit reports with dates; test datasets and metrics retained.',
                'evidence_type'    => 'manual',
                'staleness_months' => 6,
                'applies_when'     => ['consequential_decisions' => 'yes'],
            ],
            [
                'key'              => 'decisions.opt_out_path',
                'section'          => 'decisions',
                'label'            => 'Opt-out / alternative path',
                'description'      => 'A functioning opt-out or alternative path exists where law provides one, such as GDPR Article 22 and California ADMT opt-outs from 2027. Evidence: a working opt-out that routes to a human process.',
                'evidence_type'    => 'manual',
                'staleness_months' => 12,
                'applies_when'     => ['consequential_decisions' => 'yes'],
            ],

            // section 9: logging, auditability, and evidence
            [
                'key'              => 'logging.activity_log_alive',
                'section'          => 'logging',
                'label'            => 'AI activity log captures every material AI event',
                'description'      => 'Inference calls, consent changes, registry changes, disclosure-setting changes, decision outcomes and overrides, and DSR actions are logged with type, actor, subject, and UTC timestamp, without raw sensitive content. Evidence: the log, with the logged-events counter nonzero and moving.',
                'evidence_type'    => 'auto',
                'staleness_months' => 3,
                'applies_when'     => null,
            ],
            [
                'key'              => 'logging.tamper_evidence',
                'section'          => 'logging',
                'label'            => 'Logs are tamper-evident and access-controlled',
                'description'      => 'Compliance-relevant logs use append-only storage or hash chaining with role-based access, and log access is itself logged. Evidence: a storage design note plus the access-control config.',
                'evidence_type'    => 'manual',
                'staleness_months' => 12,
                'applies_when'     => null,
            ],
            [
                'key'              => 'logging.exports',
                'section'          => 'logging',
                'label'            => 'Exports work',
                'description'      => 'The consent log and activity log export to CSV or JSON on demand, scoped by date and type, for auditors and customer requests. Evidence: an export produced during the go-live test.',
                'evidence_type'    => 'manual',
                'staleness_months' => 12,
                'applies_when'     => null,
            ],
            [
                'key'              => 'logging.reports',
                'section'          => 'logging',
                'label'            => 'Reports are generatable',
                'description'      => 'A point-in-time compliance report covers checklist status, consent statistics per type, the provider registry, and recent incidents; the artifact customers attach to their own audits. Evidence: the generated report output, dated and versioned.',
                'evidence_type'    => 'manual',
                'staleness_months' => 12,
                'applies_when'     => null,
            ],
            [
                'key'              => 'logging.incident_process',
                'section'          => 'logging',
                'label'            => 'Serious-incident process exists',
                'description'      => 'AI malfunctions with harm potential get logged, assessed, and reported where thresholds are met, per EU AI Act post-market duties or contract. Evidence: an incident runbook plus at least one tabletop test.',
                'evidence_type'    => 'manual',
                'staleness_months' => 12,
                'applies_when'     => null,
            ],

            // section 10: vendors, security, and content safety
            [
                'key'              => 'vendors.due_diligence',
                'section'          => 'vendors',
                'label'            => 'Vendor due diligence per provider',
                'description'      => 'Per provider: DPA signed, training-on-our-data position confirmed and configured, sub-processors reviewed, model card collected, marking and deprecation terms in contract, and an exit plan. Evidence: a completed due-diligence record per registry row.',
                'evidence_type'    => 'auto',
                'staleness_months' => 12,
                'applies_when'     => null,
            ],
            [
                'key'              => 'vendors.prompt_injection_controls',
                'section'          => 'vendors',
                'label'            => 'Prompt-injection and output-handling controls',
                'description'      => 'Untrusted content reaching the model is treated as data, not instructions, and model output is treated as untrusted input to downstream systems; OWASP LLM Top 10 is the working reference. Evidence: a threat-model note plus injection test cases in CI.',
                'evidence_type'    => 'manual',
                'staleness_months' => 12,
                'applies_when'     => null,
            ],
            [
                'key'              => 'vendors.secrets_management',
                'section'          => 'vendors',
                'label'            => 'Secrets and access',
                'description'      => 'Model API keys live in a secret manager, per environment, rotated, with least-privilege scopes and usage anomaly alerts. Evidence: the secret-manager config plus a rotation record.',
                'evidence_type'    => 'manual',
                'staleness_months' => 12,
                'applies_when'     => null,
            ],
            [
                'key'              => 'vendors.content_safety',
                'section'          => 'vendors',
                'label'            => 'Content-safety controls proportionate to the surface',
                'description'      => 'Moderation for user-facing generation, minor-protection measures where minors are plausible users, and NCII/CSAM prevention with the TAKE IT DOWN removal path where users can publish imagery. Evidence: moderation config plus a takedown workflow with the 48-hour clock tracked.',
                'evidence_type'    => 'manual',
                'staleness_months' => 12,
                'applies_when'     => null,
            ],
            [
                'key'              => 'vendors.rate_limiting',
                'section'          => 'vendors',
                'label'            => 'Rate limiting and abuse controls',
                'description'      => 'AI endpoints carry rate limiting and abuse controls, both for cost and because abuse of the endpoint becomes a compliance problem. Evidence: limiter config and alerting.',
                'evidence_type'    => 'manual',
                'staleness_months' => 12,
                'applies_when'     => null,
            ],
        ];
    }
}
