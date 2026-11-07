# Upgrading WP TOC

What changes for your site when you move between versions. The full list of
changes is in [CHANGELOG.md](CHANGELOG.md); what WP TOC does is explained in
[README.md](README.md).

## From 1.0.x to 1.1.0

1.1.0 is a rewrite. The biggest change: **the table of contents now appears
automatically** on posts and pages with 3 or more headings. You no longer need
a shortcode.

### Before you upgrade

- **Check your PHP version: 8.4 or later is required.** On older PHP, WP TOC
  does not load at all; administrators see a notice saying why. Your host's
  control panel or **Tools → Site Health → Info → Server** shows the version.
- **WordPress 6.7 or later is required** (the first version that supports PHP 8.4).
- **Remove the old code.** If you pasted the 1.0 code into `functions.php`,
  delete it. If you installed `wp-auto-toc.php` as a plugin, deactivate and
  delete it. The main file is now `wp-toc.php`, so WordPress will not treat
  1.1.0 as an update of the old plugin.

### Install 1.1.0

- **In a theme (main use):** copy the `wp-toc` folder into the theme and add
  `require_once get_stylesheet_directory() . '/wp-toc/wp-toc.php';` to `functions.php`.
- **As a plugin:** put the folder in `wp-content/plugins/` and activate it.

### What you will notice

- **Long posts get a table of contents automatically**, placed before their
  first heading. Posts with fewer than 3 headings don't. To limit it to some
  post types, move it, or turn it off, use the `wp_toc_auto_insert` filter
  (see [README.md](README.md#choosing-where-it-appears)).
- **Posts that already use `[auto_toc]` keep their table of contents where the
  shortcode is**, with the same `tags` and `title` attributes. The shortcode
  replaces the automatic one, so there is never a second copy. New posts can
  use `[wp_toc]`, or nothing at all.
- **Headings that already have an `id` keep it**, so existing links still work.
  Headings without one get the same kind of anchor as before, e.g.
  `<h2>My Heading</h2>` → `#my-heading`.
- **schema.org data is added**: microdata on the table of contents and a
  `<script type="application/ld+json" class="wp-toc-schema">` after it. There is
  no option to turn it off.
- **Smooth scrolling and a heading offset**: pages with a table of contents
  scroll smoothly (unless the reader prefers reduced motion), and headings with
  an id get `scroll-margin-top: 2rem`. Change the offset with `--wp-toc-scroll-offset`.
- In 1.0.x the table of contents never actually appeared, because of a bug in
  the order of operations. If you were used to seeing nothing, this is new.

### Check if you customised anything

**CSS.** The old classes are still output, but the markup changed:

| 1.0.x | 1.1.0 |
|---|---|
| `<div class="auto-toc-container">` | `<nav class="wp-toc auto-toc-container" aria-labelledby="…" itemscope …>` |
| `<div class="auto-toc-title">` | `<span class="wp-toc-title auto-toc-title" id="…" itemprop="name">` |
| `<ul class="auto-toc-list">` | `<ul class="wp-toc-list auto-toc-list">` |
| nested `<ul>` | `<ul class="wp-toc-sublist">` |
| `<li><a>Label</a>` | `<li itemprop="hasPart" itemscope …><a itemprop="url"><span itemprop="name">Label</span></a>` |

- Selectors such as `div.auto-toc-container` or `div.auto-toc-title` no longer match. Drop the `div` part.
- Link labels are now wrapped in a `<span>`. Selectors such as `.auto-toc-list a` still match.
- The title is a `<span>`; the stylesheet sets `display: block`. If you turn the
  stylesheet off, add `.wp-toc-title { display: block; }` to your own CSS.
- The styles are now in `assets/css/wp-toc.css` (handle `wp-toc`) instead of an
  inline `<style>` in every page's `<head>`. To remove them:
  `add_filter( 'wp_toc_load_css', '__return_false' );`
  (1.0.x needed `remove_action( 'wp_head', 'wp_auto_toc_styles' );`, which no longer does anything.)
- Use the `wp-toc-*` classes for new CSS. The `auto-toc-*` classes may be removed in 2.0.

**PHP.** These were removed:

| Removed | Use instead |
|---|---|
| `wp_auto_toc_prepare_content()` | `WP_TOC::filter_content()`; use the `wp_toc_enabled` filter to skip posts |
| `wp_auto_toc_shortcode()` | `WP_TOC::shortcode()`; use `wp_toc_html` to change the output |
| `wp_auto_toc_styles()` | `wp_toc_load_css` filter |
| `global $wp_auto_toc_items` | `wp_toc_items` filter |
