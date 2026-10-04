# Stack: bricks-native

**Status: STUB (alpha).** Real content arrives at mpd-core spec Stage 8, after the proving project ships. Until then this file points at the project that owns the rules.

A `bricks-native` site runs Bricks with **no Automatic.css installed**, ever: not deactivated, not "for reference." Tokens live in the Bricks Style Manager. A small framework plugin, **mpd-core**, reproduces the subset of ACSS v3 conventions in actual use, **under the same names**, so the KB's untagged conventions and habits carry over.

## How this file is used

`00`, read protocol step 4: a project whose `CLAUDE.md` declares `stack: bricks-native` loads this file, and every `[stack:acss]` rule in the knowledgebase is replaced by what is listed here. Untagged rules apply unchanged.

## During alpha, the authority is the proving project

- **Spec:** `docs/mpd-core-alpha-build-spec.md` in the pkjsupport project (Local: `app/public/docs/`).
- **Replacement table:** that project's `CLAUDE.md` → "Suspended KB rules and replacements." It is authoritative over this stub until Stage 8 copies it here.
- **Token map:** the project's `mpd-core/TOKENS.md` plus its committed Style Manager export.

Summary of the replacements, so a session can orient without opening the project. If anything here disagrees with the project table, the table wins:

| `[stack:acss]` rule | bricks-native replacement |
|---|---|
| Pinned brand tokens live in ACSS Global CSS | Tokens live in the Bricks Style Manager; JSON export committed |
| Irregular heading ladders → ACSS per-level Font Size Overrides | `--h1`–`--h6` defined individually in the Style Manager |
| Bare `--variable` in a Bricks field expands to `var(--variable)` | **Always write `var(--variable)`.** Bare `--variable` fails silently |
| `@include clickable-parent` / `@include focus-parent` | The pattern in the BEM class via `%root%`, or the mpd-core utility class |
| ACSS `.container` handles max-width and centering | Bricks Container element; width from Theme Style = `var(--content-width)` |
| Theme Style variables resolve from ACSS | Same Theme Style values; variables resolve from the Style Manager |
| Child-theme CSS as the third styling surface | mpd-core plugin CSS for framework-level rules only; component styles stay in BEM classes |
| ACSS configuration via `save_settings()` | Not applicable. Token values entered in the Style Manager per `TOKENS.md` |
| ACSS token map required before a build | `TOKENS.md` + the Style Manager export |
| ACSS owns the WS Form layer (`option-forms`, `--f-*`) | **Open.** Until decided, `03` → "WS Form — skin it by overriding root `--wsf-form-*` vars" is the live fallback |

## Known open questions (alpha)

- **`style-manager.min.css` is probably the token output here.** Two `03` entries tell you to delete that file. On this stack it is expected to carry the tokens, and only a builder session writes it. Both entries carry an unverified warning; settle it at spec Stage 4.
- **Utilities that ACSS shipped and mpd-core may not:** `.hidden-accessible`, `-trans-N` color tokens, `--grid-N` templates, the `-rgb` partials. Each ships only if the Stage 2 usage audit shows real use, so check `TOKENS.md` before you assume one resolves. If it isn't there, Bricks' `.screen-reader-text` is the visually-hidden fallback.
