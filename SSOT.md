# WP TOC: Single Source of Truth

This file is the reference for every fact about WP TOC. `README.md`,
`readme.txt`, `UPGRADING.md`, `CHANGELOG.md` and the header in `wp-toc.php`
must agree with it. When something changes, update this file first, then the
others. `tests/test-docs.php` fails if the version, requirements, author or
license drift between files.

Dates in the docs use the D-Month-YYYY format, e.g. 6-November-2026.

## What WP TOC is for

Long articles are easier to read when readers can see their structure and
jump to the part they need. WP TOC gives every long post a **table of
contents** built from its headings, with no editing work:

- It reads the headings in the post (`<h2>`, `<h3>` and so on), gives each one
  an anchor (`id`), and inserts a linked, nested list of them.
- It describes the page's sections to search engines with **schema.org**
  structured data, as JSON-LD and as microdata on the list itself.
- It needs no settings page, no activation and no shortcode: drop it into a
  theme (or install it as a plugin) and posts with enough headings get a TOC.

It is meant to be **part of a theme's own code**, but the same files also work
as a regular plugin.

## Identity

| Key | Value |
|---|---|
| Name | WP TOC |
| Slug | `wp-toc` |
| Main file | `wp-toc.php` |
| Text domain | `wp-toc` |
| Version | 1.1.0 |
| Requires at least (WordPress) | 6.7 |
| Tested up to (WordPress) | 7.1 |
| Requires PHP | 8.4 |
| Author | MENJ |
| Author URI | https://menj.blog |
| Plugin URI | https://github.com/menj/wp-toc |
| License | GPL-3.0-or-later |
| License URI | https://www.gnu.org/licenses/gpl-3.0.html |
| Original work | Ikram Hawramani, MIT License (notice kept in `LICENSE-MIT-ORIGINAL`) |

### Why these minimums

- **PHP 8.4, strictly.** On older PHP, `wp-toc.php` stops before loading any
  code and shows administrators a notice instead. That file uses only syntax
  old PHP can parse, so the check itself always runs. The code in `includes/`
  uses PHP 8.3/8.4 features (typed class constants, `array_any()`,
  `array_find()`) and `strict_types`.
- **WordPress 6.7**, the first release with PHP 8.4 support. WP TOC also needs
  `WP_HTML_Tag_Processor::next_token()` (6.5+).

## Two ways to use it

The same folder works in both setups. WP TOC detects which one applies.

| | Theme code | Plugin |
|---|---|---|
| Install | Copy the `wp-toc/` folder into the theme; add `require_once get_stylesheet_directory() . '/wp-toc/wp-toc.php';` to `functions.php` | Put the folder in `wp-content/plugins/` and activate it |
| Detected when | `wp-toc.php` is outside the plugins and must-use plugins directories | `wp-toc.php` is inside `WP_PLUGIN_DIR` or `WPMU_PLUGIN_DIR` |
| Stylesheet URL | Child theme, parent theme, then `wp-content`; inline CSS if the folder has no public URL | `plugins_url()` |
| Translations | `languages/wp-toc-{locale}.mo` in the folder | `load_plugin_textdomain()` |
| Rank Math "Table of Contents" check | Not recognized (Rank Math only checks active plugins) | Recognized |

Shared rules:

- **Loaded twice** (bundled in a theme and active as a plugin): the first copy
  wins; the second returns immediately.
- **Loaded late** (after `init` has run): shortcodes and translations are
  registered at once.
- **Override the URL** for unusual setups: define `WP_TOC_URL` before loading,
  or use the `wp_toc_base_url` filter (return `''` to inline the CSS).
- Constants: `WP_TOC_VERSION`, `WP_TOC_FILE`, `WP_TOC_DIR` (and optional `WP_TOC_URL`).

## Automatic table of contents

On by default. No shortcode needed.

| Setting | Default | Meaning |
|---|---|---|
| `enabled` | `true` | Turn automatic insertion on or off. |
| `post_types` | `post`, `page` | Post types that get one. |
| `position` | `before_first_heading` | `before_first_heading`, `after_first_paragraph` or `top`. |
| `atts` | `min` = `3` | Shortcode attributes for the automatic TOC (see below). |

Change them with the `wp_toc_auto_insert` filter.

It is added only to the **main post of a singular front-end view**: not on
archives, feeds or admin screens, not to other posts shown on the page (e.g.
related posts), not outside the main loop, not to password-protected posts
until they are unlocked, and **not to excerpts** (an excerpt built from the
content would otherwise start with the TOC's text, and SEO and sharing tools
reuse excerpts as descriptions).

**Never two tables of contents:** when the post already contains another TOC,
no automatic one is added. Detected by class: Rank Math's TOC block
(`wp-block-rank-math-toc-block`), Yoast SEO's (`yoast-table-of-contents`), the
core block (`wp-block-table-of-contents`), Easy Table of Contents, LuckyWP,
Table of Contents Plus, Kadence, Spectra, Ultimate Blocks and SimpleTOC. Add
more with `wp_toc_other_toc_markers`. A `[wp_toc]` typed by hand is still shown.

**Placement:** the TOC is inserted before the top-level element that contains
the first heading, never inside another element (an accordion's `<summary>`, a
table cell, a link), where it would be hidden or make invalid HTML.
`after_first_paragraph` uses the first top-level paragraph.

A `[wp_toc]` (or `[auto_toc]`) shortcode in a post places the TOC by hand and
replaces the automatic one for that post.

## Shortcodes (optional)

| Tag | Status |
|---|---|
| `[wp_toc]` | Places a TOC exactly where it is typed. |
| `[auto_toc]` | Legacy alias from 1.0.x. Same behaviour. |

| Attribute | Default | Accepted values | Effect |
|---|---|---|---|
| `tags` | `h2,h3` | Comma-separated `h1`–`h6`, any case | Heading levels to include. Anything else is ignored; if nothing valid remains, the default is used. |
| `title` | `Table of Contents` (translatable) | Text; HTML is stripped | Title above the list. Empty: no visible title; the `<nav>` gets an `aria-label` and the schema name comes from a `<meta>`. |
| `depth` | `0` | Integer ≥ 0 | Maximum nesting depth. `0` means no limit. |
| `min` | `1` (automatic TOC: `3`) | Integer ≥ 1 | Minimum number of entries before the TOC is shown. Headings still get anchors when it is hidden. |
| `collapsible` | `false` | `true`/`false`, `1`/`0`, `yes`/`no`, `on`/`off` | Wraps the list in `<details>`/`<summary>`, with no JavaScript. |
| `collapsed` | `false` | Same as above | With `collapsible`, start closed. |

There is no attribute to turn schema.org output off; see [Schema.org](#schemaorg).

## Heading rules

These decide the anchors (`id`s) that TOC links, schema.org URLs and search
engines' "Jump to" links point at.

- Headings are read from the final rendered content, after all blocks and shortcodes.
- **An author's `id` is kept**, so links to it keep working. Exception: when the
  same `id` is already used earlier in the content, the browser can never reach
  the second one, so that heading gets a new `id`.
- **A missing `id` is generated** from the heading text with `sanitize_title()`:
  - Non-Latin letters stay readable: `<h2>مرحبا بالعالم</h2>` → `id="مرحبا-بالعالم"`,
    not the percent-encoded form `sanitize_title()` returns.
  - Only letters, marks, numbers, `-` and `_` are kept, so symbols and emoji
    stay out of anchors: `🚀 Launch` → `launch`. (WordPress transliterates some
    symbols first, e.g. `€` → `e`.)
  - At most 72 characters, cut at the last word boundary.
  - Unique against **every** `id` in the content, and against the
    [reserved ids](#reserved-ids), by adding `-2`, `-3` and so on. An empty
    slug becomes `section`.
- **Heading text** (the TOC label and the source of the anchor) is what readers
  see: whitespace is collapsed, and footnote markers (`<sup data-fn>`),
  screen-reader-only text (`.screen-reader-text`) and decorative parts
  (`aria-hidden="true"`) are left out.
- **Skipped headings:** class `no-toc`, attribute `data-no-toc`, class
  `screen-reader-text`, inside `<template>`, or empty.
- Nesting: each entry becomes a child of the closest earlier entry with a
  lower level. Skipped levels (h2 → h4) nest one step; a first heading deeper
  than later ones stays at the top level.
- Nothing else in the content changes: only `id` attributes are added, and the
  TOC is inserted.

### Reserved ids

Generated anchors never take ids that WordPress and themes use for landmarks
and links, or theme links would jump to the heading instead (e.g. "Skip to
content" → `#content`, comment links → `#comments`, `#respond`):

`content`, `main`, `primary`, `secondary`, `page`, `site`, `top`, `masthead`,
`colophon`, `header`, `footer`, `site-header`, `site-footer`, `site-content`,
`main-content`, `content-area`, `site-navigation`, `navigation`,
`primary-menu`, `menu`, `nav`, `sidebar`, `search`, `searchform`,
`breadcrumbs`, `comments`, `respond`, `reply-title`, `commentform`, `comment`,
`cancel-comment-reply-link`, `wpadminbar`, `more`, and the post's own
`post-{ID}` (themes wrap posts in `<article id="post-{ID}">`).

A heading titled "Comments" gets `#comments-2`. Add a theme's own ids with
the `wp_toc_reserved_ids` filter. An `id` an author typed is never changed.

### Stable links

Generated anchors follow the heading text and order: editing a heading, or
adding an earlier heading with the same text, changes them, and links to the
old anchor stop working. For sections people link to, give the heading its own
anchor (block editor: **Advanced → HTML anchor**); WP TOC always keeps it.

## Markup

Every element WP TOC outputs carries schema.org microdata.

```html
<nav class="wp-toc auto-toc-container" aria-labelledby="wp-toc-title-1"
     itemscope itemtype="https://schema.org/SiteNavigationElement"
     itemid="https://example.com/post/#wp-toc-1">
  <link itemprop="isPartOf" href="https://example.com/post/#webpage">
  <span class="wp-toc-title auto-toc-title" id="wp-toc-title-1" itemprop="name">Table of Contents</span>
  <ul class="wp-toc-list auto-toc-list">
    <li itemprop="hasPart" itemscope itemtype="https://schema.org/WebPageElement"
        itemid="https://example.com/post/#intro">
      <a href="#intro" itemprop="url"><span itemprop="name">Intro</span></a>
      <ul class="wp-toc-sublist">
        <li itemprop="hasPart" itemscope itemtype="https://schema.org/WebPageElement"
            itemid="https://example.com/post/#detail">
          <a href="#detail" itemprop="url"><span itemprop="name">Detail</span></a>
        </li>
      </ul>
    </li>
  </ul>
</nav>
<script type="application/ld+json" class="wp-toc-schema">…</script>
```

- With `collapsible="true"`, the title and list sit inside
  `<details class="wp-toc-details" open><summary class="wp-toc-summary">…</summary>…</details>`,
  and the `<nav>` also gets the class `wp-toc--collapsible`.
- With no title, `<meta itemprop="name" content="Table of Contents">` gives the name.
- With no current post (no permalink), `itemid` and `isPartOf` are left out.

| Class | Element | Notes |
|---|---|---|
| `wp-toc` | `<nav>` | |
| `auto-toc-container` | `<nav>` | Legacy, kept for existing custom CSS |
| `wp-toc--collapsible` | `<nav>` | Only when collapsible |
| `wp-toc-title`, `auto-toc-title` | Title `<span>` | |
| `wp-toc-details`, `wp-toc-summary` | `<details>`, `<summary>` | Only when collapsible |
| `wp-toc-list`, `auto-toc-list` | Top-level `<ul>` | |
| `wp-toc-sublist` | Nested `<ul>` | |
| `wp-toc-schema` | JSON-LD `<script>` | |

## Schema.org

Always on, for every element WP TOC outputs.

| Element | Type / property |
|---|---|
| The TOC (`<nav>`) | `SiteNavigationElement` (the type Rank Math's TOC block uses), `itemid` = permalink + `#wp-toc-N` |
| Its title | `name` (or a `<meta itemprop="name">` when there is no title) |
| Its page | `isPartOf` → the WebPage node `@id` |
| Each entry (`<li>`) | `WebPageElement` via `hasPart`, `itemid` = section URL |
| Each link | `url` (resolves to the absolute section URL) |
| Each label | `name` |
| Nested entries | `hasPart` of their parent entry |

JSON-LD, one block per post after its first TOC:

- A `WebPage` node with `url` = permalink and `hasPart` listing each section as
  a `WebPageElement` (`@id`, `name`, `url`), nested to match the TOC.
- Section `@id` and `url` = page URL + `#` + percent-encoded heading id. The
  microdata `itemid` of each entry is the same value, so the two descriptions merge.
- **Page URL:** the permalink; on page 2+ of a post split with `<!--nextpage-->`,
  that page's URL (built by WordPress's `_wp_link_page()`), since the heading
  only exists there.
- **`url` is left out when Yoast SEO or Rank Math is active.** The node merges
  into their WebPage, which already has a `url` (its canonical, which can
  differ from the permalink); sending a second one would contradict it.
- WebPage `@id`, chosen to match the SEO plugin's WebPage node so search engines merge them:
  - Rank Math active (`RANK_MATH_VERSION`) and the post is the page being viewed:
    Rank Math's canonical URL + `#webpage` (`RankMath\Paper\Paper::get()->get_canonical()`),
    which also covers a custom canonical. Falls back to the next rule if that call fails.
  - Yoast SEO active (`WPSEO_VERSION`): the post's permalink, also on page 2+.
  - Otherwise: permalink + `#webpage`.
- Encoded with `JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP`.
- No permalink (no current post) means no JSON-LD.

Headings inside the post content get no microdata: they are not inside a
schema.org scope, so properties on them would mean nothing, and WP TOC does
not change the content beyond heading ids.

## CSS

- File: `assets/css/wp-toc.css`, style handle `wp-toc`.
- Enqueued in `<head>` on singular views that will show a TOC (automatic or
  shortcode); otherwise enqueued when a TOC is rendered (printed in the
  footer). Not enqueued from admin requests.
- Printed inline when the folder has no public URL (see [Two ways to use it](#two-ways-to-use-it)).
- Custom properties: `--wp-toc-bg`, `--wp-toc-border`, `--wp-toc-indent`,
  `--wp-toc-scroll-offset` (default `2rem`, used for `scroll-margin-top` on headings with an id).

## Working alongside SEO plugins

WP TOC adds to what Yoast SEO and Rank Math output; it never replaces or
contradicts it:

- Its JSON-LD attaches to their WebPage node by `@id` and only adds `hasPart`
  (plus `@type: WebPage`, which every page type they use is a kind of); it
  leaves `url`, name and everything else to them.
- It skips the automatic TOC when their TOC block is in the post.
- It never adds TOC text to excerpts or descriptions.
- It never takes ids their or the theme's links use.

## Rank Math compatibility

Checked against Rank Math 1.0.268 and Rank Math Pro 3.0.49.

- **Content analysis (plugin setup only):** WP TOC adds itself to
  `rank_math/researches/toc_plugins`, so Rank Math's "Table of Contents" check
  passes. Rank Math checks this list against active plugins, so theme code
  can't be recognized.
- **Schema:** see the `@id` and `url` rules above.
- **Rank Math's TOC block:** when it is in a post, no automatic TOC is added, so
  there are never two. A `[wp_toc]` typed next to it is still shown.
- **Descriptions:** with Rank Math's `rank_math/paper/auto_generated_description/apply_shortcode`
  filter on, Rank Math runs shortcodes outside `the_content`. `[wp_toc]` then outputs only
  its HTML-comment placeholder, which Rank Math strips, so no TOC text leaks into descriptions.
  The automatic TOC is only added inside the main loop, so it never reaches them either.
- **Rank Math Pro video detection** runs `the_content` on save to find embeds. The TOC
  contains no video, iframe or embed markup, and the stylesheet is not enqueued in admin requests.
- **Existing anchors:** heading ids added by Rank Math's TOC block are kept.

## Hooks

| Hook | Type | Arguments | Purpose |
|---|---|---|---|
| `wp_toc_auto_insert` | filter | `array $settings, int $post_id` | Automatic TOC settings (see above). |
| `wp_toc_enabled` | filter | `bool $enabled, int $post_id` | Return false to skip a post entirely; placeholders are removed. |
| `wp_toc_default_atts` | filter | `array $defaults` | Change default shortcode attributes. |
| `shortcode_atts_wp_toc` | filter | core `shortcode_atts` arguments | Core filter, for both shortcode tags. |
| `wp_toc_items` | filter | `array $items, int $post_id` | Flat list of `array( 'level', 'id', 'text' )` before nesting. Entries without an id or text are skipped. |
| `wp_toc_heading_id` | filter | `string $slug, string $text, int $level` | Generated id, before it is made unique. |
| `wp_toc_reserved_ids` | filter | `string[] $reserved, int $post_id` | Ids headings must not take (see [Reserved ids](#reserved-ids)). |
| `wp_toc_other_toc_markers` | filter | `string[] $markers` | Class names of other TOC tools; when one is in the post, no automatic TOC is added. |
| `wp_toc_html` | filter | `string $html, array $tree, array $atts, int $post_id` | Final TOC markup. |
| `wp_toc_schema` | filter | `array $data, array $tree, int $post_id` | Change the JSON-LD data. |
| `wp_toc_schema_webpage_id` | filter | `string $id, string $permalink, int $post_id` | `@id` of the WebPage node. |
| `wp_toc_base_url` | filter | `string $url, string $file` | URL of the WP TOC folder; `''` inlines the CSS. |
| `wp_toc_load_css` | filter | `bool $load` | Return false to not load the stylesheet (e.g. the theme styles the TOC). |
| `wp_toc_content_filter_priority` | filter | `int $priority` | Priority of the `the_content` filter (default 99, minimum 12). Read when WP TOC loads, so add it before loading WP TOC. |

## How it works

1. Where the automatic TOC applies, or where `[wp_toc]` is typed, WP TOC needs a
   TOC. The shortcode runs at `the_content` priority 11 (core `do_shortcode`) and
   outputs only a placeholder: `<!--wp-toc:HEX-->`, where HEX is the hex-encoded
   JSON of its attributes.
2. `WP_TOC::filter_content()` runs at priority 99, after every block and shortcode.
   It reads every heading and id with `WP_HTML_Tag_Processor` and writes missing
   heading ids (nothing else in the content changes).
3. For the automatic TOC, it inserts a placeholder at the chosen position, using
   byte offsets from `WP_TOC_HTML_Processor` (a small subclass that reads the
   HTML API's bookmarks).
4. It builds the tree and replaces each placeholder with the TOC markup, plus one
   JSON-LD block. A `<p>` wrapped around a placeholder is removed with it.

Limitations:

- The TOC only covers content passed through `the_content`. Shortcodes rendered
  elsewhere (widgets, `do_shortcode()` in templates) output nothing.
- Generated ids follow `sanitize_title()` rules; use `wp_toc_heading_id` to change them.

## Files

| Path | Purpose |
|---|---|
| `wp-toc.php` | Header (used in plugin setup), double-load and PHP 8.4 checks, constants, bootstrap |
| `includes/class-wp-toc.php` | All logic (`WP_TOC` class) |
| `includes/class-wp-toc-html-processor.php` | `WP_TOC_HTML_Processor`: HTML API with byte offsets, for placing the automatic TOC |
| `assets/css/wp-toc.css` | Default styles |
| `languages/wp-toc.pot` | Translation template |
| `readme.txt` | WordPress.org readme (plugin setup) |
| `README.md` | GitHub readme |
| `SSOT.md` | This file |
| `UPGRADING.md` | Upgrade notes between versions |
| `CHANGELOG.md` | Release history |
| `LICENSE` | GNU GPL v3 |
| `LICENSE-MIT-ORIGINAL` | MIT notice for the original code |
| `tests/` | PHPUnit tests (real WordPress core code, no database); `tests/fixtures/` holds a stand-in for Rank Math's `Paper` class; `tests/php-version-guard.php` checks the PHP 8.4 guard on old PHP |
| `bin/install-wp-core.sh` | Downloads the WordPress core files the tests load into `.cache/wordpress` |
| `composer.json`, `phpunit.xml.dist`, `phpcs.xml.dist` | Dev tooling |
| `.github/workflows/ci.yml` | CI: lint and PHPCS on PHP 8.4; PHPUnit on PHP 8.4 and 8.5 with WordPress 7.1, plus 8.4 with WordPress 6.7; the PHP guard on PHP 7.4 and 8.3 |

Only `wp-toc.php`, `includes/`, `assets/`, `languages/` and the license files are
needed at runtime; the rest is excluded from release archives (`.gitattributes`).

## Release checklist

1. Update the version in this file.
2. Update `Version` in the `wp-toc.php` header and `WP_TOC_VERSION`.
3. Update `Stable tag` in `readme.txt`, and its changelog and upgrade notice.
4. Add the release to `CHANGELOG.md` (date as D-Month-YYYY), and to `UPGRADING.md` if anything changes for users.
5. Regenerate `languages/wp-toc.pot` if strings changed.
6. Run `composer test` and `composer phpcs` on PHP 8.4+, and `composer test-guard` on an older PHP.
7. Tag the release `vX.Y.Z`.
