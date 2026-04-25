<?php
/**
 * JSON Executor Class
 * 
 * Executes JSON snippet code (typically for API responses or data).
 * 
 * @package ASSE\Snippets
 * @since 1.0.0
 */

namespace ASSE\Snippets;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class JSON_Executor
 */
class JSON_Executor implements Executor_Interface {
    
    /**
     * Execute JSON code
     *
     * @param string $code    Code to execute.
     * @param array  $snippet Snippet data.
     * @param array  $atts    Shortcode attributes (optional).
     * @param string $content Shortcode content (optional).
     * @return string JSON output.
     */
    public function execute($code, $snippet, $atts = array(), $content = '') {
        // Sanitize JSON
        $code = \ASSE\Security\Security::sanitize_json($code);
        
        // Check if this is being used as a shortcode or globally
        if (isset($snippet['scope']) && $snippet['scope'] === 'shortcode') {
            // For shortcode, return formatted JSON in pre tag
            return '<pre class="asse-json-output">' . esc_html($code) . '</pre>';
        } else {
            // For global execution, could be used for AJAX responses etc.
            // Just return the JSON as-is
            return $code;
        }
    }
}
