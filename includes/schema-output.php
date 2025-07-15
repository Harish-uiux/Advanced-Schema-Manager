<?php

class SchemaOutput {
    
    public function output_schema($post_id) {
        $enable_custom = get_post_meta($post_id, '_asm_enable_custom', true);
        
        if (!$enable_custom) {
            // Let Yoast handle schema
            return;
        }
        
        // Remove Yoast schema output when custom is enabled
        add_filter('wpseo_json_ld_output', '__return_false');
        
        $schema_type = get_post_meta($post_id, '_asm_schema_type', true);
        $schema_data = get_post_meta($post_id, '_asm_schema_data', true);
        $custom_schema = get_post_meta($post_id, '_asm_custom_schema', true);
        
        if (!empty($custom_schema)) {
            // Validate and output custom JSON-LD
            $decoded = json_decode($custom_schema, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                echo '<script type="application/ld+json">';
                echo $custom_schema;
                echo '</script>';
            }
        } else {
            // Generate schema from fields
            $generated_schema = $this->generate_schema($schema_type, $schema_data, $post_id);
            if ($generated_schema) {
                echo '<script type="application/ld+json">';
                echo json_encode($generated_schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                echo '</script>';
            }
        }
    }
    
    private function generate_schema($schema_type, $schema_data, $post_id) {
        $post = get_post($post_id);
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => $schema_type,
            '@id' => get_permalink($post_id) . '#' . $schema_type,
            'url' => get_permalink($post_id)
        );
        
        switch ($schema_type) {
            case 'SoftwareApplication':
                return $this->generate_software_application_schema($schema, $schema_data, $post);
            case 'Product':
                return $this->generate_product_schema($schema, $schema_data, $post);
            case 'FAQPage':
                return $this->generate_faq_schema($schema, $schema_data, $post);
            case 'Organization':
                return $this->generate_organization_schema($schema, $schema_data, $post);
            case 'BreadcrumbList':
                return $this->generate_breadcrumb_schema($schema, $schema_data, $post);
            case 'Article':
                return $this->generate_article_schema($schema, $schema_data, $post);
            default:
                return null;
        }
    }
    
    private function generate_software_application_schema($schema, $data, $post) {
        $schema['name'] = !empty($data['name']) ? $data['name'] : $post->post_title;
        $schema['description'] = !empty($data['description']) ? $data['description'] : get_the_excerpt($post->ID);
        
        if (!empty($data['applicationCategory'])) {
            $schema['applicationCategory'] = $data['applicationCategory'];
        }
        
        if (!empty($data['operatingSystem'])) {
            $schema['operatingSystem'] = $data['operatingSystem'];
        }
        
        if (!empty($data['softwareVersion'])) {
            $schema['softwareVersion'] = $data['softwareVersion'];
        }
        
        if (!empty($data['price'])) {
            $schema['offers'] = array(
                '@type' => 'Offer',
                'price' => $data['price'],
                'priceCurrency' => !empty($data['priceCurrency']) ? $data['priceCurrency'] : 'USD',
                'availability' => 'https://schema.org/InStock'
            );
        }
        
        if (!empty($data['features'])) {
            $features = explode("\n", $data['features']);
            $schema['featureList'] = array_map('trim', array_filter($features));
        }
        
        if (!empty($data['screenshot'])) {
            $schema['screenshot'] = array(
                '@type' => 'ImageObject',
                'url' => $data['screenshot']
            );
        }
        
        // Add provider (organization)
        $schema['provider'] = array(
            '@type' => 'Organization',
            'name' => get_bloginfo('name'),
            'url' => home_url()
        );
        
        return $schema;
    }
    
    private function generate_product_schema($schema, $data, $post) {
        $schema['name'] = !empty($data['name']) ? $data['name'] : $post->post_title;
        $schema['description'] = !empty($data['description']) ? $data['description'] : get_the_excerpt($post->ID);
        
        if (!empty($data['brand'])) {
            $schema['brand'] = array(
                '@type' => 'Brand',
                'name' => $data['brand']
            );
        }
        
        if (!empty($data['price'])) {
            $schema['offers'] = array(
                '@type' => 'Offer',
                'price' => $data['price'],
                'priceCurrency' => !empty($data['priceCurrency']) ? $data['priceCurrency'] : 'USD',
                'availability' => 'https://schema.org/InStock'
            );
        }
        
        return $schema;
    }
    
    private function generate_faq_schema($schema, $data, $post) {
        $schema['name'] = $post->post_title;
        
        if (!empty($data['faqs'])) {
            $mainEntity = array();
            foreach ($data['faqs'] as $faq) {
                if (!empty($faq['question']) && !empty($faq['answer'])) {
                    $mainEntity[] = array(
                        '@type' => 'Question',
                        'name' => $faq['question'],
                        'acceptedAnswer' => array(
                            '@type' => 'Answer',
                            'text' => $faq['answer']
                        )
                    );
                }
            }
            $schema['mainEntity'] = $mainEntity;
        }
        
        return $schema;
    }
    
    private function generate_organization_schema($schema, $data, $post) {
        $schema['name'] = !empty($data['name']) ? $data['name'] : get_bloginfo('name');
        $schema['url'] = !empty($data['url']) ? $data['url'] : home_url();
        
        if (!empty($data['description'])) {
            $schema['description'] = $data['description'];
        }
        
        if (!empty($data['logo'])) {
            $schema['logo'] = array(
                '@type' => 'ImageObject',
                'url' => $data['logo']
            );
        }
        
        return $schema;
    }
    
    private function generate_breadcrumb_schema($schema, $data, $post) {
        if (!empty($data['breadcrumbs'])) {
            $itemListElement = array();
            foreach ($data['breadcrumbs'] as $breadcrumb) {
                if (!empty($breadcrumb['name']) && !empty($breadcrumb['url'])) {
                    $itemListElement[] = array(
                        '@type' => 'ListItem',
                        'position' => (int)$breadcrumb['position'],
                        'name' => $breadcrumb['name'],
                        'item' => $breadcrumb['url']
                    );
                }
            }
            
            usort($itemListElement, function($a, $b) {
                return $a['position'] - $b['position'];
            });
            
            $schema['itemListElement'] = $itemListElement;
        }
        
        return $schema;
    }
    
    private function generate_article_schema($schema, $data, $post) {
        $schema['headline'] = !empty($data['headline']) ? $data['headline'] : $post->post_title;
        $schema['description'] = !empty($data['description']) ? $data['description'] : get_the_excerpt($post->ID);
        $schema['datePublished'] = get_the_date('c', $post->ID);
        $schema['dateModified'] = get_the_modified_date('c', $post->ID);
        
        $schema['author'] = array(
            '@type' => 'Person',
            'name' => get_the_author_meta('display_name', $post->post_author)
        );
        
        $schema['publisher'] = array(
            '@type' => 'Organization',
            'name' => get_bloginfo('name'),
            'url' => home_url()
        );
        
        return $schema;
    }
}
