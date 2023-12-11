<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="stylesheet" href="{{ asset('css/app.css') }}">
        <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
        <link rel="stylesheet" id="font-awesome-5.7.2" href="{{ asset("css/halfdata-plugin/fontawesome-all.min.css") }}">
        <!-- Fonts -->
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap">
        <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.12.4/jquery.min.js"></script>
        <script
            id="leform-remote"
            src="{{ asset('js/halfdata-plugin/leform.js') }}"
            data-handler="{{ route('form-remote-init') }}"
        ></script>
        <script src="{{ asset('js/qrcode.min.js') }}"></script>
        <script
            type="text/javascript"
            src="{{ asset('js/halfdata-plugin/signature_pad.js') }}"
        ></script>
		<script
            type="text/javascript"
            src="{{ asset('js/iframe-messenger.js') }}"
        ></script>
        <script>
            const jwtToken = @json($jwtToken);
            const systemVariables = @json($systemVariables);
            const jwtVariables = @json($jwtVariables);
            const getVariables = @json($getVariables);

            function leform_submit(_object, _action) {
                var prev_page_id;
                var button_pressed = false;
                if (jQuery(_object).hasClass("leform-button")) button_pressed = true;
                var form_uid = jQuery(_object).closest(".leform-form").attr("data-id");
                var form_id = jQuery(_object).closest(".leform-form").attr("data-form-id");
                var page_id = jQuery(_object).closest(".leform-form").attr("data-page");
                var session_length = jQuery(_object).closest(".leform-form").attr("data-session");
                var allowed_actions = new Array("next", "prev", "submit");
                if (typeof _action == undefined || _action == "") _action = "submit";
                else if (allowed_actions.indexOf(_action) == -1) _action = "submit";
                jQuery(".leform-form-"+form_uid).find(".leform-element-error").fadeOut(300, function(){
                    jQuery(this).remove();
                });
                if (_action == "prev") {
                    leform_sending = false;
                    if (leform_seq_pages.hasOwnProperty(form_uid) && leform_seq_pages[form_uid].length > 0) {
                        prev_page_id = leform_seq_pages[form_uid][leform_seq_pages[form_uid].length-1];
                        leform_seq_pages[form_uid].splice(leform_seq_pages[form_uid].length-1, 1);
                        if (leform_popup_active_id) {
                            jQuery("#leform-popup-"+leform_popup_active_id+" .leform-popup-close").fadeOut(300, function(){
                                jQuery("#leform-popup-"+leform_popup_active_id+" .leform-popup-close").fadeIn(300);
                            });
                        }
                        jQuery(".leform-progress-"+form_uid+".leform-progress-outside[data-page='"+page_id+"']").fadeOut(300);
                        jQuery(".leform-form-"+form_uid+"[data-page='"+page_id+"']").fadeOut(300, function(){
                            jQuery(".leform-form-"+form_uid+"[data-page='"+prev_page_id+"']").fadeIn(300);
                            jQuery(".leform-progress-"+form_uid+".leform-progress-outside[data-page='"+prev_page_id+"']").fadeIn(300);
                            leform_resize();
                        });
                        return false;
                    } else return false;
                }

                if (button_pressed) {
                    var original_icon = jQuery(_object).attr("data-original-icon");
                    if (typeof original_icon === typeof undefined || original_icon === false) {
                        original_icon = jQuery(_object).children("i").first().attr("class");
                        if (typeof original_icon !== typeof undefined && original_icon !== false) {
                            jQuery(_object).attr("data-original-icon", original_icon);
                        }
                    }
                    jQuery(_object).children("i").first().attr("class", "leform-if leform-if-spinner leform-if-spin");
                    jQuery(_object).find("span").text(jQuery(_object).attr("data-loading"));
                }

                jQuery(".leform-form-"+form_uid).find(".leform-button").addClass("leform-button-disabled");
                jQuery(".leform-form-"+form_uid+"[data-page='"+page_id+"']").children(".leform-confirmaton-message").slideUp(300, function(){
                    jQuery(".leform-form-"+form_uid+"[data-page='"+page_id+"']").children(".leform-confirmaton-message").remove();
                });

                if (leform_uploads.hasOwnProperty(form_uid)) {
                    var waiting_upload = false;
                    for (var upload_id in leform_uploads[form_uid]) {
                        if ((leform_uploads[form_uid]).hasOwnProperty(upload_id)) {
                            if (leform_uploads[form_uid][upload_id] == "LOADING") {
                                waiting_upload = true;
                            }
                        }
                    }
                    if (waiting_upload) {
                        setTimeout(function(){
                            leform_submit(_object, _action);
                        }, 500);
                        return false;
                    }
                }

                if (leform_sending) return false;
                leform_sending = true;

                var all_pages = new Array();
                jQuery(".leform-form-"+form_uid).each(function(){
                    all_pages.push(jQuery(this).attr("data-page"));
                });

                if (typeof SignaturePad != typeof undefined) {
                    jQuery(".leform-form-"+form_uid).find(".leform-signature").each(function(){
                        var element_id = jQuery(this).closest(".leform-element").attr("data-id");
                        if (leform_signatures.hasOwnProperty(form_uid+"-"+element_id)) {
                            var data_url = "";
                            if (!(leform_signatures[form_uid+"-"+element_id]).isEmpty()) data_url = (leform_signatures[form_uid+"-"+element_id]).toDataURL();
                            jQuery(this).closest(".leform-element").find("input").val(data_url);
                        }
                    });
                }

                var xd = jQuery(".leform-form-"+form_uid).attr("data-xd");
                if (!xd) xd = "off";

                var post_data = {
                    "action" : "leform-front-"+_action,
                    "form-id" : form_id,
                    "page-id" : page_id,
                    "form-data" : leform_encode64(jQuery(".leform-form-"+form_uid).find("textarea, input, select").serialize()),
                    "hostname" : window.location.hostname,
                    "page-title" : leform_consts["page-title"],
                    "jwtToken" : jwtToken,
                    "systemVariables" : systemVariables,
                    "getVariables": getVariables,
                    "_token": "{{ csrf_token() }}",
                };
                if (typeof leform_preview_mode != "undefined" && leform_preview_mode == "on") post_data["preview"] = "on";
                if (leform_customjs_handlers.hasOwnProperty(form_uid)) {
                    leform_customjs_handlers[form_uid].errors = {};
                    if (leform_customjs_handlers[form_uid].hasOwnProperty("beforesubmit") && typeof leform_customjs_handlers[form_uid].beforesubmit == 'function') {
                        try {
                            leform_customjs_handlers[form_uid].beforesubmit();
                        } catch(error) {
                        }
                    }
                }
                jQuery.ajax({
                    // url		:	leform_vars['ajax-url'],
                    url		:	'{{ route("submit-form") }}',
                    data	:	post_data,
                    method	:	(leform_vars["method"] == "get" && xd == "on" ? "get" : "post"),
                    dataType:	(leform_vars["method"] == "get" && xd == "on" ? "jsonp" : "json"),
                    async	:	true,
                    success	: function(return_data, ...rest) {
                        try {
                            var data, temp;
                            if (typeof return_data == 'object') data = return_data;
                            else data = jQuery.parseJSON(return_data);
                            if (data.status == "OK") {
                                /* download pdf for user */
                                if (data.hasOwnProperty("pdfLink")) {
                                    window.open(data["pdfLink"]);
                                }

                                if (data.hasOwnProperty("record-id")) {
                                    jQuery(".leform-form-"+form_uid+" .leform-const-record-id").text(data["record-id"]);
                                }
                                if (leform_is_numeric(session_length) && session_length > 0) {
                                    leform_write_cookie("leform-session-"+form_id, "", 0);
                                    if (leform_sessions.hasOwnProperty(form_id)) delete leform_sessions[form_id];
                                }
                                if (data.hasOwnProperty("error")) {
                                    console.log(data["error"]);
                                }
                                if (leform_customjs_handlers.hasOwnProperty(form_uid)) {
                                    leform_customjs_handlers[form_uid].errors = {};
                                    if (leform_customjs_handlers[form_uid].hasOwnProperty("aftersubmitsuccess") && typeof leform_customjs_handlers[form_uid].aftersubmitsuccess == 'function') {
                                        try {
                                            leform_customjs_handlers[form_uid].aftersubmitsuccess();
                                        } catch(error) {
                                        }
                                    }
                                }
                                if (data.hasOwnProperty("forms")) {
                                    jQuery(".leform-form-"+form_uid+"[data-page='"+page_id+"']").append(data["forms"]);
                                    jQuery(".leform-form-"+form_uid+"[data-page='"+page_id+"']").find(".leform-send").trigger("click");
                                }
                                switch (data.type) {
                                    case 'message-redirect':
                                    case 'message-payment':
                                    case 'message':
                                        if (data['reset-form'] == "on") leform_reset_form(form_uid);
                                        jQuery(".leform-form-"+form_uid+"[data-page='"+page_id+"']").append("<div class='leform-confirmaton-message leform-element leform-element-html'>"+data.message+"</div>");
                                        jQuery(".leform-form-"+form_uid+"[data-page='"+page_id+"']").children(".leform-confirmaton-message").slideDown(300, function() {
                                            leform_resize();
                                        });
                                        if (parseInt(data.delay, 10) > 0) {
                                            setTimeout(function(){
                                                jQuery(".leform-form-"+form_uid+"[data-page='"+page_id+"']").children(".leform-confirmaton-message").slideUp(300, function(){
                                                    jQuery(".leform-form-"+form_uid+"[data-page='"+page_id+"']").children(".leform-confirmaton-message").remove();
                                                    leform_resize();
                                                });
                                                if (data.type == 'message-redirect') location.href = data.url;
                                                else if (data.type == 'message-payment') {
                                                    if (data.hasOwnProperty("payment-form")) {
                                                        jQuery(".leform-form-"+form_uid+"[data-page='"+page_id+"']").append(data["payment-form"]);
                                                        jQuery(".leform-form-"+form_uid+"[data-page='"+page_id+"']").find(".leform-pay").trigger("click");
                                                    } else if (data.hasOwnProperty("payment-message")) {
                                                        leform_popup_message_open(data["payment-message"]);
                                                    } else if (data.hasOwnProperty("stripe")) {
                                                        leform_stripe_checkout(data["stripe"]["public-key"], data["stripe"]["session-id"]);
                                                    } else if (data.hasOwnProperty("payumoney")) {
                                                        leform_payumoney_checkout(data["payumoney"]["request-data"]);
                                                    }
                                                }
                                            }, 1000*parseInt(data.delay, 10));
                                        } else {
                                            if (data.type == 'message-payment') {
                                                if (data.hasOwnProperty("payment-form")) {
                                                    jQuery(".leform-form-"+form_uid+"[data-page='"+page_id+"']").append(data["payment-form"]);
                                                    jQuery(".leform-form-"+form_uid+"[data-page='"+page_id+"']").find(".leform-pay").trigger("click");
                                                } else if (data.hasOwnProperty("payment-message")) {
                                                    leform_popup_message_open(data["payment-message"]);
                                                } else if (data.hasOwnProperty("stripe")) {
                                                    leform_stripe_checkout(data["stripe"]["public-key"], data["stripe"]["session-id"]);
                                                } else if (data.hasOwnProperty("payumoney")) {
                                                    leform_payumoney_checkout(data["payumoney"]["request-data"]);
                                                }
                                            }
                                        }
                                        break;
                                    case 'redirect':
                                        if (data['reset-form'] == "on") leform_reset_form(form_uid);
                                        location.href = data.url;
                                        break;
                                    case 'payment':
                                        if (data['reset-form'] == "on") leform_reset_form(form_uid);
                                        if (data.hasOwnProperty("payment-form")) {
                                            jQuery(".leform-form-"+form_uid+"[data-page='"+page_id+"']").append(data["payment-form"]);
                                            jQuery(".leform-form-"+form_uid+"[data-page='"+page_id+"']").find(".leform-pay").trigger("click");
                                        } else if (data.hasOwnProperty("payment-message")) {
                                            leform_popup_message_open(data["payment-message"]);
                                        } else if (data.hasOwnProperty("stripe")) {
                                            leform_stripe_checkout(data["stripe"]["public-key"], data["stripe"]["session-id"]);
                                        } else if (data.hasOwnProperty("payumoney")) {
                                            leform_payumoney_checkout(data["payumoney"]["request-data"]);
                                        }
                                        break;
                                    case 'page-redirect':
                                    case 'page-payment':
                                        if (parseInt(data.delay, 10) > 0) {
                                            setTimeout(function(){
                                                if (data['reset-form'] == "on") leform_reset_form(form_uid);
                                                if (data.type == 'page-redirect') location.href = data.url;
                                                else {
                                                    if (data.hasOwnProperty("payment-form")) {
                                                        jQuery(".leform-form-"+form_uid+"[data-page='"+page_id+"']").append(data["payment-form"]);
                                                        jQuery(".leform-form-"+form_uid+"[data-page='"+page_id+"']").find(".leform-pay").trigger("click");
                                                    } else if (data.hasOwnProperty("payment-message")) {
                                                        leform_popup_message_open(data["payment-message"]);
                                                    } else if (data.hasOwnProperty("stripe")) {
                                                        leform_stripe_checkout(data["stripe"]["public-key"], data["stripe"]["session-id"]);
                                                    } else if (data.hasOwnProperty("payumoney")) {
                                                        leform_payumoney_checkout(data["payumoney"]["request-data"]);
                                                    }
                                                }
                                            }, 1000*parseInt(data.delay, 10));
                                        } else {
                                            if (data.type == 'page-redirect') location.href = data.url;
                                            else {
                                                if (data.hasOwnProperty("payment-form")) {
                                                    jQuery(".leform-form-"+form_uid+"[data-page='"+page_id+"']").append(data["payment-form"]);
                                                    jQuery(".leform-form-"+form_uid+"[data-page='"+page_id+"']").find(".leform-pay").trigger("click");
                                                } else if (data.hasOwnProperty("payment-message")) {
                                                    leform_popup_message_open(data["payment-message"]);
                                                } else if (data.hasOwnProperty("stripe")) {
                                                    leform_stripe_checkout(data["stripe"]["public-key"], data["stripe"]["session-id"]);
                                                } else if (data.hasOwnProperty("payumoney")) {
                                                    leform_payumoney_checkout(data["payumoney"]["request-data"]);
                                                }
                                            }
                                        }
                                    default:
                                        if (!leform_seq_pages.hasOwnProperty(form_uid)) leform_seq_pages[form_uid] = new Array();
                                        leform_seq_pages[form_uid].push(page_id);
                                        if (leform_popup_active_id) {
                                            jQuery("#leform-popup-"+leform_popup_active_id+" .leform-popup-close").fadeOut(300, function(){
                                                jQuery("#leform-popup-"+leform_popup_active_id+" .leform-popup-close").fadeIn(300);
                                            });
                                        }
                                        jQuery(".leform-progress-"+form_uid+".leform-progress-outside[data-page='"+page_id+"']").fadeOut(300);
                                        jQuery(".leform-form-"+form_uid+"[data-page='"+page_id+"']").fadeOut(300, function(){
                                            jQuery(".leform-progress-"+form_uid+".leform-progress-outside[data-page='confirmation']").fadeIn(300);
                                            jQuery(".leform-form-"+form_uid+"[data-page='confirmation']").fadeIn(300);
                                            if (leform_popup_active_id) {
                                                jQuery("#leform-popup-"+leform_popup_active_id+"-overlay").stop().animate({scrollTop: 0}, 300);
                                            } else {
                                                var element_top = jQuery(".leform-form-"+form_uid+"[data-page='confirmation']").offset().top;
                                                var viewport_top = jQuery(window).scrollTop();
                                                var viewport_bottom = viewport_top + jQuery(window).height();
                                                if (element_top < viewport_top || element_top > viewport_bottom) {
                                                    jQuery('html, body').stop().animate({scrollTop: element_top-60}, 300);
                                                }
                                            }
                                            leform_resize();
                                        });
                                        break;
                                }
                                leform_track(form_uid, "leform", "submit");
                            } else if (data.status == "REDIRECT") {
                                window.location.href = data.redirectUrl;
                                return false;
                            } else if (data.status == "NEXT") {
                                if (!leform_seq_pages.hasOwnProperty(form_uid)) leform_seq_pages[form_uid] = new Array();
                                leform_seq_pages[form_uid].push(page_id);
                                if (leform_popup_active_id) {
                                    jQuery("#leform-popup-"+leform_popup_active_id+" .leform-popup-close").fadeOut(300, function(){
                                        jQuery("#leform-popup-"+leform_popup_active_id+" .leform-popup-close").fadeIn(300);
                                    });
                                }
                                jQuery(".leform-progress-"+form_uid+".leform-progress-outside[data-page='"+page_id+"']").fadeOut(300);
                                jQuery(".leform-form-"+form_uid+"[data-page='"+page_id+"']").fadeOut(300, function(){
                                    jQuery(".leform-form-"+form_uid+"[data-page='"+data.page+"']").fadeIn(300);
                                    jQuery(".leform-progress-"+form_uid+".leform-progress-outside[data-page='"+data.page+"']").fadeIn(300);
                                    if (leform_popup_active_id) {
                                        jQuery("#leform-popup-"+leform_popup_active_id+"-overlay").stop().animate({scrollTop: 0}, 300);
                                    } else {
                                        var element_top = jQuery(".leform-form-"+form_uid+"[data-page='"+data.page+"']").offset().top;
                                        var viewport_top = jQuery(window).scrollTop();
                                        var viewport_bottom = viewport_top + jQuery(window).height();
                                        if (element_top < viewport_top || element_top > viewport_bottom) {
                                            jQuery('html, body').stop().animate({scrollTop: element_top-60}, 300);
                                        }
                                    }
                                    leform_resize();
                                });
                            } else if (data.status == "ERROR") {
                                var min_index = null;
                                for (var id in data["errors"]) {
                                    if (data["errors"].hasOwnProperty(id)) {
                                        temp = id.split(":");
                                        if (all_pages.indexOf(temp[0]) >= 0) {
                                            if (min_index == null) min_index = all_pages.indexOf(temp[0]);
                                            else if (all_pages.indexOf(temp[0]) < min_index) min_index = all_pages.indexOf(temp[0]);
                                        }
                                        jQuery(".leform-form-"+form_uid+"[data-page='"+temp[0]+"']").find(".leform-element-"+temp[1]).find(".leform-input").append("<div class='leform-element-error'><span>"+data["errors"][id]+"</span></div>");
                                        jQuery(".leform-form-"+form_uid+"[data-page='"+temp[0]+"']").find(".leform-element-"+temp[1]).find(".leform-uploader").append("<div class='leform-uploader-error'><span>"+data["errors"][id]+"</span></div>");
                                    }
                                }
                                if (min_index != null && all_pages[min_index] != page_id) {
                                    for (var i=min_index; i<all_pages.length; i++) {
                                        if (leform_seq_pages[form_uid].indexOf(all_pages[i]) >= 0) leform_seq_pages[form_uid].splice(leform_seq_pages[form_uid].indexOf(all_pages[i]), 1);
                                    }
                                    jQuery(".leform-form-"+form_uid+"[data-page='"+page_id+"']").fadeOut(300, function(){
                                        jQuery(".leform-form-"+form_uid+"[data-page='"+all_pages[min_index]+"']").fadeIn(300);
                                        page_id = all_pages[min_index];
                                        jQuery(".leform-form-"+form_uid).find(".leform-element-error, .leform-uploader-error").fadeIn(300);
                                    });
                                } else jQuery(".leform-form-"+form_uid).find(".leform-element-error, .leform-uploader-error").fadeIn(300);
                                jQuery(".leform-form-"+form_uid+"[data-page='"+page_id+"'] .leform-element").each(function(){
                                    if (jQuery(this).find(".leform-element-error, .leform-uploader-error").length > 0) {
                                        if (leform_popup_active_id) {
                                            jQuery("#leform-popup-"+leform_popup_active_id+"-overlay").stop().animate({scrollTop: 0}, 300);
                                            return false;
                                        } else {
                                            var element_top = jQuery(this).offset().top;
                                            var viewport_top = jQuery(window).scrollTop();
                                            var viewport_bottom = viewport_top + jQuery(window).height();
                                            if (element_top < viewport_top || element_top > viewport_bottom) {
                                                jQuery('html, body').stop().animate({scrollTop: element_top-60}, 300);
                                                return false;
                                            }
                                        }
                                    }
                                });
                            } else if (data.status == "FATAL") {

                            } else {

                            }
                        } catch(error) {
                            console.log(error);
                        }
                        if (button_pressed) {
                            jQuery(_object).find("span").text(jQuery(_object).attr("data-label"));
                            var original_icon = jQuery(_object).attr("data-original-icon");
                            if (typeof original_icon !== typeof undefined && original_icon !== false) jQuery(_object).children("i").first().attr("class", original_icon);
                        }
                        jQuery(".leform-form-"+form_uid).find(".leform-button").removeClass("leform-button-disabled");
                        leform_sending = false;
                    },
                    error	: function(XMLHttpRequest, textStatus, errorThrown) {
                        console.log(errorThrown);
                        if (button_pressed) {
                            jQuery(_object).find("span").text(jQuery(_object).attr("data-label"));
                            var original_icon = jQuery(_object).attr("data-original-icon");
                            if (typeof original_icon !== typeof undefined && original_icon !== false) jQuery(_object).children("i").first().attr("class", original_icon);
                        }
                        jQuery(".leform-form-"+form_uid).find(".leform-button").removeClass("leform-button-disabled");
                        leform_sending = false;
                    }
                });
                return false;
            }

            function leform_uploader_progress(_form_uid, _upload_id) {
                var post_data = {
                    "action" : "leform-upload-progress",
                    "upload-id" : _upload_id,
                    "hostname" : window.location.hostname,
                    "_token": "{{ csrf_token() }}",
                };
                if (leform_uploads[_form_uid][_upload_id] === "DELETED") return;
                else if (leform_uploads[_form_uid][_upload_id] === "UPLOADED") post_data["last-request"] = "on";
                jQuery.ajax({
                    url		:	'{{ route("upload-progress") }}',
                    data	:	post_data,
                    method	:	(leform_vars["method"] == "get" ? "get" : "post"),
                    dataType:	(leform_vars["method"] == "get" ? "jsonp" : "json"),
                    async	:	true,
                    success	: function(return_data) {
                        try {
                            let data, file_container, field_id;
                            field_id = jQuery("#"+_upload_id).closest(".leform-element").attr("data-id");
                            if (typeof return_data == 'object') data = return_data;
                            else data = jQuery.parseJSON(return_data);
                            if (data.status === "OK") {
                                leform_uploads[_form_uid][_upload_id] = 'OK';
                                if (data.hasOwnProperty("result")) {
                                    for (var i=0; i<data["result"].length; i++) {
                                        file_container = jQuery("#"+_upload_id).closest(".leform-upload-input").find(".leform-uploader-file-"+_upload_id+"[data-name='"+leform_escape_html(data["result"][i]["name"])+"']");
                                        if (data["result"][i]["status"] === "OK") {
                                            jQuery(file_container).find(".leform-uploader-progress").html("<div class='leform-uploader-progress-bar' style='width:100%;'></div>");
                                            jQuery(file_container).append("<input type='hidden' name='leform-"+field_id+"[]' value='"+leform_escape_html(data["result"][i]["uid"])+"' />");
                                        } else {
                                            jQuery(file_container).find(".leform-uploader-progress").html("<div class='leform-uploader-progress-error'>"+data["result"][i]["message"]+"</div>");
                                            jQuery(file_container).removeClass("leform-uploader-file-countable");
                                        }
                                        jQuery(file_container).addClass("leform-uploader-file-processed");
                                    }
                                }
                                jQuery("#"+_upload_id).closest(".leform-upload-input").find(".leform-uploader-file-"+_upload_id).each(function(){
                                    if (!jQuery(this).hasClass("leform-uploader-file-processed")) {
                                        jQuery(this).find(".leform-uploader-progress").html("<div class='leform-uploader-progress-error'>File can not be uploaded.</div>");
                                        jQuery(this).removeClass("leform-uploader-file-countable");
                                        jQuery(this).addClass("leform-uploader-file-processed");
                                    }
                                });
                                leform_input_changed("#"+_upload_id);
                                jQuery("#"+_upload_id).remove();
                            } else if (data.status == "LOADING") {
                                if (data.hasOwnProperty("progress")) {
                                    for (var i=0; i<data["progress"].length; i++) {
                                        file_container = jQuery("#"+_upload_id).closest(".leform-upload-input").find(".leform-uploader-file-"+_upload_id+"[data-name='"+leform_escape_html(data["progress"][i]["name"])+"']");
                                        if (file_container.length > 0) {
                                            jQuery(file_container).find(".leform-uploader-progress").html("<div class='leform-uploader-progress-bar' style='width:"+Math.ceil(100*parseInt(data["progress"][i]["bytes_processed"]) / parseInt(jQuery(file_container).attr("data-size"), 10))+"%;'></div>");
                                        }
                                    }
                                }
                                setTimeout(function(){
                                    leform_uploader_progress(_form_uid, _upload_id);
                                }, 500);
                            } else {
                                leform_uploads[_form_uid][_upload_id] = 'ERROR';
                                jQuery("#"+_upload_id).closest(".leform-upload-input").find(".leform-uploader-file-"+_upload_id).each(function(){
                                    if (!jQuery(this).hasClass("leform-uploader-file-processed")) {
                                        jQuery(this).find(".leform-uploader-progress").html("<div class='leform-uploader-progress-error'>Internal Error!</div>");
                                        jQuery(this).removeClass("leform-uploader-file-countable");
                                        jQuery(this).addClass("leform-uploader-file-processed");
                                    }
                                });
                                jQuery("#"+_upload_id).remove();
                            }
                        } catch(error) {
                            console.log(error);
                            leform_uploads[_form_uid][_upload_id] = 'ERROR';
                            jQuery("#"+_upload_id).closest(".leform-upload-input").find(".leform-uploader-file-"+_upload_id).each(function(){
                                if (!jQuery(this).hasClass("leform-uploader-file-processed")) {
                                    jQuery(this).find(".leform-uploader-progress").html("<div class='leform-uploader-progress-error'>Internal Error!</div>");
                                    jQuery(this).removeClass("leform-uploader-file-countable");
                                    jQuery(this).addClass("leform-uploader-file-processed");
                                }
                            });
                            jQuery("#"+_upload_id).remove();
                        }
                    },
                    error	: function(XMLHttpRequest, textStatus, errorThrown) {
                        console.log(errorThrown);
                        leform_uploads[_form_uid][_upload_id] = 'ERROR';
                        jQuery("#"+_upload_id).closest(".leform-upload-input").find(".leform-uploader-file-"+_upload_id).each(function(){
                            if (!jQuery(this).hasClass("leform-uploader-file-processed")) {
                                jQuery(this).find(".leform-uploader-progress").html("<div class='leform-uploader-progress-error'>Internal Error!</div>");
                                jQuery(this).removeClass("leform-uploader-file-countable");
                                jQuery(this).addClass("leform-uploader-file-processed");
                            }
                        });
                        jQuery("#"+_upload_id).remove();
                    }
                });
            }

            function leform_uploader_finish(_object) {
                var upload_id = jQuery(_object).closest(".leform-uploader").attr("id");
                var form_uid = jQuery(_object).closest(".leform-form").attr("data-id");
                if (
                    leform_uploads.hasOwnProperty(form_uid)
                    && leform_uploads[form_uid].hasOwnProperty(upload_id)
                    && leform_uploads[form_uid][upload_id] == "LOADING"
                ) {
                    leform_uploads[form_uid][upload_id] = "UPLOADED";
                }
            }

            function leform_uploader_start(_object) {
                console.log(_object, 'start');
                var temp;
                var upload_id = jQuery(_object).closest(".leform-uploader").attr("id");
                var form_uid = jQuery(_object).closest(".leform-form").attr("data-id");
                var form_element = jQuery(_object).closest(".leform-element");
                var max_size = parseInt(jQuery(form_element).attr("data-max-size"), 10)*1024*1024;
                var max_files = parseInt(jQuery(form_element).attr("data-max-files"), 10);
                temp = jQuery(form_element).attr("data-allowed-extensions");
                temp = temp.toLowerCase();
                var allowed_extensions = temp.split(",");
                temp = null;
                var countable_files = jQuery(_object).closest(".leform-upload-input").find(".leform-uploader-file-countable").length;
                var size_visual, ext, html = "";
                var error = false;
                var error_message = "";
                var files = jQuery(_object).find("input[type=file]")[0].files;
                if (files.length < 1) return false;
                for (var i=0; i<files.length; i++) {
                    if (countable_files + files.length > max_files) {
                        error = true;
                        error_message = jQuery(form_element).attr("data-max-files-error");
                        break;
                    }
                    ext = "."+(files[i].name).split(".").pop();
                    ext = ext.toLowerCase();
                    if (allowed_extensions.length > 0 && allowed_extensions[0] != "" && allowed_extensions.indexOf(ext) < 0) {
                        error = true;
                        error_message = jQuery(form_element).attr("data-allowed-extensions-error");
                        break;
                    }
                    if (max_size > 0 && files[i].size > max_size) {
                        error = true;
                        error_message = jQuery(form_element).attr("data-max-size-error");
                        break;
                    }
                    if (files[i].size > 4*1024*1024) size_visual = Math.round(10*files[i].size/(1024*1024))/10 + " Mb";
                    else if (files[i].size > 4*1024) size_visual = Math.round(10*files[i].size/1024)/10 + " Kb";
                    else size_visual = files[i].size + " bytes";
                    html += "<div class='leform-uploader-file leform-uploader-file-"+upload_id+" leform-uploader-file-countable' data-upload='"+upload_id+"' data-name='"+leform_escape_html(files[i].name)+"' data-size='"+files[i].size+"'><div class='leform-uploader-file-title'>"+leform_escape_html(files[i].name)+" ("+size_visual+")</div><div class='leform-uploader-progress'>Uploading...</div><span onclick='return leform_uploader_file_delete(this);'><i class='leform-if leform-if-times'></i></span></div>";
                }
                if (error) {
                    jQuery(_object).closest(".leform-uploader").append("<div class='leform-uploader-error'><span>"+error_message+"</span></div>");
                    jQuery(_object).closest(".leform-uploader").find(".leform-uploader-error").fadeIn(300);
                    return false;
                } else {
                    jQuery(_object).closest(".leform-uploader").find(".leform-button").remove();
                    var new_upload_id = Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15);
                    temp = leform_decode64(jQuery(_object).closest(".leform-upload-input").find(".leform-uploader-template").val());
                    temp = temp.replace(new RegExp("%%upload-id%%", 'g'), new_upload_id).replace(new RegExp("%%ajax-url%%", 'g'), leform_vars["ajax-url"]);
                    jQuery(_object).closest(".leform-uploaders").append(temp);
                    jQuery(_object).closest(".leform-upload-input").find(".leform-uploader-files").append(html);
                    if (!leform_uploads.hasOwnProperty(form_uid)) leform_uploads[form_uid] = {};
                    leform_uploads[form_uid][upload_id] = 'LOADING';
                }

                let formData = new FormData();
                for (const el of jQuery(_object)[0].elements) {
                    if (el.type === "file") {
                        for (const file of el.files) {
                            formData.append(el.name, file, file.name);
                        }
                    } else {
                        formData.append(el.name, el.value);
                    }
                }

                fetch(_object.action, { method: "POST", body: formData })
                    .then((res) => res.json())
                    .then((data) => {
                        if (leform_uploads[form_uid][upload_id] === "DELETED") {
                            return;
                        } else if (leform_uploads[form_uid][upload_id] === "UPLOADED") {
                            post_data["last-request"] = "on";
                        }

                        try {
                            let file_container, field_id;
                            field_id = jQuery("#"+upload_id).closest(".leform-element").attr("data-id");

                            leform_uploads[form_uid][upload_id] = 'OK';
                            if (data) {
                                for (let i = 0; i < data.length; i++) {
                                    file_container = jQuery("#"+upload_id)
                                        .closest(".leform-upload-input")
                                        .find(".leform-uploader-file-"+upload_id+"[data-name='"+leform_escape_html(data[i]["name"])+"']");
                                    if (data[i]["status"] === "OK") {
                                        jQuery(file_container)
                                            .find(".leform-uploader-progress")
                                            //.html("<div class='leform-uploader-progress-bar' style='width:100%;'></div>");
                                            .remove();
                                        jQuery(file_container).append("<input type='hidden' name='leform-"+field_id+"[]' value='"+leform_escape_html(data[i]["uid"])+"' />");
                                    } else {
                                        jQuery(file_container)
                                            .find(".leform-uploader-progress")
                                            .html("<div class='leform-uploader-progress-error'>"+data[i]["message"]+"</div>");
                                        jQuery(file_container).removeClass("leform-uploader-file-countable");
                                    }
                                    jQuery(file_container).addClass("leform-uploader-file-processed");
                                }
                            }
                            jQuery("#"+upload_id).closest(".leform-upload-input").find(".leform-uploader-file-"+upload_id).each(function(){
                                if (!jQuery(this).hasClass("leform-uploader-file-processed")) {
                                    jQuery(this).find(".leform-uploader-progress").html("<div class='leform-uploader-progress-error'>File can not be uploaded.</div>");
                                    jQuery(this).removeClass("leform-uploader-file-countable");
                                    jQuery(this).addClass("leform-uploader-file-processed");
                                }
                            });
                            leform_input_changed("#"+upload_id);
                            jQuery("#"+upload_id).remove();
                        } catch(error) {
                            console.log(error);
                            leform_uploads[form_uid][upload_id] = 'ERROR';
                            jQuery("#"+upload_id).closest(".leform-upload-input").find(".leform-uploader-file-"+upload_id).each(function(){
                                if (!jQuery(this).hasClass("leform-uploader-file-processed")) {
                                    jQuery(this).find(".leform-uploader-progress").html("<div class='leform-uploader-progress-error'>Internal Error!</div>");
                                    jQuery(this).removeClass("leform-uploader-file-countable");
                                    jQuery(this).addClass("leform-uploader-file-processed");
                                }
                            });
                            jQuery("#"+upload_id).remove();
                        }
                    })
                    .catch((error) => {
                        console.log(error);
                        leform_uploads[form_uid][upload_id] = 'ERROR';
                        jQuery("#"+upload_id).closest(".leform-upload-input").find(".leform-uploader-file-"+upload_id).each(function(){
                            if (!jQuery(this).hasClass("leform-uploader-file-processed")) {
                                jQuery(this).find(".leform-uploader-progress").html("<div class='leform-uploader-progress-error'>Internal Error!</div>");
                                jQuery(this).removeClass("leform-uploader-file-countable");
                                jQuery(this).addClass("leform-uploader-file-processed");
                            }
                        });
                        jQuery("#"+upload_id).remove();
                    });

                leform_uploader_finish();
                return false;
            }

            async function handleSubmitSignature(
                component,
                signaturePad,
                response,
            ) {
                if (response.status >= 400) {
                    return;
                }

                const signatureUrl = await response.text();
                /* clearInterval(intervalId); gotta make this global */
                clearIntervals();

                const signaturePreview = component
                    .querySelector(".signature-preview");
                signaturePreview.querySelector("img").src = signatureUrl;
                signaturePreview.classList.remove("hidden");
                if (signaturePad) {
                    signaturePad.clear();
                }

                component
                    .querySelector(".clear-signature")
                    .classList
                    .remove("hidden");

                component
                    .querySelector("form")
                    .classList
                    .add("hidden");

                component
                    .querySelector(".leform-input input[type='hidden']")
                    .value = signatureUrl;
            }

            function listenForSignatureSubmition(signatureHash, component) {
                const signaturePreview = component
                    .querySelector(".signature-preview");

                const intervalId = setInterval(async () => {
                    const res = await fetch(`/signature/${signatureHash}`);
                    handleSubmitSignature(component, null, res);
                }, 5000);
            }

            function prepareQRCode(component, token) {
                return new QRCode(
                    component,
                    {
                        text: `${window.location.origin}/signature-input/${encodeURI(token)}`,
                        width: 128,
                        height: 128,
                        colorDark : "#000000",
                        colorLight : "#ffffff",
                        correctLevel : QRCode.CorrectLevel.H
                    }
                );
            }

            function sendEmailHandler(token, sendEmailSection) {
                const emailInput = sendEmailSection
                    .querySelector("input[name='email']");

                const messageBox = sendEmailSection
                    .querySelector(".message-box");
                messageBox.innerText = "";
                const emailButton = sendEmailSection.querySelector("button");
                const loadingIcon = document.createElement("i");
                loadingIcon.classList.add("fas", "fa-spinner", "fa-spin");
                emailButton.appendChild(loadingIcon);

                emailButton.disabled = true;

                const email = emailInput.value;

                const formId = jQuery(emailInput)
                    .closest(".leform-form")
                    .attr("data-form-id");

                const emailFormData = new FormData();
                emailFormData.append("_token", "{{ csrf_token() }}");
                emailFormData.append("form-id", formId);
                emailFormData.append("signature_token", token);
                emailFormData.append("email", email);

                fetch("{{ route('email-signature-input-url') }}", {
                    method: "POST",
                    body: emailFormData,
                })
                    .then(async (response) => {
                        const message = await response.text();
                        messageBox.innerText = message;
                        if (response.status >= 400) {
                            messageBox.classList.add("bg-red-400");
                        } else {
                            messageBox.classList.add("bg-green-400");
                        }
                        emailButton.disabled = false;
                        emailButton.removeChild(loadingIcon);
                        setTimeout(() => {
                            messageBox.innerText = "";
                            messageBox.classList.remove("bg-red-400");
                            messageBox.classList.remove("bg-green-400");
                        }, 2000);
                    });
            }

            function sendSmsHandler(token, sendSmsSection) {
                const messageBox = sendSmsSection
                    .querySelector(".message-box");
                messageBox.innerText = "";
                const smsButton = sendSmsSection.querySelector("button");
                const loadingIcon = document.createElement("i");
                loadingIcon.classList.add("fas", "fa-spinner", "fa-spin");
                smsButton.appendChild(loadingIcon);

                smsButton.disabled = true;
                const phonenumber = sendSmsSection
                    .querySelector("input[name='phonenumber']")
                    .value

                const formId = jQuery(messageBox)
                    .closest(".leform-form")
                    .attr("data-form-id");

                const smsFormData = new FormData();
                smsFormData.append("form-id", formId);
                smsFormData.append("_token", "{{ csrf_token() }}");
                smsFormData.append("signature_token", token);
                smsFormData.append("phone-number", phonenumber);

                fetch("{{ route('sms-signature-input-url') }}", {
                    method: "POST",
                    body: smsFormData,
                })
                    .then(async (response) => {
                        const message = await response.text();
                        messageBox.innerText = message;
                        if (response.status >= 400) {
                            messageBox.classList.add("bg-red-400");
                        } else {
                            messageBox.classList.add("bg-green-400");
                        }
                        smsButton.disabled = false;
                        smsButton.removeChild(loadingIcon);
                        setTimeout(() => {
                            messageBox.innerText = "";
                            messageBox.classList.remove("bg-red-400");
                            messageBox.classList.remove("bg-green-400");
                        }, 2000);
                    });
            }

            function handleSignatureMethodChange(component, e) {
                const signatureMethods = component
                    .querySelectorAll(".signature-input-methods > div");
                const activeMethodClass = e.target.dataset.input;

                for (const signatureMethod of signatureMethods) {
                    const isActive = signatureMethod
                        .classList
                        .contains(activeMethodClass);
                    signatureMethod.classList.toggle("hidden", !isActive);
                }
            }

            async function getSignatureToken(component) {
                const signatureHeight = component.dataset.signatureHeight;

                const urlPath = "{{ route('add-form-signature-token', [
                    'formId' => $id
                ]) }}"
                const url = new URL(urlPath);
                url.searchParams.append("height", signatureHeight);

                const body = new FormData();
                body.append("_token", "{{ csrf_token() }}");

                try {
                    const res = await fetch(url, { method: "POST", body })
                    const hash = (await res.json()).token;
                    component.dataset.signatureHash = hash;
                    return hash;
                } catch (err) {
                    console.log(err);
                }
            }

            async function clearSignature(token, component) {
                const body = new FormData();
                body.append("_token", "{{ csrf_token() }}");

                const res = await fetch(`/signature/${token}/delete`, {
                    method: "POST",
                    body,
                });

                const signaturePreview = component
                    .querySelector(".signature-preview");
                signaturePreview.src = ""
                signaturePreview.classList.add("hidden");

                component
                    .querySelector(".clear-signature")
                    .classList
                    .add("hidden");

                component
                    .querySelector("form")
                    .classList
                    .remove("hidden");
                listenForSignatureSubmition(token, component);
            }

            function convertSignatureStringToImage(string) {
                return fetch(string)
                    .then(string => string.blob())
                    .then(blob => new File([blob], "File name", {
                        type: "image/png"
                    }))
                    .catch(console.log);
            }

            function clearIntervals() {
                let i = setInterval(() => {}, 100000);
                while (i >= 0) {
                    window.clearInterval(i--);
                }
            }

            function initSignatureInput(component) {
                console.log(component);
                const signatureContainer = component
                    .querySelector(".input-for-signature");
                const canvas = signatureContainer.querySelector("canvas");
                const signatureHash = component.dataset.signatureHash;

                const penColor = canvas.dataset.color;
                const height = canvas.dataset.height;

                canvas.height = height;
                canvas.width = canvas.parentElement.offsetWidth;
                window.addEventListener('resize', function (e) {
                    canvas.width = canvas.parentElement.offsetWidth;
                });

                const signaturePad = new SignaturePad(canvas, { penColor });

                signatureContainer
                    .querySelector("button.clear-signature")
                    .addEventListener("click", () => signaturePad.clear());

                signatureContainer
                    .querySelector("button.submit-signature")
                    .addEventListener("click", (e) => {
                        if (!signaturePad.isEmpty()) {
                            convertSignatureStringToImage(signaturePad.toDataURL())
                                .then((file) => {
                                    const formData = new FormData();
                                    formData.append("_token", "{{ csrf_token() }}");
                                    formData.append("signature_token", signatureHash);
                                    formData.append("signature", file);

                                    fetch("{{ route('submit-signature') }}", {
                                        method: "POST",
                                        body: formData,
                                    }).then(handleSubmitSignature.bind(
                                        null,
                                        component,
                                        signaturePad,
                                    ));
                                });
                        }
                    });
            }

            async function prepareSignatureComponent(component) {
                const token = await getSignatureToken(component);
                console.log(token);
                const methodSelectors = component
                    .querySelectorAll(".signature-input-methods-select input");

                for (const methodSelector of methodSelectors) {
                    methodSelector
                        .addEventListener(
                            "change",
                            handleSignatureMethodChange.bind(null, component)
                        );
                }

                component
                    .querySelector(".signature-preview .clear-signature")
                    .addEventListener(
                        "click",
                        clearSignature.bind(null, token, component)
                    );

                initSignatureInput(component);
                prepareQRCode(component.querySelector(".qr-code"), token);
                listenForSignatureSubmition(token, component);

                const emailInput = component
                    .querySelector(".send-email-for-signature");
                if (emailInput) {
                    emailInput
                        .querySelector("button")
                        .addEventListener(
                            "click",
                            sendEmailHandler.bind(null, token, emailInput)
                        );
                }

                const phoneNumberInput = component
                    .querySelector(".send-sms-for-signature");
                if (phoneNumberInput) {
                    phoneNumberInput
                        .querySelector("button")
                        .addEventListener(
                            "click",
                            sendSmsHandler.bind(null, token, phoneNumberInput)
                        );
                }
            }
            function onVisible(element, callback) {
                new IntersectionObserver((entries, observer) => {
                    entries.forEach(entry => {
                    if(entry.intersectionRatio > 0) {
                        callback(element);
                        observer.disconnect();
                    }
                    });
                }).observe(element);
            }
            function isHidden(el) {
                return (el.offsetParent === null)
            }
            function leform_signature_init() {
                const pages = document.getElementsByClassName("leform-form");
                for(const page of pages) {
                    if(isHidden(page)) {
                        onVisible(page, () => leform_signature_init_on_page(page));
                    } else {
                        leform_signature_init_on_page(page);
                    }
                }
            }
            function leform_signature_init_on_page(page) {

                const signatureComponents = page
                    .querySelectorAll(".leform-element[data-type='signature']");
                for (const component of signatureComponents) {
                    prepareSignatureComponent(component);
                }
            }
        </script>
        <style id="custom-css">{!! $customCss !!}</style>

        <script>
            function leform_repeaterinput_init() {
                const inputs = document
                    .querySelectorAll(".leform-element[data-type='repeater-input']");
                if (inputs.length === 0) {
                    return;
                }

                function countRows(tableBody) {
                    return tableBody.querySelectorAll("tr").length;
                }

                function resetRepeaterInputRowIndexes(tableBody) {
                    const rows = tableBody.querySelectorAll("tr");
                    for (let i = 0; i < rows.length; i++) {
                        const row = rows[i];
                        const fields = row.querySelectorAll("td");
                        for (const field of fields) {
                            const type = field.dataset.type;
                            switch (type) {
                                case "text":
                                case "email":
                                case "password":
                                case "number": {
                                    const input = field.querySelector("input");
                                    input.name = input.name.replace(/-\d*\[\]/, `-${i}[]`);
                                    break;
                                }
                                case "select": {
                                    const input = field.querySelector("select");
                                    input.name = input.name.replace(/-\d*\[\]/, `-${i}[]`);
                                    break;
                                }
                                case "star-rating": {
                                    const stars = field.querySelectorAll("input");
                                    const labels = field.querySelectorAll("label");
                                    for (let j = 0; j < stars.length; j++) {
                                        const star = stars[j];
                                        const label = labels[j];
                                        star.name = star.name.replace(
                                            /leform-(\d*)-(\d*)-(\d*)/,
                                            (match, elementId, _, columnId) => {
                                                return `leform-${elementId}-${i}-${columnId}`;
                                            }
                                        );
                                        star.id = star.name + `-${stars.length - j}`;
                                        label.setAttribute("for", star.id);
                                    }
                                    break;
                                }
                                default:
                                    break;
                            }
                        }
                    }
                }

                function resetRepeaterInputMathExpressions(form, tableBody, repeaterInputId) {
                    const repeaterInputMathExpressions = form
                        .querySelectorAll(`.leform-repeater-math[data-repeater-input-id='${repeaterInputId}']`);
                    const reorganizedMathExpressions = {};
                    for (const repeaterInputMathExpression of repeaterInputMathExpressions) {
                        const expressionId = repeaterInputMathExpression.dataset.id;
                        if (reorganizedMathExpressions[expressionId]) {
                            reorganizedMathExpressions[expressionId].push(repeaterInputMathExpression);
                        } else {
                            reorganizedMathExpressions[expressionId] = [repeaterInputMathExpression];
                        }
                    }

                    for (const mathExpressionGroup of Object.values(reorganizedMathExpressions)) {
                        for (let i = 0; i < mathExpressionGroup.length; i++) {
                            mathExpressionGroup[i].dataset.row = i + 1;
                        }
                    }
                }

                function removeRowMathExpressions(row) {
                    const tableBody = row.parentElement;
                    const rowIndex = ([...tableBody.children].indexOf(row) + 1);

                    const form = $(row).parents(".leform-inline")[0];
                    const repeaterInput = $(row)
                        .parents(".leform-element[data-type='repeater-input']")[0]
                    const repeaterInputId = repeaterInput.dataset.id;

                    const rowMathExpressions = form
                        .querySelectorAll(`.leform-repeater-math[data-row='${rowIndex}'][data-repeater-input-id='${repeaterInputId}']`);
                    for (const rowMathExpression of rowMathExpressions) {
                        rowMathExpression.parentElement.removeChild(rowMathExpression);
                    }

                    resetRepeaterInputMathExpressions(form, tableBody, repeaterInputId);
                }

                function removeRowHandler(row) {
                    const tableBody = row.parentElement;

                    removeRowMathExpressions(row);

                    tableBody.removeChild(row);

                    resetRepeaterInputRowIndexes(tableBody);

                    if (countRows(tableBody) === 1) {
                        const firstRowCloseButton = tableBody.querySelector("tr .remove-row");
                        firstRowCloseButton.classList.add("hidden");
                    }

                    const form_uid = $(tableBody)
                        .parents(".leform-inline")[0]
                        .querySelector(".leform-form")
                        .dataset.id;
                    leform_handle_math(form_uid);
                }

                function resetRow(row) {
                    const fields = row.querySelectorAll("td");
                    const rowCount = countRows(row.parentElement);
                    const rowIndex = rowCount - 1;

                    for (const field of fields) {
                        const type = field.dataset.type;
                        switch (type) {
                            case "text":
                            case "email":
                            case "password":
                            case "number": {
                                const input = field.querySelector("input");
                                input.value = input.dataset.defaultValue || "";
                                input.name = input
                                    .name
                                    .replace("0[]", `${rowIndex}[]`);
                                break;
                            }
                            case "select": {
                                const input = field.querySelector("select");
                                input.name = input
                                    .name
                                    .replace("0[]", `${rowIndex}[]`);
                                if (input.querySelector("option:checked")) {
                                    input.value = input
                                        .querySelector("option:checked")
                                        .value;
                                } else {
                                    input.value = "";
                                }
                                break;
                            }
                            case "date": {
                                const input = field.querySelector("input.leform-date");
                                input.value = "";
                                input.name = input
                                    .name
                                    .replace("0[]", `${rowIndex}[]`);
                                leform_datepicker_init(input);
                                break;
                            }
                            case "time": {
                                const input = field.querySelector("input.leform-time");
                                input.value = "";
                                input.name = input
                                    .name
                                    .replace("0[]", `${rowIndex}[]`);
                                leform_timepicker_init(input);
                                break;
                            }
                            case "rangeslider": {
                                const input = field.querySelector("input.leform-rangeslider");
                                const existingSlider = field.querySelector("span");
                                existingSlider.parentElement.removeChild(existingSlider);

                                input.dataset.from = input.dataset.min || 0;
                                input.name = input
                                    .name
                                    .replace("0[]", `${rowIndex}[]`);

                                leform_rangeslider_init(input);
                                input.classList.add("irs-hidden-input");
                                break;
                            }
                            case "star-rating": {
                                const stars = field.querySelectorAll("input");
                                const labels = field.querySelectorAll("label");
                                for (let i = 0; i < stars.length; i++) {
                                    const star = stars[i];
                                    const label = labels[i];
                                    star.checked = false;
                                    star.name = star.name.replace(
                                        /leform-(\d*)-(\d*)-(\d*)/,
                                        (match, elementId, _, columnId) => {
                                            return `leform-${elementId}-${rowIndex}-${columnId}`;
                                        }
                                    );
                                    star.id = star.name + `-${stars.length - i}`;
                                    label.setAttribute("for", star.id);
                                }
                                break;
                            }
                            default:
                                break;
                        }
                    }
                }

                function addHandlersToRow(row, tableBody) {
                    const removeRowButton = row.querySelector(".remove-row");

                    removeRowButton
                        .addEventListener("click", removeRowHandler.bind(null, row));
                }

                for (const input of inputs) {
                    const tableBody = input.querySelector("tbody");
                    const addRowButton = input.querySelector("tfoot .add-row");
                    addRowButton.addEventListener("click", () => {
                        const firstRowCloseButton = tableBody.querySelector("tr .remove-row");
                        firstRowCloseButton.classList.remove("hidden");

                        const newRow = tableBody.querySelector("tr").cloneNode(true);
                        tableBody.appendChild(newRow);
                        resetRow(newRow);
                        addHandlersToRow(newRow);

                        const form = $(newRow).parents(".leform-inline")[0];
                        if (form) {
                            const formId = form.dataset.formId;
                            const repeaterInput = $(newRow)
                                .parents(".leform-element[data-type='repeater-input']")[0]
                            const repeaterInputId = repeaterInput.dataset.id;
                            const mathExpressions = form
                                .querySelectorAll(`.leform-repeater-math[data-row='1'][data-repeater-input-id='${repeaterInputId}']`);

                            for (const mathExpression of mathExpressions) {
                                const newMathExpression = mathExpression.cloneNode(true);
                                newMathExpression.dataset.row = tableBody.children.length;
                                mathExpression.after(newMathExpression);
                            }
                            const form_uid = form.querySelector(".leform-form").dataset.id;
                            leform_handle_math(form_uid);
                        }
                    });

                    const row = tableBody.querySelector("tbody tr");
                    addHandlersToRow(row, tableBody);
                }
            }
        </script>
    </head>
    <body>
        @auth
            <div id="adminbar" class="bg-gray-800 px-2">
                <x-nav-link-adminbar :href="route('forms')">
                    <i class="fa fa-cog mr-1.5"></i>{{ __('Dashboard') }}
                </x-nav-link-adminbar>
                <x-nav-link-adminbar :href="route('create-form', ['id' => $id])">
                    <i class="fa fa-pen mr-1.5"></i>{{ __('Edit form') }}
                </x-nav-link-adminbar>
            </div>
        @endauth
        
        <div class="leform-inline" data-id="{{ $id }}"></div>
    </body>
</html>

