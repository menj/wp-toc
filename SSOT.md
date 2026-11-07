# WP TOC: Single Source of Truth

This file is the reference for every fact about the plugin. `README.md`,
`readme.txt`, `UPGRADING.md`, `CHANGELOG.md` and the plugin header in
`wp-toc.php` must agree with it. When something changes, update this file
first, then the others. `tests/test-docs.php` fails if the version, the
requirements or the license drift between files.

## Identity

| Key | Value |
|---|---|
| Name | WP TOC |
| Slug | `wp-toc` |
| Main file | `wp-toc.php` |
| Text domain | `wp-toc` |
| Version | 1.1.0 |
| Requires at least (WordPress) | 6.5 |
| Tested up to (WordPress) | 7.1 |
| Requires PHP | 7.4 |
| Author | Mohd Elfie Nieshaem Juferi |
| Author URI | https://menj.blog |
| Plugin URI | https://github.com/menj/wp-toc |
| License | GPL-3.0-or-later |
| License URI | https://www.gnu.org/licenses/gpl-3.0.html |
| Original work | Ikram Hawramani, MIT License (notice kept in `LICENSE-MIT-ORIGINAL`) |

Why WordPress 6.5: the plugin uses `WP_HTML_Tag_Processor::next_token()`,
added in 6.5, to read heading text without re-serializing the post.

## Shortcodes

| Tag | Status |
|---|---|
| `[wp_toc]` | Main shortcode. |
| `[auto_toc]` | Legacy alias from 1.0.x. Same behaviour, kept so existing posts keep working. |

## Shortcode attributes

| Attribute | Default | Accepted values | Effect |
|---|---|---|---|
| `tags` | `h2,h3` | Comma-separated `h1`–`h6`, any case | Heading levels to include. Anything else is ignored; if nothing valid remains, the default is used. |
| `title` | `Table of Contents` (translatable) | Text; HTML is stripped | Title shown above the list. Empty: no visible title, and the `<nav>` gets an `aria-label` instead. |
| `depth` | `0` | Integer ≥ 0 | Maximum nesting depth. `0` means no limit. |
| `min` | `1` | Integer ≥ 1 | Minimum number of entries before the TOC is shown. Headings still get anchors when it is hidden. |
| `collapsible` | `false` | `true`/`false`, `1`/`0`, `yes`/`no`, `on`/`off` | Wraps the list in `<details>`/`<summary>`, with no JavaScript. |
| `collapsed` | `false` | Same as above | With `collapsible`, start closed. |
| `schema` | `true` | Same as above | Output schema.org JSON-LD after the TOC (at most once per post). |

## Heading rules

- Headings are read from the final rendered content, after all blocks and shortcodes.
- An existing `id` on a heading is kept and used as its anchor.
- A missing `id` is generated with `sanitize_title()` from the heading text,
  then made unique against **every** `id` in the content (not just headings),
  by adding `-2`, `-3` and so on. An empty slug becomes `section`.
  Non-Latin characters stay readable: `<h2>مرحبا بالعالم</h2>` gets
  `id="مرحبا-بالعالم"`, not the percent-encoded form `sanitize_title()` returns.
- Heading text is the heading's text content with whitespace collapsed.
- Headings with the class `no-toc` or a `data-no-toc` attribute are skipped.
- Headings inside `<template>` are skipped, since template content is never displayed.
- Empty headings are skipped.
- Nesting: each entry becomes a child of the closest earlier entry with a
  lower level. Skipped levels (h2 → h4) nest one step; a first heading deeper
  than later ones stays at the top level.

## Markup

```html
<nav class="wp-toc auto-toc-container" aria-labelledby="wp-toc-title-1">
  <span class="wp-toc-title auto-toc-title" id="wp-toc-title-1">Table of Contents</span>
  <ul class="wp-toc-list auto-toc-list">
    <li><a href="#intro">Intro</a>
      <ul class="wp-toc-sublist"><li><a href="#detail">Detail</a></li></ul>
    </li>
  </ul>
</nav>
```

With `collapsible="true"`, the title and list sit inside
`<details class="wp-toc-details" open><summary class="wp-toc-summary">…</summary>…</details>`,
and the `<nav>` also gets the class `wp-toc--collapsible`.

| Class | Element | Notes |
|---|---|---|
| `wp-toc` | `<nav>` | New in 1.1.0 |
| `auto-toc-container` | `<nav>` | Legacy, kept for existing custom CSS |
| `wp-toc--collapsible` | `<nav>` | Only when collapsible |
| `wp-toc-title`, `auto-toc-title` | Title `<span>` | |
| `wp-toc-details`, `wp-toc-summary` | `<details>`, `<summary>` | Only when collapsible |
| `wp-toc-list`, `auto-toc-list` | Top-level `<ul>` | |
| `wp-toc-sublist` | Nested `<ul>` | |
| `wp-toc-schema` | JSON-LD `<script>` | |

## CSS

- File: `assets/css/wp-toc.css`, style handle `wp-toc`.
- Enqueued in `<head>` on singular views whose content contains either
  shortcode; otherwise enqueued by the shortcode itself (printed in the footer).
- Custom properties: `--wp-toc-bg`, `--wp-toc-border`, `--wp-toc-indent`,
  `--wp-toc-scroll-offset` (default `2rem`, used for `scroll-margin-top` on headings with an id).

## Schema.org

- One `<script type="application/ld+json" class="wp-toc-schema">` after the
  first TOC in a post that has `schema` on.
- A `WebPage` node with `url` set to the permalink and `hasPart` listing each
  section as a `WebPageElement` (`name`, absolute `url` with the percent-encoded `#anchor`), nested to match the TOC.
- `@id`, chosen to match the SEO plugin's WebPage node so search engines merge the two:
  - Rank Math active (`RANK_MATH_VERSION`) and the post is the page being viewed:
    Rank Math's canonical URL + `#webpage` (`RankMath\Paper\Paper::get()->get_canonical()`),
    which also covers a custom canonical. Falls back to the next rule if that call fails.
  - Yoast SEO active (`WPSEO_VERSION`): the permalink.
  - Otherwise: permalink + `#webpage`.
- Section `url`s always use the permalink, like Rank Math's own TOC block.
- Encoded with `JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP`.
- No permalink (no current post) means no JSON-LD.

## Rank Math compatibility

Checked against Rank Math 1.0.268 and Rank Math Pro 3.0.49.

- **Content analysis:** WP TOC adds itself to `rank_math/researches/toc_plugins`,
  so Rank Math's "Table of Contents" check passes when WP TOC is active.
- **Schema:** see the `@id` rules above. Rank Math's own TOC block outputs separate
  `SiteNavigationElement` entries; both can be used on the same page.
- **Descriptions:** with Rank Math's `rank_math/paper/auto_generated_description/apply_shortcode`
  filter on, Rank Math runs shortcodes outside `the_content`. `[wp_toc]` then outputs only
  its HTML-comment placeholder, which Rank Math strips, so no TOC text leaks into descriptions.
- **Rank Math Pro video detection** runs `the_content` on save to find embeds. The TOC
  contains no video, iframe or embed markup, and the stylesheet is not enqueued in admin requests.
- **Existing anchors:** heading ids added by Rank Math's TOC block are kept.

## Hooks

| Hook | Type | Arguments | Purpose |
|---|---|---|---|
| `wp_toc_enabled` | filter | `bool $enabled, int $post_id` | Return false to skip processing; placeholders are removed. |
| `wp_toc_default_atts` | filter | `array $defaults` | Change default shortcode attributes. |
| `shortcode_atts_wp_toc` | filter | core `shortcode_atts` arguments | Core filter, for both shortcode tags. |
| `wp_toc_items` | filter | `array $items, int $post_id` | Flat list of `array( 'level', 'id', 'text' )` before nesting. |
| `wp_toc_heading_id` | filter | `string $slug, string $text, int $level` | Generated id, before it is made unique. |
| `wp_toc_html` | filter | `string $html, array $tree, array $atts, int $post_id` | Final TOC markup. |
| `wp_toc_schema` | filter | `array $data, array $tree, int $post_id` | JSON-LD data; return empty to output none. |
| `wp_toc_schema_webpage_id` | filter | `string $id, string $permalink, int $post_id` | `@id` of the WebPage node. |
| `wp_toc_load_css` | filter | `bool $load` | Return false to not load the stylesheet. |
| `wp_toc_content_filter_priority` | filter | `int $priority` | Priority of the `the_content` filter (default 99, minimum 12). Read when the plugin loads, so add it in a mu-plugin. |

## How it works

1. `[wp_toc]` runs at `the_content` priority 11 (core `do_shortcode`) and outputs
   only a placeholder: `<!--wp-toc:HEX-->`, where HEX is the hex-encoded JSON of its attributes.
2. `WP_TOC::filter_content()` runs at priority 99. It reads every heading and id with
   `WP_HTML_Tag_Processor`, writes missing heading ids (nothing else in the
   content changes), builds the tree and replaces each placeholder with the TOC.
3. A `<p>` wrapped around a placeholder is removed with it.

Limitations:
- The TOC only covers the content passed through `the_content`. Shortcodes
  rendered elsewhere (widgets, `do_shortcode()` in templates) output nothing.
- Generated ids follow `sanitize_title()` rules; use `wp_toc_heading_id` to change them.

## Files

| Path | Purpose |
|---|---|
| `wp-toc.php` | Plugin header, constants (`WP_TOC_VERSION`, `WP_TOC_FILE`), bootstrap |
| `includes/class-wp-toc.php` | All logic (`WP_TOC` class) |
| `assets/css/wp-toc.css` | Default styles |
| `languages/wp-toc.pot` | Translation template |
| `readme.txt` | WordPress.org readme |
| `README.md` | GitHub readme |
| `SSOT.md` | This file |
| `UPGRADING.md` | Upgrade notes between versions |
| `CHANGELOG.md` | Release history |
| `LICENSE` | GNU GPL v3 |
| `LICENSE-MIT-ORIGINAL` | MIT notice for the original code |
| `tests/` | PHPUnit tests (real WordPress core code, no database); `tests/fixtures/` holds a stand-in for Rank Math's `Paper` class |
| `bin/install-wp-core.sh` | Downloads the WordPress core files the tests load into `.cache/wordpress` |
| `composer.json`, `phpunit.xml.dist`, `phpcs.xml.dist` | Dev tooling |
| `.github/workflows/ci.yml` | CI: lint, PHPCS, PHPUnit on PHP 7.4–8.4 with WordPress 7.1, plus WordPress 6.5 |

## Release checklist

1. Update the version in this file.
2. Update `Version` in the `wp-toc.php` header and `WP_TOC_VERSION`.
3. Update `Stable tag` in `readme.txt`, and its changelog and upgrade notice.
4. Add the release to `CHANGELOG.md`, and to `UPGRADING.md` if anything changes for users.
5. Regenerate `languages/wp-toc.pot` if strings changed.
6. Run `composer test` and `composer phpcs`.
7. Tag the release `vX.Y.Z`.
