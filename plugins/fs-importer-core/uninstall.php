<?php
/**
 * Uninstall handler for FS Importer Core
 *
 * @package FS_Importer_Core
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
delete_option('fs_importer_core_settings');
delete_site_option('fs_importer_core_settings');

/**
 * Delete related transients
 */
$wpdb->query(
	$wpdb->prepare(
		"DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
		$wpdb->esc_like('_transient_fs_importer_') . '%'
	)
);

/**
 * Drop custom tables
 */
$table_name = $wpdb->prefix . 'fs_importer_logs';

if ($wpdb->get_var(
	$wpdb->prepare(
		'SHOW TABLES LIKE %s',
		$table_name
	)
) === $table_name) {
	$wpdb->query("DROP TABLE IF EXISTS {$table_name}");
}
