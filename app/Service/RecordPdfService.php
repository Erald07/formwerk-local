<?php

namespace App\Service;

use App\Helpers\PdfTemplate;
use App\Models\Record;
use App\Models\Form;
use App\Service\LeformFormService;
use Barryvdh\Snappy\Facades\SnappyPdf;
use Dompdf\Dompdf;
use Illuminate\Support\Facades\File;
use App\Models\Webfont;
use VerumConsilium\Browsershot\Facades\PDF;
use Spatie\Browsershot\Browsershot;
use Faker\Factory as Faker;

class RecordPdfService
{
    public static function getWebfonts($form)
    {
        $stylesToCheck = [
            "text-style",
            "label-text-style",
            "description-text-style",
            "required-text-style",
            "input-text-style",
            "input-hover-text-style",
            "input-focus-text-style",
            "button-text-style",
            "button-hover-text-style",
            "button-active-text-style",
            "imageselect-text-style",
            "tile-text-style",
            "tile-hover-text-style",
            "tile-selected-text-style",
            "error-text-style"
        ];

        $webfonts = [];
        foreach ($stylesToCheck as $key) {
            $styleName = $key . "-family";
            if (
                array_key_exists($styleName, $form["options"])
                && $form["options"][$styleName] !== ""
            ) {
                $webfonts[] = $form["options"][$styleName];
            }
        }

        $style = "";
        if (!empty($webfonts)) {
            $webfonts = array_unique($webfonts);
            $esc_array = [];
            foreach ($webfonts as $array_value) {
                $esc_array[] = $array_value;
            }
            $webfonts_array = Webfont::whereIn('family', $esc_array)
                ->where('deleted', 0)
                ->get();
            if (!empty($webfonts_array)) {
                $families = [];
                $subsets = [];
                foreach ($webfonts_array as $webfont) {
                    $families[] = str_replace(
                        ' ',
                        '+',
                        $webfont['family']
                    ) . ':' . $webfont['variants'];
                    $webfont_subsets = explode(',', $webfont['subsets']);
                    if (!empty($webfont_subsets) && is_array($webfont_subsets)) {
                        $subsets = array_merge($subsets, $webfont_subsets);
                    }
                }
                $subsets = array_unique($subsets);
                $query = '?family=' . implode('|', $families);
                if (!empty($subsets)) {
                    $query .= '&subset=' . implode(',', $subsets);
                }
                $style = '<link href="https://fonts.googleapis.com/css' . $query . '" rel="stylesheet" type="text/css">' . $style;
            }
        }
        return $style;
    }

    public static function decodeForm($form)
    {
        $form["options"] = json_decode($form["options"], true);
        $form["elements"] = json_decode($form["elements"], true);
        foreach ($form["elements"] as $index => $element) {
            $form["elements"][$index] = json_decode($element, true);
        }
        return $form;
    }

    public static function decodeRecord($record)
    {
        $record["fields"] = json_decode($record["fields"], true);
        return $record;
    }

    public static function getDecodedForm($formId)
    {
        $form = Form::firstWhere("id", $formId);
        if (!$form) {
            return null;
        }
        return RecordPdfService::decodeForm($form->toArray());
    }

    public static function getDecodedRecord($recordId)
    {
        $record = Record::firstWhere("id", $recordId);
        if (!$record) {
            return null;
        }
        return RecordPdfService::decodeRecord($record->toArray());
    }

    public static function generateRecordPdfName($record, $form)
    {
        $options = is_array($form["options"]) ? $form["options"] : json_decode($form["options"], true);
        $hasCustomName = isset($options["has-custom-pdf-filename"]) && $options["has-custom-pdf-filename"] !== "";
        $customName = $hasCustomName && isset($options["custom-pdf-filename"]) ? trim($options["custom-pdf-filename"]) : '';
        if ($hasCustomName && $customName) {
            $predefinedValues = [];
            if (
                !empty($record["predefined_values"])
                && !is_null($record["predefined_values"])
            ) {
                $predefinedValues = json_decode($record["predefined_values"], true);
                if (!$predefinedValues) {
                    $predefinedValues = [];
                }
            }
            $allVariables = evo_get_all_variables($predefinedValues);
            $time = time();
            $systemValues = [
                "{{fw_id}}" => Faker::create()->numerify(str_repeat("#", 12)),
                "{{fw_yyyymmdd}}" => date("Ymd", $time),
                "{{fw_yyyymmdd_hhii}}" => date("Ymd_Hi", $time),
                "{{fw_yyyymmdd_hhiiss}}" => date("Ymd_His", $time),
                "{{fw_random_5}}" => Faker::create()->numerify("#####"),
            ];
            $formValues = is_array($record["fields"]) ? $record["fields"] : json_decode($record["fields"], true);

            $customName = LeformFormService::replaceFormValues(
                $customName,
                $formValues,
                LeformFormService::getFormElements($form),
                $options
            );
            foreach ($systemValues as $systemValueKey => $systemValue) {
                $customName = str_replace($systemValueKey, $systemValue, $customName);
            }
            $customName = replaceWithPredefinedValues(
                $customName,
                $allVariables
            );
            return $customName . ".pdf";
        } else {
            return $form["name"] . "-" . $record["id"] . ".pdf";
        }
    }

    public static function generateRecordPdf($record, $form, $mpdfDestination = "S")
    {
        $options = is_array($form["options"]) ? $form["options"] : json_decode($form["options"], true);
        $use_pupetter = isset($options["use-pupeteer"]) && $options["use-pupeteer"] === "on";
        $is_pdf = true;
        // $is_pdf = false;
        $predefinedValues = [];
        if (
            !empty($record["predefined_values"])
            && !is_null($record["predefined_values"])
        ) {
            $predefinedValues = json_decode($record["predefined_values"], true);
            if (!$predefinedValues) {
                $predefinedValues = [];
            }
        }
        $fs = new FormService($form, $record["fields"], $is_pdf, $predefinedValues);
        $res = $fs->getFormObject();
        $res["predefinedValues"] = $predefinedValues;
        $base_path = $is_pdf ? getcwd() : "";
        $res['css_files'] = [
            $base_path . ('/css/pdf.css'),
            // $base_path . ('/css/app.css'),
            // $base_path . ('/css/admin.css'),
            // $base_path . ('/css/halfdata-plugin/style.css'),
            // $base_path . ('/css/halfdata-plugin/leform-if.min.css'),
            // $base_path . ('/css/halfdata-plugin/airdatepicker.min.css'),
            // $base_path . ('/css/halfdata-plugin/ion.rangeSlider.min.css')
        ];
        $res['webfont_link'] = RecordPdfService::getWebfonts($form);
        $res['record'] = $record;
        $res['base_path'] = $base_path;
        $filename = RecordPdfService::generateRecordPdfName($record, $form);

        if ($is_pdf) {
            if($use_pupetter) {
                return self::generatePdfWithPupetter($res, $form, $mpdfDestination = "S", $filename);
            }
            return self::generatePdfWithSnappy($res, $form, $mpdfDestination ="S", $filename);
        }

        return view('pdf', $res);
    }
    public static function generatePdfWithPupetter($res, $form, $mpdfDestination = "S", $filename) {

        $html = view('pdf', $res)->render();
        $pdf =  Browsershot::html($html)
            ->showBackground()->waitUntilNetworkIdle()->pdf($filename);
        if (
            $form["options"]['form-background-first-page-file']
            || $form["options"]['form-background-other-page-file']
        ) {
            return PdfTemplate::mergePDF($form["options"], $filename, $pdf);
        }
        return $mpdfDestination === 'S' ? response()->stream(function () use ($pdf) {
            echo $pdf;
        }, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"'
        ]) : $pdf;

    }
    public static function generatePdfWithSnappy($res, $form, $mpdfDestination ="S", $filename)
    {
        $html = view('pdf', $res)->render();
        $pdf = SnappyPdf::loadHTML($html);
        if (
            $form["options"]['form-background-first-page-file']
            || $form["options"]['form-background-other-page-file']
        ) {
            return PdfTemplate::mergePDF($form["options"], $filename, $pdf);
        }
        return $pdf->download($filename);
    }

    public static function generateRecordPdf2($record, $form, $mpdfDestination = "S")
    {
        $pdf = app('dompdf.wrapper');
        // $pdf = new Dompdf();
        // echo json_encode($record["fields"]);
        // exit;
        $is_pdf = true;
        $fs = new FormService($form, $record["fields"], $is_pdf);
        $res = $fs->getFormObject();
        $res["predefinedValues"] = [];
        $res['css_files'] = [];
        $base_path = ""; // $is_pdf ? getcwd() : "";
        $cssFiles = [
            $base_path . ('/css/pdf.css'),
            $base_path . ('/css/admin.css'),
            $base_path . ('/css/halfdata-plugin/style.css'),
            $base_path . ('/css/halfdata-plugin/fontawesome.min.css'),
            $base_path . ('/css/halfdata-plugin/fontawesome-solid.min.css'),
            $base_path . ('/css/halfdata-plugin/leform-if.min.css'),
            $base_path . ('/css/halfdata-plugin/airdatepicker.min.css'),
            $base_path . ('/css/halfdata-plugin/ion.rangeSlider.min.css'),
            $base_path . ('/css/halfdata-plugin/fontawesome-all.min.css')
        ];
        $res['css_files'] = $cssFiles;
        $res['record'] = $record;
        $css = "";
        // foreach ($cssFiles as $css_file) {
        //     $css = File::get(public_path( $css_file));
        //     $res['css_files'][] = $css;// RecordPdfService::minify($css, false);
        // }
        // echo json_encode($res['elements'][1]);
        $pdf->loadView("record-pdf", $res);
        $filename = RecordPdfService::generateRecordPdfName($record, $form);
        // if($form["options"]['form-background-first-page-file'] || $form["options"]['form-background-other-page-file'] ) {
        //     return PdfTemplate::mergePDF($form["options"], $filename, $pdf);
        // }
        return $is_pdf ? $pdf->download($filename) : view("record-pdf", $res);
        // $content = view("record-pdf", $res)->render();
        // $pdf->loadHtml($content, 'UTF8');
        // $pdf->render();
        // return $pdf->stream("exportUsers.pdf", [
        //     "Attachment" => true
        // ]);
    }
    private static function minify($css, $comments)
    {
        // Normalize whitespace
        $css = preg_replace('/\s+/', ' ', $css);

        // Remove comment blocks, everything between /* and */, unless preserved with /*! ... */
        if (!$comments) {
            $css = preg_replace('/\/\*[^\!](.*?)\*\//', '', $css);
        } //if

        // Remove ; before }
        $css = preg_replace('/;(?=\s*})/', '', $css);

        // Remove space after , : ; { } */ >
        $css = preg_replace('/(,|:|;|\{|}|\*\/|>) /', '$1', $css);

        // Remove space before , ; { } ( ) >
        $css = preg_replace('/ (,|;|\{|}|\(|\)|>)/', '$1', $css);

        // Strips leading 0 on decimal values (converts 0.5px into .5px)
        $css = preg_replace('/(:| )0\.([0-9]+)(%|em|ex|px|in|cm|mm|pt|pc)/i', '${1}.${2}${3}', $css);

        // Strips units if value is 0 (converts 0px to 0)
        $css = preg_replace('/(:| )(\.?)0(%|em|ex|px|in|cm|mm|pt|pc)/i', '${1}0', $css);

        // Converts all zeros value into short-hand
        $css = preg_replace('/0 0 0 0/', '0', $css);

        // Shortern 6-character hex color codes to 3-character where possible
        $css = preg_replace('/#([a-f0-9])\\1([a-f0-9])\\2([a-f0-9])\\3/i', '#\1\2\3', $css);

        return trim($css);
    } //minify
}
