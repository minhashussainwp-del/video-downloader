<?php
/**
 * Shortcode Engine Class
 * 
 * Handles registration and execution of shortcodes from snippets.
 * 
 * @package ASSE\Frontend
 * @since 1.0.0
 */

namespace ASSE\Frontend;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ShortcodeEngine
 */
class ShortcodeEngine {
    
    /**
     * Cache for registered shortcodes
     *
     * @var array
     */
    protected $registered_shortcodes = array();
    
    /**
     * Initialize the shortcode engine
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize shortcode hooks
     */
    private function init_hooks() {
        add_action('init', array($this, 'register_all_shortcodes'));
        add_filter('the_content', array($this, 'process_nested_shortcodes'), 7);
    }
    
    /**
     * Register all shortcodes from snippets
     */
    public function register_all_shortcodes() {
        $db = new \ASSE\Database\DB();
        
        // Get all active snippets with shortcode scope
        $snippets = $db->get_snippets(array(
            'status' => 'active',
        ));
        
        foreach ($snippets as $snippet) {
            if (in_array($snippet['scope'], array('shortcode', 'both'))) {
                $this->register_shortcode($snippet);
            }
        }
    }
    
    /**
     * Register a single shortcode
     *
     * @param array $snippet Snippet data.
     */
    public function register_shortcode($snippet) {
        $shortcode_tag = $this->generate_shortcode_tag($snippet);
        
        if (empty($shortcode_tag)) {
            return;
        }
        
        // Check if already registered
        if (shortcode_exists($shortcode_tag)) {
            return;
        }
        
        // Register shortcode
        add_shortcode($shortcode_tag, array($this, 'execute_shortcode'));
        
        // Store in cache
        $this->registered_shortcodes[$shortcode_tag] = $snippet;
    }
    
    /**
     * Generate shortcode tag from snippet
     *
     * @param array $snippet Snippet data.
     * @return string Shortcode tag.
     */
    private function generate_shortcode_tag($snippet) {
        // Use slug as shortcode tag, prefixed with 'asse_'
        $tag = 'asse_' . sanitize_title($snippet['slug']);
        
        /**
         * Filter to customize shortcode tag
         *
         * @param string $tag     Shortcode tag.
         * @param array  $snippet Snippet data.
         */
        return apply_filters('asse_shortcode_tag', $tag, $snippet);
    }
    
    /**
     * Execute shortcode callback
     *
     * @param array  $atts    Shortcode attributes.
     * @param string $content Shortcode content.
     * @param string $tag     Shortcode tag.
     * @return string Rendered output.
     */
    public function execute_shortcode($atts, $content = '', $tag = '') {
        // Get snippet from cache or database
        if (!isset($this->registered_shortcodes[$tag])) {
            $db = new \ASSE\Database\DB();
            $snippet = $db->get_snippet_by_slug(str_replace('asse_', '', $tag));
            
            if (!$snippet) {
                return '';
            }
            
            $this->registered_shortcodes[$tag] = $snippet;
        }
        
        $snippet = $this->registered_shortcodes[$tag];
        
        // Check conditions
        $condition_manager = new \ASSE\Conditions\ConditionManager();
        if (!$condition_manager->check_conditions($snippet)) {
            return '';
        }
        
        // Start timing
        $start_time = microtime(true);
        $start_memory = memory_get_usage();
        
        try {
            // Process merge tags in code
            $code = $this->process_merge_tags($snippet['code'], $atts, $content);
            
            // Get executor based on type
            $executor = $this->get_executor($snippet['type']);
            
            if ($executor) {
                $output = $executor->execute($code, $snippet, $atts, $content);
                
                // Log successful execution
                $end_time = microtime(true);
                $end_memory = memory_get_usage();
                
                $execution_time = ($end_time - $start_time) * 1000; // ms
                $memory_used = $end_memory - $start_memory;
                
                $db = new \ASSE\Database\DB();
                $db->log_execution($snippet['id'], $execution_time, $memory_used, 'success');
                
                /**
                 * Filter shortcode output
                 *
                 * @param string $output   Shortcode output.
                 * @param array  $atts     Shortcode attributes.
                 * @param string $content  Shortcode content.
                 * @param string $tag      Shortcode tag.
                 * @param array  $snippet  Snippet data.
                 */
                return apply_filters('asse_shortcode_output', $output, $atts, $content, $tag, $snippet);
            }
            
            return '';
        } catch (\Exception $e) {
            // Log error
            $db = new \ASSE\Database\DB();
            $db->log_execution($snippet['id'], 0, 0, 'error', $e->getMessage());
            
            // Display error in debug mode
            $settings = get_option('asse_settings', array());
            if (!empty($settings['debug_mode'])) {
                return '<!-- ASSE Shortcode Error: ' . esc_html($e->getMessage()) . ' -->';
            }
            
            return '';
        }
    }
    
    /**
     * Process merge tags in code
     *
     * @param string $code    Code to process.
     * @param array  $atts    Shortcode attributes.
     * @param string $content Shortcode content.
     * @return string Processed code.
     */
    private function process_merge_tags($code, $atts, $content) {
        $merge_tags = new \ASSE\Includes\MergeTags();
        
        // Process standard merge tags
        $code = $merge_tags->process($code);
        
        // Process attribute merge tags
        foreach ($atts as $key => $value) {
            $code = str_replace('{attr.' . $key . '}', $value, $code);
        }
        
        // Process content merge tag
        $code = str_replace('{content}', $content, $code);
        
        return $code;
    }
    
    /**
     * Get executor for snippet type
     *
     * @param string $type Snippet type.
     * @return object|null Executor object or null.
     */
    private function get_executor($type) {
        $executor_class = '\\ASSE\\Snippets\\' . ucfirst(strtolower($type)) . '_Executor';
        
        if (class_exists($executor_class)) {
            return new $executor_class();
        }
        
        return null;
    }
    
    /**
     * Process nested shortcodes in content
     *
     * @param string $content Content to process.
     * @return string Processed content.
     */
    public function process_nested_shortcodes($content) {
        // WordPress already handles this, but we can add custom processing here
        return $content;
    }
    
    /**
     * Get all registered shortcodes
     *
     * @return array Array of registered shortcode tags.
     */
    public function get_registered_shortcodes() {
        return array_keys($this->registered_shortcodes);
    }
    
    /**
     * Unregister a shortcode
     *
     * @param string $tag Shortcode tag to unregister.
     */
    public function unregister_shortcode($tag) {
        remove_shortcode($tag);
        unset($this->registered_shortcodes[$tag]);
    }
    
    /**
     * Clear shortcode cache
     */
    public function clear_cache() {
        $this->registered_shortcodes = array();
    }
}
