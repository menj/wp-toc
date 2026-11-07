<?php
/**
 * HTML processor that can report where a bookmarked token starts and ends.
 *
 * @package WP_TOC
 */

declare(strict_types=1);

defined( 'ABSPATH' ) || exit;

/**
 * WP_HTML_Tag_Processor exposes no byte offsets, but its protected
 * $bookmarks hold a WP_HTML_Span (start, length) for each bookmark. WP TOC
 * uses that to insert the automatic table of contents at an exact position
 * instead of guessing with regular expressions.
 */
final class WP_TOC_HTML_Processor extends WP_HTML_Tag_Processor {

	/**
	 * Byte offsets of a bookmarked token in the original HTML.
	 *
	 * @param string $name Bookmark name.
	 * @return array{start: int, end: int}|null
	 */
	public function get_bookmark_offsets( string $name ): ?array {
		$span = $this->bookmarks[ $name ] ?? null;
		if ( ! $span instanceof WP_HTML_Span ) {
			return null;
		}

		return array(
			'start' => (int) $span->start,
			'end'   => (int) $span->start + (int) $span->length,
		);
	}
}
