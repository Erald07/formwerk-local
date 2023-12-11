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

if (isset($arrElement['input-style-size']) && $arrElement['input-style-size'] != '') {
    $element->extra_class .= ' leform-input-' . $arrElement['input-style-size'];
}
$input_style = '';
if(isset($arrElement['css']) && count($arrElement['css']) > 0) {
    foreach ($arrElement['css'] as $key => $css) {
        if($css['selector'] === 'input') {
            $input_style = $css['css'];
            break;
        }
    }
}
?>

{{-- TODO: Check data-deps and data-dynamic --}}
<div class="{!! getElementClasses($element) !!}" data-type="{{ $element->type }}" data-id="{{ $element->id }}"
    style="page-break-inside: avoid;">
    <div class="leform-column-label{{ $element->column_label_class }}">
        <label class="leform-label @if ($arrElement['label-style-align'] !='' ) leform-ta-{{ $arrElement['label-style-align'] }} @endif">
            {!! replaceWithPredefinedValues($arrElement['label'], $predefinedValues) !!} &nbsp;
        </label>
    </div>

    <div class="leform-column-input{{$element->column_input_class}}">
        <div class="leform-input{{$element->extra_class}} leform-input-value"   >
            {{-- {!!$element->icon!!} --}}
            <div 
            name="leform-{{$arrElement['id']}}"
            style="{{$input_style}}"
            class="@if($arrElement['input-style-align'] != "") leform-ta-{{$arrElement['input-style-align']}} @endif @if($masked) leform-mask @endif {{$arrElement["css-class"]}}"
            @if($masked) data-xmask='{{$arrElement["mask-mask"]}}' @endif
            >
                @if (isset($element->value))
                    {{$element->value}}
                @else
                    &nbsp;
                @endif
            </div>
        </div>
        @if($options['description-style-position'] != 'none') 
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
