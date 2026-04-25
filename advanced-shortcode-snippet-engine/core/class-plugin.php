<?php
/**
 * Main Plugin Class
 * 
 * Initializes and runs the Advanced Shortcode & Snippet Engine plugin.
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
 * Class Plugin
 */
class Plugin {
    
    /**
     * The loader that's responsible for maintaining and registering all hooks
     *
     * @var Loader
     */
    protected $loader;
    
    /**
     * Define the core version of the plugin
     *
     * @var string
     */
    protected $version;
    
    /**
     * Initialize the class and set its properties
     */
    public function __construct() {
        $this->version = ASSE_VERSION;
        $this->loader = new Loader();
        
        $this->define_core_hooks();
        $this->init_components();
    }
    
    /**
     * Define core WordPress hooks
     */
    private function define_core_hooks() {
        // Add admin menu
        $this->loader->add_action('admin_menu', $this, 'add_admin_menu');
        
        // Initialize admin components
        $this->loader->add_action('admin_init', $this, 'init_admin');
        
        // Initialize frontend components
        $this->loader->add_action('init', $this, 'init_frontend');
        
        // Register shortcodes
        $this->loader->add_action('init', $this, 'register_shortcodes');
        
        // Enqueue admin scripts and styles
        $this->loader->add_action('admin_enqueue_scripts', $this, 'enqueue_admin_assets');
        
        // Enqueue frontend scripts and styles
        $this->loader->add_action('wp_enqueue_scripts', $this, 'enqueue_frontend_assets');
        
        // Add plugin action links
        $this->loader->add_filter('plugin_action_links_' . ASSE_PLUGIN_BASENAME, $this, 'add_action_links');
    }
    
    /**
     * Initialize plugin components
     */
    private function init_components() {
        // Database initialization
        if (is_admin()) {
            new \ASSE\Database\DB();
        }
        
        // Security initialization
        new \ASSE\Security\Security();
        
        // Admin initialization
        if (is_admin()) {
            new \ASSE\Admin\Admin();
            new \ASSE\Admin\AdminMenu();
        }
        
        // Frontend initialization
        new \ASSE\Frontend\Frontend();
        new \ASSE\Frontend\ShortcodeEngine();
        
        // API initialization
        new \ASSE\API\REST_API();
        
        // Conditions manager
        new \ASSE\Conditions\ConditionManager();
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_menu_page(
            __('Advanced Shortcode Engine', 'advanced-shortcode-snippet-engine'),
            __('Snippet Engine', 'advanced-shortcode-snippet-engine'),
            'manage_options',
            'asse-dashboard',
            array($this, 'display_dashboard'),
            'dashicons-editor-code',
            30
        );
        
        add_submenu_page(
            'asse-dashboard',
            __('Dashboard', 'advanced-shortcode-snippet-engine'),
            __('Dashboard', 'advanced-shortcode-snippet-engine'),
            'manage_options',
            'asse-dashboard',
            array($this, 'display_dashboard')
        );
        
        add_submenu_page(
            'asse-dashboard',
            __('All Snippets', 'advanced-shortcode-snippet-engine'),
            __('All Snippets', 'advanced-shortcode-snippet-engine'),
            'manage_options',
            'asse-snippets',
            array($this, 'display_snippets')
        );
        
        add_submenu_page(
            'asse-dashboard',
            __('Add New', 'advanced-shortcode-snippet-engine'),
            __('Add New', 'advanced-shortcode-snippet-engine'),
            'manage_options',
            'asse-add-snippet',
            array($this, 'display_add_snippet')
        );
        
        add_submenu_page(
            'asse-dashboard',
            __('Categories & Tags', 'advanced-shortcode-snippet-engine'),
            __('Categories & Tags', 'advanced-shortcode-snippet-engine'),
            'manage_options',
            'asse-categories',
            array($this, 'display_categories')
        );
        
        add_submenu_page(
            'asse-dashboard',
            __('Import/Export', 'advanced-shortcode-snippet-engine'),
            __('Import/Export', 'advanced-shortcode-snippet-engine'),
            'manage_options',
            'asse-import-export',
            array($this, 'display_import_export')
        );
        
        add_submenu_page(
            'asse-dashboard',
            __('Settings', 'advanced-shortcode-snippet-engine'),
            __('Settings', 'advanced-shortcode-snippet-engine'),
            'manage_options',
            'asse-settings',
            array($this, 'display_settings')
        );
        
        add_submenu_page(
            'asse-dashboard',
            __('Tools & Debug', 'advanced-shortcode-snippet-engine'),
            __('Tools & Debug', 'advanced-shortcode-snippet-engine'),
            'manage_options',
            'asse-tools',
            array($this, 'display_tools')
        );
    }
    
    /**
     * Initialize admin components
     */
    public function init_admin() {
        // Admin-specific initialization
    }
    
    /**
     * Initialize frontend components
     */
    public function init_frontend() {
        // Frontend-specific initialization
    }
    
    /**
     * Register shortcodes
     */
    public function register_shortcodes() {
        // Shortcodes are registered by ShortcodeEngine
    }
    
    /**
     * Enqueue admin assets
     *
     * @param string $hook Current admin page hook.
     */
    public function enqueue_admin_assets($hook) {
        // Only load on plugin pages
        if (strpos($hook, 'asse-') === false) {
            return;
        }
        
        // Enqueue admin CSS
        wp_enqueue_style(
            'asse-admin-css',
            ASSE_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            $this->version
        );
        
        // Enqueue CodeMirror for code editor
        wp_enqueue_code_editor(array('type' => 'text/html'));
        wp_enqueue_script('wp-theme-plugin-editor');
        wp_enqueue_style('wp-codemirror');
        
        // Enqueue admin JS
        wp_enqueue_script(
            'asse-admin-js',
            ASSE_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery', 'wp-theme-plugin-editor'),
            $this->version,
            true
        );
        
        // Localize script
        wp_localize_script('asse-admin-js', 'asseAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('asse_admin_nonce'),
            'strings' => array(
                'confirmDelete' => __('Are you sure you want to delete this snippet?', 'advanced-shortcode-snippet-engine'),
                'saving' => __('Saving...', 'advanced-shortcode-snippet-engine'),
                'saved' => __('Saved!', 'advanced-shortcode-snippet-engine'),
                'error' => __('Error saving snippet', 'advanced-shortcode-snippet-engine'),
            )
        ));
    }
    
    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        // Enqueue frontend CSS if needed
        wp_enqueue_style(
            'asse-frontend-css',
            ASSE_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            $this->version
        );
        
        // Enqueue frontend JS if needed
        wp_enqueue_script(
            'asse-frontend-js',
            ASSE_PLUGIN_URL . 'assets/js/frontend.js',
            array('jquery'),
            $this->version,
            true
        );
    }
    
    /**
     * Add plugin action links
     *
     * @param array $links Existing action links.
     * @return array Modified action links.
     */
    public function add_action_links($links) {
        $custom_links = array(
            '<a href="' . admin_url('admin.php?page=asse-dashboard') . '">' . __('Dashboard', 'advanced-shortcode-snippet-engine') . '</a>',
            '<a href="' . admin_url('admin.php?page=asse-settings') . '">' . __('Settings', 'advanced-shortcode-snippet-engine') . '</a>',
        );
        
        return array_merge($custom_links, $links);
    }
    
    /**
     * Display dashboard page
     */
    public function display_dashboard() {
        include ASSE_PLUGIN_DIR . 'admin/views/dashboard.php';
    }
    
    /**
     * Display snippets list page
     */
    public function display_snippets() {
        include ASSE_PLUGIN_DIR . 'admin/views/snippets-list.php';
    }
    
    /**
     * Display add snippet page
     */
    public function display_add_snippet() {
        include ASSE_PLUGIN_DIR . 'admin/views/add-snippet.php';
    }
    
    /**
     * Display categories page
     */
    public function display_categories() {
        include ASSE_PLUGIN_DIR . 'admin/views/categories.php';
    }
    
    /**
     * Display import/export page
     */
    public function display_import_export() {
        include ASSE_PLUGIN_DIR . 'admin/views/import-export.php';
    }
    
    /**
     * Display settings page
     */
    public function display_settings() {
        include ASSE_PLUGIN_DIR . 'admin/views/settings.php';
    }
    
    /**
     * Display tools page
     */
    public function display_tools() {
        include ASSE_PLUGIN_DIR . 'admin/views/tools.php';
    }
    
    /**
     * Run the loader to execute all hooks
     */
    public function run() {
        $this->loader->run();
    }
    
    /**
     * Get the loader instance
     *
     * @return Loader
     */
    public function get_loader() {
        return $this->loader;
    }
    
    /**
     * Get the plugin version
     *
     * @return string
     */
    public function get_version() {
        return $this->version;
    }
}
