<?php
/**
 * Stand-ins for PHP 8.4 functions, used only when the test suite runs on an
 * older PHP during local development. Never loaded by the plugin itself.
 *
 * @package WP_TOC
 */

// phpcs:disable

if ( ! function_exists( 'array_any' ) ) {
	function array_any( array $array, callable $callback ): bool {
		foreach ( $array as $key => $value ) {
			if ( $callback( $value, $key ) ) {
				return true;
			}
		}
		return false;
	}
}

if ( ! function_exists( 'array_find' ) ) {
	function array_find( array $array, callable $callback ): mixed {
		foreach ( $array as $key => $value ) {
			if ( $callback( $value, $key ) ) {
				return $value;
			}
		}
		return null;
	}
}
