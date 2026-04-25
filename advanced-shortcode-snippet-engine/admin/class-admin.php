<?php
/**
 * Admin Class
 * 
 * Handles all admin functionality for the plugin.
 * 
 * @package ASSE\Admin
 * @since 1.0.0
 */

namespace ASSE\Admin;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Admin
 */
class Admin {
    
    /**
     * Initialize the admin class
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize admin hooks
     */
    private function init_hooks() {
        // Add admin notices
        add_action('admin_notices', array($this, 'admin_notices'));
        
        // Handle AJAX requests
        add_action('wp_ajax_asse_save_snippet', array($this, 'ajax_save_snippet'));
        add_action('wp_ajax_asse_delete_snippet', array($this, 'ajax_delete_snippet'));
        add_action('wp_ajax_asse_toggle_status', array($this, 'ajax_toggle_status'));
        add_action('wp_ajax_asse_validate_code', array($this, 'ajax_validate_code'));
        
        // Add meta boxes for post types
        add_action('add_meta_boxes', array($this, 'add_meta_boxes'));
        
        // Save meta box data
        add_action('save_post', array($this, 'save_meta_boxes'));
    }
    
    /**
     * Display admin notices
     */
    public function admin_notices() {
        // Check for safe mode
        if (defined('ASSE_SAFE_MODE') && ASSE_SAFE_MODE) {
            echo '<div class="error"><p><strong>' . esc_html__('Advanced Shortcode Engine Safe Mode is active. All snippet execution is disabled.', 'advanced-shortcode-snippet-engine') . '</strong></p></div>';
        }
        
        // Check PHP version
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            echo '<div class="error"><p>' . esc_html__('Advanced Shortcode Engine requires PHP 7.4 or higher. Please upgrade your PHP version.', 'advanced-shortcode-snippet-engine') . '</p></div>';
        }
    }
    
    /**
     * AJAX handler for saving snippets
     */
    public function ajax_save_snippet() {
        // Verify nonce
        if (!check_admin_referer('asse_admin_nonce', 'nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'advanced-shortcode-snippet-engine')));
        }
        
        // Check capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'advanced-shortcode-snippet-engine')));
        }
        
        // Get POST data
        $data = isset($_POST['snippet']) ? $_POST['snippet'] : array();
        
        if (empty($data)) {
            wp_send_json_error(array('message' => __('No data provided', 'advanced-shortcode-snippet-engine')));
        }
        
        $db = new \ASSE\Database\DB();
        
        // Validate PHP syntax if type is PHP
        if (isset($data['type']) && $data['type'] === 'php') {
            $validation = \ASSE\Security\Security::validate_php_syntax($data['code']);
            if (!$validation['valid']) {
                wp_send_json_error(array('message' => __('PHP Syntax Error:', 'advanced-shortcode-snippet-engine') . ' ' . $validation['error']));
            }
        }
        
        // Insert or update
        if (isset($data['id']) && !empty($data['id'])) {
            $result = $db->update_snippet(intval($data['id']), $data);
        } else {
            $result = $db->insert_snippet($data);
        }
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success(array(
            'message' => __('Snippet saved successfully', 'advanced-shortcode-snippet-engine'),
            'id' => $result,
        ));
    }
    
    /**
     * AJAX handler for deleting snippets
     */
    public function ajax_delete_snippet() {
        // Verify nonce
        if (!check_admin_referer('asse_admin_nonce', 'nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'advanced-shortcode-snippet-engine')));
        }
        
        // Check capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'advanced-shortcode-snippet-engine')));
        }
        
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        
        if (!$id) {
            wp_send_json_error(array('message' => __('Invalid snippet ID', 'advanced-shortcode-snippet-engine')));
        }
        
        $db = new \ASSE\Database\DB();
        $result = $db->delete_snippet($id);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success(array('message' => __('Snippet deleted successfully', 'advanced-shortcode-snippet-engine')));
    }
    
    /**
     * AJAX handler for toggling snippet status
     */
    public function ajax_toggle_status() {
        // Verify nonce
        if (!check_admin_referer('asse_admin_nonce', 'nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'advanced-shortcode-snippet-engine')));
        }
        
        // Check capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'advanced-shortcode-snippet-engine')));
        }
        
        $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
        $new_status = isset($_POST['status']) ? sanitize_text_field($_POST['status']) : 'inactive';
        
        if (!$id) {
            wp_send_json_error(array('message' => __('Invalid snippet ID', 'advanced-shortcode-snippet-engine')));
        }
        
        $db = new \ASSE\Database\DB();
        $result = $db->update_snippet($id, array('status' => $new_status));
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        wp_send_json_success(array('message' => __('Status updated', 'advanced-shortcode-snippet-engine')));
    }
    
    /**
     * AJAX handler for validating code
     */
    public function ajax_validate_code() {
        // Verify nonce
        if (!check_admin_referer('asse_admin_nonce', 'nonce')) {
            wp_send_json_error(array('message' => __('Security check failed', 'advanced-shortcode-snippet-engine')));
        }
        
        $code = isset($_POST['code']) ? wp_unslash($_POST['code']) : '';
        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'php';
        
        if ($type === 'php') {
            $validation = \ASSE\Security\Security::validate_php_syntax($code);
            
            if ($validation['valid']) {
                wp_send_json_success(array('message' => __('Syntax is valid', 'advanced-shortcode-snippet-engine')));
            } else {
                wp_send_json_error(array('message' => $validation['error']));
            }
        } else {
            wp_send_json_success(array('message' => __('Validation not available for this type', 'advanced-shortcode-snippet-engine')));
        }
    }
    
    /**
     * Add meta boxes to post types
     */
    public function add_meta_boxes() {
        add_meta_box(
            'asse_snippet_override',
            __('Snippet Engine Overrides', 'advanced-shortcode-snippet-engine'),
            array($this, 'render_meta_box'),
            null,
            'side',
            'default'
        );
    }
    
    /**
     * Render meta box content
     *
     * @param \WP_Post $post Current post object.
     */
    public function render_meta_box($post) {
        wp_nonce_field('asse_save_meta', 'asse_meta_nonce');
        
        $disabled_snippets = get_post_meta($post->ID, '_asse_disabled_snippets', true);
        $disabled_snippets = is_array($disabled_snippets) ? $disabled_snippets : array();
        
        $db = new \ASSE\Database\DB();
        $snippets = $db->get_snippets(array('status' => 'active'));
        
        echo '<p>' . esc_html__('Disable specific snippets on this page:', 'advanced-shortcode-snippet-engine') . '</p>';
        echo '<select name="asse_disabled_snippets[]" multiple style="width: 100%;">';
        
        foreach ($snippets as $snippet) {
            $selected = in_array($snippet['id'], $disabled_snippets) ? ' selected' : '';
            echo '<option value="' . esc_attr($snippet['id']) . '"' . $selected . '>' . esc_html($snippet['title']) . '</option>';
        }
        
        echo '</select>';
        echo '<p class="description">' . esc_html__('Hold Ctrl/Cmd to select multiple', 'advanced-shortcode-snippet-engine') . '</p>';
    }
    
    /**
     * Save meta box data
     *
     * @param int $post_id Post ID.
     */
    public function save_meta_boxes($post_id) {
        // Verify nonce
        if (!isset($_POST['asse_meta_nonce']) || !wp_verify_nonce($_POST['asse_meta_nonce'], 'asse_save_meta')) {
            return;
        }
        
        // Check autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check capability
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Save disabled snippets
        if (isset($_POST['asse_disabled_snippets'])) {
            $disabled = array_map('intval', $_POST['asse_disabled_snippets']);
            update_post_meta($post_id, '_asse_disabled_snippets', $disabled);
        } else {
            delete_post_meta($post_id, '_asse_disabled_snippets');
        }
    }
}
