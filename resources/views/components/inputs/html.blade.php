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
$css_class = "";
$css_class = $arrElement["css-class"];

// dd($element);
?>
@if ($element->id)
<div
  class="leform-element leform-element-{{$element->id}} leform-element-html {{$css_class}}"
  data-type="{{ $element->type }}" data-id="{{ $element->id }}"
>{!!$properties['content']!!}
<div class='leform-element-cover'></div>
</div>
@endif
