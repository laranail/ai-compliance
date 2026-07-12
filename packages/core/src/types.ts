/**
 * the shared component contract served by GET /ai-compliance/boot and
 * consumed in-process by the blade/livewire/filament stacks. CONTRACT bumps
 * only on breaking shape changes; additive keys are fine within a major.
 */
export const CONTRACT = 1;

export type ConsentStatus = 'granted' | 'denied' | 'withdrawn';

export interface ConsentTypeInfo {
    slug: string;
    label: string | null;
    description: string | null;
    legal_basis: string;
    default_state: ConsentStatus;
    short_html: string | null;
    policy_version: string | null;
}

export interface ConsentStateEntry {
    status: ConsentStatus;
    recorded_at: string | null;
    policy_version: string | null;
}

export interface DisclosureEntry {
    html: string;
    version: string | null;
}

export interface DocumentEntry {
    title: string;
    url: string;
    version: string | null;
}

export interface BootPayload {
    contract: number;
    locale: string;
    fallback_locale: string;
    consent: {
        types: ConsentTypeInfo[];
        state: Record<string, ConsentStateEntry>;
        reconsent: string[];
    };
    disclosures: Record<string, DisclosureEntry>;
    documents: Record<string, DocumentEntry>;
    features: Record<string, string[]>;
    strings: Record<string, string>;
    endpoints: {
        boot: string;
        policy: string;
        consents: string;
    };
    guest_key: string | null;
}

export interface ConsentChange {
    type: string;
    status: ConsentStatus;
}

export type ChangeListener = (change: ConsentChange, payload: BootPayload) => void;
