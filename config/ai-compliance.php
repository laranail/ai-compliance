<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Placeholders
    |--------------------------------------------------------------------------
    |
    | Values substituted into policy documents at serve time. Every occurrence
    | of {{key}} in a compiled policy is replaced with the value below; null
    | values (and prose placeholders left in the shipped templates) stay in
    | the output and are reported as unresolved so the operator fills them.
    |
    */
    'placeholders' => [
        'company'             => env('AI_COMPLIANCE_COMPANY', env('APP_NAME')),
        'product'             => env('AI_COMPLIANCE_PRODUCT', env('APP_NAME')),
        'contact_email'       => env('AI_COMPLIANCE_CONTACT_EMAIL'),
        'privacy_url'         => env('AI_COMPLIANCE_PRIVACY_URL'),
        'terms_url'           => env('AI_COMPLIANCE_TERMS_URL'),
        'settings_path'       => env('AI_COMPLIANCE_SETTINGS_PATH', '/settings/ai'),
        'domain'              => env('APP_URL'),
        'dpo_or_contact_name' => env('AI_COMPLIANCE_DPO_NAME'),
        'jurisdiction'        => env('AI_COMPLIANCE_JURISDICTION'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Locales
    |--------------------------------------------------------------------------
    |
    | The default locale is the one policy documents ship in and the last
    | fallback for every request. Fallbacks map a requested locale to the
    | chain tried before the default, e.g. 'de-CH' => ['de', 'en'].
    |
    */
    'locales' => [
        'default'   => 'en',
        'fallbacks' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Policy sources
    |--------------------------------------------------------------------------
    |
    | 'path' is the app-level directory scanned before the package's shipped
    | policies (null uses resources/policies/ai-compliance). Publish the
    | shipped files there with:
    | php artisan vendor:publish --tag=laranail::ai-compliance-policies
    |
    */
    'policies' => [
        'path'  => null,
        'cache' => [
            'enabled' => env('AI_COMPLIANCE_POLICY_CACHE', true),
            'store'   => null,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Shortcodes
    |--------------------------------------------------------------------------
    |
    | The closed vocabulary of [[shortcode key="value"]] islands allowed in
    | policy markdown. Each compiles to an <ai-c> element that Blade/Livewire
    | replace server-side and the JS core hydrates in the browser. Unknown
    | shortcodes render their fallback text and log a warning.
    |
    */
    'shortcodes' => [
        'consent-toggle',
        'consent-panel',
        'provider-list',
        'policy-link',
        'disclosure',
    ],

    /*
    |--------------------------------------------------------------------------
    | Routes
    |--------------------------------------------------------------------------
    |
    | 'routes' are the consumer endpoints (boot, policies). 'admin_routes'
    | are the editing api, guarded by the three gates the host app defines:
    | ai-compliance:manage, ai-compliance:audit, ai-compliance:export.
    |
    */
    'routes' => [
        'enabled'    => true,
        'prefix'     => 'ai-compliance',
        'middleware' => ['web'],
        'rate_limit' => '60,1',
    ],

    'admin_routes' => [
        'enabled'    => true,
        'prefix'     => 'ai-compliance/admin',
        'middleware' => ['web', 'auth'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Consent types
    |--------------------------------------------------------------------------
    |
    | The granular consent switches the package models. Labels and
    | descriptions live in the translation files; each slug here must have a
    | consent text document (resources/policies/{locale}/consent/{slug}.md).
    |
    */
    'consent_types' => [
        'ai_training' => [
            'legal_basis'   => 'consent',
            'default_state' => 'denied',
        ],
        'ai_chatbot' => [
            'legal_basis'   => 'consent',
            'default_state' => 'denied',
        ],
        'ai_recommendations' => [
            'legal_basis'   => 'consent',
            'default_state' => 'denied',
        ],
        'ai_personalization' => [
            'legal_basis'   => 'consent',
            'default_state' => 'denied',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Disclosure surfaces
    |--------------------------------------------------------------------------
    |
    | Each surface maps to a disclosure document
    | (resources/policies/{locale}/disclosures/{surface}.md).
    |
    */
    'disclosure_surfaces' => [
        'chat',
        'content',
        'decision',
    ],

    /*
    |--------------------------------------------------------------------------
    | Tables
    |--------------------------------------------------------------------------
    |
    | Table names for the compliance schema (used from the versioning
    | milestone onward). Rename before running migrations, never after.
    |
    */
    'tables' => [
        'policy_documents'       => 'ai_policy_documents',
        'policy_versions'        => 'ai_policy_versions',
        'policy_translations'    => 'ai_policy_translations',
        'consent_types'          => 'ai_consent_types',
        'consent_records'        => 'ai_consent_records',
        'activity_events'        => 'ai_activity_events',
        'providers'              => 'ai_providers',
        'checklist_items'        => 'ai_checklist_items',
        'classification_answers' => 'ai_classification_answers',
        'feature_states'         => 'ai_feature_states',
    ],

    /*
    |--------------------------------------------------------------------------
    | Subjects
    |--------------------------------------------------------------------------
    |
    | 'user_model' defaults to the app's auth provider model; the package
    | registers it under the short 'user' morph alias so stored *_type
    | columns stay stable if the host renames classes. 'morph_map' adds
    | host-defined aliases. 'guest' controls the pseudonymous cookie issued
    | to unauthenticated subjects when they record a consent.
    |
    */
    'user_model' => null,
    'morph_map'  => [],

    'guest' => [
        'cookie'        => 'laranail_ai_compliance_guest',
        'lifetime_days' => 365,
    ],

    /*
    |--------------------------------------------------------------------------
    | Livewire
    |--------------------------------------------------------------------------
    |
    | The ConsentPreferences and ReconsentPrompt components register only
    | when livewire/livewire is installed (a suggest dependency) and this
    | flag is on.
    |
    */
    'livewire' => [
        'enabled' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Features
    |--------------------------------------------------------------------------
    |
    | Feature gating for AiConsent::allows(): each feature lists the consent
    | types that must all be granted before it may run for a subject.
    | Unlisted features are denied by default. Admin feature toggles and the
    | pennant bridge arrive with the checklist milestone.
    |
    | 'smart_summaries' => ['ai_training'],
    |
    */
    'features' => [],

    /*
    |--------------------------------------------------------------------------
    | Checks and alerting
    |--------------------------------------------------------------------------
    |
    | The automated checks run on this schedule (daily; set null to disable)
    | and on demand via laranail::ai-compliance.audit or the admin endpoint.
    | 'alerting.mail' receives check failures; the activity log counts as
    | silent after 'log_silence_hours' without an event.
    |
    */
    'checks' => [
        'schedule' => 'daily',
    ],

    'alerting' => [
        'mail'              => env('AI_COMPLIANCE_ALERT_MAIL'),
        'log_silence_hours' => 24,
    ],

    /*
    |--------------------------------------------------------------------------
    | Retention
    |--------------------------------------------------------------------------
    |
    | Retention periods in days per store, verified by the retention check.
    | The pruning jobs arrive with the activity milestone; consent records
    | are only ever pruned by explicit command, never by schedule.
    |
    */
    'retention' => [
        'activity_events' => null,
        'consent_records' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Activity log
    |--------------------------------------------------------------------------
    |
    | 'hash_chain' turns on the tamper-evidence tier: every event links to
    | its predecessor and laranail::ai-compliance.verify-chain recomputes the
    | chain. Note that pruning chained events and dsr erasure both break the
    | chain by design.
    |
    */
    'activity' => [
        'hash_chain' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Re-consent
    |--------------------------------------------------------------------------
    |
    | Channels for the ReconsentRequested notification sent by
    | laranail::ai-compliance.notify-reconsent. The 'database' channel needs
    | the host's notifications table.
    |
    */
    'reconsent' => [
        'channels' => ['mail'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Seeders
    |--------------------------------------------------------------------------
    |
    | With 'auto' on, the package's idempotent seeders (checklist items,
    | initial policy import) run with the host's `php artisan db:seed` via
    | the package-tools seeder registry. The demo seeder never auto-runs.
    |
    */
    'seeders' => [
        'auto' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Provider calls
    |--------------------------------------------------------------------------
    |
    | Per-vendor do-not-train request adjustments (lowercase vendor key),
    | injected whenever the subject has NOT granted ai_training. Vendors
    | without a per-request flag (contract-level positions) map to an empty
    | array — the inference event still records do_not_train so the position
    | is auditable.
    |
    */
    'providers' => [
        'timeout'      => 120, // seconds per outbound provider call (models stream slowly)
        'do_not_train' => [
            'openai'    => ['body' => 'store', 'body_value' => false],
            'anthropic' => [],
        ],
    ],

];
