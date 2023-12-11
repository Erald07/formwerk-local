<?php

namespace App\View\Components\Emails;

use Illuminate\View\Component;
use Illuminate\Support\Facades\Storage;

use App\Models\Upload;

class FormSubmitted extends Component
{
    public $isTableLayout;

    public $elements;

    public $values;

    public $hasFile;

    public $files;

    public $recordId;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($isTableLayout, $elements, $values, $hasFile, $files = null, $recordId)
    {
        $this->isTableLayout = $isTableLayout;
        $this->elements = $elements;
        $this->values = $values;
        $this->hasFile = $hasFile;
        $this->files = $files;
        $this->recordId = $recordId;
    }

    public function renderValue($field, $value)
    {
        if ($field["type"] === "file") {
            if (
                gettype($value) !== "array"
                || count($value) === 0
            ) {
                return "";
            }

            $files = Upload::whereIn("id", $value)
                ->get();

            $output = "";
            foreach ($files as $file) {
                $filePath = "uploads/$file->filename";
                $fileExists = Storage::disk("public")
                    ->exists($filePath);

                if ($fileExists) {
                    $url = getcwd() . Storage::url($filePath);
                    $output .= "
                        <div>
                            <img src='$url' height='70px' />
                        </div>
                    ";
                }
            }
            return $output;
        } else if ($field["type"] === "signature") {
            if (empty($value)) {
                return "";
            }

            $filePath = str_replace("/storage/", "", $value);
            $fileName = str_replace("signatures/", "", $filePath);
            $signatureFileExists = Storage::disk("public")
                ->exists($filePath);

            if ($signatureFileExists) {
                $url = getcwd() .Storage::url($filePath);
                return "
                    <img src='$url' height='70px' />
                ";
            } else {
                return "";
            }
        } else if ($field["type"] === "repeater-input") {
            return "";
        } else if (gettype($value) === "array") {
            return implode(",", $value);
        } else {
            return $value;
        }
    }

    public function replaceWithPredefinedValues($string, $predefinedValues = [])
    {
        if ($predefinedValues === null) {
            $string = preg_replace("/\{\{[A-z1-9-_]*\}\}/", "", $string);
            return $string;
        }
        $allVariables = evo_get_all_variables($predefinedValues);
        $allowedTypes = ["boolean", "integer", "double", "string"];
        foreach($allVariables as $key => $value) {
            if (in_array(gettype($value), $allowedTypes)) {
                $string = preg_replace("/\{\{$key\}\}/", $value, $string);
            }
        }
        $string = preg_replace("/\{\{[A-z1-9-_]*\}\}/", "", $string);
        return $string;
    }
    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('components.emails.form-submitted');
    }
}
