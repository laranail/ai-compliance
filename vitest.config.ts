import { defineConfig } from 'vitest/config';

export default defineConfig({
    test: {
        environment: 'jsdom',
        include: ['packages/*/tests/**/*.test.{ts,tsx}'],
    },
    resolve: {
        alias: {
            '@laranail/ai-compliance': new URL('./packages/core/src/index.ts', import.meta.url).pathname,
        },
    },
});
