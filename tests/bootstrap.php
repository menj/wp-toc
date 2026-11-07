<?php
/**
 * Test bootstrap.
 *
 * Loads the real WordPress hook, formatting, kses, shortcode and HTML API code
 * downloaded by bin/install-wp-core.sh, and stubs only the few
 * environment functions (options, translation, permalinks, styles) those
 * files or the plugin call. No database is needed.
 *
 * @package WP_TOC
 */

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

define( 'ABSPATH', dirname( __DIR__ ) . '/.cache/wordpress/' );
define( 'WPINC', 'wp-includes' );
// plugin_basename() resolves paths against these.
define( 'WP_PLUGIN_DIR', dirname( __DIR__, 2 ) );
define( 'WPMU_PLUGIN_DIR', dirname( __DIR__, 2 ) . '/mu-plugins' );

if ( ! is_dir( ABSPATH . WPINC ) ) {
	fwrite( STDERR, "WordPress core not found. Run bin/install-wp-core.sh (composer test does this).\n" );
	exit( 1 );
}

require_once __DIR__ . '/stubs.php';

$wp_toc_core_files = array(
	'compat.php',
	'plugin.php',
	'compat-utf8.php',
	'utf8.php',
	'formatting.php',
	'kses.php',
	'shortcodes.php',
	'class-wp-token-map.php',
	'html-api/html5-named-character-references.php',
	'html-api/class-wp-html-attribute-token.php',
	'html-api/class-wp-html-span.php',
	'html-api/class-wp-html-text-replacement.php',
	'html-api/class-wp-html-decoder.php',
	'html-api/class-wp-html-tag-processor.php',
);
// PHPUnit includes this file inside a function, so file-level variables in
// core files would otherwise be local instead of global.
global $shortcode_tags, $html5_named_character_references, $wp_plugin_paths;
$wp_plugin_paths = array(); // Set by wp-settings.php; read by plugin_basename().

foreach ( $wp_toc_core_files as $wp_toc_file ) {
	if ( file_exists( ABSPATH . WPINC . '/' . $wp_toc_file ) ) {
		require_once ABSPATH . WPINC . '/' . $wp_toc_file;
	}
}

// The parts of default-filters.php that matter for the_content.
add_filter( 'sanitize_title', 'sanitize_title_with_dashes', 10, 3 );
add_filter( 'the_content', 'wptexturize' );
add_filter( 'the_content', 'wpautop' );
add_filter( 'the_content', 'shortcode_unautop' );
add_filter( 'the_content', 'do_shortcode', 11 ); // AFTER wpautop().

require_once dirname( __DIR__ ) . '/wp-toc.php';

WP_TOC::register_shortcodes();

require_once __DIR__ . '/class-wp-toc-testcase.php';
