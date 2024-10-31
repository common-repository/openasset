import "./styles/main.scss";
// Move details on sinlge project page under description on smaller screens
jQuery(document).ready(function ($) {
	function moveDetailsDiv() {
		// Check if the screen width is md (768px) or smaller
		if ($(window).width() <= 768) {
			// Move .oa-details to be after .oa-description within the .col-md-8 column
			$(".oa-details").insertAfter(".oa-description");
			$(".oa-team-member-projects").insertAfter(".oa-details");
		} else {
			// Move .oa-details back to its original position after .oa-gallery
			$(".oa-details").insertAfter(".oa-details-wide");
			$(".oa-team-member-projects").insertAfter(".oa-profile-image");
		}
	}

	// Run the function on load
	moveDetailsDiv();

	// Bind the function to the window resize event
	$(window).resize(moveDetailsDiv);
});

// custom search input logic
jQuery(document).ready(function($) {
    var inputContainer = $('.custom-input');
    var input = $('.search-field');

    // Function to toggle icon visibility
    function toggleIconVisibility() {
        if (input.val()) {
            inputContainer.removeClass('hide-icon');
        } else {
            inputContainer.addClass('hide-icon');
        }
    }

    // Initial check
    toggleIconVisibility();

    // Event listener for input changes
    input.on('input', toggleIconVisibility);

    // Click event to clear the input
    inputContainer.click(function(event) {
        var iconWidth = 20; // Width of your icon
        var clickX = event.clientX - input.offset().left;

        if (clickX > input.outerWidth() - iconWidth - 10) { // 10 is right padding, adjust as needed
            input.val('');
            toggleIconVisibility();
        }
    });
});

// Script for keyword-filters-new.php
jQuery(document).ready(function ($) {
	// Ensure script only runs when the partial is visible
	if ($(".oa-keyword-filters-new").length) {
		// Fetch the selected keywords from the data attribute
		var selectedKeywords = [];
		try {
			selectedKeywords =
				JSON.parse(
					$(".oa-keyword-filters-new").data("selected-keywords")
				) || [];
		} catch (e) {
			console.error("Error parsing selected keywords:", e);
		}

		// Your existing JavaScript code here...

		// Set selected keywords from data attribute
		selectedKeywords.forEach(function (keyword) {
			$('select.oa-child-keywords option[value="' + keyword + '"]').prop(
				"selected",
				true
			);
		});

		var dropdownArrow =
			'<svg class="dropdown-arrow" version="1.1" id="Capa_1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" width="12px" height="12px" viewBox="0 0 201.458 201.457" xml:space="preserve"><g><path d="M193.177,46.233l8.28,8.28L100.734,155.241L0,54.495l8.28-8.279l92.46,92.46L193.177,46.233z"></path></g></svg>';
		var closeIcon =
			'<svg class="close-icon" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M1 1L13 13" stroke="#0400C3"/><path d="M1 13L13 0.999999" stroke="#0400C3"/></svg>';

		function updateSelect2Arrow($select2Container) {
			var arrow = $select2Container.find(".select2-selection__arrow");
			if ($select2Container.prev("select").val()) {
				arrow.html(closeIcon).show();
			} else {
				arrow.html(dropdownArrow).show();
			}
		}

		function alignSelect2Dropdown($selectElement) {
			$(".custom-dropdown").parent().css({ display: "none" });

			setTimeout(function () {
				$(".custom-dropdown")
					.parent()
					.css({ left: "unset", display: "inline-block" });
			}, 1);
		}

		function initializeSelect2($selectElement) {
			var placeholder =
				$selectElement.data("placeholder") || "Select an option";

			$selectElement
				.select2({
					placeholder: placeholder,
					allowClear: true,
					width: "auto",
					minimumResultsForSearch: Infinity,
					dropdownCssClass: "custom-dropdown",
					dropdownParent: $selectElement.parent(),
					templateResult: function (data) {
						if (data.loading) return data.text;
						return $("<span>" + data.text + "</span>");
					},
					templateSelection: function (data) {
						if (data.id === "") {
							return placeholder;
						}
						return $("<span>" + data.text + "</span>");
					},
				})
				.on("select2:open", function () {
					var $container = $(this).next(".select2-container");
					var $arrow = $container.find(
						".select2-selection__arrow svg"
					);
					if ($arrow.hasClass("dropdown-arrow")) {
						$arrow.css("transform", "rotate(180deg)");
					}
					alignSelect2Dropdown($(this));
					adjustDropdownPosition();
				})
				.on("select2:close", function () {
					var $container = $(this).next(".select2-container");
					var $arrow = $container.find(
						".select2-selection__arrow svg"
					);
					if ($arrow.hasClass("dropdown-arrow")) {
						$arrow.css("transform", "rotate(0deg)");
					}
					updateSelect2Arrow($container);
				})
				.on("select2:select", function () {
					updateSelect2Arrow($(this).next(".select2-container"));
				})
				.on("select2:unselect", function () {
					updateSelect2Arrow($(this).next(".select2-container"));
				});

			updateSelect2Arrow($selectElement.next(".select2-container"));
		}

		$(".oa-child-keywords").each(function () {
			initializeSelect2($(this));
		});

		$(document).on("click", ".close-icon", function () {
			var $selectElement = $(this)
				.closest(".select2-container")
				.prev("select");
			$(this).hide();
			$selectElement.val(null).trigger("change");

			updateUrlParameters();
		});

		$(".oa-child-keywords").on("change", function () {
			updateUrlParameters();
		});

		function updateUrlParameters() {
			var selectedFilters = [];
			var nonce = $('input[name="keyword_filters_nonce"]').val();

			$(".oa-child-keywords").each(function () {
				var val = $(this).val();
				if (val) {
					selectedFilters.push(val);
				}
			});

			var urlParams = new URLSearchParams();
			selectedFilters.forEach(function (filter) {
				urlParams.append("keywordfilters[]", filter);
			});
			urlParams.append("keyword_filters_nonce", nonce);

			var baseUrl = window.location.href.split("?")[0].split("/page/")[0];
			window.location.href = baseUrl + "?" + urlParams.toString();
		}

		// Set selected keywords from data attribute
		selectedKeywords.forEach(function (keyword) {
			$('select.oa-child-keywords option[value="' + keyword + '"]').prop(
				"selected",
				true
			);
		});

		$(".oa-child-keywords").each(function () {
			initializeSelect2($(this));
		});

		$(".select2-container").each(function () {
			updateSelect2Arrow($(this));
		});
	}

	// Function to adjust dropdown position
	function adjustDropdownPosition() {
		const scrollLeft = $(".oa-keyword-filters-new").scrollLeft();
		const select2Container = $(".select2-dropdown");
		select2Container.css("transform", `translateX(-${scrollLeft}px)`);
	}
	$(".oa-keyword-filters-new").on("scroll", adjustDropdownPosition);
});

// code for search-form.php
document.addEventListener("DOMContentLoaded", function () {
	var clearSearchButton = document.querySelector(
		".custom-input .clear-search"
	);
	var searchField = document.querySelector(".custom-input .search-field");

	if (clearSearchButton && searchField) {
		clearSearchButton.addEventListener("click", function () {
			searchField.value = ""; // Clear the search input
			var urlParams = new URLSearchParams(window.location.search);
			urlParams.delete("s"); // Remove the search parameter

			// Reload the page without the search parameter
			window.location.search = urlParams.toString();
		});
	}
});