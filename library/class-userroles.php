<?php
/**
 * Helper functions for WordPress User Roles
 *
 * @package MyVideoRoomPlugin\Core\Library
 */

namespace MyVideoRoomPlugin\Library;

/**
 * Class UserRoles
 */
class UserRoles {

	/**
	 * The target user
	 *
	 * @var \WP_User|null
	 */
	private ?\WP_User $user;

	// ---

	/**
	 * UserRoles constructor.
	 *
	 * @param \WP_User|null $user - WP User Object.
	 */
	public function __construct( \WP_User $user = null ) {
		if ( $user ) {
			$this->user = $user;
		} else {
			$this->user = \wp_get_current_user();
		}
	}

	// ---

	/**
	 * Is the current WordPress user an administrator?
	 *
	 * @return bool
	 */
	public function is_wordpress_administrator(): bool {
		return $this->user_has_role( 'administrator' );
	}

	/**
	 * Does the user have a certain role
	 *
	 * @param string $role The role to check.
	 *
	 * @return bool
	 */
	private function user_has_role( string $role ): bool {
		return ( $this->user && in_array( $role, $this->user->roles, true ) );
	}

	/**
	 * Is the current WordPress user a WCFM Vendor?
	 *
	 * @return bool
	 */
	public function is_wcfm_vendor(): bool {
		return $this->user_has_role( 'wcfm_vendor' );
	}

	/**
	 * Is the current WordPress user a WCFM Shop Staff Member?
	 *
	 * @return bool
	 */
	public function is_wcfm_shop_staff(): bool {
		return $this->user_has_role( 'shop_staff' );
	}

	/**
	 * Is the current WordPress user a WCFM Store Manager?
	 *
	 * @return bool
	 */
	public function is_wcfm_store_manager(): bool {
		return $this->user_has_role( 'store_manager' );
	}

	/**
	 * Get current user WordPress Roles
	 *
	 * @param ?int $user_id The user id.
	 *
	 * @return array of roles or a null array if user is not logged in.
	 */
	public function get_user_roles( int $user_id = null ): ?array {

		if ( ! \is_user_logged_in() ) {
			return array();

		} elseif ( ! $user_id && \is_user_logged_in() ) {
			$user_id = get_current_user_id();
		}

		$user_meta = get_userdata( $user_id );

		return $user_meta->roles;
	}
}
