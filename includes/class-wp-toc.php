<?php
/**
 * Core functionality for WP TOC.
 *
 * @package WP_TOC
 */

defined( 'ABSPATH' ) || exit;

/**
 * Builds a table of contents from a post's headings.
 *
 * How it works:
 *
 * 1. The [wp_toc] shortcode (alias [auto_toc]) runs at the_content priority 11
 *    and only outputs a placeholder comment carrying its attributes.
 * 2. WP_TOC::filter_content() runs late on the_content (priority 99 by
 *    default), after every other shortcode and block has rendered. It reads
 *    the final headings, adds missing id attributes and swaps each
 *    placeholder for the rendered table of contents.
 *
 * Headings are read and changed with WP_HTML_Tag_Processor, so the rest of
 * the post's markup is left exactly as it was.
 */
final class WP_TOC {

	/**
	 * Main shortcode tag.
	 */
	const SHORTCODE = 'wp_toc';

	/**
	 * Legacy shortcode tag, kept so existing posts keep working.
	 */
	const LEGACY_SHORTCODE = 'auto_toc';

	/**
	 * Stylesheet handle.
	 */
	const STYLE_HANDLE = 'wp-toc';

	/**
	 * Matches a placeholder, plus a <p> wrapper around it when there is one.
	 * Group 2 holds the hex-encoded JSON attributes.
	 */
	const PLACEHOLDER_PATTERN = '#(<p>\s*)?<!--wp-toc:([0-9a-f]*)-->(?(1)\s*</p>)#';

	/**
	 * Placeholder prefix, used for a cheap check before running the regex.
	 */
	const PLACEHOLDER_PREFIX = '<!--wp-toc:';

	/**
	 * Number of tables of contents rendered on this request, for unique title ids.
	 *
	 * @var int
	 */
	private static $toc_count = 0;

	/**
	 * Registers hooks.
	 *
	 * @return void
	 */
	public static function init(): void {
		add_action( 'init', array( __CLASS__, 'load_textdomain' ) );
		add_action( 'init', array( __CLASS__, 'register_shortcodes' ) );
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_assets' ) );
		add_filter( 'rank_math/researches/toc_plugins', array( __CLASS__, 'register_with_rank_math' ) );

		/**
		 * Filters the priority of the the_content filter that builds the TOC.
		 *
		 * It must run after do_shortcode (priority 11).
		 *
		 * @param int $priority Default 99.
		 */
		$priority = (int) apply_filters( 'wp_toc_content_filter_priority', 99 );
		add_filter( 'the_content', array( __CLASS__, 'filter_content' ), max( 12, $priority ) );
	}

	/**
	 * Loads translations.
	 *
	 * @return void
	 */
	public static function load_textdomain(): void {
		load_plugin_textdomain( 'wp-toc', false, dirname( plugin_basename( WP_TOC_FILE ) ) . '/languages' );
	}

	/**
	 * Registers [wp_toc] and the legacy [auto_toc] alias.
	 *
	 * @return void
	 */
	public static function register_shortcodes(): void {
		add_shortcode( self::SHORTCODE, array( __CLASS__, 'shortcode' ) );
		add_shortcode( self::LEGACY_SHORTCODE, array( __CLASS__, 'shortcode' ) );
	}

	/**
	 * Tells Rank Math that a table of contents plugin is active, so its
	 * "Table of Contents" content-analysis check passes.
	 *
	 * @param mixed $plugins Plugin basename => name.
	 * @return array
	 */
	public static function register_with_rank_math( $plugins ) {
		$plugins = is_array( $plugins ) ? $plugins : array();

		$plugins[ plugin_basename( WP_TOC_FILE ) ] = 'WP TOC';
		return $plugins;
	}

	/**
	 * Registers the stylesheet and enqueues it on singular views that use the shortcode.
	 *
	 * @return void
	 */
	public static function register_assets(): void {
		self::register_style();

		if ( ! is_singular() ) {
			return;
		}

		$post = get_post();
		if ( $post && ( has_shortcode( $post->post_content, self::SHORTCODE ) || has_shortcode( $post->post_content, self::LEGACY_SHORTCODE ) ) ) {
			self::enqueue_style();
		}
	}

	/**
	 * Enqueues the stylesheet unless it has been switched off.
	 *
	 * Called again from the shortcode so the CSS also loads when the shortcode
	 * appears somewhere register_assets() could not see it. WordPress prints
	 * late-enqueued styles in the footer.
	 *
	 * @return void
	 */
	public static function enqueue_style(): void {
		/**
		 * Filters whether WP TOC loads its stylesheet.
		 *
		 * @param bool $load Default true.
		 */
		if ( ! apply_filters( 'wp_toc_load_css', true ) ) {
			return;
		}

		if ( ! wp_style_is( self::STYLE_HANDLE, 'registered' ) ) {
			self::register_style();
		}

		wp_enqueue_style( self::STYLE_HANDLE );
	}

	/**
	 * Registers the stylesheet.
	 *
	 * @return void
	 */
	private static function register_style(): void {
		wp_register_style(
			self::STYLE_HANDLE,
			plugins_url( 'assets/css/wp-toc.css', WP_TOC_FILE ),
			array(),
			WP_TOC_VERSION
		);
	}

	/**
	 * Default shortcode attributes.
	 *
	 * @return array
	 */
	public static function default_atts(): array {
		$defaults = array(
			'tags'        => 'h2,h3',
			'title'       => __( 'Table of Contents', 'wp-toc' ),
			'depth'       => '0',
			'min'         => '1',
			'collapsible' => 'false',
			'collapsed'   => 'false',
			'schema'      => 'true',
		);

		/**
		 * Filters the default shortcode attributes.
		 *
		 * @param array $defaults Default attributes.
		 */
		return array_merge( $defaults, (array) apply_filters( 'wp_toc_default_atts', $defaults ) );
	}

	/**
	 * Shortcode callback: outputs a placeholder that filter_content() replaces later.
	 *
	 * @param array|string $atts Shortcode attributes.
	 * @return string
	 */
	public static function shortcode( $atts ): string {
		$atts = shortcode_atts( self::default_atts(), $atts, self::SHORTCODE );

		// Admin requests also render content (e.g. Rank Math Pro scans it for videos on save); no page to style there.
		if ( ! is_admin() ) {
			self::enqueue_style();
		}

		// Hex keeps the payload free of "--" and ">", which would end the comment early.
		return self::PLACEHOLDER_PREFIX . bin2hex( (string) wp_json_encode( $atts ) ) . '-->';
	}

	/**
	 * Turns raw shortcode attributes into validated settings.
	 *
	 * @param array $atts Raw attributes (strings).
	 * @return array {
	 *     @type int[]  $levels      Heading levels to include, e.g. array( 2, 3 ).
	 *     @type string $title       Title text, may be empty.
	 *     @type int    $depth       Maximum nesting depth, 0 for no limit.
	 *     @type int    $min         Minimum number of entries before the TOC is shown.
	 *     @type bool   $collapsible Whether the TOC can be collapsed.
	 *     @type bool   $collapsed   Whether a collapsible TOC starts collapsed.
	 *     @type bool   $schema      Whether to output schema.org JSON-LD.
	 * }
	 */
	public static function sanitize_atts( $atts ): array {
		$defaults = self::default_atts();
		$atts     = array_merge( $defaults, (array) $atts );

		$levels = self::parse_levels( $atts['tags'] );
		if ( empty( $levels ) ) {
			$levels = self::parse_levels( $defaults['tags'] );
		}
		if ( empty( $levels ) ) {
			$levels = array( 2, 3 );
		}

		return array(
			'levels'      => $levels,
			'title'       => trim( wp_strip_all_tags( (string) $atts['title'] ) ),
			'depth'       => max( 0, (int) $atts['depth'] ),
			'min'         => max( 1, (int) $atts['min'] ),
			'collapsible' => self::to_bool( $atts['collapsible'] ),
			'collapsed'   => self::to_bool( $atts['collapsed'] ),
			'schema'      => self::to_bool( $atts['schema'] ),
		);
	}

	/**
	 * Parses a "h2,h3" list into sorted unique levels. Anything other than h1-h6 is ignored.
	 *
	 * @param string $tags Comma-separated tag names.
	 * @return int[]
	 */
	private static function parse_levels( $tags ): array {
		$levels = array();
		foreach ( explode( ',', strtolower( (string) $tags ) ) as $tag ) {
			$tag = trim( $tag );
			if ( preg_match( '/^h([1-6])$/', $tag, $m ) ) {
				$levels[ (int) $m[1] ] = (int) $m[1];
			}
		}
		ksort( $levels );
		return array_values( $levels );
	}

	/**
	 * Interprets a shortcode attribute as a boolean ("true", "1", "yes", "on").
	 *
	 * @param mixed $value Value.
	 * @return bool
	 */
	private static function to_bool( $value ): bool {
		return is_bool( $value ) ? $value : filter_var( $value, FILTER_VALIDATE_BOOLEAN );
	}

	/**
	 * Whether this WordPress has the HTML API features the plugin needs (6.5+).
	 *
	 * @return bool
	 */
	public static function html_api_available(): bool {
		return class_exists( 'WP_HTML_Tag_Processor' ) && method_exists( 'WP_HTML_Tag_Processor', 'next_token' );
	}

	/**
	 * The_content filter: adds heading ids and replaces placeholders with the TOC.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	public static function filter_content( $content ) {
		if ( ! is_string( $content ) || false === strpos( $content, self::PLACEHOLDER_PREFIX ) ) {
			return $content;
		}

		$post_id = (int) get_the_ID();

		/**
		 * Filters whether WP TOC builds a table of contents for this content.
		 *
		 * When false, the placeholders are removed and headings are left alone.
		 *
		 * @param bool $enabled Default true.
		 * @param int  $post_id Current post ID, 0 if none.
		 */
		if ( ! apply_filters( 'wp_toc_enabled', true, $post_id ) || ! self::html_api_available() ) {
			return (string) preg_replace( self::PLACEHOLDER_PATTERN, '', $content );
		}

		$result  = self::process_headings( $content );
		$content = $result['content'];

		/**
		 * Filters the headings found in the content.
		 *
		 * @param array $items   List of array( 'level' => int, 'id' => string, 'text' => string ).
		 * @param int   $post_id Current post ID, 0 if none.
		 */
		$items       = (array) apply_filters( 'wp_toc_items', $result['items'], $post_id );
		$used_ids    = $result['ids'];
		$schema_done = false;

		return (string) preg_replace_callback(
			self::PLACEHOLDER_PATTERN,
			static function ( $matches ) use ( $items, &$used_ids, &$schema_done, $post_id ) {
				$atts = self::sanitize_atts( self::decode_atts( $matches[2] ) );
				$tree = self::build_tree( $items, $atts );

				if ( self::count_nodes( $tree ) < $atts['min'] ) {
					return '';
				}

				$html = self::render_html( $tree, $atts, $used_ids, $post_id );

				if ( $atts['schema'] && ! $schema_done ) {
					$html       .= self::render_schema( $tree, $post_id );
					$schema_done = true;
				}

				return $html;
			},
			$content
		);
	}

	/**
	 * Decodes a placeholder's attributes.
	 *
	 * @param string $hex Hex-encoded JSON.
	 * @return array
	 */
	private static function decode_atts( string $hex ): array {
		$json = ( '' !== $hex && 0 === strlen( $hex ) % 2 ) ? hex2bin( $hex ) : false;
		$atts = false !== $json ? json_decode( $json, true ) : null;
		return is_array( $atts ) ? $atts : array();
	}

	/**
	 * Finds headings, adds missing ids and collects TOC entries.
	 *
	 * Existing heading ids are kept. New ids are unique across every id
	 * already in the content, not just other headings. Headings with the
	 * class "no-toc" or a data-no-toc attribute are skipped, and so are
	 * headings inside <template>, whose content is never displayed.
	 *
	 * @param string $html Content HTML.
	 * @return array {
	 *     @type string $content Updated HTML.
	 *     @type array  $items   Entries: array( 'level' => int, 'id' => string, 'text' => string ).
	 *     @type array  $ids     Every id in the updated content, as keys.
	 * }
	 */
	public static function process_headings( string $html ): array {
		$ids            = array();
		$headings       = array();
		$current        = null;
		$template_depth = 0;

		// Pass 1: read every heading's text and record every existing id.
		$processor = new WP_HTML_Tag_Processor( $html );
		while ( $processor->next_token() ) {
			$type = $processor->get_token_type();

			if ( '#text' === $type ) {
				if ( null !== $current ) {
					$current['text'] .= $processor->get_modifiable_text();
				}
				continue;
			}

			if ( '#tag' !== $type ) {
				continue;
			}

			$tag        = (string) $processor->get_tag();
			$is_heading = (bool) preg_match( '/^H([1-6])$/', $tag, $m );

			if ( 'TEMPLATE' === $tag ) {
				$template_depth = max( 0, $template_depth + ( $processor->is_tag_closer() ? -1 : 1 ) );
				continue;
			}

			if ( $processor->is_tag_closer() ) {
				if ( $is_heading && null !== $current ) {
					$headings[] = $current;
					$current    = null;
				}
				continue;
			}

			$id = $processor->get_attribute( 'id' );
			$id = is_string( $id ) ? trim( $id ) : '';
			if ( '' !== $id && 0 === $template_depth ) {
				$ids[ $id ] = true;
			}

			if ( $is_heading ) {
				if ( null !== $current ) {
					// The previous heading was never closed.
					$headings[] = $current;
				}
				$current = array(
					'level'   => (int) $m[1],
					'id'      => $id,
					'text'    => '',
					'exclude' => $template_depth > 0 || true === $processor->has_class( 'no-toc' ) || null !== $processor->get_attribute( 'data-no-toc' ),
					'new'     => false,
				);
			}
		}
		if ( null !== $current ) {
			$headings[] = $current;
		}

		// Work out the entries and any ids that need adding.
		$items  = array();
		$needed = false;
		foreach ( $headings as $i => $heading ) {
			$text = trim( (string) preg_replace( '/\s+/u', ' ', $heading['text'] ) );

			if ( $heading['exclude'] || '' === $text ) {
				continue;
			}

			if ( '' === $heading['id'] ) {
				/**
				 * Filters the id generated for a heading before it is made unique.
				 *
				 * @param string $slug  Slug from sanitize_title(), with non-ASCII
				 *                      characters decoded (e.g. "café-crème", "مرحبا").
				 * @param string $text  Heading text.
				 * @param int    $level Heading level, 1-6.
				 */
				$slug = (string) apply_filters( 'wp_toc_heading_id', self::slug( $text ), $text, $heading['level'] );

				$headings[ $i ]['id']  = self::unique_id( $slug, $ids );
				$headings[ $i ]['new'] = true;
				$needed                = true;
			}

			$items[] = array(
				'level' => $heading['level'],
				'id'    => $headings[ $i ]['id'],
				'text'  => $text,
			);
		}

		// Pass 2: write the new ids. Headings are matched by their order in the content.
		if ( $needed ) {
			$processor = new WP_HTML_Tag_Processor( $html );
			$index     = 0;
			while ( $processor->next_tag() ) {
				if ( ! preg_match( '/^H[1-6]$/', (string) $processor->get_tag() ) ) {
					continue;
				}
				if ( ! empty( $headings[ $index ]['new'] ) ) {
					$processor->set_attribute( 'id', $headings[ $index ]['id'] );
				}
				++$index;
			}
			$html = $processor->get_updated_html();
		}

		return array(
			'content' => $html,
			'items'   => $items,
			'ids'     => $ids,
		);
	}

	/**
	 * Builds an id from heading text.
	 *
	 * Sanitize_title() percent-encodes non-ASCII characters ("%d9%85"). A
	 * literal "%" in an id is ambiguous in URLs, so the slug is decoded back
	 * to readable Unicode, which HTML ids and URL fragments both support.
	 *
	 * @param string $text Heading text.
	 * @return string
	 */
	private static function slug( string $text ): string {
		$slug    = sanitize_title( $text );
		$decoded = rawurldecode( $slug );
		return ( $decoded !== $slug && preg_match( '//u', $decoded ) ) ? $decoded : $slug;
	}

	/**
	 * Makes an id unique against $used and records it there.
	 *
	 * @param string $id   Wanted id.
	 * @param array  $used Ids in use, as keys. Updated.
	 * @return string
	 */
	private static function unique_id( string $id, array &$used ): string {
		$id = trim( (string) preg_replace( '/\s+/', '-', $id ), '-' );
		if ( '' === $id ) {
			$id = 'section';
		}

		$candidate = $id;
		$suffix    = 2;
		while ( isset( $used[ $candidate ] ) ) {
			$candidate = $id . '-' . $suffix;
			++$suffix;
		}

		$used[ $candidate ] = true;
		return $candidate;
	}

	/**
	 * Builds the nested TOC from flat entries.
	 *
	 * Each entry becomes a child of the closest earlier entry with a lower
	 * level, so skipped levels (h2 then h4) and a deeper first heading are
	 * handled without broken markup.
	 *
	 * @param array $items Entries from process_headings().
	 * @param array $atts  Settings from sanitize_atts().
	 * @return array Nodes: array( 'level', 'id', 'text', 'children' => array( ...nodes ) ).
	 */
	public static function build_tree( array $items, array $atts ): array {
		$nodes = array();
		$roots = array();
		$stack = array();

		foreach ( $items as $item ) {
			// Entries can come from the wp_toc_items filter, so check their shape.
			if (
				! is_array( $item )
				|| ! isset( $item['level'], $item['id'], $item['text'] )
				|| '' === trim( (string) $item['id'] )
				|| '' === trim( (string) $item['text'] )
				|| ! in_array( (int) $item['level'], $atts['levels'], true )
			) {
				continue;
			}

			while ( $stack && $nodes[ end( $stack ) ]['level'] >= (int) $item['level'] ) {
				array_pop( $stack );
			}

			if ( $atts['depth'] > 0 && count( $stack ) >= $atts['depth'] ) {
				continue;
			}

			$index           = count( $nodes );
			$nodes[ $index ] = array(
				'level'    => (int) $item['level'],
				'id'       => (string) $item['id'],
				'text'     => (string) $item['text'],
				'children' => array(),
			);

			if ( $stack ) {
				$nodes[ end( $stack ) ]['children'][] = $index;
			} else {
				$roots[] = $index;
			}
			$stack[] = $index;
		}

		return self::nest( $nodes, $roots );
	}

	/**
	 * Turns index-linked nodes into nested arrays.
	 *
	 * @param array $nodes   Nodes keyed by index.
	 * @param int[] $indices Indices to resolve.
	 * @return array
	 */
	private static function nest( array $nodes, array $indices ): array {
		$out = array();
		foreach ( $indices as $index ) {
			$node             = $nodes[ $index ];
			$node['children'] = self::nest( $nodes, $node['children'] );
			$out[]            = $node;
		}
		return $out;
	}

	/**
	 * Counts nodes in a tree.
	 *
	 * @param array $tree Tree from build_tree().
	 * @return int
	 */
	public static function count_nodes( array $tree ): int {
		$count = 0;
		foreach ( $tree as $node ) {
			$count += 1 + self::count_nodes( $node['children'] );
		}
		return $count;
	}

	/**
	 * Renders the TOC.
	 *
	 * @param array $tree     Tree from build_tree().
	 * @param array $atts     Settings from sanitize_atts().
	 * @param array $used_ids Ids in use, as keys. Updated with the title id.
	 * @param int   $post_id  Current post ID.
	 * @return string
	 */
	public static function render_html( array $tree, array $atts, array &$used_ids, int $post_id = 0 ): string {
		++self::$toc_count;

		$classes = 'wp-toc auto-toc-container' . ( $atts['collapsible'] ? ' wp-toc--collapsible' : '' );
		$list    = self::render_list( $tree, true );

		if ( '' !== $atts['title'] ) {
			$title_id = self::unique_id( 'wp-toc-title-' . self::$toc_count, $used_ids );
			$label    = 'aria-labelledby="' . esc_attr( $title_id ) . '"';
			$title    = '<span class="wp-toc-title auto-toc-title" id="' . esc_attr( $title_id ) . '">' . esc_html( $atts['title'] ) . '</span>';
		} else {
			$label = 'aria-label="' . esc_attr__( 'Table of Contents', 'wp-toc' ) . '"';
			$title = '';
		}

		$html = '<nav class="' . esc_attr( $classes ) . '" ' . $label . '>';

		if ( $atts['collapsible'] ) {
			$summary = '' !== $title ? $title : '<span class="wp-toc-title auto-toc-title">' . esc_html__( 'Table of Contents', 'wp-toc' ) . '</span>';
			$html   .= '<details class="wp-toc-details"' . ( $atts['collapsed'] ? '' : ' open' ) . '>';
			$html   .= '<summary class="wp-toc-summary">' . $summary . '</summary>';
			$html   .= $list . '</details>';
		} else {
			$html .= $title . $list;
		}

		$html .= '</nav>';

		/**
		 * Filters the TOC HTML.
		 *
		 * @param string $html    TOC markup.
		 * @param array  $tree    Tree from build_tree().
		 * @param array  $atts    Settings from sanitize_atts().
		 * @param int    $post_id Current post ID.
		 */
		return (string) apply_filters( 'wp_toc_html', $html, $tree, $atts, $post_id );
	}

	/**
	 * Renders one level of the list.
	 *
	 * @param array $nodes   Nodes at this level.
	 * @param bool  $is_root Whether this is the top level.
	 * @return string
	 */
	private static function render_list( array $nodes, bool $is_root ): string {
		$html = '<ul class="' . ( $is_root ? 'wp-toc-list auto-toc-list' : 'wp-toc-sublist' ) . '">';
		foreach ( $nodes as $node ) {
			$html .= '<li><a href="#' . esc_attr( $node['id'] ) . '">' . esc_html( $node['text'] ) . '</a>';
			if ( $node['children'] ) {
				$html .= self::render_list( $node['children'], false );
			}
			$html .= '</li>';
		}
		return $html . '</ul>';
	}

	/**
	 * Renders schema.org JSON-LD describing the TOC's sections.
	 *
	 * Outputs a WebPage node whose hasPart lists each section as a
	 * WebPageElement, nested to match the TOC. When Yoast SEO or Rank Math is
	 * active, the node reuses the @id of the WebPage they output, so search
	 * engines merge it into their graph. Section URLs use the permalink, like
	 * Rank Math's own TOC block.
	 *
	 * @param array $tree    Tree from build_tree().
	 * @param int   $post_id Current post ID.
	 * @return string
	 */
	public static function render_schema( array $tree, int $post_id ): string {
		$permalink = $post_id ? get_permalink( $post_id ) : false;
		if ( ! $permalink || ! $tree ) {
			return '';
		}

		$data = array(
			'@context' => 'https://schema.org',
			'@type'    => 'WebPage',
			'@id'      => self::schema_webpage_id( $permalink, $post_id ),
			'url'      => $permalink,
			'hasPart'  => self::schema_parts( $tree, $permalink ),
		);

		/**
		 * Filters the TOC's JSON-LD. Return an empty value to output nothing.
		 *
		 * @param array $data    JSON-LD data.
		 * @param array $tree    Tree from build_tree().
		 * @param int   $post_id Current post ID.
		 */
		$data = apply_filters( 'wp_toc_schema', $data, $tree, $post_id );
		if ( empty( $data ) ) {
			return '';
		}

		// JSON_HEX_TAG stops "</script>" in a heading from ending the script block.
		$json = wp_json_encode( $data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP );
		if ( ! $json ) {
			return '';
		}

		return '<script type="application/ld+json" class="wp-toc-schema">' . $json . '</script>';
	}

	/**
	 * The @id to use for the WebPage node.
	 *
	 * @param string $permalink Post URL.
	 * @param int    $post_id   Post ID.
	 * @return string
	 */
	private static function schema_webpage_id( string $permalink, int $post_id ): string {
		$rank_math_canonical = self::rank_math_canonical( $post_id );

		if ( '' !== $rank_math_canonical ) {
			// Rank Math: canonical URL + "#webpage" (RankMath\Schema\Webpage).
			$id = $rank_math_canonical . '#webpage';
		} elseif ( defined( 'WPSEO_VERSION' ) ) {
			// Yoast SEO: the page URL (Meta_Tags_Context::generate_main_schema_id()).
			$id = $permalink;
		} else {
			$id = $permalink . '#webpage';
		}

		/**
		 * Filters the @id of the WebPage node the TOC's JSON-LD attaches to.
		 *
		 * @param string $id        WebPage @id.
		 * @param string $permalink Post URL.
		 * @param int    $post_id   Post ID.
		 */
		return (string) apply_filters( 'wp_toc_schema_webpage_id', $id, $permalink, $post_id );
	}

	/**
	 * Rank Math's canonical URL for the current page, when Rank Math is active
	 * and $post_id is the page being viewed. Empty string otherwise.
	 *
	 * The canonical can differ from the permalink (a custom canonical, for
	 * example), and Rank Math builds its WebPage @id from it.
	 *
	 * @param int $post_id Post ID.
	 * @return string
	 */
	private static function rank_math_canonical( int $post_id ): string {
		if (
			! defined( 'RANK_MATH_VERSION' )
			|| ! class_exists( '\RankMath\Paper\Paper' )
			|| ! is_callable( array( '\RankMath\Paper\Paper', 'get' ) )
			|| ! is_singular()
			|| (int) get_queried_object_id() !== $post_id
		) {
			return '';
		}

		try {
			$canonical = \RankMath\Paper\Paper::get()->get_canonical();
		} catch ( \Throwable $e ) {
			// Rank Math internals changed; fall back to the permalink.
			return '';
		}

		return is_string( $canonical ) ? $canonical : '';
	}

	/**
	 * Converts tree nodes to WebPageElement entries.
	 *
	 * @param array  $nodes     Nodes.
	 * @param string $permalink Post URL.
	 * @return array
	 */
	private static function schema_parts( array $nodes, string $permalink ): array {
		$parts = array();
		foreach ( $nodes as $node ) {
			$part = array(
				'@type' => 'WebPageElement',
				'name'  => $node['text'],
				'url'   => $permalink . '#' . rawurlencode( $node['id'] ),
			);
			if ( $node['children'] ) {
				$part['hasPart'] = self::schema_parts( $node['children'], $permalink );
			}
			$parts[] = $part;
		}
		return $parts;
	}

	/**
	 * Resets per-request state. For tests.
	 *
	 * @return void
	 */
	public static function reset(): void {
		self::$toc_count = 0;
	}
}
