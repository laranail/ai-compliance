import { describe, expect, it, vi } from 'vitest';
import { AiComplianceClient, hydrate } from '../src/index.js';

const client = new AiComplianceClient();

function container(html: string): HTMLElement {
    const element = document.createElement('div');
    element.innerHTML = html;

    return element;
}

describe('hydrate', () => {
    it('mounts registered islands into their ai-c elements with decoded props', () => {
        const root = container(
            '<ai-c data-component="consent-toggle" data-props=\'{"type":"ai_training"}\'>fallback</ai-c>',
        );

        const mount = vi.fn((element: HTMLElement, props: Record<string, string>) => {
            element.textContent = `toggle:${props['type']}`;
        });

        hydrate(root, { 'consent-toggle': mount }, client);

        expect(mount).toHaveBeenCalledOnce();
        expect(root.textContent).toBe('toggle:ai_training');
        expect(root.querySelector('ai-c')?.getAttribute('data-hydrated')).toBe('true');
    });

    it('leaves unregistered islands showing their fallback text', () => {
        const root = container('<ai-c data-component="provider-list" data-props="{}">the fallback</ai-c>');

        hydrate(root, {}, client);

        expect(root.textContent).toBe('the fallback');
        expect(root.querySelector('ai-c')?.getAttribute('data-hydrated')).toBeNull();
    });

    it('runs every mount cleanup on unhydrate', () => {
        const root = container(
            '<ai-c data-component="a" data-props="{}"></ai-c><ai-c data-component="a" data-props="{}"></ai-c>',
        );

        const cleanup = vi.fn();
        const unhydrate = hydrate(root, { a: () => cleanup }, client);

        unhydrate();

        expect(cleanup).toHaveBeenCalledTimes(2);
    });

    it('tolerates malformed props json', () => {
        const root = container('<ai-c data-component="a" data-props="not-json">x</ai-c>');
        const mount = vi.fn();

        hydrate(root, { a: mount }, client);

        expect(mount).toHaveBeenCalledWith(expect.anything(), {}, client);
    });
});
