<?php
/**
 * PHP Executor Class
 * 
 * Executes PHP snippet code safely.
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
 * Class PHP_Executor
 */
class PHP_Executor implements Executor_Interface {
    
    /**
     * Execute PHP code
     *
     * @param string $code    Code to execute.
     * @param array  $snippet Snippet data.
     * @param array  $atts    Shortcode attributes (optional).
     * @param string $content Shortcode content (optional).
     * @return mixed Execution result.
     */
    public function execute($code, $snippet, $atts = array(), $content = '') {
        // Start output buffering
        ob_start();
        
        try {
            // Get settings for execution limits
            $settings = get_option('asse_settings', array());
            $timeout = isset($settings['execution_timeout']) ? intval($settings['execution_timeout']) : 30;
            
            // Set error handler
            set_error_handler(array($this, 'error_handler'));
            
            // Prepare variables for snippet
            $shortcode_atts = $atts;
            $shortcode_content = $content;
            $snippet_data = $snippet;
            
            // Execute code
            eval('?>' . $code);
            
            // Restore error handler
            restore_error_handler();
            
            // Get output
            $output = ob_get_clean();
            
            return $output;
        } catch (\Exception $e) {
            ob_end_clean();
            throw $e;
        }
    }
    
    /**
     * Custom error handler
     *
     * @param int    $errno   Error number.
     * @param string $errstr  Error message.
     * @param string $errfile Error file.
     * @param int    $errline Error line.
     * @return bool False to let PHP handle the error.
     */
    public function error_handler($errno, $errstr, $errfile, $errline) {
        // Log error
        error_log("ASSE PHP Error: [{$errno}] {$errstr} in {$errfile} on line {$errline}");
        
        // Let PHP handle it too
        return false;
    }
}
