# Changelog

All notable changes to WP TOC are documented here. The format is based on
[Keep a Changelog](https://keepachangelog.com/en/1.1.0/), and the project uses
[Semantic Versioning](https://semver.org/).

## [Unreleased]

## [1.1.0] - 2026-11-06

Upgrade notes: [UPGRADING.md](UPGRADING.md#from-10x-to-110).

### Fixed
- The table of contents never appeared and headings never got ids. The shortcode ran (priority 11) before the code that read the headings (priority 99). The shortcode now outputs a placeholder that is filled in after the headings are read.
- Broken list markup: a stray `</li>` before the first item, unclosed lists when the last heading was nested, and unbalanced tags when levels were skipped (h2 → h4) or the first heading was deeper than later ones.
- The whole post was re-serialized through DOMDocument, which wrapped loose text in `<p>`, turned characters into entities and could alter HTML5, SVG and script markup. Only heading `id` attributes are now added; nothing else changes.
- Existing heading ids were overwritten, breaking links to them. They are now kept.
- Generated ids could clash with other ids in the post. They are now unique across all ids.
- `libxml_use_internal_errors()` was left switched on for the rest of the request.
- Heading text with line breaks or extra spaces produced messy TOC labels; whitespace is now collapsed.
- The `tags` attribute accepted any value. Only `h1`–`h6` are accepted now.

### Added
- `[wp_toc]` shortcode. `[auto_toc]` keeps working as an alias.
- Attributes: `depth`, `min`, `collapsible`, `collapsed`, `schema`.
- Skip a heading with the class `no-toc` or a `data-no-toc` attribute. Headings inside `<template>` are always skipped.
- Readable ids for non-Latin headings (`#مرحبا-بالعالم` instead of `#%d9%85…`).
- schema.org JSON-LD: a `WebPage` node with nested `WebPageElement` sections, linked to Yoast SEO's or Rank Math's page node by `@id`. With Rank Math, the `@id` uses Rank Math's canonical URL.
- Rank Math compatibility: registers with `rank_math/researches/toc_plugins` so Rank Math's "Table of Contents" analysis check passes.
- Filters: `wp_toc_enabled`, `wp_toc_default_atts`, `wp_toc_items`, `wp_toc_heading_id`, `wp_toc_html`, `wp_toc_schema`, `wp_toc_schema_webpage_id`, `wp_toc_load_css`, `wp_toc_content_filter_priority`.
- `wp-toc-*` CSS classes alongside the existing `auto-toc-*` ones.
- `scroll-margin-top` on headings, so sticky headers don't cover them (`--wp-toc-scroll-offset`).
- Translation support: text domain `wp-toc`, `languages/wp-toc.pot`.
- `readme.txt`, `SSOT.md`, `UPGRADING.md` and this changelog.
- PHPUnit tests, PHPCS (WordPress Coding Standards, PHPCompatibilityWP) and GitHub Actions CI.

### Changed
- The TOC is a `<nav>` landmark labelled by its title (or `aria-label` when the title is empty), instead of a `<div>`.
- The title is a `<span>` instead of a `<div>`.
- Nested lists have the class `wp-toc-sublist`.
- Entries added through `wp_toc_items` without an `id` or `text` are skipped instead of rendering empty links.
- Styles moved from an inline `<style>` printed on every page to `assets/css/wp-toc.css`, loaded only where a TOC is used. Colours now follow the theme's text colour.
- Code moved into the `WP_TOC` class in `includes/class-wp-toc.php`. The functions `wp_auto_toc_prepare_content`, `wp_auto_toc_shortcode` and `wp_auto_toc_styles` and the `$wp_auto_toc_items` global were removed.
- Requires WordPress 6.5 and PHP 7.4.
- Plugin renamed to WP TOC (slug `wp-toc`, main file `wp-toc.php`).
- License changed to GPL-3.0-or-later. The original MIT notice is kept in `LICENSE-MIT-ORIGINAL`.

## [1.0.0] - 2025

- Initial release by Ikram Hawramani: `[auto_toc]` shortcode with `tags` and `title` attributes.

[Unreleased]: https://github.com/menj/wp-toc/compare/v1.1.0...HEAD
[1.1.0]: https://github.com/menj/wp-toc/releases/tag/v1.1.0
