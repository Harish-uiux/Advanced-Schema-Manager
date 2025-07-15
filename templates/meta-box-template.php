<div id="advanced-schema-manager">
    <table class="form-table">
        <tr>
            <th><label for="asm_enable_custom">Enable Custom Schema</label></th>
            <td>
                <input type="checkbox" id="asm_enable_custom" name="asm_enable_custom" value="1" <?php checked($enable_custom, 1); ?>>
                <p class="description">Check to use custom schema, uncheck to use Yoast SEO default</p>
            </td>
        </tr>
        <tr id="schema-mode-row" style="<?php echo $enable_custom ? '' : 'display:none;'; ?>">
            <th><label for="asm_schema_mode">Schema Mode</label></th>
            <td>
                <select id="asm_schema_mode" name="asm_schema_mode">
                    <option value="single" <?php selected($schema_mode, 'single'); ?>>Single Schema</option>
                    <option value="multiple" <?php selected($schema_mode, 'multiple'); ?>>Multiple Schemas</option>
                    <option value="custom_json" <?php selected($schema_mode, 'custom_json'); ?>>Custom JSON</option>
                </select>
                <p class="description">Choose how you want to add schema markup</p>
            </td>
        </tr>
        
        <!-- Single Schema Mode -->
        <tr id="single-schema-row" style="<?php echo ($enable_custom && $schema_mode == 'single') ? '' : 'display:none;'; ?>">
            <th><label for="asm_schema_type">Schema Type</label></th>
            <td>
                <select id="asm_schema_type" name="asm_schema_type">
                    <option value="">Select Schema Type</option>
                    <option value="SoftwareApplication" <?php selected($schema_type, 'SoftwareApplication'); ?>>Software Application</option>
                    <option value="Product" <?php selected($schema_type, 'Product'); ?>>Product</option>
                    <option value="Organization" <?php selected($schema_type, 'Organization'); ?>>Organization</option>
                    <option value="FAQPage" <?php selected($schema_type, 'FAQPage'); ?>>FAQ Page</option>
                    <option value="Article" <?php selected($schema_type, 'Article'); ?>>Article</option>
                    <option value="BreadcrumbList" <?php selected($schema_type, 'BreadcrumbList'); ?>>Breadcrumb List</option>
                    <option value="LocalBusiness" <?php selected($schema_type, 'LocalBusiness'); ?>>Local Business</option>
                    <option value="WebPage" <?php selected($schema_type, 'WebPage'); ?>>Web Page</option>
                    <option value="ImageObject" <?php selected($schema_type, 'ImageObject'); ?>>Image Object</option>
                </select>
            </td>
        </tr>
        
        <!-- Multiple Schemas Mode -->
        <tr id="multiple-schemas-row" style="<?php echo ($enable_custom && $schema_mode == 'multiple') ? '' : 'display:none;'; ?>">
            <th><label>Multiple Schemas</label></th>
            <td>
                <div id="multiple-schemas-container">
                    <?php if (!empty($multiple_schemas)): ?>
                        <?php foreach ($multiple_schemas as $index => $schema): ?>
                            <div class="schema-item" data-index="<?php echo $index; ?>">
                                <h4>Schema <?php echo $index + 1; ?></h4>
                                <select name="asm_multiple_schemas[<?php echo $index; ?>][type]" class="schema-type-select">
                                    <option value="">Select Schema Type</option>
                                    <option value="SoftwareApplication" <?php selected($schema['type'], 'SoftwareApplication'); ?>>Software Application</option>
                                    <option value="FAQPage" <?php selected($schema['type'], 'FAQPage'); ?>>FAQ Page</option>
                                    <option value="Organization" <?php selected($schema['type'], 'Organization'); ?>>Organization</option>
                                    <option value="BreadcrumbList" <?php selected($schema['type'], 'BreadcrumbList'); ?>>Breadcrumb List</option>
                                    <option value="ImageObject" <?php selected($schema['type'], 'ImageObject'); ?>>Image Object</option>
                                </select>
                                <div class="schema-fields-container">
                                    <!-- Fields will be loaded here -->
                                </div>
                                <button type="button" class="button remove-schema-item">Remove Schema</button>
                                <hr>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
                <button type="button" id="add-schema-item" class="button">Add Schema</button>
            </td>
        </tr>
        
        <!-- Custom JSON Mode -->
        <tr id="custom-json-row" style="<?php echo ($enable_custom && $schema_mode == 'custom_json') ? '' : 'display:none;'; ?>">
            <th><label for="asm_custom_schema">Custom Schema JSON</label></th>
            <td>
                <textarea id="asm_custom_schema" name="asm_custom_schema" rows="15" cols="50" class="large-text code"><?php echo esc_textarea($custom_schema); ?></textarea>
                <p class="description">Add custom JSON-LD schema markup (can be single schema object or array of schemas)</p>
            </td>
        </tr>
        
        <!-- Single Schema Fields -->
        <tr id="schema-fields-row" style="<?php echo ($enable_custom && $schema_mode == 'single' && $schema_type) ? '' : 'display:none;'; ?>">
            <th><label>Schema Fields</label></th>
            <td>
                <div id="schema-fields-container">
                    <?php if ($schema_type && $schema_mode == 'single') { 
                        $schema_fields = new SchemaFields();
                        $schema_fields->render_schema_fields($schema_type, $schema_data); 
                    } ?>
                </div>
            </td>
        </tr>
    </table>
</div>
