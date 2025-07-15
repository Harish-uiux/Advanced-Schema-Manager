<?php
class SchemaOutput {
    
    public static function output_schema($post_id) {
        $enable_custom = get_post_meta($post_id, '_asm_enable_custom', true);
        
        if (!$enable_custom) {
            // Let Yoast handle schema
            return;
        }
        
        $schema_type = get_post_meta($post_id, '_asm_schema_type', true);
        $schema_data = get_post_meta($post_id, '_asm_schema_data', true);
        $custom_schema = get_post_meta($post_id, '_asm_custom_schema', true);
        
        if (!empty($custom_schema)) {
            // Output custom JSON-LD
            echo '<script type="application/ld+json">';
            echo $custom_schema;
            echo '</script>';
        } else {
            // Generate schema from fields
            $generated_schema = self::generate_schema($schema_type, $schema_data, $post_id);
            if ($generated_schema) {
                echo '<script type="application/ld+json">';
                echo json_encode($generated_schema, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                echo '</script>';
            }
        }
    }
    
    private static function generate_schema($schema_type, $schema_data, $post_id) {
        $post = get_post($post_id);
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => $schema_type,
            '@id' => get_permalink($post_id) . '#' . $schema_type,
            'url' => get_permalink($post_id)
        );
        
        switch ($schema_type) {
            case 'SoftwareApplication':
                return self::generate_software_application_schema($schema, $schema_data, $post);
            case 'Product':
                return self::generate_product_schema($schema, $schema_data, $post);
            case 'FAQPage':
                return self::generate_faq_schema($schema, $schema_data, $post);
            case 'Organization':
                return self::generate_organization_schema($schema, $schema_data, $post);
            case 'BreadcrumbList':
                return self::generate_breadcrumb_schema($schema, $schema_data, $post);
            default:
                return null;
        }
    }
    
    private static function generate_software_application_schema($schema, $data, $post) {
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
            $schema['featureList'] = array_map('trim', $features);
        }
        
        if (!empty($data['screenshot'])) {
            $schema['screenshot'] = array(
                '@type' => 'ImageObject',
                'url' => $data['screenshot']
            );
        }
        
        if (!empty($data['downloadUrl'])) {
            $schema['downloadUrl'] = $data['downloadUrl'];
        }
        
        if (!empty($data['installUrl'])) {
            $schema['installUrl'] = $data['installUrl'];
        }
        
        if (!empty($data['aggregateRating']['ratingValue'])) {
            $schema['aggregateRating'] = array(
                '@type' => 'AggregateRating',
                'ratingValue' => $data['aggregateRating']['ratingValue'],
                'ratingCount' => !empty($data['aggregateRating']['ratingCount']) ? $data['aggregateRating']['ratingCount'] : 1
            );
        }
        
        // Add provider (organization)
        $schema['provider'] = array(
            '@type' => 'Organization',
            'name' => 'BoostMyShop',
            'url' => home_url()
        );
        
        return $schema;
    }
    
    private static function generate_product_schema($schema, $data, $post) {
        $schema['name'] = !empty($data['name']) ? $data['name'] : $post->post_title;
        $schema['description'] = !empty($data['description']) ? $data['description'] : get_the_excerpt($post->ID);
        
        if (!empty($data['brand'])) {
            $schema['brand'] = array(
                '@type' => 'Brand',
                'name' => $data['brand']
            );
        }
        
        if (!empty($data['category'])) {
            $schema['category'] = $data['category'];
        }
        
        if (!empty($data['sku'])) {
            $schema['sku'] = $data['sku'];
        }
        
        if (!empty($data['price'])) {
            $schema['offers'] = array(
                '@type' => 'Offer',
                'price' => $data['price'],
                'priceCurrency' => !empty($data['priceCurrency']) ? $data['priceCurrency'] : 'USD',
                'availability' => !empty($data['availability']) ? $data['availability'] : 'https://schema.org/InStock'
            );
        }
        
        if (!empty($data['image'])) {
            $schema['image'] = array(
                '@type' => 'ImageObject',
                'url' => $data['image']
            );
        }
        
        return $schema;
    }
    
    private static function generate_faq_schema($schema, $data, $post) {
        $schema['name'] = $post->post_title;
        $schema['description'] = get_the_excerpt($post->ID);
        
        if (!empty($data['faqs'])) {
            $mainEntity = array();
            foreach ($data['faqs'] as $faq) {
                $mainEntity[] = array(
                    '@type' => 'Question',
                    'name' => $faq['question'],
                    'acceptedAnswer' => array(
                        '@type' => 'Answer',
                        'text' => $faq['answer']
                    )
                );
            }
            $schema['mainEntity'] = $mainEntity;
        }
        
        return $schema;
    }
    
    private static function generate_organization_schema($schema, $data, $post) {
        $schema['name'] = !empty($data['name']) ? $data['name'] : get_bloginfo('name');
        $schema['url'] = !empty($data['url']) ? $data['url'] : home_url();
        
        if (!empty($data['logo'])) {
            $schema['logo'] = array(
                '@type' => 'ImageObject',
                'url' => $data['logo']
            );
        }
        
        if (!empty($data['description'])) {
            $schema['description'] = $data['description'];
        }
        
        if (!empty($data['address'])) {
            $address = array('@type' => 'PostalAddress');
            foreach ($data['address'] as $key => $value) {
                if (!empty($value)) {
                    $address[$key] = $value;
                }
            }
            if (count($address) > 1) {
                $schema['address'] = $address;
            }
        }
        
        if (!empty($data['contactPoint'])) {
            $contactPoint = array('@type' => 'ContactPoint');
            foreach ($data['contactPoint'] as $key => $value) {
                if (!empty($value)) {
                    $contactPoint[$key] = $value;
                }
            }
            if (count($contactPoint) > 1) {
                $schema['contactPoint'] = $contactPoint;
            }
        }
        
        if (!empty($data['sameAs'])) {
            $sameAs = explode("\n", $data['sameAs']);
            $schema['sameAs'] = array_map('trim', array_filter($sameAs));
        }
        
        return $schema;
    }
    
    private static function generate_breadcrumb_schema($schema, $data, $post) {
        if (!empty($data['breadcrumbs'])) {
            $itemListElement = array();
            foreach ($data['breadcrumbs'] as $breadcrumb) {
                $itemListElement[] = array(
                    '@type' => 'ListItem',
                    'position' => (int)$breadcrumb['position'],
                    'name' => $breadcrumb['name'],
                    'item' => $breadcrumb['url']
                );
            }
            
            // Sort by position
            usort($itemListElement, function($a, $b) {
                return $a['position'] - $b['position'];
            });
            
            $schema['itemListElement'] = $itemListElement;
        }
        
        return $schema;
    }
}
