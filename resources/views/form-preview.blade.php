<!DOCTYPE html>
<html>
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
        <meta name="robots" content="noindex, nofollow, noarchive, nosnippet">
        <title>
            Form Preview:
            @if (empty($formObject->id))
                Form not found
            @else
                {{ $formObject->name }}
            @endif
        </title>
        <style>body{font-family:arial;font-size:15px;}body::-webkit-scrollbar{width: 5px;}body::-webkit-scrollbar-track{box-shadow:inset 0 0 6px rgba(0,0,0,0.1);}body::-webkit-scrollbar-thumb{background-color:#26B99A;}div.not-found{margin: 40px;text-align:center;}</style>
        <link rel="stylesheet" href="{{ asset('css/halfdata-plugin/preview.css') }}" type="text/css" media="all" />
        <link rel="stylesheet" href="{{ asset('css/halfdata-plugin/style.css') }}" type="text/css" media="all" />

        @if ($leform->options['fa-enable'] == 'on')
            @if (
                $leform->options['fa-solid-enable'] == 'on'
                && $leform->options['fa-regular-enable'] == 'on'
                && $leform->options['fa-brands-enable'] == 'on'
            )
                <link rel="stylesheet" href="{{ asset('css/halfdata-plugin/fontawesome-all.css') }}" type="text/css" media="all" />
            @else
                <link rel="stylesheet" href="{{ asset('css/halfdata-plugin/fontawesome.css') }}" type="text/css" media="all" />

                @if ($leform->options['fa-solid-enable'] == 'on')
                    <link rel="stylesheet" href="{{ asset('css/halfdata-plugin/fontawesome-solid.css') }}" type="text/css" media="all" />
                @endif

                @if ($leform->options['fa-regular-enable'] == 'on')
                    <link rel="stylesheet" href="{{ asset('css/halfdata-plugin/fontawesome-regular.css') }}" type="text/css" media="all" />
                @endif

                @if ($leform->options['fa-brands-enable'] == 'on')
                    <link rel="stylesheet" href="{{ asset('css/halfdata-plugin/fontawesome-brands.css') }}" type="text/css" media="all" />
                @endif
            @endif
        @else
            <link rel="stylesheet" href="{{ asset('css/halfdata-plugin/leform-fa.css') }}" type="text/css" media="all" />
        @endif

        <link rel="stylesheet" href="{{ asset('css/app.css') }}">
        <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
        <link rel="stylesheet" id="font-awesome-5.7.2" href="{{ asset("css/halfdata-plugin/fontawesome-all.min.css") }}">

        <link rel="stylesheet" href="{{ asset('css/halfdata-plugin/airdatepicker.css') }}" type="text/css" media="all" />
        <link rel="stylesheet" href="{{ asset('css/halfdata-plugin/ion.rangeSlider.css') }}" type="text/css" media="all" />
        <link rel="stylesheet" href="{{ asset('css/halfdata-plugin/tooltipster.bundle.css') }}" type="text/css" media="all" />
        <script src="{{ asset('js/app.js') }}" defer></script>
        <script type="text/javascript" src="{{ asset('js/halfdata-plugin/jquery.min.js') }}"></script>
        <script type="text/javascript" src="{{ asset('js/halfdata-plugin/signature_pad.js') }}"></script>
        <script type="text/javascript" src="{{ asset('js/halfdata-plugin/ion.rangeSlider.min.js') }}"></script>
        <script type="text/javascript" src="{{ asset('js/halfdata-plugin/tooltipster.bundle.min.js') }}"></script>
        <script type="text/javascript" src="{{ asset('js/halfdata-plugin/jsep.min.js') }}"></script>
        {{--
            this seems to be payment service required, don't think it is needed
            do_action('leform_preview_head')
        --}}
        <script type="text/javascript">
            var leform_preview_mode = "on";
            // var leform_customjs_handlers = {}; // this throws error but eh doesn't work without it
        </script>
        <style id="custom-css"></style>

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
        @if ($isTemplateView)
            @include('layouts.navigation')
        @endif

        <div class="leform-pages-bar @if ($isTemplateView) mt-16 max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 w-full @endif">
            <ul class="leform-pages-bar-items">
                @if (!empty($formObject->id))
                    @foreach ($formObject->form_pages as $formPage)
                        <li
                            class="leform-pages-bar-item"
                            data-id="{{ $formPage['id'] }}"
                            data-name="{{ $formPage['name'] }}"
                        >
                            <label onclick="return leform_pages_activate(this);">
                                {{ $formPage['name'] }}
                            </label>
                        </li>
                    @endforeach
                @endif
            </ul>
        </div>

        {!! $content !!}

        {{--
            done
            leform_front_class::front_footer()
        --}}
        <script>
            var leform_ajax_url = '#';
            var leform_overlays = @json($overlays);
            var leform_ga_tracking = "{{ $gaTracking }}";
        </script>

        <script type="text/javascript" src="{{ asset('js/halfdata-plugin/preview.js') }}"></script>
        <script
            id="leform-remote"
            type="text/javascript"
            src="{{ asset('js/halfdata-plugin/leform.js') }}"
            data-handler="{{ route('form-remote-init') }}"
        ></script>
        <script type="text/javascript" src="{{ asset('js/halfdata-plugin/airdatepicker.js') }}"></script>
        <script type="text/javascript" src="{{ asset('js/halfdata-plugin/jquery.mask.min.js') }}"></script>


        <!--
        <script src="http://localhost:8070/content/plugins/halfdata-green-forms/js/leform.min.js?ver=1.35" data-handler="http://localhost:8070/ajax.php"></script>
        -->
    </body>
</html>
