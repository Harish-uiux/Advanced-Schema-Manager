<?php

class SchemaFields {
    
    public function render_schema_fields($schema_type, $data) {
        $fields = $this->get_schema_fields($schema_type);
        
        if (empty($fields)) {
            echo '<p>No fields available for this schema type.</p>';
            return;
        }
        
        foreach ($fields as $field_key => $field) {
            $value = isset($data[$field_key]) ? $data[$field_key] : '';
            $this->render_field($field_key, $field, $value);
        }
    }
    
    private function render_field($field_key, $field, $value) {
        $field_name = "asm_schema_data[{$field_key}]";
        $field_id = "asm_schema_data_{$field_key}";
        
        echo '<div class="field-group">';
        echo '<label for="' . $field_id . '">' . $field['label'];
        if (!empty($field['required'])) {
            echo ' <span class="required">*</span>';
        }
        echo '</label>';
        
        switch ($field['type']) {
            case 'text':
                echo '<input type="text" id="' . $field_id . '" name="' . $field_name . '" value="' . esc_attr($value) . '" class="regular-text"';
                if (!empty($field['placeholder'])) {
                    echo ' placeholder="' . esc_attr($field['placeholder']) . '"';
                }
                echo '>';
                break;
                
            case 'textarea':
                echo '<textarea id="' . $field_id . '" name="' . $field_name . '" rows="4" class="large-text"';
                if (!empty($field['placeholder'])) {
                    echo ' placeholder="' . esc_attr($field['placeholder']) . '"';
                }
                echo '>' . esc_textarea($value) . '</textarea>';
                break;
                
            case 'select':
                echo '<select id="' . $field_id . '" name="' . $field_name . '">';
                echo '<option value="">Select...</option>';
                if (!empty($field['options'])) {
                    foreach ($field['options'] as $option_value => $option_label) {
                        echo '<option value="' . esc_attr($option_value) . '"' . selected($value, $option_value, false) . '>' . esc_html($option_label) . '</option>';
                    }
                }
                echo '</select>';
                break;
                
            case 'number':
                echo '<input type="number" id="' . $field_id . '" name="' . $field_name . '" value="' . esc_attr($value) . '" class="small-text"';
                if (!empty($field['min'])) {
                    echo ' min="' . esc_attr($field['min']) . '"';
                }
                if (!empty($field['max'])) {
                    echo ' max="' . esc_attr($field['max']) . '"';
                }
                if (!empty($field['step'])) {
                    echo ' step="' . esc_attr($field['step']) . '"';
                }
                echo '>';
                break;
                
            case 'url':
                echo '<input type="url" id="' . $field_id . '" name="' . $field_name . '" value="' . esc_attr($value) . '" class="regular-text"';
                if (!empty($field['placeholder'])) {
                    echo ' placeholder="' . esc_attr($field['placeholder']) . '"';
                }
                echo '>';
                break;
                
            case 'repeater':
                $this->render_repeater_field($field_key, $field, $value);
                break;
        }
        
        if (!empty($field['description'])) {
            echo '<p class="description">' . esc_html($field['description']) . '</p>';
        }
        
        echo '</div>';
    }
    
    private function render_repeater_field($field_key, $field, $values) {
        $field_name = "asm_schema_data[{$field_key}]";
        
        echo '<div class="repeater-container">';
        
        if (is_array($values)) {
            foreach ($values as $index => $value) {
                $this->render_repeater_item($field_key, $field, $index, $value);
            }
        }
        
        echo '</div>';
        echo '<button type="button" class="button add-repeater-item" data-field="' . $field_key . '">Add ' . $field['label'] . '</button>';
        
        // Template for new items
        echo '<div class="repeater-template" style="display:none;">';
        $this->render_repeater_item($field_key, $field, 'INDEX', array());
        echo '</div>';
    }
    
    private function render_repeater_item($field_key, $field, $index, $values) {
        echo '<div class="repeater-item" style="border: 1px solid #ccc; padding: 10px; margin-bottom: 10px;">';
        
        foreach ($field['fields'] as $sub_field_key => $sub_field) {
            $sub_field_name = "asm_schema_data[{$field_key}][{$index}][{$sub_field_key}]";
            $sub_field_id = "asm_schema_data_{$field_key}_{$index}_{$sub_field_key}";
            $sub_value = isset($values[$sub_field_key]) ? $values[$sub_field_key] : '';
            
            echo '<div class="sub-field-group" style="margin-bottom: 10px;">';
            echo '<label for="' . $sub_field_id . '">' . $sub_field['label'] . '</label>';
            
            switch ($sub_field['type']) {
                case 'text':
                    echo '<input type="text" id="' . $sub_field_id . '" name="' . $sub_field_name . '" value="' . esc_attr($sub_value) . '" class="regular-text">';
                    break;
                case 'textarea':
                    echo '<textarea id="' . $sub_field_id . '" name="' . $sub_field_name . '" rows="3" class="large-text">' . esc_textarea($sub_value) . '</textarea>';
                    break;
                case 'url':
                    echo '<input type="url" id="' . $sub_field_id . '" name="' . $sub_field_name . '" value="' . esc_attr($sub_value) . '" class="regular-text">';
                    break;
                case 'number':
                    echo '<input type="number" id="' . $sub_field_id . '" name="' . $sub_field_name . '" value="' . esc_attr($sub_value) . '" class="small-text">';
                    break;
            }
            
            echo '</div>';
        }
        
        echo '<button type="button" class="button remove-repeater-item">Remove</button>';
        echo '</div>';
    }
    
    private function get_schema_fields($schema_type) {
        switch ($schema_type) {
            case 'SoftwareApplication':
                return $this->get_software_application_fields();
            case 'Product':
                return $this->get_product_fields();
            case 'FAQPage':
                return $this->get_faq_fields();
            case 'Organization':
                return $this->get_organization_fields();
            case 'BreadcrumbList':
                return $this->get_breadcrumb_fields();
            case 'Article':
                return $this->get_article_fields();
            default:
                return array();
        }
    }
    
    private function get_software_application_fields() {
        return array(
            'name' => array(
                'type' => 'text',
                'label' => 'Application Name',
                'required' => true,
                'placeholder' => 'e.g., MyFulfillment Order Management System'
            ),
            'description' => array(
                'type' => 'textarea',
                'label' => 'Description',
                'required' => true,
                'placeholder' => 'Describe your software application'
            ),
            'applicationCategory' => array(
                'type' => 'select',
                'label' => 'Application Category',
                'options' => array(
                    'BusinessApplication' => 'Business Application',
                    'GameApplication' => 'Game Application',
                    'MultimediaApplication' => 'Multimedia Application',
                    'MobileApplication' => 'Mobile Application',
                    'WebApplication' => 'Web Application'
                ),
                'required' => true
            ),
            'operatingSystem' => array(
                'type' => 'text',
                'label' => 'Operating System',
                'placeholder' => 'e.g., Web-based, Windows, macOS'
            ),
            'softwareVersion' => array(
                'type' => 'text',
                'label' => 'Software Version',
                'placeholder' => 'e.g., 2025, 1.0.0'
            ),
            'price' => array(
                'type' => 'text',
                'label' => 'Price',
                'placeholder' => 'e.g., Contact for pricing, $99'
            ),
            'priceCurrency' => array(
                'type' => 'text',
                'label' => 'Price Currency',
                'placeholder' => 'e.g., USD, EUR'
            ),
            'features' => array(
                'type' => 'textarea',
                'label' => 'Features (one per line)',
                'placeholder' => 'Order tracking' . "\n" . 'Inventory management' . "\n" . 'Shipping integration'
            ),
            'screenshot' => array(
                'type' => 'url',
                'label' => 'Screenshot URL',
                'placeholder' => 'https://example.com/screenshot.jpg'
            )
        );
    }
    
    private function get_product_fields() {
        return array(
            'name' => array(
                'type' => 'text',
                'label' => 'Product Name',
                'required' => true
            ),
            'description' => array(
                'type' => 'textarea',
                'label' => 'Description',
                'required' => true
            ),
            'brand' => array(
                'type' => 'text',
                'label' => 'Brand',
                'placeholder' => 'BoostMyShop'
            ),
            'price' => array(
                'type' => 'text',
                'label' => 'Price',
                'required' => true
            ),
            'priceCurrency' => array(
                'type' => 'text',
                'label' => 'Currency',
                'placeholder' => 'USD'
            )
        );
    }
    
    private function get_faq_fields() {
        return array(
            'faqs' => array(
                'type' => 'repeater',
                'label' => 'FAQ Items',
                'fields' => array(
                    'question' => array(
                        'type' => 'text',
                        'label' => 'Question',
                        'required' => true
                    ),
                    'answer' => array(
                        'type' => 'textarea',
                        'label' => 'Answer',
                        'required' => true
                    )
                )
            )
        );
    }
    
    private function get_organization_fields() {
        return array(
            'name' => array(
                'type' => 'text',
                'label' => 'Organization Name',
                'required' => true,
                'placeholder' => 'BoostMyShop'
            ),
            'url' => array(
                'type' => 'url',
                'label' => 'Website URL',
                'required' => true
            ),
            'logo' => array(
                'type' => 'url',
                'label' => 'Logo URL',
                'placeholder' => 'https://example.com/logo.png'
            ),
            'description' => array(
                'type' => 'textarea',
                'label' => 'Description'
            )
        );
    }
    
    private function get_breadcrumb_fields() {
        return array(
            'breadcrumbs' => array(
                'type' => 'repeater',
                'label' => 'Breadcrumb Items',
                'fields' => array(
                    'name' => array(
                        'type' => 'text',
                        'label' => 'Name',
                        'required' => true
                    ),
                    'url' => array(
                        'type' => 'url',
                        'label' => 'URL',
                        'required' => true
                    ),
                    'position' => array(
                        'type' => 'number',
                        'label' => 'Position',
                        'required' => true,
                        'min' => 1
                    )
                )
            )
        );
    }
    
    private function get_article_fields() {
        return array(
            'headline' => array(
                'type' => 'text',
                'label' => 'Headline',
                'required' => true
            ),
            'description' => array(
                'type' => 'textarea',
                'label' => 'Description',
                'required' => true
            )
        );
    }
}
