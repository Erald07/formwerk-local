<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use App\Models\Record;
use App\Models\Company;
use App\Service\FormService;
use App\Service\RecordPdfService;
use App\Service\ArrayToXml;
use App\Service\LeformService;
use App\Service\LeformFormService;
use DOMDocument;
use Exception;
use ZipArchive;
use Faker\Factory as Faker;

class XMLController extends Controller
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

    private static $checkVisibility = [
        "radio",
        "checkbox",
        "text",
        "email",
        "columns",
    ];
    private static $form_object;

    private static function getTimeSystemVariables($time)
    {
        return [
            "{{fw_yyyymmdd}}" => date("Ymd", $time),
            "{{fw_yyyymmdd_hhii}}" => date("Ymd_Hi", $time),
            "{{fw_yyyymmdd_hhiiss}}" => date("Ymd_His", $time),
        ];
    }

    private static function replaceCustomXmlFieldsWithValues(
        $customXmlFields,
        $replaceValues
    ) {
        foreach ($customXmlFields as $index => $field) {
            $customXmlFields[$index]["value"] = replaceWithPredefinedValues(
                $customXmlFields[$index]["value"],
                $replaceValues
            );
        }
        return $customXmlFields;
    }

    private static function replaceSystemVariables($submittionTime, $customXmlFields, $value, $type)
    {
        $timeSystemVariables = self::getTimeSystemVariables($submittionTime);
        $systemValues = [
            "{{fw_id}}" => Faker::create()->numerify(str_repeat("#", 12)),
            "{{fw_yyyymmdd}}" => $timeSystemVariables["{{fw_yyyymmdd}}"],
            "{{fw_yyyymmdd_hhii}}" => $timeSystemVariables["{{fw_yyyymmdd_hhii}}"],
            "{{fw_yyyymmdd_hhiiss}}" => $timeSystemVariables["{{fw_yyyymmdd_hhiiss}}"],
            "{{fw_random_5}}" => Faker::create()->numerify("#####"),
        ];
        if ($value && !in_array($type, ["columns", "repeater-input"])) {
            $systemValues["{{fw_value}}"] = $value;
        }
        foreach ($customXmlFields as $index => $field) {
            foreach ($systemValues as $systemValueKey => $systemValue) {
                if (isset($customXmlFields[$index])) {
                    if (!is_array($customXmlFields[$index])) {
                        $customXmlFields[$index] = (array) $customXmlFields[$index];
                    }
                    $customXmlFields[$index]["value"] = str_replace(
                        $systemValueKey,
                        is_array($systemValue) ? implode(", ", $systemValue) : $systemValue,
                        isset($customXmlFields[$index]["value"]) ? $customXmlFields[$index]["value"] : ''
                    );
                }
            }
        }
        return $customXmlFields;
    }

    private static function replaceFormValues(
        $customXmlFields,
        $values,
        $elements,
        $options
    )
    {
        foreach ($customXmlFields as $index => $field) {
            $customXmlFields[$index]["value"] = LeformFormService::replaceFormValues(
                $customXmlFields[$index]["value"],
                $values,
                $elements,
                $options,
            );
        }
        return $customXmlFields;
    }

    private static function replaceFormValuesOnAllElements(&$elementsToReplace, $allElements, $values, $options)
    {
        foreach ($elementsToReplace as $elementToReplace) {
            if ($elementToReplace->type === "columns") {
                $customXmlFields = $elementToReplace->{"custom-xml-fields"};
                $customXmlFields = is_string($customXmlFields)
                    ? json_decode($customXmlFields, true)
                    : json_decode(json_encode($customXmlFields), true);
                $elementToReplace->{"custom-xml-fields"} = self::replaceFormValues(
                    $customXmlFields,
                    $values,
                    $allElements,
                    $options
                );
                self::replaceFormValuesOnAllElements(
                    $elementToReplace->properties->elements,
                    $allElements,
                    $values,
                    $options
                );
            }
        }
        return $elementsToReplace;
    }

    public static function extractObjectOrArrayAsArray($variable, $attrName)
    {
        $returnValue = null;
        if (
            is_object($variable)
            && property_exists($variable, $attrName)
        ) {
            $returnValue = $variable->{$attrName};
        } else if (
            is_array($variable)
            && array_key_exists($attrName, $variable)
        ) {
            $returnValue = $variable[$attrName];
        }

        if (is_string($returnValue)) {
            return json_decode($returnValue, true);
        } else {
            return (array) $returnValue;
        }
    }

    private static function extractCustomXmlFields($element)
    {
        return XMLController::extractObjectOrArrayAsArray(
            $element,
            "custom-xml-fields"
        );
    }

    private static function isElementXMLDisplayable($element, $options)
    {
        $res = in_array($element->type, XMLController::$acceptedFields)  &&
            !(isset($element->{'xml-field-not-exported'}) && $element->{'xml-field-not-exported'} === 'on');

        if(isset(self::$form_object) && in_array($element->type, XMLController::$checkVisibility)) {
            $res = $res && (
                self::$form_object->is_element_visible($element->id) ||
                $options['xml-hide-hidden-fields'] == 'off'
            )? true : false;
            // dd(self::$form_object->is_element_visible($element->id));
        }
        return $res;
    }

    private static function buildXMLElementList(
        $elements,
        $predefinedValues,
        $values,
        $submittionTime,
        $form,
        $formActiveColumn
    )
    {
        $allVariables = evo_get_all_variables($predefinedValues);
        $XMLElements = [];
        foreach ($elements as $element) {
            if (XMLController::isElementXMLDisplayable($element, (array) $form["options"])) {
                $xmlElement = XMLController::buildXMLElement(
                    $element,
                    $allVariables,
                    $values,
                    $submittionTime,
                    $form,
                    $formActiveColumn
                );
                if ($xmlElement) {
                    $XMLElements[] = $xmlElement;
                }
            }
        }
        return $XMLElements;
    }

    private static function mapColumnElementsToColumn($submittionTime, $element, $values, $predefinedValues, $form, $formActiveColumn)
    {
        $allVariables = evo_get_all_variables($predefinedValues);
        $columns = [];
        foreach ($element->properties->elements as $colElement) {
            if (XMLController::isElementXMLDisplayable($colElement, (array) $form["options"])) {
                $parentCol = $colElement->{"_parent-col"};
                if (!array_key_exists($parentCol, $columns)) {
                    $columns[$parentCol] = [];
                }
                $element_value = "";
                if (property_exists($values, $colElement->id)) {
                    $element_value = $values->{$colElement->id};
                }
                $colElement->{"custom-xml-fields"} = XMLController::replaceSystemVariables(
                    $submittionTime,
                    XMLController::extractCustomXmlFields($colElement),
                    $element_value,
                    $colElement->type
                );

                $colElement->{"custom-xml-fields"} = XMLController::replaceCustomXmlFieldsWithValues(
                    $colElement->{"custom-xml-fields"},
                    $allVariables
                );

                /*
                $colElement->{"custom-xml-fields"} = array_merge(
                    XMLController::extractCustomXmlFields($element),
                    XMLController::extractCustomXmlFields($colElement),
                );
                */
                $colElement->{"form-custom-xml-fields"} = XMLController::extractObjectOrArrayAsArray(
                    $element,
                    "form-custom-xml-fields"
                );
                // $colElement->{"custom-xml-fields"} = array_merge(
                //     XMLController::extractObjectOrArrayAsArray($element, "form-custom-xml-fields"),
                //     XMLController::extractCustomXmlFields($colElement),
                // );
                $colElement->{"custom-xml-fields"} = XMLController::extractCustomXmlFields($colElement);
                $colElement->{"xml-field-names"} = array_merge(
                    (array) XMLController::extractObjectOrArrayAsArray(
                        (array) $form["options"],
                        "xml-field-names"
                    ),
                    (array) XMLController::extractObjectOrArrayAsArray(
                        $colElement,
                        "xml-field-names"
                    )
                );
                $colElement->{"xml-field-names-active"} = self::mergeActiveColumns($formActiveColumn,  (array) XMLController::extractObjectOrArrayAsArray(
                    $colElement,
                    "xml-field-names-active"
                ));

                $columns[$parentCol][] = $colElement;
            }
        }
        return $columns;
    }

    private static function buildXMLElement(
        $element,
        $predefinedValues,
        $values,
        $submittionTime,
        $form,
        $formActiveColumn
    )
    {
        $allVariables = evo_get_all_variables($predefinedValues);
        $type = "single";

        if ($element->type === "columns") {
            if ($element->{"has-dynamic-values"} === "on") {
                $type = "dynamic";
            } else {
                $type = "columns";
            }
        } else if($element->type === "repeater-input") {

            $type = "repeater";
        }

        switch ($type) {
            case "single": {
                    $key = "";
                    if (property_exists($element, "label")) {
                        $key = replaceWithPredefinedValues(
                            $element->label,
                            $allVariables
                        );
                    } else {
                        $key = replaceWithPredefinedValues(
                            $element->name,
                            $allVariables
                        );
                    }

                    $value = "";
                    if (property_exists($values, $element->id)) {
                        $value = $values->{$element->id};
                    }
                    switch ($element->type) {
                        case "signature":
                            $value = $value === "" ? 0 : 1;
                            break;
                        case "date":
                            $value = LeformFormService::getDateValue($form["options"], $element, $value);
                            break;
                        default:
                            switch (gettype($value)) {
                                case "array":
                                    $value = implode(", ", $value);
                                    break;
                                case "object":
                                    // this will most likely never happen
                                    $value = $value;
                                    break;
                                default:
                                    $value = $value;
                                    break;
                            }
                            break;
                    }

                    return [
                        "xmlType" => $type,
                        "key" => $key,
                        "value" => $value,
                        // "value_max" => ($element->type == "textarea") ? "" : "",
                        "value_max" => "", // only number inputs have max value
                        "value_default" => in_array(
                            $element->type,
                            ["text", "textarea", "hidden"]
                        ) ? replaceWithPredefinedValues($element->default, $allVariables) : "",

                        "type" => $element->type,
                        "customFields" => is_string($element->{"custom-xml-fields"})
                            ? json_decode($element->{"custom-xml-fields"})
                            : $element->{"custom-xml-fields"},
                        "fieldNames" => XMLController::extractObjectOrArrayAsArray(
                            $element,
                            "xml-field-names"
                        ),
                        "fieldNamesActive" => self::mergeActiveColumns($formActiveColumn, (array) XMLController::extractObjectOrArrayAsArray(
                            $element,
                            "xml-field-names-active"
                        ))
                    ];
                }
            case "repeater": {

                    $fields = [];
                    $repeaterValues = isset($values->{$element->id}) && is_array($values->{$element->id}) ? $values->{$element->id} : [[]];
                    foreach($repeaterValues as $rowValue) {
                        foreach($element->fields as $key=>$field) {
                            $fields[] =[
                                "xmlType" => "single",
                                "key" => $field->name,
                                "value" => isset($rowValue[$key]) ? $rowValue[$key] : (!empty($field->defaultValue) ? $field->defaultValue : ""),
                                "value_max" => "",
                                "value_default" => "",
                                "type" => $field->type,
                                "customFields" => [],
                                "fieldNames" => [],
                                "fieldNamesActive" => [
                                    "key" => "on",
                                    "value" => "on",
                                    "value_default" => "on",
                                    "type" => "on",
                                ],
                            ];
                        }
                    }
                    return [
                        "xmlType" => $type,
                        "fields" => [$fields],
                        "customFields" => is_string($element->{"custom-xml-fields"})
                        ? json_decode($element->{"custom-xml-fields"})
                        : $element->{"custom-xml-fields"},
                        "fieldNames" => XMLController::extractObjectOrArrayAsArray(
                            $element,
                            "xml-field-names"
                        ),
                        "fieldNamesActive" => self::mergeActiveColumns($formActiveColumn, (array) XMLController::extractObjectOrArrayAsArray(
                            $element,
                            "xml-field-names-active"
                        ))
                    ];
                }
            case "columns": {
                    $columns = XMLController::mapColumnElementsToColumn($submittionTime, $element, $values, $allVariables, $form, $formActiveColumn);

                    foreach ($columns as $columnIndex => $column) {
                        $columns[$columnIndex] = XMLController::buildXMLElementList(
                            $column,
                            $allVariables,
                            $values,
                            $submittionTime,
                            $form,
                            $formActiveColumn
                        );
                    }

                    return [
                        "xmlType" => $type,
                        "columns" => $columns,
                        "customFields" => is_string($element->{"custom-xml-fields"})
                            ? json_decode($element->{"custom-xml-fields"})
                            : $element->{"custom-xml-fields"},
                        "fieldNames" => XMLController::extractObjectOrArrayAsArray(
                            $element,
                            "xml-field-names"
                        ),
                        "fieldNamesActive" => self::mergeActiveColumns($formActiveColumn, (array) XMLController::extractObjectOrArrayAsArray(
                            $element,
                            "xml-field-names-active"
                        ))
                    ];
                }
            case "dynamic": {
                    $dynamicValueKey = $element->{"dynamic-value"};
                    $dynamicValueNumber = $element->{"dynamic-value-index"};
                    $dynamicValueIndex = $dynamicValueNumber - 1;

                    if (
                        !array_key_exists($dynamicValueKey, $allVariables)
                        || !array_key_exists(
                            $dynamicValueIndex,
                            $allVariables[$dynamicValueKey]
                        )
                    ) {
                        return null;
                    }

                    $columns = XMLController::mapColumnElementsToColumn($submittionTime, $element, $values, $allVariables[$dynamicValueKey][$dynamicValueIndex], $form, $formActiveColumn);

                    foreach ($columns as $columnIndex => $column) {
                        $columns[$columnIndex] = XMLController::buildXMLElementList(
                            $column,
                            $allVariables[$dynamicValueKey][$dynamicValueIndex],
                            $values,
                            $submittionTime,
                            $form,
                            $formActiveColumn
                        );
                    }

                    return [
                        "xmlType" => $type,
                        "key" => $dynamicValueKey,
                        "index" => $dynamicValueIndex,
                        "columns" => $columns,
                        // "fieldNames" => $element->{"xml-field-names"},
                        "customFields" => is_string($element->{"custom-xml-fields"})
                            ? json_decode($element->{"custom-xml-fields"})
                            : $element->{"custom-xml-fields"},
                        "fieldNames" => XMLController::extractObjectOrArrayAsArray(
                            $element,
                            "xml-field-names"
                        ),
                        "fieldNamesActive" => self::mergeActiveColumns($formActiveColumn, (array) XMLController::extractObjectOrArrayAsArray(
                            $element,
                            "xml-field-names-active"
                        ))
                    ];
                }
        }
    }

    public static function mergeActiveColumns($formActiveColumn, $elementActiveColumn)
    {
        $activeColumns = [];
        foreach (LeformService::$defaultXmlFieldNames as $tag => $fieldName) {
            if (isset($formActiveColumn[$tag]) && $formActiveColumn[$tag] === 'off') {
                $activeColumns[$tag] = 'off';
            } else if (isset($elementActiveColumn[$tag]) && $elementActiveColumn[$tag] === 'off') {
                $activeColumns[$tag] = 'off';
            } else {
                $activeColumns[$tag] = 'on';
            }
        }
        return $activeColumns;
    }

    public static function createXMLForEntry($companyId, $recordId, $form_object)
    {
        self::$form_object = $form_object;

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

        $submittionTime = $record->created;

        $form["options"]["custom-xml-fields"] = XMLController::replaceSystemVariables(
            $submittionTime,
            XMLController::extractCustomXmlFields($form["options"]),
            null,
            null
        );
        $form["options"]["custom-xml-fields"] = XMLController::replaceFormValues(
            XMLController::extractCustomXmlFields($form["options"]),
            json_decode($record["fields"], true),
            LeformFormService::getFormElements($record->form->toArray()),
            $form["options"]
        );
        $form["options"]["custom-xml-fields"] = XMLController::replaceCustomXmlFieldsWithValues(
            XMLController::extractCustomXmlFields($form["options"]),
            $allVariables
        );

        $values = json_decode($record->fields);
        $elements = [];
        $formActiveColumn = (array) XMLController::extractObjectOrArrayAsArray(
            $form["options"],
            "xml-field-names-active"
        );

        $allElements = array_merge([], $form["elements"]);
        $form["elements"] = self::replaceFormValuesOnAllElements(
            $form["elements"],
            LeformFormService::getFormElements($record->form->toArray()),
            json_decode($record["fields"], true),
            $form["options"]
        );

        $orderedElements = FormService::getElementsSortedByOrder($form, "xml-order");

        foreach ($orderedElements as $element) {
            if (XMLController::isElementXMLDisplayable($element, (array) $form["options"])) {
                $element->{"form-custom-xml-fields"} = XMLController::extractCustomXmlFields($form["options"]);
                // $element->{"custom-xml-fields"} = array_merge(
                //     // XMLController::extractCustomXmlFields($form["options"]),
                //     XMLController::extractCustomXmlFields($element)
                // );
                $element->{"custom-xml-fields"} = XMLController::extractCustomXmlFields($element);
                $element_value = "";
                if (property_exists($values, $element->id)) {
                    $element_value = $values->{$element->id};
                }
                $element->{"custom-xml-fields"} = XMLController::replaceSystemVariables(
                    $submittionTime,
                    XMLController::extractCustomXmlFields($element),
                    $element_value,
                    $element->type,
                );
                $type = "single";

                if ($element->type === "columns") {
                    if ($element->{"has-dynamic-values"} === "on") {
                        $type = "dynamic";
                    } else {
                        $type = "columns";
                    }
                } else if($element->type === "repeater-input") {
                    $type = "repeater";
                }
                if ($type === 'dynamic') {
                    $dynamicValueKey = $element->{"dynamic-value"};
                    $dynamicValueNumber = $element->{"dynamic-value-index"};
                    $dynamicValueIndex = $dynamicValueNumber - 1;
                    if (
                        array_key_exists($dynamicValueKey, $allVariables) && array_key_exists(
                            $dynamicValueIndex,
                            $allVariables[$dynamicValueKey]
                        )
                    ) {
                        $element->{"custom-xml-fields"} = XMLController::replaceCustomXmlFieldsWithValues(
                            $element->{"custom-xml-fields"},
                            $allVariables[$dynamicValueKey][$dynamicValueIndex]
                        );
                    } else {
                        $element->{"custom-xml-fields"} = XMLController::replaceCustomXmlFieldsWithValues(
                            $element->{"custom-xml-fields"},
                            $allVariables
                        );
                    }
                } else {
                    $element->{"custom-xml-fields"} = XMLController::replaceCustomXmlFieldsWithValues(
                        $element->{"custom-xml-fields"},
                        $allVariables
                    );
                }

                $element->{"xml-field-names"} = array_merge(
                    (array) XMLController::extractObjectOrArrayAsArray(
                        $form["options"],
                        "xml-field-names"
                    ),
                    (array) XMLController::extractObjectOrArrayAsArray(
                        $element,
                        "xml-field-names"
                    )
                );

                $element->{"xml-field-names-active"} = self::mergeActiveColumns($formActiveColumn, (array) XMLController::extractObjectOrArrayAsArray(
                    $element,
                    "xml-field-names-active"
                ));
                $elements[] = $element;
            }
        }

        $xmlData = XMLController::buildXMLElementList(
            $elements,
            $allVariables,
            $values,
            $submittionTime,
            $form,
            $formActiveColumn
        );
        $xml = ArrayToXml::convert(
            $xmlData,
            $form,
            $record->dynamic_form_name_with_values,
            XMLController::extractCustomXmlFields($form["options"])
        );

        return $xml;
    }

    public static function downloadXMLFile(Request $request, $recordId)
    {
        $record = Record::firstWhere("id", $recordId);
        if (
            !isset($record) || !isset($record->xml_file_name) || is_null($record->xml_file_name) ||
            !Storage::disk('private')->exists($record->xml_file_name)
        ) {
            return response(__("File not found"), 404);
        }
        return Storage::disk('private')->download($record->xml_file_name);
        // $companyId = $request->user()->company_id;

        // $record = Record::with("form")
        //     ->where("deleted", 0)
        //     ->where("id", $recordId)
        //     ->first();
        // $options = json_decode($record->form->options, true);

        // if ($options["generate-xml-on-save"] != "on") {
        //     return response(__("XML is not allowed for this form"), 404);
        // }

        // $xml = XMLController::createXMLForEntry($companyId, $recordId);
        // $company = Company::firstWhere("id", $companyId);
        // $fullFileName = self::generateXMLFileName(
        //     $company,
        //     $record->form,
        //     $record,
        //     false,
        // );
        // $fileName = substr(
        //     $fullFileName,
        //     strrpos($fullFileName, '/') + 1,
        // );

        // return response()->streamDownload(function () use ($xml) {
        //     echo $xml;
        // }, $fileName);
    }



    public static function downloadXMLFiles(Request $request)
    {
        $time = time();
        $fileName = "entries-xml-$time.zip";
        if($request->has('id') && is_array($request->get('id'))) {
            $recordIds = $request->get('id');
            $records = Record::whereIn("id", $recordIds)->get();
        } else if($request->has('start') && $request->has('end')) {
            $start = $request->get('start');
            $end = $request->get('end');
            $from = date('Y-m-d', strtotime($start));
            $to = date('Y-m-d', strtotime($end));
            $records = Record::whereDate("created_at", '>=', $from)->whereDate("created_at", '<=', $to)->get();
        } else {
            return response()->download(public_path("empty.zip"), $fileName);   
        }
        if(!(isset($records)&& count($records) > 0)) {
            return response()->download(public_path("empty.zip"), $fileName);   
        }
        $zip      = new ZipArchive;
        $allNames = [];
        $res = $zip->open(public_path($fileName), ZipArchive::CREATE | ZipArchive::OVERWRITE);
        if ( $res) {
            foreach ($records as $record) {
                if (!(
                    !isset($record) || !isset($record->xml_file_name) || is_null($record->xml_file_name) ||
                    !Storage::disk('private')->exists($record->xml_file_name)
                )) {
                    $path =  $record->xml_file_name;
                    $relativeName = basename($path);
                    $i = 0;
                    while(isset($allNames[$relativeName])) {
                        $relativeName .= date("Y.m.d His");
                        if($i > 0) {
                            $relativeName .= "-$i";
                        }
                        $i+=1;
                    }
                    $allNames[$relativeName] = true;
                   $zip->addFile(Storage::disk('private')->path($path), $relativeName);
                }
            }
        }
        if($zip->numFiles === 0) {
            return response()->download(public_path("empty.zip"), $fileName);
        }
        $zip->close();

        return response()->download(public_path($fileName), $fileName)->deleteFileAfterSend(true);
    }

    public static function generateXMLFileName($company, $form, $entry, $withSubmittionTime = true)
    {
        $submitionTime = $withSubmittionTime ? $entry->created : time();

        $fileName = $company->company_name . "/"
            . $form["name"] . "/"
            . ($entry->dynamic_form_name_with_values
                ? $entry->dynamic_form_name_with_values . "/"
                : "")
            . date("Y", $submitionTime) . "/";

        $formOptions = json_decode($form->options, true);
        $hasCustomFileName = (is_array($formOptions)
            && array_key_exists("has-xml-custom-file-name", $formOptions) &&
            $formOptions["has-xml-custom-file-name"] == "on");
        $fileNameExpression = $hasCustomFileName  ? $formOptions["xml-custom-file-name"] : "";

        if ($fileNameExpression !== "") {
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
                . ($entry->dynamic_form_name_with_values
                    ? $entry->dynamic_form_name_with_values . "_"
                    : "")
                . date("Ymd_His", $submitionTime) . "_"
                . Faker::create()->numerify(str_repeat("#", 7));
        }

        return $fileName . ".xml";
    }

    public static function saveXMLFile($fileName, $xml)
    {
        Storage::disk("private")->put($fileName, $xml);
    }
}
