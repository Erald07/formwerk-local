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
?>

{{-- TODO: Check data-deps and data-dynamic --}}
<div class="{!! getElementClasses($element) !!}" 
  data-type="{{ $element->type }}" 
  data-id="{{ $element->id }}"
  data-max-files="{{intval($arrElement['max-files'])}}"
  data-max-files-error="{{$arrElement['max-files-error']}}"
  data-max-size="{{intval($arrElement['max-size'])}}"
  data-max-size-error="{{$arrElement['max-size-error']}}"
  data-allowed-extensions="{{implode(',', $properties['accept'])}}"
  data-allowed-extensions-error="{{$arrElement['allowed-extensions-error']}}"
  style="page-break-inside: avoid;"
>
    <div class="leform-column-label{{ $element->column_label_class }}">
        <label class="leform-label @if ($arrElement['label-style-align'] !='' ) leform-ta-{{ $arrElement['label-style-align'] }} @endif">
            {!! replaceWithPredefinedValues($arrElement['label'], $predefinedValues) !!} &nbsp;
        </label>
    </div>

    <div class="{{$element->column_input_class}}">
        <div class="{{$element->extra_class}}">
            @if(isset($element->value) && is_array($element->value)) 
              @foreach ($element->value as $item)
                <?php 
                  $item = (object) $item;
                ?>
                <div>
                  <img src="{{$item->url}}" style="max-height: 200px" />
                </div>
              @endforeach
            @endif
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
