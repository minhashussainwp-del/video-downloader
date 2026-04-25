<?php
/**
 * Merge Tags Class
 * 
 * Handles processing of merge tags (smart variables) in snippet code.
 * 
 * @package ASSE\Includes
 * @since 1.0.0
 */

namespace ASSE\Includes;

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Class MergeTags
 */
class MergeTags {
    
    /**
     * Process all merge tags in content
     *
     * @param string $content Content to process.
     * @return string Processed content.
     */
    public function process($content) {
        // User data tags
        $content = $this->process_user_tags($content);
        
        // Post data tags
        $content = $this->process_post_tags($content);
        
        // Site data tags
        $content = $this->process_site_tags($content);
        
        // Date/time tags
        $content = $this->process_date_tags($content);
        
        // URL tags
        $content = $this->process_url_tags($content);
        
        // Request parameter tags
        $content = $this->process_request_tags($content);
        
        // Server tags
        $content = $this->process_server_tags($content);
        
        /**
         * Filter to add custom merge tag processing
         *
         * @param string $content Processed content.
         */
        return apply_filters('asse_merge_tags_processed', $content);
    }
    
    /**
     * Process user-related merge tags
     *
     * @param string $content Content to process.
     * @return string Processed content.
     */
    private function process_user_tags($content) {
        if (is_user_logged_in()) {
            $user = wp_get_current_user();
            
            $replacements = array(
                '{user_id}' => $user->ID,
                '{user_name}' => $user->display_name,
                '{user_login}' => $user->user_login,
                '{user_email}' => $user->user_email,
                '{user_role}' => implode(', ', $user->roles),
                '{user_firstname}' => $user->first_name,
                '{user_lastname}' => $user->last_name,
            );
        } else {
            $replacements = array(
                '{user_id}' => '',
                '{user_name}' => 'Guest',
                '{user_login}' => '',
                '{user_email}' => '',
                '{user_role}' => '',
                '{user_firstname}' => '',
                '{user_lastname}' => '',
            );
        }
        
        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }
    
    /**
     * Process post-related merge tags
     *
     * @param string $content Content to process.
     * @return string Processed content.
     */
    private function process_post_tags($content) {
        global $post;
        
        if ($post) {
            $replacements = array(
                '{post_id}' => $post->ID,
                '{post_title}' => $post->post_title,
                '{post_content}' => $post->post_content,
                '{post_excerpt}' => $post->post_excerpt,
                '{post_slug}' => $post->post_name,
                '{post_date}' => $post->post_date,
                '{post_modified}' => $post->post_modified,
                '{post_author}' => get_the_author_meta('display_name', $post->post_author),
                '{post_permalink}' => get_permalink($post),
            );
        } else {
            $replacements = array(
                '{post_id}' => '',
                '{post_title}' => '',
                '{post_content}' => '',
                '{post_excerpt}' => '',
                '{post_slug}' => '',
                '{post_date}' => '',
                '{post_modified}' => '',
                '{post_author}' => '',
                '{post_permalink}' => '',
            );
        }
        
        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }
    
    /**
     * Process site-related merge tags
     *
     * @param string $content Content to process.
     * @return string Processed content.
     */
    private function process_site_tags($content) {
        $replacements = array(
            '{site_url}' => get_site_url(),
            '{site_name}' => get_bloginfo('name'),
            '{site_description}' => get_bloginfo('description'),
            '{site_admin_email}' => get_bloginfo('admin_email'),
            '{wp_version}' => get_bloginfo('version'),
        );
        
        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }
    
    /**
     * Process date/time-related merge tags
     *
     * @param string $content Content to process.
     * @return string Processed content.
     */
    private function process_date_tags($content) {
        $current_time = current_time('timestamp');
        
        $replacements = array(
            '{date_current}' => date_i18n(get_option('date_format'), $current_time),
            '{time_current}' => date_i18n(get_option('time_format'), $current_time),
            '{date_Y-m-d}' => date('Y-m-d', $current_time),
            '{date_m/d/Y}' => date('m/d/Y', $current_time),
            '{date_d/m/Y}' => date('d/m/Y', $current_time),
            '{time_H:i:s}' => date('H:i:s', $current_time),
            '{time_h:i A}' => date('h:i A', $current_time),
            '{datetime_Y-m-d_H:i:s}' => date('Y-m-d H:i:s', $current_time),
            '{year}' => date('Y', $current_time),
            '{month}' => date('m', $current_time),
            '{day}' => date('d', $current_time),
        );
        
        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }
    
    /**
     * Process URL-related merge tags
     *
     * @param string $content Content to process.
     * @return string Processed content.
     */
    private function process_url_tags($content) {
        $current_url = $this->get_current_url();
        $referrer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
        
        $replacements = array(
            '{url_current}' => $current_url,
            '{url_referrer}' => $referrer,
            '{url_path}' => parse_url($current_url, PHP_URL_PATH),
            '{url_query}' => parse_url($current_url, PHP_URL_QUERY),
        );
        
        return str_replace(array_keys($replacements), array_values($replacements), $content);
    }
    
    /**
     * Process request parameter merge tags
     *
     * @param string $content Content to process.
     * @return string Processed content.
     */
    private function process_request_tags($content) {
        // GET parameters: {get_parameter.name}
        preg_match_all('/\{get_([^}]+)\}/', $content, $get_matches);
        foreach ($get_matches[1] as $param) {
            $value = isset($_GET[$param]) ? sanitize_text_field($_GET[$param]) : '';
            $content = str_replace('{get_' . $param . '}', $value, $content);
        }
        
        // POST parameters: {post_parameter.name}
        preg_match_all('/\{post_([^}]+)\}/', $content, $post_matches);
        foreach ($post_matches[1] as $param) {
            $value = isset($_POST[$param]) ? sanitize_text_field($_POST[$param]) : '';
            $content = str_replace('{post_' . $param . '}', $value, $content);
        }
        
        // Cookie values: {cookie.name}
        preg_match_all('/\{cookie_([^}]+)\}/', $content, $cookie_matches);
        foreach ($cookie_matches[1] as $param) {
            $value = isset($_COOKIE[$param]) ? sanitize_text_field($_COOKIE[$param]) : '';
            $content = str_replace('{cookie_' . $param . '}', $value, $content);
        }
        
        return $content;
    }
    
    /**
     * Process server-related merge tags
     *
     * @param string $content Content to process.
     * @return string Processed content.
     */
    private function process_server_tags($content) {
        $ip_address = $this->get_client_ip();
        
        $replacements = array(
            '{ip_address}' => $ip_address,
            '{user_agent}' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '',
            '{request_method}' => isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '',
            '{server_name}' => isset($_SERVER['SERVER_NAME']) ? $_SERVER['SERVER_NAME'] : '',
            '{remote_host}' => isset($_SERVER['REMOTE_HOST']) ? $_SERVER['REMOTE_HOST'] : '',
        );
        
        return str_replace(array_keys($replacements), array_values($replacements), $content);
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
    
    /**
     * Get client IP address
     *
     * @return string IP address.
     */
    private function get_client_ip() {
        $ip_keys = array(
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        );
        
        foreach ($ip_keys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : '';
    }
}
