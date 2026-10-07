# mpd-core changelog

## 0.2.0 — 2026-10-07

First extraction into claude-config, from the site it was built on (pkjsupport, live 2026-10-07). The 0.1.0-alpha history is that project's gaps log; this entry is its distillation.

**Verified** on a fresh install from the non-ACSS Local Blueprint (Bricks 2.4.2, AT 3.5), following README steps 1–6 as written:
- **Installs and re-runs cleanly.** The install ran with no errors, and a full re-run duplicated nothing.
- **Emits as designed.** The system font stack is unquoted, the line-height tokens are in place, and the container width reaches nested containers.
- **The type scale holds.** h1 measures 32 / 38 / 44px at 360 / 863 / 1366: the ends of its range, and the exact midpoint at 863.
- **No page overflow** at any width.
- **Patterns pass with a real mouse and keyboard.** A card's corner click lands on its heading link. Tab rings the link on a plain card, puts a 2px shadow on a `focus-parent--shadow` card and a 2px outline on a `--outline` card, and suppresses the link's own ring on both.
- **The tools work.** `readback.php` flags only the deliberately classless defaults section. `drift-check.php` caught both planted edits (a class value and a builder text edit) without writing anything.

### Package

- **Values and structure are separate.** A site's values live in one file (`tokens/values.php`, from `values.example.php`); the token and Theme Style scripts read it, so they ship unchanged across sites. 0.1.0 had every value inline in the scripts.
- **Fluid values compile from pixel ranges:** `[ 'fluid', 30, 36 ]` becomes `clamp(1.875rem, calc(0.5964vw + 1.7408rem), 2.25rem)` over the values file's range, the formula the usage audit reproduced from ACSS's own output.
- **Neutral, light-first placeholder values**, all text roles at 4.5:1 or better on every surface and the input border at 3:1 or better. No brand values ship.
- **Fonts without a custom font are fully typed.** Bricks emits generic families (`system-ui`, `ui-monospace`) unquoted, so a system stack goes through the Theme Style's typed font control with its `fallback`, and no plugin CSS is needed.
- **Tools that write output now require the output path** (`export.php` → `OUT`, `readback.php` → `OUTDIR`, `audit.sh` / `report.py` → `OUTDIR`). Their old defaults were their own folders, which in claude-config is a public repo.
- `setup/seed-pattern-classes.php`, `tools/drift-check.php` and a neutral kitchen sink are new (below).

### Added

- **Pattern anchors are pickable.** `setup/seed-pattern-classes.php` registers `clickable-parent`, `focus-parent--shadow` and `focus-parent--outline` as name-only global classes (empty settings, so they emit no CSS). As CSS alone they never appeared in the class picker, and builds typed them as raw `_cssClasses` strings.
- **`dd` reset** for builder-made description lists, scoped like the list reset (`:where(dd[class*="brxe-"])`), so rich-text `<dl>`s keep the UA indent.
- **`--color-border-input`**: the edge of a form control, which needs 3:1 against both the field and the page. `--color-border` / `--color-border-strong` are decorative and don't reach it. The WS Form bridge now uses it instead of borrowing `--color-text-muted`.
- **`--line-height-body` / `--line-height-heading`**, consumed by the Theme Style, so a component that has to restate a line height (Bricks' nav-menu ships `line-height: 60px` on its sub-menu) references the token instead of copying the number. Per-level heading line heights stay optional in the values file.
- **`--grid-5`, `--grid-6`.**
- **Kitchen sink** (`kitchen-sink/build.php`): one private page proving every token and pattern on an install, built from the values file.
- **Drift check** (`tools/drift-check.php`): dry-runs a page-build script with every database write intercepted and lists the builder edits it would revert.

### Carried from 0.1.0 (built during the first site, now framework defaults)

- **Builder-list reset**: `:where(ul[class*="brxe-"], ol[class*="brxe-"])` drops markers and indent; rich-text lists keep theirs. Give such lists `role="list"` (Safari drops list semantics under `list-style: none`).
- **Sticky footer**: on a short page the footer sits at the viewport bottom. Front-end only (`body.bricks-is-frontend:has(> #brx-content)`), with the admin bar subtracted when logged in.
- **WS Form bridge**: WS Form keeps its own styler, re-pointed at purpose tokens through its root variables.
- **`form` contextual-spacing target**, so a shortcode-embedded form in post content gets flow spacing.

### Changed

- The status roles stay generic (`success`, `warning`, `danger`, `info`, `neutral`). The template no longer maps them to a document lifecycle; "Review due" was the first site's and nobody used it.

### Considered, not shipped

Each needs a design decision a placeholder can't make. Add it on the first site that needs it, then fold it back here.

- **Secondary (outline) button.** The Theme Style defines `primary` only.
- **Weighted grids** (`--grid-3-2`, a `2fr repeat(3, 1fr)` row). The first site wrote one inline in a footer and lived with `--grid-2` for a page-header split.
- **`.eyebrow`** as a framework class. Every ACSS site had one, but no utilities ship, and it needs `aria-hidden="true"` per `01`, which a class can't add.
- **A second color context** (light sections on a dark site, or the reverse). The purpose tier is designed for it; the mechanism isn't.
