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
$value = 0;
if(isset($element->value)) {
    $value = $element->value;
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
        <div class="leform-input leform-rangeslider">
            <span class="irs irs--flat js-irs-0">
                <span class="irs">
                    <span class="irs-line" tabindex="0"></span>
                    <span class="irs-min" style="display: none; visibility: visible;">0</span>
                    <span class="irs-max" style="display: none; visibility: visible;">1</span>
                    <span class="irs-from" style="visibility: hidden;">0</span>
                    <span class="irs-to" style="visibility: hidden;">0</span>
                    <span class="irs-single" style="left: {{$value - 0.9}}%;">{{$value}}</span>
                </span>
                <span class="irs-grid"></span>
                <span class="irs-bar irs-bar--single" style="left: 0px; width: {{$value + 0.3}}%;"></span>
                <span class="irs-shadow shadow-single" style="display: none;"></span>
                <span class="irs-handle single" style="left: {{$value - 0.5}}%;">
                    <i></i><i></i><i></i></span>
                </span>
                <div
                class="leform-rangeslider irs-hidden-input" 
                @isset($element->value)
                    value="{{$element->value}}"
                @endisset
                data-type="single" 
                data-grid="false" 
                data-hide-min-max="true" 
                data-skin="flat" 
                data-min="0" 
                data-max="100" 
                data-step="1" 
                data-from="30" 
                data-to="70" 
                data-prefix="" 
                data-postfix="" 
                tabindex="-1" 
                readonly="">
            </div>
        {{-- <div class="leform-input leform-rangeslider{{$element->extra_class}}" >
            {!!$element->icon!!}
            <div 
            name="leform-{{$arrElement['id']}}" 
            class="leform-rangeslider {{$arrElement["css-class"]}}"
            @if($masked) data-xmask='{{$arrElement["mask-mask"]}}' @endif
            @isset($element->value)
              value="{{$element->value}}"
            @endisset
            {!!$arrElement['options']!!}
            />
        </div> --}}
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
