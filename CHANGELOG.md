# Changelog

All notable changes to WP TOC are documented here. The format is based on
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), the project uses
[Semantic Versioning](https://semver.org/), and dates are written D-Month-YYYY.

WP TOC adds an automatic, schema.org-annotated table of contents to long posts.
It is meant to be built into a theme, and also works as a plugin. See
[README.md](README.md) for what it does.

## [1.1.0] - Unreleased

A rewrite. Upgrade notes: [UPGRADING.md](UPGRADING.md#from-10x-to-110).

### Added
- **Automatic table of contents.** Posts and pages with 3 or more headings get one before their first heading, with no shortcode and no settings. Configure or turn it off with the `wp_toc_auto_insert` filter (post types, position, attributes).
- **Theme code or plugin, same files.** Load `wp-toc.php` from a theme's `functions.php`, or activate it as a plugin. WP TOC detects which, finds its own URL (child theme, parent theme, `wp-content`, or plugin), inlines its CSS when the folder has no public URL, loads only once if both are present, and still registers its shortcodes when loaded after `init`.
- **schema.org on every output element.** Microdata on the table of contents (`SiteNavigationElement`, with `name` and `isPartOf`) and on each entry (`WebPageElement` with `name` and `url`, nested through `hasPart`), plus a JSON-LD `WebPage` whose `hasPart` lists the sections. Section `@id`s match the microdata `itemid`s, and the WebPage `@id` matches Yoast SEO's or Rank Math's.
- **Rank Math compatibility** (checked against 1.0.268 and Pro 3.0.49): the WebPage `@id` uses Rank Math's canonical URL; the table of contents never reaches Rank Math's auto-generated descriptions; in the plugin setup, Rank Math's "Table of Contents" analysis check recognizes WP TOC.
- `[wp_toc]` shortcode for placing the table of contents by hand; `[auto_toc]` keeps working as an alias. A shortcode in a post replaces the automatic table of contents.
- Attributes: `depth`, `min`, `collapsible` (native `<details>`), `collapsed`.
- Skip a heading with the class `no-toc` or a `data-no-toc` attribute. Headings inside `<template>` are always skipped.
- Readable anchors for non-Latin headings (`#مرحبا-بالعالم` instead of `#%d9%85…`).
- Filters: `wp_toc_auto_insert`, `wp_toc_enabled`, `wp_toc_default_atts`, `wp_toc_items`, `wp_toc_heading_id`, `wp_toc_html`, `wp_toc_schema`, `wp_toc_schema_webpage_id`, `wp_toc_base_url`, `wp_toc_load_css`, `wp_toc_content_filter_priority`.
- `wp-toc-*` CSS classes alongside the existing `auto-toc-*` ones.
- `scroll-margin-top` on headings, so sticky headers don't cover them (`--wp-toc-scroll-offset`).
- Translation support: text domain `wp-toc`, `languages/wp-toc.pot`.
- `readme.txt`, `SSOT.md`, `UPGRADING.md` and this changelog.
- PHPUnit tests, PHPCS (WordPress Coding Standards, PHPCompatibilityWP), a PHP-version guard check, and GitHub Actions CI.

### Fixed
- The table of contents never appeared and headings never got ids. The shortcode ran (priority 11) before the code that read the headings (priority 99). It now outputs a placeholder that is filled in after the headings are read.
- Broken list markup: a stray `</li>` before the first item, unclosed lists when the last heading was nested, and unbalanced tags when levels were skipped (h2 → h4) or the first heading was deeper than later ones.
- The whole post was re-serialized through DOMDocument, which wrapped loose text in `<p>`, turned characters into entities and could alter HTML5, SVG and script markup. Only heading `id` attributes are now added; nothing else changes.
- Existing heading ids were overwritten, breaking links to them. They are now kept.
- Generated ids could clash with other ids in the post. They are now unique across all ids.
- `libxml_use_internal_errors()` was left switched on for the rest of the request.
- Heading text with line breaks or extra spaces produced messy labels; whitespace is now collapsed.
- The `tags` attribute accepted any value. Only `h1`–`h6` are accepted now.
- Entries added through `wp_toc_items` without an `id` or `text` are skipped instead of rendering empty links.

### Changed
- **Requires PHP 8.4 or later**, strictly: on older PHP, WP TOC does not load and shows administrators a notice. The code uses `strict_types` and PHP 8.3/8.4 features.
- **Requires WordPress 6.7 or later**, the first version that supports PHP 8.4.
- The table of contents is a `<nav>` landmark labelled by its title (or `aria-label` when the title is empty), instead of a `<div>`. The title is a `<span>`; nested lists have the class `wp-toc-sublist`; labels are wrapped in `<span itemprop="name">`.
- Styles moved from an inline `<style>` printed on every page to `assets/css/wp-toc.css`, loaded only where a table of contents is shown. Colours follow the theme's text colour.
- Code moved into the `WP_TOC` class in `includes/`. The functions `wp_auto_toc_prepare_content`, `wp_auto_toc_shortcode` and `wp_auto_toc_styles` and the `$wp_auto_toc_items` global were removed.
- Renamed to WP TOC (slug `wp-toc`, main file `wp-toc.php`).
- License changed to GPL-3.0-or-later. The original MIT notice is kept in `LICENSE-MIT-ORIGINAL`.

## [1.0.0] - 2025

- Initial release by Ikram Hawramani: `[auto_toc]` shortcode with `tags` and `title` attributes, for pasting into `functions.php` or installing as a plugin.

[1.1.0]: https://github.com/menj/wp-toc/compare/375d32f...main
