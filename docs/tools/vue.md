# Vue

Reference for `@laranail/ai-compliance-vue`.

```bash
npm install @laranail/ai-compliance-vue
```

Peer dependency: vue >= 3.4. The plugin boots the shared client once and
provides reactive state to the composable and components.

```ts
import { createApp } from 'vue';
import { createAiConsent } from '@laranail/ai-compliance-vue';

createApp(App).use(createAiConsent()).mount('#app');
```

```vue
<script setup>
import { AiGate, AiDisclosure, AiPreferences, AiReconsentPrompt, useAiConsent } from '@laranail/ai-compliance-vue';

const { granted, set } = useAiConsent();
</script>

<template>
    <AiReconsentPrompt />

    <AiGate consent="ai_chatbot">
        <AiDisclosure surface="chat" />
        <ChatWindow :personalized="granted('ai_personalization')" />

        <template #fallback>
            <AiPreferences />
        </template>
    </AiGate>
</template>
```

| Export | What |
|---|---|
| `createAiConsent(options)` | the plugin: `client`, `endpoint`, `locale` options |
| `useAiConsent()` | `{ client, payload, granted, allows, set }` (payload is a shallow ref) |
| `AiGate` | default slot when `consent` / `feature` allows; `#fallback` otherwise; pre-boot counts as denied |
| `AiDisclosure` | the versioned disclosure line for a `surface` |
| `AiPreferences` | the consent panel; toggles call `set()` and re-render from the server state |
| `AiReconsentPrompt` | renders only when the boot payload flags superseded consents |

The components are render-function based — no SFC compiler is required to
consume the package, and all strings come from the payload.

## See also

- [JS SDK](js-sdk.md)
- [React](react.md)

---

[← Docs index](../../README.md#documentation)
