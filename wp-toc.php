<?php
/**
 * Plugin Name:       WP TOC
 * Plugin URI:        https://github.com/menj/wp-toc
 * Description:       Automatic table of contents for long posts, with schema.org structured data. Works as theme code (load it from functions.php) or as a plugin.
 * Version:           1.1.0
 * Requires at least: 6.7
 * Requires PHP:      8.4
 * Author:            MENJ
 * Author URI:        https://menj.blog
 * License:           GPL-3.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       wp-toc
 * Domain Path:       /languages
 *
 * @package WP_TOC
 *
 * Copyright (C) 2026 MENJ
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

/*
 * WP TOC works two ways, from the same files:
 *
 * - As a plugin: put this folder in wp-content/plugins/ and activate it.
 * - Inside a theme: copy this folder into the theme and load it from
 *   functions.php, e.g.
 *       require_once get_stylesheet_directory() . '/wp-toc/wp-toc.php';
 *   The plugin header above is just a comment in that case.
 *
 * See README.md and SSOT.md.
 */

defined( 'ABSPATH' ) || exit;

// Already loaded, e.g. bundled in a theme while the plugin is also active. The first copy wins.
if ( defined( 'WP_TOC_VERSION' ) ) {
	return;
}

/*
 * WP TOC requires PHP 8.4 or later and does not load at all on older PHP.
 * This file must stay parseable by old PHP versions so the check below can
 * run and explain why; the code in includes/ is only loaded after it passes.
 */
if ( version_compare( PHP_VERSION, '8.4', '<' ) ) {
	if ( ! function_exists( 'wp_toc_php_version_notice' ) ) {
		/**
		 * Admin notice shown when PHP is too old for WP TOC.
		 *
		 * @return void
		 */
		function wp_toc_php_version_notice() {
			if ( ! current_user_can( 'activate_plugins' ) && ! current_user_can( 'switch_themes' ) ) {
				return;
			}

			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html(
					sprintf(
						/* translators: 1: required PHP version, 2: current PHP version. */
						__( 'WP TOC requires PHP %1$s or later. This site runs PHP %2$s, so WP TOC is not loaded.', 'wp-toc' ),
						'8.4',
						PHP_VERSION
					)
				)
			);
		}
	}
	add_action( 'admin_notices', 'wp_toc_php_version_notice' );
	return;
}

define( 'WP_TOC_VERSION', '1.1.0' );
define( 'WP_TOC_FILE', __FILE__ );
define( 'WP_TOC_DIR', __DIR__ );

require_once __DIR__ . '/includes/class-wp-toc-html-processor.php';
require_once __DIR__ . '/includes/class-wp-toc.php';

WP_TOC::init();
