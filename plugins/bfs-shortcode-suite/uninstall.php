<?php
/**
 * Uninstall handler for BFS Shortcode Suite
 *
 * @package BFS_Shortcode_Suite
 */

declare(strict_types=1);

if (!defined('WP_UNINSTALL_PLUGIN')) {
	exit;
}

// This plugin does not store persistent data.
// Shortcodes are part of user content and must never be removed automatically.
