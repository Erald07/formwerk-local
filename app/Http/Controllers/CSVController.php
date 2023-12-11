<?php

namespace App\Http\Controllers;

use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

use App\Models\Record;

use App\Service\FormService;
use App\Service\RecordPdfService;
use App\Service\LeformFormService;

use Faker\Factory as Faker;

class CSVController extends Controller
{
    private static $acceptedFields = [
        "text",
        "email",
        "textarea",
        "select",
        "tile",
        "radio",
        "checkbox",
        "signature",
        "date",
        "hidden",
        "columns",
        "repeater-input",
        "rangeslider"
    ];

    private static function isElementCSVDisplayable($element)
    {
        return in_array($element->type, CSVController::$acceptedFields);
    }

    public static function getRowsFromElement($element, $predefinedValues)
    {
        if (!CSVController::isElementCSVDisplayable($element)) {
            return [];
        }

        $rows = [];
        $show = true;
        if (property_exists($element, "display-on-csv")) {
            $show = ($element->{"display-on-csv"} === "on");
        }
        $allVariables = evo_get_all_variables($predefinedValues);
        if ($show) {
            if ($element->type === "columns") {
                $nextPredefinedValues = $allVariables;
                // TODO getVariables

                if ($element->{"has-dynamic-values"} == "on") {
                    $dynamicValueKey = $element->{"dynamic-value"};
                    $dynamicValueNumber = $element->{"dynamic-value-index"};
                    $dynamicValueIndex = $dynamicValueNumber - 1;

                    if (
                        array_key_exists($dynamicValueKey, $allVariables)
                        && array_key_exists($dynamicValueIndex, $allVariables[$dynamicValueKey])
                    ) {
                        $nextPredefinedValues = $allVariables[$dynamicValueKey];
                    } else {
                        return $rows;
                    }
                }

                foreach ($element->properties->elements as $colElement) {
                    foreach (CSVController::getRowsFromElement($colElement, $nextPredefinedValues) as $el) {
                        $rows[] = $el;
                    }
                }
            } else {
                $rows[] = $element;
            }
        }
        return $rows;
    }

    public static function createCSVForEntry($companyId, $recordId, $encoding)
    {
        $record = Record::with("form")
            ->where("deleted", 0)
            ->where("id", $recordId)
            ->first();

        if (!$record) {
            return response(__("Record not found"), 404);
        }

        if (
            $record
            && $record["form"]["company_id"] != $companyId
        ) {
            return response(__("Form does not belong to user"), 403);
        }

        $form = $record->form->toArray();
        $form = RecordPdfService::decodeForm($form);
        $fs = new FormService($form, $record["fields"]);
        $form = $fs->getFormObject();

        $predefinedValues = json_decode($record->predefined_values, true);
        $allVariables = evo_get_all_variables($predefinedValues);
        $elements = [];
        $orderedElements = FormService::getElementsSortedByOrder($form, "csv-order");
        foreach ($orderedElements as $element) {
            foreach (CSVController::getRowsFromElement($element, $allVariables) as $el) {
                if ($el) {
                    $elements[] = $el;
                }
            }
        }

        $values = json_decode($record->fields);
        $separator = isset($form['options']) && isset($form['options']['csv-file-separator']) ?
        $form['options']['csv-file-separator'] : ',';
        $enclosure =  isset($form['options']) && isset($form['options']['csv-input-enclosure']) ?
        $form['options']['csv-input-enclosure'] : "'";
        $includeHeader =  isset($form['options']) && isset($form['options']['csv-include-header']) ?
        $form['options']['csv-include-header'] : 'on';

        $csv = "";
        $header = "";
        $body = "";

        foreach ($elements as $element) {
            if (property_exists($values, $element->id)) {
                $value = $values->{$element->id};

                if ($element->type === "repeater-input") {
                    $repeaterValues = isset($values->{$element->id}) && is_array($values->{$element->id}) ? $values->{$element->id} : [[]];
                    $rValues = [];
                    foreach ($repeaterValues as $rowValue) {
                        foreach ($element->fields as $key => $field) {
                            if(!isset($rValues[$key])){
                                $rValues[$key] = [];
                            }
                            $rValues[$key][] = isset($rowValue[$key]) ? $rowValue[$key] : (!empty($field->defaultValue) ? $field->defaultValue : "");
                        }
                    }
                    foreach ($element->fields as $key => $field) {
                        $field = (object) $field;
                        $header .= replaceWithPredefinedValues(
                                $field->name,
                                $allVariables
                            ) . $separator;

                        $fieldValue = isset($rValues[$key]) ? $rValues[$key] : [];
                        if (is_array($fieldValue)) {
                            $fieldValue = array_filter($fieldValue, function ($value) {
                                return $value !== '';
                            });
                            $fieldValue = implode(",", $fieldValue);
                        }

                        $body .= $enclosure . $fieldValue . $enclosure . $separator;
                    }
                } else {
                    $header .= replaceWithPredefinedValues(
                            $element->name,
                            $allVariables
                        ) . $separator;

                    if ($element->type === "signature") {
                        $body .= ($value ? 1 : 0);
                    } else {
                        if (is_array($value)) {
                            $value = implode(",", $value);
                        }
                        $body .= $enclosure . $value . $enclosure . $separator;
                    }
                }
            }
        }

        $body = rtrim($body, $separator);
        $header = rtrim($header, $separator);

        $body .= PHP_EOL;


        if ($encoding == 'utf-8') {
            $body = utf8_encode($body);
            $header = utf8_encode($header);
        }
        if ($encoding == "ansii") {
            $body = mb_convert_encoding($body, 'Windows-1252', 'UTF-8');
            $header = mb_convert_encoding($header, 'Windows-1252', 'UTF-8');
        }

        // if($includeHeader === 'on') {
        //     $csv .= $header. "\n";
        // }
        // $csv .= $body;
        return ["body" => $body, "header" => $header, 'includeHeader' => $includeHeader];
    }

    public static function downloadCSVFile(Request $request, $recordId)
    {
        $record = Record::firstWhere("id", $recordId);
        if (!isset($record) || !isset($record->csv_file_name) || is_null($record->csv_file_name) || !Storage::disk('private')->exists($record->csv_file_name)) {
            return response(__("File not found"), 404);
        }
        return Storage::disk('private')->download($record->csv_file_name);
        // $companyId = $request->user()->company_id;

        // $record = Record::with("form")
        //     ->where("deleted", 0)
        //     ->where("id", $recordId)
        //     ->first();
        // $options = json_decode($record->form->options, true);

        // if ($options["generate-csv-on-save"] != "on") {
        //     return response(__("CSV is not allowed for this form"), 404);
        // }

        // $csv = self::createCSVForEntry($companyId, $recordId);
        // $company = Company::firstWhere("id", $companyId);
        // $fullFileName = self::generateCSVFileName(
        //     $company,
        //     $record->form,
        //     $record,
        //     false,
        // );
        // $fileName = substr(
        //     $fullFileName,
        //     strrpos($fullFileName, '/') + 1,
        // );

        // return response()->streamDownload(function () use ($csv) {
        //     echo $csv;
        // }, $fileName);
    }

    public static function generateCSVFileName($company, $form, $entry, $withSubmittionTime = true)
    {
        $submitionTime = $withSubmittionTime ? $entry->created : time();

        $fileName = $company->company_name . "/"
            . $form["name"] . "/"
            . (
                $entry->dynamic_form_name_with_values
                    ? $entry->dynamic_form_name_with_values . "/"
                    : ""
            )
            . date("Y", $submitionTime) . "/";

        $formOptions = json_decode($form->options, true);
        $hasCustomFileName = ($formOptions["has-csv-custom-file-name"] == "on");
        $fileNameExpression = $formOptions["csv-custom-file-name"];

        if ($hasCustomFileName && $fileNameExpression !== "") {
            $systemValues = [
                "{{fw_id}}" => Faker::create()->numerify(str_repeat("#", 12)),
                "{{fw_yyyymmdd}}" => date("Ymd", $submitionTime),
                "{{fw_yyyymmdd_hhii}}" => date("Ymd_Hi", $submitionTime),
                "{{fw_yyyymmdd_hhiiss}}" => date("Ymd_His", $submitionTime),
                "{{fw_random_5}}" => Faker::create()->numerify("#####"),
            ];
            foreach ($systemValues as $variable => $variableValue) {
                $fileNameExpression = str_replace(
                    $variable,
                    $variableValue,
                    $fileNameExpression,
                );
            }

            $fileNameExpression = LeformFormService::replaceFormValues(
                $fileNameExpression,
                json_decode($entry["fields"], true),
                LeformFormService::getFormElements($form),
                $formOptions
            );
            $predefinedValues = json_decode(
                $entry->predefined_values,
                true,
            );
            $allVariables = evo_get_all_variables($predefinedValues);
            $fileNameExpression = replaceWithPredefinedValues(
                $fileNameExpression,
                $allVariables
            );

            $fileName .= $fileNameExpression;
        } else {
            $fileName .= $form["name"] . "_"
                . (
                    $entry->dynamic_form_name_with_values
                        ? $entry->dynamic_form_name_with_values . "_"
                        : ""
                )
                . date("Ymd_His", $submitionTime) . "_"
                . Faker::create()->numerify(str_repeat("#", 7));
        }

        return $fileName. ".csv";
    }

    public static function saveCSVFile($fileName, $csvParts, $append = false)
    {
        $csv = "";
        if ($append && Storage::disk('private')->exists($fileName)) {
            Storage::disk("private")->append($fileName, $csvParts['body']);
        } else {
            if ($csvParts['includeHeader'] === 'on') {
                $csv .= $csvParts['header'] . "\n";
            }
            $csv .= $csvParts['body'];

            Storage::disk("private")->put($fileName, $csv);
        }
    }
}
