<?php
/*
 * Plugin Name: Webp Images
 * Text Domain: webp-images
 * Description: Convert uploaded images to webp format and conditionally serve them to supported browsers.
 * Author: Code Parrots
 * Author URI: https://www.codeparrots.com
 * License: GPLv2 or later
 * Version: 1.0.0
 */

/*
 Copyright (C)  2018 Code Parrots

 This program is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License along
 with this program; if not, write to the Free Software Foundation, Inc.,
 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301 USA.
 */

/* Check & Quit */
if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

/* constants */
define( 'WEBP_IMAGES_BASE', plugin_basename( __FILE__ ) );
define( 'WEBP_IMAGES_URL', plugin_dir_url( __FILE__ ) );
define( 'WEBP_IMAGES_PATH', plugin_dir_path( __FILE__ ) );
define( 'WEBP_IMAGES_MIN_WP', '4.0' );
define( 'WEBP_IMAGES_VERSION', '1.0.0' );

/* loader */
add_action( 'plugins_loaded', [ 'WebP_Images', 'instance' ] );

/* uninstall */
register_uninstall_hook( __FILE__, [ 'WebP_Images', 'handle_uninstall_hook' ] );

/* activation */
register_activation_hook( __FILE__, [ 'WebP_Images', 'handle_activation_hook' ] );

/**
 * Load Translations
 *
 * @since 1.0.0
 */
function webp_images_i18n() {

	load_plugin_textdomain( 'webp-images', false, dirname( plugin_basename( __FILE__ ) ) . '/i18n' );

}
add_action( 'plugins_loaded', 'webp_images_i18n' );

/* autoload init */
spl_autoload_register( 'webp_images_autoload' );

/* autoload funktion */
function webp_images_autoload( $class ) {

	$valid_classes = [
		'WebP_Images',
		'WebP_Images_Rewriter',
		'WebP_Images_Settings',
		'WebP_Images_Handle_Uploads',
		'WebP_Images_Media_Fields',
		'WebP_Images_AJAX_Handler',
		'WebP_Images_Upload_Paths',
	];

	if ( ! in_array( $class, $valid_classes, true ) ) {

		return;

	}

	require_once(
		sprintf(
			'%s/lib/classes/class-%s.php',
			dirname( __FILE__ ),
			str_replace( '_', '-', strtolower( $class ) )
		)
	);

}
