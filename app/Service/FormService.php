<?php

namespace App\Service;

use App\Models\Form;
use App\Models\Upload;
use Exception;
use Illuminate\Support\Facades\Storage;
use MathParser\Interpreting\Evaluator;
use MathParser\StdMathParser;

class FormService
{
  var $form_options, $form_pages, $form_elements, $form_inputs, $form_logic, $form_dependencies, $id = null, $name;
  var $cache_html = null, $cache_style = null, $cache_uids = [], $cache_time = null;
  var $form_data = [], $form_info = [], $predefinedValues= [];
  var $preview = false;
  var $record_id = 0;

  var $form, $leform, $form_values, $base_path;
  var $math_expressions = [];
  function __construct($form_details, $values = null, $is_pdf = false, $predefinedValues = [])
  {
    $this->form = $form_details;
    $this->leform = new LeformService();
    $this->form_values = $values;
    $allVariables = evo_get_all_variables($predefinedValues);

    $this->predefinedValues = $allVariables;
    $this->leform = new LeformService;
    $this->base_path = $is_pdf ? getcwd() : '';

    if (empty($form_details)) {
      return;
    }

    $this->id = $form_details['id'];
    $this->cache_html = $form_details['cache_html'];
    $this->cache_style = $form_details['cache_style'];
    $this->cache_uids = json_decode($form_details['cache_uids'], true);
    $this->cache_time = $form_details['cache_time'];
    $this->name = $form_details['name'];

    $default_form_options = $this->leform->getDefaultFormOptions();
    $this->form_options = $form_details['options'];

    if (!empty($this->form_options)) {
      $this->form_options = array_merge($default_form_options, $this->form_options);
    } else {
      $this->form_options = $default_form_options;
    }
    $this->form_pages = json_decode($form_details['pages'], true);

    $default_page_options = $this->leform->getDefaultFormOptions("page");
    if (is_array($this->form_pages)) {
      foreach ($this->form_pages as $key => $form_page) {
        if (is_array($form_page)) {
          $this->form_pages[$key] = array_merge($default_page_options, $form_page);
        } else {
          unset($this->form_pages[$key]);
        }
      }
      $this->form_pages = array_values($this->form_pages);
    } else {
      $this->form_pages = [];
    }

    $this->form_elements = $form_details['elements'];
    if (is_array($this->form_elements)) {
      foreach ($this->form_elements as $key => $element_options) {
        if (
          is_array($element_options)
          && array_key_exists('type', $element_options)
        ) {
          $default_element_options = $this->leform->getDefaultFormOptions($element_options['type']);
          $element_options = array_merge($default_element_options, $element_options);
          if (!is_null($this->form_values) && isset($this->form_values[$element_options['id']])) {
            $element_options['value'] = $this->form_values[$element_options['id']];
          }
          $this->form_elements[$key] = $element_options;
        } else {
          unset($this->form_elements[$key]);
        }
      }
      $this->form_elements = array_values($this->form_elements);
    } else {
      $this->form_elements = [];
    }

    $this->form_inputs = [];
    for ($i = 0; $i < sizeof($this->form_elements); $i++) {
      if (
        array_key_exists($this->form_elements[$i]['type'], $this->leform->toolbarTools)
        && $this->leform->toolbarTools[$this->form_elements[$i]['type']]['type'] == 'input'
      ) {
        $this->form_inputs[] = $this->form_elements[$i]['id'];
      }
    }
    $this->form_logic = [];
    $this->form_dependencies = [];
    for ($i = 0; $i < sizeof($this->form_elements); $i++) {
      if (
        array_key_exists('logic-enable', $this->form_elements[$i])
        && $this->form_elements[$i]['logic-enable'] == 'on'
        && array_key_exists('logic', $this->form_elements[$i])
        && is_array($this->form_elements[$i]['logic'])
        && array_key_exists('rules', $this->form_elements[$i]['logic'])
        && is_array($this->form_elements[$i]['logic']['rules'])
      ) {
        $logic = [
          'action' => $this->form_elements[$i]['logic']['action'],
          'operator' => $this->form_elements[$i]['logic']['operator'],
          'rules' => []
        ];
        foreach ($this->form_elements[$i]['logic']['rules'] as $rule) {
          if (in_array($rule['field'], $this->form_inputs)) {
            $logic['rules'][] = $rule;
            if (
              !array_key_exists($rule['field'], $this->form_dependencies)
              || !is_array($this->form_dependencies[$rule['field']])
              || !in_array($this->form_elements[$i]['id'], $this->form_dependencies[$rule['field']])
            ) {
              $this->form_dependencies[$rule['field']][] = $this->form_elements[$i]['id'];
            }
          }
        }
        if (!empty($logic['rules'])) {
          $this->form_logic[$this->form_elements[$i]['id']] = $logic;
        }
      }
    }
    for ($i = 0; $i < sizeof($this->form_pages); $i++) {
      if (
        array_key_exists('logic-enable', $this->form_pages[$i])
        && $this->form_pages[$i]['logic-enable'] == 'on'
        && array_key_exists('logic', $this->form_pages[$i])
        && is_array($this->form_pages[$i]['logic'])
        && array_key_exists('rules', $this->form_pages[$i]['logic'])
        && is_array($this->form_pages[$i]['logic']['rules'])
      ) {
        $logic = [
          'action' => $this->form_pages[$i]['logic']['action'],
          'operator' => $this->form_pages[$i]['logic']['operator'],
          'rules' => []
        ];
        foreach ($this->form_pages[$i]['logic']['rules'] as $rule) {
          if (in_array($rule['field'], $this->form_inputs)) {
            $logic['rules'][] = $rule;
          }
        }
        if (!empty($logic['rules'])) {
          $this->form_logic[$this->form_pages[$i]['id']] = $logic;
        }
      }
    }
    if (
      array_key_exists('confirmations', $this->form_options)
      && is_array($this->form_options['confirmations'])
    ) {
      for ($i = 0; $i < sizeof($this->form_options['confirmations']); $i++) {
        if (
          array_key_exists('logic-enable', $this->form_options['confirmations'][$i])
          && $this->form_options['confirmations'][$i]['logic-enable'] == 'on'
          && array_key_exists('logic', $this->form_options['confirmations'][$i])
          && is_array($this->form_options['confirmations'][$i]['logic'])
          && array_key_exists('rules', $this->form_options['confirmations'][$i]['logic'])
          && is_array($this->form_options['confirmations'][$i]['logic']['rules'])
        ) {
          $logic = [
            'action' => $this->form_options['confirmations'][$i]['logic']['action'],
            'operator' => $this->form_options['confirmations'][$i]['logic']['operator'],
            'rules' => []
          ];
          foreach ($this->form_options['confirmations'][$i]['logic']['rules'] as $rule) {
            if (in_array($rule['field'], $this->form_inputs)) {
              $logic['rules'][] = $rule;
            }
          }
          if (!empty($logic['rules'])) {
            $this->form_logic['confirmation-' . $i] = $logic;
          }
        }
      }
    }
    if (array_key_exists('notifications', $this->form_options) && is_array($this->form_options['notifications'])) {
      for ($i = 0; $i < sizeof($this->form_options['notifications']); $i++) {
        if (array_key_exists('logic-enable', $this->form_options['notifications'][$i]) && $this->form_options['notifications'][$i]['logic-enable'] == 'on' && array_key_exists('logic', $this->form_options['notifications'][$i]) && is_array($this->form_options['notifications'][$i]['logic']) && array_key_exists('rules', $this->form_options['notifications'][$i]['logic']) && is_array($this->form_options['notifications'][$i]['logic']['rules'])) {
          $logic = [
            'action' => $this->form_options['notifications'][$i]['logic']['action'],
            'operator' => $this->form_options['notifications'][$i]['logic']['operator'],
            'rules' => []
          ];
          foreach ($this->form_options['notifications'][$i]['logic']['rules'] as $rule) {
            if (in_array($rule['field'], $this->form_inputs)) {
              $logic['rules'][] = $rule;
            }
          }
          if (!empty($logic['rules'])) {
            $this->form_logic['notification-' . $i] = $logic;
          }
        }
      }
    }
    if (array_key_exists('integrations', $this->form_options) && is_array($this->form_options['integrations'])) {
      for ($i = 0; $i < sizeof($this->form_options['integrations']); $i++) {
        if (array_key_exists('logic-enable', $this->form_options['integrations'][$i]) && $this->form_options['integrations'][$i]['logic-enable'] == 'on' && array_key_exists('logic', $this->form_options['integrations'][$i]) && is_array($this->form_options['integrations'][$i]['logic']) && array_key_exists('rules', $this->form_options['integrations'][$i]['logic']) && is_array($this->form_options['integrations'][$i]['logic']['rules'])) {
          $logic = [
            'action' => $this->form_options['integrations'][$i]['logic']['action'],
            'operator' => $this->form_options['integrations'][$i]['logic']['operator'],
            'rules' => []
          ];
          foreach ($this->form_options['integrations'][$i]['logic']['rules'] as $rule) {
            if (in_array($rule['field'], $this->form_inputs)) {
              $logic['rules'][] = $rule;
            }
          }
          if (!empty($logic['rules'])) {
            $this->form_logic['integration-' . $i] = $logic;
          }
        }
      }
    }

    if (
      array_key_exists('math-expressions', $this->form_options)
      && !empty($this->form_options['math-expressions'])
    ) {
      $repeaterInputs = array_filter($this->form_elements, function ($element) {
        return (array_key_exists("type", $element)
          && $element["type"] === "repeater-input"
        );
      });
      $indexedRepeaterInputs = [];
      foreach ($repeaterInputs as $repeaterInput) {
        $indexedRepeaterInputs[$repeaterInput["id"]] = $repeaterInput;
      }

      foreach ($this->form_options['math-expressions'] as $mathExpression) {
        $_expression = $mathExpression["expression"];

        $formValuesMatches = [];
        $formValuesIds = [];
        preg_match_all(
          '/{{(\d+)\|.+?}}/',
          $mathExpression["expression"],
          $formValuesMatches
        );
        $formValuesIds = $formValuesMatches[1];
        for ($matchIndex = 0; $matchIndex < count($formValuesMatches[0]); $matchIndex++) {
          $match = $formValuesMatches[0][$matchIndex];
          $id = $formValuesMatches[1][$matchIndex];
          $idValue = (!is_null($values)
            && isset($values[$id])
          )
            ? $values[$id]
            : 0;
          $_expression = str_replace($match, $idValue, $_expression);
        }
        $mathExpression["formValuesIds"] = $formValuesIds;

        $rowValuesMatches = [];
        $rowValuesIds = [];
        preg_match_all(
          '/\[\[(\d+)\|[^\|]+\|(\d+)\|[^\|]+\]\]/',
          $mathExpression["expression"],
          $rowValuesMatches
        );
        for ($matchIndex = 0; $matchIndex < count($rowValuesMatches[0]); $matchIndex++) {
          $match = $rowValuesMatches[0][$matchIndex];
          $repeaterInputId = $rowValuesMatches[1][$matchIndex];
          $repeaterInputColumn = $rowValuesMatches[2][$matchIndex];
          $mathExpression["repeaterInputId"] = $repeaterInputId;
          array_push($rowValuesIds, [
            "repeaterInputId" => $repeaterInputId,
            "repeaterInputColumn" => $repeaterInputColumn
          ]);
          $_expression = str_replace($match, "[[$repeaterInputId|$repeaterInputColumn]]", $_expression);
        }
        $mathExpression["rowValuesIds"] = $rowValuesIds;

        $rowTotalsMatches = [];
        $rowTotalsIds = [];
        preg_match_all(
          '/\{\[(\d+)\|[^\|]+\|(\d+)\|[^\|]+\]\}/',
          $mathExpression["expression"],
          $rowTotalsMatches
        );
        for ($matchIndex = 0; $matchIndex < count($rowTotalsMatches[0]); $matchIndex++) {
          $match = $rowTotalsMatches[0][$matchIndex];
          $repeaterInputId = $rowTotalsMatches[1][$matchIndex];
          $repeaterInputColumn = $rowTotalsMatches[2][$matchIndex];
          $mathExpression["repeaterInputId"] = $repeaterInputId;
          array_push($rowTotalsIds, [
            "repeaterInputId" => $repeaterInputId,
            "repeaterInputColumn" => $repeaterInputColumn
          ]);
          $_expression = str_replace($match, "{[$repeaterInputId|$repeaterInputColumn]}", $_expression);
        }
        $mathExpression["rowTotalsIds"] = $rowTotalsIds;

        /**
         * importance top to bottom
         * totals - has total values
         * row - has row values
         * simple - only form values
         */
        $expressionType = (count($rowTotalsIds) > 0)
          ? "totals"
          : ((count($rowValuesIds) > 0) ? "row" : "simple");
        $mathExpression["expressionType"] = $expressionType;

        switch ($expressionType) {
          case "simple": {
              $value = 0;
              try {
                $parser = new StdMathParser();
                $AST = $parser->parse($_expression);
                $evaluator = new Evaluator();
                $value = $AST->accept($evaluator);
              } catch (Exception $e) {
                // skip for the moment
              }
              if (empty($value)) {
                $value = 0;
              }
              $mathExpression["value"] = $value;
              break;
            }
          case "row": {
              $repeaterInputRows = (!is_null($values)
                && isset($values[$mathExpression["repeaterInputId"]])
              )
                ? $values[$mathExpression["repeaterInputId"]]
                : [];
              $rowValues = [];
              foreach ($repeaterInputRows as $repeaterInputRow) {
                $rowExpression = $_expression;

                foreach ($rowValuesIds as $rowValuesId) {
                  $inputId = $rowValuesId["repeaterInputId"];
                  $column = $rowValuesId["repeaterInputColumn"];
                  $rowExpression = str_replace(
                    "[[$inputId|$column]]",
                    $repeaterInputRow[$column - 1],
                    $rowExpression
                  );
                }

                $value = 0;
                try {
                  $parser = new StdMathParser();
                  $AST = $parser->parse($rowExpression);
                  $evaluator = new Evaluator();
                  $value = $AST->accept($evaluator);
                } catch (Exception $e) {
                  // skip for the moment
                }
                if (empty($value)) {
                  $value = 0;
                }
                array_push($rowValues, $value);
              }
              $mathExpression["value"] = $rowValues;
              break;
            }
          case "totals": {
              $repeaterInputRows = (!is_null($values)
                && isset($values[$mathExpression["repeaterInputId"]])
              )
                ? $values[$mathExpression["repeaterInputId"]]
                : [];

              $rowValues = 0;
              foreach ($repeaterInputRows as $repeaterInputRow) {
                $rowExpression = $_expression;
                foreach ($rowTotalsIds as $rowTotalsId) {
                  $inputId = $rowTotalsId["repeaterInputId"];
                  $column = $rowTotalsId["repeaterInputColumn"];
                  $_val = 0;

                  if (isset($repeaterInputRow[$column - 1])) {
                    $_val = $repeaterInputRow[$column - 1];
                  }

                  $rowExpression = str_replace(
                    "{[$inputId|$column]}",
                    $_val,
                    $rowExpression
                  );
                }

                $value = 0;
                try {
                  $parser = new StdMathParser();
                  $AST = $parser->parse($rowExpression);
                  $evaluator = new Evaluator();
                  $value = $AST->accept($evaluator);
                } catch (Exception $e) {
                  // skip for the moment
                }
                if (empty($value)) {
                  $value = 0;
                }
                $rowValues += $value;
              }
              $mathExpression["value"] = $rowValues;
              break;
            }
        }

        $key = "{{" . $mathExpression['id'] . "|" . $mathExpression['name'] . "}}";
        $this->math_expressions[$key] = $mathExpression;
      }
    }
  }

  public static function getExternalValuesAsArray($element) {
    if (
      isset($element['external-datasource']) &&
      isset($element['external-datasource-url']) &&
      $element['external-datasource'] === 'on' &&
      filter_var($element['external-datasource-url'], FILTER_VALIDATE_URL)
    ) {
      $element["options"] = evo_get_external_select_datasource(
        $element['external-datasource-url'],
        isset($element['external-datasource-path']) ? $element['external-datasource-path'] : '',
        $element["options"]
      );
    }
    return $element["options"];
  }


  public static function getExternalValuesAsString($element, $defaultVal, $form_values)
  {
    if (
      isset($element['external-datasource']) &&
      isset($element['external-datasource-url']) &&
      $element['external-datasource'] === 'on' &&
      filter_var($element['external-datasource-url'], FILTER_VALIDATE_URL)
    ) {
      $url =  $element['external-datasource-url']; //preg_replace("/{{(\d+).+?}}/", '{{$1}}', $element['external-datasource-url']);
      $matches = [];
      preg_match_all(
        "/{{(\d+).+?}}/",
        $url,
        $matches
      );

      for ($matchIndex = 0; $matchIndex < count($matches[0]); $matchIndex++) {
        $match = $matches[0][$matchIndex];
        $id = $matches[1][$matchIndex];

        $value = "";
        if (
          !is_null($form_values)
          && isset($form_values[$id])
        ) {
          $value = $form_values[$id];
        }

        $url = str_replace(
          $match,
          urlencode($value),
          $url
        );
      }
      $defaultVal = evo_get_external_text_datasource(
        $url,
        isset($element['external-datasource-path']) ? $element['external-datasource-path'] : '',
        $defaultVal
      );
    }
    return $defaultVal;
  }


  public function getFormObject()
  {
    $response['id'] = $this->id;
    $response['uuid'] = $this->leform->random_string(16);
    $response['name'] = $this->name;
    $response['form_pages'] = $this->form_pages;
    // form options
    $response['options'] = $this->form_options;
    // form elements
    $response['elements'] = [];

    for ($i = 0; $i < sizeof($this->form_pages); $i++) {
      if (!empty($this->form_pages[$i]) && is_array($this->form_pages[$i])) {
        $response['elements'] = array_merge(
          $response['elements'],
          $this->get_form_elements($this->form_pages[$i]['id'], 0)
        );
      }
    }

    $response['css'] = $this->get_form_css();
    $response['toolbarTools'] = $this->leform->toolbarTools;
    $response['leformOptions'] = $this->leform->options;

    return $response;
  }

  public function get_form_elements($_parent, $_parent_col)
  {
    $elements = [];
    $idxs = [];
    $seqs = [];
    for ($i = 0; $i < sizeof($this->form_elements); $i++) {
      if (empty($this->form_elements[$i])) continue;
      if (
        $this->form_elements[$i]["_parent"] == $_parent
        && ($this->form_elements[$i]["_parent-col"] == $_parent_col || $_parent == "")
      ) {
        $idxs[] = $i;
        $seqs[] = intval($this->form_elements[$i]["_seq"]);
      }
    }
    if (empty($idxs)) {
      return [];
    }

    for ($i = 0; $i < sizeof($seqs); $i++) {
      $sorted = -1;
      for ($j = 0; $j < sizeof($seqs) - 1; $j++) {
        if ($seqs[$j] > $seqs[$j + 1]) {
          $sorted = $seqs[$j];
          $seqs[$j] = $seqs[$j + 1];
          $seqs[$j + 1] = $sorted;
          $sorted = $idxs[$j];
          $idxs[$j] = $idxs[$j + 1];
          $idxs[$j + 1] = $sorted;
        }
      }
      if ($sorted == -1) break;
    }
    for ($k = 0; $k < sizeof($idxs); $k++) {
      $i = $idxs[$k];
      $element = $this->form_elements[$i];
      if (empty($this->form_elements[$i])) continue;
      $icon = "";
      $options = "";
      $extra_class = "";
      $column_label_class = "";
      $column_input_class = "";
      $properties = [];

      if (array_key_exists('label-style-position', $element)) {
        $properties["label-style-position"] = $element["label-style-position"];
        if ($properties["label-style-position"] == "") $properties["label-style-position"] = $this->form_options["label-style-position"];
        if ($properties["label-style-position"] == "") $properties["label-style-position"] = "top";
        if ($element["label-style-position"] == "left" || $element["label-style-position"] == "right") $properties["label-style-width"] = $element["label-style-width"];
        else $properties["label-style-width"] = "";
        if ($properties["label-style-width"] == "") $properties["label-style-width"] = $this->form_options["label-style-width"];
        $properties["label-style-width"] = intval($properties["label-style-width"]);
        if ($properties["label-style-width"] < 1 || $properties["label-style-width"] > 11) $properties["label-style-width"] = 3;
        if ($properties["label-style-position"] == "left" || $properties["label-style-position"] == "right") {
          $column_label_class = " leform-col-" . $properties["label-style-width"];
          $column_input_class = " leform-col-" . (12 - $properties["label-style-width"]);
        }
      }

      if (array_key_exists('icon-left-icon', $element)) {
        if ($element["icon-left-icon"] != "") {
          if (
            array_key_exists("input-icon-display", $this->form_options)
            && $this->form_options["input-icon-display"] === "show"
          ) {
            $extra_class .= " leform-icon-left";
            $icon .= "<i class='leform-icon-left " . $element["icon-left-icon"] . "'></i>";
            $options = "";
            if ($element["icon-left-size"] != "") {
              $options .= "font-size:" . $element["icon-left-size"] . "px;";
            }
          }
        }
      }
      if (array_key_exists('icon-right-icon', $element)) {
        if ($element["icon-right-icon"] != "") {
          if (
            array_key_exists("input-icon-display", $this->form_options)
            && $this->form_options["input-icon-display"] === "show"
          ) {
            $extra_class .= " leform-icon-right";
            $icon .= "<i class='leform-icon-right " . $element["icon-right-icon"] . "'></i>";
            $options = "";
            if ($element["icon-right-size"] != "") {
              $options .= "font-size:" . $element["icon-right-size"] . "px;";
            }
          }
        }
      }

      if (
        array_key_exists("css", $element)
        && sizeof($element["css"]) > 0
      ) {
        $elementPropertiesMeta = $this->leform->getElementPropertiesMeta();
        if (
          array_key_exists(
            $element["type"],
            $elementPropertiesMeta
          )
          && array_key_exists(
            "css",
            $elementPropertiesMeta[$element["type"]]
          )
        ) {
          for ($j = 0; $j < sizeof($element["css"]); $j++) {
            if (
              !empty($element["css"][$j]["css"])
              && !empty($element["css"][$j]["selector"])
            ) {
              if (
                array_key_exists(
                  $element["css"][$j]["selector"],
                  $elementPropertiesMeta[$element["type"]]["css"]["selectors"]
                )
              ) {
                $properties["css-class"] = $elementPropertiesMeta[$element["type"]]["css"]["selectors"][$element["css"][$j]["selector"]]["front-class"];
                $properties["css-class"] = str_replace(
                  ["{element-id}", "{form-id}"],
                  [$element['id'], $this->id],
                  $properties["css-class"]
                );
              }
            }
          }
        }
      }
      $properties["tooltip-label"] = "";
      $properties["tooltip-description"] = "";
      $properties["tooltip-input"] = "";
      if (array_key_exists("tooltip", $element) && trim($element["tooltip"]) != "") {
        if (array_key_exists("tooltip-anchor", $this->form_options) && $this->form_options["tooltip-anchor"] != "" && $this->form_options["tooltip-anchor"] != "none") {
          switch ($this->form_options["tooltip-anchor"]) {
            case 'description':
              $properties["tooltip-description"] = " <span class='leform-tooltip-anchor leform-if leform-if-help-circled' title='" .
                $this->replaceWithPredefinedValues(
                  $element["tooltip"],
                  $this->predefinedValues,
                )
                . "'></span>";
              break;
            case 'input':
              $properties["tooltip-input"] = " title='" .
                $this->replaceWithPredefinedValues(
                  $element["tooltip"],
                  $this->predefinedValues,
                )
                . "'";
              break;
            default:
              $properties["tooltip-label"] = " <span class='leform-tooltip-anchor leform-if leform-if-help-circled' title='" .
                $this->replaceWithPredefinedValues(
                  $element["tooltip"],
                  $this->predefinedValues,
                )
                . "'></span>";
              break;
          }
        }
      }

      $properties["required-label-left"] = "";
      $properties["required-label-right"] = "";
      $properties["required-description-left"] = "";
      $properties["required-description-right"] = "";
      if (array_key_exists("required", $element) && trim($element["required"]) == "on") {
        if (array_key_exists("required-position", $this->form_options) && $this->form_options["required-position"] != "" && $this->form_options["required-position"] != "none" && array_key_exists("required-text", $this->form_options) && $this->form_options["required-text"] != "") {
          switch ($this->form_options["required-position"]) {
            case 'label-left':
            case 'label-right':
            case 'description-left':
            case 'description-right':
              $properties["required-" . $this->form_options["required-position"]] = "<span class='leform-required-symbol leform-required-symbol-" . $this->form_options["required-position"] . "'>" . $this->form_options["required-text"] . "</span>";
              break;
            default:
              break;
          }
        }
      }
      if (array_key_exists($element["type"], $this->leform->toolbarTools)) {
        switch ($element["type"]) {
          case "button":
          case "link-button":
            $icon = "";
            if (array_key_exists("button-style-size", $element) && $element['button-style-size'] != "") $properties['size'] = $element['button-style-size'];
            else $properties['size'] = $this->form_options['button-style-size'];
            if (array_key_exists("button-style-width", $element) && $element['button-style-width'] != "") $properties['width'] = $element['button-style-width'];
            else $properties['width'] = $this->form_options['button-style-width'];
            if (
              array_key_exists("button-style-position", $element)
              && $element['button-style-position'] != ""
            ) {
              $properties['position'] = $element['button-style-position'];
            } else {
              $properties['position'] = $this->form_options['button-style-position'];
            }
            $label = '<span>' .
              $this->replaceWithPredefinedValues(
                $element["label"],
                $this->predefinedValues,
              )
              . '</span>';
            if (array_key_exists("icon-left", $element) && $element["icon-left"] != "") $label = "<i class='leform-icon-left " . $element["icon-left"] . "'></i>" . $label;
            if (array_key_exists("icon-right", $element) && $element["icon-right"] != "") $label .= "<i class='leform-icon-right " . $element["icon-right"] . "'></i>";

            $properties['style-attr'] = "";
            if (array_key_exists("colors-background", $element) && $element["colors-background"] != "") $properties['style-attr'] .= "background-color:" . $element["colors-background"] . ";";
            if (array_key_exists("colors-border", $element) && $element["colors-border"] != "") $properties['style-attr'] .= "border-color:" . $element["colors-border"] . ";";
            if (array_key_exists("colors-text", $element) && $element["colors-text"] != "") $properties['style-attr'] .= "color:" . $element["colors-text"] . ";";

            $properties['style-attr'] = "";
            if (array_key_exists("colors-hover-background", $element) && $element["colors-hover-background"] != "") $properties['style-attr'] .= "background-color:" . $element["colors-hover-background"] . ";";
            if (array_key_exists("colors-hover-border", $element) && $element["colors-hover-border"] != "") $properties['style-attr'] .= "border-color:" . $element["colors-hover-border"] . ";";
            if (array_key_exists("colors-hover-text", $element) && $element["colors-hover-text"] != "") $properties['style-attr'] .= "color:" . $element["colors-hover-text"] . ";";

            $properties['style-attr'] = "";
            if (array_key_exists("colors-active-background", $element) && $element["colors-active-background"] != "") $properties['style-attr'] .= "background-color:" . $element["colors-active-background"] . ";";
            if (array_key_exists("colors-active-border", $element) && $element["colors-active-border"] != "") $properties['style-attr'] .= "border-color:" . $element["colors-active-border"] . ";";
            if (array_key_exists("colors-active-text", $element) && $element["colors-active-text"] != "") $properties['style-attr'] .= "color:" . $element["colors-active-text"] . ";";

            $properties['extra_attr'] = '';
            if ($element["type"] == 'button') {
              if ($element['button-type'] == 'submit') $properties['extra_attr'] = " href='#' onclick='return leform_submit(this);'";
              else if ($element['button-type'] == 'next') $properties['extra_attr'] = " href='#' onclick='return leform_submit(this, \"next\");'";
              else if ($element['button-type'] == 'prev') $properties['extra_attr'] = " href='#' onclick='return leform_submit(this, \"prev\");'";
            } else {
              $properties['extra_attr'] = " href='" . $element['link'] . "'" . ($element['new-tab'] == "on" ? " target='_blank'" : "");
            }
            break;

          case "file":
            if (array_key_exists("button-style-size", $element) && $element['button-style-size'] != "") $properties['size'] = $element['button-style-size'];
            else $properties['size'] = $this->form_options['button-style-size'];
            if (array_key_exists("button-style-width", $element) && $element['button-style-width'] != "") $properties['width'] = $element['button-style-width'];
            else $properties['width'] = $this->form_options['button-style-width'];
            if (array_key_exists("button-style-position", $element) && $element['button-style-position'] != "") $properties['position'] = $element['button-style-position'];
            else $properties['position'] = $this->form_options['button-style-position'];
            $label = '<span>' . $element["button-label"] . '</span>';
            if (array_key_exists("icon-left", $element) && $element["icon-left"] != "") $label = "<i class='leform-icon-left " . $element["icon-left"] . "'></i>" . $label;
            if (array_key_exists("icon-right", $element) && $element["icon-right"] != "") $label .= "<i class='leform-icon-right " . $element["icon-right"] . "'></i>";

            $properties['style-attr'] = "";
            if (array_key_exists("colors-background", $element) && $element["colors-background"] != "") $properties['style-attr'] .= "background-color:" . $element["colors-background"] . ";";
            if (array_key_exists("colors-border", $element) && $element["colors-border"] != "") $properties['style-attr'] .= "border-color:" . $element["colors-border"] . ";";
            if (array_key_exists("colors-text", $element) && $element["colors-text"] != "") $properties['style-attr'] .= "color:" . $element["colors-text"] . ";";

            $properties['style-attr'] = "";
            if (array_key_exists("colors-hover-background", $element) && $element["colors-hover-background"] != "") $properties['style-attr'] .= "background-color:" . $element["colors-hover-background"] . ";";
            if (array_key_exists("colors-hover-border", $element) && $element["colors-hover-border"] != "") $properties['style-attr'] .= "border-color:" . $element["colors-hover-border"] . ";";
            if (array_key_exists("colors-hover-text", $element) && $element["colors-hover-text"] != "") $properties['style-attr'] .= "color:" . $element["colors-hover-text"] . ";";

            $properties['style-attr'] = "";
            if (array_key_exists("colors-active-background", $element) && $element["colors-active-background"] != "") $properties['style-attr'] .= "background-color:" . $element["colors-active-background"] . ";";
            if (array_key_exists("colors-active-border", $element) && $element["colors-active-border"] != "") $properties['style-attr'] .= "border-color:" . $element["colors-active-border"] . ";";
            if (array_key_exists("colors-active-text", $element) && $element["colors-active-text"] != "") $properties['style-attr'] .= "color:" . $element["colors-active-text"] . ";";

            $accept_raw = explode(',', $element['allowed-extensions']);
            $accept = [];
            foreach ($accept_raw as $extension) {
              $extension = trim(trim($extension), '.');
              if (!empty($extension)) $accept[] = '.' . strtolower($extension);
            }
            $properties['accept'] = $accept;
            if (isset($element['value']) && is_array($element['value'])) {
              $files = Upload::whereIn("id", $element['value'])
                ->get();
              $element['value'] = [];
              foreach ($files as $file) {
                $filePath = "uploads/$file->filename";
                $fileExists = Storage::disk("public")
                  ->exists($filePath);

                if ($fileExists) {
                  // $url = Storage::url($filePath);
                  $url = $this->base_path . Storage::url($filePath);
                  $dummyUrlVariable = public_path("storage/uploads/$file->filename");
                  if (file_exists($dummyUrlVariable)) {
                      $element['value'][] = [
                          'name' => $file->filename_original,
                          'url' => "data:image/jpeg;base64," . base64_encode(file_get_contents($dummyUrlVariable))
                      ];
                  }
                }
              }
            }
            break;

          case "email":
            if ($element['input-style-size'] != "")
              $extra_class .= " leform-input-" . $element['input-style-size'];
            break;

          case "text":
            if ($element['input-style-size'] != "") {
              $extra_class .= " leform-input-" . $element['input-style-size'];
            }
            $element["default"] = FormService::getExternalValuesAsString(
              $element,
              $element["default"],
              $this->form_values
            );
            break;

          case "number":
            if ($element['input-style-size'] != "") $extra_class .= " leform-input-" . $element['input-style-size'];
            break;

          case "numspinner":
            $ranges = $this->_prepare_ranges($this->form_elements[$i]["number-advanced-value2"]);
            if ($element['input-style-size'] != "") $extra_class .= " leform-input-" . $element['input-style-size'];
            $properties['ranges'] = $ranges;
            break;

          case "password":
            if ($element['input-style-size'] != "") $extra_class .= " leform-input-" . $element['input-style-size'];
            break;

          case "date":
            if ($element['input-style-size'] != "") $extra_class .= " leform-input-" . $element['input-style-size'];
            break;

          case "time":
            if ($element['input-style-size'] != "") $extra_class .= " leform-input-" . $element['input-style-size'];
            break;

          case "textarea":
            $properties["textarea-height"] = $element["textarea-style-height"];
            if ($properties["textarea-height"] == "") $properties["textarea-height"] = $this->form_options["textarea-height"];
            if ($properties["textarea-height"] == "") $properties["textarea-height"] = 160;

            $element["default"] = FormService::getExternalValuesAsString(
              $element,
              $element["default"],
              $this->form_values
            );
            break;

          case "signature":
            $properties["height"] = $element["height"];
            if (empty($properties["height"])) {
              $properties["height"] = 220;
            }
            $value = array_key_exists('value', $element) ? $element['value'] : null;
            if (!empty($value)) {
              $filePath = str_replace("/storage/", "", $value);
              $fileName = str_replace("signatures/", "", $filePath);
              $signatureFileExists = Storage::disk("public")
                ->exists($filePath);
              if ($signatureFileExists) {
                // $element['value'] = $this->base_path . Storage::url($filePath);
                // $element['value'] = Storage::url($filePath);
                $element['value'] = "data:image/jpeg;base64," . base64_encode(file_get_contents(public_path($value)));
              } else {
                $element['value'] = "";
              }
            }
            break;

          case "rangeslider":
            $options = ($element["readonly"] == "on" ?  "data-from-fixed='true' data-to-fixed='true'" : "") . " " . ($element["double"] == "on" ? "data-type='double'" : "data-type='single'") . " " . ($element["grid-enable"] == "on" ? "data-grid='true'" : "data-grid='false'") . " " . ($element["min-max-labels"] == "on" ? "data-hide-min-max='false'" : "data-hide-min-max='true'") . " data-skin='" . $this->form_options['rangeslider-skin'] . "' data-min='" . $element["range-value1"] . "' data-max='" . $element["range-value2"] . "' data-step='" . $element["range-value3"] . "' data-from='" .
              $this->replaceWithPredefinedValues(
                $element["handle"],
                $this->predefinedValues
              )
              . "'data-to='" .
              $this->replaceWithPredefinedValues(
                $element["handle2"],
                $this->predefinedValues
              )
              . "' data-prefix='" . $element["prefix"] . "' data-postfix='" . $element["postfix"] . "' data-input-values-separator=':'";
            break;

          case "select":
            $options = "";
            if(isset($element['value']) && is_array($element['options'])) {
              $element["options"] = FormService::getExternalValuesAsArray($element);
              $selectValue = $element['value'];
              foreach($element['options'] as $option) {
                if($selectValue === $option['value']) {
                  $options = !empty($option['label']) ? $option['label'] : $selectValue;
                  break;
                }
              }
            }
            break;
          case "checkbox":
            $options = "";
            $id = $this->leform->random_string(16);
            $properties['checkbox-size'] = $this->form_options['checkbox-radio-style-size'];
            if (empty($element['checkbox-style-position'])) {
              $properties['checkbox-position'] = $this->form_options['checkbox-radio-style-position'];
            } else {
              $properties['checkbox-position'] = $element['checkbox-style-position'];
            }
            if (empty($element['checkbox-style-align'])) {
              $properties['checkbox-align'] = $this->form_options['checkbox-radio-style-align'];
            } else {
              $properties['checkbox-align'] = $element['checkbox-style-align'];
            }
            if (empty($element['checkbox-style-layout'])) {
              $properties['checkbox-layout'] = $this->form_options['checkbox-radio-style-layout'];
            } else {
              $properties['checkbox-layout'] = $element['checkbox-style-layout'];
            }
            $extra_class .= " leform-cr-layout-" . $properties['checkbox-layout'] . " leform-cr-layout-" . $properties['checkbox-align'];

            for ($j = 0; $j < sizeof($element["options"]); $j++) {
              $selected = "";
              if (
                array_key_exists("value", $element)
                && (is_array($element["value"])
                  ? in_array($element["options"][$j]["value"], $element["value"])
                  : $element["options"][$j]["value"] === $element["value"]
                )
              ) {
                $selected = " checked='checked'";
              }
              $option = "<div class='leform-cr-box'><input class='leform-checkbox leform-checkbox-" . $this->form_options["checkbox-view"] . " leform-checkbox-" . $properties["checkbox-size"] . "' type='checkbox' name='leform-" . $element['id'] . "[]' id='" . "leform-checkbox-" . $element['id'] . "-" . $id . "-" . $i . "-" . $j . "' value='" . $element["options"][$j]["value"] . "'" . $selected . " data-default='" . (empty($selected) ? 'off' : 'on') . "' data-input-name='" . $element["name"] . "' onchange='leform_input_changed(this);' /><label for='" . "leform-checkbox-" . $id . "-" . $i . "-" . $j . "' onclick='jQuery(this).closest(\".leform-input\").find(\".leform-element-error\").fadeOut(300, function(){jQuery(this).remove();});'></label></div>";
              if ($properties['checkbox-position'] == "left") {
                $option .= "<div class='leform-cr-label leform-ta-" . $properties['checkbox-align'] . "'><label for='" . "leform-checkbox-" . $element['id'] . "-" . $id . "-" . $i . "-" . $j . "' onclick='jQuery(this).closest(\".leform-input\").find(\".leform-element-error\").fadeOut(300, function(){jQuery(this).remove();});'>" . $element["options"][$j]["label"] . "</label></div>";
              } else {
                $option = "<div class='leform-cr-label leform-ta-" . $properties['checkbox-align'] . "'><label for='" . "leform-checkbox-" . $element['id'] . "-" . $id . "-" . $i . "-" . $j . "' onclick='jQuery(this).closest(\".leform-input\").find(\".leform-element-error\").fadeOut(300, function(){jQuery(this).remove();});'>" . $element["options"][$j]["label"] . "</label></div>" . $option;
              }
              $options .= "<div class='leform-cr-container leform-cr-container-" . $properties["checkbox-size"] . " leform-cr-container-" . $properties["checkbox-position"] . "'>" . $option . "</div>";
            }
            break;

          case "matrix":
            $options = "";
            $id = $this->leform->random_string(16);
            $uids[] = $id;
            $properties['checkbox-size'] = $this->form_options['checkbox-radio-style-size'];
            $properties['checkbox-position'] = $this->form_options['checkbox-radio-style-position'];
            $properties['checkbox-align'] = $this->form_options['checkbox-radio-style-align'];
            $properties['checkbox-layout'] = $this->form_options['checkbox-radio-style-layout'];

            $extra_class .= " leform-cr-layout-" . $properties['checkbox-layout'] . " leform-cr-layout-" . $properties['checkbox-align'];
            $mOptions = '<table style="border: none !important;" class="w-full"><thead style="border: none !important;"><tr style="border: none !important;"><th style="border: none !important;"></th>';
            $topOptions = "";
            foreach ($element['top'] as $leftOption) {
              $topOptions .= "
                    <div class='pb-3'>
                        " . $leftOption['label'] . "
                    </div>
                ";
              $mOptions .= "<th style='border: none !important;' class='pb-3'>" . $leftOption['label'] . "</th>";
            }
            $mOptions .= "</tr></thead><tbody style='border: none !important;'>";

            $topOptions = "
                <div class='grid grid-cols-" . (count($element["top"]) + 2) . " gap-2'>
                    <div class='col-span-2'></div>
                    $topOptions
                </div>
            ";

            $isCheckbox = $element['multi-select'] === 'on';

            $checkboxOptions = "";
            // for ($j = 0; $j < sizeof($this->form_elements[$i]["left"]); $j++) {
            foreach ($element["left"] as $leftElementIndex => $leftElement) {
              $row = "";
              $mOptions .= "<tr style='border: none !important;'><th style='border: none !important;'> " . $leftElement['label'] . "</th>";
              // foreach ($this->form_elements[$i]["top"] as $elementKey => $element) {
              foreach ($element["top"] as $topElementIndex => $topElement) {
                $classlist = "";
                $value = $leftElement["value"] . "--" . $topElement["value"];
                $inputType = ($isCheckbox) ? 'checkbox' : 'radio';

                if ($isCheckbox) {
                  $classlist = implode(' ', [
                    "leform-checkbox",
                    "leform-checkbox-" . $this->form_options["checkbox-view"],
                    "leform-checkbox-" . $properties["checkbox-size"],
                  ]);
                } else {
                  $classlist = implode(' ', [
                    "leform-radio",
                    "leform-radio-" . $this->form_options["radio-view"],
                    "leform-radio-" . $properties["checkbox-size"],
                  ]);
                }

                $checkboxOption = "
                        <div class='leform-cr-box'>
                            <input
                                class='$classlist'
                                type='$inputType'
                                name='leform-" . $element['id'] . "[]'
                                id='leform-checkbox-" . $element['id'] . "-$id-$i-$j-$topElementIndex'
                                value='$value'
                                " . ((array_key_exists("value", $element)
                  && is_array($element["value"])
                ) ? (in_array($value, $element["value"]) ? "checked" : "") : "") . "
                            />
                            <label
                                for='leform-checkbox-" . $element['id'] . "-$id-$i-$j-$topElementIndex'
                                onclick='jQuery(this).closest(\".leform-input\").find(\".leform-element-error\").fadeOut(300, function(){jQuery(this).remove();});'
                            ></label>
                        </div>
                    ";
                $mOptions .= "<td style='border: none !important;'><div class='leform-cr-box'>
                            <input
                                class='$classlist'
                                type='$inputType'
                                name='leform-" . $element['id'] . "[]'
                                id='leform-checkbox-" . $element['id'] . "-$id-$i-$j-$topElementIndex'
                                value='$value'
                                " . (
                  (array_key_exists("value", $element) && is_array($element["value"])
                    && in_array($value, $element["value"])
                  )
                  ? "checked"
                  : ""
                ) . "
                            />
                            <label
                                for='leform-checkbox-" . $element['id'] . "-$id-$i-$j-$topElementIndex'
                                onclick='jQuery(this).closest(\".leform-input\").find(\".leform-element-error\").fadeOut(300, function(){jQuery(this).remove();});'
                            ></label>
                        </div></td>";
                $row .= $checkboxOption;
              }
              $row = "
                    <form class='grid grid-cols-" .  (count($element["top"]) + 2) . " gap-2'>
                        <div class='col-span-2'>
                            " . $leftElement['label'] . "
                        </div>
                        $row
                    </form>
                ";

              $checkboxOptions .= "
                    <div class='leform-cr-container leform-cr-container-"
                . $properties["checkbox-size"] .
                " leform-cr-container-"
                . $properties["checkbox-position"] .
                "'
                    >
                        $row
                    </div>
                ";

              $mOptions .= "</tr>";
            }
            $mOptions .= "</tbody></table>";
            // $options = $topOptions . $checkboxOptions;
            $options = $mOptions;
            break;

          case "imageselect":
            $selectOptions = "";
            $options = "";

            $properties["image-width"] = intval($element['image-style-width']);
            if (
              $properties["image-width"] <= 0
              || $properties["image-width"] >= 600
            ) {
              $properties["image-width"] = 120;
            }
            $properties["image-height"] = intval($element['image-style-height']);
            if (
              $properties["image-height"] <= 0
              || $properties["image-height"] >= 600
            ) {
              $properties["image-height"] = 120;
            }

            $properties["label-height"] = intval($element['label-height']);
            if (
              $properties["label-height"] <= 0
              || $properties["label-height"] >= 200
              || $element['label-enable'] != 'on'
            ) {
              $properties["label-height"] = 0;
            }

            if ($this->form_options['imageselect-selected-scale'] == 'on') {
              $scale = min(
                floatval(($properties["image-width"] + 8) / $properties["image-width"]),
                floatval(($properties["image-height"] + 8) / $properties["image-height"])
              );
              $properties["image-scale"] = $scale;
            }
            $extra_class .= ' leform-ta-' . $this->form_options['imageselect-style-align'] . ' leform-imageselect-' . $this->form_options['imageselect-style-effect'];

            if (array_key_exists("value", $element)) {
              foreach ($element["options"] as $option) {
                $isSelected = false;

                if (gettype($element["value"]) === "array") {
                  $isSelected = in_array($option["value"], $element["value"]);
                } else {
                  $isSelected = $element["value"] === $option["value"];
                }

                if ($isSelected) {
                  $selected = " ";
                  $properties['image-label'] = "";
                  if ($properties["label-height"] > 0) {
                    $properties['image-label'] = "<span class='leform-imageselect-label'>" . $element["options"][$j]["label"] . "</span>";
                  }
                  $image = "data:image/jpeg;base64," . base64_encode(file_get_contents(public_path($option['image'])));
                  $selectOptions .= "
                              <input
                                  class='leform-imageselect'
                                  type='" . $element['mode'] . "'
                                  checked='checked'
                              />
                              <label>
                                  <span
                                      class='leform-imageselect-image'
                                      style='background-image: url(" . $image . ");'
                                  >
                                  </span>
                                  " . $properties['image-label'] . "
                              </label>
                          ";
                }
              }
            }

            $element["selectOptions"] = $selectOptions;

            break;

          case "multiselect":
            if (!empty($element['multiselect-style-align'])) {
              $properties['align'] = $element['multiselect-style-align'];
            } else if (!empty($this->form_options['multiselect-style-align'])) {
              $properties['align'] = $this->form_options['multiselect-style-align'];
            } else {
              $properties['align'] = 'left';
            }

            $multiselectOptions = "";
            foreach ($element["options"] as $option) {
              $selected = "";
              if (
                array_key_exists("value", $element)
                && is_array($element["value"])
                && in_array($option["value"], $element["value"])
              ) {
                $selected = " checked='checked'";
              }
              $multiselectOptions .= "
                    <input
                        type='checkbox'
                        " . $selected . "
                    />
                    <label>" . $option["label"] . "</label>
                ";
            }
            $element["multiselectOptions"] = $multiselectOptions;
            break;

          case "radio":
            $radioOptions = "";
            $properties['radio-size'] = $this->form_options['checkbox-radio-style-size'];
            if (empty($element['radio-style-position'])) {
              $properties['radio-position'] = $this->form_options['checkbox-radio-style-position'];
            } else {
              $properties['radio-position'] = $element['radio-style-position'];
            }
            if (empty($element['radio-style-align'])) {
              $properties['radio-align'] = $this->form_options['checkbox-radio-style-align'];
            } else {
              $properties['radio-align'] = $element['radio-style-align'];
            }
            if (empty($element['radio-style-layout'])) {
              $properties['radio-layout'] = $this->form_options['checkbox-radio-style-layout'];
            } else {
              $properties['radio-layout'] = $element['radio-style-layout'];
            }
            $extra_class .= " leform-cr-layout-" . $properties['radio-layout'] . " leform-cr-layout-" . $properties['radio-align'];

            foreach ($element["options"] as $option) {
              $selected = "";
              if (
                array_key_exists("value", $element)
                && $option["value"] === $element["value"]
              ) {
                $selected = " checked='checked'";
              }
              $optionHtml = "
                    <div class='leform-cr-box'>
                        <input
                            class='leform-radio leform-radio-" . $this->form_options["radio-view"] . " leform-radio-" . $properties["radio-size"] . "'
                            type='radio'
                            " . $selected . "
                        />
                        <label></label>
                    </div>
                ";
              if ($properties['radio-position'] == "left") {
                $optionHtml .= "
                        <div class='leform-cr-label leform-ta-" . $properties['radio-align'] . "'>
                            <label>
                                " . $option["label"] . "
                            </label>
                        </div>
                    ";
              } else {
                $optionHtml = "
                        <div class='leform-cr-label leform-ta-" . $properties['radio-align'] . "'>
                            <label>" . $option["label"] . "</label>
                        </div>
                    " . $optionHtml;
              }
              $radioOptions .= "<div class='leform-cr-container leform-cr-container-" . $properties["radio-size"] . " leform-cr-container-" . $properties["radio-position"] . "'>" . $optionHtml . "</div>";
            }
            $element["radioOptions"] = $radioOptions;
            break;

          case "tile":
            $tileOptions = "";

            if (
              array_key_exists("tile-style-size", $element)
              && $element['tile-style-size'] != ""
            ) {
              $properties['size'] = $element['tile-style-size'];
            } else {
              $properties['size'] = $this->form_options['tile-style-size'];
            }
            if (
              array_key_exists("tile-style-width", $element)
              && $element['tile-style-width'] != ""
            ) {
              $properties['width'] = $element['tile-style-width'];
            } else {
              $properties['width'] = $this->form_options['tile-style-width'];
            }
            if (
              array_key_exists("tile-style-position", $element)
              && $element['tile-style-position'] != ""
            ) {
              $properties['position'] = $element['tile-style-position'];
            } else {
              $properties['position'] = $this->form_options['tile-style-position'];
            }
            if (
              array_key_exists("tile-style-layout", $element)
              && $element['tile-style-layout'] != ""
            ) {
              $properties['layout'] = $element['tile-style-layout'];
            } else {
              $properties['layout'] = $this->form_options['tile-style-layout'];
            }
            $extra_class .= " leform-tile-layout-" . $properties['layout'] . " leform-tile-layout-" . $properties['position'] . " leform-tile-transform-" . $this->form_options['tile-selected-transform'];

            foreach ($element["options"] as $option) {
              $selected = "";
              if (array_key_exists("value", $element)) {
                if (gettype($element["value"]) === "array") {
                  if (in_array($option["value"], $element["value"])) {
                    $selected = " checked='checked'";
                  }
                } else {
                  if ($option["value"] === $element["value"]) {
                    $selected = " checked='checked'";
                  }
                }
              }
              // if(!empty($selected)) {
              $optionHtml = "
                <div class='leform-tile-box'>
                    <input
                        class='leform-tile leform-tile-" . $properties["size"] . "'
                        type='" . $element['mode'] . "'
                        " . $selected . "
                    />
                    <label>" . $option["label"] . "</label>
                </div>
              ";
              $tileOptions .= "<td><div class='leform-tile-container leform-tile-" . $properties["width"] . "'>" . $optionHtml . "</div></td>";
              // }
            }

            $element["tileOptions"] = $tileOptions;
            break;

          case "star-rating":
            $starOptions = "";

            for ($i = $element['total-stars']; $i > 0; $i--) {
              $checked = (array_key_exists("value", $element)
                && intval($element["value"]) === $i) ? "checked='checked'" : "";
              $starOptions .= "
                    <input type='radio' " . $checked . " />
                    <label></label>
                ";
            }

            $extra_class = "";
            if (!empty($element['star-style-size'])) {
              $extra_class .= " leform-star-rating-" . $element['star-style-size'];
            }

            if ($this->form_options["filled-star-rating-mode"] === "on") {
              $extra_class .= " filled";
            }

            $element["starOptions"] = $starOptions;
            $element["extra_class"] = $extra_class;
            break;

          case "html":
            $htmlExternal = "";
            if (
              isset($element['external-datasource']) &&
              isset($element['external-datasource-url']) &&
              $element['external-datasource'] === 'on'
            ) {
              $url =  $element['external-datasource-url'];//preg_replace("/{{(\d+).+?}}/", '{{$1}}', $element['external-datasource-url']);
              $matches = [];
              preg_match_all(
                "/{{(\d+).+?}}/",
                $url,
                $matches
              );

              for ($matchIndex = 0; $matchIndex < count($matches[0]); $matchIndex++) {
                $match = $matches[0][$matchIndex];
                $id = $matches[1][$matchIndex];

                $value = "";
                if (
                  !is_null($this->form_values)
                  && isset($this->form_values[$id])
                ) {
                  $value = $this->form_values[$id];
                }

                $url = str_replace(
                  $match,
                  urlencode($value),
                  $url 
                );
              }
              $element["content"] = FormService::getExternalValuesAsString(
                $element,
                $element["content"],
                $this->form_values
              );
              // $element['external-datasource-url'] = $url;
              // $element['external-datasource-path'] = $path;
              // $htmlExternal = "
              //   <span class='html-external-datasource' data-url='$url'>
              //       <input type='hidden' class='html-external-datasource-transformed' data-path='$path' value=''/>
              //   </span>
              // ";
            }
            $content = $this->replaceWithPredefinedValues(
              $element["content"],
              $this->predefinedValues
            );
            // replace the variables
            if (
              !empty($this->math_expressions)
              && is_array($this->math_expressions)
            ) {
              foreach ($this->math_expressions as $key => $math_expression) {
                $replacement = "
                    <span
                        class='leform-var leform-var-" . $math_expression['id'] . "'
                        data-id='" . $math_expression['id'] . "'
                    >" . (
                  (gettype($math_expression['value']) === "array")
                  ? implode(", ", $math_expression['value'])
                  : $math_expression['value']
                ) .  "</span>";
                $content = str_replace($key, $replacement, $content);
              }
            }

            $matches = [];
            preg_match_all(
              "/{{(\d+).+?}}/",
              $content,
              $matches
            );

            for ($matchIndex = 0; $matchIndex < count($matches[0]); $matchIndex++) {
              $match = $matches[0][$matchIndex];
              $id = $matches[1][$matchIndex];

              $value = "";
              if (
                !is_null($this->form_values)
                && isset($this->form_values[$id])
              ) {
                $value = $this->form_values[$id];
              }

              $replacement = "
                  <span
                      class='leform-repeater-var leform-repeater-var-$1'
                      data-id='$1'
                  >" . $value . "</span>
              ";

              $content = str_replace(
                $match,
                $replacement,
                $content
              );
            }

            $properties['content'] = " <div class='leform-element-html-container'> $content $htmlExternal</div>";
            break;

          case "columns":
            $options = "";
            $properties['elements'] = [];
            for ($j = 0; $j < $this->form_elements[$i]['_cols']; $j++) {
              $el = $this->get_form_elements($this->form_elements[$i]['id'], $j);
              $properties['elements'] = array_merge($properties['elements'], $el);
            }
            break;

          case "repeater-input":
            $properties['formValues'] = $this->form_values;
            $properties['expressions'] = (!empty($this->math_expressions)
              && is_array($this->math_expressions)
            ) ?  $this->math_expressions : [];

            $footerTotalsExpression = "";
            $element_values = isset($element['value']) ? $element['value'] : [];
            if ($this->form_elements[$i]["has-footer"] === "on") {
              $footerTotalsExpression = $this->form_elements[$i]["footer-tolals"];

              foreach ($this->math_expressions as $name => $expression) {
                $replacement = "";
                if ($expression["expressionType"] === "row") {
                  $replacement = "
                          <span
                              class='leform-repeater-var leform-repeater-var-" . $expression["id"] . "'
                              data-id='" . $expression["id"] . "'
                          ></span>
                      ";
                } else {
                  $replacement = "
                          <span
                              class='leform-repeater-var leform-repeater-var-" . $expression["id"] . "'
                              data-id='" . $expression["id"] . "'
                          >
                              " . $expression["value"] . "
                          </span>
                      ";
                }
                $footerTotalsExpression = str_replace(
                  $name,
                  $replacement,
                  $footerTotalsExpression
                );
              }

              $matches = [];
              preg_match_all(
                "/{{(\d+).+?}}/",
                $footerTotalsExpression,
                $matches
              );

              for ($matchIndex = 0; $matchIndex < count($matches[0]); $matchIndex++) {
                $match = $matches[0][$matchIndex];
                $id = $matches[1][$matchIndex];

                $value = "";
                if (
                  !is_null($this->form_values)
                  && isset($this->form_values[$id])
                ) {
                  $value = $this->form_values[$id];
                }

                $replacement = "
                    <span
                        class='leform-repeater-var leform-repeater-var-$1'
                        data-id='$1'
                    >" . $value . "</span>
                ";

                $footerTotalsExpression = str_replace(
                  $match,
                  $replacement,
                  $footerTotalsExpression
                );
              }
            }

            $properties['footerTotalsExpression'] = $footerTotalsExpression;
            $properties['row_expressions'] = [];
            if (isset($this->form_elements[$i]["expressions"]) && is_array($this->form_elements[$i]["expressions"]) && count($element_values) > 0) {
              $expressions = $this->form_elements[$i]["expressions"];
              foreach ($element_values as $ind => $row_value) {
                $properties['row_expressions'][$ind] = [];
                foreach ($expressions as $math_expression) {
                  $data = [];
                  $ids = [];
                  $el_values = [];
                  preg_match_all(
                    "/\[\[(\d+).+?(\]\])/",
                    $math_expression["expression"],
                    $matches
                  );
                  for ($j = 0; $j < sizeof($matches[0]); $j++) {
                    if (!empty($matches[0][$j]) && !empty($matches[1][$j])) {
                      $data[$matches[0][$j]] = '{' . $matches[1][$j] . '}';
                      $ids[] = $matches[1][$j];
                      $el_values[$matches[1][$j]] = !is_null($row_value) && isset($row_value[intval($matches[1][$j]) - 1]) ? $row_value[intval($matches[1][$j]) - 1] : 0;
                    }
                  }
                  $expression = strtr($math_expression["expression"], $data);
                  $formula = $expression;
                  foreach ($ids as $id) {
                    $formula = str_replace('{' . $id . '}', $el_values[$id], $formula);
                  }
                  $value = 0;
                  try {
                    $parser = new StdMathParser();
                    $AST = $parser->parse($formula);
                    $evaluator = new Evaluator();
                    $value = $AST->accept($evaluator);
                    if (empty($value)) {
                      $value = 0;
                    }
                  } catch (Exception $e) {
                    // skip for the moment
                  }
                  $key = "[[" . $math_expression['id'] . "|" . $math_expression['name'] . "]]";
                  $math_expression['value'] = $value;
                  // echo json_encode($key);
                  // exit;
                  $properties['row_expressions'][$ind][$key] = $math_expression;
                }
              }
            }
            break;

          case "iban-input":
            $options = "";
            break;

          default:
            break;
        }
      }
      $element['icon'] = $icon;
      $element['options'] = $options;
      $element['extra_class'] = $extra_class;
      $element['column_label_class'] = $column_label_class;
      $element['column_input_class'] = $column_input_class;
      $element['properties'] = $properties;
      $elements[] = $element;
    }

    return json_decode(json_encode($elements), FALSE);
  }

  public function get_form_css()
  {
    $style = '';

    if (
      array_key_exists("element-spacing", $this->form_options)
      && $this->form_options["element-spacing"] != ""
    ) {
      $element_spacing = intval(intval($this->form_options["element-spacing"]) / 2);
      if ($element_spacing > 0) {
        $style .= ".leform-form-" . $this->id . " .leform-element, .leform-progress-" . $this->id . " {padding:" . $element_spacing . "px;}";
      }
    }

    if ($this->form_options["progress-enable"] == "on") {
      if ($this->form_options["progress-type"] == 'progress-2') {
        if (array_key_exists("progress-color-color1", $this->form_options) && $this->form_options["progress-color-color1"] != "") $style .= ".leform-progress-" . $this->id . " ul.leform-progress-t2,.leform-progress-" . $this->id . " ul.leform-progress-t2>li>span{background-color:" . $this->form_options["progress-color-color1"] . ";}.leform-progress-" . $this->id . " ul.leform-progress-t2>li>label{color:" . $this->form_options["progress-color-color1"] . ";}";
        if (array_key_exists("progress-color-color2", $this->form_options) && $this->form_options["progress-color-color2"] != "") $style .= ".leform-progress-" . $this->id . " ul.leform-progress-t2>li.leform-progress-t2-active>span,.leform-progress-" . $this->id . " ul.leform-progress-t2>li.leform-progress-t2-passed>span{background-color:" . $this->form_options["progress-color-color2"] . ";}";
        if (array_key_exists("progress-color-color3", $this->form_options) && $this->form_options["progress-color-color3"] != "") $style .= ".leform-progress-" . $this->id . " ul.leform-progress-t2>li>span{color:" . $this->form_options["progress-color-color3"] . ";}";
        if (array_key_exists("progress-color-color4", $this->form_options) && $this->form_options["progress-color-color4"] != "") $style .= ".leform-progress-" . $this->id . " ul.leform-progress-t2>li.leform-progress-t2-active>label{color:" . $this->form_options["progress-color-color4"] . ";}";
      } else {
        if (array_key_exists("progress-color-color1", $this->form_options) && $this->form_options["progress-color-color1"] != "") $style .= ".leform-progress-" . $this->id . " div.leform-progress-t1>div{background-color:" . $this->form_options["progress-color-color1"] . ";}";
        if (array_key_exists("progress-color-color2", $this->form_options) && $this->form_options["progress-color-color2"] != "") $style .= ".leform-progress-" . $this->id . " div.leform-progress-t1>div>div{background-color:" . $this->form_options["progress-color-color2"] . ";}";
        if (array_key_exists("progress-color-color3", $this->form_options) && $this->form_options["progress-color-color3"] != "") $style .= ".leform-progress-" . $this->id . " div.leform-progress-t1>div>div{color:" . $this->form_options["progress-color-color3"] . ";}";
        if (array_key_exists("progress-color-color4", $this->form_options) && $this->form_options["progress-color-color4"] != "") $style .= ".leform-progress-" . $this->id . " div.leform-progress-t1>label{color:" . $this->form_options["progress-color-color4"] . ";}";
      }
      $style .= ".leform-progress-" . $this->id . "{max-width:" . $this->form_options["max-width-value"] . $this->form_options["max-width-unit"] . ";}";
    }

    $important = $this->leform->advancedOptions['important-enable'] == 'on' ? true : false;

    $webfonts = [];
    $text_style = $this->_build_style_text("text-style");
    if ($text_style["webfont"] != "" && !in_array($text_style["webfont"], $webfonts)) $webfonts[] = $text_style["webfont"];
    $style_attr = $text_style["style"];
    $style .= ".leform-form-" . $this->id . ", .leform-form-" . $this->id . " *, .leform-progress-" . $this->id . ", .leform-tooltipster-content-" . $this->id . " {" . $style_attr . "}";

    $style_attr = $this->_build_style_background("inline-background-style");
    $style_attr .= $this->_build_style_border("inline-border-style");
    $style_attr .= $this->_build_shadow("inline-shadow");
    $style_attr .= $this->_build_style_padding("inline-padding", $element_spacing);
    $style .= ".leform-inline .leform-form-" . $this->id . "{" . $style_attr . "}";

    $style_attr = $this->_build_style_background("popup-background-style");
    $style_attr .= $this->_build_style_border("popup-border-style");
    $style_attr .= $this->_build_shadow("popup-shadow");
    $style_attr .= $this->_build_style_padding("popup-padding", $element_spacing);
    $style .= "#leform-popup-" . $this->id . " .leform-form-" . $this->id . "{" . $style_attr . "}";
    $style .= "#leform-popup-" . $this->id . " .leform-popup-close {color:" . $this->form_options["popup-close-color-color1"] . "} #leform-popup-" . $this->id . " .leform-popup-close:hover {color:" . $this->form_options["popup-close-color-color2"] . "}";


    $text_style = $this->_build_style_text("label-text-style");
    if ($text_style["webfont"] != "" && !in_array($text_style["webfont"], $webfonts)) $webfonts[] = $text_style["webfont"];
    $style_attr = $text_style["style"];
    $style .= ".leform-form-" . $this->id . " .leform-element label.leform-label,.leform-form-" . $this->id . " .leform-element label.leform-label span.leform-required-symbol{" . $style_attr . "}";
    $text_style = $this->_build_style_text("description-text-style");
    if ($text_style["webfont"] != "" && !in_array($text_style["webfont"], $webfonts)) $webfonts[] = $text_style["webfont"];
    $style_attr = $text_style["style"];
    $style .= ".leform-form-" . $this->id . " .leform-element label.leform-description,.leform-form-" . $this->id . " .leform-element label.leform-description span.leform-required-symbol{" . $style_attr . "}";
    $text_style = $this->_build_style_text("required-text-style");
    if ($text_style["webfont"] != "" && !in_array($text_style["webfont"], $webfonts)) $webfonts[] = $text_style["webfont"];
    $style_attr = $text_style["style"];
    $style .= ".leform-form-" . $this->id . " .leform-element label.leform-label span.leform-required-symbol,.leform-form-" . $this->id . " .leform-element label.leform-description span.leform-required-symbol{" . $style_attr . "}";

    $text_style = $this->_build_style_text("input-text-style", $important);
    if ($text_style["webfont"] != "" && !in_array($text_style["webfont"], $webfonts)) $webfonts[] = $text_style["webfont"];
    $style_attr = $text_style["style"];
    $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input div.leform-signature-box span i{" . $style_attr . "}";
    $style_attr .= $this->_build_style_background("input-background-style", $important);
    $style_attr .= $this->_build_style_border("input-border-style", $important);
    $style_attr .= $this->_build_shadow("input-shadow", $important);
    $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input div.leform-signature-box,.leform-form-" . $this->id . " .leform-element div.leform-input div.leform-multiselect,.leform-form-" . $this->id . " .leform-element div.leform-input input[type='text'],.leform-form-" . $this->id . " .leform-element div.leform-input input[type='email'],.leform-form-" . $this->id . " .leform-element div.leform-input input[type='password'],.leform-form-" . $this->id . " .leform-element div.leform-input select,.leform-form-" . $this->id . " .leform-element div.leform-input select option,.leform-form-" . $this->id . " .leform-element div.leform-input textarea{" . $style_attr . "}";
    if (array_key_exists("input-text-style-color", $this->form_options) && $this->form_options["input-text-style-color"] != "") $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input ::placeholder{color:" . $this->form_options["input-text-style-color"] . "; opacity: 0.9;} .leform-form-" . $this->id . " .leform-element div.leform-input ::-ms-input-placeholder{color:" . $this->form_options["input-text-style-color"] . "; opacity: 0.9;}";
    $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input div.leform-multiselect::-webkit-scrollbar-thumb{background-color:" . $this->form_options["input-border-style-color"] . ";}";
    if ($this->form_options["input-hover-inherit"] == "off") {
      $text_style = $this->_build_style_text("input-hover-text-style", $important);
      if ($text_style["webfont"] != "" && !in_array($text_style["webfont"], $webfonts)) $webfonts[] = $text_style["webfont"];
      $style_attr = $text_style["style"];
      $style_attr .= $this->_build_style_background("input-hover-background-style", $important);
      $style_attr .= $this->_build_style_border("input-hover-border-style", $important);
      $style_attr .= $this->_build_shadow("input-hover-shadow", $important);
      $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input input[type='text']:hover,.leform-form-" . $this->id . " .leform-element div.leform-input input[type='email']:hover,.leform-form-" . $this->id . " .leform-element div.leform-input input[type='password']:hover,.leform-form-" . $this->id . " .leform-element div.leform-input select:hover,.leform-form-" . $this->id . " .leform-element div.leform-input select:hover option,.leform-form-" . $this->id . " .leform-element div.leform-input textarea:hover{" . $style_attr . "}";
    }
    if ($this->form_options["input-focus-inherit"] == "off") {
      $text_style = $this->_build_style_text("input-focus-text-style", $important);
      if ($text_style["webfont"] != "" && !in_array($text_style["webfont"], $webfonts)) $webfonts[] = $text_style["webfont"];
      $style_attr = $text_style["style"];
      $style_attr .= $this->_build_style_background("input-focus-background-style", $important);
      $style_attr .= $this->_build_style_border("input-focus-border-style", $important);
      $style_attr .= $this->_build_shadow("input-focus-shadow", $important);
      $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input input[type='text']:focus,.leform-form-" . $this->id . " .leform-element div.leform-input input[type='email']:focus,.leform-form-" . $this->id . " .leform-element div.leform-input input[type='password']:focus,.leform-form-" . $this->id . " .leform-element div.leform-input select:focus,.leform-form-" . $this->id . " .leform-element div.leform-input select:focus option,.leform-form-" . $this->id . " .leform-element div.leform-input textarea:focus{" . $style_attr . "}";
    }
    $style_attr = "";
    if ($this->form_options["input-icon-size"] != "") {
      $style_attr .= "font-size:" . $this->form_options["input-icon-size"] . "px;";
    }
    if ($this->form_options["input-icon-color"] != "") {
      $style_attr .= "color:" . $this->form_options["input-icon-color"] . ";";
    }
    if ($this->form_options['input-icon-position'] != 'outside') {
      if ($this->form_options["input-icon-background"] != "") {
        $style_attr .= "background:" . $this->form_options["input-icon-background"] . ";";
      }
      if ($this->form_options["input-icon-border"] != "") {
        $style_attr .= "border-color:" . $this->form_options["input-icon-border"] . ";border-style:solid;";
        if (array_key_exists("input-border-style-width", $this->form_options)) {
          $size = intval($this->form_options["input-border-style-width"]);
          if ($size >= 0 && $size <= 16) $style_attr .= "border-width:" . $size . "px;";
        }
      }
      if (array_key_exists("input-border-style-radius", $this->form_options)) {
        $size = intval($this->form_options["input-border-style-radius"]);
        if ($size >= 0 && $size <= 100) $style_attr .= "border-radius:" . $size . "px;";
      }
      if ($this->form_options["input-icon-background"] != "" || $this->form_options["input-icon-border"] != "") {
        $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input.leform-icon-left input[type='text'],.leform-form-" . $this->id . " .leform-element div.leform-input.leform-icon-left input[type='email'],.leform-form-" . $this->id . " .leform-element div.leform-input.leform-icon-left input[type='password'],.leform-form-" . $this->id . " .leform-element div.leform-input.leform-icon-left textarea {padding-left: 56px !important;}";
        $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input.leform-icon-right input[type='text'],.leform-form-" . $this->id . " .leform-element div.leform-input.leform-icon-right input[type='email'],.leform-form-" . $this->id . " .leform-element div.leform-input.leform-icon-right input[type='password'],.leform-form-" . $this->id . " .leform-element div.leform-input.leform-icon-right textarea {padding-right: 56px !important;}";
      }
    }
    if (!empty($style_attr)) {
      $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input>i.leform-icon-left, .leform-form-" . $this->id . " .leform-element div.leform-input>i.leform-icon-right{" . $style_attr . "}";
    }
    $text_style = $this->_build_style_text("button-text-style");
    if ($text_style["webfont"] != "" && !in_array($text_style["webfont"], $webfonts)) $webfonts[] = $text_style["webfont"];
    $style_attr = $text_style["style"];
    $style_attr .= $this->_build_style_background("button-background-style");
    $style_attr .= $this->_build_style_border("button-border-style");
    $style_attr .= $this->_build_shadow("button-shadow");
    $style .= ".leform-form-" . $this->id . " .leform-element .leform-button,.leform-form-" . $this->id . " .leform-element .leform-button:visited{" . $style_attr . "}";
    if ($this->form_options["button-hover-inherit"] == "off") {
      $text_style = $this->_build_style_text("button-hover-text-style");
      if ($text_style["webfont"] != "" && !in_array($text_style["webfont"], $webfonts)) $webfonts[] = $text_style["webfont"];
      $style_attr = $text_style["style"];
      $style_attr .= $this->_build_style_background("button-hover-background-style");
      $style_attr .= $this->_build_style_border("button-hover-border-style");
      $style_attr .= $this->_build_shadow("button-hover-shadow");
      $style .= ".leform-form-" . $this->id . " .leform-element .leform-button:hover,.leform-form-" . $this->id . " .leform-element .leform-button:focus{" . $style_attr . "}";
    }
    if ($this->form_options["button-active-inherit"] == "off") {
      $text_style = $this->_build_style_text("button-active-text-style");
      if ($text_style["webfont"] != "" && !in_array($text_style["webfont"], $webfonts)) $webfonts[] = $text_style["webfont"];
      $style_attr = $text_style["style"];
      $style_attr .= $this->_build_style_background("button-active-background-style");
      $style_attr .= $this->_build_style_border("button-active-border-style");
      $style_attr .= $this->_build_shadow("button-active-shadow");
      $style .= ".leform-form-" . $this->id . " .leform-element .leform-button:active{" . $style_attr . "}";
    }

    $style_attr = $this->_build_style_border("imageselect-border-style");
    $style_attr .= $this->_build_shadow("imageselect-shadow");
    $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input .leform-imageselect+label{" . $style_attr . "}";
    if ($this->form_options["imageselect-hover-inherit"] == "off") {
      $style_attr = $this->_build_style_border("imageselect-hover-border-style");
      $style_attr .= $this->_build_shadow("imageselect-hover-shadow");
      $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input .leform-imageselect+label:hover{" . $style_attr . "}";
    }
    if ($this->form_options["imageselect-selected-inherit"] == "off") {
      $style_attr = $this->_build_style_border("imageselect-selected-border-style");
      $style_attr .= $this->_build_shadow("imageselect-selected-shadow");
      $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input .leform-imageselect:checked+label{" . $style_attr . "}";
    }
    $text_style = $this->_build_style_text("imageselect-text-style");
    if ($text_style["webfont"] != "" && !in_array($text_style["webfont"], $webfonts)) $webfonts[] = $text_style["webfont"];
    $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input .leform-imageselect+label span.leform-imageselect-label{" . $text_style["style"] . "}";

    $style_attr = "";
    if (array_key_exists("checkbox-radio-unchecked-color-color2", $this->form_options) && $this->form_options["checkbox-radio-unchecked-color-color2"] != "") $style_attr .= "background-color:" . $this->form_options["checkbox-radio-unchecked-color-color2"] . ";";
    else $style_attr .= "background-color:transparent;";
    $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input input[type='checkbox'].leform-checkbox-tgl:checked+label:after{" . $style_attr . "}";
    if (array_key_exists("checkbox-radio-unchecked-color-color1", $this->form_options) && $this->form_options["checkbox-radio-unchecked-color-color1"] != "") $style_attr .= "border-color:" . $this->form_options["checkbox-radio-unchecked-color-color1"] . ";";
    else $style_attr .= "border-color:transparent;";
    if (array_key_exists("checkbox-radio-unchecked-color-color3", $this->form_options) && $this->form_options["checkbox-radio-unchecked-color-color3"] != "") $style_attr .= "color:" . $this->form_options["checkbox-radio-unchecked-color-color3"] . ";";
    else $style_attr .= "color:#ccc;";
    $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input input[type='checkbox'].leform-checkbox-classic+label,.leform-form-" . $this->id . " .leform-element div.leform-input input[type='checkbox'].leform-checkbox-fa-check+label,.leform-form-" . $this->id . " .leform-element div.leform-input input[type='checkbox'].leform-checkbox-square+label,.leform-form-" . $this->id . " .leform-element div.leform-input input[type='checkbox'].leform-checkbox-tgl+label{" . $style_attr . "}";
    $style_attr = "";
    if (array_key_exists("checkbox-radio-unchecked-color-color3", $this->form_options) && $this->form_options["checkbox-radio-unchecked-color-color3"] != "") $style_attr .= "background-color:" . $this->form_options["checkbox-radio-unchecked-color-color3"] . ";";
    else $style_attr .= "color:#ccc;";
    $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input input[type='checkbox'].leform-checkbox-square:checked+label:after{" . $style_attr . "}";
    $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input input[type='checkbox'].leform-checkbox-tgl:checked+label,.leform-form-" . $this->id . " .leform-element div.leform-input input[type='checkbox'].leform-checkbox-tgl+label:after{" . $style_attr . "}";
    if ($this->form_options["checkbox-radio-checked-inherit"] == "off") {
      $style_attr = "";
      if (array_key_exists("checkbox-radio-checked-color-color2", $this->form_options) && $this->form_options["checkbox-radio-checked-color-color2"] != "") $style_attr .= "background-color:" . $this->form_options["checkbox-radio-checked-color-color2"] . ";";
      else $style_attr .= "background-color:transparent;";
      if (array_key_exists("checkbox-radio-checked-color-color1", $this->form_options) && $this->form_options["checkbox-radio-checked-color-color1"] != "") $style_attr .= "border-color:" . $this->form_options["checkbox-radio-checked-color-color1"] . ";";
      else $style_attr .= "border-color:transparent;";
      if (array_key_exists("checkbox-radio-checked-color-color3", $this->form_options) && $this->form_options["checkbox-radio-checked-color-color3"] != "") $style_attr .= "color:" . $this->form_options["checkbox-radio-checked-color-color3"] . ";";
      else $style_attr .= "color:#ccc;";
      $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input input[type='checkbox'].leform-checkbox-classic:checked+label,.leform-form-" . $this->id . " .leform-element div.leform-input input[type='checkbox'].leform-checkbox-fa-check:checked+label,.leform-form-" . $this->id . " .leform-element div.leform-input input[type='checkbox'].leform-checkbox-square:checked+label, .leform-form-" . $this->id . " .leform-element div.leform-input input[type='checkbox'].leform-checkbox-tgl:checked+label{" . $style_attr . "}";
      $style_attr = "";
      if (array_key_exists("checkbox-radio-checked-color-color3", $this->form_options) && $this->form_options["checkbox-radio-checked-color-color3"] != "") $style_attr .= "background-color:" . $this->form_options["checkbox-radio-checked-color-color3"] . ";";
      else $style_attr .= "background-color:#ccc;";
      $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input input[type='checkbox'].leform-checkbox-square:checked+label:after{" . $style_attr . "}";
      $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input input[type='checkbox'].leform-checkbox-tgl:checked+label:after{" . $style_attr . "}";
    }

    $style_attr = "";
    if (array_key_exists("checkbox-radio-unchecked-color-color2", $this->form_options) && $this->form_options["checkbox-radio-unchecked-color-color2"] != "") $style_attr .= "background-color:" . $this->form_options["checkbox-radio-unchecked-color-color2"] . ";";
    else $style_attr .= "background-color:transparent;";
    if (array_key_exists("checkbox-radio-unchecked-color-color1", $this->form_options) && $this->form_options["checkbox-radio-unchecked-color-color1"] != "") $style_attr .= "border-color:" . $this->form_options["checkbox-radio-unchecked-color-color1"] . ";";
    else $style_attr .= "border-color:transparent;";
    if (array_key_exists("checkbox-radio-unchecked-color-color3", $this->form_options) && $this->form_options["checkbox-radio-unchecked-color-color3"] != "") $style_attr .= "color:" . $this->form_options["checkbox-radio-unchecked-color-color3"] . ";";
    else $style_attr .= "color:#ccc;";
    $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input input[type='radio'].leform-radio-classic+label,.leform-form-" . $this->id . " .leform-element div.leform-input input[type='radio'].leform-radio-fa-check+label,.leform-form-" . $this->id . " .leform-element div.leform-input input[type='radio'].leform-radio-dot+label{" . $style_attr . "}";
    $style_attr = "";
    if (array_key_exists("checkbox-radio-unchecked-color-color3", $this->form_options) && $this->form_options["checkbox-radio-unchecked-color-color3"] != "") $style_attr .= "background-color:" . $this->form_options["checkbox-radio-unchecked-color-color3"] . ";";
    else $style_attr .= "background-color:#ccc;";
    $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input input[type='radio'].leform-radio-dot:checked+label:after{" . $style_attr . "}";
    if ($this->form_options["checkbox-radio-checked-inherit"] == "off") {
      $style_attr = "";
      if (array_key_exists("checkbox-radio-checked-color-color2", $this->form_options) && $this->form_options["checkbox-radio-checked-color-color2"] != "") $style_attr .= "background-color:" . $this->form_options["checkbox-radio-checked-color-color2"] . ";";
      else $style_attr .= "background-color:transparent;";
      if (array_key_exists("checkbox-radio-checked-color-color1", $this->form_options) && $this->form_options["checkbox-radio-checked-color-color1"] != "") $style_attr .= "border-color:" . $this->form_options["checkbox-radio-checked-color-color1"] . ";";
      else $style_attr .= "border-color:transparent;";
      if (array_key_exists("checkbox-radio-checked-color-color3", $this->form_options) && $this->form_options["checkbox-radio-checked-color-color3"] != "") $style_attr .= "color:" . $this->form_options["checkbox-radio-checked-color-color3"] . ";";
      else $style_attr .= "color:#ccc;";
      $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input input[type='radio'].leform-radio-classic:checked+label,.leform-form-" . $this->id . " .leform-element div.leform-input input[type='radio'].leform-radio-fa-check:checked+label,.leform-form-" . $this->id . " .leform-element div.leform-input input[type='radio'].leform-radio-dot:checked+label{" . $style_attr . "}";
      $style_attr = "";
      if (array_key_exists("checkbox-radio-checked-color-color3", $this->form_options) && $this->form_options["checkbox-radio-checked-color-color3"] != "") $style_attr .= "background-color:" . $this->form_options["checkbox-radio-checked-color-color3"] . ";";
      else $style_attr .= "background-color:#ccc;";
      $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input input[type='radio'].leform-radio-dot:checked+label:after{" . $style_attr . "}";
    }

    $style_attr = "";
    if (array_key_exists("multiselect-style-hover-background", $this->form_options) && $this->form_options["multiselect-style-hover-background"] != "") $style_attr .= "background-color:" . $this->form_options['multiselect-style-hover-background'] . ";";
    if (array_key_exists("multiselect-style-hover-color", $this->form_options) && $this->form_options["multiselect-style-hover-color"] != "") $style_attr .= "color:" . $this->form_options['multiselect-style-hover-color'] . ";";
    if (!empty($style_attr)) $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input div.leform-multiselect>input[type='checkbox']+label:hover{" . $style_attr . "}";
    $style_attr = "";
    if (array_key_exists("multiselect-style-selected-background", $this->form_options) && $this->form_options["multiselect-style-selected-background"] != "") $style_attr .= "background-color:" . $this->form_options['multiselect-style-selected-background'] . ";";
    if (array_key_exists("multiselect-style-selected-color", $this->form_options) && $this->form_options["multiselect-style-selected-color"] != "") $style_attr .= "color:" . $this->form_options['multiselect-style-selected-color'] . ";";
    if (!empty($style_attr)) $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input div.leform-multiselect>input[type='checkbox']:checked+label{" . $style_attr . "}";
    if (array_key_exists("multiselect-style-height", $this->form_options) && $this->form_options["multiselect-style-height"] != "") $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input div.leform-multiselect{height:" . intval($this->form_options['multiselect-style-height']) . "px;}";

    if ($this->leform->options['range-slider-enable'] == 'on') {
      if (array_key_exists("rangeslider-color-color1", $this->form_options) && $this->form_options["rangeslider-color-color1"] != "") $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input.leform-rangeslider .irs-line,.leform-form-" . $this->id . " .leform-element div.leform-input.leform-rangeslider .irs-min,.leform-form-" . $this->id . " .leform-element div.leform-input.leform-rangeslider .irs-max,.leform-form-" . $this->id . " .leform-element div.leform-input.leform-rangeslider .irs-grid-pol{background-color:" . $this->form_options['rangeslider-color-color1'] . " !important;}";
      if (array_key_exists("rangeslider-color-color2", $this->form_options) && $this->form_options["rangeslider-color-color2"] != "") $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input.leform-rangeslider .irs-grid-text,.leform-form-" . $this->id . " .leform-element div.leform-input.leform-rangeslider .irs-min,.leform-form-" . $this->id . " .leform-element div.leform-input.leform-rangeslider .irs-max{color:" . $this->form_options["rangeslider-color-color2"] . " !important;}";
      if (array_key_exists("rangeslider-color-color3", $this->form_options) && $this->form_options["rangeslider-color-color3"] != "") $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input.leform-rangeslider .irs-bar{background-color:" . $this->form_options["rangeslider-color-color3"] . " !important;}";
      if (array_key_exists("rangeslider-color-color4", $this->form_options) && $this->form_options["rangeslider-color-color4"] != "") {
        $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input.leform-rangeslider .irs-single,.leform-form-" . $this->id . " .leform-element div.leform-input.leform-rangeslider .irs-from,.leform-form-" . $this->id . " .leform-element div.leform-input.leform-rangeslider .irs-to{background-color:" . $this->form_options["rangeslider-color-color4"] . " !important;}";
        //$style .= ".leform-form-".$this->id." .leform-element div.leform-input.leform-rangeslider .irs-single:before,.leform-form-".$this->id." .leform-element div.leform-input.leform-rangeslider .irs-from:before,.leform-form-".$this->id." .leform-element div.leform-input.leform-rangeslider .irs-to:before{border-top-color:".$this->form_options["rangeslider-color-color4"]." !important;}";
        switch ($this->form_options["rangeslider-skin"]) {
          case 'sharp':
            $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input.leform-rangeslider .irs--sharp .irs-handle,.leform-form-" . $this->id . " .leform-element div.leform-input.leform-rangeslider .irs--sharp .irs-handle:hover,.leform-form-" . $this->id . " .leform-element div.leform-input.leform-rangeslider .irs--sharp .irs-handle.state_hover{background-color:" . $this->form_options["rangeslider-color-color4"] . " !important;}";
            $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input.leform-rangeslider .irs--sharp .irs-handle > i:first-child,.leform-form-" . $this->id . " .leform-element div.leform-input.leform-rangeslider .irs--sharp .irs-handle:hover > i:first-child,.leform-form-" . $this->id . " .leform-element div.leform-input.leform-rangeslider .irs--sharp .irs-handle.state_hover > i:first-child{border-top-color:" . $this->form_options["rangeslider-color-color4"] . " !important;}";
            break;
          case 'round':
            $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input.leform-rangeslider .irs--round .irs-handle,.leform-form-" . $this->id . " .leform-element div.leform-input.leform-rangeslider .irs--round .irs-handle:hover,.leform-form-" . $this->id . " .leform-element div.leform-input.leform-rangeslider .irs--round .irs-handle.state_hover{border-color:" . $this->form_options["rangeslider-color-color4"] . " !important;}";
            break;
          default:
            $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input.leform-rangeslider .irs--flat .irs-handle > i:first-child,.leform-form-" . $this->id . " .leform-element div.leform-input.leform-rangeslider .irs--flat .irs-handle:hover > i:first-child,.leform-form-" . $this->id . " .leform-element div.leform-input.leform-rangeslider .irs--flat .irs-handle.state_hover > i:first-child{background-color:" . $this->form_options["rangeslider-color-color4"] . " !important;}";
            break;
        }
      }
      if (array_key_exists("rangeslider-color-color5", $this->form_options) && $this->form_options["rangeslider-color-color5"] != "") {
        $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input.leform-rangeslider .irs-single,.leform-form-" . $this->id . " .leform-element div.leform-input.leform-rangeslider .irs-from,.leform-form-" . $this->id . " .leform-element div.leform-input.leform-rangeslider .irs-to{color:" . $this->form_options["rangeslider-color-color5"] . " !important;}";
        if ($this->form_options["rangeslider-skin"] == "round") {
          $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input.leform-rangeslider .irs--round .irs-handle,.leform-form-" . $this->id . " .leform-element div.leform-input.leform-rangeslider .irs--round .irs-handle:hover,.leform-form-" . $this->id . " .leform-element div.leform-input.leform-rangeslider .irs--round .irs-handle.state_hover{background-color:" . $this->form_options["rangeslider-color-color5"] . " !important;}";
        }
      }
    }

    $text_style = $this->_build_style_text("tile-text-style");
    if ($text_style["webfont"] != "" && !in_array($text_style["webfont"], $webfonts)) $webfonts[] = $text_style["webfont"];
    $style_attr = $text_style["style"];
    $style_attr .= $this->_build_style_background("tile-background-style");
    $style_attr .= $this->_build_style_border("tile-border-style");
    $style_attr .= $this->_build_shadow("tile-shadow");
    $style .= ".leform-form-" . $this->id . " .leform-element input[type='checkbox'].leform-tile+label, .leform-form-" . $this->id . " .leform-element input[type='radio'].leform-tile+label {" . $style_attr . "}";
    if ($this->form_options["tile-hover-inherit"] == "off") {
      $text_style = $this->_build_style_text("tile-hover-text-style");
      if ($text_style["webfont"] != "" && !in_array($text_style["webfont"], $webfonts)) $webfonts[] = $text_style["webfont"];
      $style_attr = $text_style["style"];
      $style_attr .= $this->_build_style_background("tile-hover-background-style");
      $style_attr .= $this->_build_style_border("tile-hover-border-style");
      $style_attr .= $this->_build_shadow("tile-hover-shadow");
      $style .= ".leform-form-" . $this->id . " .leform-element input[type='checkbox'].leform-tile+label:hover, .leform-form-" . $this->id . " .leform-element input[type='radio'].leform-tile+label:hover{" . $style_attr . "}";
    }
    if ($this->form_options["tile-selected-inherit"] == "off") {
      $text_style = $this->_build_style_text("tile-selected-text-style");
      if ($text_style["webfont"] != "" && !in_array($text_style["webfont"], $webfonts)) $webfonts[] = $text_style["webfont"];
      $style_attr = $text_style["style"];
      $style_attr .= $this->_build_style_background("tile-selected-background-style");
      $style_attr .= $this->_build_style_border("tile-selected-border-style");
      $style_attr .= $this->_build_shadow("tile-selected-shadow");
      $style .= ".leform-form-" . $this->id . " .leform-element input[type='checkbox'].leform-tile:checked+label, .leform-form-" . $this->id . " .leform-element input[type='radio'].leform-tile:checked+label{" . $style_attr . "}";
    }

    $text_style = $this->_build_style_text("error-text-style");
    if ($text_style["webfont"] != "" && !in_array($text_style["webfont"], $webfonts)) $webfonts[] = $text_style["webfont"];
    $style_attr = $text_style["style"];
    $style_attr .= $this->_build_style_background("error-background-style");
    $style .= ".leform-form-" . $this->id . " .leform-element .leform-input .leform-element-error,.leform-uploader-error{" . $style_attr . "}";

    for ($i = 0; $i < sizeof($this->form_pages); $i++) {
      if (!empty($this->form_pages[$i]) && is_array($this->form_pages[$i])) {
        $chStyle = $this->_build_children_css($this->form_pages[$i]['id'], 0);
        $style .= $chStyle;
      }
    }

    $style .= $this->form_options["custom-css"];

    return $style;
  }

  public function _build_children_css($_parent, $_parent_col)
  {

    $style = '';
    $uids = [];
    $properties = [];

    $idxs = [];
    $seqs = [];
    for ($i = 0; $i < sizeof($this->form_elements); $i++) {
      if (empty($this->form_elements[$i])) continue;
      if ($this->form_elements[$i]["_parent"] == $_parent && ($this->form_elements[$i]["_parent-col"] == $_parent_col || $_parent == "")) {
        $idxs[] = $i;
        $seqs[] = intval($this->form_elements[$i]["_seq"]);
      }
    }
    if (empty($idxs)) {
      return $style;
    }

    for ($i = 0; $i < sizeof($seqs); $i++) {
      $sorted = -1;
      for ($j = 0; $j < sizeof($seqs) - 1; $j++) {
        if ($seqs[$j] > $seqs[$j + 1]) {
          $sorted = $seqs[$j];
          $seqs[$j] = $seqs[$j + 1];
          $seqs[$j + 1] = $sorted;
          $sorted = $idxs[$j];
          $idxs[$j] = $idxs[$j + 1];
          $idxs[$j + 1] = $sorted;
        }
      }
      if ($sorted == -1) {
        break;
      }
    }

    for ($k = 0; $k < sizeof($idxs); $k++) {
      $i = $idxs[$k];
      $icon = "";
      $options = "";
      $extra_class = "";
      $column_label_class = "";
      $column_input_class = "";
      $properties = [];

      if (empty($this->form_elements[$i])) {
        continue;
      }
      if (array_key_exists('label-style-position', $this->form_elements[$i])) {
        $properties["label-style-position"] = $this->form_elements[$i]["label-style-position"];
        if ($properties["label-style-position"] == "") $properties["label-style-position"] = $this->form_options["label-style-position"];
        if ($properties["label-style-position"] == "") $properties["label-style-position"] = "top";
        if ($this->form_elements[$i]["label-style-position"] == "left" || $this->form_elements[$i]["label-style-position"] == "right") $properties["label-style-width"] = $this->form_elements[$i]["label-style-width"];
        else $properties["label-style-width"] = "";
        if ($properties["label-style-width"] == "") $properties["label-style-width"] = $this->form_options["label-style-width"];
        $properties["label-style-width"] = intval($properties["label-style-width"]);
        if ($properties["label-style-width"] < 1 || $properties["label-style-width"] > 11) $properties["label-style-width"] = 3;
        if ($properties["label-style-position"] == "left" || $properties["label-style-position"] == "right") {
          $column_label_class = " leform-col-" . $properties["label-style-width"];
          $column_input_class = " leform-col-" . (12 - $properties["label-style-width"]);
        }
      }
      if (array_key_exists('icon-left-icon', $this->form_elements[$i])) {
        if ($this->form_elements[$i]["icon-left-icon"] != "") {
          if (
            array_key_exists("input-icon-display", $this->form_options)
            && $this->form_options["input-icon-display"] === "show"
          ) {
            $extra_class .= " leform-icon-left";
            $icon .= "<i class='leform-icon-left " . $this->form_elements[$i]["icon-left-icon"] . "'></i>";
            $options = "";
            if ($this->form_elements[$i]["icon-left-size"] != "") {
              $options .= "font-size:" . $this->form_elements[$i]["icon-left-size"] . "px;";
            }
            if (!empty($options)) {
              $style .= ".leform-form-" . $this->id . " .leform-element-" . $this->form_elements[$i]['id'] . " div.leform-input>i.leform-icon-left{" . $options . "}";
            }
          }
        }
      }
      if (array_key_exists('icon-right-icon', $this->form_elements[$i])) {
        if ($this->form_elements[$i]["icon-right-icon"] != "") {
          if (
            array_key_exists("input-icon-display", $this->form_options)
            && $this->form_options["input-icon-display"] === "show"
          ) {
            $extra_class .= " leform-icon-right";
            $icon .= "<i class='leform-icon-right " . $this->form_elements[$i]["icon-right-icon"] . "'></i>";
            $options = "";
            if ($this->form_elements[$i]["icon-right-size"] != "") {
              $options .= "font-size:" . $this->form_elements[$i]["icon-right-size"] . "px;";
            }
            if (!empty($options)) {
              $style .= ".leform-form-" . $this->id . " .leform-element-" . $this->form_elements[$i]['id'] . " div.leform-input>i.leform-icon-right{" . $options . "}";
            }
          }
        }
      }

      if (
        array_key_exists("css", $this->form_elements[$i])
        && sizeof($this->form_elements[$i]["css"]) > 0
      ) {
        $elementPropertiesMeta = $this->leform->getElementPropertiesMeta();
        if (
          array_key_exists(
            $this->form_elements[$i]["type"],
            $elementPropertiesMeta
          )
          && array_key_exists(
            "css",
            $elementPropertiesMeta[$this->form_elements[$i]["type"]]
          )
        ) {
          for ($j = 0; $j < sizeof($this->form_elements[$i]["css"]); $j++) {
            if (
              !empty($this->form_elements[$i]["css"][$j]["css"])
              && !empty($this->form_elements[$i]["css"][$j]["selector"])
            ) {
              if (
                array_key_exists(
                  $this->form_elements[$i]["css"][$j]["selector"],
                  $elementPropertiesMeta[$this->form_elements[$i]["type"]]["css"]["selectors"]
                )
              ) {
                $properties["css-class"] = $elementPropertiesMeta[$this->form_elements[$i]["type"]]["css"]["selectors"][$this->form_elements[$i]["css"][$j]["selector"]]["front-class"];
                $properties["css-class"] = str_replace(
                  ["{element-id}", "{form-id}"],
                  [$this->form_elements[$i]['id'], $this->id],
                  $properties["css-class"]
                );
                $style .= $properties["css-class"] . "{" . $this->form_elements[$i]["css"][$j]["css"] . "}";
              }
            }
          }
        }
      }
      $properties["tooltip-label"] = "";
      $properties["tooltip-description"] = "";
      $properties["tooltip-input"] = "";

      $properties["required-label-left"] = "";
      $properties["required-label-right"] = "";
      $properties["required-description-left"] = "";
      $properties["required-description-right"] = "";
      if (array_key_exists("required", $this->form_elements[$i]) && trim($this->form_elements[$i]["required"]) == "on") {
        if (array_key_exists("required-position", $this->form_options) && $this->form_options["required-position"] != "" && $this->form_options["required-position"] != "none" && array_key_exists("required-text", $this->form_options) && $this->form_options["required-text"] != "") {
          switch ($this->form_options["required-position"]) {
            case 'label-left':
            case 'label-right':
            case 'description-left':
            case 'description-right':
              $properties["required-" . $this->form_options["required-position"]] = "<span class='leform-required-symbol leform-required-symbol-" . $this->form_options["required-position"] . "'>" . $this->form_options["required-text"] . "</span>";
              break;
            default:
              break;
          }
        }
      }

      if (array_key_exists($this->form_elements[$i]["type"], $this->leform->toolbarTools)) {
        switch ($this->form_elements[$i]["type"]) {
          case "button":
          case "link-button":
            $icon = "";
            if (array_key_exists("button-style-size", $this->form_elements[$i]) && $this->form_elements[$i]['button-style-size'] != "") $properties['size'] = $this->form_elements[$i]['button-style-size'];
            else $properties['size'] = $this->form_options['button-style-size'];
            if (array_key_exists("button-style-width", $this->form_elements[$i]) && $this->form_elements[$i]['button-style-width'] != "") $properties['width'] = $this->form_elements[$i]['button-style-width'];
            else $properties['width'] = $this->form_options['button-style-width'];
            if (
              array_key_exists("button-style-position", $this->form_elements[$i])
              && $this->form_elements[$i]['button-style-position'] != ""
            ) {
              $properties['position'] = $this->form_elements[$i]['button-style-position'];
            } else {
              $properties['position'] = $this->form_options['button-style-position'];
            }
            $properties['style-attr'] = "";
            if (array_key_exists("colors-background", $this->form_elements[$i]) && $this->form_elements[$i]["colors-background"] != "") $properties['style-attr'] .= "background-color:" . $this->form_elements[$i]["colors-background"] . ";";
            if (array_key_exists("colors-border", $this->form_elements[$i]) && $this->form_elements[$i]["colors-border"] != "") $properties['style-attr'] .= "border-color:" . $this->form_elements[$i]["colors-border"] . ";";
            if (array_key_exists("colors-text", $this->form_elements[$i]) && $this->form_elements[$i]["colors-text"] != "") $properties['style-attr'] .= "color:" . $this->form_elements[$i]["colors-text"] . ";";
            if ($properties['style-attr'] != "") $style .= ".leform-form-" . $this->id . " .leform-element-" . $this->form_elements[$i]['id'] . " .leform-button, .leform-form-" . $this->id . " .leform-element-" . $this->form_elements[$i]['id'] . " .leform-button:visited{" . $properties['style-attr'] . "}";

            $properties['style-attr'] = "";
            if (array_key_exists("colors-hover-background", $this->form_elements[$i]) && $this->form_elements[$i]["colors-hover-background"] != "") $properties['style-attr'] .= "background-color:" . $this->form_elements[$i]["colors-hover-background"] . ";";
            if (array_key_exists("colors-hover-border", $this->form_elements[$i]) && $this->form_elements[$i]["colors-hover-border"] != "") $properties['style-attr'] .= "border-color:" . $this->form_elements[$i]["colors-hover-border"] . ";";
            if (array_key_exists("colors-hover-text", $this->form_elements[$i]) && $this->form_elements[$i]["colors-hover-text"] != "") $properties['style-attr'] .= "color:" . $this->form_elements[$i]["colors-hover-text"] . ";";
            if ($properties['style-attr'] != "") $style .= ".leform-form-" . $this->id . " .leform-element-" . $this->form_elements[$i]['id'] . " .leform-button:hover, .leform-form-" . $this->id . " .leform-element-" . $this->form_elements[$i]['id'] . " .leform-button:focus{" . $properties['style-attr'] . "}";

            $properties['style-attr'] = "";
            if (array_key_exists("colors-active-background", $this->form_elements[$i]) && $this->form_elements[$i]["colors-active-background"] != "") $properties['style-attr'] .= "background-color:" . $this->form_elements[$i]["colors-active-background"] . ";";
            if (array_key_exists("colors-active-border", $this->form_elements[$i]) && $this->form_elements[$i]["colors-active-border"] != "") $properties['style-attr'] .= "border-color:" . $this->form_elements[$i]["colors-active-border"] . ";";
            if (array_key_exists("colors-active-text", $this->form_elements[$i]) && $this->form_elements[$i]["colors-active-text"] != "") $properties['style-attr'] .= "color:" . $this->form_elements[$i]["colors-active-text"] . ";";
            if ($properties['style-attr'] != "") $style .= ".leform-form-" . $this->id . " .leform-element-" . $this->form_elements[$i]['id'] . " .leform-button:active{" . $properties['style-attr'] . "}";

            break;

          case "file":
            if (array_key_exists("button-style-size", $this->form_elements[$i]) && $this->form_elements[$i]['button-style-size'] != "") $properties['size'] = $this->form_elements[$i]['button-style-size'];
            else $properties['size'] = $this->form_options['button-style-size'];
            if (array_key_exists("button-style-width", $this->form_elements[$i]) && $this->form_elements[$i]['button-style-width'] != "") $properties['width'] = $this->form_elements[$i]['button-style-width'];
            else $properties['width'] = $this->form_options['button-style-width'];
            if (array_key_exists("button-style-position", $this->form_elements[$i]) && $this->form_elements[$i]['button-style-position'] != "") $properties['position'] = $this->form_elements[$i]['button-style-position'];
            else $properties['position'] = $this->form_options['button-style-position'];
            $label = '<span>' . $this->form_elements[$i]["button-label"] . '</span>';
            if (array_key_exists("icon-left", $this->form_elements[$i]) && $this->form_elements[$i]["icon-left"] != "") $label = "<i class='leform-icon-left " . $this->form_elements[$i]["icon-left"] . "'></i>" . $label;
            if (array_key_exists("icon-right", $this->form_elements[$i]) && $this->form_elements[$i]["icon-right"] != "") $label .= "<i class='leform-icon-right " . $this->form_elements[$i]["icon-right"] . "'></i>";

            $properties['style-attr'] = "";
            if (array_key_exists("colors-background", $this->form_elements[$i]) && $this->form_elements[$i]["colors-background"] != "") $properties['style-attr'] .= "background-color:" . $this->form_elements[$i]["colors-background"] . ";";
            if (array_key_exists("colors-border", $this->form_elements[$i]) && $this->form_elements[$i]["colors-border"] != "") $properties['style-attr'] .= "border-color:" . $this->form_elements[$i]["colors-border"] . ";";
            if (array_key_exists("colors-text", $this->form_elements[$i]) && $this->form_elements[$i]["colors-text"] != "") $properties['style-attr'] .= "color:" . $this->form_elements[$i]["colors-text"] . ";";
            if ($properties['style-attr'] != "") $style .= ".leform-form-" . $this->id . " .leform-element-" . $this->form_elements[$i]['id'] . " .leform-button, .leform-form-" . $this->id . " .leform-element-" . $this->form_elements[$i]['id'] . " .leform-button:visited{" . $properties['style-attr'] . "}";

            $properties['style-attr'] = "";
            if (array_key_exists("colors-hover-background", $this->form_elements[$i]) && $this->form_elements[$i]["colors-hover-background"] != "") $properties['style-attr'] .= "background-color:" . $this->form_elements[$i]["colors-hover-background"] . ";";
            if (array_key_exists("colors-hover-border", $this->form_elements[$i]) && $this->form_elements[$i]["colors-hover-border"] != "") $properties['style-attr'] .= "border-color:" . $this->form_elements[$i]["colors-hover-border"] . ";";
            if (array_key_exists("colors-hover-text", $this->form_elements[$i]) && $this->form_elements[$i]["colors-hover-text"] != "") $properties['style-attr'] .= "color:" . $this->form_elements[$i]["colors-hover-text"] . ";";
            if ($properties['style-attr'] != "") $style .= ".leform-form-" . $this->id . " .leform-element-" . $this->form_elements[$i]['id'] . " .leform-button:hover, .leform-form-" . $this->id . " .leform-element-" . $this->form_elements[$i]['id'] . " .leform-button:focus{" . $properties['style-attr'] . "}";

            $properties['style-attr'] = "";
            if (array_key_exists("colors-active-background", $this->form_elements[$i]) && $this->form_elements[$i]["colors-active-background"] != "") $properties['style-attr'] .= "background-color:" . $this->form_elements[$i]["colors-active-background"] . ";";
            if (array_key_exists("colors-active-border", $this->form_elements[$i]) && $this->form_elements[$i]["colors-active-border"] != "") $properties['style-attr'] .= "border-color:" . $this->form_elements[$i]["colors-active-border"] . ";";
            if (array_key_exists("colors-active-text", $this->form_elements[$i]) && $this->form_elements[$i]["colors-active-text"] != "") $properties['style-attr'] .= "color:" . $this->form_elements[$i]["colors-active-text"] . ";";
            if ($properties['style-attr'] != "") $style .= ".leform-form-" . $this->id . " .leform-element-" . $this->form_elements[$i]['id'] . " .leform-button:active{" . $properties['style-attr'] . "}";
            break;


          case "textarea":
            $properties["textarea-height"] = $this->form_elements[$i]["textarea-style-height"];
            if ($properties["textarea-height"] == "") $properties["textarea-height"] = $this->form_options["textarea-height"];
            if ($properties["textarea-height"] == "") $properties["textarea-height"] = 160;
            $style .= ".leform-form-" . $this->id . " .leform-element-" . $this->form_elements[$i]['id'] . " div.leform-input {height:" . $properties["textarea-height"] . "px; line-height:2.5;} .leform-form-" . $this->id . " .leform-element-" . $this->form_elements[$i]['id'] . " div.leform-input textarea{line-height:1.4;}";

            $this->form_elements[$i]["default"] = FormService::getExternalValuesAsString(
              $this->form_elements[$i],
              $this->form_elements[$i]["default"],
              $this->form_values
            );
            break;

          case "signature":
            $properties["height"] = $this->form_elements[$i]["height"];
            if (empty($properties["height"])) {
              $properties["height"] = 220;
            }
            $style .= ".leform-form-" . $this->id . " .leform-element-" . $this->form_elements[$i]['id'] . " div.leform-input {height:auto;} .leform-form-" . $this->id . " .leform-element-" . $this->form_elements[$i]['id'] . " div.leform-input div.leform-signature-box {height:" . $properties["height"] . "px;}";

            break;

          case "rangeslider":
            $style .= ".leform-form-" . $this->id . " .leform-element-" . $this->form_elements[$i]['id'] . " div.leform-input{height:auto;line-height:1;}";
            break;

          case "checkbox":
            $options = "";
            $id = $this->leform->random_string(16);
            $uids[] = $id;
            $style .= ".leform-form-" . $this->id . " .leform-element-" . $this->form_elements[$i]['id'] . " div.leform-input{height:auto;line-height:1;}";
            if ($this->form_options["checkbox-view"] === "inverted") {
              $style .= "
                                .leform-form-" . $this->id . " .leform-element-" . $this->form_elements[$i]['id'] . " div.leform-input .leform-checkbox.leform-checkbox-inverted + label {
                                    background-color: " . $this->form_options["checkbox-radio-unchecked-color-color2"] . ";
                                    border-color: " . $this->form_options["checkbox-radio-unchecked-color-color1"] . ";
                                }

                                .leform-form-" . $this->id . " .leform-element-" . $this->form_elements[$i]['id'] . " div.leform-input .leform-checkbox.leform-checkbox-inverted:checked + label {
                                    background-color: " . $this->form_options["checkbox-radio-unchecked-color-color1"] . ";
                                    border-color: " . $this->form_options["checkbox-radio-unchecked-color-color1"] . ";
                                }

                                .leform-form-" . $this->id . " .leform-element-" . $this->form_elements[$i]['id'] . " div.leform-input .leform-checkbox.leform-checkbox-inverted:checked + label::after {
                                    color: white;
                                }
                            ";
            }
            break;

          case "matrix":
            $options = "";
            $id = $this->leform->random_string(16);
            $uids[] = $id;
            $style .= ".leform-form-" . $this->id . " .leform-element-" . $this->form_elements[$i]['id'] . " div.leform-input{height:auto;line-height:1;}";
            if ($this->form_options["checkbox-view"] === "inverted") {
              $style .= "
                                .leform-element-" . $this->form_elements[$i]['id'] . " div.leform-input .leform-checkbox-inverted + label {
                                    background-color: " . $this->form_options["checkbox-radio-unchecked-color-color2"] . ";
                                    border-color: " . $this->form_options["checkbox-radio-unchecked-color-color1"] . ";
                                }

                                .leform-element-" . $this->form_elements[$i]['id'] . " div.leform-input .leform-checkbox-inverted:checked + label {
                                    background-color: " . $this->form_options["checkbox-radio-unchecked-color-color1"] . ";
                                    border-color: " . $this->form_options["checkbox-radio-unchecked-color-color1"] . ";
                                }

                                .leform-element-" . $this->form_elements[$i]['id'] . " div.leform-input .leform-checkbox-inverted:checked + label::after {
                                    color: white;
                                }
                            ";
            }
            break;

          case "imageselect":
            $options = "";
            $id = $this->leform->random_string(16);
            $uids[] = $id;
            $style .= ".leform-form-" . $this->id . " .leform-element-" . $this->form_elements[$i]['id'] . " div.leform-input{height:auto;line-height:1;}";
            $properties["image-width"] = intval($this->form_elements[$i]['image-style-width']);
            if ($properties["image-width"] <= 0 || $properties["image-width"] >= 600) $properties["image-width"] = 120;
            $properties["image-height"] = intval($this->form_elements[$i]['image-style-height']);
            if ($properties["image-height"] <= 0 || $properties["image-height"] >= 600) $properties["image-height"] = 120;

            $properties["label-height"] = intval($this->form_elements[$i]['label-height']);
            if ($properties["label-height"] <= 0 || $properties["label-height"] >= 200 || $this->form_elements[$i]['label-enable'] != 'on') $properties["label-height"] = 0;

            if ($this->form_options['imageselect-selected-scale'] == 'on') {
              $scale = min(floatval(($properties["image-width"] + 8) / $properties["image-width"]), floatval(($properties["image-height"] + 8) / $properties["image-height"]));
              $style .= ".leform-form-" . $this->id . " .leform-element-" . $this->form_elements[$i]['id'] . " div.leform-input .leform-imageselect:checked+label {transform: scale(" . number_format($scale, 2, '.', '') . ");}";
            }
            $extra_class .= ' leform-ta-' . $this->form_options['imageselect-style-align'] . ' leform-imageselect-' . $this->form_options['imageselect-style-effect'];

            $imageHeight = 0;
            $labelHeight = 0;
            if ($properties["image-height"]) {
              $imageHeight = $properties["image-height"];
            }
            if ($properties["label-height"]) {
              $labelHeight = $properties["label-height"];
            }
            $totalHeight = $labelHeight + $imageHeight;

            $style .= ".leform-form-" . $this->id . " .leform-element-" . $this->form_elements[$i]['id'] . " div.leform-input .leform-imageselect+label { width:" . $properties["image-width"] . "px; height:" . $totalHeight . "px; } ";
            $style .= ".leform-form-" . $this->id . " .leform-element-" . $this->form_elements[$i]['id'] . " div.leform-input .leform-imageselect+label  span.leform-imageselect-image {height:" . $properties["image-height"] . "px;background-size:" . $this->form_elements[$i]['image-style-size'] . ";}";
            break;

          case "multiselect":
            $options = "";
            $id = $this->leform->random_string(16);
            $uids[] = $id;
            $style .= ".leform-form-" . $this->id . " .leform-element-" . $this->form_elements[$i]['id'] . " div.leform-input{height:auto;line-height:1;}";
            if (!empty($this->form_elements[$i]['multiselect-style-height'])) $style .= ".leform-form-" . $this->id . " .leform-element-" . $this->form_elements[$i]['id'] . " div.leform-multiselect {height:" . intval($this->form_elements[$i]['multiselect-style-height']) . "px;}";
            break;

          case "radio":
            $options = "";
            $id = $this->leform->random_string(16);
            $uids[] = $id;
            $style .= ".leform-form-" . $this->id . " .leform-element-" . $this->form_elements[$i]['id'] . " div.leform-input{height:auto;line-height:1;}";
            break;

          case "tile":
            $options = "";
            $id = $this->leform->random_string(16);
            $uids[] = $id;
            $style .= ".leform-form-" . $this->id . " .leform-element-" . $this->form_elements[$i]['id'] . " div.leform-input{height:auto;line-height:1;}";
            break;

          case "star-rating":
            $options = "";
            $id = $this->leform->random_string(16);
            $uids[] = $id;
            $style .= ".leform-form-" . $this->id . " .leform-element-" . $this->form_elements[$i]['id'] . " div.leform-input{height:auto;line-height:1;}";

            if (
              $this->form_options["star-rating-color"]
              && $this->form_elements[$i]["overwrite-global-theme-colour"] !== "on"
            ) {
              $a = null;
              if ($this->form_options["filled-star-rating-mode"] === "on") {
                $style .= ".leform-star-rating > label {
                        color: " . $this->form_elements[$i]['star-style-color-unrated'] . " !important;
                    }";
                $a = $this->form_elements[$i]['star-style-color-unrated'];
              } else {
                $style .= ".leform-star-rating > label {
                        color: " . $this->form_options['star-rating-color'] . " !important;
                    }";
                $a = $this->form_options['star-rating-color'];
              }
              $style .= "
                    .leform-star-rating>input:checked~label,
                    .leform-star-rating:not(:checked)>label:hover,
                    .leform-star-rating:not(:checked)>label:hover~label {
                        color: " . $this->form_options['star-rating-color'] . " !important;
                    }
                ";
            } else {
              if (!empty($this->form_elements[$i]['star-style-color-unrated'])) {
                $style .= "
                        .leform-form-" . $this->id . " .leform-element-" . $this->form_elements[$i]['id'] . " .leform-star-rating > label {
                            color:" . $this->form_elements[$i]['star-style-color-unrated'] . " !important;
                        }
                    ";
              }
              if (!empty($this->form_elements[$i]['star-style-color-rated'])) {
                $style .= "
                        .leform-form-" . $this->id . " .leform-element-" . $this->form_elements[$i]['id'] . " .leform-star-rating > input:checked~label,
                        .leform-form-" . $this->id . " .leform-element-" . $this->form_elements[$i]['id'] . " .leform-star-rating:not(:checked) > label:hover,
                        .leform-form-" . $this->id . " .leform-element-" . $this->form_elements[$i]['id'] . " .leform-star-rating:not(:checked) > label:hover~label {
                            color:" . $this->form_elements[$i]['star-style-color-rated'] . " !important;
                        }
                    ";
              }
            }
            break;

          case "html":
            if ($this->form_options["html-headings-color"]) {
              $style .= "
                                .leform-element-" . $this->form_elements[$i]["id"] . " h1,
                                .leform-element-" . $this->form_elements[$i]["id"] . " h2,
                                .leform-element-" . $this->form_elements[$i]["id"] . " h3,
                                .leform-element-" . $this->form_elements[$i]["id"] . " h4,
                                .leform-element-" . $this->form_elements[$i]["id"] . " h5,
                                .leform-element-" . $this->form_elements[$i]["id"] . " h6 {
                                    color: " . $this->form_options["html-headings-color"] . ";
                                }
                            ";
            }

            if ($this->form_options["html-paragraph-color"]) {
              $style .= "
                                .leform-element-" . $this->form_elements[$i]["id"] . " p {
                                    color: " . $this->form_options["html-paragraph-color"] . ";
                                }
                            ";
            }

            if ($this->form_options["html-hr-color"]) {
              $style .= "
                                .leform-element-" . $this->form_elements[$i]["id"] . " hr {
                                    border-color: " . $this->form_options["html-hr-color"] . ";
                                }
                            ";
            }

            if ($this->form_options["html-hr-height"]) {
              $style .= "
                                .leform-element-" . $this->form_elements[$i]["id"] . " hr {
                                    border-top-width: " . $this->form_options["html-hr-height"] . "px;
                                }
                            ";
            }
            break;

          case "columns":
            $options = "";
            for ($j = 0; $j < $this->form_elements[$i]['_cols']; $j++) {
              $style .=  $this->_build_children_css($this->form_elements[$i]['id'], $j);
            }
            break;

          case "repeater-input":
            $fields = $this->form_elements[$i]["fields"];

            $hasStarRating = false;
            foreach ($fields as $field) {
              if ($field["type"] === "star-rating") {
                $hasStarRating = true;
                break;
              }
            }

            if (
              $hasStarRating
              && $this->form_options["star-rating-color"]
            ) {
              $a = null;
              if ($this->form_options["filled-star-rating-mode"] === "on") {
                /*
                                $style .= ".leform-star-rating > label {
                                    color: ". $this->form_elements[$i]['star-style-color-unrated'] ." !important;
                                }";
                                $a = $this->form_elements[$i]['star-style-color-unrated'];
                                 */
              } else {
                $style .= ".leform-star-rating > label {
                                    color: " . $this->form_options['star-rating-color'] . " !important;
                                }";
                $a = $this->form_options['star-rating-color'];
              }
              $style .= "
                                .leform-star-rating>input:checked~label,
                                .leform-star-rating:not(:checked)>label:hover,
                                .leform-star-rating:not(:checked)>label:hover~label {
                                    color: " . $this->form_options['star-rating-color'] . " !important;
                                }
                            ";
            }
            break;

          case "iban-input":
            $options = "";
            $style="";
            break;
            default:
            break;
        }
      }
    }

    return $style;
  }



  protected function _build_style_text($_key, $_important = false)
  {
    $style = "";
    if (array_key_exists($_key . "-family", $this->form_options) && $this->form_options[$_key . "-family"] != "") {
      $style .= "font-family:'" . $this->form_options[$_key . "-family"] . "','arial'" . ($_important ? " !important" : "") . ";";
    }
    if (array_key_exists($_key . "-size", $this->form_options)) {
      $size = intval($this->form_options[$_key . "-size"]);
      if ($size >= 8 && $size <= 64) {
        $style .= "font-size:" . $size . "px" . ($_important ? " !important" : "") . ";";
      }
    }
    if (array_key_exists($_key . "-color", $this->form_options) && $this->form_options[$_key . "-color"] != "") {
      $style .= "color:" . $this->form_options[$_key . "-color"] . ($_important ? " !important" : "") . ";";
    }
    if (array_key_exists($_key . "-bold", $this->form_options) && $this->form_options[$_key . "-bold"] == "on") {
      $style .= "font-weight:bold" . ($_important ? " !important" : "") . ";";
    } else {
      $style .= "font-weight:normal" . ($_important ? " !important" : "") . ";";
    }
    if (array_key_exists($_key . "-italic", $this->form_options) && $this->form_options[$_key . "-italic"] == "on") {
      $style .= "font-style:italic" . ($_important ? " !important" : "") . ";";
    } else {
      $style .= "font-style:normal" . ($_important ? " !important" : "") . ";";
    }
    if (array_key_exists($_key . "-underline", $this->form_options) && $this->form_options[$_key . "-underline"] == "on") {
      $style .= "text-decoration:underline" . ($_important ? " !important" : "") . ";";
    } else {
      $style .= "text-decoration:none" . ($_important ? " !important" : "") . ";";
    }
    if (array_key_exists($_key . "-align", $this->form_options) && $this->form_options[$_key . "-align"] != "") {
      $style .= "text-align:" . $this->form_options[$_key . "-align"] . ";";
    }
    return ["style" => $style, "webfont" => $this->form_options[$_key . "-family"]];
  }

  protected function _build_style_background($_key, $_important = false)
  {
    $style = "";
    $hposition = "left";
    $vposition = "top";
    $color1 = "transparent";
    $color2 = "transparent";
    $direction = "to bottom";
    if (array_key_exists($_key . "-color", $this->form_options) && $this->form_options[$_key . "-color"] != "")
      $color1 = $this->form_options[$_key . "-color"];

    if (array_key_exists($_key . "-gradient", $this->form_options) && $this->form_options[$_key . "-gradient"] == "2shades") {
      $style .= "background-color:" . $color1 . ($_important ? " !important" : "") . ";background-image:linear-gradient(to bottom,rgba(255,255,255,.05) 0,rgba(255,255,255,.05) 50%,rgba(0,0,0,.05) 51%,rgba(0,0,0,.05) 100%)" . ($_important ? " !important" : "") . ";";
    } else if (array_key_exists($_key . "-gradient", $this->form_options) && ($this->form_options[$_key . "-gradient"] == "horizontal" || $this->form_options[$_key . "-gradient"] == "vertical" || $this->form_options[$_key . "-gradient"] == "diagonal")) {
      if (array_key_exists($_key . "-color2", $this->form_options) && $this->form_options[$_key . "-color2"] != "") {
        $color2 = $this->form_options[$_key . "-color2"];
      }
      if ($this->form_options[$_key . "-gradient"] == "horizontal") {
        $direction = "to right";
      } else if ($this->form_options[$_key . "-gradient"] == "diagonal") {
        $direction = "to bottom right";
      }
      $style .= "background-image:linear-gradient(" . $direction . "," . $color1 . "," . $color2 . ")" . ($_important ? " !important" : "") . ";";
    } else if (array_key_exists($_key . "-image", $this->form_options) && $this->form_options[$_key . "-image"] != "") {
      $style .= "background-color:" . $color1 . ($_important ? " !important" : "") . ";background-image:url('" . $this->form_options[$_key . "-image"] . "')" . ($_important ? " !important" : "") . ";";
      if (array_key_exists($_key . "-size", $this->form_options) && $this->form_options[$_key . "-size"] != "") {
        $style .= "background-size:" . $this->form_options[$_key . "-size"] . ($_important ? " !important" : "") . ";";
      }
      if (array_key_exists($_key . "-repeat", $this->form_options) && $this->form_options[$_key . "-repeat"] != "") {
        $style .= "background-repeat:" . $this->form_options[$_key . "-repeat"] . ($_important ? " !important" : "") . ";";
      }
      if (array_key_exists($_key . "-horizontal-position", $this->form_options) && $this->form_options[$_key . "-horizontal-position"] != "") {
        switch ($this->form_options[$_key . "-horizontal-position"]) {
          case 'center':
            $hposition = "center";
            break;
          case 'right':
            $hposition = "right";
            break;
          default:
            $hposition = "left";
            break;
        }
      }
      if (array_key_exists($_key . "-vertical-position", $this->form_options) && $this->form_options[$_key . "-vertical-position"] != "") {
        switch ($this->form_options[$_key . "-vertical-position"]) {
          case 'middle':
            $vposition = "center";
            break;
          case 'bottom':
            $vposition = "bottom";
            break;
          default:
            $vposition = "top";
            break;
        }
      }
      $style .= "background-position: " . $hposition . " " . $vposition . ($_important ? " !important" : "") . ";";
    } else {
      $style .= "background-color:" . $color1 . ($_important ? " !important" : "") . ";background-image:none" . ($_important ? " !important" : "") . ";";
    }
    return $style;
  }

  protected function _build_style_border($_key, $_important = false)
  {
    $style = "";
    if (array_key_exists($_key . "-width", $this->form_options)) {
      $size = intval($this->form_options[$_key . "-width"]);
      if ($size >= 0 && $size <= 16) {
        $style .= "border-width:" . $size . "px" . ($_important ? " !important" : "") . ";";
      }
    }
    if (array_key_exists($_key . "-style", $this->form_options) && $this->form_options[$_key . "-style"] != "") {
      $style .= "border-style:" . $this->form_options[$_key . "-style"] . ($_important ? " !important" : "") . ";";
    }
    if (array_key_exists($_key . "-color", $this->form_options) && $this->form_options[$_key . "-color"] != "") {
      $style .= "border-color:" . $this->form_options[$_key . "-color"] . ($_important ? " !important" : "") . ";";
    }
    if (array_key_exists($_key . "-radius", $this->form_options)) {
      $size = intval($this->form_options[$_key . "-radius"]);
      if ($size >= 0 && $size <= 100) {
        $style .= "border-radius:" . $size . "px" . ($_important ? " !important" : "") . ";";
      }
    }
    if (array_key_exists($_key . "-top", $this->form_options) && $this->form_options[$_key . "-top"] != "on") {
      $style .= "border-top:none !important;";
    }
    if (array_key_exists($_key . "-left", $this->form_options) && $this->form_options[$_key . "-left"] != "on") {
      $style .= "border-left:none !important;";
    }
    if (array_key_exists($_key . "-right", $this->form_options) && $this->form_options[$_key . "-right"] != "on") {
      $style .= "border-right:none !important;";
    }
    if (array_key_exists($_key . "-bottom", $this->form_options) && $this->form_options[$_key . "-bottom"] != "on") {
      $style .= "border-bottom:none !important;";
    }
    return $style;
  }

  protected function _build_shadow($_key, $_important = false)
  {
    $style = "box-shadow:none;";
    $color = "transparent";
    $shadow_style = "regular";
    if (array_key_exists($_key . "-size", $this->form_options) && $this->form_options[$_key . "-size"] != "") {
      if (array_key_exists($_key . "-color", $this->form_options) && $this->form_options[$_key . "-color"] != "") {
        $color = $this->form_options[$_key . "-color"];
      }
      if (array_key_exists($_key . "-style", $this->form_options) && $this->form_options[$_key . "-style"] != "") {
        $shadow_style = $this->form_options[$_key . "-style"];
      }
      switch ($shadow_style) {
        case 'solid':
          if ($this->form_options[$_key . "-size"] == "tiny") {
            $style = "box-shadow: 1px 1px 0px 0px " . $color . ($_important ? " !important" : "") . ";";
          } else if ($this->form_options[$_key . "-size"] == "small") {
            $style = "box-shadow: 2px 2px 0px 0px " . $color . ($_important ? " !important" : "") . ";";
          } else if ($this->form_options[$_key . "-size"] == "medium") {
            $style = "box-shadow: 4px 4px 0px 0px " . $color . ($_important ? " !important" : "") . ";";
          } else if ($this->form_options[$_key . "-size"] == "large") {
            $style = "box-shadow: 6px 6px 0px 0px " . $color . ($_important ? " !important" : "") . ";";
          } else if ($this->form_options[$_key . "-size"] == "huge") {
            $style = "box-shadow: 8px 8px 0px 0px " . $color . ($_important ? " !important" : "") . ";";
          }
          break;
        case 'inset':
          if ($this->form_options[$_key . "-size"] == "tiny") {
            $style = "box-shadow: inset 0px 0px 15px -9px " . $color . ($_important ? " !important" : "") . ";";
          } else if ($this->form_options[$_key . "-size"] == "small") {
            $style = "box-shadow: inset 0px 0px 15px -8px " . $color . ($_important ? " !important" : "") . ";";
          } else if ($this->form_options[$_key . "-size"] == "medium") {
            $style = "box-shadow: inset 0px 0px 15px -7px " . $color . ($_important ? " !important" : "") . ";";
          } else if ($this->form_options[$_key . "-size"] == "large") {
            $style = "box-shadow: inset 0px 0px 15px -6px " . $color . ($_important ? " !important" : "") . ";";
          } else if ($this->form_options[$_key . "-size"] == "huge") {
            $style = "box-shadow: inset 0px 0px 15px -5px " . $color . ($_important ? " !important" : "") . ";";
          }
          break;
        default:
          if ($this->form_options[$_key . "-size"] == "tiny") {
            $style = "box-shadow: 1px 1px 15px -9px " . $color . ($_important ? " !important" : "") . ";";
          } else if ($this->form_options[$_key . "-size"] == "small") {
            $style = "box-shadow: 1px 1px 15px -8px " . $color . ($_important ? " !important" : "") . ";";
          } else if ($this->form_options[$_key . "-size"] == "medium") {
            $style = "box-shadow: 1px 1px 15px -7px " . $color . ($_important ? " !important" : "") . ";";
          } else if ($this->form_options[$_key . "-size"] == "large") {
            $style = "box-shadow: 1px 1px 15px -6px " . $color . ($_important ? " !important" : "") . ";";
          } else if ($this->form_options[$_key . "-size"] == "huge") {
            $style = "box-shadow: 1px 1px 15px -5px " . $color . ($_important ? " !important" : "") . ";";
          }
          break;
      }
    }
    return $style;
  }

  protected function _build_style_padding($_key, $_spacing = 0)
  {
    $style = "";
    $integer = 0;
    if (array_key_exists($_key . "-top", $this->form_options)) {
      $integer = max(intval($this->form_options[$_key . "-top"]) - $_spacing, 0);
      if ($integer >= 0 && $integer <= 300) {
        $style .= "padding-top:" . $integer . "px;";
      }
    }
    if (array_key_exists($_key . "-right", $this->form_options)) {
      $integer = max(intval($this->form_options[$_key . "-right"]) - $_spacing, 0);
      if ($integer >= 0 && $integer <= 300) {
        $style .= "padding-right:" . $integer . "px;";
      }
    }
    if (array_key_exists($_key . "-bottom", $this->form_options)) {
      $integer = max(intval($this->form_options[$_key . "-bottom"]) - $_spacing, 0);
      if ($integer >= 0 && $integer <= 300) {
        $style .= "padding-bottom:" . $integer . "px;";
      }
    }
    if (array_key_exists($_key . "-left", $this->form_options)) {
      $integer = max(intval($this->form_options[$_key . "-left"]) - $_spacing, 0);
      if ($integer >= 0 && $integer <= 300) {
        $style .= "padding-left:" . $integer . "px;";
      }
    }
    return $style;
  }


  protected function _build_hidden($_parent)
  {
    $html = '';
    for ($i = 0; $i < sizeof($this->form_elements); $i++) {
      if (empty($this->form_elements[$i])) {
        continue;
      }
      if ($this->form_elements[$i]["type"] != "hidden") {
        continue;
      }
      if ($this->form_elements[$i]["_parent"] != $_parent) {
        continue;
      }
      $html .= "<input class='leform-hidden' type='hidden' name='leform-" . $this->form_elements[$i]['id'] . "'" . ($this->form_elements[$i]['dynamic-default'] == 'on' ? " data-dynamic='" . $this->form_elements[$i]['dynamic-parameter'] . "'" : "") . " data-id='" . $this->form_elements[$i]['id'] . "' data-default='" . $this->form_elements[$i]["default"] . "' value='" . $this->form_elements[$i]["default"] . "' />";
    }
    return $html;
  }

  protected function replaceWithPredefinedValues($string, $predefinedValues = [])
  {
    if ($predefinedValues === null) {
      $string = preg_replace("/\{\{[A-z1-9-_]*\}\}/", "", $string);
      return $string;
    }
    $allVariables = evo_get_all_variables($predefinedValues);
    $allowedTypes = ["boolean", "integer", "double", "string"];
    foreach ($allVariables as $key => $value) {
      if (in_array(gettype($value), $allowedTypes)) {
        $string = preg_replace("/\{\{$key\}\}/", $value, $string);
      }
    }
    $string = preg_replace("/\{\{[A-z1-9-_]*\}\}/", "", $string);
    return $string;
  }
  protected function _prepare_ranges($_ranges)
  {
    $raw_ranges = explode(',', $_ranges);
    $sanitized_ranges = [];
    foreach ($raw_ranges as $range) {
      $range = trim($range);
      if (strlen($range) == 0) {
        continue;
      }
      $range_parts = explode('...', $range);
      if (sizeof($range_parts) == 1) {
        $range_parts[0] = trim($range_parts[0]);
        if (strlen($range_parts[0]) > 0 && is_numeric($range_parts[0])) {
          $sanitized_ranges[] = $range_parts[0];
        } else {
          continue;
        }
      } else if (sizeof($range_parts) == 2) {
        $range_parts[0] = trim($range_parts[0]);
        $range_parts[1] = trim($range_parts[1]);
        if (strlen($range_parts[0]) == 0) {
          $range_parts[0] = -2147483648;
        } else if (!is_numeric($range_parts[0])) {
          continue;
        }

        if (strlen($range_parts[1]) == 0) {
          $range_parts[1] = 2147483647;
        } else if (!is_numeric($range_parts[1])) {
          continue;
        }

        if ($range_parts[0] < $range_parts[1]) {
          $sanitized_ranges[] = $range_parts[0] . '...' . $range_parts[1];
        } else if ($range_parts[0] > $range_parts[1]) {
          $sanitized_ranges[] = $range_parts[1] . '...' . $range_parts[0];
        } else {
          $sanitized_ranges[] = $range_parts[0];
        }
      } else {
        continue;
      }
    }
    do {
      $finish = true;
      for ($i = 0; $i < sizeof($sanitized_ranges) - 1; $i++) {
        $range = explode('...', $sanitized_ranges[$i]);
        $val1 = $range[0];
        $range = explode('...', $sanitized_ranges[$i + 1]);
        $val2 = $range[0];
        if ($val2 < $val1) {
          $val1 = $sanitized_ranges[$i];
          $sanitized_ranges[$i] = $sanitized_ranges[$i + 1];
          $sanitized_ranges[$i + 1] = $val1;
          $finish = false;
        }
      }
    } while ($finish === false);
    do {
      $finish = true;
      for ($i = 0; $i < sizeof($sanitized_ranges) - 1; $i++) {
        $range1 = explode('...', $sanitized_ranges[$i]);
        if (sizeof($range1) == 1) $range1[1] = $range1[0];
        $range2 = explode('...', $sanitized_ranges[$i + 1]);
        if (sizeof($range2) == 1) $range2[1] = $range2[0];
        if ($range1[1] >= $range2[0]) {
          $max = max($range1[1], $range2[1]);
          if ($range1[0] == $max) $sanitized_ranges[$i + 1] = $max;
          else $sanitized_ranges[$i + 1] = $range1[0] . '...' . $max;
          unset($sanitized_ranges[$i]);
          $finish = false;
        }
      }
      $sanitized_ranges = array_values($sanitized_ranges);
    } while ($finish === false);
    return implode(',', $sanitized_ranges);
  }

  private function renderRepeaterInputFieldRow(
    $fields,
    $rowIndex,
    $formFieldIndex,
    $properties,
    $value
  ) {
    $form_options = $this->form_options;
    $elementPropertiesMeta = $this->leform->getElementPropertiesMeta();

    $row = "";
    foreach ($fields as $columnIndex => $field) {
      $component = "";
      $containerStyle = "";
      switch ($field["type"]) {
        case "text":
        case "email":
        case "password":
          $component = "
                        <input
                            name='leform-" . $this->form_elements[$formFieldIndex]['id'] . "-" . $rowIndex . "[]'
                            class='w-full'
                            type='" . $this->accessOrReturnString("type", $field) . "'
                            placeholder='" . $this->accessOrReturnString("placeholder", $field) . "'
                            value='" . $this->accessOrReturnString("defaultValue", $field) . "'
                            data-default-value='" . $this->accessOrReturnString("defaultValue", $field) . "'
                            oninput='leform_input_changed(this);'
                            data-input-name='" .$field["name"] . "'
                        />
                    ";
          break;
        case "number":
          $component = "
                        <input
                            name='leform-" . $this->form_elements[$formFieldIndex]['id'] . "-" . $rowIndex . "[]'
                            class='w-full'
                            type='number'
                            placeholder='" . $this->accessOrReturnString("placeholder", $field) . "'
                            value='" . $this->accessOrReturnString("defaultValue", $field) . "'
                            data-default-value='" . $this->accessOrReturnString("defaultValue", $field) . "'
                            oninput='leform_input_changed(this);'
                            data-input-name='" . $field["name"] . "'
                        />
                    ";
          break;
        case "select":
          $field["options"] = FormService::getExternalValuesAsArray($field);
          $options = "";
          foreach ($field["options"] as $j => $option) {
            $selected = ($field["defaultValue"] === $j)
              ? "selected"
              : "";
            $options .= "
                            <option
                                value='{$option}'
                                {$selected}
                            >
                                {$option}
                            </option>
                        ";
          }
          $component = "
                        <select
                            name='leform-" . $this->form_elements[$formFieldIndex]['id'] . "-" . $rowIndex . "[]'
                            class='w-full'
                            onchange='leform_input_changed(this);'
                            data-input-name='" . $field["name"] . "'
                        >
                            <option value='' selected disabled>"
            . $this->accessOrReturnString("placeholder", $field) .
            "</option>
                            {$options}
                        </select>
                    ";
          break;
        case "date":
          $component = "
                        <input
                            name='leform-" . $this->form_elements[$formFieldIndex]['id'] . "-" . $rowIndex . "[]'
                            type='text'
                            class='leform-date w-full'
                            data-default='" . $field["defaultValue"] . "'
                            value='" . $field["defaultValue"] . "'
                            oninput='leform_input_changed(this);'
                            data-input-name='" . $field["name"] . "'
                        />
                    ";
          break;
        case "time":
          $component = "
                        <input
                            name='leform-" . $this->form_elements[$formFieldIndex]['id'] . "-" . $rowIndex . "[]'
                            type='text'
                            class='leform-time w-full'
                            " . (array_key_exists("defaultValue", $field)
            ? "data-default='" . $field["defaultValue"] . "'"
            : "")
            . "
                            " . (array_key_exists("defaultValue", $field)
              ? "value='" . $field["defaultValue"] . "'"
              : "")
            . "
                            oninput='leform_input_changed(this);'
                            data-input-name='" . $field["name"] . "'
                        />
                    ";
          break;
        case "rangeslider":
          if (isset($style)) {
            $style .= "#leform-element-{$formFieldIndex} div.leform-input{height:auto;line-height:1;}";
          } else {
            $style = "#leform-element-{$formFieldIndex} div.leform-input{height:auto;line-height:1;}";
          }

          $containerStyle = "style='width: 150px;'";

          $options = [
            [
              "data-type",
              $elementPropertiesMeta["rangeslider"]["double"] === "on"
                ? "double"
                : "single"
            ],
            [
              "data-grid",
              $elementPropertiesMeta["rangeslider"]["grid-enable"] === "on"
                ? "true"
                : "false"
            ],
            [
              "data-hide-min-max",
              $elementPropertiesMeta["rangeslider"]["min-max-labels"] === "on"
                ? "false"
                : "true"
            ],
            ["data-skin", $form_options["rangeslider-skin"]],
            [
              "data-from",
              $this->accessOrReturnString("defaultValue", $field)
                || $this->accessOrReturnString("min", $field, 0)
            ],
            ["data-min", $this->accessOrReturnString("min", $field, 0)],
            ["data-max", $this->accessOrReturnString("max", $field, 100)],
          ];

          $rangeSliderOptions = "";
          foreach ($options as $option) {
            /* $rangeSliderOptions .= implode("=", $option); */
            $rangeSliderOptions .= $option[0] . "='" . $option[1] . "' ";
          }

          $component = "
                        <input
                            name='leform-" . $this->form_elements[$formFieldIndex]['id'] . "-" . $rowIndex . "[]'
                            type='text w-full'
                            class='leform-rangeslider'
                            {$rangeSliderOptions}
                        />
                    ";
          break;
        case "star-rating":
          $starRatingOptions = "";
          $starCount = $this->accessOrReturnString("starCount", $field, 5);

          for ($j = $starCount; $j > 0; $j--) {
            $starRatingOptions .= "
                            <input
                                type='radio'
                                id='leform-stars-" .
              $this->form_elements[$formFieldIndex]['id']
              . "-" .
              $rowIndex
              . "-" .
              $columnIndex
              . "-" . $j . "'
                                name='leform-" .
              $this->form_elements[$formFieldIndex]['id']
              . "-" .
              $rowIndex
              . "-" .
              $columnIndex
              . "'
                                value='$j'
                                onchange='leform_input_changed(this);'
                                data-input-name='" . $field["name"] . "'
                            />
                            <label for='leform-stars-" .
              $this->form_elements[$formFieldIndex]['id']
              . "-" .
              $rowIndex
              . "-" .
              $columnIndex
              . "-$j'>
                            </label>
                        ";
          }

          $component = "
                        <fieldset class='leform-star-rating'>
                            $starRatingOptions
                        </fieldset>
                    ";
          break;
        case "link-button":
          $component = "
                        <a
                            class='px-3 leform-button leform-button-"
            . $form_options['button-active-transform'] .
            " leform-button-"
            . $this->accessOrReturnString("width", $properties) .
            " leform-button-"
            . $this->accessOrReturnString("size", $properties) .
            "'
                            href='" . $this->accessOrReturnString("href", $field) . "'
                            target='_blank'
                            onclick='return false;'
                        >"
            . $this->accessOrReturnString("buttonText", $field) .
            "</a>
                        <div class='leform-element-cover'></div>
                    ";
          break;
        case "html": {
            $htmlContent = "";

            if (array_key_exists("content", $field)) {
              $replacement = "
                            <span
                                class='leform-repeater-var leform-repeater-var-$1'
                                data-id='$1'
                            ></span>
                        ";
              $htmlContent = $field["content"];
              /* $htmlContent = preg_replace("/{{(\d+).+?}}/", $replacement, $htmlContent); */
              $htmlContent = preg_replace("/\[\[(\d+).+?(\]\])/", $replacement, $htmlContent);
            }
            $htmlExternal = "";
            if (
              isset($field['external-datasource']) &&
              isset($field['external-datasource-url']) &&
              $field['external-datasource'] === 'on'
            ) {
              // $component = FormService::getExternalValuesAsString(
              //   $field,
              //   $component,
              //   $this->form_values
              // );
              $url =  preg_replace("/{{(\d+).+?}}/", '{{$1}}', $field['external-datasource-url']);
              $path = isset($field['external-datasource-path']) ? $field['external-datasource-path'] : '';

              $htmlExternal = "
                <span class='html-external-datasource' data-url='$url' >
                    <input type='hidden' class='html-external-datasource-transformed' data-path='$path' value=''/>
                </span>
              ";
            }
            $component = " <div class='leform-element-html-container'> $htmlContent <div class='leform-element-cover'></div> $htmlExternal</div>";
            break;
          }
      }
      $row .= "
                <td class='p-2 border-2' data-type='" . $field["type"] . "'>
                    <div class='leform-input' $containerStyle>
                        $component
                    </div>
                </td>
            ";
    }
    return "
            <tr>
                $row
                <td class='border-2 w-10'>
                    <div class='flex justify-center'>
                        <button
                            class='remove-row bg-red-400 rounded-xl w-6 h-6 flex justify-center items-center hidden'
                            style='color: white;'
                        >
                            -
                        </button>
                    </div>
                </td>
            </tr>
        ";
  }

  private function accessOrReturnString($property, $array, $defaultValue = 0)
  {
    if (array_key_exists($property, $array)) {
      return $array[$property];
    } else {
      if ($defaultValue !== null) {
        return $defaultValue;
      } else {
        return "";
      }
    }
  }

  public static function mapColumnElementsToColumn(&$element)
  {
      $columns = [];
      foreach ($element->properties->elements as $colElement) {
          $parentCol = $colElement->{"_parent-col"};
          if ($colElement->type === "columns") {
              self::mapColumnElementsToColumn($colElement);
          }
          if (!array_key_exists($parentCol, $columns)) {
              $columns[$parentCol] = [];
          }
          $columns[$parentCol][] = $colElement;
      }
      $element->properties->elements = $columns;
  }

  public static function isElementOrdered($element, $orderAccessor)
  {
    return property_exists($element, $orderAccessor)
        && $element->{$orderAccessor}
        && is_numeric($element->{$orderAccessor});
  }

  public static function popOrderedColumnElements(&$element, $orderAccessor)
  {
      $orderedElements = [];
      foreach ($element->properties->elements as $colElementIndex => $colElement) {
          if ($colElement->type === "columns") {
              $orderedElements = array_merge(
                  $orderedElements,
                  self::popOrderedColumnElements($colElement, $orderAccessor),
              );
          } else if (self::isElementOrdered($colElement, $orderAccessor)) {
              $orderedElements[] = $colElement;
              unset($element->properties->elements[$colElementIndex]);
          }
      }
      return $orderedElements;
  }

  public static function appendElementToOrderedList(&$list, $element, $orderAccessor)
  {
      $order = $element->{$orderAccessor};
      if (!array_key_exists($order, $list)) {
          $ordered[$order] = [];
      }
      $list[$order][] = $element;
  }

  public static function getElementsSortedByOrder($form, $orderAccessor = "order")
  {
      $ordered = [];
      $unordered = [];
      foreach ($form["elements"] as $element) {
          if ($element->type === "columns") {
              $popedElements = self::popOrderedColumnElements(
                  $element,
                  $orderAccessor
              );
              foreach ($popedElements as $popedElement) {
                  self::appendElementToOrderedList(
                      $ordered,
                      $popedElement,
                      $orderAccessor
                  );
              }
          }
          if (self::isElementOrdered($element, $orderAccessor)) {
              self::appendElementToOrderedList(
                  $ordered,
                  $element,
                  $orderAccessor
              );
          } else {
              $unordered[] = $element;
          }
      }
      ksort($ordered);
      $allElementsOrdered = [];
      foreach ($ordered as $orderGroup) {
          $allElementsOrdered = array_merge($allElementsOrdered, $orderGroup);
      }
      $allElementsOrdered = array_merge($allElementsOrdered, $unordered);
      return $allElementsOrdered;
  }
}
