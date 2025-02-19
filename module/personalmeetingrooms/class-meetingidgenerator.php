<?php
/**
 * Generate unique ids
 *
 * @package MyVideoRoomExtrasPlugin\Library
 */

namespace MyVideoRoomPlugin\Module\PersonalMeetingRooms;

/**
 * Class MeetingIdGenerator
 */
class MeetingIdGenerator {

	/**
	 * Create a unique hash based on the user and nonce
	 *
	 * @param int $user_id The WordPress user id.
	 *
	 * @return string
	 */
	public function get_meeting_hash_from_user_id( int $user_id ): string {
		$input = $user_id ^ self::get_meeting_nonce();

		$items = \array_map( 'intval', \str_split( (string) $input ) );
		$seed  = \array_pop( $items );

		$items = $this->seeded_shuffle( $items, $seed + \substr( $this->get_meeting_nonce(), 3 ) );

		$items[] = $seed;

		$number = \implode( '', $items );

		$number_sections = array(
			\substr( $number, 0, 3 ),
			\substr( $number, 3, 4 ),
			\substr( $number, 7 ),
		);

		return \implode( '-', $number_sections );
	}

	/**
	 * Get 11 digit integer based on WordPress Nonce Salt
	 *
	 * @return int
	 */
	private function get_meeting_nonce(): int {
		return (int) \substr(
			\base_convert( md5( NONCE_SALT ), 16, 10 ),
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
	private function seeded_shuffle( array $items, int $seed ): array {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.rand_seeding_mt_srand
		\mt_srand( $seed );

		for ( $i = \count( $items ) - 1; $i > 0; $i -- ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.rand_mt_rand
			$j = \mt_rand( 0, $i );

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
	public function get_user_id_from_meeting_hash( string $hash ): int {
		$items = \str_split( (string) \str_replace( '-', '', $hash ) );
		$seed  = \array_pop( $items );

		$items = $this->seeded_unshuffle( $items, $seed + \substr( $this->get_meeting_nonce(), 3 ) );

		$items[] = $seed;

		$number = \implode( '', $items );

		return $number ^ $this->get_meeting_nonce();
	}

	/**
	 * Un-shuffle an deterministically shuffled array based on a seed
	 *
	 * @param array $items A list of items.
	 * @param int   $seed  The seed used to shuffle the items.
	 *
	 * @return array
	 */
	private function seeded_unshuffle( array $items, int $seed ): array {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.rand_seeding_mt_srand
		\mt_srand( $seed );

		$indices = array();
		for ( $i = \count( $items ) - 1; $i > 0; $i -- ) {

			// phpcs:ignore WordPress.WP.AlternativeFunctions.rand_mt_rand
			$indices[ $i ] = \mt_rand( 0, $i );
		}

		foreach ( \array_reverse( $indices, true ) as $i => $j ) {
			list( $items[ $i ], $items[ $j ] ) = array( $items[ $j ], $items[ $i ] );
		}

		return $items;
	}
}
