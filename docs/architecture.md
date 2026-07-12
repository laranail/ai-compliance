# Architecture

How the package is shaped, and why.

## The problem shape

AI compliance programs need three things at once: policy texts users can read
(and regulators can date-stamp), proof of what each user agreed to and when, and
running checks that the program is actually live. This package treats the policy
text as the root of that graph: everything users see is a versionable markdown
document, consents reference the exact version shown, and checks verify the
surfaces exist.

## Why markdown files and a database

Policies ship as per-locale markdown files: git-friendly, reviewable, publishable
into the app, editable without touching a database. But consent records need a
stable anchor that survives file edits and deployments, and in-app editing with
draft/publish cannot depend on a writable filesystem. So storage is hybrid: files
are the shipped defaults and the read-only layer; publishing (from the versioning
milestone) snapshots a version into the database, and the repository resolves
database-first, files second. Checksums on both sides flag drift instead of
silently overwriting either.

## Why plain markdown, not mdx

Five UI stacks must render the same source: Blade, Livewire, Filament, React,
Vue. MDX is executable JSX: it needs a JS compile step and cannot run in the
three PHP stacks, and letting policy editors write executable content is a
security hazard. Instead, dynamic islands use a closed shortcode vocabulary:
`[[consent-toggle type="ai_training" fallback="…"]]` compiles to a neutral
`<ai-c data-component data-props>` element. Server stacks replace those nodes
while rendering; the JS core hydrates them in the browser; everything else shows
the fallback text. Unknown shortcodes degrade to their fallback and log a
warning, so a typo can never blank a legal document.

## The pipeline

```
PolicyFileLoader          app-published dir overlays the package dir
  └─ PolicyCompiler       commonmark: frontmatter -> meta, [[shortcodes]] -> <ai-c>,
     │                    raw html escaped, {{placeholders}} untouched
  └─ CompiledPolicyCache  content-addressed by source checksum
  └─ PolicyRepository     locale fallback chain, db-first from the versioning milestone
     └─ PlaceholderRegistry   serve-time {{placeholder}} substitution + unresolved report
        └─ BootPayload        the shared contract (contract: 1)
```

Placeholder substitution happens at serve time on purpose: stored and cached
content stays faithful to what was authored, and a config change (new contact
email) takes effect without republishing anything.

## The shared contract

One payload feeds all five UI stacks: `GET /ai-compliance/boot` for the JS core,
`AiCompliance::bootPayload()` in-process for Blade/Livewire/Filament. It carries
a `contract` integer that only bumps on breaking shape changes, so JS bindings
can verify compatibility at boot.

## Why the compiler escapes raw html

Policy sources are data authored by operators (and later edited in the admin),
not trusted markup. `html_input => 'escape'` neutralizes pasted html; the
shortcode vocabulary is the only interactive escape hatch, and it is a closed,
registered list.

## The subsystems around the pipeline

Everything else consumes the pipeline or feeds evidence around it: policy
versioning with draft/publish and checksum staleness
([policy versioning](tools/policy-versioning.md)); the append-only consent
core whose records reference the exact policy version shown
([consent](tools/consent.md)); the five UI stacks over the one contract
(Blade/Livewire server-side, React/Vue via the JS core, and the Filament
plugin); the checklist and checks engine that keep the program verifiable
([checks](tools/checks.md)); the activity log with tamper evidence, retention,
and do-not-train enforcement ([activity log](tools/activity-log.md)); and the
pseudonymized exports and auditor report
([exports and reports](tools/exports-and-reports.md)).

## See also

- [Policy pipeline](tools/policy-pipeline.md)
- [Configuration](configuration.md)

---

[← Docs index](../README.md#documentation)
