jQuery(document).ready(function ($) {
	var schemaCounter = 0;

	// Toggle custom schema fields with text update
	$("#asm_enable_custom")
		.change(function () {
			var isChecked = $(this).is(":checked");
			var toggleText = $(this)
				.closest(".asm-toggle-container")
				.find(".asm-toggle-text");

			if (isChecked) {
				$("#schema-mode-row").show();
				toggleText.text("Enabled");
				toggleSchemaMode();
			} else {
				$(
					"#schema-mode-row, #single-schema-row, #multiple-schemas-row, #custom-json-row, #schema-fields-row"
				).hide();
				toggleText.text("Disabled");
			}
		})
		.trigger("change");

	// Add smooth animation to toggle
	$(".asm-toggle-switch input").change(function () {
		var container = $(this).closest(".asm-toggle-container");
		container.addClass("asm-toggle-animating");

		setTimeout(function () {
			container.removeClass("asm-toggle-animating");
		}, 400);
	});

	// Toggle schema mode
	$("#asm_schema_mode").change(function () {
		toggleSchemaMode();
	});

	function toggleSchemaMode() {
		var mode = $("#asm_schema_mode").val();

		$(
			"#single-schema-row, #multiple-schemas-row, #custom-json-row, #schema-fields-row"
		).hide();

		switch (mode) {
			case "single":
				$("#single-schema-row").show();
				if ($("#asm_schema_type").val()) {
					$("#schema-fields-row").show();
					loadSchemaFields($("#asm_schema_type").val());
				}
				break;
			case "multiple":
				$("#multiple-schemas-row").show();
				break;
			case "custom_json":
				$("#custom-json-row").show();
				break;
		}
	}

	// Single schema type change
	$("#asm_schema_type").change(function () {
		var schemaType = $(this).val();
		if (schemaType) {
			$("#schema-fields-row").show();
			loadSchemaFields(schemaType);
		} else {
			$("#schema-fields-row").hide();
		}
	});

	// Add new schema item
	$("#add-schema-item").click(function () {
		addSchemaItem();
	});

	// Remove schema item
	$(document).on("click", ".remove-schema-item", function () {
		$(this).closest(".schema-item").remove();
	});

	// Schema type change for multiple schemas
	$(document).on("change", ".schema-type-select", function () {
		var schemaType = $(this).val();
		var container = $(this).siblings(".schema-fields-container");
		var index = $(this).closest(".schema-item").data("index");

		if (schemaType) {
			loadMultipleSchemaFields(schemaType, container, index);
		} else {
			container.empty();
		}
	});

	function addSchemaItem() {
		var template = `
            <div class="schema-item" data-index="${schemaCounter}">
                <h4>Schema ${schemaCounter + 1}</h4>
                <select name="asm_multiple_schemas[${schemaCounter}][type]" class="schema-type-select">
                    <option value="">Select Schema Type</option>
                    <option value="SoftwareApplication">Software Application</option>
                    <option value="FAQPage">FAQ Page</option>
                    <option value="Organization">Organization</option>
                    <option value="BreadcrumbList">Breadcrumb List</option>
                    <option value="ImageObject">Image Object</option>
                    <option value="LocalBusiness">Local Business</option>
                </select>
                <div class="schema-fields-container"></div>
                <button type="button" class="button remove-schema-item">Remove Schema</button>
                <hr>
            </div>
        `;

		$("#multiple-schemas-container").append(template);
		schemaCounter++;
	}

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
				}
			},
		});
	}

	function loadMultipleSchemaFields(schemaType, container, index) {
		$.ajax({
			url: asm_ajax.ajax_url,
			type: "POST",
			data: {
				action: "load_multiple_schema_fields",
				schema_type: schemaType,
				index: index,
				post_id: $("#post_ID").val(),
				nonce: asm_ajax.nonce,
			},
			success: function (response) {
				if (response.success) {
					container.html(response.data);
				}
			},
		});
	}

	// Initialize existing multiple schemas
	$(".schema-type-select").each(function () {
		if ($(this).val()) {
			var schemaType = $(this).val();
			var container = $(this).siblings(".schema-fields-container");
			var index = $(this).closest(".schema-item").data("index");
			loadMultipleSchemaFields(schemaType, container, index);
		}
	});

	// FAQ detection mode handler
	$(document).on("change", 'select[name*="faq_detection_mode"]', function () {
		var mode = $(this).val();
		var container = $(this).closest(".field-group").parent();

		if (mode === "manual") {
			container.find(".repeater-container").show();
			container.find(".add-repeater-item").show();
		} else {
			container.find(".repeater-container").hide();
			container.find(".add-repeater-item").hide();
		}
	});

	// Initialize FAQ detection mode on page load
	$('select[name*="faq_detection_mode"]').trigger("change");

	// Add repeater functionality
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

	// Toggle enhancement - add visual feedback
	$(".asm-toggle-switch").hover(
		function () {
			$(this).find(".asm-toggle-slider").addClass("asm-toggle-hover");
		},
		function () {
			$(this).find(".asm-toggle-slider").removeClass("asm-toggle-hover");
		}
	);
});
