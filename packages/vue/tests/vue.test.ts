import { AiComplianceClient, type BootPayload } from '@laranail/ai-compliance';
import { createApp, defineComponent, h, nextTick } from 'vue';
import { describe, expect, it, vi } from 'vitest';
import fixture from '../../core/tests/fixtures/boot.json' with { type: 'json' };
import { AiDisclosure, AiGate, AiPreferences, AiReconsentPrompt, createAiConsent } from '../src/index.js';

const payload = fixture as unknown as BootPayload;

function jsonResponse(body: unknown, status = 200): Response {
    return new Response(JSON.stringify(body), { status, headers: { 'Content-Type': 'application/json' } });
}

async function mount(component: unknown, overrides: Partial<BootPayload> = {}) {
    const fetchMock = vi.fn().mockResolvedValueOnce(jsonResponse({ ...payload, ...overrides }));
    const client = new AiComplianceClient({ fetch: fetchMock as unknown as typeof fetch });

    const host = document.createElement('div');
    document.body.appendChild(host);

    const app = createApp(defineComponent(() => () => h(component as never)));
    app.use(createAiConsent({ client }));
    app.mount(host);

    await vi.waitFor(() => {
        if (!client.booted()) throw new Error('not booted yet');
    });
    await nextTick();

    return { host, client, fetchMock, app };
}

describe('AiDisclosure', () => {
    it('renders the disclosure html for a surface', async () => {
        const { host } = await mount(AiDisclosure);

        const note = host.querySelector('[role="note"]');
        expect(note?.getAttribute('data-surface')).toBe('chat');
        expect(note?.textContent).toContain('AI assistant');
    });
});

describe('AiGate', () => {
    it('shows fallback until granted, then the default slot', async () => {
        const gated = defineComponent(() => () =>
            h(AiGate, { consent: 'ai_chatbot' }, {
                default: () => h('span', 'SECRET_CHAT'),
                fallback: () => h('span', 'ASK_FIRST'),
            }));

        const { host, client, fetchMock } = await mount(gated);

        expect(host.textContent).toContain('ASK_FIRST');
        expect(host.textContent).not.toContain('SECRET_CHAT');

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

        await client.set('ai_chatbot', 'granted');
        await nextTick();

        expect(host.textContent).toContain('SECRET_CHAT');
        expect(host.textContent).not.toContain('ASK_FIRST');
    });
});

describe('AiPreferences', () => {
    it('renders a toggle per type and posts on click', async () => {
        const { host, fetchMock } = await mount(AiPreferences);

        expect(host.querySelectorAll('[data-consent-type]')).toHaveLength(4);

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

        const button = host
            .querySelector('[data-consent-type="ai_training"]')
            ?.querySelector('button') as HTMLButtonElement;
        button.click();

        // the click handler resolves the post asynchronously; wait for the re-render
        await vi.waitFor(() => {
            const status = host
                .querySelector('[data-consent-type="ai_training"] .ai-compliance-preference-status')
                ?.textContent;

            if (status !== 'Allowed') throw new Error(`state not refreshed yet (${status})`);
        });

        const [url, init] = fetchMock.mock.calls[1] as [string, RequestInit];
        expect(url).toBe(payload.endpoints.consents);
        expect(JSON.parse(init.body as string)).toEqual({ type: 'ai_training', status: 'granted' });
    });
});

describe('AiReconsentPrompt', () => {
    it('prompts only when the boot payload carries a reconsent flag', async () => {
        const empty = await mount(AiReconsentPrompt);
        expect(empty.host.querySelector('.ai-compliance-reconsent')).toBeNull();

        const prompted = await mount(AiReconsentPrompt, {
            consent: { ...payload.consent, reconsent: ['ai_training'] },
        });

        expect(prompted.host.querySelector('[role="alert"]')?.textContent).toContain(
            'policy you agreed to has changed',
        );
        expect(prompted.host.querySelector('[data-consent-type="ai_training"]')).not.toBeNull();
    });
});
