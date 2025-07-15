<?php
/**
 * Plugin Name: Advanced Schema Manager
 * Plugin URI: https://www.boostmyshop.com/
 * Description: Add custom schema markup to pages with Yoast SEO fallback. Sanitized and secure.
 * Version: 1.0.0
 * Author: Hariharan Gandhimani
 * Author URI: https://www.boostmyshop.com/
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: advanced-schema-manager
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 * Network: false
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit('Direct access not allowed.');
}

// Define plugin constants
define('ASM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ASM_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('ASM_PLUGIN_VERSION', '1.0.0');

// Include required files
require_once ASM_PLUGIN_PATH . 'includes/schema-fields.php';
require_once ASM_PLUGIN_PATH . 'includes/schema-output.php';

class AdvancedSchemaManager {
    
    private $schema_fields;
    private $schema_output;
    private static $instance = null;
    
    public static function get_instance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    private function __construct() {
        $this->schema_fields = new SchemaFields();
        $this->schema_output = new SchemaOutput();
        
        // Initialize hooks
        add_action('init', array($this, 'init'));
        add_action('wp_head', array($this, 'output_schema'), 5);
        add_action('add_meta_boxes', array($this, 'add_schema_meta_box'));
        add_action('save_post', array($this, 'save_schema_meta'), 10, 2);
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_load_schema_fields', array($this, 'ajax_load_schema_fields'));
        add_action('wp_ajax_load_multiple_schema_fields', array($this, 'ajax_load_multiple_schema_fields'));
        add_action('admin_notices', array($this, 'display_admin_notices'));
        
        // Hook for plugin activation
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
    }
    
    public function activate() {
        // Create default options
        add_option('asm_plugin_version', ASM_PLUGIN_VERSION);
        add_option('asm_plugin_settings', array());
        
        // Clear any existing caches
        wp_cache_flush();
    }
    
    public function deactivate() {
        // Clean up transients
        $this->cleanup_transients();
    }
    
    private function cleanup_transients() {
        global $wpdb;
        
        $wpdb->query(
            "DELETE FROM {$wpdb->options} 
             WHERE option_name LIKE '_transient_asm_rate_limit_%' 
             OR option_name LIKE '_transient_timeout_asm_rate_limit_%'"
        );
    }
    
    public function init() {
        // Initialize plugin
        load_plugin_textdomain('advanced-schema-manager', false, dirname(plugin_basename(__FILE__)) . '/languages');
        
        // Initialize session for notices
        if (!session_id()) {
            session_start();
        }
    }
    
    public function enqueue_admin_scripts($hook) {
        // Only load on post edit screens
        if (!in_array($hook, array('post.php', 'post-new.php'))) {
            return;
        }
        
        $screen = get_current_screen();
        if (!$screen || !in_array($screen->post_type, array('post', 'page', 'product'))) {
            return;
        }
        
        wp_enqueue_script(
            'advanced-schema-manager', 
            ASM_PLUGIN_URL . 'assets/admin.js', 
            array('jquery', 'wp-util'), 
            ASM_PLUGIN_VERSION, 
            true
        );
        
        wp_enqueue_style(
            'advanced-schema-manager', 
            ASM_PLUGIN_URL . 'assets/admin.css', 
            array(), 
            ASM_PLUGIN_VERSION
        );
        
        wp_localize_script('advanced-schema-manager', 'asm_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('asm_ajax_nonce'),
            'post_id' => get_the_ID(),
            'messages' => array(
                'invalid_json' => __('Invalid JSON format detected. Please check your syntax.', 'advanced-schema-manager'),
                'confirm_invalid' => __('Invalid JSON format detected. Save anyway?', 'advanced-schema-manager'),
                'loading' => __('Loading...', 'advanced-schema-manager'),
                'error' => __('An error occurred. Please try again.', 'advanced-schema-manager')
            )
        ));
    }
    
    public function add_schema_meta_box() {
        $post_types = apply_filters('asm_supported_post_types', array('page', 'post', 'product'));
        
        foreach ($post_types as $post_type) {
            add_meta_box(
                'advanced-schema-manager',
                __('Advanced Schema Manager', 'advanced-schema-manager'),
                array($this, 'schema_meta_box_callback'),
                $post_type,
                'normal',
                'high'
            );
        }
    }
    
    public function schema_meta_box_callback($post) {
        // Security nonce
        wp_nonce_field('advanced_schema_meta_box', 'advanced_schema_nonce');
        
        // Get saved values with proper defaults
        $enable_custom = get_post_meta($post->ID, '_asm_enable_custom', true);
        $schema_mode = get_post_meta($post->ID, '_asm_schema_mode', true);
        $schema_type = get_post_meta($post->ID, '_asm_schema_type', true);
        $schema_data = get_post_meta($post->ID, '_asm_schema_data', true);
        $custom_schema = get_post_meta($post->ID, '_asm_custom_schema', true);
        $multiple_schemas = get_post_meta($post->ID, '_asm_multiple_schemas', true);
        
        // Set defaults
        $enable_custom = !empty($enable_custom) ? true : false;
        $schema_mode = !empty($schema_mode) ? $schema_mode : 'single';
        $schema_type = !empty($schema_type) ? $schema_type : '';
        $schema_data = is_array($schema_data) ? $schema_data : array();
        $custom_schema = !empty($custom_schema) ? $custom_schema : '';
        $multiple_schemas = is_array($multiple_schemas) ? $multiple_schemas : array();
        
        // Include the template
        include ASM_PLUGIN_PATH . 'templates/meta-box-template.php';
    }
    
    public function save_schema_meta($post_id, $post) {
        // Security checks
        if (!isset($_POST['advanced_schema_nonce']) || 
            !wp_verify_nonce($_POST['advanced_schema_nonce'], 'advanced_schema_meta_box')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Don't save on post revisions
        if (wp_is_post_revision($post_id)) {
            return;
        }
        
        try {
            // Save enable custom setting
            $enable_custom = isset($_POST['asm_enable_custom']) ? 1 : 0;
            update_post_meta($post_id, '_asm_enable_custom', $enable_custom);
            
            if ($enable_custom) {
                // Save schema mode
                $schema_mode = isset($_POST['asm_schema_mode']) ? sanitize_text_field($_POST['asm_schema_mode']) : 'single';
                update_post_meta($post_id, '_asm_schema_mode', $schema_mode);
                
                // Clear previous data
                delete_post_meta($post_id, '_asm_schema_type');
                delete_post_meta($post_id, '_asm_schema_data');
                delete_post_meta($post_id, '_asm_custom_schema');
                delete_post_meta($post_id, '_asm_multiple_schemas');
                delete_post_meta($post_id, '_asm_json_error');
                
                // Save based on mode
                switch ($schema_mode) {
                    case 'single':
                        $this->save_single_schema($post_id);
                        break;
                        
                    case 'multiple':
                        $this->save_multiple_schemas($post_id);
                        break;
                        
                    case 'custom_json':
                        $this->save_custom_json($post_id);
                        break;
                }
            } else {
                // Clear all schema data if disabled
                $this->clear_all_schema_data($post_id);
            }
            
        } catch (Exception $e) {
            error_log('ASM Save Error: ' . $e->getMessage());
            $this->add_admin_notice('error', __('Error saving schema data. Please try again.', 'advanced-schema-manager'));
        }
    }
    
    private function save_single_schema($post_id) {
        if (isset($_POST['asm_schema_type'])) {
            $schema_type = sanitize_text_field($_POST['asm_schema_type']);
            update_post_meta($post_id, '_asm_schema_type', $schema_type);
        }
        
        if (isset($_POST['asm_schema_data'])) {
            $schema_data = $this->sanitize_schema_data($_POST['asm_schema_data']);
            update_post_meta($post_id, '_asm_schema_data', $schema_data);
        }
    }
    
    private function save_multiple_schemas($post_id) {
        if (isset($_POST['asm_multiple_schemas']) && is_array($_POST['asm_multiple_schemas'])) {
            $multiple_schemas = array();
            
            foreach ($_POST['asm_multiple_schemas'] as $index => $schema) {
                if (!empty($schema['type'])) {
                    $multiple_schemas[$index] = array(
                        'type' => sanitize_text_field($schema['type']),
                        'data' => isset($schema['data']) ? $this->sanitize_schema_data($schema['data']) : array()
                    );
                }
            }
            
            update_post_meta($post_id, '_asm_multiple_schemas', $multiple_schemas);
        }
    }
    
    private function save_custom_json($post_id) {
        if (isset($_POST['asm_custom_schema'])) {
            $custom_schema = wp_unslash($_POST['asm_custom_schema']);
            $custom_schema = sanitize_textarea_field($custom_schema);
            
            // Validate JSON
            if (!empty($custom_schema)) {
                if ($this->validate_json($custom_schema)) {
                    update_post_meta($post_id, '_asm_custom_schema', $custom_schema);
                    $this->add_admin_notice('success', __('Custom JSON schema saved successfully.', 'advanced-schema-manager'));
                } else {
                    update_post_meta($post_id, '_asm_custom_schema', $custom_schema);
                    update_post_meta($post_id, '_asm_json_error', __('Invalid JSON format. Please check your syntax.', 'advanced-schema-manager'));
                    $this->add_admin_notice('error', __('Invalid JSON format detected. Schema saved but may not work properly.', 'advanced-schema-manager'));
                }
            } else {
                update_post_meta($post_id, '_asm_custom_schema', '');
            }
        }
    }
    
    private function clear_all_schema_data($post_id) {
        delete_post_meta($post_id, '_asm_schema_type');
        delete_post_meta($post_id, '_asm_schema_data');
        delete_post_meta($post_id, '_asm_custom_schema');
        delete_post_meta($post_id, '_asm_multiple_schemas');
        delete_post_meta($post_id, '_asm_schema_mode');
        delete_post_meta($post_id, '_asm_json_error');
    }
    
    private function sanitize_schema_data($data) {
        if (!is_array($data)) {
            return array();
        }
        
        $sanitized = array();
        
        foreach ($data as $key => $value) {
            $key = sanitize_key($key);
            
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitize_schema_data($value);
            } else {
                switch ($key) {
                    case 'url':
                    case 'logo':
                    case 'screenshot':
                    case 'downloadUrl':
                    case 'installUrl':
                    case 'image':
                        $sanitized[$key] = esc_url_raw($value);
                        break;
                    case 'email':
                        $sanitized[$key] = sanitize_email($value);
                        break;
                    case 'price':
                    case 'ratingValue':
                    case 'ratingCount':
                    case 'position':
                        $sanitized[$key] = is_numeric($value) ? floatval($value) : sanitize_text_field($value);
                        break;
                    case 'description':
                    case 'answer':
                    case 'text':
                        $sanitized[$key] = wp_kses_post($value);
                        break;
                    default:
                        $sanitized[$key] = sanitize_text_field($value);
                }
            }
        }
        
        return $sanitized;
    }
    
    private function validate_json($json_string) {
        if (empty($json_string)) {
            return true;
        }
        
        json_decode($json_string);
        return json_last_error() === JSON_ERROR_NONE;
    }
    
    public function output_schema() {
        if (is_singular()) {
            global $post;
            if ($post && isset($post->ID)) {
                $this->schema_output->output_schema($post->ID);
            }
        }
    }
    
    public function ajax_load_schema_fields() {
        // Security and rate limiting
        $this->check_ajax_security();
        $this->check_rate_limit();
        
        $schema_type = sanitize_text_field($_POST['schema_type']);
        $post_id = intval($_POST['post_id']);
        
        // Validate inputs
        if (!$this->validate_schema_type($schema_type)) {
            wp_send_json_error(__('Invalid schema type.', 'advanced-schema-manager'));
        }
        
        if (!get_post($post_id)) {
            wp_send_json_error(__('Invalid post ID.', 'advanced-schema-manager'));
        }
        
        // Get existing data
        $schema_data = get_post_meta($post_id, '_asm_schema_data', true);
        $schema_data = is_array($schema_data) ? $schema_data : array();
        
        ob_start();
        $this->schema_fields->render_schema_fields($schema_type, $schema_data);
        $html = ob_get_clean();
        
        wp_send_json_success($html);
    }
    
    public function ajax_load_multiple_schema_fields() {
        // Security and rate limiting
        $this->check_ajax_security();
        $this->check_rate_limit();
        
        $schema_type = sanitize_text_field($_POST['schema_type']);
        $index = intval($_POST['index']);
        
        // Validate schema type
        if (!$this->validate_schema_type($schema_type)) {
            wp_send_json_error(__('Invalid schema type.', 'advanced-schema-manager'));
        }
        
        ob_start();
        $this->schema_fields->render_multiple_schema_fields($schema_type, $index, array());
        $html = ob_get_clean();
        
        wp_send_json_success($html);
    }
    
    private function check_ajax_security() {
        if (!wp_verify_nonce($_POST['nonce'], 'asm_ajax_nonce')) {
            wp_send_json_error(__('Security check failed.', 'advanced-schema-manager'));
        }
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error(__('Insufficient permissions.', 'advanced-schema-manager'));
        }
    }
    
    private function validate_schema_type($schema_type) {
        $allowed_types = array(
            'SoftwareApplication', 'Product', 'Organization', 'FAQPage', 
            'Article', 'BreadcrumbList', 'LocalBusiness', 'WebPage', 
            'ImageObject', 'Service', 'HowTo'
        );
        
        return in_array($schema_type, $allowed_types);
    }
    
    private function check_rate_limit() {
        $user_id = get_current_user_id();
        $transient_key = 'asm_rate_limit_' . $user_id;
        $requests = get_transient($transient_key);
        
        $limit = apply_filters('asm_rate_limit', 100); // 100 requests per minute
        
        if ($requests && $requests > $limit) {
            wp_send_json_error(__('Rate limit exceeded. Please wait a moment.', 'advanced-schema-manager'));
        }
        
        set_transient($transient_key, ($requests ? $requests + 1 : 1), 60);
    }
    
    private function add_admin_notice($type, $message) {
        if (!isset($_SESSION['asm_notices'])) {
            $_SESSION['asm_notices'] = array();
        }
        
        $_SESSION['asm_notices'][] = array(
            'type' => $type,
            'message' => $message
        );
    }
    
    public function display_admin_notices() {
        if (isset($_SESSION['asm_notices']) && !empty($_SESSION['asm_notices'])) {
            foreach ($_SESSION['asm_notices'] as $notice) {
                $class = $notice['type'] === 'error' ? 'notice-error' : 'notice-success';
                echo '<div class="notice ' . $class . ' is-dismissible">';
                echo '<p>' . esc_html($notice['message']) . '</p>';
                echo '</div>';
            }
            
            // Clear notices after displaying
            unset($_SESSION['asm_notices']);
        }
    }
    
    // Debug method (remove in production)
    public function debug_schema_data($post_id) {
        if (!defined('WP_DEBUG') || !WP_DEBUG) {
            return;
        }
        
        echo '<div style="background: #f0f0f0; padding: 10px; margin: 10px 0; border: 1px solid #ccc;">';
        echo '<h4>Debug Info:</h4>';
        echo '<p><strong>Enable Custom:</strong> ' . (get_post_meta($post_id, '_asm_enable_custom', true) ? 'Yes' : 'No') . '</p>';
        echo '<p><strong>Schema Mode:</strong> ' . get_post_meta($post_id, '_asm_schema_mode', true) . '</p>';
        echo '<p><strong>Custom Schema:</strong> ' . htmlspecialchars(get_post_meta($post_id, '_asm_custom_schema', true)) . '</p>';
        echo '</div>';
    }
}

// Initialize the plugin
AdvancedSchemaManager::get_instance();
