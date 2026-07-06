import {
    AiComplianceClient,
    type BootPayload,
    type ClientOptions,
    type ConsentStatus,
} from '@laranail/ai-compliance';
import {
    computed,
    defineComponent,
    h,
    inject,
    shallowRef,
    type App,
    type InjectionKey,
    type PropType,
    type ShallowRef,
} from 'vue';

interface AiConsentContext {
    client: AiComplianceClient;
    payload: ShallowRef<BootPayload | null>;
    granted: (type: string) => boolean;
    allows: (feature: string) => boolean;
    set: (type: string, status: ConsentStatus) => Promise<void>;
}

const AiConsentKey: InjectionKey<AiConsentContext> = Symbol('ai-compliance');

export interface AiConsentPluginOptions extends ClientOptions {
    client?: AiComplianceClient;
    locale?: string;
}

/**
 * app.use(createAiConsent()) boots the client once and provides live
 * consent state to the composable and components. consent-dependent
 * components treat the pre-boot window as denied-by-default.
 */
export function createAiConsent(options: AiConsentPluginOptions = {}) {
    return {
        install(app: App): void {
            const client = options.client ?? new AiComplianceClient(options);
            const payload = shallowRef<BootPayload | null>(client.booted() ? client.get() : null);

            if (!client.booted()) {
                void client.boot(options.locale).then((booted) => {
                    payload.value = booted;
                });
            }

            client.onChange((_change, fresh) => {
                payload.value = { ...fresh };
            });

            app.provide(AiConsentKey, {
                client,
                payload,
                granted: (type) => payload.value !== null && client.granted(type),
                allows: (feature) => payload.value !== null && client.allows(feature),
                set: (type, status) => client.set(type, status),
            });
        },
    };
}

export function useAiConsent(): AiConsentContext {
    const context = inject(AiConsentKey);

    if (context === undefined) {
        throw new Error('useAiConsent() needs app.use(createAiConsent()) first');
    }

    return context;
}

/** renders the default slot only when the subject may proceed; #fallback otherwise */
export const AiGate = defineComponent({
    name: 'AiGate',
    props: {
        consent: { type: String, default: undefined },
        feature: { type: String, default: undefined },
    },
    setup(props, { slots }) {
        const context = useAiConsent();

        if (props.consent === undefined && props.feature === undefined) {
            throw new Error('<AiGate> needs a consent or a feature prop');
        }

        const allowed = computed(
            () =>
                (props.feature === undefined || context.allows(props.feature)) &&
                (props.consent === undefined || context.granted(props.consent)),
        );

        return () => (allowed.value ? slots['default']?.() : slots['fallback']?.() ?? null);
    },
});

/** the versioned, translated ai-disclosure line for a surface */
export const AiDisclosure = defineComponent({
    name: 'AiDisclosure',
    props: {
        surface: { type: String, default: 'chat' },
    },
    setup(props) {
        const { payload } = useAiConsent();

        return () => {
            const disclosure = payload.value?.disclosures[props.surface];

            if (disclosure === undefined) {
                return null;
            }

            return h('div', { class: 'ai-compliance-disclosure', role: 'note', 'data-surface': props.surface }, [
                h(
                    'span',
                    { class: 'ai-compliance-disclosure-badge', 'aria-hidden': 'true' },
                    payload.value?.strings['disclosure.badge'] ?? 'AI',
                ),
                h('span', { class: 'ai-compliance-disclosure-text', innerHTML: disclosure.html }),
            ]);
        };
    },
});

/** the consent preferences panel over the shared payload */
export const AiPreferences = defineComponent({
    name: 'AiPreferences',
    setup() {
        const context = useAiConsent();

        return () => {
            const booted = context.payload.value;

            if (booted === null) {
                return null;
            }

            const strings = booted.strings;
            const reconsent = booted.consent.reconsent;

            return h('section', { class: 'ai-compliance-preferences' }, [
                h('h2', { class: 'ai-compliance-preferences-title' }, strings['preferences.title']),
                h('p', { class: 'ai-compliance-preferences-intro' }, strings['preferences.intro']),
                ...booted.consent.types.map((type) => {
                    const granted = context.granted(type.slug);

                    return h(
                        'div',
                        {
                            key: type.slug,
                            class: 'ai-compliance-preference',
                            'data-consent-type': type.slug,
                            'data-reconsent': reconsent.includes(type.slug) ? 'true' : undefined,
                        },
                        [
                            h('div', { class: 'ai-compliance-preference-heading' }, [
                                h('strong', type.label ?? type.slug),
                                h(
                                    'span',
                                    { class: 'ai-compliance-preference-status' },
                                    granted ? strings['preferences.granted'] : strings['preferences.denied'],
                                ),
                            ]),
                            reconsent.includes(type.slug)
                                ? h(
                                      'p',
                                      { class: 'ai-compliance-preference-reconsent', role: 'alert' },
                                      strings['reconsent.title'],
                                  )
                                : null,
                            type.short_html !== null
                                ? h('p', { class: 'ai-compliance-preference-short', innerHTML: type.short_html })
                                : null,
                            h(
                                'button',
                                {
                                    type: 'button',
                                    onClick: () => void context.set(type.slug, granted ? 'withdrawn' : 'granted'),
                                },
                                granted ? strings['preferences.withdraw'] : strings['preferences.allow'],
                            ),
                        ],
                    );
                }),
            ]);
        };
    },
});

/** shows only when granted consents reference superseded policy versions */
export const AiReconsentPrompt = defineComponent({
    name: 'AiReconsentPrompt',
    setup() {
        const context = useAiConsent();

        return () => {
            const booted = context.payload.value;

            if (booted === null || booted.consent.reconsent.length === 0) {
                return null;
            }

            const labels = new Map(booted.consent.types.map((type) => [type.slug, type.label ?? type.slug]));

            return h('div', { class: 'ai-compliance-reconsent' }, [
                h('p', { class: 'ai-compliance-reconsent-title', role: 'alert' }, booted.strings['reconsent.title']),
                ...booted.consent.reconsent.map((type) =>
                    h('div', { key: type, class: 'ai-compliance-reconsent-item', 'data-consent-type': type }, [
                        h('span', labels.get(type)),
                        h(
                            'button',
                            { type: 'button', onClick: () => void context.set(type, 'granted') },
                            booted.strings['reconsent.review'],
                        ),
                    ]),
                ),
            ]);
        };
    },
});
