<?php
/**
 * Plugin Name: BuddyPress Mute
 * Plugin URI: https://github.com/henrywright/buddypress-mute
 * Description: Let members mute their friends and shed unwanted items from their BuddyPress activity stream.
 * Version: 1.0.4
 * Author: Henry Wright
 * Author URI: http://about.me/henrywright
 * Text Domain: buddypress-mute
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.txt
 */

/**
 * BuddyPress Mute
 *
 * @package BuddyPress Mute
 */

/**
 * Load the plugin.
 *
 * @since 1.0.0
 */
function mute_load() {

	if ( ! bp_is_active( 'activity' ) ) {
		return;
	}
	global $bp;

	require_once dirname( __FILE__ ) . '/inc/classes/bp-component-mute.php';

	// Load the component into $bp.
	$bp->mute = new BP_Component_Mute;
}
add_action( 'bp_loaded', 'mute_load' );

/**
 * Create a table in the database.
 *
 * @since 1.0.0
 */
function mute_create_db_table() {

	global $bp, $wpdb;

	$table_name = $bp->table_prefix . 'bp_mute';
	$charset_collate = $wpdb->get_charset_collate();

	$sql = "CREATE TABLE $table_name (
		id bigint(20) NOT NULL auto_increment,
		muted_id bigint(20) NOT NULL,
		user_id bigint(20) NOT NULL,
		date_recorded datetime NOT NULL default '0000-00-00 00:00:00',
		PRIMARY KEY  (id),
		KEY (muted_id),
		KEY (user_id)
	) $charset_collate;";

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );
}
register_activation_hook( __FILE__, 'mute_create_db_table' );
