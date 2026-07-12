import { beforeEach, describe, expect, it, vi } from 'vitest';
import {
    AiComplianceClient,
    ConsentRequiredError,
    ContractMismatchError,
    NotBootedError,
    type BootPayload,
} from '../src/index.js';
import fixture from './fixtures/boot.json' with { type: 'json' };

const payload = fixture as unknown as BootPayload;

function jsonResponse(body: unknown, status = 200): Response {
    return new Response(JSON.stringify(body), {
        status,
        headers: { 'Content-Type': 'application/json' },
    });
}

function bootedClient(fetchMock: ReturnType<typeof vi.fn>): Promise<AiComplianceClient> {
    fetchMock.mockResolvedValueOnce(jsonResponse(payload));
    const client = new AiComplianceClient({ fetch: fetchMock as unknown as typeof fetch });

    return client.boot().then(() => client);
}

beforeEach(() => {
    document.cookie.split(';').forEach((cookie) => {
        const name = cookie.split('=')[0]?.trim();
        if (name) document.cookie = `${name}=; expires=Thu, 01 Jan 1970 00:00:00 GMT; path=/`;
    });
});

describe('the recorded contract fixture', () => {
    it('pins contract 1 and the payload shape the pest suite serves', () => {
        expect(payload.contract).toBe(1);
        expect(payload.consent.types.map((type) => type.slug)).toEqual([
            'ai_training',
            'ai_chatbot',
            'ai_recommendations',
            'ai_personalization',
        ]);
        expect(payload.consent.state['ai_training']).toEqual({
            status: 'denied',
            recorded_at: null,
            policy_version: null,
        });
        expect(Object.keys(payload.disclosures)).toEqual(['chat', 'content', 'decision']);
        expect(payload.documents['transparency']?.url).toContain('/ai-compliance/policies/transparency');
        expect(payload.features['smart_summaries']).toEqual(['ai_training']);
        expect(payload.strings['preferences.save']).toBe('Save choices');
        expect(payload.endpoints.consents).toContain('/ai-compliance/consents');
    });
});

describe('boot', () => {
    it('fetches same-origin with cookies and stores the payload', async () => {
        const fetchMock = vi.fn().mockResolvedValueOnce(jsonResponse(payload));
        const client = new AiComplianceClient({ fetch: fetchMock as unknown as typeof fetch });

        await client.boot();

        expect(fetchMock).toHaveBeenCalledWith(
            '/ai-compliance/boot',
            expect.objectContaining({ credentials: 'same-origin' }),
        );
        expect(client.get().locale).toBe('en');
    });

    it('passes the locale through', async () => {
        const fetchMock = vi.fn().mockResolvedValueOnce(jsonResponse(payload));
        const client = new AiComplianceClient({ fetch: fetchMock as unknown as typeof fetch });

        await client.boot('de-CH');

        expect(fetchMock).toHaveBeenCalledWith('/ai-compliance/boot?locale=de-CH', expect.anything());
    });

    it('rejects a contract it does not speak', async () => {
        const fetchMock = vi.fn().mockResolvedValueOnce(jsonResponse({ ...payload, contract: 2 }));
        const client = new AiComplianceClient({ fetch: fetchMock as unknown as typeof fetch });

        await expect(client.boot()).rejects.toBeInstanceOf(ContractMismatchError);
    });

    it('throws when state is read before boot', () => {
        expect(() => new AiComplianceClient().get()).toThrow(NotBootedError);
    });
});

describe('consent state', () => {
    it('answers granted() from the state map with denied defaults', async () => {
        const client = await bootedClient(vi.fn());

        expect(client.granted('ai_training')).toBe(false);
        expect(client.stateOf('ai_training')?.status).toBe('denied');
        expect(client.stateOf('unknown_type')).toBeNull();
    });

    it('gates features on their required consents', async () => {
        const client = await bootedClient(vi.fn());

        expect(client.allows('smart_summaries')).toBe(false); // requires ai_training (denied)
        expect(client.allows('unconfigured')).toBe(false);
    });

    it('require() rejects with ConsentRequiredError until granted', async () => {
        const client = await bootedClient(vi.fn());

        await expect(client.require('ai_training')).rejects.toBeInstanceOf(ConsentRequiredError);
    });
});

describe('set', () => {
    it('posts with the xsrf cookie, applies the server state, and notifies listeners', async () => {
        const fetchMock = vi.fn();
        const client = await bootedClient(fetchMock);

        document.cookie = `XSRF-TOKEN=${encodeURIComponent('token+value')}`;

        fetchMock.mockResolvedValueOnce(
            jsonResponse(
                {
                    data: { id: 'x' },
                    state: {
                        ...payload.consent.state,
                        ai_training: { status: 'granted', recorded_at: 'now', policy_version: '1.0' },
                    },
                    reconsent: [],
                },
                201,
            ),
        );

        const changes: string[] = [];
        const unsubscribe = client.onChange((change) => changes.push(`${change.type}:${change.status}`));

        await client.set('ai_training', 'granted');

        const [url, init] = fetchMock.mock.calls[1] as [string, RequestInit];
        expect(url).toBe(payload.endpoints.consents);
        expect((init.headers as Record<string, string>)['X-XSRF-TOKEN']).toBe('token+value');
        expect(init.credentials).toBe('same-origin');

        expect(client.granted('ai_training')).toBe(true);
        expect(client.allows('smart_summaries')).toBe(true);
        expect(changes).toEqual(['ai_training:granted']);

        unsubscribe();
        expect(client.onChange(() => undefined)).toBeInstanceOf(Function);
    });

    it('prefers an explicit csrf token over cookies', async () => {
        const fetchMock = vi.fn().mockResolvedValueOnce(jsonResponse(payload));
        const client = new AiComplianceClient({
            fetch: fetchMock as unknown as typeof fetch,
            csrfToken: 'explicit-token',
        });
        await client.boot();

        fetchMock.mockResolvedValueOnce(jsonResponse({ state: payload.consent.state, reconsent: [] }, 201));
        await client.set('ai_chatbot', 'granted');

        const [, init] = fetchMock.mock.calls[1] as [string, RequestInit];
        expect((init.headers as Record<string, string>)['X-CSRF-TOKEN']).toBe('explicit-token');
    });

    it('surfaces server failures without touching local state', async () => {
        const fetchMock = vi.fn();
        const client = await bootedClient(fetchMock);

        fetchMock.mockResolvedValueOnce(jsonResponse({ message: 'nope' }, 422));

        await expect(client.set('ai_training', 'granted')).rejects.toThrow('422');
        expect(client.granted('ai_training')).toBe(false);
    });
});
