var leform_sending = false;
var leform_context_menu_object = null;
var leform_form_pages = new Array();
var leform_form_page_active = null;
var leform_form_elements = new Array();
var leform_form_last_id = 0;
var leform_integration_last_id = 0;
var leform_payment_gateway_last_id = 0;
var leform_form_changed = false;
var leform_css_tools = [{}];
var leform_font_weights = {
	'100': 'Thin',
	'200': 'Extra-light',
	'300': 'Light',
	'400': 'Normal',
	'500': 'Medium',
	'600': 'Demi-bold',
	'700': 'Bold',
	'800': 'Heavy',
	'900': 'Black'
};
var leform_preview_loading = false;
const daterangepicker_language = {
	de: {
		monthNames: ['Januar', 'Februar', 'März', 'April', 'Mai', 'Juni', 'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'],
		monthShortNames: ['Jan', 'Feb', 'Mär', 'Apr', 'Mai', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dez'],
		weekDaysMin: ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'],
		weekDaysShort: ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'],
		weekDays: ['Sonntag', 'Montag', 'Dienstag', 'Mittwoch', 'Donnerstag', 'Freitag', 'Samstag'],
		am: 'AM',
		pm: 'PM',
		ok: 'OK',
		cancelLabel: 'Abbrechen',
		applyLabel: 'Anwenden',
	}
}
function isElementHidden(element) {
    const rect = element.getBoundingClientRect();
    const viewableDistance = 20;
    const minTop = window.innerHeight - viewableDistance;
    const minBottom = -(viewableDistance);
    const hidden = (rect.top > minTop || rect.bottom < minBottom);
    return hidden;
}

function runWhenImagesLoaded(callback) {
    const imgs = document.querySelectorAll(".leform-element img");
    const len = imgs.length;
    let counter = 0;

    if (imgs.length === 0) {
        callback();
    }

    [...imgs].forEach(function (img) {
        if (img.complete) {
          incrementCounter();
        } else {
          img.addEventListener('load', incrementCounter, { once: true });
        }
    });

    function incrementCounter() {
        counter++;
        if (counter === len) {
            callback();
        }
    }
}

/* Dialog Popup - begin */
var leform_dialog_buttons_disable = false;
function leform_dialog_open(_settings) {
	var settings = {
		width: 480,
		height: 210,
		title: leform_esc_html__('Confirm action'),
		close_enable: true,
		ok_enable: true,
		cancel_enable: true,
		ok_label: leform_esc_html__('Yes'),
		cancel_label: leform_esc_html__('Cancel'),
		echo_html: function () { this.html(leform_esc_html__('Do you really want to continue?')); this.show(); },
		ok_function: function () { leform_dialog_close(); },
		cancel_function: function () { leform_dialog_close(); },
		html: function (_html) { jQuery("#leform-dialog .leform-dialog-content-html").html(_html); },
		show: function () { jQuery("#leform-dialog .leform-dialog-loading").fadeOut(300); }
	}
	var objects = [settings, _settings],
		settings = objects.reduce(function (r, o) {
			Object.keys(o).forEach(function (k) {
				r[k] = o[k];
			});
			return r;
		}, {});

	leform_dialog_buttons_disable = false;
	jQuery("#leform-dialog .leform-dialog-loading").show();
	jQuery("#leform-dialog .leform-dialog-title h3 label").html(settings.title);
	if (settings.close_enable) jQuery("#leform-dialog .leform-dialog-title a").show();
	else jQuery("#leform-dialog .leform-dislog-title a").hide();

	settings.echo_html();
	var window_height = Math.min(2 * parseInt((jQuery(window).height() - 100) / 2, 10), settings.height);
	var window_width = Math.min(Math.min(Math.max(2 * parseInt((jQuery(window).width() - 300) / 2, 10), 880), 960), settings.width);
	jQuery("#leform-dialog").height(window_height);
	jQuery("#leform-dialog").width(window_width);
	jQuery("#leform-dialog .leform-dialog-inner").height(window_height);
	jQuery("#leform-dialog .leform-dialog-content").height(window_height - 104);

	jQuery("#leform-dialog .leform-dialog-button").off("click");
	jQuery("#leform-dialog .leform-dialog-button").removeClass("leform-dialog-button-disabled");

	if (settings.ok_enable) {
		jQuery("#leform-dialog .leform-dialog-button-ok").find("label").html(settings.ok_label);
		jQuery("#leform-dialog .leform-dialog-button-ok").on("click", function (e) {
			e.preventDefault();
			if (!leform_dialog_buttons_disable && typeof settings.ok_function == "function") {
				settings.ok_function();
			}
		});
		jQuery("#leform-dialog .leform-dialog-button-ok").show();
	} else jQuery("#leform-dialog .leform-dialog-button-ok").hide();

	if (settings.cancel_enable) {
		jQuery("#leform-dialog .leform-dialog-button-cancel").find("label").html(settings.cancel_label);
		jQuery("#leform-dialog .leform-dialog-button-cancel").on("click", function (e) {
			e.preventDefault();
			if (!leform_dialog_buttons_disable && typeof settings.cancel_function == "function") {
				settings.cancel_function();
			}
		});
		jQuery("#leform-dialog .leform-dialog-button-cancel").show();
	} else jQuery("#leform-dialog .leform-dialog-button-cancel").hide();

	jQuery("#leform-dialog-overlay").fadeIn(300);
	jQuery("#leform-dialog").css({
		'top': '50%',
		'transform': 'translate(-50%, -50%) scale(1)',
		'-webkit-transform': 'translate(-50%, -50%) scale(1)'
	});
}
function leform_dialog_close() {
	jQuery("#leform-dialog-overlay").fadeOut(300);
	jQuery("#leform-dialog").css({
		'transform': 'translate(-50%, -50%) scale(0)',
		'-webkit-transform': 'translate(-50%, -50%) scale(0)'
	});
	setTimeout(function () { jQuery("#leform-dialog").css("top", "-3000px") }, 300);
	return false;
}
/* Dialog Popup - end */

/* Settings - begin */
function leform_settings_save(_button) {
	if (leform_sending) return false;
	leform_sending = true;
	var button_object = _button;
	jQuery(button_object).find("i").attr("class", "fas fa-spinner fa-spin");
	jQuery(button_object).addClass("leform-button-disabled");
	jQuery.ajax({
		type: "POST",
		url: leform_ajax_handler,
		data: jQuery(".leform-settings-form").serialize(),
		success: function (return_data) {
			jQuery(button_object).find("i").attr("class", "fas fa-check");
			jQuery(button_object).removeClass("leform-button-disabled");
			var data;
			try {
				if (typeof return_data == 'object') data = return_data;
				else data = jQuery.parseJSON(return_data);
				if (data.status == "OK") {
					leform_global_message_show('success', data.message);
				} else if (data.status == "ERROR") {
					leform_global_message_show("danger", data.message);
				} else {
					leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
				}
			} catch (error) {
				leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
			}
			leform_sending = false;
		},
		error: function (XMLHttpRequest, textStatus, errorThrown) {
			jQuery(button_object).find("i").attr("class", "fas fa-check");
			jQuery(button_object).removeClass("leform-button-disabled");
			leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
			leform_sending = false;
		}
	});
	return false;
}
/* Settings - end */

/* Form Editor - begin */
function leform_create() {
	var name = jQuery("#leform-create-name").val();
	if (name.length < 1) name = leform_esc_html__("Untitled Form");
	leform_form_options["name"] = name;
	jQuery(".leform-admin-create-overlay").fadeOut(300);
	jQuery(".leform-admin-create").fadeOut(300);
	if (leform_gettingstarted_enable == "on") leform_gettingstarted("create-form", 0);
	return false;
}
function leform_save(_object) {
	if (leform_sending) return false;
	leform_sending = true;
	jQuery(_object).find("i").attr("class", "fas fa-spinner fa-spin");
	var post_pages = new Array();
	jQuery(".leform-pages-bar-item, .leform-pages-bar-item-confirmation").each(function () {
		var page_id = jQuery(this).attr("data-id");
		for (var i = 0; i < leform_form_pages.length; i++) {
			if (leform_form_pages[i] != null && leform_form_pages[i]['id'] == page_id) {
				post_pages.push(leform_encode64(JSON.stringify(leform_form_pages[i])));
				break;
			}
		}
	});
	var post_elements = new Array();
	for (var i = 0; i < leform_form_elements.length; i++) {
		if (jQuery("#leform-element-" + i).length && leform_form_elements[i] != null) post_elements.push(leform_encode64(JSON.stringify(leform_form_elements[i])));
	}
	const urlSearchParams = new URLSearchParams(window.location.search);
	const params = Object.fromEntries(urlSearchParams.entries());
	var post_data = {
		"action": "leform-form-save",
		"form-id": jQuery("#leform-id").val(),
		"form-options": leform_encode64(JSON.stringify(leform_form_options)),
		"form-pages": post_pages,
		"form-elements": post_elements
	};
	if (params.folder) {
		post_data.folder_id = params.folder;
	}
	jQuery.ajax({
		type: "POST",
		url: leform_ajax_handler,
		data: post_data,
		success: function (return_data) {
			try {
				var data;
				if (typeof return_data == 'object') data = return_data;
				else data = jQuery.parseJSON(return_data);
				if (data.status == "OK") {
					leform_form_changed = false;
					jQuery("#leform-id").val(data.form_id);
					var url = window.location.href;
					if (url.indexOf("&id=") < 0) {
						history.pushState(null, null, url + "&id=" + data.form_id);
						if (leform_gettingstarted_enable == "on") leform_gettingstarted("form-saved", 0);
					}
					jQuery(".leform-header-using span").attr("data-id", data.form_id);
					jQuery(".leform-header-using span").fadeIn(300);
					jQuery(".leform-header-preview span").attr("data-id", data.form_id);
					jQuery(".leform-header-preview span").fadeIn(300);
					leform_global_message_show("success", data.message);
				} else if (data.status == "ERROR") {
					leform_global_message_show("danger", data.message);
				} else {
					leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
				}
			} catch (error) {
				console.log(error);
				leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
			}
			jQuery(_object).find("i").attr("class", "far fa-save");
			leform_sending = false;
		},
		error: function (XMLHttpRequest, textStatus, errorThrown) {
			jQuery(_object).find("i").attr("class", "far fa-save");
			leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
			leform_sending = false;
		}
	});
	return false;
}

function leform_preview(_object) {
	if (leform_sending) return false;
	leform_sending = true;
	jQuery(_object).find("i").attr("class", "fas fa-spinner fa-spin");
	var post_pages = new Array();
	jQuery(".leform-pages-bar-item, .leform-pages-bar-item-confirmation").each(function () {
		var page_id = jQuery(this).attr("data-id");
		for (var i = 0; i < leform_form_pages.length; i++) {
			if (leform_form_pages[i] != null && leform_form_pages[i]['id'] == page_id) {
				post_pages.push(leform_encode64(JSON.stringify(leform_form_pages[i])));
				break;
			}
		}
	});
	var post_elements = new Array();
	for (var i = 0; i < leform_form_elements.length; i++) {
		if (jQuery("#leform-element-" + i).length && leform_form_elements[i] != null) post_elements.push(leform_encode64(JSON.stringify(leform_form_elements[i])));
	}
	var post_data = { "action": "leform-form-preview", "form-id": jQuery("#leform-id").val(), "form-options": leform_encode64(JSON.stringify(leform_form_options)), "form-pages": post_pages, "form-elements": post_elements };
	jQuery.ajax({
		type: "POST",
		url: leform_ajax_handler,
		data: post_data,
		success: function (return_data) {
			try {
				var data;
				if (typeof return_data == 'object') data = return_data;
				else data = jQuery.parseJSON(return_data);
				if (data.status == "OK") {
					jQuery("#leform-preview-iframe").attr("data-loading", "true");
					jQuery("#leform-preview .leform-admin-popup-title h3 span").text(data.form_name);
					jQuery("#leform-preview-iframe").attr("src", data.preview_url);
				} else if (data.status == "ERROR") {
					leform_global_message_show("danger", data.message);
					jQuery(_object).find("i").attr("class", "far fa-eye");
					leform_sending = false;
				} else {
					leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
					jQuery(_object).find("i").attr("class", "far fa-eye");
					leform_sending = false;
				}
			} catch (error) {
				console.log(error);
				leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
				jQuery(_object).find("i").attr("class", "far fa-eye");
				leform_sending = false;
			}
		},
		error: function (XMLHttpRequest, textStatus, errorThrown) {
			jQuery(_object).find("i").attr("class", "far fa-eye");
			leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
			leform_sending = false;
		}
	});
	return false;
}

/* Form Editor - end */

/* Element actions - begin */
function _leform_element_delete(_i) {
	if (leform_form_elements[_i] == null) return;
	if (leform_form_elements[_i]['type'] == "columns") {
		for (var i = 0; i < leform_form_elements.length; i++) {
			if (leform_form_elements[i] == null) continue;
			if (leform_form_elements[i]["_parent"] == leform_form_elements[_i]['id']) _leform_element_delete(i);
		}
	}
	leform_form_elements[_i] = null;
}
function leform_element_delete(_object) {
	var message;
	var i = jQuery(_object).attr("id");
	i = i.replace("leform-element-", "");
	if (leform_form_elements[i] == null) return false;
	if (leform_form_elements[i]['type'] == 'columns') message = leform_esc_html__('Please confirm that you want to delete the element and all sub-elements.');
	else message = leform_esc_html__('Please confirm that you want to delete the element.');
	leform_dialog_open({
		echo_html: function () {
			this.html("<div class='leform-dialog-message'>" + message + "</div>");
			this.show();
		},
		ok_label: leform_esc_html__('Delete'),
		ok_function: function (e) {
			_leform_element_delete(i);
			leform_build();
			leform_dialog_close();
		}
	});
	return false;
}
function _leform_element_duplicate(_parent_id, _new_parent_id, _i) {
	if (leform_form_elements[_i] == null) return;

	var clone = Object.assign({}, leform_form_elements[_i]);
	var j = leform_form_elements.push(clone);
	leform_form_last_id++;
	leform_form_elements[j - 1]["id"] = leform_form_last_id;
	leform_form_elements[j - 1]["_parent"] = _new_parent_id;
	if (_parent_id != _new_parent_id) {
		leform_form_elements[j - 1]["_parent-col"] = "0";
		leform_form_elements[j - 1]["_seq"] = leform_form_last_id;
	}
	if (leform_form_elements[_i]['type'] == "columns") {
		for (var i = 0; i < leform_form_elements.length; i++) {
			if (leform_form_elements[i] == null) continue;
			if (leform_form_elements[i]["_parent"] == leform_form_elements[_i]['id']) _leform_element_duplicate(leform_form_elements[j - 1]["id"], leform_form_elements[j - 1]["id"], i);
		}
	}
}
function leform_element_duplicate(_object, _page_num) {
	var message;
	var i = jQuery(_object).attr("id");
	i = i.replace("leform-element-", "");
	if (leform_form_elements[i] == null) return false;
	if (leform_form_elements[i]['type'] == 'columns') {
		message = leform_esc_html__('Please confirm that you want to duplicate the element and all sub-elements.');
	} else {
		message = leform_esc_html__('Please confirm that you want to duplicate the element.');
	}
	leform_dialog_open({
		echo_html: function () {
			this.html("<div class='leform-dialog-message'>" + message + "</div>");
			this.show();
		},
		ok_label: leform_esc_html__('Duplicate'),
		ok_function: function (e) {
			if (leform_is_numeric(_page_num) && _page_num < leform_form_pages.length && leform_form_pages[_page_num] != null) {
				_leform_element_duplicate(leform_form_elements[i]['_parent'], leform_form_pages[_page_num]['id'], i);
			} else {
				_leform_element_duplicate(leform_form_elements[i]['_parent'], leform_form_elements[i]['_parent'], i);
			}
			leform_build();
			leform_dialog_close();
		}
	});
	return false;
}
function _leform_element_move(_parent_id, _i) {
	if (leform_form_elements[_i] == null) return;
	leform_form_elements[_i]["_parent"] = _parent_id;
	leform_form_elements[_i]["_parent-col"] = "0";
	leform_form_elements[_i]["_seq"] = leform_form_last_id;
}
function leform_element_move(_object, _page_num) {
	var message;
	var i = jQuery(_object).attr("id");
	i = i.replace("leform-element-", "");
	if (leform_form_elements[i] == null) return false;
	if (leform_form_elements[i]['type'] == 'columns') {
		message = leform_esc_html__('Please confirm that you want to move the element and all sub-elements to other page.');
	} else {
		message = leform_esc_html__('Please confirm that you want to move the element to other page.');
	}
	leform_dialog_open({
		echo_html: function () {
			this.html("<div class='leform-dialog-message'>" + message + "</div>");
			this.show();
		},
		ok_label: leform_esc_html__('Move'),
		ok_function: function (e) {
			if (leform_is_numeric(_page_num) && _page_num < leform_form_pages.length && leform_form_pages[_page_num] != null) {
				_leform_element_move(leform_form_pages[_page_num]['id'], i);
			}
			leform_build();
			leform_dialog_close();
		}
	});
	return false;
}
var leform_element_properties_active = null;
var leform_element_properties_data_changed = false;

function _leform_properties_prepare(_object) {
	var properties, i, id, input_fields = new Array();
	var html = "", tab_html = "", tooltip_html = "";
	var sections_opened = 0;
	var icon_left, icon_right, options, options2, fonts, selected, temp;
	var type = jQuery(_object).attr("data-type");
	if (typeof type == undefined || type == "") return false;

	if (type == "settings") {
		properties = leform_form_options;
		jQuery("#leform-element-properties").find(".leform-admin-popup-title h3").html("<i class='fas fa-cogs'></i> "
			+ leform_esc_html__("Form Settings"));
	} else if (type == "page" || type == "page-confirmation") {
		id = jQuery(_object).closest("li").attr("data-id");
		properties = null;
		for (var i = 0; i < leform_form_pages.length; i++) {
			if (leform_form_pages[i] != null && leform_form_pages[i]["id"] == id) {
				properties = leform_form_pages[i];
				break;
			}
		}
		jQuery("#leform-element-properties")
			.find(".leform-admin-popup-title h3")
			.html("<i class='far fa-copy'></i> " + leform_esc_html__("Page Settings"));
	} else {
		i = jQuery(_object).attr("id");
		i = i.replace("leform-element-", "");
		properties = leform_form_elements[i];
		jQuery("#leform-element-properties")
			.find(".leform-admin-popup-title h3")
			.html("<i class='fas fa-cog'></i> " + leform_esc_html__("Element Properties") + "<span><i class='" + leform_toolbar_tools[properties["type"]]["icon"] + "'></i> "
				+ leform_escape_html(properties["name"])
				+ "</span>");
	}

	input_fields = leform_input_sort();

	// Prepare editor state - begin
	for (var key in leform_meta[type]) {
		/* console.log(leform_meta[type]); */
		if (leform_meta[type].hasOwnProperty(key)) {
			tooltip_html = "";
			if (leform_meta[type][key].hasOwnProperty('tooltip')) {
				tooltip_html = "<i class='fas fa-question-circle leform-tooltip-anchor'></i><div class='leform-tooltip-content'>" + leform_meta[type][key]['tooltip'] + "</div>";
			}
			switch (leform_meta[type][key]['type']) {
				case 'tab':
					for (var j = 0; j < sections_opened; j++) html += "</div>";
					sections_opened = 0;
					if (tab_html == "") tab_html += "<div id='leform-properties-tabs' class='leform-tabs'>";
					else html += "</div>";
					tab_html += "<a class='leform-tab' href='#leform-tab-" + leform_meta[type][key]['value'] + "'>" + leform_meta[type][key]['label'] + "</a>";
					html += "<div id='leform-tab-" + leform_meta[type][key]['value'] + "' class='leform-tab-content'>";
					break;

				case 'sections':
					var sectionOptions = "";
					for (var section_key in leform_meta[type][key]['sections']) {
						if (leform_meta[type][key]['sections'].hasOwnProperty(section_key)) {
							if (sectionOptions == "") selected = "leform-section-active";
							else selected = "";
							sectionOptions += "<a class='" + selected + "' href='#leform-section-" + leform_escape_html(section_key) + "'><i class='" + leform_meta[type][key]['sections'][section_key]['icon'] + "'></i> " + leform_escape_html(leform_meta[type][key]['sections'][section_key]['label']) + "</a>";
						}
					}
					html += "<h3 id='leform-" + key + "' class='leform-sections'>" + sectionOptions + "</h3>";
					break;

				case 'section-start':
					html += "<div id='leform-section-" + leform_escape_html(leform_meta[type][key]['section']) + "' class='leform-section-content'>";
					sections_opened++;
					break;

				case 'section-end':
					if (sections_opened > 0) {
						html += "</div>";
						sections_opened--;
					}
					break;

				case 'style':
					options = leform_styles_html();
					temp = "<div class='leform-properties-content-9dimes'><div class='leform-styles-select-container'>" + options + "</div><label>" + leform_escape_html(leform_meta[type][key]['caption']['style']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime'><a class='leform-admin-button' href='#' onclick='return leform_stylemanager_open(this);'><i class='fas fa-cog'></i><label>"
						+ leform_esc_html__("Theme Manager", "leform")
						+ "</label></a></div>";
					temp += "<div class='leform-properties-content-dime'><a class='leform-admin-button' href='#' onclick='return leform_styles_save(this);'><i class='fas fa-save'></i><label>" + leform_esc_html__("Save Current Theme", "leform") + "</label></a></div>";
					html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>" + leform_meta[type][key]['label'] + "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'>" + temp + "</div></div>";
					break;

				case 'key-fields':
					options = "";
					options2 = "";
					temp = "";
					if (input_fields.length > 0) {
						for (var j = 0; j < input_fields.length; j++) {
							if (temp != input_fields[j]['page-id']) {
								if (temp != "") {
									options += "</optgroup>";
									options2 += "</optgroup>";
								}
								options += "<optgroup label='" + leform_escape_html(input_fields[j]['page-name']) + "'>";
								options2 += "<optgroup label='" + leform_escape_html(input_fields[j]['page-name']) + "'>";
								temp = input_fields[j]['page-id'];
							}
							options += "<option value='" + input_fields[j]['id'] + "'" + (input_fields[j]['id'] == properties[key + '-primary'] ? " selected='selected'" : "") + ">" + input_fields[j]['id'] + " | " + leform_escape_html(input_fields[j]['name']) + "</option>";
							options2 += "<option value='" + input_fields[j]['id'] + "'" + (input_fields[j]['id'] == properties[key + '-secondary'] ? " selected='selected'" : "") + ">" + input_fields[j]['id'] + " | " + leform_escape_html(input_fields[j]['name']) + "</option>";
						}
						options += "</optgroup>";
						options2 += "</optgroup>";
					}
					temp = "<div class='leform-properties-content-half'><select name='leform-" + key + "-primary' id='leform-" + key + "-primary'><option value=''>"
						+ leform_esc_html__(leform_meta[type][key]['placeholder']['primary'])
						+ "</option>" + options + "</select><label>" + leform_escape_html(leform_meta[type][key]['caption']['primary']) + "</label></div>";
					temp += "<div class='leform-properties-content-half'><select name='leform-" + key + "-secondary' id='leform-" + key + "-secondary'><option value=''>"
						+ leform_esc_html__(leform_meta[type][key]['placeholder']['secondary'])
						+ "</option>" + options2 + "</select><label>" + leform_escape_html(leform_meta[type][key]['caption']['secondary']) + "</label></div>";
					html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>" + leform_meta[type][key]['label'] + "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content' style='display: flex;'>" + temp + "</div></div>";
					break;

				case 'personal-keys':
					options = "";
					if (input_fields.length > 0) {
						for (var j = 0; j < input_fields.length; j++) {
							options += "<input class='leform-properties-tile' type='checkbox' name='leform-" + key + "' id='leform-" + key + "-" + input_fields[j]['id'] + "' value='" + input_fields[j]['id'] + "'" + (properties[key].indexOf(input_fields[j]['id']) >= 0 ? " checked='checked'" : "") + "><label for='leform-" + key + "-" + input_fields[j]['id'] + "'>" + input_fields[j]['id'] + " | " + leform_escape_html(input_fields[j]['name']) + "</label>";
						}
					} else options = "No fields added.";
					html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>" + leform_meta[type][key]['label'] + "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'>" + options + "</div></div>";
					break;

				case 'datetime-args':
					options = "";
					for (var option_key in leform_meta[type][key]['date-format-options']) {
						if (leform_meta[type][key]['date-format-options'].hasOwnProperty(option_key)) {
							selected = "";
							if (option_key == properties[key + "-date-format"]) selected = " selected='selected'";
							options += "<option" + selected + " value='" + leform_escape_html(option_key) + "'>" + leform_escape_html(leform_meta[type][key]['date-format-options'][option_key]) + "</option>";
						}
					}
					temp = "<div class='leform-properties-content-third'><select name='leform-" + key + "-date-format' id='leform-" + key + "-date-format'>" + options + "</select><label>" + leform_escape_html(leform_meta[type][key]['date-format-label']) + "</label></div>";
					options = "";
					for (var option_key in leform_meta[type][key]['time-format-options']) {
						if (leform_meta[type][key]['time-format-options'].hasOwnProperty(option_key)) {
							selected = "";
							if (option_key == properties[key + "-time-format"]) selected = " selected='selected'";
							options += "<option" + selected + " value='" + leform_escape_html(option_key) + "'>" + leform_escape_html(leform_meta[type][key]['time-format-options'][option_key]) + "</option>";
						}
					}
					temp += "<div class='leform-properties-content-third'><select name='leform-" + key + "-time-format' id='leform-" + key + "-time-format'>" + options + "</select><label>" + leform_escape_html(leform_meta[type][key]['time-format-label']) + "</label></div>";
					options = "";
					for (var j = 0; j < (leform_meta[type][key]['locale-options']).length; j++) {
						selected = "";
						if (leform_meta[type][key]['locale-options'][j] == properties[key + "-locale"]) selected = " selected='selected'";
						options += "<option" + selected + " value='" + leform_escape_html(leform_meta[type][key]['locale-options'][j]) + "'>" + leform_escape_html(leform_meta[type][key]['locale-options'][j]) + "</option>";
					}
					temp += "<div class='leform-properties-content-third'><select name='leform-" + key + "-locale' id='leform-" + key + "-locle'>" + options + "</select><label>" + leform_escape_html(leform_meta[type][key]['locale-label']) + "</label></div>";
					html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>" + leform_meta[type][key]['label'] + "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content' style='display: flex;'>" + temp + "</div></div>";
					break;

				case 'color':
					html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>" + leform_meta[type][key]['label'] + "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'><div class='leform-properties-content-color'><input type='text' class='leform-color' data-alpha='true' name='leform-" + key + "' id='leform-" + key + "' value='" + leform_escape_html(properties[key]) + "' placeholder='...' /></div></div></div>";
					break;


				case 'two-colors':
					temp = "";
					temp += "<div class='leform-properties-content-dime'><input type='text' class='leform-color' data-alpha='true' name='leform-" + key + "-color1' id='leform-" + key + "-color1' value='" + leform_escape_html(properties[key + '-color1']) + "' placeholder='...' /><label>" + leform_escape_html(leform_meta[type][key]['caption']['color1']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime'><input type='text' class='leform-color' data-alpha='true' name='leform-" + key + "-color2' id='leform-" + key + "-color2' value='" + leform_escape_html(properties[key + '-color2']) + "' placeholder='...' /><label>" + leform_escape_html(leform_meta[type][key]['caption']['color2']) + "</label></div>";
					temp += "<div class='leform-properties-content-9dimes'></div>";
					html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>" + leform_meta[type][key]['label'] + "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'>" + temp + "</div></div>";
					break;

				case 'three-colors':
					temp = "";
					temp += "<div class='leform-properties-content-dime'><input type='text' class='leform-color' data-alpha='true' name='leform-" + key + "-color1' id='leform-" + key + "-color1' value='" + leform_escape_html(properties[key + '-color1']) + "' placeholder='...' /><label>" + leform_escape_html(leform_meta[type][key]['caption']['color1']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime'><input type='text' class='leform-color' data-alpha='true' name='leform-" + key + "-color2' id='leform-" + key + "-color2' value='" + leform_escape_html(properties[key + '-color2']) + "' placeholder='...' /><label>" + leform_escape_html(leform_meta[type][key]['caption']['color2']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime'><input type='text' class='leform-color' data-alpha='true' name='leform-" + key + "-color3' id='leform-" + key + "-color3' value='" + leform_escape_html(properties[key + '-color3']) + "' placeholder='...' /><label>" + leform_escape_html(leform_meta[type][key]['caption']['color3']) + "</label></div>";
					temp += "<div class='leform-properties-content-9dimes'></div>";
					html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>" + leform_meta[type][key]['label'] + "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'>" + temp + "</div></div>";
					break;

				case 'four-colors':
					temp = "";
					temp += "<div class='leform-properties-content-dime'><input type='text' class='leform-color' data-alpha='false' name='leform-" + key + "-color1' id='leform-" + key + "-color1' value='" + leform_escape_html(properties[key + '-color1']) + "' placeholder='...' /><label>" + leform_escape_html(leform_meta[type][key]['caption']['color1']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime'><input type='text' class='leform-color' data-alpha='false' name='leform-" + key + "-color2' id='leform-" + key + "-color2' value='" + leform_escape_html(properties[key + '-color2']) + "' placeholder='...' /><label>" + leform_escape_html(leform_meta[type][key]['caption']['color2']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime'><input type='text' class='leform-color' data-alpha='false' name='leform-" + key + "-color3' id='leform-" + key + "-color3' value='" + leform_escape_html(properties[key + '-color3']) + "' placeholder='...' /><label>" + leform_escape_html(leform_meta[type][key]['caption']['color3']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime'><input type='text' class='leform-color' data-alpha='false' name='leform-" + key + "-color4' id='leform-" + key + "-color4' value='" + leform_escape_html(properties[key + '-color4']) + "' placeholder='...' /><label>" + leform_escape_html(leform_meta[type][key]['caption']['color4']) + "</label></div>";
					temp += "<div class='leform-properties-content-9dimes'></div>";
					html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>" + leform_meta[type][key]['label'] + "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'>" + temp + "</div></div>";
					break;

				case 'five-colors':
					temp = "";
					temp += "<div class='leform-properties-content-dime'><input type='text' class='leform-color' data-alpha='false' name='leform-" + key + "-color1' id='leform-" + key + "-color1' value='" + leform_escape_html(properties[key + '-color1']) + "' placeholder='...' /><label>" + leform_escape_html(leform_meta[type][key]['caption']['color1']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime'><input type='text' class='leform-color' data-alpha='false' name='leform-" + key + "-color2' id='leform-" + key + "-color2' value='" + leform_escape_html(properties[key + '-color2']) + "' placeholder='...' /><label>" + leform_escape_html(leform_meta[type][key]['caption']['color2']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime'><input type='text' class='leform-color' data-alpha='false' name='leform-" + key + "-color3' id='leform-" + key + "-color3' value='" + leform_escape_html(properties[key + '-color3']) + "' placeholder='...' /><label>" + leform_escape_html(leform_meta[type][key]['caption']['color3']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime'><input type='text' class='leform-color' data-alpha='false' name='leform-" + key + "-color4' id='leform-" + key + "-color4' value='" + leform_escape_html(properties[key + '-color4']) + "' placeholder='...' /><label>" + leform_escape_html(leform_meta[type][key]['caption']['color4']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime'><input type='text' class='leform-color' data-alpha='false' name='leform-" + key + "-color5' id='leform-" + key + "-color5' value='" + leform_escape_html(properties[key + '-color5']) + "' placeholder='...' /><label>" + leform_escape_html(leform_meta[type][key]['caption']['color5']) + "</label></div>";
					temp += "<div class='leform-properties-content-9dimes'></div>";
					html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>" + leform_meta[type][key]['label'] + "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'>" + temp + "</div></div>";
					break;

				case 'three-numbers':
					temp = "";
					temp += "<div class='leform-properties-content-dime'><div class='leform-number'><input type='text' name='leform-" + key + "-value1' id='leform-" + key + "-value1' value='" + leform_escape_html(properties[key + '-value1']) + "' placeholder='...' /></div><label>" + leform_escape_html(leform_meta[type][key]['caption']['value1']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime'><div class='leform-number'><input type='text' name='leform-" + key + "-value2' id='leform-" + key + "-value2' value='" + leform_escape_html(properties[key + '-value2']) + "' placeholder='...' /></div><label>" + leform_escape_html(leform_meta[type][key]['caption']['value2']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime'><div class='leform-number'><input type='text' name='leform-" + key + "-value3' id='leform-" + key + "-value3' value='" + leform_escape_html(properties[key + '-value3']) + "' placeholder='...' /></div><label>" + leform_escape_html(leform_meta[type][key]['caption']['value3']) + "</label></div>";
					temp += "<div class='leform-properties-content-9dimes'></div>";
					html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>" + leform_meta[type][key]['label'] + "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'>" + temp + "</div></div>";
					break;

				case 'four-numbers':
					temp = "";
					temp += "<div class='leform-properties-content-dime'><div class='leform-number'><input type='text' name='leform-" + key + "-value1' id='leform-" + key + "-value1' value='" + leform_escape_html(properties[key + '-value1']) + "' placeholder='...' /></div><label>" + leform_escape_html(leform_meta[type][key]['caption']['value1']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime'><div class='leform-number'><input type='text' name='leform-" + key + "-value2' id='leform-" + key + "-value2' value='" + leform_escape_html(properties[key + '-value2']) + "' placeholder='...' /></div><label>" + leform_escape_html(leform_meta[type][key]['caption']['value2']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime'><div class='leform-number'><input type='text' name='leform-" + key + "-value3' id='leform-" + key + "-value3' value='" + leform_escape_html(properties[key + '-value3']) + "' placeholder='...' /></div><label>" + leform_escape_html(leform_meta[type][key]['caption']['value3']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime'><div class='leform-number'><input type='text' name='leform-" + key + "-value4' id='leform-" + key + "-value4' value='" + leform_escape_html(properties[key + '-value4']) + "' placeholder='...' /></div><label>" + leform_escape_html(leform_meta[type][key]['caption']['value4']) + "</label></div>";
					temp += "<div class='leform-properties-content-9dimes'></div>";
					html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>" + leform_meta[type][key]['label'] + "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'>" + temp + "</div></div>";
					break;

				case 'number-string-number':
					temp = "";
					temp += "<div class='leform-properties-content-dime'><div class='leform-number'><input type='text' name='leform-" + key + "-value1' id='leform-" + key + "-value1' value='" + leform_escape_html(properties[key + '-value1']) + "' placeholder='...' /></div><label>" + leform_escape_html(leform_meta[type][key]['caption']['value1']) + "</label></div>";
					temp += "<div class='leform-properties-content-two-third'><input type='text' name='leform-" + key + "-value2' id='leform-" + key + "-value2' value='" + leform_escape_html(properties[key + '-value2']) + "' placeholder='...' /><label>" + leform_escape_html(leform_meta[type][key]['caption']['value2']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime'><div class='leform-number'><input type='text' name='leform-" + key + "-value3' id='leform-" + key + "-value3' value='" + leform_escape_html(properties[key + '-value3']) + "' placeholder='...' /></div><label>" + leform_escape_html(leform_meta[type][key]['caption']['value3']) + "</label></div>";
					temp += "<div class='leform-properties-content-9dimes'></div>";
					html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>" + leform_meta[type][key]['label'] + "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'>" + temp + "</div></div>";
					break;

				case 'block-width':
					temp = "";
					temp += "<div class='leform-properties-content-dime'><input type='text' class='leform-ta-right' name='leform-" + key + "-value' id='leform-" + key + "-value' value='" + leform_escape_html(properties[key + '-value']) + "' placeholder='Ex. 960' /><label>" + leform_escape_html(leform_meta[type][key]['caption']['value']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime'><div class='leform-bar-selector'><input type='radio' value='px' name='leform-" + key + "-unit' id='leform-" + key + "-unit-px'" + (properties[key + '-unit'] == "px" ? " checked='checked'" : "") + "><label for='leform-" + key + "-unit-px'>px</label><input type='radio' value='%' name='leform-" + key + "-unit' id='leform-" + key + "-unit-percent'" + (properties[key + '-unit'] == "%" ? " checked='checked'" : "") + "><label for='leform-" + key + "-unit-percent'>%</label></div><label>" + leform_escape_html(leform_meta[type][key]['caption']['unit']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime'><div class='leform-bar-selector'><input type='radio' value='left' name='leform-" + key + "-position' id='leform-" + key + "-position-left'" + (properties[key + '-position'] == "left" ? " checked='checked'" : "") + "><label for='leform-" + key + "-position-left'><i class='fas fa-align-left'></i></label><input type='radio' value='center' name='leform-" + key + "-position' id='leform-" + key + "-position-center'" + (properties[key + '-position'] == "center" ? " checked='checked'" : "") + "><label for='leform-" + key + "-position-center'><i class='fas fa-align-center'></i></label><input type='radio' value='right' name='leform-" + key + "-position' id='leform-" + key + "-position-right'" + (properties[key + '-position'] == "right" ? " checked='checked'" : "") + "><label for='leform-" + key + "-position-right'><i class='fas fa-align-right'></i></label></div><label>" + leform_escape_html(leform_meta[type][key]['caption']['position']) + "</label></div>";
					temp += "<div class='leform-properties-content-two-third'></div>";
					html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>" + leform_meta[type][key]['label'] + "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'><div class='leform-properties-group'>" + temp + "</div></div></div>";
					break;

				case 'imageselect-style':
					temp = "";
					options = "";
					for (var option_key in leform_meta[type][key]['options']) {
						if (leform_meta[type][key]['options'].hasOwnProperty(option_key)) {
							options += "<option" + (option_key == properties[key + "-effect"] ? " selected='selected'" : "") + " value='" + leform_escape_html(option_key) + "'>" + leform_escape_html(leform_meta[type][key]['options'][option_key]) + "</option>";
						}
					}
					temp += "<div class='leform-properties-content-two-third'><select name='leform-" + key + "-effect' id='leform-" + key + "-effect'>" + options + "</select><label>" + leform_escape_html(leform_meta[type][key]['caption']['effect']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime'><div class='leform-bar-selector'><input type='radio' value='left' name='leform-" + key + "-align' id='leform-" + key + "-align-left'" + (properties[key + '-align'] == "left" ? " checked='checked'" : "") + "><label for='leform-" + key + "-align-left'><i class='fas fa-align-left'></i></label><input type='radio' value='center' name='leform-" + key + "-align' id='leform-" + key + "-align-center'" + (properties[key + '-align'] == "center" ? " checked='checked'" : "") + "><label for='leform-" + key + "-align-center'><i class='fas fa-align-center'></i></label><input type='radio' value='right' name='leform-" + key + "-align' id='leform-" + key + "-align-right'" + (properties[key + '-align'] == "right" ? " checked='checked'" : "") + "><label for='leform-" + key + "-align-right'><i class='fas fa-align-right'></i></label></div><label>" + leform_escape_html(leform_meta[type][key]['caption']['align']) + "</label></div>";
					temp += "<div class='leform-properties-content-two-third'></div>";
					html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>" + leform_meta[type][key]['label'] + "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'>" + temp + "</div></div>";
					break;

				case 'local-imageselect-style':
					temp = "";
					temp += "<div class='leform-properties-content-dime leform-input-units leform-input-px'><input type='text' class='leform-ta-right' name='leform-" + key + "-width' id='leform-" + key + "-width' value='" + leform_escape_html(properties[key + '-width']) + "' placeholder='' /><label>" + leform_escape_html(leform_meta[type][key]['caption']['width']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime leform-input-units leform-input-px'><input type='text' class='leform-ta-right' name='leform-" + key + "-height' id='leform-" + key + "-height' value='" + leform_escape_html(properties[key + '-height']) + "' placeholder='' /><label>" + leform_escape_html(leform_meta[type][key]['caption']['height']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime'><div class='leform-bar-selector'><input type='radio' value='auto' name='leform-" + key + "-size' id='leform-" + key + "-size-auto'" + (properties[key + '-size'] == "auto" ? " checked='checked'" : "") + "><label for='leform-" + key + "-size-auto'>"
						+ leform_esc_html__("Auto")
						+ "</label><input type='radio' value='contain' name='leform-" + key + "-size' id='leform-" + key + "-size-contain'" + (properties[key + '-size'] == "contain" ? " checked='checked'" : "") + "><label for='leform-" + key + "-size-contain'><i class='fas fa-compress-arrows-alt'></i></label><input type='radio' value='cover' name='leform-" + key + "-size' id='leform-" + key + "-size-cover'" + (properties[key + '-size'] == "cover" ? " checked='checked'" : "") + "><label for='leform-" + key + "-size-cover'><i class='fas fa-expand-arrows-alt'></i></label></div><label>" + leform_escape_html(leform_meta[type][key]['caption']['size']) + "</label></div>";
					temp += "<div class='leform-properties-content-two-third'></div>";
					html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>" + leform_meta[type][key]['label'] + "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'>" + temp + "</div></div>";
					break;

				case 'imageselect-mode':
				case 'tile-mode':
					temp = "";
					temp += "<div class='leform-properties-content-dime'><div class='leform-bar-selector'><input type='radio' value='radio' name='leform-" + key + "' id='leform-" + key + "-radio'" + (properties[key] == "radio" ? " checked='checked'" : "") + " onchange='leform_properties_imageselect_mode_set(this);'><label for='leform-" + key + "-radio'>"
						+ leform_esc_html__("Radio button")
						+ "</label><input type='radio' value='checkbox' name='leform-" + key + "' id='leform-" + key + "-checkbox'" + (properties[key] == "checkbox" ? " checked='checked'" : "") + " onchange='leform_properties_imageselect_mode_set(this);'><label for='leform-" + key + "-checkbox'>"
						+ leform_esc_html__("Checkbox")
						+ "</label></div></div>";
					temp += "<div class='leform-properties-content-9dimes'></div>";
					html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>" + leform_meta[type][key]['label'] + "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'>" + temp + "</div></div>";
					break;

				case 'text-style':
					temp = "";
					options = "<option value=''>" + leform_esc_html__("Default") + "</option>";
					options += "<optgroup label='" + leform_esc_html__("Standard Fonts") + "'>";
					for (var j = 0; j < leform_localfonts.length; j++) {
						options += "<option" + (leform_localfonts[j] == properties[key + '-family'] ? " selected='selected'" : "") + " value='" + leform_escape_html(leform_localfonts[j]) + "'>" + leform_escape_html(leform_localfonts[j]) + "</option>";
					}
					options += "</optgroup>";
					if (leform_customfonts.length > 0) {
						options += "<optgroup label='" + leform_esc_html__("Custom Fonts") + "'>";
						for (var j = 0; j < leform_customfonts.length; j++) {
							options += "<option" + (leform_customfonts[j] == properties[key + '-family'] ? " selected='selected'" : "") + " value='" + leform_escape_html(leform_customfonts[j]) + "'>" + leform_escape_html(leform_customfonts[j]) + "</option>";
						}
						options += "</optgroup>";
					}
					options += "<optgroup label='" + leform_esc_html__("Google Fonts") + "'>";
					for (var j = 0; j < leform_webfonts.length; j++) {
						options += "<option" + (leform_webfonts[j] == properties[key + '-family'] ? " selected='selected'" : "") + " value='" + leform_escape_html(leform_webfonts[j]) + "'>" + leform_escape_html(leform_webfonts[j]) + "</option>";
					}
					options += "</optgroup>";
					temp += "<div class='leform-properties-content-two-third'><select name='leform-" + key + "-family' id='leform-" + key + "-family'>" + options + "</select><label>" + leform_escape_html(leform_meta[type][key]['caption']['family']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime leform-input-units leform-input-px'><input type='text' class='leform-ta-right' name='leform-" + key + "-size' id='leform-" + key + "-size' value='" + leform_escape_html(properties[key + '-size']) + "' placeholder='Ex. 15' /><label>" + leform_escape_html(leform_meta[type][key]['caption']['size']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime'><div class='leform-bar-selector'><input type='radio' value='left' name='leform-" + key + "-align' id='leform-" + key + "-align-left'" + (properties[key + '-align'] == "left" ? " checked='checked'" : "") + "><label for='leform-" + key + "-align-left'><i class='fas fa-align-left'></i></label><input type='radio' value='center' name='leform-" + key + "-align' id='leform-" + key + "-align-center'" + (properties[key + '-align'] == "center" ? " checked='checked'" : "") + "><label for='leform-" + key + "-align-center'><i class='fas fa-align-center'></i></label><input type='radio' value='right' name='leform-" + key + "-align' id='leform-" + key + "-align-right'" + (properties[key + '-align'] == "right" ? " checked='checked'" : "") + "><label for='leform-" + key + "-align-right'><i class='fas fa-align-right'></i></label></div><label>" + leform_escape_html(leform_meta[type][key]['caption']['align']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime'><div class='leform-bar-selector'><input type='checkbox' value='off' name='leform-" + key + "-bold' id='leform-" + key + "-bold'" + (properties[key + '-bold'] == "on" ? " checked='checked'" : "") + "><label for='leform-" + key + "-bold'><i class='fas fa-bold'></i></label><input type='checkbox' value='off' name='leform-" + key + "-italic' id='leform-" + key + "-italic'" + (properties[key + '-italic'] == "on" ? " checked='checked'" : "") + "><label for='leform-" + key + "-italic'><i class='fas fa-italic'></i></label><input type='checkbox' value='off' name='leform-" + key + "-underline' id='leform-" + key + "-underline'" + (properties[key + '-underline'] == "on" ? " checked='checked'" : "") + "><label for='leform-" + key + "-underline'><i class='fas fa-underline'></i></label></div><label>" + leform_escape_html(leform_meta[type][key]['caption']['style']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime'><input type='text' class='leform-color' data-alpha='true' name='leform-" + key + "-color' id='leform-" + key + "-color' value='" + leform_escape_html(properties[key + '-color']) + "' placeholder='Color' /><label>" + leform_escape_html(leform_meta[type][key]['caption']['color']) + "</label></div>";
					html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>" + leform_meta[type][key]['label'] + "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'>" + temp + "</div></div>";
					break;

				case 'background-style':
					temp = "";
					temp += "<div class='leform-properties-line'>";
					temp += "<div class='leform-properties-content-two-third leform-image-url'><input type='text' name='leform-" + key + "-image' id='leform-" + key + "-image' value='" + leform_escape_html(properties[key + '-image']) + "' /><label>" + leform_escape_html(leform_meta[type][key]['caption']['image']) + "</label><span><i class='far fa-image'></i></span></div>";
					temp += "<div class='leform-properties-content-dime'><div class='leform-bar-selector'><input type='radio' value='auto' name='leform-" + key + "-size' id='leform-" + key + "-size-auto'" + (properties[key + '-size'] == "auto" ? " checked='checked'" : "") + ">" +
						"<label for='leform-" + key + "-size-auto'>"
						+ leform_esc_html__("Auto")
						+ "</label>"
						+ "<input type='radio' value='contain' name='leform-" + key + "-size' id='leform-" + key + "-size-contain'" + (properties[key + '-size'] == "contain" ? " checked='checked'" : "") + ">"
						+ "<label for='leform-" + key + "-size-contain'><i class='fas fa-compress-arrows-alt'></i></label><input type='radio' value='cover' name='leform-" + key + "-size' id='leform-" + key + "-size-cover'" + (properties[key + '-size'] == "cover" ? " checked='checked'" : "") + "><label for='leform-" + key + "-size-cover'><i class='fas fa-expand-arrows-alt'></i></label></div><label>" + leform_escape_html(leform_meta[type][key]['caption']['size']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime'><div class='leform-bar-selector'><input type='radio' value='left' name='leform-" + key + "-horizontal-position' id='leform-" + key + "-horizontal-position-left'" + (properties[key + '-horizontal-position'] == "left" ? " checked='checked'" : "") + "><label for='leform-" + key + "-horizontal-position-left'><i class='fas fa-arrow-left'></i></label><input type='radio' value='center' name='leform-" + key + "-horizontal-position' id='leform-" + key + "-horizontal-position-center'" + (properties[key + '-horizontal-position'] == "center" ? " checked='checked'" : "") + "><label for='leform-" + key + "-horizontal-position-center'><i class='far fa-dot-circle'></i></label><input type='radio' value='right' name='leform-" + key + "-horizontal-position' id='leform-" + key + "-horizontal-position-right'" + (properties[key + '-horizontal-position'] == "right" ? " checked='checked'" : "") + "><label for='leform-" + key + "-horizontal-position-right'><i class='fas fa-arrow-right'></i></label></div><label>" + leform_escape_html(leform_meta[type][key]['caption']['horizontal-position']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime'><div class='leform-bar-selector'><input type='radio' value='top' name='leform-" + key + "-vertical-position' id='leform-" + key + "-vertical-position-top'" + (properties[key + '-vertical-position'] == "top" ? " checked='checked'" : "") + "><label for='leform-" + key + "-vertical-position-top'><i class='fas fa-arrow-up'></i></label><input type='radio' value='middle' name='leform-" + key + "-vertical-position' id='leform-" + key + "-vertical-position-middle'" + (properties[key + '-vertical-position'] == "middle" ? " checked='checked'" : "") + "><label for='leform-" + key + "-vertical-position-middle'><i class='far fa-dot-circle'></i></label><input type='radio' value='bottom' name='leform-" + key + "-vertical-position' id='leform-" + key + "-vertical-position-bottom'" + (properties[key + '-vertical-position'] == "bottom" ? " checked='checked'" : "") + "><label for='leform-" + key + "-vertical-position-bottom'><i class='fas fa-arrow-down'></i></label></div><label>" + leform_escape_html(leform_meta[type][key]['caption']['vertical-position']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime'><div class='leform-bar-selector'><input type='radio' value='repeat' name='leform-" + key + "-repeat' id='leform-" + key + "-repeat-repeat'" + (properties[key + '-repeat'] == "repeat" ? " checked='checked'" : "") + "><label for='leform-" + key + "-repeat-repeat'><i class='fas fa-arrows-alt'></i></label><input type='radio' value='repeat-x' name='leform-" + key + "-repeat' id='leform-" + key + "-repeat-repeat-x'" + (properties[key + '-repeat'] == "repeat-x" ? " checked='checked'" : "") + "><label for='leform-" + key + "-repeat-repeat-x'><i class='fas fa-arrows-alt-h'></i></label><input type='radio' value='repeat-y' name='leform-" + key + "-repeat' id='leform-" + key + "-repeat-repeat-y'" + (properties[key + '-repeat'] == "repeat-y" ? " checked='checked'" : "") + "><label for='leform-" + key + "-repeat-repeat-y'><i class='fas fa-arrows-alt-v'></i></label><input type='radio' value='no-repeat' name='leform-" + key + "-repeat' id='leform-" + key + "-repeat-no-repeat'" + (properties[key + '-repeat'] == "no-repeat" ? " checked='checked'" : "") + "><label for='leform-" + key + "-repeat-no-repeat'>"
						+ leform_esc_html__("No")
						+ "</label></div><label>" + leform_escape_html(leform_meta[type][key]['caption']['repeat']) + "</label></div>";
					temp += "</div>";
					temp += "<div class='leform-properties-line'>";
					temp += "<div class='leform-properties-content-dime'><input type='text' class='leform-color' data-alpha='true' name='leform-" + key + "-color' id='leform-" + key + "-color' value='" + leform_escape_html(properties[key + '-color']) + "' placeholder='...' /><label>" + leform_escape_html(leform_meta[type][key]['caption']['color']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime'><div class='leform-bar-selector'><input type='radio' value='no' name='leform-" + key + "-gradient' id='leform-" + key + "-gradient-no'" + (properties[key + '-gradient'] == "no" ? " checked='checked'" : "") + "><label for='leform-" + key + "-gradient-no' onclick='jQuery(\"#leform-content-" + key + "-color2\").fadeOut(300);'>"
						+ leform_esc_html__("No")
						+ "</label><input type='radio' value='2shades' name='leform-" + key + "-gradient' id='leform-" + key + "-gradient-2shades'" + (properties[key + '-gradient'] == "2shades" ? " checked='checked'" : "") + "><label for='leform-" + key + "-gradient-2shades' onclick='jQuery(\"#leform-content-" + key + "-color2\").fadeOut(300);'>"
						+ leform_esc_html__("2 Shades")
						+ "</label><input type='radio' value='horizontal' name='leform-" + key + "-gradient' id='leform-" + key + "-gradient-horizontal'" + (properties[key + '-gradient'] == "horizontal" ? " checked='checked'" : "") + "><label for='leform-" + key + "-gradient-horizontal' onclick='jQuery(\"#leform-content-" + key + "-color2\").fadeIn(300);'>"
						+ leform_esc_html__("Horizontal")
						+ "</label><input type='radio' value='vertical' name='leform-" + key + "-gradient' id='leform-" + key + "-gradient-vertical'" + (properties[key + '-gradient'] == "vertical" ? " checked='checked'" : "") + "><label for='leform-" + key + "-gradient-vertical' onclick='jQuery(\"#leform-content-" + key + "-color2\").fadeIn(300);'>"
						+ leform_esc_html__("Vertical")
						+ "</label><input type='radio' value='diagonal' name='leform-" + key + "-gradient' id='leform-" + key + "-gradient-diagonal'" + (properties[key + '-gradient'] == "diagonal" ? " checked='checked'" : "") + "><label for='leform-" + key + "-gradient-diagonal' onclick='jQuery(\"#leform-content-" + key + "-color2\").fadeIn(300);'>"
						+ leform_esc_html__("Diagonal")
						+ "</label></div><label>" + leform_escape_html(leform_meta[type][key]['caption']['gradient']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime' id='leform-content-" + key + "-color2'" + (properties[key + '-gradient'] != "horizontal" && properties[key + '-gradient'] != "vertical" && properties[key + '-gradient'] != "diagonal" ? " style='display:none;'" : "") + "><input type='text' class='leform-color' data-alpha='true' name='leform-" + key + "-color2' id='leform-" + key + "-color2' value='" + leform_escape_html(properties[key + '-color2']) + "' placeholder='...' /><label>" + leform_escape_html(leform_meta[type][key]['caption']['color2']) + "</label></div>";
					temp += "<div class='leform-properties-content-9dimes'></div>";
					temp += "</div>";
					html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>" + leform_meta[type][key]['label'] + "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'>" + temp + "</div></div>";
					break;

				case 'border-style':
					temp = "";
					temp += "<div class='leform-properties-content-dime leform-input-units leform-input-px'><input type='text' class='leform-ta-right' name='leform-" + key + "-width' id='leform-" + key + "-width' value='" + leform_escape_html(properties[key + '-width']) + "' placeholder='Ex. 1' /><label>" + leform_escape_html(leform_meta[type][key]['caption']['width']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime'><div class='leform-bar-selector'><input type='radio' value='solid' name='leform-" + key + "-style' id='leform-" + key + "-style-solid'" + (properties[key + '-style'] == "solid" ? " checked='checked'" : "") + "><label for='leform-" + key + "-style-solid'>"
						+ leform_esc_html__("Solid")
						+ "</label><input type='radio' value='dashed' name='leform-" + key + "-style' id='leform-" + key + "-style-dashed'" + (properties[key + '-style'] == "dashed" ? " checked='checked'" : "") + "><label for='leform-" + key + "-style-dashed'>"
						+ leform_esc_html__("Dashed")
						+ "</label><input type='radio' value='dotted' name='leform-" + key + "-style' id='leform-" + key + "-style-dotted'" + (properties[key + '-style'] == "dotted" ? " checked='checked'" : "") + "><label for='leform-" + key + "-style-dotted'>"
						+ leform_esc_html__("Dotted")
						+ "</label></div><label>" + leform_escape_html(leform_meta[type][key]['caption']['style']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime'><div class='leform-bar-selector'><input type='radio' value='0' name='leform-" + key + "-radius' id='leform-" + key + "-radius-0'" + (properties[key + '-radius'] == "0" ? " checked='checked'" : "") + "><label for='leform-" + key + "-radius-0'>0px</label><input type='radio' value='3' name='leform-" + key + "-radius' id='leform-" + key + "-radius-3'" + (properties[key + '-radius'] == "3" ? " checked='checked'" : "") + "><label for='leform-" + key + "-radius-3'>3px</label><input type='radio' value='5' name='leform-" + key + "-radius' id='leform-" + key + "-radius-5'" + (properties[key + '-radius'] == "5" ? " checked='checked'" : "") + "><label for='leform-" + key + "-radius-5'>5px</label><input type='radio' value='10' name='leform-" + key + "-radius' id='leform-" + key + "-radius-10'" + (properties[key + '-radius'] == "10" ? " checked='checked'" : "") + "><label for='leform-" + key + "-radius-10'>10px</label><input type='radio' value='30' name='leform-" + key + "-radius' id='leform-" + key + "-radius-30'" + (properties[key + '-radius'] == "30" ? " checked='checked'" : "") + "><label for='leform-" + key + "-radius-30'>"
						+ leform_esc_html__("Max")
						+ "</label></div><label>" + leform_escape_html(leform_meta[type][key]['caption']['radius']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime'><div class='leform-bar-selector'><input type='checkbox' value='off' name='leform-" + key + "-left' id='leform-" + key + "-left'" + (properties[key + '-left'] == "on" ? " checked='checked'" : "") + "><label for='leform-" + key + "-left'><i class='fas fa-arrow-left'></i></label><input type='checkbox' value='off' name='leform-" + key + "-top' id='leform-" + key + "-top'" + (properties[key + '-top'] == "on" ? " checked='checked'" : "") + "><label for='leform-" + key + "-top'><i class='fas fa-arrow-up'></i></label><input type='checkbox' value='off' name='leform-" + key + "-bottom' id='leform-" + key + "-bottom'" + (properties[key + '-bottom'] == "on" ? " checked='checked'" : "") + "><label for='leform-" + key + "-bottom'><i class='fas fa-arrow-down'></i></label><input type='checkbox' value='off' name='leform-" + key + "-right' id='leform-" + key + "-right'" + (properties[key + '-right'] == "on" ? " checked='checked'" : "") + "><label for='leform-" + key + "-right'><i class='fas fa-arrow-right'></i></label></div><label>" + leform_escape_html(leform_meta[type][key]['caption']['border']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime'><input type='text' class='leform-color' data-alpha='true' name='leform-" + key + "-color' id='leform-" + key + "-color' value='" + leform_escape_html(properties[key + '-color']) + "' placeholder='Color' /><label>" + leform_escape_html(leform_meta[type][key]['caption']['color']) + "</label></div>";
					temp += "<div class='leform-properties-content-two-third'></div>";
					html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>" + leform_meta[type][key]['label'] + "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'><div class='leform-properties-group'>" + temp + "</div></div></div>";
					break;

				case 'global-tile-style':
					temp = "";
					temp += "<div class='leform-properties-content-dime'><div class='leform-bar-selector'><input type='radio' value='tiny' name='leform-" + key + "-size' id='leform-" + key + "-size-tiny'" + (properties[key + '-size'] == "tiny" ? " checked='checked'" : "") + "><label for='leform-" + key + "-size-tiny'>"
						+ leform_esc_html__("Tiny")
						+ "</label><input type='radio' value='small' name='leform-" + key + "-size' id='leform-" + key + "-size-small'" + (properties[key + '-size'] == "small" ? " checked='checked'" : "") + "><label for='leform-" + key + "-size-small'>"
						+ leform_esc_html__("Small")
						+ "</label><input type='radio' value='medium' name='leform-" + key + "-size' id='leform-" + key + "-size-medium'" + (properties[key + '-size'] == "medium" ? " checked='checked'" : "") + "><label for='leform-" + key + "-size-medium'>"
						+ leform_esc_html__("Medium")
						+ "</label><input type='radio' value='large' name='leform-" + key + "-size' id='leform-" + key + "-size-large'" + (properties[key + '-size'] == "large" ? " checked='checked'" : "") + "><label for='leform-" + key + "-size-large'>"
						+ leform_esc_html__("Large")
						+ "</label><input type='radio' value='huge' name='leform-" + key + "-size' id='leform-" + key + "-size-huge'" + (properties[key + '-size'] == "huge" ? " checked='checked'" : "") + "><label for='leform-" + key + "-size-huge'>"
						+ leform_esc_html__("Huge")
						+ "</label></div><label>" + leform_escape_html(leform_meta[type][key]['caption']['size']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime'><div class='leform-bar-selector'><input type='radio' value='default' name='leform-" + key + "-width' id='leform-" + key + "-width-default'" + (properties[key + '-width'] == "default" ? " checked='checked'" : "") + "><label for='leform-" + key + "-width-default'>"
						+ leform_esc_html__("Default")
						+ "</label><input type='radio' value='full' name='leform-" + key + "-width' id='leform-" + key + "-width-full'" + (properties[key + '-width'] == "full" ? " checked='checked'" : "") + "><label for='leform-" + key + "-width-full'>"
						+ leform_esc_html__("Full")
						+ "</label></div><label>" + leform_escape_html(leform_meta[type][key]['caption']['width']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime'><div class='leform-bar-selector'><input type='radio' value='left' name='leform-" + key + "-position' id='leform-" + key + "-position-left'" + (properties[key + '-position'] == "left" ? " checked='checked'" : "") + "><label for='leform-" + key + "-position-left'><i class='fas fa-arrow-left'></i></label><input type='radio' value='right' name='leform-" + key + "-position' id='leform-" + key + "-position-right'" + (properties[key + '-position'] == "right" ? " checked='checked'" : "") + "><label for='leform-" + key + "-position-right'><i class='fas fa-arrow-right'></i></label></div><label>" + leform_escape_html(leform_meta[type][key]['caption']['position']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime'><div class='leform-bar-selector'><input type='radio' value='inline' name='leform-" + key + "-layout' id='leform-" + key + "-layout-inline'" + (properties[key + '-layout'] == "inline" ? " checked='checked'" : "") + "><label for='leform-" + key + "-layout-inline'><i class='fas fa-arrow-right'></i></label><input type='radio' value='1' name='leform-" + key + "-layout' id='leform-" + key + "-layout-1'" + (properties[key + '-layout'] == "1" ? " checked='checked'" : "") + "><label for='leform-" + key + "-layout-1'><i class='fas fa-arrow-down'></i></label><input type='radio' value='2' name='leform-" + key + "-layout' id='leform-" + key + "-layout-2'" + (properties[key + '-layout'] == "2" ? " checked='checked'" : "") + "><label for='leform-" + key + "-layout-2'>2c</label><input type='radio' value='3' name='leform-" + key + "-layout' id='leform-" + key + "-layout-3'" + (properties[key + '-layout'] == "3" ? " checked='checked'" : "") + "><label for='leform-" + key + "-layout-3'>3c</label><input type='radio' value='4' name='leform-" + key + "-layout' id='leform-" + key + "-layout-4'" + (properties[key + '-layout'] == "4" ? " checked='checked'" : "") + "><label for='leform-" + key + "-layout-4'>4c</label><input type='radio' value='6' name='leform-" + key + "-layout' id='leform-" + key + "-layout-6'" + (properties[key + '-layout'] == "6" ? " checked='checked'" : "") + "><label for='leform-" + key + "-layout-6'>6c</label></div><label>" + leform_escape_html(leform_meta[type][key]['caption']['layout']) + "</label></div>";
					temp += "<div class='leform-properties-content-two-third'></div>";
					html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>" + leform_meta[type][key]['label'] + "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'><div class='leform-properties-group'>" + temp + "</div></div></div>";
					break;

				case 'local-tile-style':
					temp = "";
					temp += "<div class='leform-properties-content-dime'><div class='leform-bar-options'><input type='hidden' value='" + leform_escape_html(properties[key + '-size']) + "' name='leform-" + key + "-size' id='leform-" + key + "-size'><span class='" + (properties[key + '-size'] == "tiny" ? 'leform-bar-option-selected' : '') + "' data-value='tiny'>"
						+ leform_esc_html__("Tiny")
						+ "</span><span class='" + (properties[key + '-size'] == "small" ? 'leform-bar-option-selected' : '') + "' data-value='small'>"
						+ leform_esc_html__("Small")
						+ "</span><span class='" + (properties[key + '-size'] == "medium" ? 'leform-bar-option-selected' : '') + "' data-value='medium'>"
						+ leform_esc_html__("Medium")
						+ "</span><span class='" + (properties[key + '-size'] == "large" ? 'leform-bar-option-selected' : '') + "' data-value='large'>"
						+ leform_esc_html__("Large")
						+ "</span><span class='" + (properties[key + '-size'] == "huge" ? 'leform-bar-option-selected' : '') + "' data-value='huge'>"
						+ leform_esc_html__("Huge")
						+ "</span></div><label>" + leform_escape_html(leform_meta[type][key]['caption']['size']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime'><div class='leform-bar-options'><input type='hidden' value='" + leform_escape_html(properties[key + '-width']) + "' name='leform-" + key + "-width' id='leform-" + key + "-width'><span class='" + (properties[key + '-width'] == "default" ? 'leform-bar-option-selected' : '') + "' data-value='default'>"
						+ leform_esc_html__("Default")
						+ "</span><span class='" + (properties[key + '-width'] == "full" ? 'leform-bar-option-selected' : '') + "' data-value='full'>"
						+ leform_esc_html__("Full")
						+ "</span></div><label>" + leform_escape_html(leform_meta[type][key]['caption']['width']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime'><div class='leform-bar-options'><input type='hidden' value='" + leform_escape_html(properties[key + '-position']) + "' name='leform-" + key + "-position' id='leform-" + key + "-position'><span class='" + (properties[key + '-position'] == "left" ? 'leform-bar-option-selected' : '') + "' data-value='left'><i class='fas fa-arrow-left'></i></span><span class='" + (properties[key + '-position'] == "right" ? 'leform-bar-option-selected' : '') + "' data-value='right'><i class='fas fa-arrow-right'></i></span></div><label>" + leform_escape_html(leform_meta[type][key]['caption']['position']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime'><div class='leform-bar-options'><input type='hidden' value='" + leform_escape_html(properties[key + '-layout']) + "' name='leform-" + key + "-layout' id='leform-" + key + "-layout'><span class='" + (properties[key + '-layout'] == "inline" ? 'leform-bar-option-selected' : '') + "' data-value='inline'><i class='fas fa-arrow-right'></i></span><span class='" + (properties[key + '-layout'] == "1" ? 'leform-bar-option-selected' : '') + "' data-value='1'><i class='fas fa-arrow-down'></i></span><span class='" + (properties[key + '-layout'] == "2" ? 'leform-bar-option-selected' : '') + "' data-value='2'>2c</span><span class='" + (properties[key + '-layout'] == "3" ? 'leform-bar-option-selected' : '') + "' data-value='3'>3c</span><span class='" + (properties[key + '-layout'] == "4" ? 'leform-bar-option-selected' : '') + "' data-value='4'>4c</span><span class='" + (properties[key + '-layout'] == "6" ? 'leform-bar-option-selected' : '') + "' data-value='6'>6c</span></div><label>" + leform_escape_html(leform_meta[type][key]['caption']['layout']) + "</label></div>";
					temp += "<div class='leform-properties-content-9dimes'></div>";
					html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>" + leform_meta[type][key]['label'] + "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'><div class='leform-properties-group'>" + temp + "</div></div></div>";
					break;

				case 'global-button-style':
					temp = "";
					temp += "<div class='leform-properties-content-dime'><div class='leform-bar-selector'><input type='radio' value='tiny' name='leform-" + key + "-size' id='leform-" + key + "-size-tiny'" + (properties[key + '-size'] == "tiny" ? " checked='checked'" : "") + "><label for='leform-" + key + "-size-tiny'>"
						+ leform_esc_html__("Tiny")
						+ "</label><input type='radio' value='small' name='leform-" + key + "-size' id='leform-" + key + "-size-small'" + (properties[key + '-size'] == "small" ? " checked='checked'" : "") + "><label for='leform-" + key + "-size-small'>"
						+ leform_esc_html__("Small")
						+ "</label><input type='radio' value='medium' name='leform-" + key + "-size' id='leform-" + key + "-size-medium'" + (properties[key + '-size'] == "medium" ? " checked='checked'" : "") + "><label for='leform-" + key + "-size-medium'>"
						+ leform_esc_html__("Medium")
						+ "</label><input type='radio' value='large' name='leform-" + key + "-size' id='leform-" + key + "-size-large'" + (properties[key + '-size'] == "large" ? " checked='checked'" : "") + "><label for='leform-" + key + "-size-large'>"
						+ leform_esc_html__("Large")
						+ "</label><input type='radio' value='huge' name='leform-" + key + "-size' id='leform-" + key + "-size-huge'" + (properties[key + '-size'] == "huge" ? " checked='checked'" : "") + "><label for='leform-" + key + "-size-huge'>"
						+ leform_esc_html__("Huge")
						+ "</label></div><label>" + leform_escape_html(leform_meta[type][key]['caption']['size']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime'><div class='leform-bar-selector'><input type='radio' value='default' name='leform-" + key + "-width' id='leform-" + key + "-width-default'" + (properties[key + '-width'] == "default" ? " checked='checked'" : "") + "><label for='leform-" + key + "-width-default'>"
						+ leform_esc_html__("Default")
						+ "</label><input type='radio' value='full' name='leform-" + key + "-width' id='leform-" + key + "-width-full'" + (properties[key + '-width'] == "full" ? " checked='checked'" : "") + "><label for='leform-" + key + "-width-full'>"
						+ leform_esc_html__("Full")
						+ "</label></div><label>" + leform_escape_html(leform_meta[type][key]['caption']['width']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime'><div class='leform-bar-selector'><input type='radio' value='left' name='leform-" + key + "-position' id='leform-" + key + "-position-left'" + (properties[key + '-position'] == "left" ? " checked='checked'" : "") + "><label for='leform-" + key + "-position-left'><i class='fas fa-align-left'></i></label><input type='radio' value='center' name='leform-" + key + "-position' id='leform-" + key + "-position-center'" + (properties[key + '-position'] == "center" ? " checked='checked'" : "") + "><label for='leform-" + key + "-position-center'><i class='fas fa-align-center'></i></label><input type='radio' value='right' name='leform-" + key + "-position' id='leform-" + key + "-position-right'" + (properties[key + '-position'] == "right" ? " checked='checked'" : "") + "><label for='leform-" + key + "-position-right'><i class='fas fa-align-right'></i></label></div><label>" + leform_escape_html(leform_meta[type][key]['caption']['position']) + "</label></div>";
					temp += "<div class='leform-properties-content-two-third'></div>";
					html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>" + leform_meta[type][key]['label'] + "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'><div class='leform-properties-group'>" + temp + "</div></div></div>";
					break;

				case 'local-button-style':
					temp = "";
					temp += "<div class='leform-properties-content-dime'><div class='leform-bar-options'><input type='hidden' value='" + leform_escape_html(properties[key + '-size']) + "' name='leform-" + key + "-size' id='leform-" + key + "-size'><span class='" + (properties[key + '-size'] == "tiny" ? 'leform-bar-option-selected' : '') + "' data-value='tiny'>"
						+ leform_esc_html__("Tiny")
						+ "</span><span class='" + (properties[key + '-size'] == "small" ? 'leform-bar-option-selected' : '') + "' data-value='small'>"
						+ leform_esc_html__("Small")
						+ "</span><span class='" + (properties[key + '-size'] == "medium" ? 'leform-bar-option-selected' : '') + "' data-value='medium'>"
						+ leform_esc_html__("Medium")
						+ "</span><span class='" + (properties[key + '-size'] == "large" ? 'leform-bar-option-selected' : '') + "' data-value='large'>"
						+ leform_esc_html__("Large")
						+ "</span><span class='" + (properties[key + '-size'] == "huge" ? 'leform-bar-option-selected' : '') + "' data-value='huge'>"
						+ leform_esc_html__("Huge")
						+ "</span></div><label>" + leform_escape_html(leform_meta[type][key]['caption']['size']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime'><div class='leform-bar-options'><input type='hidden' value='" + leform_escape_html(properties[key + '-width']) + "' name='leform-" + key + "-width' id='leform-" + key + "-width'><span class='" + (properties[key + '-width'] == "default" ? 'leform-bar-option-selected' : '') + "' data-value='default'>"
						+ leform_esc_html__("Default")
						+ "</span><span class='" + (properties[key + '-width'] == "full" ? 'leform-bar-option-selected' : '') + "' data-value='full'>"
						+ leform_esc_html__("Full")
						+ "</span></div><label>" + leform_escape_html(leform_meta[type][key]['caption']['width']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime'><div class='leform-bar-options'><input type='hidden' value='" + leform_escape_html(properties[key + '-position']) + "' name='leform-" + key + "-position' id='leform-" + key + "-position'><span class='" + (properties[key + '-position'] == "left" ? 'leform-bar-option-selected' : '') + "' data-value='left'><i class='fas fa-align-left'></i></span><span class='" + (properties[key + '-position'] == "center" ? 'leform-bar-option-selected' : '') + "' data-value='center'><i class='fas fa-align-center'></i></span><span class='" + (properties[key + '-position'] == "right" ? 'leform-bar-option-selected' : '') + "' data-value='right'><i class='fas fa-align-right'></i></span></div><label>" + leform_escape_html(leform_meta[type][key]['caption']['position']) + "</label></div>";
					temp += "<div class='leform-properties-content-9dimes'></div>";
					html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>" + leform_meta[type][key]['label'] + "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'><div class='leform-properties-group'>" + temp + "</div></div></div>";
					break;

				case 'icon-style':
					temp = "";

					temp += `
                        <div class='leform-properties-content-dime'>
                            <div class='leform-bar-selector'>
                                <input
                                    type='radio'
                                    value='show'
                                    name='leform-${key}-display'
                                    id='leform-${key}-display-true'
                                    ${(properties[key + '-display'] == "show"
							? " checked='checked'"
							: ""
						)}
                                />
                                <label
                                    for='leform-${key}-display-true'
                                    onclick='if (jQuery(this).parent().find(\"input\").is(\":checked\")) jQuery(this).closest(\".leform-properties-content\").find(\".leform-properties-icon-show-only\").fadeIn(200);'
                                >
                                    ${leform_esc_html__("Show")}</i>
                                </label>
                                <input
                                    type='radio'
                                    value='hide'
                                    name='leform-${key}-display'
                                    id='leform-${key}-display-false'
                                    ${(properties[key + '-display'] == "hide"
							? " checked='checked'"
							: ""
						)}
                                />
                                <label
                                    for='leform-${key}-display-false'
                                    onclick='if (jQuery(this).parent().find(\"input\").is(\":checked\")) jQuery(this).closest(\".leform-properties-content\").find(\".leform-properties-icon-show-only\").fadeOut(200);'
                                    style="margin-left: -4px;"
                                >
                                    ${leform_esc_html__("Hide")}
                                </label>
                            </div>
                            <label>
                                ${leform_escape_html(leform_meta[type][key]['caption']['display'])}
                            </label>
                        </div>
                    `;

					temp += "<div class='leform-properties-content-dime leform-properties-icon-show-only" + (properties[key + '-position'] == "outside" ? " style='display:none;'" : "") + "'><div class='leform-bar-selector'><input type='radio' value='inside' name='leform-" + key + "-position' id='leform-" + key + "-position-inside'" + (properties[key + '-position'] == "inside" ? " checked='checked'" : "") + " /><label for='leform-" + key + "-position-inside' onclick='if (jQuery(this).parent().find(\"input\").is(\":checked\")) jQuery(this).closest(\".leform-properties-content\").find(\".leform-properties-icon-inside-only\").fadeIn(200);'>"
						+ leform_esc_html__("Inside")
						+ "</i></label><input type='radio' value='outside' name='leform-" + key + "-position' id='leform-" + key + "-position-outside'" + (properties[key + '-position'] == "outside" ? " checked='checked'" : "") + " /><label for='leform-" + key + "-position-outside' onclick='if (jQuery(this).parent().find(\"input\").is(\":checked\")) jQuery(this).closest(\".leform-properties-content\").find(\".leform-properties-icon-inside-only\").fadeOut(200);'>"
						+ leform_esc_html__("Outside")
						+ "</label></div><label>" + leform_escape_html(leform_meta[type][key]['caption']['position']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime leform-properties-icon-show-only" + (properties[key + '-position'] == "outside" ? " style='display:none;'" : "") + " leform-input-units leform-input-px'><input type='text' class='leform-ta-right' name='leform-" + key + "-size' id='leform-" + key + "-size' value='" + leform_escape_html(properties[key + '-size']) + "' placeholder='Ex. 15' /><label>" + leform_escape_html(leform_meta[type][key]['caption']['size']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime leform-properties-icon-show-only" + (properties[key + '-position'] == "outside" ? " style='display:none;'" : "") + "'><input type='text' class='leform-color' data-alpha='true' name='leform-" + key + "-color' id='leform-" + key + "-color' value='" + leform_escape_html(properties[key + '-color']) + "' placeholder='Color' /><label>" + leform_escape_html(leform_meta[type][key]['caption']['color']) + "</label></div>";

					temp += "<div class='leform-properties-content-dime leform-properties-icon-show-only leform-properties-icon-inside-only'" + (
						(
							properties[key + '-position'] == "outside"
							|| properties[key + '-display'] == "hide"
						) ? " style='display:none;'" : ""
					) + "><input type='text' class='leform-color' data-alpha='true' name='leform-" + key + "-background' id='leform-" + key + "-background' value='" + leform_escape_html(properties[key + '-background']) + "' placeholder='Color' /><label>" + leform_escape_html(leform_meta[type][key]['caption']['background']) + "</label></div>";

					temp += "<div class='leform-properties-content-dime leform-properties-icon-show-only leform-properties-icon-inside-only'" + (
						(
							properties[key + '-position'] == "outside"
							|| properties[key + '-display'] == "hide"
						) ? " style='display:none;'" : ""
					) + "><input type='text' class='leform-color' data-alpha='true' name='leform-" + key + "-border' id='leform-" + key + "-border' value='" + leform_escape_html(properties[key + '-border']) + "' placeholder='Color' /><label>" + leform_escape_html(leform_meta[type][key]['caption']['border']) + "</label></div>";

					temp += "<div class='leform-properties-content-two-third'></div>";
					html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>" + leform_meta[type][key]['label'] + "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'>" + temp + "</div></div>";
					break;

				case 'star-style':
					temp = "";
					temp += "<div class='leform-properties-content-dime'><div class='leform-bar-selector'><input type='radio' value='tiny' name='leform-" + key + "-size' id='leform-" + key + "-size-tiny'" + (properties[key + '-size'] == "tiny" ? " checked='checked'" : "") + "><label for='leform-" + key + "-size-tiny'>"
						+ leform_esc_html__("Tiny")
						+ "</label><input type='radio' value='small' name='leform-" + key + "-size' id='leform-" + key + "-size-small'" + (properties[key + '-size'] == "small" ? " checked='checked'" : "") + "><label for='leform-" + key + "-size-small'>"
						+ leform_esc_html__("Small")
						+ "</label><input type='radio' value='medium' name='leform-" + key + "-size' id='leform-" + key + "-size-medium'" + (properties[key + '-size'] == "medium" ? " checked='checked'" : "") + "><label for='leform-" + key + "-size-medium'>"
						+ leform_esc_html__("Medium")
						+ "</label><input type='radio' value='large' name='leform-" + key + "-size' id='leform-" + key + "-size-large'" + (properties[key + '-size'] == "large" ? " checked='checked'" : "") + "><label for='leform-" + key + "-size-large'>"
						+ leform_esc_html__("Large")
						+ "</label><input type='radio' value='huge' name='leform-" + key + "-size' id='leform-" + key + "-size-huge'" + (properties[key + '-size'] == "huge" ? " checked='checked'" : "") + "><label for='leform-" + key + "-size-huge'>"
						+ leform_esc_html__("Huge")
						+ "</label></div><label>" + leform_escape_html(leform_meta[type][key]['caption']['size']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime'><div class='leform-bar-selector'><input type='radio' value='left' name='leform-" + key + "-position' id='leform-" + key + "-position-left'" + (properties[key + '-position'] == "left" ? " checked='checked'" : "") + "><label for='leform-" + key + "-position-left'><i class='fas fa-align-left'></i></label><input type='radio' value='center' name='leform-" + key + "-position' id='leform-" + key + "-position-center'" + (properties[key + '-position'] == "center" ? " checked='checked'" : "") + "><label for='leform-" + key + "-position-center'><i class='fas fa-align-center'></i></label><input type='radio' value='right' name='leform-" + key + "-position' id='leform-" + key + "-position-right'" + (properties[key + '-position'] == "right" ? " checked='checked'" : "") + "><label for='leform-" + key + "-position-right'><i class='fas fa-align-right'></i></label></div><label>" + leform_escape_html(leform_meta[type][key]['caption']['position']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime'><input type='text' class='leform-color' data-alpha='true' name='leform-" + key + "-color-rated' id='leform-" + key + "-color-rated' value='" + leform_escape_html(properties[key + '-color-rated']) + "' /><label>" + leform_escape_html(leform_meta[type][key]['caption']['color-rated']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime'><input type='text' class='leform-color' data-alpha='true' name='leform-" + key + "-color-unrated' id='leform-" + key + "-color-unrated' value='" + leform_escape_html(properties[key + '-color-unrated']) + "' /><label>" + leform_escape_html(leform_meta[type][key]['caption']['color-unrated']) + "</label></div>";
					temp += "<div class='leform-properties-content-two-third'></div>";
					html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>" + leform_meta[type][key]['label'] + "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'><div class='leform-properties-group'>" + temp + "</div></div></div>";
					break;

				case 'shadow':
					temp = "";
					temp += "<div class='leform-properties-content-dime' id='leform-content-" + key + "-size'><div class='leform-bar-selector'><input type='radio' value='' name='leform-" + key + "-size' id='leform-" + key + "-size-no'" + (properties[key + '-size'] == "" ? " checked='checked'" : "") + "><label for='leform-" + key + "-size-no'>"
						+ leform_esc_html__("No")
						+ "</label><input type='radio' value='tiny' name='leform-" + key + "-size' id='leform-" + key + "-size-tiny'" + (properties[key + '-size'] == "tiny" ? " checked='checked'" : "") + "><label for='leform-" + key + "-size-tiny'><i class='fas fa-stop' style='font-size: 10px;'></i></label><input type='radio' value='small' name='leform-" + key + "-size' id='leform-" + key + "-size-small'" + (properties[key + '-size'] == "small" ? " checked='checked'" : "") + "><label for='leform-" + key + "-size-small'><i class='fas fa-stop' style='font-size: 12px;'></i></label><input type='radio' value='medium' name='leform-" + key + "-size' id='leform-" + key + "-size-medium'" + (properties[key + '-size'] == "medium" ? " checked='checked'" : "") + "><label for='leform-" + key + "-size-medium'><i class='fas fa-stop' style='font-size: 14px;'></i></label><input type='radio' value='large' name='leform-" + key + "-size' id='leform-" + key + "-size-large'" + (properties[key + '-size'] == "large" ? " checked='checked'" : "") + "><label for='leform-" + key + "-size-large'><i class='fas fa-stop' style='font-size: 16px;'></i></label><input type='radio' value='huge' name='leform-" + key + "-size' id='leform-" + key + "-size-huge'" + (properties[key + '-size'] == "huge" ? " checked='checked'" : "") + "><label for='leform-" + key + "-size-huge'><i class='fas fa-stop' style='font-size: 18px;'></i></label></div><label>" + leform_escape_html(leform_meta[type][key]['caption']['size']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime'><div class='leform-bar-selector'><input type='radio' value='regular' name='leform-" + key + "-style' id='leform-" + key + "-style-regular'" + (properties[key + '-style'] == "regular" ? " checked='checked'" : "") + "><label for='leform-" + key + "-style-regular'>"
						+ leform_esc_html__("Regular")
						+ "</label><input type='radio' value='solid' name='leform-" + key + "-style' id='leform-" + key + "-style-solid'" + (properties[key + '-style'] == "solid" ? " checked='checked'" : "") + "><label for='leform-" + key + "-style-solid'>"
						+ leform_esc_html__("Solid")
						+ "</label><input type='radio' value='inset' name='leform-" + key + "-style' id='leform-" + key + "-style-inset'" + (properties[key + '-style'] == "inset" ? " checked='checked'" : "") + "><label for='leform-" + key + "-style-inset'>"
						+ leform_esc_html__("Inset")
						+ "</label></div><label>" + leform_escape_html(leform_meta[type][key]['caption']['style']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime'><input type='text' class='leform-color' data-alpha='true' name='leform-" + key + "-color' id='leform-" + key + "-color' value='" + leform_escape_html(properties[key + '-color']) + "' placeholder='Color' /><label>" + leform_escape_html(leform_meta[type][key]['caption']['color']) + "</label></div>";
					temp += "<div class='leform-properties-content-9dimes'></div>";
					html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>" + leform_meta[type][key]['label'] + "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'><div class='leform-properties-group'>" + temp + "</div></div></div>";
					break;

				case 'padding':
					temp = "";
					temp += "<div class='leform-properties-content-dime leform-input-units leform-input-px'><input type='text' class='leform-ta-right' name='leform-" + key + "-top' id='leform-" + key + "-top' value='" + leform_escape_html(properties[key + '-top']) + "' placeholder='Ex. 10' /><label>" + leform_escape_html(leform_meta[type][key]['caption']['top']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime leform-input-units leform-input-px'><input type='text' class='leform-ta-right' name='leform-" + key + "-right' id='leform-" + key + "-right' value='" + leform_escape_html(properties[key + '-right']) + "' placeholder='Ex. 10' /><label>" + leform_escape_html(leform_meta[type][key]['caption']['right']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime leform-input-units leform-input-px'><input type='text' class='leform-ta-right' name='leform-" + key + "-bottom' id='leform-" + key + "-bottom' value='" + leform_escape_html(properties[key + '-bottom']) + "' placeholder='Ex. 10' /><label>" + leform_escape_html(leform_meta[type][key]['caption']['bottom']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime leform-input-units leform-input-px'><input type='text' class='leform-ta-right' name='leform-" + key + "-left' id='leform-" + key + "-left' value='" + leform_escape_html(properties[key + '-left']) + "' placeholder='Ex. 10' /><label>" + leform_escape_html(leform_meta[type][key]['caption']['left']) + "</label></div>";
					temp += "<div class='leform-properties-content-two-third'></div>";
					html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>" + leform_meta[type][key]['label'] + "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'><div class='leform-properties-group'>" + temp + "</div></div></div>";
					break;

				case 'label-position':
					temp = "";
					temp += "<div class='leform-properties-content-dime' id='leform-content-" + key + "-position'><div class='leform-bar-selector'><input type='radio' value='top' name='leform-" + key + "-position' id='leform-" + key + "-position-top'" + (properties[key + '-position'] == "top" ? " checked='checked'" : "") + "><label for='leform-" + key + "-position-top' onclick='jQuery(\"#leform-content-" + key + "-width\").fadeOut(300);'><i class='fas fa-arrow-up'></i></label><input type='radio' value='left' name='leform-" + key + "-position' id='leform-" + key + "-position-left'" + (properties[key + '-position'] == "left" ? " checked='checked'" : "") + "><label for='leform-" + key + "-position-left' onclick='jQuery(\"#leform-content-" + key + "-width\").fadeIn(300);'><i class='fas fa-arrow-left'></i></label><input type='radio' value='right' name='leform-" + key + "-position' id='leform-" + key + "-position-right'" + (properties[key + '-position'] == "right" ? " checked='checked'" : "") + "><label for='leform-" + key + "-position-right' onclick='jQuery(\"#leform-content-" + key + "-width\").fadeIn(300);'><i class='fas fa-arrow-right'></i></label><input type='radio' value='none' name='leform-" + key + "-position' id='leform-" + key + "-position-none'" + (properties[key + '-position'] == "none" ? " checked='checked'" : "") + "><label for='leform-" + key + "-position-none' onclick='jQuery(\"#leform-content-" + key + "-width\").fadeOut(300);'><i class='far fa-eye-slash'></i></label></div><label>" + leform_escape_html(leform_meta[type][key]['caption']['position']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime leform-properties-content-slider' id='leform-content-" + key + "-width'" + (properties[key + '-position'] != "left" && properties[key + '-position'] != "right" ? " style='display:none;'" : "") + "><div class='leform-slider-container'><input type='hidden' name='leform-" + key + "-width' id='leform-" + key + "-width' value='" + leform_escape_html(properties[key + '-width']) + "' /><div class='leform-slider' data-min='1' data-max='11' data-step='1'><div class='ui-slider-handle'></div></div></div><label>" + leform_escape_html(leform_meta[type][key]['caption']['width']) + "</label></div>";
					temp += "<div class='leform-properties-content-9dimes'></div>";
					html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>" + leform_meta[type][key]['label'] + "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'><div class='leform-properties-group'>" + temp + "</div></div></div>";
					break;

				case 'label-style':
					temp = "";
					temp += "<div class='leform-properties-content-dime' id='leform-content-" + key + "-position'><div class='leform-bar-options leform-label-position-options'><input type='hidden' value='" + leform_escape_html(properties[key + '-position']) + "' name='leform-" + key + "-position' id='leform-" + key + "-position'><span class='" + (properties[key + '-position'] == "top" ? 'leform-bar-option-selected' : '') + "' data-value='top'><i class='fas fa-arrow-up'></i></span><span class='" + (properties[key + '-position'] == "left" ? 'leform-bar-option-selected' : '') + "' data-value='left'><i class='fas fa-arrow-left'></i></span><span class='" + (properties[key + '-position'] == "right" ? 'leform-bar-option-selected' : '') + "' data-value='right'><i class='fas fa-arrow-right'></i></span><span class='" + (properties[key + '-position'] == "none" ? 'leform-bar-option-selected' : '') + "' data-value='none'><i class='far fa-eye-slash'></i></span></div><label>" + leform_escape_html(leform_meta[type][key]['caption']['position']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime leform-properties-content-slider' id='leform-content-" + key + "-width'" + (properties[key + '-position'] != "left" && properties[key + '-position'] != "right" ? " style='display:none;'" : "") + "><div class='leform-slider-container'><input type='hidden' name='leform-" + key + "-width' id='leform-" + key + "-width' value='" + leform_escape_html(properties[key + '-width']) + "' /><div class='leform-slider' data-min='1' data-max='11' data-step='1'><div class='ui-slider-handle'></div></div></div><label>" + leform_escape_html(leform_meta[type][key]['caption']['width']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime'><div class='leform-bar-options'><input type='hidden' value='" + leform_escape_html(properties[key + '-align']) + "' name='leform-" + key + "-align' id='leform-" + key + "-align'><span class='" + (properties[key + '-align'] == "left" ? 'leform-bar-option-selected' : '') + "' data-value='left'><i class='fas fa-align-left'></i></span><span class='" + (properties[key + '-align'] == "center" ? 'leform-bar-option-selected' : '') + "' data-value='center'><i class='fas fa-align-center'></i></span><span class='" + (properties[key + '-align'] == "right" ? 'leform-bar-option-selected' : '') + "' data-value='right'><i class='fas fa-align-right'></i></span></div><label>" + leform_escape_html(leform_meta[type][key]['caption']['align']) + "</label></div>";
					temp += "<div class='leform-properties-content-9dimes'></div>";
					html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>" + leform_meta[type][key]['label'] + "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'><div class='leform-properties-group'>" + temp + "</div></div></div>";
					break;

				case 'input-style':
					temp = "";
					temp += "<div class='leform-properties-content-dime'><div class='leform-bar-options'><input type='hidden' value='" + leform_escape_html(properties[key + '-size']) + "' name='leform-" + key + "-size' id='leform-" + key + "-size'><span class='" + (properties[key + '-size'] == "tiny" ? 'leform-bar-option-selected' : '') + "' data-value='tiny'>"
						+ leform_esc_html__("Tiny")
						+ "</span><span class='" + (properties[key + '-size'] == "small" ? 'leform-bar-option-selected' : '') + "' data-value='small'>"
						+ leform_esc_html__("Small")
						+ "</span><span class='" + (properties[key + '-size'] == "medium" ? 'leform-bar-option-selected' : '') + "' data-value='medium'>"
						+ leform_esc_html__("Medium")
						+ "</span><span class='" + (properties[key + '-size'] == "large" ? 'leform-bar-option-selected' : '') + "' data-value='large'>"
						+ leform_esc_html__("Large")
						+ "</span><span class='" + (properties[key + '-size'] == "huge" ? 'leform-bar-option-selected' : '') + "' data-value='huge'>"
						+ leform_esc_html__("Huge")
						+ "</span></div><label>" + leform_escape_html(leform_meta[type][key]['caption']['size']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime'><div class='leform-bar-options'><input type='hidden' value='" + leform_escape_html(properties[key + '-align']) + "' name='leform-" + key + "-align' id='leform-" + key + "-align'><span class='" + (properties[key + '-align'] == "left" ? 'leform-bar-option-selected' : '') + "' data-value='left'><i class='fas fa-align-left'></i></span><span class='" + (properties[key + '-align'] == "center" ? 'leform-bar-option-selected' : '') + "' data-value='center'><i class='fas fa-align-center'></i></span><span class='" + (properties[key + '-align'] == "right" ? 'leform-bar-option-selected' : '') + "' data-value='right'><i class='fas fa-align-right'></i></span></div><label>" + leform_escape_html(leform_meta[type][key]['caption']['align']) + "</label></div>";
					temp += "<div class='leform-properties-content-9dimes'></div>";
					html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>" + leform_meta[type][key]['label'] + "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'><div class='leform-properties-group'>" + temp + "</div></div></div>";
					break;

				case 'local-multiselect-style':
				case 'textarea-style':
					temp = "";
					temp += "<div class='leform-properties-content-dime'><div class='leform-number leform-input-units leform-input-px'><input type='text' name='leform-" + key + "-height' id='leform-" + key + "-height' value='" + leform_escape_html(properties[key + '-height']) + "' placeholder='' /></div><label>" + leform_escape_html(leform_meta[type][key]['caption']['height']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime'><div class='leform-bar-options'><input type='hidden' value='" + leform_escape_html(properties[key + '-align']) + "' name='leform-" + key + "-align' id='leform-" + key + "-align'><span class='" + (properties[key + '-align'] == "left" ? 'leform-bar-option-selected' : '') + "' data-value='left'><i class='fas fa-align-left'></i></span><span class='" + (properties[key + '-align'] == "center" ? 'leform-bar-option-selected' : '') + "' data-value='center'><i class='fas fa-align-center'></i></span><span class='" + (properties[key + '-align'] == "right" ? 'leform-bar-option-selected' : '') + "' data-value='right'><i class='fas fa-align-right'></i></span></div><label>" + leform_escape_html(leform_meta[type][key]['caption']['align']) + "</label></div>";
					temp += "<div class='leform-properties-content-9dimes'></div>";
					html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>" + leform_meta[type][key]['label'] + "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'><div class='leform-properties-group'>" + temp + "</div></div></div>";
					break;

				case 'checkbox-radio-style':
					temp = "";
					temp += "<div class='leform-properties-content-dime'><div class='leform-bar-selector'><input type='radio' value='left' name='leform-" + key + "-position' id='leform-" + key + "-position-left'" + (properties[key + '-position'] == "left" ? " checked='checked'" : "") + "><label for='leform-" + key + "-position-left'><i class='fas fa-arrow-left'></i></label><input type='radio' value='right' name='leform-" + key + "-position' id='leform-" + key + "-position-right'" + (properties[key + '-position'] == "right" ? " checked='checked'" : "") + "><label for='leform-" + key + "-position-right'><i class='fas fa-arrow-right'></i></label></div><label>" + leform_escape_html(leform_meta[type][key]['caption']['position']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime'><div class='leform-bar-selector'><input type='radio' value='left' name='leform-" + key + "-align' id='leform-" + key + "-align-left'" + (properties[key + '-align'] == "left" ? " checked='checked'" : "") + "><label for='leform-" + key + "-align-left'><i class='fas fa-align-left'></i></label><input type='radio' value='center' name='leform-" + key + "-align' id='leform-" + key + "-align-center'" + (properties[key + '-align'] == "center" ? " checked='checked'" : "") + "><label for='leform-" + key + "-align-center'><i class='fas fa-align-center'></i></label><input type='radio' value='right' name='leform-" + key + "-align' id='leform-" + key + "-align-right'" + (properties[key + '-align'] == "right" ? " checked='checked'" : "") + "><label for='leform-" + key + "-align-right'><i class='fas fa-align-right'></i></label></div><label>" + leform_escape_html(leform_meta[type][key]['caption']['align']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime'><div class='leform-bar-selector'><input type='radio' value='small' name='leform-" + key + "-size' id='leform-" + key + "-size-small'" + (properties[key + '-size'] == "small" ? " checked='checked'" : "") + "><label for='leform-" + key + "-size-small'>"
						+ leform_esc_html__("Small")
						+ "</label><input type='radio' value='medium' name='leform-" + key + "-size' id='leform-" + key + "-size-medium'" + (properties[key + '-size'] == "medium" ? " checked='checked'" : "") + "><label for='leform-" + key + "-size-medium'>"
						+ leform_esc_html__("Medium")
						+ "</label><input type='radio' value='large' name='leform-" + key + "-size' id='leform-" + key + "-size-large'" + (properties[key + '-size'] == "large" ? " checked='checked'" : "") + "><label for='leform-" + key + "-size-large'>"
						+ leform_esc_html__("Large")
						+ "</label><input type='radio' value='huge' name='leform-" + key + "-size' id='leform-" + key + "-size-huge'" + (properties[key + '-size'] == "huge" ? " checked='checked'" : "") + "><label for='leform-" + key + "-size-huge'>"
						+ leform_esc_html__("Huge")
						+ "</label></div><label>" + leform_escape_html(leform_meta[type][key]['caption']['size']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime'><div class='leform-bar-selector'><input type='radio' value='inline' name='leform-" + key + "-layout' id='leform-" + key + "-layout-inline'" + (properties[key + '-layout'] == "inline" ? " checked='checked'" : "") + "><label for='leform-" + key + "-layout-inline'><i class='fas fa-arrow-right'></i></label><input type='radio' value='1' name='leform-" + key + "-layout' id='leform-" + key + "-layout-1'" + (properties[key + '-layout'] == "1" ? " checked='checked'" : "") + "><label for='leform-" + key + "-layout-1'><i class='fas fa-arrow-down'></i></label><input type='radio' value='2' name='leform-" + key + "-layout' id='leform-" + key + "-layout-2'" + (properties[key + '-layout'] == "2" ? " checked='checked'" : "") + "><label for='leform-" + key + "-layout-2'>2c</label><input type='radio' value='3' name='leform-" + key + "-layout' id='leform-" + key + "-layout-3'" + (properties[key + '-layout'] == "3" ? " checked='checked'" : "") + "><label for='leform-" + key + "-layout-3'>3c</label><input type='radio' value='4' name='leform-" + key + "-layout' id='leform-" + key + "-layout-4'" + (properties[key + '-layout'] == "4" ? " checked='checked'" : "") + "><label for='leform-" + key + "-layout-4'>4c</label><input type='radio' value='6' name='leform-" + key + "-layout' id='leform-" + key + "-layout-6'" + (properties[key + '-layout'] == "6" ? " checked='checked'" : "") + "><label for='leform-" + key + "-layout-6'>6c</label></div><label>" + leform_escape_html(leform_meta[type][key]['caption']['layout']) + "</label></div>";
					temp += "<div class='leform-properties-content-9dimes'></div>";
					html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>" + leform_meta[type][key]['label'] + "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'><div class='leform-properties-group'>" + temp + "</div></div></div>";
					break;

				case 'local-checkbox-style':
					temp = "";
					temp += "<div class='leform-properties-content-dime'><div class='leform-bar-options'><input type='hidden' value='" + leform_escape_html(properties[key + '-position']) + "' name='leform-" + key + "-position' id='leform-" + key + "-position'><span class='" + (properties[key + '-position'] == "left" ? 'leform-bar-option-selected' : '') + "' data-value='left'><i class='fas fa-arrow-left'></i></span><span class='" + (properties[key + '-position'] == "right" ? 'leform-bar-option-selected' : '') + "' data-value='right'><i class='fas fa-arrow-right'></i></span></div><label>" + leform_escape_html(leform_meta[type][key]['caption']['position']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime'><div class='leform-bar-options'><input type='hidden' value='" + leform_escape_html(properties[key + '-align']) + "' name='leform-" + key + "-align' id='leform-" + key + "-align'><span class='" + (properties[key + '-align'] == "left" ? 'leform-bar-option-selected' : '') + "' data-value='left'><i class='fas fa-align-left'></i></span><span class='" + (properties[key + '-align'] == "center" ? 'leform-bar-option-selected' : '') + "' data-value='center'><i class='fas fa-align-center'></i></span><span class='" + (properties[key + '-align'] == "right" ? 'leform-bar-option-selected' : '') + "' data-value='right'><i class='fas fa-align-right'></i></span></div><label>" + leform_escape_html(leform_meta[type][key]['caption']['align']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime'><div class='leform-bar-options'><input type='hidden' value='" + leform_escape_html(properties[key + '-layout']) + "' name='leform-" + key + "-layout' id='leform-" + key + "-layout'><span class='" + (properties[key + '-layout'] == "inline" ? 'leform-bar-option-selected' : '') + "' data-value='inline'><i class='fas fa-arrow-right'></i></span><span class='" + (properties[key + '-layout'] == "1" ? 'leform-bar-option-selected' : '') + "' data-value='1'><i class='fas fa-arrow-down'></i></span><span class='" + (properties[key + '-layout'] == "2" ? 'leform-bar-option-selected' : '') + "' data-value='2'>2c</span><span class='" + (properties[key + '-layout'] == "3" ? 'leform-bar-option-selected' : '') + "' data-value='3'>3c</span><span class='" + (properties[key + '-layout'] == "4" ? 'leform-bar-option-selected' : '') + "' data-value='4'>4c</span><span class='" + (properties[key + '-layout'] == "6" ? 'leform-bar-option-selected' : '') + "' data-value='6'>6c</span></div><label>" + leform_escape_html(leform_meta[type][key]['caption']['layout']) + "</label></div>";
					temp += "<div class='leform-properties-content-9dimes'></div>";
					html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>" + leform_meta[type][key]['label'] + "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'><div class='leform-properties-group'>" + temp + "</div></div></div>";
					break;

				case 'checkbox-view':
					options = "";
					for (var j = 0; j < leform_meta[type][key]['options'].length; j++) {
						selected = "";
						if (properties[key] == leform_meta[type][key]['options'][j]) {
							selected = " checked='checked'";
						}
						options += "<div class='leform-properties-preview-option leform-properties-preview-option-" + leform_escape_html(leform_meta[type][key]['options'][j]) + "'><input type='radio' name='leform-" + key + "' id='leform-" + key + "-" + leform_escape_html(leform_meta[type][key]['options'][j]) + "' value='" + leform_escape_html(leform_meta[type][key]['options'][j]) + "'" + selected + " /><label class='far' for='leform-" + key + "-" + leform_escape_html(leform_meta[type][key]['options'][j]) + "'></label><div><input class='leform-checkbox leform-checkbox-large leform-checkbox-" + leform_meta[type][key]['options'][j] + "' type='checkbox' id='leform-demo-" + key + "-" + leform_escape_html(leform_meta[type][key]['options'][j]) + "' checked='checked' /><label for='leform-demo-" + key + "-" + leform_escape_html(leform_meta[type][key]['options'][j]) + "'></label></div></div>";
					}
					html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>" + leform_meta[type][key]['label'] + "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'>" + options + "</div></div>";
					break;

				case 'radio-view':
					options = "";
					for (var j = 0; j < leform_meta[type][key]['options'].length; j++) {
						selected = "";
						if (properties[key] == leform_meta[type][key]['options'][j]) selected = " checked='checked'";
						options += "<div class='leform-properties-preview-option leform-properties-preview-option-" + leform_escape_html(leform_meta[type][key]['options'][j]) + "'><input type='radio' name='leform-" + key + "' id='leform-" + key + "-" + leform_escape_html(leform_meta[type][key]['options'][j]) + "' value='" + leform_escape_html(leform_meta[type][key]['options'][j]) + "'" + selected + " /><label class='far' for='leform-" + key + "-" + leform_escape_html(leform_meta[type][key]['options'][j]) + "'></label><div><input class='leform-radio leform-radio-large leform-radio-" + leform_meta[type][key]['options'][j] + "' type='radio' id='leform-demo-" + key + "-" + leform_escape_html(leform_meta[type][key]['options'][j]) + "' name='leform-demo-" + key + "'" + selected + " /><label for='leform-demo-" + key + "-" + leform_escape_html(leform_meta[type][key]['options'][j]) + "'></label></div></div>";
					}
					html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>" + leform_meta[type][key]['label'] + "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'>" + options + "</div></div>";
					break;

				case 'multiselect-style':
					temp = "";
					temp += "<div class='leform-properties-content-dime'><div class='leform-bar-options'><input type='hidden' value='" + leform_escape_html(properties[key + '-align']) + "' name='leform-" + key + "-align' id='leform-" + key + "-align'><span class='" + (properties[key + '-align'] == "left" ? 'leform-bar-option-selected' : '') + "' data-value='left'><i class='fas fa-align-left'></i></span><span class='" + (properties[key + '-align'] == "center" ? 'leform-bar-option-selected' : '') + "' data-value='center'><i class='fas fa-align-center'></i></span><span class='" + (properties[key + '-align'] == "right" ? 'leform-bar-option-selected' : '') + "' data-value='right'><i class='fas fa-align-right'></i></span></div><label>" + leform_escape_html(leform_meta[type][key]['caption']['align']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime leform-input-units leform-input-px'><input type='text' class='leform-ta-right' name='leform-" + key + "-height' id='leform-" + key + "-height' value='" + leform_escape_html(properties[key + '-height']) + "' placeholder='Ex. 120' /><label>" + leform_escape_html(leform_meta[type][key]['caption']['height']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime'><div class='leform-properties-group'><div class='leform-properties-content-dime'><input type='text' class='leform-color' data-alpha='true' name='leform-" + key + "-hover-background' id='leform-" + key + "-hover-background' value='" + leform_escape_html(properties[key + '-hover-background']) + "' /></div><div class='leform-properties-content-dime'><input type='text' class='leform-color' data-alpha='true' name='leform-" + key + "-hover-color' id='leform-" + key + "-hover-color' value='" + leform_escape_html(properties[key + '-hover-color']) + "' /></div></div><label>" + leform_escape_html(leform_meta[type][key]['caption']['hover-color']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime'><div class='leform-properties-group'><div class='leform-properties-content-dime'><input type='text' class='leform-color' data-alpha='true' name='leform-" + key + "-selected-background' id='leform-" + key + "-selected-background' value='" + leform_escape_html(properties[key + '-selected-background']) + "' /></div><div class='leform-properties-content-dime'><input type='text' class='leform-color' data-alpha='true' name='leform-" + key + "-selected-color' id='leform-" + key + "-selected-color' value='" + leform_escape_html(properties[key + '-selected-color']) + "' /></div></div><label>" + leform_escape_html(leform_meta[type][key]['caption']['selected-color']) + "</label></div>";
					temp += "<div class='leform-properties-content-9dimes'></div>";
					html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>" + leform_meta[type][key]['label'] + "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'><div class='leform-properties-group'>" + temp + "</div></div></div>";
					break;

				case 'description-position':
					temp = "";
					temp += "<div class='leform-properties-content-dime' id='leform-content-" + key + "-position'><div class='leform-bar-selector'><input type='radio' value='bottom' name='leform-" + key + "-position' id='leform-" + key + "-position-bottom'" + (properties[key + '-position'] == "bottom" ? " checked='checked'" : "") + "><label for='leform-" + key + "-position-bottom'><i class='fas fa-arrow-down'></i></label><input type='radio' value='none' name='leform-" + key + "-position' id='leform-" + key + "-position-none'" + (properties[key + '-position'] == "none" ? " checked='checked'" : "") + "><label for='leform-" + key + "-position-none'><i class='far fa-eye-slash'></i></label></div><label>" + leform_escape_html(leform_meta[type][key]['caption']['position']) + "</label></div>";
					temp += "<div class='leform-properties-content-9dimes'></div>";
					html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>" + leform_meta[type][key]['label'] + "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'><div class='leform-properties-group'>" + temp + "</div></div></div>";
					break;

				case 'description-style':
					temp = "";
					temp += "<div class='leform-properties-content-dime' id='leform-content-" + key + "-position'><div class='leform-bar-options'><input type='hidden' value='" + leform_escape_html(properties[key + '-position']) + "' name='leform-" + key + "-position' id='leform-" + key + "-position'><span class='" + (properties[key + '-position'] == "bottom" ? 'leform-bar-option-selected' : '') + "' data-value='bottom'><i class='fas fa-arrow-down'></i></span><span class='" + (properties[key + '-position'] == "none" ? 'leform-bar-option-selected' : '') + "' data-value='none'><i class='far fa-eye-slash'></i></span></div><label>" + leform_escape_html(leform_meta[type][key]['caption']['position']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime'><div class='leform-bar-options'><input type='hidden' value='" + leform_escape_html(properties[key + '-align']) + "' name='leform-" + key + "-align' id='leform-" + key + "-align'><span class='" + (properties[key + '-align'] == "left" ? 'leform-bar-option-selected' : '') + "' data-value='left'><i class='fas fa-align-left'></i></span><span class='" + (properties[key + '-align'] == "center" ? 'leform-bar-option-selected' : '') + "' data-value='center'><i class='fas fa-align-center'></i></span><span class='" + (properties[key + '-align'] == "right" ? 'leform-bar-option-selected' : '') + "' data-value='right'><i class='fas fa-align-right'></i></span></div><label>" + leform_escape_html(leform_meta[type][key]['caption']['align']) + "</label></div>";
					temp += "<div class='leform-properties-content-9dimes'></div>";
					html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>" + leform_meta[type][key]['label'] + "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'><div class='leform-properties-group'>" + temp + "</div></div></div>";
					break;

				case 'units':
					html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>" + leform_meta[type][key]['label'] + "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'><div class='leform-number leform-input-units leform-input-" + leform_meta[type][key]['unit'] + "'><input type='text' name='leform-" + key + "' id='leform-" + key + "' value='" + leform_escape_html(properties[key]) + "' placeholder='' /></div></div></div>";
					break;

				case 'id':
					html += "<div class='leform-properties-noitem'><div class='leform-properties-label'><label>" + leform_meta[type][key]['label'] + "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'><div class='leform-number'><input type='text' name='leform-" + key + "' id='leform-" + key + "' value='" + properties["id"] + "' readonly='readonly' onclick='this.focus();this.select();' /></div></div></div>";
					break;

				case 'text':
					html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>" + leform_meta[type][key]['label'] + "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'><input type='text' name='leform-" + key + "' id='leform-" + key + "' value='" + leform_escape_html(properties[key]) + "' placeholder='' /></div></div>";
					break;

				case 'url':
					html += `<div class='leform-properties-item url-with-props' data-id='${key}'>
						<div class='leform-properties-label'>
							<label>${leform_meta[type][key]['label']}</label>
						</div>
						<div class='leform-properties-tooltip'> ${tooltip_html}</div>
						<div class='leform-properties-content leform-wysiwyg' style="display: flex;">
							<input type='text' class='url-with-props-input' name='leform-${key}' id='leform-${key}' value='${leform_escape_html(properties[key])}' placeholder='' />
							<input type='hidden' class='url-with-props-input-hidden' id='leform-hidden-${key}' value='' placeholder='' />
							<span class="evo-form-props"></span>
						</div>
					</div>`;
					break;

				case 'integer':
					html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>" + leform_meta[type][key]['label'] + "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'><div class='leform-number'><input type='text' name='leform-" + key + "' id='leform-" + key + "' value='" + leform_escape_html(properties[key]) + "' placeholder='' /></div></div></div>";
					break;

				case 'from':
					html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>" + leform_meta[type][key]['label'] + "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'><div class='leform-properties-group'><div class='leform-properties-content-half leform-input-shortcode-selector'><input type='text' name='leform-" + key + "-email' id='leform-" + key + "-email' value='" + leform_escape_html(properties[key + "-email"]) + "' placeholder='Email address...' /><div class='leform-shortcode-selector' onmouseover='leform_shortcode_selector_set(this)';><span><i class='fas fa-code'></i></span></div></div><div class='leform-properties-content-half leform-input-shortcode-selector'><input type='text' name='leform-" + key + "-name' id='leform-" + key + "-name' value='" + leform_escape_html(properties[key + "-name"]) + "' placeholder='Name...' /><div class='leform-shortcode-selector' onmouseover='leform_shortcode_selector_set(this)';><span><i class='fas fa-code'></i></span></div></div></div></div></div>";
					break;

				case 'text-shortcodes':
					html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>" + leform_meta[type][key]['label'] + "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'><div class='leform-properties-group leform-input-shortcode-selector'><input type='text' name='leform-" + key + "' id='leform-" + key + "' value='" + leform_escape_html(properties[key]) + "' placeholder='' /><div class='leform-shortcode-selector' onmouseover='leform_shortcode_selector_set(this)';><span><i class='fas fa-code'></i></span></div></div></div></div>";
					break;

				case 'email-receipment-element-selector':
					let emailElementsOptions = "";
					for (let j = 0; j < leform_form_elements.length; j++) {
						if (leform_form_elements[j] == null) {
							continue;
						}
						if (
							leform_form_elements[j].hasOwnProperty(['type'])
							&& leform_form_elements[j]['type'] == 'email'
						) {
							let label = leform_form_elements[j]['name']
								.replace(new RegExp("}", 'g'), ")");
							label = label.replace(new RegExp("{", 'g'), "(");
							let isSelected = leform_escape_html(properties[key]);
							emailElementsOptions += `
                                <option
                                    value='${leform_form_elements[j]['id']}'
                                    ${isSelected ? 'selected' : ''}
                                >
                                    ${leform_form_elements[j]['id']} | ${leform_escape_html(leform_form_elements[j]['name'])}
                                </option>
                            `;
						}
					}

					html += `
                        <div class='leform-properties-item' data-id='${key}'>
                            <div class='leform-properties-label'>
                                <label>${leform_meta[type][key]['label']}</label>
                            </div>
                            <div class='leform-properties-tooltip'>
                                ${tooltip_html}
                            </div>
                            <div class='leform-properties-content'>
                                <div class='leform-properties-group leform-input-shortcode-selector'>
                                    <select
                                        name='leform-${key}'
                                        id='leform-${key}'
                                    >
                                        ${emailElementsOptions}
                                    </select>
                                </div>
                            </div>
                        </div>
                    `;
					break;

				case 'html':
					html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>" + leform_meta[type][key]['label'] + "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content leform-wysiwyg'><textarea class='leform-tinymce leform-tinymce-pre' name='leform-" + key + "' id='leform-" + key + "'>" + properties[key] + "</textarea></div></div>";
					break;

				case 'email-autorespond-message':
					html += `
                        <div class='leform-properties-item' data-id='${key}'>
                            <div class='leform-properties-label'>
                                <label>
                                    ${leform_meta[type][key]['label']}
                                </label>
                            </div>
                            <div class='leform-properties-tooltip'>
                                ${tooltip_html}
                            </div>
                            <div class='leform-properties-content leform-wysiwyg'>
                                <textarea
                                    name='leform-${key}'
                                    id='leform-${key}'
                                    class='leform-tinymce leform-tinymce-pre'
                                >
                                    ${properties[key]}
                                </textarea>
                            </div>
                        </div>
                    `;
					break;

				case 'repeater-input-fields':
					const inputOptions = [
						["text", "Text"],
						["email", "Email"],
						["password", "Password"],
						["number", "Number"],
						["select", "Select"],
						["date", "Date"],
						["time", "Time"],
						["rangeslider", "Range slider"],
						["star-rating", "Star rating"],
						["link-button", "Link button"],
						["html", "Html"],
					];
					html += `
                        <div class='leform-properties-item' data-id='${key}'>
                            <div class='leform-properties-label'>
                                <label>
                                    ${leform_meta[type][key]['label']}
                                </label>
                            </div>
                            <div class='leform-properties-tooltip'>
                                ${tooltip_html}
                            </div>
                            <div
                                class='leform-properties-content repeater-input-fields'
                                name='leform-${key}'
                            >
                                <button
                                    type="button"
                                    class="add-field-button"
                                >
                                    +
                                </button>

                                ${properties[key]
							.map(renderRepeatableInputFieldRow)
							.join("")
						}
                            </div>
                        </div>
                    `;
					break;

				case 'repeater-input-expressions':
					html += `
                        <div class='leform-properties-item' data-id='${key}'>
                            <div class='leform-properties-label'>
                                <label>${leform_meta[type][key]['label']}</label>
                            </div>
                            <div class='leform-properties-tooltip'>
                                ${tooltip_html}
                            </div>
                            <div
                                class='leform-properties-content repeater-input-math-expressions'
                                name="leform-expressions"
                            >
                                <button
                                    type="button"
                                    class="add-expression-button"
                                >
                                    +
                                </button>

                                ${properties[key]
							.map(renderRepeatableInputExpressionRow)
							.join("")
						}
                            </div>
                        </div>
                    `;
					break;

				case 'repeater-input-footer':
					const elementProperties = getActiveElementProperties();

					let formValues = "";
					for (const element of leform_form_elements) {
						if (element == null) {
							continue;
						}
						if (
							leform_toolbar_tools.hasOwnProperty(element['type'])
							&& leform_toolbar_tools[element['type']]['type'] == 'input'
							&& element["type"] !== "repeater-input"
						) {
							let label = element['name']
								.replace(new RegExp("}", 'g'), ")")
								.replace(new RegExp("{", 'g'), "(");
							formValues += `
                                <li
                                    class="px-3 py-1.5 cursor-pointer hover:bg-gray-200"
                                    data-code="{{${element['id']}|${leform_escape_html(element['name'])}}}"
                                >
                                    ${element['id']} | ${leform_escape_html(element['name'])}
                                </li>
                            `;
						}
					}

					let expressions = "";
					for (const expression of leform_form_options["math-expressions"]) {
						const label = expression['name']
							.replace(/\}/, ")")
							.replace(/\{/, "(");
						expressions += `
                            <li
                                class="px-3 py-1.5 cursor-pointer hover:bg-gray-200"
                                data-code='{{${expression['id']}|${leform_escape_html(label)}}}'
                            >
                                ${expression['id']} | ${leform_escape_html(expression['name'])}
                            </li>
                        `;
					}

					html += `
                        <div class='leform-properties-item' data-id='${key}'>
                            <div class='leform-properties-label'>
                                <label>${leform_meta[type][key]['label']}</label>
                            </div>
                            <div class='leform-properties-tooltip'>${tooltip_html}</div>
                            <div class='leform-properties-content repeater-input-footer-totals'>
                                <div>
                                    <label>${leform_esc_html__("Footer")}</label>
                                    <div class="flex items-center">
                                        <textarea name="leform-footer-tolals">${properties[key] ? properties[key] : ""
						}</textarea>
                                        <div class="shortcode-menu">
                                            <span class="flex items-center px-3 h-full cursor-pointer shortcode-toggle">
                                                <span class="fas fa-code"></span>
                                            </span>

                                            <ul class="absolute bg-white rounded-md top-5 right-0 overflow-y-auto max-h-40 max-w-40 border-2 border-gray-300 hidden">
                                                <li class="px-3 py-1.5 font-bold">
                                                    ${leform_esc_html__("Form values")}
                                                </li>
                                                ${formValues}
                                                <li class="px-3 py-1.5 font-bold">
                                                    ${leform_esc_html__("Expressions")}
                                                </li>
                                                ${expressions}
                                            </ul>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
					break;

				case 'textarea':
					html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>" + leform_meta[type][key]['label'] + "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'><textarea name='leform-" + key + "' id='leform-" + key + "'>" + leform_escape_html(properties[key]) + "</textarea></div></div>";
					break;

				case 'text-number':
					html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>" + leform_meta[type][key]['label'] + "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'><div class='leform-number'><input type='text' name='leform-" + key + "' id='leform-" + key + "' value='" + leform_escape_html(properties[key]) + "' placeholder='' /></div></div></div>";
					break;

				case 'text-number-natural-num':
					html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>" + leform_meta[type][key]['label'] + "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'><div class='leform-number'><input type='text' name='leform-" + key + "' id='leform-" + key + "' value='" + leform_escape_html(properties[key]) + "' placeholder='' min='0' /></div></div></div>";
					break;

				case 'checkbox':
					selected = "";
					if (properties[key] == "on") selected = " checked='checked'";
					html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>" + leform_meta[type][key]['label'] + "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'><input class='leform-checkbox-toggle' type='checkbox' value='off' name='leform-" + key + "' id='leform-" + key + "'" + selected + "' /><label for='leform-" + key + "'></label></div></div>";
					break;

				case 'select':
					options = "";
					for (var option_key in leform_meta[type][key]['options']) {
						if (leform_meta[type][key]['options'].hasOwnProperty(option_key)) {
							selected = "";
							if (option_key == properties[key]) selected = " selected='selected'";
							options += "<option" + selected + " value='" + leform_escape_html(option_key) + "'>"
								+ leform_esc_html__(leform_escape_html(leform_meta[type][key]['options'][option_key]))
								+ "</option>";
						}
					}
					html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>" + leform_meta[type][key]['label'] + "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'><div class='leform-third'><select name='leform-" + key + "' id='leform-" + key + "'>" + options + "</select></div></div></div>";
					break;

				case 'select-image':
					options = "";
					for (var option_key in leform_meta[type][key]['options']) {
						if (leform_meta[type][key]['options'].hasOwnProperty(option_key)) {
							selected = "";
							if (option_key == properties[key]) selected = " checked='checked'";
							options += "<input class='leform-radio-image' type='radio'" + selected + " value='" + leform_escape_html(option_key) + "' name='leform-" + key + "' id='leform-" + key + "-" + option_key + "' /><label for='leform-" + key + "-" + option_key + "' style='width:" + leform_meta[type][key]['width'] + "px;height:" + leform_meta[type][key]['height'] + "px;background-image:url(" + leform_escape_html(leform_meta[type][key]['options'][option_key]) + ");'></label>";
						}
					}
					html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>" + leform_meta[type][key]['label'] + "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'>" + options + "</div></div>";
					break;

				case 'mask':
					options = "<option value=''>" + leform_esc_html__("None") + "</option>";
					for (var option_key in leform_meta[type][key]['preset-options']) {
						if (leform_meta[type][key]['preset-options'].hasOwnProperty(option_key)) {
							selected = "";
							if (option_key == properties[key + "-preset"]) selected = " selected='selected'";
							options += "<option" + selected + " value='" + leform_escape_html(option_key) + "'>"
								+ leform_esc_html__(leform_escape_html(leform_meta[type][key]['preset-options'][option_key]))
								+ "</option>";
						}
					}
					temp = "<div class='leform-properties-content-half'><select name='leform-" + key + "-preset' id='leform-" + key + "-preset' onchange='leform_properties_mask_preset_changed(this);'>" + options + "</select></div>";
					temp += "<div class='leform-properties-content-half'><input type='text' name='leform-" + key + "-mask' id='leform-" + key + "-mask' value='" + leform_escape_html(properties[key + "-mask"]) + "'" + (properties[key + "-preset"] == "custom" ? "" : " readonly='readonly'") + " /></div>";
					html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>" + leform_meta[type][key]['label'] + "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'><div class='leform-properties-group'>" + temp + "</div></div></div>";
					break;

				case 'radio-bar':
					options = "";
					for (var option_key in leform_meta[type][key]['options']) {
						if (leform_meta[type][key]['options'].hasOwnProperty(option_key)) {
							selected = "";
							if (option_key == properties[key]) selected = " checked='checked'";
							options += "<input type='radio' value='" + leform_escape_html(option_key) + "' name='leform-" + key + "' id='leform-" + key + "-" + leform_escape_html(option_key) + "'" + (option_key == properties[key] ? " checked='checked'" : "") + "><label for='leform-" + key + "-" + leform_escape_html(option_key) + "'>" + leform_escape_html(leform_meta[type][key]['options'][option_key]) + "</label>";
						}
					}
					html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>" + leform_meta[type][key]['label'] + "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'><div class='leform-bar-selector'>" + options + "</div></div></div>";
					break;

				case 'select-size':
					options = "";
					for (var option_key in leform_meta[type][key]['options']) {
						if (leform_meta[type][key]['options'].hasOwnProperty(option_key)) {
							selected = "";
							if (option_key == properties[key + "-size"]) {
								selected = " selected='selected'";
							}
							options += "<option" + selected + " value='" + leform_escape_html(option_key) + "'>" + leform_escape_html(leform_meta[type][key]['options'][option_key]) + "</option>";
						}
					}
					temp = "";
					temp += "<div class='leform-properties-content-dime leform-240'><div><select name='leform-" + key + "-size' id='leform-" + key + "-size' onchange='if(jQuery(this).val()==\"custom\"){jQuery(\"#leform-content-" + key + "-custom\").fadeIn(300);}else{jQuery(\"#leform-content-" + key + "-custom\").fadeOut(300);}'>" + options + "</select></div><label>" + leform_escape_html(leform_meta[type][key]['caption']['size']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime leform-input-units leform-input-px'" + (properties[key + "-size"] == "custom" ? "" : " style='display:none;'") + " id='leform-content-" + key + "-custom'><input type='text' class='leform-ta-right' name='leform-" + key + "-custom' id='leform-" + key + "-custom' value='" + leform_escape_html(properties[key + '-custom']) + "' placeholder='Ex. 480' /><label>" + leform_escape_html(leform_meta[type][key]['caption']['custom']) + "</label></div>";
					temp += "<div class='leform-properties-content-9dimes'></div>";
					html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>" + leform_meta[type][key]['label'] + "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'>" + temp + "</div></div>";
					break;

				case 'input-icons':
					temp = "";
					icon_left = properties[key + "-left-icon"];
					if (icon_left == "") icon_left = "leform-fa-noicon";
					icon_right = properties[key + "-right-icon"];
					if (icon_right == "") icon_right = "leform-fa-noicon";
					temp += "<div class='leform-properties-content-dime'><a class='leform-fa-selector-button' href='#' onclick=\"return leform_fa_selector_open(this);\" data-id='" + key + "-left-icon'><i class='" + icon_left + "'></i></a><input type='hidden' name='leform-" + key + "-left-icon' id='leform-" + key + "-left-icon' value='" + leform_escape_html(properties[key + "-left-icon"]) + "' /><label>" + leform_escape_html(leform_meta[type][key]['caption']['left']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime leform-input-units leform-input-px'><input type='text' class='leform-ta-right' name='leform-" + key + "-left-size' id='leform-" + key + "-left-size' value='" + leform_escape_html(properties[key + '-left-size']) + "' placeholder='Ex. 10' /></div>";
					temp += "<div class='leform-properties-content-dime'></div>";
					temp += "<div class='leform-properties-content-dime'><a class='leform-fa-selector-button' href='#' onclick=\"return leform_fa_selector_open(this);\" data-id='" + key + "-right-icon'><i class='" + icon_right + "'></i></a><input type='hidden' name='leform-" + key + "-right-icon' id='leform-" + key + "-right-icon' value='" + leform_escape_html(properties[key + "-right-icon"]) + "' /><label>" + leform_escape_html(leform_meta[type][key]['caption']['right']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime leform-input-units leform-input-px'><input type='text' class='leform-ta-right' name='leform-" + key + "-right-size' id='leform-" + key + "-right-size' value='" + leform_escape_html(properties[key + '-right-size']) + "' placeholder='Ex. 10' /></div>";
					temp += "<div class='leform-properties-content-two-third'></div>";
					html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>" + leform_meta[type][key]['label'] + "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'>" + temp + "</div></div>";
					break;

				case 'button-icons':
					temp = "";
					icon_left = properties[key + "-left"];
					if (icon_left == "") icon_left = "leform-fa-noicon";
					icon_right = properties[key + "-right"];
					if (icon_right == "") icon_right = "leform-fa-noicon";
					temp += "<div class='leform-properties-content-dime'><a class='leform-fa-selector-button' href='#' onclick=\"return leform_fa_selector_open(this);\" data-id='" + key + "-left'><i class='" + icon_left + "'></i></a><input type='hidden' name='leform-" + key + "-left' id='leform-" + key + "-left' value='" + leform_escape_html(properties[key + "-left"]) + "' /><label>" + leform_escape_html(leform_meta[type][key]['caption']['left']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime'><a class='leform-fa-selector-button' href='#' onclick=\"return leform_fa_selector_open(this);\" data-id='" + key + "-right'><i class='" + icon_right + "'></i></a><input type='hidden' name='leform-" + key + "-right' id='leform-" + key + "-right' value='" + leform_escape_html(properties[key + "-right"]) + "' /><label>" + leform_escape_html(leform_meta[type][key]['caption']['right']) + "</label></div>";
					temp += "<div class='leform-properties-content-9dimes'></div>";
					html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>" + leform_meta[type][key]['label'] + "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'>" + temp + "</div></div>";
					break;

				case 'css':
					html += `<div class='leform-properties-item' data-id='${key}'>
                        <div class='leform-properties-label'>
                            <label>${leform_meta[type][key]['label']}</label>
                        </div>
                        <div class='leform-properties-tooltip'>
                            ${tooltip_html}
                        </div>
                        <div class='leform-properties-content'>
                            <div class='leform-properties-content-css'></div>
                            <a
                                class='leform-admin-button leform-admin-button-gray leform-admin-button-small'
                                href='#'
                                onclick='return leform_properties_css_add(\"${type}\", null);'
                            >
                                <i class='fas fa-plus'></i>
                                <label>${leform_esc_html__("Add a style")}</label>
                            </a>
                        </div>
                    </div>`;
					break;

				case 'confirmations':
					html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>" + leform_meta[type][key]['label'] + "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'><em>" + leform_meta[type][key]['message'] + "</em><div class='leform-properties-content-confirmations'></div><a class='leform-admin-button leform-admin-button-gray leform-admin-button-small' href='#' onclick='return leform_properties_confirmations_add(null);'><i class='fas fa-plus'></i><label>Add confirmation</label></a></div></div>";
					break;

				case 'math-expressions':
					html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>" + leform_meta[type][key]['label'] + "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'><div class='leform-properties-content-math-expressions'></div><a class='leform-admin-button leform-admin-button-gray leform-admin-button-small' href='#' onclick='return leform_properties_math_add(null);'><i class='fas fa-plus'></i><label>"
						+ leform_esc_html__("Add math expression")
						+ "</label></a></div></div>";
					break;

				case 'notifications':
					html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>" + leform_meta[type][key]['label'] + "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'><em>" + leform_meta[type][key]['message'] + "</em><div class='leform-properties-content-notifications'></div><a class='leform-admin-button leform-admin-button-gray leform-admin-button-small' href='#' onclick='return leform_properties_notifications_add(null);'><i class='fas fa-plus'></i><label>Add notification</label></a></div></div>";
					break;

				case 'integrations':
					html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>" + leform_meta[type][key]['label'] + "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'><em>" + leform_meta[type][key]['message'] + "</em><div class='leform-properties-content-integrations'></div><div class='leform-properties-content-integrations-providers'>";
					if (leform_integration_providers.length == 0) {
						html += "<div class='leform-properties-inline-error'>Activate at least one marketing/CRM system on Advanced Settings page.</div>";
					} else {
						for (var provider_key in leform_integration_providers) {
							if (leform_integration_providers.hasOwnProperty(provider_key)) {
								html += "<a class='leform-admin-button leform-admin-button-gray leform-admin-button-small' href='#' onclick='return leform_properties_integrations_add(null, -1, \"" + leform_escape_html(provider_key) + "\");'><i class='fas fa-plus'></i><label>" + leform_escape_html(leform_integration_providers[provider_key]) + "</label></a>";
							}
						}
					}
					html += "</div></div></div>";
					break;

				case 'payment-gateways':
					html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>" + leform_meta[type][key]['label'] + "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'><em>" + leform_meta[type][key]['message'] + "</em><div class='leform-properties-content-payment-gateways'></div><div class='leform-properties-content-payment-gateways-providers'>";
					if (leform_payment_providers.length == 0) {
						html += "<div class='leform-properties-inline-error'>Activate at least one payment provider on Advanced Settings page.</div>";
					} else {
						for (var provider_key in leform_payment_providers) {
							if (leform_payment_providers.hasOwnProperty(provider_key)) {
								html += "<a class='leform-admin-button leform-admin-button-gray leform-admin-button-small' href='#' onclick='return leform_properties_payment_gateways_add(null, -1, \"" + leform_escape_html(provider_key) + "\");'><i class='fas fa-plus'></i><label>" + leform_escape_html(leform_payment_providers[provider_key]) + "</label></a>";
							}
						}
					}
					html += "</div></div></div>";
					break;

				case 'validators':
					options = "";
					for (var j = 0; j < leform_meta[type][key]['allowed-values'].length; j++) {
						if (leform_validators.hasOwnProperty(leform_meta[type][key]['allowed-values'][j])) {
							options += "<a class='leform-admin-button leform-admin-button-gray leform-admin-button-small' href='#' title='"
								+ leform_esc_html__(leform_validators[leform_meta[type][key]['allowed-values'][j]]["tooltip"])
								+ "' onclick='return leform_properties_validators_add(\"" + properties["id"] + "\", \"" + type + "\", \"" + leform_meta[type][key]['allowed-values'][j] + "\", null);'><i class='fas fa-plus'></i><label>"
								+ leform_esc_html__(leform_validators[leform_meta[type][key]['allowed-values'][j]]["label"])
								+ "</label></a> ";
						}
					}
					html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>" + leform_meta[type][key]['label'] + "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'><div class='leform-properties-content-validators'></div><div class='leform-properties-content-validators-allowed'>" + options + "</div></div></div>";
					break;

				case 'filters':
					options = "";
					for (var j = 0; j < leform_meta[type][key]['allowed-values'].length; j++) {
						if (leform_filters.hasOwnProperty(leform_meta[type][key]['allowed-values'][j])) {
							options += "<a class='leform-admin-button leform-admin-button-gray leform-admin-button-small' href='#' title='"
								+ leform_esc_html__(leform_filters[leform_meta[type][key]['allowed-values'][j]]["tooltip"])
								+ "' onclick='return leform_properties_filters_add(\"" + type + "\", \"" + leform_meta[type][key]['allowed-values'][j] + "\", null);'><i class='fas fa-plus'></i><label>"
								+ leform_esc_html__(leform_filters[leform_meta[type][key]['allowed-values'][j]]["label"])
								+ "</label></a>";
						}
					}
					html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>"
						+ leform_esc_html__(leform_meta[type][key]['label'])
						+ "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'><div class='leform-properties-content-filters'></div><div class='leform-properties-content-filters-allowed'>" + options + "</div></div></div>";
					break;

				case 'error':
					html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label class='leform-red'>" + leform_meta[type][key]['label'] + "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'><input type='text' name='leform-" + key + "' id='leform-" + key + "' value='" + leform_escape_html(properties[key]) + "' placeholder='" + leform_escape_html(leform_meta[type][key]['value']) + "' /><em>"
						+ leform_esc_html__("Default message")
						+ ": " + leform_escape_html(leform_meta[type][key]['value']) + "</em></div></div>";
					break;

				case 'options':
					options = "";
					for (var j = 0; j < properties[key].length; j++) {
						selected = false;
						if (properties[key][j].hasOwnProperty("default") && properties[key][j]["default"] == "on") selected = true;
						options += leform_properties_options_item_get(properties[key][j]["label"], properties[key][j]["value"], selected);
					}
					html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>" + leform_meta[type][key]['label'] + "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'><div class='leform-properties-options-table-header'><div>"
						+ leform_esc_html__("Label")
						+ "</div><div>"
						+ leform_esc_html__("Value")
						+ "</div><div></div></div><div class='leform-properties-options-box'><div class='leform-properties-options-container' data-multi='" + leform_escape_html(leform_meta[type][key]['multi-select']) + "'>" + options + "</div></div><div class='leform-properties-options-table-footer'><a class='leform-admin-button leform-admin-button-gray leform-admin-button-small' href='#' onclick='return leform_properties_options_new(null);'><i class='fas fa-plus'></i><label>"
						+ leform_esc_html__("Add option")
						+ "</label></a><a class='leform-admin-button leform-admin-button-gray leform-admin-button-small' href='#' onclick='return leform_bulk_options_open(this);'><i class='fas fa-plus'></i><label>"
						+ leform_esc_html__("Add bulk options")
						+ "</label></a></div></div></div>";
					break;

				case 'image-options':
					options = "";
					for (var j = 0; j < properties[key].length; j++) {
						selected = "";
						if (
							properties[key][j].hasOwnProperty("default")
							&& properties[key][j]["default"] == "on"
						) {
							selected = " leform-properties-options-item-default";
						}
						options += `
                            <div class='leform-properties-options-item${selected}'>
                                <div class='leform-properties-options-table'>
                                    <div class='leform-image-url'>
                                        <input class='leform-properties-options-image' type='text' value='${leform_escape_html(properties[key][j]["image"])}' placeholder='URL' readonly>
                                        <input type="file" style="display: none;" accept="image/*" />
                                        <span onclick="selectImageOptionsSelectHandler(this)"><i class='far fa-image'></i></span>
                                    </div>
                                    <div>
                                        <input class='leform-properties-options-label' type='text' value='${leform_escape_html(properties[key][j]["label"])}' placeholder='${leform_esc_html__("Label")
							}'>
                                    </div>
                                    <div>
                                        <input class='leform-properties-options-value' type='text' value='${leform_escape_html(properties[key][j]["value"])}' placeholder='${leform_esc_html__("Value")
							}'>
                                    </div>
                                    <div>
                                        <span onclick='return leform_properties_options_default(this);' title='${leform_esc_html__("Set the option as a default value")
							}'> <i class='fas fa-check'></i></span>
                                        <span onclick='return leform_properties_options_new(this);' title='${leform_esc_html__("Add the option after this one")
							}'> <i class='fas fa-plus'></i> </span>
                                        <span onclick='return leform_properties_options_copy(this);' title='${leform_esc_html__("Duplicate the option")
							}'><i class='far fa-copy'></i></span>
                                        <span onclick='return leform_properties_options_delete(this);' title='${leform_esc_html__("Delete the option")
							}'><i class='fas fa-trash-alt'></i></span>
                                        <span title='${leform_esc_html__("Move the option")
							}'><i class='fas fa-arrows-alt leform-properties-options-item-handler'></i></span>
                                    </div>
                                </div>
                            </div>
                        `;
					}
					html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>" + leform_meta[type][key]['label'] + "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content leform-properties-image-options-table'><div class='leform-properties-options-table-header'><div>"
						+ leform_esc_html__("Image")
						+ "</div><div>"
						+ leform_esc_html__("Label")
						+ "</div><div>"
						+ leform_esc_html__("Value")
						+ "</div><div></div></div><div class='leform-properties-options-box'><div class='leform-properties-options-container' data-multi='" + (properties['mode'] == "radio" ? "off" : "on") + "'>" + options + "</div></div><div class='leform-properties-options-table-footer'><a class='leform-admin-button leform-admin-button-gray leform-admin-button-small' href='#' onclick='return leform_properties_image_options_new(this);'><i class='fas fa-plus'></i><label>"
						+ leform_esc_html__("Add option")
						+ "</label></a></div></div></div>";
					break;

				case 'logic-rules':
					var input_ids = new Array();
					for (var j = 0; j < leform_form_elements.length; j++) {
						if (leform_form_elements[j] == null) continue;
						//if (leform_form_elements[j]["id"] == properties["id"]) continue;
						if (
							leform_toolbar_tools.hasOwnProperty(leform_form_elements[j]['type'])
							&& leform_toolbar_tools[leform_form_elements[j]['type']]['type'] == 'input'
						) {
							input_ids.push(leform_form_elements[j]["id"]);
						}
					}
					if (input_ids.length > 0) {
						temp = "<div class='leform-properties-group leform-properties-logic-header'>";
						options = "";
						for (var option_key in leform_meta[type][key]['actions']) {
							if (leform_meta[type][key]['actions'].hasOwnProperty(option_key)) {
								options += "<option value='" + leform_escape_html(option_key) + "'" + (properties[key]["action"] == option_key ? " selected='selected'" : "") + ">" + leform_escape_html(leform_meta[type][key]['actions'][option_key]) + "</option>";
							}
						}
						temp += "<div class='leform-properties-content-half'><select name='leform-" + key + "-action' id='leform-" + key + "-action'>" + options + "</select></div>";
						options = "";
						for (var option_key in leform_meta[type][key]['operators']) {
							if (leform_meta[type][key]['operators'].hasOwnProperty(option_key)) {
								options += "<option value='" + leform_escape_html(option_key) + "'" + (properties[key]["operator"] == option_key ? " selected='selected'" : "") + ">" + leform_escape_html(leform_meta[type][key]['operators'][option_key]) + "</option>";
							}
						}
						temp += "<div class='leform-properties-content-half'><select name='leform-" + key + "-operator' id='leform-" + key + "-operator'>" + options + "</select></div>";
						temp += "</div>";
						options = "";
						for (var j = 0; j < properties[key]["rules"].length; j++) {
							if (input_ids.indexOf(parseInt(properties[key]["rules"][j]["field"], 10)) != -1) {
								options += leform_properties_logic_rule_get(properties["id"], properties[key]["rules"][j]["field"], properties[key]["rules"][j]["rule"], properties[key]["rules"][j]["token"]);
							}
						}
						temp += "<div class='leform-properties-logic-rules'>" + options + "</div><div class='leform-properties-logic-buttons'><a class='leform-admin-button leform-admin-button-gray leform-admin-button-small' href='#' onclick='return leform_properties_logic_rule_new(this, \"" + properties["id"] + "\");'><i class='fas fa-plus'></i><label>"
							+ leform_esc_html__("Add rule")
							+ "</label></a></div>";
					} else {
						temp = "<div class='leform-properties-inline-error'>There are no elements available to use for logic rules.</div>";
					}
					html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>" + leform_meta[type][key]['label'] + "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'>" + temp + "</div></div>";
					break;

				case 'column-width':
					temp = "";
					for (var j = 0; j < properties["_cols"]; j++) {
						temp += "<div class='leform-col-width'>";
						temp += "<label>#" + (parseInt(j + 1, 10)) + "</label>";
						temp += `
                            <div class='leform-slider-container'>
                                <input
                                    type='hidden'
                                    name='leform-${key}-${j}'
                                    id='leform-${key}-${j}'
                                    value='${properties[`${key}-${j}`]}'
                                />
                                <div class='leform-slider' data-min='0' data-max='12' data-step='1'>
                                    <div class='ui-slider-handle'></div>
                                </div>
                            </div>
                        `;
						temp += "</div>";
					}
					html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>" + leform_meta[type][key]['label'] + "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'>" + temp + "</div></div>";
					break;

				case 'colors':
					temp = "";
					temp += "<div class='leform-properties-content-dime'><input type='text' class='leform-color' data-alpha='true' name='leform-" + key + "-background' id='leform-" + key + "-background' value='" + leform_escape_html(properties[key + '-background']) + "' placeholder='...' /><label>" + leform_escape_html(leform_meta[type][key]['caption']['background']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime'><input type='text' class='leform-color' data-alpha='true' name='leform-" + key + "-border' id='leform-" + key + "-border' value='" + leform_escape_html(properties[key + '-border']) + "' placeholder='...' /><label>" + leform_escape_html(leform_meta[type][key]['caption']['border']) + "</label></div>";
					temp += "<div class='leform-properties-content-dime'><input type='text' class='leform-color' data-alpha='true' name='leform-" + key + "-text' id='leform-" + key + "-text' value='" + leform_escape_html(properties[key + '-text']) + "' placeholder='...' /><label>" + leform_escape_html(leform_meta[type][key]['caption']['text']) + "</label></div>";
					temp += "<div class='leform-properties-content-two-third'></div>";
					html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>" + leform_meta[type][key]['label'] + "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'><div class='leform-properties-group'>" + temp + "</div></div></div>";
					break;

				case 'date':
					temp = "<div class='leform-properties-content-third leform-date'><input type='text' name='leform-" + key + "' id='leform-" + key + "' value='" + leform_escape_html(properties[key]) + "' /><span><i class='far fa-calendar-alt'></i></span></div>";
					temp += "<div class='leform-properties-content-two-third'></div>";
					html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>" + leform_meta[type][key]['label'] + "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'><div class='leform-properties-group'>" + temp + "</div></div></div>";
					break;

				case 'date-limit':
					options2 = "";
					for (var j = 0; j < leform_form_elements.length; j++) {
						if (leform_form_elements[j] == null) continue;
						if (leform_form_elements[j]["id"] == properties["id"]) continue;
						if (leform_form_elements[j]["type"] == "date") {
							options2 += "<option value='" + leform_form_elements[j]["id"] + "'" + (properties[key + "-field"] == leform_form_elements[j]["id"] ? " selected='selected'" : "") + ">" + leform_escape_html(leform_form_elements[j]["id"] + " | " + leform_form_elements[j]["name"]) + "</option>";
						}
					}
					options = "";
					for (var option_key in leform_meta[type][key]['type-values']) {
						if (leform_meta[type][key]['type-values'].hasOwnProperty(option_key)) {
							if (option_key != "field" || options2 != "") {
								options += "<option value='" + leform_escape_html(option_key) + "'" + (properties[key + "-type"] == option_key ? " selected='selected'" : "") + ">" + leform_escape_html(leform_meta[type][key]['type-values'][option_key]) + "</option>";
							}
						}
					}
					temp = "<div class='leform-properties-content-third'><select name='leform-" + key + "-type' id='leform-" + key + "-type' onchange='var date = jQuery(this).closest(\".leform-properties-content\").find(\".leform-date-limit-date\"); var field = jQuery(this).closest(\".leform-properties-content\").find(\".leform-date-limit-field\"); var offset = jQuery(this).closest(\".leform-properties-content\").find(\".leform-date-limit-offset\"); if (jQuery(this).val() == \"date\") {jQuery(date).show();} else {jQuery(date).hide();} if (jQuery(this).val() == \"field\") {jQuery(field).show();} else {jQuery(field).hide();} if (jQuery(this).val() == \"offset\") {jQuery(offset).show();} else {jQuery(offset).hide();}'>" + options + "</select><label>" + leform_escape_html(leform_meta[type][key]['caption']['type']) + "</label></div>";
					temp += "<div class='leform-properties-content-third leform-date-limit-date leform-date'" + (properties[key + "-type"] == "date" ? "" : " style='display: none;'") + "><input type='text' name='leform-" + key + "-date' id='leform-" + key + "-date' value='" + leform_escape_html(properties[key + "-date"]) + "' /><label>" + leform_escape_html(leform_meta[type][key]['caption']['date']) + "</label><span><i class='far fa-calendar-alt'></i></span></div>";
					temp += "<div class='leform-properties-content-third leform-date-limit-field'" + (properties[key + "-type"] == "field" ? "" : " style='display: none;'") + "><select name='leform-" + key + "-field' id='leform-" + key + "-field'>" + options2 + "</select><label>" + leform_escape_html(leform_meta[type][key]['caption']['field']) + "</label></div>";
					temp += "<div class='leform-properties-content-third leform-date-limit-offset'" + (properties[key + "-type"] == "offset" ? "" : " style='display: none;'") + "><input type='text' name='leform-" + key + "-offset' id='leform-" + key + "-offset' value='" + leform_escape_html(properties[key + "-offset"]) + "' /><label>" + leform_escape_html(leform_meta[type][key]['caption']['offset']) + "</label></div>";
					temp += "<div class='leform-properties-content-two-third'></div>";
					html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>" + leform_meta[type][key]['label'] + "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'><div class='leform-properties-group'>" + temp + "</div></div></div>";
					break;

				case 'date-default':
					options = "";
					for (var option_key in leform_meta[type][key]['type-values']) {
						if (leform_meta[type][key]['type-values'].hasOwnProperty(option_key)) {
							if (option_key != "field") {
								options += "<option value='" + leform_escape_html(option_key) + "'" + (properties[key + "-type"] == option_key ? " selected='selected'" : "") + ">" + leform_escape_html(leform_meta[type][key]['type-values'][option_key]) + "</option>";
							}
						}
					}
					temp = "<div class='leform-properties-content-third'><select name='leform-" + key + "-type' id='leform-" + key + "-type' onchange='var date = jQuery(this).closest(\".leform-properties-content\").find(\".leform-date-default-date\"); var offset = jQuery(this).closest(\".leform-properties-content\").find(\".leform-date-default-offset\"); if (jQuery(this).val() == \"date\") {jQuery(date).show();} else {jQuery(date).hide();} if (jQuery(this).val() == \"offset\") {jQuery(offset).show();} else {jQuery(offset).hide();}'>" + options + "</select><label>" + leform_escape_html(leform_meta[type][key]['caption']['type']) + "</label></div>";
					temp += "<div class='leform-properties-content-third leform-date-default-date leform-date'" + (properties[key + "-type"] == "date" ? "" : " style='display: none;'") + "><input type='text' name='leform-" + key + "-date' id='leform-" + key + "-date' value='" + leform_escape_html(properties[key + "-date"]) + "' /><label>" + leform_escape_html(leform_meta[type][key]['caption']['date']) + "</label><span><i class='far fa-calendar-alt'></i></span></div>";
					temp += "<div class='leform-properties-content-third leform-date-default-offset'" + (properties[key + "-type"] == "offset" ? "" : " style='display: none;'") + "><input type='text' name='leform-" + key + "-offset' id='leform-" + key + "-offset' value='" + leform_escape_html(properties[key + "-offset"]) + "' /><label>" + leform_escape_html(leform_meta[type][key]['caption']['offset']) + "</label></div>";
					temp += "<div class='leform-properties-content-two-third'></div>";
					html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>" + leform_meta[type][key]['label'] + "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'><div class='leform-properties-group'>" + temp + "</div></div></div>";
					break;

				case 'time':
					temp = "<div class='leform-properties-content-third leform-time'><input type='text' name='leform-" + key + "' id='leform-" + key + "' value='" + leform_escape_html(properties[key]) + "' /><span><i class='far fa-clock'></i></span></div>";
					temp += "<div class='leform-properties-content-two-third'></div>";
					html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>" + leform_meta[type][key]['label'] + "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'><div class='leform-properties-group'>" + temp + "</div></div></div>";
					break;

				case 'time-limit':
					options2 = "";
					for (var j = 0; j < leform_form_elements.length; j++) {
						if (leform_form_elements[j] == null) continue;
						if (leform_form_elements[j]["id"] == properties["id"]) continue;
						if (leform_form_elements[j]["type"] == "time") {
							options2 += "<option value='" + leform_form_elements[j]["id"] + "'" + (properties[key + "-field"] == leform_form_elements[j]["id"] ? " selected='selected'" : "") + ">" + leform_escape_html(leform_form_elements[j]["id"] + " | " + leform_form_elements[j]["name"]) + "</option>";
						}
					}
					options = "";
					for (var option_key in leform_meta[type][key]['type-values']) {
						if (leform_meta[type][key]['type-values'].hasOwnProperty(option_key)) {
							if (option_key != "field" || options2 != "") {
								options += "<option value='" + leform_escape_html(option_key) + "'" + (properties[key + "-type"] == option_key ? " selected='selected'" : "") + ">" + leform_escape_html(leform_meta[type][key]['type-values'][option_key]) + "</option>";
							}
						}
					}
					temp = "<div class='leform-properties-content-third'><select name='leform-" + key + "-type' id='leform-" + key + "-type' onchange='var time = jQuery(this).closest(\".leform-properties-content\").find(\".leform-time-limit-time\"); var field = jQuery(this).closest(\".leform-properties-content\").find(\".leform-time-limit-field\"); if (jQuery(this).val() == \"time\") {jQuery(time).show();} else {jQuery(time).hide();} if (jQuery(this).val() == \"field\") {jQuery(field).show();} else {jQuery(field).hide();}'>" + options + "</select><label>" + leform_escape_html(leform_meta[type][key]['caption']['type']) + "</label></div>";
					temp += "<div class='leform-properties-content-third leform-time-limit-time leform-time'" + (properties[key + "-type"] == "time" ? "" : " style='display: none;'") + "><input type='text' name='leform-" + key + "-time' id='leform-" + key + "-time' value='" + leform_escape_html(properties[key + "-time"]) + "' /><label>" + leform_escape_html(leform_meta[type][key]['caption']['time']) + "</label><span><i class='far fa-clock'></i></span></div>";
					temp += "<div class='leform-properties-content-third leform-time-limit-field'" + (properties[key + "-type"] == "field" ? "" : " style='display: none;'") + "><select name='leform-" + key + "-field' id='leform-" + key + "-field'>" + options2 + "</select><label>" + leform_escape_html(leform_meta[type][key]['caption']['field']) + "</label></div>";
					temp += "<div class='leform-properties-content-two-third'></div>";
					html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>" + leform_meta[type][key]['label'] + "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'><div class='leform-properties-group'>" + temp + "</div></div></div>";
					break;

				case 'hr':
					html += '<hr>';
					break;

				case 'form-background':
					html += `
						<div class='leform-properties-item' data-id='${key}'>
							<div class='leform-properties-label'>
								<label>${leform_meta[type][key]['label']}</label>
							</div>
							<div class='leform-properties-tooltip'>
								${tooltip_html}
							</div>
							<div class='leform-properties-content'>
                                <div>
                                    <div class="p-4 preview-container  ${properties[key + "-file"] ? '' : 'f-hidden'}" id="form-pdf-background-container-${key}" style="display: flex;flex-direction: row;align-items: center;">
																		 <div style="display:flex; flex-direction: column; align-items: center; margin-right: 10px;">
																				<label>
																						${leform_esc_html__('left')}
																				</label>
																				<input
																						type='text'
																						name='leform-${key}-left'
																						id='leform-${key}-left'
																						value='${leform_escape_html(properties[key + "-left"])}'
																						placeholder='Ex. 10'
																						style="width: 50px;"
																				/>
																		</div>
																		<div style="display: flex; flex-direction: column; align-items: center; justify-content: center;">
																			<div style="display:flex; flex-direction: column; align-items: center; margin-bottom: 10px;">
																					<label>
																							${leform_esc_html__('top')}
																					</label>
																					<input
																							type='text'
																							name='leform-${key}-top'
																							id='leform-${key}-top'
																							value='${leform_escape_html(properties[key + "-top"])}'
																							placeholder='Ex. 10'
																							style="width: 50px;"
																					/>
																			</div>
																			<div class="fileinput-new thumbnail" id="form-pdf-background-preview-${key}" style="width: 200px; height: 150px;">
																				${properties[key + "-file"] ? `
																					<object data="/${properties[key + "-file"].replace("public", "storage")}" type="application/pdf" width="200px" height="150px">
																							<embed src="/${properties[key + "-file"].replace("public", "storage")}" type="application/pdf">
																							<p><a href="/${properties[key + "-file"].replace("public", "storage")}">Download pdf</a>.</p>
																							</embed>
																					</object>
																				`: ''}
																				</div>
																				<div style="display:flex; flex-direction: column; align-items: center; margin-top: 10px;">
																					<label>
																							${leform_esc_html__('bottom')}
																					</label>
																					<input
																							type='text'
																							name='leform-${key}-bottom'
																							id='leform-${key}-bottom'
																							value='${leform_escape_html(properties[key + "-bottom"])}'
																							placeholder='Ex. 10'
																							style="width: 50px;"
																					/>
																			</div>
																		</div>
																				<div style="display:flex; flex-direction: column; align-items: center; margin-left: 10px;">
																					<label>
																							${leform_esc_html__('right')}
																					</label>
																					<input
																							type='text'
																							name='leform-${key}-right'
																							id='leform-${key}-right'
																							value='${leform_escape_html(properties[key + "-right"])}'
																							placeholder='Ex. 10'
																							style="width: 50px;"
																					/>
																			</div>
                                    </div>
                                </div>
																<form
                                    class="custom-pdf-background-select"
                                    action="#"
																		data-key="${key}"
                                    method="POST"
                                >
                                    <div class="flex items-center">
                                        <input
                                            id="leform-${key}-file"
                                            name="leform-${key}-file"
                                            style="width: auto;"
                                            type="hidden"
                                            value="${properties[key + "-file"]}"
                                            disabled
                                        />

                                        <input
                                            class="hidden"
                                            type="file"
                                            accept="application/pdf"
                                        />

                                        <button
                                            role="upload"
                                            class="rounded-lg h-8 bg-green-500 text-white ml-3 px-4"
                                        >
                                            ${leform_esc_html__("Select PDF")}
                                        </button>

                                        <button
                                            role="close"
                                            class="rounded-lg h-8 bg-red-500 text-white ml-3 px-4 ${properties[key + "-file"] === "" && "f-hidden"
						}"
                                        >
                                            ${leform_esc_html__("Remove PDF")}
                                        </button>
                                    </div>
								</form>
							</div>
						</div>
					`;
					break;

				case 'email-list':
					let emailsList = [];

					try {
						emailsList = JSON.parse(properties[key]);
					} catch (error) { }

					const emails = emailsList
						.map((email) => `
                            <div class="email-group mb-2">
                                <input
                                    style="width: auto;"
                                    type="email"
                                    name="leform-${key}"
                                    placeholder="${leform_esc_html__("Type email")}"
                                    value="${email}"
                                />
                                <button
                                    role="remove"
                                    class="rounded-lg h-8 w-8 bg-red-500 text-white ml-3"
                                >
                                    x
                                </button>
                            </div>
                        `)
						.join("\n");
					html += `
                        <div class='leform-properties-item after-submit-email-integration' data-id='${key}'>
                            <div class='leform-properties-label'>
                                <label>${leform_meta[type][key]['label']}</label>
                            </div>

                            <div class='leform-properties-tooltip'>
                                ${tooltip_html}
                            </div>

                            <div class='leform-properties-content'>

                                <div class="mb-2">
                                    <button
                                        role="add-email"
                                        class="rounded-lg h-8 px-4 border-2 border-green-700 text-green-700"
                                    >
                                        + ${leform_esc_html__("Add email")}
                                    </button>
                                </div>

                                <div class="email-list" style="padding-left: 0;">
                                    ${emails}
                                </div>

                            </div>
                        </div>
                    `;
					break;

				case "xml-field-names": {
					var xmlFieldForValidation = leform_meta[type][key];
					// validation
					let fieldNamesList = properties[key];
					let fieldNamesListActive = properties[`${key}-active`];
					if (typeof properties[key] === "string") {
						try {
							fieldNamesList = JSON.parse(properties[key]);
						} catch (error) { }
					}
					if (typeof fieldNamesListActive === "string") {
						try {
							fieldNamesListActive = JSON.parse(fieldNamesListActive);
						} catch (error) { }
					}
					if (typeof fieldNamesListActive !== 'object') {
						fieldNamesListActive = {};
					}
					let fieldNames = [];
					// key, label, defaultValue
					let base = [
						['tag', 'Tag', 'Element'],
						['key', 'Key', 'Label'],
						['value', 'Value', 'Eingabe'],
						['value_max', 'Value max', 'Maxwert'],
						['value_default', 'Value default', 'StandardWert'],
						['type', 'Type', 'Feldtyp']
					];
					if (['settings'].includes(type)) {
						base = [
							['form', 'Haupt Tag', 'form'],
							...base
						];
					}
					if ('columns' === type) {
						base = [
							// ['groupTag', 'Group tag', 'Gruppe'],
							['tag', 'Tag', 'Element'],
						]
					}

					const render = (fieldKey, label, defaultValue) => `
						<div class="custom-field-group mb-2" style="display: flex; align-items: center;">
							<input
									type="hidden"
									name="leform-${key}"
									value="${fieldKey}"
							/>
							<div class="mr-2" style="min-width: 100px;" >
									${leform_esc_html__(label)}:
							</div>
							<div>
								<input
									class="character-restritced-xml-field-name"
									style="width: auto;"
									type="text"
									name="value"
									placeholder="${defaultValue}"
									value="${fieldNamesList[fieldKey] || ""}"
									${xmlFieldForValidation.validation ? `data-pattern="${xmlFieldForValidation.validation}"` : ''}
									title="${leform_esc_html__('Must start with letter and contains only characters and _')}"
								/>
							</div>
							${(type === 'columns' || !['tag', 'form'].includes(fieldKey)) ? `
							<div class="leform-properties-content ml-3">
								<input class="leform-checkbox-toggle" type="checkbox" value="${fieldNamesListActive[fieldKey] || "on"}" name="active" id="leform-xml-field-${fieldKey}-active" ${fieldNamesListActive[fieldKey] === 'off' ? '' : 'checked="checked"'} >
								<label for="leform-xml-field-${fieldKey}-active"></label>
							</div>`: ''}
						</div>
					`;
					base.forEach(
						([key, label, defaultValue]) => fieldNames.push(
							render(key, label, defaultValue)
						)
					);
					fieldNames = fieldNames.join("\n");

					html += `
                        <div class='leform-properties-item' data-id='${key}'>
                            <div class='leform-properties-label'>
                                <label>${leform_meta[type][key]['label']}</label>
                            </div>

                            <div class='leform-properties-tooltip'>
                                ${tooltip_html}
                            </div>

                            <div class='leform-properties-content'>
                                <div style="padding-left: 0;" id="xml_custom_input_tag_container">
                                    ${fieldNames}
                                </div>
                            </div>
                        </div>
												<script>
													jQuery("#xml_custom_input_tag_container input").keypress(function(e){
    												// console.log(e);
														var charCode = String.fromCharCode(!e.charCode ? e.which : e.charCode);
														if(jQuery(e.target).data('pattern')) {
															var r = new RegExp(jQuery(e.target).data('pattern'))
															var val = jQuery(e.target).val();
															// console.log(val+charCode, r.test(val+charCode), jQuery(e.target).data('pattern'))
															if(!r.test(val+charCode)){
																	e.preventDefault();
																	return false;
															}
														}
													});
												</script>
                    `;
					break;
				}

				case "custom-xml-fields": {
					let customFieldsList = [];
					try {
						customFieldsList = JSON.parse(properties[key]);
					} catch (error) {
						if (Array.isArray(properties[key])) {
							customFieldsList = properties[key];
						}
					}
					const customFields = customFieldsList
						.map(({ name, value }) => renderXMLCustomFieldInput(
                            name,
                            value,
                            leform_meta[type][key].validation,
                            ["settings", "columns"].includes(type),
                            !["columns", "repeater-input"].includes(type),
                        ))
						.join("\n");

					html += `
                        <div class='leform-properties-item after-submit-xml-custom-fields' data-id='${key}'>
                            <div class='leform-properties-label'>
                                <label>${leform_meta[type][key]['label']}</label>
                            </div>

                            <div class='leform-properties-tooltip'>
                                ${tooltip_html}
                            </div>

                            <div class='leform-properties-content'>
                                <div class="mb-2">
                                    <button
                                        role="add-custom-field"
                                        class="rounded-lg h-8 px-4 border-2 border-green-700 text-green-700"
                                        ${leform_meta[type][key].validation ? `data-priority="${leform_meta[type][key].validation}"` : ''}
                                    >
                                        + ${leform_esc_html__("Add custom field")}
                                    </button>
                                </div>

                                <div class="custom-field-list" data-element-type="${type}" style="padding-left: 0;">
                                    ${customFields}
                                </div>
                            </div>
                        </div>

                        <script>
                            jQuery("input.character-restritced-xml-field-name").unbind('keypress');
                            jQuery("input.character-restritced-xml-field-name").keypress(function(e){
                            // console.log(e);
                                var charCode = String.fromCharCode(!e.charCode ? e.which : e.charCode);
                                if(jQuery(e.target).data('pattern')) {
                                    var r = new RegExp(jQuery(e.target).data('pattern'))
                                    var val = jQuery(e.target).val();
                                    // console.log(val+charCode, r.test(val+charCode), jQuery(e.target).data('pattern'))
                                    if(!r.test(val+charCode)){
                                            e.preventDefault();
                                            return false;
                                    }
                                }
                            });
                        </script>
                    `;
					break;
				}

				case "xml-file-name": {
					html += `
                        <div class="leform-properties-item" data-id="${key}">
                            <div class="leform-properties-label">
                                <label>${leform_meta[type][key]["label"]}</label>
                            </div>
                            <div class="leform-properties-tooltip">
                                ${tooltip_html}
                            </div>
                            <div class="leform-properties-content" style="display: flex;">
                                <input
                                    type="text"
                                    name="leform-${key}"
                                    id="leform-${key}"
                                    value="${leform_escape_html(properties[key])}"
                                    placeholder=""
                                    style="margin-right: 10px;"
                                />
                                ${renderXmlSystemVariablesMenu([
                                    "fw_id",
                                    "fw_yyyymmdd",
                                    "fw_yyyymmdd_hhii",
                                    "fw_yyyymmdd_hhiiss",
                                    "fw_random_5",
                                ], undefined, true)}
                            </div>
                        </div>
                    `;
					break;
				}

				case "custom-report-textarea": {
					html += `
                        <div class="leform-properties-item" data-id="${key}">
                            <div class="leform-properties-label">
                                <label>${leform_meta[type][key]["label"]}</label>
                            </div>
                            <div class="leform-properties-tooltip">
                                ${tooltip_html}
                            </div>
                            <div class="leform-properties-content" style="display: flex;">
                                ${renderXmlSystemVariablesMenu([
                                    "fw_id",
                                    "fw_yyyymmdd",
                                    "fw_yyyymmdd_hhii",
                                    "fw_yyyymmdd_hhiiss",
                                    "fw_random_5",
                                ], 'textarea', true)}
                                <textarea
                                    name="leform-${key}"
                                    id="leform-${key}"
                                >${leform_escape_html(properties[key])}</textarea>
                            </div>
                        </div>
					`;
					break;
				}

                case "image-upload": {
					html += `
                        <div class="leform-properties-item" data-id="${key}">
                            <div class="leform-properties-label">
                                <label>${leform_meta[type][key]["label"]}</label>
                            </div>
                            <div class="leform-properties-tooltip">
                                ${tooltip_html}
                            </div>
                            <div class="leform-properties-content" style="display: flex;">
                                <div class='leform-image-url'>
                                    <input
                                        name='leform-${key}'
                                        class='leform-properties-options-image'
                                        type='text'
                                        value='${properties[key]}'
                                        placeholder='URL'
                                        readonly
                                    >
                                    <input type="file" style="display: none;" accept="image/*" />
                                    <span onclick="selectImageOptionsSelectHandler(this)">
                                        <i class='far fa-image'></i>
                                    </span>
                                </div>
                            </div>
                        </div>
					`;
					break;
                }

                case "text-with-form-fields": {
                    const isTextarea = leform_meta[type][key]["isTextarea"];
					html += `
                        <div class='leform-properties-item' data-id='${key}'>
                            <div class='leform-properties-label'>
                                <label>${leform_meta[type][key]['label']}</label>
                            </div>
                            <div class='leform-properties-tooltip'>
                                ${tooltip_html}
                            </div>
                            <div class='leform-properties-content text-with-form-fields'>
                                <div class="flex items-center">
                                    ${isTextarea ? `
                                        <textarea
                                            name='leform-${key}'
                                            id='leform-${key}'
                                        >${leform_escape_html(properties[key])}</textarea>
                                    ` : `
                                        <input
                                            type='text'
                                            name='leform-${key}'
                                            id='leform-${key}'
                                            value='${leform_escape_html(properties[key])}'
                                            placeholder=''
                                        />
                                    `}
                                    ${renderFormFieldsShortcodeMenu()}
                                </div>
                            </div>
                        </div>
                    `;
					break;
                }

                case "field-or-text": {
                    const bindField = properties["bind-field"];
                    const isTextarea = leform_meta[type][key]["isTextarea"];
                    const formValues = getFormFields()
                        .filter((element) => element['id'] !== properties?.id)
                        .filter((element) => ["text", "textarea", "email", "date", "select", "iban-input"].includes(element['type']))
                        .map((element) => `
                            <option value="${element['id']}" ${element['id'].toString() === bindField ? 'selected' : ''}>
                                ${element['id']} | ${element['name']}
                            </option>
                        `)
                        .join("\n");

					html += `
                        <div class='leform-properties-item' data-id='${key}'>
                            <div class='leform-properties-label'>
                                <label>${leform_meta[type][key]['label']}</label>
                            </div>
                            <div class='leform-properties-tooltip'>
                                ${tooltip_html}
                            </div>
                            <div class='leform-properties-content'>
                                <div class="grid grid-cols-2">
                                    ${isTextarea ? `
                                        <textarea
                                            name='leform-${key}'
                                            id='leform-${key}'
                                        >${leform_escape_html(properties[key])}</textarea>
                                    ` : `
                                        <input
                                            type='text'
                                            name='leform-${key}'
                                            id='leform-${key}'
                                            value='${leform_escape_html(properties[key])}'
                                            placeholder=''
                                            ${bindField ? 'disabled' : ''}
                                        />
                                    `}
                                    <div style="padding-left: 10px;">
                                        <select
                                            id="leform-bind-field"
                                            name="leform-bind-field"
                                            onchange="handleFieldOrTextChange(this, ${isTextarea ? '\'textarea\'' : '\'input\''})"
                                        >
                                            <option value="">${leform_esc_html__("No binding")}</option>
                                            ${formValues}
                                        <select>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
					break;
                }

				default:
					break;
			}
		}
	}
	for (var j = 0; j < sections_opened; j++) html += "</div>";
	sections_opened = 0;
	if (tab_html != "") {
		tab_html += "</div>";
		html += "</div>";
	}
	jQuery("#leform-element-properties .leform-admin-popup-content-form").html(tab_html + html);

	/*
	 * please refactor
	 * */
	addEventListenersToPdfBackgroundUploaders();
	addEventListenersToAfterSubmitEmailIntegration();
	addEventListenersToAfterSubmitXMLCustomFields();
	addEventListenersToRepeaterInputFields();
	addEventListenersToRepeaterInputExpressions();
	addEventListenersToRepeaterInputFooterTotals();
	addEventListenersToTextFieldsWithFormVariables();

	if (type == "settings") {
		for (var j = 0; j < leform_form_elements.length; j++) {
			if (leform_form_elements[j] == null) continue;
			if (leform_form_elements[j]['type'] == 'signature') {
				var xd = jQuery("#leform-element-properties .leform-admin-popup-content-form").find("[name='leform-cross-domain']");
				jQuery(xd).prop("checked", false);
				jQuery(xd).prop("disabled", true);
				break;
			}
		}
	}
	jQuery("#leform-properties-tabs a").first().addClass("leform-tab-active");
	jQuery(jQuery("#leform-properties-tabs a").first().attr("href")).show();
	if (properties.hasOwnProperty("css") && Array.isArray(properties["css"])) {
		for (var j = 0; j < properties["css"].length; j++) {
			leform_properties_css_add(type, properties["css"][j])
		}
	}
	if (properties.hasOwnProperty("validators") && Array.isArray(properties["validators"])) {
		for (var j = 0; j < properties["validators"].length; j++) {
			leform_properties_validators_add(properties["id"], type, properties["validators"][j]["type"], properties["validators"][j]);
		}
	}
	if (properties.hasOwnProperty("filters") && Array.isArray(properties["filters"])) {
		for (var j = 0; j < properties["filters"].length; j++) {
			leform_properties_filters_add(type, properties["filters"][j]["type"], properties["filters"][j]);
		}
	}
	if (properties.hasOwnProperty("confirmations") && Array.isArray(properties["confirmations"])) {
		for (var j = 0; j < properties["confirmations"].length; j++) {
			leform_properties_confirmations_add(properties["confirmations"][j])
		}
		jQuery(".leform-properties-content-confirmations").sortable({
			items: ".leform-properties-sub-item",
			forcePlaceholderSize: true,
			dropOnEmpty: true,
			placeholder: "leform-properties-sub-item-placeholder",
			start: function (e, ui) {
				if (typeof wp != 'undefined' && wp.hasOwnProperty('editor') && typeof wp.editor.initialize == 'function') {
					jQuery(ui.item).find('.leform-tinymce').each(function () {
						jQuery(this).addClass("leform-tinymce-pre");
						wp.editor.remove(jQuery(this).attr("id"));
					});
				}
			},
			stop: function (e, ui) {
				leform_init_tinymce();
				leform_init_url_with_variables();
			}
		});
		jQuery(".leform-properties-sub-item").disableSelection();
	}
	if (properties.hasOwnProperty("notifications") && Array.isArray(properties["notifications"])) {
		for (var j = 0; j < properties["notifications"].length; j++) {
			leform_properties_notifications_add(properties["notifications"][j])
		}
	}
	if (
		properties.hasOwnProperty("math-expressions")
		&& Array.isArray(properties["math-expressions"])
	) {
		for (var j = 0; j < properties["math-expressions"].length; j++) {
			leform_properties_math_add(properties["math-expressions"][j])
		}
	}
	if (properties.hasOwnProperty("integrations") && Array.isArray(properties["integrations"])) {
		for (var j = 0; j < properties["integrations"].length; j++) {
			if (properties["integrations"][j]['id'] > leform_integration_last_id) leform_integration_last_id = properties["integrations"][j]['id'];
			leform_properties_integrations_add(properties["integrations"][j], j);
		}
	}
	if (properties.hasOwnProperty("payment-gateways") && Array.isArray(properties["payment-gateways"])) {
		for (var j = 0; j < properties["payment-gateways"].length; j++) {
			if (properties["payment-gateways"][j]['id'] > leform_payment_gateway_last_id) leform_payment_gateway_last_id = properties["payment-gateways"][j]['id'];
			leform_properties_payment_gateways_add(properties["payment-gateways"][j], j);
		}
	}
	if (properties.hasOwnProperty("options")) {
		jQuery(".leform-properties-options-box").resizable({
			grid: [5, 5],
			handles: "s"
		});

		jQuery(".leform-properties-options-container").sortable({
			items: ".leform-properties-options-item",
			forcePlaceholderSize: true,
			dropOnEmpty: true,
			placeholder: "leform-properties-options-item-placeholder",
			handle: ".leform-properties-options-item-handler"
		});
		jQuery(".leform-properties-options-item").disableSelection();
	}
	jQuery(".leform-properties-content .leform-date input").each(function () {
		var object = this;
		var airdatepicker = jQuery(object).airdatepicker().data('airdatepicker');
		airdatepicker.destroy();
		jQuery(object).airdatepicker({
			inline_popup: true,
			autoClose: true,
			timepicker: false,
			dateFormat: leform_form_options["datetime-args-date-format"]
		});
	});
	jQuery(".leform-properties-content .leform-date span").on("click", function (e) {
		e.preventDefault();
		var input = jQuery(this).parent().children("input");
		var airdatepicker = jQuery(input).airdatepicker().data('airdatepicker');
		airdatepicker.show();
	});
	jQuery(".leform-properties-content .leform-time input").each(function () {
		var object = this;
		var airdatepicker = jQuery(object).airdatepicker().data('airdatepicker');
		airdatepicker.destroy();
		jQuery(object).airdatepicker({
			inline_popup: true,
			autoClose: true,
			timepicker: true,
			onlyTimepicker: true,
			timeFormat: leform_form_options["datetime-args-time-format"]
		});
	});
	jQuery(".leform-properties-content .leform-time span").on("click", function (e) {
		e.preventDefault();
		var input = jQuery(this).parent().children("input");
		var airdatepicker = jQuery(input).airdatepicker().data('airdatepicker');
		airdatepicker.show();
	});
	jQuery("#leform-properties-tabs a").on("click", function (e) {
		e.preventDefault();
		if (jQuery(this).hasClass("leform-tab-active")) return;
		var tab_set = jQuery(this).parent();
		var active_tab = jQuery(tab_set).find(".leform-tab-active").attr("href");
		jQuery(tab_set).find(".leform-tab-active").removeClass("leform-tab-active");
		var tab = jQuery(this).attr("href");
		jQuery(this).addClass("leform-tab-active");
		jQuery(active_tab).fadeOut(300, function () {
			jQuery(tab).fadeIn(300);
		});
	});
	jQuery(".leform-bar-options span").on("click", function (e) {
		var parent = jQuery(this).parent();
		var value = jQuery(this).attr("data-value");
		var current_value = jQuery(parent).find("input").val();
		jQuery(parent).children("span").removeClass("leform-bar-option-selected");
		if (current_value == value) {
			value = "";
			jQuery(parent).find("input").val(value);
		} else {
			jQuery(this).addClass("leform-bar-option-selected");
			jQuery(parent).find("input").val(value);
		}
		if (jQuery(parent).find("input").attr("name") == "leform-label-style-position") {
			if (value == "left" || value == "right") jQuery("#leform-content-label-style-width").fadeIn(300);
			else jQuery("#leform-content-label-style-width").fadeOut(300);
		}
	});

	/*
	jQuery(".leform-image-url span").each((index, element) => {
			const input = jQuery(element).parent().children("input[type='text']");
			const fileSelect = jQuery(element).parent().children("input[type='file']");

			element.addEventListener("click", () => {
					fileSelect.click();
			});

			fileSelect.on("change", (e) => {
					if (!e.target.files[0]) {
							return;
					}

					const file = e.target.files[0];

					const data = new FormData();
					data.append("form_id", jQuery("#leform-id").val());
					data.append("file", file);
					data.append("_token", jQuery("meta[name='csrf-token']").attr("content"));

					fetch("/forms/upload-select-image-file", { method: "POST", body: data })
							.then((response) => response.json())
							.then((response) => {
									if (response.status === "ERROR") {
											leform_global_message_show("danger", response.message);
											return;
									}

									jQuery(input).val(response.path);
							})
							.catch((error) => {
									console.log(error);
							});
			});
	});
	*/

	jQuery(".leform-sections").each(function () {
		jQuery(this).find("a").on("click", function (e) {
			e.preventDefault();
			if (jQuery(this).hasClass("leform-section-active")) return;
			var sections_set = jQuery(this).parent();
			var active_section = jQuery(sections_set).find(".leform-section-active").attr("href");
			jQuery(sections_set).find(".leform-section-active").removeClass("leform-section-active");
			var section = jQuery(this).attr("href");
			jQuery(this).addClass("leform-section-active");
			if (jQuery(active_section).length > 0) {
				jQuery(active_section).fadeOut(300, function () {
					jQuery(section).fadeIn(300);
				});
			} else jQuery(section).fadeIn(300);
		});
		jQuery(jQuery(this).find("a").first().attr("href")).show();
	});
	jQuery(".leform-color").minicolors({
		format: 'rgb',
		opacity: true,
		change: function (value, opacity) {
			leform_properties_change();
		}
	});
	jQuery(".leform-slider").each(function () {
		var input = jQuery(this).parent().children("input");
		jQuery(this).slider({
			min: parseInt(jQuery(this).attr("data-min"), 10),
			max: parseInt(jQuery(this).attr("data-max"), 10),
			step: parseInt(jQuery(this).attr("data-step"), 10),
			value: leform_is_numeric(jQuery(input).val()) ? parseInt(jQuery(input).val(), 10) : 4,
			create: function () {
				jQuery(this).find(".ui-slider-handle").text(jQuery(this).slider("value"));
			},
			slide: function (event, ui) {
				jQuery(this).find(".ui-slider-handle").text(ui.value);
				jQuery(input).val(ui.value);
			}
		});
	});
	jQuery(".leform-properties-tooltip .leform-tooltip-anchor").tooltipster({
		contentAsHTML: true,
		maxWidth: 360,
		theme: "tooltipster-dark",
		side: "bottom",
		content: "Default",
		functionFormat: function (instance, helper, content) {
			return jQuery(helper.origin).parent().find('.leform-tooltip-content').html();
		}
	});
	jQuery(".leform-properties-content-validators-allowed a[title], .leform-properties-content-filters-allowed a[title]").tooltipster({
		maxWidth: 360,
		theme: "tooltipster-dark",
		side: "bottom"
	});

	jQuery(".leform-properties-content input").on("keyup", function (e) {
		leform_properties_change();
	});
	jQuery(".leform-properties-content input, .leform-properties-content select").on("change", function (e) {
		leform_properties_change();
	});
	leform_init_tinymce();
	leform_init_url_with_variables();
	leform_properties_visible_conditions(_object);
	// Prepare editor state - end
	return false;
}

function leform_properties_open(_object) {
	jQuery("#leform-element-properties .leform-admin-popup-content-form").html("");
	var window_height = 2 * parseInt((jQuery(window).height() - 100) / 2, 10);
	var window_width = Math.min(Math.max(2 * parseInt((jQuery(window).width() - 300) / 2, 10), 880), 1080);
	jQuery("#leform-element-properties").height(window_height);
	jQuery("#leform-element-properties").width(window_width);
	jQuery("#leform-element-properties .leform-admin-popup-inner").height(window_height);
	jQuery("#leform-element-properties .leform-admin-popup-content").height(window_height - 104);
	jQuery("#leform-element-properties-overlay").fadeIn(300);
	jQuery("#leform-element-properties").fadeIn(300);
	leform_element_properties_active = _object;
	leform_element_properties_data_changed = false;
	jQuery("#leform-element-properties .leform-admin-popup-loading").show();

	setTimeout(function () {
		_leform_properties_prepare(_object);
		jQuery("#leform-element-properties .leform-admin-popup-loading").hide();
	}, 500);
	return false;
}
function leform_styles_html() {
	var html = "<select onchange='leform_styles_load(this);'><option value=''>" + leform_esc_html__("Select theme...") + "</option>";
	var type = -1;
	var user_options = "", native_options = "";
	for (var j = 0; j < leform_styles.length; j++) {
		if (leform_styles[j]["type"] == 1) native_options += "<option value='" + leform_escape_html(leform_styles[j]["id"]) + "'>" + leform_escape_html(leform_styles[j]["name"]) + "</option>";
		else user_options += "<option value='" + leform_escape_html(leform_styles[j]["id"]) + "'>" + leform_escape_html(leform_styles[j]["name"]) + "</option>";
	}
	html += (native_options == "" ? "" : "<optgroup label='" + leform_esc_html__("Native Themes") + "'>" + native_options + "</optgroup>") + (user_options == "" ? "" : "<optgroup label='" + leform_esc_html__("User Themes") + "'>" + user_options + "</optgroup>") + "</select>";
	return html;
}
function leform_styles_save(_object) {
	var html = '';
	leform_dialog_open({
		title: leform_esc_html__('Save As...'),
		echo_html: function () {
			var html = "";
			var options = "<option value='0'>" + leform_esc_html__("Create new theme...", "leform") + "</option>";
			for (var i = 0; i < leform_styles.length; i++) {
				if (leform_styles[i]['type'] == 0) {
					options += "<option value='" + leform_escape_html(leform_styles[i]['id']) + "'>" + leform_escape_html(leform_styles[i]['name']) + "</option>";
				}
			}
			html += "<div class='leform-style-save-row'><label>" + leform_esc_html__("Save as", "leform") + ":</label><select id='leform-style-id' onchange='jQuery(this).val() == 0 ? jQuery(\"#leform-style-save-row-name\").show() : jQuery(\"#leform-style-save-row-name\").hide();'>" + options + "</select></div>"
			html += "<div class='leform-style-save-row' id='leform-style-save-row-name'><label>" + leform_esc_html__("Name", "leform") + ":</label><input type='text' value='" + leform_escape_html(leform_form_options['name'] + " theme") + "' placeholder='" + leform_esc_html__("Enter theme name...", "leform") + "' id='leform-style-name' /></div>"
			this.html(html);
			this.show();
		},
		height: 320,
		ok_label: leform_esc_html__('Save Theme'),
		ok_function: function (e) {
			_leform_styles_save(jQuery("#leform-dialog .leform-dialog-button-ok"));
		}
	});
}
function _leform_styles_save(_object) {
	var input, key, key2, style_options = {};
	if (leform_element_properties_active == null) return false;
	var type = jQuery(leform_element_properties_active).attr("data-type");
	if (typeof type == undefined || type != "settings") return false;
	if (leform_sending) return false;
	leform_sending = true;
	var icon = jQuery(_object).find("i").attr("class");
	jQuery(_object).find("i").attr("class", "fas fa-spinner fa-spin");
	for (var key in leform_form_options) {
		if (leform_form_options.hasOwnProperty(key)) {
			input = jQuery("[name='leform-" + key + "']");
			if (input.length == 0) continue;
			key2 = jQuery(input[0]).closest(".leform-properties-item").attr("data-id");
			if (typeof type == typeof undefined) continue;
			if ((leform_meta["settings"][key2]).hasOwnProperty('group') && leform_meta["settings"][key2]['group'] == 'style') {
				if (input.length > 1) {
					jQuery(input).each(function () {
						if (jQuery(this).is(":checked")) {
							style_options[key] = jQuery(this).val();
							return false;
						}
					});
				} else if (input.length > 0) {
					if (jQuery(input).is(":checked")) style_options[key] = "on";
					else style_options[key] = jQuery(input).val();
				}
			}
		}
	}
	var post_data = { "action": "leform-style-save", "id": jQuery("#leform-style-id").val(), "name": leform_encode64(jQuery("#leform-style-name").val()), "options": leform_encode64(JSON.stringify(style_options)), "form-name": leform_encode64(leform_form_options['name']) };
	jQuery.ajax({
		type: "POST",
		url: leform_ajax_handler,
		data: post_data,
		success: function (return_data) {
			try {
				var data;
				if (typeof return_data == 'object') data = return_data;
				else data = jQuery.parseJSON(return_data);
				if (data.status == "OK") {
					leform_styles = data.styles;
					var html = leform_styles_html();
					jQuery(".leform-styles-select-container").html(html);
					leform_global_message_show("success", data.message);
				} else if (data.status == "ERROR") {
					leform_global_message_show("danger", data.message);
				} else {
					leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
				}
			} catch (error) {
				console.log(error);
				leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
			}
			jQuery(_object).find("i").attr("class", icon);
			leform_sending = false;
			leform_dialog_close();
		},
		error: function (XMLHttpRequest, textStatus, errorThrown) {
			jQuery(_object).find("i").attr("class", icon);
			leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
			leform_sending = false;
			leform_dialog_close();
		}
	});
	return false;
}
function leform_styles_load(_object) {
	var style_id = jQuery(_object).val();
	if (style_id == "") return false;
	jQuery(_object).val("");
	leform_dialog_open({
		title: leform_esc_html__('Apply theme'),
		echo_html: function () {
			this.html("<div class='leform-dialog-message'>" + leform_esc_html__("Do you want to apply new theme?", "leform") + "<br />" + leform_esc_html__("Important! Existing style parameters will be overwritten by new ones.", "leform") + "</div>");
			this.show();
		},
		height: 240,
		ok_label: leform_esc_html__('Apply Theme'),
		ok_function: function (e) {
			_leform_styles_load(jQuery("#leform-dialog .leform-dialog-button-ok"), style_id);
		}
	});
}
function _leform_styles_load(_object, _style_id) {
	var input, key, key2, style_options = {};
	if (leform_element_properties_active == null) return false;
	var type = jQuery(leform_element_properties_active).attr("data-type");
	if (typeof type == undefined || type != "settings") return false;
	if (leform_sending) return false;
	leform_sending = true;
	var icon = jQuery(_object).find("i").attr("class");
	jQuery(_object).find("i").attr("class", "fas fa-spinner fa-spin");

	var post_data = { "action": "leform-style-load", "id": _style_id };
	jQuery.ajax({
		type: "POST",
		url: leform_ajax_handler,
		data: post_data,
		success: function (return_data) {
			try {
				var data;
				if (typeof return_data == 'object') data = return_data;
				else data = jQuery.parseJSON(return_data);
				if (data.status == "OK") {
					jQuery(".leform-color").minicolors("destroy");
					for (var key in data.options) {
						if (data.options.hasOwnProperty(key)) {
							input = jQuery("[name='leform-" + key + "']");
							if (input.length == 0) continue;
							key2 = jQuery(input[0]).closest(".leform-properties-item").attr("data-id");
							if (typeof type == typeof undefined) continue;
							if ((leform_meta["settings"][key2]).hasOwnProperty('group') && leform_meta["settings"][key2]['group'] == 'style') {
								jQuery(input).each(function () {
									var input_type = jQuery(this).attr("type");
									var input_value = jQuery(this).val();
									if (typeof input_type !== typeof undefined) {
										if (input_type == "radio") {
											if (input_value == (data.options)[key]) jQuery(this).prop("checked", true);
											else jQuery(this).prop("checked", false);
										} else if (input_type == "checkbox") {
											if ((data.options)[key] == "on") jQuery(this).prop("checked", true);
											else jQuery(this).prop("checked", false);
										} else jQuery(this).val((data.options)[key]);
									} else jQuery(this).val((data.options)[key]);
								});
							}
						}
					}
					jQuery(".leform-color").minicolors({
						format: 'rgb',
						opacity: true,
						change: function (value, opacity) {
							leform_properties_change();
						}
					});
					leform_global_message_show("success", data.message);
				} else if (data.status == "ERROR") {
					leform_global_message_show("danger", data.message);
				} else {
					leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
				}
			} catch (error) {
				console.log(error);
				leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
			}
			jQuery(_object).find("i").attr("class", icon);
			leform_sending = false;
			leform_dialog_close();
		},
		error: function (XMLHttpRequest, textStatus, errorThrown) {
			jQuery(_object).find("i").attr("class", icon);
			leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
			leform_sending = false;
			leform_dialog_close();
		}
	});
	return false;
}

function leform_properties_save() {
	var properties, logic, attachments, input, page_i, temp, id;
	if (leform_element_properties_active == null) return false;
	var type = jQuery(leform_element_properties_active).attr("data-type");
	if (typeof type == undefined || type == "") return false;

	jQuery("#leform-element-properties .leform-admin-popup-buttons .leform-admin-button").find("i").attr("class", "fas fa-spin fa-spinner");
	if (type == "settings") {
		properties = leform_form_options;
	} else if (type == "page" || type == "page-confirmation") {
		id = jQuery(leform_element_properties_active).closest("li").attr("data-id");
		properties = null;
		for (var i = 0; i < leform_form_pages.length; i++) {
			if (leform_form_pages[i] != null && leform_form_pages[i]["id"] == id) {
				page_i = i;
				properties = leform_form_pages[i];
				break;
			}
		}
	} else {
		i = jQuery(leform_element_properties_active).attr("id");
		i = i.replace("leform-element-", "");
		properties = leform_form_elements[i];
	}
	for (var key in properties) {
		if (properties.hasOwnProperty(key)) {
			input = jQuery("[name='leform-" + key + "']");
			if (key == "personal-keys") {
				properties[key] = new Array();
				jQuery(input).each(function () {
					if (jQuery(this).is(":checked")) {
						properties[key].push(parseInt(jQuery(this).val(), 10));
					}
				});
			} else if (key === "form-background-first-page") {
				let backgroundValues = {
					file: jQuery(input).val(),
				};

				["top", "bottom", "left", "right"].forEach((direction) => {
					backgroundValues[direction] = jQuery(input)
						.closest(`input[name=leform-${key}-${direction}]`)
						.val();
				});

				properties[key] = JSON.stringify(backgroundValues);
			} else if (key === "email-on-form-submition") {
				let emailsList = [];
				jQuery(input).each((index, element) => {
					let value = element.value.trim();
					if (value !== "") {
						emailsList.push(element.value);
					}
				});
				properties[key] = JSON.stringify(emailsList);
			} else if (key === "fields") {
				const fieldsContainer = input[0];
				const fields = fieldsContainer.querySelectorAll(".field");
				const fieldValues = [];

				for (const field of fields) {
					const fieldValue = {};
					const fieldType = field
						.querySelector(".type-select select");

					if (!fieldType.value) {
						continue;
					}

					const allFieldSettings = field
						.querySelectorAll("div[data-setting]:not(.hidden)");

					fieldValue.type = fieldType.value;
					for (const fieldSetting of allFieldSettings) {
						const setting = fieldSetting.dataset.setting;
						const inputType = fieldSetting.dataset.inputType;
						let inputValue = null;

						switch (inputType) {
							case "text":
								inputValue = fieldSetting
									.querySelector("input")
									?.value;
								break;
							case "textarea":
								inputValue = fieldSetting
									.querySelector("textarea")
									?.value;
								break;
							case "options":
								const options = fieldSetting
									.querySelectorAll(".option input[type='text']");

								const defaultCheckboxes = fieldSetting
									.querySelectorAll(".option input[type='radio']");
								fieldValue["defaultValue"] = null;

								for (let i = 0; i < defaultCheckboxes.length; i++) {
									if (defaultCheckboxes[i].checked) {
										fieldValue["defaultValue"] = i;
										break;
									}
								}

								inputValue = [...options]
									.map((option) => option.value)
									.filter((value) => value !== "");

								break;
							case "starCount":
								inputValue = fieldSetting
									.querySelector("select")
									?.value;
								break;
						}

						fieldValue[setting] = inputValue;
					}
					fieldValues.push(fieldValue);
				}

				properties[key] = fieldValues;
			} else if (key === "email-on-form-submition") {
				let emailsList = [];
				jQuery(input).each((index, element) => {
					let value = element.value.trim();
					if (value !== "") {
						emailsList.push(element.value);
					}
				});
				properties[key] = JSON.stringify(emailsList);
			} else if (key === "xml-field-names") {
				const fieldNames = (typeof properties[key] === "string")
					? JSON.parse(properties[key])
					: JSON.parse(JSON.stringify(properties[key]));
				JSON.parse(JSON.stringify(properties[key]));
				const newFieldNames = {};
				const activeFieldNames = {};
				jQuery(input).each((index, element) => {
					const field = element.value;
					const value = element
						.parentElement
						.querySelector("input[name='value']")
						.value
						.trim();
					const active = element
						.parentElement
						.querySelector("input[name='active']");
					if (value) {
						newFieldNames[field] = value;
					}
					if (active) {
						activeFieldNames[field] = active.checked ? 'on' : 'off';
					}
				});
				properties[key] = JSON.stringify(newFieldNames);
				properties[`${key}-active`] = JSON.stringify(activeFieldNames);
			} else if (key === "custom-xml-fields") {
				const customFieldsList = [];
				jQuery(input).each((index, element) => {
					const parent = element.parentElement;
					const name = parent
						.querySelector("input[name='name']")
						.value
						.trim();
					const value = parent
						.querySelector("input[name='value']")
						.value
						.trim();
					const regex = new RegExp('^(?!xml|Xml|xMl|xmL|XMl|xML|XmL|XML)[A-Za-z_][A-Za-z0-9-_.]*$');
					if (name !== "" && regex.test(name)) {
						customFieldsList.push({ name, value });
					}
				});
				properties[key] = JSON.stringify(customFieldsList);
			} else if (key === "expressions") {
				let expressions = [];
				jQuery(input).find(".expression").each((index, element) => {
					const id = element
						.querySelector("[name='id']")
						.value;
					const name = element
						.querySelector("[name='name']")
						.value;
					const expression = element
						.querySelector("[name='expression']")
						.value;
					const defaultValue = element
						.querySelector("[name='default']")
						.value;
					const decimalDigits = element
						.querySelector("[name='decimal-digits']")
						.value;

					let value = {
						id,
						name,
						expression,
						default: defaultValue,
						decimalDigits
					};

					expressions.push(value);
				});
				properties[key] = expressions;
			} else if (input.length > 1) {
				jQuery(input).each(function () {
					if (jQuery(this).is(":checked")) {
						properties[key] = jQuery(this).val();
						return false;
					}
				});
			} else if (input.length > 0) {
				if (
					jQuery(input).hasClass("leform-tinymce") &&
					typeof wp != 'undefined' &&
					wp.hasOwnProperty('editor') &&
					typeof wp.editor.initialize == 'function'
				) {
					properties[key] = wp.editor.getContent(jQuery(input).attr("id"));
				} else if (jQuery(input).is(":checked")) {
					properties[key] = "on";
				} else {
					properties[key] = jQuery(input).val();
				}
			}
		}
	}

	if (properties.hasOwnProperty("css")) {
		properties["css"] = new Array();
		jQuery(".leform-properties-content-css .leform-properties-sub-item").each(function () {
			const cssProperty = {
				"selector": jQuery(this).find(".leform-properties-sub-item-body select").val(),
				"css": jQuery(this).find(".leform-properties-sub-item-body textarea").val()
			}

			if (jQuery(this).find(".leform-properties-sub-item-body input[name='type']").val() === "theme-css") {
				cssProperty["type"] = "theme-css";
			}

			(properties["css"]).push(cssProperty);
		});
	}
	if (properties.hasOwnProperty("validators")) {
		properties["validators"] = new Array();
		jQuery(".leform-properties-content-validators .leform-properties-sub-item").each(function () {
			var validator_type = jQuery(this).attr("data-type");
			if (leform_validators.hasOwnProperty(validator_type)) {
				var validator = { "type": validator_type, "properties": {} };
				for (var key in leform_validators[validator_type]["properties"]) {
					if (leform_validators[validator_type]["properties"].hasOwnProperty(key)) {
						if (jQuery(this).find("[name=leform-validators-" + key + "]").length > 0) {
							if (jQuery(this).find("[name=leform-validators-" + key + "]").is(":checked")) validator["properties"][key] = "on";
							else validator["properties"][key] = jQuery(this).find("[name=leform-validators-" + key + "]").val();
						}
					}
				}
				(properties["validators"]).push(validator);
			}
		});
	}
	if (properties.hasOwnProperty("filters")) {
		properties["filters"] = new Array();
		jQuery(".leform-properties-content-filters .leform-properties-sub-item").each(function () {
			var filter_type = jQuery(this).attr("data-type");
			if (leform_filters.hasOwnProperty(filter_type)) {
				var filter = { "type": filter_type, "properties": {} };
				for (var key in leform_filters[filter_type]["properties"]) {
					if (leform_filters[filter_type]["properties"].hasOwnProperty(key)) {
						if (jQuery(this).find("[name=leform-filters-" + key + "]").length > 0) {
							if (jQuery(this).find("[name=leform-filters-" + key + "]").is(":checked")) filter["properties"][key] = "on";
							else filter["properties"][key] = jQuery(this).find("[name=leform-filters-" + key + "]").val();
						}
					}
				}
				(properties["filters"]).push(filter);
			}
		});
	}
	if (properties.hasOwnProperty("options")) {
		properties["options"] = new Array();
		jQuery(".leform-properties-options-container .leform-properties-options-item").each(function () {
			var selected = "off";
			if (jQuery(this).hasClass("leform-properties-options-item-default")) selected = "on";
			(properties["options"]).push({ "default": selected, "label": jQuery(this).find(".leform-properties-options-label").val(), "value": jQuery(this).find(".leform-properties-options-value").val(), "image": jQuery(this).find(".leform-properties-options-image").val() });
		});
	}
	if (properties.hasOwnProperty("left")) {
		properties["left"] = new Array();
		jQuery(
			".leform-properties-item[data-id='left'] .leform-properties-options-container .leform-properties-options-item"
		).each(function () {
			var selected = "off";
			if (jQuery(this).hasClass("leform-properties-options-item-default")) selected = "on";
			(properties["left"]).push({ "default": selected, "label": jQuery(this).find(".leform-properties-options-label").val(), "value": jQuery(this).find(".leform-properties-options-value").val(), "image": jQuery(this).find(".leform-properties-options-image").val() });
		});
	}
	if (properties.hasOwnProperty("top")) {
		properties["top"] = new Array();
		jQuery(
			".leform-properties-item[data-id='top'] .leform-properties-options-container .leform-properties-options-item"
		).each(function () {
			var selected = "off";
			if (jQuery(this).hasClass("leform-properties-options-item-default")) selected = "on";
			(properties["top"]).push({ "default": selected, "label": jQuery(this).find(".leform-properties-options-label").val(), "value": jQuery(this).find(".leform-properties-options-value").val(), "image": jQuery(this).find(".leform-properties-options-image").val() });
		});
	}
	if (properties.hasOwnProperty("confirmations")) {
		properties["confirmations"] = new Array();
		jQuery(".leform-properties-content-confirmations .leform-properties-sub-item").each(function () {
			logic = {
				"action": jQuery(this).find("[name='leform-confirmations-logic-action']").val(),
				"operator": jQuery(this).find("[name='leform-confirmations-logic-operator']").val(),
				"rules": new Array()
			};
			jQuery(this).find(".leform-properties-logic-rule").each(function () {
				(logic["rules"]).push({ "field": parseInt(jQuery(this).find(".leform-properties-logic-rule-field").val(), 10), "rule": jQuery(this).find(".leform-properties-logic-rule-rule").val(), "token": jQuery(this).find(".leform-properties-logic-rule-token").val() });
			});
			var temp = "";
			input = jQuery(this).find("[name='leform-confirmations-message']");
			if (jQuery(input).hasClass("leform-tinymce") && typeof wp != 'undefined' && wp.hasOwnProperty('editor') && typeof wp.editor.initialize == 'function') {
				temp = wp.editor.getContent(jQuery(input).attr("id"));
			} else temp = jQuery(input).val();
			(properties["confirmations"]).push({
				"name": jQuery(this).find("[name='leform-confirmations-name']").val(),
				"type": jQuery(this).find("[name='leform-confirmations-type']").val(),
				"message": temp,
				"url": jQuery(this).find("[name='leform-confirmations-url']").val(),
				"delay": jQuery(this).find("[name='leform-confirmations-delay']").val(),
				"payment-gateway": jQuery(this).find("[name='leform-confirmations-payment-gateway']").val(),
				"reset-form": jQuery(this).find("[name='leform-confirmations-reset-form']").is(":checked") ? "on" : "off",
				"logic-enable": jQuery(this).find("[name='leform-confirmations-logic-enable']").is(":checked") ? "on" : "off",
				"logic": logic
			});
		});
	}
	if (properties.hasOwnProperty("notifications")) {
		properties["notifications"] = new Array();
		jQuery(".leform-properties-content-notifications .leform-properties-sub-item").each(function () {
			logic = {
				"action": jQuery(this).find("[name='leform-notifications-logic-action']").val(),
				"operator": jQuery(this).find("[name='leform-notifications-logic-operator']").val(),
				"rules": new Array()
			};
			jQuery(this).find(".leform-properties-logic-rule").each(function () {
				(logic["rules"]).push({ "field": parseInt(jQuery(this).find(".leform-properties-logic-rule-field").val(), 10), "rule": jQuery(this).find(".leform-properties-logic-rule-rule").val(), "token": jQuery(this).find(".leform-properties-logic-rule-token").val() });
			});
			attachments = new Array();
			jQuery(this).find(".leform-properties-attachment").each(function () {
				attachments.push({ "source": jQuery(this).find(".leform-properties-attachment-source").val(), "token": jQuery(this).find(".leform-properties-attachment-token").val() });
			});

			var temp = "";
			input = jQuery(this).find("[name='leform-notifications-message']");
			if (jQuery(input).hasClass("leform-tinymce") && typeof wp != 'undefined' && wp.hasOwnProperty('editor') && typeof wp.editor.initialize == 'function') {
				temp = wp.editor.getContent(jQuery(input).attr("id"));
			} else temp = jQuery(input).val();
			(properties["notifications"]).push({
				"name": jQuery(this).find("[name='leform-notifications-name']").val(),
				"enabled": jQuery(this).find("[name='leform-notifications-enabled']").is(":checked") ? "on" : "off",
				"action": jQuery(this).find("[name='leform-notifications-action']").val(),
				"recipient-email": jQuery(this).find("[name='leform-notifications-recipient-email']").val(),
				"subject": jQuery(this).find("[name='leform-notifications-subject']").val(),
				"message": temp,
				"attachments": attachments,
				"reply-email": jQuery(this).find("[name='leform-notifications-reply-email']").val(),
				"from-email": jQuery(this).find("[name='leform-notifications-from-email']").val(),
				"from-name": jQuery(this).find("[name='leform-notifications-from-name']").val(),
				"logic-enable": jQuery(this).find("[name='leform-notifications-logic-enable']").is(":checked") ? "on" : "off",
				"logic": logic
			});
		});
	}
	if (properties.hasOwnProperty("math-expressions")) {
		properties["math-expressions"] = new Array();
		jQuery(".leform-properties-content-math-expressions .leform-properties-sub-item").each(function () {
			(properties["math-expressions"]).push({
				"id": jQuery(this).find("[name='leform-math-id']").val(),
				"name": jQuery(this).find("[name='leform-math-name']").val(),
				"expression": jQuery(this).find("[name='leform-math-expression']").val(),
				"decimal-digits": parseInt(jQuery(this).find("[name='leform-math-decimal-digits']").val(), 10),
				"default": jQuery(this).find("[name='leform-math-default']").val()
			});
		});
	}
	var integrations;
	if (properties.hasOwnProperty("integrations")) {
		integrations = new Array();
		jQuery(".leform-properties-content-integrations .leform-properties-sub-item").each(function () {
			logic = {
				"action": jQuery(this).find("[name='leform-integrations-logic-action']").val(),
				"operator": jQuery(this).find("[name='leform-integrations-logic-operator']").val(),
				"rules": new Array()
			};
			jQuery(this).find(".leform-properties-logic-rule").each(function () {
				(logic["rules"]).push({ "field": parseInt(jQuery(this).find(".leform-properties-logic-rule-field").val(), 10), "rule": jQuery(this).find(".leform-properties-logic-rule-rule").val(), "token": jQuery(this).find(".leform-properties-logic-rule-token").val() });
			});
			var content = jQuery(this).find(".leform-integrations-content");
			var data = {};
			var idx = jQuery(this).find("[name='leform-integrations-idx']").val();
			var data_loaded = jQuery(this).attr("data-loaded");
			if (properties["integrations"][idx] !== void 0 && data_loaded == "off") {
				data = properties["integrations"][idx]["data"];
			} else {
				jQuery(content).find("input, select, textarea").each(function () {
					if (jQuery(this).attr("data-skip") == "on") return;
					if (jQuery(this).attr("data-custom") == "on") return;
					var input_type = jQuery(this).attr("type");
					var name = jQuery(this).attr("name");
					var include_empty = jQuery(this).attr("data-empty");
					var name_parts = name.split(/(.*?)\[(.*?)\]/);
					if (name_parts.length > 2) {
						if (!data.hasOwnProperty(name_parts[1])) data[name_parts[1]] = {};
						if (input_type == "checkbox") {
							if (jQuery(this).is(":checked")) (data[name_parts[1]])[name_parts[2]] = jQuery(this).val();
						} else if (jQuery(this).val().length > 0 || include_empty == "on") (data[name_parts[1]])[name_parts[2]] = jQuery(this).val();
					} else {
						if (input_type == "checkbox") {
							if (jQuery(this).is(":checked")) data[name_parts[0]] = "on";
							else data[name_parts[0]] = "off";
						} else if (jQuery(this).val().length > 0 || include_empty == "on") data[name_parts[0]] = jQuery(this).val();
					}
				});
				jQuery(content).find(".leform-integrations-custom").each(function () {
					var name, value;
					var param_names = jQuery(this).attr("data-names");
					var param_values = jQuery(this).attr("data-values");
					var param_all = jQuery(this).attr("data-all");
					if (param_all != "on") param_all = "off";
					data[param_names] = new Array();
					data[param_values] = new Array();
					var names = jQuery(this).find("input.leform-integrations-custom-name");
					var values = jQuery(this).find("input.leform-integrations-custom-value");
					for (var j = 0; j < names.length; j++) {
						name = jQuery(names[j]).val();
						value = jQuery(values[j]).val();
						if (name.length > 0 && (value.length > 0 || param_all == "on")) {
							(data[param_names]).push(name);
							(data[param_values]).push(value);
						}
					}
				});
			}
			integrations.push({
				"name": jQuery(this).find("[name='leform-integrations-name']").val(),
				"enabled": jQuery(this).find("[name='leform-integrations-enabled']").is(":checked") ? "on" : "off",
				"action": jQuery(this).find("[name='leform-integrations-action']").val(),
				"provider": jQuery(this).find("[name='leform-integrations-provider']").val(),
				"data": data,
				"logic-enable": jQuery(this).find("[name='leform-integrations-logic-enable']").is(":checked") ? "on" : "off",
				"logic": logic
			});
		});
		properties["integrations"] = integrations;
	}
	if (properties.hasOwnProperty("payment-gateways")) {
		integrations = new Array();
		jQuery(".leform-properties-content-payment-gateways .leform-properties-sub-item").each(function () {
			var content = jQuery(this).find(".leform-payment-gateways-content");
			var data = {};
			var idx = jQuery(this).find("[name='leform-payment-gateways-idx']").val();
			var data_loaded = jQuery(this).attr("data-loaded");
			if (properties["payment-gateways"][idx] !== void 0 && data_loaded == "off") {
				data = properties["payment-gateways"][idx]["data"];
			} else {
				jQuery(content).find("input, select, textarea").each(function () {
					if (jQuery(this).attr("data-skip") == "on") return;
					var input_type = jQuery(this).attr("type");
					var name = jQuery(this).attr("name");
					if (name) {
						var name_parts = name.split(/(.*?)\[(.*?)\]/);
						if (name_parts.length > 2) {
							if (!data.hasOwnProperty(name_parts[1])) data[name_parts[1]] = {};
							if (input_type == "checkbox") {
								if (jQuery(this).is(":checked")) (data[name_parts[1]])[name_parts[2]] = jQuery(this).val();
							} else if (jQuery(this).val().length > 0) (data[name_parts[1]])[name_parts[2]] = jQuery(this).val();
						} else {
							if (input_type == "checkbox") {
								if (jQuery(this).is(":checked")) data[name_parts[0]] = jQuery(this).val();
							} else if (jQuery(this).val().length > 0) data[name_parts[0]] = jQuery(this).val();
						}
					}
				});
			}
			integrations.push({
				"id": jQuery(this).find("[name='leform-payment-gateways-id']").val(),
				"name": jQuery(this).find("[name='leform-payment-gateways-name']").val(),
				"provider": jQuery(this).find("[name='leform-payment-gateways-provider']").val(),
				"data": data
			});
		});
		properties["payment-gateways"] = integrations;
	}
	if (properties.hasOwnProperty("logic")) {
		properties["logic"] = {};
		if (jQuery("#leform-logic-action").length > 0) properties["logic"]["action"] = jQuery("#leform-logic-action").val();
		else properties["logic"]["action"] = leform_meta[properties['type']]['logic']['values']['action'];
		if (jQuery("#leform-logic-operator").length > 0) properties["logic"]["operator"] = jQuery("#leform-logic-operator").val();
		else properties["logic"]["operator"] = leform_meta[properties['type']]['logic']['values']['operator'];
		properties["logic"]["rules"] = new Array();
		jQuery(".leform-properties-logic-rules .leform-properties-logic-rule").each(function () {
			(properties["logic"]["rules"]).push({ "field": parseInt(jQuery(this).find(".leform-properties-logic-rule-field").val(), 10), "rule": jQuery(this).find(".leform-properties-logic-rule-rule").val(), "token": jQuery(this).find(".leform-properties-logic-rule-token").val() });
		});
	}
	if (type == "settings") {
		leform_form_options = properties;
	} else if (type == "page" || type == "page-confirmation") {
		leform_form_pages[page_i] = properties;
		jQuery(".leform-pages-bar-item, .leform-pages-bar-item-confirmation").each(function () {
			var page_id = jQuery(this).attr("data-id");
			if (page_id == properties['id']) jQuery(this).find("label").text(properties['name']);
		});
	} else {
		leform_form_elements[i] = properties;
	}
	leform_form_changed = true;
	_leform_properties_close();
	leform_build();


	const customCssElement = document.querySelector("style#custom-css");
	customCssElement.textContent = leform_form_options["custom-css"];




	return false;
}
function leform_properties_close() {
	if (leform_element_properties_data_changed) {
		leform_dialog_open({
			echo_html: function () {
				this.html("<div class='leform-dialog-message'>" + leform_esc_html__("Seems you didn't save changes. Are you sure, you want to close Properties?", "leform") + "</div>");
				this.show();
			},
			ok_label: leform_esc_html__('Close Properties'),
			ok_function: function (e) {
				_leform_properties_close();
				leform_dialog_close();
			}
		});
	} else _leform_properties_close();
	return false;
}
function _leform_properties_close() {
	leform_element_properties_data_changed = false;
	leform_element_properties_active = null;
	jQuery("#leform-element-properties-overlay").fadeOut(300);
	jQuery("#leform-element-properties").fadeOut(300, function () {
		if (typeof wp != 'undefined' && wp.hasOwnProperty('editor') && typeof wp.editor.initialize == 'function') {
			jQuery(".leform-tinymce").each(function () {
				wp.editor.remove(jQuery(this).attr("id"));
			});
		}
		jQuery("#leform-element-properties .leform-color").minicolors("destroy");
		jQuery("#leform-element-properties .leform-admin-popup-content-form").html("");
		jQuery("#leform-element-properties .leform-admin-popup-buttons .leform-admin-button").find("i").attr("class", "fas fa-check");
		jQuery("body").removeClass("leform-static");
	});
}
function leform_properties_change() {
	if (leform_element_properties_active == null) return false;
	leform_element_properties_data_changed = true;
	leform_properties_visible_conditions(leform_element_properties_active);
	return false;
}
function leform_properties_visible_conditions(_object) {
	var type = jQuery(_object).attr("data-type");
	var input;
	if (typeof type == undefined || type == "") return false;
	var visible, value = "";
	for (var key in leform_meta[type]) {
		if (leform_meta[type].hasOwnProperty(key)) {
			if (leform_meta[type][key].hasOwnProperty('visible')) {
				visible = Object.keys(leform_meta[type][key]['visible']).every(condition_key => {
					var cond_visible = false;
					if (leform_meta[type][key]['visible'].hasOwnProperty(condition_key)) {
						input = jQuery("[name='leform-" + condition_key + "']");
						if (input.length > 1) {
							jQuery(input).each(function () {
								if (jQuery(this).is(":checked")) {
									value = jQuery(this).val();
									return false;
								}
							});
						} else if (jQuery(input).is(":checked")) value = "on";
						else value = jQuery(input).val();
						if (Array.isArray(leform_meta[type][key]['visible'][condition_key])) {
							if (jQuery.inArray(value, leform_meta[type][key]['visible'][condition_key]) != -1) cond_visible = true;
						} else if (value == leform_meta[type][key]['visible'][condition_key]) cond_visible = true;
					}
					return cond_visible;
				});

				if (visible) jQuery(".leform-properties-item[data-id='" + key + "']").fadeIn(300);
				else jQuery(".leform-properties-item[data-id='" + key + "']").fadeOut(300);
			}
		}
	}
}
function leform_properties_mask_preset_changed(_object) {
	var preset = jQuery(_object).val();
	var mask_object = jQuery(_object).closest(".leform-properties-content").find("input");
	if (preset == "custom") {
		jQuery(mask_object).removeAttr("readonly");
		jQuery(mask_object).focus();
	} else {
		jQuery(mask_object).val(preset);
		jQuery(mask_object).attr("readonly", "readonly");
	}
	return false;
}
function leform_properties_options_delete(_object) {
	leform_dialog_open({
		echo_html: function () {
			this.html("<div class='leform-dialog-message'>" + leform_esc_html__('Please confirm that you want to delete the item.') + "</div>");
			this.show();
		},
		ok_label: leform_esc_html__('Delete'),
		ok_function: function (e) {
			jQuery(_object).closest(".leform-properties-options-item").fadeOut(300, function () {
				jQuery(this).remove();
			});
			leform_element_properties_data_changed = true;
			leform_dialog_close();
		}
	});
	return false;
}
function leform_properties_options_copy(_object) {
	var option = jQuery(_object).closest(".leform-properties-options-item").clone();
	jQuery(option).removeClass("leform-properties-options-item-default");
	jQuery(_object).closest(".leform-properties-options-item").after(option);
	jQuery(option).find(".leform-image-url span").on("click", function (e) {
		e.preventDefault();
		var input = jQuery(this).parent().children("input");
		var media_frame = wp.media({
			title: 'Select Image',
			library: {
				type: 'image'
			},
			multiple: false
		});
		media_frame.on("select", function () {
			var attachment = media_frame.state().get("selection").first();
			jQuery(input).val(attachment.attributes.url);
		});
		media_frame.open();
	});

	leform_element_properties_data_changed = true;
	return false;
}
function leform_properties_options_default(_object) {
	var multi = jQuery(_object).closest(".leform-properties-options-container").attr("data-multi");
	var option = jQuery(_object).closest(".leform-properties-options-item");
	if (jQuery(option).hasClass("leform-properties-options-item-default")) {
		jQuery(option).removeClass("leform-properties-options-item-default");
	} else {
		if (multi != "on") jQuery(_object).closest(".leform-properties-options-container").find(".leform-properties-options-item").removeClass("leform-properties-options-item-default");
		jQuery(option).addClass("leform-properties-options-item-default");
	}
	leform_element_properties_data_changed = true;
	return false;
}



function leform_properties_image_options_new(_object) {
	const html = `
        <div class='leform-properties-options-item'>
            <div class='leform-properties-options-table'>
                <div class='leform-image-url'>
                    <input class='leform-properties-options-image' type='text' value='' placeholder='URL' readonly>
                    <input type="file" style="display: none;" accept="image/*" />
                    <span onclick="selectImageOptionsSelectHandler(this)"><i class='far fa-image'></i></span>
                </div>
                <div>
                    <input class='leform-properties-options-label' type='text' value='' placeholder='${leform_esc_html__("Label")
		}'>
                </div>
                <div>
                    <input class='leform-properties-options-value' type='text' value='' placeholder='${leform_esc_html__("Value")
		}'>
                </div>
                <div>
                    <span onclick='return leform_properties_options_default(this);' title='${leform_esc_html__("Set the option as a default value")
		}'> <i class='fas fa-check'></i></span>
                    <span onclick='return leform_properties_options_new(this);' title='${leform_esc_html__("Add the option after this one")
		}'> <i class='fas fa-plus'></i> </span>
                    <span onclick='return leform_properties_options_copy(this);' title='${leform_esc_html__("Duplicate the option")
		}'><i class='far fa-copy'></i></span>
                    <span onclick='return leform_properties_options_delete(this);' title='${leform_esc_html__("Delete the option")
		}'><i class='fas fa-trash-alt'></i></span>
                    <span title='${leform_esc_html__("Move the option")
		}'><i class='fas fa-arrows-alt leform-properties-options-item-handler'></i></span>
                </div>
            </div>
        </div>
    `;

	$(_object)
		.parents(".leform-properties-content")
		.find(".leform-properties-options-container")
		.append($.parseHTML(html))
	return false;
}

function selectImageOptionsSelectHandler(element) {
	const input = jQuery(element).parent().children("input[type='text']");
	const fileSelect = jQuery(element).parent().children("input[type='file']");

	fileSelect.click();

	fileSelect.on("change", (e) => {
		if (!e.target.files[0]) {
			return;
		}

		const file = e.target.files[0];

		const data = new FormData();
		data.append("form_id", jQuery("#leform-id").val());
		data.append("file", file);
		data.append("_token", jQuery("meta[name='csrf-token']").attr("content"));

		fetch("/forms/upload-select-image-file", { method: "POST", body: data })
			.then((response) => response.json())
			.then((response) => {
				if (response.status === "ERROR") {
					leform_global_message_show("danger", response.message);
					return;
				}

				jQuery(input).val(response.path);
			})
			.catch((error) => {
				console.log(error);
			})
			.finally(() => {
				fileSelect.off("change");
			});
	});
}



function leform_properties_options_new(_object) {
	var option;
	if (_object != null) {
		option = jQuery(_object).closest(".leform-properties-options-item").clone();
		jQuery(option).removeClass("leform-properties-options-item-default");
		jQuery(option).find("input").val("");
		jQuery(_object).closest(".leform-properties-options-item").after(option);
	} else {
		//option = jQuery(".leform-properties-options-container .leform-properties-options-item").first().clone();
		//jQuery(option).removeClass("leform-properties-options-item-default");
		//jQuery(option).find("input").val("");
		option = leform_properties_options_item_get("", "", false);
		jQuery(".leform-properties-options-container").append(option);
	}
	jQuery(option).find(".leform-image-url span").on("click", function (e) {
		e.preventDefault();
		var input = jQuery(this).parent().children("input");
		var media_frame = wp.media({
			title: 'Select Image',
			library: {
				type: 'image'
			},
			multiple: false
		});
		media_frame.on("select", function () {
			var attachment = media_frame.state().get("selection").first();
			jQuery(input).val(attachment.attributes.url);
		});
		media_frame.open();
	});
	leform_element_properties_data_changed = true;
	return false;
}
function leform_properties_options_item_get(_label, _value, _selected) {
	var html, selected = "";
	if (_selected) selected = " leform-properties-options-item-default";
	html = "<div class='leform-properties-options-item" + selected + "'><div class='leform-properties-options-table'><div><input class='leform-properties-options-label' type='text' value='" + leform_escape_html(_label) + "' placeholder='"
		+ leform_esc_html__("Label")
		+ "'></div><div><input class='leform-properties-options-value' type='text' value='" + leform_escape_html(_value) + "' placeholder='"
		+ leform_esc_html__("Value")
		+ "'></div><div><span onclick='return leform_properties_options_default(this);' title='"
		+ leform_esc_html__("Set the option as a default value")
		+ "'><i class='fas fa-check'></i></span><span onclick='return leform_properties_options_new(this);' title='"
		+ leform_esc_html__("Add the option after this one")
		+ "'><i class='fas fa-plus'></i></span><span onclick='return leform_properties_options_copy(this);' title='"
		+ leform_esc_html__("Duplicate the option")
		+ "'><i class='far fa-copy'></i></span><span onclick='return leform_properties_options_delete(this);' title='"
		+ leform_esc_html__("Delete the option")
		+ "'><i class='fas fa-trash-alt'></i></span><span title='"
		+ leform_esc_html__("Move the option")
		+ "'><i class='fas fa-arrows-alt leform-properties-options-item-handler'></i></span></div></div></div>";
	return html;
}
function leform_properties_imageselect_mode_set(_object) {
	var value = jQuery(_object).val();
	var options = jQuery(_object).closest(".leform-properties-item").parent().find(".leform-properties-options-container");
	if (value == 'radio') {
		jQuery(options).attr("data-multi", "off");
		var first_selected = jQuery(options).find(".leform-properties-options-item-default").first();
		jQuery(options).find(".leform-properties-options-item").removeClass("leform-properties-options-item-default");
		if (first_selected.length > 0) jQuery(first_selected).addClass("leform-properties-options-item-default");
	} else {
		jQuery(options).attr("data-multi", "on");
	}
}

function leform_properties_css_add(_type, _values) {
	var extra_class = "", html = "", tools = "";
	if (leform_meta[_type].hasOwnProperty("css")) {
		if (_values == null) {
			extra_class = " leform-properties-sub-item-new";
			leform_element_properties_data_changed = true;
		} else {
			extra_class = " leform-properties-sub-item-exist";
		}
		html += "<div class='leform-properties-sub-item" + extra_class + "'><div class='leform-properties-sub-item-header'><div class='leform-properties-sub-item-header-tools'><span onclick='return leform_properties_css_delete(this);'><i class='fas fa-trash-alt'></i></span><span onclick='return leform_properties_css_details_toggle(this);'><i class='fas fa-cog'></i></span></div><label></label></div><div class='leform-properties-sub-item-body'>"
			+ `
                <input
                    type="hidden"
                    name="type"
                    value="${_values?.type || ""}"
                />
            `
			+ "<div class='leform-properties-item'><div class='leform-properties-label'><label>"
			+ leform_esc_html__("Selector")
			+ "</label></div><div class='leform-properties-content'><select onchange='return leform_properties_css_selector_change(this);'><option value=''>"
			+ leform_esc_html__("Please select")
			+ "</option>";
		for (var key in leform_meta[_type]["css"]["selectors"]) {
			if (leform_meta[_type]["css"]["selectors"].hasOwnProperty(key)) {
				html += `
                    <option value='${key}'>
                        ${leform_esc_html__(leform_meta[_type]["css"]["selectors"][key]['label'])}
                    </option>
                `;
			}
		}
		tools = "<div class='leform-properties-css-toolbar'>"
			+ "<span onclick='return leform_properties_css_style_add(this);' data-css='background-color: ;' title='"
			+ leform_esc_html__('Background color')
			+ "'><i class='material-icons'>format_color_fill</i></span>"
			+ "<span onclick='return leform_properties_css_style_add(this);' data-css='background: url() top left no-repeat;' title='"
			+ leform_esc_html__('Background')
			+ "'><i class='material-icons'>wallpaper</i></span>"
			+ "<span onclick='return leform_properties_css_style_add(this);' data-css='border-color: ;' title='"
			+ leform_esc_html__('Border color')
			+ "'><i class='material-icons'>border_color</i></span>"
			+ "<span onclick='return leform_properties_css_style_add(this);' data-css='color: ;' title='"
			+ leform_esc_html__('Text color')
			+ "'><i class='material-icons'>format_color_text</i></span>"
			+ "<span onclick='return leform_properties_css_style_add(this);' data-css='padding: ;' title='"
			+ leform_esc_html__('Padding')
			+ "'><i class='fas fa-external-link-alt'></i></span>"
			+ "<span onclick='return leform_properties_css_style_add(this);' data-css='margin: ;' title='"
			+ leform_esc_html__('Margin')
			+ "'><i class='fas fa-external-link-alt'></i></span>"
			+ "<span onclick='return leform_properties_css_style_add(this);' data-css='border-radius: ;' title='"
			+ leform_esc_html__('Border radius')
			+ "'><i class='material-icons'>crop_free</i></span>"
			+ "<span onclick='return leform_properties_css_style_add(this);' data-css='font-size: ;' title='"
			+ leform_esc_html__('Font size')
			+ "'><i class='material-icons'>format_size</i></span>"
			+ "<span onclick='return leform_properties_css_style_add(this);' data-css='line-height: ;' title='"
			+ leform_esc_html__('Line height')
			+ "'><i class='material-icons'>format_line_spacing</i></span>"
			+ "<span onclick='return leform_properties_css_style_add(this);' data-css='font-weight: bold;' title='"
			+ leform_esc_html__('Bold')
			+ "'><i class='material-icons'>format_bold</i></span>"
			+ "<span onclick='return leform_properties_css_style_add(this);' data-css='text-decoration: underline;' title='"
			+ leform_esc_html__('Underline')
			+ "'><i class='material-icons'>format_underlined</i></span>"
			+ "<span onclick='return leform_properties_css_style_add(this);' data-css='text-transform: uppercase;' title='"
			+ leform_esc_html__('Uppercase')
			+ "'><i class='material-icons'>title</i></span>"
			+ "<span onclick='return leform_properties_css_style_add(this);' data-css='text-align: left;' title='"
			+ leform_esc_html__('Text align left')
			+ "'><i class='material-icons'>format_align_left</i></span>"
			+ "<span onclick='return leform_properties_css_style_add(this);' data-css='text-align: center;' title='"
			+ leform_esc_html__('Text align center')
			+ "'><i class='material-icons'>format_align_center</i></span>"
			+ "<span onclick='return leform_properties_css_style_add(this);' data-css='text-align: right;' title='"
			+ leform_esc_html__('Text align right')
			+ "'><i class='material-icons'>format_align_right</i></span>"
			+ "<span onclick='return leform_properties_css_style_add(this);' data-css='width: ;' title='"
			+ leform_esc_html__('Width')
			+ "'><i class='material-icons'>keyboard_tab</i></span>"
			+ "<span onclick='return leform_properties_css_style_add(this);' data-css='height: ;' title='"
			+ leform_esc_html__('Height')
			+ "'><i class='material-icons'>vertical_align_top</i></span>"
			+ "<span onclick='return leform_properties_css_style_add(this);' data-css='display: none;' title='"
			+ leform_esc_html__('Hide')
			+ "'><i class='material-icons'>visibility_off</i></span></div>";

		html += "</select></div></div><div class='leform-properties-item'><div class='leform-properties-label'><label>"
			+ leform_esc_html__("CSS")
			+ "</label></div><div class='leform-properties-content'><textarea></textarea>" + tools + "</div></div></div></div>";
		if (_values == null) {
			jQuery(".leform-properties-content-css .leform-properties-sub-item-body").slideUp(300);
		}
		jQuery(".leform-properties-content-css").append(html);
		if (_values != null) {
			jQuery(".leform-properties-content-css .leform-properties-sub-item:last").find(".leform-properties-sub-item-body select").val(_values["selector"]);
			if (_values["selector"] == "") {
				jQuery(".leform-properties-content-css .leform-properties-sub-item:last").find(".leform-properties-sub-item-header label").html("");
			} else {
				jQuery(".leform-properties-content-css .leform-properties-sub-item:last").find(".leform-properties-sub-item-header label").html(jQuery(".leform-properties-content-css .leform-properties-sub-item:last").find(".leform-properties-sub-item-body select option:selected").text());
			}
			jQuery(".leform-properties-content-css .leform-properties-sub-item:last").find(".leform-properties-sub-item-body textarea").val(_values["css"]);
		}
		jQuery(".leform-properties-sub-item-new").slideDown(300);
		jQuery(".leform-properties-sub-item-new").removeClass("leform-properties-sub-item-new");
	}
	return false;
}
function leform_properties_css_style_add(_object) {
	var value = jQuery(_object).closest(".leform-properties-content").find("textarea").val();
	if (value != "") value += "\r\n";
	value += jQuery(_object).attr("data-css");
	jQuery(_object).closest(".leform-properties-content").find("textarea").val(value);
	return false;
}
function leform_properties_css_selector_change(_object) {
	if (jQuery(_object).val() == "") jQuery(_object).closest(".leform-properties-sub-item").find(".leform-properties-sub-item-header label").html("");
	else jQuery(_object).closest(".leform-properties-sub-item").find(".leform-properties-sub-item-header label").html(jQuery(_object).find("option:selected").text());
	return false;
}
function leform_properties_css_details_toggle(_object) {
	jQuery(_object).closest(".leform-properties-sub-item").addClass("leform-freeze");
	jQuery(".leform-properties-content-css .leform-properties-sub-item").each(function () {
		if (!jQuery(this).hasClass("leform-freeze")) jQuery(this).find(".leform-properties-sub-item-body").slideUp(300);
	});
	jQuery(_object).closest(".leform-properties-sub-item").removeClass("leform-freeze");
	jQuery(_object).closest(".leform-properties-sub-item").find(".leform-properties-sub-item-body").slideToggle(300);
	return false;
}
function leform_properties_css_delete(_object) {
	leform_dialog_open({
		echo_html: function () {
			this.html("<div class='leform-dialog-message'>" + leform_esc_html__('Please confirm that you want to delete the item.') + "</div>");
			this.show();
		},
		ok_label: leform_esc_html__('Delete'),
		ok_function: function (e) {
			jQuery(_object).closest(".leform-properties-sub-item").slideUp(300, function () {
				jQuery(this).remove();
			});
			leform_element_properties_data_changed = true;
			leform_dialog_close();
		}
	});
	return false;
}
function leform_properties_validators_add(_field_id, _type, _validator, _values) {
	var extra_class = "", html = "", tooltip_html, selected, options, property_value;
	var seq = 0, last;
	last = jQuery(".leform-properties-content-validators .leform-properties-sub-item").last();
	if (jQuery(last).length) seq = parseInt(jQuery(last).attr("data-seq"), 10) + 1;
	if (leform_meta[_type].hasOwnProperty("validators") && leform_validators.hasOwnProperty(_validator)) {
		if (_values == null) {
			extra_class = " leform-properties-sub-item-new";
			leform_element_properties_data_changed = true;
		} else extra_class = " leform-properties-sub-item-exist";
		html += "<div class='leform-properties-sub-item" + extra_class + "' data-type='" + _validator + "' data-seq='" + seq + "'><div class='leform-properties-sub-item-header'><div class='leform-properties-sub-item-header-tools'><span onclick='return leform_properties_validators_delete(this);'><i class='fas fa-trash-alt'></i></span><span onclick='return leform_properties_validators_details_toggle(this);'><i class='fas fa-cog'></i></span></div><label>"
			+ leform_esc_html__(leform_validators[_validator]["label"])
			+ "</label></div><div class='leform-properties-sub-item-body'>";
		for (var key in leform_validators[_validator]["properties"]) {
			if (leform_validators[_validator]["properties"].hasOwnProperty(key)) {
				tooltip_html = "";
				if (leform_validators[_validator]["properties"][key].hasOwnProperty('tooltip')) {
					tooltip_html = "<i class='fas fa-question-circle leform-tooltip-anchor'></i><div class='leform-tooltip-content'>"
						+ leform_esc_html__(leform_validators[_validator]["properties"][key]['tooltip'])
						+ "</div>";
				}
				property_value = "";
				if (_values != null && _values.hasOwnProperty("properties") && _values["properties"].hasOwnProperty(key)) property_value = _values["properties"][key];
				switch (leform_validators[_validator]["properties"][key]['type']) {
					case 'error':
						html += "<hr /><div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label class='leform-red'>"
							+ leform_esc_html__(leform_validators[_validator]["properties"][key]['label'])
							+ "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'><input type='text' name='leform-validators-" + key + "' id='leform-validators-" + seq + "-" + key + "' value='" + leform_escape_html(property_value) + "' placeholder='" + leform_escape_html(leform_validators[_validator]["properties"][key]['value']) + "' /><em>"
							+ leform_esc_html__("Default message")
							+ ": " + leform_escape_html(leform_validators[_validator]["properties"][key]['value']) + "</em></div></div>";
						break;

					case 'text':
						html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>"
							+ leform_esc_html__(leform_validators[_validator]["properties"][key]['label'])
							+ "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'><input type='text' name='leform-validators-" + key + "' id='leform-validators-" + seq + "-" + key + "' value='" + leform_escape_html(property_value) + "' placeholder='" + leform_escape_html(property_value) + "' /></div></div>";
						break;

					case 'field':
						options = "<option value=''>---</option>";
						for (var i = 0; i < leform_form_elements.length; i++) {
							if (leform_form_elements[i] == null) continue;
							if (leform_form_elements[i]["id"] == _field_id) continue;
							if (leform_toolbar_tools.hasOwnProperty(leform_form_elements[i]['type']) && leform_toolbar_tools[leform_form_elements[i]['type']]['type'] == 'input') {
								options += "<option value='" + leform_form_elements[i]['id'] + "'" + (leform_form_elements[i]['id'] == property_value ? " selected='selected'" : "") + ">" + leform_form_elements[i]['id'] + " | " + leform_escape_html(leform_form_elements[i]['name']) + "</option>";
							}
						}
						html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>"
							+ leform_esc_html__(leform_validators[_validator]["properties"][key]['label'])
							+ "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'><select name='leform-validators-" + key + "' id='leform-validators-" + seq + "-" + key + "'>" + options + "</select></div></div>";
						break;

					case 'textarea':
						html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>"
							+ leform_esc_html__(leform_validators[_validator]["properties"][key]['label'])
							+ "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'><textarea name='leform-validators-" + key + "' id='leform-validators-" + seq + "-" + key + "'>" + leform_escape_html(property_value) + "</textarea></div></div>";
						break;

					case 'integer':
						html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>"
							+ leform_esc_html__(leform_validators[_validator]["properties"][key]['label'])
							+ "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'><div class='leform-number'><input type='text' name='leform-validators-" + key + "' id='leform-validators-" + seq + "-" + key + "' value='" + leform_escape_html(property_value) + "' placeholder='' /></div></div></div>";
						break;

					case 'checkbox':
						selected = "";
						if (property_value == "on") selected = " checked='checked'";
						html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>"
							+ leform_esc_html__(leform_validators[_validator]["properties"][key]['label'])
							+ "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'><input class='leform-checkbox-toggle' type='checkbox' value='off' name='leform-validators-" + key + "' id='leform-validators-" + seq + "-" + key + "'" + selected + "' /><label for='leform-validators-" + seq + "-" + key + "'></label></div></div>";
						break;

					default:
						break;
				}
			}
		}
		html += "</div></div>";
		if (_values == null) jQuery(".leform-properties-content-validators .leform-properties-sub-item-body").slideUp(300);
		jQuery(".leform-properties-content-validators").append(html);
		jQuery(".leform-properties-sub-item-new .leform-properties-tooltip .leform-tooltip-anchor").tooltipster({
			contentAsHTML: true,
			maxWidth: 360,
			theme: "tooltipster-dark",
			side: "bottom",
			content: "Default",
			functionFormat: function (instance, helper, content) {
				return jQuery(helper.origin).parent().find('.leform-tooltip-content').html();
			}
		});
		jQuery(".leform-properties-sub-item-new").slideDown(300);
		jQuery(".leform-properties-sub-item-new").removeClass("leform-properties-sub-item-new");
	}
	return false;
}
function leform_properties_validators_details_toggle(_object) {
	jQuery(_object).closest(".leform-properties-sub-item").addClass("leform-freeze");
	jQuery(".leform-properties-content-validators .leform-properties-sub-item").each(function () {
		if (!jQuery(this).hasClass("leform-freeze")) jQuery(this).find(".leform-properties-sub-item-body").slideUp(300);
	});
	jQuery(_object).closest(".leform-properties-sub-item").removeClass("leform-freeze");
	jQuery(_object).closest(".leform-properties-sub-item").find(".leform-properties-sub-item-body").slideToggle(300);
	return false;
}
function leform_properties_validators_delete(_object) {
	leform_dialog_open({
		echo_html: function () {
			this.html("<div class='leform-dialog-message'>" + leform_esc_html__('Please confirm that you want to delete the item.') + "</div>");
			this.show();
		},
		ok_label: leform_esc_html__('Delete'),
		ok_function: function (e) {
			jQuery(_object).closest(".leform-properties-sub-item").slideUp(300, function () {
				jQuery(this).remove();
			});
			leform_element_properties_data_changed = true;
			leform_dialog_close();
		}
	});
	return false;
}

function leform_properties_integrations_name_changed(_object) {
	var label = jQuery(_object).val().substring(0, 52) + (jQuery(_object).val().length > 52 ? "..." : "");
	jQuery(_object).closest(".leform-properties-sub-item").find(".leform-properties-sub-item-header>label").text(label);
	return false;
}
function leform_properties_integrations_logic_enable_changed(_object) {
	var parent = jQuery(_object).closest(".leform-properties-sub-item");
	if (jQuery(_object).is(":checked")) jQuery(parent).find(".leform-properties-item[data-id='logic']").fadeIn(300);
	else jQuery(parent).find(".leform-properties-item[data-id='logic']").fadeOut(300);
	return false;
}
function leform_integrations_ajax_options_selected(_object) {
	var item_id = jQuery(_object).attr("data-id");
	var item_title = jQuery(_object).attr("data-title");
	jQuery(_object).closest(".leform-integrations-ajax-options").find("input[type='text']").val(item_title);
	jQuery(_object).closest(".leform-integrations-ajax-options").find("input[type='hidden']").val(item_id);
	return false;
}
function leform_integrations_custom_add(_object) {
	var template = jQuery(_object).closest("table").find(".leform-integrations-custom-template");
	if (jQuery(template).length > 0) {
		jQuery(template).before("<tr>" + jQuery(template).html() + "</tr>");
	}
}
function leform_integrations_ajax_options_focus(_object) {
	var item = jQuery(_object).closest(".leform-properties-sub-item");
	var provider = jQuery(item).find("input[name='leform-integrations-provider']").val();
	var field = jQuery(_object).attr("name");
	var deps = {};
	if (jQuery(_object).attr("data-deps")) {
		var deps_array = jQuery(_object).attr("data-deps").split(",");
		for (var i = 0; i < deps_array.length; i++) {
			if (jQuery(item).find("input[name='" + deps_array[i] + "']").is(":checked")) deps[deps_array[i]] = 'on';
			else deps[deps_array[i]] = jQuery(item).find("input[name='" + deps_array[i] + "'], select[name='" + deps_array[i] + "']").val();
		}
	}
	var post_data = {
		action: "leform-" + provider + "-" + field,
		deps: leform_encode64(JSON.stringify(deps))
	};
	if (jQuery(_object).parent().find(".leform-integrations-ajax-options-list").length == 0) {
		jQuery(_object).parent().append("<div class='leform-integrations-ajax-options-list'><div class='leform-integrations-ajax-options-list-data'></div><i class='fas fa-spin fa-spinner'></i></div>");
	}
	jQuery(_object).parent().find(".leform-integrations-ajax-options-list i").show();
	jQuery(_object).parent().find(".leform-integrations-ajax-options-list-data").hide();
	jQuery(_object).parent().find(".leform-integrations-ajax-options-list").fadeIn(300);
	var default_error = jQuery(_object).attr("data-default-error");
	if (typeof default_error === typeof undefined || default_error === false) default_error = 'Unexpected server response.';

	jQuery.ajax({
		type: "POST",
		url: leform_ajax_handler,
		data: post_data,
		success: function (return_data) {
			var data;
			try {
				if (typeof return_data == 'object') data = return_data;
				else data = jQuery.parseJSON(return_data);
				if (data.status == "OK") {
					var items_html = "";
					for (var key in data.items) {
						if (data.items.hasOwnProperty(key)) {
							var title = leform_escape_html(key) + (data.items[key] == "" ? "" : " | " + leform_escape_html(data.items[key]));
							items_html += "<a href='#' data-id='" + leform_escape_html(key) + "' data-title='" + title + "' onclick='return leform_integrations_ajax_options_selected(this);'>" + title + "</a>";
						}
					}
					if (Object.keys(data.items).length > 4) jQuery(_object).parent().find(".leform-integrations-ajax-options-list").addClass("leform-vertical-scroll");
					jQuery(_object).parent().find(".leform-integrations-ajax-options-list-data").html(items_html);
					jQuery(_object).parent().find(".leform-integrations-ajax-options-list i").hide();
					jQuery(_object).parent().find(".leform-integrations-ajax-options-list-data").show();
				} else if (data.status == "ERROR") {
					jQuery(_object).parent().find(".leform-integrations-ajax-options-list-data").html('<div class="leform-integrations-ajax-options-list-data-error">' + data.message + '</div>');
					jQuery(_object).parent().find(".leform-integrations-ajax-options-list i").hide();
					jQuery(_object).parent().find(".leform-integrations-ajax-options-list-data").show();
				} else {
					jQuery(_object).parent().find(".leform-integrations-ajax-options-list-data").html("<div class='leform-integrations-ajax-options-list-data-error'>" + default_error + "</div>");
					jQuery(_object).parent().find(".leform-integrations-ajax-options-list i").hide();
					jQuery(_object).parent().find(".leform-integrations-ajax-options-list-data").show();
				}
			} catch (error) {
				jQuery(_object).parent().find(".leform-integrations-ajax-options-list-data").html("<div class='leform-integrations-ajax-options-list-data-error'>" + default_error + "</div>");
				jQuery(_object).parent().find(".leform-integrations-ajax-options-list i").hide();
				jQuery(_object).parent().find(".leform-integrations-ajax-options-list-data").show();
			}
		},
		error: function (XMLHttpRequest, textStatus, errorThrown) {
			jQuery(_object).parent().find(".leform-integrations-ajax-options-list-data").html("<div class='leform-integrations-ajax-options-list-data-error'>" + default_error + "</div>");
			jQuery(_object).parent().find(".leform-integrations-ajax-options-list i").hide();
			jQuery(_object).parent().find(".leform-integrations-ajax-options-list-data").show();
		}
	});
}
function leform_integrations_ajax_multiselect_scroll(_object) {
	if (jQuery(_object).attr("data-next-offset") == "-1") return;
	var content_height = jQuery(_object).prop('scrollHeight');
	var position = jQuery(_object).scrollTop();
	var height = jQuery(_object).height();
	if (content_height - height - position < 20) {
		if (leform_sending) return false;
		leform_sending = true;
		var item = jQuery(_object).closest(".leform-properties-sub-item");
		var provider = jQuery(item).find("input[name='leform-integrations-provider']").val();
		var sub_action = jQuery(_object).attr("data-action");
		var deps = { "offset": parseInt(jQuery(_object).attr("data-next-offset"), 10) };
		if (jQuery(_object).attr("data-deps")) {
			var deps_array = jQuery(_object).attr("data-deps").split(",");
			for (var i = 0; i < deps_array.length; i++) {
				deps[deps_array[i]] = jQuery(item).find("input[name='" + deps_array[i] + "'], select[name='" + deps_array[i] + "']").val();
			}
		}
		var post_data = {
			"action": "leform-" + provider + "-" + sub_action,
			"deps": leform_encode64(JSON.stringify(deps))
		};
		jQuery(_object).find(".leform-integrations-ajax-multiselect-loading").slideDown(300);
		jQuery.ajax({
			type: "POST",
			url: leform_ajax_handler,
			data: post_data,
			success: function (return_data) {
				jQuery(_object).find(".leform-integrations-ajax-multiselect-loading").slideUp(300)
				var data;
				try {
					if (typeof return_data == "object") data = return_data;
					else data = jQuery.parseJSON(return_data);
					if (data.status == "OK") {
						jQuery(_object).find(".leform-integrations-ajax-multiselect-loading").before(data.html);
						jQuery(_object).attr("data-next-offset", data.offset);
					} else if (data.status == "ERROR") {
						jQuery(_object).attr("data-next-offset", "-1");
						leform_global_message_show("danger", data.message);
					} else {
						jQuery(_object).attr("data-next-offset", "-1");
						leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
					}
				} catch (error) {
					jQuery(_object).attr("data-next-offset", "-1");
					leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
				}
				leform_sending = false;
			},
			error: function (XMLHttpRequest, textStatus, errorThrown) {
				jQuery(_object).find(".leform-integrations-ajax-multiselect-loading").slideUp(300)
				jQuery(_object).attr("data-next-offset", "-1");
				leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
				leform_sending = false;
			}
		});
	}
}

function leform_integrations_ajax_inline_html(_object) {
	if (leform_sending) return false;
	leform_sending = true;
	var item = jQuery(_object).closest(".leform-properties-sub-item");
	var provider = jQuery(item).find("input[name='leform-integrations-provider']").val();
	var inline_action = jQuery(_object).attr("data-inline");
	var deps = {};

	if (jQuery(_object).attr("data-deps")) {
		var deps_array = jQuery(_object).attr("data-deps").split(",");
		for (var i = 0; i < deps_array.length; i++) {
			if (jQuery(item).find("input[name='" + deps_array[i] + "']").is(":checked")) deps[deps_array[i]] = 'on';
			else deps[deps_array[i]] = jQuery(item).find("input[name='" + deps_array[i] + "'], select[name='" + deps_array[i] + "']").val();
		}
	}

	var post_data = {
		action: "leform-" + provider + "-" + inline_action,
		deps: leform_encode64(JSON.stringify(deps))
	};
	jQuery(_object).find("i").attr("class", "fas fa-spinner fa-spin");
	jQuery(_object).addClass("leform-button-disabled");
	jQuery(_object).parent().find(".leform-integrations-ajax-inline").slideUp(300);

	var default_error = jQuery(_object).attr("data-default-error");
	if (typeof default_error === typeof undefined || default_error === false) default_error = leform_esc_html__("Something went wrong. We got unexpected server response.");

	jQuery.ajax({
		type: "POST",
		url: leform_ajax_handler,
		data: post_data,
		success: function (return_data) {
			jQuery(_object).find("i").attr("class", "fas fa-download");
			jQuery(_object).removeClass("leform-button-disabled");
			var data;
			try {
				if (typeof return_data == 'object') data = return_data;
				else data = jQuery.parseJSON(return_data);
				if (data.status == "OK") {
					jQuery(_object).parent().find(".leform-integrations-ajax-inline").html(data.html);
					jQuery(_object).parent().find(".leform-integrations-ajax-inline").slideDown(300);
				} else if (data.status == "ERROR") {
					leform_global_message_show("danger", data.message);
				} else {
					leform_global_message_show("danger", default_error);
				}
			} catch (error) {
				leform_global_message_show("danger", default_error);
			}
			leform_sending = false;
		},
		error: function (XMLHttpRequest, textStatus, errorThrown) {
			jQuery(_object).find("i").attr("class", "fas fa-download");
			jQuery(_object).removeClass("leform-button-disabled");
			leform_global_message_show("danger", default_error);
			leform_sending = false;
		}
	});
}
/*function leform_integrations_field_add(_object) {
	var template_class = jQuery(_object).attr("data-template");
	var template_object = jQuery(_object).parent().find("."+template_class);
	if (template_object.length > 0) {
		jQuery(template_object).before("<tr>"+jQuery(template_object).html()+"</tr>");
	}
	return false;
}
function leform_integrations_field_remove(_object) {
	var row = jQuery(_object).closest("tr");
	jQuery(row).fadeOut(300, function() {
		jQuery(row).remove();
	});
	return false;
}*/
function leform_properties_integrations_details_toggle(_object) {
	if (typeof _object == "undefined") return;
	var item = jQuery(_object).closest(".leform-properties-sub-item");
	jQuery(item).addClass("leform-freeze");
	jQuery(".leform-properties-content-integrations .leform-properties-sub-item").each(function () {
		if (!jQuery(this).hasClass("leform-freeze")) jQuery(this).find(".leform-properties-sub-item-body").slideUp(300);
	});
	jQuery(item).removeClass("leform-freeze");
	jQuery(item).find(".leform-properties-sub-item-body").slideToggle(300);
	if (jQuery(item).attr("data-loaded") != "on") {
		var provider = jQuery(item).find("input[name='leform-integrations-provider']").val();
		if (leform_sending) return false;
		leform_sending = true;
		var post_data = {
			action: "leform-" + provider + "-settings-html"
		};
		var idx = jQuery(item).find("input[name='leform-integrations-idx']").val();
		if (idx >= 0 && idx <= leform_form_options["integrations"].length) {
			post_data["data"] = leform_encode64(JSON.stringify(leform_form_options["integrations"][idx]["data"]));
		}
		jQuery.ajax({
			type: "POST",
			url: leform_ajax_handler,
			data: post_data,
			success: function (return_data) {
				var data;
				try {
					if (typeof return_data == 'object') data = return_data;
					else data = jQuery.parseJSON(return_data);
					if (data.status == "OK") {
						jQuery(item).attr("data-loaded", "on");
						jQuery(item).find(".leform-integrations-content").html(data.html);
						jQuery(item).find(".leform-integrations-content .leform-properties-tooltip .leform-tooltip-anchor").tooltipster({
							contentAsHTML: true,
							maxWidth: 360,
							theme: "tooltipster-dark",
							side: "bottom",
							content: "Default",
							functionFormat: function (instance, helper, content) {
								return jQuery(helper.origin).parent().find('.leform-tooltip-content').html();
							}
						});
						jQuery(item).find(".leform-integrations-ajax-options input[type='text']").on("focus", function () {
							leform_integrations_ajax_options_focus(this);
						});
						jQuery(item).find(".leform-integrations-ajax-options input[type='text']").on("blur", function () {
							jQuery(this).parent().find(".leform-integrations-ajax-options-list").fadeOut(300);
						});
						jQuery(item).find(".leform-properties-sub-item-body-loading").hide();
						jQuery(item).find(".leform-properties-sub-item-body-content").slideDown(300);
					} else if (data.status == "ERROR") {
						jQuery(item).find(".leform-properties-sub-item-body").slideUp(300);
						leform_global_message_show("danger", data.message);
					} else {
						jQuery(item).find(".leform-properties-sub-item-body").slideUp(300);
						leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
					}
				} catch (error) {
					jQuery(item).find(".leform-properties-sub-item-body").slideUp(300);
					leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
				}
				leform_sending = false;
			},
			error: function (XMLHttpRequest, textStatus, errorThrown) {
				jQuery(item).find(".leform-properties-sub-item-body").slideUp(300);
				leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
				leform_sending = false;
			}
		});
	}
	return false;
}
function leform_properties_integrations_delete(_object) {
	leform_dialog_open({
		echo_html: function () {
			this.html("<div class='leform-dialog-message'>" + leform_esc_html__('Please confirm that you want to delete the item.') + "</div>");
			this.show();
		},
		ok_label: leform_esc_html__('Delete'),
		ok_function: function (e) {
			jQuery(_object).closest(".leform-properties-sub-item").slideUp(300, function () {
				jQuery(this).remove();
			});
			leform_element_properties_data_changed = true;
			leform_dialog_close();
		}
	});
	return false;
}
function leform_properties_integrations_add(_values, _idx, _provider) {
	var extra_class = "", html = "", temp = "", property_value, enabled, logic_enable, logic_enable_id, provider = "", label = "";

	if (typeof _provider != "undefined") {
		provider = _provider;
		label = (leform_integration_providers.hasOwnProperty(provider) ? leform_integration_providers[provider] : 'Integration');
	} else if (typeof _values == "object") {
		provider = _values["provider"];
		label = _values["name"];
	}

	if (_values == null) {
		extra_class = " leform-properties-sub-item-new";
		leform_element_properties_data_changed = true;
	} else extra_class = " leform-properties-sub-item-exist";
	html += "<div class='leform-properties-sub-item" + extra_class + "' data-loaded='off'><div class='leform-properties-sub-item-header'><div class='leform-properties-sub-item-header-tools'><span onclick='return leform_properties_integrations_delete(this);'><i class='fas fa-trash-alt'></i></span><span onclick='return leform_properties_integrations_details_toggle(this);'><i class='fas fa-cog'></i></span></div><label></label></div><div class='leform-properties-sub-item-body' style='display: none;'><div class='leform-properties-sub-item-body-content' style='display: none;'>";

	html += "<div class='leform-properties-item' data-id='name'><div class='leform-properties-label'><label>" + leform_integrations['name']['label'] + "</label></div><div class='leform-properties-tooltip'><i class='fas fa-question-circle leform-tooltip-anchor'></i><div class='leform-tooltip-content'>" + leform_integrations['name']['tooltip'] + "</div></div><div class='leform-properties-content'><input type='text' name='leform-integrations-name' value='" + leform_escape_html(label) + "' oninput='return leform_properties_integrations_name_changed(this);' /></div></div>";

	if (_values != null && _values.hasOwnProperty('enabled')) enabled = _values['enabled'];
	else enabled = leform_integrations['enabled']['value'];
	var enabled_id = leform_random_string(16);
	html += "<div class='leform-properties-item' data-id='enabled'><div class='leform-properties-label'><label>" + leform_integrations['enabled']['label'] + "</label></div><div class='leform-properties-tooltip'><i class='fas fa-question-circle leform-tooltip-anchor'></i><div class='leform-tooltip-content'>" + leform_integrations['enabled']['tooltip'] + "</div></div><div class='leform-properties-content'><input class='leform-checkbox-toggle' type='checkbox' value='off' id='leform-integrations-enabled-" + enabled_id + "' name='leform-integrations-enabled'" + (enabled == "on" ? ' checked="checked"' : '') + "' /><label for='leform-integrations-enabled-" + enabled_id + "'></label></div></div>";

	if (_values != null && _values.hasOwnProperty('action')) property_value = _values['action'];
	else property_value = leform_integrations['action']['value'];
	var options = "";
	for (var option_key in leform_integrations['action']['options']) {
		if (leform_integrations['action']['options'].hasOwnProperty(option_key)) {
			options += "<option value='" + leform_escape_html(option_key) + "'" + (property_value == option_key ? " selected='selected'" : "") + ">" + leform_escape_html(leform_integrations['action']['options'][option_key]) + "</option>";
		}
	}
	html += "<div class='leform-properties-item' data-id='action'><div class='leform-properties-label'><label>" + leform_integrations['action']['label'] + "</label></div><div class='leform-properties-tooltip'><i class='fas fa-question-circle leform-tooltip-anchor'></i><div class='leform-tooltip-content'>" + leform_integrations['action']['tooltip'] + "</div></div><div class='leform-properties-content'><select name='leform-integrations-action'>" + options + "</select></div></div>";

	html += "<input type='hidden' name='leform-integrations-idx' value='" + _idx + "' /><input type='hidden' name='leform-integrations-provider' value='" + leform_escape_html(provider) + "' /><div class='leform-integrations-content'></div>";

	if (_values != null && _values.hasOwnProperty('logic-enable')) logic_enable = _values['logic-enable'];
	else logic_enable = leform_integrations['logic-enable']['value'];
	logic_enable_id = leform_random_string(16);
	html += "<div class='leform-properties-item' data-id='logic-enable'><div class='leform-properties-label'><label>" + leform_integrations['logic-enable']['label'] + "</label></div><div class='leform-properties-tooltip'><i class='fas fa-question-circle leform-tooltip-anchor'></i><div class='leform-tooltip-content'>" + leform_integrations['logic-enable']['tooltip'] + "</div></div><div class='leform-properties-content'><input class='leform-checkbox-toggle' type='checkbox' value='off' id='leform-integrations-logic-enable-" + logic_enable_id + "' name='leform-integrations-logic-enable'" + (logic_enable == "on" ? ' checked="checked"' : '') + " onchange='return leform_properties_integrations_logic_enable_changed(this);' /><label for='leform-integrations-logic-enable-" + logic_enable_id + "'></label></div></div>";

	if (_values != null && _values.hasOwnProperty('logic')) property_value = _values['logic'];
	else property_value = leform_integrations['logic']['value'];
	var input_ids = new Array();
	for (var i = 0; i < leform_form_elements.length; i++) {
		if (leform_form_elements[i] == null) continue;
		if (leform_toolbar_tools.hasOwnProperty(leform_form_elements[i]['type']) && leform_toolbar_tools[leform_form_elements[i]['type']]['type'] == 'input') {
			input_ids.push(leform_form_elements[i]["id"]);
		}
	}
	if (input_ids.length > 0) {
		temp = "<div class='leform-properties-group leform-properties-logic-header'>";
		options = "";
		for (var option_key in leform_integrations['logic']['actions']) {
			if (leform_integrations['logic']['actions'].hasOwnProperty(option_key)) {
				options += "<option value='" + leform_escape_html(option_key) + "'" + (property_value["action"] == option_key ? " selected='selected'" : "") + ">" + leform_escape_html(leform_integrations['logic']['actions'][option_key]) + "</option>";
			}
		}
		temp += "<div class='leform-properties-content-half'><select name='leform-integrations-logic-action' id='leform-logic-action'>" + options + "</select></div>";
		options = "";
		for (var option_key in leform_integrations['logic']['operators']) {
			if (leform_integrations['logic']['operators'].hasOwnProperty(option_key)) {
				options += "<option value='" + leform_escape_html(option_key) + "'" + (property_value["operator"] == option_key ? " selected='selected'" : "") + ">" + leform_escape_html(leform_integrations['logic']['operators'][option_key]) + "</option>";
			}
		}
		temp += "<div class='leform-properties-content-half'><select name='leform-integrations-logic-operator' id='leform-logic-operator'>" + options + "</select></div>";
		temp += "</div>";
		options = "";
		for (var j = 0; j < property_value["rules"].length; j++) {
			if (input_ids.indexOf(parseInt(property_value["rules"][j]["field"], 10)) != -1) {
				options += leform_properties_logic_rule_get(null, property_value["rules"][j]["field"], property_value["rules"][j]["rule"], property_value["rules"][j]["token"]);
			}
		}
		temp += "<div class='leform-properties-logic-rules'>" + options + "</div><div class='leform-properties-logic-buttons'><a class='leform-admin-button leform-admin-button-gray leform-admin-button-small' href='#' onclick='return leform_properties_logic_rule_new(this, null);'><i class='fas fa-plus'></i><label>Add rule</label></a></div>";
	} else {
		temp = "<div class='leform-properties-inline-error'>There are no elements available to use for logic rules.</div>";
	}
	html += "<div class='leform-properties-item' data-id='logic'" + (logic_enable == "on" ? "" : " style='display:none;'") + "><div class='leform-properties-label'><label>" + leform_integrations['logic']['label'] + "</label></div><div class='leform-properties-tooltip'><i class='fas fa-question-circle leform-tooltip-anchor'></i><div class='leform-tooltip-content'>" + leform_integrations['logic']['tooltip'] + "</div></div><div class='leform-properties-content'>" + temp + "</div></div>";
	html += "</div><div class='leform-properties-sub-item-body-loading'><i class='fas fa-spin fa-spinner'></i></div></div></div>";

	if (_values == null) jQuery(".leform-properties-content-integrations .leform-properties-sub-item-body").slideUp(300);
	jQuery(".leform-properties-content-integrations").append(html);
	jQuery(".leform-properties-sub-item-new .leform-properties-tooltip .leform-tooltip-anchor").tooltipster({
		contentAsHTML: true,
		maxWidth: 360,
		theme: "tooltipster-dark",
		side: "bottom",
		content: "Default",
		functionFormat: function (instance, helper, content) {
			return jQuery(helper.origin).parent().find('.leform-tooltip-content').html();
		}
	});
	leform_properties_integrations_name_changed(jQuery(".leform-properties-content-integrations .leform-properties-sub-item").last().find("[name='leform-integrations-name']"));
	if (jQuery(".leform-properties-sub-item-new").length > 0) leform_properties_integrations_details_toggle(jQuery(".leform-properties-sub-item-new").find(".leform-properties-sub-item-header-tools"));
	jQuery(".leform-properties-sub-item-new").removeClass("leform-properties-sub-item-new");
	return false;
}
function leform_integrations_zapier_connect(_object) {
	if (leform_sending) return false;
	leform_sending = true;
	var item = jQuery(_object).closest(".leform-properties-sub-item");
	var content = jQuery(item).find(".leform-integrations-custom");
	var deps = {};

	var fields = new Array();

	var name;
	var names = jQuery(content).find("input.leform-integrations-custom-name");
	for (var j = 0; j < names.length; j++) {
		name = jQuery(names[j]).val();
		if (name.length > 0) {
			fields.push(name);
		}
	}
	var post_data = {
		"action": "leform-zapier-connect",
		"webhook-url": leform_encode64(jQuery(item).find("[name='webhook-url']").val()),
		"fields": leform_encode64(JSON.stringify(fields))
	};
	jQuery(_object).find("i").attr("class", "fas fa-spinner fa-spin");
	jQuery(_object).addClass("leform-button-disabled");
	jQuery(_object).parent().find(".leform-integrations-ajax-inline").slideUp(300);

	jQuery.ajax({
		type: "POST",
		url: leform_ajax_handler,
		data: post_data,
		success: function (return_data) {
			jQuery(_object).find("i").attr("class", "fas fa-download");
			jQuery(_object).removeClass("leform-button-disabled");
			var data;
			try {
				if (typeof return_data == 'object') data = return_data;
				else data = jQuery.parseJSON(return_data);
				if (data.status == "OK") {
					leform_global_message_show("success", data.message);
				} else if (data.status == "ERROR") {
					leform_global_message_show("danger", data.message);
				} else {
					leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
				}
			} catch (error) {
				leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
			}
			leform_sending = false;
		},
		error: function (XMLHttpRequest, textStatus, errorThrown) {
			jQuery(_object).find("i").attr("class", "fas fa-download");
			jQuery(_object).removeClass("leform-button-disabled");
			leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
			leform_sending = false;
		}
	});

}
function leform_properties_integrations_constantcontact_apikey_changed(_object) {
	jQuery(_object).closest(".leform-properties-sub-item").find("input[name=token]").val("");
	var token_link = jQuery(_object).closest(".leform-properties-sub-item").find(".leform-constantcontact-token-link");
	jQuery(token_link).attr("href", jQuery(token_link).attr("data-href").replace("{api-key}", jQuery(_object).closest(".leform-properties-item").find("input").val()));
}

function leform_properties_payment_gateways_details_toggle(_object) {
	if (typeof _object == "undefined") return;
	var item = jQuery(_object).closest(".leform-properties-sub-item");
	jQuery(item).addClass("leform-freeze");
	jQuery(".leform-properties-content-payment-gateways .leform-properties-sub-item").each(function () {
		if (!jQuery(this).hasClass("leform-freeze")) jQuery(this).find(".leform-properties-sub-item-body").slideUp(300);
	});
	jQuery(item).removeClass("leform-freeze");
	jQuery(item).find(".leform-properties-sub-item-body").slideToggle(300);
	if (jQuery(item).attr("data-loaded") != "on") {
		var provider = jQuery(item).find("input[name='leform-payment-gateways-provider']").val();
		if (leform_sending) return false;
		leform_sending = true;
		var post_data = {
			action: "leform-" + provider + "-settings-html"
		};
		var idx = jQuery(item).find("input[name='leform-payment-gateways-idx']").val();
		if (idx >= 0 && idx <= leform_form_options["payment-gateways"].length) {
			post_data["data"] = leform_encode64(JSON.stringify(leform_form_options["payment-gateways"][idx]["data"]));
		}
		jQuery.ajax({
			type: "POST",
			url: leform_ajax_handler,
			data: post_data,
			success: function (return_data) {
				var data;
				try {
					if (typeof return_data == 'object') data = return_data;
					else data = jQuery.parseJSON(return_data);
					if (data.status == "OK") {
						jQuery(item).attr("data-loaded", "on");
						jQuery(item).find(".leform-payment-gateways-content").html(data.html);
						jQuery(item).find(".leform-payment-gateways-content .leform-properties-tooltip .leform-tooltip-anchor").tooltipster({
							contentAsHTML: true,
							maxWidth: 360,
							theme: "tooltipster-dark",
							side: "bottom",
							content: "Default",
							functionFormat: function (instance, helper, content) {
								return jQuery(helper.origin).parent().find('.leform-tooltip-content').html();
							}
						});
						jQuery(item).find(".leform-properties-sub-item-body-loading").hide();
						jQuery(item).find(".leform-properties-sub-item-body-content").slideDown(300);
					} else if (data.status == "ERROR") {
						jQuery(item).find(".leform-properties-sub-item-body").slideUp(300);
						leform_global_message_show("danger", data.message);
					} else {
						jQuery(item).find(".leform-properties-sub-item-body").slideUp(300);
						leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
					}
				} catch (error) {
					jQuery(item).find(".leform-properties-sub-item-body").slideUp(300);
					leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
				}
				leform_sending = false;
			},
			error: function (XMLHttpRequest, textStatus, errorThrown) {
				jQuery(item).find(".leform-properties-sub-item-body").slideUp(300);
				leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
				leform_sending = false;
			}
		});
	}
	return false;
}
function leform_properties_payment_gateways_name_changed(_object) {
	var label = jQuery(_object).val().substring(0, 52) + (jQuery(_object).val().length > 52 ? "..." : "");
	jQuery(_object).closest(".leform-properties-sub-item").find(".leform-properties-sub-item-header>label").text(label);
	leform_properties_payment_gateways_select_update();
	return false;
}
function leform_properties_payment_gateways_select_update() {
	var payment_gateways = new Array();
	jQuery(".leform-properties-content-payment-gateways .leform-properties-sub-item").each(function () {
		payment_gateways.push({ "id": jQuery(this).find("[name='leform-payment-gateways-id']").val(), "name": jQuery(this).find("[name='leform-payment-gateways-name']").val() });
	});
	jQuery(".leform-payment-gateways-select").each(function () {
		var value = jQuery(this).val();
		var options = "<option value=''" + (value == "" ? " selected='selected'" : "") + ">Select payment gateway</option>";
		for (var i = 0; i < payment_gateways.length; i++) {
			options += "<option value='" + leform_escape_html(payment_gateways[i]['id']) + "'" + (value == payment_gateways[i]['id'] ? " selected='selected'" : "") + ">" + leform_escape_html(payment_gateways[i]['name']) + "</option>";
		}
		jQuery(this).html(options);
	});
}
function leform_properties_payment_gateways_delete(_object) {
	leform_dialog_open({
		echo_html: function () {
			this.html("<div class='leform-dialog-message'>" + leform_esc_html__('Please confirm that you want to delete the item.') + "</div>");
			this.show();
		},
		ok_label: leform_esc_html__('Delete'),
		ok_function: function (e) {
			jQuery(_object).closest(".leform-properties-sub-item").slideUp(300, function () {
				jQuery(this).remove();
				leform_properties_payment_gateways_select_update();
			});
			leform_element_properties_data_changed = true;
			leform_dialog_close();
		}
	});
	return false;
}
function leform_properties_payment_gateways_add(_values, _idx, _provider) {
	var extra_class = "", html = "", property_value, enabled, provider = "", label = "";

	if (typeof _provider != "undefined") {
		provider = _provider;
		label = (leform_payment_providers.hasOwnProperty(provider) ? leform_payment_providers[provider] : 'Payment Gateway');
	} else if (typeof _values == "object") {
		provider = _values["provider"];
		label = _values["name"];
	}

	var label_beauty = label.substring(0, 52) + (label.length > 52 ? "..." : "");

	if (_values == null) {
		extra_class = " leform-properties-sub-item-new";
		leform_element_properties_data_changed = true;
	} else extra_class = " leform-properties-sub-item-exist";
	html += "<div class='leform-properties-sub-item" + extra_class + "' data-loaded='off'><div class='leform-properties-sub-item-header'><div class='leform-properties-sub-item-header-tools'><span onclick='return leform_properties_payment_gateways_delete(this);'><i class='fas fa-trash-alt'></i></span><span onclick='return leform_properties_payment_gateways_details_toggle(this);'><i class='fas fa-cog'></i></span></div><label>" + leform_escape_html(label_beauty) + "</label></div><div class='leform-properties-sub-item-body' style='display: none;'><div class='leform-properties-sub-item-body-content' style='display: none;'>";

	html += "<div class='leform-properties-item' data-id='name'><div class='leform-properties-label'><label>" + leform_payment_gateway['name']['label'] + "</label></div><div class='leform-properties-tooltip'><i class='fas fa-question-circle leform-tooltip-anchor'></i><div class='leform-tooltip-content'>" + leform_payment_gateway['name']['tooltip'] + "</div></div><div class='leform-properties-content'><input type='text' name='leform-payment-gateways-name' value='" + leform_escape_html(label) + "' oninput='return leform_properties_payment_gateways_name_changed(this);' /></div></div>";
	if (_values != null && _values.hasOwnProperty('id')) property_value = _values['id'];
	else {
		leform_form_last_id++;
		property_value = leform_form_last_id;
	}
	html += "<input type='hidden' name='leform-payment-gateways-id' value='" + property_value + "' /><input type='hidden' name='leform-payment-gateways-idx' value='" + _idx + "' /><input type='hidden' name='leform-payment-gateways-provider' value='" + leform_escape_html(provider) + "' /><div class='leform-payment-gateways-content'></div>";

	html += "</div><div class='leform-properties-sub-item-body-loading'><i class='fas fa-spin fa-spinner'></i></div></div></div>";

	if (_values == null) jQuery(".leform-properties-content-payment-gateways .leform-properties-sub-item-body").slideUp(300);
	jQuery(".leform-properties-content-payment-gateways").append(html);

	jQuery(".leform-properties-sub-item-new .leform-properties-tooltip .leform-tooltip-anchor").tooltipster({
		contentAsHTML: true,
		maxWidth: 360,
		theme: "tooltipster-dark",
		side: "bottom",
		content: "Default",
		functionFormat: function (instance, helper, content) {
			return jQuery(helper.origin).parent().find('.leform-tooltip-content').html();
		}
	});
	if (_values == null) leform_properties_payment_gateways_select_update();

	if (jQuery(".leform-properties-sub-item-new").length > 0) leform_properties_payment_gateways_details_toggle(jQuery(".leform-properties-sub-item-new").find(".leform-properties-sub-item-header-tools"));
	jQuery(".leform-properties-sub-item-new").removeClass("leform-properties-sub-item-new");
	return false;
}

function leform_properties_notifications_name_changed(_object) {
	var label = jQuery(_object).val().substring(0, 52) + (jQuery(_object).val().length > 52 ? "..." : "");
	jQuery(_object).closest(".leform-properties-sub-item").find(".leform-properties-sub-item-header>label").text(label);
	return false;
}
function leform_properties_notifications_logic_enable_changed(_object) {
	var parent = jQuery(_object).closest(".leform-properties-sub-item");
	if (jQuery(_object).is(":checked")) jQuery(parent).find(".leform-properties-item[data-id='logic']").fadeIn(300);
	else jQuery(parent).find(".leform-properties-item[data-id='logic']").fadeOut(300);
	return false;
}
function leform_properties_notifications_details_toggle(_object) {
	jQuery(_object).closest(".leform-properties-sub-item").addClass("leform-freeze");
	jQuery(".leform-properties-content-notifications .leform-properties-sub-item").each(function () {
		if (!jQuery(this).hasClass("leform-freeze")) jQuery(this).find(".leform-properties-sub-item-body").slideUp(300);
	});
	jQuery(_object).closest(".leform-properties-sub-item").removeClass("leform-freeze");
	jQuery(_object).closest(".leform-properties-sub-item").find(".leform-properties-sub-item-body").slideToggle(300);
	return false;
}
function leform_properties_notifications_delete(_object) {
	leform_dialog_open({
		echo_html: function () {
			this.html("<div class='leform-dialog-message'>" + leform_esc_html__('Please confirm that you want to delete the item.') + "</div>");
			this.show();
		},
		ok_label: leform_esc_html__('Delete'),
		ok_function: function (e) {
			jQuery(_object).closest(".leform-properties-sub-item").slideUp(300, function () {
				jQuery(this).remove();
			});
			leform_element_properties_data_changed = true;
			leform_dialog_close();
		}
	});
	return false;
}
function leform_properties_notifications_add(_values) {
	var extra_class = "", html = "", temp = "", tooltip_html, selected, property_value, enabled, logic_enable, logic_enable_id;

	var input_ids = new Array();
	var file_ids = new Array();
	for (var i = 0; i < leform_form_elements.length; i++) {
		if (leform_form_elements[i] == null) continue;
		if (leform_toolbar_tools.hasOwnProperty(leform_form_elements[i]['type']) && leform_toolbar_tools[leform_form_elements[i]['type']]['type'] == 'input') {
			input_ids.push(leform_form_elements[i]["id"]);
			if (leform_form_elements[i]['type'] == 'file') {
				file_ids.push(leform_form_elements[i]["id"]);
			}
		}
	}

	if (_values == null) {
		extra_class = " leform-properties-sub-item-new";
		leform_element_properties_data_changed = true;
	} else extra_class = " leform-properties-sub-item-exist";
	html += "<div class='leform-properties-sub-item" + extra_class + "'><div class='leform-properties-sub-item-header'><div class='leform-properties-sub-item-header-tools'><span onclick='return leform_properties_notifications_delete(this);'><i class='fas fa-trash-alt'></i></span><span onclick='return leform_properties_notifications_details_toggle(this);'><i class='fas fa-cog'></i></span></div><label></label></div><div class='leform-properties-sub-item-body'>";

	html += "<div class='leform-properties-item' data-id='name'><div class='leform-properties-label'><label>" + leform_notifications['name']['label'] + "</label></div><div class='leform-properties-tooltip'><i class='fas fa-question-circle leform-tooltip-anchor'></i><div class='leform-tooltip-content'>" + leform_notifications['name']['tooltip'] + "</div></div><div class='leform-properties-content'><input type='text' name='leform-notifications-name' value='" + (_values != null && _values.hasOwnProperty('name') ? leform_escape_html(_values['name']) : leform_escape_html(leform_notifications['name']['value'])) + "' oninput='return leform_properties_notifications_name_changed(this);' /></div></div>";

	if (_values != null && _values.hasOwnProperty('enabled')) enabled = _values['enabled'];
	else enabled = leform_notifications['enabled']['value'];
	var enabled_id = leform_random_string(16);
	html += "<div class='leform-properties-item' data-id='enabled'><div class='leform-properties-label'><label>" + leform_notifications['enabled']['label'] + "</label></div><div class='leform-properties-tooltip'><i class='fas fa-question-circle leform-tooltip-anchor'></i><div class='leform-tooltip-content'>" + leform_notifications['enabled']['tooltip'] + "</div></div><div class='leform-properties-content'><input class='leform-checkbox-toggle' type='checkbox' value='off' id='leform-notifications-enabled-" + enabled_id + "' name='leform-notifications-enabled'" + (enabled == "on" ? ' checked="checked"' : '') + "' /><label for='leform-notifications-enabled-" + enabled_id + "'></label></div></div>";

	if (_values != null && _values.hasOwnProperty('action')) property_value = _values['action'];
	else property_value = leform_notifications['action']['value'];
	var options = "";
	for (var option_key in leform_notifications['action']['options']) {
		if (leform_notifications['action']['options'].hasOwnProperty(option_key)) {
			options += "<option value='" + leform_escape_html(option_key) + "'" + (property_value == option_key ? " selected='selected'" : "") + ">" + leform_escape_html(leform_notifications['action']['options'][option_key]) + "</option>";
		}
	}
	html += "<div class='leform-properties-item' data-id='action'><div class='leform-properties-label'><label>" + leform_notifications['action']['label'] + "</label></div><div class='leform-properties-tooltip'><i class='fas fa-question-circle leform-tooltip-anchor'></i><div class='leform-tooltip-content'>" + leform_notifications['action']['tooltip'] + "</div></div><div class='leform-properties-content'><select name='leform-notifications-action'>" + options + "</select></div></div>";

	html += "<div class='leform-properties-item' data-id='recipient-email'><div class='leform-properties-label'><label>" + leform_notifications['recipient-email']['label'] + "</label></div><div class='leform-properties-tooltip'><i class='fas fa-question-circle leform-tooltip-anchor'></i><div class='leform-tooltip-content'>" + leform_notifications['recipient-email']['tooltip'] + "</div></div><div class='leform-properties-content'><div class='leform-properties-group leform-input-shortcode-selector'><input type='text' name='leform-notifications-recipient-email' value='" + (_values != null && _values.hasOwnProperty('recipient-email') ? leform_escape_html(_values['recipient-email']) : leform_escape_html(leform_notifications['recipient-email']['value'])) + "' /><div class='leform-shortcode-selector' onmouseover='leform_shortcode_selector_set(this)';><span><i class='fas fa-code'></i></span></div></div></div></div>";
	html += "<div class='leform-properties-item' data-id='subject'><div class='leform-properties-label'><label>" + leform_notifications['subject']['label'] + "</label></div><div class='leform-properties-tooltip'><i class='fas fa-question-circle leform-tooltip-anchor'></i><div class='leform-tooltip-content'>" + leform_notifications['subject']['tooltip'] + "</div></div><div class='leform-properties-content'><div class='leform-properties-group leform-input-shortcode-selector'><input type='text' name='leform-notifications-subject' value='" + (_values != null && _values.hasOwnProperty('subject') ? leform_escape_html(_values['subject']) : leform_escape_html(leform_notifications['subject']['value'])) + "' /><div class='leform-shortcode-selector' onmouseover='leform_shortcode_selector_set(this)';><span><i class='fas fa-code'></i></span></div></div></div></div>";
	var message_id = leform_random_string(16);
	html += "<div class='leform-properties-item' data-id='message'><div class='leform-properties-label'><label>" + leform_notifications['message']['label'] + "</label></div><div class='leform-properties-tooltip'><i class='fas fa-question-circle leform-tooltip-anchor'></i><div class='leform-tooltip-content'>" + leform_notifications['message']['tooltip'] + "</div></div><div class='leform-properties-content leform-wysiwyg'><textarea class='leform-tinymce leform-tinymce-pre' name='leform-notifications-message' id='leform-notifications-message-" + message_id + "'>" + (_values != null && _values.hasOwnProperty('message') ? leform_escape_html(_values['message']) : leform_escape_html(leform_notifications['message']['value'])) + "</textarea></div></div>";

	if (_values != null && _values.hasOwnProperty('attachments')) property_value = _values['attachments'];
	else property_value = leform_notifications['attachments']['value'];
	options = "";
	for (var j = 0; j < property_value.length; j++) {
		options += leform_properties_attachment_get(property_value[j]["source"], property_value[j]["token"]);
	}
	html += "<div class='leform-properties-item' data-id='attachments'><div class='leform-properties-label'><label>" + leform_notifications['attachments']['label'] + "</label></div><div class='leform-properties-tooltip'><i class='fas fa-question-circle leform-tooltip-anchor'></i><div class='leform-tooltip-content'>" + leform_notifications['attachments']['tooltip'] + "</div></div><div class='leform-properties-content'><div class='leform-properties-attachments'>" + options + "</div><div class='leform-properties-attachment-buttons'><a class='leform-admin-button leform-admin-button-gray leform-admin-button-small' href='#' onclick='return leform_properties_attachment_new(this);'><i class='fas fa-plus'></i><label>Add file</label></a></div></div></div>";

	html += "<div class='leform-properties-item' data-id='reply-email'><div class='leform-properties-label'><label>" + leform_notifications['reply-email']['label'] + "</label></div><div class='leform-properties-tooltip'><i class='fas fa-question-circle leform-tooltip-anchor'></i><div class='leform-tooltip-content'>" + leform_notifications['reply-email']['tooltip'] + "</div></div><div class='leform-properties-content'><div class='leform-properties-group leform-input-shortcode-selector'><input type='text' name='leform-notifications-reply-email' value='" + (_values != null && _values.hasOwnProperty('reply-email') ? leform_escape_html(_values['reply-email']) : leform_escape_html(leform_notifications['reply-email']['value'])) + "' /><div class='leform-shortcode-selector' onmouseover='leform_shortcode_selector_set(this)';><span><i class='fas fa-code'></i></span></div></div></div></div>";
	html += "<div class='leform-properties-item' data-id='from'><div class='leform-properties-label'><label>" + leform_notifications['from']['label'] + "</label></div><div class='leform-properties-tooltip'><i class='fas fa-question-circle leform-tooltip-anchor'></i><div class='leform-tooltip-content'>" + leform_notifications['from']['tooltip'] + "</div></div><div class='leform-properties-content'><div class='leform-properties-group'><div class='leform-properties-content-half leform-input-shortcode-selector'><input type='text' name='leform-notifications-from-email' value='" + (_values != null && _values.hasOwnProperty('from-email') ? leform_escape_html(_values['from-email']) : leform_escape_html(leform_notifications['from']['value']['email'])) + "' /><div class='leform-shortcode-selector' onmouseover='leform_shortcode_selector_set(this)';><span><i class='fas fa-code'></i></span></div></div><div class='leform-properties-content-half leform-input-shortcode-selector'><input type='text' name='leform-notifications-from-name' value='" + (_values != null && _values.hasOwnProperty('from-name') ? leform_escape_html(_values['from-name']) : leform_escape_html(leform_notifications['from']['value']['name'])) + "' /><div class='leform-shortcode-selector' onmouseover='leform_shortcode_selector_set(this)';><span><i class='fas fa-code'></i></span></div></div></div></div></div>";

	if (_values != null && _values.hasOwnProperty('logic-enable')) logic_enable = _values['logic-enable'];
	else logic_enable = leform_notifications['logic-enable']['value'];
	logic_enable_id = leform_random_string(16);
	html += "<div class='leform-properties-item' data-id='logic-enable'><div class='leform-properties-label'><label>" + leform_notifications['logic-enable']['label'] + "</label></div><div class='leform-properties-tooltip'><i class='fas fa-question-circle leform-tooltip-anchor'></i><div class='leform-tooltip-content'>" + leform_notifications['logic-enable']['tooltip'] + "</div></div><div class='leform-properties-content'><input class='leform-checkbox-toggle' type='checkbox' value='off' id='leform-notifications-logic-enable-" + logic_enable_id + "' name='leform-notifications-logic-enable'" + (logic_enable == "on" ? ' checked="checked"' : '') + "' onchange='return leform_properties_notifications_logic_enable_changed(this);' /><label for='leform-notifications-logic-enable-" + logic_enable_id + "'></label></div></div>";

	if (_values != null && _values.hasOwnProperty('logic')) property_value = _values['logic'];
	else property_value = leform_notifications['logic']['value'];
	if (input_ids.length > 0) {
		temp = "<div class='leform-properties-group leform-properties-logic-header'>";
		options = "";
		for (var option_key in leform_notifications['logic']['actions']) {
			if (leform_notifications['logic']['actions'].hasOwnProperty(option_key)) {
				options += "<option value='" + leform_escape_html(option_key) + "'" + (property_value["action"] == option_key ? " selected='selected'" : "") + ">" + leform_escape_html(leform_notifications['logic']['actions'][option_key]) + "</option>";
			}
		}
		temp += "<div class='leform-properties-content-half'><select name='leform-notifications-logic-action' id='leform-logic-action'>" + options + "</select></div>";
		options = "";
		for (var option_key in leform_notifications['logic']['operators']) {
			if (leform_notifications['logic']['operators'].hasOwnProperty(option_key)) {
				options += "<option value='" + leform_escape_html(option_key) + "'" + (property_value["operator"] == option_key ? " selected='selected'" : "") + ">" + leform_escape_html(leform_notifications['logic']['operators'][option_key]) + "</option>";
			}
		}
		temp += "<div class='leform-properties-content-half'><select name='leform-notifications-logic-operator' id='leform-logic-operator'>" + options + "</select></div>";
		temp += "</div>";
		options = "";
		for (var j = 0; j < property_value["rules"].length; j++) {
			if (input_ids.indexOf(parseInt(property_value["rules"][j]["field"], 10)) != -1) {
				options += leform_properties_logic_rule_get(null, property_value["rules"][j]["field"], property_value["rules"][j]["rule"], property_value["rules"][j]["token"]);
			}
		}
		temp += "<div class='leform-properties-logic-rules'>" + options + "</div><div class='leform-properties-logic-buttons'><a class='leform-admin-button leform-admin-button-gray leform-admin-button-small' href='#' onclick='return leform_properties_logic_rule_new(this, null);'><i class='fas fa-plus'></i><label>Add rule</label></a></div>";
	} else {
		temp = "<div class='leform-properties-inline-error'>There are no elements available to use for logic rules.</div>";
	}
	html += "<div class='leform-properties-item' data-id='logic'" + (logic_enable == "on" ? "" : " style='display:none;'") + "><div class='leform-properties-label'><label>" + leform_notifications['logic']['label'] + "</label></div><div class='leform-properties-tooltip'><i class='fas fa-question-circle leform-tooltip-anchor'></i><div class='leform-tooltip-content'>" + leform_notifications['logic']['tooltip'] + "</div></div><div class='leform-properties-content'>" + temp + "</div></div>";
	html += "</div></div>";

	if (_values == null) jQuery(".leform-properties-content-notifications .leform-properties-sub-item-body").slideUp(300);
	jQuery(".leform-properties-content-notifications").append(html);

	jQuery(".leform-properties-sub-item-new .leform-properties-tooltip .leform-tooltip-anchor").tooltipster({
		contentAsHTML: true,
		maxWidth: 360,
		theme: "tooltipster-dark",
		side: "bottom",
		content: "Default",
		functionFormat: function (instance, helper, content) {
			return jQuery(helper.origin).parent().find('.leform-tooltip-content').html();
		}
	});

	leform_init_tinymce();
	leform_init_url_with_variables();
	leform_properties_notifications_name_changed(jQuery(".leform-properties-content-notifications .leform-properties-sub-item").last().find("[name='leform-notifications-name']"));
	jQuery(".leform-properties-sub-item-new").slideDown(300);
	jQuery(".leform-properties-sub-item-new").removeClass("leform-properties-sub-item-new");
	return false;
}

function leform_properties_math_name_changed(_object) {
	var label = jQuery(_object).val().substring(0, 52) + (jQuery(_object).val().length > 52 ? "..." : "");
	jQuery(_object).closest(".leform-properties-sub-item").find(".leform-properties-sub-item-header>label").text(label);
	return false;
}
function leform_properties_math_details_toggle(_object) {
	jQuery(_object).closest(".leform-properties-sub-item").addClass("leform-freeze");
	jQuery(".leform-properties-content-math-expressions .leform-properties-sub-item").each(function () {
		if (!jQuery(this).hasClass("leform-freeze")) jQuery(this).find(".leform-properties-sub-item-body").slideUp(300);
	});
	jQuery(_object).closest(".leform-properties-sub-item").removeClass("leform-freeze");
	jQuery(_object).closest(".leform-properties-sub-item").find(".leform-properties-sub-item-body").slideToggle(300);
	return false;
}
function leform_properties_math_delete(_object) {
	leform_dialog_open({
		echo_html: function () {
			this.html("<div class='leform-dialog-message'>" + leform_esc_html__('Please confirm that you want to delete the item.') + "</div>");
			this.show();
		},
		ok_label: leform_esc_html__('Delete'),
		ok_function: function (e) {
			jQuery(_object).closest(".leform-properties-sub-item").slideUp(300, function () {
				jQuery(this).remove();
				jQuery(".leform-shortcode-selector-list-input").remove();
				jQuery(".leform-shortcode-selector-list-wysiwyg").replaceWith(leform_shortcode_selector_list_html("leform-shortcode-selector-list-wysiwyg"));
				jQuery(".leform-shortcode-selector-list-wysiwyg").replaceWith(leform_shortcode_selector_list_html("leform-shortcode-selector-list-wysiwyg"));
				jQuery(".leform-shortcode-selector-list-wysiwyg").each(function () {
					var textarea = jQuery(this).closest(".leform-wysiwyg").find(".leform-tinymce");
					if (textarea.length > 0) {
						if (typeof tinymce != typeof undefined) {
							var editor = tinymce.get(jQuery(textarea).attr("id"));
							jQuery(textarea).closest(".leform-wysiwyg").find(".leform-shortcode-selector-list-item").on("click", function () {
								editor.insertContent(jQuery(this).attr("data-code"));
							});
						}
					}
				});
			});
			leform_element_properties_data_changed = true;
			leform_dialog_close();
		}
	});
	return false;
}
function leform_properties_math_add(_values) {
	var extra_class = "", html = "", tooltip_html, property_value;

	if (_values == null) {
		extra_class = " leform-properties-sub-item-new";
		leform_element_properties_data_changed = true;
	} else {
		extra_class = " leform-properties-sub-item-exist";
	}
	html += "<div class='leform-properties-sub-item" + extra_class + "'><div class='leform-properties-sub-item-header'><div class='leform-properties-sub-item-header-tools'><span onclick='return leform_properties_math_delete(this);'><i class='fas fa-trash-alt'></i></span><span onclick='return leform_properties_math_details_toggle(this);'><i class='fas fa-cog'></i></span></div><label></label></div><div class='leform-properties-sub-item-body'>";

	if (_values != null && _values.hasOwnProperty('id')) {
		property_value = _values['id'];
	} else {
		leform_form_last_id++;
		property_value = leform_form_last_id;
	}
	html += "<div class='leform-properties-item' data-id='id'><div class='leform-properties-label'><label>"
		+ leform_esc_html__(leform_math_expressions_meta['id']['label'])
		+ "</label></div><div class='leform-properties-tooltip'><i class='fas fa-question-circle leform-tooltip-anchor'></i><div class='leform-tooltip-content'>"
		+ leform_esc_html__(leform_math_expressions_meta['id']['tooltip'])
		+ "</div></div><div class='leform-properties-content'><div class='leform-number'><input type='text' name='leform-math-id' value='" + property_value + "' readonly='readonly' onclick='this.focus();this.select();' /></div></div></div>";
	html += "<div class='leform-properties-item' data-id='name'><div class='leform-properties-label'><label>"
		+ leform_esc_html__(leform_math_expressions_meta['name']['label'])
		+ "</label></div><div class='leform-properties-tooltip'><i class='fas fa-question-circle leform-tooltip-anchor'></i><div class='leform-tooltip-content'>"
		+ leform_esc_html__(leform_math_expressions_meta['name']['tooltip'])
		+ "</div></div><div class='leform-properties-content'><input type='text' name='leform-math-name' value='" + (_values != null && _values.hasOwnProperty('name') ? leform_escape_html(_values['name']) : leform_escape_html(leform_math_expressions_meta['name']['value'])) + "' oninput='return leform_properties_math_name_changed(this);' /></div></div>";
	html += "<div class='leform-properties-item' data-id='expression'><div class='leform-properties-label'><label>"
		+ leform_esc_html__(leform_math_expressions_meta['expression']['label'])
		+ "</label></div><div class='leform-properties-tooltip'><i class='fas fa-question-circle leform-tooltip-anchor'></i><div class='leform-tooltip-content'>"
		+ leform_esc_html__(leform_math_expressions_meta['expression']['tooltip'])
		+ "</div></div><div class='leform-properties-content'><div class='leform-properties-group leform-input-shortcode-selector'><input type='text' name='leform-math-expression' value='" + (_values != null && _values.hasOwnProperty('expression') ? leform_escape_html(_values['expression']) : leform_escape_html(leform_math_expressions_meta['expression']['value'])) + "' /><div class='leform-shortcode-selector' data-disabled-groups='math' onmouseover='leform_shortcode_selector_set(this)';><span><i class='fas fa-code'></i></span></div></div></div></div>";
	html += "<div class='leform-properties-item' data-id='default'><div class='leform-properties-label'><label>"
		+ leform_esc_html__(leform_math_expressions_meta['default']['label'])
		+ "</label></div><div class='leform-properties-tooltip'><i class='fas fa-question-circle leform-tooltip-anchor'></i><div class='leform-tooltip-content'>"
		+ leform_esc_html__(leform_math_expressions_meta['default']['tooltip'])
		+ "</div></div><div class='leform-properties-content'><input type='text' name='leform-math-default' value='" + (_values != null && _values.hasOwnProperty('default') ? leform_escape_html(_values['default']) : leform_escape_html(leform_math_expressions_meta['default']['value'])) + "' /></div></div>";
	if (_values != null && _values.hasOwnProperty('decimal-digits')) {
		property_value = _values['decimal-digits'];
	} else {
		property_value = leform_math_expressions_meta['decimal-digits']['value'];
	}
	html += "<div class='leform-properties-item' data-id='decimal-digits'><div class='leform-properties-label'><label>"
		+ leform_esc_html__(leform_math_expressions_meta['decimal-digits']['label'])
		+ "</label></div><div class='leform-properties-tooltip'><i class='fas fa-question-circle leform-tooltip-anchor'></i><div class='leform-tooltip-content'>"
		+ leform_esc_html__(leform_math_expressions_meta['decimal-digits']['tooltip'])
		+ "</div></div><div class='leform-properties-content'><div class='leform-number'><select name='leform-math-decimal-digits'><option value='0'" + (property_value == 0 ? " selected='selected'" : "") + ">0</option><option value='1'" + (property_value == 1 ? " selected='selected'" : "") + ">1</option><option value='2'" + (property_value == 2 ? " selected='selected'" : "") + ">2</option><option value='3'" + (property_value == 3 ? " selected='selected'" : "") + ">3</option><option value='4'" + (property_value == 4 ? " selected='selected'" : "") + ">4</option><option value='5'" + (property_value == 5 ? " selected='selected'" : "") + ">5</option><option value='6'" + (property_value == 6 ? " selected='selected'" : "") + ">6</option><option value='7'" + (property_value == 7 ? " selected='selected'" : "") + ">7</option><option value='8'" + (property_value == 8 ? " selected='selected'" : "") + ">8</option></select></div></div></div>";
	html += "</div></div>";

	if (_values == null) {
		jQuery(".leform-properties-content-math-expressions .leform-properties-sub-item-body").slideUp(300);
	}
	jQuery(".leform-properties-content-math-expressions").append(html);

	jQuery(".leform-properties-sub-item-new .leform-properties-tooltip .leform-tooltip-anchor").tooltipster({
		contentAsHTML: true,
		maxWidth: 360,
		theme: "tooltipster-dark",
		side: "bottom",
		content: "Default",
		functionFormat: function (instance, helper, content) {
			return jQuery(helper.origin).parent().find('.leform-tooltip-content').html();
		}
	});

	leform_properties_math_name_changed(jQuery(".leform-properties-content-math-expressions .leform-properties-sub-item").last().find("[name='leform-math-name']"));
	jQuery(".leform-properties-sub-item-new").slideDown(300);
	jQuery(".leform-properties-sub-item-new").removeClass("leform-properties-sub-item-new");
	jQuery(".leform-shortcode-selector-list-input").remove();
	jQuery(".leform-shortcode-selector-list-wysiwyg").replaceWith(leform_shortcode_selector_list_html("leform-shortcode-selector-list-wysiwyg"));
	jQuery(".leform-shortcode-selector-list-wysiwyg").each(function () {
		var textarea = jQuery(this).closest(".leform-wysiwyg").find(".leform-tinymce");
		if (textarea.length > 0) {
			if (typeof tinymce != typeof undefined) {
				var editor = tinymce.get(jQuery(textarea).attr("id"));
				jQuery(textarea).closest(".leform-wysiwyg").find(".leform-shortcode-selector-list-item").on("click", function () {
					editor.insertContent(jQuery(this).attr("data-code"));
				});
			}
		}
	});
	return false;
}

function leform_properties_confirmations_name_changed(_object) {
	var label = jQuery(_object).val().substring(0, 52) + (jQuery(_object).val().length > 52 ? "..." : "");
	jQuery(_object).closest(".leform-properties-sub-item").find(".leform-properties-sub-item-header>label").text(label);
	return false;
}
function leform_properties_confirmations_logic_enable_changed(_object) {
	var parent = jQuery(_object).closest(".leform-properties-sub-item");
	if (jQuery(_object).is(":checked")) jQuery(parent).find(".leform-properties-item[data-id='logic']").fadeIn(300);
	else jQuery(parent).find(".leform-properties-item[data-id='logic']").fadeOut(300);
	return false;
}
function leform_properties_confirmations_type_changed(_object) {
	var parent = jQuery(_object).closest(".leform-properties-sub-item");
	switch (jQuery(_object).val()) {
		case 'page':
			jQuery(parent).find(".leform-properties-item[data-id='message']").hide();
			jQuery(parent).find(".leform-properties-item[data-id='url']").hide();
			jQuery(parent).find(".leform-properties-item[data-id='delay']").hide();
			jQuery(parent).find(".leform-properties-item[data-id='payment-gateway']").hide();
			break;
		case 'page-redirect':
			jQuery(parent).find(".leform-properties-item[data-id='message']").hide();
			jQuery(parent).find(".leform-properties-item[data-id='url']").show();
			jQuery(parent).find(".leform-properties-item[data-id='delay']").show();
			jQuery(parent).find(".leform-properties-item[data-id='payment-gateway']").hide();
			break;
		case 'page-payment':
			jQuery(parent).find(".leform-properties-item[data-id='message']").hide();
			jQuery(parent).find(".leform-properties-item[data-id='url']").hide();
			jQuery(parent).find(".leform-properties-item[data-id='delay']").show();
			jQuery(parent).find(".leform-properties-item[data-id='payment-gateway']").show();
			break;
		case 'message':
			jQuery(parent).find(".leform-properties-item[data-id='message']").show();
			jQuery(parent).find(".leform-properties-item[data-id='url']").hide();
			jQuery(parent).find(".leform-properties-item[data-id='delay']").show();
			jQuery(parent).find(".leform-properties-item[data-id='payment-gateway']").hide();
			break;
		case 'message-redirect':
			jQuery(parent).find(".leform-properties-item[data-id='message']").show();
			jQuery(parent).find(".leform-properties-item[data-id='url']").show();
			jQuery(parent).find(".leform-properties-item[data-id='delay']").show();
			jQuery(parent).find(".leform-properties-item[data-id='payment-gateway']").hide();
			break;
		case 'message-payment':
			jQuery(parent).find(".leform-properties-item[data-id='message']").show();
			jQuery(parent).find(".leform-properties-item[data-id='url']").hide();
			jQuery(parent).find(".leform-properties-item[data-id='delay']").show();
			jQuery(parent).find(".leform-properties-item[data-id='payment-gateway']").show();
			break;
		case 'redirect':
			jQuery(parent).find(".leform-properties-item[data-id='message']").hide();
			jQuery(parent).find(".leform-properties-item[data-id='url']").show();
			jQuery(parent).find(".leform-properties-item[data-id='delay']").hide();
			jQuery(parent).find(".leform-properties-item[data-id='payment-gateway']").hide();
			break;
		case 'payment':
			jQuery(parent).find(".leform-properties-item[data-id='message']").hide();
			jQuery(parent).find(".leform-properties-item[data-id='url']").hide();
			jQuery(parent).find(".leform-properties-item[data-id='delay']").hide();
			jQuery(parent).find(".leform-properties-item[data-id='payment-gateway']").show();
			break;
		default:
			break;
	}
	return false;
}
function leform_properties_confirmations_details_toggle(_object) {
	jQuery(_object).closest(".leform-properties-sub-item").addClass("leform-freeze");
	jQuery(".leform-properties-content-confirmations .leform-properties-sub-item").each(function () {
		if (!jQuery(this).hasClass("leform-freeze")) jQuery(this).find(".leform-properties-sub-item-body").slideUp(300);
	});
	jQuery(_object).closest(".leform-properties-sub-item").removeClass("leform-freeze");
	jQuery(_object).closest(".leform-properties-sub-item").find(".leform-properties-sub-item-body").slideToggle(300);
	return false;
}
function leform_properties_confirmations_delete(_object) {
	leform_dialog_open({
		echo_html: function () {
			this.html("<div class='leform-dialog-message'>" + leform_esc_html__('Please confirm that you want to delete the item.') + "</div>");
			this.show();
		},
		ok_label: leform_esc_html__('Delete'),
		ok_function: function (e) {
			jQuery(_object).closest(".leform-properties-sub-item").slideUp(300, function () {
				jQuery(this).remove();
			});
			leform_element_properties_data_changed = true;
			leform_dialog_close();
		}
	});
	return false;
}
function leform_properties_confirmations_add(_values) {
	var extra_class = "", html = "", temp = "", tooltip_html, selected, property_value, logic_enable, logic_enable_id;

	if (_values == null) {
		extra_class = " leform-properties-sub-item-new";
		leform_element_properties_data_changed = true;
	} else extra_class = " leform-properties-sub-item-exist";
	html += "<div class='leform-properties-sub-item" + extra_class + "'><div class='leform-properties-sub-item-header'><div class='leform-properties-sub-item-header-tools'><span onclick='return leform_properties_confirmations_delete(this);'><i class='fas fa-trash-alt'></i></span><span onclick='return leform_properties_confirmations_details_toggle(this);'><i class='fas fa-cog'></i></span></div><label></label></div><div class='leform-properties-sub-item-body'>";
	html += "<div class='leform-properties-item' data-id='name'><div class='leform-properties-label'><label>" + leform_confirmations['name']['label'] + "</label></div><div class='leform-properties-tooltip'><i class='fas fa-question-circle leform-tooltip-anchor'></i><div class='leform-tooltip-content'>" + leform_confirmations['name']['tooltip'] + "</div></div><div class='leform-properties-content'><input type='text' name='leform-confirmations-name' value='" + (_values != null && _values.hasOwnProperty('name') ? leform_escape_html(_values['name']) : leform_escape_html(leform_confirmations['name']['value'])) + "' oninput='return leform_properties_confirmations_name_changed(this);' /></div></div>";
	var options = "";
	if (_values != null && _values.hasOwnProperty('type')) property_value = _values['type'];
	else property_value = leform_confirmations['type']['value'];
	for (var option_key in leform_confirmations['type']['options']) {
		if (leform_confirmations['type']['options'].hasOwnProperty(option_key)) {
			selected = "";
			if (option_key == property_value) selected = " selected='selected'";
			options += "<option" + selected + " value='" + leform_escape_html(option_key) + "'>" + leform_escape_html(leform_confirmations['type']['options'][option_key]) + "</option>";
		}
	}
	html += "<div class='leform-properties-item' data-id='type'><div class='leform-properties-label'><label>" + leform_confirmations['type']['label'] + "</label></div><div class='leform-properties-tooltip'><i class='fas fa-question-circle leform-tooltip-anchor'></i><div class='leform-tooltip-content'>" + leform_confirmations['type']['tooltip'] + "</div></div><div class='leform-properties-content'><select name='leform-confirmations-type' onchange='return leform_properties_confirmations_type_changed(this);'>" + options + "</select></div></div>";
	var message_id = leform_random_string(16);
	html += "<div class='leform-properties-item' data-id='message'><div class='leform-properties-label'><label>" + leform_confirmations['message']['label'] + "</label></div><div class='leform-properties-tooltip'><i class='fas fa-question-circle leform-tooltip-anchor'></i><div class='leform-tooltip-content'>" + leform_confirmations['message']['tooltip'] + "</div></div><div class='leform-properties-content leform-wysiwyg'><textarea class='leform-tinymce leform-tinymce-pre' name='leform-confirmations-message' id='leform-confirmations-message-" + message_id + "'>" + (_values != null && _values.hasOwnProperty('message') ? leform_escape_html(_values['message']) : leform_escape_html(leform_confirmations['message']['value'])) + "</textarea></div></div>";
	html += "<div class='leform-properties-item' data-id='url'><div class='leform-properties-label'><label>" + leform_confirmations['url']['label'] + "</label></div><div class='leform-properties-tooltip'><i class='fas fa-question-circle leform-tooltip-anchor'></i><div class='leform-tooltip-content'>" + leform_confirmations['url']['tooltip'] + "</div></div><div class='leform-properties-content'><div class='leform-properties-group leform-input-shortcode-selector'><input type='text' name='leform-confirmations-url' value='" + (_values != null && _values.hasOwnProperty('url') ? leform_escape_html(_values['url']) : leform_escape_html(leform_confirmations['url']['value'])) + "' /><div class='leform-shortcode-selector' onmouseover='leform_shortcode_selector_set(this)';><span><i class='fas fa-code'></i></span></div></div></div></div>";

	html += "<div class='leform-properties-item' data-id='delay'><div class='leform-properties-label'><label>" + leform_confirmations['delay']['label'] + "</label></div><div class='leform-properties-tooltip'><i class='fas fa-question-circle leform-tooltip-anchor'></i><div class='leform-tooltip-content'>" + leform_confirmations['delay']['tooltip'] + "</div></div><div class='leform-properties-content'><div><input class='leform-number' type='text' name='leform-confirmations-delay' value='" + (_values != null && _values.hasOwnProperty('delay') ? leform_escape_html(_values['delay']) : leform_escape_html(leform_confirmations['delay']['value'])) + "' />" + (leform_confirmations['delay'].hasOwnProperty("unit") ? " " + leform_confirmations['delay']["unit"] : "") + "</div></div></div>";

	property_value = (_values != null && _values.hasOwnProperty('payment-gateway') ? leform_escape_html(_values['payment-gateway']) : leform_escape_html(leform_confirmations['payment-gateway']['value']));
	options = "<option value=''>Select payment gateway</option>";
	for (var key in leform_form_options['payment-gateways']) {
		selected = "";
		if (leform_form_options['payment-gateways'][key]['id'] == property_value) selected = " selected='selected'";
		options += "<option" + selected + " value='" + leform_escape_html(leform_form_options['payment-gateways'][key]['id']) + "'>" + leform_escape_html(leform_form_options['payment-gateways'][key]['name']) + "</option>";
	}
	html += "<div class='leform-properties-item' data-id='payment-gateway'><div class='leform-properties-label'><label>" + leform_confirmations['payment-gateway']['label'] + "</label></div><div class='leform-properties-tooltip'><i class='fas fa-question-circle leform-tooltip-anchor'></i><div class='leform-tooltip-content'>" + leform_confirmations['payment-gateway']['tooltip'] + "</div></div><div class='leform-properties-content'><select class='leform-payment-gateways-select' name='leform-confirmations-payment-gateway'>" + options + "</select></div></div>";

	var reset_form;
	if (_values != null && _values.hasOwnProperty('reset-form')) reset_form = _values['reset-form'];
	else reset_form = leform_confirmations['reset-form']['value'];
	var reset_form_id = leform_random_string(16);
	html += "<div class='leform-properties-item' data-id='reset-form'><div class='leform-properties-label'><label>" + leform_confirmations['reset-form']['label'] + "</label></div><div class='leform-properties-tooltip'><i class='fas fa-question-circle leform-tooltip-anchor'></i><div class='leform-tooltip-content'>" + leform_confirmations['reset-form']['tooltip'] + "</div></div><div class='leform-properties-content'><input class='leform-checkbox-toggle' type='checkbox' value='off' id='leform-confirmations-reset-form-" + reset_form_id + "' name='leform-confirmations-reset-form'" + (reset_form == "on" ? ' checked="checked"' : '') + "' /><label for='leform-confirmations-reset-form-" + reset_form_id + "'></label></div></div>";

	if (_values != null && _values.hasOwnProperty('logic-enable')) logic_enable = _values['logic-enable'];
	else logic_enable = leform_confirmations['logic-enable']['value'];
	logic_enable_id = leform_random_string(16);
	html += "<div class='leform-properties-item' data-id='logic-enable'><div class='leform-properties-label'><label>" + leform_confirmations['logic-enable']['label'] + "</label></div><div class='leform-properties-tooltip'><i class='fas fa-question-circle leform-tooltip-anchor'></i><div class='leform-tooltip-content'>" + leform_confirmations['logic-enable']['tooltip'] + "</div></div><div class='leform-properties-content'><input class='leform-checkbox-toggle' type='checkbox' value='off' id='leform-confirmations-logic-enable-" + logic_enable_id + "' name='leform-confirmations-logic-enable'" + (logic_enable == "on" ? ' checked="checked"' : '') + "' onchange='return leform_properties_confirmations_logic_enable_changed(this);' /><label for='leform-confirmations-logic-enable-" + logic_enable_id + "'></label></div></div>";

	if (_values != null && _values.hasOwnProperty('logic')) property_value = _values['logic'];
	else property_value = leform_confirmations['logic']['value'];
	var input_ids = new Array();
	for (var i = 0; i < leform_form_elements.length; i++) {
		if (leform_form_elements[i] == null) continue;
		if (leform_toolbar_tools.hasOwnProperty(leform_form_elements[i]['type']) && leform_toolbar_tools[leform_form_elements[i]['type']]['type'] == 'input') {
			input_ids.push(leform_form_elements[i]["id"]);
		}
	}
	if (input_ids.length > 0) {
		temp = "<div class='leform-properties-group leform-properties-logic-header'>";
		options = "";
		for (var option_key in leform_confirmations['logic']['actions']) {
			if (leform_confirmations['logic']['actions'].hasOwnProperty(option_key)) {
				options += "<option value='" + leform_escape_html(option_key) + "'" + (property_value["action"] == option_key ? " selected='selected'" : "") + ">" + leform_escape_html(leform_confirmations['logic']['actions'][option_key]) + "</option>";
			}
		}
		temp += "<div class='leform-properties-content-half'><select name='leform-confirmations-logic-action' id='leform-logic-action'>" + options + "</select></div>";
		options = "";
		for (var option_key in leform_confirmations['logic']['operators']) {
			if (leform_confirmations['logic']['operators'].hasOwnProperty(option_key)) {
				options += "<option value='" + leform_escape_html(option_key) + "'" + (property_value["operator"] == option_key ? " selected='selected'" : "") + ">" + leform_escape_html(leform_confirmations['logic']['operators'][option_key]) + "</option>";
			}
		}
		temp += "<div class='leform-properties-content-half'><select name='leform-confirmations-logic-operator' id='leform-logic-operator'>" + options + "</select></div>";
		temp += "</div>";
		options = "";
		for (var j = 0; j < property_value["rules"].length; j++) {
			if (input_ids.indexOf(parseInt(property_value["rules"][j]["field"], 10)) != -1) {
				options += leform_properties_logic_rule_get(null, property_value["rules"][j]["field"], property_value["rules"][j]["rule"], property_value["rules"][j]["token"]);
			}
		}
		temp += "<div class='leform-properties-logic-rules'>" + options + "</div><div class='leform-properties-logic-buttons'><a class='leform-admin-button leform-admin-button-gray leform-admin-button-small' href='#' onclick='return leform_properties_logic_rule_new(this, null);'><i class='fas fa-plus'></i><label>Add rule</label></a></div>";
	} else {
		temp = "<div class='leform-properties-inline-error'>There are no elements available to use for logic rules.</div>";
	}
	html += "<div class='leform-properties-item' data-id='logic'" + (logic_enable == "on" ? "" : " style='display:none;'") + "><div class='leform-properties-label'><label>" + leform_confirmations['logic']['label'] + "</label></div><div class='leform-properties-tooltip'><i class='fas fa-question-circle leform-tooltip-anchor'></i><div class='leform-tooltip-content'>" + leform_confirmations['logic']['tooltip'] + "</div></div><div class='leform-properties-content'>" + temp + "</div></div>";
	html += "</div></div>";

	if (_values == null) jQuery(".leform-properties-content-confirmations .leform-properties-sub-item-body").slideUp(300);
	jQuery(".leform-properties-content-confirmations").append(html);

	jQuery(".leform-properties-sub-item-new .leform-properties-tooltip .leform-tooltip-anchor").tooltipster({
		contentAsHTML: true,
		maxWidth: 360,
		theme: "tooltipster-dark",
		side: "bottom",
		content: "Default",
		functionFormat: function (instance, helper, content) {
			return jQuery(helper.origin).parent().find('.leform-tooltip-content').html();
		}
	});

	leform_init_tinymce();
	leform_init_url_with_variables();
	leform_properties_confirmations_name_changed(jQuery(".leform-properties-content-confirmations .leform-properties-sub-item").last().find("[name='leform-confirmations-name']"));
	leform_properties_confirmations_type_changed(jQuery(".leform-properties-content-confirmations .leform-properties-sub-item").last().find("[name='leform-confirmations-type']"));
	jQuery(".leform-properties-sub-item-new").slideDown(300);
	jQuery(".leform-properties-sub-item-new").removeClass("leform-properties-sub-item-new");
	return false;
}
function leform_properties_filters_add(_type, _filter, _values) {
	var extra_class = "", html = "", tooltip_html, selected, property_value = "";
	var seq = 0, last;
	last = jQuery(".leform-properties-content-filters .leform-properties-sub-item").last();
	if (jQuery(last).length) seq = parseInt(jQuery(last).attr("data-seq"), 10) + 1;
	if (leform_meta[_type].hasOwnProperty("filters") && leform_filters.hasOwnProperty(_filter)) {
		if (_values == null) {
			extra_class = " leform-properties-sub-item-new";
			leform_element_properties_data_changed = true;
		} else extra_class = " leform-properties-sub-item-exist";
		if (leform_filters[_filter].hasOwnProperty("properties")) property_value = "<span onclick='return leform_properties_filters_details_toggle(this);'><i class='fas fa-cog'></i></span>";
		html += "<div class='leform-properties-sub-item" + extra_class + "' data-type='" + _filter + "' data-seq='" + seq + "'><div class='leform-properties-sub-item-header'><div class='leform-properties-sub-item-header-tools'><span onclick='return leform_properties_filters_delete(this);'><i class='fas fa-trash-alt'></i></span>" + property_value + "</div><label>"
			+ leform_esc_html__(leform_filters[_filter]["label"])
			+ "</label></div><div class='leform-properties-sub-item-body'>";
		for (var key in leform_filters[_filter]["properties"]) {
			if (leform_filters[_filter]["properties"].hasOwnProperty(key)) {
				tooltip_html = "";
				if (leform_filters[_filter]["properties"][key].hasOwnProperty('tooltip')) {
					tooltip_html = "<i class='fas fa-question-circle leform-tooltip-anchor'></i><div class='leform-tooltip-content'>"
						+ leform_esc_html__(leform_filters[_filter]["properties"][key]['tooltip'])
						+ "</div>";
				}
				property_value = "";
				if (_values != null && _values.hasOwnProperty("properties") && _values["properties"].hasOwnProperty(key)) property_value = _values["properties"][key];
				switch (leform_filters[_filter]["properties"][key]['type']) {
					case 'text':
						html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>"
							+ leform_esc_html__(leform_filters[_filter]["properties"][key]['label'])
							+ "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'><input type='text' name='leform-filters-" + key + "' id='leform-filters-" + seq + "-" + key + "' value='" + leform_escape_html(property_value) + "' placeholder='" + leform_escape_html(property_value) + "' /></div></div>";
						break;

					case 'integer':
						html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>"
							+ leform_esc_html__(leform_filters[_filter]["properties"][key]['label'])
							+ "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'><div class='leform-number'><input type='text' name='leform-filters-" + key + "' id='leform-filters-" + seq + "-" + key + "' value='" + leform_escape_html(property_value) + "' placeholder='' /></div></div></div>";
						break;

					case 'checkbox':
						selected = "";
						if (property_value == "on") selected = " checked='checked'";
						html += "<div class='leform-properties-item' data-id='" + key + "'><div class='leform-properties-label'><label>"
							+ leform_esc_html__(leform_filters[_filter]["properties"][key]['label'])
							+ "</label></div><div class='leform-properties-tooltip'>" + tooltip_html + "</div><div class='leform-properties-content'><input class='leform-checkbox-toggle' type='checkbox' value='off' name='leform-filters-" + key + "' id='leform-filters-" + seq + "-" + key + "'" + selected + "' /><label for='leform-filters-" + seq + "-" + key + "'></label></div></div>";
						break;

					default:
						break;
				}
			}
		}
		html += "</div></div>";
		if (_values == null) jQuery(".leform-properties-content-filters .leform-properties-sub-item-body").slideUp(300);
		jQuery(".leform-properties-content-filters").append(html);
		jQuery(".leform-properties-sub-item-new .leform-properties-tooltip .leform-tooltip-anchor").tooltipster({
			contentAsHTML: true,
			maxWidth: 360,
			theme: "tooltipster-dark",
			side: "bottom",
			content: "Default",
			functionFormat: function (instance, helper, content) {
				return jQuery(helper.origin).parent().find('.leform-tooltip-content').html();
			}
		});

		jQuery(".leform-properties-sub-item-new").slideDown(300);
		jQuery(".leform-properties-sub-item-new").removeClass("leform-properties-sub-item-new");
	}
	return false;
}
function leform_properties_filters_details_toggle(_object) {
	jQuery(_object).closest(".leform-properties-sub-item").addClass("leform-freeze");
	jQuery(".leform-properties-content-filters .leform-properties-sub-item").each(function () {
		if (!jQuery(this).hasClass("leform-freeze")) jQuery(this).find(".leform-properties-sub-item-body").slideUp(300);
	});
	jQuery(_object).closest(".leform-properties-sub-item").removeClass("leform-freeze");
	jQuery(_object).closest(".leform-properties-sub-item").find(".leform-properties-sub-item-body").slideToggle(300);
	return false;
}
function leform_properties_filters_delete(_object) {
	leform_dialog_open({
		echo_html: function () {
			this.html("<div class='leform-dialog-message'>" + leform_esc_html__('Please confirm that you want to delete the item.') + "</div>");
			this.show();
		},
		ok_label: leform_esc_html__('Delete'),
		ok_function: function (e) {
			jQuery(_object).closest(".leform-properties-sub-item").slideUp(300, function () {
				jQuery(this).remove();
			});
			leform_element_properties_data_changed = true;
			leform_dialog_close();
		}
	});
	return false;
}
function leform_properties_logic_rule_delete(_object) {
	leform_dialog_open({
		echo_html: function () {
			this.html("<div class='leform-dialog-message'>" + leform_esc_html__('Please confirm that you want to delete the item.') + "</div>");
			this.show();
		},
		ok_label: leform_esc_html__('Delete'),
		ok_function: function (e) {
			jQuery(_object).closest(".leform-properties-logic-rule").slideUp(300, function () {
				jQuery(this).remove();
			});
			leform_element_properties_data_changed = true;
			leform_dialog_close();
		}
	});
	return false;
}
function leform_properties_logic_rule_token_change(_object) {
	var rule = jQuery(_object).closest(".leform-properties-logic-rule");
	var html = leform_properties_logic_rule_token_get(jQuery(rule).find(".leform-properties-logic-rule-field").val(), jQuery(rule).find(".leform-properties-logic-rule-rule").val(), "");
	jQuery(rule).find(".leform-properties-logic-rule-token-container").html(html);
	return false;
}
function leform_properties_logic_rule_token_get(_field, _rule, _token) {
	var html = "", input = null, options = "";

	for (var i = 0; i < leform_form_elements.length; i++) {
		if (leform_form_elements[i] == null) continue;
		if (leform_form_elements[i]['id'] == _field) {
			input = leform_form_elements[i];
			break;
		}
	}
	if (input == null) {
		html = "<input class='leform-properties-logic-rule-token' type='text' placeholder='"
			+ leform_esc_html__("Enter your value...")
			+ "' value='" + (_token ? leform_escape_html(_token) : "") + "' />";
	} else {
		if (_rule == 'is-empty' || _rule == 'is-not-empty') {
			html = "<input class='leform-properties-logic-rule-token' type='hidden' value='' />";
		} else if (_rule == 'is' || _rule == 'is-not') {
			if (input.hasOwnProperty("options") && input["options"].length > 0) {
				for (var i = 0; i < input["options"].length; i++) {
					options += "<option value='" + leform_escape_html(input["options"][i]["value"]) + "'" + (input["options"][i]["value"] == _token ? " selected='selected'" : "") + ">" + leform_escape_html(input["options"][i]["label"]) + "</option>";
				}
				html = "<select class='leform-properties-logic-rule-token'>" + options + "</select>";
			} else {
				html = "<input class='leform-properties-logic-rule-token' type='text' placeholder='"
					+ leform_esc_html__("Enter your value...")
					+ "' value='" + (_token ? leform_escape_html(_token) : "") + "' />";
			}
		} else {
			html = "<input class='leform-properties-logic-rule-token' type='text' placeholder='"
				+ leform_esc_html__("Enter your value...")
				+ "' value='" + (_token ? leform_escape_html(_token) : "") + "' />";
		}
	}
	return html;
}
function leform_properties_logic_rule_get(_field_id, _field, _rule, _token) {
	var temp = "", html = "", field_options = "", rule_options = "";

	var field_selected = null, rule_selected = null;
	var input_fields = leform_input_sort();
	if (input_fields.length > 0) {
		for (var j = 0; j < input_fields.length; j++) {
			if (temp != input_fields[j]['page-id']) {
				if (temp != "") {
					field_options += "</optgroup>";
				}
				field_options += "<optgroup label='"
					+ leform_esc_html__(leform_escape_html(input_fields[j]['page-name']))
					+ "'>";
				temp = input_fields[j]['page-id'];
			}
			if (field_selected == null || _field == input_fields[j]['id']) {
				field_selected = input_fields[j]['id'];
			}
			field_options += "<option value='" + input_fields[j]['id'] + "'" + (input_fields[j]['id'] == _field ? " selected='selected'" : "") + ">" + input_fields[j]['id'] + " | " + leform_escape_html(input_fields[j]['name']) + "</option>";
		}
		field_options += "</optgroup>";
	}
	for (var key in leform_logic_rules) {
		if (rule_selected == null || _rule == key) {
			rule_selected = key;
		}
		if (leform_logic_rules.hasOwnProperty(key)) {
			rule_options += "<option value='" + key + "'" + (key == _rule ? " selected='selected'" : "") + ">" + leform_escape_html(leform_logic_rules[key]) + "</option>";
		}
	}
	var field_token = leform_properties_logic_rule_token_get(field_selected, rule_selected, _token);
	html = "<div class='leform-properties-logic-rule'><div class='leform-properties-logic-rule-table'><div><select class='leform-properties-logic-rule-field' onchange='leform_properties_logic_rule_token_change(this);'>" + field_options + "</select></div><div><select class='leform-properties-logic-rule-rule' onchange='leform_properties_logic_rule_token_change(this);'>" + rule_options + "</select></div><div class='leform-properties-logic-rule-token-container'>" + field_token + "</div><div><span onclick='return leform_properties_logic_rule_delete(this);' title='"
		+ leform_esc_html__("Delete the option")
		+ "'><i class='fas fa-trash-alt'></i></span></div></div></div>";
	return html;
}
function leform_properties_logic_rule_new(_object, _field_id) {
	var rule_html = leform_properties_logic_rule_get(_field_id, null, null, null);
	jQuery(_object).closest(".leform-properties-content").find(".leform-properties-logic-rules").append(rule_html);
	leform_element_properties_data_changed = true;
	return false;
}

function leform_properties_attachment_media(_object) {
	var input = jQuery(_object).parent().children("input");
	var media_frame = wp.media({
		title: 'Select Media',
		multiple: false
	});
	media_frame.on("select", function () {
		var attachment = media_frame.state().get("selection").first();
		jQuery(input).val(attachment.attributes.id + " | " + attachment.attributes.filename);
	});
	media_frame.open();
}
function leform_properties_attachment_delete(_object) {
	var attachment = jQuery(_object).closest(".leform-properties-attachment");
	jQuery(attachment).slideUp(300, function () { jQuery(attachment).remove(); });
	leform_element_properties_data_changed = true;
	return false;
}
function leform_properties_attachment_token_change(_object) {
	var attachment = jQuery(_object).closest(".leform-properties-attachment");
	var html = leform_properties_attachment_token_get(jQuery(attachment).find(".leform-properties-attachment-source").val(), "");
	jQuery(attachment).find(".leform-properties-attachment-token-container").html(html);
	return false;
}
function leform_properties_attachment_token_get(_source, _token) {
	var html = "", input = null, options = "";
	if (_source == "media-library") html = "<div class='leform-media-id'><input class='leform-properties-attachment-token' type='text' placeholder='' readonly='readonly' value='" + leform_escape_html(_token) + "' onclick='leform_properties_attachment_media(this);' /><span onclick='leform_properties_attachment_media(this);'><i class='far fa-file'></i></span></div>";
	else if (_source == "file") html = "<input class='leform-properties-attachment-token' type='text' placeholder='Enter the FULL path of the file on the server (not URL!).' value='" + leform_escape_html(_token) + "' />";
	else {
		for (var i = 0; i < leform_form_elements.length; i++) {
			if (leform_form_elements[i] == null) continue;
			if (leform_form_elements[i]['type'] == 'file') {
				options += "<option value='" + leform_form_elements[i]['id'] + "'" + (leform_form_elements[i]['id'] == _token ? " selected='selected'" : "") + ">" + leform_form_elements[i]['id'] + " | " + leform_escape_html(leform_form_elements[i]['name']) + "</option>";
			}
		}
		if (options != "") html = "<select class='leform-properties-attachment-token'>" + options + "</select>";
		else html = "No form elements (files) found.";
	}
	return html;
}
function leform_properties_attachment_get(_source, _token) {
	var token = leform_properties_attachment_token_get(_source, _token);
	var html = "<div class='leform-properties-attachment'><div class='leform-properties-attachment-table'><div><select class='leform-properties-attachment-source' onchange='leform_properties_attachment_token_change(this);'><option value='form-element'" + (_source == "form-element" ? " selected='selected'" : "") + ">Form Element</option>" + (typeof UAP_CORE == typeof undefined ? "<option value='media-library'" + (_source == "media-library" ? " selected='selected'" : "") + ">Media Library</option>" : "") + "<option value='file'" + (_source == "file" ? " selected='selected'" : "") + ">File on Server</option></select></div><div class='leform-properties-attachment-token-container'>" + token + "</div><div><span onclick='return leform_properties_attachment_delete(this);' title='Delete the attachment'><i class='fas fa-trash-alt'></i></span></div></div></div>";
	return html;
}
function leform_properties_attachment_new(_object) {
	var attachment_html = leform_properties_attachment_get(null, null);
	jQuery(_object).closest(".leform-properties-content").find(".leform-properties-attachments").append(attachment_html);
	leform_element_properties_data_changed = true;
	return false;
}

function leform_init_tinymce() {
	var label;

	jQuery(".leform-tinymce-pre").each(function () {
		var temp = leform_shortcode_selector_list_html(
			"leform-shortcode-selector-list-wysiwyg",
			this.classList.contains("repeater-input-content"),
			this.classList.contains("repeater-input-content") ? this : null,
		);
		temp = "<div class='leform-shortcode-selector'><span class='leform-shortcode-selector-button'><i class='fas fa-code'></i></span>" + temp + "</div>";

		if (jQuery(this).find(".leform-shortcode-selector").length == 0) {
			jQuery(this).after(temp);
		}
		jQuery("body").addClass("leform-static");
		var textarea = this;
		jQuery(textarea)
			.closest(".leform-wysiwyg")
			.find(".leform-shortcode-selector-list-item")
			.on("click", function (e) {
				var input = jQuery(this).closest(".leform-input-shortcode-selector").find("textarea");
				input = textarea;
				var caret_pos = input.selectionStart;
				var current_value = jQuery(input).val();
				jQuery(input).val(current_value.substring(0, caret_pos) + jQuery(this).attr("data-code") + current_value.substring(caret_pos));
			});

		jQuery(this).removeClass("leform-tinymce-pre");
	});
}

function leform_init_url_with_variables() {
	jQuery(".url-with-props-input").each(function () {
		var temp = leform_shortcode_selector_list_select();
		temp = "<div class='leform-shortcode-selector'><span class='leform-shortcode-selector-button' style='top:0!important;'><i class='fas fa-code'></i></span>" + temp + "</div>";
		
		if (jQuery(this).find(".leform-shortcode-selector").length == 0) {
			jQuery(this).before(temp);
		}
		jQuery("body").addClass("leform-static");
		var textarea = this;
		jQuery(textarea)
			.closest(".leform-wysiwyg")
			.find(".leform-shortcode-selector-list-item")
			.on("click", function (e) {
				input = textarea;
				var caret_pos = input.selectionStart;
				var current_value = jQuery(input).val();
				jQuery(input).val(current_value.substring(0, caret_pos) + jQuery(this).attr("data-code") + current_value.substring(caret_pos));
			});

		jQuery(this).removeClass("url-with-props-input");
	});
}

var leform_shortcode_selector_setting = false;
function leform_shortcode_selector_set(_object) {
	if (leform_shortcode_selector_setting) return;
	leform_shortcode_selector_setting = true;
	jQuery(".leform-shortcode-selector-list-input").find("li").show();
	var disabled_groups_raw = jQuery(_object).attr("data-disabled-groups");
	if (typeof disabled_groups_raw == typeof "string") {
		if (disabled_groups_raw.length > 0) {
			var disabled_groups = disabled_groups_raw.split(",");
			for (var j = 0; j < disabled_groups.length; j++) {
				if (disabled_groups[j].length > 0) jQuery(".leform-shortcode-selector-list-input").find("li.leform-shortcode-selector-list-item-" + disabled_groups[j]).hide();
			}
		}
	}
	if (jQuery(_object).find(".leform-shortcode-selector-list-input").length > 0) {
		leform_shortcode_selector_setting = false;
		return;
	}
	if (jQuery(".leform-shortcode-selector-list-input").length > 0) {
		jQuery(".leform-shortcode-selector-list-input").appendTo(_object);
		leform_shortcode_selector_setting = false;
		return;
	}
	var html = leform_shortcode_selector_list_html("leform-shortcode-selector-list-input");
	jQuery(_object).append(html);
	jQuery(_object).find(".leform-shortcode-selector-list-item").on("click", function (e) {
		var input = jQuery(this).closest(".leform-input-shortcode-selector").find("input, textarea");
		var caret_pos = input[0].selectionStart;
		var current_value = jQuery(input).val();
		jQuery(input).val(current_value.substring(0, caret_pos) + jQuery(this).attr("data-code") + current_value.substring(caret_pos));
	});
	leform_shortcode_selector_setting = false;
	return;
}

function leform_shortcode_selector_list_html_old(
	_class,
	isRepeaterInput = false,
	field = null,
) {
	var type, items, label, id;

	const isEmailAutoResponseMessage = (_class === "leform-shortcode-selector-list-wysiwyg");

	var temp = `
        <ul class='${_class}' style='margin-top: ${isEmailAutoResponseMessage ? "109px" : "0px"};'>
            <li class='leform-shortcode-selector-group leform-shortcode-selector-list-item-field'>
                ${leform_esc_html__("Form values")}
            </li>
    `;

	for (var j = 0; j < leform_form_elements.length; j++) {
		if (leform_form_elements[j] == null) continue;
		if (
			leform_toolbar_tools.hasOwnProperty(leform_form_elements[j]['type'])
			&& leform_toolbar_tools[leform_form_elements[j]['type']]['type'] == 'input'
			&& leform_form_elements[j]["type"] !== "repeater-input"
		) {
			label = leform_form_elements[j]['name'].replace(new RegExp("}", 'g'), ")");
			label = label.replace(new RegExp("{", 'g'), "(");
			temp += "<li class='leform-shortcode-selector-list-item leform-shortcode-selector-list-item-field' data-code='{{" + leform_form_elements[j]['id'] + "|" + leform_escape_html(label) + "}}'>" + leform_form_elements[j]['id'] + " | " + leform_escape_html(leform_form_elements[j]['name']) + "</li>";
		}
	}

	var math_from_window = false;
	if (leform_element_properties_active != null) {
		var type = jQuery(leform_element_properties_active).attr("data-type");
		if (type == "settings") math_from_window = true;
	}
	if (math_from_window) {
		items = jQuery(".leform-properties-content-math-expressions .leform-properties-sub-item");
		if (items.length > 0) {
			temp += `
                <li class='leform-shortcode-selector-group leform-shortcode-selector-list-item-math'>
                    ${leform_esc_html__("Math expressions")}
                </li>
            `;
			jQuery(items).each(function () {
				label = jQuery(this).find("[name='leform-math-name']").val();
				label = label.replace(new RegExp("}", 'g'), ")");
				label = label.replace(new RegExp("{", 'g'), "(");
				id = jQuery(this).find("[name='leform-math-id']").val();
				temp += "<li class='leform-shortcode-selector-list-item leform-shortcode-selector-list-item-math' data-code='{{" + id + "|" + leform_escape_html(label) + "}}'>" + id + " | " + jQuery(this).find("[name='leform-math-name']").val() + "</li>";
			});
		}
	} else {
		if (leform_form_options.hasOwnProperty("math-expressions")) {
			if (leform_form_options["math-expressions"].length > 0) {
				temp += `
                    <li class='leform-shortcode-selector-group'>
                        ${leform_esc_html__("Math expressions")}
                    </li>
                `;
				for (var j = 0; j < leform_form_options["math-expressions"].length; j++) {
					label = leform_form_options["math-expressions"][j]['name'].replace(new RegExp("}", 'g'), ")");
					label = label.replace(new RegExp("{", 'g'), "(");
					temp += "<li class='leform-shortcode-selector-list-item leform-shortcode-selector-list-item-math' data-code='{{" + leform_form_options["math-expressions"][j]['id'] + "|" + leform_escape_html(label) + "}}'>" + leform_form_options["math-expressions"][j]['id'] + " | " + leform_escape_html(leform_form_options["math-expressions"][j]['name']) + "</li>";
				}
			}
		}
	}

	if (isRepeaterInput && field) {
		temp += `
            <li class='leform-shortcode-selector-group leform-shortcode-selector-list-item-math'>
                ${leform_esc_html__("Repeater input expressions")}
            </li>
        `;

		const elementProperties = getActiveElementProperties();
		for (const expression of elementProperties.expressions) {
			temp += `
                <li
                    class='leform-shortcode-selector-list-item leform-shortcode-selector-list-item-math expression'
                    data-code='[[${expression.id}|${expression.name}]]'
                >
                    ${expression.id} | ${expression.name}
                </li>
            `;
		}
	}

	temp += "</ul>";
	return temp;
}

function leform_shortcode_selector_list_html(_class) {
	let html = '';

	html += `
        <li class='leform-shortcode-selector-group leform-shortcode-selector-list-item-field'>
            ${leform_esc_html__("Form values")}
        </li>
    `;
	for (const element of leform_form_elements) {
		if (element == null) {
			continue;
		}

		if (
			leform_toolbar_tools.hasOwnProperty(element["type"])
			&& leform_toolbar_tools[element["type"]]["type"] == "input"
			&& element["type"] !== "repeater-input"
		) {
			const label = element["name"]
				.replace(/\{/g, "(")
				.replace(/\}/g, ")");

			html += `
                <li
                    class='leform-shortcode-selector-list-item leform-shortcode-selector-list-item-field'
                    data-code='{{${element['id']}|${leform_escape_html(label)}}}'
                >
                    ${element['id']} | ${leform_escape_html(element['name'])}
                </li>
            `;
		}
	}

	if (_class === "leform-shortcode-selector-list-input") {
		let math_from_window = false;
		if (leform_element_properties_active != null) {
			const type = jQuery(leform_element_properties_active).attr("data-type");
			if (type == "settings") {
				math_from_window = true;
			}
		}

		if (math_from_window) {
			const items = document
				.querySelectorAll(".leform-properties-content-math-expressions .leform-properties-sub-item");
			if (items.length > 0) {
				html += `
                    <li class='leform-shortcode-selector-group leform-shortcode-selector-list-item-math'>
                        ${leform_esc_html__("Math expressions")}
                    </li>
                `;
				items.forEach((item) => {
					const label = item
						.querySelector("[name='leform-math-name']")
						.value;
					const cleanedLabel = label
						.replace(/\}/, ")")
						.replace(/\{/, "(");
					const id = item.querySelector("[name='leform-math-id']").value;
					html += `
                        <li
                            class='leform-shortcode-selector-list-item leform-shortcode-selector-list-item-math'
                            data-code='{{${id}|${leform_escape_html(label)}}}'
                        >
                            ${id} | ${cleanedLabel}
                        </li>
                    `;
				});
			}
		} else {
			if (leform_form_options.hasOwnProperty("math-expressions")) {
				if (leform_form_options["math-expressions"].length > 0) {
					html += `
                        <li class='leform-shortcode-selector-group'>
                            ${leform_esc_html__("Math expressions")}
                        </li>
                    `;
					for (const expression of leform_form_options["math-expressions"]) {
						const label = expression['name']
							.replace(/\}/, ")")
							.replace(/\{/, "(");
						html += `
                            <li
                                class='leform-shortcode-selector-list-item leform-shortcode-selector-list-item-math'
                                data-code='{{${expression['id']}|${leform_escape_html(label)}}}'
                            >
                                ${expression['id']} | ${leform_escape_html(expression['name'])}
                            </li>
                        `;
					}
				}
			}
		}

		const repeaterInputs = leform_form_elements.filter((element) =>
			element
			&& leform_toolbar_tools.hasOwnProperty(element["type"])
			&& leform_toolbar_tools[element["type"]]["type"] == "input"
			&& element["type"] === "repeater-input"
		);

		if (repeaterInputs.length > 0) {
			let rowValuesCodes = "";
			let rowTotalsCodes = "";
			repeaterInputs.forEach((repeaterInput) => {
				const cleanRepeaterInputName = repeaterInput["name"]
					.replace(/\[/g, "(")
					.replace(/\]/g, ")")
					.replace(/\{/g, "(")
					.replace(/\}/g, ")");

				repeaterInput.fields.forEach((field, index) => {
					const cleanFieldName = field["name"]
						.replace(/\[/g, "(")
						.replace(/\]/g, ")")
						.replace(/\{/g, "(")
						.replace(/\}/g, ")");

					const itemCode = [
						repeaterInput['id'],
						cleanRepeaterInputName,
						index + 1,
						cleanFieldName
					].join("|");
					const itemContent = [
						repeaterInput['id'],
						leform_escape_html(repeaterInput['name']),
						index + 1,
						leform_escape_html(field['name'])
					].join(" | ");

					rowValuesCodes += `
                        <li
                            class='leform-shortcode-selector-list-item leform-shortcode-selector-list-item-field'
                            data-code='[[${itemCode}]]'
                        >
                            ${itemContent}
                        </li>
                    `;
					rowTotalsCodes += `
                        <li
                            class='leform-shortcode-selector-list-item leform-shortcode-selector-list-item-field'
                            data-code='{[${itemCode}]}'
                        >
                            ${itemContent}
                        </li>
                    `;
				});
			});

			html += `
                <li class='leform-shortcode-selector-group leform-shortcode-selector-list-item-field'>
                    ${leform_esc_html__("Repeater input row values")}
                </li>
            `;
			html += rowValuesCodes;
			html += `
                <li class='leform-shortcode-selector-group leform-shortcode-selector-list-item-field'>
                    ${leform_esc_html__("Repeater input total values")}
                </li>
            `;
			html += rowTotalsCodes;
		}
	} else {
		html += `
            <li class='leform-shortcode-selector-group leform-shortcode-selector-list-item-math'>
                ${leform_esc_html__("Math expressions")}
            </li>
        `;
		for (const expression of leform_form_options["math-expressions"]) {
			const label = expression['name']
				.replace(/\}/, ")")
				.replace(/\{/, "(");
			html += `
                <li
                    class='leform-shortcode-selector-list-item leform-shortcode-selector-list-item-field'
                    data-code='{{${expression['id']}|${leform_escape_html(label)}}}'
                >
                    ${expression['id']} | ${leform_escape_html(expression['name'])}
                </li>
            `;
		}
	}

	html = `
        <ul class='${_class}' style='margin-top: 0px;'>
            ${html}
        </ul>
    `;
	return html;
}


function leform_shortcode_selector_list_select(_class) {
	let html = '';

	html += `
        <li class='leform-shortcode-selector-group leform-shortcode-selector-list-item-field'>
            ${leform_esc_html__("Form values")}
        </li>
    `;
	for (const element of leform_form_elements) {
		if (element == null) {
			continue;
		}

		if (
			leform_toolbar_tools.hasOwnProperty(element["type"])
			&& leform_toolbar_tools[element["type"]]["type"] == "input"
			&& element["type"] !== "repeater-input"
		) {
			const label = element["name"]
				.replace(/\{/g, "(")
				.replace(/\}/g, ")");

			html += `
                <li
                    class='leform-shortcode-selector-list-item leform-shortcode-selector-list-item-field'
                    data-code='{{${element['id']}|${leform_escape_html(label)}}}'
                >
                    ${element['id']} | ${leform_escape_html(element['name'])}
                </li>
            `;
		}
	}

	html = `
        <ul class='${_class}' style='margin-top: 0px;'>
            ${html}
        </ul>
    `;
	return html;
}









let leform_email_element_selector_setting = false;
function leform_email_element_selector_set(_object) {
	if (leform_email_element_selector_setting) {
		return;
	}
	leform_email_element_selector_setting = true;
	jQuery(".leform-shortcode-selector-list-input").find("li").show();
	let disabled_groups_raw = jQuery(_object).attr("data-disabled-groups");
	if (typeof disabled_groups_raw == typeof "string") {
		if (disabled_groups_raw.length > 0) {
			let disabled_groups = disabled_groups_raw.split(",");
			for (let j = 0; j < disabled_groups.length; j++) {
				if (disabled_groups[j].length > 0) {
					jQuery(".leform-shortcode-selector-list-input")
						.find("li.leform-shortcode-selector-list-item-" + disabled_groups[j])
						.hide();
				}
			}
		}
	}
	if (jQuery(_object).find(".leform-shortcode-selector-list-input").length > 0) {
		leform_email_element_selector_setting = false;
		return;
	}
	if (jQuery(".leform-shortcode-selector-list-input").length > 0) {
		jQuery(".leform-shortcode-selector-list-input").appendTo(_object);
		leform_email_element_selector_setting = false;
		return;
	}
	let html = leform_email_element_selector_list_html("leform-shortcode-selector-list-input");
	jQuery(_object).append(html);
	jQuery(_object)
		.find(".leform-shortcode-selector-list-item")
		.on("click", function (e) {
			let input = jQuery(this)
				.closest(".leform-input-shortcode-selector")
				.find("input, textarea");
			jQuery(input).val(jQuery(this).attr("data-code"));
		});
	leform_email_element_selector_setting = false;
	return;
}

function leform_email_element_selector_list_html(_class) {
	let type, items, label, id;

	let emailElements = "";

	for (let j = 0; j < leform_form_elements.length; j++) {
		if (leform_form_elements[j] == null) {
			continue;
		}
		if (
			leform_toolbar_tools.hasOwnProperty(leform_form_elements[j]['type'])
			&& leform_form_elements[j]['type'] == 'email'
		) {
			label = leform_form_elements[j]['name']
				.replace(new RegExp("}", 'g'), ")");
			label = label.replace(new RegExp("{", 'g'), "(");
			emailElements += `
                <li
                    class='leform-shortcode-selector-list-item leform-shortcode-selector-list-item-field'
                    data-code='${leform_form_elements[j]['id']}|${leform_escape_html(label)}'
                >
                    ${leform_form_elements[j]['id']} | ${leform_escape_html(leform_form_elements[j]['name'])}
                </li>
            `;
		}
	}
	let temp = `
        <ul class='${_class}' style="top: 0px;">
            <li class='leform-shortcode-selector-group leform-shortcode-selector-list-item-field'>
                ${leform_esc_html__("Form values")}
            </li>
            ${emailElements}
        </ul>
    `;
	return temp;
}












/* Element actions - end */

/* Bulk Options - begin */
var leform_bulk_options_object = null;
function leform_bulk_options_open(_object) {
	leform_bulk_options_object = jQuery(_object).closest(".leform-properties-item");
	if (leform_bulk_options_object) {
		var window_height = 2 * parseInt((jQuery(window).height() - 100) / 2, 10);
		var window_width = Math.max(2 * parseInt((jQuery(window).width() - 300) / 2, 10), 600);
		jQuery("#leform-bulk-options").height(window_height);
		jQuery("#leform-bulk-options").width(window_width);
		jQuery("#leform-bulk-options .leform-admin-popup-inner").height(window_height);
		jQuery("#leform-bulk-options .leform-admin-popup-content").height(window_height - 104);
		jQuery("#leform-bulk-options-overlay").fadeIn(300);
		jQuery("#leform-bulk-options").fadeIn(300);
		jQuery(".leform-bulk-editor textarea").val("");
	}
	return false;
}
function leform_bulk_options_close() {
	leform_bulk_options_object = null;
	jQuery("#leform-bulk-options-overlay").fadeOut(300);
	jQuery("#leform-bulk-options").fadeOut(300);
}
function leform_bulk_category_add(_object) {
	var category = jQuery(_object).attr("data-category");
	if (!category) return false;
	var value = jQuery(".leform-bulk-editor textarea").val();
	if (category == "existing") {
		if (leform_bulk_options_object) {
			jQuery(leform_bulk_options_object).find(".leform-properties-options-item").each(function () {
				var option_label = jQuery(this).find('.leform-properties-options-label').val();
				var option_value = jQuery(this).find('.leform-properties-options-value').val();
				if (value != "") value += "\r\n";
				if (option_label != option_value) value += option_label + "|" + option_value;
				else value += option_label;
			});
		}
	} else {
		if (leform_predefined_options != null && leform_predefined_options.hasOwnProperty(category)) {
			for (var i = 0; i < leform_predefined_options[category]["options"].length; i++) {
				if (value != "") value += "\r\n";
				value += leform_predefined_options[category]["options"][i];
			}
		}
	}
	jQuery(".leform-bulk-editor textarea").val(value);
	return false;
}
function leform_bulk_options_add() {
	var option;
	var html = "";
	if (leform_bulk_options_object) {
		if (jQuery("#leform-bulk-options-overwrite").is(":checked")) {
			jQuery(leform_bulk_options_object).find(".leform-properties-options-container .leform-properties-options-item").remove();
		}
		var options_str = jQuery(".leform-bulk-editor textarea").val();
		var options = options_str.split("\n");
		for (var i = 0; i < options.length; i++) {
			option = options[i].split("|");
			if (option.length == 1) html += leform_properties_options_item_get(option[0], option[0], false);
			else html += leform_properties_options_item_get(option[0], option[1], false);
		}
		jQuery(leform_bulk_options_object).find(".leform-properties-options-container").append(html);
	}
	leform_element_properties_data_changed = true;
	leform_bulk_options_close();
}
/* Bulk Options - end */

/* Font Awesome selector - begin */
var leform_fa_selector_active = null;
function leform_fa_selector_open(_object) {
	var window_height = 2 * parseInt((jQuery(window).height() - 100) / 2, 10);
	var window_width = Math.max(40 * parseInt((jQuery(window).width() - 300) / 40, 10) + 6, 606);
	jQuery(".leform-fa-selector").height(window_height);
	jQuery(".leform-fa-selector").width(window_width);
	jQuery(".leform-fa-selector-inner").height(window_height);
	jQuery(".leform-fa-selector-content").height(window_height - 72 - 20);
	jQuery(".leform-fa-selector-overlay").show();
	jQuery(".leform-fa-selector").show();
	leform_fa_selector_active = _object;
	return false;
}
function leform_fa_selector_close() {
	leform_fa_selector_active = null;
	jQuery(".leform-fa-selector-overlay").hide();
	jQuery(".leform-fa-selector").hide();
}
function leform_fa_selector_set(_object) {
	var icon_class;
	if (leform_fa_selector_active == null) return false;
	var icon = jQuery(_object).find("i").attr("class");
	if (icon == "") icon_class = "leform-fa-noicon";
	else icon_class = icon;
	var icon_element = jQuery(leform_fa_selector_active).attr("data-id");
	jQuery("#leform-" + icon_element).val(icon);
	jQuery(leform_fa_selector_active).find("i").attr("class", icon_class);
	leform_properties_change();
	leform_fa_selector_close();
	return false;
}
/* Font Awesome selector - end */

/* Pages - start */
function leform_pages_add() {
	if (leform_meta.hasOwnProperty("page")) {
		leform_form_last_id++;
		var page = { "id": leform_form_last_id, "type": "page" };
		for (var key in leform_meta["page"]) {
			if (leform_meta["page"].hasOwnProperty(key)) {
				switch (leform_meta["page"][key]['type']) {
					default:
						if (leform_meta["page"][key].hasOwnProperty('value')) {
							if (typeof leform_meta["page"][key]['value'] == 'object') {
								for (var option_key in leform_meta["page"][key]['value']) {
									if (leform_meta["page"][key]['value'].hasOwnProperty(option_key)) {
										page[key + "-" + option_key] = leform_meta["page"][key]['value'][option_key];
									}
								}
							} else page[key] = leform_meta["page"][key]['value'];
						} else if (leform_meta["page"][key].hasOwnProperty('values')) page[key] = leform_meta["page"][key]['values'];
						break;
				}
			}
		}
		leform_form_pages.push(page);
		leform_form_changed = true;

		if (jQuery(".leform-pages-bar-item-confirmation").length > 0) jQuery(".leform-pages-bar-item-confirmation").before("<li class='leform-pages-bar-item' data-id='" + page["id"] + "' data-name='" + leform_escape_html(page['name']) + "'><label onclick='return leform_pages_activate(this);'>" + leform_escape_html(page['name']) + "</label><span><a href='#' data-type='page' onclick='return leform_properties_open(this);'><i class='fas fa-cog'></i></a><a href='#' class='leform-pages-bar-item-delete' onclick='return leform_pages_delete(this);'><i class='fas fa-trash-alt'></i></a></span></li>");
		else jQuery(".leform-pages-add").before("<li class='leform-pages-bar-item' data-id='" + page["id"] + "' data-name='" + leform_escape_html(page['name']) + "'><label onclick='return leform_pages_activate(this);'>" + leform_escape_html(page['name']) + "</label><span><a href='#' data-type='page' onclick='return leform_properties_open(this);'><i class='fas fa-cog'></i></a><a href='#' class='leform-pages-bar-item-delete' onclick='return leform_pages_delete(this);'><i class='fas fa-trash-alt'></i></a></span></li>");
		if (jQuery(".leform-pages-bar-item").length == 1) jQuery(".leform-pages-bar-item").find(".leform-pages-bar-item-delete").addClass("leform-pages-bar-item-delete-disabled");
		else jQuery(".leform-pages-bar-item").find(".leform-pages-bar-item-delete").removeClass("leform-pages-bar-item-delete-disabled");

		jQuery(".leform-builder").append("<div id='leform-form-" + page['id'] + "' class='leform-form leform-elements' _data-parent='" + page['id'] + "' _data-parent-col='0'></div>");
		leform_build_progress();
	}
	return false;
}
function _leform_pages_delete(_object) {
	var page_id = jQuery(_object).closest("li").attr("data-id");
	for (var i = 0; i < leform_form_pages.length; i++) {
		if (leform_form_pages[i] != null && leform_form_pages[i]['id'] == page_id) {
			leform_form_pages[i] = null;
			break;
		}
	}
	jQuery(_object).closest("li").remove();
	jQuery("#leform-form-" + page_id).remove();

	for (var i = 0; i < leform_form_elements.length; i++) {
		if (leform_form_elements[i] != null && leform_form_elements[i]["_parent"] == page_id) {
			_leform_element_delete(i);
		}

	}
	if (jQuery(".leform-pages-bar-item").length == 1) jQuery(".leform-pages-bar-item").find(".leform-pages-bar-item-delete").addClass("leform-pages-bar-item-delete-disabled");
	else jQuery(".leform-pages-bar-item").find(".leform-pages-bar-item-delete").removeClass("leform-pages-bar-item-delete-disabled");

	if (leform_form_page_active == page_id) leform_pages_activate(jQuery(".leform-pages-bar-item").first().find("label"));
	leform_build_progress();
}
function leform_pages_delete(_object) {
	if (jQuery(".leform-pages-bar-item").length <= 1) return false;
	leform_dialog_open({
		echo_html: function () {
			this.html("<div class='leform-dialog-message'>" + leform_esc_html__("Please confirm that you want to delete the page and all sub-elements.", "leform") + "</div>");
			this.show();
		},
		ok_label: leform_esc_html__('Delete'),
		ok_function: function (e) {
			_leform_pages_delete(_object);
			leform_dialog_close();
		}
	});
	return false;
}
function leform_pages_activate(_object) {
	var page_id = jQuery(_object).closest("li").attr("data-id");
	if (leform_form_page_active == page_id) return false;
	if (leform_form_page_active != null && jQuery("#leform-form-" + leform_form_page_active).length > 0) {
		if (leform_form_options["progress-position"] == "outside") {
			jQuery("#leform-progress-" + leform_form_page_active).fadeOut(300);
		}
		jQuery("#leform-form-" + leform_form_page_active).fadeOut(300, function () { jQuery("#leform-form-" + page_id).fadeIn(300); jQuery("#leform-progress-" + page_id).fadeIn(300); });
	} else {
		if (leform_form_options["progress-position"] == "outside") jQuery("#leform-progress-" + page_id).fadeIn(300);
		jQuery("#leform-form-" + page_id).fadeIn(300);
	}
	leform_form_page_active = page_id;
	jQuery(".leform-pages-bar-item-active").removeClass("leform-pages-bar-item-active");
	jQuery(".leform-pages-bar-item[data-id='" + page_id + "'], .leform-pages-bar-item-confirmation[data-id='" + page_id + "']").addClass("leform-pages-bar-item-active");
	if (page_id == "confirmation") jQuery(".leform-toolbar-tool-input, .leform-toolbar-tool-submit").hide();
	else jQuery(".leform-toolbar-tool-input, .leform-toolbar-tool-submit").show();
	return false;
}
/* Pages - end */

function _leform_sync_elements() {
	jQuery(".leform-elements").css({ "min-height": "60px" });
	jQuery(".leform-row").each(function () {
		var max_height = 0;
		jQuery(this).children(".leform-col").each(function () {
			var height = jQuery(this).children(".leform-elements").height();
			if (height > max_height) max_height = height;
		});
		jQuery(this).children(".leform-col").each(function () {
			jQuery(this).children(".leform-elements").css({ "min-height": max_height + "px" });
		});
	});
	jQuery(".leform-elements").each(function () {
		var parent = jQuery(this).attr("_data-parent");
		var column = jQuery(this).attr("_data-parent-col");
		var seq = 0;
		jQuery(this).children(".leform-element").each(function () {
			var i = jQuery(this).attr("id");
			i = i.replace("leform-element-", "");
			leform_form_elements[i]["_parent"] = parent;
			leform_form_elements[i]["_parent-col"] = column;
			leform_form_elements[i]["_seq"] = seq;
			seq++;
		});
	});
}
function _leform_build_hidden_list(_parent) {
	var html = "";
	for (var i = 0; i < leform_form_elements.length; i++) {
		if (leform_form_elements[i] == null) continue;
		if (leform_form_elements[i]["type"] != "hidden") continue;
		if (leform_form_elements[i]["_parent"] != _parent) continue;
		html += "<div class='leform-hidden-element' id='leform-element-" + i + "' data-type='" + leform_form_elements[i]["type"] + "'>" + leform_escape_html(leform_form_elements[i]["name"]) + "</div>";
	}
	if (html != "") html = "<div class='leform-hidden-container'><label>Hidden fields:</label>" + html + "</div>";
	return html;
}
function _leform_build_children(_parent, _parent_col) {
	var adminbar_height = parseInt(jQuery("#wpadminbar").height(), 10);
	var resizable_handle = "all";
	var html = "", style = "";
	var label, options, selected, icon, option, extra_class, style_attr;
	var column_label_class, column_input_class;
	var properties = {};

	var idxs = new Array();
	var seqs = new Array();
	for (var i = 0; i < leform_form_elements.length; i++) {
		if (leform_form_elements[i] == null) continue;
		if (leform_form_elements[i]["_parent"] == _parent && (leform_form_elements[i]["_parent-col"] == _parent_col || _parent == "")) {
			idxs.push(i);
			seqs.push(parseInt(leform_form_elements[i]["_seq"], 10));
		}
	}

	if (idxs.length == 0) return { "html": "", "style": "" };
	var sorted;
	for (var i = 0; i < seqs.length; i++) {
		sorted = -1;
		for (var j = 0; j < seqs.length - 1; j++) {
			if (seqs[j] > seqs[j + 1]) {
				sorted = seqs[j];
				seqs[j] = seqs[j + 1];
				seqs[j + 1] = sorted;
				sorted = idxs[j];
				idxs[j] = idxs[j + 1];
				idxs[j + 1] = sorted;
			}
		}
		if (sorted == -1) break;
	}
	/*
	if (leform_form_options["input-placeholder-color"]) {
			console.log("ajsdfnkasjdnfsdnfsdfa");
			style += `
					input::placeholder {
							color: '${leform_form_options["input-placeholder-color"]} !important';
					}
			`;
	}
	*/
	for (var k = 0; k < idxs.length; k++) {
		i = idxs[k];
		icon = "";
		options = "";
		extra_class = "";
		column_label_class = "";
		column_input_class = "";
		properties = {};
		if (leform_form_elements[i] == null) continue;

		if (leform_form_elements[i].hasOwnProperty("label-style-position")) {
			properties["label-style-position"] = leform_form_elements[i]["label-style-position"];
			if (properties["label-style-position"] == "") properties["label-style-position"] = leform_form_options["label-style-position"];
			if (properties["label-style-position"] == "") properties["label-style-position"] = "top";
			if (leform_form_elements[i]["label-style-position"] == "left" || leform_form_elements[i]["label-style-position"] == "right") properties["label-style-width"] = leform_form_elements[i]["label-style-width"];
			else properties["label-style-width"] = "";
			if (properties["label-style-width"] == "") properties["label-style-width"] = leform_form_options["label-style-width"];
			if (!leform_is_numeric(properties["label-style-width"]) || parseInt(properties["label-style-width"], 10) < 1 || parseInt(properties["label-style-width"], 10) > 11) properties["label-style-width"] = 3;
			if (properties["label-style-position"] == "left" || properties["label-style-position"] == "right") {
				column_label_class = " leform-col-" + properties["label-style-width"];
				column_input_class = " leform-col-" + (12 - properties["label-style-width"]);
			}
		}
		if (leform_form_elements[i].hasOwnProperty("icon-left-icon")) {
			if (leform_form_elements[i]["icon-left-icon"] != "") {
				if (leform_form_options["input-icon-display"] === "show") {
					extra_class += " leform-icon-left";
					icon += "<i class='leform-icon-left " + leform_form_elements[i]["icon-left-icon"] + "'></i>";
					options = "";
					if (leform_form_elements[i]["icon-left-size"] != "") {
						options += "font-size:" + leform_form_elements[i]["icon-left-size"] + "px;";
					}
					if (options != "") {
						style += "#leform-element-" + i + " div.leform-input>i.leform-icon-left{" + options + "}";
					}
				}
			}
		}
		if (leform_form_elements[i].hasOwnProperty("icon-right-icon")) {
			if (leform_form_elements[i]["icon-right-icon"] != "") {
				if (leform_form_options["input-icon-display"] === "show") {
					extra_class += " leform-icon-right";
					icon += "<i class='leform-icon-right " + leform_form_elements[i]["icon-right-icon"] + "'></i>";
					options = "";
					if (leform_form_elements[i]["icon-right-size"] != "") {
						options += "font-size:" + leform_form_elements[i]["icon-right-size"] + "px;";
					}
					if (options != "") {
						style += "#leform-element-" + i + " div.leform-input>i.leform-icon-right{" + options + "}";
					}
				}
			}
		}
		if (leform_form_elements[i].hasOwnProperty("css") && leform_form_elements[i]["css"].length > 0) {
			if (leform_meta.hasOwnProperty(leform_form_elements[i]["type"]) && leform_meta[leform_form_elements[i]["type"]].hasOwnProperty("css")) {
				for (var j = 0; j < leform_form_elements[i]["css"].length; j++) {
					if (leform_form_elements[i]["css"][j]["css"] != "" && leform_form_elements[i]["css"][j]["selector"] != "") {
						if (leform_meta[leform_form_elements[i]["type"]]["css"]["selectors"].hasOwnProperty(leform_form_elements[i]["css"][j]["selector"])) {
							properties["css-class"] = leform_meta[leform_form_elements[i]["type"]]["css"]["selectors"][leform_form_elements[i]["css"][j]["selector"]]["admin-class"];
							properties["css-class"] = properties["css-class"].replace(new RegExp("{element-id}", 'g'), i);
							style += properties["css-class"] + "{" + leform_form_elements[i]["css"][j]["css"] + "}";
						}
					}
				}
			}
		}
		properties["tooltip-label"] = "";
		properties["tooltip-description"] = "";
		properties["tooltip-input"] = "";
		if (leform_form_elements[i].hasOwnProperty("tooltip") && leform_form_elements[i]["tooltip"].length > 0) {
			if (leform_form_options.hasOwnProperty("tooltip-anchor") && leform_form_options["tooltip-anchor"] != "" && leform_form_options["tooltip-anchor"] != "none") {
				switch (leform_form_options["tooltip-anchor"]) {
					case 'description':
						properties["tooltip-description"] = " <span class='leform-tooltip-anchor leform-if leform-if-help-circled' title='" + leform_escape_html(leform_form_elements[i]["tooltip"]) + "'></span>";
						break;
					case 'input':
						properties["tooltip-input"] = " title='" + leform_escape_html(leform_form_elements[i]["tooltip"]) + "'";
						break;
					default:
						/*
						 class='fas fa-info border-2 px-2.5 py-1 rounded-full'
						 class='leform-tooltip-anchor leform-if leform-if-help-circled'
						*/
						properties["tooltip-label"] = `
                            <span
                                class="fa fa-info rounded-full border-2 ml-3"
                                style="padding: 2px 9px; font-size: 0.7em; color: ${leform_form_options["html-headings-color"]
							}; border-color: ${leform_form_options["html-headings-color"]
							};"
                                title="${leform_escape_html(leform_form_elements[i]["tooltip"])}"
                            ></span>
                        `;
						break;
				}
			}
		}
		properties["required-label-left"] = "";
		properties["required-label-right"] = "";
		properties["required-description-left"] = "";
		properties["required-description-right"] = "";
		if (leform_form_elements[i].hasOwnProperty("required") && leform_form_elements[i]["required"] == "on") {
			if (leform_form_options.hasOwnProperty("required-position") && leform_form_options["required-position"] != "" && leform_form_options["required-position"] != "none" && leform_form_options.hasOwnProperty("required-text") && leform_form_options["required-text"] != "") {
				switch (leform_form_options["required-position"]) {
					case 'label-left':
					case 'label-right':
					case 'description-left':
					case 'description-right':
						properties["required-" + leform_form_options["required-position"]] = "<span class='leform-required-symbol leform-required-symbol-" + leform_form_options["required-position"] + "'>" + leform_escape_html(leform_form_options["required-text"]) + "</span>";
						break;
					default:
						break;
				}
			}
		}
		if (leform_toolbar_tools.hasOwnProperty(leform_form_elements[i]["type"])) {
			switch (leform_form_elements[i]["type"]) {
				case "button":
				case "link-button":
					icon = "";
					if (leform_form_elements[i].hasOwnProperty("button-style-size") && leform_form_elements[i]['button-style-size'] != "") properties['size'] = leform_form_elements[i]['button-style-size'];
					else properties['size'] = leform_form_options['button-style-size'];
					if (leform_form_elements[i].hasOwnProperty("button-style-width") && leform_form_elements[i]['button-style-width'] != "") properties['width'] = leform_form_elements[i]['button-style-width'];
					else properties['width'] = leform_form_options['button-style-width'];
					if (leform_form_elements[i].hasOwnProperty("button-style-position") && leform_form_elements[i]['button-style-position'] != "") properties['position'] = leform_form_elements[i]['button-style-position'];
					else properties['position'] = leform_form_options['button-style-position'];
					label = "<span>" + leform_escape_html(leform_form_elements[i]["label"]) + "</span>";
					if (leform_form_elements[i].hasOwnProperty("icon-left") && leform_form_elements[i]["icon-left"] != "") label = "<i class='leform-icon-left " + leform_form_elements[i]["icon-left"] + "'></i>" + label;
					if (leform_form_elements[i].hasOwnProperty("icon-right") && leform_form_elements[i]["icon-right"] != "") label += "<i class='leform-icon-right " + leform_form_elements[i]["icon-right"] + "'></i>";

					properties['style-attr'] = "";
					if (leform_form_elements[i].hasOwnProperty("colors-background") && leform_form_elements[i]["colors-background"] != "") properties['style-attr'] += "background-color:" + leform_form_elements[i]["colors-background"] + ";";
					if (leform_form_elements[i].hasOwnProperty("colors-border") && leform_form_elements[i]["colors-border"] != "") properties['style-attr'] += "border-color:" + leform_form_elements[i]["colors-border"] + ";";
					if (leform_form_elements[i].hasOwnProperty("colors-text") && leform_form_elements[i]["colors-text"] != "") properties['style-attr'] += "color:" + leform_form_elements[i]["colors-text"] + ";";
					if (properties['style-attr'] != "") style += "#leform-element-" + i + " .leform-button{" + properties['style-attr'] + "}";

					properties['style-attr'] = "";
					if (leform_form_elements[i].hasOwnProperty("colors-hover-background") && leform_form_elements[i]["colors-hover-background"] != "") properties['style-attr'] += "background-color:" + leform_form_elements[i]["colors-hover-background"] + ";";
					if (leform_form_elements[i].hasOwnProperty("colors-hover-border") && leform_form_elements[i]["colors-hover-border"] != "") properties['style-attr'] += "border-color:" + leform_form_elements[i]["colors-hover-border"] + ";";
					if (leform_form_elements[i].hasOwnProperty("colors-hover-text") && leform_form_elements[i]["colors-hover-text"] != "") properties['style-attr'] += "color:" + leform_form_elements[i]["colors-hover-text"] + ";";
					if (properties['style-attr'] != "") style += "#leform-element-" + i + " .leform-button:hover{" + properties['style-attr'] + "}";

					properties['style-attr'] = "";
					if (leform_form_elements[i].hasOwnProperty("colors-active-background") && leform_form_elements[i]["colors-active-background"] != "") properties['style-attr'] += "background-color:" + leform_form_elements[i]["colors-active-background"] + ";";
					if (leform_form_elements[i].hasOwnProperty("colors-active-border") && leform_form_elements[i]["colors-active-border"] != "") properties['style-attr'] += "border-color:" + leform_form_elements[i]["colors-active-border"] + ";";
					if (leform_form_elements[i].hasOwnProperty("colors-active-text") && leform_form_elements[i]["colors-active-text"] != "") properties['style-attr'] += "color:" + leform_form_elements[i]["colors-active-text"] + ";";
					if (properties['style-attr'] != "") style += "#leform-element-" + i + " .leform-button:active{" + properties['style-attr'] + "}";

					html += "<div id='leform-element-" + i + "' class='leform-element-" + i + " leform-element leform-ta-" + properties['position'] + "' data-type='" + leform_form_elements[i]["type"] + "'><a class='leform-button leform-button-" + leform_form_options["button-active-transform"] + " leform-button-" + properties['width'] + " leform-button-" + properties['size'] + " " + leform_form_elements[i]["css-class"] + "' href='#' onclick='return false;'>" + label + "</a><div class='leform-element-cover'></div></div>";
					break;

				case "email":
				case "text":
					if (leform_form_elements[i]['input-style-size'] != "") extra_class += " leform-input-" + leform_form_elements[i]['input-style-size'];
					html += "<div id='leform-element-" + i + "' class='leform-element-" + i + " leform-element" + (properties["label-style-position"] != "" ? " leform-element-label-" +
						properties["label-style-position"] : "") + (leform_form_elements[i]['description-style-position'] != "" ? " leform-element-description-" + leform_form_elements[i]['description-style-position'] : "") +
						"' data-type='" + leform_form_elements[i]["type"] + "'><div class='leform-column-label" + column_label_class + "'><label class='leform-label" + (leform_form_elements[i]['label-style-align'] != "" ? " leform-ta-" +
							leform_form_elements[i]['label-style-align'] : "") + "'>" + properties["required-label-left"] + leform_escape_html(leform_form_elements[i]["label"]) + properties["required-label-right"] +
						properties["tooltip-label"] + "</label></div><div class='leform-column-input" + column_input_class + "'><div class='leform-input" + extra_class + "'" + properties["tooltip-input"] + ">" + icon +
						"<input type='text' class='" + (leform_form_elements[i]['input-style-align'] != "" ? "leform-ta-" + leform_form_elements[i]['input-style-align'] + " " : "") + leform_form_elements[i]["css-class"] +
						"' placeholder='" + leform_escape_html(leform_form_elements[i]["placeholder"]) + "' value='" + leform_escape_html(leform_form_elements[i]["default"]) +
						"' /></div><label class='leform-description" + (leform_form_elements[i]['description-style-align'] != "" ? " leform-ta-" + leform_form_elements[i]['description-style-align'] : "") +
						"'>" + properties["required-description-left"] + leform_escape_html(leform_form_elements[i]["description"]) + properties["required-description-right"] + properties["tooltip-description"] +
						"</label></div><div class='leform-element-cover'></div></div>";
					break;

				case "number":
					if (leform_form_elements[i]['input-style-size'] != "") extra_class += " leform-input-" + leform_form_elements[i]['input-style-size'];
					html += "<div id='leform-element-" + i + "' class='leform-element-" + i + " leform-element" + (properties["label-style-position"] != "" ? " leform-element-label-" +
						properties["label-style-position"] : "") + (leform_form_elements[i]['description-style-position'] != "" ? " leform-element-description-" + leform_form_elements[i]['description-style-position'] : "") +
						"' data-type='" + leform_form_elements[i]["type"] + "'><div class='leform-column-label" + column_label_class + "'><label class='leform-label" + (leform_form_elements[i]['label-style-align'] != "" ?
							" leform-ta-" + leform_form_elements[i]['label-style-align']
							: ""
						) + "'>" + properties["required-label-left"] + leform_escape_html(leform_form_elements[i]["label"]) + properties["required-label-right"] + properties["tooltip-label"] +
						"</label></div><div class='leform-column-input" + column_input_class + "'><div class='leform-input" + extra_class + "'" + properties["tooltip-input"] + ">" + icon +
						"<input type='text' class='" + (leform_form_elements[i]['input-style-align'] != "" ? "leform-ta-" + leform_form_elements[i]['input-style-align'] + " " : "") +
						leform_form_elements[i]["css-class"] + "' placeholder='" + leform_escape_html(leform_form_elements[i]["placeholder"]) + "' value='" + leform_escape_html(leform_form_elements[i]["number-value3"]) +
						"' /></div><label class='leform-description" + (leform_form_elements[i]['description-style-align'] != "" ? " leform-ta-" + leform_form_elements[i]['description-style-align'] : "") + "'>" +
						properties["required-description-left"] + leform_escape_html(leform_form_elements[i]["description"]) + properties["required-description-right"] + properties["tooltip-description"] +
						"</label></div><div class='leform-element-cover'></div></div>";
					break;

				case "numspinner":
					properties['value'] = parseFloat(leform_form_elements[i]["number-value2"]).toFixed(leform_form_elements[i]["decimal"]);
					if (leform_form_elements[i]['input-style-size'] != "") extra_class += " leform-input-" + leform_form_elements[i]['input-style-size'];
					html += "<div id='leform-element-" + i + "' class='leform-element-" + i + " leform-element" + (properties["label-style-position"] != "" ? " leform-element-label-" +
						properties["label-style-position"] : "") + (leform_form_elements[i]['description-style-position'] != "" ? " leform-element-description-" +
							leform_form_elements[i]['description-style-position'] : "") + "' data-type='" + leform_form_elements[i]["type"] + "'><div class='leform-column-label" +
						column_label_class + "'><label class='leform-label" + (leform_form_elements[i]['label-style-align'] != "" ? " leform-ta-" + leform_form_elements[i]['label-style-align'] : "") + "'>" +
						properties["required-label-left"] + leform_escape_html(leform_form_elements[i]["label"]) + properties["required-label-right"] + properties["tooltip-label"] + "</label></div><div class='leform-column-input" +
						column_input_class + "'><div class='leform-input leform-icon-left leform-icon-right" + extra_class + "'" + properties["tooltip-input"] +
						"><i class='leform-icon-left leform-if leform-if-minus leform-numspinner-minus'></i><i class='leform-icon-right leform-if leform-if-plus leform-numspinner-plus'></i><input type='text' class='" +
						(leform_form_elements[i]['input-style-align'] != "" ? "leform-ta-" + leform_form_elements[i]['input-style-align'] + " " : "") + leform_form_elements[i]["css-class"] + "' placeholder='...' value='" +
						leform_escape_html(properties["value"]) + "' readonly='readonly' /></div><label class='leform-description" + (leform_form_elements[i]['description-style-align'] != "" ? " leform-ta-" +
							leform_form_elements[i]['description-style-align'] : "") + "'>" + properties["required-description-left"] + leform_escape_html(leform_form_elements[i]["description"]) +
						properties["required-description-right"] + properties["tooltip-description"] + "</label></div><div class='leform-element-cover'></div></div>";
					break;

				case "password":
					if (leform_form_elements[i]['input-style-size'] != "") extra_class += " leform-input-" + leform_form_elements[i]['input-style-size'];
					html += "<div id='leform-element-" + i + "' class='leform-element-" + i + " leform-element" + (properties["label-style-position"] != "" ? " leform-element-label-" +
						properties["label-style-position"] : "") + (leform_form_elements[i]['description-style-position'] != "" ? " leform-element-description-" + leform_form_elements[i]['description-style-position'] : "") +
						"' data-type='" + leform_form_elements[i]["type"] + "'><div class='leform-column-label" + column_label_class + "'><label class='leform-label" +
						(leform_form_elements[i]['label-style-align'] != "" ? " leform-ta-" + leform_form_elements[i]['label-style-align'] : "") + "'>" + properties["required-label-left"] +
						leform_escape_html(leform_form_elements[i]["label"]) + properties["required-label-right"] + properties["tooltip-label"] + "</label></div><div class='leform-column-input" +
						column_input_class + "'><div class='leform-input" + extra_class + "'" + properties["tooltip-input"] + ">" + icon + "<input type='password' class='" +
						(leform_form_elements[i]['input-style-align'] != "" ? "leform-ta-" + leform_form_elements[i]['input-style-align'] + " " : "") + leform_form_elements[i]["css-class"] + "' placeholder='" +
						leform_escape_html(leform_form_elements[i]["placeholder"]) + "' value='" + leform_escape_html(leform_form_elements[i]["default"]) + "' /></div><label class='leform-description" +
						(leform_form_elements[i]['description-style-align'] != "" ? " leform-ta-" + leform_form_elements[i]['description-style-align'] : "") + "'>" + properties["required-description-left"] +
						leform_escape_html(leform_form_elements[i]["description"]) + properties["required-description-right"] + properties["tooltip-description"] + "</label></div><div class='leform-element-cover'></div></div>";
					break;

				case "textarea":
					properties["textarea-height"] = leform_form_elements[i]["textarea-style-height"];
					if (properties["textarea-height"] == "") properties["textarea-height"] = leform_form_options["textarea-height"];
					if (properties["textarea-height"] == "") properties["textarea-height"] = 160;
					style += "#leform-element-" + i + " div.leform-input {height:" + properties["textarea-height"] + "px; line-height:2.5;} #leform-element-" + i + " div.leform-input textarea{line-height:1.4;}";
					html += "<div id='leform-element-" + i + "' class='leform-element-" + i + " leform-element" + (properties['label-style-position'] != "" ? " leform-element-label-" +
						properties['label-style-position'] : "") + (leform_form_elements[i]['description-style-position'] != "" ? " leform-element-description-" + leform_form_elements[i]['description-style-position'] : "") +
						"' data-type='" + leform_form_elements[i]["type"] + "'><div class='leform-column-label" + column_label_class + "'><label class='leform-label" +
						(leform_form_elements[i]['label-style-align'] != "" ? " leform-ta-" + leform_form_elements[i]['label-style-align'] : "") + "'>" + properties["required-label-left"] +
						leform_escape_html(leform_form_elements[i]["label"]) + properties["required-label-right"] + properties["tooltip-label"] + "</label></div><div class='leform-column-input" +
						column_input_class + "'><div class='leform-input" + extra_class + "'" + properties["tooltip-input"] + ">" + icon + "<textarea class='" +
						(leform_form_elements[i]['textarea-style-align'] != "" ? "leform-ta-" + leform_form_elements[i]['textarea-style-align'] + " " : "") + leform_form_elements[i]["css-class"] + "' placeholder='" +
						leform_escape_html(leform_form_elements[i]["placeholder"]) + "'>" + leform_escape_html(leform_form_elements[i]["default"]) + "</textarea></div><label class='leform-description" +
						(leform_form_elements[i]['description-style-align'] != "" ? " leform-ta-" + leform_form_elements[i]['description-style-align'] : "") + "'>" + properties["required-description-left"] +
						leform_escape_html(leform_form_elements[i]["description"]) + properties["required-description-right"] + properties["tooltip-description"] + "</label></div><div class='leform-element-cover'></div></div>";
					break;

				case "signature":
					properties["height"] = leform_form_elements[i]["height"];
					if (properties["height"] == "") {
						properties["height"] = 120;
					}
					style += "#leform-element-" + i + " div.leform-input {height:auto;} #leform-element-" + i + " div.leform-input div.leform-signature-box {height:" + properties["height"] + "px;}";

					// html += "<div id='leform-element-"+i+"' class='leform-element-"+i+" leform-element"+(properties['label-style-position'] != "" ? " leform-element-label-"+properties['label-style-position'] : "")+(leform_form_elements[i]['description-style-position'] != "" ? " leform-element-description-"+leform_form_elements[i]['description-style-position'] : "")+"' data-type='"+leform_form_elements[i]["type"]+"'><div class='leform-column-label"+column_label_class+"'><label class='leform-label"+(leform_form_elements[i]['label-style-align'] != "" ? " leform-ta-"+leform_form_elements[i]['label-style-align'] : "")+"'>"+properties["required-label-left"]+leform_escape_html(leform_form_elements[i]["label"])+properties["required-label-right"]+properties["tooltip-label"]+"</label></div><div class='leform-column-input"+column_input_class+"'><div class='leform-input"+extra_class+"'"+properties["tooltip-input"]+"><div class='leform-signature-box'><canvas class='leform-signature'></canvas><span><i class='leform-if leform-if-eraser'></i></span></div></div><label class='leform-description"+(leform_form_elements[i]['description-style-align'] != "" ? " leform-ta-"+leform_form_elements[i]['description-style-align'] : "")+"'>"+properties["required-description-left"]+leform_escape_html(leform_form_elements[i]["description"])+properties["required-description-right"]+properties["tooltip-description"]+"</label></div><div class='leform-element-cover'></div></div>";

					html += "<div id='leform-element-" + i + "' class='leform-element-" + i + " leform-element" + (properties['label-style-position'] != "" ? " leform-element-label-" +
						properties['label-style-position'] : "") + (leform_form_elements[i]['description-style-position'] != "" ? " leform-element-description-" +
							leform_form_elements[i]['description-style-position'] : "") + "' data-type='" + leform_form_elements[i]["type"] + "'> <div class='leform-column-label" +
						column_label_class + "'> <label class='leform-label" + (leform_form_elements[i]['label-style-align'] != "" ? " leform-ta-" + leform_form_elements[i]['label-style-align'] : "") + "'> " +
						properties["required-label-left"] + leform_escape_html(leform_form_elements[i]["label"]) + properties["required-label-right"] + properties["tooltip-label"] + " </label> </div>";

					html += `
						<div class="signature-input-methods-select flex justify-between mb-3">Eine Vorschau des Unterschriftenfelds ist nur über die Vorschaufunktion oder den Direktlink verfügbar.</div>
                    `;

					html += "<div class='leform-column-input" + column_input_class + "'> <div class='leform-input" + extra_class + "'" + properties["tooltip-input"] + "> <input type='hidden' name='leform-" + i +
						"' value='' /> </div> <label class='leform-description" + (leform_form_elements[i]['description-style-align'] != "" ? " leform-ta-" + leform_form_elements[i]['description-style-align'] : "") +
						"'> " + properties["required-description-left"] + leform_escape_html(leform_form_elements[i]["description"]) + properties["required-description-right"] + properties["tooltip-description"] +
						" </label> </div> <div class='leform-element-cover'></div></div>";
					break;

				case "rangeslider":
					/* from merge
style += "#leform-element-"+i+" div.leform-input{height:auto;line-height:1;}";
const rangeoptions = (leform_form_elements[i]["readonly"] == "on" ? "data-from-fixed='true' data-to-fixed='true'" : "")+" "+(leform_form_elements[i]["double"] == "on" ? "data-type='double'" : "data-type='single'")+" "+(leform_form_elements[i]["grid-enable"] == "on" ? "data-grid='true'" : "data-grid='false'")+" "+(leform_form_elements[i]["min-max-labels"] == "on" ? "data-hide-min-max='false'" : "data-hide-min-max='true'")+" data-skin='"+leform_form_options['rangeslider-skin']+"' data-min='"+leform_form_elements[i]["range-value1"]+"' data-max='"+leform_form_elements[i]["range-value2"]+"' data-step='"+leform_form_elements[i]["range-value3"]+"' data-from='"+leform_form_elements[i]["handle"]+"' data-to='"+leform_form_elements[i]["handle2"]+"' data-prefix='"+leform_form_elements[i]["prefix"]+"' data-postfix='"+leform_form_elements[i]["postfix"]+"'";
html += "<div id='leform-element-"+i+"' class='leform-element-"+i+" leform-element"+(properties["label-style-position"] != "" ? " leform-element-label-"+properties["label-style-position"] : "")+(leform_form_elements[i]['description-style-position'] != "" ? " leform-element-description-"+leform_form_elements[i]['description-style-position'] : "")+"' data-type='"+leform_form_elements[i]["type"]+"'><div class='leform-column-label"+column_label_class+"'><label class='leform-label"+(leform_form_elements[i]['label-style-align'] != "" ? " leform-ta-"+leform_form_elements[i]['label-style-align'] : "")+"'>"+properties["required-label-left"]+leform_escape_html(leform_form_elements[i]["label"])+properties["required-label-right"]+properties["tooltip-label"]+"</label></div><div class='leform-column-input"+column_input_class+"'><div class='leform-input leform-rangeslider"+extra_class+"'"+properties["tooltip-input"]+"><input type='text' class='leform-rangeslider "+leform_form_elements[i]["css-class"]+"' value='"+leform_escape_html(leform_form_elements[i]["default"])+"' "+
							rangeoptions
							+" /></div><label class='leform-description"+(leform_form_elements[i]['description-style-align'] != "" ? " leform-ta-"+leform_form_elements[i]['description-style-align'] : "")+"'>"+properties["required-description-left"]+leform_escape_html(leform_form_elements[i]["description"])+properties["required-description-right"]+properties["tooltip-description"]+"</label></div><div class='leform-element-cover'></div></div>";
*/
					style += "#leform-element-" + i + " div.leform-input{height:auto;line-height:1;}";
					let rangesliderOptions = (leform_form_elements[i]["readonly"] == "on" ? "data-from-fixed='true' data-to-fixed='true'" : "") + " " + (leform_form_elements[i]["double"] == "on" ? "data-type='double'" : "data-type='single'") + " " + (leform_form_elements[i]["grid-enable"] == "on" ? "data-grid='true'" : "data-grid='false'") + " " + (leform_form_elements[i]["min-max-labels"] == "on" ? "data-hide-min-max='false'" : "data-hide-min-max='true'") + " data-skin='" + leform_form_options['rangeslider-skin'] + "' data-min='" + leform_form_elements[i]["range-value1"] + "' data-max='" + leform_form_elements[i]["range-value2"] + "' data-step='" + leform_form_elements[i]["range-value3"] + "' data-from='" + leform_form_elements[i]["handle"] + "' data-to='" + leform_form_elements[i]["handle2"] + "' data-prefix='" + leform_form_elements[i]["prefix"] + "' data-postfix='" + leform_form_elements[i]["postfix"] + "'";
					html += "<div id='leform-element-" + i + "' class='leform-element-" + i + " leform-element" + (properties["label-style-position"] != "" ? " leform-element-label-" + properties["label-style-position"] : "") +
						(leform_form_elements[i]['description-style-position'] != "" ? " leform-element-description-" + leform_form_elements[i]['description-style-position'] : "") + "' data-type='" + leform_form_elements[i]["type"] +
						"'><div class='leform-column-label" + column_label_class + "'><label class='leform-label" + (leform_form_elements[i]['label-style-align'] != "" ? " leform-ta-" + leform_form_elements[i]['label-style-align'] : "") +
						"'>" + properties["required-label-left"] + leform_escape_html(leform_form_elements[i]["label"]) + properties["required-label-right"] + properties["tooltip-label"] + "</label></div><div class='leform-column-input" +
						column_input_class + "'><div class='leform-input leform-rangeslider" + extra_class + "'" + properties["tooltip-input"] + "><input type='text' class='leform-rangeslider " + leform_form_elements[i]["css-class"] +
						"' value='" + leform_escape_html(leform_form_elements[i]["default"]) + "' " + rangesliderOptions + " /></div><label class='leform-description" + (leform_form_elements[i]['description-style-align'] != "" ? " leform-ta-" +
							leform_form_elements[i]['description-style-align'] : "") + "'>" + properties["required-description-left"] + leform_escape_html(leform_form_elements[i]["description"]) + properties["required-description-right"] +
						properties["tooltip-description"] + "</label></div><div class='leform-element-cover'></div></div>";
					break;

				case "select":
					let selectOptions = "";
					if (leform_form_elements[i]["please-select-option"] == "on") selectOptions += "<option value=''>" + leform_escape_html(leform_form_elements[i]["please-select-text"]) + "</option>";
					for (var j = 0; j < leform_form_elements[i]["options"].length; j++) {
						selected = "";
						if (leform_form_elements[i]["options"][j].hasOwnProperty("default") && leform_form_elements[i]["options"][j]["default"] == "on") selected = " selected='selected'";
						selectOptions += "<option value='" + leform_escape_html(leform_form_elements[i]["options"][j]["value"]) + "'" + selected + ">" + leform_escape_html(leform_form_elements[i]["options"][j]["label"]) + "</option>";
					}
					if (leform_form_elements[i]['input-style-size'] != "") extra_class += " leform-input-" + leform_form_elements[i]['input-style-size'];
					html += "<div id='leform-element-" + i + "' class='leform-element-" + i + " leform-element" + (properties["label-style-position"] != "" ? " leform-element-label-" + properties["label-style-position"] : "") +
						(leform_form_elements[i]['description-style-position'] != "" ? " leform-element-description-" + leform_form_elements[i]['description-style-position'] : "") + "' data-type='" + leform_form_elements[i]["type"] +
						"'><div class='leform-column-label" + column_label_class + "'><label class='leform-label" + (leform_form_elements[i]['label-style-align'] != "" ? " leform-ta-" + leform_form_elements[i]['label-style-align'] : "") +
						"'>" + properties["required-label-left"] + leform_escape_html(leform_form_elements[i]["label"]) + properties["required-label-right"] + properties["tooltip-label"] + "</label></div><div class='leform-column-input" +
						column_input_class + "'><div class='leform-input" + extra_class + "'" + properties["tooltip-input"] + ">"
						+ "<div class='absolute right-4'><i class='fas fa-chevron-down' style='font-size: 1.3em; color: " +
						leform_form_options["select-arrow-color"]
						+ "'></i></div>"
						+ "<select class='" + (leform_form_elements[i]['input-style-align'] != "" ? "leform-ta-" + leform_form_elements[i]['input-style-align'] + " " : "") + leform_form_elements[i]["css-class"] + "'>"
						+ selectOptions +
						"</select></div><label class='leform-description" + (leform_form_elements[i]['description-style-align'] != "" ? " leform-ta-" + leform_form_elements[i]['description-style-align'] : "") + "'>" +
						properties["required-description-left"] + leform_escape_html(leform_form_elements[i]["description"]) + properties["required-description-right"] + properties["tooltip-description"] +
						"</label></div><div class='leform-element-cover'></div></div>";
					break;

				case "checkbox":
					var checkboxOptions = "";
					style += "#leform-element-" + i + " div.leform-input{height:auto;line-height:1;}";

					if (leform_form_options["checkbox-view"] === "inverted") {
						style += `
							#leform-element-${i} div.leform-input .leform-checkbox-inverted + label {
									background-color: ${leform_form_options["checkbox-radio-unchecked-color-color2"]};
									border-color: ${leform_form_options["checkbox-radio-unchecked-color-color1"]};
							}

							#leform-element-${i} div.leform-input .leform-checkbox-inverted:checked + label {
									background-color: ${leform_form_options["checkbox-radio-unchecked-color-color1"]};
									border-color: ${leform_form_options["checkbox-radio-unchecked-color-color1"]};
							}

							#leform-element-${i} div.leform-input .leform-checkbox-inverted:checked + label::after {
									color: white;
							}
						`;
					}

					properties['checkbox-size'] = leform_form_options['checkbox-radio-style-size'];
					if (leform_form_elements[i]['checkbox-style-position'] == "") {
						properties['checkbox-position'] = leform_form_options['checkbox-radio-style-position'];
					} else {
						properties['checkbox-position'] = leform_form_elements[i]['checkbox-style-position'];
					}
					if (leform_form_elements[i]['checkbox-style-align'] == "") {
						properties['checkbox-align'] = leform_form_options['checkbox-radio-style-align'];
					} else {
						properties['checkbox-align'] = leform_form_elements[i]['checkbox-style-align'];
					}
					if (leform_form_elements[i]['checkbox-style-layout'] == "") {
						properties['checkbox-layout'] = leform_form_options['checkbox-radio-style-layout'];
					} else {
						properties['checkbox-layout'] = leform_form_elements[i]['checkbox-style-layout'];
					}
					extra_class = " leform-cr-layout-" + properties['checkbox-layout'] + " leform-cr-layout-" + properties['checkbox-align'];

					for (var j = 0; j < leform_form_elements[i]["options"].length; j++) {
						selected = "";
						if (
							leform_form_elements[i]["options"][j].hasOwnProperty("default")
							&& leform_form_elements[i]["options"][j]["default"] == "on"
						) {
							selected = " checked='checked'";
						}
						option = "<div class='leform-cr-box'><input class='leform-checkbox leform-checkbox-" + leform_form_options["checkbox-view"] + " leform-checkbox-" + properties["checkbox-size"] +
							"' type='checkbox' id='leform-checkbox-" + i + "-" + j + "' value='" + leform_escape_html(leform_form_elements[i]["options"][j]["value"]) + "'" + selected + " /><label for='leform-checkbox-" +
							i + "-" + j + "'></label></div>";
						if (properties['checkbox-position'] == "left") {
							option += "<div class='leform-cr-label leform-ta-" + properties['checkbox-align'] + "'><label for='leform-checkbox-" + i + "-" + j + "'>" + leform_form_elements[i]["options"][j]["label"] + "</label></div>";
						} else {
							option = "<div class='leform-cr-label leform-ta-" + properties['checkbox-align'] + "'><label for='leform-checkbox-" + i + "-" + j + "'>" + leform_form_elements[i]["options"][j]["label"] + "</label></div>" + option;
						}
						checkboxOptions += "<div class='leform-cr-container leform-cr-container-" + properties["checkbox-size"] + " leform-cr-container-" + properties["checkbox-position"] + "'>" + option + "</div>";
					}
					html += "<div id='leform-element-" + i + "' class='leform-element-" + i + " leform-element" + (properties["label-style-position"] != "" ?
						" leform-element-label-" + properties["label-style-position"] : "") + (leform_form_elements[i]['description-style-position'] != "" ?
							" leform-element-description-" + leform_form_elements[i]['description-style-position'] : "") + "' data-type='" + leform_form_elements[i]["type"] +
						"'><div class='leform-column-label" + column_label_class + "'><label class='leform-label" + (leform_form_elements[i]['label-style-align'] != "" ?
							" leform-ta-" + leform_form_elements[i]['label-style-align'] : "") + "'>" + properties["required-label-left"] + leform_escape_html(leform_form_elements[i]["label"]) +
						properties["required-label-right"] + properties["tooltip-label"] + "</label></div><div class='leform-column-input" + column_input_class + "'><div class='leform-input" +
						extra_class + "'" + properties["tooltip-input"] + ">" + checkboxOptions + "</div><label class='leform-description" + (leform_form_elements[i]['description-style-align'] != "" ? " leform-ta-" +
							leform_form_elements[i]['description-style-align'] : "") + "'>" + properties["required-description-left"] + leform_escape_html(leform_form_elements[i]["description"]) + properties["required-description-right"] +
						properties["tooltip-description"] + "</label></div><div class='leform-element-cover'></div></div>";
					break;

				case "radio":
					var radioOptions = "";
					style += "#leform-element-" + i + " div.leform-input{height:auto;line-height:1;}";
					properties['radio-size'] = leform_form_options['checkbox-radio-style-size'];
					if (leform_form_elements[i]['radio-style-position'] == "") properties['radio-position'] = leform_form_options['checkbox-radio-style-position'];
					else properties['radio-position'] = leform_form_elements[i]['radio-style-position'];
					if (leform_form_elements[i]['radio-style-align'] == "") properties['radio-align'] = leform_form_options['checkbox-radio-style-align'];
					else properties['radio-align'] = leform_form_elements[i]['radio-style-align'];
					if (leform_form_elements[i]['radio-style-layout'] == "") properties['radio-layout'] = leform_form_options['checkbox-radio-style-layout'];
					else properties['radio-layout'] = leform_form_elements[i]['radio-style-layout'];
					extra_class = " leform-cr-layout-" + properties['radio-layout'] + " leform-cr-layout-" + properties['radio-align'];

					for (var j = 0; j < leform_form_elements[i]["options"].length; j++) {
						selected = "";
						if (leform_form_elements[i]["options"][j].hasOwnProperty("default") && leform_form_elements[i]["options"][j]["default"] == "on") selected = " checked='checked'";
						option = "<div class='leform-cr-box'><input class='leform-radio leform-radio-" + leform_form_options["radio-view"] + " leform-radio-" + properties["radio-size"] + "' type='radio' name='leform-radio-" + i + "' id='leform-radio-" + i + "-" + j + "' value='" + leform_escape_html(leform_form_elements[i]["options"][j]["value"]) + "'" + selected + " /><label for='leform-radio-" + i + "-" + j + "'></label></div>";
						if (properties['radio-position'] == "left") option += "<div class='leform-cr-label leform-ta-" + properties['radio-align'] + "'><label for='leform-radio-" + i + "-" + j + "'>" + leform_form_elements[i]["options"][j]["label"] + "</label></div>";
						else option = "<div class='leform-cr-label leform-ta-" + properties['radio-align'] + "'><label for='leform-radio-" + i + "-" + j + "'>" + leform_form_elements[i]["options"][j]["label"] + "</label></div>" + option;
						radioOptions += "<div class='leform-cr-container leform-cr-container-" + properties["radio-size"] + " leform-cr-container-" + properties["radio-position"] + "'>" + option + "</div>";
					}
					html += "<div id='leform-element-" + i + "' class='leform-element-" + i + " leform-element" + (properties["label-style-position"] != "" ? " leform-element-label-" +
						properties["label-style-position"] : "") + (leform_form_elements[i]['description-style-position'] != "" ? " leform-element-description-" + leform_form_elements[i]['description-style-position'] : "") +
						"' data-type='" + leform_form_elements[i]["type"] + "'><div class='leform-column-label" + column_label_class + "'><label class='leform-label" +
						(leform_form_elements[i]['label-style-align'] != "" ? " leform-ta-" + leform_form_elements[i]['label-style-align'] : "") + "'>" + properties["required-label-left"] +
						leform_escape_html(leform_form_elements[i]["label"]) + properties["required-label-right"] + properties["tooltip-label"] + "</label></div><div class='leform-column-input" +
						column_input_class + "'><div class='leform-input" + extra_class + "'" + properties["tooltip-input"] + ">" + radioOptions + "</div><label class='leform-description" +
						(leform_form_elements[i]['description-style-align'] != "" ? " leform-ta-" + leform_form_elements[i]['description-style-align'] : "") + "'>" + properties["required-description-left"] +
						leform_escape_html(leform_form_elements[i]["description"]) + properties["required-description-right"] + properties["tooltip-description"] + "</label></div><div class='leform-element-cover'></div></div>";
					break;

				case "matrix":
					style += "#leform-element-" + i + " div.leform-input{height:auto;line-height:1;}";
					properties['checkbox-size'] = leform_form_options['checkbox-radio-style-size'];

					if (leform_form_options["checkbox-view"] === "inverted") {
						style += `
                            #leform-element-${i} div.leform-input .leform-checkbox-inverted + label {
                                background-color: ${leform_form_options["checkbox-radio-unchecked-color-color2"]};
                                border-color: ${leform_form_options["checkbox-radio-unchecked-color-color1"]};
                            }

                            #leform-element-${i} div.leform-input .leform-checkbox-inverted:checked + label {
                                background-color: ${leform_form_options["checkbox-radio-unchecked-color-color1"]};
                                border-color: ${leform_form_options["checkbox-radio-unchecked-color-color1"]};
                            }

                            #leform-element-${i} div.leform-input .leform-checkbox-inverted:checked + label::after {
                                color: white;
                            }
                        `;
					}

					if (leform_form_elements[i]['checkbox-style-position'] == "") {
						properties['checkbox-position'] = leform_form_options['checkbox-radio-style-position'];
					} else {
						properties['checkbox-position'] = leform_form_elements[i]['checkbox-style-position'];
					}

					if (leform_form_elements[i]['checkbox-style-align'] == "") {
						properties['checkbox-align'] = leform_form_options['checkbox-radio-style-align'];
					} else {
						properties['checkbox-align'] = leform_form_elements[i]['checkbox-style-align'];
					}

					if (leform_form_elements[i]['checkbox-style-layout'] == "") {
						properties['checkbox-layout'] = leform_form_options['checkbox-radio-style-layout'];
					} else {
						properties['checkbox-layout'] = leform_form_elements[i]['checkbox-style-layout'];
					}

					extra_class = ` leform-cr-layout-${properties['checkbox-layout']
						} leform-cr-layout-${properties['checkbox-align']
						}`;

					options = '';

					const isCheckbox = leform_form_elements[i]['multi-select'] === 'on';

					for (const leftOption of leform_form_elements[i]["top"]) {
						options += `
							<div class="pb-3 pr-2">
								${leftOption.label}
							</div>
						`;
					}
					options = `
                        <div class="grid grid-cols-${leform_form_elements[i]["top"].length + 2} gap-2">
                            <div class="col-span-2"></div>
                            ${options}
                        </div>
                    `;

					for (let j = 0; j < leform_form_elements[i]["left"].length; j++) {
						let row = '';
						for (let k = 0; k < leform_form_elements[i]["top"].length; k++) {
							let classlist = '';

							if (isCheckbox) {
								classlist = [
									'leform-checkbox',
									`leform-checkbox-${leform_form_options["checkbox-view"]}`,
									`leform-checkbox-${properties["checkbox-size"]}`,
								].join(' ');
							} else {
								classlist = [
									'leform-radio',
									`leform-radio-${leform_form_options["radio-view"]}`,
									`leform-radio-${properties["checkbox-size"]}`,
								].join(' ');
							}

							let value = [
								leform_escape_html(leform_form_elements[i]["left"][j]["value"]),
								leform_escape_html(leform_form_elements[i]["top"][k]["value"]),
							].join('--');

							option = `
								<div class='leform-cr-box inline-block'>
									<input
										class='${classlist}'
										type='${isCheckbox ? 'checkbox' : 'radio'}'
										id='leform-checkbox-${i}-${j}-${k}'
										value='${value}'
										name='${leform_form_elements[i]['name']}'
									/>
									<label for='leform-checkbox-${i}-${j}-${k}'></label>
								</div>
							`;

							row += `
								<div class='leform-cr-container leform-cr-container-${properties["checkbox-size"]
								} leform-cr-container-${properties["checkbox-position"]
								}'>
									${option}
								</div>
							`;
						}
						row = `
                            <form class="grid grid-cols-${leform_form_elements[i]["top"].length + 2} gap-2">
                                <div class="col-span-2">
                                    ${leform_form_elements[i]["left"][j]["label"]}
                                </div>
                                ${row}
                            </form>
                        `;
						options += row;
					}

					html += `
						<div
							id='leform-element-${i}'
							class='leform-element-${i} leform-element ${(properties["label-style-position"] != ""
							? " leform-element-label-" + properties["label-style-position"]
							: ""
						)
						}${(leform_form_elements[i]['description-style-position'] != ""
							? " leform-element-description-" + leform_form_elements[i]['description-style-position']
							: ""
						)
						}'
							data-type='${leform_form_elements[i]["type"]}'
						>
							<div class='leform-column-label${column_label_class}'>
								<label
									class='leform-label${(leform_form_elements[i]['label-style-align'] != ""
							? " leform-ta-" + leform_form_elements[i]['label-style-align']
							: ""
						)
						}'
								>
									${properties["required-label-left"]
						+ leform_escape_html(leform_form_elements[i]["label"])
						+ properties["required-label-right"]
						+ properties["tooltip-label"]
						}
								</label>
							</div>
							<div class='leform-column-input${column_input_class}'>
								<div
									class='leform-input inline-block${extra_class}'
									${properties["tooltip-input"]}
								>
									${options}
								</div>
								<label
									class='leform-description${(leform_form_elements[i]['description-style-align'] != ""
							? " leform-ta-" + leform_form_elements[i]['description-style-align']
							: ""
						)
						}'
								>
									${properties["required-description-left"]
						+ leform_escape_html(leform_form_elements[i]["description"])
						+ properties["required-description-right"]
						+ properties["tooltip-description"]
						}
								</label>
							</div>
							<div class='leform-element-cover'></div>
						</div>
					`;
					break;

				case "repeater-input": {
					const fields = leform_form_elements[i]["fields"];

					style += "#leform-element-" + i + " div.leform-input{height:auto;line-height:1;}";
					style += `
                        #leform-element-${i} thead td {
                            font-weight: bold;
                        }

                        #leform-element-${i} .add-row {
                            background-color: #FFFFFF;
                            border-color: ${leform_form_options["html-headings-color"]
						|| leform_form_options["input-text-style-color"]
						};
                        }

                        #leform-element-${i} .add-row:hover {
                            background-color: ${leform_form_options["html-headings-color"]
						|| leform_form_options["input-text-style-color"]
						};
                        }

                        #leform-element-${i} .add-row span,
                        #leform-element-${i} .add-row i
                        {
                            color: ${leform_form_options["html-headings-color"]
						|| leform_form_options["input-text-style-color"]
						};
                        }

                        #leform-element-${i} .add-row:hover span,
                        #leform-element-${i} .add-row:hover i
                        {
                            color: #FFFFFF;
                        }

                        .leform-element-${i} .add-row span {
                            font-weight: bold;
                        }

                        #leform-element-${i} .add-row i {
                            font-size: 16px;
                        }
                    `;

					if (leform_form_options["star-rating-color"]) {
						if (leform_form_options["filled-star-rating-mode"] === "on") {
							style += `.leform-star-rating > label {
                                color: ${leform_form_elements[i]['star-style-color-unrated']} !important;
                            }`;
						} else {
							style += `.leform-star-rating > label {
                                color: ${leform_form_options["star-rating-color"]} !important;
                            }`;
						}
						style += `
                            .leform-star-rating>input:checked~label,
                            .leform-star-rating:not(:checked)>label:hover,
                            .leform-star-rating:not(:checked)>label:hover~label {
                                color: ${leform_form_options["star-rating-color"]} !important;
                            }
                        `;
					}

					html += `
                        <div
                            id='leform-element-${i}'
                            class='leform-element-${i} leform-element${(properties["label-style-position"] != ""
							? " leform-element-label-" + properties["label-style-position"]
							: "")
						+ (leform_form_elements[i]['description-style-position'] != ""
							? " leform-element-description-" + leform_form_elements[i]['description-style-position']
							: "")
						}'
                            data-type='${leform_form_elements[i]["type"]}'
                        >
                            <div class='leform-column-label${column_label_class}'>
                                <label
                                    class='leform-label${(leform_form_elements[i]['label-style-align'] != ""
							? " leform-ta-" + leform_form_elements[i]['label-style-align']
							: ""
						)
						}'
                                >
                                    ${properties["required-label-left"]
						+ leform_escape_html(leform_form_elements[i]["label"])
						+ properties["required-label-right"]
						+ properties["tooltip-label"]}
                                </label>
                            </div>
                            <div class='leform-column-input${column_input_class}'>
                                <div>
                                    <table class="w-full">
                                        <thead>
                                            <tr>
                                                ${fields.map((field) => `
                                                    <td
                                                        class="${leform_form_elements[i]["has-borders"] === "on" ? "border-2" : ""
							} p-2"
                                                        style="border-color: #d5d9dd;"
                                                    >
                                                        ${field.name}
                                                    </td>
                                                `).join("\n")}
                                                <td
                                                    class="${leform_form_elements[i]["has-borders"] === "on" ? "border-2" : ""
						} w-10"
                                                    style="border-color: #d5d9dd;"
                                                ></td>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                ${fields.map((field, columnIndex) => {
							switch (field.type) {
								case "text":
								case "email":
								case "password":
									return `
                                                                <td class="p-2 ${leform_form_elements[i]["has-borders"] === "on" ? "border-2" : ""
										}">
                                                                    <div class="leform-input">
                                                                        <input
                                                                            class="w-full"
                                                                            type="${field.type}"
                                                                            placeholder="${field.placeholder || ""}"
                                                                            value="${field.defaultValue || ""}"
                                                                        />
                                                                    </div>
                                                               </td>
                                                            `;
								case "number":
									return `
                                                                <td class="p-2 ${leform_form_elements[i]["has-borders"] === "on" ? "border-2" : ""
										}">
                                                                    <div class="leform-input">
                                                                        <input
                                                                            class="w-full"
                                                                            type='number'
                                                                            placeholder='${field.placeholder || ""}'
                                                                            value="${field.defaultValue || ""}"
                                                                            style="border-color: ${leform_form_options["input-border-style-color"]}; border-radius: ${leform_form_options["input-border-style-radius"]}px;"
                                                                        />
                                                                    </div>
                                                                </td>
                                                            `;
								case "select":
									let options = "";
									for (let i = 0; i < field.options.length; i++) {
										const option = field.options[i];
										const selected = (field["defaultValue"] === i)
											? "selected"
											: "";
										options += `
                                                                    <option
                                                                        value="${leform_escape_html(option)}"
                                                                        ${selected}
                                                                    >
                                                                        ${leform_escape_html(option)}
                                                                    </option>
                                                                `;
									}

									return `
                                                                <td class="p-2 ${leform_form_elements[i]["has-borders"] === "on" ? "border-2" : ""
										}">
                                                                    <div class="leform-input w-20">
                                                                        <select class="w-full">
                                                                            <option
                                                                                value=""
                                                                                selected="selected"
                                                                                disabled
                                                                            >
                                                                                ${field.placeholder || ""}
                                                                            </option>
                                                                            ${options}
                                                                        </select>
                                                                    </div>
                                                                </td>
                                                            `;
								case "date":
									return `
                                                                <td class="p-2 ${leform_form_elements[i]["has-borders"] === "on" ? "border-2" : ""
										}">
                                                                    <div class='leform-input'>
                                                                        <input
                                                                            type='text'
                                                                            class='leform-date w-full'
                                                                            data-default='${field["defaultValue"]}'
                                                                            value='${field["defaultValue"]}'
                                                                        />
                                                                    </div>
                                                                </td>
                                                            `;
								case "time":
									return `
                                                                <td class="p-2 ${leform_form_elements[i]["has-borders"] === "on" ? "border-2" : ""
										}">
                                                                    <div class='leform-input'>
                                                                        <input
                                                                            type='text'
                                                                            class='leform-time w-full'
                                                                            data-default='${field["defaultValue"]}'
                                                                            value='${field["defaultValue"]}'
                                                                        />
                                                                    </div>
                                                                </td>
                                                            `;
								case "rangeslider":
									let rangeSliderOptions = [
										/*
										[
												"data-type",
												leform_meta["rangeslider"]["double"] == "on"
														? "double"
														: "single"
										],
										*/
										["data-type", "single"],
										[
											"data-grid",
											leform_meta["rangeslider"]["grid-enable"] == "on"
												? "true"
												: "false"
										],
										[
											"data-hide-min-max",
											leform_meta["rangeslider"]["min-max-labels"] == "on"
												? "false"
												: "true"
										],
										["data-skin", leform_form_options['rangeslider-skin']],
										[
											"data-from",
											field["defaultValue"] || field["min"]
										],
										["data-min", field["min"]],
										["data-max", field["max"]],
									];

									rangeSliderOptions = rangeSliderOptions
										.reduce((str, option) =>
											str += ` ${option.join("=")}`, "");

									return `
                                                                <td class="p-2 ${leform_form_elements[i]["has-borders"] === "on" ? "border-2" : ""
										}">
                                                                    <div
                                                                        class='leform-input'
                                                                        style='width: 150px; height:auto; line-height:1;'
                                                                    >
                                                                        <input
                                                                            type='text w-full'
                                                                            class='leform-rangeslider'
                                                                            ${rangeSliderOptions}
                                                                        />
                                                                    </div>
                                                                </td>
                                                            `;

								case "star-rating":
									let starRatingOptions = "";
									const starCount = field["starCount"] || 5;

									for (let j = starCount; j > 0; j--) {
										starRatingOptions += `
                                                                    <input
                                                                        type='radio'
                                                                        id='leform-stars-${i}-${columnIndex}-${j}'
                                                                        name='leform-stars-${i}-${columnIndex}'
                                                                        value='${j}'
                                                                    />
                                                                    <label for='leform-stars-${i}-${columnIndex}-${j}'>
                                                                    </label>
                                                                `;
									}

									return `
                                                                <td class="p-2 ${leform_form_elements[i]["has-borders"] === "on" ? "border-2" : ""
										}">
                                                                    <div class='leform-input'>
                                                                        <fieldset class='leform-star-rating'>
                                                                            ${starRatingOptions}
                                                                        </fieldset>
                                                                    </div>
                                                                </td>
                                                            `;
								case "link-button":
									return `
                                                                <td class="p-2 ${leform_form_elements[i]["has-borders"] === "on" ? "border-2" : ""
										}">
                                                                    <div>
                                                                        <a
                                                                            class="leform-button leform-button-${leform_form_options["button-active-transform"]
										} leform-button-${properties["width"]
										} leform-button-${properties["size"]
										}"
                                                                            href="${field.href || ""}"
                                                                            target="_blank"
                                                                            onclick="return false;"
                                                                        >
                                                                            ${field.buttonText || ""}
                                                                        </a>
                                                                        <div class="leform-element-cover"></div>
                                                                    </div>
                                                                </td>
                                                            `;
								case "html":
									return `
                                                                <td class="p-2 ${leform_form_elements[i]["has-borders"] === "on" ? "border-2" : ""
										}">
                                                                    <div>
                                                                        ${field["content"] || ""}
                                                                        <div class='leform-element-cover'></div>
                                                                    </div>
                                                                </td>
                                                            `;
							}
						}).join("\n")}
                                                <td class="${leform_form_elements[i]["has-borders"] === "on" ? "border-2" : ""
						} w-10">
                                                    <div class="flex justify-center">
                                                        <button
                                                            class="bg-red-400 rounded-xl w-6 h-6 flex justify-center items-center"
                                                            style="color: white;"
                                                        >
                                                            -
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                        <tfoot>
                                            <tr class="${fields.length >= parseInt(leform_form_elements[i]["add-row-width"])
							? leform_form_elements[i]["has-borders"] === "on" ? "border-2" : ""
							: ""
						}">
                                                <td
                                                    class="p-2 ${fields.length >= parseInt(leform_form_elements[i]["add-row-width"])
							? ""
							: leform_form_elements[i]["has-borders"] === "on" ? "border-2" : ""
						}"
                                                    colspan="${leform_form_elements[i]["add-row-width"]}"
                                                >
                                                    <button class='add-row ${leform_form_elements[i]["has-borders"] === "on" ? "border-2" : ""
						} w-full px-4 py-3 rounded-md flex justify-between items-center focus:outline-none border-2'>
                                                        <span>${leform_form_elements[i]["add-row-label"]}</span>
                                                        <i class='fa fa-plus'></i>
                                                    </button>
                                                </td>
                                                ${fields.length >= parseInt(leform_form_elements[i]["add-row-width"])
							? "<td colspan='999'></td>"
							: ""
						}
                                            </tr>
                                            ${(leform_form_elements[i]["has-footer"] === "on")
							? `
                                                    <tr>
                                                        <td class="p-2 ${leform_form_elements[i]["has-borders"] === "on" ? "border-2" : ""
							}" colspan='999'>
                                                            ${leform_form_elements[i]["footer-tolals"]}
                                                        </td>
                                                    </tr>
                                                `
							: ""
						}
                                        </tfoot>
                                    </table>
                                </div>
                                <div class='leform-input${extra_class}' ${properties["tooltip-input"]} style="height: 0px";></div>
                                <label class='leform-description${(leform_form_elements[i]['description-style-align'] != ""
							? " leform-ta-" + leform_form_elements[i]['description-style-align']
							: "")
						}'>
                                    ${properties["required-description-left"]
						+ leform_escape_html(leform_form_elements[i]["description"])
						+ properties["required-description-right"]
						+ properties["tooltip-description"]}
                                </label>
                            </div>
                            <div class='leform-element-cover'></div>
                        </div>
                    `;
					break;
				}

				case "imageselect":
					style += "#leform-element-" + i + " div.leform-input{height:auto;line-height:1;}";

					properties['image-size'] = leform_form_elements[i]['image-style-size'];
					properties["image-width"] = leform_form_elements[i]['image-style-width'];
					if (!leform_is_numeric(properties["image-width"])) properties["image-width"] = 120;
					properties["image-height"] = leform_form_elements[i]['image-style-height'];
					if (!leform_is_numeric(properties["image-height"])) properties["image-height"] = 120;
					properties["label-height"] = leform_form_elements[i]['label-height'];
					if (!leform_is_numeric(properties["label-height"]) || leform_form_elements[i]['label-enable'] != "on") properties["label-height"] = 0;
					properties["image-width"] = parseInt(properties["image-width"], 10);
					properties["image-height"] = parseInt(properties["image-height"], 10);
					properties["label-height"] = parseInt(properties["label-height"], 10);

					if (leform_form_options.hasOwnProperty('imageselect-selected-scale') && leform_form_options['imageselect-selected-scale'] == "on") {
						var scale = 1.10;
						if (properties["image-width"] > 0 && properties["image-height"] > 0) scale = Math.min(parseFloat((properties["image-width"] + 8) / properties["image-width"]), parseFloat((properties["image-height"] + 8) / properties["image-height"]));
						style += "#leform-element-" + i + " div.leform-input .leform-imageselect:checked+label {transform: scale(" + scale + ");}";
					}
					extra_class += ' leform-ta-' + leform_form_options['imageselect-style-align'] + ' leform-imageselect-' + leform_form_options['imageselect-style-effect'];
					style += "#leform-element-" + i + " div.leform-input .leform-imageselect+label {width:" + properties["image-width"] + "px;height:" + parseInt(properties["image-height"] + properties["label-height"], 10) + "px;}";
					style += "#leform-element-" + i + " div.leform-input .leform-imageselect+label span.leform-imageselect-image {height:" + properties["image-height"] + "px;background-size:" + properties['image-size'] + ";}";
					for (var j = 0; j < leform_form_elements[i]["options"].length; j++) {
						selected = "";
						if (leform_form_elements[i]["options"][j].hasOwnProperty("default") && leform_form_elements[i]["options"][j]["default"] == "on") selected = " checked='checked'";
						properties['image-label'] = "";
						if (properties["label-height"] > 0) {
							properties['image-label'] = "<span class='leform-imageselect-label'>" + leform_escape_html(leform_form_elements[i]["options"][j]["label"]) + "</span>";
						}
						options += "<input class='leform-imageselect' type='" + leform_form_elements[i]['mode'] + "' name='leform-image-" + i + "' id='leform-image-" + i + "-" + j + "' value='" + leform_escape_html(leform_form_elements[i]["options"][j]["value"]) + "'" + selected + " /><label for='leform-image-" + i + "-" + j + "'><span class='leform-imageselect-image' style='background-image: url(" + leform_form_elements[i]["options"][j]["image"] + ");'></span>" + properties['image-label'] + "</label>";
					}

					html += "<div id='leform-element-" + i + "' class='leform-element-" + i + " leform-element" + (properties["label-style-position"] != "" ? " leform-element-label-" + properties["label-style-position"] : "") + (leform_form_elements[i]['description-style-position'] != "" ? " leform-element-description-" + leform_form_elements[i]['description-style-position'] : "") + "' data-type='" + leform_form_elements[i]["type"] + "'><div class='leform-column-label" + column_label_class + "'><label class='leform-label" + (leform_form_elements[i]['label-style-align'] != "" ? " leform-ta-" + leform_form_elements[i]['label-style-align'] : "") + "'>" + properties["required-label-left"] + leform_escape_html(leform_form_elements[i]["label"]) + properties["required-label-right"] + properties["tooltip-label"] + "</label></div><div class='leform-column-input" + column_input_class + "'><div class='leform-input" + extra_class + "'" + properties["tooltip-input"] + ">" + options + "</div><label class='leform-description" + (leform_form_elements[i]['description-style-align'] != "" ? " leform-ta-" + leform_form_elements[i]['description-style-align'] : "") + "'>" + properties["required-description-left"] + leform_escape_html(leform_form_elements[i]["description"]) + properties["required-description-right"] + properties["tooltip-description"] + "</label></div><div class='leform-element-cover'></div></div>";
					break;

				case "tile":
					style += "#leform-element-" + i + " div.leform-input{height:auto;line-height:1;}";
					if (leform_form_elements[i].hasOwnProperty("tile-style-size") && leform_form_elements[i]['tile-style-size'] != "") properties['size'] = leform_form_elements[i]['tile-style-size'];
					else properties['size'] = leform_form_options['tile-style-size'];
					if (leform_form_elements[i].hasOwnProperty("tile-style-width") && leform_form_elements[i]['tile-style-width'] != "") properties['width'] = leform_form_elements[i]['tile-style-width'];
					else properties['width'] = leform_form_options['tile-style-width'];
					if (leform_form_elements[i].hasOwnProperty("tile-style-position") && leform_form_elements[i]['tile-style-position'] != "") properties['position'] = leform_form_elements[i]['tile-style-position'];
					else properties['position'] = leform_form_options['tile-style-position'];
					if (leform_form_elements[i].hasOwnProperty("tile-style-layout") && leform_form_elements[i]['tile-style-layout'] != "") properties['layout'] = leform_form_elements[i]['tile-style-layout'];
					else properties['layout'] = leform_form_options['tile-style-layout'];
					extra_class = " leform-tile-layout-" + properties['layout'] + " leform-tile-layout-" + properties['position'] + " leform-tile-transform-" + leform_form_options['tile-selected-transform'];

					for (var j = 0; j < leform_form_elements[i]["options"].length; j++) {
						selected = "";
						if (leform_form_elements[i]["options"][j].hasOwnProperty("default") && leform_form_elements[i]["options"][j]["default"] == "on") selected = " checked='checked'";
						option = "<div class='leform-tile-box'><input class='leform-tile leform-tile-" + properties["size"] + "' type='" + leform_form_elements[i]['mode'] + "' name='leform-tile-" + i + "' id='leform-tile-" + i + "-" + j + "' value='" + leform_escape_html(leform_form_elements[i]["options"][j]["value"]) + "'" + selected + " /><label for='leform-tile-" + i + "-" + j + "'>" + leform_form_elements[i]["options"][j]["label"] + "</label></div>";
						options += "<div class='leform-tile-container leform-tile-" + properties["width"] + "'>" + option + "</div>";
					}
					html += "<div id='leform-element-" + i + "' class='leform-element-" + i + " leform-element" + (properties["label-style-position"] != "" ? " leform-element-label-" + properties["label-style-position"] : "") + (leform_form_elements[i]['description-style-position'] != "" ? " leform-element-description-" + leform_form_elements[i]['description-style-position'] : "") + "' data-type='" + leform_form_elements[i]["type"] + "'><div class='leform-column-label" + column_label_class + "'><label class='leform-label" + (leform_form_elements[i]['label-style-align'] != "" ? " leform-ta-" + leform_form_elements[i]['label-style-align'] : "") + "'>" + properties["required-label-left"] + leform_escape_html(leform_form_elements[i]["label"]) + properties["required-label-right"] + properties["tooltip-label"] + "</label></div><div class='leform-column-input" + column_input_class + "'><div class='leform-input" + extra_class + "'" + properties["tooltip-input"] + ">"
						+ "<form>"
						+ options
						+ "</form>"
						+ "</div><label class='leform-description" + (leform_form_elements[i]['description-style-align'] != "" ? " leform-ta-" + leform_form_elements[i]['description-style-align'] : "") + "'>" + properties["required-description-left"] + leform_escape_html(leform_form_elements[i]["description"]) + properties["required-description-right"] + properties["tooltip-description"] + "</label></div><div class='leform-element-cover'></div></div>";
					break;

				case "multiselect":
					style += "#leform-element-" + i + " div.leform-input{height:auto;line-height:1;}";
					if (leform_form_elements[i]['multiselect-style-height'] != "") style += "#leform-element-" + i + " div.leform-multiselect {height:" + parseInt(leform_form_elements[i]['multiselect-style-height'], 10) + "px;}";
					if (leform_form_elements[i]['multiselect-style-align'] != "") properties['align'] = leform_form_elements[i]['multiselect-style-align'];
					else if (leform_form_options['multiselect-style-align'] != "") properties['align'] = leform_form_options['multiselect-style-align'];
					else properties['align'] = 'left';

					for (var j = 0; j < leform_form_elements[i]["options"].length; j++) {
						selected = "";
						if (leform_form_elements[i]["options"][j].hasOwnProperty("default") && leform_form_elements[i]["options"][j]["default"] == "on") selected = " checked='checked'";
						options += "<input type='checkbox' id='leform-checkbox-" + i + "-" + j + "' value='" + leform_escape_html(leform_form_elements[i]["options"][j]["value"]) + "'" + selected + " /><label for='leform-checkbox-" + i + "-" + j + "'>" + leform_escape_html(leform_form_elements[i]["options"][j]["label"]) + "</label>";
					}
					html += "<div id='leform-element-" + i + "' class='leform-element-" + i + " leform-element" + (properties["label-style-position"] != "" ? " leform-element-label-" + properties["label-style-position"] : "") + (leform_form_elements[i]['description-style-position'] != "" ? " leform-element-description-" + leform_form_elements[i]['description-style-position'] : "") + "' data-type='" + leform_form_elements[i]["type"] + "'><div class='leform-column-label" + column_label_class + "'><label class='leform-label" + (leform_form_elements[i]['label-style-align'] != "" ? " leform-ta-" + leform_form_elements[i]['label-style-align'] : "") + "'>" + properties["required-label-left"] + leform_escape_html(leform_form_elements[i]["label"]) + properties["required-label-right"] + properties["tooltip-label"] + "</label></div><div class='leform-column-input" + column_input_class + "'><div class='leform-input'" + properties["tooltip-input"] + "><div class='leform-multiselect leform-ta-" + properties["align"] + "'>" + options + "</div></div><label class='leform-description" + (leform_form_elements[i]['description-style-align'] != "" ? " leform-ta-" + leform_form_elements[i]['description-style-align'] : "") + "'>" + properties["required-description-left"] + leform_escape_html(leform_form_elements[i]["description"]) + properties["required-description-right"] + properties["tooltip-description"] + "</label></div><div class='leform-element-cover'></div></div>";
					break;

				case "file":
					icon = "";
					if (leform_form_elements[i].hasOwnProperty("button-style-size") && leform_form_elements[i]['button-style-size'] != "") properties['size'] = leform_form_elements[i]['button-style-size'];
					else properties['size'] = leform_form_options['button-style-size'];
					if (leform_form_elements[i].hasOwnProperty("button-style-width") && leform_form_elements[i]['button-style-width'] != "") properties['width'] = leform_form_elements[i]['button-style-width'];
					else properties['width'] = leform_form_options['button-style-width'];
					if (leform_form_elements[i].hasOwnProperty("button-style-position") && leform_form_elements[i]['button-style-position'] != "") properties['position'] = leform_form_elements[i]['button-style-position'];
					else properties['position'] = leform_form_options['button-style-position'];
					label = "<span>" + leform_escape_html(leform_form_elements[i]["button-label"]) + "</span>";
					if (leform_form_elements[i].hasOwnProperty("icon-left") && leform_form_elements[i]["icon-left"] != "") label = "<i class='leform-icon-left " + leform_form_elements[i]["icon-left"] + "'></i>" + label;
					if (leform_form_elements[i].hasOwnProperty("icon-right") && leform_form_elements[i]["icon-right"] != "") label += "<i class='leform-icon-right " + leform_form_elements[i]["icon-right"] + "'></i>";

					properties['style-attr'] = "";
					if (leform_form_elements[i].hasOwnProperty("colors-background") && leform_form_elements[i]["colors-background"] != "") properties['style-attr'] += "background-color:" + leform_form_elements[i]["colors-background"] + ";";
					if (leform_form_elements[i].hasOwnProperty("colors-border") && leform_form_elements[i]["colors-border"] != "") properties['style-attr'] += "border-color:" + leform_form_elements[i]["colors-border"] + ";";
					if (leform_form_elements[i].hasOwnProperty("colors-text") && leform_form_elements[i]["colors-text"] != "") properties['style-attr'] += "color:" + leform_form_elements[i]["colors-text"] + ";";
					if (properties['style-attr'] != "") style += "#leform-element-" + i + " .leform-button{" + properties['style-attr'] + "}";

					properties['style-attr'] = "";
					if (leform_form_elements[i].hasOwnProperty("colors-hover-background") && leform_form_elements[i]["colors-hover-background"] != "") properties['style-attr'] += "background-color:" + leform_form_elements[i]["colors-hover-background"] + ";";
					if (leform_form_elements[i].hasOwnProperty("colors-hover-border") && leform_form_elements[i]["colors-hover-border"] != "") properties['style-attr'] += "border-color:" + leform_form_elements[i]["colors-hover-border"] + ";";
					if (leform_form_elements[i].hasOwnProperty("colors-hover-text") && leform_form_elements[i]["colors-hover-text"] != "") properties['style-attr'] += "color:" + leform_form_elements[i]["colors-hover-text"] + ";";
					if (properties['style-attr'] != "") style += "#leform-element-" + i + " .leform-button:hover{" + properties['style-attr'] + "}";

					properties['style-attr'] = "";
					if (leform_form_elements[i].hasOwnProperty("colors-active-background") && leform_form_elements[i]["colors-active-background"] != "") properties['style-attr'] += "background-color:" + leform_form_elements[i]["colors-active-background"] + ";";
					if (leform_form_elements[i].hasOwnProperty("colors-active-border") && leform_form_elements[i]["colors-active-border"] != "") properties['style-attr'] += "border-color:" + leform_form_elements[i]["colors-active-border"] + ";";
					if (leform_form_elements[i].hasOwnProperty("colors-active-text") && leform_form_elements[i]["colors-active-text"] != "") properties['style-attr'] += "color:" + leform_form_elements[i]["colors-active-text"] + ";";
					if (properties['style-attr'] != "") style += "#leform-element-" + i + " .leform-button:active{" + properties['style-attr'] + "}";

					html += "<div id='leform-element-" + i + "' class='leform-element-" + i + " leform-element" + (properties["label-style-position"] != "" ? " leform-element-label-" + properties["label-style-position"] : "") + (leform_form_elements[i]['description-style-position'] != "" ? " leform-element-description-" + leform_form_elements[i]['description-style-position'] : "") + "' data-type='" + leform_form_elements[i]["type"] + "'><div class='leform-column-label" + column_label_class + "'><label class='leform-label" + (leform_form_elements[i]['label-style-align'] != "" ? " leform-ta-" + leform_form_elements[i]['label-style-align'] : "") + "'>" + properties["required-label-left"] + leform_escape_html(leform_form_elements[i]["label"]) + properties["required-label-right"] + properties["tooltip-label"] + "</label></div><div class='leform-column-input" + column_input_class + "'><div class='leform-upload-input leform-ta-" + properties['position'] + extra_class + "'" + properties["tooltip-input"] + "><a class='leform-button leform-button-" + leform_form_options["button-active-transform"] + " leform-button-" + properties['width'] + " leform-button-" + properties['size'] + " " + leform_form_elements[i]["css-class"] + "' href='#' onclick='return false;'>" + label + "</a></div><label class='leform-description" + (leform_form_elements[i]['description-style-align'] != "" ? " leform-ta-" + leform_form_elements[i]['description-style-align'] : "") + "'>" + properties["required-description-left"] + leform_escape_html(leform_form_elements[i]["description"]) + properties["required-description-right"] + properties["tooltip-description"] + "</label></div><div class='leform-element-cover'></div></div>";
					break;

				case "date":
					if (leform_form_elements[i]['input-style-size'] != "") extra_class += " leform-input-" + leform_form_elements[i]['input-style-size'];
					html += "<div id='leform-element-" + i + "' class='leform-element-" + i + " leform-element" + (properties["label-style-position"] != "" ? " leform-element-label-" + properties["label-style-position"] : "") + (leform_form_elements[i]['description-style-position'] != "" ? " leform-element-description-" + leform_form_elements[i]['description-style-position'] : "") + "' data-type='" + leform_form_elements[i]["type"] + "'><div class='leform-column-label" + column_label_class + "'><label class='leform-label" + (leform_form_elements[i]['label-style-align'] != "" ? " leform-ta-" + leform_form_elements[i]['label-style-align'] : "") + "'>" + properties["required-label-left"] + leform_escape_html(leform_form_elements[i]["label"]) + properties["required-label-right"] + properties["tooltip-label"] + "</label></div><div class='leform-column-input" + column_input_class + "'><div class='leform-input" + extra_class + "'" + properties["tooltip-input"] + ">" + icon + "<input type='text' class='leform-date " + (leform_form_elements[i]['input-style-align'] != "" ? "leform-ta-" + leform_form_elements[i]['input-style-align'] + " " : "") + leform_form_elements[i]["css-class"] + "' placeholder='" + leform_escape_html(leform_form_elements[i]["placeholder"]) + "' value='" + leform_escape_html(leform_form_elements[i]["default-date"]) + "' /></div><label class='leform-description" + (leform_form_elements[i]['description-style-align'] != "" ? " leform-ta-" + leform_form_elements[i]['description-style-align'] : "") + "'>" + properties["required-description-left"] + leform_escape_html(leform_form_elements[i]["description"]) + properties["required-description-right"] + properties["tooltip-description"] + "</label></div><div class='leform-element-cover'></div></div>";
					break;

				case "time":
					if (leform_form_elements[i]['input-style-size'] != "") extra_class += " leform-input-" + leform_form_elements[i]['input-style-size'];
					html += "<div id='leform-element-" + i + "' class='leform-element-" + i + " leform-element" + (properties["label-style-position"] != "" ? " leform-element-label-" + properties["label-style-position"] : "") + (leform_form_elements[i]['description-style-position'] != "" ? " leform-element-description-" + leform_form_elements[i]['description-style-position'] : "") + "' data-type='" + leform_form_elements[i]["type"] + "'><div class='leform-column-label" + column_label_class + "'><label class='leform-label" + (leform_form_elements[i]['label-style-align'] != "" ? " leform-ta-" + leform_form_elements[i]['label-style-align'] : "") + "'>" + properties["required-label-left"] + leform_escape_html(leform_form_elements[i]["label"]) + properties["required-label-right"] + properties["tooltip-label"] + "</label></div><div class='leform-column-input" + column_input_class + "'><div class='leform-input" + extra_class + "'" + properties["tooltip-input"] + ">" + icon + "<input type='text' class='leform-time " + (leform_form_elements[i]['input-style-align'] != "" ? "leform-ta-" + leform_form_elements[i]['input-style-align'] + " " : "") + leform_form_elements[i]["css-class"] + "' placeholder='" + leform_escape_html(leform_form_elements[i]["placeholder"]) + "' value='" + leform_escape_html(leform_form_elements[i]["default"]) + "' /></div><label class='leform-description" + (leform_form_elements[i]['description-style-align'] != "" ? " leform-ta-" + leform_form_elements[i]['description-style-align'] : "") + "'>" + properties["required-description-left"] + leform_escape_html(leform_form_elements[i]["description"]) + properties["required-description-right"] + properties["tooltip-description"] + "</label></div><div class='leform-element-cover'></div></div>";
					break;

				case "star-rating":
					style += "#leform-element-" + i + " div.leform-input{height:auto;line-height:1;}";
					if (
						leform_form_options["star-rating-color"]
						&& leform_form_elements[i]["overwrite-global-theme-colour"] !== "on"
					) {
						if (leform_form_options["filled-star-rating-mode"] === "on") {
							style += `.leform-star-rating > label {
                                color: ${leform_form_elements[i]['star-style-color-unrated']} !important;
                            }`;
						} else {
							style += `.leform-star-rating > label {
                                color: ${leform_form_options["star-rating-color"]} !important;
                            }`;
						}
						style += `
                            .leform-star-rating>input:checked~label,
                            .leform-star-rating:not(:checked)>label:hover,
                            .leform-star-rating:not(:checked)>label:hover~label {
                                color: ${leform_form_options["star-rating-color"]} !important;
                            }
                        `;
					} else {
						if (leform_form_elements[i]['star-style-color-unrated'] != "") {
							style += `
                                #leform-element-${i} .leform-star-rating > label {
                                    color: ${leform_form_elements[i]['star-style-color-unrated']} !important;
                                }
                            `;
						}

						if (leform_form_elements[i]['star-style-color-rated'] != "") {
							style += `
                                #leform-element-${i} .leform-star-rating > input:checked~label,
                                #leform-element-${i} .leform-star-rating:not(:checked) > label:hover,
                                #leform-element-${i} .leform-star-rating:not(:checked) > label:hover~label {
                                    color: ${leform_form_elements[i]['star-style-color-rated']} !important;
                                }
                            `;
						}
					}

					options = "";
					for (var j = leform_form_elements[i]['total-stars']; j > 0; j--) {
						options += "<input type='radio' id='leform-stars-" + i + "-" + j + "' name='leform-stars-" + i + "' value='" + j + "'" + (leform_form_elements[i]['default'] == j ? " checked='checked'" : "") + " /><label for='leform-stars-" + i + "-" + j + "'></label>";
					}
					extra_class = "";
					if (leform_form_elements[i]['star-style-size'] != "") {
						extra_class += " leform-star-rating-" + leform_form_elements[i]['star-style-size'];
					}
					if (leform_form_options["filled-star-rating-mode"] === "on") {
						extra_class += " filled";
					}
					html += "<div id='leform-element-" + i + "' class='leform-element-" + i + " leform-element" + (properties["label-style-position"] != "" ? " leform-element-label-" + properties["label-style-position"] : "") + (leform_form_elements[i]['description-style-position'] != "" ? " leform-element-description-" + leform_form_elements[i]['description-style-position'] : "") + "' data-type='" + leform_form_elements[i]["type"] + "'><div class='leform-column-label" + column_label_class + "'><label class='leform-label" + (leform_form_elements[i]['label-style-align'] != "" ? " leform-ta-" + leform_form_elements[i]['label-style-align'] : "") + "'>" + properties["required-label-left"] + leform_escape_html(leform_form_elements[i]["label"]) + properties["required-label-right"] + properties["tooltip-label"] + "</label></div><div class='leform-column-input" + column_input_class + "'><div class='leform-input leform-ta-" + leform_form_elements[i]['star-style-position'] + "'" + properties["tooltip-input"] + "><fieldset class='leform-star-rating" + extra_class + "'>" + options + "</fieldset></div><label class='leform-description" + (leform_form_elements[i]['description-style-align'] != "" ? " leform-ta-" + leform_form_elements[i]['description-style-align'] : "") + "'>" + properties["required-description-left"] + leform_escape_html(leform_form_elements[i]["description"]) + properties["required-description-right"] + properties["tooltip-description"] + "</label></div><div class='leform-element-cover'></div></div>";
					break;

				case "html":
					if (leform_form_options["html-headings-color"]) {
						style += `
                            #leform-element-${i} h1,
                            #leform-element-${i} h2,
                            #leform-element-${i} h3,
                            #leform-element-${i} h4,
                            #leform-element-${i} h5,
                            #leform-element-${i} h6 {
                                color: ${leform_form_options["html-headings-color"]};
                            }
                        `;
					}

					if (leform_form_options["html-paragraph-color"]) {
						style += `
                            #leform-element-${i} p {
                                color: ${leform_form_options["html-paragraph-color"]};
                            }
                        `;
					}

					if (leform_form_options["html-hr-color"]) {
						style += `
                            #leform-element-${i} hr {
                                border-color: ${leform_form_options["html-hr-color"]};
                            }
                        `;
					}

					if (leform_form_options["html-hr-height"]) {
						style += `
                            #leform-element-${i} hr {
                                border-top-width: ${leform_form_options["html-hr-height"]}px;
                            }
                        `;
					}

					html += `
                        <div
                            id='leform-element-${i}'
                            class='leform-element-${i} leform-element leform-element-html'
                            data-type='${leform_form_elements[i]["type"]}'
                        >
                            ${leform_form_elements[i]["content"]}
                            <div class='leform-element-cover'></div>
                        </div>
                    `;
					break;

				case "background-image": {
					html += `
							<div
									id='leform-element-${i}'
									class='leform-element-${i} leform-element'
									data-type='${leform_form_elements[i]["type"]}'
							>
									<img src="${leform_form_elements[i]["image"]}" />
							</div>
					`;
					break;
				}

				case "columns":
					var colOptions = "";
					for (var j = 0; j < leform_form_elements[i]['_cols']; j++) {
						properties = _leform_build_children(leform_form_elements[i]['id'], j);
						style += properties["style"];
						colOptions += "<div class='leform-col leform-col-" + leform_form_elements[i]["widths-" + j] + "'><div class='leform-elements' _data-parent='" + leform_form_elements[i]['id'] + "' _data-parent-col='" + j + "'>" + properties["html"] + "</div></div>";
					}
					html += "<div id='leform-element-" + i + "' class='leform-element-" + i + " leform-row leform-element' data-type='" + leform_form_elements[i]["type"] + "'>" + colOptions + "</div>";
					break;


				case "iban-input": {
					html += `
							<div
									id='leform-element-${i}'
									class='leform-element-${i} leform-element leform-row leform-element-label-top'
									data-type='${leform_form_elements[i]["type"]}'
							>
								<div class="leform-col leform-col-7">
									<div style="min-height: 60px;">
										<div data-type="text" style="position: relative; left: 0px; top: 0px;">
											<div class="leform-column-label">
												<label class="leform-label">IBAN</label>
											</div>
											<div class="leform-column-input">
												<div class="leform-input">
													<input name='iban' type="text" class="iban-mask leform-mask" placeholder="DE____________________" data-xmask="SS00000000000000000000" value="">
												</div>
											</div>
										</div>
									</div>
								</div>
								<div class="leform-col leform-col-5">
									<div style="min-height: 60px;">
										<div style="position: relative; left: 0px; top: 0px;">
											<div class="leform-column-label">
												<label class="leform-label">BIC</label>
											</div>
											<div class="leform-column-input">
												<div class="leform-input">
													<input name='bic' readonly type="text" class="bic-mask" placeholder="" value="">
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
					`;
					break;
				}
				default:
					break;
			}
		}
	}
	return { "html": html, "style": style };
}

function leform_build_style_text(_properties, _key) {
	var style = "", webfont = "";
	var integer;
	if (_properties.hasOwnProperty(_key + "-family") && _properties[_key + "-family"] != "") {
		style += "font-family:'" + _properties[_key + "-family"] + "','arial';";
		if (leform_localfonts.indexOf(_properties[_key + "-family"]) == -1 && leform_customfonts.indexOf(_properties[_key + "-family"]) == -1) webfont = _properties[_key + "-family"];
	}
	if (_properties.hasOwnProperty(_key + "-size")) {
		integer = parseInt(_properties[_key + "-size"], 10);
		if (integer >= 8 && integer <= 64) style += "font-size:" + integer + "px;";
	}
	if (_properties.hasOwnProperty(_key + "-color") && _properties[_key + "-color"] != "") style += "color:" + _properties[_key + "-color"] + ";";
	if (_properties.hasOwnProperty(_key + "-bold") && _properties[_key + "-bold"] == "on") style += "font-weight:bold;";
	else style += "font-weight:normal;";
	if (_properties.hasOwnProperty(_key + "-italic") && _properties[_key + "-italic"] == "on") style += "font-style:italic;";
	else style += "font-style:normal;";
	if (_properties.hasOwnProperty(_key + "-underline") && _properties[_key + "-underline"] == "on") style += "text-decoration:underline;";
	else style += "text-decoration:none;";
	if (_properties.hasOwnProperty(_key + "-align") && _properties[_key + "-align"] != "") style += "text-align:" + _properties[_key + "-align"] + ";";
	return { "style": style, "webfont": webfont };
}
function leform_build_style_background(_properties, _key) {
	var style = "";
	var integer, hposition = "left", vposition = "top";
	var direction = "to bottom", color1 = "transparent", color2 = "transparent";
	if (_properties.hasOwnProperty(_key + "-color") && _properties[_key + "-color"] != "") color1 = _properties[_key + "-color"];

	if (_properties.hasOwnProperty(_key + "-gradient") && _properties[_key + "-gradient"] == "2shades") {
		style += "background-color:" + color1 + "; background-image:linear-gradient(to bottom,rgba(255,255,255,.05) 0,rgba(255,255,255,.05) 50%,rgba(0,0,0,.05) 51%,rgba(0,0,0,.05) 100%);";
	} else if (_properties.hasOwnProperty(_key + "-gradient") && (_properties[_key + "-gradient"] == "horizontal" || _properties[_key + "-gradient"] == "vertical" || _properties[_key + "-gradient"] == "diagonal")) {
		if (_properties.hasOwnProperty(_key + "-color2") && _properties[_key + "-color2"] != "") color2 = _properties[_key + "-color2"];
		if (_properties[_key + "-gradient"] == "horizontal") direction = "to right";
		else if (_properties[_key + "-gradient"] == "diagonal") direction = "to bottom right";
		style += "background-image:linear-gradient(" + direction + "," + color1 + "," + color2 + ");";
	} else if (_properties.hasOwnProperty(_key + "-image") && _properties[_key + "-image"] != "") {
		style += "background-color:" + color1 + "; background-image:url('" + _properties[_key + "-image"] + "');";
		if (_properties.hasOwnProperty(_key + "-size") && _properties[_key + "-size"] != "") style += "background-size:" + _properties[_key + "-size"] + ";";
		if (_properties.hasOwnProperty(_key + "-repeat") && _properties[_key + "-repeat"] != "") style += "background-repeat:" + _properties[_key + "-repeat"] + ";";
		if (_properties.hasOwnProperty(_key + "-horizontal-position") && _properties[_key + "-horizontal-position"] != "") {
			switch (_properties[_key + "-horizontal-position"]) {
				case 'center':
					hposition = "center";
					break;
				case 'right':
					hposition = "right";
					break;
				default:
					hposition = "left";
					break;
			}
		}
		if (_properties.hasOwnProperty(_key + "-vertical-position") && _properties[_key + "-vertical-position"] != "") {
			switch (_properties[_key + "-vertical-position"]) {
				case 'center':
					vposition = "center";
					break;
				case 'bottom':
					vposition = "bottom";
					break;
				default:
					vposition = "top";
					break;
			}
		}
		style += "background-position: " + hposition + " " + vposition + ";";
	} else style += "background-color:" + color1 + "; background-image:none;";
	return style;
}
function leform_build_style_border(_properties, _key) {
	var style = "";
	var integer;
	if (_properties.hasOwnProperty(_key + "-width")) {
		integer = parseInt(_properties[_key + "-width"], 10);
		if (integer >= 0 && integer <= 16) style += "border-width:" + integer + "px;";
	}
	if (_properties.hasOwnProperty(_key + "-style") && _properties[_key + "-style"] != "") style += "border-style:" + _properties[_key + "-style"] + ";";
	if (_properties.hasOwnProperty(_key + "-color") && _properties[_key + "-color"] != "") style += "border-color:" + _properties[_key + "-color"] + ";";
	if (_properties.hasOwnProperty(_key + "-radius")) {
		integer = parseInt(_properties[_key + "-radius"], 10);
		if (integer >= 0 && integer <= 100) style += "border-radius:" + integer + "px;";
	}
	if (_properties.hasOwnProperty(_key + "-top") && _properties[_key + "-top"] != "on") style += "border-top:none !important;";
	if (_properties.hasOwnProperty(_key + "-left") && _properties[_key + "-left"] != "on") style += "border-left:none !important;";
	if (_properties.hasOwnProperty(_key + "-right") && _properties[_key + "-right"] != "on") style += "border-right:none !important;";
	if (_properties.hasOwnProperty(_key + "-bottom") && _properties[_key + "-bottom"] != "on") style += "border-bottom:none !important;";
	return style;
}
function leform_build_shadow(_properties, _key) {
	var style = "box-shadow:none;";
	var color = "transparent";
	var shadow_style = "regular";
	if (_properties.hasOwnProperty(_key + "-size") && _properties[_key + "-size"] != "") {
		if (_properties.hasOwnProperty(_key + "-color") && _properties[_key + "-color"] != "") color = _properties[_key + "-color"];
		if (_properties.hasOwnProperty(_key + "-style") && _properties[_key + "-style"] != "") shadow_style = _properties[_key + "-style"];
		switch (shadow_style) {
			case 'solid':
				if (_properties[_key + "-size"] == "tiny") style = "box-shadow: 1px 1px 0px 0px " + color + ";";
				else if (_properties[_key + "-size"] == "small") style = "box-shadow: 2px 2px 0px 0px " + color + ";";
				else if (_properties[_key + "-size"] == "medium") style = "box-shadow: 4px 4px 0px 0px " + color + ";";
				else if (_properties[_key + "-size"] == "large") style = "box-shadow: 6px 6px 0px 0px " + color + ";";
				else if (_properties[_key + "-size"] == "huge") style = "box-shadow: 8px 8px 0px 0px " + color + ";";
				break;
			case 'inset':
				if (_properties[_key + "-size"] == "tiny") style = "box-shadow: inset 0px 0px 15px -9px " + color + ";";
				else if (_properties[_key + "-size"] == "small") style = "box-shadow: inset 0px 0px 15px -8px " + color + ";";
				else if (_properties[_key + "-size"] == "medium") style = "box-shadow: inset 0px 0px 15px -7px " + color + ";";
				else if (_properties[_key + "-size"] == "large") style = "box-shadow: inset 0px 0px 15px -6px " + color + ";";
				else if (_properties[_key + "-size"] == "huge") style = "box-shadow: inset 0px 0px 15px -5px " + color + ";";
				break;
			default:
				if (_properties[_key + "-size"] == "tiny") style = "box-shadow: 1px 1px 15px -9px " + color + ";";
				else if (_properties[_key + "-size"] == "small") style = "box-shadow: 1px 1px 15px -8px " + color + ";";
				else if (_properties[_key + "-size"] == "medium") style = "box-shadow: 1px 1px 15px -7px " + color + ";";
				else if (_properties[_key + "-size"] == "large") style = "box-shadow: 1px 1px 15px -6px " + color + ";";
				else if (_properties[_key + "-size"] == "huge") style = "box-shadow: 1px 1px 15px -5px " + color + ";";
				break;
		}
	}
	return style;
}
function leform_build_style_padding(_properties, _key) {
	var style = "";
	var integer;
	if (_properties.hasOwnProperty(_key + "-top")) {
		integer = parseInt(_properties[_key + "-top"], 10);
		if (integer >= 0 && integer <= 300) style += "padding-top:" + integer + "px;";
	}
	if (_properties.hasOwnProperty(_key + "-right")) {
		integer = parseInt(_properties[_key + "-right"], 10);
		if (integer >= 0 && integer <= 300) style += "padding-right:" + integer + "px;";
	}
	if (_properties.hasOwnProperty(_key + "-bottom")) {
		integer = parseInt(_properties[_key + "-bottom"], 10);
		if (integer >= 0 && integer <= 300) style += "padding-bottom:" + integer + "px;";
	}
	if (_properties.hasOwnProperty(_key + "-left")) {
		integer = parseInt(_properties[_key + "-left"], 10);
		if (integer >= 0 && integer <= 300) style += "padding-left:" + integer + "px;";
	}
	return style;
}

function leform_build_progress() {
	var html = "";
	jQuery(".leform-progress").remove();
	if (leform_form_options["progress-enable"] != "on") return;
	var pages = ".leform-pages-bar-item";
	if (leform_form_options["progress-confirmation-enable"] == "on") pages += ",.leform-pages-bar-item-confirmation";
	var total_pages = jQuery(pages).length;
	var idx = 0;
	jQuery(pages).each(function () {
		var page_id = jQuery(this).attr("data-id");
		var page_name = jQuery(this).attr("data-name");
		if (leform_form_options["progress-type"] == 'progress-2') {
			html = "<div class='leform-progress leform-progress-" + leform_form_options["progress-position"] + "' id='leform-progress-" + page_id + "'><ul class='leform-progress-t2" + (leform_form_options["progress-striped"] == "on" ? " leform-progress-stripes" : "") + "'>";
			var i = 0;
			jQuery(pages).each(function () {
				var page_name = jQuery(this).attr("data-name");
				html += "<li" + (i < idx ? " class='leform-progress-t2-passed'" : (i == idx ? " class='leform-progress-t2-active'" : "")) + " style='width:" + (Math.floor(10000 / total_pages) / 100) + "%;'><span>" + (i + 1) + "</span>" + (leform_form_options["progress-label-enable"] == "on" ? "<label>" + leform_escape_html(page_name) + "</label>" : "") + "</li>";
				i++;
			});
			html += "</ul></div>";
		} else {
			var width = parseInt(Math.round(100 * (idx + 1) / total_pages), 10);
			html = "<div class='leform-progress leform-progress-" + leform_form_options["progress-position"] + "' id='leform-progress-" + page_id + "'><div class='leform-progress-t1" + (leform_form_options["progress-striped"] == "on" ? " leform-progress-stripes" : "") + "'><div><div style='width:" + width + "%'>" + width + "%</div></div>" + (leform_form_options["progress-label-enable"] == "on" ? "<label>" + leform_escape_html(page_name) + "</label>" : "") + "</div></div>";
		}
		if (leform_form_options["progress-position"] == "outside") {
			jQuery(".leform-builder").prepend(html);
		} else {
			jQuery("#leform-form-" + page_id).prepend(html);
		}
		idx++;
	});

	if (leform_form_options["progress-position"] == "outside") {
		if (leform_form_page_active != null) {
			jQuery("#leform-progress-" + leform_form_page_active).show();
		}
	}
	return;
}

function leform_build() {
	var adminbar_height;
	if (jQuery("#wpadminbar").length > 0) adminbar_height = parseInt(jQuery("#wpadminbar").height(), 10);
	else adminbar_height = 0;
	var text_style, style_attr, style = "";
	var webfonts = new Array();
	jQuery(".leform-form").html("");
	jQuery(".leform-form").attr("class", jQuery(".leform-form").attr("class").replace(/\bleform-form-input-[a-z]+\b/g, ""));
	jQuery(".leform-form").addClass("leform-form-input-" + leform_form_options["input-size"]);
	jQuery(".leform-form").attr("class", jQuery(".leform-form").attr("class").replace(/\bleform-form-icon-[a-z]+\b/g, ""));
	jQuery(".leform-form").addClass("leform-form-icon-" + leform_form_options["input-icon-position"]);
	jQuery(".leform-form").attr("class", jQuery(".leform-form").attr("class").replace(/\bleform-form-description-[a-z]+\b/g, ""));
	jQuery(".leform-form").addClass("leform-form-description-" + leform_form_options["description-style-position"]);

	if (leform_form_options["progress-enable"] == "on") {
		if (leform_form_options["progress-type"] == 'progress-2') {
			if (leform_form_options.hasOwnProperty("progress-color-color1") && leform_form_options['progress-color-color1'] != "") style += "ul.leform-progress-t2,ul.leform-progress-t2>li>span{background-color:" + leform_form_options['progress-color-color1'] + ";}ul.leform-progress-t2>li>label{color:" + leform_form_options['progress-color-color1'] + ";}";
			if (leform_form_options.hasOwnProperty("progress-color-color2") && leform_form_options['progress-color-color2'] != "") style += "ul.leform-progress-t2>li.leform-progress-t2-active>span,ul.leform-progress-t2>li.leform-progress-t2-passed>span{background-color:" + leform_form_options['progress-color-color2'] + ";}";
			if (leform_form_options.hasOwnProperty("progress-color-color3") && leform_form_options['progress-color-color3'] != "") style += "ul.leform-progress-t2>li>span{color:" + leform_form_options['progress-color-color3'] + ";}";
			if (leform_form_options.hasOwnProperty("progress-color-color4") && leform_form_options['progress-color-color4'] != "") style += "ul.leform-progress-t2>li.leform-progress-t2-active>label{color:" + leform_form_options['progress-color-color4'] + ";}";
		} else {
			if (leform_form_options.hasOwnProperty("progress-color-color1") && leform_form_options['progress-color-color1'] != "") style += "div.leform-progress-t1>div{background-color:" + leform_form_options['progress-color-color1'] + ";}";
			if (leform_form_options.hasOwnProperty("progress-color-color2") && leform_form_options['progress-color-color2'] != "") style += "div.leform-progress-t1>div>div{background-color:" + leform_form_options['progress-color-color2'] + ";}";
			if (leform_form_options.hasOwnProperty("progress-color-color3") && leform_form_options['progress-color-color3'] != "") style += "div.leform-progress-t1>div>div{color:" + leform_form_options['progress-color-color3'] + ";}";
			if (leform_form_options.hasOwnProperty("progress-color-color4") && leform_form_options['progress-color-color4'] != "") style += "div.leform-progress-t1>label{color:" + leform_form_options['progress-color-color4'] + ";}";
		}
		style += ".leform-progress{max-width:" + leform_form_options["max-width-value"] + leform_form_options["max-width-unit"] + ";}";
	}

	text_style = leform_build_style_text(leform_form_options, "text-style");
	if (text_style["webfont"] != "" && webfonts.indexOf(text_style["webfont"]) == -1) webfonts.push(text_style["webfont"]);
	style_attr = text_style["style"];
	style += ".leform-form *, .leform-progress {" + style_attr + "}";
	style_attr += leform_build_style_background(leform_form_options, "inline-background-style");
	style_attr += leform_build_style_border(leform_form_options, "inline-border-style");
	style_attr += leform_build_shadow(leform_form_options, "inline-shadow");
	style_attr += leform_build_style_padding(leform_form_options, "inline-padding");
	style_attr += "max-width:" + leform_form_options["max-width-value"] + leform_form_options["max-width-unit"] + ";";
	style += ".leform-form{" + style_attr + "}";

	text_style = leform_build_style_text(leform_form_options, "label-text-style");
	if (text_style["webfont"] != "" && webfonts.indexOf(text_style["webfont"]) == -1) webfonts.push(text_style["webfont"]);
	style_attr = text_style["style"];
	style += ".leform-element label.leform-label, .leform-element label.leform-label .leform-required-symbol {" + style_attr + "}";
	text_style = leform_build_style_text(leform_form_options, "description-text-style");
	if (text_style["webfont"] != "" && webfonts.indexOf(text_style["webfont"]) == -1) webfonts.push(text_style["webfont"]);
	style_attr = text_style["style"];
	style += ".leform-element label.leform-description, .leform-element label.leform-description .leform-required-symbol{" + style_attr + "}";

	text_style = leform_build_style_text(leform_form_options, "required-text-style");
	if (text_style["webfont"] != "" && webfonts.indexOf(text_style["webfont"]) == -1) webfonts.push(text_style["webfont"]);
	style_attr = text_style["style"];
	style += ".leform-element label.leform-label span.leform-required-symbol, .leform-element label.leform-description span.leform-required-symbol {" + style_attr + "}";

	text_style = leform_build_style_text(leform_form_options, "input-text-style");
	if (text_style["webfont"] != "" && webfonts.indexOf(text_style["webfont"]) == -1) webfonts.push(text_style["webfont"]);
	style_attr = text_style["style"];
	style += ".leform-element div.leform-input div.leform-signature-box span i{" + style_attr + "}";
	style_attr += leform_build_style_background(leform_form_options, "input-background-style");
	style_attr += leform_build_style_border(leform_form_options, "input-border-style");
	style_attr += leform_build_shadow(leform_form_options, "input-shadow");
	style += ".leform-element div.leform-input div.leform-signature-box,.leform-element div.leform-input div.leform-signature-box,.leform-element div.leform-input div.leform-multiselect,.leform-element div.leform-input input[type='text'],.leform-element div.leform-input input[type='email'],.leform-element div.leform-input input[type='password'],.leform-element div.leform-input select,.leform-element div.leform-input select option,.leform-element div.leform-input textarea{" + style_attr + "}";
	style += ".leform-element div.leform-input ::placeholder{color:"
		+ ((leform_form_options["input-placeholder-color"])
			? leform_form_options["input-placeholder-color"]
			: leform_form_options["input-text-style-color"]
		) +
		"; opacity: 0.9;}";
	style += ".leform-element div.leform-input div.leform-multiselect::-webkit-scrollbar-thumb{background-color:" + leform_form_options["input-border-style-color"] + ";}"
	if (leform_form_options["input-hover-inherit"] == "off") {
		text_style = leform_build_style_text(leform_form_options, "input-hover-text-style");
		if (text_style["webfont"] != "" && webfonts.indexOf(text_style["webfont"]) == -1) webfonts.push(text_style["webfont"]);
		style_attr = text_style["style"];
		style_attr += leform_build_style_background(leform_form_options, "input-hover-background-style");
		style_attr += leform_build_style_border(leform_form_options, "input-hover-border-style");
		style_attr += leform_build_shadow(leform_form_options, "input-hover-shadow");
		style += ".leform-element div.leform-input input[type='text']:hover,.leform-element div.leform-input input[type='email']:hover,.leform-element div.leform-input input[type='password']:hover,.leform-element div.leform-input select:hover,.leform-element div.leform-input select:hover option,.leform-element div.leform-input textarea:hover{" + style_attr + "}";
	}
	if (leform_form_options["input-focus-inherit"] == "off") {
		text_style = leform_build_style_text(leform_form_options, "input-focus-text-style");
		if (text_style["webfont"] != "" && webfonts.indexOf(text_style["webfont"]) == -1) webfonts.push(text_style["webfont"]);
		style_attr = text_style["style"];
		style_attr += leform_build_style_background(leform_form_options, "input-focus-background-style");
		style_attr += leform_build_style_border(leform_form_options, "input-focus-border-style");
		style_attr += leform_build_shadow(leform_form_options, "input-focus-shadow");
		style += ".leform-element div.leform-input input[type='text']:focus,.leform-element div.leform-input input[type='email']:focus,.leform-element div.leform-input input[type='password']:focus,.leform-element div.leform-input select:focus,.leform-element div.leform-input select:focus option,.leform-element div.leform-input textarea:focus{" + style_attr + "}";
	}

	style_attr = leform_build_style_border(leform_form_options, "imageselect-border-style");
	style_attr += leform_build_shadow(leform_form_options, "imageselect-shadow");
	style += ".leform-element div.leform-input .leform-imageselect+label{" + style_attr + "}";
	if (leform_form_options["imageselect-hover-inherit"] == "off") {
		style_attr = leform_build_style_border(leform_form_options, "imageselect-hover-border-style");
		style_attr += leform_build_shadow(leform_form_options, "imageselect-hover-shadow");
		style += ".leform-element div.leform-input .leform-imageselect+label:hover{" + style_attr + "}";
	}
	if (leform_form_options["imageselect-selected-inherit"] == "off") {
		style_attr = leform_build_style_border(leform_form_options, "imageselect-selected-border-style");
		style_attr += leform_build_shadow(leform_form_options, "imageselect-selected-shadow");
		style += ".leform-element div.leform-input .leform-imageselect:checked+label{" + style_attr + "}";
	}
	text_style = leform_build_style_text(leform_form_options, "imageselect-text-style");
	if (text_style["webfont"] != "" && webfonts.indexOf(text_style["webfont"]) == -1) webfonts.push(text_style["webfont"]);
	style += ".leform-element div.leform-input .leform-imageselect+label span.leform-imageselect-label{" + text_style["style"] + "}";

	style_attr = "";
	if (leform_form_options["input-icon-size"] != "") {
		style_attr += "font-size:" + leform_form_options["input-icon-size"] + "px;";
	}
	if (leform_form_options["input-icon-color"] != "") {
		style_attr += "color:" + leform_form_options["input-icon-color"] + ";";
	}
	if (leform_form_options["input-icon-position"] != "outside") {
		if (leform_form_options["input-icon-background"] != "") {
			style_attr += "background:" + leform_form_options["input-icon-background"] + ";";
		}
		if (leform_form_options["input-icon-border"] != "") {
			style_attr += "border-color:" + leform_form_options["input-icon-border"] + ";border-style:solid;";
			if (leform_form_options.hasOwnProperty("input-border-style-width")) {
				integer = parseInt(leform_form_options["input-border-style-width"], 10);
				if (integer >= 0 && integer <= 16) style_attr += "border-width:" + integer + "px;";
			}
		}
		if (leform_form_options.hasOwnProperty("input-border-style-radius")) {
			var integer = parseInt(leform_form_options["input-border-style-radius"], 10);
			if (integer >= 0 && integer <= 100) style_attr += "border-radius:" + integer + "px;";
		}
		if (leform_form_options["input-icon-background"] != "" || leform_form_options["input-icon-border"] != "") {
			style += "div.leform-input.leform-icon-left input[type='text'], div.leform-input.leform-icon-left input[type='email'],div.leform-input.leform-icon-left input[type='password'],div.leform-input.leform-icon-left textarea {padding-left: 56px !important;}";
			style += "div.leform-input.leform-icon-right input[type='text'], div.leform-input.leform-icon-right input[type='email'],div.leform-input.leform-icon-right input[type='password'],div.leform-input.leform-icon-right textarea {padding-right: 56px !important;}";
		}
	}
	if (style_attr != "") {
		style += "div.leform-input>i.leform-icon-left, div.leform-input>i.leform-icon-right {" + style_attr + "}";
	}

	text_style = leform_build_style_text(leform_form_options, "button-text-style");
	if (text_style["webfont"] != "" && webfonts.indexOf(text_style["webfont"]) == -1) webfonts.push(text_style["webfont"]);
	style_attr = text_style["style"];
	style_attr += leform_build_style_background(leform_form_options, "button-background-style");
	style_attr += leform_build_style_border(leform_form_options, "button-border-style");
	style_attr += leform_build_shadow(leform_form_options, "button-shadow");
	style += ".leform-element .leform-button{" + style_attr + "}";
	if (leform_form_options["button-hover-inherit"] == "off") {
		text_style = leform_build_style_text(leform_form_options, "button-hover-text-style");
		if (text_style["webfont"] != "" && webfonts.indexOf(text_style["webfont"]) == -1) webfonts.push(text_style["webfont"]);
		style_attr = text_style["style"];
		style_attr += leform_build_style_background(leform_form_options, "button-hover-background-style");
		style_attr += leform_build_style_border(leform_form_options, "button-hover-border-style");
		style_attr += leform_build_shadow(leform_form_options, "button-hover-shadow");
		style += ".leform-element .leform-button:hover,.leform-element .leform-button:focus{" + style_attr + "}";
	}
	if (leform_form_options["button-active-inherit"] == "off") {
		text_style = leform_build_style_text(leform_form_options, "button-active-text-style");
		if (text_style["webfont"] != "" && webfonts.indexOf(text_style["webfont"]) == -1) webfonts.push(text_style["webfont"]);
		style_attr = text_style["style"];
		style_attr += leform_build_style_background(leform_form_options, "button-active-background-style");
		style_attr += leform_build_style_border(leform_form_options, "button-active-border-style");
		style_attr += leform_build_shadow(leform_form_options, "button-active-shadow");
		style += ".leform-element .leform-button:active{" + style_attr + "}";
	}

	text_style = leform_build_style_text(leform_form_options, "tile-text-style");
	if (text_style["webfont"] != "" && webfonts.indexOf(text_style["webfont"]) == -1) webfonts.push(text_style["webfont"]);
	style_attr = text_style["style"];
	style_attr += leform_build_style_background(leform_form_options, "tile-background-style");
	style_attr += leform_build_style_border(leform_form_options, "tile-border-style");
	style_attr += leform_build_shadow(leform_form_options, "tile-shadow");
	style += ".leform-element input[type='checkbox'].leform-tile+label,.leform-element input[type='radio'].leform-tile+label{" + style_attr + "}";
	if (leform_form_options["tile-hover-inherit"] == "off") {
		text_style = leform_build_style_text(leform_form_options, "tile-hover-text-style");
		if (text_style["webfont"] != "" && webfonts.indexOf(text_style["webfont"]) == -1) webfonts.push(text_style["webfont"]);
		style_attr = text_style["style"];
		style_attr += leform_build_style_background(leform_form_options, "tile-hover-background-style");
		style_attr += leform_build_style_border(leform_form_options, "tile-hover-border-style");
		style_attr += leform_build_shadow(leform_form_options, "tile-hover-shadow");
		style += ".leform-element input[type='checkbox'].leform-tile+label:hover,.leform-element input[type='radio'].leform-tile+label:hover{" + style_attr + "}";
	}
	if (leform_form_options["tile-selected-inherit"] == "off") {
		text_style = leform_build_style_text(leform_form_options, "tile-selected-text-style");
		if (text_style["webfont"] != "" && webfonts.indexOf(text_style["webfont"]) == -1) webfonts.push(text_style["webfont"]);
		style_attr = text_style["style"];
		style_attr += leform_build_style_background(leform_form_options, "tile-selected-background-style");
		style_attr += leform_build_style_border(leform_form_options, "tile-selected-border-style");
		style_attr += leform_build_shadow(leform_form_options, "tile-selected-shadow");
		style += ".leform-element input[type='checkbox'].leform-tile:checked+label,.leform-element input[type='radio'].leform-tile:checked+label{" + style_attr + "}";
	}

	style_attr = "";

	if (
		leform_form_options.hasOwnProperty("checkbox-radio-unchecked-color-color2")
		&& leform_form_options["checkbox-radio-unchecked-color-color2"] != ""
	) {
		style_attr += "background-color:" + leform_form_options["checkbox-radio-unchecked-color-color2"] + ";";
	} else {
		style_attr += "background-color:transparent;";
	}
	style += ".leform-element div.leform-input input[type='checkbox'].leform-checkbox-tgl:checked+label:after{" + style_attr + "}";

	if (
		leform_form_options.hasOwnProperty("checkbox-radio-unchecked-color-color1")
		&& leform_form_options["checkbox-radio-unchecked-color-color1"] != ""
	) {
		style_attr += "border-color:" + leform_form_options["checkbox-radio-unchecked-color-color1"] + ";";
	} else {
		style_attr += "border-color:transparent;";
	}

	if (
		leform_form_options.hasOwnProperty("checkbox-radio-unchecked-color-color3")
		&& leform_form_options["checkbox-radio-unchecked-color-color3"] != ""
	) {
		style_attr += "color:" + leform_form_options["checkbox-radio-unchecked-color-color3"] + ";";
	} else {
		style_attr += "color:#ccc;";
	}
	style += ".leform-element div.leform-input input[type='checkbox'].leform-checkbox-classic+label,.leform-element div.leform-input input[type='checkbox'].leform-checkbox-fa-check+label,.leform-element div.leform-input input[type='checkbox'].leform-checkbox-square+label,.leform-element div.leform-input input[type='checkbox'].leform-checkbox-tgl+label{" + style_attr + "}";

	style_attr = "";
	if (leform_form_options.hasOwnProperty("checkbox-radio-unchecked-color-color3") && leform_form_options["checkbox-radio-unchecked-color-color3"] != "") style_attr += "background-color:" + leform_form_options["checkbox-radio-unchecked-color-color3"] + ";";
	else style_attr += "background-color:#ccc;";
	style += ".leform-element div.leform-input input[type='checkbox'].leform-checkbox-square:checked+label:after{" + style_attr + "}";
	style += ".leform-element div.leform-input input[type='checkbox'].leform-checkbox-tgl:checked+label,.leform-element div.leform-input input[type='checkbox'].leform-checkbox-tgl+label:after{" + style_attr + "}";
	if (leform_form_options["checkbox-radio-checked-inherit"] == "off") {
		style_attr = "";
		if (leform_form_options.hasOwnProperty("checkbox-radio-checked-color-color2") && leform_form_options["checkbox-radio-checked-color-color2"] != "") style_attr += "background-color:" + leform_form_options["checkbox-radio-checked-color-color2"] + ";";
		else style_attr += "background-color:transparent;";
		if (leform_form_options.hasOwnProperty("checkbox-radio-checked-color-color1") && leform_form_options["checkbox-radio-checked-color-color1"] != "") style_attr += "border-color:" + leform_form_options["checkbox-radio-checked-color-color1"] + ";";
		else style_attr += "border-color:transparent;";
		if (leform_form_options.hasOwnProperty("checkbox-radio-checked-color-color3") && leform_form_options["checkbox-radio-checked-color-color3"] != "") style_attr += "color:" + leform_form_options["checkbox-radio-checked-color-color3"] + ";";
		else style_attr += "color:#ccc;";
		style += ".leform-element div.leform-input input[type='checkbox'].leform-checkbox-classic:checked+label,.leform-element div.leform-input input[type='checkbox'].leform-checkbox-fa-check:checked+label,.leform-element div.leform-input input[type='checkbox'].leform-checkbox-square:checked+label,.leform-element div.leform-input input[type='checkbox'].leform-checkbox-tgl:checked+label{" + style_attr + "}";
		style_attr = "";
		if (leform_form_options.hasOwnProperty("checkbox-radio-checked-color-color3") && leform_form_options["checkbox-radio-checked-color-color3"] != "") style_attr += "background-color:" + leform_form_options["checkbox-radio-checked-color-color3"] + ";";
		else style_attr += "background-color:#ccc;";
		style += ".leform-element div.leform-input input[type='checkbox'].leform-checkbox-square:checked+label:after{" + style_attr + "}";
		style += ".leform-element div.leform-input input[type='checkbox'].leform-checkbox-tgl:checked+label:after{" + style_attr + "}";
	}

	style_attr = "";
	if (leform_form_options.hasOwnProperty("checkbox-radio-unchecked-color-color2") && leform_form_options["checkbox-radio-unchecked-color-color2"] != "") style_attr += "background-color:" + leform_form_options["checkbox-radio-unchecked-color-color2"] + ";";
	else style_attr += "background-color:transparent;";
	if (leform_form_options.hasOwnProperty("checkbox-radio-unchecked-color-color1") && leform_form_options["checkbox-radio-unchecked-color-color1"] != "") style_attr += "border-color:" + leform_form_options["checkbox-radio-unchecked-color-color1"] + ";";
	else style_attr += "border-color:transparent;";
	if (leform_form_options.hasOwnProperty("checkbox-radio-unchecked-color-color3") && leform_form_options["checkbox-radio-unchecked-color-color3"] != "") style_attr += "color:" + leform_form_options["checkbox-radio-unchecked-color-color3"] + ";";
	else style_attr += "color:#ccc;";
	style += ".leform-element div.leform-input input[type='radio'].leform-radio-classic+label,.leform-element div.leform-input input[type='radio'].leform-radio-fa-check+label,.leform-element div.leform-input input[type='radio'].leform-radio-dot+label{" + style_attr + "}";
	style_attr = "";
	if (leform_form_options.hasOwnProperty("checkbox-radio-unchecked-color-color3") && leform_form_options["checkbox-radio-unchecked-color-color3"] != "") style_attr += "background-color:" + leform_form_options["checkbox-radio-unchecked-color-color3"] + ";";
	else style_attr += "background-color:#ccc;";
	style += ".leform-element div.leform-input input[type='radio'].leform-radio-dot:checked+label:after{" + style_attr + "}";
	if (leform_form_options["checkbox-radio-checked-inherit"] == "off") {
		style_attr = "";
		if (leform_form_options.hasOwnProperty("checkbox-radio-checked-color-color2") && leform_form_options["checkbox-radio-checked-color-color2"] != "") style_attr += "background-color:" + leform_form_options["checkbox-radio-checked-color-color2"] + ";";
		else style_attr += "background-color:transparent;";
		if (leform_form_options.hasOwnProperty("checkbox-radio-checked-color-color1") && leform_form_options["checkbox-radio-checked-color-color1"] != "") style_attr += "border-color:" + leform_form_options["checkbox-radio-checked-color-color1"] + ";";
		else style_attr += "border-color:transparent;";
		if (leform_form_options.hasOwnProperty("checkbox-radio-checked-color-color3") && leform_form_options["checkbox-radio-checked-color-color3"] != "") style_attr += "color:" + leform_form_options["checkbox-radio-checked-color-color3"] + ";";
		else style_attr += "color:#ccc;";
		style += ".leform-element div.leform-input input[type='radio'].leform-radio-classic:checked+label,.leform-element div.leform-input input[type='radio'].leform-radio-fa-check:checked+label,.leform-element div.leform-input input[type='radio'].leform-radio-dot:checked+label{" + style_attr + "}";
		style_attr = "";
		if (leform_form_options.hasOwnProperty("checkbox-radio-checked-color-color3") && leform_form_options["checkbox-radio-checked-color-color3"] != "") style_attr += "background-color:" + leform_form_options["checkbox-radio-checked-color-color3"] + ";";
		else style_attr += "background-color:#ccc;";
		style += ".leform-element div.leform-input input[type='radio'].leform-radio-dot:checked+label:after{" + style_attr + "}";
	}

	style_attr = "";
	if (leform_form_options.hasOwnProperty("multiselect-style-hover-background") && leform_form_options["multiselect-style-hover-background"] != "") style_attr += "background-color:" + leform_form_options['multiselect-style-hover-background'] + ";";
	if (leform_form_options.hasOwnProperty("multiselect-style-hover-color") && leform_form_options["multiselect-style-hover-color"] != "") style_attr += "color:" + leform_form_options['multiselect-style-hover-color'] + ";";
	if (style_attr != "") style += ".leform-element div.leform-input div.leform-multiselect>input[type='checkbox']+label:hover{" + style_attr + "}";
	style_attr = "";
	if (leform_form_options.hasOwnProperty("multiselect-style-selected-background") && leform_form_options["multiselect-style-selected-background"] != "") style_attr += "background-color:" + leform_form_options['multiselect-style-selected-background'] + ";";
	if (leform_form_options.hasOwnProperty("multiselect-style-selected-color") && leform_form_options["multiselect-style-selected-color"] != "") style_attr += "color:" + leform_form_options['multiselect-style-selected-color'] + ";";
	if (style_attr != "") style += ".leform-element div.leform-input div.leform-multiselect>input[type='checkbox']:checked+label{" + style_attr + "}";
	if (leform_form_options.hasOwnProperty("multiselect-style-height") && leform_form_options["multiselect-style-height"] != "") style += ".leform-element div.leform-input div.leform-multiselect{height:" + parseInt(leform_form_options['multiselect-style-height'], 10) + "px;}";

	if (typeof jQuery.fn.ionRangeSlider != typeof undefined && jQuery.fn.ionRangeSlider) {
		if (leform_form_options.hasOwnProperty("rangeslider-color-color1") && leform_form_options["rangeslider-color-color1"] != "") style += ".leform-element div.leform-input.leform-rangeslider .irs-line, .leform-element div.leform-input.leform-rangeslider .irs-min, .leform-element div.leform-input.leform-rangeslider .irs-max, .leform-element div.leform-input.leform-rangeslider .irs-grid-pol{background-color:" + leform_form_options["rangeslider-color-color1"] + " !important;}";
		if (leform_form_options.hasOwnProperty("rangeslider-color-color2") && leform_form_options["rangeslider-color-color2"] != "") style += ".leform-element div.leform-input.leform-rangeslider .irs-grid-text, .leform-element div.leform-input.leform-rangeslider .irs-min, .leform-element div.leform-input.leform-rangeslider .irs-max{color:" + leform_form_options["rangeslider-color-color2"] + " !important;}";
		if (leform_form_options.hasOwnProperty("rangeslider-color-color3") && leform_form_options["rangeslider-color-color3"] != "") style += ".leform-element div.leform-input.leform-rangeslider .irs-bar{background-color:" + leform_form_options["rangeslider-color-color3"] + " !important;}";
		if (leform_form_options.hasOwnProperty("rangeslider-color-color4") && leform_form_options["rangeslider-color-color4"] != "") {
			style += ".leform-element div.leform-input.leform-rangeslider .irs-single, .leform-element div.leform-input.leform-rangeslider .irs-from, .leform-element div.leform-input.leform-rangeslider .irs-to{background-color:" + leform_form_options["rangeslider-color-color4"] + " !important;}";
			style += ".leform-element div.leform-input.leform-rangeslider .irs-single:before, .leform-element div.leform-input.leform-rangeslider .irs-from:before, .leform-element div.leform-input.leform-rangeslider .irs-to:before{border-top-color:" + leform_form_options["rangeslider-color-color4"] + " !important;}";
			switch (leform_form_options["rangeslider-skin"]) {
				case 'sharp':
					style += ".leform-element div.leform-input.leform-rangeslider .irs--sharp .irs-handle, .leform-element div.leform-input.leform-rangeslider .irs--sharp .irs-handle:hover, .leform-element div.leform-input.leform-rangeslider .irs--sharp .irs-handle.state_hover{background-color:" + leform_form_options["rangeslider-color-color4"] + " !important;}";
					style += ".leform-element div.leform-input.leform-rangeslider .irs--sharp .irs-handle > i:first-child, .leform-element div.leform-input.leform-rangeslider .irs--sharp .irs-handle:hover > i:first-child, .leform-element div.leform-input.leform-rangeslider .irs--sharp .irs-handle.state_hover > i:first-child{border-top-color:" + leform_form_options["rangeslider-color-color4"] + " !important;}";
					break;
				case 'round':
					style += ".leform-element div.leform-input.leform-rangeslider .irs--round .irs-handle, .leform-element div.leform-input.leform-rangeslider .irs--round .irs-handle:hover, .leform-element div.leform-input.leform-rangeslider .irs--round .irs-handle.state_hover{border-color:" + leform_form_options["rangeslider-color-color4"] + " !important;}";
					break;
				default:
					style += ".leform-element div.leform-input.leform-rangeslider .irs--flat .irs-handle > i:first-child, .leform-element div.leform-input.leform-rangeslider .irs--flat .irs-handle:hover > i:first-child, .leform-element div.leform-input.leform-rangeslider .irs--flat .irs-handle.state_hover > i:first-child{background-color:" + leform_form_options["rangeslider-color-color4"] + " !important;}";
					break;
			}
		}
		if (leform_form_options.hasOwnProperty("rangeslider-color-color5") && leform_form_options["rangeslider-color-color5"] != "") {
			style += ".leform-element div.leform-input.leform-rangeslider .irs-single, .leform-element div.leform-input.leform-rangeslider .irs-from, .leform-element div.leform-input.leform-rangeslider .irs-to{color:" + leform_form_options["rangeslider-color-color5"] + " !important;}";
			if (leform_form_options["rangeslider-skin"] == "round") {
				style += ".leform-element div.leform-input.leform-rangeslider .irs--round .irs-handle, .leform-element div.leform-input.leform-rangeslider .irs--round .irs-handle:hover, .leform-element div.leform-input.leform-rangeslider .irs--round .irs-handle.state_hover{background-color:" + leform_form_options["rangeslider-color-color5"] + " !important;}";
			}
		}
	}

	text_style = "";
	webfonts = webfonts.filter(f => f)
		.map(f => f[0])
		.filter(f => f);
	for (var i = 0; i < webfonts.length; i++) {
		text_style += "<link href='//fonts.googleapis.com/css?family="
			+ webfonts[i].replace(" ", "+")
			+ ":100,200,300,400,500,600,700,800,900&subset=arabic,vietnamese,hebrew,thai,bengali,latin,latin-ext,cyrillic,cyrillic-ext,greek' rel='stylesheet' type='text/css'>";
	}
	jQuery(".leform-form-global-style").html(text_style + "<style>" + style + "</style>");

	var output;
	for (var i = 0; i < leform_form_pages.length; i++) {
		if (leform_form_pages[i] != null) {
			output = _leform_build_children(leform_form_pages[i]['id'], 0);
			jQuery("#leform-form-" + leform_form_pages[i]['id']).append("<style>" + output["style"] + "</style>" + output["html"]);
			output = _leform_build_hidden_list(leform_form_pages[i]['id']);
			jQuery("#leform-form-" + leform_form_pages[i]['id']).append(output);
		}
	}


	// CUSTOM
	runAfterChildrenAreBuild();
	// ENDCUSTOM

	jQuery(".leform-form").each(function () {
		var id = jQuery(this).attr("id");
		jQuery("#" + id + " .leform-elements, #" + id + ".leform-elements").sortable({
			connectWith: "#" + id + " .leform-elements, #" + id + ".leform-elements",
			items: ".leform-element",
			forcePlaceholderSize: true,
			dropOnEmpty: true,
			placeholder: "leform-element-placeholder",
			start: function (event, ui) {
				jQuery(ui.helper).addClass("leform-element-helper");
				jQuery(".leform-context-menu").hide();
			},
			stop: function (event, ui) {
				jQuery(".leform-element-helper").removeClass("leform-element-helper");
				_leform_sync_elements();
			}
		});
	});
	_leform_sync_elements();
	leform_build_progress();
	if (typeof jQuery.fn.ionRangeSlider != typeof undefined && jQuery.fn.ionRangeSlider) jQuery("input.leform-rangeslider").ionRangeSlider();
	jQuery(".leform-element, .leform-hidden-element").on("contextmenu", function (e) {
		e.preventDefault();
		jQuery(".leform-context-menu").hide();
		leform_context_menu_object = this;
		jQuery(".leform-context-menu").css({ "top": (e.pageY - adminbar_height), "left": e.pageX });
		jQuery(".leform-context-menu-multi-page").remove();
		var li_duplicate_pages = new Array();
		var li_move_pages = new Array();
		for (var i = 0; i < leform_form_pages.length; i++) {
			if (leform_form_pages[i] != null && leform_form_pages[i]['id'] != "confirmation" && leform_form_pages[i]['id'] != leform_form_page_active) {
				li_duplicate_pages.push("<li><a href='#' onclick='return leform_element_duplicate(leform_context_menu_object, " + i + ");'>" + leform_escape_html(leform_form_pages[i]["name"]) + "</a></li>");
				li_move_pages.push("<li><a href='#' onclick='return leform_element_move(leform_context_menu_object, " + i + ");'>" + leform_escape_html(leform_form_pages[i]["name"]) + "</a></li>");
			}
		}
		if (li_duplicate_pages.length > 0) {
			jQuery(".leform-context-menu-last").after("<li class='leform-context-menu-multi-page'><a href='#' onclick='return false;'><i class='fas fa-caret-right'></i><i class='far fa-copy'></i>"
				+ leform_esc_html__("Duplicate to")
				+ "</a><ul>" + li_duplicate_pages.join("") + "</ul></li><li class='leform-context-menu-multi-page'><a href='#' onclick='return false;'><i class='fas fa-caret-right'></i><i class='far fa-arrow-alt-circle-right'></i>"
				+ leform_esc_html__("Move to")
				+ "</a><ul>" + li_move_pages.join("") + "</ul></li>");
		}
		jQuery(".leform-context-menu").show();
		return false;
	});
}
function leform_log_resize() {
	if (leform_record_active) {
		var popup_height = 2 * parseInt((jQuery(window).height() - 100) / 2, 10);
		var popup_width = Math.min(Math.max(2 * parseInt((jQuery(window).width() - 300) / 2, 10), 640), 1080);
		jQuery("#leform-record-details").height(popup_height);
		jQuery("#leform-record-details").width(popup_width);
		jQuery("#leform-record-details .leform-admin-popup-inner").height(popup_height);
		jQuery("#leform-record-details .leform-admin-popup-content").height(popup_height - 52);
	}
}
function leform_log_ready() {
	leform_log_resize();
	jQuery(window).resize(function () {
		leform_log_resize();
	});
}
function leform_forms_resize() {
	if (leform_more_active) {
		var popup_height = 2 * parseInt((jQuery(window).height() - 100) / 2, 10);
		var popup_width = Math.min(Math.max(2 * parseInt((jQuery(window).width() - 300) / 2, 10), 640), 840);
		jQuery("#leform-more-using").height(popup_height);
		jQuery("#leform-more-using").width(popup_width);
		jQuery("#leform-more-using .leform-admin-popup-inner").height(popup_height);
		jQuery("#leform-more-using .leform-admin-popup-content").height(popup_height - 52);
	}
}
function leform_forms_ready() {
	jQuery("span[title]").tooltipster({
		contentAsHTML: true,
		maxWidth: 360,
		theme: "tooltipster-dark",
		side: "bottom"
	});
	leform_forms_resize();
	jQuery(window).resize(function () {
		leform_forms_resize();
	});
}
function leform_form_resize() {
	var window_height = jQuery(window).height();
	var adminbar_height = jQuery("#wpadminbar").height();
	if (!leform_is_numeric(adminbar_height)) adminbar_height = 0;
	var toolbar_height = jQuery(".leform-toolbar").height();
	var top_padding = 20;
	var header_height = jQuery(".leform-header").height();
	//var builder_height = parseInt(window_height, 10) - parseInt(adminbar_height, 10) - parseInt(header_height, 10) - parseInt(toolbar_height, 10) - parseInt(top_padding, 10);
	var builder_height = parseInt(window_height, 10);
	var toolbars_height = jQuery(".leform-toolbars").height();
	jQuery(".leform-builder").css({ "min-height": builder_height + "px", "padding-top": parseInt(toolbars_height + 20, 10) + "px" });
	jQuery(".leform-form").css({ "min-height": parseInt(builder_height - 20, 10) + "px" });
	var builder_width = jQuery(".leform-builder").outerWidth();
	jQuery(".leform-toolbars").css({ "width": builder_width + "px" });
	if (leform_element_properties_active) {
		var popup_height = 2 * parseInt((jQuery(window).height() - 100) / 2, 10);
		var popup_width = Math.min(Math.max(2 * parseInt((jQuery(window).width() - 300) / 2, 10), 880), 1080);
		jQuery("#leform-element-properties").height(popup_height);
		jQuery("#leform-element-properties").width(popup_width);
		jQuery("#leform-element-properties .leform-admin-popup-inner").height(popup_height);
		jQuery("#leform-element-properties .leform-admin-popup-content").height(popup_height - 104);
	}
	if (leform_bulk_options_object) {
		var popup_height = 2 * parseInt((jQuery(window).height() - 100) / 2, 10);
		var popup_width = Math.min(Math.max(2 * parseInt((jQuery(window).width() - 300) / 2, 10), 880), 1080);
		jQuery("#leform-bulk-options").height(popup_height);
		jQuery("#leform-bulk-options").width(popup_width);
		jQuery("#leform-bulk-options .leform-admin-popup-inner").height(popup_height);
		jQuery("#leform-bulk-options .leform-admin-popup-content").height(popup_height - 104);
	}
	if (leform_record_active) {
		var popup_height = 2 * parseInt((jQuery(window).height() - 100) / 2, 10);
		var popup_width = Math.min(Math.max(2 * parseInt((jQuery(window).width() - 300) / 2, 10), 640), 1080);
		jQuery("#leform-record-details").height(popup_height);
		jQuery("#leform-record-details").width(popup_width);
		jQuery("#leform-record-details .leform-admin-popup-inner").height(popup_height);
		jQuery("#leform-record-details .leform-admin-popup-content").height(popup_height - 52);
	}
	if (leform_more_active) {
		var popup_height = 2 * parseInt((jQuery(window).height() - 100) / 2, 10);
		var popup_width = Math.min(Math.max(2 * parseInt((jQuery(window).width() - 300) / 2, 10), 640), 840);
		jQuery("#leform-more-using").height(popup_height);
		jQuery("#leform-more-using").width(popup_width);
		jQuery("#leform-more-using .leform-admin-popup-inner").height(popup_height);
		jQuery("#leform-more-using .leform-admin-popup-content").height(popup_height - 52);
	}
	if (leform_preview_active) {
		var max_width = parseInt(jQuery("#leform-preview").attr("data-width"), 10);
		var popup_height = 2 * parseInt((jQuery(window).height() - 40) / 2, 10);
		var popup_width = Math.min(Math.max(2 * parseInt((jQuery(window).width() - 40) / 2, 10), 480), max_width);
		jQuery("#leform-preview").height(popup_height);
		jQuery("#leform-preview").width(popup_width);
		jQuery("#leform-preview .leform-admin-popup-inner").height(popup_height);
		jQuery("#leform-preview .leform-admin-popup-content").height(popup_height - 52);
		jQuery("#leform-preview-iframe").height(popup_height - 52);
	}
	if (leform_stylemanager_active) {
		var popup_height = 2 * parseInt((jQuery(window).height() - 100) / 2, 10);
		var popup_width = Math.min(Math.max(2 * parseInt((jQuery(window).width() - 300) / 2, 10), 640), 840);
		jQuery("#leform-stylemanager").height(popup_height);
		jQuery("#leform-stylemanager").width(popup_width);
		jQuery("#leform-stylemanager .leform-admin-popup-inner").height(popup_height);
		jQuery("#leform-stylemanager .leform-admin-popup-content").height(popup_height - 52);
	}
}
function leform_form_ready() {
	leform_form_resize();
	jQuery(window).resize(function () {
		leform_form_resize();
	});
	jQuery(window).scroll(function (e) {
		var position = jQuery(window).scrollTop();
		var adminbar_height = jQuery("#wpadminbar").height();
		if (!leform_is_numeric(adminbar_height)) adminbar_height = 0;
		var offset = jQuery(".leform-builder").offset().top - adminbar_height;
		if (position > offset) {
			jQuery("html").addClass("leform-toolbars-fixed");
		} else {
			jQuery("html").removeClass("leform-toolbars-fixed");
		}
	});

	for (var i = 0; i < leform_form_pages_raw.length; i++) {
		if (typeof leform_form_pages_raw[i] == 'object') {
			if (parseInt(leform_form_pages_raw[i]['id'], 10) > leform_form_last_id) leform_form_last_id = parseInt(leform_form_pages_raw[i]['id'], 10);
			leform_form_pages.push(leform_form_pages_raw[i]);
		}
	}

	if (leform_form_options.hasOwnProperty("math-expressions")) {
		for (var i = 0; i < leform_form_options["math-expressions"].length; i++) {
			if (typeof leform_form_options["math-expressions"][i] == 'object') {
				if (parseInt(leform_form_options["math-expressions"][i]['id'], 10) > leform_form_last_id) {
					leform_form_last_id = parseInt(leform_form_options["math-expressions"][i]['id'], 10);
				}
			}
		}
	}
	if (leform_form_options.hasOwnProperty("payment-gateways")) {
		for (var i = 0; i < leform_form_options["payment-gateways"].length; i++) {
			if (typeof leform_form_options["payment-gateways"][i] == 'object') {
				if (parseInt(leform_form_options["payment-gateways"][i]['id'], 10) > leform_form_last_id) {
					leform_form_last_id = parseInt(leform_form_options["payment-gateways"][i]['id'], 10);
				}
			}
		}
	}

	if (jQuery(".leform-pages-bar-item").length == 1) jQuery(".leform-pages-bar-item").find(".leform-pages-bar-item-delete").addClass("leform-pages-bar-item-delete-disabled");
	else jQuery(".leform-pages-bar-item").find(".leform-pages-bar-item-delete").removeClass("leform-pages-bar-item-delete-disabled");
	leform_pages_activate(jQuery(".leform-pages-bar-item").first().find("label"));

	var tmp;
	for (var i = 0; i < leform_form_elements_raw.length; i++) {
		tmp = JSON.parse(leform_form_elements_raw[i]);
		if (typeof tmp == 'object') {
			if (parseInt(tmp['id'], 10) > leform_form_last_id) leform_form_last_id = parseInt(tmp['id'], 10);
			leform_form_elements.push(tmp);
		}
	}
	jQuery(".leform-toolbar-list>li>a[title]").tooltipster({
		maxWidth: 360,
		theme: "tooltipster-dark leform-toolbar-tooltipster",
		side: "bottom"
	});

	jQuery(".leform-toolbar-list li a").on("click", function (e) {
		e.preventDefault();
		var type = jQuery(this).parent().attr("data-type");
		if (typeof type == undefined || type == "") return false;
		var columns = 1;
		if (leform_meta.hasOwnProperty(type)) {
			leform_form_last_id++;
			var element = { "type": type, "resize": "both", "height": "auto", "_parent": leform_form_page_active, "_parent-col": 0, "_seq": leform_form_last_id, "id": leform_form_last_id };
			if (type == "columns") {
				columns = parseInt(jQuery(this).parent().attr("data-option"), 10);
				if (columns != 2 && columns != 3 && columns != 4 && columns != 6) columns = 1;
				element['_cols'] = columns;
			}
			for (var key in leform_meta[type]) {
				if (leform_meta[type].hasOwnProperty(key)) {
					switch (leform_meta[type][key]['type']) {
						case 'column-width':
							for (var i = 0; i < columns; i++) {
								element[key + "-" + i] = parseInt(12 / columns, 10);
							}
							break;

						default:
							if (leform_meta[type][key].hasOwnProperty('value')) {
								if (typeof leform_meta[type][key]['value'] == 'object') {
									for (var option_key in leform_meta[type][key]['value']) {
										if (leform_meta[type][key]['value'].hasOwnProperty(option_key)) {
											element[key + "-" + option_key] = leform_meta[type][key]['value'][option_key];
										}
									}
								} else element[key] = leform_meta[type][key]['value'];
							} else if (leform_meta[type][key].hasOwnProperty('values')) element[key] = leform_meta[type][key]['values'];
							break;
					}
				}
			}
			leform_form_elements.push(element);
			leform_form_changed = true;
			leform_build();

            const newFieldIndex = leform_form_elements.findIndex((formElement) => formElement.id === element["id"]);
            const newElement = document.getElementById(`leform-element-${newFieldIndex}`);

            // this is problematic because the height of the image is found async
            const containsImages = (leform_form_elements.findIndex((formElement) => formElement.type === "background-image") !== -1);

            const callback = () => {
                if (isElementHidden(newElement)) {
                    newElement.scrollIntoView({ behavior: "smooth" });
                }
            }

            if (newElement) {
                if (containsImages) {
                    runWhenImagesLoaded(callback);
                } else {
                    callback();
                }
            }

			if (leform_gettingstarted_enable == "on" && leform_form_elements.length <= 2) leform_gettingstarted("element-properties", 0);
		}
	});
	jQuery("body").append('<div class="leform-context-menu"><ul><li><a href="#" onclick="return leform_properties_open(leform_context_menu_object);"><i class="fas fa-pencil-alt"></i>'
		+ leform_esc_html__("Properties")
		+ '</a></li><li class="leform-context-menu-last"><a href="#" onclick="return leform_element_duplicate(leform_context_menu_object);"><i class="far fa-copy"></i>'
		+ leform_esc_html__("Duplicate")
		+ '</a></li><li class="leform-context-menu-line"></li><li><a href="#" onclick="return leform_element_delete(leform_context_menu_object);"><i class="fas fa-trash-alt"></i>'
		+ leform_esc_html__("Delete")
		+ '</a></li></ul></div>');
	jQuery("body").on("click", function (e) {
		jQuery(".leform-context-menu").hide();
	});
	jQuery(".leform-fa-selector-header input").on("keyup", function (e) {
		var needle = jQuery(this).val().toLowerCase();
		if (needle == "") {
			jQuery(this).parent().parent().find(".leform-fa-selector-content span").show();
		} else {
			var icons = jQuery(this).parent().parent().find(".leform-fa-selector-content");
			jQuery(icons).find("span").each(function () {
				if (jQuery(this).attr("title").toLowerCase().indexOf(needle) === -1) jQuery(this).hide();
				else jQuery(this).show();
			});
		}
		return false;
	});
	jQuery(window).on('beforeunload', function (e) {
		if (leform_element_properties_data_changed || leform_form_changed) return 'Form changed!';
		return;
	});
	jQuery(".leform-pages-bar-items").sortable({
		items: "li.leform-pages-bar-item",
		containment: "parent",
		forcePlaceholderSize: true,
		dropOnEmpty: true,
		placeholder: "leform-pages-bar-item-placeholder",
		start: function (event, ui) {
			jQuery(ui.helper).addClass("leform-pages-bar-item-helper");
			jQuery(".leform-context-menu").hide();
		},
		stop: function (event, ui) {
			jQuery(".leform-pages-bar-item-helper").removeClass("leform-pages-bar-item-helper");
			leform_build_progress();
		}
	});
	jQuery(".leform-pages-bar-items, .leform-pages-bar-items-confirmation").disableSelection();
	jQuery(".leform-element").disableSelection();
	leform_build();
}

function leform_forms_status_toggle(_object) {
	if (leform_sending) return false;
	leform_sending = true;
	var form_id = jQuery(_object).attr("data-id");
	var form_status = jQuery(_object).attr("data-status");
	var form_status_label = jQuery(_object).closest("tr").find("td.column-active").html();
	var doing_label = jQuery(_object).attr("data-doing");
	var do_label = jQuery(_object).html();
	jQuery(_object).html("<i class='fas fa-spinner fa-spin'></i> " + doing_label);
	jQuery(_object).closest("tr").find(".row-actions").addClass("visible");
	jQuery(_object).closest("tr").find("td.column-active").html("<i class='fas fa-spinner fa-spin'></i>");
	var post_data = { "action": "leform-forms-status-toggle", "form-id": form_id, "form-status": form_status };
	jQuery.ajax({
		type: "POST",
		url: leform_ajax_handler,
		data: post_data,
		success: function (return_data) {
			jQuery(_object).html(do_label);
			try {
				var data;
				if (typeof return_data == 'object') data = return_data;
				else data = jQuery.parseJSON(return_data);
				if (data.status == "OK") {
					jQuery(_object).html(data.form_action);
					jQuery(_object).attr("data-status", data.form_status);
					jQuery(_object).attr("data-doing", data.form_action_doing);
					if (data.form_status == "active") jQuery(_object).closest("tr").find(".leform-table-list-badge-status").html("");
					else jQuery(_object).closest("tr").find(".leform-table-list-badge-status").html("<span class='leform-badge leform-badge-danger'>Inactive</span>");
					leform_global_message_show("success", data.message);
				} else if (data.status == "ERROR") {
					jQuery(_object).closest("tr").find("td.column-active").html(form_status_label);
					leform_global_message_show("danger", data.message);
				} else {
					jQuery(_object).closest("tr").find("td.column-active").html(form_status_label);
					leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
				}
			} catch (error) {
				jQuery(_object).closest("tr").find("td.column-active").html(form_status_label);
				leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
			}
			jQuery(_object).closest("tr").find(".row-actions").removeClass("visible");
			leform_sending = false;
		},
		error: function (XMLHttpRequest, textStatus, errorThrown) {
			jQuery(_object).closest("tr").find(".row-actions").removeClass("visible");
			jQuery(_object).html(do_label);
			jQuery(_object).closest("tr").find("td.column-active").html(form_status_label);
			leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
			leform_sending = false;
		}
	});
	return false;
}

function leform_forms_delete(_object) {
	leform_dialog_open({
		echo_html: function () {
			this.html("<div class='leform-dialog-message'>" + leform_esc_html__("Please confirm that you want to delete the form.", "leform") + "</div>");
			this.show();
		},
		ok_label: leform_esc_html__('Delete'),
		ok_function: function (e) {
			_leform_forms_delete(_object);
			leform_dialog_close();
		}
	});
	return false;
}

function _leform_forms_delete(_object) {
	if (leform_sending) return false;
	leform_sending = true;
	var form_id = jQuery(_object).attr("data-id");
	var doing_label = jQuery(_object).attr("data-doing");
	var do_label = jQuery(_object).html();
	jQuery(_object).html("<i class='fas fa-spinner fa-spin'></i> " + doing_label);
	jQuery(_object).closest("tr").find(".row-actions").addClass("visible");
	var post_data = { "action": "leform-forms-delete", "form-id": form_id };
	jQuery.ajax({
		type: "POST",
		url: leform_ajax_handler,
		data: post_data,
		success: function (return_data) {
			try {
				var data;
				if (typeof return_data == 'object') data = return_data;
				else data = jQuery.parseJSON(return_data);
				if (data.status == "OK") {
					jQuery(_object).closest("tr").fadeOut(300, function () {
						jQuery(_object).closest("tr").remove();
					});
					leform_global_message_show("success", data.message);
				} else if (data.status == "ERROR") {
					leform_global_message_show("danger", data.message);
				} else {
					leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
				}
			} catch (error) {
				leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
			}
			jQuery(_object).html(do_label);
			jQuery(_object).closest("tr").find(".row-actions").removeClass("visible");
			leform_sending = false;
		},
		error: function (XMLHttpRequest, textStatus, errorThrown) {
			jQuery(_object).closest("tr").find(".row-actions").removeClass("visible");
			jQuery(_object).html(do_label);
			leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
			leform_sending = false;
		}
	});
	return false;
}

function leform_folder_delete(_object) {
	leform_dialog_open({
		echo_html: function () {
			this.html("<div class='leform-dialog-message'>" + leform_esc_html__("Please confirm that you want to delete this folder.", "leform") + "</div>");
			this.show();
		},
		ok_label: leform_esc_html__('Delete'),
		ok_function: function (e) {
			_leform_folder_delete(_object);
			leform_dialog_close();
		}
	});
	return false;
}

function leform_forms_duplicate(_object) {
	leform_dialog_open({
		echo_html: function () {
			this.html("<div class='leform-dialog-message'>" + leform_esc_html__("Please confirm that you want to duplicate the form.", "leform") + "</div>");
			this.show();
		},
		ok_label: leform_esc_html__('Duplicate'),
		ok_function: function (e) {
			_leform_forms_duplicate(_object);
			leform_dialog_close();
		}
	});
	return false;
}

function leform_forms_create_folder(_object, name = '', id = 0) {
	leform_dialog_open({
		title: leform_esc_html__(id === 0 ? 'Create Folder' : 'Update Folder'),
		height: 220,
		echo_html: function () {
			this.html(`<div class="leform-admin-create-content" style="padding:0; height: auto;">
			<input type="text" value="${name}" id="leform-folder-name" name"folder_name" placeholder="${leform_esc_html__('Please enter the folder name')}...">
			</div>`);
			this.show();
		},
		ok_label: leform_esc_html__(id === 0 ? 'Create': 'Rename'),
		ok_function: function (e) {
			_leform_forms_create_folder(_object, document.getElementById('leform-folder-name').value, id);
			leform_dialog_close();
		}
	});
	return false;
}

function leform_forms_move_folder(_object, listStr = '', id = 0, parent) {
	var list = [];
	try {
		list = JSON.parse(listStr);
	} catch (e) { }
	var dataType = jQuery(_object).attr("data-type");
	if (dataType === 'folder') {
		if (id) {
			list = list.filter(l => l.id !== id);
		}
		if (parent) {
			list = list.filter(l => l.id !== parent);
		}
	}
	leform_dialog_open({
		title: leform_esc_html__('Move Folder'),
		height: 220,
		echo_html: function () {
			this.html(`<div class="leform-admin-create-content" style="padding:0; height: auto;">
				<select style="width: 100%" id="leform-folder-list"> 
					<option value="0">Dashboard</option>
					${list.map(l => `<option value="${l.id}" ${l.id === parent ? 'selected' : ''}>${l.name}</option>`).join(' ')}
				</select>
			</div>`);
			this.show();
		},
		ok_label: leform_esc_html__('Move'),
		ok_function: function (e) {
			_leform_forms_move_folder(_object, document.getElementById('leform-folder-list').value, id, parent? `${parent}`: '');
			leform_dialog_close();
		}
	});
	return false;
}

function _leform_forms_duplicate(_object) {
	if (leform_sending) return false;
	leform_sending = true;
	var form_id = jQuery(_object).attr("data-id");
	var doing_label = jQuery(_object).attr("data-doing");
	var do_label = jQuery(_object).html();
	jQuery(_object).html("<i class='fas fa-spinner fa-spin'></i> " + doing_label);
	jQuery(_object).closest("tr").find(".row-actions").addClass("visible");
	var post_data = { "action": "leform-forms-duplicate", "form-id": form_id };
	jQuery.ajax({
		type: "POST",
		url: leform_ajax_handler,
		data: post_data,
		success: function (return_data) {
			try {
				var data;
				if (typeof return_data == 'object') data = return_data;
				else data = jQuery.parseJSON(return_data);
				if (data.status == "OK") {
					leform_global_message_show("success", data.message);
					location.reload();
				} else if (data.status == "ERROR") {
					leform_global_message_show("danger", data.message);
				} else {
					leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
				}
			} catch (error) {
				leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
			}
			jQuery(_object).html(do_label);
			jQuery(_object).closest("tr").find(".row-actions").removeClass("visible");
			leform_sending = false;
		},
		error: function (XMLHttpRequest, textStatus, errorThrown) {
			jQuery(_object).closest("tr").find(".row-actions").removeClass("visible");
			jQuery(_object).html(do_label);
			leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
			leform_sending = false;
		}
	});
	return false;
}

function leform_forms_share(_object) {
    const html = `
        <div class='leform-dialog-message'>
            ${leform_esc_html__("Please confirm that you want to share the form.", "leform")}
        </div>
    `;
    leform_dialog_open({
        echo_html: function () {
            this.html(html);
            this.show();
        },
        ok_label: _object.dataset.value === "1"
            ? leform_esc_html__('Unshare')
            : leform_esc_html__('Share'),
        ok_function: function () {
            _leform_forms_share(_object);
            leform_dialog_close();
        }
    });
    return false;
}

function leform_forms_copy_template(_object) {
    const html = `
        <div class='leform-dialog-message'>
            ${leform_esc_html__("Please confirm that you want to copy this template.", "leform")}
        </div>
    `;
    leform_dialog_open({
        echo_html: function () {
            this.html(html);
            this.show();
        },
        ok_label: leform_esc_html__('Copy'),
        ok_function: function () {
            _leform_forms_copy_template(_object);
            leform_dialog_close();
        }
    });
    return false;
}

function leform_columns_toggle(_object) {
	var columns = {};
	var json_columns = "";
	if (typeof _object === 'string' || _object instanceof String) {
		json_columns = leform_read_cookie("leform-" + _object + "-columns");
		if (json_columns != null) {
			try {
				columns = JSON.parse(json_columns);
				if (typeof columns == "object" && !jQuery.isEmptyObject(columns)) {
					jQuery("ul.leform-" + _object + "-columns input").each(function () {
						var id = jQuery(this).attr("data-id");
						if (columns.hasOwnProperty(id)) {
							if (columns[id] == "on") {
								jQuery(this).prop("checked", true);
								jQuery(".leform-column-" + id).show();
							} else {
								jQuery(this).prop("checked", false);
								jQuery(".leform-column-" + id).hide();
							}
						}
					});
					leform_write_cookie("leform-" + _object + "-columns", json_columns, 365);
				}
			} catch (error) {
				console.log(error);
			}
		}
	} else {
		var columns_set = jQuery(_object).closest("ul");
		if (columns_set) {
			jQuery(columns_set).find("input").each(function () {
				var id = jQuery(this).attr("data-id");
				if (jQuery(this).is(":checked")) {
					columns[id] = "on";
					jQuery(".leform-column-" + id).show();
				} else {
					columns[id] = "off";
					jQuery(".leform-column-" + id).hide();
				}
			});
			leform_write_cookie("leform-" + jQuery(columns_set).attr("data-id") + "-columns", JSON.stringify(columns), 365);
		}
	}

	return false;
}
var leform_more_active = null;
function leform_more_using_open(_object) {
	jQuery("#leform-more-using .leform-admin-popup-content-form").html("");
	var window_height = 2 * parseInt((jQuery(window).height() - 100) / 2, 10);
	var window_width = Math.min(Math.max(2 * parseInt((jQuery(window).width() - 300) / 2, 10), 640), 840);
	jQuery("#leform-more-using").height(window_height);
	jQuery("#leform-more-using").width(window_width);
	jQuery("#leform-more-using .leform-admin-popup-inner").height(window_height);
	jQuery("#leform-more-using .leform-admin-popup-content").height(window_height - 52);
	jQuery("#leform-more-using-overlay").fadeIn(300);
	jQuery("#leform-more-using").fadeIn(300);
	jQuery("#leform-more-using .leform-admin-popup-title h3 span").html("");
	jQuery("#leform-more-using .leform-admin-popup-loading").show();
	leform_more_active = jQuery(_object).attr("data-id");
	var post_data = { "action": "leform-using", "form-id": leform_more_active };
	jQuery.ajax({
		type: "POST",
		url: leform_ajax_handler,
		data: post_data,
		success: function (return_data) {
			try {
				var data;
				if (typeof return_data == 'object') data = return_data;
				else data = jQuery.parseJSON(return_data);
				if (data.status == "OK") {
					jQuery("#leform-more-using .leform-admin-popup-content-form").html(data.html);
					jQuery("#leform-more-using .leform-admin-popup-title h3 span").html(data.form_name);
					jQuery("#leform-more-using .leform-admin-popup-loading").hide();
				} else if (data.status == "ERROR") {
					leform_more_using_close();
					leform_global_message_show("danger", data.message);
				} else {
					leform_more_using_close();
					leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
				}
			} catch (error) {
				leform_more_using_close();
				leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
			}
		},
		error: function (XMLHttpRequest, textStatus, errorThrown) {
			leform_more_using_close();
			leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
		}
	});

	return false;
}

function leform_more_using_close() {
	jQuery("#leform-more-using-overlay").fadeOut(300);
	jQuery("#leform-more-using").fadeOut(300);
	leform_more_active = null;
	setTimeout(function () { jQuery("#leform-more-using .leform-admin-popup-content-form").html(""); }, 1000);
	return false;
}

var leform_stylemanager_active = null;
function leform_stylemanager_open(_object) {
	var actions_html, html = "";
	jQuery("#leform-stylemanager .leform-admin-popup-content-form").html("");
	var window_height = 2 * parseInt((jQuery(window).height() - 100) / 2, 10);
	var window_width = Math.min(Math.max(2 * parseInt((jQuery(window).width() - 300) / 2, 10), 640), 840);
	jQuery("#leform-stylemanager").height(window_height);
	jQuery("#leform-stylemanager").width(window_width);
	jQuery("#leform-stylemanager .leform-admin-popup-inner").height(window_height);
	jQuery("#leform-stylemanager .leform-admin-popup-content").height(window_height - 52);
	jQuery("#leform-stylemanager-overlay").fadeIn(300);
	jQuery("#leform-stylemanager").fadeIn(300);
	jQuery("#leform-stylemanager .leform-admin-popup-loading").show();
	leform_stylemanager_active = true;
	html = "<div class='leform-stylemanager-details" + (leform_styles.length > 0 ? "" : " leform-stylemanager-empty") + "'><div class='leform-stylemanager-buttons'><a href='#' class='leform-button' onclick='jQuery(\"#leform-import-style-file\").click(); return false;'><i class='fas fa-upload'></i><label>" + leform_esc_html__("Import Theme", "leform") + "</label></a></div><table>";
	if (leform_styles.length > 0) {
		for (var i = 0; i < leform_styles.length; i++) {
			if (leform_styles[i]["type"] == 0 || leform_styles[i]["type"] == "0") {
				actions_html = "<div class='leform-table-list-actions'><span><i class='fas fa-ellipsis-v'></i></span><div class='leform-table-list-menu'><ul><li><a href='#' data-id='" + leform_escape_html(leform_styles[i]["id"]) + "' onclick='return leform_stylemanager_rename(this);'>" + leform_esc_html__("Rename", "leform") + "</a></li><li><a href='/export-style?id=" + leform_escape_html(leform_styles[i]["id"]) + "' target='_blank'>" + leform_esc_html__("Export", "leform") + "</a></li><li class='leform-table-list-menu-line'></li><li><a href='#' data-id='" + leform_escape_html(leform_styles[i]["id"]) + "' data-doing='" + leform_esc_html__("Deleting...", "leform") + "' onclick='return leform_stylemanager_delete(this);'>" + leform_esc_html__("Delete", "leform") + "</a></li></ul></div></div>";
				html += "<tr><th>" + leform_escape_html(leform_styles[i]["name"]) + "</th><td>" + actions_html + "</td></tr>";
			}
		}
	} else {
		html += "<tr><th>" + leform_esc_html__("No user styles found.", "leform") + "</th></tr>";
	}
	html += "</table></div>";

	jQuery("#leform-stylemanager .leform-admin-popup-content-form").html(html);
	jQuery("#leform-stylemanager .leform-admin-popup-loading").hide();

	return false;
}

function leform_stylemanager_close() {
	jQuery("#leform-stylemanager-overlay").fadeOut(300);
	jQuery("#leform-stylemanager").fadeOut(300);
	leform_stylemanager_active = null;
	setTimeout(function () { jQuery("#leform-stylemanager .leform-admin-popup-content-form").html(""); }, 1000);
	return false;
}

function leform_stylemanager_delete(_object) {
	leform_dialog_open({
		echo_html: function () {
			this.html("<div class='leform-dialog-message'>" + leform_esc_html__("Please confirm that you want to delete the theme.", "leform") + "</div>");
			this.show();
		},
		ok_label: leform_esc_html__('Delete'),
		ok_function: function (e) {
			_leform_stylemanager_delete(_object);
			leform_dialog_close();
		}
	});
	return false;
}

function _leform_stylemanager_delete(_object) {
	if (leform_sending) return false;
	leform_sending = true;
	var style_id = jQuery(_object).attr("data-id");
	var doing_label = jQuery(_object).attr("data-doing");
	var do_label = jQuery(_object).html();
	jQuery(_object).html("<i class='fas fa-spinner fa-spin'></i> " + doing_label);
	var post_data = { "action": "leform-stylemanager-delete", "style-id": style_id };
	jQuery.ajax({
		type: "POST",
		url: leform_ajax_handler,
		data: post_data,
		success: function (return_data) {
			try {
				var data;
				if (typeof return_data == 'object') data = return_data;
				else data = jQuery.parseJSON(return_data);
				if (data.status == "OK") {
					jQuery(_object).closest("tr").fadeOut(300, function () {
						jQuery(_object).closest("tr").remove();
					});
					leform_styles = data.styles;
					var html = leform_styles_html();
					jQuery(".leform-styles-select-container").html(html);
					leform_global_message_show("success", data.message);
				} else if (data.status == "ERROR") {
					leform_global_message_show("danger", data.message);
				} else {
					leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
				}
			} catch (error) {
				leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
			}
			jQuery(_object).html(do_label);
			leform_sending = false;
		},
		error: function (XMLHttpRequest, textStatus, errorThrown) {
			jQuery(_object).html(do_label);
			leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
			leform_sending = false;
		}
	});
	return false;
}

function leform_stylemanager_rename(_object) {
	var style_id = jQuery(_object).attr("data-id");
	leform_dialog_open({
		echo_html: function () {
			var name = leform_form_options['name'] + " style";
			for (var i = 0; i < leform_styles.length; i++) {
				if (leform_styles[i]['id'] == style_id) {
					name = leform_styles[i]['name'];
					break;
				}
			}
			var html = "<div class='leform-style-save-row' id='leform-style-save-row-name'><label>" + leform_esc_html__("Name", "leform") + ":</label><input type='text' value='" + leform_escape_html(name) + "' placeholder='" + leform_esc_html__("Enter style name...", "leform") + "' id='leform-style-name' /></div>"
			this.html(html);
			this.show();
		},
		height: 270,
		title: leform_esc_html__('Rename the theme', 'leform'),
		ok_label: leform_esc_html__('Rename', 'leform'),
		ok_function: function (e) {
			_leform_stylemanager_rename(_object, jQuery("#leform-dialog .leform-dialog-button-ok"), style_id);
		}
	});
	return false;
}

function _leform_stylemanager_rename(_object, _button, _style_id) {
	if (leform_sending) return false;
	leform_sending = true;
	var icon = jQuery(_button).find("i").attr("class");
	jQuery(_button).find("i").attr("class", "fas fa-spinner fa-spin");
	var post_data = { "action": "leform-stylemanager-save", "style-id": _style_id, "name": leform_encode64(jQuery("#leform-style-name").val()) };
	jQuery.ajax({
		type: "POST",
		url: leform_ajax_handler,
		data: post_data,
		success: function (return_data) {
			try {
				var data;
				if (typeof return_data == 'object') data = return_data;
				else data = jQuery.parseJSON(return_data);
				if (data.status == "OK") {
					leform_styles = data.styles;
					var html = leform_styles_html();
					jQuery(".leform-styles-select-container").html(html);
					jQuery(_object).closest("tr").find("th").html(data.name);
					leform_global_message_show("success", data.message);
				} else if (data.status == "ERROR") {
					leform_global_message_show("danger", data.message);
				} else {
					leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
				}
			} catch (error) {
				console.log(error);
				leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
			}
			jQuery(_button).find("i").attr("class", icon);
			leform_sending = false;
			leform_dialog_close();
		},
		error: function (XMLHttpRequest, textStatus, errorThrown) {
			jQuery(_button).find("i").attr("class", icon);
			leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
			leform_sending = false;
			leform_dialog_close();
		}
	});
	return false;
}

function leform_stylemanager_imported(_object) {
	if (jQuery(_object).attr("data-loading") != "true") return;
	jQuery(_object).attr("data-loading", "false");
	var return_data = jQuery(_object).contents().find("body").html();
	try {
		var data;
		if (typeof return_data == 'object') data = return_data;
		else data = jQuery.parseJSON(return_data);
		if (data.status == "OK") {
			leform_styles.push({ "id": data.id, "name": data.name, "type": data.type });
			var html = leform_styles_html();
			jQuery(".leform-styles-select-container").html(html);
			var row = "<tr><th>" + leform_escape_html(data.name) + "</th><td><div class='leform-table-list-actions'><span><i class='fas fa-ellipsis-v'></i></span><div class='leform-table-list-menu'><ul><li><a href='#' data-id='" + leform_escape_html(data.id) + "' onclick='return leform_stylemanager_rename(this);'>" + leform_esc_html__("Rename", "leform") + "</a></li><li><a href='?page=leform&leform-action=export-style&id=" + leform_escape_html(data.id) + "' target='_blank'>" + leform_esc_html__("Export", "leform") + "</a></li><li class='leform-table-list-menu-line'></li><li><a href='#' data-id='" + leform_escape_html(data.id) + "' data-doing='" + leform_esc_html__("Deleting...", "leform") + "' onclick='return leform_stylemanager_delete(this);'>" + leform_esc_html__("Delete", "leform") + "</a></li></ul></div></div></td></tr>";
			if (jQuery(".leform-stylemanager-details").hasClass("leform-stylemanager-empty")) {
				jQuery(".leform-stylemanager-details").removeClass("leform-stylemanager-empty");
				jQuery(".leform-stylemanager-details table").html(row);
			} else jQuery(".leform-stylemanager-details table").prepend(row);
			leform_global_message_show("success", data.message);
		} else if (data.status == "ERROR") {
			leform_global_message_show("danger", data.message);
		} else {
			leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
		}
	} catch (error) {
		console.log(error);
		leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
	}
	return;
}

var leform_preview_active = null;
function leform_preview_size(_object) {
	if (jQuery(_object).hasClass("leform-preview-size-active")) return;
	jQuery(".leform-preview-size-active").removeClass("leform-preview-size-active");
	jQuery(_object).addClass("leform-preview-size-active");
	var max_width = parseInt(jQuery(_object).attr("data-width"), 10);
	jQuery("#leform-preview").attr("data-width", max_width);
	var window_width = Math.min(Math.max(2 * parseInt((jQuery(window).width() - 40) / 2, 10), 480), max_width);
	jQuery("#leform-preview").width(window_width);

}
function leform_preview_loaded(_object) {
	if (jQuery(_object).attr("data-loading") != "true") return;
	jQuery(_object).attr("data-loading", "false");
	leform_preview_open();
	jQuery(".leform-header-preview").find("i").attr("class", "far fa-eye");
	leform_sending = false;
	return;
}
function leform_preview_open() {
	var max_width = parseInt(jQuery("#leform-preview").attr("data-width"), 10);
	var window_height = 2 * parseInt((jQuery(window).height() - 40) / 2, 10);
	var window_width = Math.min(Math.max(2 * parseInt((jQuery(window).width() - 40) / 2, 10), 480), max_width);
	jQuery("#leform-preview").height(window_height);
	jQuery("#leform-preview").width(window_width);
	jQuery("#leform-preview .leform-admin-popup-inner").height(window_height);
	jQuery("#leform-preview .leform-admin-popup-content").height(window_height - 52);
	jQuery("#leform-preview-iframe").height(window_height - 52);
	jQuery("#leform-preview-overlay").fadeIn(300);
	jQuery("#leform-preview").fadeIn(300);
	leform_preview_active = true;
	return false;
}

function leform_preview_close() {
	jQuery("#leform-preview-overlay").fadeOut(300);
	jQuery("#leform-preview").fadeOut(300);
	leform_preview_active = null;
	setTimeout(function () { jQuery("#leform-preview-iframe").attr("src", "about:blank"); }, 1000);
	return false;
}

function leform_stats_reset(_object) {
	leform_dialog_open({
		echo_html: function () {
			this.html("<div class='leform-dialog-message'>" + leform_esc_html__("Please confirm that you want to reset form statistics.", "leform") + "</div>");
			this.show();
		},
		ok_label: leform_esc_html__('Reset'),
		ok_function: function (e) {
			_leform_stats_reset(_object);
			leform_dialog_close();
		}
	});
	return false;
}

function _leform_stats_reset(_object) {
	if (leform_sending) return false;
	leform_sending = true;
	var form_id = jQuery(_object).attr("data-id");
	var doing_label = jQuery(_object).attr("data-doing");
	var do_label = jQuery(_object).html();
	jQuery(_object).html("<i class='fas fa-spinner fa-spin'></i> " + doing_label);
	jQuery(_object).closest("tr").find(".row-actions").addClass("visible");
	var post_data = { "action": "leform-stats-reset", "form-id": form_id };
	jQuery.ajax({
		type: "POST",
		url: leform_ajax_handler,
		data: post_data,
		success: function (return_data) {
			try {
				var data;
				if (typeof return_data == 'object') data = return_data;
				else data = jQuery.parseJSON(return_data);
				if (data.status == "OK") {
					leform_global_message_show("success", data.message);
				} else if (data.status == "ERROR") {
					leform_global_message_show("danger", data.message);
				} else {
					leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
				}
			} catch (error) {
				leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
			}
			jQuery(_object).html(do_label);
			jQuery(_object).closest("tr").find(".row-actions").removeClass("visible");
			leform_sending = false;
		},
		error: function (XMLHttpRequest, textStatus, errorThrown) {
			jQuery(_object).closest("tr").find(".row-actions").removeClass("visible");
			jQuery(_object).html(do_label);
			leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
			leform_sending = false;
		}
	});
	return false;
}

var leform_record_active = null;
function leform_record_details_open(_object) {
	var href;
	jQuery("#leform-record-details .leform-admin-popup-content-form").html("");
	var window_height = 2 * parseInt((jQuery(window).height() - 100) / 2, 10);
	var window_width = Math.min(Math.max(2 * parseInt((jQuery(window).width() - 300) / 2, 10), 640), 1080);
	jQuery("#leform-record-details").height(window_height);
	jQuery("#leform-record-details").width(window_width);
	jQuery("#leform-record-details .leform-admin-popup-inner").height(window_height);
	jQuery("#leform-record-details .leform-admin-popup-content").height(window_height - 52);
	jQuery("#leform-record-details-overlay").fadeIn(300);
	jQuery("#leform-record-details").fadeIn(300);
	jQuery("#leform-record-details .leform-admin-popup-title h3 span").html("");
	jQuery("#leform-record-details .leform-admin-popup-loading").show();
	leform_record_active = jQuery(_object).attr("data-id");
	var pdf_button = jQuery("#leform-record-details .leform-admin-popup-title span.leform-export-pdf");
	if (pdf_button.length > 0) {
		href = jQuery(pdf_button).attr("data-url");
		href = href.replace(/{ID}/g, leform_record_active);
		jQuery(pdf_button).find("a").attr("href", href);
	}
	var print_button = jQuery("#leform-record-details .leform-admin-popup-title span.leform-print");
	if (print_button.length > 0) {
		href = jQuery(print_button).attr("data-url");
		href = href.replace(/{ID}/g, leform_record_active);
		jQuery(print_button).find("a").attr("href", href);
	}
	var post_data = { "action": "leform-record-details", "record-id": leform_record_active };
	jQuery.ajax({
		type: "POST",
		url: leform_ajax_handler,
		data: post_data,
		success: function (return_data) {
			try {
				var data;
				if (typeof return_data == 'object') data = return_data;
				else data = jQuery.parseJSON(return_data);
				if (data.status == "OK") {
					jQuery("#leform-record-details .leform-admin-popup-content-form").html(data.html);
					jQuery("#leform-record-details .leform-admin-popup-title h3 span").html(data.form_name);
					jQuery("#leform-record-details .leform-admin-popup-loading").hide();
				} else if (data.status == "ERROR") {
					leform_record_details_close();
					leform_global_message_show("danger", data.message);
				} else {
					leform_record_details_close();
					leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
				}
			} catch (error) {
				leform_record_details_close();
				leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
			}
		},
		error: function (XMLHttpRequest, textStatus, errorThrown) {
			leform_record_details_close();
			leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
		}
	});

	return false;
}

function leform_record_details_close() {
	jQuery("#leform-record-details-overlay").fadeOut(300);
	jQuery("#leform-record-details").fadeOut(300);
	leform_record_active = null;
	setTimeout(function () { jQuery("#leform-record-details .leform-admin-popup-content-form").html(""); }, 1000);
	return false;
}

function dateRangeKeyUp(e) {
	if (e.keyCode === 13) {
		e.stopPropagation();
		e.preventDefault();

		const currentVal = $(this).val();
		return false;
	}
}
function applyDateRangePicker(ev, picker) {
	const currentVal = $(this).val();
	const seStartDate = picker.startDate.format('DD.MM.YYYY');
	const seEndDate = picker.endDate.format('DD.MM.YYYY');
	const newVal = seStartDate + ' - ' + seEndDate;

	if (currentVal !== newVal) {
		$(this).val(newVal);
	}
}
function cancelDateRangePicker(...args) {
	const newVal = '';
	$(this).val(newVal).trigger('change');
}

function export_xml_zip(url) {
	leform_dialog_open({
		title: leform_esc_html__("Export XML by date range", "leform"),
		echo_html: function () {
			this.html('<input type="text" placeholder="Datumsbereich" value="" autocomplete="new-password" style="width: 100%; background-color: white;" class="form-control" id="entry_date_range">');
			this.show();
			const dateRangeInput = jQuery("#entry_date_range");
			dateRangeInput.daterangepicker({
				showDropdowns: true,
				maxDate: new Date(),
				autoUpdateInput: false,
				locale: {
					...daterangepicker_language.de,
					format: 'DD.MM.YYYY',
				}
			});
			dateRangeInput.on('keyup', dateRangeKeyUp);
			dateRangeInput.on('apply.daterangepicker', applyDateRangePicker);

			dateRangeInput.on('cancel.daterangepicker', cancelDateRangePicker);
		},
		ok_label: leform_esc_html__('Export'),
		ok_function: function (e) {
			const dateRangeInput = jQuery("#entry_date_range");
			const [startDate, endDate] = dateRangeInput.val().split(' - ');
			if (startDate && endDate) {
				dateRangeInput.css('border-color', 'initial');
				window.open(`${url}?start=${startDate}&end=${endDate}`, '_blank');
				leform_dialog_close();
			} else {
				dateRangeInput.css('border-color', 'red');
			}
		}
	});
	return false;
}

function leform_records_delete(_object) {
	leform_dialog_open({
		echo_html: function () {
			this.html("<div class='leform-dialog-message'>" + leform_esc_html__("Please confirm that you want to delete the record.", "leform") + "</div>");
			this.show();
		},
		ok_label: leform_esc_html__('Delete'),
		ok_function: function (e) {
			_leform_records_delete(_object);
			leform_dialog_close();
		}
	});
	return false;
}

function _leform_records_delete(_object) {
	if (leform_sending) return false;
	leform_sending = true;
	var record_id = jQuery(_object).attr("data-id");
	var doing_label = jQuery(_object).attr("data-doing");
	var do_label = jQuery(_object).html();
	jQuery(_object).html("<i class='fas fa-spinner fa-spin'></i> " + doing_label);
	jQuery(_object).closest("tr").find(".row-actions").addClass("visible");
	var post_data = { "action": "leform-records-delete", "record-id": record_id };
	jQuery.ajax({
		type: "POST",
		url: leform_ajax_handler,
		data: post_data,
		success: function (return_data) {
			try {
				var data;
				if (typeof return_data == 'object') data = return_data;
				else data = jQuery.parseJSON(return_data);
				if (data.status == "OK") {
					jQuery(_object).closest("tr").fadeOut(300, function () {
						jQuery(_object).closest("tr").remove();
					});
					leform_global_message_show("success", data.message);
				} else if (data.status == "ERROR") {
					leform_global_message_show("danger", data.message);
				} else {
					leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
				}
			} catch (error) {
				leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
			}
			jQuery(_object).html(do_label);
			jQuery(_object).closest("tr").find(".row-actions").removeClass("visible");
			leform_sending = false;
		},
		error: function (XMLHttpRequest, textStatus, errorThrown) {
			jQuery(_object).closest("tr").find(".row-actions").removeClass("visible");
			jQuery(_object).html(do_label);
			leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
			leform_sending = false;
		}
	});
	return false;
}

function leform_transaction_details_open(_object) {
	jQuery("#leform-record-details .leform-admin-popup-content-form").html("");
	var window_height = 2 * parseInt((jQuery(window).height() - 100) / 2, 10);
	var window_width = Math.min(Math.max(2 * parseInt((jQuery(window).width() - 300) / 2, 10), 640), 1080);
	jQuery("#leform-record-details").height(window_height);
	jQuery("#leform-record-details").width(window_width);
	jQuery("#leform-record-details .leform-admin-popup-inner").height(window_height);
	jQuery("#leform-record-details .leform-admin-popup-content").height(window_height - 52);
	jQuery("#leform-record-details-overlay").fadeIn(300);
	jQuery("#leform-record-details").fadeIn(300);
	jQuery("#leform-record-details .leform-admin-popup-title h3 span").html("");
	jQuery("#leform-record-details .leform-admin-popup-loading").show();
	leform_record_active = jQuery(_object).attr("data-id");
	var href;
	var pdf_button = jQuery("#leform-record-details .leform-admin-popup-title span.leform-export-pdf");
	if (pdf_button.length > 0) {
		href = jQuery(pdf_button).attr("data-url");
		href = href.replace(/{ID}/g, leform_record_active);
		jQuery(pdf_button).find("a").attr("href", href);
	}
	var print_button = jQuery("#leform-record-details .leform-admin-popup-title span.leform-print");
	if (print_button.length > 0) {
		href = jQuery(print_button).attr("data-url");
		href = href.replace(/{ID}/g, leform_record_active);
		jQuery(print_button).find("a").attr("href", href);
	}
	var post_data = { "action": "leform-transaction-details", "transaction-id": leform_record_active };
	jQuery.ajax({
		type: "POST",
		url: leform_ajax_handler,
		data: post_data,
		success: function (return_data) {
			try {
				var data;
				if (typeof return_data == 'object') data = return_data;
				else data = jQuery.parseJSON(return_data);
				if (data.status == "OK") {
					jQuery("#leform-record-details .leform-admin-popup-content-form").html(data.html);
					jQuery("#leform-record-details .leform-admin-popup-loading").hide();
				} else if (data.status == "ERROR") {
					leform_record_details_close();
					leform_global_message_show("danger", data.message);
				} else {
					leform_record_details_close();
					leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
				}
			} catch (error) {
				leform_record_details_close();
				leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
			}
		},
		error: function (XMLHttpRequest, textStatus, errorThrown) {
			leform_record_details_close();
			leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
		}
	});

	return false;
}

function leform_transaction_details_close() {
	jQuery("#leform-record-details-overlay").fadeOut(300);
	jQuery("#leform-record-details").fadeOut(300);
	leform_record_active = null;
	setTimeout(function () { jQuery("#leform-record-details .leform-admin-popup-content-form").html(""); }, 1000);
}

function leform_transactions_delete(_object) {
	leform_dialog_open({
		echo_html: function () {
			this.html("<div class='leform-dialog-message'>" + leform_esc_html__("Please confirm that you want to delete the transaction.", "leform") + "</div>");
			this.show();
		},
		ok_label: leform_esc_html__('Delete'),
		ok_function: function (e) {
			_leform_transactions_delete(_object);
			leform_dialog_close();
		}
	});
	return false;
}

function _leform_transactions_delete(_object) {
	if (leform_sending) return false;
	leform_sending = true;
	var record_id = jQuery(_object).attr("data-id");
	var doing_label = jQuery(_object).attr("data-doing");
	var do_label = jQuery(_object).html();
	jQuery(_object).html("<i class='fas fa-spinner fa-spin'></i> " + doing_label);
	jQuery(_object).closest("tr").find(".row-actions").addClass("visible");
	var post_data = { "action": "leform-transactions-delete", "transaction-id": record_id };
	jQuery.ajax({
		type: "POST",
		url: leform_ajax_handler,
		data: post_data,
		success: function (return_data) {
			try {
				var data;
				if (typeof return_data == 'object') data = return_data;
				else data = jQuery.parseJSON(return_data);
				if (data.status == "OK") {
					jQuery(_object).closest("tr").fadeOut(300, function () {
						jQuery(_object).closest("tr").remove();
					});
					leform_global_message_show("success", data.message);
				} else if (data.status == "ERROR") {
					leform_global_message_show("danger", data.message);
				} else {
					leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
				}
			} catch (error) {
				leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
			}
			jQuery(_object).html(do_label);
			jQuery(_object).closest("tr").find(".row-actions").removeClass("visible");
			leform_sending = false;
		},
		error: function (XMLHttpRequest, textStatus, errorThrown) {
			jQuery(_object).closest("tr").find(".row-actions").removeClass("visible");
			jQuery(_object).html(do_label);
			leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
			leform_sending = false;
		}
	});
	return false;
}

function leform_field_analytics_load(_object) {
	if (leform_sending) return false;
	leform_sending = true;
	jQuery(_object).find("i").attr("class", "fas fa-spinner fa-spin");
	jQuery(_object).addClass("leform-stats-button-disabled");
	var post_data = { "action": "leform-field-analytics-load", "form-id": jQuery("#leform-stats-form").val(), "start-date": jQuery("#leform-stats-date-start").val(), "end-date": jQuery("#leform-stats-date-end").val(), "period": (jQuery("#leform-stats-period").is(":checked") ? "on" : "off") };
	jQuery.ajax({
		type: "POST",
		url: leform_ajax_handler,
		data: post_data,
		success: function (return_data) {
			try {
				var data;
				if (typeof return_data == 'object') data = return_data;
				else data = jQuery.parseJSON(return_data);
				if (data.status == "OK") {
					leform_field_analytics_build_charts(data.data);
				} else if (data.status == "ERROR") {
					leform_global_message_show("danger", data.message);
				} else {
					leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
				}
			} catch (error) {
				leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
			}
			jQuery(_object).find("i").attr("class", "fas fa-check");
			jQuery(_object).removeClass("leform-stats-button-disabled");
			leform_sending = false;
		},
		error: function (XMLHttpRequest, textStatus, errorThrown) {
			jQuery(_object).find("i").attr("class", "fas fa-check");
			jQuery(_object).removeClass("leform-stats-button-disabled");
			leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
			leform_sending = false;
		}
	});
	return false;
}

function leform_field_analytics_ready() {
	var airdatepicker = jQuery("#leform-stats-date-start").airdatepicker().data('airdatepicker');
	airdatepicker.destroy();
	jQuery("#leform-stats-date-start").airdatepicker({
		autoClose: true,
		timepicker: false,
		dateFormat: 'dd.mm.yyyy',
		onShow: function (inst, animationCompleted) {
			var max_date_string = jQuery("#leform-stats-date-end").val() ? jQuery("#leform-stats-date-end").val() : "2030-12-31";
			inst.update('maxDate', new Date(max_date_string));
		}
	});
	airdatepicker = jQuery("#leform-stats-date-end").airdatepicker().data('airdatepicker');
	airdatepicker.destroy();
	jQuery("#leform-stats-date-end").airdatepicker({
		autoClose: true,
		timepicker: false,
		dateFormat: 'dd.mm.yyyy',
		onShow: function (inst, animationCompleted) {
			var min_date_string = jQuery("#leform-stats-date-start").val() ? jQuery("#leform-stats-date-start").val() : "2018-01-01";
			inst.update('minDate', new Date(min_date_string));
		}
	});
	jQuery("#leform-stats-period").on("change", function (e) {
		if (jQuery("#leform-stats-period").is(":checked")) {
			jQuery(".leform-stats-input-container").fadeIn(300);
		} else {
			jQuery(".leform-stats-input-container").fadeOut(300);
		}
	});

	var data = JSON.parse(jQuery("#leform-field-analytics-initial-data").val());
	if (jQuery("#leform-stats-form").val() != 0) leform_field_analytics_build_charts(data);
}

function leform_field_analytics_build_charts(_charts) {
	var colors = new Array(
		{
			backgroundColor: 'rgb(255, 99, 132, 0.7)',
			borderColor: 'rgb(255, 99, 132)',
		},
		{
			backgroundColor: 'rgba(75, 192, 192, 0.7)',
			borderColor: 'rgb(75, 192, 192)',
		},
		{
			backgroundColor: 'rgba(255, 205, 86, 0.7)',
			borderColor: 'rgb(255, 205, 86)',
		},
		{
			backgroundColor: 'rgba(54, 162, 235, 0.7)',
			borderColor: 'rgb(54, 162, 235)',
		},
		{
			backgroundColor: 'rgba(153, 102, 255, 0.7)',
			borderColor: 'rgb(153, 102, 255)',
		},
		{
			backgroundColor: 'rgba(201, 203, 207, 0.7)',
			borderColor: 'rgb(201, 203, 207)',
		}
	);
	if (_charts.length == 0) {
		jQuery(".leform-field-analytics-container").html("<div class='leform-field-analytics-noform'>No data found.</div>");
	} else {
		var column1_height = 0, column2_height = 0, height = 0, chart_html = "";
		var labels = new Array();
		var values = new Array();
		jQuery(".leform-field-analytics-container").html("");
		if (_charts.length > 1) jQuery(".leform-field-analytics-container").html("<div class='leform-field-analytics-columns'><div id='leform-field-analytics-column1'></div><div id='leform-field-analytics-column2'></div></div>");
		else jQuery(".leform-field-analytics-container").html("");
		for (var i = 0; i < _charts.length; i++) {
			labels = new Array();
			values = new Array();
			for (var j = 0; j < _charts[i]['chart'].length; j++) {
				if (_charts[i]['chart'][j]['label'].length > 24) labels.push(_charts[i]['chart'][j]['label'].substring(0, 20) + "...");
				else labels.push(_charts[i]['chart'][j]['label']);
				values.push(parseInt(_charts[i]['chart'][j]['value'], 10));
			}
			height = 128 + 24 * labels.length;
			chart_html = "<div class='leform-field-analytics-chart-box'><canvas id='leform-field-" + _charts[i]["form-id"] + "-" + _charts[i]["id"] + "'></canvas></div>";
			if (_charts.length > 1) {
				if (column1_height > column2_height) {
					jQuery("#leform-field-analytics-column2").append(chart_html);
					column2_height += height + 32;
				} else {
					jQuery("#leform-field-analytics-column1").append(chart_html);
					column1_height += height + 32;
				}
			} else jQuery(".leform-field-analytics-container").append(chart_html);

			jQuery("#leform-field-" + _charts[i]["form-id"] + "-" + _charts[i]["id"]).height(height);
			leform_chart = new Chart("leform-field-" + _charts[i]["form-id"] + "-" + _charts[i]["id"], {
				type: "horizontalBar",
				data: {
					labels: labels,
					datasets: [{
						label: _charts[i]["title"],
						data: values,
						backgroundColor: colors[i % colors.length]["backgroundColor"],
						borderColor: colors[i % colors.length]["borderColor"],
						borderWidth: 1
					}]
				},
				options: {
					responsive: true,
					maintainAspectRatio: false,
					tooltips: {
						mode: 'index',
						intersect: false,
					},
					legend: {
						display: false
					},
					title: {
						display: true,
						text: _charts[i]["title"]
					},
					scales: {
						yAxes: [{
							maxBarThickness: 32
						}],
						xAxes: [{
							ticks: {
								beginAtZero: true
							}
						}],
					}
				}
			});

		}
	}
}

function leform_stats_load(_object) {
	if (leform_sending) return false;
	leform_sending = true;
	jQuery(_object).find("i").attr("class", "fas fa-spinner fa-spin");
	jQuery(_object).addClass("leform-stats-button-disabled");
	var post_data = { "action": "leform-stats-load", "form-id": jQuery("#leform-stats-form").val(), "start-date": jQuery("#leform-stats-date-start").val(), "end-date": jQuery("#leform-stats-date-end").val() };
	jQuery.ajax({
		type: "POST",
		url: leform_ajax_handler,
		data: post_data,
		success: function (return_data) {
			try {
				var data;
				if (typeof return_data == 'object') data = return_data;
				else data = jQuery.parseJSON(return_data);
				if (data.status == "OK") {
					var labels = new Array();
					var impressions = new Array();
					var submits = new Array();
					var confirmed = new Array();
					var payments = new Array();
					for (var key in data.data) {
						if (data.data.hasOwnProperty(key)) {
							labels.push(data.data[key]["label"]);
							impressions.push(data.data[key]["impressions"]);
							confirmed.push(data.data[key]["confirmed"]);
							submits.push(data.data[key]["submits"]);
							payments.push(data.data[key]["payments"]);
						}
					}
					leform_stats_build_chart(labels, impressions, submits, confirmed, payments);
				} else if (data.status == "ERROR") {
					leform_global_message_show("danger", data.message);
				} else {
					leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
				}
			} catch (error) {
				leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
			}
			jQuery(_object).find("i").attr("class", "fas fa-check");
			jQuery(_object).removeClass("leform-stats-button-disabled");
			leform_sending = false;
		},
		error: function (XMLHttpRequest, textStatus, errorThrown) {
			jQuery(_object).find("i").attr("class", "fas fa-check");
			jQuery(_object).removeClass("leform-stats-button-disabled");
			leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
			leform_sending = false;
		}
	});
	return false;

}

function leform_stats_ready() {
	var airdatepicker = jQuery("#leform-stats-date-start").airdatepicker().data('airdatepicker');
	airdatepicker.destroy();
	jQuery("#leform-stats-date-start").airdatepicker({
		autoClose: true,
		timepicker: false,
		dateFormat: 'dd.mm.yyyy',
		onShow: function (inst, animationCompleted) {
			var max_date_string = jQuery("#leform-stats-date-end").val() ? jQuery("#leform-stats-date-end").val() : "2030-12-31";
			inst.update('maxDate', new Date(max_date_string));
		}
	});
	airdatepicker = jQuery("#leform-stats-date-end").airdatepicker().data('airdatepicker');
	airdatepicker.destroy();
	jQuery("#leform-stats-date-end").airdatepicker({
		autoClose: true,
		timepicker: false,
		dateFormat: 'dd.mm.yyyy',
		onShow: function (inst, animationCompleted) {
			var min_date_string = jQuery("#leform-stats-date-start").val() ? jQuery("#leform-stats-date-start").val() : "2018-01-01";
			inst.update('minDate', new Date(min_date_string));
		}
	});
	var labels = new Array();
	var impressions = new Array();
	var submits = new Array();
	var confirmed = new Array();
	var payments = new Array();
	var data = JSON.parse(jQuery("#leform-stats-initial-data").val());
	for (var key in data) {
		if (data.hasOwnProperty(key)) {
			labels.push(data[key]["label"]);
			impressions.push(data[key]["impressions"]);
			submits.push(data[key]["submits"]);
			confirmed.push(data[key]["confirmed"]);
			payments.push(data[key]["payments"]);
		}
	}
	leform_stats_build_chart(labels, impressions, submits, confirmed, payments);
}

var leform_chart = null;
function leform_stats_build_chart(_labels, _impressions, _submits, _confirmed, _payments) {
	if (leform_chart) leform_chart.destroy();
	leform_chart = new Chart("leform-stats", {
		type: "line",
		data: {
			labels: _labels,
			datasets: [{
				label: "Impressions",
				lineTension: 0,
				fill: false,
				data: _impressions,
				backgroundColor: 'rgb(255, 99, 132)',
				borderColor: 'rgb(255, 99, 132)',
				borderWidth: 2
			},
			{
				label: "Submits",
				lineTension: 0,
				fill: false,
				data: _submits,
				backgroundColor: 'rgb(255, 159, 64)',
				borderColor: 'rgb(255, 159, 64)',
				borderWidth: 2
			},
			{
				label: "Confirmed",
				lineTension: 0,
				fill: false,
				data: _confirmed,
				backgroundColor: 'rgb(75, 192, 192)',
				borderColor: 'rgb(75, 192, 192)',
				borderWidth: 2
			},
			{
				label: "Payments",
				lineTension: 0,
				fill: false,
				data: _payments,
				backgroundColor: 'rgb(204, 125, 188)',
				borderColor: 'rgb(204, 125, 188)',
				borderWidth: 2
			}
			]
		},
		options: {
			responsive: true,
			tooltips: {
				mode: 'index',
				intersect: false,
			},
			/*			hover: {
							mode: 'nearest',
							intersect: true
						},*/
			scales: {
				yAxes: [{
					ticks: {
						beginAtZero: true
					}
				}]
			}
		}
	});
}

function leform_record_field_empty(_object) {
	leform_dialog_open({
		echo_html: function () {
			this.html("<div class='leform-dialog-message'>" + leform_esc_html__('Please confirm that you want to empty this field.') + "</div>");
			this.show();
		},
		ok_label: leform_esc_html__('Empty Field'),
		ok_function: function (e) {
			_leform_record_field_empty(jQuery("#leform-dialog .leform-dialog-button-ok"), _object);
		}
	});
}

function _leform_record_field_empty(_button, _object) {
	if (leform_sending) return false;
	leform_sending = true;
	var field_id = jQuery(_object).closest(".leform-record-details-table-value").attr("data-id");
	var record_id = jQuery(_object).closest(".leform-record-details").attr("data-id");
	var icon = jQuery(_button).find("i").attr("class");
	jQuery(_button).find("i").attr("class", "fas fa-spinner fa-spin");
	var post_data = { "action": "leform-record-field-empty", "record-id": record_id, "field-id": field_id };
	jQuery.ajax({
		type: "POST",
		url: leform_ajax_handler,
		data: post_data,
		success: function (return_data) {
			try {
				var data;
				if (typeof return_data == 'object') data = return_data;
				else data = jQuery.parseJSON(return_data);
				if (data.status == "OK") {
					jQuery(_object).closest(".leform-record-details-table-value").find(".leform-record-field-value").text("-");
					leform_global_message_show("success", data.message);
				} else if (data.status == "ERROR") {
					leform_global_message_show("danger", data.message);
				} else {
					leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
				}
			} catch (error) {
				console.log(error);
				leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
			}
			jQuery(_button).find("i").attr("class", icon);
			leform_sending = false;
			leform_dialog_close();
		},
		error: function (XMLHttpRequest, textStatus, errorThrown) {
			jQuery(_button).find("i").attr("class", icon);
			leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
			leform_sending = false;
			leform_dialog_close();
		}
	});
	return false;
}

function leform_record_field_remove(_object) {
	leform_dialog_open({
		echo_html: function () {
			this.html("<div class='leform-dialog-message'>" + leform_esc_html__('Please confirm that you want to remove this field.') + "</div>");
			this.show();
		},
		ok_label: leform_esc_html__('Remove Field'),
		ok_function: function (e) {
			_leform_record_field_remove(jQuery("#leform-dialog .leform-dialog-button-ok"), _object);
		}
	});
}

function _leform_record_field_remove(_button, _object) {
	if (leform_sending) return false;
	leform_sending = true;
	var field_id = jQuery(_object).closest(".leform-record-details-table-value").attr("data-id");
	var record_id = jQuery(_object).closest(".leform-record-details").attr("data-id");
	var icon = jQuery(_button).find("i").attr("class");
	jQuery(_button).find("i").attr("class", "fas fa-spinner fa-spin");
	var post_data = { "action": "leform-record-field-remove", "record-id": record_id, "field-id": field_id };
	jQuery.ajax({
		type: "POST",
		url: leform_ajax_handler,
		data: post_data,
		success: function (return_data) {
			try {
				var data;
				if (typeof return_data == 'object') data = return_data;
				else data = jQuery.parseJSON(return_data);
				if (data.status == "OK") {
					jQuery(_object).closest("tr").fadeOut(300, function () {
						jQuery(_object).closest("tr").remove();
					});
					leform_global_message_show("success", data.message);
				} else if (data.status == "ERROR") {
					leform_global_message_show("danger", data.message);
				} else {
					leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
				}
			} catch (error) {
				console.log(error);
				leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
			}
			jQuery(_button).find("i").attr("class", icon);
			leform_sending = false;
			leform_dialog_close();
		},
		error: function (XMLHttpRequest, textStatus, errorThrown) {
			jQuery(_button).find("i").attr("class", icon);
			leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
			leform_sending = false;
			leform_dialog_close();
		}
	});
	return false;
}

function leform_record_field_load_editor(_button) {
	if (leform_sending) return false;
	leform_sending = true;
	var field_id = jQuery(_button).closest(".leform-record-details-table-value").attr("data-id");
	var record_id = jQuery(_button).closest(".leform-record-details").attr("data-id");
	var icon = jQuery(_button).find("i").attr("class");
	jQuery(_button).find("i").attr("class", "fas fa-spinner fa-spin");
	var post_data = { "action": "leform-record-field-load-editor", "record-id": record_id, "field-id": field_id };
	jQuery.ajax({
		type: "POST",
		url: leform_ajax_handler,
		data: post_data,
		success: function (return_data) {
			try {
				var data;
				if (typeof return_data == 'object') data = return_data;
				else data = jQuery.parseJSON(return_data);
				if (data.status == "OK") {
					jQuery(_button).closest(".leform-record-details-table-value").find(".leform-record-field-value").fadeOut(300, function () {
						jQuery(_button).closest(".leform-record-details-table-value").find(".leform-record-field-editor").html(data.html);
						jQuery(_button).closest(".leform-record-details-table-value").find(".leform-record-field-editor").fadeIn(300);
					});
				} else if (data.status == "ERROR") {
					leform_global_message_show("danger", data.message);
				} else {
					leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
				}
			} catch (error) {
				console.log(error);
				leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
			}
			jQuery(_button).find("i").attr("class", icon);
			leform_sending = false;
			leform_dialog_close();
		},
		error: function (XMLHttpRequest, textStatus, errorThrown) {
			jQuery(_button).find("i").attr("class", icon);
			leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
			leform_sending = false;
			leform_dialog_close();
		}
	});
	return false;
}

function leform_record_field_cancel_editor(_button) {
	jQuery(_button).closest(".leform-record-details-table-value").find(".leform-record-field-editor").fadeOut(300, function () {
		jQuery(_button).closest(".leform-record-details-table-value").find(".leform-record-field-value").fadeIn(300);
		jQuery(_button).closest(".leform-record-details-table-value").find(".leform-record-field-editor").html("");
	});
}

function leform_record_field_save(_button) {
	if (leform_sending) return false;
	leform_sending = true;
	var field_id = jQuery(_button).closest(".leform-record-details-table-value").attr("data-id");
	var record_id = jQuery(_button).closest(".leform-record-details").attr("data-id");
	var icon = jQuery(_button).find("i").attr("class");
	jQuery(_button).find("i").attr("class", "fas fa-spinner fa-spin");
	var post_data = { "action": "leform-record-field-save", "record-id": record_id, "field-id": field_id, "value": leform_encode64(jQuery(_button).closest(".leform-record-field-editor").find("textarea, input, select").serialize()) };
	jQuery.ajax({
		type: "POST",
		url: leform_ajax_handler,
		data: post_data,
		success: function (return_data) {
			try {
				var data;
				if (typeof return_data == 'object') data = return_data;
				else data = jQuery.parseJSON(return_data);
				if (data.status == "OK") {
					jQuery(_button).closest(".leform-record-details-table-value").find(".leform-record-field-editor").fadeOut(300, function () {
						jQuery(_button).closest(".leform-record-details-table-value").find(".leform-record-field-value").html(data.html);
						jQuery(_button).closest(".leform-record-details-table-value").find(".leform-record-field-value").fadeIn(300);
						jQuery(_button).closest(".leform-record-details-table-value").find(".leform-record-field-editor").html("");
					});
					leform_global_message_show("success", data.message);
				} else if (data.status == "ERROR") {
					leform_global_message_show("danger", data.message);
				} else {
					leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
				}
			} catch (error) {
				console.log(error);
				leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
			}
			jQuery(_button).find("i").attr("class", icon);
			leform_sending = false;
			leform_dialog_close();
		},
		error: function (XMLHttpRequest, textStatus, errorThrown) {
			jQuery(_button).find("i").attr("class", icon);
			leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
			leform_sending = false;
			leform_dialog_close();
		}
	});
	return false;
}

function leform_input_sort() {
	var input_fields = new Array();
	var fields = new Array();
	for (var i = 0; i < leform_form_pages.length; i++) {
		if (leform_form_pages[i] != null) {
			fields = _leform_input_sort(leform_form_pages[i]['id'], 0, leform_form_pages[i]['id'], leform_form_pages[i]['name']);
			if (fields.length > 0) input_fields = input_fields.concat(fields);
		}
	}
	return input_fields;
}
function _leform_input_sort(_parent, _parent_col, _page_id, _page_name) {
	var input_fields = new Array();
	var fields = new Array();
	var idxs = new Array();
	var seqs = new Array();
	for (var i = 0; i < leform_form_elements.length; i++) {
		if (leform_form_elements[i] == null) continue;
		if (leform_form_elements[i]["_parent"] == _parent && (leform_form_elements[i]["_parent-col"] == _parent_col || _parent == "")) {
			idxs.push(i);
			seqs.push(parseInt(leform_form_elements[i]["_seq"], 10));
		}
	}
	if (idxs.length == 0) return input_fields;
	var sorted;
	for (var i = 0; i < seqs.length; i++) {
		sorted = -1;
		for (var j = 0; j < seqs.length - 1; j++) {
			if (seqs[j] > seqs[j + 1]) {
				sorted = seqs[j];
				seqs[j] = seqs[j + 1];
				seqs[j + 1] = sorted;
				sorted = idxs[j];
				idxs[j] = idxs[j + 1];
				idxs[j + 1] = sorted;
			}
		}
		if (sorted == -1) break;
	}
	for (var k = 0; k < idxs.length; k++) {
		i = idxs[k];
		if (leform_form_elements[i] == null) continue;
		if (leform_toolbar_tools.hasOwnProperty(leform_form_elements[i]['type']) && leform_toolbar_tools[leform_form_elements[i]['type']]['type'] == 'input') {
			input_fields.push({ "id": leform_form_elements[i]['id'], "name": leform_form_elements[i]['name'], "page-id": _page_id, "page-name": _page_name });
		} else if (leform_form_elements[i]["type"] == "columns") {
			for (var j = 0; j < leform_form_elements[i]['_cols']; j++) {
				fields = _leform_input_sort(leform_form_elements[i]['id'], j, _page_id, _page_name);
				if (fields.length > 0) input_fields = input_fields.concat(fields);
			}
		}
	}
	return input_fields;
}

var leform_htmlform_connecting = false;
function leform_htmlform_connect(_object) {
	if (leform_htmlform_connecting) return false;
	jQuery(_object).find("i").attr("class", "fas fa-spinner fa-spin");
	jQuery(_object).addClass("leform-button-disabled");
	leform_htmlform_connecting = true;
	var post_data = { "action": "leform-htmlform-connect", "html": jQuery(_object).parent().find("textarea").val() };
	jQuery.ajax({
		type: "POST",
		url: leform_ajax_handler,
		data: post_data,
		success: function (return_data) {
			jQuery(_object).find("i").attr("class", "fas fa-random");
			jQuery(_object).removeClass("leform-button-disabled");
			try {
				var data = jQuery.parseJSON(return_data);
				if (data.status == "OK") {
					var container = jQuery(_object).closest(".leform-htmlform-form");
					jQuery(container).fadeOut(300, function () {
						jQuery(container).html(data.html);
						jQuery(container).fadeIn(300);
					});
					leform_global_message_show("success", data.message);
				} else if (data.status == "ERROR") {
					leform_global_message_show("danger", data.message);
				} else {
					leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
				}
			} catch (error) {
				leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
			}
			leform_htmlform_connecting = false;
		},
		error: function (XMLHttpRequest, textStatus, errorThrown) {
			jQuery(_object).find("i").attr("class", "fas fa-random");
			jQuery(_object).removeClass("leform-button-disabled");
			leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
			leform_htmlform_connecting = false;
		}
	});
	return false;
}
function leform_htmlform_disconnect(_object) {
	if (leform_htmlform_connecting) return false;
	jQuery(_object).find("i").attr("class", "fas fa-spinner fa-spin");
	jQuery(_object).addClass("leform-button-disabled");
	leform_htmlform_connecting = true;
	var post_data = { "action": "leform-htmlform-disconnect", "html": jQuery(_object).closest(".leform-htmlform-form").find("input[name='html']").val() };
	jQuery.ajax({
		type: "POST",
		url: leform_ajax_handler,
		data: post_data,
		success: function (return_data) {
			jQuery(_object).find("i").attr("class", "fas fa-times");
			jQuery(_object).removeClass("leform-button-disabled");
			try {
				var data = jQuery.parseJSON(return_data);
				if (data.status == "OK") {
					var container = jQuery(_object).closest(".leform-htmlform-form");
					jQuery(container).fadeOut(300, function () {
						jQuery(container).html(data.html);
						jQuery(container).fadeIn(300);
					});
					leform_global_message_show("success", data.message);
				} else if (data.status == "ERROR") {
					leform_global_message_show("danger", data.message);
				} else {
					leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
				}
			} catch (error) {
				leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
			}
			leform_htmlform_connecting = false;
		},
		error: function (XMLHttpRequest, textStatus, errorThrown) {
			jQuery(_object).find("i").attr("class", "fas fa-times");
			jQuery(_object).removeClass("leform-button-disabled");
			leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
			leform_htmlform_connecting = false;
		}
	});
	return false;
}

var leform_gettingstarted_steps = {};
function leform_gettingstarted(_screen, _step) {
	var screen_cookie = leform_read_cookie("leform-gettingstarted-" + _screen);
	if (screen_cookie == "off") return;
	if (jQuery(".leform-gettingstarted-overlay").length < 1) {
		jQuery("body").append("<div class='leform-gettingstarted-overlay'></div>");
		jQuery(".leform-gettingstarted-overlay").fadeIn(1000);
	}
	if (leform_gettingstarted_steps.hasOwnProperty(_screen) && _step < leform_gettingstarted_steps[_screen].length) {
		jQuery(".leform-gettingstarted-highlight").removeClass("leform-gettingstarted-highlight");
		jQuery(".leform-gettingstarted-bubble").remove();

		jQuery(leform_gettingstarted_steps[_screen][_step]["selector"]).addClass("leform-gettingstarted-highlight");
		var html = "<div class='leform-gettingstarted-bubble leform-gettingstarted-bubble-" + leform_gettingstarted_steps[_screen][_step]["class"] + "' style='" + leform_gettingstarted_steps[_screen][_step]["style"] + "'><p>" + leform_gettingstarted_steps[_screen][_step]["text"] + "</p><span onclick=\"leform_gettingstarted('" + _screen + "', " + (_step + 1) + ");\">Got it!</span></div>";
		jQuery(".leform-gettingstarted-highlight").append(html);
		jQuery(".leform-gettingstarted-bubble").fadeIn(300);
	} else {
		jQuery(".leform-gettingstarted-overlay").fadeOut(300, function () {
			jQuery(".leform-gettingstarted-overlay").remove();
		});
		jQuery(".leform-gettingstarted-bubble").fadeOut(300, function () {
			jQuery(".leform-gettingstarted-bubble").remove();
		});
		jQuery(".leform-gettingstarted-highlight").removeClass("leform-gettingstarted-highlight");
		leform_write_cookie("leform-gettingstarted-" + _screen, "off", 365);
	}
}

function leform_email_validator_changed(_object) {
	var value = jQuery(_object).val();
	jQuery(".leform-email-validator-options").hide();
	jQuery(".leform-email-validator-" + value).fadeIn(200);
	return false;
}

var leform_global_message_timer;
function leform_global_message_show(_type, _message) {
	clearTimeout(leform_global_message_timer);
	jQuery("#leform-global-message").fadeOut(300, function () {
		jQuery("#leform-global-message").attr("class", "");
		jQuery("#leform-global-message").addClass("leform-global-message-" + _type).html(_message);
		jQuery("#leform-global-message").fadeIn(300);
		leform_global_message_timer = setTimeout(function () { jQuery("#leform-global-message").fadeOut(300); }, 5000);
	});
}

function leform_escape_html(_text) {
	if (typeof _text != typeof "string") return _text;
	if (!_text) return "";
	var map = {
		'&': '&amp;',
		'<': '&lt;',
		'>': '&gt;',
		'"': '&quot;',
		"'": '&#039;'
	};
	return _text.replace(/[&<>"']/g, function (m) { return map[m]; });
}
function leform_is_numeric(_text) {
	return !isNaN(parseInt(_text)) && isFinite(_text);
}
function leform_random_string(_length) {
	var length, text = "";
	var possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
	if (typeof _length == "undefined") length = 16;
	else length = _length;
	for (var i = 0; i < length; i++) text += possible.charAt(Math.floor(Math.random() * possible.length));
	return text;
}

function leform_utf8encode(string) {
	string = string.replace(/\x0d\x0a/g, "\x0a");
	var output = "";
	for (var n = 0; n < string.length; n++) {
		var c = string.charCodeAt(n);
		if (c < 128) {
			output += String.fromCharCode(c);
		} else if ((c > 127) && (c < 2048)) {
			output += String.fromCharCode((c >> 6) | 192);
			output += String.fromCharCode((c & 63) | 128);
		} else {
			output += String.fromCharCode((c >> 12) | 224);
			output += String.fromCharCode(((c >> 6) & 63) | 128);
			output += String.fromCharCode((c & 63) | 128);
		}
	}
	return output;
}
function leform_encode64(input) {
	var keyString = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
	var output = "";
	var chr1, chr2, chr3, enc1, enc2, enc3, enc4;
	var i = 0;
	input = leform_utf8encode(input);
	while (i < input.length) {
		chr1 = input.charCodeAt(i++);
		chr2 = input.charCodeAt(i++);
		chr3 = input.charCodeAt(i++);
		enc1 = chr1 >> 2;
		enc2 = ((chr1 & 3) << 4) | (chr2 >> 4);
		enc3 = ((chr2 & 15) << 2) | (chr3 >> 6);
		enc4 = chr3 & 63;
		if (isNaN(chr2)) {
			enc3 = enc4 = 64;
		} else if (isNaN(chr3)) {
			enc4 = 64;
		}
		output = output + keyString.charAt(enc1) + keyString.charAt(enc2) + keyString.charAt(enc3) + keyString.charAt(enc4);
	}
	return output;
}
function leform_utf8decode(input) {
	var string = "";
	var i = 0;
	var c = 0, c1 = 0, c2 = 0;
	while (i < input.length) {
		c = input.charCodeAt(i);
		if (c < 128) {
			string += String.fromCharCode(c);
			i++;
		} else if ((c > 191) && (c < 224)) {
			c2 = input.charCodeAt(i + 1);
			string += String.fromCharCode(((c & 31) << 6) | (c2 & 63));
			i += 2;
		} else {
			c2 = input.charCodeAt(i + 1);
			c3 = input.charCodeAt(i + 2);
			string += String.fromCharCode(((c & 15) << 12) | ((c2 & 63) << 6) | (c3 & 63));
			i += 3;
		}
	}
	return string;
}
function leform_decode64(input) {
	var keyString = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
	var output = "";
	var chr1, chr2, chr3;
	var enc1, enc2, enc3, enc4;
	var i = 0;
	input = input.replace(/[^A-Za-z0-9\+\/\=]/g, "");
	while (i < input.length) {
		enc1 = keyString.indexOf(input.charAt(i++));
		enc2 = keyString.indexOf(input.charAt(i++));
		enc3 = keyString.indexOf(input.charAt(i++));
		enc4 = keyString.indexOf(input.charAt(i++));
		chr1 = (enc1 << 2) | (enc2 >> 4);
		chr2 = ((enc2 & 15) << 4) | (enc3 >> 2);
		chr3 = ((enc3 & 3) << 6) | enc4;
		output = output + String.fromCharCode(chr1);
		if (enc3 != 64) {
			output = output + String.fromCharCode(chr2);
		}
		if (enc4 != 64) {
			output = output + String.fromCharCode(chr3);
		}
	}
	output = leform_utf8decode(output);
	return output;
}
function leform_esc_html__(_string) {
	var string;
	if (typeof leform_translations == typeof {} && leform_translations.hasOwnProperty(_string)) {
		string = leform_translations[_string];
		if (string.length == 0) string = _string;
	} else string = _string;
	return leform_escape_html(string);
}
function leform_read_cookie(key) {
	var pairs = document.cookie.split("; ");
	for (var i = 0, pair; pair = pairs[i] && pairs[i].split("="); i++) {
		if (pair[0] === key) return pair[1] || "";
	}
	return null;
}
function leform_write_cookie(key, value, days) {
	if (days) {
		var date = new Date();
		date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
		var expires = "; expires=" + date.toGMTString();
	} else var expires = "";
	document.cookie = key + "=" + value + expires + "; path=/";
}
function getFormFields() {
    return leform_form_elements.filter((element) =>
        element !== null
        && leform_toolbar_tools.hasOwnProperty(element['type'])
        && leform_toolbar_tools[element['type']]['type'] == 'input'
        && element["type"] !== "repeater-input"
    );
}
function renderFormFieldsShortcodeMenu() {
    const formValues = getFormFields()
        .map((element) => `
            <li
                class="px-3 py-1.5 cursor-pointer hover:bg-gray-200"
                data-code="{{form_${leform_escape_html(element['name'])}}}"
            >
                ${element['id']} | ${leform_escape_html(element['name'])}
            </li>
        `)
        .join("\n");

    return `
        <div class="form-fields-shortcode-menu">
            <span class="flex items-center px-3 h-full cursor-pointer shortcode-toggle">
                <span class="fas fa-code"></span>
            </span>

            <ul class="absolute bg-white rounded-md top-5 right-0 overflow-y-auto max-h-40 max-w-40 border-2 border-gray-300 z-20 hidden">
                ${formValues}
            </ul>
        </div>
    `;
}
