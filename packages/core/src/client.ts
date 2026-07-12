import {
    type BootPayload,
    type ChangeListener,
    type ConsentStateEntry,
    type ConsentStatus,
    CONTRACT,
} from './types.js';

export interface ClientOptions {
    /** the boot endpoint; default /ai-compliance/boot */
    endpoint?: string;
    /** explicit csrf token; defaults to the XSRF-TOKEN cookie or the csrf-token meta tag */
    csrfToken?: string;
    /** custom fetch (tests, ssr); defaults to globalThis.fetch */
    fetch?: typeof globalThis.fetch;
}

export class ContractMismatchError extends Error {
    constructor(public readonly served: number) {
        super(
            `ai-compliance boot payload is contract ${served}, this client expects ${CONTRACT}; ` +
                'upgrade @laranail/ai-compliance together with the composer package',
        );
        this.name = 'ContractMismatchError';
    }
}

export class ConsentRequiredError extends Error {
    constructor(public readonly type: string) {
        super(`consent [${type}] has not been granted`);
        this.name = 'ConsentRequiredError';
    }
}

export class NotBootedError extends Error {
    constructor() {
        super('call boot() before reading ai-compliance state');
        this.name = 'NotBootedError';
    }
}

/**
 * framework-agnostic client over the boot contract. same-origin only: it
 * sends the session/guest cookies with every request and never talks to a
 * third party. state updates come from the server's authoritative response,
 * not optimistic writes.
 */
export class AiComplianceClient {
    private payload: BootPayload | null = null;

    private readonly listeners = new Set<ChangeListener>();

    private readonly endpoint: string;

    private readonly csrfToken: string | undefined;

    private readonly fetchImpl: typeof globalThis.fetch;

    constructor(options: ClientOptions = {}) {
        this.endpoint = options.endpoint ?? '/ai-compliance/boot';
        this.csrfToken = options.csrfToken;
        this.fetchImpl = options.fetch ?? globalThis.fetch.bind(globalThis);
    }

    async boot(locale?: string): Promise<BootPayload> {
        const url = locale ? `${this.endpoint}?locale=${encodeURIComponent(locale)}` : this.endpoint;

        const response = await this.fetchImpl(url, {
            headers: { Accept: 'application/json' },
            credentials: 'same-origin',
        });

        if (!response.ok) {
            throw new Error(`ai-compliance boot failed with status ${response.status}`);
        }

        const payload = (await response.json()) as BootPayload;

        if (payload.contract !== CONTRACT) {
            throw new ContractMismatchError(payload.contract);
        }

        this.payload = payload;

        return payload;
    }

    /** the last booted payload; throws before boot() */
    get(): BootPayload {
        if (this.payload === null) {
            throw new NotBootedError();
        }

        return this.payload;
    }

    booted(): boolean {
        return this.payload !== null;
    }

    stateOf(type: string): ConsentStateEntry | null {
        return this.get().consent.state[type] ?? null;
    }

    granted(type: string): boolean {
        return this.stateOf(type)?.status === 'granted';
    }

    /** every consent the feature requires must be granted; unknown features are denied */
    allows(feature: string): boolean {
        const required = this.get().features[feature];

        if (required === undefined) {
            return false;
        }

        return required.every((type) => this.granted(type));
    }

    /** consent types whose granted version was superseded */
    reconsent(): string[] {
        return this.get().consent.reconsent;
    }

    /** resolves when granted, rejects with ConsentRequiredError otherwise */
    async require(type: string): Promise<void> {
        if (!this.granted(type)) {
            throw new ConsentRequiredError(type);
        }
    }

    /** record a consent decision; the server response refreshes local state */
    async set(type: string, status: ConsentStatus): Promise<void> {
        const payload = this.get();

        const response = await this.fetchImpl(payload.endpoints.consents, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                ...this.csrfHeaders(),
            },
            credentials: 'same-origin',
            body: JSON.stringify({ type, status }),
        });

        if (!response.ok) {
            throw new Error(`recording consent failed with status ${response.status}`);
        }

        const body = (await response.json()) as {
            state: Record<string, ConsentStateEntry>;
            reconsent: string[];
        };

        this.payload = {
            ...payload,
            consent: { ...payload.consent, state: body.state, reconsent: body.reconsent },
        };

        const change = { type, status };

        for (const listener of this.listeners) {
            listener(change, this.payload);
        }
    }

    /** subscribe to consent changes; returns the unsubscriber */
    onChange(listener: ChangeListener): () => void {
        this.listeners.add(listener);

        return () => this.listeners.delete(listener);
    }

    private csrfHeaders(): Record<string, string> {
        if (this.csrfToken !== undefined) {
            return { 'X-CSRF-TOKEN': this.csrfToken };
        }

        const xsrf = readCookie('XSRF-TOKEN');

        if (xsrf !== null) {
            return { 'X-XSRF-TOKEN': xsrf };
        }

        const meta =
            typeof document !== 'undefined'
                ? document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
                : null;

        return meta?.content ? { 'X-CSRF-TOKEN': meta.content } : {};
    }
}

function readCookie(name: string): string | null {
    if (typeof document === 'undefined') {
        return null;
    }

    for (const part of document.cookie.split('; ')) {
        const eq = part.indexOf('=');

        if (eq > 0 && part.slice(0, eq) === name) {
            return decodeURIComponent(part.slice(eq + 1));
        }
    }

    return null;
}
