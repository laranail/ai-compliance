import { AiComplianceClient, type BootPayload } from '@laranail/ai-compliance';
import { act, render, screen } from '@testing-library/react';
import { describe, expect, it, vi } from 'vitest';
import fixture from '../../core/tests/fixtures/boot.json' with { type: 'json' };
import { AiConsentProvider, AiDisclosure, AiGate, AiPreferences, AiReconsentPrompt } from '../src/index.js';

const payload = fixture as unknown as BootPayload;

function jsonResponse(body: unknown, status = 200): Response {
    return new Response(JSON.stringify(body), { status, headers: { 'Content-Type': 'application/json' } });
}

function makeClient(overrides: Partial<BootPayload> = {}) {
    const fetchMock = vi.fn().mockResolvedValueOnce(jsonResponse({ ...payload, ...overrides }));
    const client = new AiComplianceClient({ fetch: fetchMock as unknown as typeof fetch });

    return { client, fetchMock };
}

describe('AiConsentProvider + AiDisclosure', () => {
    it('boots and renders the disclosure html for a surface', async () => {
        const { client } = makeClient();

        await act(async () => {
            render(
                <AiConsentProvider client={client}>
                    <AiDisclosure surface="chat" />
                </AiConsentProvider>,
            );
        });

        expect(screen.getByRole('note')).toHaveProperty('dataset.surface', 'chat');
        expect(screen.getByRole('note').textContent).toContain('AI assistant');
    });
});

describe('AiGate', () => {
    it('shows the fallback until consent is granted, then the children', async () => {
        const { client, fetchMock } = makeClient();

        await act(async () => {
            render(
                <AiConsentProvider client={client}>
                    <AiGate consent="ai_chatbot" fallback={<span>ASK_FIRST</span>}>
                        <span>SECRET_CHAT</span>
                    </AiGate>
                </AiConsentProvider>,
            );
        });

        expect(screen.queryByText('SECRET_CHAT')).toBeNull();
        expect(screen.getByText('ASK_FIRST')).toBeDefined();

        fetchMock.mockResolvedValueOnce(
            jsonResponse(
                {
                    state: {
                        ...payload.consent.state,
                        ai_chatbot: { status: 'granted', recorded_at: 'now', policy_version: '1.0' },
                    },
                    reconsent: [],
                },
                201,
            ),
        );

        await act(async () => {
            await client.set('ai_chatbot', 'granted');
        });

        expect(screen.getByText('SECRET_CHAT')).toBeDefined();
        expect(screen.queryByText('ASK_FIRST')).toBeNull();
    });
});

describe('AiPreferences', () => {
    it('renders a toggle per type and posts on click', async () => {
        const { client, fetchMock } = makeClient();

        await act(async () => {
            render(
                <AiConsentProvider client={client}>
                    <AiPreferences />
                </AiConsentProvider>,
            );
        });

        const panel = document.querySelectorAll('[data-consent-type]');
        expect(panel).toHaveLength(4);

        fetchMock.mockResolvedValueOnce(
            jsonResponse(
                {
                    state: {
                        ...payload.consent.state,
                        ai_training: { status: 'granted', recorded_at: 'now', policy_version: '1.0' },
                    },
                    reconsent: [],
                },
                201,
            ),
        );

        const button = document
            .querySelector('[data-consent-type="ai_training"]')
            ?.querySelector('button') as HTMLButtonElement;

        await act(async () => {
            button.click();
        });

        const [url, init] = fetchMock.mock.calls[1] as [string, RequestInit];
        expect(url).toBe(payload.endpoints.consents);
        expect(JSON.parse(init.body as string)).toEqual({ type: 'ai_training', status: 'granted' });

        expect(
            document.querySelector('[data-consent-type="ai_training"]')?.textContent,
        ).toContain('Allowed');
    });
});

describe('AiReconsentPrompt', () => {
    it('renders nothing without a reconsent flag and prompts when the boot payload carries one', async () => {
        const { client } = makeClient({
            consent: { ...payload.consent, reconsent: ['ai_training'] },
        });

        await act(async () => {
            render(
                <AiConsentProvider client={client}>
                    <AiReconsentPrompt />
                </AiConsentProvider>,
            );
        });

        expect(screen.getByRole('alert').textContent).toContain('policy you agreed to has changed');
        expect(document.querySelector('[data-consent-type="ai_training"]')).not.toBeNull();
    });
});
