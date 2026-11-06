=== WP TOC ===
Contributors: menj
Tags: table of contents, toc, headings, schema, shortcode
Requires at least: 6.5
Tested up to: 7.1
Requires PHP: 7.4
Stable tag: 1.1.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Lightweight, server-side table of contents. Adds anchors to headings and renders a nested, accessible list with schema.org markup.

== Description ==

Add `[wp_toc]` to a post or page. WP TOC links every heading, renders a nested table of contents and describes the page's sections with schema.org markup. There is no JavaScript and no settings page.

* **Reads the final page.** Works after every block and shortcode has rendered, so headings from other plugins are included.
* **Leaves your content alone.** Uses the WordPress HTML API to add `id` attributes to headings, and changes nothing else.
* **Stable anchors.** Keeps ids that headings already have. New ids are unique across the whole post.
* **Correct nesting.** Skipped heading levels still produce valid, balanced lists.
* **Accessible.** A labelled `<nav>` landmark, with optional collapsing that uses native `<details>`.
* **Schema.org.** JSON-LD lists each section as a `WebPageElement`, and links to the page node from Yoast SEO or Rank Math.
* **Light.** The stylesheet only loads where a TOC is used, and suits light and dark themes.
* **Extensible.** Filters for items, ids, markup, schema and defaults.

= Shortcode attributes =

* `tags`: heading levels to include, `h1` to `h6`. Default `h2,h3`.
* `title`: title above the list. Default "Table of Contents". `title=""` hides it.
* `depth`: maximum nesting depth. Default `0` (no limit).
* `min`: only show the TOC with at least this many entries. Default `1`.
* `collapsible`: let readers collapse the TOC. Default `false`.
* `collapsed`: start collapsed. Default `false`.
* `schema`: output schema.org JSON-LD. Default `true`.

Example: `[wp_toc tags="h2,h3,h4" title="In this article" collapsible="true"]`

The 1.0 shortcode `[auto_toc]` still works.

= Leaving a heading out =

Add the CSS class `no-toc` to the heading (block editor: Advanced → Additional CSS class(es)), or a `data-no-toc` attribute.

= Developers =

Filters: `wp_toc_enabled`, `wp_toc_default_atts`, `wp_toc_items`, `wp_toc_heading_id`, `wp_toc_html`, `wp_toc_schema`, `wp_toc_schema_webpage_id`, `wp_toc_load_css`, `wp_toc_content_filter_priority`. Arguments and details are in SSOT.md on [GitHub](https://github.com/menj/wp-toc).

== Installation ==

1. Upload the `wp-toc` folder to `/wp-content/plugins/`, or install it from the Plugins screen.
2. Activate WP TOC.
3. Add `[wp_toc]` where the table of contents should appear.

== Frequently Asked Questions ==

= Does the table of contents give me a rich result in Google? =

No. There is no rich result for a table of contents. Google's "Jump to" links come from the heading anchors that WP TOC adds. The JSON-LD describes the page's structure for search engines and other tools.

= Can I use my own styles? =

Yes. Override the custom properties `--wp-toc-bg`, `--wp-toc-border`, `--wp-toc-indent` and `--wp-toc-scroll-offset`, or turn the stylesheet off with `add_filter( 'wp_toc_load_css', '__return_false' );`.

= Why does the shortcode show nothing in a widget? =

WP TOC reads headings from the post content, so the shortcode only works inside post and page content.

= Will my existing heading links break? =

No. Headings that already have an `id` keep it.

== Changelog ==

= 1.1.0 =
* Fixed: the table of contents never appeared because the shortcode ran before the headings were read.
* Fixed: broken list markup with nested, skipped or out-of-order heading levels.
* Fixed: posts were re-serialized, which altered markup; now only heading ids are added.
* Fixed: existing heading ids were overwritten, and generated ids could clash with other ids.
* Added: `[wp_toc]` shortcode (`[auto_toc]` still works), `depth`, `min`, `collapsible`, `collapsed` and `schema` attributes.
* Added: schema.org JSON-LD, `no-toc` exclusions, filters, translations.
* Changed: accessible `<nav>` markup; the stylesheet only loads where needed.
* Changed: requires WordPress 6.5 and PHP 7.4. Renamed to WP TOC; licensed GPLv3 or later.

= 1.0.0 =
* Initial release by Ikram Hawramani.

== Upgrade Notice ==

= 1.1.0 =
Fixes the table of contents not appearing. Requires WordPress 6.5 and PHP 7.4. If you pasted the old code into functions.php, remove it first. Custom CSS that targets div.auto-toc-container needs updating; see UPGRADING.md.
