# JS SDK

Reference for `@laranail/ai-compliance`, the framework-agnostic client the React and Vue bindings sit on.

```bash
npm install @laranail/ai-compliance
```

The composer package and the npm packages release in lockstep from the same
`vX.Y.Z` tag; the boot payload carries a `contract` integer the client
verifies at boot (`ContractMismatchError` on mismatch), so a stale JS bundle
fails loudly instead of misreading state.

## The client

```ts
import { AiComplianceClient } from '@laranail/ai-compliance';

const client = new AiComplianceClient(); // endpoint default /ai-compliance/boot
await client.boot();                     // or boot('de-CH')

client.granted('ai_chatbot');            // current state, denied-by-default
client.allows('smart_summaries');        // feature gating from the payload's features map
client.reconsent();                      // types whose granted version was superseded
await client.require('ai_chatbot');      // rejects with ConsentRequiredError

await client.set('ai_training', 'granted');
const unsubscribe = client.onChange((change, payload) => { /* re-render */ });
```

Everything is same-origin: requests carry the session/guest cookies
(`credentials: 'same-origin'`), no third party is ever contacted, and the
client never mints identity: the guest key is issued by the server on the
first consent write. `set()` applies the server's authoritative state from the
response; there are no optimistic writes. CSRF is automatic: the `XSRF-TOKEN`
cookie (Laravel's default), the `csrf-token` meta tag, or an explicit
`csrfToken` option.

## Hydrating `<ai-c>` islands

Server-compiled policy html carries `<ai-c data-component data-props>`
elements. Register mounts for the components you support; anything
unregistered keeps its server-rendered fallback text:

```ts
import { hydrate } from '@laranail/ai-compliance';

const unhydrate = hydrate(document.body, {
    'consent-toggle': (element, props, client) => {
        // mount your toggle for props.type; return a cleanup if needed
    },
}, client);
```

## The contract fixture

`packages/core/tests/fixtures/boot.json` is recorded by the Pest suite
(`AI_COMPLIANCE_EXPORT_FIXTURE=1`) and pinned by vitest, so the PHP serializer
and the TypeScript types can never drift silently.

## See also

- [React](react.md)
- [Vue](vue.md)
- [Consent](consent.md)

---

[← Docs index](../../README.md#documentation)
