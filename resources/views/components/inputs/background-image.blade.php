@props([
    'viewOnly' => true,
    'options',
    'element',
    'properties',
    'form_dependencies',
    'predefinedValues' => [],
    'leformOptions',
])
<?php
$arrElement = (array) $element;
$properties = isset($element->properties) ? (array) $element->properties : [];
$masked = isset($leformOptions) && $leformOptions['mask-enable'] == 'on' && array_key_exists('mask-mask', $arrElement) && !empty($arrElement['mask-mask']);

if (isset($arrElement['input-style-size']) && $arrElement['input-style-size'] != '') {
    $element->extra_class .= ' leform-input-' . $arrElement['input-style-size'];
}

$filePath = str_replace("/storage/", "", $element->image);
$fileName = str_replace("select-image-input-images/", "", $filePath);
$fileExists = Storage::disk("public")->exists($filePath);
$fileUrl = "";
if($fileExists) {
$fileUrl = "data:image/jpeg;base64," . base64_encode(file_get_contents(public_path($element->image)));
}
?>
@if ($element->id && !empty($fileUrl))
    <div class="leform-element {{ $element->id }}" data-type="{{ $element->type }}" style="padding: 0!important;">
        <img src="{{ $fileUrl }}" style="max-width: 100%" />
    </div>
@endif
