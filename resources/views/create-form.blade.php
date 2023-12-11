@extends('layouts.forms')

@section('custom-head')
    <link rel="stylesheet" href="{{ asset('css/fontawesome-all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/jquery-ui.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/color-picker.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">

    <link rel="stylesheet" id="leform" href="{{ asset("css/halfdata-plugin/admin.css") }}">
    <link rel="stylesheet" id="leform-front" href="{{ asset("css/halfdata-plugin/style.css") }}">
    <link rel="stylesheet" id="tooltipster" href="{{ asset("css/halfdata-plugin/tooltipster.bundle.min.css") }}">
    <link rel="stylesheet" id="leform-fa" href="{{ asset("css/halfdata-plugin/leform-fa.css") }}">
    <link rel="stylesheet" id="leform-if" href="{{ asset("css/halfdata-plugin/leform-if.css") }}">
    <link rel="stylesheet" id="font-awesome-5.7.2" href="{{ asset("css/halfdata-plugin/fontawesome-all.min.css") }}">
    <link rel="stylesheet" id="material-icons-3.0.1" href="{{ asset("css/halfdata-plugin/material-icons.css") }}">






    <link href="https://fonts.googleapis.com/css2?family=Material+Icons" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Icons+Outlined" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Icons+Round" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Icons+Sharp" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Icons+Two+Tone" rel="stylesheet">






    <link rel="stylesheet" id="airdatepicker" href="{{ asset("css/halfdata-plugin/airdatepicker.css") }}">
    <link rel="stylesheet" id="minicolors" href="{{ asset("css/halfdata-plugin/jquery.minicolors.css") }}">
    <link rel="stylesheet" id="rangeSlider" href="{{ asset("css/halfdata-plugin/ion.rangeSlider.css") }}">

    <style id="custom-css">{!! $customCss !!}</style>

    <script>
        let wpColorPickerL10n = {
            "clear": "Clear",
            "defaultString": "Default",
            "pick": "Select Color",
            "current": "Current Color",
        };
    </script>

    <script>
        const repeatableInputFieldSettingsConfig = {
            "text": ["placeholder", "defaultValue"],
            "email": ["placeholder", "defaultValue"],
            "password": ["placeholder", "defaultValue"],
            "number": ["placeholder", "defaultValue"],
            "select": ["placeholder", "options"],
            "date": ["defaultValue"],
            "time": ["defaultValue"],
            "rangeslider": ["defaultValue", "min", "max"],
            "star-rating": ["starCount"],
            "link-button": ["buttonText", "href"],
            "html": ["content"],
        };

        const repeatableInputSettingFields = Object
            .entries(repeatableInputFieldSettingsConfig)
            .reduce((allSettings, [field, settings]) => {
                settings.forEach((setting) => {
                    if (allSettings[setting]) {
                        allSettings[setting].push(field);
                    } else {
                        allSettings[setting] = [field];
                    }
                });
                return allSettings;
            }, {});

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

        function renderSelectOption(
            isNew = false,
            isChecked = false,
            option = "",
        ) {
            const checkedAttribute = isChecked ? "checked" : "";
            return `
                <div class="mb-1 option flex items-center">
                    <input
                        type="text"
                        ${!isNew ? `value="${option}"` : ""}
                    />
                    <input
                        class="ml-2"
                        type="radio"
                        name="select-option"
                        ${checkedAttribute}
                    />
                    <button
                        type="button"
                        class="ml-2 remove-option"
                    >
                        x
                    </button>
                </div>
            `;
        }

        function renderRepeatableInputFieldRow(field = null) {
            const isNew = (field === null);
            function choseDisplayStatus(setting) {
                if (isNew) {
                    return "hidden";
                }
                if (repeatableInputSettingFields[setting].includes(field.type)) {
                    return "";
                } else {
                    return "hidden";
                }
            }

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
                            data-code="`
                            + "{" + "{" + element['id'] + "|" + leform_escape_html(element['name']) + "}" + "}" + `"
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
                        data-code="`
                        + "{" + "{" + expression['id'] + "|" + leform_escape_html(label) + "}" + "}" + `"
                    >
                        ${expression['id']} | ${leform_escape_html(expression['name'])}
                    </li>
                `;
            }

            return `
                <form class="flex field mb-3" style="padding-left: 0px;">
                    <div class="w-full mr-2 type-select">
                        <label>${leform_esc_html__("Type")}</label>
                        <select name="type">
                            <option value="" selected disabled>
                                ${leform_esc_html__("Type")}
                            </option>
                            ${inputOptions.map(([value, label]) => `
                                <option
                                    value="${value}"
                                    ${(!isNew && (value === field.type))
                                        ? "selected"
                                        : ""
                                    }
                                >
                                    ${leform_esc_html__(label)}
                                </option>
                            `).join("\n")}
                        </select>
                    </div>

                    <div
                        class="w-full mr-2 ${(isNew) ? "hidden" : ""}"
                        data-setting="name"
                        data-input-type="text"
                    >
                        <label>${leform_esc_html__("Name")}</label>
                        <input
                            type="text"
                            ${(!isNew && field?.name)
                                ? `value="${field.name}"`
                                : ""
                            }
                        />
                    </div>

                    <div
                        class="w-full mr-2 ${choseDisplayStatus("placeholder")}"
                        data-setting="placeholder"
                        data-input-type="text"
                    >
                        <label>${leform_esc_html__("Placeholder")}</label>
                        <input
                            type="text"
                            ${(!isNew && field?.placeholder)
                                ? `value="${field.placeholder}"`
                                : ""
                            }
                        />
                    </div>

                    <div
                        class="w-full mr-2 ${choseDisplayStatus("defaultValue")}"
                        data-setting="defaultValue"
                        data-input-type="text"
                    >
                        <label>${leform_esc_html__("Default value")}</label>
                        <input
                            type="text"
                            ${(!isNew && field?.defaultValue)
                                ? `value="${field.defaultValue}"`
                                : ""
                            }
                        />
                    </div>

                    <div
                        class="w-full mr-2 ${choseDisplayStatus("options")}"
                        data-setting="options"
                        data-input-type="options"
                    >
                        <button
                            type="button"
                            class="add-option"
                        >
                            +
                        </button>
                        <label>${leform_esc_html__("Options")}</label>
                        ${(!isNew && field.options)
                            ? field
                                .options
                                .map((option, index) => renderSelectOption(
                                    false,
                                    (field.defaultValue === index),
                                    option,
                                ))
                                .join("")
                            : ''
                        }
                    </div>

                    <div
                        class="w-full mr-2 ${choseDisplayStatus("buttonText")}"
                        data-setting="buttonText"
                        data-input-type="text"
                    >
                        <label>${leform_esc_html__("Text")}</label>
                        <input
                            type="text"
                            ${(!isNew && field?.buttonText)
                                ? `value="${field.buttonText}"`
                                : ""
                            }
                        />
                    </div>

                    <div
                        class="w-full mr-2 ${choseDisplayStatus("href")}"
                        data-setting="href"
                        data-input-type="text"
                    >
                        <label>${leform_esc_html__("Href")}</label>
                        <input
                            type="text"
                            ${(!isNew && field?.href)
                                ? `value="${field.href}"`
                                : ""
                            }
                        />
                    </div>

                    <div
                        class="w-full mr-2 ${choseDisplayStatus("min")}"
                        data-setting="min"
                        data-input-type="text"
                    >
                        <label>${leform_esc_html__("Min")}</label>
                        <input
                            type="text"
                            ${(!isNew && field?.min)
                                ? `value="${field.min}"`
                                : "0"
                            }
                        />
                    </div>

                    <div
                        class="w-full mr-2 ${choseDisplayStatus("max")}"
                        data-setting="max"
                        data-input-type="text"
                    >
                        <label>${leform_esc_html__("Max")}</label>
                        <input
                            type="text"
                            ${(!isNew && field?.max)
                                ? `value="${field.max}"`
                                : "100"
                            }
                        />
                    </div>

                    <div
                        class="w-full mr-2 ${choseDisplayStatus("starCount")}"
                        data-setting="starCount"
                        data-input-type="starCount"
                    >
                        <label>${leform_esc_html__("Star count")}</label>
                        <select>
                            ${new Array(8).fill(null).map((_, i) => `
                                <option ${
                                    (!isNew && ((i + 3).toString() === field.starCount))
                                        ? "selected='selected'"
                                        : ""
                                }>
                                    ${i + 3}
                                </option>
                            `).join("")}
                        </select>
                    </div>

                    <div
                        class="w-full mr-2 ${choseDisplayStatus("content")} relative"
                        data-setting="content"
                        data-input-type="textarea"
                    >
                        <label>${leform_esc_html__("Content")}</label>
                        <textarea class='repeater-input-content'>${
                            (!isNew && field.content)
                                ? field.content
                                : ''
                        }</textarea>
                        <div class="shortcode-menu absolute">
                            <span
                                class="absolute rounded-md flex items-center px-3 h-full cursor-pointer shortcode-toggle h-10 bg-gray-200 border-2 border-gray-300"
                                style="bottom: 10px; left: -44px; height: 38px;"
                            >
                                <span class="fas fa-code"></span>
                            </span>

                            <ul class="absolute bg-white rounded-md right-0 overflow-y-auto max-h-40 max-w-40 border-2 border-gray-300 hidden bottom-0">
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

                    <div class="px-3">
                        <button
                            type="button"
                            class="remove-field-button"
                        >
                            x
                        </button>
                    </div>
                </form>
            `;
        }
    </script>

    <script>
        function getNewExpressionId() {
            const expressions = document
                .querySelectorAll(".repeater-input-math-expressions .expression");
            let biggestId = 0;

            for (const expression of expressions) {
                let id = expression
                    .querySelector("[name='id']")
                    .value;
                id = parseInt(id);

                if (id > biggestId) {
                    biggestId = id;
                }
            }

            return biggestId + 1;
        }

        function getActiveElementProperties() {
            if (leform_element_properties_active === null) {
                return {};
            }

            let elementProperties = {};
            const elementIndex = leform_element_properties_active
                .id
                .match(/(?!=leform-element-)\d+/)
                [0];

            if (leform_form_elements && leform_form_elements[elementIndex]) {
                elementProperties = leform_form_elements[elementIndex];
            } else {
                elementProperties = {};
            }

            return elementProperties;
        }

        function renderRepeatableInputExpressionRow(expression) {
            const id = expression
                ? expression.id
                : getNewExpressionId();

            const elementProperties = getActiveElementProperties();
            const fields = elementProperties.fields;

            return `
                <div
                    class="expression mb-3"
                    style="padding: 0px !important"
                >
                    <div class="px-10 py-6 bg-gray-200 rounded-md">
                        <div class="flex justify-end">
                            <button class="remove-expression-button">
                                x
                            </button>
                        </div>
                        <div class="grid grid-cols-4 mb-3">
                            <label class="col-span-1 flex items-center">
                                ${leform_esc_html__("Id")}
                            </label>
                            <input
                                name="id"
                                class="col-span-3"
                                type="text"
                                value="${id}"
                                disabled
                            />
                        </div>
                        <div class="grid grid-cols-4 mb-3">
                            <label class="col-span-1 flex items-center">
                                ${leform_esc_html__("Name")}
                            </label>
                            <input
                                name="name"
                                class="col-span-3"
                                type="text"
                                ${expression ? `value="${expression.name}"` : ""}
                            />
                        </div>
                        <div class="grid grid-cols-4 mb-3">
                            <label class="col-span-1 flex items-center">
                                ${leform_esc_html__("Expression")}
                            </label>
                            <div class="flex col-span-3">
                                <input
                                    name="expression"
                                    class=""
                                    type="text"
                                    ${expression ? `value="${expression.expression}"` : ""}
                                />
                                <div class="shortcode-menu">
                                    <span class="flex items-center px-3 h-full cursor-pointer shortcode-toggle">
                                        <span class="fas fa-code"></span>
                                    </span>

                                    <ul
                                        class="absolute bg-white rounded-md right-0 overflow-y-auto w-36 max-h-36 border-2 border-gray-300 hidden"
                                        style="margin-top: -40px;"
                                    >
                                        ${fields.map((field, index) => `
                                            <li
                                                class="px-3 py-1.5 cursor-pointer hover:bg-gray-200"
                                                data-code="[[${index + 1}|${field.name}]]"
                                            >
                                                ${index + 1} | ${field.name}
                                            </li>
                                        `).join("")}
                                    </ul>
                                </div>
                            </div>
                        </div>
                        <div class="grid grid-cols-4 mb-3">
                            <label class="col-span-1 flex items-center">
                                ${leform_esc_html__("Default")}
                            </label>
                            <input
                                name="default"
                                class="col-span-3"
                                type="text"
                                ${expression ? `value="${expression.default}"` : ""}
                            />
                        </div>
                        <div class="grid grid-cols-4">
                            <label class="col-span-1 flex items-center">
                                ${leform_esc_html__("Decimal digits")}
                            </label>
                            <select name="decimal-digits" class="col-span-3">
                            ${new Array(9).fill(null).map((_, i) => `
                                <option
                                    value="${i}"
                                    ${(
                                        expression
                                        && expression.decimalDigits
                                        && (i === parseInt(expression.decimalDigits))
                                    ) ? "selected" : ""}
                                >
                                    ${i}
                                </option>
                            `)}
                            </select>
                        </div>
                    </div>
                </div>
            `;
        }
    </script>

    <script src="{{ asset("js/jquery.min.js") }}"></script>
    <script src="{{ asset("js/jquery-ui.min.js") }}"></script>
    <script src="{{ asset("js/iris.min.js") }}"></script>
    <script src="{{ asset("js/color-picker.min.js") }}"></script>
    <script src="{{ asset("js/admin.js") }}"></script>

    <script id="" src="{{ asset("js/qrcode.min.js") }}"></script>
    <script id="leform" src="{{ asset("js/halfdata-plugin/admin.js") }}"></script>
    <script id="tooltipster" src="{{ asset("js/halfdata-plugin/tooltipster.bundle.min.js") }}"></script>
    <script id="airdatepicker" src="{{ asset("js/halfdata-plugin/airdatepicker.js") }}"></script>
    <script id="chart" src="{{ asset("js/halfdata-plugin/chart.min.js") }}"></script>
    <script id="jquery.mask" src="{{ asset("js/halfdata-plugin/jquery.mask.min.js") }}"></script>
    <script id="minicolors" src="{{ asset("js/halfdata-plugin/jquery.minicolors.js") }}"></script>
    <script id="rangeSlider" src="{{ asset("js/halfdata-plugin/ion.rangeSlider.js") }}"></script>
    <script id="jquery.alphanum" src="{{ asset("js/halfdata-plugin/jquery.alphanum.js") }}"></script>

    <script>
        let ajax_handler = "#";
    </script>
    <script>
        let leform_uap_core = true
        let leform_ajax_handler = "/";
        let leform_plugin_url = "/";
        let leform_forms_encoded = "[]";
        let leform_gettingstarted_enable = "false";
        let leform_gettingsstarted_encoded = "[]";
        // let leform_gettingstarted_steps = JSON.parse(leform_decode64(leform_gettingsstarted_encoded));
    </script>

    <!-- custom save functionality -->
    <script>
        function leform_more_using_open(_object) {
            jQuery("#leform-more-using .leform-admin-popup-content-form").html("");
            var window_height = 2*parseInt((jQuery(window).height() - 100)/2, 10);
            var window_width = Math.min(Math.max(2*parseInt((jQuery(window).width() - 300)/2, 10), 640), 840);
            jQuery("#leform-more-using").height(window_height);
            jQuery("#leform-more-using").width(window_width);
            jQuery("#leform-more-using .leform-admin-popup-inner").height(window_height);
            jQuery("#leform-more-using .leform-admin-popup-content").height(window_height - 52);
            jQuery("#leform-more-using-overlay").fadeIn(300);
            jQuery("#leform-more-using").fadeIn(300);
            jQuery("#leform-more-using .leform-admin-popup-title h3 span").html("");
            jQuery("#leform-more-using .leform-admin-popup-loading").show();
            leform_more_active = jQuery(_object).attr("data-id");
            var post_data = {
                "_token"  : "{{ csrf_token() }}",
                "action"  : "leform-using",
                "form-id" : leform_more_active,
            };
            jQuery.ajax({
                type	: "POST",
                url		: "{{ route('use-form') }}",
                data	: post_data,
                success	: function(return_data) {
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
                    } catch(error) {
                        leform_more_using_close();
                        leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
                    }
                },
                error	: function(XMLHttpRequest, textStatus, errorThrown) {
                    leform_more_using_close();
                    leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
                }
            });

            return false;
        }

        function custom_leform_save(_object) {
            if (leform_sending) return false;
            leform_sending = true;
            jQuery(_object).find("i").attr("class", "fas fa-spinner fa-spin");
            var post_pages = new Array();
            jQuery(".leform-pages-bar-item, .leform-pages-bar-item-confirmation").each(function(){
              var page_id = jQuery(this).attr("data-id");
              for (var i=0; i<leform_form_pages.length; i++) {
                if (leform_form_pages[i] != null && leform_form_pages[i]['id'] == page_id) {
                  post_pages.push(leform_encode64(JSON.stringify(leform_form_pages[i])));
                  break;
                }
              }
            });
            var post_elements = new Array();
            for (var i=0; i<leform_form_elements.length; i++) {
              if (jQuery("#leform-element-"+i).length && leform_form_elements[i] != null) post_elements.push(leform_encode64(JSON.stringify(leform_form_elements[i])));
            }

            const urlSearchParams = new URLSearchParams(window.location.search);
            const params = Object.fromEntries(urlSearchParams.entries());
            var post_data = {
                "action" : "leform-form-save",
                "form-id" : jQuery("#leform-id").val(),
                "form-options" : leform_encode64(JSON.stringify(leform_form_options)),
                "form-pages" : post_pages,
                "form-elements" : post_elements,
                "_token": "{{ csrf_token() }}",
            };
            if (params.folder) {
                post_data.folder_id = params.folder;
            }
            console.log(params, post_data);
            jQuery.ajax({
              type	: "POST",
              url		: '{{ route('store-form') }}',
              data	: post_data,
              success	: function(return_data) {
                try {
                  var data;
                  if (typeof return_data == 'object') data = return_data;
                  else data = jQuery.parseJSON(return_data);
                  if (data.status == "OK") {
                    leform_form_changed = false;
                    jQuery("#leform-id").val(data.form_id);
                    var url = window.location.href;
                    if (url.indexOf("&id=") < 0) {
                      history.pushState(null, null, url+"&id="+data.form_id);
                      if (leform_gettingstarted_enable == "on") {
                          leform_gettingstarted("form-saved", 0);
                      }

                      function createLinkElement(title, link, id) {
                        return `
                            <div
                                id="${id}"
                                class="inline-block mr-4 py-3 px-4 bg-white"
                            >
                                <h2 class="m-0">${title}</h2>

                                <a href="${link}" target="_blank">
                                    ${link}
                                </a>
                            </div>
                        `;
                      }

                      let parentComponent = jQuery('.wrap.leform-admin.leform-admin-editor').parent()[0];
                      jQuery(createLinkElement(
                          "{{ __('Public url:') }}",
                          data.long_link,
                          "public-url"
                      )).appendTo(parentComponent);
                      jQuery(createLinkElement(
                          "{{ __('Short url:') }}",
                          data.short_link,
                          "short-url"
                      )).appendTo(parentComponent);
                    } else {
                        const publicUrlElement = document.querySelector("#public-url a");
                        if (publicUrlElement) {
                            publicUrlElement.href = data.long_link;
                            publicUrlElement.textContent = data.long_link;
                        }
                        const shortUrlElement = document.querySelector("#short-url a");
                        if (shortUrlElement) {
                            shortUrlElement.href = data.short_link;
                            shortUrlElement.textContent = data.short_link;
                        }
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
                } catch(error) {
                  console.log(error);
                  leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
                }
                jQuery(_object).find("i").attr("class", "far fa-save");
                leform_sending = false;
              },
              error	: function(XMLHttpRequest, textStatus, errorThrown) {
                jQuery(_object).find("i").attr("class", "far fa-save");
                leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
                leform_sending = false;
              }
            });
            return false;
        }

        function custom_leform_preview(_object) {
            if (leform_sending) return false;
            leform_sending = true;
            jQuery(_object).find("i").attr("class", "fas fa-spinner fa-spin");
            var post_pages = new Array();
            jQuery(".leform-pages-bar-item, .leform-pages-bar-item-confirmation").each(function(){
              var page_id = jQuery(this).attr("data-id");
              for (var i=0; i<leform_form_pages.length; i++) {
                if (leform_form_pages[i] != null && leform_form_pages[i]['id'] == page_id) {
                  post_pages.push(leform_encode64(JSON.stringify(leform_form_pages[i])));
                  break;
                }
              }
            });
            var post_elements = new Array();
            for (var i=0; i<leform_form_elements.length; i++) {
              if (jQuery("#leform-element-"+i).length && leform_form_elements[i] != null) post_elements.push(leform_encode64(JSON.stringify(leform_form_elements[i])));
            }
            var post_data = {
              "action" : "leform-form-preview",
              "form-id" : jQuery("#leform-id").val(),
              "form-options" : leform_encode64(JSON.stringify(leform_form_options)),
              "form-pages" : post_pages,
              "form-elements" : post_elements,
              "_token": "{{ csrf_token() }}",
            };
            jQuery.ajax({
              type	: "POST",
              url		: '{{ route('store-form') }}',
              data	: post_data,
              success	: function(return_data) {
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
                } catch(error) {
                  console.log(error);
                  leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
                  jQuery(_object).find("i").attr("class", "far fa-eye");
                  leform_sending = false;
                }
              },
              error	: function(XMLHttpRequest, textStatus, errorThrown) {
                jQuery(_object).find("i").attr("class", "far fa-eye");
                leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
                leform_sending = false;
              }
            });
            return false;
        }

        function addThemeCssToElements(key, options) {
            const styleElementType = key.split("-css-styles")[0];
            leform_form_elements
                .filter((element) => element.type === styleElementType)
                .forEach((element) => {
                    element.css = element.css
                        .filter((css) => css.type !== "theme-css");
                    options[key].forEach((css) => {
                        element.css.push({
                            ...css,
                            type: "theme-css"
                        });
                    })
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

            var post_data = {
                "action" : "leform-style-load",
                "id" : _style_id,
                "_token": "{{ csrf_token() }}",
            };
            jQuery.ajax({
                type	: "POST",
                url		: "{{ route('admin-style-load') }}",
                data	: post_data,
                success	: function(return_data) {
                    try {
                        var data;
                        if (typeof return_data == 'object') data = return_data;
                        else data = jQuery.parseJSON(return_data);
                        if (data.status == "OK") {
                            jQuery(".leform-color").minicolors("destroy");
                            for (var key in data.options) {
                                if (data.options.hasOwnProperty(key)) {
                                    const elementCustomCssStyles = Object
                                        .keys(leform_toolbar_tools)
                                        .map((key) => key + "-css-styles");

                                    if (elementCustomCssStyles.includes(key)) {
                                        addThemeCssToElements(key, data.options);
                                    }
                                    input = jQuery("[name='leform-"+key+"']");
                                    if (input.length == 0) {
                                        continue;
                                    }
                                    key2 = jQuery(input[0]).closest(".leform-properties-item").attr("data-id");
                                    if (typeof type == typeof undefined) {
                                        continue;
                                    }
                                    if (
                                        (leform_meta["settings"][key2]).hasOwnProperty('group')
                                        && leform_meta["settings"][key2]['group'] == 'style'
                                    ) {
                                        jQuery(input).each(function() {
                                            var input_type = jQuery(this).attr("type");
                                            var input_value = jQuery(this).val();
                                            if (typeof input_type !== typeof undefined) {
                                                if (input_type == "radio") {
                                                    if (input_value == (data.options)[key]) {
                                                        jQuery(this).prop("checked", true);
                                                    }
                                                    else jQuery(this).prop("checked", false);
                                                } else if (input_type == "checkbox") {
                                                    if ((data.options)[key] == "on") {
                                                        jQuery(this).prop("checked", true);
                                                    } else {
                                                        jQuery(this).prop("checked", false);
                                                    }
                                                } else {
                                                    jQuery(this).val((data.options)[key]);
                                                }
                                            } else {
                                                jQuery(this).val((data.options)[key]);
                                            }
                                        });
                                    }
                                }
                            }
                            jQuery(".leform-color").minicolors({
                                format: 'rgb',
                                opacity: true,
                                change: function(value, opacity) {
                                    leform_properties_change();
                                }
                            });
                            leform_global_message_show("success", data.message);
                        } else if (data.status == "ERROR") {
                            leform_global_message_show("danger", data.message);
                        } else {
                            leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
                        }
                    } catch(error) {
                        console.log(error);
                        leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
                    }
                    jQuery(_object).find("i").attr("class", icon);
                    leform_sending = false;
                    leform_dialog_close();
                },
                error	: function(XMLHttpRequest, textStatus, errorThrown) {
                    jQuery(_object).find("i").attr("class", icon);
                    leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
                    leform_sending = false;
                    leform_dialog_close();
                }
            });
            return false;
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
                    input = jQuery("[name='leform-"+key+"']");
                    if (input.length == 0) continue;
                    key2 = jQuery(input[0]).closest(".leform-properties-item").attr("data-id");
                    if (typeof type == typeof undefined) continue;
                    if ((leform_meta["settings"][key2]).hasOwnProperty('group') && leform_meta["settings"][key2]['group'] == 'style') {
                        if (input.length > 1) {
                            jQuery(input).each(function(){
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
            var post_data = {
                "action" : "leform-style-save",
                "id" : jQuery("#leform-style-id").val(),
                "name" : leform_encode64(jQuery("#leform-style-name").val()),
                "options" : leform_encode64(JSON.stringify(style_options)),
                "form-name" : leform_encode64(leform_form_options['name']),
                "_token": "{{ csrf_token() }}",
            };
            jQuery.ajax({
                type	: "POST",
                url		: "{{ route('admin-style-save') }}",
                data	: post_data,
                success	: function(return_data) {
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
                    } catch(error) {
                        console.log(error);
                        leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
                    }
                    jQuery(_object).find("i").attr("class", icon);
                    leform_sending = false;
                    leform_dialog_close();
                },
                error	: function(XMLHttpRequest, textStatus, errorThrown) {
                    jQuery(_object).find("i").attr("class", icon);
                    leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
                    leform_sending = false;
                    leform_dialog_close();
                }
            });
            return false;
        }

        function _leform_stylemanager_rename(_object, _button, _style_id) {
            if (leform_sending) return false;
            leform_sending = true;
            var icon = jQuery(_button).find("i").attr("class");
            jQuery(_button).find("i").attr("class", "fas fa-spinner fa-spin");
            var post_data = {
                "action" : "leform-stylemanager-save",
                "style-id" : _style_id,
                "name" : leform_encode64(jQuery("#leform-style-name").val()),
                "_token": "{{ csrf_token() }}",
            };
            jQuery.ajax({
                type	: "POST",
                url		: "{{ route('rename-theme') }}",
                data	: post_data,
                success	: function(return_data) {
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
                    } catch(error) {
                        console.log(error);
                        leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
                    }
                    jQuery(_button).find("i").attr("class", icon);
                    leform_sending = false;
                    leform_dialog_close();
                },
                error	: function(XMLHttpRequest, textStatus, errorThrown) {
                    jQuery(_button).find("i").attr("class", icon);
                    leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
                    leform_sending = false;
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
            jQuery(_object).html("<i class='fas fa-spinner fa-spin'></i> "+doing_label);
            var post_data = {
                "action" : "leform-stylemanager-delete",
                "style-id" : style_id,
                "_token": "{{ csrf_token() }}",
            };
            jQuery.ajax({
                type	: "POST",
                url		: "{{ route('delete-theme') }}",
                data	: post_data,
                success	: function(return_data) {
                    try {
                        var data;
                        if (typeof return_data == 'object') data = return_data;
                        else data = jQuery.parseJSON(return_data);
                        if (data.status == "OK") {
                            jQuery(_object).closest("tr").fadeOut(300, function(){
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
                    } catch(error) {
                        leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
                    }
                    jQuery(_object).html(do_label);
                    leform_sending = false;
                },
                error	: function(XMLHttpRequest, textStatus, errorThrown) {
                    jQuery(_object).html(do_label);
                    leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
                    leform_sending = false;
                }
            });
            return false;
        }

        function leform_stylemanager_imported(_object) {
            if (jQuery(_object).attr("data-loading") != "true") return;
            jQuery(_object).attr("data-loading", "false");
            var return_data = jQuery(_object).contents().find("body pre").html();
            try {
                var data;
                if (typeof return_data == 'object') data = return_data;
                else data = jQuery.parseJSON(return_data);
                if (data.status == "OK") {
                    leform_styles.push({"id" : data.id, "name" : data.name, "type" : data.type});
                    var html = leform_styles_html();
                    jQuery(".leform-styles-select-container").html(html);
                    var row ="<tr><th>"+leform_escape_html(data.name)+"</th><td><div class='leform-table-list-actions'><span><i class='fas fa-ellipsis-v'></i></span><div class='leform-table-list-menu'><ul><li><a href='#' data-id='"+leform_escape_html(data.id)+"' onclick='return leform_stylemanager_rename(this);'>"+leform_esc_html__("Rename", "leform")+"</a></li><li><a href='?page=leform&leform-action=export-style&id="+leform_escape_html(data.id)+"' target='_blank'>"+leform_esc_html__("Export", "leform")+"</a></li><li class='leform-table-list-menu-line'></li><li><a href='#' data-id='"+leform_escape_html(data.id)+"' data-doing='"+leform_esc_html__("Deleting...", "leform")+"' onclick='return leform_stylemanager_delete(this);'>"+leform_esc_html__("Delete", "leform")+"</a></li></ul></div></div></td></tr>";
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
            } catch(error) {
                console.log(error);
                leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
            }
            return;
        }

        function leform_preview_loaded(_object) {
            if (jQuery(_object).attr("data-loading") != "true") return;
            jQuery(_object).attr("data-loading", "false");
            leform_preview_open();
            jQuery(".leform-header-preview").find("i").attr("class", "far fa-eye");
            leform_sending = false;

            let customStylesContainer = window.frames['leform-preview-iframe']
                .document
                .querySelector('head #custom-css')

            customStylesContainer.textContent = leform_form_options['custom-css'];

            return;
        }

        function addEventListenersToPdfBackgroundUploaders() {
            for (const form of document.querySelectorAll(".custom-pdf-background-select")) {
                const fileUploadElement = form.querySelector("input[type='file']");
                const valuePlaceholder = form.querySelector("input[type='hidden']");
                const uploadFileButton = form.querySelector("button[role='upload']");
                const removeFileButton = form.querySelector("button[role='close']");
                const dataKy = form.getAttribute('data-key');
                const previewContainer = document.getElementById(`form-pdf-background-container-${dataKy}`);
                const pdfPreview = document.getElementById(`form-pdf-background-preview-${dataKy}`);
                uploadFileButton.addEventListener("click", (e) => {
                    e.preventDefault();
                    fileUploadElement.click();
                });

                fileUploadElement.addEventListener("change", (e) => {
                    const file = e.target.files[0];
                    if (!file) {
                        return;
                    }

                    const data = new FormData();
                    data.append("form_id", jQuery("#leform-id").val());
                    data.append("file", file);
                    data.append("_token", "{{ csrf_token() }}");

                    fetch("{{ route('form-background-pdf-upload') }}", { method: "POST", body: data })
                        .then((response) => response.json())
                        .then((response) => {
                            valuePlaceholder.value = response.filename;
                            removeFileButton.classList.remove("f-hidden");
                            const filename = `/${response.filename.replace("public", "storage")}`
                            pdfPreview.innerHTML = `
                                <object data="${filename}" type="application/pdf" width="200px" height="150px">
                                    <embed src="${filename}" type="application/pdf"></embed>
                                </object>
                            `;
                            previewContainer.classList.remove('f-hidden');
                        })
                        .catch((error) => {
                            console.log(error);
                        });
                });

                removeFileButton.addEventListener("click", (e) => {
                    e.preventDefault();
                    fileUploadElement.value = "";
                    valuePlaceholder.value = "";
                    removeFileButton.classList.add("f-hidden");
                    pdfPreview.innerHTML = "";
                    previewContainer.classList.add('f-hidden');
                });
            }
        }

        function prepareRepeaterInputField(field, component) {
            field
                .querySelector("select[name='type']")
                .addEventListener("change", (e) => {
                    const fieldType = e.target.value;
                    const allFieldSettings = field
                        .querySelectorAll("div[data-setting]")
                    const visibleSettings = ["name"]
                        .concat(repeatableInputFieldSettingsConfig[fieldType]);

                    for (const fieldSetting of allFieldSettings) {
                        const isVisible = visibleSettings
                            .includes(fieldSetting.dataset.setting);
                        fieldSetting.classList.toggle("hidden", !isVisible);
                    }
                });

            field
                .querySelector(".remove-field-button")
                .addEventListener("click", () => {
                    component.removeChild(field)
                });

            const optionsContainer = field
                .querySelector("div[data-setting='options']");
            optionsContainer
                .querySelector("button.add-option")
                .addEventListener("click", () => {
                    const tempElement = document.createElement("div");
                    tempElement.innerHTML = renderSelectOption(true, false, "");
                    const newOption = tempElement.children[0];

                    newOption
                        .querySelector(".remove-option")
                        .addEventListener("click", (e) => {
                            optionsContainer.removeChild(newOption);
                        });

                    optionsContainer.appendChild(newOption);
                });

            function removeSelectOption(e) {
                optionsContainer.removeChild(e.target.parentElement);
            }

            Array
                .from(optionsContainer.querySelectorAll(".remove-option"))
                .forEach((element) =>
                    element.addEventListener("click", removeSelectOption)
                );

            const shortCodeMenu = field
                .querySelector(".shortcode-menu");
            const menu = shortCodeMenu.querySelector("ul");
            const menuItems = menu.querySelectorAll("li[data-code]");

            $(shortCodeMenu).hover(
                () => menu.classList.remove("hidden"),
                () => menu.classList.add("hidden"),
            );

            const htmlInput = field
                .querySelector("[data-setting='content'] textarea");
            for (const menuItem of menuItems) {
                menuItem.addEventListener("click", (e) => {
                    htmlInput.value += e.target.dataset.code;
                });
            }
        }

        function prepareRepeaterInputExpression(expression, component) {
            expression
                .querySelector(".remove-expression-button")
                .addEventListener("click", () => {
                    component.removeChild(expression);
                });

            const shortCodeMenu = expression
                .querySelector(".shortcode-menu");
            const menu = shortCodeMenu.querySelector("ul");
            const menuItems = menu.querySelectorAll("li[data-code]");

            $(shortCodeMenu).hover(
                () => menu.classList.remove("hidden"),
                () => menu.classList.add("hidden"),
            );

            const expressionInput = expression
                .querySelector("[name='expression']");
            for (const menuItem of menuItems) {
                menuItem.addEventListener("click", (e) => {
                    expressionInput.value += e.target.dataset.code;
                });
            }
        }

        function addEventListenersToRepeaterInputFields() {
            const component = document
                .querySelector(".repeater-input-fields");

            if (!component) {
                return;
            }
            const addField = component.querySelector(".add-field-button");
            const fields = component.querySelectorAll(".field");

            for (const field of fields) {
                prepareRepeaterInputField(field, component);
            }

            addField.addEventListener("click", (e) => {
                const tempElement = document.createElement("div");
                tempElement.innerHTML = renderRepeatableInputFieldRow();
                const newField = tempElement.children[0];
                component.appendChild(newField);

                prepareRepeaterInputField(newField, component);
            });
        }

        function addEventListenersToRepeaterInputExpressions() {
            const component = document
                .querySelector(".repeater-input-math-expressions");

            if (!component) {
                return;
            }
            const addField = component.querySelector(".add-expression-button");
            const expressions = component.querySelectorAll(".expression");

            for (const expression of expressions) {
                prepareRepeaterInputExpression(expression, component);
            }

            addField.addEventListener("click", (e) => {
                const tempElement = document.createElement("div");
                tempElement.innerHTML = renderRepeatableInputExpressionRow();
                const newExpression = tempElement.children[0];
                component.appendChild(newExpression);

                prepareRepeaterInputExpression(newExpression, component);
            });
        }

        function addEventListenersToAfterSubmitEmailIntegration() {
            const emailIntegrationGroup = document
                .querySelector(".after-submit-email-integration");

            if (!emailIntegrationGroup) {
                return;
            }

            const emailList = emailIntegrationGroup
                .querySelector(".email-list");

            const addEmailButton = emailIntegrationGroup
                .querySelector("button[role='add-email']");
            addEmailButton.addEventListener("click", (e) => {
                const container = document.createElement("div");
                container.classList.add("email-group", "mb-2");
                emailList.appendChild(container);

                const emailInput = document.createElement("input");
                emailInput.style = "width: auto;";
                emailInput.type = "email";
                emailInput.name = "leform-email-on-form-submition";
                emailInput.placeholder = leform_esc_html__("Type email");
                container.appendChild(emailInput);

                const removeEmailButton = document.createElement("button");
                removeEmailButton.role = "remove";
                removeEmailButton.classList.add(
                    "rounded-lg",
                    "h-8",
                    "w-8",
                    "bg-red-500",
                    "text-white",
                    "ml-3",
                );
                removeEmailButton.innerText = "x";
                container.appendChild(removeEmailButton);

                removeEmailButton.addEventListener("click", (e) => {
                    e.preventDefault();
                    container.parentElement.removeChild(container);
                });
            });

            for (const emailGroup of emailList.children) {
                const emailField = emailGroup
                    .querySelector("input[type='email']");

                const removeEmailButton = emailGroup
                    .querySelector("button[role='remove']");
                removeEmailButton.addEventListener("click", (e) => {
                    e.preventDefault();
                    emailGroup.parentElement.removeChild(emailGroup);
                });
            }
        }

        function renderXMLCustomFieldInput(name = "", value = "", validation = false, hasFormValues = false, showElementValue = true) {
            const baseInputName = "custom-xml-fields";
            const xmlSystemVariablesMenu = renderXmlSystemVariablesMenu([
                "fw_value",
                "fw_id",
                "fw_yyyymmdd",
                "fw_yyyymmdd_hhii",
                "fw_yyyymmdd_hhiiss",
                "fw_random_5",
            ].filter(v => v !== 'fw_value' || showElementValue), "input[name=value]", hasFormValues);
            return `
                <div class="custom-field-group mb-2">
                    <input type="hidden" name="leform-${baseInputName}" />
                    <input
                        class="character-restritced-xml-field-name"
                        style="width: auto; margin-right: 9px;"
                        type="text"
                        name="name"
                        placeholder="${leform_esc_html__("Name")}"
                        value="${name}"
                        ${validation ? `data-pattern="${validation}"` : ''}
                    />
                    <div class="inline-flex">
                        <input
                            style="width: auto; margin-right: 10px;"
                            type="text"
                            name="value"
                            placeholder="${leform_esc_html__("Value")}"
                            value="${value}"
                        />
                        ${xmlSystemVariablesMenu}
                    </div>
                    <button
                        role="remove"
                        class="rounded-lg h-8 w-8 bg-red-500 text-white ml-3"
                    >
                        x
                    </button>
                </div>
            `;
        }

        function addEventListenersToAfterSubmitXMLCustomFields() {
            const xmCustomFieldsGroup = document
                .querySelector(".after-submit-xml-custom-fields");

            if (!xmCustomFieldsGroup) {
                return;
            }

            const customFieldsList = xmCustomFieldsGroup
                .querySelector(".custom-field-list");
            const elementType = customFieldsList.dataset.elementType;

            const addCustomFieldButton = xmCustomFieldsGroup
                .querySelector("button[role='add-custom-field']");
            addCustomFieldButton.addEventListener("click", (e) => {
                var p = jQuery(e.target).data('priority')
                const dummyElement = document.createElement("div");
                dummyElement.innerHTML = renderXMLCustomFieldInput(
                    '', 
                    '', 
                    p || false, 
                    ["settings", "columns"].includes(elementType), 
                    ["columns", "repeater-input"].includes(elementType)
                );
                const customFieldInput = dummyElement.children[0];
                customFieldsList.appendChild(customFieldInput);

                customFieldInput
                    .querySelector("button[role='remove']")
                    .addEventListener("click", (e) => {
                        e.preventDefault();
                        customFieldInput.parentElement.removeChild(customFieldInput);
                    });
                jQuery("input.character-restritced-xml-field-name").unbind('keypress')
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
            });

            for (const customFieldGroup of customFieldsList.children) {
                const customFieldField = customFieldGroup
                    .querySelector("input[type='customField']");

                const removeCustomFieldButton = customFieldGroup
                    .querySelector("button[role='remove']");
                removeCustomFieldButton.addEventListener("click", (e) => {
                    e.preventDefault();
                    customFieldGroup.parentElement.removeChild(customFieldGroup);
                });
            }

            $(".character-restritced-xml-field-name").alphanum({
                allow: '-_',
                allowSpace: false,
            });
        }

        function addEventListenersToRepeaterInputFooterTotals() {
            const footerTotalsContainer = document
                .querySelector(".repeater-input-footer-totals");

            if (!footerTotalsContainer) {
                return;
            }

            const shortCodeMenu = footerTotalsContainer
                .querySelector(".shortcode-menu");
            const menu = shortCodeMenu.querySelector("ul");

            $(shortCodeMenu).hover(
                () => menu.classList.remove("hidden"),
                () => menu.classList.add("hidden"),
            );

            const menuItems = menu.querySelectorAll("li[data-code]");
            const input = footerTotalsContainer
                .querySelector("[name='leform-footer-tolals']");
            for (const menuItem of menuItems) {
                menuItem.addEventListener("click", (e) => {
                    input.value += e.target.dataset.code;
                });
            }
        }

        function xmlSystemVariablesMenuOnClick(item, selector) {
            $($(item).parents(".relative")[0].parentElement)
                .find(selector)
                .val((_, v) => v + item.dataset.attr);
        }

        function renderXmlSystemVariablesMenu(values = [], inputSelector = "input", hasFormValues = false) {
            return `
                <div
                    class="relative"
                    onmouseover="$(this).find('ul').addClass('block').removeClass('hidden')"
                    onmouseout="$(this).find('ul').addClass('hidden').removeClass('block')"
                >
                    <span class="h-10 w-10 flex items-center justify-center border-2 rounded-md mr-2">
                        <i class="fas fa-code"></i>
                    </span>
                    <ul class="hidden absolute bg-white border-2 rounded-md bottom-0 right-0 max-h-36 overflow-y-auto">
                        <li class="px-2 py-1 font-bold">
                            {{__("Formwerk variables")}}
                        </li>
                        ${values.map((e) => `
                            <li
                                class="px-2 py-1 cursor-pointer hover:bg-gray-200"
                                data-attr="\{\{${e}\}\}"
                                onclick="xmlSystemVariablesMenuOnClick(this, '${inputSelector}')"
                            >
                                ${e}
                            </li>
                        `).join("\n")}
                        ${hasFormValues
                            ? `
                                <li class="px-2 py-1 font-bold">
                                    {{__("Form values")}}
                                </li>
                            `
                            : ''}
                        ${hasFormValues
                            ? getFormFields().map((field) => `
                                <li
                                    class="px-2 py-1 cursor-pointer hover:bg-gray-200"
                                    data-attr="\{\{form_${field["name"]}\}\}"
                                    onclick="xmlSystemVariablesMenuOnClick(this, '${inputSelector}')"
                                >
                                    ${field["id"]} | ${leform_escape_html(field["name"])}
                                </li>
                            `).join("\n")
                            : ''}
                    </ul>
                </div>
            `;
        }

        function runAfterChildrenAreBuild() {}
        function validateInputForXml(el, regex) {
            console.log(el, regex);
        }
    </script>

    <script>
        function addEventListenersToTextFieldsWithFormVariables() {
            const inputGroups = document.querySelectorAll(".text-with-form-fields");
            for (const inputGroup of inputGroups) {
                const shortCodeMenu = inputGroup
                    .querySelector(".form-fields-shortcode-menu");
                const menu = shortCodeMenu.querySelector("ul");
                $(shortCodeMenu).hover(
                    () => menu.classList.remove("hidden"),
                    () => menu.classList.add("hidden"),
                );

                const input = inputGroup.querySelector("input");
                shortCodeMenu
                    .querySelectorAll("li")
                    .forEach((option) => option.addEventListener(
                        "click",
                        (e) => input.value += e.target.dataset.code
                    ));
            }
        }
    </script>

    <script>
        function handleFieldOrTextChange(selectElement, inputSelector = 'input') {
            const input = selectElement.parentElement.parentElement.querySelector(inputSelector);
            input.disabled = selectElement.value !== "";
            input.value = "";
        }
    </script>

    <script>
        let leform_translations = @json($frontendTranslations);
    </script>
@endsection

@section('content')
    <div class="wrap leform-admin leform-admin-editor">
        <x-leform.editor
            :formId="$formId"
            :formPages="$formPages"
            :toolbarTools="$toolbarTools"
            :faSolid="$faSolid"
            :faRegular="$faRegular"
            :faBrands="$faBrands"
            :fontAwesomeBasic="$fontAwesomeBasic"
            :options="$options"
            :predefinedOptions="$predefinedOptions"
            :elementPropertiesMeta="$elementPropertiesMeta"
            :validatorsMeta="$validatorsMeta"
            :filtersMeta="$filtersMeta"
            :confirmationsMeta="$confirmationsMeta"
            :notificationsMeta="$notificationsMeta"
            :integrationsMeta="$integrationsMeta"
            :paymentGatewaysMeta="$paymentGatewaysMeta"
            :mathMeta="$mathMeta"
            :logicRules="$logicRules"
            :formOptions="$formOptions"
            :formElements="$formElements"
            :styles="$styles"
            :webfonts="$webfonts"
            :localFonts="$localFonts"
            :customFonts="$customFonts"
            :longLink="$longLink"
            :shortLink="$shortLink" />
    </div>

	@if ($longLink)
        <div
            id="public-url"
            class="inline-block mr-4 py-3 px-4 bg-white"
        >
            <h2 class="m-0">{{ __('Public url') }}</h2>

            <a href="{{ $longLink }}" target="_blank">
                {{ urldecode($longLink) }}
            </a>
        </div>
    @endif

    @if ($shortLink)
        <div
            id="short-url"
            class="inline-block mr-4 py-3 px-4 bg-white"
        >
            <h2 class="m-0">{{ __('Short url') }}</h2>

            <a href="{{ $shortLink }}" target="_blank">
                {{ urldecode($shortLink) }}
            </a>
        </div>
    @endif

@endsection

