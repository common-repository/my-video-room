<?php
/**
 * MyVideoRoom WordPress Plugin
 *
 * @package             MyVideoRoomPlugin
 * @license             GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:         MyVideoRoom
 * Plugin URI:          https://clubcloud.tech
 * Text Domain:         myvideoroom
 * Description:         Integrates the MyVideoRoom Service into WordPress
 * Version:             3.0.2
 * Requires PHP:        7.4
 * Requires at least:   5.6
 * Author:              ClubCloud
 * Author URI:          https://clubcloud.tech/
 * License:             GPLv2 or later
 * License URI:         https://www.gnu.org/licenses/gpl-2.0.html
 */

declare( strict_types=1 );

namespace MyVideoRoomPlugin;

use MyVideoRoomPlugin\Library\Module;

if ( ! \defined( 'WPINC' ) ) {
	die;
}

if ( ! \function_exists( 'get_plugin_data' ) ) {
	include_once ABSPATH . 'wp-admin/includes/plugin.php';
}

if ( ! \class_exists( Plugin::class ) ) {
	/**
	 * Autoloader for classes in the MyVideoRoom Plugin
	 *
	 * @param string $class_name The name of the class to autoload.
	 *
	 * @return boolean
	 */
	function autoloader( string $class_name ): bool {
		if ( \strpos( $class_name, 'MyVideoRoomPlugin' ) === 0 ) {
			$src_location = \realpath( \plugin_dir_path( __FILE__ ) ) . DIRECTORY_SEPARATOR;

			$file_name = \str_replace( 'MyVideoRoomPlugin\\', '', $class_name );
			$file_name = \strtolower( $file_name );

			$file_name = \str_replace( '\\', DIRECTORY_SEPARATOR, $file_name ) . '.php';

			$path     = ( \pathinfo( $file_name ) );
			$location = \realpath( $src_location . $path['dirname'] ) . '/class-' . $path['basename'];

			if ( ! \file_exists( $location ) ) {
				return false;
			}

			return (bool) include_once $location;
		}

		return false;
	}

	\spl_autoload_register( 'MyVideoRoomPlugin\autoloader' );

	\add_action( 'plugins_loaded', array( Module::class, 'load_built_in_modules' ) );

	\add_action( 'init', array( Plugin::class, 'init' ) );

	\register_activation_hook( __FILE__, array( Activation::class, 'activate' ) );
	\register_deactivation_hook( __FILE__, array( Activation::class, 'deactivate' ) );
	\register_uninstall_hook( __FILE__, array( Activation::class, 'uninstall' ) );
}
