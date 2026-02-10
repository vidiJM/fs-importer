<?php
/**
 * Uninstall handler for FS Importer Sprinter
 *
 * @package FS_Importer_Sprinter
 */

declare(strict_types=1);

if (!defined('WP_UNINSTALL_PLUGIN')) {
	exit;
}

if (!current_user_can('activate_plugins')) {
	return;
}

global $wpdb;

/**
 * Delete plugin options
 */
delete_option('fs_importer_sprinter_settings');
delete_site_option('fs_importer_sprinter_settings');

/**
 * Delete related transients
 */
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
		$wpdb->esc_like('_transient_fs_importer_sprinter_') . '%'
	)
);

/**
 * Clear scheduled cron events
 */
$cron_hook = 'fs_importer_sprinter_cron';

$timestamp = wp_next_scheduled($cron_hook);
if ($timestamp !== false) {
	wp_unschedule_event($timestamp, $cron_hook);
}
