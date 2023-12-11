<?php

namespace App\Service;

use App\Http\Controllers\XMLController;
use DOMDocument;
use SimpleXMLElement;
use App\Service\LeformService;

class ArrayToXml
{
    public static function getNameOrDefault($arr, $name)
    {
        if (array_key_exists($name, $arr)) {
            return $arr[$name];
        } else {
            return LeformService::$defaultXmlFieldNames[$name];
        }
    }

    public static function buildField($xmlField, $field, $hideAttributes = false)
    {
        switch ($field["xmlType"]) {
            case "single": {
                    $values = ["key", "value", "value_max", "value_default", "type"];
                    $fieldNamesActive =  is_array($field['fieldNamesActive']) ? $field['fieldNamesActive'] : [];
                    foreach ($field["customFields"] as $customField) {
                        $customKey = $customField["name"];
                        $xmlField->$customKey = $customField["value"];
                    }
                    foreach ($values as $value) {
                        $key = ArrayToXml::getNameOrDefault(
                            $field["fieldNames"],
                            $value
                        );
                        if (!(isset($fieldNamesActive[$value]) && $fieldNamesActive[$value] === 'off')) {
                            $xmlField->$key = $field[$value];
                        }
                    }
                    break;
                }
            case "columns": {
                    foreach ($field["customFields"] as $customField) {
                        $customKey = $customField["name"];
                        $xmlField->$customKey = $customField["value"];
                    }
                    foreach ($field["columns"] as $columnNumber => $column) {
                        // $columnXml = $xmlField->addChild("column_" . $columnNumber);
                        ArrayToXml::buildFieldList($xmlField, $column, $hideAttributes);
                    }
                    break;
                }
            case "repeater": {
                    foreach ($field["customFields"] as $customField) {
                        $customKey = $customField["name"];
                        $xmlField->$customKey = $customField["value"];
                    }
                    foreach ($field["fields"] as $row) {
                        ArrayToXml::buildFieldList($xmlField, $row, $hideAttributes);
                        break;
                    }
                    break;
                }                
                
            case "dynamic": {
                    foreach ($field["customFields"] as $customField) {
                        $customKey = $customField["name"];
                        $xmlField->$customKey = $customField["value"];
                    }
                    foreach ($field["columns"] as $column) {
                        ArrayToXml::buildFieldList($xmlField, $column, $hideAttributes);
                    }
                    break;
                }
        }
    }

    public static function buildFieldList($parent, $fields, $hideAttributes = false)
    {
        foreach ($fields as $field) {
            $xmlType = $field["xmlType"];
            $isDynamic = ($xmlType === "dynamic");
            $tag_key = "tag";
            $fieldName = ArrayToXml::getNameOrDefault($field["fieldNames"], $tag_key);
            $fieldNamesActive =  is_array($field['fieldNamesActive']) ? $field['fieldNamesActive'] : [];
            if (!(isset($fieldNamesActive[$tag_key]) && $fieldNamesActive[$tag_key] === 'off')) {
                $xmlField = $parent->addChild($fieldName);
                if (!$hideAttributes) {
                    $xmlField->addAttribute("type", $xmlType);
                    if ($isDynamic) {
                        $xmlField->addAttribute("key", $field["key"]);
                        $xmlField->addAttribute("index", $field["index"]);
                    }
                }
                ArrayToXml::buildField($xmlField, $field, $hideAttributes);
            } else {
                ArrayToXml::buildField($parent, $field, $hideAttributes);
            }
        }
    }

    public static function convert($array, $form, $dynamicName = "", $customFields)
    {
        $customFormName = (array) XMLController::extractObjectOrArrayAsArray(
            (array) $form["options"],
            "xml-field-names"
        );
        $hideAttributes = (((array)$form["options"])["hide-xml-element-attr"] === "on") ? true : false;
        $formName = "form";
        if (isset($customFormName['form']) && !empty($customFormName['form'])) {
            $formName = $customFormName['form'];
        }
        $xml = new SimpleXMLElement("<$formName />");
        if (!$hideAttributes) {
            $xml->addAttribute("name", $form["name"]);
            $xml->addAttribute("name_dynamic", $dynamicName);
            $xml->addAttribute("id", $form["id"]);
        }

        if (isset($customFields) && is_array($customFields)) {
            foreach ($customFields as $customField) {
                $customKey = $customField["name"];
                $xml->$customKey = $customField["value"];
            }
        }
        ArrayToXml::buildFieldList($xml, $array, $hideAttributes);

        $dom_output = new DOMDocument("1.0");
        $dom_sxe = dom_import_simplexml($xml);
        $dom_sxe = $dom_output->importNode($dom_sxe, true);
        $dom_sxe = $dom_output->appendChild($dom_sxe);
        $dom_output->preserveWhiteSpace = false;
        $dom_output->formatOutput = true;

        return $dom_output->saveXML($dom_output, LIBXML_NOEMPTYTAG);
    }
}