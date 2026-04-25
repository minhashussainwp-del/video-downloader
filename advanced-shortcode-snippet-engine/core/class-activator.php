<?php
/**
 * Activator Class
 * 
 * Fired during plugin activation to create database tables and set defaults.
 * 
 * @package ASSE\Core
 * @since 1.0.0
 */

namespace ASSE\Core;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Activator
 */
class Activator {
    
    /**
     * Activate the plugin
     */
    public static function activate() {
        // Create database tables
        self::create_tables();
        
        // Set default options
        self::set_defaults();
        
        // Schedule cron events
        self::schedule_events();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Log activation
        error_log('Advanced Shortcode & Snippet Engine activated');
    }
    
    /**
     * Create database tables
     */
    private static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Snippets table
        $snippets_table = $wpdb->prefix . 'asse_snippets';
        $sql_snippets = "CREATE TABLE $snippets_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            slug varchar(255) NOT NULL,
            type varchar(20) NOT NULL DEFAULT 'php',
            code longtext NOT NULL,
            status varchar(20) NOT NULL DEFAULT 'inactive',
            priority int(4) NOT NULL DEFAULT 10,
            scope varchar(50) NOT NULL DEFAULT 'global',
            conditions longtext,
            tags text,
            categories text,
            author_id bigint(20) NOT NULL,
            modified_by bigint(20),
            created_at datetime NOT NULL,
            updated_at datetime NOT NULL,
            version int(10) NOT NULL DEFAULT 1,
            PRIMARY KEY (id),
            KEY slug (slug),
            KEY type (type),
            KEY status (status)
        ) $charset_collate;";
        
        // Revisions table
        $revisions_table = $wpdb->prefix . 'asse_snippets_revisions';
        $sql_revisions = "CREATE TABLE $revisions_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            snippet_id bigint(20) NOT NULL,
            code longtext NOT NULL,
            modified_by bigint(20) NOT NULL,
            changed_at datetime NOT NULL,
            change_note text,
            PRIMARY KEY (id),
            KEY snippet_id (snippet_id)
        ) $charset_collate;";
        
        // Execution logs table
        $logs_table = $wpdb->prefix . 'asse_execution_logs';
        $sql_logs = "CREATE TABLE $logs_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            snippet_id bigint(20) NOT NULL,
            execution_time float NOT NULL DEFAULT 0,
            memory_usage int NOT NULL DEFAULT 0,
            status varchar(20) NOT NULL DEFAULT 'success',
            message text,
            created_at datetime NOT NULL,
            PRIMARY KEY (id),
            KEY snippet_id (snippet_id),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";
        
        // Include upgrade.php for dbDelta
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        
        dbDelta($sql_snippets);
        dbDelta($sql_revisions);
        dbDelta($sql_logs);
        
        update_option('asse_db_version', ASSE_VERSION);
    }
    
    /**
     * Set default plugin options
     */
    private static function set_defaults() {
        $defaults = array(
            'version' => ASSE_VERSION,
            'debug_mode' => false,
            'safe_mode' => false,
            'cache_enabled' => true,
            'cache_ttl' => 3600,
            'max_revisions' => 10,
            'execution_timeout' => 30,
            'memory_limit' => 67108864, // 64MB in bytes
            'log_errors' => true,
            'minify_css' => false,
            'minify_js' => false,
            'enable_api' => true,
            'api_keys' => array(),
        );
        
        if (!get_option('asse_settings')) {
            add_option('asse_settings', $defaults);
        } else {
            // Merge with existing settings
            $existing = get_option('asse_settings');
            $merged = wp_parse_args($existing, $defaults);
            update_option('asse_settings', $merged);
        }
    }
    
    /**
     * Schedule cron events
     */
    private static function schedule_events() {
        // Daily cleanup of old logs
        if (!wp_next_scheduled('asse_daily_cleanup')) {
            wp_schedule_event(time(), 'daily', 'asse_daily_cleanup');
        }
        
        // Hourly cache cleanup
        if (!wp_next_scheduled('asse_cache_cleanup')) {
            wp_schedule_event(time(), 'hourly', 'asse_cache_cleanup');
        }
    }
}
