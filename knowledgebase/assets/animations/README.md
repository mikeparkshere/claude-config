# Animation Toolkit — hardened

> Lightweight, dependency-free scroll-reveal framework for the MPD stack (Bricks + ACSS + child theme). Chosen over Bricks' and Advanced Themer's built-in animation systems deliberately: ~8 KB, no library, and it gives the same polish without the weight.
>
> **This is the hardened build.** The 2026-06-28 review listed four fixes needed before site-wide use; they were never folded back into the file, so every project that copied it forward inherited the unhardened version. They are applied here. Copy from this folder, not from an older project.

---

## Files

| File | Role | Ship it? |
|---|---|---|
| `animations.css` | Gated hidden states, revealed states, stagger, modifiers, reduced-motion | ✅ |
| `animations.js` | IntersectionObserver engine, DOM re-scan, `window.mpdAnim.refresh()` | ✅ |

`accessibility.js` from older copies of this framework is **not** part of it and is not included here. It patches Bricks' *native* offcanvas (`brx-open`), submenu icon SVGs and a duplicated `aria-current`, against one project's BEM classes. On a build using BricksExtras' offcanvas — which wires its own ARIA — every selector in it is dead. If a project needs those fixes, merge them into that project's own `a11y.js`.

---

## API

**Reveal** (on the element): `anim-fade-up` · `anim-fade-in` · `anim-fade-down` · `anim-slide-left` · `anim-slide-right` · `anim-scale-in` · `anim-scale-up` · `anim-blur-in`

**Stagger** (on the parent; direct children with reveal classes fire in sequence, up to 12): `anim-stagger` (80 ms) · `anim-stagger-tight` (50 ms) · `anim-stagger-wide` (120 ms)

**Modifiers:** `anim-fast` (.3s) · `anim-slow` (.8s) · `anim-delay-1`…`-5` (100–500 ms) · `anim-ease-smooth` · `anim-ease-spring` · `anim-ease-bounce`

**Body config:** `data-anim-threshold` (default `0.1`, **`0` is honoured**) · `data-anim-margin` (default `-40px`) · `data-anim-once` (`false` to re-trigger) · `data-anim-observe-dom` (`false` to disable the AJAX re-scan)

**Tokens** — retune motion from `:root` rather than editing rules:

```css
:root {
  --anim-duration: .6s;
  --anim-delay: 0ms;
  --anim-easing: cubic-bezier(.25,.46,.45,.94);
  --anim-travel: 28px;      /* fade-up / fade-down */
  --anim-travel-x: 40px;    /* slide-left / slide-right */
  --anim-stagger-step: 80ms;
}
```

---

## What was hardened, and why each one matters

**1. Fail-safe gate — the one that could hide a live site.** The base state is `opacity: 0`. Unhardened, that applied unconditionally, so if JS failed, was deferred badly, or was stripped by an optimiser, the content was **invisible to users and crawlers, permanently**. Every hidden rule is now scoped under `html.js-anim`, set by an inline `<head>` script before first paint. No JS → no class → nothing hidden.

**2. AJAX re-scan.** Elements were observed once at `DOMContentLoaded`. Anything injected afterwards — query-loop pagination, filters, load-more — was never observed and stayed at `opacity: 0` for good. Now re-scans on a debounced `MutationObserver` with a cheap early-out, plus `window.mpdAnim.refresh()` for DOM swaps that don't mutate detectably. **Check this on any site with a paginated archive.**

**3. `data-anim-threshold="0"` now works.** It was parsed as `parseFloat(x) || default`, and `0` is falsy — so it silently became `0.1`. That's precisely the value the tall-wrapper fix needs (an element taller than ~10× the viewport can never cross a 0.1 threshold and so never reveals), which meant the documented workaround could not be expressed through the documented config.

**4. Stagger children are no longer double-observed.** They were registered with the element observer *and* handled by the stagger observer, so a child entering the viewport before its container revealed itself and broke the sequence.

**5. `will-change` released on reveal.** It was permanent on every reveal element; `.anim-visible { will-change: auto }` drops the compositor hint once the element has arrived.

**6. Tokenised, and `!important` removed.** Modifiers set tokens instead of fighting the base rule, so all six `!important` declarations are gone. The delay modifiers use the doubled-class trick (`.anim-delay-1.anim-delay-1`, `(0,2,0)`) so an explicit delay still beats a stagger container's computed one — same outcome, without poisoning the property for later overrides. Stagger collapsed from 36 near-identical rules to 15 by putting the step in a token the container sets.

---

## Integration

**Home: the child theme.** Presentation lives there per stack convention — not the core plugin, not WPCodeBox.

**The builder guard is two-tier, and the tiers differ on purpose:**

```php
add_action( 'wp_enqueue_scripts', function() {
    $dir = get_stylesheet_directory();
    $uri = get_stylesheet_directory_uri();

    // General child-theme CSS: skip only the builder PANEL. Brand tokens and
    // signature devices need to render in the canvas so the build looks like
    // the site.
    if ( ! bricks_is_builder_main() ) {
        wp_enqueue_style( 'bricks-child', get_stylesheet_uri(), [ 'bricks-frontend' ], filemtime( $dir . '/style.css' ) );
    }

    // Animation layer: skip EVERY builder context. The hidden state is
    // opacity: 0, which blanks the canvas and makes the page uneditable.
    if ( ! bricks_is_builder_main() && ! bricks_is_builder() && ! isset( $_GET['bricks'] ) ) {
        wp_enqueue_style(  '<prefix>-animations', $uri . '/css/animations.css', [ 'bricks-frontend' ], filemtime( $dir . '/css/animations.css' ) );
        wp_enqueue_script( '<prefix>-animations', $uri . '/js/animations.js', [], filemtime( $dir . '/js/animations.js' ), true );
    }
} );

// The gate. Inline and in <head> — animations.js is deferred and would arrive
// after first paint, producing exactly the flash the gate prevents.
add_action( 'wp_head', function () {
    if ( bricks_is_builder_main() || bricks_is_builder() || isset( $_GET['bricks'] ) ) {
        return;
    }
    echo "<script>document.documentElement.classList.add('js-anim');</script>\n";
}, 1 );
```

⚠️ **Do not collapse the two guards.** `03` → *"Frontend Toolkit must skip the builder iframe"* is about the animation layer specifically. Tightening the general child-theme enqueue to `bricks_is_builder()` instead stops the brand tokens loading in the canvas.

**Register the vocabulary as name-only Bricks global classes** (21 of them) so they autocomplete for whoever builds pages. `settings` must be `array()` — a `stdClass` there crashes Bricks site-wide and recovery needs direct MySQL (`02`).

---

## Usage — restraint is the brand

Site-wide does **not** mean motion on everything. Subtle and consistent reads expensive; over-animation reads cheap.

| Context | Use | Notes |
|---|---|---|
| Section intros / headers | `anim-fade-up` | The workhorse — **one reveal per section** |
| Card grids | `anim-stagger` on the grid + `anim-fade-up` on cards | The premium move |
| Feature images | `anim-scale-in`, or `anim-blur-in` sparingly | Blur-in reads expensive; use rarely |
| Default easing | `anim-ease-smooth` | Elegant expo-out |

- **Avoid `anim-ease-bounce` / `anim-ease-spring`** on premium brands — playful, reads cheap.
- Keep the default travel; don't increase it.
- **Never** on: header, nav, sticky elements, footer utility rows, form fields, or anything above the fold. Reveal-on-scroll is a below-the-fold technique and animating the LCP element delays it.
- Don't cascade text line-by-line.

**Roll out template-by-template**, not all at once, so a vocabulary mistake surfaces on one page rather than across the site.

---

## Stack interactions

- **Remove-Unused-CSS (Perfmatters and friends).** The `.anim-*` rules are applied by JS, so RUCSS treats them as unused and strips them — leaving every animated element permanently hidden. **Exclude `animations.css`.** Put it on the go-live checklist the moment RUCSS is switched on.
- **Reduced motion** — handled in both CSS and JS; motion-sensitive users get instant, static content. Aligns with a WCAG 2.1 AA target.
- **CWV** — negligible, and off the LCP path provided nothing above the fold is animated.
- **Tall wrappers** — an element taller than ~10× the viewport can never cross the default 0.1 threshold. Animate the small pieces, not the container that grows with content. `data-anim-threshold="0"` is the escape hatch and now actually works.

---

*Hardened 2026-08-11 on the WCDP build, from the 2026-06-28 review of the WPCodeBox-migrated original. Verified against Bricks 2.3.10 / ACSS 3.3.6 / WordPress 7.0.*
