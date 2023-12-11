@props([
    'viewOnly' => true,
    'options',
    'element',
    'properties',
    'form_dependencies',
    'predefinedValues' => [],
    'leformOptions',
    'toolbarTools'
])

<?php
    $arrElement = (array) $element;
    $fields = (array) $element->fields;
    $hasClass = count($fields) >= intval($arrElement["add-row-width"]) ? true : false;
    $properties = isset($element->properties) ? (array) $element->properties : [];
    $masked = isset($leformOptions)
        && $leformOptions['mask-enable'] == 'on'
        && array_key_exists('mask-mask', $arrElement)
        && !empty($arrElement['mask-mask']);
    if (isset($arrElement['input-style-size']) && $arrElement['input-style-size'] != '') {
        $element->extra_class .= ' leform-input-' . $arrElement['input-style-size'];
    }

    $elementValue = [];
    foreach ($element->value as $rowIndex => $rowValues) {
        $newRowValues = [];
        $htmlInputCount = 0;
        for ($colNum = 0; $colNum < count($fields); $colNum++) {
            if ($fields[$colNum]['type'] === "html") {
                $newRowValues[] = "";
                $htmlInputCount++;
            } else {
                $newRowValues[] = isset($rowValues[$colNum - $htmlInputCount]) ? $rowValues[$colNum - $htmlInputCount] : '' ;
            }
        }
        $elementValue[] = $newRowValues;
    }
?>

{{-- TODO: Check data-deps and data-dynamic --}}
<div
    class="{!! getElementClasses($element) !!}" 
    data-type="{{ $element->type }}" 
    data-id="{{ $element->id }}"
>
  <div class="repeater-inputessss leform-column-label{{ $element->column_label_class }}">
    <label class="leform-label @if(isset($arrElement['label-style-align']) && $arrElement['label-style-align'] !='') leform-ta-{{ $arrElement['label-style-align'] }} @endif">
         &nbsp;
    </label>
  </div>

  <div class="leform-column-input{{$element->column_input_class}}">
    <div>
        <table class='w-full'>
            <thead>
                <tr>
                    @foreach ($fields as $fieldIndex => $field)
                      <td
                          class='@if ($arrElement["has-borders"] === "on") border-2 @endif pb-1 pt-0 @if ($fieldIndex === 0) pr-2 pl-0 @elseif ($fieldIndex === (count($fields) - 1)) pl-2 pr-0 @else px-2 @endif'
                          style='border-color: #d5d9dd;'
                      >
                          {{$field["name"]}}
                      </td>
                    @endforeach
                </tr>
            </thead>

            @if (!empty($elementValue) && is_array($elementValue))
                <tbody>
                    @foreach ($elementValue as $rowIndex =>$item)
                        <tr>
                            @foreach ($fields as $ind => $field)
                                <?php $field = (object) $field; ?>
                                <td class='py-1 @if ($ind === 0) pr-2 pl-0 @elseif ($ind === (count($fields) - 1)) pl-2 pr-0 @else px-2 @endif @if ($arrElement["has-borders"] === "on") border-2 @endif '>
                                    @if(isset($item[$ind]) || $field->type === 'html')
                                        <?php $el_value = $field->type !== 'html' ? $item[$ind] : '';  ?>
                                        <x-inputs.repeater-input.inputs
                                            :element="$element"
                                            :rowIndex="$rowIndex"
                                            :colIndex="$ind"
                                            :value="$el_value"
                                            :field="$field"
                                            :properties="$properties"
                                        />
                                    @endif
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </tbody>
            @endif

            @if($arrElement["has-footer"] === "on")
                <tfoot>
                    <tr class='@if ($arrElement["has-borders"] === "on") border-2 @endif'>
                        <td class='py-2 pr-2' colspan='999'>
                            {!!$properties['footerTotalsExpression']!!}
                        </td>
                    </tr>
                </tfoot>
            @endif
        </table>
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
