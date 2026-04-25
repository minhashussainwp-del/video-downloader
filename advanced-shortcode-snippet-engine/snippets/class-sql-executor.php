<?php
/**
 * SQL Executor Class
 * 
 * Executes SQL snippet code (SELECT queries only for safety).
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
 * Class SQL_Executor
 */
class SQL_Executor implements Executor_Interface {
    
    /**
     * Execute SQL code
     *
     * @param string $code    Code to execute.
     * @param array  $snippet Snippet data.
     * @param array  $atts    Shortcode attributes (optional).
     * @param string $content Shortcode content (optional).
     * @return string Query results as HTML table or JSON.
     */
    public function execute($code, $snippet, $atts = array(), $content = '') {
        global $wpdb;
        
        // Sanitize SQL
        $code = \ASSE\Security\Security::sanitize_sql($code);
        
        // Check if query was blocked
        if (strpos($code, '-- Blocked:') === 0) {
            return '<!-- ' . esc_html($code) . ' -->';
        }
        
        try {
            // Execute query
            $results = $wpdb->get_results($code, ARRAY_A);
            
            if ($results === null) {
                // Query error
                return '<!-- SQL Error: ' . esc_html($wpdb->last_error) . ' -->';
            }
            
            // Format output based on context
            if (isset($atts['format']) && $atts['format'] === 'json') {
                return json_encode($results, JSON_PRETTY_PRINT);
            } elseif (isset($atts['format']) && $atts['format'] === 'csv') {
                return $this->format_as_csv($results);
            } else {
                // Default: HTML table
                return $this->format_as_table($results, $atts);
            }
        } catch (\Exception $e) {
            return '<!-- SQL Error: ' . esc_html($e->getMessage()) . ' -->';
        }
    }
    
    /**
     * Format results as HTML table
     *
     * @param array $results Query results.
     * @param array $atts    Shortcode attributes.
     * @return string HTML table.
     */
    private function format_as_table($results, $atts) {
        if (empty($results)) {
            return '<p>No results found.</p>';
        }
        
        $html = '<table class="asse-sql-results';
        if (isset($atts['class'])) {
            $html .= ' ' . esc_attr($atts['class']);
        }
        $html .= '">';
        
        // Table header
        $html .= '<thead><tr>';
        foreach (array_keys($results[0]) as $column) {
            $html .= '<th>' . esc_html($column) . '</th>';
        }
        $html .= '</tr></thead>';
        
        // Table body
        $html .= '<tbody>';
        foreach ($results as $row) {
            $html .= '<tr>';
            foreach ($row as $cell) {
                $html .= '<td>' . esc_html($cell) . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody>';
        
        $html .= '</table>';
        
        return $html;
    }
    
    /**
     * Format results as CSV
     *
     * @param array $results Query results.
     * @return string CSV formatted string.
     */
    private function format_as_csv($results) {
        if (empty($results)) {
            return '';
        }
        
        $output = fopen('php://temp', 'r+');
        
        // Header row
        fputcsv($output, array_keys($results[0]));
        
        // Data rows
        foreach ($results as $row) {
            fputcsv($output, $row);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return '<pre class="asse-csv-output">' . esc_html($csv) . '</pre>';
    }
}
