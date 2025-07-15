<div id="advanced-schema-manager">
    <table class="form-table">
        <tr>
            <th><label for="asm_enable_custom"><?php _e('Enable Custom Schema', 'advanced-schema-manager'); ?></label></th>
            <td>
                <div class="asm-toggle-container">
                    <label class="asm-toggle-switch">
                        <input type="checkbox" id="asm_enable_custom" name="asm_enable_custom" value="1" <?php checked($enable_custom, true); ?>>
                        <span class="asm-toggle-slider"></span>
                    </label>
                    <span class="asm-toggle-label">
                        <span class="asm-toggle-text"><?php echo $enable_custom ? __('Enabled', 'advanced-schema-manager') : __('Disabled', 'advanced-schema-manager'); ?></span>
                    </span>
                </div>
                <p class="description"><?php _e('Toggle to use custom schema, or keep disabled to use Yoast SEO default', 'advanced-schema-manager'); ?></p>
            </td>
        </tr>
        
        <tr id="schema-mode-row" style="<?php echo $enable_custom ? '' : 'display:none;'; ?>">
            <th><label for="asm_schema_mode"><?php _e('Schema Mode', 'advanced-schema-manager'); ?></label></th>
            <td>
                <select id="asm_schema_mode" name="asm_schema_mode">
                    <option value="single" <?php selected($schema_mode, 'single'); ?>><?php _e('Single Schema', 'advanced-schema-manager'); ?></option>
                    <option value="multiple" <?php selected($schema_mode, 'multiple'); ?>><?php _e('Multiple Schemas', 'advanced-schema-manager'); ?></option>
                    <option value="custom_json" <?php selected($schema_mode, 'custom_json'); ?>><?php _e('Custom JSON', 'advanced-schema-manager'); ?></option>
                </select>
                <p class="description"><?php _e('Choose how you want to add schema markup', 'advanced-schema-manager'); ?></p>
            </td>
        </tr>
        
        <!-- Single Schema -->
        <tr id="single-schema-row" style="<?php echo ($enable_custom && $schema_mode == 'single') ? '' : 'display:none;'; ?>">
            <th><label for="asm_schema_type"><?php _e('Schema Type', 'advanced-schema-manager'); ?></label></th>
            <td>
                <select id="asm_schema_type" name="asm_schema_type">
                    <option value=""><?php _e('Select Schema Type', 'advanced-schema-manager'); ?></option>
                    <option value="SoftwareApplication" <?php selected($schema_type, 'SoftwareApplication'); ?>><?php _e('Software Application', 'advanced-schema-manager'); ?></option>
                    <option value="Product" <?php selected($schema_type, 'Product'); ?>><?php _e('Product', 'advanced-schema-manager'); ?></option>
                    <option value="Organization" <?php selected($schema_type, 'Organization'); ?>><?php _e('Organization', 'advanced-schema-manager'); ?></option>
                    <option value="FAQPage" <?php selected($schema_type, 'FAQPage'); ?>><?php _e('FAQ Page', 'advanced-schema-manager'); ?></option>
                    <option value="Article" <?php selected($schema_type, 'Article'); ?>><?php _e('Article', 'advanced-schema-manager'); ?></option>
                    <option value="BreadcrumbList" <?php selected($schema_type, 'BreadcrumbList'); ?>><?php _e('Breadcrumb List', 'advanced-schema-manager'); ?></option>
                    <option value="LocalBusiness" <?php selected($schema_type, 'LocalBusiness'); ?>><?php _e('Local Business', 'advanced-schema-manager'); ?></option>
                    <option value="WebPage" <?php selected($schema_type, 'WebPage'); ?>><?php _e('Web Page', 'advanced-schema-manager'); ?></option>
                    <option value="ImageObject" <?php selected($schema_type, 'ImageObject'); ?>><?php _e('Image Object', 'advanced-schema-manager'); ?></option>
                </select>
            </td>
        </tr>
        
        <!-- Multiple Schemas -->
        <tr id="multiple-schemas-row" style="<?php echo ($enable_custom && $schema_mode == 'multiple') ? '' : 'display:none;'; ?>">
            <th><label><?php _e('Multiple Schemas', 'advanced-schema-manager'); ?></label></th>
            <td>
                <div id="multiple-schemas-container">
                    <?php if (!empty($multiple_schemas)): ?>
                        <?php foreach ($multiple_schemas as $index => $schema): ?>
                            <div class="schema-item" data-index="<?php echo esc_attr($index); ?>">
                                <h4><?php printf(__('Schema %d', 'advanced-schema-manager'), $index + 1); ?></h4>
                                <select name="asm_multiple_schemas[<?php echo esc_attr($index); ?>][type]" class="schema-type-select">
                                    <option value=""><?php _e('Select Schema Type', 'advanced-schema-manager'); ?></option>
                                    <option value="SoftwareApplication" <?php selected($schema['type'], 'SoftwareApplication'); ?>><?php _e('Software Application', 'advanced-schema-manager'); ?></option>
                                    <option value="FAQPage" <?php selected($schema['type'], 'FAQPage'); ?>><?php _e('FAQ Page', 'advanced-schema-manager'); ?></option>
                                    <option value="Organization" <?php selected($schema['type'], 'Organization'); ?>><?php _e('Organization', 'advanced-schema-manager'); ?></option>
                                    <option value="BreadcrumbList" <?php selected($schema['type'], 'BreadcrumbList'); ?>><?php _e('Breadcrumb List', 'advanced-schema-manager'); ?></option>
                                    <option value="ImageObject" <?php selected($schema['type'], 'ImageObject'); ?>><?php _e('Image Object', 'advanced-schema-manager'); ?></option>
                                </select>
                                <div class="schema-fields-container">
                                    <?php 
                                    if (!empty($schema['type']) && !empty($schema['data'])) {
                                        $this->schema_fields->render_multiple_schema_fields($schema['type'], $index, $schema['data']);
                                    }
                                    ?>
                                </div>
                                <button type="button" class="button remove-schema-item"><?php _e('Remove Schema', 'advanced-schema-manager'); ?></button>
                                <hr>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <button type="button" id="add-schema-item" class="button button-secondary"><?php _e('Add Schema', 'advanced-schema-manager'); ?></button>
            </td>
        </tr>
        
        <!-- Custom JSON -->
        <tr id="custom-json-row" style="<?php echo ($enable_custom && $schema_mode == 'custom_json') ? '' : 'display:none;'; ?>">
            <th><label for="asm_custom_schema"><?php _e('Custom Schema JSON', 'advanced-schema-manager'); ?></label></th>
            <td>
                <div class="asm-json-editor">
                    <textarea 
                        id="asm_custom_schema" 
                        name="asm_custom_schema" 
                        rows="20" 
                        cols="50" 
                        class="large-text code"
                        placeholder='<?php echo esc_attr(__('Example:', 'advanced-schema-manager') . "\n" . '{
  "@context": "https://schema.org",
  "@type": "SoftwareApplication",
  "name": "Your App Name",
  "description": "Your app description",
  "applicationCategory": "BusinessApplication",
  "operatingSystem": "Web-based"
}'); ?>'><?php echo esc_textarea($custom_schema); ?></textarea>
                </div>
                <p class="description"><?php _e('Add custom JSON-LD schema markup. You can use a single schema object or an array of multiple schemas.', 'advanced-schema-manager'); ?></p>
                
                <div class="asm-json-tools">
                    <button type="button" id="asm-validate-json" class="button button-secondary">
                        <?php _e('Validate JSON', 'advanced-schema-manager'); ?>
                    </button>
                    <button type="button" id="asm-format-json" class="button button-secondary">
                        <?php _e('Format JSON', 'advanced-schema-manager'); ?>
                    </button>
                    <button type="button" id="asm-minify-json" class="button button-secondary">
                        <?php _e('Minify JSON', 'advanced-schema-manager'); ?>
                    </button>
                </div>
                
                <div id="asm-json-status" class="asm-json-status"></div>
                
                <?php
                $json_error = get_post_meta($post->ID, '_asm_json_error', true);
                if ($json_error) {
                    echo '<div class="asm-json-error notice notice-error inline">';
                    echo '<p>' . esc_html($json_error) . '</p>';
                    echo '</div>';
                }
                ?>
            </td>
        </tr>
        
        <!-- Single Schema Fields -->
        <tr id="schema-fields-row" style="<?php echo ($enable_custom && $schema_mode == 'single' && $schema_type) ? '' : 'display:none;'; ?>">
            <th><label><?php _e('Schema Fields', 'advanced-schema-manager'); ?></label></th>
            <td>
                <div id="schema-fields-container">
                    <?php if ($schema_type && $schema_mode == 'single') { 
                        $this->schema_fields->render_schema_fields($schema_type, $schema_data); 
                    } ?>
                </div>
            </td>
        </tr>
    </table>
    
    <?php if (defined('WP_DEBUG') && WP_DEBUG): ?>
        <?php $this->debug_schema_data($post->ID); ?>
    <?php endif; ?>
</div>
