# Upgrading WP TOC

Notes for moving between versions. The full list of changes is in
[CHANGELOG.md](CHANGELOG.md).

## From 1.0.x to 1.1.0

### Before you upgrade

- **WordPress 6.5 or later** and **PHP 7.4 or later** are now required.
  On older WordPress the plugin does nothing: placeholders are removed and headings are left alone.
- **If you pasted the code into `functions.php`,** remove it before activating the plugin.
  The plugin now has several files and is installed like any other plugin.
  Running both would register `[auto_toc]` twice.
- **Main file renamed** from `wp-auto-toc.php` to `wp-toc.php`, and the folder should be `wp-toc`.
  If you installed the old file as a plugin, deactivate and delete it, then install the new folder.
  WordPress treats the renamed file as a different plugin, so it will not upgrade in place.

### Nothing to change in your posts

- `[auto_toc]` keeps working, with the same `tags` and `title` attributes.
  New posts can use `[wp_toc]`.
- Headings that already have an `id` keep it, so existing links to them still work.
  Headings without one get the same kind of slug as before, e.g. `<h2>My Heading</h2>` → `#my-heading`.
- The TOC now actually appears. In 1.0.x, a bug in the order of operations meant it rendered nothing.

### Check if you customised anything

**CSS.** The old classes are still output, but the markup changed:

| 1.0.x | 1.1.0 |
|---|---|
| `<div class="auto-toc-container">` | `<nav class="wp-toc auto-toc-container" aria-labelledby="…">` |
| `<div class="auto-toc-title">` | `<span class="wp-toc-title auto-toc-title" id="…">` |
| `<ul class="auto-toc-list">` | `<ul class="wp-toc-list auto-toc-list">` |
| nested `<ul>` | `<ul class="wp-toc-sublist">` |

- Selectors such as `div.auto-toc-container` or `div.auto-toc-title` no longer match. Drop the `div` part.
- The title is now inline by default; the plugin's CSS sets `display: block`. If you turned the plugin's CSS off, add `.wp-toc-title { display: block; }` to your own.
- The plugin's styles are now in `assets/css/wp-toc.css` (handle `wp-toc`) instead of an inline `<style>` in every page's `<head>`.
  To remove them: `add_filter( 'wp_toc_load_css', '__return_false' );`
  (1.0.x needed `remove_action( 'wp_head', 'wp_auto_toc_styles' );`, which no longer does anything.)
- New CSS should use the `wp-toc-*` classes. The `auto-toc-*` classes may be removed in 2.0.

**PHP.** These were removed:

| Removed | Use instead |
|---|---|
| `wp_auto_toc_prepare_content()` | `WP_TOC::filter_content()`; use the `wp_toc_enabled` filter to skip posts |
| `wp_auto_toc_shortcode()` | `WP_TOC::shortcode()`; use `wp_toc_html` to change the output |
| `wp_auto_toc_styles()` | `wp_toc_load_css` filter |
| `global $wp_auto_toc_items` | `wp_toc_items` filter |

### New output you may notice

- **JSON-LD:** a `<script type="application/ld+json" class="wp-toc-schema">` follows the first TOC in each post.
  Turn it off with `schema="false"` or `add_filter( 'wp_toc_schema', '__return_false' );`.
- **Smooth scrolling and heading offset:** pages with a TOC scroll smoothly (unless the reader prefers reduced motion), and headings with an id get `scroll-margin-top: 2rem`.
  Change the offset with `--wp-toc-scroll-offset`.
