<?php
/**
 * BuddyPress Module for MyVideoRoom
 *
 * @package MyVideoRoomPlugin/Module/BuddyPress
 */

declare( strict_types=1 );

namespace MyVideoRoomPlugin\Module\BuddyPress;

use MyVideoRoomPlugin\Library\Module;
use MyVideoRoomPlugin\Module\BuddyPress\Module as BuddyPress;
use MyVideoRoomPlugin\Plugin;

\add_action(
	Plugin::ACTION_INIT,
	function () {
		Module::register(
			'buddypress',
			\__( 'BuddyPress', 'myvideoroom' ),
			array(
				\__(
					'Integrates BuddyPress and MyVideoRoom - adding video rooms to the BuddyPress user profile pages and to group pages. Users get their own personal video room rendered in the their BuddyPress Profile page as a separate video meeting tab, and are given control of their own video room settings and permissions - including whether to show the room to non-friends. Guests viewing a user profile in BuddyPress can enter a video room straight from the user’s profile page. Owners and moderators of BuddyPress groups can enable or disable the video room for the group, as well as control their layouts, templates, room permissions and reception settings, including creating members only groups.',
					'myvideoroom'
				),
			),
		//phpcs:ignore Squiz.PHP.CommentedOutCode.Found
			/* fn() => new BuddyPress() */
		)
		->add_compatibility_hook( fn() => true ) // @TODO - for testing - always compatible.
		->add_activation_hook( fn() => ( new Activation() )->activate() )
		->add_uninstall_hook( fn() => ( new Activation() )->uninstall() );
	}
);
