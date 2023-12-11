@extends('layouts.forms')

@section('custom-head')
    <link rel="stylesheet" href="{{ asset('css/fontawesome-all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/jquery-ui.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/color-picker.min.css') }}">

    <link rel="stylesheet" id="leform" href="{{ asset("css/halfdata-plugin/admin.css") }}">
    <link rel="stylesheet" id="leform-front" href="{{ asset("css/halfdata-plugin/style.css") }}">
    <link rel="stylesheet" id="tooltipster" href="{{ asset("css/halfdata-plugin/tooltipster.bundle.min.css") }}">
    <link rel="stylesheet" id="leform-fa" href="{{ asset("css/halfdata-plugin/leform-fa.css") }}">
    <link rel="stylesheet" id="leform-if" href="{{ asset("css/halfdata-plugin/leform-if.css") }}">
    <link rel="stylesheet" id="font-awesome-5.7.2" href="{{ asset("css/halfdata-plugin/fontawesome-all.min.css") }}">
    <link rel="stylesheet" id="material-icons-3.0.1" href="{{ asset("css/halfdata-plugin/material-icons.css") }}">
    <link rel="stylesheet" id="airdatepicker" href="{{ asset("css/halfdata-plugin/airdatepicker.css") }}">
    <link rel="stylesheet" id="minicolors" href="{{ asset("css/halfdata-plugin/jquery.minicolors.css") }}">

    <script>
        let wpColorPickerL10n = {
            "clear": "Clear",
            "defaultString": "Default",
            "pick": "Select Color",
            "current": "Current Color",
        };
    </script>

    <script src="{{ asset("js/jquery.min.js") }}"></script>
    <script src="{{ asset("js/jquery-ui.min.js") }}"></script>
    <script src="{{ asset("js/iris.min.js") }}"></script>
    <script src="{{ asset("js/color-picker.min.js") }}"></script>
    <script src="{{ asset("js/admin.js") }}"></script>

    <script id="leform" src="{{ asset("js/halfdata-plugin/admin.js") }}"></script>
    <script id="tooltipster" src="{{ asset("js/halfdata-plugin/tooltipster.bundle.min.js") }}"></script>
    <script id="airdatepicker" src="{{ asset("js/halfdata-plugin/airdatepicker.js") }}"></script>
    <script id="chart" src="{{ asset("js/halfdata-plugin/chart.min.js") }}"></script>
    <script id="jquery.mask" src="{{ asset("js/halfdata-plugin/jquery.mask.min.js") }}"></script>
    <script id="minicolors" src="{{ asset("js/halfdata-plugin/jquery.minicolors.js") }}"></script>

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
            var post_data = {
                "action": "leform-forms-status-toggle",
                "form-id": form_id,
                "form-status": form_status,
                "_token": "{{ csrf_token() }}",
            };
            jQuery.ajax({
                type: "POST",
                url: "{{ route('toogle-form-status') }}",
                data: post_data,
                success: function(return_data) {
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
                error: function(XMLHttpRequest, textStatus, errorThrown) {
                    jQuery(_object).closest("tr").find(".row-actions").removeClass("visible");
                    jQuery(_object).html(do_label);
                    jQuery(_object).closest("tr").find("td.column-active").html(form_status_label);
                    leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
                    leform_sending = false;
                }
            });
            return false;
        }

        function _leform_forms_duplicate(_object) {
            if (leform_sending) {
                return false;
            }
            leform_sending = true;
            var form_id = jQuery(_object).attr("data-id");
            var doing_label = jQuery(_object).attr("data-doing");
            var do_label = jQuery(_object).html();
            jQuery(_object).html("<i class='fas fa-spinner fa-spin'></i> " + doing_label);
            jQuery(_object).closest("tr").find(".row-actions").addClass("visible");
            var post_data = {
                "action": "leform-forms-duplicate",
                "form-id": form_id,
                "_token": "{{ csrf_token() }}",
            };
            jQuery.ajax({
                type: "POST",
                url: "{{ route('duplicate-form') }}",
                data: post_data,
                success: function(return_data) {
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
                error: function(XMLHttpRequest, textStatus, errorThrown) {
                    jQuery(_object).closest("tr").find(".row-actions").removeClass("visible");
                    jQuery(_object).html(do_label);
                    leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
                    leform_sending = false;
                }
            });
            return false;
        }

        function _leform_forms_delete(_object) {
            if (leform_sending) {
                return false;
            }
            leform_sending = true;
            var form_id = jQuery(_object).attr("data-id");
            var doing_label = jQuery(_object).attr("data-doing");
            var do_label = jQuery(_object).html();
            jQuery(_object).html("<i class='fas fa-spinner fa-spin'></i> " + doing_label);
            jQuery(_object).closest("tr").find(".row-actions").addClass("visible");
            var post_data = {
                "action": "leform-forms-delete",
                "form-id": form_id,
                "_token": "{{ csrf_token() }}",
            };
            jQuery.ajax({
                type: "POST",
                url: "{{ route('delete-form') }}",
                data: post_data,
                success: function(return_data) {
                    try {
                        var data;
                        if (typeof return_data == 'object') {
                            data = return_data;
                        } else {
                            data = jQuery.parseJSON(return_data);
                        }
                        if (data.status == "OK") {
                            jQuery(_object).closest(".form-item").fadeOut(300, function() {
                                jQuery(_object).closest(".form-item").remove();
                            });
                            leform_global_message_show("success", data.message);
                        } else if (data.status == "ERROR") {
                            leform_global_message_show("danger", data.message);
                        } else {
                            leform_global_message_show(
                                "danger",
                                leform_esc_html__("Something went wrong. We got unexpected server response.")
                            );
                        }
                    } catch (error) {
                        leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
                    }
                    jQuery(_object).html(do_label);
                    jQuery(_object).closest("tr").find(".row-actions").removeClass("visible");
                    leform_sending = false;
                },
                error: function(XMLHttpRequest, textStatus, errorThrown) {
                    jQuery(_object).closest("tr").find(".row-actions").removeClass("visible");
                    jQuery(_object).html(do_label);
                    leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
                    leform_sending = false;
                }
            });
            return false;
        }

        function _leform_forms_share(_object) {
            if (leform_sending) {
                return false;
            }
            leform_sending = true;
            var form_id = jQuery(_object).attr("data-id");
            var doing_label = jQuery(_object).attr("data-doing");
            var do_label = jQuery(_object).html();
            jQuery(_object).html("<i class='fas fa-spinner fa-spin'></i> " + doing_label);
            jQuery(_object).closest("tr").find(".row-actions").addClass("visible");
            var post_data = {
                "form-id": form_id,
                "_token": "{{ csrf_token() }}"
            };
            jQuery.ajax({
                type: "POST",
                url: "{{ route('toggle-form-shareable') }}",
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
                    // jQuery(_object).html(do_label);
                    // jQuery(_object).closest("tr").find(".row-actions").removeClass("visible");
                    // leform_sending = false;
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

        function _leform_forms_copy_template(_object) {
            if (leform_sending) {
                return false;
            }
            leform_sending = true;
            var form_id = jQuery(_object).attr("data-id");
            var doing_label = jQuery(_object).attr("data-doing");
            var do_label = jQuery(_object).html();
            jQuery(_object).html("<i class='fas fa-spinner fa-spin'></i> " + doing_label);
            jQuery(_object).closest("tr").find(".row-actions").addClass("visible");
            var post_data = {
                "form-id": form_id,
                "_token": "{{ csrf_token() }}"
            };
            jQuery.ajax({
                type: "POST",
                url: "{{ route('copy-template') }}",
                data: post_data,
                success: function (return_data) {
                    try {
                        var data;
                        if (typeof return_data == 'object') data = return_data;
                        else data = jQuery.parseJSON(return_data);
                        if (data.status == "OK") {
                            leform_global_message_show("success", data.message);
                            location.href = `/forms/create?page=formwerk&id=${data.id}`;
                        } else if (data.status == "ERROR") {
                            leform_global_message_show("danger", data.message);
                        } else {
                            leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
                        }
                    } catch (error) {
                        leform_global_message_show("danger", leform_esc_html__("Something went wrong. We got unexpected server response."));
                    }
                    jQuery(_object).html(do_label);
                    // jQuery(_object).closest("tr").find(".row-actions").removeClass("visible");
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

        let leform_translations = @json($frontendTranslations);
    </script>
@endsection

@section('content')
    @if (sizeof($forms) > 0)
        <div class="bg-gray-100 grid grid-cols-3 gap-5 items-center justify-center">
            @foreach($forms as $form)
                <div class="form-item p-6 bg-white flex items-center space-x-6 rounded-lg shadow-md hover:scale-105 transition transform duration-500 cursor-pointer">
                    <a href="{{ route('create-form', ['id' => $form['id']]) }}" class="block">
                        <div>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                        </div>
                    </a>

                    <div>
                        <a href="{{ route('preview-form', ['id' => $form['id'], 'isTemplateView' => true]) }}" class="block">
                            <h1 class="text-xl font-bold text-gray-700 mb-2">
                                {{ $form['name'] }}
                            </h1>
                        </a>

                        <p class="text-gray-600 w-80 text-sm">
                            {{ __('Shared by tenant on date', [
                                'tenant' => $form['company_name'],
                                'date' => date('d.m.Y', strtotime($form['share_date']))
                            ]) }}
                        </p>

                        <p class="text-gray-600 w-80 text-xs flex space-x-4">
                            <a
                                href="#"
                                data-id="{{ $form['id'] }}"
                                data-doing="{{ __('Copying') }}..."
                                onclick="return leform_forms_copy_template(this);"
                            >
                                {{ __('Copy to my workspace') }}
                            </a>
                        </p>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <tr>
            <td colspan="4" class="leform-table-list-empty">
                {{ __('List is empty') }}.
            </td>
        </tr>
    @endif

    <div id="leform-global-message"></div>

    <div class="leform-dialog-overlay" id="leform-dialog-overlay"></div>
    <div class="leform-dialog" id="leform-dialog">
        <div class="leform-dialog-inner">
            <div class="leform-dialog-title">
                <a href="#" title="{{ __('Close') }}" onclick="return leform_dialog_close();">
                    <i class="fas fa-times"></i>
                </a>
                <h3><i class="fas fa-cog"></i><label></label></h3>
            </div>
            <div class="leform-dialog-content">
                <div class="leform-dialog-content-html">
                </div>
            </div>
            <div class="leform-dialog-buttons">
                <a class="leform-dialog-button leform-dialog-button-ok" href="#" onclick="return false;"><i class="fas fa-check"></i><label></label></a>
                <a class="leform-dialog-button leform-dialog-button-cancel" href="#" onclick="return false;"><i class="fas fa-times"></i><label></label></a>
            </div>
            <div class="leform-dialog-loading"><i class="fas fa-spinner fa-spin"></i></div>
        </div>
    </div>

    <div class="leform-admin-popup-overlay" id="leform-more-using-overlay"></div>
    <div class="leform-admin-popup" id="leform-more-using">
        <div class="leform-admin-popup-inner">
            <div class="leform-admin-popup-title">
                <a href="#" title="{{ __('Close') }}" onclick="return leform_more_using_close();">
                    <i class="fas fa-times"></i>
                </a>
                <h3>
                    <i class="fas fa-code"></i>
                   {{ __('How To Use') }}
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
            leform_forms_ready();
        });
    </script>
@endsection
