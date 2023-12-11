@props([
    'viewOnly' => true,
    'options',
    'element',
    'properties',
    'form_dependencies',
    'predefinedValues' => [],
    'leformOptions',
])

<?php
    $arrElement = (array) $element;
    $properties = isset($element->properties)
        ? (array) $element->properties
        : [];

    if (
        isset($arrElement['input-style-size'])
        && $arrElement['input-style-size'] != ''
    ) {
        $element->extra_class .= ' leform-input-' . $arrElement['input-style-size'];
    }
?>

{{-- TODO: Check data-deps and data-dynamic --}}

<div
    class="{!! getElementClasses($element) !!}"
    data-type="{{ $element->type }}"
    data-id="{{ $element->id }}"
    style="page-break-inside: avoid;"
>
    <div class="leform-column-label{{ $element->column_label_class }}">
        <label class="leform-label @if ($arrElement['label-style-align'] !='' ) leform-ta-{{ $arrElement['label-style-align'] }} @endif">
            {!! replaceWithPredefinedValues($arrElement['label'], $predefinedValues) !!} &nbsp;
        </label>
    </div>

    <div class="leform-column-input{{$element->column_input_class}}">
        <div
            class="leform-input{{$element->extra_class}}"
            style="border: none; width: 100%; padding: .5em .3em;"
        >
            <div class='leform-multiselect leform-ta-{{ $properties["align"] }}'>
                {!! $element->multiselectOptions !!}
            </div>
        </div>
        @if($options['description-style-position'] != 'none'  && !$viewOnly) 
          <label class='leform-description @if($arrElement['description-style-align'] !="") leform-ta-{{$arrElement['description-style-align']}} @endif'>
              {!!replaceWithPredefinedValues(
              $properties["required-description-left"]
              .$arrElement["description"]
              .$properties["required-description-right"]
              .$properties["tooltip-description"],
              $predefinedValues
              )!!}
          </label>
          @endif
    </div>
</div>

