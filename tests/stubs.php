<?php
/**
 * Stubs for WordPress functions that live outside the core files the tests load.
 *
 * Behaviour can be steered through $GLOBALS['wp_toc_test'].
 *
 * @package WP_TOC
 */

// phpcs:disable

$GLOBALS['wp_toc_test'] = array();

function wp_toc_test_reset() {
	$GLOBALS['wp_toc_test'] = array(
		'post_id'    => 0,
		'singular'   => false,
		'post'       => null,
		'is_admin'   => false,
		'queried_id' => 0,
		'registered' => array(),
		'enqueued'   => array(),
	);
}
wp_toc_test_reset();

function get_option( $name, $default_value = false ) {
	$options = array(
		'blog_charset' => 'UTF-8',
		'home'         => 'https://example.com',
		'siteurl'      => 'https://example.com',
	);
	return array_key_exists( $name, $options ) ? $options[ $name ] : $default_value;
}
function is_multisite() { return false; }
function wp_load_alloptions( $force_cache = false ) { return array( 'blog_charset' => 'UTF-8' ); }
function get_locale() { return 'en_US'; }
function determine_locale() { return 'en_US'; }
function __( $text, $domain = 'default' ) { return $text; }
function _x( $text, $context, $domain = 'default' ) { return $text; }
function esc_html__( $text, $domain = 'default' ) { return esc_html( $text ); }
function esc_attr__( $text, $domain = 'default' ) { return esc_attr( $text ); }
function _doing_it_wrong( $function_name, $message, $version ) {}
function _deprecated_function( $function_name, $version, $replacement = '' ) {}
function _deprecated_argument( $function_name, $version, $message = '' ) {}
function mbstring_binary_safe_encoding( $reset = false ) {}
function reset_mbstring_encoding() {}
function wp_allowed_protocols() {
	return array( 'http', 'https', 'ftp', 'ftps', 'mailto', 'news', 'irc', 'irc6', 'ircs', 'gopher', 'nntp', 'feed', 'telnet', 'mms', 'rtsp', 'sms', 'svn', 'tel', 'fax', 'xmpp', 'webcal', 'urn' );
}
function wp_json_encode( $value, $flags = 0, $depth = 512 ) { return json_encode( $value, $flags, $depth ); }
function is_admin() { return ! empty( $GLOBALS['wp_toc_test']['is_admin'] ); }
function get_queried_object_id() { return $GLOBALS['wp_toc_test']['queried_id']; }
function wp_normalize_path( $path ) { return str_replace( '\\', '/', (string) $path ); }
function __return_false() { return false; }
function __return_true() { return true; }
function is_utf8_charset( $blog_charset = null ) { return true; }
function _canonical_charset( $charset ) { return 'UTF-8'; }

function get_the_ID() { return $GLOBALS['wp_toc_test']['post_id']; }
function get_permalink( $post = 0 ) { return $post ? 'https://example.com/post-' . (int) $post . '/' : false; }
function is_singular( $post_types = '' ) { return $GLOBALS['wp_toc_test']['singular']; }
function get_post( $post = null ) { return $GLOBALS['wp_toc_test']['post']; }

function plugins_url( $path = '', $plugin = '' ) { return 'https://example.com/wp-content/plugins/wp-toc/' . ltrim( $path, '/' ); }
function load_plugin_textdomain( $domain, $deprecated = false, $plugin_rel_path = false ) { return true; }

function wp_register_style( $handle, $src, $deps = array(), $ver = false, $media = 'all' ) {
	$GLOBALS['wp_toc_test']['registered'][ $handle ] = $src;
	return true;
}
function wp_enqueue_style( $handle, $src = '', $deps = array(), $ver = false, $media = 'all' ) {
	$GLOBALS['wp_toc_test']['enqueued'][ $handle ] = true;
}
function wp_style_is( $handle, $status = 'enqueued' ) {
	return 'registered' === $status
		? isset( $GLOBALS['wp_toc_test']['registered'][ $handle ] )
		: isset( $GLOBALS['wp_toc_test']['enqueued'][ $handle ] );
}
