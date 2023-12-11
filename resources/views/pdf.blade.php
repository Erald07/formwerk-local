<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <link rel="stylesheet" type="text/css" href="{{ $base_path }}/css/font-awsome-pdf.min.css">

    {!! $webfont_link !!}
    @foreach ($css_files as $item)
        <link rel="stylesheet" type="text/css" href="{{ $item }}">
    @endforeach
    <style>
        .leform-element>.leform-column-label.leform-col-1,
        .leform-element>.leform-column-label.leform-col-2,
        .leform-element>.leform-column-label.leform-col-3,
        .leform-element>.leform-column-label.leform-col-4,
        .leform-element>.leform-column-label.leform-col-5,
        .leform-element>.leform-column-label.leform-col-6,
        .leform-element>.leform-column-label.leform-col-7,
        .leform-element>.leform-column-label.leform-col-8,
        .leform-element>.leform-column-label.leform-col-9,
        .leform-element>.leform-column-label.leform-col-10,
        .leform-element>.leform-column-label.leform-col-11,
        .leform-element>.leform-column-label.leform-col-12,
        .leform-element>.leform-column-input.leform-col-1,
        .leform-element>.leform-column-input.leform-col-2,
        .leform-element>.leform-column-input.leform-col-3,
        .leform-element>.leform-column-input.leform-col-4,
        .leform-element>.leform-column-input.leform-col-5,
        .leform-element>.leform-column-input.leform-col-6,
        .leform-element>.leform-column-input.leform-col-7,
        .leform-element>.leform-column-input.leform-col-8,
        .leform-element>.leform-column-input.leform-col-9,
        .leform-element>.leform-column-input.leform-col-10,
        .leform-element>.leform-column-input.leform-col-11,
        .leform-element>.leform-column-input.leform-col-12 {
            display: inline-block;
        }

        .leform-element[data-type='tile'] .leform-tile-box i,
        .leform-element[data-type='tile'] .leform-tile-box i {
            font-size: 40px;
            color: inherit;
        }

        .leform-element[data-type='tile'] .leform-tile-box label,
        .leform-element[data-type='tile'] .leform-tile-box label {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .leform-tile-full .leform-tile-box {
            display: block !important;
        }

        /* Ion.RangeSlider - end */
        {!! $css !!} 
        input {
            border: none !important;
            @if (!empty($options['max-width-value']))width: {{ $options['max-width-value'] }}{{ $options['max-width-unit'] }};
            @endif
        }

        @font-face {
            font-family: "FontAwesomeRegular";
            font-weight: normal;
            font-style: normal;
            src: url("{{ $base_path }}/fonts/fa-regular-400.otf") format("opentype");
        }

        @font-face {
            font-family: 'Font Awesome 5 Free';
            font-style: normal;
            font-weight: 900;
            font-display: block;
            src: url("{{ $base_path }}/fonts/fa-solid-900.otf") format("opentype");
        }

        @font-face {
            font-family: 'Font Awesome 5 Free';
            font-style: normal;
            font-weight: 400;
            font-display: block;
            src: url("{{ $base_path }}/fonts/fa-regular-400.otf") format("opentype");
        }

        @font-face {
            font-family: 'Font Awesome 5 Brands';
            font-style: normal;
            font-weight: 400;
            font-display: block;
            src: url("{{ $base_path }}/fonts/fa-brands-400.otf") format("opentype");
        }

        @font-face {
            font-family: "FontAwesomeBrands";
            font-weight: normal;
            font-style: normal;
            src: url("{{ $base_path }}/fonts/fa-brands-400.otf") format("opentype");
        }

        @font-face {
            font-family: "FontAwesomeSolid";
            font-weight: bold;
            font-style: normal;
            src: url("{{ $base_path }}/fonts/fa-solid-900.otf") format("opentype");
        }

        .fas {
            font-weight: bold !important;
            font-family: FontAwesomeSolid !important;
        }

        .fas:before {
            font-weight: bold !important;
            font-family: FontAwesomeSolid !important;
        }

        .fab {
            font-weight: normal !important;
            font-family: FontAwesomeBrands !important;
        }

        .fab:before {
            font-weight: normal !important;
            font-family: FontAwesomeBrands !important;
        }

        .far {
            font-weight: normal !important;
            font-family: FontAwesomeRegular !important;
        }

        .far:before {
            font-weight: normal !important;
            font-family: FontAwesomeRegular !important;
        }

        .leform-inline .leform-form-{{ $id }} {
            padding: 0 20px !important;
            width: 100%;
        }

        .leform-form-inner {
            width: 100%;
        }

        .leform-form-inner .leform-element {
            width: 100%;
        }

        .leform-col-0 {
            width: 0% !important;
        }

        .leform-col-1 {
            width: 8.3333333333% !important;
        }

        .leform-col-2 {
            width: 16.6666666667% !important;
        }

        .leform-col-3 {
            width: 25% !important;
        }

        .leform-col-4 {
            width: 33.333333333% !important;
        }

        .leform-col-5 {
            width: 41.6666666667% !important;
        }

        .leform-col-6 {
            width: 50% !important;
        }

        .leform-col-7 {
            width: 58.333333333% !important;
        }

        .leform-col-8 {
            width: 66.6666666667% !important;
        }

        .leform-col-9 {
            width: 75% !important;
        }

        .leform-col-10 {
            width: 83.333333333% !important;
        }

        .leform-col-11 {
            width: 91.6666666667% !important;
        }

        .leform-col-12 {
            width: 100% !important;
        }

        .leform-star-rating-huge>label:after {
            font-size: 28px;
        }

        .leform-element {
            height: max-content !important;
        }

        @page {
            size: 'A4';
            padding: 0 !important;
        }
        .leform-element-html-container {
            break-inside: always;
        }
        /* body.pdf-view {
            width: 210mm;
            height: 296mm;
            margin: auto;
            padding: 0;
            background: white;
        }
        .pdf-view .leform-form-inner {
            width: 100%;
            height: 278mm;
            padding: 0.94cm 15mm !important;
        } */
    </style>
</head>

<body class="pdf-view">

    <?php
    $pages = [];
    $collapse = 480;
    if (array_key_exists('responsiveness-size', $options) && in_array($options['responsiveness-size'], [480, 768, 1024, 'custom'])) {
        if ($options['responsiveness-size'] == 'custom') {
            $collapse = intval($options['responsiveness-custom']);
        } else {
            $collapse = $options['responsiveness-size'];
        }
    }
    $form_logic = [];
    $form_dependencies = [];
    $form_inputs = [];
    $rawElements = [];
    $tb = (array) $toolbarTools;
    if (!function_exists('_formInputs')) {
        function _formInputs($element, $form_inputs, $tb)
        {
            $allowedInputs = ['input', 'other'];
            if (array_key_exists($element['type'], $tb) && in_array($tb[$element['type']]['type'], $allowedInputs)) {
                $form_inputs[] = $element['id'];
            }
            if (array_key_exists('properties', $element) && array_key_exists('elements', $element['properties']) && is_array($element['properties']['elements'])) {
                foreach ($element['properties']['elements'] as $propElement) {
                    $form_inputs = _formInputs($propElement, $form_inputs, $tb);
                }
            }
            return $form_inputs;
        }
    }
    for ($i = 0; $i < sizeof($elements); $i++) {
        $element = json_decode(json_encode($elements[$i]), true);
        $form_inputs = _formInputs($element, $form_inputs, $tb);
        if (!isset($pages[$element['_parent']])) {
            $pages[$element['_parent']] = [];
        }
        $pages[$element['_parent']][] = (object) $element;
    }
    if (!function_exists('processElement')) {
        function processElement($element, $rawElements, $form_inputs, $form_dependencies, $form_logic)
        {
            $element = json_decode(json_encode($element), true);
            $rawElements[$element['id']] = $element;
            if (array_key_exists('properties', $element) && array_key_exists('elements', $element['properties']) && is_array($element['properties']['elements'])) {
                foreach ($element['properties']['elements'] as $propElement) {
                    $result = processElement($propElement, $rawElements, $form_inputs, $form_dependencies, $form_logic);
                    $rawElements = $result['rawElements'];
                    $form_inputs = $result['form_inputs'];
                    $form_dependencies = $result['form_dependencies'];
                    $form_logic = $result['form_logic'];
                }
            }
            if (array_key_exists('logic-enable', $element) && $element['logic-enable'] == 'on' && array_key_exists('logic', $element) && is_array($element['logic']) && array_key_exists('rules', $element['logic']) && is_array($element['logic']['rules'])) {
                $logic = [
                    'action' => $element['logic']['action'],
                    'operator' => $element['logic']['operator'],
                    'rules' => [],
                ];
                foreach ($element['logic']['rules'] as $rule) {
                    if (in_array($rule['field'], $form_inputs)) {
                        $logic['rules'][] = $rule;
                        if (!array_key_exists($rule['field'], $form_dependencies) || !is_array($form_dependencies[$rule['field']]) || !in_array($element['id'], $form_dependencies[$rule['field']])) {
                            $form_dependencies[$rule['field']][] = $element['id'];
                        }
                    }
                }
                if (!empty($logic['rules'])) {
                    $form_logic[$element['id']] = $logic;
                }
                if ($element['type'] === 'columns' && array_key_exists('properties', $element) && array_key_exists('elements', $element['properties']) && is_array($element['properties']['elements'])) {
                    foreach ($element['properties']['elements'] as $colElement) {
                        $rawElements[$colElement['id']] = $colElement;
                        if (array_key_exists('logic-enable', $colElement) && $colElement['logic-enable'] == 'on' && array_key_exists('logic', $colElement) && is_array($colElement['logic']) && array_key_exists('rules', $colElement['logic']) && is_array($colElement['logic']['rules'])) {
                            $logic = [
                                'action' => $colElement['logic']['action'],
                                'operator' => $colElement['logic']['operator'],
                                'rules' => [],
                            ];
                            foreach ($colElement['logic']['rules'] as $rule) {
                                if (in_array($rule['field'], $form_inputs)) {
                                    $logic['rules'][] = $rule;
                                    if (!array_key_exists($rule['field'], $form_dependencies) || !is_array($form_dependencies[$rule['field']]) || !in_array($colElement['id'], $form_dependencies[$rule['field']])) {
                                        $form_dependencies[$rule['field']][] = $colElement['id'];
                                    }
                                }
                            }
                            if (!empty($logic['rules'])) {
                                $form_logic[$colElement['id']] = $logic;
                            }
                        }
                    }
                }
            }
            return [
                'rawElements' => $rawElements,
                'form_inputs' => $form_inputs,
                'form_dependencies' => $form_dependencies,
                'form_logic' => $form_logic,
            ];
        }
    }
    for ($i = 0; $i < sizeof($elements); $i++) {
        $result = processElement($elements[$i], $rawElements, $form_inputs, $form_dependencies, $form_logic);
        $rawElements = $result['rawElements'];
        $form_inputs = $result['form_inputs'];
        $form_dependencies = $result['form_dependencies'];
        $form_logic = $result['form_logic'];
    }
    unset($pages['confirmation']);
    ?>
    <div class="leform-inline leform-container" style="width: 100%;">
        @foreach ($pages as $pageElements)
            <div class="leform-form leform-form-{{ $id }} leform-form-{{ $uuid }} leform-form-input-{{ $options['input-size'] }} leform-form-icon-{{ $options['input-icon-position'] }}
                leform-form-description-{{ $options['description-style-position'] }}"
                data-session="@if ($options['session-enable'] == 'on') {{ intval($options['session-length']) }} @else 0 @endif"
                data-collapse="{{ $collapse }}" data-id="{{ $uuid }}" data-form-id="{{ $id }}"
                data-title="{{ $name }}"
                data-tooltip-theme="
                                           @if (array_key_exists('tooltip-theme', $options)) {{ $options['tooltip-theme'] }} @else dark @endif"
                style="padding: 0!important">
                <div class="leform-form-inner">
                    @foreach ($pageElements as $formElement)
                        @if (evo_is_element_visible($formElement->id, $form_logic, $formElement, $rawElements, $record))
                            <x-inputs :formElement="$formElement" :formLogic="$form_logic" :rawElements="$rawElements" :predefinedValues="$predefinedValues"
                                :leformOptions="$leformOptions" :options="$options" :toolbarTools="$toolbarTools" :record="$record">
                            </x-inputs>
                        @endif
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</body
