=== WP TOC ===
Contributors: menj
Tags: table of contents, toc, headings, schema, structured data
Requires at least: 6.7
Tested up to: 7.1
Requires PHP: 8.4
Stable tag: 1.1.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

Automatic table of contents for long posts, with schema.org structured data. Works as theme code or as a plugin.

== Description ==

Long articles are hard to scan. WP TOC gives every long post and page a linked, nested table of contents built from its headings, and describes the page's sections to search engines with schema.org data. Writers don't have to do anything: no settings, no shortcode.

When a post is shown, WP TOC:

1. Reads the headings in the finished post, including ones from blocks and other plugins.
2. Gives each heading an anchor, keeping any anchor it already has.
3. Inserts a table of contents before the first heading, when the post has at least 3 headings.
4. Adds schema.org data: microdata on the table of contents and each entry, plus JSON-LD, linked to Yoast SEO's or Rank Math's page data.
5. Leaves the rest of the post's HTML exactly as it was.

There is no JavaScript, and the small stylesheet only loads where a table of contents is shown.

**Built for themes, works as a plugin.** WP TOC is designed to be part of a theme's own code: copy the folder into the theme and load it from `functions.php`, with nothing to activate. The same files also work as this plugin.

= Placing it by hand (optional) =

Type `[wp_toc]` in a post to put the table of contents exactly there. It replaces the automatic one for that post. The 1.0 shortcode `[auto_toc]` still works.

* `tags`: heading levels to include, `h1` to `h6`. Default `h2,h3`.
* `title`: title above the list. Default "Table of Contents". `title=""` hides it.
* `depth`: maximum nesting depth. Default `0` (no limit).
* `min`: only show it with at least this many entries. Default `1`.
* `collapsible`: let readers collapse it. Default `false`.
* `collapsed`: start collapsed. Default `false`.

= Leaving a heading out =

Add the CSS class `no-toc` to the heading (block editor: Advanced → Additional CSS class(es)), or a `data-no-toc` attribute.

= Developers =

Change where the automatic table of contents appears with the `wp_toc_auto_insert` filter (post types, position, attributes, or turn it off). Other filters: `wp_toc_enabled`, `wp_toc_default_atts`, `wp_toc_items`, `wp_toc_heading_id`, `wp_toc_html`, `wp_toc_schema`, `wp_toc_schema_webpage_id`, `wp_toc_base_url`, `wp_toc_load_css`, `wp_toc_content_filter_priority`. Details are in SSOT.md on [GitHub](https://github.com/menj/wp-toc).

== Installation ==

As a plugin:

1. Upload the `wp-toc` folder to `/wp-content/plugins/`.
2. Activate WP TOC. Posts and pages with 3 or more headings now show a table of contents.

In a theme:

1. Copy the `wp-toc` folder into the theme.
2. Add `require_once get_stylesheet_directory() . '/wp-toc/wp-toc.php';` to the theme's `functions.php`.

Requires PHP 8.4 or later. On older PHP, WP TOC does not load and shows administrators a notice.

== Frequently Asked Questions ==

= Do I need to add a shortcode? =

No. Posts and pages with 3 or more headings get a table of contents automatically. The shortcode is only for placing it somewhere specific.

= Does the table of contents give me a rich result in Google? =

No. There is no rich result for a table of contents. Google's "Jump to" links come from the heading anchors that WP TOC adds. The schema.org data describes the page's structure for search engines and other tools.

= Does it work with Rank Math? =

Yes. The schema.org data attaches to Rank Math's page data using Rank Math's canonical URL, the table of contents never appears in Rank Math's auto-generated descriptions, and Rank Math's "Table of Contents" analysis check recognizes WP TOC when it is installed as a plugin.

= Can I use my own styles? =

Yes. Override the custom properties `--wp-toc-bg`, `--wp-toc-border`, `--wp-toc-indent` and `--wp-toc-scroll-offset`, or turn the stylesheet off with `add_filter( 'wp_toc_load_css', '__return_false' );`.

= Will my existing heading links break? =

No. Headings that already have an `id` keep it.

== Changelog ==

= 1.1.0 =
* Added: automatic table of contents on posts and pages with 3 or more headings, no shortcode needed.
* Added: works as theme code or as a plugin, from the same files.
* Added: schema.org microdata on every element, plus JSON-LD, linked to Yoast SEO and Rank Math.
* Added: Rank Math compatibility, `[wp_toc]` shortcode (`[auto_toc]` still works), `depth`, `min`, `collapsible` and `collapsed` attributes, `no-toc` exclusions, filters, translations.
* Fixed: the table of contents never appeared because the shortcode ran before the headings were read.
* Fixed: broken list markup with nested, skipped or out-of-order heading levels.
* Fixed: posts were re-serialized, which altered markup; now only heading ids are added.
* Fixed: existing heading ids were overwritten, and generated ids could clash with other ids.
* Changed: requires PHP 8.4 and WordPress 6.7. Accessible `<nav>` markup; the stylesheet only loads where needed. Renamed to WP TOC; licensed GPLv3 or later.

= 1.0.0 =
* Initial release by Ikram Hawramani.

== Upgrade Notice ==

= 1.1.0 =
Requires PHP 8.4 and WordPress 6.7. Long posts now get a table of contents automatically. Remove any 1.0 code pasted into functions.php first. Custom CSS that targets div.auto-toc-container needs updating; see UPGRADING.md.
