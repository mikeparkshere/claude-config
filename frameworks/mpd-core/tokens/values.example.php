<?php
/**
 * mpd-core token values — NEUTRAL PLACEHOLDERS. Copy to the site's own repo as `values.php` and replace.
 *
 * This is the only file that holds a site's values. The scripts beside it hold structure:
 *   apply-tokens.php       → Bricks Style Manager (palette + purpose colors + every other token)
 *   apply-theme-style.php  → Bricks Theme Style (fonts, weights, line heights, framework defaults)
 *   export.php             → style-manager-export.json, the committed record of what's live
 *
 * Record every value's reason in the site's TOKENS.md (template: ../TOKENS.template.md).
 *
 * Value forms:
 *   '1rem', 'var(--x)', 'calc(…)'  written as-is
 *   [ 'fluid', 30, 36 ]            min px → max px across meta.fluid_min..fluid_max, compiled to
 *                                  clamp(min, calc(A·vw + B·rem), max) at 16px root (the formula the audit
 *                                  reproduced from ACSS's own output). Equal min and max compiles to a static rem.
 */

return [
	'meta' => [
		'site'      => 'example',
		'fluid_min' => 360,    // px. Mike's standard; the audit found it on 5/5 sites
		'fluid_max' => 1366,   // px. Also --content-width
	],

	// Typed font controls. `custom` is the title of a registered Bricks custom font (bricks_fonts post);
	// leave it null to use `family` directly. A generic family (system-ui, ui-monospace, sans-serif …)
	// is emitted unquoted by Bricks, so the system stack below is fully typed: no plugin CSS needed.
	'fonts' => [
		'sans' => [ 'custom' => null, 'family' => 'system-ui', 'fallback' => '-apple-system, "Segoe UI", Roboto, sans-serif' ],
		'mono' => [ 'custom' => null, 'family' => 'ui-monospace', 'fallback' => '"SF Mono", Consolas, monospace' ],
	],

	// ---------------------------------------------------------------- palette tier (Variable Manager)
	// Raw values, never used by components. Name = hue + step, step = (1 − OKLCH L) × 1000 rounded to 10,
	// so a new color slots in by measurement. Light-first neutral placeholder; contrast notes are on #FFFFFF.
	'palette' => [
		'gray-0'    => '#FFFFFF', 'gray-20'  => '#F6F7F9', 'gray-50'  => '#EEF0F3', 'gray-80' => '#E2E6EB',
		'gray-150'  => '#C9CFD7',
		'gray-390'  => '#7B8594',   // 3.7:1: control edges (non-text 3:1)
		'gray-500'  => '#5B6574',   // 5.9:1
		'gray-600'  => '#3F4855',   // 9.3:1
		'gray-780'  => '#161A21',   // 17.4:1
		'blue-30'   => '#EEF4FF', 'blue-470' => '#2B63D9', 'blue-540' => '#1F4FB8', 'blue-600' => '#193F94',
		'green-40'  => '#E9F6EE', 'green-490' => '#18794A',
		'amber-30'  => '#FDF4E4', 'amber-470' => '#9A5B06',
		'red-40'    => '#FDEDED', 'red-470'   => '#C0302B',
		'cyan-40'   => '#E6F5F8', 'cyan-500'  => '#0B6F87',
	],

	// ---------------------------------------------------------------- purpose tier (Color Manager)
	// What components use. The set of names is the framework; the values are the site's.
	'purpose' => [
		'color-bg'             => 'var(--gray-0)',
		'color-surface'        => 'var(--gray-20)',
		'color-surface-raised' => 'var(--gray-0)',
		'color-surface-hover'  => 'var(--gray-50)',
		'color-border'         => 'var(--gray-80)',    // decorative hairlines
		'color-border-strong'  => 'var(--gray-150)',   // decorative dividers
		'color-border-input'   => 'var(--gray-390)',   // a control's edge: ≥ 3:1 against the field AND the page
		'color-text'           => 'var(--gray-780)',
		'color-text-secondary' => 'var(--gray-600)',
		'color-text-muted'     => 'var(--gray-500)',   // must still pass 4.5:1 on every surface it sits on
		'color-text-on-accent' => 'var(--gray-0)',     // 5.4:1 on the accent
		'color-accent'         => 'var(--blue-470)',
		'color-accent-hover'   => 'var(--blue-540)',
		'color-accent-active'  => 'var(--blue-600)',
		'color-accent-tint'    => 'var(--blue-30)',
		'color-link'           => 'var(--color-accent)',
		'color-link-hover'     => 'var(--color-accent-hover)',
		'color-focus'          => 'var(--color-accent)',
		'color-success'        => 'var(--green-490)', 'color-success-tint' => 'var(--green-40)',
		'color-warning'        => 'var(--amber-470)', 'color-warning-tint' => 'var(--amber-30)',
		'color-danger'         => 'var(--red-470)',   'color-danger-tint'  => 'var(--red-40)',
		'color-info'           => 'var(--cyan-500)',  'color-info-tint'    => 'var(--cyan-40)',
		'color-neutral'        => 'var(--gray-500)',  'color-neutral-tint' => 'var(--gray-50)',
	],

	// ---------------------------------------------------------------- everything else (Variable Manager)
	// Category => tokens. Spacing, gutter and layout are Mike's standard ACSS output (360–1366, 100% root),
	// carried over unchanged; they're framework defaults more than brand values.
	'variables' => [
		'Layout' => [
			'root-font-size'     => '100%',   // locked: 1rem = 16px
			'content-width'      => '85.375rem',
			'content-width-safe' => 'min(var(--content-width), calc(100% - var(--gutter) * 2))',
			'width-m'            => 'calc(var(--content-width) * 0.4)',
			'width-l'            => 'calc(var(--content-width) * 0.6)',
			'width-xl'           => 'calc(var(--content-width) * 0.8)',
			'reading-width'      => '40rem',
			'gutter'             => 'clamp(1.25rem, calc(3.9761vw + 0.3554rem), 3.75rem)',
		],
		'Spacing' => [
			'space-xs'          => '0.8333rem',   // ACSS's xs never scaled (min > max); pinned static
			'space-s'           => 'clamp(1.125rem, calc(0.1988vw + 1.0803rem), 1.25rem)',
			'space-m'           => 'clamp(1.5rem, calc(0.5964vw + 1.3658rem), 1.875rem)',
			'space-l'           => 'clamp(2rem, calc(1.2922vw + 1.7092rem), 2.8125rem)',
			'space-xl'          => 'clamp(2.6667rem, calc(2.4685vw + 2.1113rem), 4.2188rem)',
			'space-xxl'         => 'clamp(3.5529rem, calc(4.4138vw + 2.5598rem), 6.3281rem)',
			'section-space-xs'  => 'clamp(1.6883rem, calc(1.2910vw + 1.3978rem), 2.5rem)',
			'section-space-s'   => 'clamp(2.25rem, calc(2.3857vw + 1.7132rem), 3.75rem)',
			'section-space-m'   => 'clamp(3rem, calc(4.1750vw + 2.0606rem), 5.625rem)',
			'section-space-l'   => 'clamp(4rem, calc(7.0577vw + 2.4120rem), 8.4375rem)',
			'section-space-xl'  => 'clamp(5.3307rem, calc(11.6511vw + 2.7092rem), 12.6563rem)',
			'section-space-xxl' => 'clamp(7.1058rem, calc(18.8924vw + 2.8550rem), 18.9844rem)',
			'content-gap'       => 'var(--space-m)',
			'grid-gap'          => 'var(--space-m)',
		],
		'Grid' => [
			'grid-1'   => 'repeat(1, minmax(0, 1fr))',
			'grid-2'   => 'repeat(2, minmax(0, 1fr))',
			'grid-3'   => 'repeat(3, minmax(0, 1fr))',
			'grid-4'   => 'repeat(4, minmax(0, 1fr))',
			'grid-5'   => 'repeat(5, minmax(0, 1fr))',
			'grid-6'   => 'repeat(6, minmax(0, 1fr))',
			'grid-1-2' => 'minmax(0, 1fr) minmax(0, 2fr)',
			'grid-2-3' => 'minmax(0, 2fr) minmax(0, 3fr)',
		],
		// Each heading level strictly smaller than the one above at every width.
		'Typography' => [
			'display'  => [ 'fluid', 40, 56 ],
			'h1'       => [ 'fluid', 32, 44 ],
			'h2'       => [ 'fluid', 26, 34 ],
			'h3'       => [ 'fluid', 22, 26 ],
			'h4'       => [ 'fluid', 19, 21 ],
			'h5'       => [ 'fluid', 17, 18 ],
			'h6'       => '1rem',
			'text-xxl' => [ 'fluid', 22, 26 ],
			'text-xl'  => [ 'fluid', 19, 21 ],
			'text-l'   => [ 'fluid', 17, 18 ],
			'text-m'   => '1rem',
			'text-s'   => '0.875rem',
			'text-xs'  => '0.8125rem',
			'line-height-body'    => '1.6',
			'line-height-heading' => '1.2',
			'font-sans' => 'system-ui, -apple-system, "Segoe UI", Roboto, sans-serif',
			'font-mono' => 'ui-monospace, "SF Mono", Consolas, monospace',
		],
		'Radius' => [
			'radius-xs' => '0.125rem', 'radius-s' => '0.25rem', 'radius-m' => '0.375rem',
			'radius-l'  => '0.5rem',   'radius-xl' => '0.75rem', 'radius-xxl' => '1rem',
			'radius'        => 'var(--radius-m)',   // what components use by default
			'radius-pill'   => '9999px',
			'radius-circle' => '50%',
		],
		'Focus' => [
			'focus-width'  => '0.125rem',
			'focus-offset' => '0.125rem',
			'focus-color'  => 'var(--color-focus)',
		],
	],

	// ---------------------------------------------------------------- Theme Style values without tokens
	'theme_style' => [
		'body_weight'           => '400',
		'body_letter_spacing'   => null,   // e.g. '0.01em' for light text on dark
		'heading_weight'        => '600',
		'button_weight'         => '500',
		// Per-level heading line heights. null = inherit --line-height-heading from the headings group.
		'heading_line_heights'  => [ 'h1' => null, 'h2' => null, 'h3' => null, 'h4' => null, 'h5' => null, 'h6' => null ],
	],
];
