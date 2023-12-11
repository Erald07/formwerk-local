@extends('layouts.forms')

@section('custom-head')
    <link rel="stylesheet" href="{{ asset('css/fontawesome-all.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/jquery-ui.min.css') }}">
    <link rel="stylesheet" href="{{ asset('css/color-picker.min.css') }}">

    <link rel="stylesheet" id="leform" href="{{ asset('css/halfdata-plugin/admin.css') }}">
    <link rel="stylesheet" id="leform-front" href="{{ asset('css/halfdata-plugin/style.css') }}">
    <link rel="stylesheet" id="tooltipster" href="{{ asset('css/halfdata-plugin/tooltipster.bundle.min.css') }}">
    <link rel="stylesheet" id="leform-fa" href="{{ asset('css/halfdata-plugin/leform-fa.css') }}">
    <link rel="stylesheet" id="leform-if" href="{{ asset('css/halfdata-plugin/leform-if.css') }}">
    <link rel="stylesheet" id="font-awesome-5.7.2" href="{{ asset('css/halfdata-plugin/fontawesome-all.min.css') }}">
    <link rel="stylesheet" id="material-icons-3.0.1" href="{{ asset('css/halfdata-plugin/material-icons.css') }}">
    <link rel="stylesheet" id="airdatepicker" href="{{ asset('css/halfdata-plugin/airdatepicker.css') }}">
    <link rel="stylesheet" id="minicolors" href="{{ asset('css/halfdata-plugin/jquery.minicolors.css') }}">

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
                            if (data.form_status == "active") jQuery(_object).closest(".form-item").find(
                                ".leform-table-list-badge-status").html("");
                            else jQuery(_object).closest(".form-item").find(".leform-table-list-badge-status")
                                .html(
                                    "<span class='leform-badge leform-badge-danger'>Inactive</span>");
                            leform_global_message_show("success", data.message);
                        } else if (data.status == "ERROR") {
                            jQuery(_object).closest("tr").find("td.column-active").html(form_status_label);
                            leform_global_message_show("danger", data.message);
                        } else {
                            jQuery(_object).closest("tr").find("td.column-active").html(form_status_label);
                            leform_global_message_show("danger", leform_esc_html__(
                                "Something went wrong. We got unexpected server response."));
                        }
                    } catch (error) {
                        jQuery(_object).closest("tr").find("td.column-active").html(form_status_label);
                        leform_global_message_show("danger", leform_esc_html__(
                            "Something went wrong. We got unexpected server response."));
                    }
                    jQuery(_object).closest("tr").find(".row-actions").removeClass("visible");
                    leform_sending = false;
                },
                error: function(XMLHttpRequest, textStatus, errorThrown) {
                    jQuery(_object).closest("tr").find(".row-actions").removeClass("visible");
                    jQuery(_object).html(do_label);
                    jQuery(_object).closest("tr").find("td.column-active").html(form_status_label);
                    leform_global_message_show("danger", leform_esc_html__(
                        "Something went wrong. We got unexpected server response."));
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
                                leform_esc_html__(
                                    "Something went wrong. We got unexpected server response.")
                            );
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
                            leform_global_message_show("danger", leform_esc_html__(
                                "Something went wrong. We got unexpected server response."));
                        }
                    } catch (error) {
                        leform_global_message_show("danger", leform_esc_html__(
                            "Something went wrong. We got unexpected server response."));
                    }
                    // jQuery(_object).html(do_label);
                    // jQuery(_object).closest("tr").find(".row-actions").removeClass("visible");
                    // leform_sending = false;
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


        function _leform_folder_delete(_object) {
            if (leform_sending) {
                return false;
            }
            leform_sending = true;
            var folder_id = jQuery(_object).attr("data-id");
            var doing_label = jQuery(_object).attr("data-doing");
            var do_label = jQuery(_object).html();
            jQuery(_object).html("<i class='fas fa-spinner fa-spin'></i> " + doing_label);
            jQuery(_object).closest("tr").find(".row-actions").addClass("visible");
            var post_data = {
                "action": "leform-folder-delete",
                "folder-id": folder_id,
                "_token": "{{ csrf_token() }}",
            };
            jQuery.ajax({
                type: "DELETE",
                url: `/folder/${folder_id}`,
                data: post_data,
                success: function(return_data) {
                    try {
                        var data;
                        if (typeof return_data == 'object') {
                            data = return_data;
                        } else {
                            data = jQuery.parseJSON(return_data);
                        }
                        if (data.id) {
                            window.location.reload();
                            leform_global_message_show("success", data.message);
                        } else if (data.status == "ERROR") {
                            leform_global_message_show("danger", data.message);
                        } else {
                            leform_global_message_show(
                                "danger",
                                leform_esc_html__(
                                    "Something went wrong. We got unexpected server response.")
                            );
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

        function _leform_forms_create_folder(_object, name, id) {
            if (leform_sending || !name) {
                return false;
            }
            leform_sending = true;
            var parent_id = jQuery(_object).attr("data-parent");
            var doing_label = jQuery(_object).attr("data-doing");
            var do_label = jQuery(_object).html();
            jQuery(_object).html("<i class='fas fa-spinner fa-spin'></i> " + doing_label);
            jQuery(_object).closest("tr").find(".row-actions").addClass("visible");
            var post_data = {
                "action": id === 0 ? "leform-folder-create" : "leform-folder-rename",
                "parent_id": parent_id,
                "_token": "{{ csrf_token() }}",
                "name": name
            };
            jQuery.ajax({
                type: id === 0 ? "POST" : "PUT",
                url: `/folder${id === 0 ? '': `/${id}`}`,
                data: post_data,
                success: function(return_data) {
                    try {
                        var data;
                        if (typeof return_data == 'object') {
                            data = return_data;
                        } else {
                            data = jQuery.parseJSON(return_data);
                        }
                        if (data.id) {
                            leform_global_message_show("success", data.message);
                            window.location.reload();
                        } else if (data.status == "ERROR") {
                            leform_global_message_show("danger", data.message);
                        } else {
                            leform_global_message_show(
                                "danger",
                                leform_esc_html__(
                                    "Something went wrong. We got unexpected server response.")
                            );
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

        function _leform_forms_move_folder(_object, newParent, id, oldParent) {
            if (leform_sending || newParent === oldParent || !id) {
                return false;
            }

            leform_sending = true;
            var doing_label = jQuery(_object).attr("data-doing");
            var dataType = jQuery(_object).attr("data-type");
            var do_label = jQuery(_object).html();
            jQuery(_object).html("<i class='fas fa-spinner fa-spin'></i> " + doing_label);
            jQuery(_object).closest("tr").find(".row-actions").addClass("visible");
            var post_data = {
                "action": "leform-folder-move",
                "_token": "{{ csrf_token() }}",
            };
            jQuery.ajax({
                type: "PUT",
                url: `/${dataType}/${id}/parent/${newParent || 0}`,
                data: post_data,
                success: function(return_data) {
                    try {
                        var data;
                        if (typeof return_data == 'object') {
                            data = return_data;
                        } else {
                            data = jQuery.parseJSON(return_data);
                        }
                        if (data.id) {
                            leform_global_message_show("success", data.message);
                            window.location.reload();
                        } else if (data.status == "ERROR") {
                            leform_global_message_show("danger", data.message);
                        } else {
                            leform_global_message_show(
                                "danger",
                                leform_esc_html__(
                                    "Something went wrong. We got unexpected server response.")
                            );
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
        let leform_translations = @json($frontendTranslations);
    </script>
    <style>
        .form-view-dropdown {
            position: absolute;
            margin: 0;
            top: 10px;
            right: 10px;
        }

        .form-content-card {
            position: relative;
        }

        .bg-primary {
            background: var(--primary-color) !important;
        }

        .form-item:hover {
            z-index: 50;
        }
    </style>
@endsection

@section('page-header')
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-end">
            <div class="py-4">
                <a href="{{ route('create-form') }}?folder={{ $currentFolder }}"
                    class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 active:bg-gray-900 focus:outline-none focus:border-gray-900 focus:ring ring-gray-300 disabled:opacity-25 transition ease-in-out duration-150 ml-3 float-right bg-green-500 focus:border-green-900 hover:bg-green-600 active:bg-green-900">
                    {{ __('Create Form') }}
                </a>
                <a href="#" data-doing="{{ __('Creating') }}..." data-parent="{{ $currentFolder }}"
                    onclick="return leform_forms_create_folder(this, '');"
                    class="bg-primary inline-flex items-center px-4 py-2 bg-blue-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-blue-700 active:bg-blue-900 focus:outline-none focus:border-blue-900 focus:ring ring-blue-300 disabled:opacity-25 transition ease-in-out duration-150 ml-3 float-right bg-blue-500 focus:border-blue-900 hover:bg-blue-600 active:bg-blue-900">
                    {{ __('Create Folder') }}
                </a>
            </div>
        </div>
    </div>
@endsection

@section('content')
    @if (sizeof($breadcrumb) > 0)
        <div class="bg-gray-100 grid grid-cols-3 gap-5 items-center justify-center mb-5">
            <span class="breadcrum">
                <a href="{{ route('dashboard') }}">Home</a>
                @foreach ($breadcrumb as $b)
                    &nbsp;>&nbsp;<a href="/dashboard/{{ $b['id'] }}">{{ $b['name'] }}</a>
                @endforeach
            </span>
        </div>
    @endif
    @if (sizeof($folders) > 0)
        <div class="bg-gray-100 grid grid-cols-3 gap-5 items-center justify-center">
            @foreach ($folders as $folder)
                <div
                    class="bg-primary form-item p-6 bg-blue-500 flex items-center space-x-6 rounded-lg shadow-md hover:scale-105 transition transform duration-500 cursor-pointer text-white mb-5">
                    <a href="/dashboard/{{ $folder_ids }}{{ $folder['id'] }}" class="block text-white">

                        <div>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                        </div>
                    </a>
                    <a href="/dashboard/{{ $folder_ids }}{{ $folder['id'] }}" class="block text-white">

                        <div>
                            <h1 class="text-xl font-bold text-white mb-2">
                                {{ $folder['name'] }}
                            </h1>
                            <p class="text-white w-80 text-sm">
                                <small>{{ __('Forms') }}: {{ $folder['forms_count'] }}</small>
                            </p>

                        </div>


                    </a>
                    <div class="sm:flex sm:items-center sm:ml-6 form-view-dropdown ">
                        <x-dropdown align="right" width="48">
                            <x-slot name="trigger">
                                <button
                                    class="flex items-center text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none focus:text-gray-700 focus:border-gray-300 transition duration-150 ease-in-out">
                                    <div class="ml-1">
                                        <i class="fas fa-ellipsis-v text-white" style="font-size: 20px"></i>
                                    </div>
                                </button>
                            </x-slot>

                            <x-slot name="content">
                                <a href="#"
                                    class="block px-4 py-2 text-sm leading-5 text-gray-700 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out"
                                    data-id="{{ $folder['id'] }}" data-doing="{{ __('Deleting') }}..."
                                    onclick="return leform_folder_delete(this);">
                                    {{ __('Delete') }}
                                </a>
                                <a href="#"
                                    class="block px-4 py-2 text-sm leading-5 text-gray-700 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out"
                                    data-id="{{ $folder['id'] }}" data-doing="{{ __('Deleting') }}..."
                                    data-parent="{{ $folder['parent_id'] }}"
                                    onclick="return leform_forms_create_folder(this, '{{ $folder['name'] }}', {{ $folder['id'] }});">
                                    {{ __('Rename') }}
                                </a>
                                <a href="#"
                                    class="block px-4 py-2 text-sm leading-5 text-gray-700 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out"
                                    data-id="{{ $folder['id'] }}" data-doing="{{ __('Moving') }}..." data-type="folder"
                                    onclick="return leform_forms_move_folder(this, '{{ $listOfFolders }}' , {{ $folder['id'] }}, {{ $folder['parent_id'] }});">
                                    {{ __('Move') }}
                                </a>
                            </x-slot>
                        </x-dropdown>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
    @if (sizeof($forms) > 0)
        <div class="bg-gray-100 grid grid-cols-3 gap-5 items-center justify-center">
            @foreach ($forms as $form)
                <div
                    class="form-item p-6 bg-white flex items-center space-x-6 rounded-lg shadow-md hover:scale-105 transition transform duration-500 cursor-pointer">
                    <a href="{{ route('create-form', ['id' => $form['id']]) }}" class="block">
                        <div>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24"
                                stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                        </div>
                    </a>

                    <div class="form-content-card">
                        <a href="{{ route('create-form', ['id' => $form['id']]) }}" class="block">
                            <h1 class="text-xl font-bold text-gray-700 mb-2 mr-5">
                                {{ $form['name'] }}

                                <span class="leform-table-list-badge-status">
                                    @if ($form['active'] < 1)
                                        <span class="leform-badge leform-badge-danger">
                                            {{ __('Inactive') }}
                                        </span>
                                    @endif
                                </span>
                            </h1>
                        </a>

                        <p class="text-gray-600 w-80 text-sm">
                            <a href="{{ route('entries', ['form' => $form['id']]) }}" class="font-bold">
                                {{ __('Entries') }}: {{ count($form['records']) }}
                            </a>
                            <br>
                            @if ($form['share_form_id'])
                                <small>
                                    {{ __('Shared by tenant on date', ['tenant' => $form['company_name'], 'date' => date('d.m.Y', $form['created'])]) }}
                                </small>
                            @else
                                <small>{{ __('Created') }}: {{ date('d.m.Y', $form['created']) }}</small>
                            @endif
                        </p>

                        {{-- <p class="text-gray-600 w-80 text-xs flex space-x-4">
                        </p> --}}
                    </div>

                    <div class="sm:flex sm:items-center sm:ml-6 form-view-dropdown ">
                        <x-dropdown align="right" width="48">
                            <x-slot name="trigger">
                                <button
                                    class="flex items-center text-sm font-medium text-gray-500 hover:text-gray-700 hover:border-gray-300 focus:outline-none focus:text-gray-700 focus:border-gray-300 transition duration-150 ease-in-out">
                                    <div class="ml-1">
                                        <i class="fas fa-ellipsis-v text-white"
                                            style="font-size: 20px; color: var(--primary-color)"></i>
                                    </div>
                                </button>
                            </x-slot>

                            <x-slot name="content">
                                <a href="#" data-id="{{ $form['id'] }}"
                                    class="block px-4 py-2 text-sm leading-5 text-gray-700 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out"
                                    onclick="return leform_forms_status_toggle(this);"
                                    @if ($form['active'] > 0) data-status="active"
                                        data-doing="{{ __('Deactivating') }}..."
                                    @else
                                        data-status="inactive"
                                        data-doing="{{ __('Activating') }}..." @endif>
                                    @if ($form['active'] > 0)
                                        {{ __('Deactivate') }}
                                    @else
                                        {{ __('Activate') }}
                                    @endif
                                </a>

                                <a href="#" data-id="{{ $form['id'] }}" data-doing="{{ __('Deleting') }}..."
                                    class="block px-4 py-2 text-sm leading-5 text-gray-700 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out"
                                    onclick="return leform_forms_delete(this);">
                                    {{ __('Delete') }}
                                </a>

                                <a href="#" data-id="{{ $form['id'] }}" data-doing="{{ __('Duplicating') }}..."
                                    class="block px-4 py-2 text-sm leading-5 text-gray-700 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out"
                                    onclick="return leform_forms_duplicate(this);">
                                    {{ __('Duplicate') }}
                                </a>

                                <a href="#" data-id="{{ $form['id'] }}" data-value="{{ $form['shareable'] }}"
                                    class="block px-4 py-2 text-sm leading-5 text-gray-700 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out"
                                    data-doing="{{ __('Sharing') }}..." onclick="return leform_forms_share(this);">
                                    @if ($form['shareable'])
                                        {{ __('Unshare') }}
                                    @else
                                        {{ __('Share') }}
                                    @endif
                                </a>
                                <a href="#"
                                    class="block px-4 py-2 text-sm leading-5 text-gray-700 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 transition duration-150 ease-in-out"
                                    data-id="{{ $form['id'] }}" data-doing="{{ __('Moving') }}..." data-type="forms"
                                    onclick="return leform_forms_move_folder(this, '{{ $listOfFolders }}' , {{ $form['id'] }}, {{ $form['folder_id'] }});">
                                    {{ __('Move') }}
                                </a>
                            </x-slot>
                        </x-dropdown>
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
                <a class="leform-dialog-button leform-dialog-button-ok" href="#" onclick="return false;"><i
                        class="fas fa-check"></i><label></label></a>
                <a class="leform-dialog-button leform-dialog-button-cancel" href="#" onclick="return false;"><i
                        class="fas fa-times"></i><label></label></a>
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
