<?php
/**
 * Plugin Name: Advanced Schema Manager
 * Description: Add custom schema markup to pages with Yoast SEO fallback
 * Version: 1.0.0
 * Author: Your Name
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Include required files
require_once plugin_dir_path(__FILE__) . 'includes/schema-fields.php';
require_once plugin_dir_path(__FILE__) . 'includes/schema-output.php';

class AdvancedSchemaManager {
    
    private $schema_fields;
    private $schema_output;
    
    public function __construct() {
        $this->schema_fields = new SchemaFields();
        $this->schema_output = new SchemaOutput();
        
        add_action('init', array($this, 'init'));
        add_action('wp_head', array($this, 'output_schema'), 1);
        add_action('add_meta_boxes', array($this, 'add_schema_meta_box'));
        add_action('save_post', array($this, 'save_schema_meta'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('wp_ajax_load_schema_fields', array($this, 'ajax_load_schema_fields'));
    }
    
    public function init() {
        // Initialize plugin
    }
    
    public function enqueue_admin_scripts($hook) {
        if ($hook == 'post.php' || $hook == 'post-new.php') {
            wp_enqueue_script(
                'advanced-schema-manager', 
                plugin_dir_url(__FILE__) . 'assets/admin.js', 
                array('jquery'), 
                '1.0.0', 
                true
            );
            wp_enqueue_style(
                'advanced-schema-manager', 
                plugin_dir_url(__FILE__) . 'assets/admin.css', 
                array(), 
                '1.0.0'
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
            'Advanced Schema Manager',
            array($this, 'schema_meta_box_callback'),
            array('page', 'post', 'product'),
            'normal',
            'high'
        );
    }
    
    public function schema_meta_box_callback($post) {
        wp_nonce_field('advanced_schema_nonce', 'advanced_schema_nonce');
        
        $schema_type = get_post_meta($post->ID, '_asm_schema_type', true);
        $schema_data = get_post_meta($post->ID, '_asm_schema_data', true);
        $custom_schema = get_post_meta($post->ID, '_asm_custom_schema', true);
        $enable_custom = get_post_meta($post->ID, '_asm_enable_custom', true);
        
        // Include the template
        include plugin_dir_path(__FILE__) . 'templates/meta-box-template.php';
    }
    
    public function save_schema_meta($post_id) {
        if (!isset($_POST['advanced_schema_nonce']) || !wp_verify_nonce($_POST['advanced_schema_nonce'], 'advanced_schema_nonce')) {
            return;
        }
        
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        if (isset($_POST['post_type']) && 'page' == $_POST['post_type']) {
            if (!current_user_can('edit_page', $post_id)) {
                return;
            }
        } else {
            if (!current_user_can('edit_post', $post_id)) {
                return;
            }
        }
        
        $enable_custom = isset($_POST['asm_enable_custom']) ? 1 : 0;
        update_post_meta($post_id, '_asm_enable_custom', $enable_custom);
        
        if ($enable_custom) {
            $schema_type = sanitize_text_field($_POST['asm_schema_type']);
            $custom_schema = wp_kses_post($_POST['asm_custom_schema']);
            
            update_post_meta($post_id, '_asm_schema_type', $schema_type);
            update_post_meta($post_id, '_asm_custom_schema', $custom_schema);
            
            if (isset($_POST['asm_schema_data'])) {
                $schema_data = $this->sanitize_schema_data($_POST['asm_schema_data']);
                update_post_meta($post_id, '_asm_schema_data', $schema_data);
            }
        }
    }
    
    private function sanitize_schema_data($data) {
        $sanitized = array();
        
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $sanitized[$key] = $this->sanitize_schema_data($value);
            } else {
                $sanitized[$key] = sanitize_text_field($value);
            }
        }
        
        return $sanitized;
    }
    
    public function output_schema() {
        if (is_singular()) {
            global $post;
            $this->schema_output->output_schema($post->ID);
        }
    }
    
    public function ajax_load_schema_fields() {
        if (!wp_verify_nonce($_POST['nonce'], 'asm_ajax_nonce')) {
            wp_die('Security check failed');
        }
        
        $schema_type = sanitize_text_field($_POST['schema_type']);
        $post_id = intval($_POST['post_id']);
        
        ob_start();
        $this->schema_fields->render_schema_fields($schema_type, array());
        $html = ob_get_clean();
        
        wp_send_json_success($html);
    }
}

// Initialize the plugin
new AdvancedSchemaManager();
