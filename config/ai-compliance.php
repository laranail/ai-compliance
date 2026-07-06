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
        'company' => env('AI_COMPLIANCE_COMPANY', env('APP_NAME')),
        'product' => env('AI_COMPLIANCE_PRODUCT', env('APP_NAME')),
        'contact_email' => env('AI_COMPLIANCE_CONTACT_EMAIL'),
        'privacy_url' => env('AI_COMPLIANCE_PRIVACY_URL'),
        'terms_url' => env('AI_COMPLIANCE_TERMS_URL'),
        'settings_path' => env('AI_COMPLIANCE_SETTINGS_PATH', '/settings/ai'),
        'domain' => env('APP_URL'),
        'dpo_or_contact_name' => env('AI_COMPLIANCE_DPO_NAME'),
        'jurisdiction' => env('AI_COMPLIANCE_JURISDICTION'),
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
        'default' => 'en',
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
        'path' => null,
        'cache' => [
            'enabled' => env('AI_COMPLIANCE_POLICY_CACHE', true),
            'store' => null,
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
    */
    'routes' => [
        'enabled' => true,
        'prefix' => 'ai-compliance',
        'middleware' => ['web'],
        'rate_limit' => '60,1',
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
            'legal_basis' => 'consent',
            'default_state' => 'denied',
        ],
        'ai_chatbot' => [
            'legal_basis' => 'consent',
            'default_state' => 'denied',
        ],
        'ai_recommendations' => [
            'legal_basis' => 'consent',
            'default_state' => 'denied',
        ],
        'ai_personalization' => [
            'legal_basis' => 'consent',
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
        'policy_documents' => 'ai_policy_documents',
        'policy_versions' => 'ai_policy_versions',
        'policy_translations' => 'ai_policy_translations',
        'consent_types' => 'ai_consent_types',
        'consent_records' => 'ai_consent_records',
        'activity_events' => 'ai_activity_events',
        'providers' => 'ai_providers',
        'checklist_items' => 'ai_checklist_items',
        'classification_answers' => 'ai_classification_answers',
        'feature_states' => 'ai_feature_states',
    ],

    /*
    |--------------------------------------------------------------------------
    | Subjects
    |--------------------------------------------------------------------------
    |
    | 'user_model' defaults to the app's auth provider model. 'morph_map'
    | adds host-defined aliases to the enforced morph map.
    |
    */
    'user_model' => null,
    'morph_map' => [],

];
