<?php
/**
 * Uninstall Advanced Shortcode & Snippet Engine
 * 
 * This file runs when the plugin is deleted from WordPress.
 * It removes all plugin data from the database.
 * 
 * @package ASSE
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// Check if user wants to keep data (configurable via constant)
if (defined('ASSE_KEEP_DATA_ON_UNINSTALL') && ASSE_KEEP_DATA_ON_UNINSTALL) {
    return;
}

// Delete plugin options
delete_option('asse_settings');
delete_option('asse_db_version');

// Delete transients
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_asse_%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_asse_%'");

// Drop database tables
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}asse_snippets");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}asse_snippets_revisions");
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}asse_execution_logs");

// Delete uploaded files (logs directory)
$upload_dir = wp_upload_dir();
$log_dir = $upload_dir['basedir'] . '/asse-logs/';

if (is_dir($log_dir)) {
    // Remove all files in log directory
    array_map('unlink', glob($log_dir . '*'));
    // Remove directory
    rmdir($log_dir);
}

// Clear any cached data
wp_cache_flush();

error_log('Advanced Shortcode & Snippet Engine uninstalled - all data removed');
