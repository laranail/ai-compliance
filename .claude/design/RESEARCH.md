# Research

Answers to the four design questions, each with a recommendation and the rejected
alternatives. Decisions here were confirmed with the maintainer on 2026-07-05 and are
binding; DESIGN.md builds on them.

## A. Policy storage: files vs database vs hybrid

**Recommendation: hybrid.** Markdown files are the shipped, git-friendly, editable
defaults; publishing snapshots a version into the database (documents → versions →
per-locale translations). The database is the runtime source of truth for anything
ever published; files are the source of truth for defaults and for projects that
never touch the admin editor.

Why:

- Consent records need a stable anchor. "This user consented to policy 1.0" must
  survive file edits, deployments, and package upgrades. A DB version row
  (`ai_policy_versions`) gives every consent record a foreign key to the exact text
  shown; files alone can't do that without git-sha bookkeeping inside the app.
- The brief requires in-app editing with draft/publish. Pure files would need the web
  process to write to disk — breaks on read-only filesystems, multi-server
  deployments, and containerized hosts, and puts admin edits outside the DB backup.
- Shipped defaults must stay upgradeable. Files under `resources/policies/` publish
  into the app like views do; a package upgrade can ship improved templates and the
  checksum-based sync flags (never overwrites) operator-edited documents.

Rejected:

- **Pure files** — no admin editing without fs writes; no stable `policy_version_id`
  for consent rows; multi-server drift. Retained only as the read-only layer for
  documents never published to the DB (M1 works entirely in this mode).
- **Pure DB** — loses git history for defaults, makes the shipped templates awkward
  (seeder blobs instead of reviewable md files), and couples policy authoring to a
  running app. The original spec's JSON-columns table was this shape; superseded.

## B. Rich markdown editor

**Recommendation: Milkdown** for the package-owned editing surfaces (the JS core wraps
it once; React/Vue bindings and the Livewire bridge reuse that wrapper), and
**Filament's built-in `MarkdownEditor` field** inside the Filament plugin so it feels
native there. Both edit the same `source_markdown` through the same preview endpoint,
so the pipeline doesn't care which produced the text.

| Editor | License | Size (min+gz, editor core) | Markdown fidelity | Framework coupling | Notes |
|---|---|---|---|---|---|
| **Milkdown** | MIT | ~110–130 KB with commonmark preset | Native — the document model IS CommonMark AST (remark-based) | None (plugin system; official React/Vue helpers) | WYSIWYG-on-markdown; ProseMirror underneath; actively maintained |
| Tiptap + tiptap-markdown | MIT (core) | ~90–120 KB + extension | Markdown is a serialization bolt-on; round-trip can be lossy (HTML-first document model) | None, good React/Vue support | Excellent editor, but md fidelity matters more here — policies are stored as md |
| EasyMDE | MIT | ~75 KB + CodeMirror 5 | Perfect (it's a textarea with preview) | None | Effectively unmaintained (CodeMirror 5 lineage); side-by-side preview, not WYSIWYG. This is what Filament's MarkdownEditor wraps, which is why the Filament panel gets it for free |
| ToastUI Editor | MIT | ~400–500 KB | Good | None | Heaviest by far; slower release cadence; overkill |

Why Milkdown: byte-faithful markdown round-tripping (the versioning/staleness design
depends on checksums over `source_markdown`, so a lossy editor would create phantom
edits), MIT license, framework-agnostic core matching the one-core-many-bindings shape
of this package, and a plugin slot where the `[[shortcode]]` convention (question C)
can render as a widget instead of raw text.

Editor dependencies are npm-side only (`packages/core` optional peer); the PHP package
never bundles JS beyond the precompiled component assets, and the Filament plugin adds
no npm dependency at all.

## C. md vs mdx

**Recommendation: plain markdown (CommonMark) + a shortcode convention; no MDX.**

Policy files are CommonMark with YAML frontmatter. Dynamic/interactive islands use a
shortcode: `[[consent-toggle type="ai_training"]]`. The compiler turns each shortcode
into a neutral custom element — `<ai-c data-component="consent-toggle"
data-props='{"type":"ai_training"}'>fallback text</ai-c>` — and each stack binds it
natively: Blade/Livewire/Filament replace the nodes server-side while rendering; the
JS core ships a hydrator that mounts registered React/Vue/vanilla components into
them. Unknown shortcodes render their fallback text and log a warning, so a typo can
never blank a legal document.

Why:

- MDX is JSX: it requires a JS compile step and imports React (or a Vue analogue)
  component scope. Three of the five required stacks (Blade, Livewire, Filament)
  could not execute it at all — the "same policy source feeds all five stacks"
  requirement rules MDX out on its own.
- Admin-edited content is data, not code. MDX executes; letting the policy editor
  inject executable component code is an XSS/RCE-shaped hazard. Shortcodes are a
  closed, registered vocabulary.
- CommonMark + frontmatter is exactly what league/commonmark (already in every
  Laravel app) parses; the whole compile step stays in PHP where the versioning,
  checksums, and caching live.

Rejected: **true MDX** (React/Vue only, compile step, executable content);
**HTML-in-markdown islands** (no server-side vocabulary, XSS surface — the compiler
escapes raw HTML instead, `html_input => 'escape'`).

## D. Multilanguage layout

**Recommendation: per-locale directories** — `resources/policies/{locale}/{path}.md`
(`en/consent/ai_training.md`, `de/transparency.md`), mirroring Laravel's
`lang/{locale}/` idiom. Component strings (button labels, "updated" badges) live in
ordinary package translations (`resources/lang/{locale}/ai-compliance.php`),
publishable and overridable the normal Laravel way.

- **Fallback**: resolution walks `config('laranail.ai-compliance.locales.fallbacks')`
  (e.g. `['de-CH' => ['de', 'en']]`), then the app's `app.fallback_locale`, then the
  package default (`en`). Applied uniformly at three layers: file loading, DB
  translation lookup, and the boot payload — which reports the locale actually served
  (`"locale": "en"` when a `de` request fell back) so UIs can show a "not yet
  translated" hint.
- **Staleness** is two sha256 checksum comparisons, both stored on
  `ai_policy_translations`:
  1. *File drift* — `file_checksum` (the shipped file's hash at import) vs the file's
     current hash. Diverged + DB copy never hand-edited (`checksum ==
     file_checksum`) → sync auto-creates a new draft. Diverged + hand-edited → flag
     for review, never overwrite.
  2. *Translation drift* — `origin_checksum` (hash of the default-locale source a
     translation was made from) vs the default-locale translation's current
     `checksum`. Diverged → that locale is stale; flagged in the admin API, the
     staleness report, and (from M6) a checklist item.

Rejected: **per-locale filenames** (`policy.en.md` — flat dirs get noisy at
14 files × N locales, and diffing "what's missing in de" is a directory listing away
in the chosen layout, a filename parse in this one); **gettext-style po/mo or
translation keys inside one file** (policies are whole documents, not strings;
translators work per document).
