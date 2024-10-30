<?php
/**
 * Filter hooks
 *
 * @package BuddyPress Mute
 * @subpackage Filters
 */

add_filter( 'bp_after_has_activities_parse_args', 'mute_filter_site_activity'               );
add_filter( 'bp_after_has_activities_parse_args', 'mute_filter_site_activity_scope_friends' );
add_filter( 'bp_after_has_activities_parse_args', 'mute_filter_site_activity_scope_groups'  );
add_filter( 'bp_after_has_activities_parse_args', 'mute_filter_user_activity_scope_friends' );
add_filter( 'bp_after_has_activities_parse_args', 'mute_filter_user_activity_scope_groups'  );
add_filter( 'bp_after_has_activities_parse_args', 'mute_filter_activity_object_groups'      );
add_filter( 'bp_after_has_members_parse_args',    'mute_filter_members_friends'             );
add_filter( 'bp_after_has_members_parse_args',    'mute_filter_members_all'                 );
