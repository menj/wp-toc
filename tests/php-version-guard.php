<?php
/**
 * Checks that wp-toc.php refuses to load on PHP below 8.4.
 *
 * Run with an old PHP (CI uses 7.4 and 8.3): php tests/php-version-guard.php
 * Uses only PHP 7-compatible syntax, since that is the point.
 * Exits 0 when WP TOC stayed unloaded and registered its admin notice.
 *
 * @package WP_TOC
 */

// phpcs:disable

if ( PHP_VERSION_ID >= 80400 ) {
	fwrite( STDERR, "Run this check with PHP below 8.4.\n" );
	exit( 1 );
}

define( 'ABSPATH', __DIR__ . '/' );

$actions = array();
function add_action( $hook, $callback ) { $GLOBALS['actions'][ $hook ][] = $callback; }
function current_user_can( $capability ) { return true; }
function __( $text, $domain = 'default' ) { return $text; }
function esc_html( $text ) { return htmlspecialchars( (string) $text, ENT_QUOTES ); }

require dirname( __DIR__ ) . '/wp-toc.php';

$failures = array();
if ( defined( 'WP_TOC_VERSION' ) ) {
	$failures[] = 'WP_TOC_VERSION was defined.';
}
if ( class_exists( 'WP_TOC', false ) ) {
	$failures[] = 'The WP_TOC class was loaded.';
}
if ( array( 'wp_toc_php_version_notice' ) !== ( $actions['admin_notices'] ?? array() ) ) {
	$failures[] = 'The admin notice was not registered.';
}

ob_start();
wp_toc_php_version_notice();
$notice = (string) ob_get_clean();
if ( false === strpos( $notice, 'requires PHP 8.4' ) || false === strpos( $notice, PHP_VERSION ) ) {
	$failures[] = 'Unexpected notice: ' . $notice;
}

if ( $failures ) {
	fwrite( STDERR, implode( "\n", $failures ) . "\n" );
	exit( 1 );
}

echo 'OK: WP TOC did not load on PHP ' . PHP_VERSION . " and shows an admin notice.\n";
