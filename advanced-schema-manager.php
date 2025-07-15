<?php
/**
 * Plugin Name: Advanced Schema Manager
 * Plugin URI: https://yourwebsite.com/advanced-schema-manager
 * Description: Add custom schema markup to pages with Yoast SEO fallback. Sanitized and secure.
 * Version: 1.0.0
 * Author: Your Name
 * Author URI: https://yourwebsite.com
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

// Define plugin constants BEFORE using them
define('ASM_PLUGIN_URL', plugin_dir_url(__FILE__));
define('ASM_PLUGIN_PATH', plugin_dir_path(__FILE__));
define('ASM_PLUGIN_VERSION', '1.0.0');

// Include required files
require_once ASM_PLUGIN_PATH . 'includes/schema-fields.php';
require_once ASM_PLUGIN_PATH . 'includes/schema-output.php';

class AdvancedSchemaManager {
    
    private $schema_fields;
    private $schema_output;
    
    public function __construct() {
        $this->schema_fields = new SchemaFields();
        $this->schema_output = new SchemaOutput();
        
        // Security headers
        add_action('admin_init', array($this, 'add_csp_headers'));
        
        // Verify file integrity
        if (!$this->verify_file_integrity()) {
            add_action('admin_notices', array($this, 'show_security_warning'));
            return;
        }
        
        // Initialize hooks
        add_action('init', array($this, 'init'));
        add_action('wp_head', array($this, 'output_schema'), 1);
        add_action('add_meta_boxes', array($this, 'add_schema_meta_box'));
        add_action('save_post', array($this, 'save_schema_meta'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_load_schema_fields', array($this, 'ajax_load_schema_fields'));
        add_action('wp_ajax_load_multiple_schema_fields', array($this, 'ajax_load_multiple_schema_fields'));
    }
    
    private function verify_file_integrity() {
        // Check if core files exist and are not modified
        $core_files = array(
            'includes/schema-fields.php',
            'includes/schema-output.php',
            'templates/meta-box-template.php'
        );
        
        foreach ($core_files as $file) {
            if (!file_exists(ASM_PLUGIN_PATH . $file)) {
                return false;
            }
        }
        
        return true;
    }
    
    public function show_security_warning() {
        ?>
        <div class="notice notice-error">
            <p><strong>Advanced Schema Manager:</strong> Plugin files are missing or corrupted. Please reinstall the plugin.</p>
        </div>
        <?php
    }
    
    public function init() {
        // Initialize plugin
        load_plugin_textdomain('advanced-schema-manager', false, dirname(plugin_basename(__FILE__)) . '/languages');
    }
    
    public function enqueue_admin_scripts($hook) {
        if ($hook == 'post.php' || $hook == 'post-new.php') {
            wp_enqueue_script(
                'advanced-schema-manager', 
                ASM_PLUGIN_URL . 'assets/admin.js', 
                array('jquery'), 
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
                'nonce' => wp_create_nonce('asm_ajax_nonce')
            ));
        }
    }
    
    public function add_schema_meta_box() {
        add_meta_box(
            'advanced-schema-manager',
            __('Advanced Schema Manager', 'advanced-schema-manager'),
            array($this, 'schema_meta_box_callback'),
            array('page', 'post', 'product'),
            'normal',
            'high'
        );
    }
    
    public function schema_meta_box_callback($post) {
        wp_nonce_field('advanced_schema_nonce', 'advanced_schema_nonce');
        
        $schema_type = sanitize_text_field(get_post_meta($post->ID, '_asm_schema_type', true));
        $schema_data = $this->sanitize_schema_data(get_post_meta($post->ID, '_asm_schema_data', true));
        $custom_schema = wp_kses_post(get_post_meta($post->ID, '_asm_custom_schema', true));
        $enable_custom = (bool) get_post_meta($post->ID, '_asm_enable_custom', true);
        
        // Add these missing variables
        $schema_mode = sanitize_text_field(get_post_meta($post->ID, '_asm_schema_mode', true));
        $multiple_schemas = get_post_meta($post->ID, '_asm_multiple_schemas', true);
        
        // Set defaults if empty
        if (empty($schema_mode)) {
            $schema_mode = 'single';
        }
        if (empty($multiple_schemas) || !is_array($multiple_schemas)) {
            $multiple_schemas = array();
        }
        
        // Include the template
        include ASM_PLUGIN_PATH . 'templates/meta-box-template.php';
    }
    
    public function save_schema_meta($post_id) {
        // Enhanced security checks
        if (!isset($_POST['advanced_schema_nonce']) || 
            !wp_verify_nonce($_POST['advanced_schema_nonce'], 'advanced_schema_nonce')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        // Check if user has permission
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
        
        // Additional capability check for admin functions
        if (!current_user_can('manage_options') && !current_user_can('edit_posts')) {
            wp_die(__('You do not have sufficient permissions to perform this action.', 'advanced-schema-manager'));
        }
        
        $enable_custom = isset($_POST['asm_enable_custom']) ? 1 : 0;
        update_post_meta($post_id, '_asm_enable_custom', $enable_custom);
        
        if ($enable_custom) {
            // Save schema mode
            $schema_mode = isset($_POST['asm_schema_mode']) ? sanitize_text_field($_POST['asm_schema_mode']) : 'single';
            update_post_meta($post_id, '_asm_schema_mode', $schema_mode);
            
            // Save based on mode
            switch ($schema_mode) {
                case 'single':
                    if (isset($_POST['asm_schema_type'])) {
                        $schema_type = sanitize_text_field($_POST['asm_schema_type']);
                        update_post_meta($post_id, '_asm_schema_type', $schema_type);
                    }
                    
                    if (isset($_POST['asm_schema_data'])) {
                        $schema_data = $this->sanitize_schema_data($_POST['asm_schema_data']);
                        update_post_meta($post_id, '_asm_schema_data', $schema_data);
                    }
                    break;
                    
                case 'multiple':
                    if (isset($_POST['asm_multiple_schemas'])) {
                        $multiple_schemas = array();
                        foreach ($_POST['asm_multiple_schemas'] as $index => $schema) {
                            $multiple_schemas[$index] = array(
                                'type' => sanitize_text_field($schema['type']),
                                'data' => isset($schema['data']) ? $this->sanitize_schema_data($schema['data']) : array()
                            );
                        }
                        update_post_meta($post_id, '_asm_multiple_schemas', $multiple_schemas);
                    }
                    break;
                    
                case 'custom_json':
                    if (isset($_POST['asm_custom_schema'])) {
                        $custom_schema = wp_kses_post($_POST['asm_custom_schema']);
                        
                        // Validate JSON before saving
                        if ($this->validate_json($custom_schema)) {
                            update_post_meta($post_id, '_asm_custom_schema', $custom_schema);
                        } else {
                            add_action('admin_notices', array($this, 'show_json_error'));
                        }
                    }
                    break;
            }
        }
    }
    
    public function show_json_error() {
        ?>
        <div class="notice notice-error is-dismissible">
            <p><?php _e('Invalid JSON format in schema markup. Please check your JSON syntax.', 'advanced-schema-manager'); ?></p>
        </div>
        <?php
    }
    
    public function ajax_load_multiple_schema_fields() {
        // Enhanced security
        if (!wp_verify_nonce($_POST['nonce'], 'asm_ajax_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $schema_type = sanitize_text_field($_POST['schema_type']);
        $index = intval($_POST['index']);
        
        // Validate schema type
        $allowed_types = array('SoftwareApplication', 'Product', 'Organization', 'FAQPage', 'Article', 'BreadcrumbList', 'LocalBusiness', 'WebPage', 'ImageObject');
        if (!in_array($schema_type, $allowed_types)) {
            wp_send_json_error('Invalid schema type');
        }
        
        ob_start();
        $this->schema_fields->render_multiple_schema_fields($schema_type, $index, array());
        $html = ob_get_clean();
        
        wp_send_json_success($html);
    }
    
    // Enhanced sanitization
    private function sanitize_schema_data($data) {
        if (!is_array($data)) {
            return array();
        }
        
        $sanitized = array();
        
        foreach ($data as $key => $value) {
            $key = sanitize_key($key); // Sanitize array keys
            
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitize_schema_data($value);
            } else {
                // Sanitize based on field type
                switch ($key) {
                    case 'url':
                    case 'logo':
                    case 'screenshot':
                    case 'downloadUrl':
                    case 'installUrl':
                        $sanitized[$key] = esc_url_raw($value);
                        break;
                    case 'email':
                        $sanitized[$key] = sanitize_email($value);
                        break;
                    case 'price':
                    case 'ratingValue':
                    case 'ratingCount':
                        $sanitized[$key] = is_numeric($value) ? floatval($value) : sanitize_text_field($value);
                        break;
                    case 'description':
                    case 'answer':
                        $sanitized[$key] = wp_kses_post($value);
                        break;
                    default:
                        $sanitized[$key] = sanitize_text_field($value);
                }
            }
        }
        
        return $sanitized;
    }
    
    // JSON validation
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
            $this->schema_output->output_schema($post->ID);
        }
    }
    
    public function ajax_load_schema_fields() {
        // Rate limiting
        $this->check_rate_limit();
        
        // Enhanced security
        if (!wp_verify_nonce($_POST['nonce'], 'asm_ajax_nonce')) {
            wp_send_json_error('Security check failed');
        }
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }
        
        $schema_type = sanitize_text_field($_POST['schema_type']);
        $post_id = intval($_POST['post_id']);
        
        // Validate schema type
        $allowed_types = array('SoftwareApplication', 'Product', 'Organization', 'FAQPage', 'Article', 'BreadcrumbList', 'LocalBusiness', 'WebPage', 'ImageObject');
        if (!in_array($schema_type, $allowed_types)) {
            wp_send_json_error('Invalid schema type');
        }
        
        // Validate post ID
        if (!get_post($post_id)) {
            wp_send_json_error('Invalid post ID');
        }
        
        ob_start();
        $this->schema_fields->render_schema_fields($schema_type, array());
        $html = ob_get_clean();
        
        wp_send_json_success($html);
    }
    
    private function check_rate_limit() {
        $transient_key = 'asm_rate_limit_' . get_current_user_id();
        $requests = get_transient($transient_key);
        
        if ($requests && $requests > 50) { // 50 requests per minute
            wp_send_json_error('Rate limit exceeded');
        }
        
        set_transient($transient_key, ($requests ? $requests + 1 : 1), 60);
    }
    
    public function add_csp_headers() {
        if (is_admin()) {
            header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline';");
        }
    }
}

// Initialize the plugin
new AdvancedSchemaManager();
