# WP TOC

A lightweight, server-side table of contents for WordPress. Add `[wp_toc]` to a post and WP TOC links every heading, renders a nested, accessible list, and describes the page's sections with schema.org markup. There is no JavaScript and no settings page.

**Requires:** WordPress 6.5+ · PHP 7.4+ · **Version:** 1.1.0 · **License:** GPL-3.0-or-later

## Features

- **Reads the final page.** Works after every block and shortcode has rendered, so headings created by other plugins are included.
- **Leaves your content alone.** Uses WordPress's HTML API (`WP_HTML_Tag_Processor`) to add `id` attributes to headings, and changes nothing else in the post.
- **Stable anchors.** Keeps ids that headings already have. New ids are unique across the whole post, with `-2`, `-3` for duplicates.
- **Correct nesting.** Skipped levels (h2 → h4) and posts that start with a deeper heading still produce valid, balanced lists.
- **Accessible.** Output is a labelled `<nav>` landmark. Optional collapsing uses native `<details>`, with no JavaScript.
- **Schema.org.** JSON-LD lists each section as a `WebPageElement`, and links to the page node from Yoast SEO or Rank Math.
- **Light.** The stylesheet only loads on pages that use the shortcode, and adapts to light and dark themes.
- **Extensible.** Filters for items, ids, markup, schema and defaults.

## Installation

1. Download the code from https://github.com/menj/wp-toc and put the folder in `wp-content/plugins/wp-toc/`.
2. Activate **WP TOC** under **Plugins**.

The plugin is a main file plus an `includes/` folder and a stylesheet, so it is no longer meant to be pasted into `functions.php`.

## Usage

Add the shortcode where the table of contents should appear:

```text
[wp_toc]
```

The 1.0 shortcode `[auto_toc]` still works and behaves the same.

### Attributes

| Attribute | Default | Description |
|---|---|---|
| `tags` | `h2,h3` | Heading levels to include, `h1`–`h6`. |
| `title` | `Table of Contents` | Title above the list. `title=""` hides it. |
| `depth` | `0` | Maximum nesting depth; `0` is unlimited. |
| `min` | `1` | Only show the TOC when it has at least this many entries. |
| `collapsible` | `false` | Let readers collapse the TOC. |
| `collapsed` | `false` | Start collapsed (with `collapsible="true"`). |
| `schema` | `true` | Output schema.org JSON-LD. |

### Examples

```text
[wp_toc tags="h2,h3,h4" title="In this article"]
[wp_toc depth="1" min="3"]
[wp_toc collapsible="true" collapsed="true"]
[wp_toc title="" schema="false"]
```

### Leaving a heading out

Give the heading the class `no-toc`, or a `data-no-toc` attribute. In the block editor, add `no-toc` under **Advanced → Additional CSS class(es)**.

```html
<h2 class="no-toc">Not in the table of contents</h2>
```

## Styling

The stylesheet uses the theme's text colour, so it suits light and dark themes. Adjust it with custom properties:

```css
.wp-toc {
	--wp-toc-bg: #fffbe6;
	--wp-toc-border: #e6d58c;
	--wp-toc-indent: 1.5em;
}
:root {
	--wp-toc-scroll-offset: 80px; /* height of a sticky header */
}
```

Or turn the stylesheet off and write your own:

```php
add_filter( 'wp_toc_load_css', '__return_false' );
```

Use the `wp-toc-*` classes for new CSS. The old `auto-toc-container`, `auto-toc-title` and `auto-toc-list` classes are still output. The full markup and class list are in [SSOT.md](SSOT.md#markup).

## Schema.org

After the first TOC in a post, WP TOC outputs JSON-LD like this:

```json
{
  "@context": "https://schema.org",
  "@type": "WebPage",
  "@id": "https://example.com/my-post/#webpage",
  "url": "https://example.com/my-post/",
  "hasPart": [
    {
      "@type": "WebPageElement",
      "name": "Installation",
      "url": "https://example.com/my-post/#installation",
      "hasPart": [
        { "@type": "WebPageElement", "name": "Requirements", "url": "https://example.com/my-post/#requirements" }
      ]
    }
  ]
}
```

The `@id` matches the WebPage node your SEO plugin outputs, so search engines merge the sections into its graph:

- **Rank Math:** Rank Math's canonical URL + `#webpage`, so a custom canonical is respected.
- **Yoast SEO:** the page URL.
- **Neither:** the permalink + `#webpage`.

## Rank Math

WP TOC is checked against Rank Math 1.0.268 and Rank Math Pro 3.0.49:

- Rank Math's **Table of Contents** content-analysis check recognizes WP TOC.
- The schema attaches to Rank Math's WebPage node, as above.
- The TOC never leaks into Rank Math's auto-generated descriptions, even when Rank Math is set to parse shortcodes.
- Headings that already have anchors (for example from Rank Math's TOC block) keep them.

This markup does not produce a rich result. Google's "Jump to" links come from the heading anchors themselves. Turn the markup off per TOC with `schema="false"`, or everywhere with `add_filter( 'wp_toc_schema', '__return_false' );`.

## Hooks

| Filter | Use |
|---|---|
| `wp_toc_enabled` | Return `false` to skip a post. |
| `wp_toc_default_atts` | Change default attributes for every TOC. |
| `wp_toc_items` | Add, remove or rename entries before they are nested. |
| `wp_toc_heading_id` | Change how heading ids are generated. |
| `wp_toc_html` | Change the final markup. |
| `wp_toc_schema` | Change or remove the JSON-LD. |
| `wp_toc_schema_webpage_id` | Set the `@id` the JSON-LD attaches to. |
| `wp_toc_load_css` | Return `false` to skip the stylesheet. |
| `wp_toc_content_filter_priority` | Priority of the content filter (default 99). Add it in a mu-plugin. |

Example: use "Contents" as the default title for every TOC.

```php
add_filter( 'wp_toc_default_atts', function ( $atts ) {
	$atts['title'] = 'Contents';
	return $atts;
} );
```

Arguments for every hook are listed in [SSOT.md](SSOT.md#hooks).

## Development

```bash
composer install
```

```bash
composer test
```

```bash
composer phpcs
```

The tests run against real WordPress core code (hooks, formatting, shortcodes and the HTML API) without a database. CI runs `php -l`, PHPCS (WordPress Coding Standards and PHPCompatibilityWP) and PHPUnit on PHP 7.4 to 8.4, against WordPress 7.1 and the oldest supported version, 6.5.

## Documentation

- [SSOT.md](SSOT.md): every fact about the plugin in one place (versions, attributes, markup, hooks, release checklist).
- [UPGRADING.md](UPGRADING.md): what changes when you upgrade.
- [CHANGELOG.md](CHANGELOG.md): release history.
- [readme.txt](readme.txt): WordPress.org readme.

## Credits

Developed and maintained by [MENJ](https://github.com/menj): [github.com/menj/wp-toc](https://github.com/menj/wp-toc).

Based on original work by Ikram Hawramani.

## License

WP TOC is free software, licensed under the [GNU General Public License v3 or later](https://www.gnu.org/licenses/gpl-3.0.html) (GPL-3.0-or-later). See [LICENSE](LICENSE).

Copyright (C) 2026 MENJ

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

Based on original work by Ikram Hawramani, released under the MIT License. The original MIT notice is retained in [LICENSE-MIT-ORIGINAL](LICENSE-MIT-ORIGINAL).
