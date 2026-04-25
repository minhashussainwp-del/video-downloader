<?php
/**
 * I18n Class
 * 
 * Define the internationalization functionality for the plugin.
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
 * Class I18n
 */
class I18n {
    
    /**
     * Load the plugin text domain for translation
     */
    public function load_plugin_textdomain() {
        load_plugin_textdomain(
            'advanced-shortcode-snippet-engine',
            false,
            dirname(ASSE_PLUGIN_BASENAME) . '/languages/'
        );
    }
}
