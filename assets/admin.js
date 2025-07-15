jQuery(document).ready(function ($) {
	// Toggle custom schema fields
	$("#asm_enable_custom")
		.change(function () {
			if ($(this).is(":checked")) {
				$("#schema-type-row, #schema-fields-row, #custom-schema-row").show();
			} else {
				$("#schema-type-row, #schema-fields-row, #custom-schema-row").hide();
			}
		})
		.trigger("change");

	// Load schema fields based on type
	$("#asm_schema_type").change(function () {
		var schemaType = $(this).val();
		if (schemaType) {
			loadSchemaFields(schemaType);
		} else {
			$("#schema-fields-container").empty();
		}
	});

	function loadSchemaFields(schemaType) {
		$.ajax({
			url: asm_ajax.ajax_url,
			type: "POST",
			data: {
				action: "load_schema_fields",
				schema_type: schemaType,
				post_id: $("#post_ID").val(),
				nonce: asm_ajax.nonce,
			},
			success: function (response) {
				if (response.success) {
					$("#schema-fields-container").html(response.data);
					initializeRepeaterHandlers();
				}
			},
		});
	}

	function initializeRepeaterHandlers() {
		// Add repeater item
		$(document).on("click", ".add-repeater-item", function (e) {
			e.preventDefault();
			var button = $(this);
			var template = button.siblings(".repeater-template").html();
			var container = button.siblings(".repeater-container");
			var index = container.find(".repeater-item").length;

			template = template.replace(/\[INDEX\]/g, index);
			container.append(template);
		});

		// Remove repeater item
		$(document).on("click", ".remove-repeater-item", function (e) {
			e.preventDefault();
			$(this).closest(".repeater-item").remove();
		});
	}

	// Initialize on page load if schema type is already selected
	if ($("#asm_schema_type").val()) {
		loadSchemaFields($("#asm_schema_type").val());
	}

	// Initialize repeater handlers for existing fields
	initializeRepeaterHandlers();
});
