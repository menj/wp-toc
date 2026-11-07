<?php
/**
 * Base test case.
 *
 * @package WP_TOC
 */

use PHPUnit\Framework\TestCase;

/**
 * Shared helpers.
 */
abstract class WP_TOC_TestCase extends TestCase {

	/**
	 * Resets state before each test.
	 */
	protected function setUp(): void {
		parent::setUp();
		wp_toc_test_reset();
		WP_TOC::reset();
	}

	/**
	 * Removes filters a test added.
	 */
	protected function tearDown(): void {
		foreach ( array( 'wp_toc_enabled', 'wp_toc_items', 'wp_toc_html', 'wp_toc_schema', 'wp_toc_schema_webpage_id', 'wp_toc_heading_id', 'wp_toc_load_css', 'wp_toc_default_atts' ) as $hook ) {
			remove_all_filters( $hook );
		}
		parent::tearDown();
	}

	/**
	 * Runs content through the same the_content pipeline as WordPress:
	 * wpautop and shortcode_unautop (10), do_shortcode (11), WP TOC (99).
	 *
	 * @param string $content Raw post content.
	 * @param int    $post_id Post ID to report from get_the_ID().
	 * @return string
	 */
	protected function the_content( $content, $post_id = 42 ) {
		$GLOBALS['wp_toc_test']['post_id'] = $post_id;

		$priority = has_filter( 'the_content', array( 'WP_TOC', 'filter_content' ) );
		if ( false === $priority ) {
			add_filter( 'the_content', array( 'WP_TOC', 'filter_content' ), 99 );
		}

		return apply_filters( 'the_content', $content );
	}

	/**
	 * Runs only the WP TOC filter on already-rendered HTML that contains a shortcode.
	 *
	 * @param string $html    HTML containing [wp_toc] shortcodes.
	 * @param int    $post_id Post ID.
	 * @return string
	 */
	protected function render( $html, $post_id = 42 ) {
		$GLOBALS['wp_toc_test']['post_id'] = $post_id;
		return WP_TOC::filter_content( do_shortcode( $html ) );
	}

	/**
	 * Extracts the TOC's structure as nested arrays: array( 'Text' => array( children ) ).
	 * Fails the test if the markup is not well-formed.
	 *
	 * @param string $html HTML containing a TOC.
	 * @param int    $nth  Which TOC (0-based).
	 * @return array
	 */
	protected function toc_structure( $html, $nth = 0 ) {
		preg_match_all( '#<nav class="wp-toc[^"]*"[^>]*>.*?</nav>#s', $html, $m );
		$this->assertArrayHasKey( $nth, $m[0], 'TOC not found in: ' . $html );
		// "<details open>" is valid HTML but not XML.
		$nav = str_replace( ' open>', ' open="open">', $m[0][ $nth ] );

		$this->assertSame( substr_count( $nav, '<ul' ), substr_count( $nav, '</ul>' ), 'Unbalanced <ul>' );
		$this->assertSame( substr_count( $nav, '<li>' ), substr_count( $nav, '</li>' ), 'Unbalanced <li>' );

		$doc = new DOMDocument();
		$this->assertTrue( $doc->loadXML( $nav ), 'TOC markup is not well-formed: ' . $nav );

		$xpath = new DOMXPath( $doc );
		$root  = $xpath->query( '//ul[contains(@class,"wp-toc-list")]' )->item( 0 );
		$this->assertNotNull( $root );

		return $this->walk_list( $root );
	}

	/**
	 * Walks a <ul>.
	 *
	 * @param DOMElement $ul List element.
	 * @return array
	 */
	private function walk_list( DOMElement $ul ) {
		$out = array();
		foreach ( $ul->childNodes as $li ) {
			if ( ! $li instanceof DOMElement ) {
				continue;
			}
			$this->assertSame( 'li', $li->nodeName, 'Only <li> may be a direct child of <ul>' );
			$text     = '';
			$children = array();
			foreach ( $li->childNodes as $child ) {
				if ( $child instanceof DOMElement && 'a' === $child->nodeName ) {
					$text = $child->textContent;
				} elseif ( $child instanceof DOMElement && 'ul' === $child->nodeName ) {
					$children = $this->walk_list( $child );
				}
			}
			$out[ $text ] = $children;
		}
		return $out;
	}

	/**
	 * Decodes the JSON-LD blocks in some HTML.
	 *
	 * @param string $html HTML.
	 * @return array List of decoded blocks.
	 */
	protected function json_ld( $html ) {
		preg_match_all( '#<script type="application/ld\+json" class="wp-toc-schema">(.*?)</script>#s', $html, $m );
		return array_map(
			static function ( $json ) {
				return json_decode( $json, true );
			},
			$m[1]
		);
	}
}
