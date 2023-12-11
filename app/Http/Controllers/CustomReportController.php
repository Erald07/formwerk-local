<?php

namespace App\Http\Controllers;

use App\Models\Record;
use App\Service\LeformFormService;
use Faker\Factory as Faker;
use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CustomReportController extends Controller
{
    private static function getTimeSystemVariables($time)
    {
        return [
            "{{fw_yyyymmdd}}" => date("Ymd", $time),
            "{{fw_yyyymmdd_hhii}}" => date("Ymd_Hi", $time),
            "{{fw_yyyymmdd_hhiiss}}" => date("Ymd_His", $time),
        ];
    }

    private static function nestedPredefinedValuesReplace(
        $string,
        $predefinedValues
    ) {
        $regex = "/\{\{[A-z1-9-_\[\]]*\}\}/";
        if ($predefinedValues === null) {
            $string = preg_replace($regex, "", $string);
            return $string;
        }
        $allVariables = evo_get_all_variables($predefinedValues);
        $variables = [];
        preg_match_all($regex, $string, $variables);
        if ($variables == false) {
            return $string;
        }
        $variables = $variables[0];
        $allowedTypes = ["boolean", "integer", "double", "string"];
        foreach ($variables as $variable) {
            $path = str_replace("[", ".", $variable);
            $path = str_replace("]", "", $path);
            $path = str_replace("{", "", $path);
            $path = str_replace("}", "", $path);
            $value = Arr::get($allVariables, $path, "");
            if (in_array(gettype($value), $allowedTypes)) {
                $string = str_replace($variable, $value, $string);
            }
        }
        $string = preg_replace($regex, "", $string);
        return $string;
    }

    public static function createReportForEntry($companyId, $recordId)
    {
        $record = Record::with("form")
            ->where("deleted", 0)
            ->where("id", $recordId)
            ->first();

        if (!$record) {
            return response(__("Record not found"), 404);
        } else if ($record["form"]["company_id"] != $companyId) {
            return response(__("Form does not belong to user"), 403);
        }

        $form = $record->form;
        $content = json_decode($form["options"], true)["report-content"];

        $submittionTime = $record->created;

        $timeSystemVariables = self::getTimeSystemVariables($submittionTime);
        $predefinedValues = json_decode($record->{"predefined_values"}, true);
        $allVariables = evo_get_all_variables($predefinedValues);
        // im sorry i ain't donin array joins, moodle should definitely not send system variables
        $allVariables["fw_id"] = Faker::create()->numerify(str_repeat("#", 12));
        $allVariables["fw_yyyymmdd"] = $timeSystemVariables["{{fw_yyyymmdd}}"];
        $allVariables["fw_yyyymmdd_hhii"] = $timeSystemVariables["{{fw_yyyymmdd_hhii}}"];
        $allVariables["fw_yyyymmdd_hhiiss"] = $timeSystemVariables["{{fw_yyyymmdd_hhiiss}}"];
        $allVariables["fw_random_5"] = Faker::create()->numerify("#####");

        $content = LeformFormService::replaceFormValues(
            $content,
            json_decode($record["fields"], true),
            LeformFormService::getFormElements($form),
            json_decode($form["options"], true)
        );

        return self::nestedPredefinedValuesReplace(
            $content,
            $allVariables
        );
    }

    public static function generateFileName($company, $form, $entry, $withSubmittionTime = true)
    {
        $submitionTime = $withSubmittionTime ? $entry->created : time();

        $fileName = $company->company_name . "/"
            . $form["name"] . "/"
            . ($entry->dynamic_form_name_with_values
                ? $entry->dynamic_form_name_with_values . "/"
                : "")
            . date("Y", $submitionTime) . "/";

        $formOptions = json_decode($form->options, true);
        $hasCustomFileName = ($formOptions["has-report-content-custom-file-name"] == "on");
        $fileNameExpression = $formOptions["report-content-custom-file-name"];

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
                . ($entry->dynamic_form_name_with_values
                    ? $entry->dynamic_form_name_with_values . "_"
                    : "")
                . date("Ymd_His", $submitionTime) . "_"
                . Faker::create()->numerify(str_repeat("#", 7));
        }

        if ($formOptions["report-content-extension"] != "") {
            $fileName .= "." . $formOptions["report-content-extension"];
        }

        return $fileName;
    }
    public static function downloadFile(Request $request, $recordId)
    {

        $record = Record::firstWhere("id", $recordId);
        if (!isset($record) || !isset($record->custom_report_file_name) || is_null($record->custom_report_file_name) || !Storage::disk('private')->exists($record->custom_report_file_name)) {
            return response(__("File not found"), 404);
        }
        return Storage::disk('private')->download($record->custom_report_file_name);
    }
}
