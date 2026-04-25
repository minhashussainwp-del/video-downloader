<?php
/**
 * REST API Class
 * 
 * Registers REST API endpoints for the plugin.
 * 
 * @package ASSE\API
 * @since 1.0.0
 */

namespace ASSE\API;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class REST_API
 */
class REST_API {
    
    /**
     * REST API namespace
     *
     * @var string
     */
    protected $namespace = 'asse/v1';
    
    /**
     * Initialize the REST API class
     */
    public function __construct() {
        add_action('rest_api_init', array($this, 'register_routes'));
    }
    
    /**
     * Register REST API routes
     */
    public function register_routes() {
        // List all snippets
        register_rest_route($this->namespace, '/snippets', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_snippets'),
            'permission_callback' => array($this, 'check_api_permission'),
        ));
        
        // Get single snippet
        register_rest_route($this->namespace, '/snippets/(?P<id>\d+)', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_snippet'),
            'permission_callback' => array($this, 'check_api_permission'),
            'args' => array(
                'id' => array(
                    'required' => true,
                    'type' => 'integer',
                ),
            ),
        ));
        
        // Create snippet
        register_rest_route($this->namespace, '/snippets', array(
            'methods' => 'POST',
            'callback' => array($this, 'create_snippet'),
            'permission_callback' => array($this, 'check_api_permission'),
        ));
        
        // Update snippet
        register_rest_route($this->namespace, '/snippets/(?P<id>\d+)', array(
            'methods' => 'PUT',
            'callback' => array($this, 'update_snippet'),
            'permission_callback' => array($this, 'check_api_permission'),
            'args' => array(
                'id' => array(
                    'required' => true,
                    'type' => 'integer',
                ),
            ),
        ));
        
        // Delete snippet
        register_rest_route($this->namespace, '/snippets/(?P<id>\d+)', array(
            'methods' => 'DELETE',
            'callback' => array($this, 'delete_snippet'),
            'permission_callback' => array($this, 'check_api_permission'),
            'args' => array(
                'id' => array(
                    'required' => true,
                    'type' => 'integer',
                ),
            ),
        ));
        
        // Execute snippet
        register_rest_route($this->namespace, '/execute', array(
            'methods' => 'POST',
            'callback' => array($this, 'execute_snippet'),
            'permission_callback' => array($this, 'check_api_permission'),
        ));
        
        // List shortcodes
        register_rest_route($this->namespace, '/shortcodes', array(
            'methods' => 'GET',
            'callback' => array($this, 'get_shortcodes'),
            'permission_callback' => '__return_true',
        ));
        
        // Validate code
        register_rest_route($this->namespace, '/validate', array(
            'methods' => 'POST',
            'callback' => array($this, 'validate_code'),
            'permission_callback' => array($this, 'check_api_permission'),
        ));
    }
    
    /**
     * Check API permission
     *
     * @param \WP_REST_Request $request Request object.
     * @return bool|\WP_Error True if authorized, WP_Error otherwise.
     */
    public function check_api_permission($request) {
        // Check if API is enabled
        $settings = get_option('asse_settings', array());
        if (empty($settings['enable_api'])) {
            return new \WP_Error('rest_forbidden', __('API is disabled', 'advanced-shortcode-snippet-engine'), array('status' => 403));
        }
        
        // Check for API key in header
        $api_key = $request->get_header('X-ASSE-API-Key');
        
        if ($api_key) {
            $valid_keys = isset($settings['api_keys']) ? $settings['api_keys'] : array();
            if (in_array($api_key, $valid_keys)) {
                return true;
            }
        }
        
        // Fall back to WordPress authentication
        if (!current_user_can('manage_options')) {
            return new \WP_Error('rest_forbidden', __('Insufficient permissions', 'advanced-shortcode-snippet-engine'), array('status' => 403));
        }
        
        return true;
    }
    
    /**
     * Get all snippets
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response Response object.
     */
    public function get_snippets($request) {
        $db = new \ASSE\Database\DB();
        
        $args = array(
            'type' => $request->get_param('type'),
            'status' => $request->get_param('status'),
            'scope' => $request->get_param('scope'),
            'search' => $request->get_param('search'),
        );
        
        $snippets = $db->get_snippets($args);
        
        return rest_ensure_response(array(
            'success' => true,
            'data' => $snippets,
            'count' => count($snippets),
        ));
    }
    
    /**
     * Get single snippet
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response Response object.
     */
    public function get_snippet($request) {
        $db = new \ASSE\Database\DB();
        
        $snippet = $db->get_snippet($request->get_param('id'));
        
        if (!$snippet) {
            return rest_ensure_response(array(
                'success' => false,
                'message' => __('Snippet not found', 'advanced-shortcode-snippet-engine'),
            ));
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'data' => $snippet,
        ));
    }
    
    /**
     * Create snippet
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response Response object.
     */
    public function create_snippet($request) {
        $db = new \ASSE\Database\DB();
        
        $data = array(
            'title' => $request->get_param('title'),
            'slug' => $request->get_param('slug'),
            'type' => $request->get_param('type') ?: 'php',
            'code' => $request->get_param('code'),
            'status' => $request->get_param('status') ?: 'inactive',
            'priority' => $request->get_param('priority') ?: 10,
            'scope' => $request->get_param('scope') ?: 'global',
        );
        
        $result = $db->insert_snippet($data);
        
        if (is_wp_error($result)) {
            return rest_ensure_response(array(
                'success' => false,
                'message' => $result->get_error_message(),
            ));
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'message' => __('Snippet created successfully', 'advanced-shortcode-snippet-engine'),
            'id' => $result,
        ));
    }
    
    /**
     * Update snippet
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response Response object.
     */
    public function update_snippet($request) {
        $db = new \ASSE\Database\DB();
        
        $data = array(
            'title' => $request->get_param('title'),
            'slug' => $request->get_param('slug'),
            'type' => $request->get_param('type'),
            'code' => $request->get_param('code'),
            'status' => $request->get_param('status'),
            'priority' => $request->get_param('priority'),
            'scope' => $request->get_param('scope'),
        );
        
        // Remove null values
        $data = array_filter($data, function($value) {
            return $value !== null;
        });
        
        $result = $db->update_snippet($request->get_param('id'), $data);
        
        if (is_wp_error($result)) {
            return rest_ensure_response(array(
                'success' => false,
                'message' => $result->get_error_message(),
            ));
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'message' => __('Snippet updated successfully', 'advanced-shortcode-snippet-engine'),
        ));
    }
    
    /**
     * Delete snippet
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response Response object.
     */
    public function delete_snippet($request) {
        $db = new \ASSE\Database\DB();
        
        $result = $db->delete_snippet($request->get_param('id'));
        
        if (is_wp_error($result)) {
            return rest_ensure_response(array(
                'success' => false,
                'message' => $result->get_error_message(),
            ));
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'message' => __('Snippet deleted successfully', 'advanced-shortcode-snippet-engine'),
        ));
    }
    
    /**
     * Execute snippet
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response Response object.
     */
    public function execute_snippet($request) {
        $id = $request->get_param('id');
        
        if (!$id) {
            return rest_ensure_response(array(
                'success' => false,
                'message' => __('Snippet ID required', 'advanced-shortcode-snippet-engine'),
            ));
        }
        
        $db = new \ASSE\Database\DB();
        $snippet = $db->get_snippet($id);
        
        if (!$snippet) {
            return rest_ensure_response(array(
                'success' => false,
                'message' => __('Snippet not found', 'advanced-shortcode-snippet-engine'),
            ));
        }
        
        $executor_class = '\\ASSE\\Snippets\\' . ucfirst(strtolower($snippet['type'])) . '_Executor';
        
        if (!class_exists($executor_class)) {
            return rest_ensure_response(array(
                'success' => false,
                'message' => __('Executor not found', 'advanced-shortcode-snippet-engine'),
            ));
        }
        
        $executor = new $executor_class();
        $output = $executor->execute($snippet['code'], $snippet);
        
        return rest_ensure_response(array(
            'success' => true,
            'output' => $output,
        ));
    }
    
    /**
     * Get registered shortcodes
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response Response object.
     */
    public function get_shortcodes($request) {
        $shortcode_engine = new \ASSE\Frontend\ShortcodeEngine();
        $shortcodes = $shortcode_engine->get_registered_shortcodes();
        
        return rest_ensure_response(array(
            'success' => true,
            'data' => $shortcodes,
        ));
    }
    
    /**
     * Validate code
     *
     * @param \WP_REST_Request $request Request object.
     * @return \WP_REST_Response Response object.
     */
    public function validate_code($request) {
        $code = $request->get_param('code');
        $type = $request->get_param('type') ?: 'php';
        
        if ($type === 'php') {
            $validation = \ASSE\Security\Security::validate_php_syntax($code);
            
            return rest_ensure_response(array(
                'success' => $validation['valid'],
                'valid' => $validation['valid'],
                'error' => $validation['error'],
            ));
        }
        
        return rest_ensure_response(array(
            'success' => true,
            'message' => __('Validation not available for this type', 'advanced-shortcode-snippet-engine'),
        ));
    }
}
