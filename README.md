# WP TOC

**Automatic table of contents for WordPress, built into your theme.**

**Requires:** WordPress 6.7+ · PHP 8.4+ · **Version:** 1.1.0 · **License:** GPL-3.0-or-later

## What it is for

Long articles are hard to scan. Readers want to see what a post covers and jump
straight to the part they need, and search engines want to understand how the
page is organised.

WP TOC solves both, without any work from the people writing posts:

- **Readers** get a linked, nested table of contents at the top of every long
  post. Clicking an entry jumps to that section.
- **Search engines** get the same structure as schema.org data, so they can
  understand the page's sections. (There is no special Google result for a
  table of contents; Google's "Jump to" links come from the anchors WP TOC adds.)
- **Writers** do nothing. There is no setting to fill in and no shortcode to
  remember.

## What it does

When a post or page is shown, WP TOC:

1. **Reads the headings** in the finished post (`<h2>`, `<h3>`, and so on),
   including headings created by blocks and other plugins.
2. **Gives each heading an anchor** (an `id` such as `#installation`), so it
   can be linked to. Headings that already have one keep it.
3. **Inserts a table of contents** just before the first heading, when the
   post has at least 3 headings. Shorter posts are left as they are.
4. **Adds schema.org data** describing the table of contents and each section
   (microdata on the list, plus JSON-LD), linked to the page data from Yoast SEO
   or Rank Math when either is installed.
5. **Changes nothing else.** The rest of the post's HTML is left exactly as it was.

It runs on the server, so there is no JavaScript, and its small stylesheet only
loads on pages that show a table of contents.

## Adding it to a theme (main use)

WP TOC is meant to be part of your theme's code. It does not need to be
activated.

1. Copy the `wp-toc` folder into your theme, e.g. `wp-content/themes/your-theme/wp-toc/`.
2. Add one line to the theme's `functions.php`:

```php
require_once get_stylesheet_directory() . '/wp-toc/wp-toc.php';
```

That's all. Posts and pages with 3 or more headings now show a table of contents.

Use `get_template_directory()` instead if the folder is in a parent theme. The
folder can live anywhere in the theme; WP TOC works out its own URL. Only
`wp-toc.php`, `includes/`, `assets/` and `languages/` are needed.

## Using it as a plugin (also supported)

The same folder works as a normal plugin: put it in `wp-content/plugins/wp-toc/`
and activate **WP TOC** under **Plugins**. It behaves the same way. If a theme
also bundles it, only one copy runs.

## Requirements

- **PHP 8.4 or later.** On older PHP, WP TOC does not load at all and shows
  administrators a notice explaining why.
- **WordPress 6.7 or later**, the first version that supports PHP 8.4.

## Choosing where it appears

Everything works without configuration. To change the defaults, add a filter in
the theme's `functions.php`:

```php
add_filter( 'wp_toc_auto_insert', function ( $settings ) {
	$settings['post_types'] = array( 'post', 'docs' );       // Default: post, page.
	$settings['position']   = 'after_first_paragraph';       // Or 'before_first_heading' (default), 'top'.
	$settings['atts']       = array( 'min' => '4', 'tags' => 'h2,h3,h4' );
	return $settings;
} );
```

Turn the automatic table of contents off (you can still place it by hand):

```php
add_filter( 'wp_toc_auto_insert', function ( $settings ) {
	$settings['enabled'] = false;
	return $settings;
} );
```

The automatic table of contents only appears in the main post of a single post
or page view: not on archives, in feeds, in related-post lists, or on
password-protected posts until they are unlocked.

### Placing it by hand in one post (optional)

Typing the shortcode in a post puts the table of contents exactly there instead
of the automatic position:

```text
[wp_toc]
```

The 1.0 shortcode `[auto_toc]` still works.

| Attribute | Default | Description |
|---|---|---|
| `tags` | `h2,h3` | Heading levels to include, `h1`–`h6`. |
| `title` | `Table of Contents` | Title above the list. `title=""` hides it. |
| `depth` | `0` | Maximum nesting depth; `0` is unlimited. |
| `min` | `1` | Only show it when it has at least this many entries. |
| `collapsible` | `false` | Let readers collapse it. |
| `collapsed` | `false` | Start collapsed (with `collapsible="true"`). |

The same attributes go in `atts` for the automatic table of contents.

### Leaving a heading out

Give the heading the class `no-toc`, or a `data-no-toc` attribute. In the block
editor, add `no-toc` under **Advanced → Additional CSS class(es)**.

```html
<h2 class="no-toc">Not in the table of contents</h2>
```

## Styling

The stylesheet uses the theme's text colour, so it suits light and dark themes.
Adjust it with custom properties:

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

Or turn the stylesheet off and style it in the theme's own CSS:

```php
add_filter( 'wp_toc_load_css', '__return_false' );
```

Use the `wp-toc-*` classes for new CSS. The 1.0 classes `auto-toc-container`,
`auto-toc-title` and `auto-toc-list` are still output. The full markup and class
list are in [SSOT.md](SSOT.md#markup).

## Schema.org

Every element WP TOC outputs carries schema.org data, with no option to turn it off:

| Element | schema.org |
|---|---|
| The table of contents | `SiteNavigationElement`, with `name` and `isPartOf` the page |
| Each entry | `WebPageElement` with `name` and `url`, nested through `hasPart` |
| The page (JSON-LD) | `WebPage` whose `hasPart` lists every section |

The microdata and the JSON-LD use the same identifiers for each section, so
search engines read them as one description. The page identifier matches the one
your SEO plugin uses:

- **Rank Math:** Rank Math's canonical URL + `#webpage`, so a custom canonical is respected.
- **Yoast SEO:** the page URL.
- **Neither:** the permalink + `#webpage`.

Headings inside the post get anchors but no schema.org attributes: they sit
outside any schema.org scope, so attributes there would mean nothing.

## Rank Math

Checked against Rank Math 1.0.268 and Rank Math Pro 3.0.49:

- The schema.org data attaches to Rank Math's page data, as above.
- The table of contents never leaks into Rank Math's auto-generated descriptions.
- Headings that already have anchors (for example from Rank Math's TOC block) keep them.
- Rank Math's **Table of Contents** analysis check recognises WP TOC **when it is
  installed as a plugin**. Rank Math only looks at active plugins, so it can't
  see WP TOC inside a theme; the check's result doesn't affect the page.

## For developers

| Filter | Use |
|---|---|
| `wp_toc_auto_insert` | Settings for the automatic table of contents. |
| `wp_toc_enabled` | Return `false` to skip a post entirely. |
| `wp_toc_default_atts` | Default attributes for every table of contents. |
| `wp_toc_items` | Add, remove or rename entries before they are nested. |
| `wp_toc_heading_id` | Change how heading anchors are generated. |
| `wp_toc_html` | Change the final markup. |
| `wp_toc_schema` | Change the JSON-LD. |
| `wp_toc_schema_webpage_id` | Set the page identifier the schema.org data attaches to. |
| `wp_toc_base_url` | Set the folder's URL; return `''` to print the CSS inline. |
| `wp_toc_load_css` | Return `false` to skip the stylesheet. |
| `wp_toc_content_filter_priority` | Priority of the content filter (default 99). Add it before loading WP TOC. |

Arguments for every hook, how it works internally, and the release checklist are
in [SSOT.md](SSOT.md).

### Working on WP TOC

```bash
composer install
```

```bash
composer test
```

```bash
composer phpcs
```

The tests run against real WordPress core code (hooks, formatting, shortcodes
and the HTML API) without a database. CI runs PHPCS and PHPUnit on PHP 8.4 and
8.5 against WordPress 7.1 and 6.7, and checks that WP TOC refuses to load on
PHP 7.4 and 8.3.

## Documentation

- [SSOT.md](SSOT.md): every fact about WP TOC in one place (requirements, settings, markup, schema, hooks, release checklist).
- [UPGRADING.md](UPGRADING.md): what changes when you upgrade.
- [CHANGELOG.md](CHANGELOG.md): release history.
- [readme.txt](readme.txt): WordPress.org readme, for the plugin setup.

## Credits

Developed and maintained by [MENJ](https://github.com/menj): [github.com/menj/wp-toc](https://github.com/menj/wp-toc).

Based on original work by Ikram Hawramani.

## License

WP TOC is free software, licensed under the [GNU General Public License v3 or later](https://www.gnu.org/licenses/gpl-3.0.html) (GPL-3.0-or-later). See [LICENSE](LICENSE).

Copyright (C) 2026 MENJ

This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for more details.

Based on original work by Ikram Hawramani, released under the MIT License. The original MIT notice is retained in [LICENSE-MIT-ORIGINAL](LICENSE-MIT-ORIGINAL).
