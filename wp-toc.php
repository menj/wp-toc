<?php
/**
 * Plugin Name:       WP TOC
 * Plugin URI:        https://github.com/menj/wp-toc
 * Description:       Lightweight, server-side table of contents. Adds anchors to headings and renders a nested, accessible list with schema.org markup via the [wp_toc] shortcode.
 * Version:           1.1.0
 * Requires at least: 6.5
 * Requires PHP:      7.4
 * Author:            Mohd Elfie Nieshaem Juferi
 * Author URI:        https://menj.blog
 * License:           GPL-3.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       wp-toc
 * Domain Path:       /languages
 *
 * @package WP_TOC
 *
 * Copyright (C) 2026 Mohd Elfie Nieshaem Juferi
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * Based on original work by Ikram Hawramani (https://hawramani.com),
 * released under the MIT License; see LICENSE-MIT-ORIGINAL.
 */

defined( 'ABSPATH' ) || exit;

define( 'WP_TOC_VERSION', '1.1.0' );
define( 'WP_TOC_FILE', __FILE__ );

require_once __DIR__ . '/includes/class-wp-toc.php';

WP_TOC::init();
