@extends('layouts.forms')

@section('custom-head')
    <script>
        let leform_translations = @json($frontendTranslations);
    </script>
    <link rel="stylesheet" href="{{ asset('css/fontawesome-all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/jquery-ui.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/color-picker.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">

    <link rel="stylesheet" id="leform" href="{{ asset('css/halfdata-plugin/admin.css') }}">
    <link rel="stylesheet" id="leform-front" href="{{ asset('css/halfdata-plugin/style.css') }}">
    <link rel="stylesheet" id="tooltipster" href="{{ asset('css/halfdata-plugin/tooltipster.bundle.min.css') }}">
    <link rel="stylesheet" id="leform-fa" href="{{ asset('css/halfdata-plugin/leform-fa.css') }}">
    <link rel="stylesheet" id="leform-if" href="{{ asset('css/halfdata-plugin/leform-if.css') }}">
    <link rel="stylesheet" id="font-awesome-5.7.2" href="{{ asset('css/halfdata-plugin/fontawesome-all.min.css') }}">
    <link rel="stylesheet" id="material-icons-3.0.1" href="{{ asset('css/halfdata-plugin/material-icons.css') }}">
    <link rel="stylesheet" id="airdatepicker" href="{{ asset('css/halfdata-plugin/airdatepicker.css') }}">
    <link rel="stylesheet" id="minicolors" href="{{ asset('css/halfdata-plugin/jquery.minicolors.css') }}">
    <link rel="stylesheet" id="daterangepicker" href="{{ asset('js/daterangepicker/daterangepicker.css') }}">
    <link rel="stylesheet" id="datatable-css" href="https://cdn.datatables.net/1.11.3/css/jquery.dataTables.min.css">
    <link rel="stylesheet" id="datatable-sort-css"
        href="https://cdn.datatables.net/select/1.3.3/css/select.dataTables.min.css">
    <style>
        table.dataTable thead td.select-checkbox:before,
        table.dataTable thead th.select-checkbox:before {
            content: " ";
            margin-top: -6px;
            margin-left: -6px;
            border: 1px solid black;
            border-radius: 3px;
        }

        table.dataTable thead td.select-checkbox,
        table.dataTable thead th.select-checkbox {
            position: relative;
        }

        table.dataTable thead td.select-checkbox:before,
        table.dataTable thead td.select-checkbox:after,
        table.dataTable thead th.select-checkbox:before,
        table.dataTable thead th.select-checkbox:after {
            display: block;
            position: absolute;
            top: 1.2em;
            left: 50%;
            width: 12px;
            height: 12px;
            box-sizing: border-box;
        }

        table.dataTable tr.selected td.select-checkbox:after,
        table.dataTable tr.selected th.select-checkbox:after {
            content: "✓";
            font-size: 15px;
            margin-top: -12px;
            margin-left: -5px;
            text-align: center;
            text-shadow: 1px 1px #b0bed9, -1px -1px #b0bed9, 1px -1px #b0bed9, -1px 1px #b0bed9;
        }

    </style>
    <script>
        let wpColorPickerL10n = {
            "clear": "Clear",
            "defaultString": "Default",
            "pick": "Select Color",
            "current": "Current Color",
        };
    </script>

    <script src="{{ asset('js/jquery.min.js') }}"></script>
    <script src="{{ asset('js/jquery-ui.min.js') }}"></script>
    <script src="{{ asset('js/iris.min.js') }}"></script>
    <script src="{{ asset('js/color-picker.min.js') }}"></script>
    <script src="{{ asset('js/admin.js') }}"></script>

    <script id="leform" src="{{ asset('js/halfdata-plugin/admin.js') }}"></script>
    <script id="tooltipster" src="{{ asset('js/halfdata-plugin/tooltipster.bundle.min.js') }}"></script>
    <script id="airdatepicker" src="{{ asset('js/halfdata-plugin/airdatepicker.js') }}"></script>
    <script id="chart" src="{{ asset('js/halfdata-plugin/chart.min.js') }}"></script>
    <script id="jquery.mask" src="{{ asset('js/halfdata-plugin/jquery.mask.min.js') }}"></script>
    <script id="minicolors" src="{{ asset('js/halfdata-plugin/jquery.minicolors.js') }}"></script>
    <script id="moment" src="{{ asset('js/daterangepicker/moment.min.js') }}"></script>
    <script id="daterangepicker" src="{{ asset('js/daterangepicker/daterangepicker.min.js') }}"></script>
    <script id="datatable" src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
    <script id="datatable-select" src="https://cdn.datatables.net/select/1.3.3/js/dataTables.select.min.js"></script>

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

    <script>
        function leform_record_details_open(leform_record_active) {
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
            var post_data = {
                "action": "leform-record-details",
                "record-id": leform_record_active,
                "_token": "{{ csrf_token() }}",
            };
            jQuery.ajax({
                type: "POST",
                url: "{{ route('entries-details') }}",
                data: post_data,
                success: function(return_data) {
                    try {
                        var data;
                        if (typeof return_data == 'object') data = return_data;
                        else data = jQuery.parseJSON(return_data);
                        if (data.status == "OK") {
                            jQuery("#leform-record-details .leform-admin-popup-content-form").html(data.html);
                            jQuery("#leform-record-details .leform-admin-popup-title h3 span").html(data
                                .form_name);
                            jQuery("#leform-record-details .leform-admin-popup-loading").hide();
                        } else if (data.status == "ERROR") {
                            leform_record_details_close();
                            leform_global_message_show("danger", data.message);
                        } else {
                            leform_record_details_close();
                            leform_global_message_show("danger", leform_esc_html__(
                                "Something went wrong. We got unexpected server response."));
                        }
                    } catch (error) {
                        leform_record_details_close();
                        leform_global_message_show("danger", leform_esc_html__(
                            "Something went wrong. We got unexpected server response."));
                    }
                },
                error: function(XMLHttpRequest, textStatus, errorThrown) {
                    leform_record_details_close();
                    leform_global_message_show("danger", leform_esc_html__(
                        "Something went wrong. We got unexpected server response."));
                }
            });

            return false;
        }

        function _leform_view_details(_object) {
            leform_record_details_open(jQuery(_object).attr("data-id"))
        }

        function _leform_records_delete(_object) {
            if (leform_sending) return false;
            leform_sending = true;
            var record_id = jQuery(_object).attr("data-id");
            var doing_label = jQuery(_object).attr("data-doing");
            var do_label = jQuery(_object).html();
            jQuery(_object).html("<i class='fas fa-spinner fa-spin'></i> " + doing_label);
            jQuery(_object).closest("tr").find(".row-actions").addClass("visible");
            var post_data = {
                "action": "leform-records-delete",
                "record-id": record_id,
                "_token": "{{ csrf_token() }}",
            };
            jQuery.ajax({
                type: "POST",
                url: "{{ route('delete-entry') }}",
                data: post_data,
                success: function(return_data) {
                    try {
                        var data;
                        if (typeof return_data == 'object') data = return_data;
                        else data = jQuery.parseJSON(return_data);
                        if (data.status == "OK") {
                            jQuery(_object).closest("tr").fadeOut(300, function() {
                                jQuery(_object).closest("tr").remove();
                            });
                            leform_global_message_show("success", data.message);
                        } else if (data.status == "ERROR") {
                            leform_global_message_show("danger", data.message);
                        } else {
                            leform_global_message_show("danger", leform_esc_html__(
                                "Something went wrong. We got unexpected server response."));
                        }
                    } catch (error) {
                        leform_global_message_show("danger", leform_esc_html__(
                            "Something went wrong. We got unexpected server response."));
                    }
                    jQuery(_object).html(do_label);
                    jQuery(_object).closest("tr").find(".row-actions").removeClass("visible");
                    leform_sending = false;
                },
                error: function(XMLHttpRequest, textStatus, errorThrown) {
                    jQuery(_object).closest("tr").find(".row-actions").removeClass("visible");
                    jQuery(_object).html(do_label);
                    leform_global_message_show("danger", leform_esc_html__(
                        "Something went wrong. We got unexpected server response."));
                    leform_sending = false;
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
            var post_data = {
                "action": "leform-record-field-load-editor",
                "record-id": record_id,
                "field-id": field_id,
                "_token": "{{ csrf_token() }}",
            };
            jQuery.ajax({
                type: "POST",
                url: "{{ route('record-field-load-editor') }}",
                data: post_data,
                success: function(return_data) {
                    try {
                        var data;
                        if (typeof return_data == 'object') data = return_data;
                        else data = jQuery.parseJSON(return_data);
                        if (data.status == "OK") {
                            jQuery(_button).closest(".leform-record-details-table-value").find(
                                ".leform-record-field-value").fadeOut(300, function() {
                                jQuery(_button).closest(".leform-record-details-table-value").find(
                                    ".leform-record-field-editor").html(data.html);
                                jQuery(_button).closest(".leform-record-details-table-value").find(
                                    ".leform-record-field-editor").fadeIn(300);
                            });
                        } else if (data.status == "ERROR") {
                            leform_global_message_show("danger", data.message);
                        } else {
                            leform_global_message_show("danger", leform_esc_html__(
                                "Something went wrong. We got unexpected server response."));
                        }
                    } catch (error) {
                        console.log(error);
                        leform_global_message_show("danger", leform_esc_html__(
                            "Something went wrong. We got unexpected server response."));
                    }
                    jQuery(_button).find("i").attr("class", icon);
                    leform_sending = false;
                    leform_dialog_close();
                },
                error: function(XMLHttpRequest, textStatus, errorThrown) {
                    jQuery(_button).find("i").attr("class", icon);
                    leform_global_message_show("danger", leform_esc_html__(
                        "Something went wrong. We got unexpected server response."));
                    leform_sending = false;
                    leform_dialog_close();
                }
            });
            return false;
        }

        function leform_record_field_save(_button) {
            if (leform_sending) return false;
            leform_sending = true;
            var field_id = jQuery(_button).closest(".leform-record-details-table-value").attr("data-id");
            var record_id = jQuery(_button).closest(".leform-record-details").attr("data-id");
            var icon = jQuery(_button).find("i").attr("class");
            jQuery(_button).find("i").attr("class", "fas fa-spinner fa-spin");
            var post_data = {
                "action": "leform-record-field-save",
                "record-id": record_id,
                "field-id": field_id,
                "value": leform_encode64(jQuery(_button).closest(".leform-record-field-editor").find(
                    "textarea, input, select").serialize()),
                "_token": "{{ csrf_token() }}",
            };
            jQuery.ajax({
                type: "POST",
                url: "{{ route('record-field-save') }}",
                data: post_data,
                success: function(return_data) {
                    try {
                        var data;
                        if (typeof return_data == 'object') data = return_data;
                        else data = jQuery.parseJSON(return_data);
                        if (data.status == "OK") {
                            jQuery(_button).closest(".leform-record-details-table-value").find(
                                ".leform-record-field-editor").fadeOut(300, function() {
                                jQuery(_button).closest(".leform-record-details-table-value").find(
                                    ".leform-record-field-value").html(data.html);
                                jQuery(_button).closest(".leform-record-details-table-value").find(
                                    ".leform-record-field-value").fadeIn(300);
                                jQuery(_button).closest(".leform-record-details-table-value").find(
                                    ".leform-record-field-editor").html("");
                            });
                            leform_global_message_show("success", data.message);
                            window.location.reload();
                        } else if (data.status == "ERROR") {
                            leform_global_message_show("danger", data.message);
                        } else {
                            leform_global_message_show("danger", leform_esc_html__(
                                "Something went wrong. We got unexpected server response."));
                        }
                    } catch (error) {
                        console.log(error);
                        leform_global_message_show("danger", leform_esc_html__(
                            "Something went wrong. We got unexpected server response."));
                    }
                    jQuery(_button).find("i").attr("class", icon);
                    leform_sending = false;
                    leform_dialog_close();
                },
                error: function(XMLHttpRequest, textStatus, errorThrown) {
                    jQuery(_button).find("i").attr("class", icon);
                    leform_global_message_show("danger", leform_esc_html__(
                        "Something went wrong. We got unexpected server response."));
                    leform_sending = false;
                    leform_dialog_close();
                }
            });
            return false;
        }

        function _leform_record_field_empty(_button, _object) {
            if (leform_sending) return false;
            leform_sending = true;
            var field_id = jQuery(_object).closest(".leform-record-details-table-value").attr("data-id");
            var record_id = jQuery(_object).closest(".leform-record-details").attr("data-id");
            var icon = jQuery(_button).find("i").attr("class");
            jQuery(_button).find("i").attr("class", "fas fa-spinner fa-spin");
            var post_data = {
                "action": "leform-record-field-empty",
                "record-id": record_id,
                "field-id": field_id,
                "_token": "{{ csrf_token() }}",
            };
            jQuery.ajax({
                type: "POST",
                url: "{{ route('record-field-empty') }}",
                data: post_data,
                success: function(return_data) {
                    try {
                        var data;
                        if (typeof return_data == 'object') data = return_data;
                        else data = jQuery.parseJSON(return_data);
                        if (data.status == "OK") {
                            jQuery(_object).closest(".leform-record-details-table-value").find(
                                ".leform-record-field-value").text("-");
                            leform_global_message_show("success", data.message);
                            window.location.reload();
                        } else if (data.status == "ERROR") {
                            leform_global_message_show("danger", data.message);
                        } else {
                            leform_global_message_show("danger", leform_esc_html__(
                                "Something went wrong. We got unexpected server response."));
                        }
                    } catch (error) {
                        console.log(error);
                        leform_global_message_show("danger", leform_esc_html__(
                            "Something went wrong. We got unexpected server response."));
                    }
                    jQuery(_button).find("i").attr("class", icon);
                    leform_sending = false;
                    leform_dialog_close();
                },
                error: function(XMLHttpRequest, textStatus, errorThrown) {
                    jQuery(_button).find("i").attr("class", icon);
                    leform_global_message_show("danger", leform_esc_html__(
                        "Something went wrong. We got unexpected server response."));
                    leform_sending = false;
                    leform_dialog_close();
                }
            });
            return false;
        }

        function _leform_record_field_remove(_button, _object) {
            if (leform_sending) return false;
            leform_sending = true;
            var field_id = jQuery(_object).closest(".leform-record-details-table-value").attr("data-id");
            var record_id = jQuery(_object).closest(".leform-record-details").attr("data-id");
            var icon = jQuery(_button).find("i").attr("class");
            jQuery(_button).find("i").attr("class", "fas fa-spinner fa-spin");
            var post_data = {
                "action": "leform-record-field-remove",
                "record-id": record_id,
                "field-id": field_id,
                "_token": "{{ csrf_token() }}",
            };
            jQuery.ajax({
                type: "POST",
                url: "{{ route('record-field-remove') }}",
                data: post_data,
                success: function(return_data) {
                    try {
                        var data;
                        if (typeof return_data == 'object') data = return_data;
                        else data = jQuery.parseJSON(return_data);
                        if (data.status == "OK") {
                            jQuery(_object).closest("tr").fadeOut(300, function() {
                                jQuery(_object).closest("tr").remove();
                            });
                            leform_global_message_show("success", data.message);
                            window.location.reload();
                        } else if (data.status == "ERROR") {
                            leform_global_message_show("danger", data.message);
                        } else {
                            leform_global_message_show("danger", leform_esc_html__(
                                "Something went wrong. We got unexpected server response."));
                        }
                    } catch (error) {
                        console.log(error);
                        leform_global_message_show("danger", leform_esc_html__(
                            "Something went wrong. We got unexpected server response."));
                    }
                    jQuery(_button).find("i").attr("class", icon);
                    leform_sending = false;
                    leform_dialog_close();
                },
                error: function(XMLHttpRequest, textStatus, errorThrown) {
                    jQuery(_button).find("i").attr("class", icon);
                    leform_global_message_show("danger", leform_esc_html__(
                        "Something went wrong. We got unexpected server response."));
                    leform_sending = false;
                    leform_dialog_close();
                }
            });
            return false;
        }

        $(window).on('load', function() {
            @isset($_GET['record_id'])
                leform_record_details_open({{ $_GET['record_id'] }});
            @endisset
            jQuery("#entries_archived").on("change", function() {
                jQuery(this).val(jQuery(this).prop('checked') ? '1' : '0');
                jQuery("#leform-filter-form").submit();
            })
        })
    </script>

    <script>
        function hideOnClickOutside(element, hideFunction) {
            function outsideClickListener(event) {
                if (
                    !element.contains(event.target) &&
                    isVisible(element)
                ) {
                    hideFunction();
                    // removeClickListener();
                }
            }

            function removeClickListener() {
                document.removeEventListener('click', outsideClickListener);
            }
            document.addEventListener('click', outsideClickListener);
        }

        function isVisible(elem) {
            return (
                !!elem &&
                !!(
                    elem.offsetWidth ||
                    elem.offsetHeight ||
                    elem.getClientRects().length
                )
            );
        }

        function debounce(fn, delay) {
            let timer;
            return function(...args) {
                if (timer) {
                    clearTimeout(timer);
                }
                timer = setTimeout(fn.apply(null, args), delay)
            }
        }

        function closeMenu(menuElement) {
            const searchBox = menuElement.querySelector(".search");
            searchBox.value = "";

            const options = menuElement.querySelectorAll(".options .option");
            onSearchChange("", options);

            menuElement.classList.add("hidden");
        }

        function toggleMenu(menuElement) {
            menuElement.classList.toggle("hidden");
            if (!menuElement.classList.contains("hidden")) {
                const toggleWidth = menuElement
                    .parentElement
                    .querySelector(".value-container")
                    .offsetWidth;
                const menuWidth = menuElement.offsetWidth;
                menuElement.style.marginLeft = (toggleWidth - menuWidth).toString() + "px";
            }
        }

        function onSearchChange(search, options) {
            for (const option of options) {
                const optionLabel = option.dataset.label;
                option.classList.toggle(
                    "hidden",
                    !optionLabel.includes(search)
                );
            }
        }
    </script>
@endsection

@section('content')
    <div class="max-w-7xl mx-auto">
        <form id="leform-filter-form" class="mb-0" action="{{ route('entries') }}" method="get"
            class="uap-filter-form leform-filter-form">
            <div class="flex justify-between items-end ">
                <div style="flex: 1 1 50%">
                    <div class="flex items-end  flex-wrap pb-1">
                        <div id="leform-action-form-container" style="display: none;" class="uap-filter-form leform-filter-form">
                            <div class="flex">
                                <div class="flex flex-col">
                                    <div class="flex flex-col">
                                        <label>{{ __('app.Edit entries') }}</label>
                                        <select id="leform-action-form" style="min-width: 200px;">
                                            <option value="-1"></option>
                                            <option value="delete">{{ __('Delete') }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        @foreach ($filters as $key => $value)
                            @if ($value !== null && !in_array($key, ['search']))
                                <input type="hidden" name="{{ $key }}" value="{{ $value }}" />
                            @endif
                        @endforeach
                        <div class="uap-filter-form leform-filter-form">
                            <input class="mr-1 border-2 p-1.5" name="search" value="{{ $filters['search'] }}" />

                            <button class="px-4 py-2 bg-blue-300 text-white rounded mr-4" type="submit">
                                {{ __('Search') }}
                            </button>
                        </div>
                        <div class="uap-filter-form leform-filter-form" data-id="entries_archived" style="margin: auto 0 5px;">
                            <div class="leform-properties-label"
                                style="margin: auto;vertical-align: middle;padding: 0;min-width: 150px;">
                                <label>{{ __('Archived Entries') }}</label>
                            </div>
                            <div class="leform-properties-content">
                                <input class="leform-checkbox-toggle" type="checkbox" value="1" name="archived"
                                    id="entries_archived" <?php echo isset($_GET['archived']) && $_GET['archived'] === '1' ? 'checked="checked"' : ''; ?>>
                                <label for="entries_archived"></label>
                            </div>
                        </div>
                    </div>
                </div>
                <div  style="flex: 1 1 50%">
                    <div class="leform-top-form-right flex flex-row align-bottom">
                        <div class="flex">
                            @foreach ($filters as $key => $value)
                                @if ($value !== null && !in_array($key, ['form', 'dynamic_value']))
                                    <input type="hidden" name="{{ $key }}" value="{{ $value }}" />
                                @endif
                            @endforeach

                            <div class="flex flex-col mr-2">
                                <x-combobox-select :name="'form'" class="mr-0 mb-0" :label="__('Form')" :options="$forms"
                                    :labelPropertyName="'name'" :valuePropertyName="'id'" :selectedItem="$selectedForm"
                                    :placeholder="__('All Forms')" />
                            </div>

                            @if ($form_id)
                                <div class="flex flex-col">
                                    <x-combobox-select class="mb-0" :name="'dynamic-value'"
                                        :label="__('Dynamic value')" :options="$form_entries_map" :selectedItem="$dynamic_value"
                                        :placeholder="__('No dynamic value')" />
                                </div>
                            @endif
                            @if ($hasFilters)
                                <div class="flex flex-col">
                                    <a href="{{ route('entries') }}" class="px-4 py-2 bg-red-300 text-white rounded ml-4" style="height: 40px; margin-top: 25px;">
                                        {{ __('Clear filters') }}
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="wrap leform-admin">
        <div class="leform-top-forms">
            <div class="leform-top-form-left"></div>
        </div>

        <table id="leform-table-log" class="leform-table-list widefat mb-24" style="display:none;">
            <thead>
                <tr>
                    <th class="select-checkbox" style="text-align: center;" id="selectAllEntries"></th>

                    <th class="leform-column leform-column-id">
                        {{ __('ID') }}
                    </th>

                    <th class="leform-column leform-column-primary">
                        <x-entries-page.sort-filters-form :text="'Primary Field'" :filters="$filters"
                            :sortField="'primary_field_value'" />
                    </th>

                    <th class="leform-column leform-column-secondary">
                        <x-entries-page.sort-filters-form :text="'Secondray Field'" :filters="$filters"
                            :sortField="'secondary_field_value'" />
                    </th>

                    <th class="leform-column leform-column-form">
                        <x-entries-page.sort-filters-form :text="'Form'" :filters="$filters" :sortField="'form'" />
                    </th>

                    <th class="leform-column leform-column-amount" style="width: 100px;">
                        <x-entries-page.sort-filters-form :text="'Amount'" :filters="$filters" :sortField="'amount'" />
                    </th>

                    <th class="leform-column leform-column-created" style="width: 130px;">
                        <x-entries-page.sort-filters-form :text="'Created'" :filters="$filters" :sortField="'created'" />
                    </th>

                    <th class="leform-column leform-column-actions" style="width: 35px;"></th>
                </tr>
            </thead>

            <tbody>
                @if (sizeof($rows) > 0)
                    @foreach ($rows as $row)
                        <tr>
                            <td></td>

                            <td class="leform-column leform-column-id">{{ $row['id'] }}</td>

                            <td class="leform-column leform-column-primary">
                                <div class="flex">
                                    <a href="#" onclick="return _leform_view_details(this);" data-id="{{ $row['id'] }}">
                                        @if ($row['primary_field_value'])
                                            <strong>{{ $row['primary_field_value'] }}</strong>
                                        @else
                                            <strong>-</strong>
                                        @endif
                                    </a>

                                    @if ($row['primary_field_value'])
                                        @if ($row['status'] == 1)
                                            <span class="leform-badge leform-badge-danger">{{ __('Unconfirmed') }}</span>
                                        @elseif ($row['status'] == 2)
                                            <span class="leform-badge leform-badge-success">{{ __('Confirmed') }}</span>
                                        @endif
                                    @endif

                                    @if ($row['primary_field_value'])
                                        <x-entries-page.filters-form :filters="$filters"
                                            :displayedFilter="'primary_field_value'"
                                            :displayedFilterValue="$row['primary_field_value']" />
                                    @endif
                                </div>
                            </td>

                            <td class="leform-column leform-column-secondary">
                                <div class="flex">
                                    @if ($row['secondary_field_value'])
                                        {{ $row['secondary_field_value'] }}
                                        <x-entries-page.filters-form :filters="$filters"
                                            :displayedFilter="'secondary_field_value'"
                                            :displayedFilterValue="$row['secondary_field_value']" />
                                    @else
                                        -
                                    @endif
                                </div>
                            </td>

                            <td class="leform-column leform-column-form">
                                <div class="flex">
                                    @if ($row['deleted'] == 0)
                                        <a href="{{ route('create-form', ['id' => $row['form']['id']]) }}">
                                            {{ $row['form']['name'] }}
                                        </a>
                                    @else
                                        {{ $row['form']['name'] }} ({{ __('deleted') }})
                                    @endif

                                    <x-entries-page.filters-form :filters="$filters" :displayedFilter="'form'"
                                        :displayedFilterValue="$row['form']['id']" />
                                </div>
                            </td>

                            <td class="leform-column leform-column-amount">
                                @if ($row['amount'] > 0)
                                    <a href="#">
                                        @if ($row['currency'] != 'BTC')
                                            {{ number_format($row['amount'], 2, '.', '') }}
                                        @else
                                            {{ number_format($row['amount'], 8, '.', '') }}
                                        @endif
                                        {{ $row['currency'] }}
                                    </a>

                                    @if ($row['status'] == 4)
                                        <span class="leform-badge leform-badge-success">{{ __('Paid') }}</span>
                                    @else
                                        <span class="leform-badge leform-badge-danger">{{ __('Unpaid') }}</span>
                                    @endif
                                @else
                                    -
                                @endif
                            </td>

                            <td class="leform-column leform-column-created">
                                {{ $leform->unixtime_string($row['created']) }}
                            </td>

                            <td class="leform-column leform-column-actions">
                                <div class="leform-table-list-actions">
                                    <span><i class="fas fa-ellipsis-v"></i></span>
                                    <div class="leform-table-list-menu">
                                        <ul>
                                            <li>
                                                <a href="#" onclick="return _leform_view_details(this);"
                                                    data-id="{{ $row['id'] }}">
                                                    {{ __('Details') }}
                                                </a>
                                            </li>
                                            <li>
                                                <a href="#" data-id="{{ $row['id'] }}"
                                                    data-doing="{{ __('Deleting') }}..."
                                                    onclick="return leform_records_delete(this);">
                                                    {{ __('Delete') }}
                                                </a>
                                            </li>
                                            <li>
                                                <a href="{{ route('record-pdf-download', ['id' => $row['id']]) }}"
                                                    target="_blank">
                                                    {{ __('Pdf file') }}
                                                </a>
                                            </li>
                                            <?php
                                            $xml_file_disabled = !(isset($row['xml_file_name']) && !is_null($row['xml_file_name']) && Storage::disk('private')->exists($row['xml_file_name'])) ? true : false;
                                            $csv_file_disabled = !(isset($row['csv_file_name']) && !is_null($row['csv_file_name']) && Storage::disk('private')->exists($row['csv_file_name'])) ? true : false;
                                            $custom_report_file_disabled = !(isset($row['custom_report_file_name']) && !is_null($row['custom_report_file_name']) && Storage::disk('private')->exists($row['custom_report_file_name'])) ? true : false;
                                            
                                            ?>
                                            <li style="display: flex">
                                                <a class="{{ $xml_file_disabled ? 'disabled' : '' }}"
                                                    href="{{ route('record-xml-download', ['id' => $row['id']]) }}"
                                                    target="_blank">

                                                    {{ __('XML File') }}
                                                </a>
                                                @if ($xml_file_disabled)
                                                    <div class="leform-properties-tooltip">
                                                        <i class="fas fa-question-circle leform-tooltip-anchor tooltipster tooltip"
                                                            title="{{ __("File doesn't exist") }}"></i>
                                                    </div>
                                                @endif
                                            </li>
                                            <li style="display: flex">
                                                <a class="{{ $csv_file_disabled ? 'disabled' : '' }}"
                                                    href="{{ route('record-csv-download', ['id' => $row['id']]) }}"
                                                    target="_blank">
                                                    {{ __('CSV File') }}
                                                </a>
                                                @if ($csv_file_disabled)
                                                    <div class="leform-properties-tooltip">
                                                        <i class="fas fa-question-circle leform-tooltip-anchor tooltipster tooltip"
                                                            title="{{ __("File doesn't exist") }}"></i>
                                                    </div>
                                                @endif
                                            </li>
                                            <li style="display: flex">
                                                <a class="{{ $custom_report_file_disabled ? 'disabled' : '' }}"
                                                    href="{{ route('record-custom-report-download', ['id' => $row['id']]) }}"
                                                    target="_blank">
                                                    {{ __('Custom report File') }}
                                                </a>
                                                @if ($custom_report_file_disabled)
                                                    <div class="leform-properties-tooltip">
                                                        <i class="fas fa-question-circle leform-tooltip-anchor tooltipster tooltip"
                                                            title="{{ __("File doesn't exist") }}"></i>
                                                    </div>
                                                @endif
                                            </li>
                                        </ul>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                @else
                    <tr>
                        <td colspan="7" class="leform-table-list-empty">
                            @if (strlen($search_query) > 0)
                                {{ __('No results found for') }} <strong>{{ $search_query }}</strong>
                            @else
                                {{ __('List is empty') }}.
                            @endif
                        </td>
                    </tr>
                @endif
            </tbody>
            <x-entries-page.table-footer :rows="$rows" />
        </table>
        <script>
            leform_columns_toggle("log");
            jQuery("#leform-table-log").show();
        </script>
    </div>

    <div id="leform-global-message"></div>
    <div class="leform-dialog-overlay" id="leform-dialog-overlay"></div>
    <div class="leform-dialog" id="leform-dialog">
        <div class="leform-dialog-inner">
            <div class="leform-dialog-title">
                <a href="#" title="{{ __('Close') }}" onclick="return leform_dialog_close();">
                    <i class="fas fa-times"></i>
                </a>
                <h3>
                    <i class="fas fa-cog"></i>
                    <label></label>
                </h3>
            </div>
            <div class="leform-dialog-content">
                <div class="leform-dialog-content-html">
                </div>
            </div>
            <div class="leform-dialog-buttons">
                <a class="leform-dialog-button leform-dialog-button-ok" href="#" onclick="return false;">
                    <i class="fas fa-check"></i>
                    <label></label>
                </a>
                <a class="leform-dialog-button leform-dialog-button-cancel" href="#" onclick="return false;">
                    <i class="fas fa-times"></i>
                    <label></label>
                </a>
            </div>
            <div class="leform-dialog-loading"><i class="fas fa-spinner fa-spin"></i></div>
        </div>
    </div>

    <div id="mainEntriesLoading" style="display: none" class="leform-admin-popup-loading">
        <i class="fas fa-spinner fa-spin"></i>
    </div>
    @if (!empty($error_message))
        <script>
            jQuery(document).ready(function() {
                leform_global_message_show("danger", "{{ $error_message }}")
            });
        </script>
    @elseif (!empty($success_message))
        <script>
            jQuery(document).ready(function() {
                leform_global_message_show("success", "{{ $success_message }}")
            });
        </script>
    @endif
    <div class="leform-admin-popup-overlay" id="leform-record-details-overlay"></div>
    <div class="leform-admin-popup" id="leform-record-details">
        <div class="leform-admin-popup-inner">
            <div class="leform-admin-popup-title">
                <a href="#" title="{{ __('Close') }}" onclick="return leform_record_details_close();">
                    <i class="fas fa-times"></i>
                </a>
                {{-- <span class="leform-export-pdf" data-url="#">
                            <a target="_blank" href="#">
                                <i class="fas fa-file-pdf"></i>
                            </a>
                        </span>
                        <span class="leform-print" data-url="#">
                            <a target="_blank" href="#">
                                <i class="fas fa-print"></i>
                            </a>
                        </span> --}}
                <h3>
                    <i class="fas fa-cog"></i>
                    {{ __('Record Details') }}
                    <span></span>
                </h3>
            </div>
            <div class="leform-admin-popup-content">
                <div class="leform-admin-popup-content-form"></div>
            </div>
            <div class="leform-admin-popup-loading">
                <i class="fas fa-spinner fa-spin"></i>
            </div>
        </div>
    </div>
    <script>
        jQuery(document).ready(function() {
            const exportLabel = leform_esc_html__("Export", "leform");
            leform_log_ready();
            $('.tooltip').tooltipster();
            if($("#leform-table-log tr>td.leform-column-id").length) {
                var entriesTable = $("#leform-table-log").DataTable({
                    bFilter: false,
                    bPaginate: false,
                    bInfo: false,
                    ordering: false,
                    columnDefs: [{
                        orderable: false,
                        className: 'select-checkbox',
                        targets: 0
                    }],
                    select: {
                        style: 'multi',
                        selector: 'td:first-child'
                    },
                    order: [
                        [1, 'asc']
                    ],
                   "footerCallback": function( tfoot, data, start, end, display ) {
                    if(!tfoot) {
                        jQuery("#leform-table-log").append(`<tfoot class="border-t-2">
                            <tr>
                                <td colspan="8" class="select-checkbox" rowspan="1">
                                    <nav role="navigation" class="flex items-center justify-between">
                                        <div class="flex justify-between flex-1 sm:hidden">
                                            <span> </span>
                                            <button id="export-all-xml-sm" class="hover:bg-gray-200 text-gray-600 px-4 py-2 rounded">${exportLabel}</button>
                                            <span> </span>
                                        </div>
                                        <div class="hidden sm:flex-1 sm:flex sm:items-center sm:justify-between">
                                            <div></div>
                                            <button id="export-all-xml" class="hover:bg-gray-200 text-gray-600 px-4 py-2 rounded">${exportLabel}</button>
                                            <div></div>
                                        </div>
                                    </nav>
                                </td>
                            </tr>
                        </tfoot>
                        `)
                    } else {
                     jQuery(`<button id="export-all-xml" class="hover:bg-gray-200 text-gray-600 px-4 py-2 rounded">${exportLabel}</button>`).insertAfter(jQuery(tfoot).find('td > nav > div> div:nth-child(1)'))
                     jQuery(`<button id="export-all-xml-sm" class="hover:bg-gray-200 text-gray-600 px-4 py-2 rounded">${exportLabel}</button>`).insertAfter(jQuery(tfoot).find('td > nav > div > span'))
                    }
                    }
                });
                function exportAllXml () {
                    const url = "{{ route('record-xml-zip-download') }}";
                     const selectedRows = entriesTable.rows({
                        selected: true
                    });
                    if(selectedRows.count()) {
                        const ids = selectedRows.data().toArray().map(d => 1 * d[1]).map(d => {
                            return `id[]=${d}`;
                        }).join('&');
                        window.open(`${url}?${ids}`, '_blank')
                    } else {
                        export_xml_zip(url);
                    }
                   // window.open("{{ route('record-xml-zip-download') }}?id=4438&id=4437&id=4436", '_blank')
                }
                $('#export-all-xml').on('click', exportAllXml);
                $('#export-all-xml-sm').on('click', exportAllXml);
                $('#selectAllEntries').on('click', function() {
                    if (!$('#selectAllEntries').parent().hasClass('selected')) {
                        entriesTable.rows().select();
                    } else {
                        entriesTable.rows().deselect();
                    }
                    $('#selectAllEntries').parent().toggleClass('selected')
                });
                entriesTable.on('select', function(e, dt, type, indexes) {
                    const selectedRows = entriesTable.rows({
                        selected: true
                    });
                    if (selectedRows.count()) {
                        $("#leform-action-form-container").show();
                    } else {
                        $("#leform-action-form-container").hide();
                    }
                });
                entriesTable.on('deselect', function(e, dt, type, indexes) {
                    if (type === 'row') {
                        const selectedRows = entriesTable.rows({
                            selected: true
                        });
                        if (selectedRows.count()) {
                            $("#leform-action-form-container").show();
                        } else {
                            $("#leform-action-form-container").hide();
                        }
                    }
                });
                $("#leform-action-form").change(function(e) {
                    const selectedRows = entriesTable.rows({
                        selected: true
                    });
                    e.preventDefault();
                    const value = $(this).val()
                    if (value === 'delete' && selectedRows.count()) {
                        $("#mainEntriesLoading").show();
                        const ids = selectedRows.data().toArray().map(d => 1 * d[1]);
                        var post_data = {
                            "action": value,
                            "ids": ids,
                            "_token": "{{ csrf_token() }}",
                        };
                        jQuery.ajax({
                            type: "POST",
                            url: "{{ route('entries-actions') }}",
                            data: post_data,
                            success: function(return_data) {
                                window.location.reload();
                            },
                            error: function(XMLHttpRequest, textStatus, errorThrown) {
                                leform_global_message_show("danger", leform_esc_html__(
                                    "Something went wrong. We got unexpected server response."
                                ));
                                $(this).val('-1')
                                $("#mainEntriesLoading").hide();
                            }
                        });
                    }
                    return false;
                })
            }
            const selects = document
                .querySelectorAll(".custom-combobox-select");
            for (const select of selects) {
                const submitOnSelect = select.dataset.submitOnSelect === "true";
                const valueContainer = select.querySelector(".value-container");
                const valueInput = valueContainer.querySelector("input");
                const valueBox = valueContainer.querySelector(".value-box");
                const menu = select.querySelector(".menu");
                const searchBox = menu.querySelector(".search");
                const optionsContainer = menu.querySelector(".options");
                const options = optionsContainer.querySelectorAll(".option");

                hideOnClickOutside(select, closeMenu.bind(null, menu));

                for (const option of options) {
                    option.addEventListener("click", (e) => {
                        const selectedValue = menu.dataset.value;
                        const isSelected = option.dataset.value === selectedValue;
                        if (isSelected) {
                            return;
                        }
                        valueBox.textContent = option.dataset.label;
                        valueInput.value = option.dataset.value;
                        closeMenu(menu);

                        if (submitOnSelect) {
                            const form = select.closest("form");
                            if (form) {
                                form.submit();
                            }
                        }
                    });
                }

                function searchListener(e) {
                    const searchValue = e.target.value;
                    onSearchChange(searchValue, options);
                }

                const debouncedSearchListener = debounce(searchListener, 300);
                searchBox.addEventListener("keyup", debouncedSearchListener);

                valueContainer.addEventListener(
                    "click",
                    toggleMenu.bind(null, menu)
                );
            }
        });
    </script>
@endsection
