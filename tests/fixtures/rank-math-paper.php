<?php
/**
 * Minimal stand-in for Rank Math's Paper class (seo-by-rank-math 1.0.268,
 * includes/frontend/paper/class-paper.php): Paper::get()->get_canonical().
 *
 * @package WP_TOC
 */

namespace RankMath\Paper;

// phpcs:disable

class Paper {
	public static $canonical = '';
	public static $throw     = false;

	public static function get() {
		if ( self::$throw ) {
			throw new \RuntimeException( 'Paper not set up' );
		}
		return new self();
	}

	public function get_canonical( $un_paged = false, $no_override = false ) {
		return self::$canonical;
	}
}
