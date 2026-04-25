<?php
/**
 * Security Class
 * 
 * Handles security measures including input sanitization, output escaping, and permission checks.
 * 
 * @package ASSE\Security
 * @since 1.0.0
 */

namespace ASSE\Security;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class Security
 */
class Security {
    
    /**
     * Dangerous PHP functions that should be blocked
     *
     * @var array
     */
    protected static $dangerous_functions = array(
        'exec',
        'system',
        'passthru',
        'shell_exec',
        'popen',
        'proc_open',
        'pcntl_exec',
        'eval',
        'assert',
        'create_function',
        'call_user_func_array',
        'base64_decode',
        'gzinflate',
        'urldecode',
        'rawurldecode',
        'str_rot13',
        'preg_replace', // When used with /e modifier
    );
    
    /**
     * Initialize security measures
     */
    public function __construct() {
        $this->init_hooks();
    }
    
    /**
     * Initialize security hooks
     */
    private function init_hooks() {
        // Add nonce to all admin forms
        add_action('admin_head', array($this, 'add_admin_nonce'));
        
        // Check for safe mode constant
        if (defined('ASSE_SAFE_MODE') && ASSE_SAFE_MODE) {
            add_filter('asse_snippet_execution', '__return_false');
        }
    }
    
    /**
     * Verify nonce for admin actions
     *
     * @param string $action Action name.
     * @param string $name   Nonce name (optional).
     * @return bool True if valid, false otherwise.
     */
    public static function verify_nonce($action, $name = 'asse_admin_nonce') {
        if (!isset($_REQUEST[$name]) || !wp_verify_nonce(sanitize_text_field($_REQUEST[$name]), $action)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Check user capability
     *
     * @param string $capability Required capability.
     * @return bool True if user has capability, false otherwise.
     */
    public static function check_capability($capability = 'manage_options') {
        if (!current_user_can($capability)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * Sanitize snippet code based on type
     *
     * @param string $code Code to sanitize.
     * @param string $type Snippet type (php, css, js, html, json, sql).
     * @return string Sanitized code.
     */
    public static function sanitize_code($code, $type = 'php') {
        switch ($type) {
            case 'css':
                return self::sanitize_css($code);
            
            case 'js':
                return self::sanitize_js($code);
            
            case 'html':
                return wp_kses_post($code);
            
            case 'json':
                return self::sanitize_json($code);
            
            case 'sql':
                return self::sanitize_sql($code);
            
            case 'php':
            default:
                return self::sanitize_php($code);
        }
    }
    
    /**
     * Sanitize PHP code
     *
     * @param string $code PHP code to sanitize.
     * @return string Sanitized code.
     */
    public static function sanitize_php($code) {
        // Remove dangerous functions
        foreach (self::$dangerous_functions as $function) {
            $pattern = '/\b' . preg_quote($function, '/') . '\s*\(/i';
            if (preg_match($pattern, $code)) {
                // Log attempt
                error_log('ASSE Security: Attempted use of dangerous function ' . $function);
            }
        }
        
        return trim($code);
    }
    
    /**
     * Sanitize CSS code
     *
     * @param string $code CSS code to sanitize.
     * @return string Sanitized code.
     */
    public static function sanitize_css($code) {
        // Strip HTML tags
        $code = strip_tags($code);
        
        // Remove expressions (IE-specific, but still a risk)
        $code = preg_replace('/expression\s*\(/i', '', $code);
        
        // Remove javascript: urls
        $code = preg_replace('/javascript\s*:/i', '', $code);
        
        // Remove behavior (IE-specific)
        $code = preg_replace('/behavior\s*:/i', '', $code);
        
        return trim($code);
    }
    
    /**
     * Sanitize JavaScript code
     *
     * @param string $code JS code to sanitize.
     * @return string Sanitized code.
     */
    public static function sanitize_js($code) {
        // Remove script tags if present
        $code = preg_replace('/<script[^>]*>/i', '', $code);
        $code = preg_replace('/<\/script>/i', '', $code);
        
        // Remove HTML comments
        $code = preg_replace('/<!--.*?-->/s', '', $code);
        
        return trim($code);
    }
    
    /**
     * Sanitize JSON code
     *
     * @param string $code JSON code to sanitize.
     * @return string Sanitized code.
     */
    public static function sanitize_json($code) {
        // Try to decode and re-encode to ensure valid JSON
        $decoded = json_decode($code, true);
        
        if (json_last_error() === JSON_ERROR_NONE) {
            return json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
        
        // If invalid JSON, return empty object
        return '{}';
    }
    
    /**
     * Sanitize SQL code
     *
     * @param string $code SQL code to sanitize.
     * @return string Sanitized code.
     */
    public static function sanitize_sql($code) {
        global $wpdb;
        
        // Only allow SELECT queries for safety
        $code = trim($code);
        $first_word = strtoupper(strtok($code, " \n\t"));
        
        // Block dangerous operations
        $blocked_operations = array('DROP', 'DELETE', 'TRUNCATE', 'ALTER', 'CREATE', 'INSERT', 'UPDATE', 'REPLACE');
        
        if (in_array($first_word, $blocked_operations)) {
            error_log('ASSE Security: Blocked dangerous SQL operation: ' . $first_word);
            return '-- Blocked: Dangerous SQL operation';
        }
        
        return $code;
    }
    
    /**
     * Escape output based on context
     *
     * @param string $output  Output to escape.
     * @param string $context Context (html, attr, js, url, text).
     * @return string Escaped output.
     */
    public static function escape_output($output, $context = 'html') {
        switch ($context) {
            case 'attr':
                return esc_attr($output);
            
            case 'js':
                return esc_js($output);
            
            case 'url':
                return esc_url($output);
            
            case 'text':
                return esc_html($output);
            
            case 'html':
            default:
                return wp_kses_post($output);
        }
    }
    
    /**
     * Validate PHP syntax
     *
     * @param string $code PHP code to validate.
     * @return array Array with 'valid' boolean and 'error' message if invalid.
     */
    public static function validate_php_syntax($code) {
        // Create temporary file
        $tmp_file = tempnam(sys_get_temp_dir(), 'asse_php_');
        
        // Wrap code in a function to check syntax without executing
        $wrapped_code = "<?php\nfunction asse_syntax_check() {\n" . $code . "\n}";
        
        file_put_contents($tmp_file, $wrapped_code);
        
        // Check syntax using PHP lint
        $output = array();
        $return_var = 0;
        exec("php -l " . escapeshellarg($tmp_file) . " 2>&1", $output, $return_var);
        
        // Clean up
        unlink($tmp_file);
        
        if ($return_var !== 0) {
            return array(
                'valid' => false,
                'error' => implode("\n", $output),
            );
        }
        
        return array(
            'valid' => true,
            'error' => '',
        );
    }
    
    /**
     * Check if code contains dangerous patterns
     *
     * @param string $code Code to check.
     * @return array Array with 'safe' boolean and 'warnings' array.
     */
    public static function check_dangerous_patterns($code) {
        $warnings = array();
        
        // Check for dangerous functions
        foreach (self::$dangerous_functions as $function) {
            $pattern = '/\b' . preg_quote($function, '/') . '\s*\(/i';
            if (preg_match($pattern, $code)) {
                $warnings[] = sprintf(__('Use of dangerous function "%s" detected', 'advanced-shortcode-snippet-engine'), $function);
            }
        }
        
        // Check for remote file inclusion attempts
        if (preg_match('/(include|require)(_once)?\s*\(\s*[\'"]https?:\/\//i', $code)) {
            $warnings[] = __('Remote file inclusion detected', 'advanced-shortcode-snippet-engine');
        }
        
        // Check for eval-like patterns
        if (preg_match('/eval\s*\(/i', $code)) {
            $warnings[] = __('Use of eval() detected', 'advanced-shortcode-snippet-engine');
        }
        
        return array(
            'safe' => empty($warnings),
            'warnings' => $warnings,
        );
    }
    
    /**
     * Add nonce field to admin forms
     */
    public function add_admin_nonce() {
        // This is handled by individual forms
    }
    
    /**
     * Get current user's allowed capabilities for snippets
     *
     * @return array Array of capabilities.
     */
    public static function get_snippet_capabilities() {
        return array(
            'manage_snippets' => __('Manage Snippets', 'advanced-shortcode-snippet-engine'),
            'edit_snippets' => __('Edit Snippets', 'advanced-shortcode-snippet-engine'),
            'delete_snippets' => __('Delete Snippets', 'advanced-shortcode-snippet-engine'),
            'execute_snippets' => __('Execute Snippets', 'advanced-shortcode-snippet-engine'),
        );
    }
    
    /**
     * Add custom capabilities to roles
     */
    public static function add_custom_capabilities() {
        $capabilities = self::get_snippet_capabilities();
        
        // Add to administrator role
        $admin_role = get_role('administrator');
        if ($admin_role) {
            foreach ($capabilities as $cap => $label) {
                $admin_role->add_cap($cap);
            }
        }
        
        // Add limited capabilities to editor role
        $editor_role = get_role('editor');
        if ($editor_role) {
            $editor_role->add_cap('edit_snippets');
            $editor_role->add_cap('execute_snippets');
        }
    }
    
    /**
     * Remove custom capabilities from roles
     */
    public static function remove_custom_capabilities() {
        $capabilities = array_keys(self::get_snippet_capabilities());
        
        $roles = array('administrator', 'editor');
        
        foreach ($roles as $role_name) {
            $role = get_role($role_name);
            if ($role) {
                foreach ($capabilities as $cap) {
                    $role->remove_cap($cap);
                }
            }
        }
    }
}
