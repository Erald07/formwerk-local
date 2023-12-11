@props([
    'viewOnly' => true,
    'options',
    'value',
    'properties',
    'form_dependencies',
    'predefinedValues' => [],
    'field',
    'leformOptions',
    'toolbarTools',
    'colIndex',
    'element',
    'rowIndex',
])

@switch($field->type)
    @case('text')
    @case('email')
    @case('password')
    @case('number')
        <div
            name="leform-{{ $element->id }}-{{ $rowIndex }}[]"
            class="w-full leform-input-value"
        > {{ $value }}</div>
        @break

    @case('select')
    @case('rangeslider')
        <div
            name="leform-{{ $element->id }}-{{ $rowIndex }}[]"
            class='w-full leform-input-value'>
            {{ $value }}
        </div>
        @break

    @case('date')
    @case('time')
        <div
            name="leform-{{ $element->id }}-{{ $rowIndex }}[]"
            class='leform-{{ $field->type }} w-full leform-input-value'
        >
            {{ $value }}
        </div>
        @break

    @case('star-rating')
        <?php $starCount = isset($field->starCount) ? $field->starCount : 5; ?>
        <fieldset class='leform-star-rating'>
            @for ($j = $starCount; $j > 0; $j--)
                <input type='radio' @if ($j === $value) checked="checked" @endif />
                <label></label>
            @endfor
        </fieldset>
        @break

    @case('html')
        <?php
            $htmlContent = '';

            if (isset($field->content)) {
                $expressions = (array) $properties["expressions"];
                $htmlContent = $field->content;
                if (!empty($expressions)) {
                    foreach ($expressions as $name => $expression) {
                        if (
                            !property_exists($expression, "repeaterInputId")
                            || intval($expression->repeaterInputId) === $element->id
                        ) {
                            $replacement = "
                                <span
                                    class='leform-repeater-var leform-repeater-var-" . $expression->id . "'
                                    data-id='" . $expression->id . "'
                                >
                                    ". (
                                        (gettype($expression->value) === "array")
                                            ? $expression->value[$rowIndex]
                                            : $expression->value
                                    ) ."
                                </span>
                            ";
                            $htmlContent = str_replace($name, $replacement, $htmlContent);
                        }
                    }
                }

                $matches = [];
                preg_match_all(
                    "/{{(\d+).+?}}/",
                    $htmlContent,
                    $matches
                );

                for ($matchIndex = 0; $matchIndex < count($matches[0]); $matchIndex++) {
                  $match = $matches[0][$matchIndex];
                  $id = $matches[1][$matchIndex];

                  $value = "";
                  if (
                      !is_null((array) $properties["formValues"])
                      && isset(((array) $properties["formValues"])[$id])
                  ) {
                      $value = ((array) $properties["formValues"])[$id];
                  }

                  $replacement = "
                      <span
                          class='leform-repeater-var leform-repeater-var-$1'
                          data-id='$1'
                      >". $value ."</span>
                  ";

                  $htmlContent = str_replace(
                      $match,
                      $replacement,
                      $htmlContent
                  );
                }
            }
        ?>
        <div class="d-element-inline">
          {!! $htmlContent !!}
        </div>
        <div class='leform-element-cover'></div>
        @break
@endswitch

