<?php
/**
 * Uninstall FS Shortcode Suite
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Remove plugin-specific options
delete_option('fs_shortcode_suite_version');
delete_option('fs_shortcode_suite_settings');

// Clear transients if used
global $wpdb;

$wpdb->query("
    DELETE FROM {$wpdb->options}
    WHERE option_name LIKE '_transient_fs_%'
");

$wpdb->query("
    DELETE FROM {$wpdb->options}
    WHERE option_name LIKE '_transient_timeout_fs_%'
");

// Do NOT remove CPT posts or taxonomies here,
// as they belong to the data architecture, not the shortcode layer.
