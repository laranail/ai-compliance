<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\View\ViewException;
use Simtabi\Laranail\AiCompliance\Consent\ConsentManager;
use Simtabi\Laranail\AiCompliance\Policy\Versioning\PolicySync;

uses(RefreshDatabase::class);

it('renders the chat disclosure for an anonymous user before any model output', function (): void {
    // spec acceptance test 3: the disclosure appears on the surface itself,
    // with no authentication and no consent recorded
    $view = $this->blade('<x-laranail-ai-compliance::disclosure surface="chat" />');

    $view->assertSee('ai-compliance-disclosure', escape: false)
        ->assertSee('AI assistant', escape: false)
        ->assertSee('data-surface="chat"', escape: false);
});

it('renders disclosures per locale through the fallback chain', function (): void {
    overridePolicyDir([
        'de/disclosures/chat.md' => "---\ntitle: KI-Hinweis\ntype: disclosure\n---\n\nDu chattest mit einer KI von {{company}}.",
    ]);

    $german = $this->blade('<x-laranail-ai-compliance::disclosure surface="chat" locale="de" />');
    $german->assertSee('Du chattest mit einer KI von Acme.', escape: false);

    // a locale with no translation falls back to english
    $swahili = $this->blade('<x-laranail-ai-compliance::disclosure surface="chat" locale="sw" />');
    $swahili->assertSee('AI assistant', escape: false);
});

it('renders nothing for an unknown disclosure surface', function (): void {
    $view = $this->blade('<x-laranail-ai-compliance::disclosure surface="nonexistent" />');

    $view->assertDontSee('ai-compliance-disclosure', escape: false);
});

it('gates a slot on a consent type', function (): void {
    config()->set('laranail.ai-compliance.features', ['chat_assistant' => ['ai_chatbot']]);

    $template = '<x-laranail-ai-compliance::gate consent="ai_chatbot">SECRET_CHAT<x-slot:fallback>ASK_FIRST</x-slot:fallback></x-laranail-ai-compliance::gate>';

    // anonymous subject with denied-by-default consent
    $this->blade($template)->assertSee('ASK_FIRST')->assertDontSee('SECRET_CHAT');

    // consented user
    $user = makeUser();
    app(ConsentManager::class)->grant($user, 'ai_chatbot');
    $this->actingAs($user);

    $this->blade($template)->assertSee('SECRET_CHAT')->assertDontSee('ASK_FIRST');
});

it('gates a slot on a configured feature', function (): void {
    config()->set('laranail.ai-compliance.features', ['smart_summaries' => ['ai_training']]);

    $user = makeUser();
    $this->actingAs($user);

    $template = '<x-laranail-ai-compliance::gate feature="smart_summaries">SUMMARIES</x-laranail-ai-compliance::gate>';

    $this->blade($template)->assertDontSee('SUMMARIES');

    app(ConsentManager::class)->grant($user, 'ai_training');

    $this->blade($template)->assertSee('SUMMARIES');
});

it('requires a feature or consent attribute on the gate', function (): void {
    // blade wraps the component's InvalidArgumentException
    $this->blade('<x-laranail-ai-compliance::gate>X</x-laranail-ai-compliance::gate>');
})->throws(ViewException::class, 'needs a feature or a consent attribute');

it('renders a policy document with title, substituted body, and server-rendered islands', function (): void {
    $view = $this->blade('<x-laranail-ai-compliance::policy slug="transparency" />');

    $view->assertSee('How Acme App uses AI')
        ->assertSee('Acme', escape: false)
        ->assertDontSee('<ai-c', escape: false)
        ->assertSee('ai-compliance-preferences', escape: false); // the consent-panel island rendered
});

it('shows the fallback notice when the locale is not translated', function (): void {
    $view = $this->blade('<x-laranail-ai-compliance::policy slug="transparency" locale="de" />');

    $view->assertSee('ai-compliance-policy-fallback', escape: false);
});

it('renders the version footer once a version is published', function (): void {
    app(PolicySync::class)->sync();

    $view = $this->blade('<x-laranail-ai-compliance::policy slug="transparency" />');

    $view->assertSee('data-slug="transparency"', escape: false)
        ->assertSee('Version 1.0');
});

it('renders the preferences panel with one toggle per configured type', function (): void {
    $user = makeUser();
    app(ConsentManager::class)->grant($user, 'ai_chatbot');
    $this->actingAs($user);

    $view = $this->blade('<x-laranail-ai-compliance::preferences />');

    $view->assertSee('AI privacy choices')
        ->assertSee('data-consent-type="ai_training"', escape: false)
        ->assertSee('data-consent-type="ai_chatbot"', escape: false)
        ->assertSee('data-consent-type="ai_recommendations"', escape: false)
        ->assertSee('data-consent-type="ai_personalization"', escape: false)
        ->assertSee('Withdraw'); // the granted ai_chatbot toggle offers withdrawal
});
