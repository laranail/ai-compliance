import type { AiComplianceClient } from './client.js';

/**
 * mounts an interactive replacement into one <ai-c> element. return a
 * cleanup function to run on unhydrate, or nothing.
 */
export type IslandMount = (
    element: HTMLElement,
    props: Record<string, string>,
    client: AiComplianceClient,
) => void | (() => void);

export type IslandRegistry = Record<string, IslandMount>;

/**
 * hydrates the <ai-c data-component data-props> elements the policy
 * compiler emits: registered components mount into their element (the
 * fallback text stays visible until then); unregistered ones keep the
 * fallback — a policy document never breaks because an island is missing.
 * returns a cleanup that unmounts everything it mounted.
 */
export function hydrate(
    root: ParentNode,
    registry: IslandRegistry,
    client: AiComplianceClient,
): () => void {
    const cleanups: Array<() => void> = [];

    for (const element of root.querySelectorAll<HTMLElement>('ai-c[data-component]')) {
        const name = element.dataset['component'];

        if (name === undefined) {
            continue;
        }

        const mount = registry[name];

        if (mount === undefined) {
            continue; // unregistered island: the server-rendered fallback stays
        }

        const cleanup = mount(element, decodeProps(element.dataset['props']), client);

        if (typeof cleanup === 'function') {
            cleanups.push(cleanup);
        }

        element.dataset['hydrated'] = 'true';
    }

    return () => {
        for (const cleanup of cleanups) {
            cleanup();
        }
    };
}

function decodeProps(encoded: string | undefined): Record<string, string> {
    if (encoded === undefined || encoded === '') {
        return {};
    }

    try {
        const decoded: unknown = JSON.parse(encoded);

        if (typeof decoded !== 'object' || decoded === null) {
            return {};
        }

        const props: Record<string, string> = {};

        for (const [key, value] of Object.entries(decoded)) {
            if (typeof value === 'string') {
                props[key] = value;
            }
        }

        return props;
    } catch {
        return {};
    }
}
