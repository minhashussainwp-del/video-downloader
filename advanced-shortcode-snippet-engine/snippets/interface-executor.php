<?php
/**
 * Interface Executor
 * 
 * Interface for all snippet executors.
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
 * Interface Executor
 */
interface Executor_Interface {
    
    /**
     * Execute the snippet code
     *
     * @param string $code    Code to execute.
     * @param array  $snippet Snippet data.
     * @param array  $atts    Shortcode attributes (optional).
     * @param string $content Shortcode content (optional).
     * @return mixed Execution result.
     */
    public function execute($code, $snippet, $atts = array(), $content = '');
}
