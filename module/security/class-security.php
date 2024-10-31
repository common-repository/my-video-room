<?php
/**
 * Class Security- Provides the Render Block Host Function for Security.
 *
 * @package file class-security.php.
 */

namespace MyVideoRoomPlugin\Module\Security;

use MyVideoRoomPlugin\Factory;
use MyVideoRoomPlugin\DAO\ModuleConfig;
use MyVideoRoomPlugin\DAO\RoomMap;
use MyVideoRoomPlugin\Library\Dependencies;
use MyVideoRoomPlugin\Module\Security\DAO\SecurityVideoPreference as SecurityVideoPreferenceDao;
use MyVideoRoomPlugin\Module\Security\Library\PageFilters;
use MyVideoRoomPlugin\Entity\MenuTabDisplay;
use MyVideoRoomPlugin\Module\Security\Library\SecurityNotifications;
use MyVideoRoomPlugin\Module\Security\Library\SecurityRoomHelpers;
use MyVideoRoomPlugin\Module\Security\Shortcode\SecurityVideoPreference;

/**
 * Class Security- Provides the Render Block Host Function for Security.
 */
class Security {

	const TABLE_NAME_SECURITY_CONFIG = 'myvideoroom_security_config';
	const MODULE_SECURITY_NAME       = 'security-module';
	const MODULE_SECURITY_ENTITY     = 'security-entity';
	const HOST_TABLE_SUFFIX          = 'host-permission';
	const MULTI_ROOM_HOST_SUFFIX     = Dependencies::MULTI_ROOM_HOST_SUFFIX;
	const MODULE_SECURITY_ID         = Dependencies::MODULE_SECURITY_ID; // Proxied to Main Core so Activation state can be queried by Core Modules.
	const MODULE_SECURITY_ENTITY_ID  = Dependencies::MODULE_SECURITY_ENTITY_ID;
	const MODULE_SECURITY_ADMIN_PAGE = 'view-admin-settings-security';
	const MODULE_SECURITY_DISPLAY    = ' Advanced Room Permissions';
	const PERMISSIONS_TABLE          = 'security-default-permissions';

	/**
	 * Initialise On Module Activation.
	 * Once off functions for activating Module.
	 */
	public function activate_module() {
		// Install Room Security Config table.
		Factory::get_instance( SecurityVideoPreferenceDao::class )->install_security_config_table();

		// Register and Activate Module In Module Table.
		$module_config = Factory::get_instance( ModuleConfig::class );

		$module_config->register_module_in_db(
			self::MODULE_SECURITY_NAME,
			self::MODULE_SECURITY_ID,
			true
		);

		$module_config->register_module_in_db(
			self::MODULE_SECURITY_ENTITY,
			self::MODULE_SECURITY_ENTITY_ID,
			true
		);

		$module_config->update_enabled_status( self::MODULE_SECURITY_ID, true );
		$module_config->update_enabled_status( self::MODULE_SECURITY_ENTITY_ID, true );

	}

	/**
	 * De-Initialise On Module De-activation.
	 * Once off functions for activating Module.
	 */
	public function de_activate_module() {
		Factory::get_instance( ModuleConfig::class )->update_enabled_status( self::MODULE_SECURITY_ID, false );
		Factory::get_instance( ModuleConfig::class )->update_enabled_status( self::MODULE_SECURITY_ENTITY_ID, false );
	}

	/**
	 * Runtime Shortcodes and Setup
	 * Required for Normal Runtime.
	 */
	public function init() {
		// Turn on Init Filters.
		Factory::get_instance( PageFilters::class )->runtime_filters();
		$this->security_menu_setup();
		add_filter( 'myvideoroom_sitevideo_admin_page_menu', array( $this, 'render_security_sitevideo_tabs' ), 20, 2 );

		// Add Permissions Menu to Main Frontend Template.
		add_filter(
			'myvideoroom_main_template_render',
			array(
				$this,
				'render_shortcode_security_permissions_tab',
			),
			40,
			4
		);

		// Add Permissions Icons Status to Main Shortcode Header.
		\add_filter(
			'myvideoroom_template_icon_section',
			array(
				Factory::get_instance( SecurityNotifications::class ),
				'add_default_video_icons_to_header',
			),
			10,
			4
		);

		// Add Permissions Notification of Status to Main Permissions SecurityVideoPreference Normal and Admin Forms.
		\add_filter(
			'myvideoroom_security_admin_preference_buttons',
			array(
				Factory::get_instance( SecurityNotifications::class ),
				'show_security_admin_status',
			),
			10,
			3
		);
		\add_filter(
			'myvideoroom_security_settings_preference_buttons',
			array(
				Factory::get_instance( SecurityNotifications::class ),
				'show_security_settings_status',
			),
			10,
			3
		);
		\add_filter(
			'myvideoroom_security_roomhosts_preference_buttons',
			array(
				Factory::get_instance( SecurityNotifications::class ),
				'show_security_roomhosts_status',
			),
			10,
			3
		);
		\add_filter(
			'myvideoroom_sitevideo_control_panel_view',
			array(
				Factory::get_instance( SecurityNotifications::class ),
				'show_security_sitewide_status',
			),
			10,
			1
		);

		// Add Config Page to Main Room Manager.
		add_filter(
			'myvideoroom_permissions_manager_menu',
			array(
				Factory::get_instance( SecurityRoomHelpers::class ),
				'render_security_admin_settings_page',
			),
			10,
			1
		);

		// Actions for Disable Feature Module (Enable is in Defaults as it wont run if module is off).
		\add_action(
			'myvideoroom_disable_feature_module',
			array(
				Factory::get_instance( SecurityRoomHelpers::class ),
				'security_disable_feature_module',
			)
		);

		\add_filter(
			'myvideoroom_site_video_user_host_status',
			array(
				Factory::get_instance( PageFilters::class ),
				'current_user_is_host',
			),
			10,
			2
		);
	}

	/**
	 * Setup of Module Menu
	 */
	public function security_menu_setup() {
		add_action( 'mvr_module_submenu_add', array( $this, 'security_menu_button' ) );
	}

	/**
	 * Render Module Menu.
	 */
	public function security_menu_button() {
		$name = self::MODULE_SECURITY_DISPLAY;
		$slug = self::MODULE_SECURITY_NAME;
		//phpcs:ignore --WordPress.WP.I18n.NonSingularStringLiteralText - $name is a constant text literal already.
		$display = esc_html__( $name, 'myvideoroom' );
		echo '<a class="mvr-menu-header-item" href="?page=my-video-room-extras&tab=' . esc_html( $slug ) . '">' . esc_html( $display ) . '</a>';
	}

	/**
	 * Render Security Admin Page.
	 *
	 * @return string
	 */
	public function render_security_admin_page(): string {
		return ( require __DIR__ . '/views/view-settings-security.php' )();
	}

	/**
	 * Render Security Admin Tabs.
	 *
	 * @param array $input   - the inbound menu.
	 * @param int   $room_id - the room identifier.
	 *
	 * @return array - outbound menu.
	 */
	public function render_security_sitevideo_tabs( array $input, int $room_id ): array {
		$room_object = Factory::get_instance( RoomMap::class )->get_room_info( $room_id );

		if ( ! $room_object ) {
			return $input;
		}

		$room_name = $room_object->room_name;

		// Host Menu Tab - rendered in Security as its a module feature of Security.
		$host_menu = new MenuTabDisplay(
			esc_html__( 'Room Hosts', 'my-video-room' ),
			'roomhosts',
			fn() => Factory::get_instance( SecurityVideoPreference::class )
						->choose_settings(
							$room_id,
							$room_name . Dependencies::MULTI_ROOM_HOST_SUFFIX,
							null,
							'roomhost'
						)
		);
		array_push( $input, $host_menu );

		// Permissions Default Tab - rendered in Security as its a module feature of Security.
		$base_menu = new MenuTabDisplay(
			esc_html__( 'Room Permissions', 'my-video-room' ),
			'roompermissions',
			fn() => Factory::get_instance( SecurityVideoPreference::class )->choose_settings(
				$room_id,
				esc_textarea( $room_name ),
				'roomhost'
			)
		);
		array_push( $input, $base_menu );

		return $input;
	}

	/**
	 * Render Security Admin Tabs in Main Shortcode.
	 *
	 * @param array  $input       - the inbound menu.
	 * @param int    $post_id     - the user or entity identifier.
	 * @param string $room_name   - the room identifier.
	 * @param bool   $host_status - whether function is for a host type.
	 *
	 * @return array - outbound menu.
	 */
	public function render_shortcode_security_permissions_tab( array $input, int $post_id, string $room_name, bool $host_status ): array {
		if ( ! $host_status ) {
			return $input;
		}
		$permissions_menu = new MenuTabDisplay(
			esc_html__( 'Room Permissions', 'my-video-room' ),
			'roompermissions',
			fn() => Factory::get_instance( SecurityVideoPreference::class )
				->choose_settings(
					$post_id,
					$room_name,
				)
		);
		array_push( $input, $permissions_menu );

		return $input;
	}
}
