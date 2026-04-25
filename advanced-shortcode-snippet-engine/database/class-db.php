<?php
/**
 * Database Class
 * 
 * Handles all database operations for the plugin.
 * 
 * @package ASSE\Database
 * @since 1.0.0
 */

namespace ASSE\Database;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class DB
 */
class DB {
    
    /**
     * Global WordPress database object
     *
     * @var \wpdb
     */
    protected $db;
    
    /**
     * Snippets table name
     *
     * @var string
     */
    protected $snippets_table;
    
    /**
     * Revisions table name
     *
     * @var string
     */
    protected $revisions_table;
    
    /**
     * Execution logs table name
     *
     * @var string
     */
    protected $logs_table;
    
    /**
     * Initialize the database class
     */
    public function __construct() {
        global $wpdb;
        
        $this->db = $wpdb;
        $this->snippets_table = $wpdb->prefix . 'asse_snippets';
        $this->revisions_table = $wpdb->prefix . 'asse_snippets_revisions';
        $this->logs_table = $wpdb->prefix . 'asse_execution_logs';
    }
    
    /**
     * Get a snippet by ID
     *
     * @param int $id Snippet ID.
     * @return array|null Snippet data or null if not found.
     */
    public function get_snippet($id) {
        $result = $this->db->get_row(
            $this->db->prepare("SELECT * FROM {$this->snippets_table} WHERE id = %d", $id),
            ARRAY_A
        );
        
        return $result;
    }
    
    /**
     * Get a snippet by slug
     *
     * @param string $slug Snippet slug.
     * @return array|null Snippet data or null if not found.
     */
    public function get_snippet_by_slug($slug) {
        $result = $this->db->get_row(
            $this->db->prepare("SELECT * FROM {$this->snippets_table} WHERE slug = %s", $slug),
            ARRAY_A
        );
        
        return $result;
    }
    
    /**
     * Get all snippets with optional filters
     *
     * @param array $args Query arguments.
     * @return array Array of snippets.
     */
    public function get_snippets($args = array()) {
        $defaults = array(
            'type' => null,
            'status' => null,
            'scope' => null,
            'search' => null,
            'orderby' => 'updated_at',
            'order' => 'DESC',
            'limit' => -1,
            'offset' => 0,
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = array('1=1');
        $values = array();
        
        if ($args['type']) {
            $where[] = 'type = %s';
            $values[] = $args['type'];
        }
        
        if ($args['status']) {
            $where[] = 'status = %s';
            $values[] = $args['status'];
        }
        
        if ($args['scope']) {
            $where[] = 'scope = %s';
            $values[] = $args['scope'];
        }
        
        if ($args['search']) {
            $where[] = '(title LIKE %s OR description LIKE %s OR code LIKE %s)';
            $search_term = '%' . $this->db->esc_like($args['search']) . '%';
            $values[] = $search_term;
            $values[] = $search_term;
            $values[] = $search_term;
        }
        
        $where_clause = implode(' AND ', $where);
        
        $query = "SELECT * FROM {$this->snippets_table} WHERE {$where_clause}";
        $query .= $this->db->prepare(" ORDER BY {$args['orderby']} {$args['order']}", '');
        
        if ($args['limit'] > 0) {
            $query .= $this->db->prepare(' LIMIT %d OFFSET %d', $args['limit'], $args['offset']);
        }
        
        if (!empty($values)) {
            $query = $this->db->prepare($query, $values);
        }
        
        $results = $this->db->get_results($query, ARRAY_A);
        
        return $results ? $results : array();
    }
    
    /**
     * Insert a new snippet
     *
     * @param array $data Snippet data.
     * @return int|WP_Error Snippet ID on success, WP_Error on failure.
     */
    public function insert_snippet($data) {
        $defaults = array(
            'title' => '',
            'slug' => '',
            'type' => 'php',
            'code' => '',
            'status' => 'inactive',
            'priority' => 10,
            'scope' => 'global',
            'conditions' => '',
            'tags' => '',
            'categories' => '',
            'author_id' => get_current_user_id(),
            'modified_by' => get_current_user_id(),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
            'version' => 1,
        );
        
        $data = wp_parse_args($data, $defaults);
        
        // Generate slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = $this->generate_unique_slug($data['title']);
        }
        
        $inserted = $this->db->insert($this->snippets_table, $data);
        
        if ($inserted === false) {
            return new \WP_Error('db_insert_error', $this->db->last_error);
        }
        
        return $this->db->insert_id;
    }
    
    /**
     * Update an existing snippet
     *
     * @param int   $id   Snippet ID.
     * @param array $data Snippet data to update.
     * @return bool|WP_Error True on success, WP_Error on failure.
     */
    public function update_snippet($id, $data) {
        $data['updated_at'] = current_time('mysql');
        $data['modified_by'] = get_current_user_id();
        
        // Increment version
        $existing = $this->get_snippet($id);
        if ($existing) {
            $data['version'] = $existing['version'] + 1;
            
            // Create revision before updating
            $this->create_revision($id, $existing['code']);
        }
        
        $updated = $this->db->update(
            $this->snippets_table,
            $data,
            array('id' => $id)
        );
        
        if ($updated === false) {
            return new \WP_Error('db_update_error', $this->db->last_error);
        }
        
        return true;
    }
    
    /**
     * Delete a snippet
     *
     * @param int $id Snippet ID.
     * @return bool|WP_Error True on success, WP_Error on failure.
     */
    public function delete_snippet($id) {
        // Delete associated revisions
        $this->db->delete(
            $this->revisions_table,
            array('snippet_id' => $id)
        );
        
        // Delete associated logs
        $this->db->delete(
            $this->logs_table,
            array('snippet_id' => $id)
        );
        
        // Delete the snippet
        $deleted = $this->db->delete(
            $this->snippets_table,
            array('id' => $id)
        );
        
        if ($deleted === false) {
            return new \WP_Error('db_delete_error', $this->db->last_error);
        }
        
        return true;
    }
    
    /**
     * Create a revision for a snippet
     *
     * @param int    $snippet_id Snippet ID.
     * @param string $code       Code to save as revision.
     * @param string $note       Optional change note.
     * @return int|WP_Error Revision ID on success, WP_Error on failure.
     */
    public function create_revision($snippet_id, $code, $note = '') {
        $data = array(
            'snippet_id' => $snippet_id,
            'code' => $code,
            'modified_by' => get_current_user_id(),
            'changed_at' => current_time('mysql'),
            'change_note' => $note,
        );
        
        $inserted = $this->db->insert($this->revisions_table, $data);
        
        if ($inserted === false) {
            return new \WP_Error('db_insert_error', $this->db->last_error);
        }
        
        $revision_id = $this->db->insert_id;
        
        // Cleanup old revisions if exceeding limit
        $this->cleanup_revisions($snippet_id);
        
        return $revision_id;
    }
    
    /**
     * Get revisions for a snippet
     *
     * @param int $snippet_id Snippet ID.
     * @param int $limit      Maximum number of revisions to return.
     * @return array Array of revisions.
     */
    public function get_revisions($snippet_id, $limit = 10) {
        $results = $this->db->get_results(
            $this->db->prepare(
                "SELECT * FROM {$this->revisions_table} WHERE snippet_id = %d ORDER BY changed_at DESC LIMIT %d",
                $snippet_id,
                $limit
            ),
            ARRAY_A
        );
        
        return $results ? $results : array();
    }
    
    /**
     * Cleanup old revisions, keeping only the most recent ones
     *
     * @param int $snippet_id Snippet ID.
     */
    public function cleanup_revisions($snippet_id) {
        $settings = get_option('asse_settings', array());
        $max_revisions = isset($settings['max_revisions']) ? intval($settings['max_revisions']) : 10;
        
        // Get revision IDs to keep
        $keep_ids = $this->db->get_col(
            $this->db->prepare(
                "SELECT id FROM {$this->revisions_table} WHERE snippet_id = %d ORDER BY changed_at DESC LIMIT %d",
                $snippet_id,
                $max_revisions
            )
        );
        
        if (!empty($keep_ids)) {
            $keep_ids_str = implode(',', array_map('intval', $keep_ids));
            $this->db->query(
                "DELETE FROM {$this->revisions_table} WHERE snippet_id = {$snippet_id} AND id NOT IN ({$keep_ids_str})"
            );
        }
    }
    
    /**
     * Log snippet execution
     *
     * @param int    $snippet_id     Snippet ID.
     * @param float  $execution_time Execution time in milliseconds.
     * @param int    $memory_usage   Memory usage in bytes.
     * @param string $status         Execution status (success, error, skipped).
     * @param string $message        Optional message or error.
     * @return int|WP_Error Log ID on success, WP_Error on failure.
     */
    public function log_execution($snippet_id, $execution_time, $memory_usage, $status = 'success', $message = '') {
        $data = array(
            'snippet_id' => $snippet_id,
            'execution_time' => $execution_time,
            'memory_usage' => $memory_usage,
            'status' => $status,
            'message' => $message,
            'created_at' => current_time('mysql'),
        );
        
        $inserted = $this->db->insert($this->logs_table, $data);
        
        if ($inserted === false) {
            return new \WP_Error('db_insert_error', $this->db->last_error);
        }
        
        return $this->db->insert_id;
    }
    
    /**
     * Generate a unique slug
     *
     * @param string $title Title to generate slug from.
     * @return string Unique slug.
     */
    private function generate_unique_slug($title) {
        $slug = sanitize_title($title);
        $original_slug = $slug;
        $counter = 1;
        
        while ($this->get_snippet_by_slug($slug)) {
            $slug = $original_slug . '-' . $counter;
            $counter++;
        }
        
        return $slug;
    }
    
    /**
     * Count snippets by status
     *
     * @return array Array of counts by status.
     */
    public function count_by_status() {
        $results = $this->db->get_results(
            "SELECT status, COUNT(*) as count FROM {$this->snippets_table} GROUP BY status",
            ARRAY_A
        );
        
        $counts = array('active' => 0, 'inactive' => 0, 'draft' => 0);
        
        foreach ($results as $row) {
            $counts[$row['status']] = intval($row['count']);
        }
        
        return $counts;
    }
    
    /**
     * Get total number of snippets
     *
     * @return int Total count.
     */
    public function get_total_snippets() {
        return (int) $this->db->get_var("SELECT COUNT(*) FROM {$this->snippets_table}");
    }
}
