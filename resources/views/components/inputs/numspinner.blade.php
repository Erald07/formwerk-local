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
$properties = isset($element->properties) ? (array) $element->properties : [];
$masked = isset($leformOptions) && $leformOptions['mask-enable'] == 'on' && array_key_exists('mask-mask', $arrElement) && !empty($arrElement['mask-mask']);
$mode = $arrElement['simple-mode'] === 'on' ? 'simple': 'advanced';
if (isset($arrElement['input-style-size']) && $arrElement['input-style-size'] != '') {
    $element->extra_class .= ' leform-input-' . $arrElement['input-style-size'];
}
?>

{{-- TODO: Check data-deps and data-dynamic --}}
<div class="{!! getElementClasses($element) !!}" 
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
        <div class="leform-input leform-icon-left leform-icon-right{{$element->extra_class}}"   >
            <i class='leform-icon-left leform-if leform-if-minus leform-numspinner-minus'></i>
            <i class='leform-icon-right leform-if leform-if-plus leform-numspinner-plus'></i>
            <input
            readonly='readonly'
            name="leform-{{$arrElement['id']}}" 
            class="leform-number @if($arrElement['input-style-align'] != "") leform-ta-{{$arrElement['input-style-align']}} @endif @if($masked) leform-mask @endif {{$arrElement["css-class"]}}"
            @if($masked) data-xmask='{{$arrElement["mask-mask"]}}' @endif
            @isset($element->value)
              value="{{number_format($arrElement["value"], $arrElement["decimal"], '.', '')}}"
            @endisset
            data-mode={{$mode}}
            @if ($arrElement["simple-mode"] === 'on')
                data-min={{$arrElement["number-value1"]}}
                data-max={{$arrElement["number-value3"]}}
                data-step={{$arrElement["number-value4"]}}
                data-value={{number_format($arrElement["number-value2"], $arrElement["decimal"], '.', '')}}
            @else
                data-range={{$prooperties['ranges']}}
                data-step={{$arrElement["number-advanced-value3"]}}
                data-value={{number_format($arrElement["number-advanced-value1"], $arrElement["decimal"], '.', '')}}
            @endif
            data-decimal={{$arrElement["decimal"]}}
            />
        </div>
        @if($options['description-style-position'] != 'none' && !$viewOnly) 
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
