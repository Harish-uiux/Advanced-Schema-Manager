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
    
    $schema_mode = get_post_meta($post_id, '_asm_schema_mode', true);
    
    switch ($schema_mode) {
        case 'single':
            $this->output_single_schema($post_id);
            break;
        case 'multiple':
            $this->output_multiple_schemas($post_id);
            break;
        case 'custom_json':
            $this->output_custom_json_schema($post_id);
            break;
    }
}

private function output_single_schema($post_id) {
    $schema_type = get_post_meta($post_id, '_asm_schema_type', true);
    $schema_data = get_post_meta($post_id, '_asm_schema_data', true);
    
    $generated_schema = $this->generate_schema($schema_type, $schema_data, $post_id);
    if ($generated_schema) {
        echo '<script type="application/ld+json">';
        echo json_encode($generated_schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        echo '</script>';
    }
}

private function output_multiple_schemas($post_id) {
    $multiple_schemas = get_post_meta($post_id, '_asm_multiple_schemas', true);
    
    if (!empty($multiple_schemas)) {
        foreach ($multiple_schemas as $schema_item) {
            if (!empty($schema_item['type'])) {
                $generated_schema = $this->generate_schema($schema_item['type'], $schema_item['data'], $post_id);
                if ($generated_schema) {
                    echo '<script type="application/ld+json">';
                    echo json_encode($generated_schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                    echo '</script>';
                }
            }
        }
    }
}

private function output_custom_json_schema($post_id) {
    $custom_schema = get_post_meta($post_id, '_asm_custom_schema', true);
    
    if (!empty($custom_schema)) {
        $decoded = json_decode($custom_schema, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            echo '<script type="application/ld+json">';
            echo $custom_schema;
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
    
    $detection_mode = !empty($data['faq_detection_mode']) ? $data['faq_detection_mode'] : 'manual';
    
    switch ($detection_mode) {
        case 'manual':
            $faqs = !empty($data['faqs']) ? $data['faqs'] : array();
            break;
        case 'auto_h3':
            $faqs = $this->extract_faqs_from_h3($post->post_content);
            break;
        case 'auto_accordion':
            $faqs = $this->extract_faqs_from_accordion($post->post_content);
            break;
        case 'auto_details':
            $faqs = $this->extract_faqs_from_details($post->post_content);
            break;
        default:
            $faqs = array();
    }
    
    if (!empty($faqs)) {
        $mainEntity = array();
        foreach ($faqs as $faq) {
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

// Extract FAQs from H3 tags pattern
private function extract_faqs_from_h3($content) {
    $faqs = array();
    
    // Pattern: <h3>Question</h3> followed by content until next h3
    $pattern = '/<h3[^>]*>(.*?)<\/h3>\s*(.*?)(?=<h3|$)/is';
    
    if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $question = strip_tags($match[1]);
            $answer = strip_tags($match[2]);
            
            // Only include if it looks like a question
            if (preg_match('/\?$/', $question) && !empty($answer)) {
                $faqs[] = array(
                    'question' => trim($question),
                    'answer' => trim($answer)
                );
            }
        }
    }
    
    return $faqs;
}

// Extract FAQs from WordPress accordion blocks
private function extract_faqs_from_accordion($content) {
    $faqs = array();
    
    // Pattern for Gutenberg accordion blocks
    $pattern = '/<!-- wp:group[^>]*-->\s*<div[^>]*>.*?<h[0-9][^>]*>(.*?)<\/h[0-9]>.*?<p[^>]*>(.*?)<\/p>.*?<\/div>\s*<!-- \/wp:group -->/is';
    
    if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $question = strip_tags($match[1]);
            $answer = strip_tags($match[2]);
            
            if (!empty($question) && !empty($answer)) {
                $faqs[] = array(
                    'question' => trim($question),
                    'answer' => trim($answer)
                );
            }
        }
    }
    
    return $faqs;
}

// Extract FAQs from HTML details tags
private function extract_faqs_from_details($content) {
    $faqs = array();
    
    // Pattern: <details><summary>Question</summary>Answer</details>
    $pattern = '/<details[^>]*>\s*<summary[^>]*>(.*?)<\/summary>\s*(.*?)\s*<\/details>/is';
    
    if (preg_match_all($pattern, $content, $matches, PREG_SET_ORDER)) {
        foreach ($matches as $match) {
            $question = strip_tags($match[1]);
            $answer = strip_tags($match[2]);
            
            if (!empty($question) && !empty($answer)) {
                $faqs[] = array(
                    'question' => trim($question),
                    'answer' => trim($answer)
                );
            }
        }
    }
    
    return $faqs;
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
