<?php
/**
 * Condition Manager Class
 * 
 * Handles evaluation of snippet execution conditions.
 * 
 * @package ASSE\Conditions
 * @since 1.0.0
 */

namespace ASSE\Conditions;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class ConditionManager
 */
class ConditionManager {
    
    /**
     * Check if snippet should execute based on its conditions
     *
     * @param array $snippet Snippet data.
     * @return bool True if conditions pass, false otherwise.
     */
    public function check_conditions($snippet) {
        // If no conditions set, allow execution
        if (empty($snippet['conditions'])) {
            return true;
        }
        
        $conditions = json_decode($snippet['conditions'], true);
        
        if (!is_array($conditions)) {
            return true;
        }
        
        // Check all condition groups
        foreach ($conditions as $condition_type => $condition_value) {
            if (!$this->evaluate_condition($condition_type, $condition_value)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Evaluate a single condition
     *
     * @param string $type  Condition type.
     * @param mixed  $value Condition value.
     * @return bool True if condition passes.
     */
    private function evaluate_condition($type, $value) {
        switch ($type) {
            case 'location':
                return $this->check_location($value);
            
            case 'user':
                return $this->check_user($value);
            
            case 'device':
                return $this->check_device($value);
            
            case 'url':
                return $this->check_url($value);
            
            case 'time':
                return $this->check_time($value);
            
            case 'referrer':
                return $this->check_referrer($value);
            
            case 'cookie':
                return $this->check_cookie($value);
            
            default:
                return true;
        }
    }
    
    /**
     * Check location conditions
     *
     * @param array $value Location condition values.
     * @return bool True if location matches.
     */
    private function check_location($value) {
        if (empty($value['type']) || $value['type'] === 'all') {
            return true;
        }
        
        switch ($value['type']) {
            case 'singular':
                return is_singular($value['post_types'] ?? array());
            
            case 'archive':
                return is_archive();
            
            case 'home':
                return is_home();
            
            case 'front_page':
                return is_front_page();
            
            case 'category':
                return is_category($value['categories'] ?? array());
            
            case 'tag':
                return is_tag($value['tags'] ?? array());
            
            case 'taxonomy':
                return is_tax($value['taxonomies'] ?? array());
            
            case 'post_id':
                $post_ids = isset($value['post_ids']) ? array_map('intval', explode(',', $value['post_ids'])) : array();
                return is_singular() && in_array(get_the_ID(), $post_ids);
            
            case 'page_id':
                $page_ids = isset($value['page_ids']) ? array_map('intval', explode(',', $value['page_ids'])) : array();
                return is_page($page_ids);
            
            default:
                return true;
        }
    }
    
    /**
     * Check user conditions
     *
     * @param array $value User condition values.
     * @return bool True if user matches.
     */
    private function check_user($value) {
        // Logged in status
        if (isset($value['logged_in'])) {
            if ($value['logged_in'] === 'yes' && !is_user_logged_in()) {
                return false;
            }
            if ($value['logged_in'] === 'no' && is_user_logged_in()) {
                return false;
            }
        }
        
        // User roles
        if (!empty($value['roles'])) {
            $roles = is_array($value['roles']) ? $value['roles'] : explode(',', $value['roles']);
            $roles = array_map('trim', $roles);
            
            if (!is_user_logged_in()) {
                return false;
            }
            
            $user = wp_get_current_user();
            $user_roles = $user->roles;
            
            if (!array_intersect($roles, $user_roles)) {
                return false;
            }
        }
        
        // Specific user IDs
        if (!empty($value['user_ids'])) {
            $user_ids = is_array($value['user_ids']) ? $value['user_ids'] : explode(',', $value['user_ids']);
            $user_ids = array_map('intval', $user_ids);
            
            if (!is_user_logged_in()) {
                return false;
            }
            
            if (!in_array(get_current_user_id(), $user_ids)) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Check device conditions
     *
     * @param array $value Device condition values.
     * @return bool True if device matches.
     */
    private function check_device($value) {
        if (empty($value['type']) || $value['type'] === 'all') {
            return true;
        }
        
        $wp_is_mobile = wp_is_mobile();
        
        switch ($value['type']) {
            case 'mobile':
                return $wp_is_mobile;
            
            case 'desktop':
                return !$wp_is_mobile;
            
            default:
                return true;
        }
    }
    
    /**
     * Check URL conditions
     *
     * @param array $value URL condition values.
     * @return bool True if URL matches.
     */
    private function check_url($value) {
        if (empty($value['pattern'])) {
            return true;
        }
        
        $current_url = $this->get_current_url();
        $pattern = $value['pattern'];
        $match_type = $value['match_type'] ?? 'exact';
        
        switch ($match_type) {
            case 'exact':
                return $current_url === $pattern;
            
            case 'contains':
                return strpos($current_url, $pattern) !== false;
            
            case 'regex':
                return (bool) preg_match($pattern, $current_url);
            
            case 'starts_with':
                return strpos($current_url, $pattern) === 0;
            
            default:
                return true;
        }
    }
    
    /**
     * Check time conditions
     *
     * @param array $value Time condition values.
     * @return bool True if time matches.
     */
    private function check_time($value) {
        $current_time = current_time('timestamp');
        
        // Date range
        if (!empty($value['date_start'])) {
            $start = strtotime($value['date_start']);
            if ($current_time < $start) {
                return false;
            }
        }
        
        if (!empty($value['date_end'])) {
            $end = strtotime($value['date_end']);
            if ($current_time > $end) {
                return false;
            }
        }
        
        // Day of week
        if (!empty($value['days'])) {
            $current_day = date('N', $current_time); // 1 (Monday) to 7 (Sunday)
            $allowed_days = is_array($value['days']) ? $value['days'] : explode(',', $value['days']);
            $allowed_days = array_map('intval', $allowed_days);
            
            if (!in_array($current_day, $allowed_days)) {
                return false;
            }
        }
        
        // Time of day
        if (!empty($value['time_start']) && !empty($value['time_end'])) {
            $current_hour = date('H:i', $current_time);
            
            if ($current_hour < $value['time_start'] || $current_hour > $value['time_end']) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Check referrer conditions
     *
     * @param array $value Referrer condition values.
     * @return bool True if referrer matches.
     */
    private function check_referrer($value) {
        if (empty($value['domain'])) {
            return true;
        }
        
        $referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
        
        if (empty($referrer)) {
            return $value['match_type'] === 'none';
        }
        
        $referrer_host = parse_url($referrer, PHP_URL_HOST);
        $target_domain = $value['domain'];
        
        switch ($value['match_type'] ?? 'contains') {
            case 'exact':
                return $referrer_host === $target_domain;
            
            case 'contains':
                return strpos($referrer_host, $target_domain) !== false;
            
            case 'internal':
                return $referrer_host === parse_url(home_url(), PHP_URL_HOST);
            
            case 'external':
                return $referrer_host !== parse_url(home_url(), PHP_URL_HOST);
            
            default:
                return true;
        }
    }
    
    /**
     * Check cookie conditions
     *
     * @param array $value Cookie condition values.
     * @return bool True if cookie matches.
     */
    private function check_cookie($value) {
        if (empty($value['name'])) {
            return true;
        }
        
        $cookie_name = $value['name'];
        $cookie_exists = isset($_COOKIE[$cookie_name]);
        
        // Check if cookie should exist
        if (isset($value['exists'])) {
            if ($value['exists'] === 'yes' && !$cookie_exists) {
                return false;
            }
            if ($value['exists'] === 'no' && $cookie_exists) {
                return false;
            }
        }
        
        // Check cookie value
        if ($cookie_exists && isset($value['value'])) {
            $cookie_value = sanitize_text_field($_COOKIE[$cookie_name]);
            
            if ($cookie_value !== $value['value']) {
                return false;
            }
        }
        
        return true;
    }
    
    /**
     * Get current page URL
     *
     * @return string Current URL.
     */
    private function get_current_url() {
        $protocol = is_ssl() ? 'https://' : 'http://';
        $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
        $uri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '';
        
        return $protocol . $host . $uri;
    }
}
