import {
    AiComplianceClient,
    type BootPayload,
    type ClientOptions,
    type ConsentStatus,
} from '@laranail/ai-compliance';
import {
    createContext,
    useCallback,
    useContext,
    useEffect,
    useMemo,
    useState,
    type ReactNode,
} from 'react';

interface AiConsentContextValue {
    client: AiComplianceClient;
    payload: BootPayload | null;
    granted: (type: string) => boolean;
    allows: (feature: string) => boolean;
    set: (type: string, status: ConsentStatus) => Promise<void>;
    reconsent: string[];
    strings: Record<string, string>;
}

const AiConsentContext = createContext<AiConsentContextValue | null>(null);

export interface AiConsentProviderProps extends ClientOptions {
    client?: AiComplianceClient;
    locale?: string;
    children: ReactNode;
}

/**
 * boots the client once and shares live consent state with every component
 * below it. children render immediately; consent-dependent components treat
 * the pre-boot window as denied-by-default.
 */
export function AiConsentProvider({ client, locale, children, ...options }: AiConsentProviderProps) {
    const resolvedClient = useMemo(() => client ?? new AiComplianceClient(options), [client]);
    const [payload, setPayload] = useState<BootPayload | null>(
        resolvedClient.booted() ? resolvedClient.get() : null,
    );

    useEffect(() => {
        let active = true;

        if (!resolvedClient.booted()) {
            void resolvedClient.boot(locale).then((booted) => {
                if (active) setPayload(booted);
            });
        }

        const unsubscribe = resolvedClient.onChange((_change, fresh) => {
            if (active) setPayload({ ...fresh });
        });

        return () => {
            active = false;
            unsubscribe();
        };
    }, [resolvedClient, locale]);

    const value = useMemo<AiConsentContextValue>(
        () => ({
            client: resolvedClient,
            payload,
            granted: (type) => payload !== null && resolvedClient.granted(type),
            allows: (feature) => payload !== null && resolvedClient.allows(feature),
            set: (type, status) => resolvedClient.set(type, status),
            reconsent: payload?.consent.reconsent ?? [],
            strings: payload?.strings ?? {},
        }),
        [resolvedClient, payload],
    );

    return <AiConsentContext.Provider value={value}>{children}</AiConsentContext.Provider>;
}

export function useAiConsent(): AiConsentContextValue {
    const context = useContext(AiConsentContext);

    if (context === null) {
        throw new Error('useAiConsent() needs an <AiConsentProvider> above it');
    }

    return context;
}

export interface AiGateProps {
    consent?: string;
    feature?: string;
    fallback?: ReactNode;
    children: ReactNode;
}

/** renders children only when the subject may proceed; fallback otherwise */
export function AiGate({ consent, feature, fallback = null, children }: AiGateProps) {
    const { granted, allows } = useAiConsent();

    if (consent === undefined && feature === undefined) {
        throw new Error('<AiGate> needs a consent or a feature prop');
    }

    const allowed =
        (feature === undefined || allows(feature)) && (consent === undefined || granted(consent));

    return <>{allowed ? children : fallback}</>;
}

export interface AiDisclosureProps {
    surface?: string;
}

/** the versioned, translated ai-disclosure line for a surface */
export function AiDisclosure({ surface = 'chat' }: AiDisclosureProps) {
    const { payload, strings } = useAiConsent();
    const disclosure = payload?.disclosures[surface];

    if (disclosure === undefined) {
        return null;
    }

    return (
        <div className="ai-compliance-disclosure" role="note" data-surface={surface}>
            <span className="ai-compliance-disclosure-badge" aria-hidden="true">
                {strings['disclosure.badge'] ?? 'AI'}
            </span>
            <span
                className="ai-compliance-disclosure-text"
                dangerouslySetInnerHTML={{ __html: disclosure.html }}
            />
        </div>
    );
}

/** the consent preferences panel over the shared payload */
export function AiPreferences() {
    const { payload, granted, set, reconsent, strings } = useAiConsent();

    if (payload === null) {
        return null;
    }

    return (
        <section className="ai-compliance-preferences">
            <h2 className="ai-compliance-preferences-title">{strings['preferences.title']}</h2>
            <p className="ai-compliance-preferences-intro">{strings['preferences.intro']}</p>

            {payload.consent.types.map((type) => {
                const isGranted = granted(type.slug);

                return (
                    <div
                        key={type.slug}
                        className="ai-compliance-preference"
                        data-consent-type={type.slug}
                        data-reconsent={reconsent.includes(type.slug) || undefined}
                    >
                        <div className="ai-compliance-preference-heading">
                            <strong>{type.label ?? type.slug}</strong>
                            <span className="ai-compliance-preference-status">
                                {isGranted ? strings['preferences.granted'] : strings['preferences.denied']}
                            </span>
                        </div>

                        {reconsent.includes(type.slug) ? (
                            <p className="ai-compliance-preference-reconsent" role="alert">
                                {strings['reconsent.title']}
                            </p>
                        ) : null}

                        {type.short_html !== null ? (
                            <p
                                className="ai-compliance-preference-short"
                                dangerouslySetInnerHTML={{ __html: type.short_html }}
                            />
                        ) : null}

                        <button
                            type="button"
                            onClick={() => void set(type.slug, isGranted ? 'withdrawn' : 'granted')}
                        >
                            {isGranted ? strings['preferences.withdraw'] : strings['preferences.allow']}
                        </button>
                    </div>
                );
            })}
        </section>
    );
}

/** shows only when granted consents reference superseded policy versions */
export function AiReconsentPrompt() {
    const { payload, set, reconsent, strings } = useAiConsent();

    if (payload === null || reconsent.length === 0) {
        return null;
    }

    const labels = new Map(payload.consent.types.map((type) => [type.slug, type.label ?? type.slug]));

    return (
        <div className="ai-compliance-reconsent">
            <p className="ai-compliance-reconsent-title" role="alert">
                {strings['reconsent.title']}
            </p>

            {reconsent.map((type) => (
                <div key={type} className="ai-compliance-reconsent-item" data-consent-type={type}>
                    <span>{labels.get(type)}</span>
                    <button type="button" onClick={() => void set(type, 'granted')}>
                        {strings['reconsent.review']}
                    </button>
                </div>
            ))}
        </div>
    );
}
