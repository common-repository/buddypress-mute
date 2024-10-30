<?php
/**
 * Action hooks
 *
 * @package BuddyPress Mute
 * @subpackage Actions
 */

add_action( 'bp_init',                           'mute_i18n'                            );
add_action( 'wp_enqueue_scripts',                'mute_js'                              );
add_action( 'bp_member_header_actions',          'mute_add_member_header_button',    99 );
add_action( 'bp_directory_members_actions',      'mute_add_member_dir_button',       99 );
add_action( 'bp_group_members_list_item_action', 'mute_add_group_member_dir_button', 99 );
add_action( 'delete_user',                       'mute_delete'                          );
add_action( 'bp_core_deleted_account',           'mute_delete'                          );
add_action( 'bp_actions',                        'mute_action_start'                    );
add_action( 'bp_actions',                        'mute_action_stop'                     );
add_action( 'wp_ajax_mute',                      'mute_ajax_start'                      );
add_action( 'wp_ajax_unmute',                    'mute_ajax_stop'                       );
