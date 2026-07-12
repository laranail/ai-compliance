<?php

declare(strict_types=1);

it('renders a compiled policy in the terminal', function (): void {
    $this->artisan('laranail::ai-compliance.policy.show', ['slug' => 'transparency'])
        ->expectsOutputToContain('How Acme App uses AI')
        ->assertSuccessful();
});

it('accepts the locale option and reports fallback resolution', function (): void {
    $this->artisan('laranail::ai-compliance.policy.show', ['slug' => 'transparency', '--locale' => 'de'])
        ->expectsOutputToContain('requested de')
        ->assertSuccessful();
});

it('fails cleanly for unknown documents', function (): void {
    $this->artisan('laranail::ai-compliance.policy.show', ['slug' => 'nope'])
        ->expectsOutputToContain('not found')
        ->assertFailed();
});
