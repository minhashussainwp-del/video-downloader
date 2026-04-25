<?php
/**
 * JavaScript Executor Class
 * 
 * Executes JavaScript snippet code by outputting in script tags.
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
 * Class JS_Executor
 */
class JS_Executor implements Executor_Interface {
    
    /**
     * Execute JavaScript code
     *
     * @param string $code    Code to execute.
     * @param array  $snippet Snippet data.
     * @param array  $atts    Shortcode attributes (optional).
     * @param string $content Shortcode content (optional).
     * @return string JavaScript output wrapped in script tags.
     */
    public function execute($code, $snippet, $atts = array(), $content = '') {
        // Sanitize JavaScript
        $code = \ASSE\Security\Security::sanitize_js($code);
        
        // Check if minification is enabled
        $settings = get_option('asse_settings', array());
        if (!empty($settings['minify_js'])) {
            $code = $this->minify_js($code);
        }
        
        // Determine output format based on scope and position
        if (isset($snippet['scope']) && $snippet['scope'] === 'shortcode') {
            // For shortcode, return inline script
            return '<script>' . $code . '</script>';
        } else {
            // For global execution
            return '<script id="asse-js-' . esc_attr($snippet['slug']) . '">' . $code . '</script>';
        }
    }
    
    /**
     * Minify JavaScript code (basic)
     *
     * @param string $js JavaScript code to minify.
     * @return string Minified JavaScript.
     */
    private function minify_js($js) {
        // Remove single-line comments
        $js = preg_replace('/\/\/.*$/m', '', $js);
        
        // Remove multi-line comments
        $js = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $js);
        
        // Remove leading/trailing whitespace from lines
        $lines = explode("\n", $js);
        $lines = array_map('trim', $lines);
        $js = implode('', $lines);
        
        // Remove multiple spaces
        $js = preg_replace('/\s+/', ' ', $js);
        
        return trim($js);
    }
}
