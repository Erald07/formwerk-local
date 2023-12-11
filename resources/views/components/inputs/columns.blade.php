@props([
    'viewOnly' => true,
    'options',
    'element',
    'properties',
    'form_dependencies',
    'predefinedValues' => [],
    'leformOptions',
    'toolbarTools',
    'rawElements',
    'formLogic',
    'record'
])

<?php
    $arrElement = (array) $element;
    $properties = isset($element->properties) ? (array) $element->properties : [];
    $masked = isset($leformOptions) && $leformOptions['mask-enable'] == 'on' && array_key_exists('mask-mask', $arrElement) && !empty($arrElement['mask-mask']);

    if (isset($arrElement['input-style-size']) && $arrElement['input-style-size'] != '') {
        $element->extra_class .= ' leform-input-' . $arrElement['input-style-size'];
    }
    $propElements = $properties['elements'];
    $hasDynamicValues = (
        (
            array_key_exists("has-dynamic-values", $arrElement)
            && $arrElement["has-dynamic-values"] === "on"
        )
            ? true
            : false
    );
    $dynamicValueName = (
        (
            $hasDynamicValues
            && array_key_exists("dynamic-value", $arrElement)
            && $arrElement["dynamic-value"] !== ""
        )
            ? $arrElement["dynamic-value"]
            : null
    );
    $dynamicValueIndex = (
        (
            $hasDynamicValues
            && array_key_exists("dynamic-value-index", $arrElement)
            && $arrElement["dynamic-value-index"] !== ""
        )
            ? (intval($arrElement["dynamic-value-index"]) - 1)
            : null
    );

    $shouldRender = true;
    $dynamicValues = null;

    if ($hasDynamicValues) {
        if (
            $dynamicValueName !== null
            && is_array($predefinedValues)
            && $dynamicValueIndex !== null
            && array_key_exists($dynamicValueName, $predefinedValues)
            && is_array($predefinedValues[$dynamicValueName])
            && array_key_exists($dynamicValueIndex, $predefinedValues[$dynamicValueName])
        ) {
            $dynamicValues = $predefinedValues[$dynamicValueName][(int)$dynamicValueIndex];
        }else {
            $shouldRender = false;
        }
    } else {
        $dynamicValues = $predefinedValues;
    }
    // check showif
    $showElement = evo_is_element_visible($element->id, $formLogic, $element, $rawElements, $record);
?>

@if ($shouldRender && $showElement)
    {{-- TODO: Check data-deps and data-dynamic --}}
    <div
        class="leform-row leform-element leform-element-{{$element->id}} {{$arrElement["css-class"]}}"
        data-type="{{ $element->type }}"
        data-id="{{ $element->id }}"
        {{-- style="padding: 10px!important;page-break-inside: avoid;" --}}
    >
        @if(!empty($properties['elements']))
          @for ($i = 0; $i < $arrElement["_cols"]; $i++)
              <div
                    class='leform-col leform-col-{{$arrElement["widths-".$i]}}'
                    {{-- style="outline: 1px dashed #ccc;" --}}
                >
                  <div
                      class='leform-elements'
                      _data-parent='{{ $element->id }}'
                      _data-parent-col='{{$i}}'
                  >
                    <table style="width: 100%; border:0;">
                        <tbody style="width: 100%; border:0;">
                        <tr style="width: 100%; border:0;">
                            <td>
                                @foreach ($propElements as $formElement)
                                    <?php $formElement = (object) $formElement; ?>
                                    @if (
                                        $i === intval(((array) $formElement)["_parent-col"])
                                        && evo_is_element_visible($formElement->id, $formLogic, $formElement, $rawElements, $record)
                                    )
                                        <x-inputs
                                            :formElement="$formElement"
                                            :predefinedValues="$dynamicValues"
                                            :leformOptions="$leformOptions"
                                            :options="$options"
                                            :toolbarTools="$toolbarTools"
                                            :formLogic="$formLogic"
                                            :rawElements="$rawElements"
                                            :record="$record"
                                        ></x-inputs>
                                    @endif
                                @endforeach
                            </td>
                        </tr></tbody>
                      </table>
                  </div>
              </div>
          @endfor
        @endif
    </div>
@endif
