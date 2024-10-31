<?php
/**
 * Allows passing in more complex text options
 *
 * @package MyVideoRoomPlugin
 */

declare( strict_types=1 );

namespace MyVideoRoomPlugin\Module\Monitor;

use MyVideoRoomPlugin\Factory;
use MyVideoRoomPlugin\Shortcode\App;
use MyVideoRoomPlugin\Library\Logger;

/**
 * Class TextOptionShortcode
 */
class TextOptionShortcode {
	const SHORTCODE_TAG = App::SHORTCODE_TAG . '_text_option';

	/**
	 * Install the shortcode
	 */
	public function init() {
		\add_shortcode( self::SHORTCODE_TAG, array( $this, 'output_shortcode' ) );
	}

	/**
	 * Output the shortcode
	 *
	 * @return string
	 */
	public function output_shortcode(): string {
		$message = \sprintf(
		/* translators: First %s is it ths shortcode tag for the text-option and the second for the monitor tag */
			\esc_html__( 'The %1$s should be called from within the %2$s shortcode', 'myvideoroom' ),
			self::SHORTCODE_TAG,
			Module::SHORTCODE_TAG
		);

		return Factory::get_instance( Logger::class )->return_error( $message );
	}

}
