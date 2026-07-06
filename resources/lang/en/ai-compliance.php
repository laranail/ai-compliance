<?php

declare(strict_types=1);

return [

    'consent_types' => [
        'ai_training' => [
            'label' => 'AI training permissions',
            'description' => 'Allow your content and activity to be used to improve and train AI models.',
        ],
        'ai_chatbot' => [
            'label' => 'AI chatbot interactions',
            'description' => 'Allow the AI assistant to process your messages so it can respond.',
        ],
        'ai_recommendations' => [
            'label' => 'AI recommendations',
            'description' => 'Allow AI to suggest content based on your activity.',
        ],
        'ai_personalization' => [
            'label' => 'AI personalization',
            'description' => 'Allow AI to adapt the interface and content to you.',
        ],
    ],

    'strings' => [
        'preferences.title' => 'AI privacy choices',
        'preferences.intro' => 'Control, separately, how AI may use your data. Saying no never blocks the non-AI parts of the product.',
        'preferences.save' => 'Save choices',
        'preferences.saved' => 'Your choices have been saved.',
        'preferences.granted' => 'Allowed',
        'preferences.denied' => 'Not allowed',
        'preferences.withdraw' => 'Withdraw',
        'disclosure.badge' => 'AI',
        'policy.updated' => 'Updated',
        'policy.version' => 'Version',
        'policy.fallback_notice' => 'This document is not yet available in your language; you are reading the :locale version.',
        'reconsent.title' => 'A policy you agreed to has changed',
        'reconsent.review' => 'Review the changes',
    ],

];
