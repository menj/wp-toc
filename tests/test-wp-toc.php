<?php
/**
 * Behaviour tests for WP TOC.
 *
 * @package WP_TOC
 */

/**
 * Tests the shortcode, heading processing, nesting, output and schema.
 */
class Test_WP_TOC extends WP_TOC_TestCase {

	// ---------------------------------------------------------------------
	// P0: filter order.
	// ---------------------------------------------------------------------

	public function test_toc_renders_through_real_the_content_pipeline() {
		$out = $this->the_content( "[wp_toc]\n\n<h2>Alpha</h2>\n\nText\n\n<h3>Beta</h3>\n\n<h2>Gamma</h2>" );

		$this->assertStringContainsString( '<h2 id="alpha">Alpha</h2>', $out );
		$this->assertStringContainsString( '<h3 id="beta">Beta</h3>', $out );
		$this->assertSame( array( 'Alpha' => array( 'Beta' => array() ), 'Gamma' => array() ), $this->toc_structure( $out ) );
		$this->assertStringNotContainsString( '<!--wp-toc:', $out );
		$this->assertStringNotContainsString( '[wp_toc]', $out );
	}

	public function test_legacy_auto_toc_shortcode_still_works() {
		$out = $this->the_content( "[auto_toc]\n\n<h2>Alpha</h2>\n\n<h2>Beta</h2>" );
		$this->assertSame( array( 'Alpha' => array(), 'Beta' => array() ), $this->toc_structure( $out ) );
	}

	public function test_placeholder_paragraph_wrapper_is_removed() {
		$out = WP_TOC::filter_content( '<p>' . do_shortcode( '[wp_toc]' ) . '</p><h2>A</h2>' );
		$this->assertStringStartsWith( '<nav ', $out );
	}

	public function test_init_hooks_content_filter_after_do_shortcode() {
		WP_TOC::init();
		$priority = has_filter( 'the_content', array( 'WP_TOC', 'filter_content' ) );
		$this->assertGreaterThan( has_filter( 'the_content', 'do_shortcode' ), $priority );
	}

	// ---------------------------------------------------------------------
	// P0: nesting.
	// ---------------------------------------------------------------------

	public function test_no_stray_closing_li_before_first_item() {
		$out = $this->render( '[wp_toc]<h2>A</h2><h2>B</h2>' );
		$this->assertStringContainsString( '<ul class="wp-toc-list auto-toc-list"><li itemprop="hasPart" itemscope itemtype="https://schema.org/WebPageElement" itemid="https://example.com/post-42/#a"><a href="#a" itemprop="url"><span itemprop="name">A</span></a></li><li itemprop="hasPart" itemscope itemtype="https://schema.org/WebPageElement" itemid="https://example.com/post-42/#b">', $out );
	}

	public function test_ending_on_a_deeper_level_closes_everything() {
		$out = $this->render( '[wp_toc tags="h2,h3,h4"]<h2>A</h2><h3>B</h3><h4>C</h4>' );
		$this->assertSame( array( 'A' => array( 'B' => array( 'C' => array() ) ) ), $this->toc_structure( $out ) );
	}

	public function test_skipped_level_nests_without_breaking_markup() {
		$out = $this->render( '[wp_toc tags="h2,h3,h4"]<h2>A</h2><h4>B</h4><h2>C</h2>' );
		$this->assertSame( array( 'A' => array( 'B' => array() ), 'C' => array() ), $this->toc_structure( $out ) );
	}

	public function test_first_heading_deeper_than_later_ones() {
		$out = $this->render( '[wp_toc]<h3>A</h3><h2>B</h2><h3>C</h3>' );
		$this->assertSame( array( 'A' => array(), 'B' => array( 'C' => array() ) ), $this->toc_structure( $out ) );
	}

	public function test_complex_outline() {
		$out = $this->render( '[wp_toc tags="h1,h2,h3,h4,h5,h6"]<h4>Pre</h4><h1>One</h1><h3>a</h3><h2>b</h2><h6>c</h6><h5>d</h5><h1>Two</h1>' );
		$this->assertSame(
			array(
				'Pre' => array(),
				'One' => array(
					'a' => array(),
					'b' => array(
						'c' => array(),
						'd' => array(),
					),
				),
				'Two' => array(),
			),
			$this->toc_structure( $out )
		);
	}

	// ---------------------------------------------------------------------
	// P1: content preservation, ids.
	// ---------------------------------------------------------------------

	public function test_content_is_otherwise_left_untouched() {
		$body = 'Intro text <b>bold</b> Caf&eacute; café &amp; "quotes"'
			. '<section data-x=\'1\'><h2 class="t">Title</h2><img src="a.png" alt="x"/></section>'
			. '<svg viewBox="0 0 1 1"><path d="M0 0"/></svg>'
			. '<script>var s = "<h2>not a heading</h2>";</script>'
			. '<custom-el foo>raw</custom-el>';

		$out = $this->render( '[wp_toc]' . $body );
		$out = preg_replace( '#^<nav.*?</nav>(<script type="application/ld\+json".*?</script>)?#s', '', $out );

		$this->assertSame( str_replace( '<h2 class="t">', '<h2 id="title" class="t">', $body ), $out );
	}

	public function test_existing_heading_ids_are_kept() {
		$out = $this->render( '[wp_toc]<h2 id="keep-me">Title</h2><h2>Other</h2>' );
		$this->assertStringContainsString( '<h2 id="keep-me">Title</h2>', $out );
		$this->assertStringContainsString( 'href="#keep-me"', $out );
		$this->assertStringContainsString( '<h2 id="other">Other</h2>', $out );
	}

	public function test_duplicate_headings_get_unique_ids() {
		$out = $this->render( '[wp_toc]<h2>Same</h2><h2>Same</h2><h2>Same</h2><h2>Same 2</h2>' );
		$this->assertStringContainsString( '<h2 id="same">', $out );
		$this->assertStringContainsString( '<h2 id="same-2">Same</h2>', $out );
		$this->assertStringContainsString( '<h2 id="same-3">Same</h2>', $out );
		// "Same 2" slugs to "same-2", which is taken.
		$this->assertStringContainsString( '<h2 id="same-2-2">Same 2</h2>', $out );
	}

	public function test_new_ids_do_not_clash_with_other_ids_in_content() {
		$out = $this->render( '[wp_toc]<div id="intro">x</div><h2>Intro</h2><p id="later">y</p><h2>Later</h2>' );
		$this->assertStringContainsString( '<h2 id="intro-2">Intro</h2>', $out );
		$this->assertStringContainsString( '<h2 id="later-2">Later</h2>', $out );
	}

	public function test_existing_id_later_in_content_is_reserved() {
		$out = $this->render( '[wp_toc]<h2>Intro</h2><h2 id="intro">Second</h2>' );
		$this->assertStringContainsString( '<h2 id="intro-2">Intro</h2>', $out );
		$this->assertStringContainsString( '<h2 id="intro">Second</h2>', $out );
	}

	public function test_heading_text_whitespace_and_inline_markup() {
		$out = $this->render( "[wp_toc]<h2>\n  Hello   <em>big</em>\n\t<a href='#'>world</a>  </h2>" );
		$this->assertStringContainsString( '<a href="#hello-big-world" itemprop="url"><span itemprop="name">Hello big world</span></a>', $out );
	}

	public function test_utf8_and_non_latin_headings() {
		$out = $this->render( '[wp_toc]<h2>Café &amp; Crème</h2><h2>مرحبا بالعالم</h2>' );
		$this->assertStringContainsString( '<h2 id="cafe-creme">', $out );
		$this->assertStringContainsString( '>Café &amp; Crème</span></a>', $out );
		$this->assertStringContainsString( '>مرحبا بالعالم</span></a>', $out );
		$this->assertStringContainsString( '<h2 id="مرحبا-بالعالم">مرحبا بالعالم</h2>', $out, 'Non-Latin ids are readable, not percent-encoded' );
		$this->assertStringContainsString( '<a href="#مرحبا-بالعالم" itemprop="url">', $out );
	}

	public function test_headings_inside_template_are_ignored() {
		$out = $this->render( '[wp_toc]<h2>Shown</h2><template><h2>Hidden</h2><template><h3>Nested</h3></template><h2 id="t">T</h2></template><h2>After</h2>' );
		$this->assertSame( array( 'Shown' => array(), 'After' => array() ), $this->toc_structure( $out ) );
		$this->assertStringContainsString( '<template><h2>Hidden</h2><template><h3>Nested</h3></template><h2 id="t">T</h2></template><h2 id="after">After</h2>', $out );
	}

	public function test_heading_text_is_escaped_in_toc() {
		$out = $this->render( '[wp_toc]<h2>&lt;img src=x onerror=alert(1)&gt;</h2>' );
		$this->assertStringContainsString( '>&lt;img src=x onerror=alert(1)&gt;</span></a>', $out );
		$this->assertStringNotContainsString( '<img src=x', $out );
	}

	public function test_empty_heading_is_skipped() {
		$out = $this->render( '[wp_toc]<h2> </h2><h2>Real</h2><h2>Other</h2>' );
		$this->assertStringContainsString( '<h2> </h2>', $out );
		$this->assertSame( array( 'Real' => array(), 'Other' => array() ), $this->toc_structure( $out ) );
	}

	public function test_unclosed_heading_does_not_break_processing() {
		$out = $this->render( '[wp_toc]<h2>One<h2>Two</h2>' );
		$this->assertSame( array( 'One' => array(), 'Two' => array() ), $this->toc_structure( $out ) );
	}

	public function test_libxml_error_setting_is_untouched() {
		$before = libxml_use_internal_errors( false );
		$this->render( '[wp_toc]<h2>A</h2><h2>B</h2>' );
		$this->assertFalse( libxml_use_internal_errors( $before ) );
	}

	// ---------------------------------------------------------------------
	// P1: attribute validation.
	// ---------------------------------------------------------------------

	public function test_tags_attribute_is_validated() {
		$atts = WP_TOC::sanitize_atts( array( 'tags' => ' H3, h2 ,p, script, h9, h2' ) );
		$this->assertSame( array( 2, 3 ), $atts['levels'] );

		$atts = WP_TOC::sanitize_atts( array( 'tags' => 'div,span' ) );
		$this->assertSame( array( 2, 3 ), $atts['levels'] );
	}

	public function test_title_is_stripped_and_escaped() {
		$out = $this->render( '[wp_toc title="<b>Read</b> &quot;this&quot;"]<h2>A</h2>' );
		$this->assertStringContainsString( '>Read &quot;this&quot;</span>', $out );
	}

	// ---------------------------------------------------------------------
	// P2: rename and back-compat.
	// ---------------------------------------------------------------------

	public function test_both_new_and_legacy_css_classes_are_present() {
		$out = $this->render( '[wp_toc]<h2>A</h2><h3>B</h3>' );
		$this->assertStringContainsString( 'class="wp-toc auto-toc-container"', $out );
		$this->assertStringContainsString( 'class="wp-toc-title auto-toc-title"', $out );
		$this->assertStringContainsString( 'class="wp-toc-list auto-toc-list"', $out );
	}

	// ---------------------------------------------------------------------
	// P3: features.
	// ---------------------------------------------------------------------

	public function test_no_toc_class_and_data_attribute_exclude_headings() {
		$out = $this->render( '[wp_toc]<h2 class="foo no-toc">Hidden</h2><h2 data-no-toc>Also hidden</h2><h2>Shown</h2>' );
		$this->assertSame( array( 'Shown' => array() ), $this->toc_structure( $out ) );
		$this->assertStringContainsString( '<h2 class="foo no-toc">Hidden</h2>', $out );
	}

	public function test_depth_limits_nesting() {
		$out = $this->render( '[wp_toc tags="h2,h3,h4" depth="2"]<h2>A</h2><h3>B</h3><h4>C</h4><h3>D</h3><h2>E</h2>' );
		$this->assertSame( array( 'A' => array( 'B' => array(), 'D' => array() ), 'E' => array() ), $this->toc_structure( $out ) );

		$out = $this->render( '[wp_toc tags="h2,h3,h4" depth="1"]<h2>A</h2><h3>B</h3><h2>E</h2>' );
		$this->assertSame( array( 'A' => array(), 'E' => array() ), $this->toc_structure( $out ) );
	}

	public function test_min_hides_short_tocs() {
		$out = $this->render( '[wp_toc min="3"]<h2>A</h2><h2>B</h2>' );
		$this->assertStringNotContainsString( '<nav', $out );
		$this->assertStringContainsString( '<h2 id="a">A</h2>', $out, 'Headings still get anchors' );

		$out = $this->render( '[wp_toc min="3"]<h2>A</h2><h2>B</h2><h2>C</h2>' );
		$this->assertStringContainsString( '<nav', $out );
	}

	public function test_no_matching_headings_renders_nothing() {
		$out = $this->render( 'x[wp_toc]y<p>no headings</p>' );
		$this->assertSame( 'xy<p>no headings</p>', $out );
	}

	public function test_collapsible() {
		$out = $this->render( '[wp_toc collapsible="true"]<h2>A</h2>' );
		$this->assertStringContainsString( '<details class="wp-toc-details" open><summary class="wp-toc-summary"><span class="wp-toc-title auto-toc-title" id="wp-toc-title-1" itemprop="name">Table of Contents</span></summary>', $out );
		$this->assertStringContainsString( 'wp-toc--collapsible', $out );
		$this->assertSame( array( 'A' => array() ), $this->toc_structure( $out ) );

		$out = $this->render( '[wp_toc collapsible="yes" collapsed="1"]<h2>A</h2>' );
		$this->assertStringContainsString( '<details class="wp-toc-details"><summary', $out );
	}

	public function test_filters() {
		add_filter(
			'wp_toc_items',
			static function ( $items ) {
				$items[] = array(
					'level' => 2,
					'id'    => 'extra',
					'text'  => 'Extra',
				);
				return $items;
			}
		);
		add_filter(
			'wp_toc_heading_id',
			static function ( $slug ) {
				return 'sec-' . $slug;
			}
		);
		add_filter(
			'wp_toc_html',
			static function ( $html ) {
				return '<div class="wrap">' . $html . '</div>';
			}
		);
		add_filter(
			'wp_toc_default_atts',
			static function ( $atts ) {
				$atts['title'] = 'Contents';
				return $atts;
			}
		);

		$out = $this->render( '[wp_toc]<h2>A</h2>' );
		$this->assertStringContainsString( '<h2 id="sec-a">A</h2>', $out );
		$this->assertStringStartsWith( '<div class="wrap"><nav', $out );
		$this->assertStringContainsString( '>Contents</span>', $out );
		$this->assertSame( array( 'A' => array(), 'Extra' => array() ), $this->toc_structure( $out ) );
	}

	public function test_disabled_leaves_content_alone() {
		add_filter( 'wp_toc_enabled', '__return_false' );
		$out = $this->render( 'a[wp_toc]b<h2>A</h2>' );
		$this->assertSame( 'ab<h2>A</h2>', $out );
	}

	public function test_content_without_placeholder_is_returned_as_is() {
		$html = '<h2>A</h2><p>x</p>';
		$this->assertSame( $html, WP_TOC::filter_content( $html ) );
	}

	public function test_malformed_placeholder_falls_back_to_defaults() {
		$out = WP_TOC::filter_content( '<!--wp-toc:zz--><!--wp-toc:abc--><h2>A</h2>' );
		$this->assertSame( '<!--wp-toc:zz-->', substr( $out, 0, 16 ), 'Non-hex payload is not treated as a placeholder' );
		$this->assertStringContainsString( '<nav', $out );
	}

	// ---------------------------------------------------------------------
	// P4: accessibility and assets.
	// ---------------------------------------------------------------------

	public function test_nav_landmark_is_labelled_by_title() {
		$out = $this->render( '[wp_toc]<h2>A</h2>' );
		$this->assertStringContainsString( '<nav class="wp-toc auto-toc-container" aria-labelledby="wp-toc-title-1" itemscope itemtype="https://schema.org/SiteNavigationElement" itemid="https://example.com/post-42/#wp-toc-1">', $out );
		$this->assertStringContainsString( 'id="wp-toc-title-1"', $out );
	}

	public function test_nav_without_title_uses_aria_label() {
		$out = $this->render( '[wp_toc title=""]<h2>A</h2>' );
		$this->assertStringContainsString( '<nav class="wp-toc auto-toc-container" aria-label="Table of Contents" itemscope itemtype="https://schema.org/SiteNavigationElement" itemid="https://example.com/post-42/#wp-toc-1">', $out );
		$this->assertStringNotContainsString( 'wp-toc-title', $out );
		$this->assertStringContainsString( '<meta itemprop="name" content="Table of Contents">', $out, 'Name for schema.org without a visible title' );
	}

	public function test_multiple_tocs_get_unique_title_ids_and_one_schema_block() {
		$out = $this->render( '[wp_toc][wp_toc title="Again"]<h2 id="wp-toc-title-2">A</h2>' );
		$this->assertStringContainsString( 'id="wp-toc-title-1"', $out );
		$this->assertStringContainsString( 'id="wp-toc-title-2-2"', $out, 'Title id avoids an id already in the content' );
		$this->assertCount( 1, $this->json_ld( $out ) );
	}

	public function test_style_is_enqueued_by_shortcode() {
		$this->render( '[wp_toc]<h2>A</h2>' );
		$this->assertTrue( wp_style_is( 'wp-toc' ) );
		$this->assertStringEndsWith( 'assets/css/wp-toc.css', $GLOBALS['wp_toc_test']['registered']['wp-toc'] );
	}

	public function test_style_can_be_disabled() {
		add_filter( 'wp_toc_load_css', '__return_false' );
		$this->render( '[wp_toc]<h2>A</h2>' );
		$this->assertFalse( wp_style_is( 'wp-toc' ) );
	}

	public function test_style_only_enqueued_early_on_posts_using_shortcode() {
		$GLOBALS['wp_toc_test']['singular']        = true;
		$GLOBALS['wp_toc_test']['post_types'][ 9 ] = 'attachment'; // No automatic TOC for this type.
		$GLOBALS['wp_toc_test']['post']            = (object) array(
			'ID'           => 9,
			'post_content' => '<h2>A</h2>',
		);
		WP_TOC::register_assets();
		$this->assertTrue( wp_style_is( 'wp-toc', 'registered' ) );
		$this->assertFalse( wp_style_is( 'wp-toc' ) );

		$GLOBALS['wp_toc_test']['post'] = (object) array(
			'ID'           => 9,
			'post_content' => '[auto_toc]<h2>A</h2>',
		);
		WP_TOC::register_assets();
		$this->assertTrue( wp_style_is( 'wp-toc' ) );
	}

	// ---------------------------------------------------------------------
	// P3: schema.org.
	// ---------------------------------------------------------------------

	public function test_schema_json_ld_is_nested_and_absolute() {
		$out    = $this->render( '[wp_toc]<h2>A</h2><h3>B</h3><h2>C</h2>', 7 );
		$blocks = $this->json_ld( $out );

		$this->assertCount( 1, $blocks );
		$this->assertSame(
			array(
				'@context' => 'https://schema.org',
				'@type'    => 'WebPage',
				'@id'      => 'https://example.com/post-7/#webpage',
				'url'      => 'https://example.com/post-7/',
				'hasPart'  => array(
					array(
						'@type'   => 'WebPageElement',
						'@id'     => 'https://example.com/post-7/#a',
						'name'    => 'A',
						'url'     => 'https://example.com/post-7/#a',
						'hasPart' => array(
							array(
								'@type' => 'WebPageElement',
								'@id'   => 'https://example.com/post-7/#b',
								'name'  => 'B',
								'url'   => 'https://example.com/post-7/#b',
							),
						),
					),
					array(
						'@type' => 'WebPageElement',
						'@id'   => 'https://example.com/post-7/#c',
						'name'  => 'C',
						'url'   => 'https://example.com/post-7/#c',
					),
				),
			),
			$blocks[0]
		);
	}

	public function test_schema_escapes_script_breakout() {
		$out = $this->render( '[wp_toc]<h2>&lt;/script&gt;&lt;script&gt;alert(1)&lt;/script&gt; &amp; ü</h2>' );
		preg_match( '#<script type="application/ld\+json" class="wp-toc-schema">(.*?)</script>#s', $out, $m );
		$this->assertStringNotContainsString( '<', $m[1] );
		$this->assertStringContainsString( '\\u003C/script\\u003E', $m[1] );
		$this->assertStringContainsString( 'ü', $m[1], 'Unicode stays readable' );
		$this->assertSame( '</script><script>alert(1)</script> & ü', $this->json_ld( $out )[0]['hasPart'][0]['name'] );
	}

	public function test_schema_urls_percent_encode_ids() {
		$out   = $this->render( '[wp_toc]<h2>مرحبا</h2><h2 id="50%off">Sale</h2>' );
		$parts = $this->json_ld( $out )[0]['hasPart'];
		$this->assertSame( 'https://example.com/post-42/#' . rawurlencode( 'مرحبا' ), $parts[0]['url'] );
		$this->assertSame( 'https://example.com/post-42/#50%25off', $parts[1]['url'], 'A literal % is encoded so the fragment decodes back to the id' );
	}

	public function test_schema_is_always_output() {
		// The 1.1.0 development "schema" attribute was removed: schema.org output has no opt-out.
		$out = $this->render( '[wp_toc schema="false"]<h2>A</h2>' );
		$this->assertCount( 1, $this->json_ld( $out ) );
		$this->assertStringContainsString( 'itemtype="https://schema.org/SiteNavigationElement"', $out );
	}

	public function test_microdata_covers_every_element_and_matches_json_ld() {
		$out = $this->render( '[wp_toc tags="h2,h3,h4"]<h2>A</h2><h3>B</h3><h4>C</h4><h2>D</h2>', 7 );
		preg_match( '#<nav.*?</nav>#s', $out, $m );
		$nav = $m[0];

		$this->assertStringContainsString( '<nav class="wp-toc auto-toc-container" aria-labelledby="wp-toc-title-1" itemscope itemtype="https://schema.org/SiteNavigationElement" itemid="https://example.com/post-7/#wp-toc-1">', $nav );
		$this->assertStringContainsString( '<link itemprop="isPartOf" href="https://example.com/post-7/#webpage">', $nav );
		$this->assertStringContainsString( 'id="wp-toc-title-1" itemprop="name">Table of Contents</span>', $nav );

		// Every entry: a WebPageElement with url and name, nested through hasPart.
		$this->assertSame( 4, preg_match_all( '#<li itemprop="hasPart" itemscope itemtype="https://schema.org/WebPageElement" itemid="([^"]+)"><a href="([^"]+)" itemprop="url"><span itemprop="name">[^<]+</span></a>#', $nav, $items ) );
		$this->assertSame( preg_match_all( '#<li[\s>]#', $nav ), 4, 'No entry without microdata' );
		$this->assertSame( preg_match_all( '#<a[\s>]#', $nav ), 4, 'No link without microdata' );

		// Microdata itemids are the JSON-LD @ids of the same sections.
		$json_ids = array();
		array_walk_recursive(
			$this->json_ld( $out )[0]['hasPart'],
			static function ( $value, $key ) use ( &$json_ids ) {
				if ( '@id' === $key ) {
					$json_ids[] = $value;
				}
			}
		);
		$this->assertSame( $json_ids, $items[1] );
		$this->assertSame( array( '#a', '#b', '#c', '#d' ), $items[2] );
	}

	public function test_microdata_without_a_post_has_no_ids() {
		$out = $this->render( '[wp_toc]<h2>A</h2>', 0 );
		$this->assertStringContainsString( 'itemscope itemtype="https://schema.org/SiteNavigationElement">', $out );
		$this->assertStringNotContainsString( 'itemid=', $out );
		$this->assertStringNotContainsString( 'isPartOf', $out );
	}

	public function test_schema_filter_can_change_json_ld() {
		add_filter(
			'wp_toc_schema',
			static function ( array $data ): array {
				$data['name'] = 'Changed';
				return $data;
			}
		);
		$this->assertSame( 'Changed', $this->json_ld( $this->render( '[wp_toc]<h2>A</h2>' ) )[0]['name'] );
	}

	public function test_schema_needs_a_post() {
		$this->assertCount( 0, $this->json_ld( $this->render( '[wp_toc]<h2>A</h2>', 0 ) ) );
	}

	public function test_schema_webpage_id_filter() {
		add_filter(
			'wp_toc_schema_webpage_id',
			static function ( $id, $permalink ) {
				return $permalink . '#custom';
			},
			10,
			2
		);
		$this->assertSame( 'https://example.com/post-42/#custom', $this->json_ld( $this->render( '[wp_toc]<h2>A</h2>' ) )[0]['@id'] );
	}

	public function test_filtered_items_with_missing_id_or_text_are_skipped() {
		add_filter(
			'wp_toc_items',
			static function ( $items ) {
				$items[] = array(
					'level' => 2,
					'id'    => '',
					'text'  => 'No id',
				);
				$items[] = array(
					'level' => 2,
					'id'    => 'no-text',
					'text'  => '  ',
				);
				$items[] = 'not an array';
				$items[] = array( 'level' => 2 );
				return $items;
			}
		);
		$out = $this->render( '[wp_toc]<h2>A</h2>' );
		$this->assertSame( array( 'A' => array() ), $this->toc_structure( $out ) );
		$this->assertStringNotContainsString( 'href="#"', $out );
	}

	public function test_style_not_enqueued_from_admin_requests() {
		$GLOBALS['wp_toc_test']['is_admin'] = true;
		$this->render( '[wp_toc]<h2>A</h2>' );
		$this->assertFalse( wp_style_is( 'wp-toc' ) );
	}

	// ---------------------------------------------------------------------
	// Automatic insertion (no shortcode needed).
	// ---------------------------------------------------------------------

	/**
	 * Puts the test in the main loop of a singular view of post 42.
	 */
	private function view_post( int $post_id = 42 ): void {
		$GLOBALS['wp_toc_test']['singular']   = true;
		$GLOBALS['wp_toc_test']['in_loop']    = true;
		$GLOBALS['wp_toc_test']['main_query'] = true;
		$GLOBALS['wp_toc_test']['queried_id'] = $post_id;
	}

	private const LONG_POST = "<p>Intro</p>\n\n<h2>One</h2>\n\n<p>a</p>\n\n<h3>Two</h3>\n\n<h2>Three</h2>";

	public function test_auto_inserts_before_first_heading_without_a_shortcode() {
		$this->view_post();
		$out = $this->the_content( self::LONG_POST );

		$this->assertSame( array( 'One' => array( 'Two' => array() ), 'Three' => array() ), $this->toc_structure( $out ) );
		$this->assertMatchesRegularExpression( '#<p>Intro</p>\s*<nav class="wp-toc[^>]*>.*</nav><script type="application/ld\+json".*?</script><h2 id="one">One</h2>#s', $out );
		$this->assertCount( 1, $this->json_ld( $out ) );
		$this->assertTrue( wp_style_is( 'wp-toc' ), 'CSS is loaded for the automatic TOC' );
	}

	public function test_auto_insert_needs_three_headings_by_default() {
		$this->view_post();
		$out = $this->the_content( "<h2>One</h2>\n\n<h2>Two</h2>" );
		$this->assertStringNotContainsString( '<nav', $out );
		$this->assertStringContainsString( '<h2 id="one">One</h2>', $out, 'Headings still get anchors' );
	}

	public function test_manual_shortcode_replaces_the_automatic_toc() {
		$this->view_post();
		$out = $this->the_content( self::LONG_POST . "\n\n[wp_toc title=\"Here\"]" );
		$this->assertSame( 1, substr_count( $out, '<nav' ) );
		$this->assertStringContainsString( '>Here</span>', $out );
		$this->assertMatchesRegularExpression( '#<h2 id="three">Three</h2>\s*<nav#', $out, 'TOC sits where the shortcode is' );
	}

	public function test_auto_insert_positions() {
		$this->view_post();

		add_filter( 'wp_toc_auto_insert', static fn( array $s ): array => array( 'position' => 'top' ) + $s );
		$this->assertStringStartsWith( '<nav', $this->the_content( self::LONG_POST ) );

		remove_all_filters( 'wp_toc_auto_insert' );
		add_filter( 'wp_toc_auto_insert', static fn( array $s ): array => array( 'position' => 'after_first_paragraph' ) + $s );
		$this->assertStringStartsWith( '<p>Intro</p><nav', $this->the_content( self::LONG_POST ) );
	}

	public function test_auto_insert_attributes_and_post_types_are_configurable() {
		$this->view_post();
		add_filter(
			'wp_toc_auto_insert',
			static fn( array $s ): array => array(
				'post_types' => array( 'page' ),
				'atts'       => array(
					'min'   => '1',
					'title' => 'Contents',
				),
			) + $s
		);

		$this->assertStringNotContainsString( '<nav', $this->the_content( self::LONG_POST ), 'Posts are no longer included' );

		$GLOBALS['wp_toc_test']['post_types'][42] = 'page';
		$out = $this->the_content( "<h2>Only</h2>" );
		$this->assertStringContainsString( '>Contents</span>', $out );
	}

	public function test_auto_insert_can_be_turned_off() {
		$this->view_post();
		add_filter( 'wp_toc_auto_insert', static fn( array $s ): array => array( 'enabled' => false ) + $s );
		$out = $this->the_content( self::LONG_POST );
		$this->assertStringNotContainsString( '<nav', $out );
		$this->assertStringNotContainsString( 'id="one"', $out, 'Content is left alone' );
	}

	/**
	 * @dataProvider provide_contexts_without_auto_insert
	 */
	public function test_no_auto_insert_outside_the_main_post( string $key, $value ) {
		$this->view_post();
		if ( 'password' === $key ) {
			$GLOBALS['wp_toc_test']['password'][42] = true;
		} else {
			$GLOBALS['wp_toc_test'][ $key ] = $value;
		}

		$this->assertStringNotContainsString( '<nav', $this->the_content( self::LONG_POST ) );
	}

	public static function provide_contexts_without_auto_insert(): array {
		return array(
			'archive'            => array( 'singular', false ),
			'outside the loop'   => array( 'in_loop', false ),
			'secondary query'    => array( 'main_query', false ),
			'feed'               => array( 'is_feed', true ),
			'admin'              => array( 'is_admin', true ),
			'another post'       => array( 'queried_id', 7 ),
			'password protected' => array( 'password', true ),
		);
	}

	// ---------------------------------------------------------------------
	// Theme or plugin.
	// ---------------------------------------------------------------------

	public function test_context_detection() {
		$this->assertSame( 'plugin', WP_TOC::context_for( WP_PLUGIN_DIR . '/wp-toc/wp-toc.php' ) );
		$this->assertSame( 'plugin', WP_TOC::context_for( WPMU_PLUGIN_DIR . '/wp-toc/wp-toc.php' ) );
		$this->assertSame( 'theme', WP_TOC::context_for( '/srv/site/wp-content/themes/child/inc/wp-toc/wp-toc.php' ) );
		$this->assertSame( 'plugin', WP_TOC::context(), 'The test checkout sits in the plugins directory' );
	}

	public function test_base_url_in_theme_and_other_locations() {
		$this->assertSame( 'https://example.com/wp-content/themes/child/inc/wp-toc/', WP_TOC::base_url_for( '/srv/site/wp-content/themes/child/inc/wp-toc/wp-toc.php' ) );
		$this->assertSame( 'https://example.com/wp-content/themes/parent/wp-toc/', WP_TOC::base_url_for( '/srv/site/wp-content/themes/parent/wp-toc/wp-toc.php' ) );
		$this->assertSame( 'https://example.com/wp-content/lib/wp-toc/', WP_TOC::base_url_for( '/srv/site/wp-content/lib/wp-toc/wp-toc.php' ) );
		$this->assertSame( '', WP_TOC::base_url_for( '/opt/elsewhere/wp-toc/wp-toc.php' ) );
		$this->assertSame( 'https://example.com/wp-content/plugins/wp-toc/', WP_TOC::base_url_for( WP_PLUGIN_DIR . '/wp-toc/wp-toc.php' ) );
	}

	public function test_css_is_inlined_when_folder_has_no_url() {
		add_filter( 'wp_toc_base_url', '__return_empty_string' );
		$this->render( '[wp_toc]<h2>A</h2>' );

		$this->assertFalse( $GLOBALS['wp_toc_test']['registered']['wp-toc'] );
		$this->assertStringContainsString( '.wp-toc {', $GLOBALS['wp_toc_test']['inline_css']['wp-toc'] );
	}

	/**
	 * A theme can load WP TOC after init has run.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_loading_after_init_registers_shortcodes_immediately() {
		remove_all_actions( 'init' );
		remove_shortcode( 'wp_toc' );
		remove_shortcode( 'auto_toc' );
		do_action( 'init' );
		$this->assertFalse( shortcode_exists( 'wp_toc' ) );

		WP_TOC::init();
		$this->assertTrue( shortcode_exists( 'wp_toc' ) );
		$this->assertTrue( shortcode_exists( 'auto_toc' ) );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_wp_toc_url_constant_overrides_base_url() {
		define( 'WP_TOC_URL', 'https://cdn.example.com/wp-toc' );
		$this->assertSame( 'https://cdn.example.com/wp-toc/', WP_TOC::base_url_for( WP_TOC_FILE ) );
	}

	// ---------------------------------------------------------------------
	// Rank Math compatibility.
	// ---------------------------------------------------------------------

	public function test_registers_as_toc_plugin_for_rank_math_analysis() {
		$plugins = apply_filters( 'rank_math/researches/toc_plugins', array( 'wp-shortcode/wp-shortcode.php' => 'WP Shortcode by RankMath' ) );
		$this->assertSame( 'WP TOC', $plugins[ plugin_basename( WP_TOC_FILE ) ] );
		$this->assertSame( 'wp-toc/wp-toc.php', plugin_basename( WP_TOC_FILE ), 'Key matches the entry in active_plugins' );
		$this->assertArrayHasKey( 'wp-shortcode/wp-shortcode.php', $plugins, 'Existing entries are kept' );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_schema_uses_rank_math_canonical_webpage_id() {
		define( 'RANK_MATH_VERSION', '1.0.268' );
		require_once __DIR__ . '/fixtures/rank-math-paper.php';
		\RankMath\Paper\Paper::$canonical = 'https://example.com/custom-canonical/';

		$GLOBALS['wp_toc_test']['singular']   = true;
		$GLOBALS['wp_toc_test']['queried_id'] = 42;

		$data = $this->json_ld( $this->render( '[wp_toc]<h2>A</h2>' ) )[0];
		$this->assertSame( 'https://example.com/custom-canonical/#webpage', $data['@id'] );
		$this->assertSame( 'https://example.com/post-42/#a', $data['hasPart'][0]['url'], 'Section URLs use the permalink, like Rank Math\'s TOC block' );

		// Another post's content on the same page (e.g. a related-posts block) keeps its own permalink.
		$data = $this->json_ld( $this->render( '[wp_toc]<h2>A</h2>', 7 ) )[0];
		$this->assertSame( 'https://example.com/post-7/#webpage', $data['@id'] );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_schema_falls_back_when_rank_math_internals_fail() {
		define( 'RANK_MATH_VERSION', '1.0.268' );
		require_once __DIR__ . '/fixtures/rank-math-paper.php';
		\RankMath\Paper\Paper::$throw = true;

		$GLOBALS['wp_toc_test']['singular']   = true;
		$GLOBALS['wp_toc_test']['queried_id'] = 42;

		$this->assertSame( 'https://example.com/post-42/#webpage', $this->json_ld( $this->render( '[wp_toc]<h2>A</h2>' ) )[0]['@id'] );
	}

	/**
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_schema_uses_yoast_webpage_id_when_yoast_is_active() {
		define( 'WPSEO_VERSION', '99.0' );
		$this->assertSame( 'https://example.com/post-42/', $this->json_ld( $this->render( '[wp_toc]<h2>A</h2>' ) )[0]['@id'] );
	}
}
