<?php
/**
 * Frontend Class
 * 
 * Handles all frontend functionality for the plugin.
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
 * Class Frontend
 */
class Frontend {
    
    /**
     * Initialize the frontend class
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize frontend hooks
     */
    private function init_hooks() {
        // Execute global snippets at appropriate hooks
        add_action('wp_head', array($this, 'execute_header_snippets'), 1);
        add_action('wp_body_open', array($this, 'execute_body_open_snippets'), 1);
        add_action('wp_footer', array($this, 'execute_footer_snippets'), 1);
        
        // Add execution comments in debug mode
        add_action('wp_head', array($this, 'add_debug_comment'), 999);
    }
    
    /**
     * Execute header snippets
     */
    public function execute_header_snippets() {
        $snippets = $this->get_snippets_for_scope('global', 'header');
        
        foreach ($snippets as $snippet) {
            $this->execute_snippet($snippet, 'header');
        }
    }
    
    /**
     * Execute body open snippets
     */
    public function execute_body_open_snippets() {
        $snippets = $this->get_snippets_for_scope('global', 'body_open');
        
        foreach ($snippets as $snippet) {
            $this->execute_snippet($snippet, 'body_open');
        }
    }
    
    /**
     * Execute footer snippets
     */
    public function execute_footer_snippets() {
        $snippets = $this->get_snippets_for_scope('global', 'footer');
        
        foreach ($snippets as $snippet) {
            $this->execute_snippet($snippet, 'footer');
        }
    }
    
    /**
     * Get snippets for a specific scope and position
     *
     * @param string $scope    Snippet scope (global, shortcode, both).
     * @param string $position Position (header, body_open, footer).
     * @return array Array of snippets.
     */
    private function get_snippets_for_scope($scope, $position) {
        $db = new \ASSE\Database\DB();
        
        $args = array(
            'status' => 'active',
            'orderby' => 'priority',
            'order' => 'DESC',
        );
        
        if ($scope === 'global') {
            $args['scope'] = 'global';
        } elseif ($scope === 'both') {
            // Get both global and both scope snippets
            $all_snippets = $db->get_snippets(array('status' => 'active'));
            $filtered = array_filter($all_snippets, function($snippet) {
                return in_array($snippet['scope'], array('global', 'both'));
            });
            return $filtered;
        }
        
        $snippets = $db->get_snippets($args);
        
        // Filter by conditions
        $condition_manager = new \ASSE\Conditions\ConditionManager();
        $snippets = array_filter($snippets, function($snippet) use ($condition_manager) {
            return $condition_manager->check_conditions($snippet);
        });
        
        return $snippets;
    }
    
    /**
     * Execute a single snippet
     *
     * @param array  $snippet  Snippet data.
     * @param string $position Execution position.
     */
    private function execute_snippet($snippet, $position) {
        // Check if already executed (prevent duplicates)
        static $executed = array();
        $exec_key = $snippet['id'] . '_' . $position;
        
        if (isset($executed[$exec_key])) {
            return;
        }
        
        $executed[$exec_key] = true;
        
        // Start timing
        $start_time = microtime(true);
        $start_memory = memory_get_usage();
        
        try {
            // Get executor based on type
            $executor = $this->get_executor($snippet['type']);
            
            if ($executor) {
                $output = $executor->execute($snippet['code'], $snippet);
                
                // Output based on type
                if (!empty($output)) {
                    echo $output;
                }
                
                // Log successful execution
                $end_time = microtime(true);
                $end_memory = memory_get_usage();
                
                $execution_time = ($end_time - $start_time) * 1000; // ms
                $memory_used = $end_memory - $start_memory;
                
                $db = new \ASSE\Database\DB();
                $db->log_execution($snippet['id'], $execution_time, $memory_used, 'success');
            }
        } catch (\Exception $e) {
            // Log error
            $db = new \ASSE\Database\DB();
            $db->log_execution($snippet['id'], 0, 0, 'error', $e->getMessage());
            
            // Display error in debug mode
            $settings = get_option('asse_settings', array());
            if (!empty($settings['debug_mode'])) {
                echo '<!-- ASSE Error: ' . esc_html($e->getMessage()) . ' -->';
            }
        }
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
     * Add debug comment to page
     */
    public function add_debug_comment() {
        $settings = get_option('asse_settings', array());
        
        if (empty($settings['debug_mode'])) {
            return;
        }
        
        $db = new \ASSE\Database\DB();
        $total = $db->get_total_snippets();
        $counts = $db->count_by_status();
        
        echo "\n<!-- \n";
        echo "Advanced Shortcode & Snippet Engine Debug Info\n";
        echo "Total Snippets: {$total}\n";
        echo "Active: {$counts['active']} | Inactive: {$counts['inactive']} | Draft: {$counts['draft']}\n";
        echo "Execution Time: " . round(microtime(true) - $_SERVER['REQUEST_TIME'], 3) . "s\n";
        echo "-->\n";
    }
}
