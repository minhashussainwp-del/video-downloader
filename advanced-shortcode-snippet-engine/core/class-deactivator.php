<?php
/**
 * Deactivator Class
 * 
 * Fired during plugin deactivation to cleanup caches and scheduled events.
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
 * Class Deactivator
 */
class Deactivator {
    
    /**
     * Deactivate the plugin
     */
    public static function deactivate() {
        // Unschedule cron events
        self::unschedule_events();
        
        // Clear transients and caches
        self::clear_caches();
        
        // Flush rewrite rules
        flush_rewrite_rules();
        
        // Log deactivation
        error_log('Advanced Shortcode & Snippet Engine deactivated');
    }
    
    /**
     * Unschedule all cron events
     */
    private static function unschedule_events() {
        $events = array(
            'asse_daily_cleanup',
            'asse_cache_cleanup',
        );
        
        foreach ($events as $event) {
            $timestamp = wp_next_scheduled($event);
            if ($timestamp) {
                wp_unschedule_event($timestamp, $event);
            }
        }
    }
    
    /**
     * Clear all plugin caches and transients
     */
    private static function clear_caches() {
        global $wpdb;
        
        // Delete all transients related to the plugin
        $wpdb->query(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_asse_%' OR option_name LIKE '_transient_timeout_asse_%'"
        );
        
        // Clear object cache
        wp_cache_flush();
        
        // Delete cached snippet outputs
        delete_transient('asse_snippet_cache');
    }
}
