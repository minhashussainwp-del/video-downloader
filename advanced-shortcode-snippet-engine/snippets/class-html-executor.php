<?php
/**
 * HTML Executor Class
 * 
 * Executes HTML snippet code.
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
 * Class HTML_Executor
 */
class HTML_Executor implements Executor_Interface {
    
    /**
     * Execute HTML code
     *
     * @param string $code    Code to execute.
     * @param array  $snippet Snippet data.
     * @param array  $atts    Shortcode attributes (optional).
     * @param string $content Shortcode content (optional).
     * @return string HTML output.
     */
    public function execute($code, $snippet, $atts = array(), $content = '') {
        // Sanitize HTML (allow all tags for flexibility)
        $allowed_tags = wp_kses_allowed_html('post');
        
        // Allow additional tags commonly used in snippets
        $allowed_tags['style'] = array(
            'type' => true,
            'id' => true,
            'class' => true,
        );
        $allowed_tags['script'] = array(
            'type' => true,
            'id' => true,
            'src' => true,
            'async' => true,
            'defer' => true,
        );
        
        $code = wp_kses($code, $allowed_tags);
        
        return $code;
    }
}
