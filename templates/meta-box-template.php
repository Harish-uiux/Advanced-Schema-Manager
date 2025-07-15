<div id="advanced-schema-manager">
    <table class="form-table">
        <tr>
            <th><label for="asm_enable_custom">Enable Custom Schema</label></th>
            <td>
                <input type="checkbox" id="asm_enable_custom" name="asm_enable_custom" value="1" <?php checked($enable_custom, 1); ?>>
                <p class="description">Check to use custom schema, uncheck to use Yoast SEO default</p>
            </td>
        </tr>
        <tr id="schema-type-row">
            <th><label for="asm_schema_type">Schema Type</label></th>
            <td>
                <select id="asm_schema_type" name="asm_schema_type">
                    <option value="">Select Schema Type</option>
                    <option value="Organization" <?php selected($schema_type, 'Organization'); ?>>Organization</option>
                    <option value="SoftwareApplication" <?php selected($schema_type, 'SoftwareApplication'); ?>>Software Application</option>
                    <option value="Product" <?php selected($schema_type, 'Product'); ?>>Product</option>
                    <option value="Article" <?php selected($schema_type, 'Article'); ?>>Article</option>
                    <option value="FAQPage" <?php selected($schema_type, 'FAQPage'); ?>>FAQ Page</option>
                    <option value="HowTo" <?php selected($schema_type, 'HowTo'); ?>>How To</option>
                    <option value="Service" <?php selected($schema_type, 'Service'); ?>>Service</option>
                    <option value="LocalBusiness" <?php selected($schema_type, 'LocalBusiness'); ?>>Local Business</option>
                    <option value="WebPage" <?php selected($schema_type, 'WebPage'); ?>>Web Page</option>
                    <option value="BreadcrumbList" <?php selected($schema_type, 'BreadcrumbList'); ?>>Breadcrumb List</option>
                </select>
            </td>
        </tr>
        <tr id="schema-fields-row">
            <th><label>Schema Fields</label></th>
            <td>
                <div id="schema-fields-container">
                    <!-- Dynamic fields will be loaded here -->
                </div>
            </td>
        </tr>
        <tr>
            <th><label for="asm_custom_schema">Custom Schema JSON</label></th>
            <td>
                <textarea id="asm_custom_schema" name="asm_custom_schema" rows="10" cols="50" class="large-text code"><?php echo esc_textarea($schema_data); ?></textarea>
                <p class="description">Add custom JSON-LD schema markup or use the fields above</p>
            </td>
        </tr>
    </table>
</div>
