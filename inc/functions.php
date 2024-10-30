<?php
/**
 * Function definitions
 *
 * @package BuddyPress Mute
 * @subpackage Functions
 */

/**
 * Enqueue the bp-mute-js script.
 *
 * @since 1.0.0
 */
function mute_js() {

	if ( ! is_user_logged_in() )
		return;

	if ( bp_is_user() || bp_is_members_directory() || bp_is_group_members() ) {

		wp_enqueue_script( 'bp-mute-js', plugins_url( 'js/script.min.js', dirname( __FILE__ ) ), array( 'jquery' ), NULL, true );

		// Make data available to the script.
		wp_localize_script(
			'bp-mute-js',
			'mute',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'start'    => wp_create_nonce( 'mute-nonce' ),
				'stop'     => wp_create_nonce( 'unmute-nonce' )
			)
		);
	}
}

/**
 * Load the plugin's textdomain.
 *
 * @since 1.0.0
 */
function mute_i18n() {
	load_plugin_textdomain( 'buddypress-mute' );
}

/**
 * Catch the 'all' screen.
 *
 * @since 1.0.0
 */
function mute_all_screen() {

	/**
	 * Fires before loading the 'all' screen template.
	 *
	 * @since 1.0.0
	 */
	do_action( 'bp_mute_all_screen' );

	add_action( 'bp_after_member_plugin_template', 'mute_disable_members_loop_ajax' );
	add_action( 'bp_template_content',             'mute_template_part'             );

	bp_core_load_template( 'members/single/plugins' );
}

/**
 * Catch the 'friends' screen.
 *
 * @since 1.0.0
 */
function mute_friends_screen() {

	/**
	 * Fires before loading the 'friends' screen template.
	 *
	 * @since 1.0.0
	 */
	do_action( 'bp_mute_friends_screen' );

	add_action( 'bp_after_member_plugin_template', 'mute_disable_members_loop_ajax' );
	add_action( 'bp_template_content',             'mute_template_part'             );

	bp_core_load_template( 'members/single/plugins' );
}

/**
 * Get a template part.
 *
 * @since 1.0.0
 */
function mute_template_part() {
	bp_get_template_part( 'members/members-loop' );
}

/**
 * Create a button.
 *
 * @since 1.0.0
 *
 * @param int $muted_id The ID of the muted user.
 * @return string
 */
function mute_get_button( $muted_id ) {

	global $bp, $members_template;

	if ( ! $muted_id ) {
		return;
	}

	$obj = new Mute( $muted_id, bp_loggedin_user_id() );

	// Determine the mute status.
	$action = $obj->id ? '/stop/' : '/start/';

	$url = bp_core_get_user_domain( $muted_id ) . $bp->mute->slug . $action;

	$button = array(
		'id'                => $obj->id ? 'muted' : 'unmuted',
		'link_class'        => $obj->id ? 'muted' : 'unmuted',
		'link_id'           => $obj->id ? 'mute-' . $muted_id : 'mute-' . $muted_id,
		'link_title'        => $obj->id ? _x( 'Unmute', 'Button', 'buddypress-mute' ) : _x( 'Mute', 'Button', 'buddypress-mute' ),
		'link_text'         => $obj->id ? _x( 'Unmute', 'Button', 'buddypress-mute' ) : _x( 'Mute', 'Button', 'buddypress-mute' ),
		'link_href'         => $obj->id ? wp_nonce_url( $url, 'unmute' ) : wp_nonce_url( $url, 'mute' ),
		'wrapper_class'     => 'mute-button',
		'component'         => 'mute',
		'wrapper_id'        => 'mute-button-' . $muted_id,
		'must_be_logged_in' => true,
		'block_self'        => true
	);
	return bp_get_button( $button );
}

/**
 * Output a button in the profile header area.
 *
 * @since 1.0.0
 */
function mute_add_member_header_button() {
	echo mute_get_button( bp_displayed_user_id() );
}

/**
 * Output a button for each member in the loop.
 *
 * @since 1.0.0
 */
function mute_add_member_dir_button() {
	global $members_template;

	echo mute_get_button( $members_template->member->id );
}

/**
 * Output a button for each member in the group.
 *
 * @since 1.0.3
 */
function mute_add_group_member_dir_button() {
	global $members_template;

	echo mute_get_button( $members_template->member->user_id );
}

/**
 * Delete all mute records relating to a given user.
 *
 * @since 1.1.0
 *
 * @param int $user_id The ID of the identicon owner.
 */
function mute_delete( $user_id ) {
	Mute::delete_all( $user_id );
}

/**
 * Start muting a user if JavaScript is disabled.
 *
 * @since 1.0.0
 */
function mute_action_start() {

	if ( ! bp_is_current_component( 'mute' ) || ! bp_is_current_action( 'start' ) ) {
		return;
	}

	// Check the nonce.
	check_admin_referer( 'mute' );

	$obj = new Mute( bp_displayed_user_id(), bp_loggedin_user_id() );

	if ( $obj->id ) {
		$message = sprintf( __( 'You are already muting %s.', 'buddypress-mute' ), bp_get_displayed_user_fullname() );
		$status = 'error';
	} else {
		if ( $obj->save() === false ) {
			$message = __( 'This user could not be muted. Try again.', 'buddypress-mute' );
			$status = 'error';
		} else {
			$message = sprintf( __( 'You are now muting %s.', 'buddypress-mute' ), bp_get_displayed_user_fullname() );
			$status = 'success';
		}
	}

	// Output the message.
	bp_core_add_message( $message, $status );
	bp_core_redirect( wp_get_referer() );
}

/**
 * Stop muting a user if JavaScript is disabled.
 *
 * @since 1.0.0
 */
function mute_action_stop() {

	if ( ! bp_is_current_component( 'mute' ) || ! bp_is_current_action( 'stop' ) ) {
		return;
	}

	// Check the nonce.
	check_admin_referer( 'unmute' );

	$obj = new Mute( bp_displayed_user_id(), bp_loggedin_user_id() );

	if ( ! $obj->id ) {
		$message = sprintf( __( 'You are not muting %s.', 'buddypress-mute' ), bp_get_displayed_user_fullname() );
		$status = 'error';
	} else {
		if ( $obj->delete() === false ) {
			$message = __( 'This user could not be unmuted. Try again.', 'buddypress-mute' );
			$status = 'error';
		} else {
			$message = sprintf( __( 'You have successfully unmuted %s.', 'buddypress-mute' ), bp_get_displayed_user_fullname() );
			$status = 'success';
		}
	}

	// Output the message.
	bp_core_add_message( $message, $status );
	bp_core_redirect( wp_get_referer() );
}

/**
 * Start muting a user if JavaScript is enabled.
 *
 * @since 1.0.0
 */
function mute_ajax_start() {

	// Check the nonce.
	check_ajax_referer( 'mute-nonce', 'start' );

	$mute = new Mute( (int) $_POST['uid'], bp_loggedin_user_id() );

	if ( $mute->id ) {
		$response['status'] = 'failure';
	} else {
		$response['status'] = $mute->save() ? 'success' : 'failure';
	}

	$count = Mute::get_count( bp_displayed_user_id() );
	$response['count'] = $count ? $count : 0;

	// Send the response and exit.
	wp_send_json( $response );
}

/**
 * Stop muting a user if JavaScript is enabled.
 *
 * @since 1.0.0
 */
function mute_ajax_stop() {

	// Check the nonce.
	check_ajax_referer( 'unmute-nonce', 'stop' );

	$mute = new Mute( (int) $_POST['uid'], bp_loggedin_user_id() );

	if ( ! $mute->id ) {
		$response['status'] = 'failure';
	} else {
		$response['status'] = $mute->delete() ? 'success' : 'failure';
	}

	$count = Mute::get_count( bp_displayed_user_id() );
	$response['count'] = $count ? $count : 0;

	// Send the response and exit.
	wp_send_json( $response );
}

/**
 * Filter site activity stream if scope is "".
 *
 * @since 1.0.3
 *
 * @param array $r The activity arguments.
 * @return array
 */
function mute_filter_site_activity( $r ) {

	if ( ! is_user_logged_in() ) {
		return $r;
	}

	if ( ! bp_is_activity_directory() ) {
		return $r;
	}

	if ( $r['scope'] !== "" ) {
		return $r;
	}

	// Get an array of muted user IDs.
	$muted_ids = Mute::get_muting( bp_loggedin_user_id() );

	$filter_query[] = array(
		array(
			'column'   => 'user_id',
			'value'    => $muted_ids,
			'compare'  => 'NOT IN'
		)
	);
	$r['filter_query'] = $filter_query;
	return $r;
}

/**
 * Filter site activity stream if scope is "friends".
 *
 * @since 1.0.3
 *
 * @param array $r The activity arguments.
 * @return array
 */
function mute_filter_site_activity_scope_friends( $r ) {

	if ( ! is_user_logged_in() ) {
		return $r;
	}

	if ( ! bp_is_activity_directory() ) {
		return $r;
	}

	if ( $r['scope'] !== "friends" ) {
		return $r;
	}

	$r['scope'] = '';

	// Get an array of friend IDs.
	$friend_ids = friends_get_friend_user_ids( bp_loggedin_user_id() );

	// Get an array of muted user IDs.
	$muted_ids = Mute::get_muting( bp_loggedin_user_id() );

	// Get an array of unmuted friend IDs.
	$r['user_id'] = array_diff( $friend_ids, $muted_ids );

	return $r;
}

/**
 * Filter site activity stream if scope is "groups".
 *
 * @since 1.0.3
 *
 * @param array $r The activity arguments.
 * @return array
 */
function mute_filter_site_activity_scope_groups( $r ) {

	if ( ! is_user_logged_in() ) {
		return $r;
	}

	if ( ! bp_is_activity_directory() ) {
		return $r;
	}

	if ( $r['scope'] !== "groups" ) {
		return $r;
	}

	$r['scope'] = '';

	// Get an array of muted user IDs.
	$muted_ids = Mute::get_muting( bp_loggedin_user_id() );

	// Get an array of groups joined.
	$groups = groups_get_user_groups( bp_loggedin_user_id() );

	$filter_query[] = array(
		array(
			'column'   => 'component',
			'value'    => buddypress()->groups->id
		)
	);

	$filter_query[] = array(
		array(
			'column'   => 'item_id',
			'value'    => $groups['groups'],
			'compare'  => 'IN'
		)
	);

	$filter_query[] = array(
		array(
			'column'   => 'user_id',
			'value'    => $muted_ids,
			'compare'  => 'NOT IN'
		)
	);

	$r['filter_query'] = $filter_query;

	return $r;
}

/**
 * Filter user activity stream if scope is "friends".
 *
 * @since 1.0.3
 *
 * @param array $r The activity arguments.
 * @return array
 */
function mute_filter_user_activity_scope_friends( $r ) {

	if ( ! bp_is_my_profile() ) {
		return $r;
	}

	if ( $r['scope'] !== "friends" ) {
		return $r;
	}

	$r['scope'] = '';

	// Get an array of friend IDs.
	$friend_ids = friends_get_friend_user_ids( bp_displayed_user_id() );

	// Get an array of muted user IDs.
	$muted_ids = Mute::get_muting( bp_displayed_user_id() );

	// Get an array of unmuted friend IDs.
	$r['user_id'] = array_diff( $friend_ids, $muted_ids );

	return $r;
}

/**
 * Filter user activity stream if scope is "groups".
 *
 * @since 1.0.3
 *
 * @param array $r The activity arguments.
 * @return array
 */
function mute_filter_user_activity_scope_groups( $r ) {

	if ( ! bp_is_my_profile() ) {
		return $r;
	}

	if ( $r['scope'] !== "groups" ) {
		return $r;
	}

	$r['scope'] = '';

	$r['user_id'] = false;

	// Get an array of muted user IDs.
	$muted_ids = Mute::get_muting( bp_displayed_user_id() );

	// Get an array of groups joined.
	$groups = groups_get_user_groups( bp_displayed_user_id() );

	$filter_query[] = array(
		array(
			'column'   => 'component',
			'value'    => buddypress()->groups->id
		)
	);

	$filter_query[] = array(
		array(
			'column'   => 'item_id',
			'value'    => $groups['groups'],
			'compare'  => 'IN'
		)
	);

	$filter_query[] = array(
		array(
			'column'   => 'user_id',
			'value'    => $muted_ids,
			'compare'  => 'NOT IN'
		)
	);

	$r['filter_query'] = $filter_query;

	return $r;
}

/**
 * Filter activity stream if object is "groups".
 *
 * @since 1.0.3
 *
 * @param array $r The activity arguments.
 * @return array
 */
function mute_filter_activity_object_groups( $r ) {

	if ( ! is_user_logged_in() ) {
		return $r;
	}

	if ( ! bp_is_group() ) {
		return $r;
	}

	if ( $r['object'] !== "groups" ) {
		return $r;
	}

	$r['scope'] = '';

	// Get an array of muted user IDs.
	$muted_ids = Mute::get_muting( bp_loggedin_user_id() );

	$filter_query[] = array(
		array(
			'column'   => 'user_id',
			'value'    => $muted_ids,
			'compare'  => 'NOT IN'
		)
	);

	$r['filter_query'] = $filter_query;

	return $r;
}

/**
 * Filter the members loop to show muted friends.
 *
 * @since 1.0.0
 *
 * @param array $r Arguments for changing the contents of the loop.
 * @return array
 */
function mute_filter_members_friends( $r ) {

	if ( ! bp_is_active( 'friends' ) ) {
		return $r;
	}

	if ( bp_is_current_component( 'mute' ) && bp_is_current_action( 'friends' ) ) {

		// Get an array of muted user IDs.
		$muted_ids = Mute::get_muting( bp_displayed_user_id() );

		foreach ( $muted_ids as $muted_id ) {

			// Check if the users are friends.
			$result = friends_check_friendship( bp_displayed_user_id(), $muted_id );

			if ( $result ) {
				$array[] = $muted_id;
			}
		}

		if ( empty( $array ) ) {
			$r['include'] = 0;
		} else {
			$r['include'] = $array;
		}
	}
	return $r;
}

/**
 * Filter the members loop to show all muted users.
 *
 * @since 1.0.0
 *
 * @param array $r Arguments for changing the contents of the loop.
 * @return array
 */
function mute_filter_members_all( $r ) {

	if ( bp_is_current_component( 'mute' ) && bp_is_current_action( 'all' ) ) {

		// Get an array of muted user IDs.
		$muted_ids = Mute::get_muting( bp_displayed_user_id() );

		if ( empty( $muted_ids ) ) {
			$r['include'] = 0;
		} else {
			$r['include'] = $muted_ids;
		}
	}
	return $r;
}

/**
 * Disable ajax in the plugin.php members loop.
 *
 * @since 1.0.0
 */
function mute_disable_members_loop_ajax() {
	?>
	<script>
		jQuery(document).ready( function() {
			jQuery( "#pag-top, #pag-bottom" ).addClass( "no-ajax" );
		});
	</script>
	<?php
}
