<?php
/**
 * Plugin Name: Advanced Shortcode & Snippet Engine
 * Plugin URI: https://example.com/advanced-shortcode-snippet-engine
 * Description: A production-ready WordPress plugin for creating, managing, and executing PHP, CSS, JavaScript, HTML, and JSON snippets with dynamic shortcode support.
 * Version: 1.0.0
 * Author: Minhas Hussain
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: advanced-shortcode-snippet-engine
 * Domain Path: /languages
 * Requires at least: 5.6
 * Requires PHP: 7.4
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('ASSE_VERSION', '1.0.0');
define('ASSE_PLUGIN_FILE', __FILE__);
define('ASSE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('ASSE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ASSE_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Check minimum PHP version
if (version_compare(PHP_VERSION, '7.4', '<')) {
    add_action('admin_notices', function() {
        echo '<div class="error"><p>' . esc_html__('Advanced Shortcode & Snippet Engine requires PHP 7.4 or higher.', 'advanced-shortcode-snippet-engine') . '</p></div>';
    });
    return;
}

// Autoloader
require_once ASSE_PLUGIN_DIR . 'vendor/autoload.php';

// Core classes
require_once ASSE_PLUGIN_DIR . 'core/class-plugin.php';
require_once ASSE_PLUGIN_DIR . 'core/class-loader.php';
require_once ASSE_PLUGIN_DIR . 'core/class-activator.php';
require_once ASSE_PLUGIN_DIR . 'core/class-deactivator.php';
require_once ASSE_PLUGIN_DIR . 'core/class-i18n.php';

// Initialize plugin on plugins_loaded action
add_action('plugins_loaded', 'asse_init_plugin');

/**
 * Initialize the plugin
 */
function asse_init_plugin() {
    // Load text domain
    load_plugin_textdomain(
        'advanced-shortcode-snippet-engine',
        false,
        dirname(ASSE_PLUGIN_BASENAME) . '/languages'
    );
    
    // Check if dependencies are loaded
    if (!class_exists('ASSE\\Core\\Plugin')) {
        add_action('admin_notices', function() {
            echo '<div class="error"><p>' . esc_html__('Advanced Shortcode & Snippet Engine: Missing dependencies. Please ensure all files are present.', 'advanced-shortcode-snippet-engine') . '</p></div>';
        });
        return;
    }
    
    // Create plugin instance
    $plugin = new ASSE\Core\Plugin();
    
    // Register activation hook
    register_activation_hook(ASSE_PLUGIN_FILE, array('ASSE\\Core\\Activator', 'activate'));
    
    // Register deactivation hook
    register_deactivation_hook(ASSE_PLUGIN_FILE, array('ASSE\\Core\\Deactivator', 'deactivate'));
    
    // Run the plugin
    $plugin->run();
}
