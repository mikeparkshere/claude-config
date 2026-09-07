<?php
/**
 * Export two SEOPress artifacts. Fleet tool — see seopress-fleet-brief.md.
 *
 *   1. a full site backup (restorable onto the site it came from)
 *   2. a scrubbed fleet baseline (importable onto any MPD site)
 *
 * Run:
 *   SEOPRESS_OUT_DIR=~/backups/<site>/seopress \
 *   SEOPRESS_SEED_DIR=~/claude-config/seopress \
 *   wp eval-file export-baseline.php
 *
 * The scrub does NOT rely on ExportSettings alone — see the notes below for
 * the site-specific values its own categories miss.
 */

$out_dir  = getenv( 'SEOPRESS_OUT_DIR' );
$seed_dir = getenv( 'SEOPRESS_SEED_DIR' );

$export = seopress_get_service( 'ExportSettings' );

/* --- 1. full site backup ------------------------------------------- */
$full = $export->handle();
file_put_contents( $out_dir . '/seopress-full-export.json', wp_json_encode( $full, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );
echo "full export  : " . count( array_filter( $full ) ) . " populated options\n";

/* --- 2. fleet baseline --------------------------------------------- */
$seed = $export->handle(
	array( 'knowledge_graph', 'social_profiles', 'analytics_ids', 'verification_codes', 'license', 'api_keys' )
);

// ExportSettings has no notion of site-specific POST TYPES, so the seed would
// otherwise carry Oakham's `trailers` / `trailer_type` / `trailer_year` keys.
// Harmless on another site (SEOPress only reads entries for registered types)
// but noise in a baseline. Keep only the WordPress built-ins.
$keep_pt  = array( 'post', 'page' );
$keep_tax = array( 'category', 'post_tag', 'post_format' );

foreach ( array( 'seopress_titles_single_titles', 'seopress_titles_archive_titles' ) as $k ) {
	if ( ! empty( $seed['seopress_titles_option_name'][ $k ] ) ) {
		$seed['seopress_titles_option_name'][ $k ] = array_intersect_key(
			$seed['seopress_titles_option_name'][ $k ],
			array_flip( $keep_pt )
		);
	}
}
if ( ! empty( $seed['seopress_titles_option_name']['seopress_titles_tax_titles'] ) ) {
	$seed['seopress_titles_option_name']['seopress_titles_tax_titles'] = array_intersect_key(
		$seed['seopress_titles_option_name']['seopress_titles_tax_titles'],
		array_flip( $keep_tax )
	);
}
$seed['seopress_xml_sitemap_option_name']['seopress_xml_sitemap_post_types_list'] = array(
	'page' => array( 'include' => '1' ),
);
$seed['seopress_xml_sitemap_option_name']['seopress_xml_sitemap_taxonomies_list'] = array();

// Instant Indexing key is per-site; blank it rather than shipping Oakham's.
unset( $seed['seopress_instant_indexing_option_name'] );

// ExportSettings' `knowledge_graph` category only scrubs seopress_social_*
// keys, so the site's alternate name — which lives in the TITLES option and
// feeds schema `alternateName` — survives the scrub. Strip it explicitly.
unset( $seed['seopress_titles_option_name']['seopress_titles_home_site_title_alt'] );

// Drop options that are false / never created on a free install.
$seed = array_filter(
	$seed,
	function ( $v ) {
		return false !== $v && null !== $v;
	}
);

file_put_contents( $seed_dir . '/seopress-fleet-baseline.json', wp_json_encode( $seed, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES ) );
echo "fleet baseline: " . implode( ', ', array_keys( $seed ) ) . "\n";

/* --- verify the scrub actually happened ---------------------------- */
$leak = array();
foreach ( array( 'knowledge', 'accounts_facebook', 'accounts_twitter' ) as $needle ) {
	foreach ( (array) ( $seed['seopress_social_option_name'] ?? array() ) as $key => $val ) {
		if ( false !== strpos( $key, $needle ) ) {
			$leak[] = $key;
		}
	}
}
echo 'site-specific leak check: ' . ( empty( $leak ) ? "clean\n" : implode( ', ', $leak ) . "\n" );
echo 'seed social keys remaining: ' . implode( ', ', array_keys( (array) ( $seed['seopress_social_option_name'] ?? array() ) ) ) . "\n";
