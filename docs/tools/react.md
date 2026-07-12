# React

Reference for `@laranail/ai-compliance-react`.

```bash
npm install @laranail/ai-compliance-react
```

Peer dependency: react >= 18. The provider boots the shared client once and
re-renders consumers on every consent change.

```tsx
import {
    AiConsentProvider,
    useAiConsent,
    AiGate,
    AiDisclosure,
    AiPreferences,
    AiReconsentPrompt,
} from '@laranail/ai-compliance-react';

function App() {
    return (
        <AiConsentProvider>
            <AiReconsentPrompt />
            <Chat />
        </AiConsentProvider>
    );
}

function Chat() {
    const { granted, set } = useAiConsent();

    return (
        <AiGate consent="ai_chatbot" fallback={<AiPreferences />}>
            <AiDisclosure surface="chat" />
            <ChatWindow personalized={granted('ai_personalization')} />
        </AiGate>
    );
}
```

| Export | What |
|---|---|
| `AiConsentProvider` | boots the client (`client`, `endpoint`, `locale` props) and provides live state |
| `useAiConsent()` | `{ client, payload, granted, allows, set, reconsent, strings }` |
| `AiGate` | children when `consent` / `feature` allows; `fallback` otherwise; pre-boot counts as denied |
| `AiDisclosure` | the versioned disclosure line for a `surface` |
| `AiPreferences` | the consent panel; toggles call `set()` and re-render from the server state |
| `AiReconsentPrompt` | renders only when the boot payload flags superseded consents; one-click re-grant |

All strings come from the payload (`strings.*`), so the components speak the
app's locale with no extra i18n setup.

## See also

- [JS SDK](js-sdk.md)
- [Vue](vue.md)

---

[← Docs index](../../README.md#documentation)
