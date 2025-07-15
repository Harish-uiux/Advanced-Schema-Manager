<?php
class SchemaFields {
    
    public static function get_software_application_fields() {
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
                'placeholder' => 'Order tracking\nInventory management\nShipping integration'
            ),
            'screenshot' => array(
                'type' => 'url',
                'label' => 'Screenshot URL',
                'placeholder' => 'https://example.com/screenshot.jpg'
            ),
            'downloadUrl' => array(
                'type' => 'url',
                'label' => 'Download URL',
                'placeholder' => 'https://example.com/download'
            ),
            'installUrl' => array(
                'type' => 'url',
                'label' => 'Install URL',
                'placeholder' => 'https://example.com/install'
            ),
            'aggregateRating' => array(
                'type' => 'group',
                'label' => 'Aggregate Rating',
                'fields' => array(
                    'ratingValue' => array(
                        'type' => 'number',
                        'label' => 'Rating Value',
                        'min' => 1,
                        'max' => 5,
                        'step' => 0.1
                    ),
                    'ratingCount' => array(
                        'type' => 'number',
                        'label' => 'Rating Count',
                        'min' => 1
                    )
                )
            )
        );
    }
    
    public static function get_product_fields() {
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
            'category' => array(
                'type' => 'text',
                'label' => 'Category',
                'placeholder' => 'Business Software'
            ),
            'sku' => array(
                'type' => 'text',
                'label' => 'SKU',
                'placeholder' => 'BMS-OMS-2025'
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
            ),
            'availability' => array(
                'type' => 'select',
                'label' => 'Availability',
                'options' => array(
                    'https://schema.org/InStock' => 'In Stock',
                    'https://schema.org/OutOfStock' => 'Out of Stock',
                    'https://schema.org/PreOrder' => 'Pre Order'
                )
            ),
            'image' => array(
                'type' => 'url',
                'label' => 'Product Image',
                'placeholder' => 'https://example.com/product-image.jpg'
            )
        );
    }
    
    public static function get_faq_fields() {
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
    
    public static function get_organization_fields() {
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
            ),
            'address' => array(
                'type' => 'group',
                'label' => 'Address',
                'fields' => array(
                    'streetAddress' => array(
                        'type' => 'text',
                        'label' => 'Street Address'
                    ),
                    'addressLocality' => array(
                        'type' => 'text',
                        'label' => 'City'
                    ),
                    'addressRegion' => array(
                        'type' => 'text',
                        'label' => 'State/Region'
                    ),
                    'postalCode' => array(
                        'type' => 'text',
                        'label' => 'Postal Code'
                    ),
                    'addressCountry' => array(
                        'type' => 'text',
                        'label' => 'Country'
                    )
                )
            ),
            'contactPoint' => array(
                'type' => 'group',
                'label' => 'Contact Information',
                'fields' => array(
                    'telephone' => array(
                        'type' => 'text',
                        'label' => 'Phone Number'
                    ),
                    'email' => array(
                        'type' => 'email',
                        'label' => 'Email'
                    ),
                    'contactType' => array(
                        'type' => 'select',
                        'label' => 'Contact Type',
                        'options' => array(
                            'customer service' => 'Customer Service',
                            'sales' => 'Sales',
                            'support' => 'Support'
                        )
                    )
                )
            ),
            'sameAs' => array(
                'type' => 'textarea',
                'label' => 'Social Media URLs (one per line)',
                'placeholder' => 'https://linkedin.com/company/boostmyshop'
            )
        );
    }
    
    public static function get_breadcrumb_fields() {
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
}
