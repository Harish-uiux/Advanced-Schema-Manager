<?php
/**
 * Plugin Name: Advanced Schema Manager
 * Description: Add custom schema markup to pages with Yoast SEO fallback
 * Version: 1.0.0
 * Author: Hariharan Gandhimani
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class AdvancedSchemaManager {
    
    public function __construct() {
        add_action('init', array($this, 'init'));
        add_action('wp_head', array($this, 'output_schema'), 1);
        add_action('add_meta_boxes', array($this, 'add_schema_meta_box'));
        add_action('save_post', array($this, 'save_schema_meta'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }
    
    public function init() {
        // Initialize plugin
    }
    
    public function add_schema_meta_box() {
        add_meta_box(
            'advanced-schema-manager',
            'Advanced Schema Manager',
            array($this, 'schema_meta_box_callback'),
            array('page', 'post', 'product'), // Add more post types as needed
            'normal',
            'high'
        );
    }
    
    public function schema_meta_box_callback($post) {
        wp_nonce_field('advanced_schema_nonce', 'advanced_schema_nonce');
        
        $schema_type = get_post_meta($post->ID, '_asm_schema_type', true);
        $schema_data = get_post_meta($post->ID, '_asm_schema_data', true);
        $enable_custom = get_post_meta($post->ID, '_asm_enable_custom', true);
        
        include plugin_dir_path(__FILE__) . 'templates/meta-box-template.php';
    }
}

new AdvancedSchemaManager();
