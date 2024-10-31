<?php
/**
 * Generate unique ids for Meetings and handles their creation.
 *
 * @package MyVideoRoomExtrasPlugin\Library
 */

namespace MyVideoRoomPlugin\Library;

use MyVideoRoomPlugin\Factory;

/**
 * Class MeetingIdGenerator
 * Generate unique ids for Meetings and handles their creation.
 */
class MeetingIdGenerator {

	/**
	 * A Shortcode to Return Header Displays and Meeting Invites correctly in Sequences for Menus
	 * This is meant to be the new universal formatting invite list
	 *
	 * @param string|array $params - $host - the host type. $invite - the Invite Code. $user_id - the inbound user ID to convert if any.
	 *
	 * @return string
	 */
	public function invite_menu_shortcode( $params = array() ): string {
		$type = $params['type'] ?? 'host';
		//phpcs:ignore -- WordPress.Security.NonceVerification.Recommended - not needed as data is lookup and transient only.
		$host = $params['host'] ?? htmlspecialchars( sanitize_text_field( wp_unslash( $_GET['host'] ?? '' ) ) );
		//phpcs:ignore -- WordPress.Security.NonceVerification.Recommended - not needed as data is lookup and transient only.
		$invite   = $params['invite'] ?? htmlspecialchars( sanitize_text_field( wp_unslash( $_GET['invite'] ?? '' ) ) );
		$user_id  = $params['user_id'] ?? '';
		$meet_url = Factory::get_instance( RoomAdmin::class )->get_room_url( Dependencies::ROOM_NAME_PERSONAL_MEETING );

		if ( 'host' === $type ) {
			if ( ! $user_id ) {
				$user    = wp_get_current_user();
				$user_id = $user->ID;
			}
			$out_meeting_id = $this->invite( $user_id, 'user' );

			return $meet_url . '?invite=' . $out_meeting_id;
		}

		if ( $invite && ! $user_id ) {
			$user_id = $this->invite( $invite, 'in' );
		}

		if ( $host && ! $user_id ) {
			$input   = $host;
			$user    = Factory::get_instance( WordPressUser::class )->get_wordpress_user_by_identifier_string( $input );
			$user_id = $user->ID;
		}
		$invite      = $this->invite( $user_id, 'user' );
		$user_detail = Factory::get_instance( WordPressUser::class )->get_wordpress_user_by_id( $user_id );

		if ( 'guestname' === $type ) {
			return $user_detail->display_name;
		} elseif ( 'guestlink' === $type && $invite ) {
			return $meet_url . '?invite=' . $invite;
		}

		return '';
	}

	/**
	 * Constructs Invites for meetings from User or Room ID.
	 * Function is called on to support Shortcode meeting functions
	 *
	 * @param  ?string $invite    - Invite - the invite number.
	 * @param string  $direction - inbound or outbound.
	 * @param  ?string $input     - the item to hash or decode.
	 *
	 * @return string - the Invite or resulted de-hash.
	 */
	public function invite( ?string $invite, string $direction, string $input = null ) {

		if ( $input && ! $invite && 'out' === $direction ) {
			return self::get_meeting_hash_from_user_id( $input );
		}

		if ( ! $invite && ! $input && 'user' !== $direction ) {
			return null;
		}

		$user_id = null;

		if ( isset( $input ) ) {
			$user = Factory::get_instance( WordPressUser::class )->get_wordpress_user_by_identifier_string( $input );

			if ( ! $user ) {
				return 'Invalid Username or Email entered<br>';
			}

			$user_id = $user->ID;
		}

		switch ( $direction ) {
			case 'in':
				$user_id = self::get_user_id_from_meeting_hash( $invite );
				break;
			case 'user':
				if ( $invite ) {
					$user_id = self::get_meeting_hash_from_user_id( $invite );
				}

				break;
			case 'out':
				$user_id = self::get_meeting_hash_from_user_id( $user_id );
				break;
		}

		return $user_id;
	}

	/**
	 * Create a unique hash based on the user and nonce
	 *
	 * @param int $user_id The WordPress user id.
	 *
	 * @return string
	 */
	public static function get_meeting_hash_from_user_id( int $user_id ): string {
		$input = $user_id ^ self::get_meeting_nonce();

		$items = array_map( 'intval', str_split( (string) $input ) );
		$seed  = array_pop( $items );

		$items = self::seeded_shuffle( $items, $seed + substr( self::get_meeting_nonce(), 3 ) );

		$items[] = $seed;

		$number = implode( '', $items );

		$number_sections = array(
			substr( $number, 0, 3 ),
			substr( $number, 3, 4 ),
			substr( $number, 7 ),
		);

		return implode( '-', $number_sections );
	}

	/**
	 * Get 11 digit integer based on WordPress Nonce Salt
	 *
	 * @return int
	 */
	private static function get_meeting_nonce(): int {
		return (int) substr(
			base_convert( md5( NONCE_SALT ), 16, 10 ),
			0,
			11
		);
	}

	/**
	 * Deterministic shuffle an array based on a seed
	 *
	 * @param array   $items Array to shuffle.
	 * @param integer $seed  Seed for the randomizer.
	 *
	 * @return mixed
	 */
	private static function seeded_shuffle( array $items, int $seed ): array {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.rand_seeding_mt_srand
		mt_srand( $seed );

		for ( $i = count( $items ) - 1; $i > 0; $i -- ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.rand_mt_rand
			$j = mt_rand( 0, $i );

			list( $items[ $i ], $items[ $j ] ) = array( $items[ $j ], $items[ $i ] );
		}

		return $items;
	}

	/**
	 * Retrieve the user id from a meeting hash
	 *
	 * @param string $hash The hash generated by self::get_meeting_hash_from_user_id.
	 *
	 * @return int
	 */
	public static function get_user_id_from_meeting_hash( string $hash ): int {
		$items = str_split( (string) str_replace( '-', '', $hash ) );
		$seed  = array_pop( $items );

		$items = self::seeded_unshuffle( $items, $seed + substr( self::get_meeting_nonce(), 3 ) );

		$items[] = $seed;

		$number = implode( '', $items );

		return $number ^ self::get_meeting_nonce();
	}

	/**
	 * Un-shuffle an deterministically shuffled array based on a seed
	 *
	 * @param array $items A list of items.
	 * @param int   $seed  The seed used to shuffle the items.
	 *
	 * @return array
	 */
	private static function seeded_unshuffle( array $items, int $seed ): array {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.rand_seeding_mt_srand
		mt_srand( $seed );

		$indices = array();
		for ( $i = count( $items ) - 1; $i > 0; $i -- ) {

			// phpcs:ignore WordPress.WP.AlternativeFunctions.rand_mt_rand
			$indices[ $i ] = mt_rand( 0, $i );
		}

		foreach ( array_reverse( $indices, true ) as $i => $j ) {
			list( $items[ $i ], $items[ $j ] ) = array( $items[ $j ], $items[ $i ] );
		}

		return $items;
	}
}
