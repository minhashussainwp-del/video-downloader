<?php
/**
 * CSS Executor Class
 * 
 * Executes CSS snippet code by outputting in style tags.
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
 * Class CSS_Executor
 */
class CSS_Executor implements Executor_Interface {
    
    /**
     * Execute CSS code
     *
     * @param string $code    Code to execute.
     * @param array  $snippet Snippet data.
     * @param array  $atts    Shortcode attributes (optional).
     * @param string $content Shortcode content (optional).
     * @return string CSS output wrapped in style tags.
     */
    public function execute($code, $snippet, $atts = array(), $content = '') {
        // Sanitize CSS
        $code = \ASSE\Security\Security::sanitize_css($code);
        
        // Check if minification is enabled
        $settings = get_option('asse_settings', array());
        if (!empty($settings['minify_css'])) {
            $code = $this->minify_css($code);
        }
        
        // Determine output format based on scope
        if (isset($snippet['scope']) && $snippet['scope'] === 'shortcode') {
            // For shortcode, return inline style
            return '<style>' . $code . '</style>';
        } else {
            // For global execution, check if we're in header
            return '<style id="asse-css-' . esc_attr($snippet['slug']) . '">' . $code . '</style>';
        }
    }
    
    /**
     * Minify CSS code
     *
     * @param string $css CSS code to minify.
     * @return string Minified CSS.
     */
    private function minify_css($css) {
        // Remove comments
        $css = preg_replace('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $css);
        
        // Remove whitespace around symbols
        $css = preg_replace('/\s*([{}:;,])\s*/', '$1', $css);
        
        // Remove trailing semicolons before closing brace
        $css = preg_replace('/;}/', '}', $css);
        
        // Remove multiple newlines
        $css = preg_replace('/\n+/', '', $css);
        
        return trim($css);
    }
}
