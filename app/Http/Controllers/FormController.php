<?php

namespace App\Http\Controllers;

use App\Http\Controllers\XMLController;
use App\Http\Controllers\CSVController;
use App\Service\LeformFormService;
use App\Service\RecordPdfService;
use App\Service\WebfontService;
use App\Service\LeformService;
use App\Service\SettingsService;
use App\Models\Webfont;
use App\Models\Preview;
use App\Models\Upload;
use App\Models\Record;
use App\Models\Setting;
use App\Models\Form;
use App\Models\FormBackground;
use App\Models\WebhookSecurityHash;
use App\Models\Company;
use App\Mail\FormSubmitted;
use App\Mail\AfterSubmitEmailAutorespond;
use App\Models\Folder;
use Exception;
use DOMDocument;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;

use Faker\Factory as Faker;
use phpseclib3\Net\SFTP;
use GuzzleHttp;
use Illuminate\Support\Facades\Crypt;

class FormController extends FormHelperController
{
    public function index(Request $request, LeformService $leform, $folder_ids = "")
    {   $folderQuery = Folder::select('*');
        $folderId = null;
        $folderIds = [];
        if(!empty($folder_ids)) {
            $folderIds = explode('-', $folder_ids);
            if(count($folderIds) > 0) {
                $folderId = 1 * end($folderIds);
                 $folderQuery->where('parent_id', $folderId);
            } else {
                $folderQuery->whereNull('parent_id');
            }
        } else {
            $folderQuery->whereNull('parent_id');
        }
        $folders = $folderQuery->get();
        foreach($folders as $folder) {
            $childrenIds = $folder->getChildrenIds();
            $childrenIds[] = $folder->id;
            $folder->forms_count = Form::whereIn('folder_id', $childrenIds)->count();
        }
        $formQuery= Form::with([
            'records' => function ($query) {
                $query->where('deleted', 0);
            }
        ])
            ->leftJoin('companies', 'share_company_id', '=', 'companies.id')
            ->select('laravel_uap_leform_forms.*', 'companies.company_name');
        if($folderId) {
            $formQuery->where('folder_id', $folderId);
        } else {
            $formQuery->whereNull('folder_id');
        }
        $forms = $formQuery->get();
        $breadcrumb = [];
        if(count($folderIds)) {
            $allFolders = Folder::whereIn('id', $folderIds)->get();
            $cFid = "";
            foreach($folderIds as $fid) {
                $f = $allFolders->first(function($item) use($fid) {
                    $fid = 1 * $fid;
                    return $item->id == $fid;
                });
                if (isset($f)) {
                    $b = [
                        'id' => "$cFid$f->id",
                        'name' => "$f->name",
                    ];
                    $breadcrumb[] = $b;
                    $cFid = $b["id"] . "-";
                }
            }
        }
        $parentFolders = Folder::whereNull('parent_id')->get();
        $listOfFolders = [];
        foreach($parentFolders as $f) {
            $listOfFolders[] = [
                'id' => $f->id,
                'name' => $f->name,
            ];
            $listOfFolders = $f->childrenList($listOfFolders);
        }
        return view('forms', [
            'listOfFolders' => json_encode($listOfFolders),
            'forms' => $forms,
            'folders' => $folders,
            'currentFolder' => $folderId,
            'breadcrumb' => $breadcrumb,
            'folder_ids' => !empty($folder_ids) ? "$folder_ids-" : $folder_ids,
            'advancedOptions' => $leform->advancedOptions,
            "frontendTranslations" => __("frontend_translations")
        ]);
    }

    public function useForm(Request $request)
    {
        $callback = '';
        if ($request->has("callback")) {
            header("Content-type: text/javascript");
            $callback = preg_replace(
                '/[^a-zA-Z0-9_]/',
                '',
                $request->param("callback")
            );
        }

        $form_id = $request->input('form-id');
        if ($form_id !== null) {
            $form_id = intval($form_id);
            # $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."leform_forms WHERE deleted = '0' AND id = '".esc_sql($form_id)."'", ARRAY_A);
            $form_details = Form::firstWhere("id", $form_id);
            if (empty($form_details)) {
                $form_id = null;
            }
        }

        if (empty($form_id)) {
            $return_data = [
                'status' => 'ERROR',
                'message' => __('Requested form not found.'),
            ];
            if (!empty($callback)) {
                return $callback . '(' . json_encode($return_data) . ')';
            } else {
                return json_encode($return_data);
            }
        }

        $html = view("use-form", [
            "form" => $form_details,
            "frontendTranslations" => __("frontend_translations")
        ])
            ->render();

        $return_data = [
            'status' => 'OK',
            'html' => $html,
            'form_name' => $form_details['name'],
        ];

        if (!empty($callback)) {
            return $callback . '(' . json_encode($return_data) . ')';
        } else {
            return json_encode($return_data);
        }
    }

    public function create(Request $request, LeformService $leform)
    {
        if (!$request->has("page")) {
            $query = ["page" => "formwerk"];
            $query += $request->query();
            return redirect()->route("create-form", $query);
        }

        $user = $request->user();

        $defaultFormOptions = $leform->getDefaultFormOptions();
        $formId = $request->query("id", null);
        $formOptions = null;
        $formDetails = [];
        $formElements = [];

        if ($request->has("id")) {
            $formDetails = Form::where("id", $formId)
                ->where("deleted", 0)
                ->first();

            if (!$formDetails) {
                return response('Form not found', 404);
            }

            # if the form doesn't belong to the user redirect to creating a new form
            if ($formDetails->company_id != $user->company_id) {
                return redirect()->route("create-form", ["page" => "formwerk"]);
            };

            if (!empty($formDetails)) {
                $formOptions = json_decode($formDetails["options"], true);
            }
        }

        if (!empty($formOptions)) {
            $formOptions = array_merge($defaultFormOptions, $formOptions);
        } else {
            $formOptions = $defaultFormOptions;
        }

        $defaultPageOptions = $leform->getDefaultFormOptions("page");
        $defaultPageConfirmationOptions = $leform->getDefaultFormOptions("page-confirmation");
        $formPages = [array_merge($defaultPageOptions, ["id" => 1, "type" => "page"])];

        if (!empty($formDetails)) {
            $formOptions["name"] = $formDetails["name"];
            $formOptions["active"] = $formDetails["active"] > 0 ? "on" : "off";

            $formPages = json_decode($formDetails["pages"], true);
            if (is_array($formPages)) {
                foreach ($formPages as $key => $pageOptions) {
                    if (is_array($pageOptions)) {
                        if ($pageOptions["type"] == "page") {
                            $pageOptions = array_merge($defaultPageOptions, $pageOptions);
                        } else {
                            $pageOptions = array_merge($defaultPageConfirmationOptions, $pageOptions);
                        }
                        $formPages[$key] = $pageOptions;
                    } else {
                        unset($formPages[$key]);
                    }
                }
                $formPages = array_values($formPages);
            } else {
                $formPages = [array_merge($defaultPageOptions, ["id" => 1])];
            }

            $formElements = json_decode($formDetails["elements"], true);
            if (is_array($formElements)) {
                foreach ($formElements as $key => $formElementRaw) {
                    $elementOptions = json_decode($formElementRaw, true);
                    if (is_array($elementOptions) && array_key_exists("type", $elementOptions)) {
                        $defaultElementOptions = $leform->getDefaultFormOptions($elementOptions["type"]);
                        $elementOptions = array_merge($defaultElementOptions, $elementOptions);
                        $formElements[$key] = json_encode($elementOptions);
                    } else {
                        unset($formElements[$key]);
                    }
                }
                $formElements = array_values($formElements);
            } else {
                $formElements = [];
            }
        }

        $confirmationFound = false;
        foreach ($formPages as $formPage) {
            if ($formPage["id"] == "confirmation") {
                $confirmationFound = true;
                break;
            }
        }
        if (!$confirmationFound) {
            $formPages[] = array_merge(
                $defaultPageConfirmationOptions,
                ["id" => "confirmation", "type" => "page-confirmation", "name" => "Bestätigung"]
            );
            $defaultElementOptions = $leform->getDefaultFormOptions("html");
            $elementOptions = [
                "type" => "html",
                "_parent" => "confirmation",
                "_parent-col" => 0,
                "_seq" => 0,
                "id" => 0,
                "content" => "<h4 style='text-align: center;'>Vielen Dank!</h4><p style='text-align: center;'>Ihre Eingabe wird nun verarbeitet.</p>"
            ];
            $elementOptions = array_merge($defaultElementOptions, $elementOptions);
            $formElements[] = json_encode($elementOptions);
        }

        $webfonts = [];

        foreach (Webfont::get() as $webfont) {
            $webfonts[] = $webfont["family"];
        }

        $webfontService = new WebfontService();

        $customFonts = [];
        foreach (explode("\n", $leform->advancedOptions["custom-fonts"]) as $font) {
            $font = trim(trim($font), "'\"`");
            if (!empty($font)) {
                $customFonts[] = $font;
            }
        }

        $elementPropertiesMeta = $leform->getElementPropertiesMeta();
        $inputKeys = [
            "columns",
            "email",
            "text",
            "textarea",
            "select",
            "checkbox",
            "imageselect",
            "tile",
            "multiselect",
            "radio",
            "matrix",
            "repeater-input",
            "iban-input",
            "date",
            "time",
            "file",
            "star-rating",
            "password",
            "signature",
            "rangeslider",
            "number",
            "numspinner",
            "hidden",
            "button",
            "link-button",
            "html"
        ];
        $translatableStrings = [
            "label",
            "tooltip",
            "caption",
            "date-format-label",
            "time-format-label",
            "locale-label",
            "sections",
            "time-format-options",
            "options",
            "actions",
            "operators",
            "type-values",
        ];

        $translationStrings = [];

        foreach ($elementPropertiesMeta as $elementKey => $element) {
            foreach ($element as $settingKey => $setting) {
                foreach ($translatableStrings as $translatableString) {
                    if (array_key_exists($translatableString, $setting)) {
                        if ($setting["type"] === "sections") {
                            foreach ($setting["sections"] as $sectionKey => $section) {
                                $elementPropertiesMeta[$elementKey][$settingKey]["sections"][$sectionKey]["label"] = __(
                                    $section["label"]
                                );
                            }
                        } else if (gettype($elementPropertiesMeta[$elementKey][$settingKey][$translatableString]) === "array") {
                            foreach ($elementPropertiesMeta[$elementKey][$settingKey][$translatableString] as $nestedTranslatableKey => $nestedTranslatableString) {
                                $elementPropertiesMeta[$elementKey][$settingKey][$translatableString][$nestedTranslatableKey] = __(
                                    $nestedTranslatableString
                                );
                            }
                        } else {
                            $elementPropertiesMeta[$elementKey][$settingKey][$translatableString] = __(
                                $elementPropertiesMeta[$elementKey][$settingKey][$translatableString]
                            );
                        }
                    }
                }
            }
        }

        foreach ($elementPropertiesMeta["settings"]["key-fields"]["placeholder"] as $key => $keyFieldPlaceholder) {
            $elementPropertiesMeta["settings"]["key-fields"]["placeholder"][$key] = __(
                $keyFieldPlaceholder
            );
        }

        $logicRules = [];
        foreach ($leform->logicRules as $ruleName => $rule) {
            $logicRules[$ruleName] = __($rule);
        }

        $filtersMeta = $leform->filtersMeta;
        foreach ($filtersMeta as $filterKey => $filter) {
            if (array_key_exists("label", $filter)) {
                $filtersMeta[$filterKey]["label"] = _($filter["label"]);
            }
            if (array_key_exists("tooltip", $filter)) {
                $filtersMeta[$filterKey]["tooltip"] = _($filter["tooltip"]);
            }
            if (array_key_exists("properties", $filter)) {
                foreach ($filtersMeta[$filterKey]["properties"] as $propertyKey => $property) {
                    if (array_key_exists("label", $property)) {
                        $filtersMeta[$filterKey]["properties"][$propertyKey]["label"] = _(
                            $filtersMeta[$filterKey]["properties"][$propertyKey]["label"]
                        );
                    }
                    if (array_key_exists("tooltip", $property)) {
                        $filtersMeta[$filterKey]["properties"][$propertyKey]["tooltip"] = _(
                            $filtersMeta[$filterKey]["properties"][$propertyKey]["tooltip"]
                        );
                    }
                }
            }
        }

        $validatorsMeta = $leform->validatorsMeta;
        foreach ($validatorsMeta as $validatorKey => $validator) {
            if (array_key_exists("label", $validator)) {
                $validatorsMeta[$validatorKey]["label"] = _($validator["label"]);
            }
            if (array_key_exists("tooltip", $validator)) {
                $validatorsMeta[$validatorKey]["tooltip"] = _($validator["tooltip"]);
            }
            if (array_key_exists("properties", $validator)) {
                foreach ($validator["properties"] as $propertyKey => $propertyValue) {
                    $validatorsMeta[$validatorKey]["properties"][$propertyKey]["label"] = _(
                        $propertyValue["label"]
                    );
                    $validatorsMeta[$validatorKey]["properties"][$propertyKey]["tooltip"] = _(
                        $propertyValue["tooltip"]
                    );
                }
            }
        }

        $predefinedOptions = $leform->predefinedOptions;
        foreach ($predefinedOptions as $optionKey => $optionValue) {
            if (array_key_exists("label", $predefinedOptions[$optionKey])) {
                $predefinedOptions[$optionKey]["label"] = _(
                    $optionValue["label"]
                );
            }
        }

        $viewContex = [
            "formPages" => $formPages,
            "formId" => $formId,
            "toolbarTools" => $leform->toolbarTools,
            "faSolid" => $leform->fontAwesomeIcons["solid"],
            "faRegular" => $leform->fontAwesomeIcons["regular"],
            "faBrands" => $leform->fontAwesomeIcons["brands"],
            "fontAwesomeBasic" => $leform->fontAwesomeIcons["basic"],
            "options" => $leform->options,
            "predefinedOptions" => $predefinedOptions,
            "elementPropertiesMeta" => $elementPropertiesMeta,
            "validatorsMeta" => $validatorsMeta,
            "filtersMeta" => $filtersMeta,
            "confirmationsMeta" => $leform->confirmationsMeta,
            "notificationsMeta" => $leform->notificationsMeta,
            "integrationsMeta" => $leform->integrationsMeta,
            "paymentGatewaysMeta" => $leform->paymentGatewaysMeta,
            "mathMeta" => $leform->mathMeta,
            "logicRules" => $logicRules,
            "formOptions" => $formOptions,
            "formElements" => $formElements,
            "styles" => $leform->get_styles(),
            "webfonts" => $webfonts,
            "localFonts" => $webfontService->local_fonts,
            "customFonts" => $customFonts,
            "autocompleteOptions" => $leform->autocomplete_options,
            "frontendTranslations" => __("frontend_translations")
        ];

        if ($formDetails) {
            $viewContex["shortLink"] = $this->getFormShortLink($formDetails["short_link"]);
            $viewContex["longLink"] = $this->getFormLongLink(
                $formDetails["name"],
                $formDetails["short_link"]
            );
            $viewContex["customCss"] = $formOptions['custom-css'];
        } else {
            $viewContex["shortLink"] = null;
            $viewContex["longLink"] = null;
            $viewContex["customCss"] = "";
        }

        return view("create-form", $viewContex);
    }

    public function store(Request $request, LeformService $leform)
    {
        $user = $request->user();
        $callback = '';
        if ($request->has('callback')) {
            header("Content-type: text/javascript");
            $callback = preg_replace('/[^a-zA-Z0-9_]/', '', $request->callback);
        }
        $folder_id = null;
        if($request->has('folder_id')) {
            $folder_id = intval($request->input('folder_id'));
        }
        $formId = null;
        $formOptions = null;
        $formDetails = [];
        if ($request->has('form-id')) {
            $formId = intval($request->input('form-id'));
            $formDetails = Form::where('id', $formId)
                ->where('deleted', 0)
                ->first();
            if (empty($formDetails)) {
                $formId = null;
            }
        }

        $defaultFormOptions = $leform->getDefaultFormOptions();
        $formOptions = $defaultFormOptions;
        if ($request->has('form-options')) {
            $formOptionsNew = json_decode(base64_decode(trim(stripslashes($request->input('form-options')))), true);

            if (is_array($formOptionsNew) && !empty($formOptionsNew)) {
                $formOptions = array_merge($formOptions, $formOptionsNew);
            } else {
                $returnData = [
                    'status' => 'ERROR',
                    'message' => __('Form Options sent incorrectly. Do not close this page and contact Green Forms author.'),
                ];
                if (!empty($callback)) {
                    return $callback . '(' . json_encode($returnData) . ')';
                } else {
                    return json_encode($returnData);
                }
            }
        } else {
            $returnData = [
                'status' => 'ERROR',
                'message' => __('Form Options sent incorrectly. Do not close this page and contact Green Forms author.'),
            ];
            if (!empty($callback)) {
                return $callback . '(' . json_encode($returnData) . ')';
            } else {
                return json_encode($returnData);
            }
        }
        if (empty($formOptions['name'])) {
            $formOptions['name'] = 'Untitled';
        }

        $formPages = [];
        $defaultPageOptions = $leform->getDefaultFormOptions("page");
        $defaultPageConfirmationOptions = $leform->getDefaultFormOptions("page-confirmation");
        if ($request->has('form-pages') && is_array($request->input('form-pages'))) {
            foreach ($request->input('form-pages') as $encodedPage) {
                $pageOptions = json_decode(base64_decode(trim(stripslashes($encodedPage))), true);
                if (is_array($pageOptions)) {
                    if (!array_key_exists('type', $pageOptions)) {
                        $pageOptions['type'] = 'page';
                    }
                    if ($pageOptions['type'] == 'page') {
                        $pageOptions = array_merge($defaultPageOptions, $pageOptions);
                    } else {
                        $pageOptions = array_merge($defaultPageConfirmationOptions, $pageOptions);
                    }
                    $formPages[] = $pageOptions;
                }
            }
        }
        if (empty($formPages)) {
            $returnData = [
                'status' => 'ERROR',
                'message' => __('Form Pages sent incorrectly. Do not close this page and contact Green Forms author.'),
            ];
            if (!empty($callback)) {
                return $callback . '(' . json_encode($returnData) . ')';
            } else {
                return json_encode($returnData);
            }
        }

        $formElements = [];
        if (
            $request->has('form-elements') &&
            is_array($request->input('form-elements'))
        ) {
            foreach ($request->input('form-elements') as $encodedElement) {
                $elementOptions = json_decode(base64_decode(trim(stripslashes($encodedElement))), true);
                if (is_array($elementOptions) && array_key_exists('type', $elementOptions)) {
                    if ($elementOptions['type'] == 'signature') {
                        $formOptions['cross-domain'] = 'off';
                    }
                    $defaultElementOptions = $leform->getDefaultFormOptions($elementOptions['type']);
                    $elementOptions = array_merge($defaultElementOptions, $elementOptions);
                    $formElements[] = json_encode($elementOptions);
                }
            }
        }
        if (empty($formElements)) {
            $returnData = [
                'status' => 'ERROR',
                'message' => __('Form Elements are empty or sent incorrectly. Do not close this page and contact Green Forms author.'),
            ];
            if (!empty($callback)) {
                return $callback . '(' . json_encode($returnData) . ')';
            } else {
                return json_encode($returnData);
            }
        }

        $dynamicNameValues = $this->getFormNameDynamicValues($formOptions);

        if (empty($formId)) {
            if ($request->input('action') != 'leform-form-preview') {
                $form = new Form;
                $form->name = $formOptions['name'];
                $form->options = json_encode($formOptions);
                $form->pages = json_encode($formPages);
                $form->elements = json_encode($formElements);
                $form->cache_time = 0;
                $form->active = $formOptions['active'] == 'on' ? 1 : 0;
                $form->created = time();
                $form->modified = time();
                $form->deleted = 0;
                $form->user_id = $user->id;
                $form->short_link = Faker::create()->bothify('???##');
                $form->dynamic_name_values = json_encode($dynamicNameValues);
                if (!is_null($folder_id)) {
                    $form->folder_id = $folder_id;
                }
                $form->save();
                $formId = $form->id;

                FormBackground::whereNull('form_id')
                    ->whereIn('filename', [
                        $formOptions['form-background-first-page-file'],
                        $formOptions['form-background-other-page-file'],
                    ])
                    ->update(['form_id' => $formId]);
            }
        } else {
            if ($request->input('action') != 'leform-form-preview') {
                $form = Form::where('id', $formId)
                    ->where('deleted', 0)
                    ->first();

                $form->name = $formOptions['name'];
                $form->options = json_encode($formOptions);

                $form->pages = json_encode($formPages);
                $form->elements = json_encode($formElements);
                $form->cache_time = 0;
                $form->active = $formOptions['active'] == 'on' ? 1 : 0;
                $form->modified = time();
                $form->dynamic_name_values = json_encode($dynamicNameValues);
                if(!is_null($folder_id)) {
                    $form->folder_id = $folder_id;
                }
                $form->save();
            }
            $previewDetails = Preview::where('form_id', $formId)
                ->where('deleted', 0)
                ->first();
            if (empty($previewDetails)) {
                $preview = new Preview;
                $preview->form_id = $formId;
                $preview->name = $formOptions['name'];
                $preview->options = json_encode($formOptions);
                $preview->pages = json_encode($formPages);
                $preview->elements = json_encode($formElements);
                $preview->created = time();
                $preview->deleted = 0;
                $preview->save();

                $form = Form::where('id', $formId)
                    ->where('deleted', 0)
                    ->first();
                $form->preview_id = $preview->id;
                if (!is_null($folder_id)) {
                    $form->folder_id = $folder_id;
                }
                $form->save();
            } else {
                $previewDetails->form_id = $formId;
                $previewDetails->name = $formOptions['name'];
                $previewDetails->options = json_encode($formOptions);
                $previewDetails->pages = json_encode($formPages);
                $previewDetails->elements = json_encode($formElements);
                $previewDetails->save();

                $form = Form::where('id', $formId)
                    ->where('deleted', 0)
                    ->first();
                $form->preview_id = $previewDetails->id;
                if (!is_null($folder_id)) {
                    $form->folder_id = $folder_id;
                }
                $form->save();
            }
        }

        if (
            $formId
            && $formOptions["webhook-integration-security-enable"] === "on"
        ) {
            $securityToken = $formOptions["webhook-integration-security"];
            $webhookSecurityHash = WebhookSecurityHash::firstWhere("form_id", $formId);

            if (!$webhookSecurityHash) {
                WebhookSecurityHash::create([
                    "security_token" => $securityToken,
                    "hash" => Hash::make($securityToken),
                    "form_id" => $formId,
                ]);
            } else {
                if ($webhookSecurityHash->security_token !== $securityToken) {
                    $webhookSecurityHash->security_token = $securityToken;
                    $webhookSecurityHash->hash = Hash::make($securityToken);
                    $webhookSecurityHash->save();
                }
            }
        }

        $returnData = [
            'status' => 'OK',
            'form_id' => $formId,
            'form_name' => $formOptions['name'],
            # 'preview_url' => admin_url('admin.php').'?leform-action=preview&id='.$formId,
            'preview_url' => route('preview-form', ['id' => $formId]),
            'message' => __('The form successfully saved.'),
            'short_link' => $this->getFormShortLink($form['short_link']),
            'long_link' => $this->getFormLongLink(
                $form['name'],
                $form['short_link']
            ),
        ];

        $this->cleanUnusedPdfFormBackgrounds($formId);

        if (!empty($callback)) {
            return $callback . '(' . json_encode($returnData) . ')';
        } else {
            return json_encode($returnData);
        }
    }

    public function preview(Request $request, LeformService $leform)
    {
        $formId = $request->query("id");

        $user = $request->user();
        $form = Form::withoutGlobalScope("company")
            ->where('id', $formId)
            ->where('deleted', 0)
            ->first();

        if (!$form) {
            return response('Form not found', 404);
        }

        $forms = Form::where('deleted', 0)
            ->where('active', 1)
            ->get();
        $defaultFormOptions = $leform->getDefaultFormOptions();

        $overlays = [];

        foreach ($forms as $form_details) {
            $form_options = json_decode($form_details['options'], true);
            if (!empty($form_options) && is_array($form_options)) {
                $form_options = array_merge($defaultFormOptions, $form_options);
            } else {
                $form_options = $defaultFormOptions;
            }

            $accessor = 'leform-' . $form_details['id'];
            $overlays[$accessor] = [];

            if (empty($form_options['popup-overlay-color'])) {
                $overlays[$accessor][] = 'rgba(0,0,0,0.7)';
            } else {
                $overlays[$accessor][] = $form_options['popup-overlay-color'];
            }

            if (empty($form_options['popup-spinner-color-color1'])) {
                $overlays[$accessor][] = '#FF5722';
            } else {
                $overlays[$accessor][] = $form_options['popup-spinner-color-color1'];
            }

            if (empty($form_options['popup-spinner-color-color2'])) {
                $overlays[$accessor][] = '#FF9800';
            } else {
                $overlays[$accessor][] = $form_options['popup-spinner-color-color2'];
            }

            if (empty($form_options['popup-spinner-color-color3'])) {
                $overlays[$accessor][] = '#FFC107';
            } else {
                $overlays[$accessor][] = $form_options['popup-spinner-color-color3'];
            }
        }

        $overlays['none'] = ['', '', '', ''];

        if (!$form["shareable"]) {
            if (!$this->compareUserWithFormCreator($user, $form)) {
                return response('Form does not belong to user', 403);
            }
        }

        $formObject = new LeformFormService($formId, true);
        $content = '<div class="not-found">Requested form not found</div>';
        if (!empty($formObject->id)) {
            $content = $formObject::shortcode_handler([
                'id' => $formObject->id,
                'preview' => true,
            ]);
        }
        return view('form-preview', [
            'content' => $content,
            'leform' => $leform,
            'formObject' => $formObject,
            'customCss' => json_decode($form->options, true)['custom-css'],
            'overlays' => $overlays,
            'gaTracking' => $leform->options['ga-tracking'],
            "frontendTranslations" => __("frontend_translations"),
            "isTemplateView" => $request->query("isTemplateView", false)
        ]);
    }

    public function formRemoteInit(Request $request, LeformService $leform)
    {
        $callback = '';
        if ($request->has('callback')) {
            header("Content-type: text/javascript");
            $callback = preg_replace('/[^a-zA-Z0-9_]/', '', $request->input('callback'));
        }

        if ($leform->advancedOptions['minified-sources'] == 'on') {
            $min = '.min';
        } else {
            $min = '';
        }

        $return_data = [
            'status' => 'OK',
            'consts' => ['ip' => $_SERVER['REMOTE_ADDR']]
        ];

        if ($request->has('form-ids')) {
            $form_id = $request->input('form-ids');
            # check if the ids are a list, at first it was supported but it is not
            # required and it would cause more harm than good
            if (strpos($form_id, ',') != false) {
                $form_id_end = strpos($form_id, ',');
                $form_id = intval(substr(0, $form_id_end));
            }
        } else {
            return response('Form id is required', 400);
        }

        $user = $request->user();
        if ($user) {
            $current_user = $user;
            $return_data['consts']['wp-user-login'] = $current_user->user_login;
            $return_data['consts']['wp-user-email'] = $current_user->user_email;
        }

        $return_data['resources']['css'][] = asset('/css/halfdata-plugin/style.css');
        if ($leform->options['fa-enable'] == 'on') {
            if ($leform->options['fa-css-disable'] != 'on') {
                if ($leform->options['fa-solid-enable'] == 'on' && $leform->options['fa-regular-enable'] == 'on' && $leform->options['fa-brands-enable'] == 'on') {
                    $return_data['resources']['css'][] = asset('/css/halfdata-plugin/fontawesome-all' . $min . '.css');
                } else {
                    $return_data['resources']['css'][] = asset('/css/halfdata-plugin/fontawesome' . $min . '.css');
                    if ($leform->options['fa-solid-enable'] == 'on') {
                        $return_data['resources']['css'][] = asset('/css/halfdata-plugin/fontawesome-solid' . $min . '.css');
                    }
                    if ($leform->options['fa-regular-enable'] == 'on') {
                        $return_data['resources']['css'][] = asset('/css/halfdata-plugin/fontawesome-regular' . $min . '.css');
                    }
                    if ($leform->options['fa-brands-enable'] == 'on') {
                        $return_data['resources']['css'][] = asset('/css/halfdata-plugin/fontawesome-brands' . $min . '.css');
                    }
                }
            }
        } else {
            $return_data['resources']['css'][] = asset('/css/halfdata-plugin/leform-fa' . $min . '.css');
        }

        $return_data['resources']['css'][] = asset('/css/halfdata-plugin/leform-if' . $min . '.css');
        $return_data['plugins'] = [];
        if ($leform->options['signature-enable'] == 'on') {
            if ($leform->options['signature-js-disable'] != 'on') {
                $return_data['resources']['js'][] = asset('/js/halfdata-plugin/signature_pad' . $min . '.js');
            }
            $return_data['plugins'][] = 'signature_pad';
        }

        if ($leform->options['airdatepicker-enable'] == 'on') {
            if ($leform->options['airdatepicker-js-disable'] != 'on') {
                $return_data['resources']['css'][] = asset('/css/halfdata-plugin/airdatepicker' . $min . '.css');
                $return_data['resources']['js'][] = asset('/js/halfdata-plugin/airdatepicker' . $min . '.js');
            }
            $return_data['plugins'][] = 'airdatepicker';
        }

        if ($leform->options['range-slider-enable'] == 'on') {
            if ($leform->options['range-slider-js-disable'] != 'on') {
                $return_data['resources']['css'][] = asset('/css/halfdata-plugin/ion.rangeSlider' . $min . '.css');
                $return_data['resources']['js'][] = asset('/js/halfdata-plugin/ion.rangeSlider' . $min . '.js');
            }
            $return_data['plugins'][] = 'ion.rangeSlider';
        }

        if ($leform->options['tooltipster-enable'] == 'on') {
            if ($leform->options['tooltipster-js-disable'] != 'on') {
                $return_data['resources']['css'][] = asset('/css/halfdata-plugin/tooltipster.bundle' . $min . '.css');
                $return_data['resources']['js'][] = asset('/js/halfdata-plugin/tooltipster.bundle' . $min . '.js');
            }
            $return_data['plugins'][] = 'tooltipster';
        }

        if ($leform->options['jsep-enable'] == 'on') {
            if ($leform->options['jsep-js-disable'] != 'on') {
                $return_data['resources']['js'][] = asset('/js/halfdata-plugin/jsep' . $min . '.js');
            }
            $return_data['plugins'][] = 'jsep';
        }

        if ($leform->options['mask-enable'] == 'on') {
            if ($leform->options['mask-js-disable'] != 'on') {
                $return_data['resources']['js'][] = asset('/js/halfdata-plugin/jquery.mask' . $min . '.js');
            }
            $return_data['plugins'][] = 'jquery.mask';
        }
        $return_data['ga-tracking'] = $leform->options['ga-tracking'];

        if (array_key_exists('ignore-status', $_REQUEST) && $_REQUEST['ignore-status'] == 'on') {
            $ignore_status = true;
        } else {
            $ignore_status = false;
        }

        #$forms = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."leform_forms WHERE deleted = '0'".($igonre_status ? '' : " AND active = '1'"), ARRAY_A);
        $form = Form::where('deleted', 0)
            ->where('active', 1)
            ->where('id', $form_id)
            ->first();

        if ($form == null) {
            return response('Form not found', 404);
        }

        $default_form_options = $leform->getDefaultFormOptions();
        $return_data['overlays'] = [];

        $form_options = json_decode($form['options'], true);
        if (!empty($form_options) && is_array($form_options)) {
            $form_options = array_merge($default_form_options, $form_options);
        } else {
            $form_options = $default_form_options;
        }
        $return_data['overlays']['leform-' . $form['id']] = [
            (empty($form_options['popup-overlay-color'])
                ? 'rgba(0,0,0,0.7)'
                : $form_options['popup-overlay-color']),
            $form_options['popup-overlay-click'],
            (empty($form_options["popup-spinner-color-color1"])
                ? '#FF5722'
                : $form_options["popup-spinner-color-color1"]),
            (empty($form_options["popup-spinner-color-color2"])
                ? '#FF9800'
                : $form_options["popup-spinner-color-color2"]),
            (empty($form_options["popup-spinner-color-color3"])
                ? '#FFC107'
                : $form_options["popup-spinner-color-color3"])
        ];

        $return_data["inline-forms"] = [];

        $xd = false;
        if (array_key_exists('hostname', $_REQUEST)) {
            $server_name = str_replace('www.', '', strtolower($_SERVER['SERVER_NAME']));
            $http_host = str_replace('www.', '', strtolower($_SERVER['HTTP_HOST']));
            $hostname = str_replace('www.', '', strtolower($_REQUEST['hostname']));
            if ($hostname != $server_name && $hostname != $http_host) {
                $xd = true;
            } else {
                if (array_key_exists('HTTP_REFERER', $_SERVER) && !empty($_SERVER['HTTP_REFERER'])) {
                    $ref_host = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
                    $ref_host = str_replace('www.', '', strtolower($ref_host));
                    if ($ref_host !== false && $ref_host != $server_name && $ref_host != $http_host) {
                        $xd = true;
                    }
                }
            }
        } else {
            $xd = true;
        }

        $token = $request->input("jwtToken");
        $predefinedValues = $this->getPredefinedValuesFromJWT(
            $form['company_id'],
            $token
        );
        if (isset($form_options["required-token"]) && $form_options["required-token"] === "on" && !$token) {
            $description = $form_options["required-token-description"];
            $title = trans('validation.access-denied');
            $return_data["inline-forms"][] = '
                <style>
                    .no-data-container {border-radius: 5px;background-color: #ff9898;color: #000;padding: 20px 30px;max-width: 500px;margin: 3em auto;}
                    .no-data-container h1 {font-size: 26px;margin-bottom: 5px;}
                </style>
                <div class="">
                    <div class="no-data-container">
                        <h1>'. $title.'</h1>
                        <p>'.$description.'</p>
                    </div>
                </div>
            ';
        } else {
            $getVariables = $request->input("getVariables");
            if(!is_array($getVariables)) {
                $getVariables = [];
            }
            if (!is_array($predefinedValues)) {
                $predefinedValues = [];
            }
            $predefinedValues['__get_params'] = $getVariables;
            $return_data["inline-forms"][] = LeformFormService::shortcode_handler([
                "id" => $form_id,
                "xd" => $xd,
                "predefinedValues" => $predefinedValues,
            ]);
        }
        if (!empty($callback)) {
            return $callback . '(' . json_encode($return_data) . ')';
        } else {
            return json_encode($return_data);
        }
    }

    public function submitForm(Request $request)
    {
        $callback = '';
        if (isset($_REQUEST['callback'])) {
            header("Content-type: text/javascript");
            $callback = preg_replace('/[^a-zA-Z0-9_]/', '', $_REQUEST['callback']);
        }
        $xd = false;
        if ($request->has('hostname')) {
            $server_name = str_replace('www.', '', strtolower($_SERVER['SERVER_NAME']));
            $http_host = str_replace('www.', '', strtolower($_SERVER['HTTP_HOST']));
            $hostname = str_replace('www.', '', strtolower($_REQUEST['hostname']));
            if ($hostname != $server_name && $hostname != $http_host) {
                $xd = true;
            } else {
                if (array_key_exists('HTTP_REFERER', $_SERVER) && !empty($_SERVER['HTTP_REFERER'])) {
                    $ref_host = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
                    $ref_host = str_replace('www.', '', strtolower($ref_host));
                    if ($ref_host !== false && $ref_host !== $server_name && $ref_host !== $http_host) {
                        $xd = true;
                    }
                }
            }
        } else {
            $xd = true;
        }

        $preview = false;
        if (array_key_exists('preview', $_REQUEST) && $_REQUEST['preview'] == 'on') {
            $preview = true;
        }
        $form_object = new LeformFormService(
            intval($_REQUEST['form-id']),
            $preview
        );
        $token = $request->input("jwtToken");
        $predefinedValues = $this->getPredefinedValuesFromJWT(
            $form_object->company_id,
            $token
        );
        $getVariables = $request->input("getVariables");
        if (!is_array($getVariables)) {
            $getVariables = [];
        }
        if (!is_array($predefinedValues)) {
            $predefinedValues = [];
        }
        $predefinedValues['__get_params'] = $getVariables;
        $systemVariables = $request->input("systemVariables", null);
        $form_object->setVariables($predefinedValues, $systemVariables);

        if (!empty($form_object->id)) {
            if ($xd === false || $form_object->form_options['cross-domain'] === 'on') {
                if (array_key_exists('page-id', $_REQUEST)) {
                    $page_id = intval($_REQUEST['page-id']);
                } else {
                    $page_id = 0;
                }
                $pages = $form_object->get_pages();
                if (array_key_exists($page_id, $pages)) {
                    $form_data = [];
                    parse_str(base64_decode($_REQUEST['form-data']), $form_data);

                    $form_object->set_form_data($form_data);
                    $form_object->set_form_info([], $request->user());

                    $errors_all = $form_object->validate_form_data();

                    if ($errors_all === false) {
                        $return_data = [
                            'status' => 'FATAL',
                            'message' => __('Requested form not found.')
                        ];
                    } else if (!is_array($errors_all)) {
                        $return_data = [
                            'status' => 'FATAL',
                            'message' => __('Unexpected error.')
                        ];
                    } else {
                        $errors = [];
                        if (!empty($errors_all)) {
                            foreach ($pages as $key => $elements) {
                                if ($form_object->is_page_visible($key)) {
                                    foreach ($elements as $element_id) {
                                        if (array_key_exists($element_id, $errors_all)) {
                                            $errors[$key . ':' . $element_id] = $errors_all[$element_id];
                                        }
                                    }
                                }
                                if ($key == $page_id) {
                                    break;
                                }
                            }
                        }
                        if (empty($errors)) {
                            $next_page_id = $form_object->get_next_page_id($page_id);
                            if ($next_page_id === false) {
                                $return_data = [
                                    'status' => 'FATAL',
                                    'message' => __('Requested page not found.')
                                ];
                            } else if ($next_page_id === true || $next_page_id == 'confirmation') {
                                $payment_ok = false;
                                //$log_record = ['str-id' => '', 'id' => 0];
                                if (!$preview) {
                                    $log_record = $form_object->save_data();
                                }
                                $confirmation = $form_object->get_confirmation();
                                $return_data = [
                                    'status' => 'OK',
                                    'record-id' => $form_object->record_id
                                ];
                                if (empty($confirmation)) {
                                    $return_data['type'] = 'page';
                                    $return_data['reset-form'] = 'on';
                                } else {
                                    $return_data['type'] = $confirmation['type'];
                                    $return_data['reset-form'] = $confirmation['reset-form'];
                                    if (
                                        in_array(
                                            $confirmation['type'],
                                            [
                                                'page-redirect',
                                                'page-payment',
                                                'message-redirect',
                                                'message-payment',
                                                'message'
                                            ]
                                        )
                                    ) {
                                        $return_data['delay'] = $confirmation['delay'];
                                    }
                                    if (
                                        in_array(
                                            $confirmation['type'],
                                            ['page-payment', 'message-payment', 'payment']
                                        )
                                    ) {
                                        if (!$payment_ok) {
                                            if (in_array($confirmation['type'], ['page-payment', 'payment'])) {
                                                $return_data['type'] = 'page';
                                            } else {
                                                $return_data['type'] = 'message';
                                            }
                                        }
                                    }
                                }
                            } else {
                                $return_data = [
                                    'status' => 'NEXT',
                                    'page' => $next_page_id
                                ];
                            }
                        } else {
                            $return_data = [
                                'status' => 'ERROR',
                                'errors' => $errors
                            ];
                        }
                    }
                } else {
                    $return_data = [
                        'status' => 'FATAL',
                        'message' => __('Requested page not found.')
                    ];
                }
            } else {
                $return_data = [
                    'status' => 'FATAL',
                    'message' => __('Cross-domain calls are not allowed for this form.')
                ];
            }
        } else {
            $return_data = [
                'status' => 'FATAL',
                'message' => __('Requested form not found.')
            ];
        }

        if (
            $request->input('action') == 'leform-front-submit'
            && $return_data["status"] === "OK"
        ) {
            if (
                $form_object->form_options["webhook-integration-enable"] == "on"
                && $form_object->form_options["webhook-integration"] !== ""
            ) {
                $hash = null;

                if ($form_object->form_options["webhook-integration-security-enable"] == "on") {
                    $hash = WebhookSecurityHash::firstWhere("form_id", $form_object->id)
                        ->hash;
                }

                $response = Http::withHeaders([
                    "X-Signature" => $hash,
                ])->post(
                    $form_object->form_options["webhook-integration"],
                    $this->prepareRecordForWebhook($return_data["record-id"]),
                );
            }

            if ($form_object->form_options['redirect-enable'] == 'on') {
                $return_data = [
                    'status' => 'REDIRECT',
                    'redirectUrl' => $form_object->form_options['redirect-url'],
                ];
            }

            if ($form_object->form_options['email-on-form-submition-enable'] == 'on') {
                $emailReceivers = json_decode(
                    $form_object->form_options['email-on-form-submition']
                );
                $subject = $form_object->name;
                if ($form_object->form_options["subject-of-email-on-form-submition"]) {
                    $subject = $form_object->form_options["subject-of-email-on-form-submition"];
                    $allVaribles = evo_get_all_variables($predefinedValues);
                    $subject = preg_replace_callback(
                        "/{{(.+?)}}/",
                        function ($matches) use ($allVaribles, $form_object) {
                            $variable = $matches[1];
                            if (
                                array_key_exists($variable, $allVaribles)
                                && is_string($allVaribles[$variable])
                            ) {
                                return $allVaribles[$variable];
                            } else if (str_starts_with($variable, "form_")) {
                                return LeformFormService::getValueForVariableField(
                                    $variable,
                                    $form_object->form_elements,
                                    $form_object->form_data,
                                    $form_object->form_options
                                );
                            } else {
                                return "";
                            }
                        },
                        $subject
                    );
                }
                foreach ($emailReceivers as $emailReceiver) {
                    $mail = new FormSubmitted(
                        $return_data["record-id"],
                        $subject,
                    );
                    $allVaribles = evo_get_all_variables($predefinedValues);
                    $emailReceiver = preg_replace_callback(
                        "/{{(.+?)}}/",
                        function ($matches) use ($allVaribles) {
                            if (
                                array_key_exists($matches[1], $allVaribles)
                                && is_string($allVaribles[$matches[1]])
                            ) {
                                return $allVaribles[$matches[1]];
                            } else {
                                return "";
                            }
                        },
                        $emailReceiver
                    );
                    try {
                        $validator = Validator::make(
                            ['email' => $emailReceiver],
                            ['email' => 'required|email']
                        );

                        $companyId = $form_object->company_id;
                        if (
                            SettingsService::areSmtpSettingsConfigured($companyId)
                            && !$validator->fails()
                        ) {
                            Mail::to($emailReceiver)->send($mail);
                        } else {
                        }
                    } catch (\Exception $ex) {
                        Log::error($ex);
                    }
                }
            }

            if ($form_object->form_options["double-enable"] == "on") {
                $emailElementId = $form_object->form_options["double-email-recipient"];

                if (isset($form_object->form_data[$emailElementId]) && !empty($form_object->form_data[$emailElementId])) {
                    $recipient = $form_object->form_data[$emailElementId];
                    $mail = new AfterSubmitEmailAutorespond([
                        "subject" => $form_object->form_options["double-email-subject"],
                        "message" => $form_object->form_options["double-email-message"],
                        "fields" => $form_object->form_data,
                    ]);
                    try {
                        $companyId = $form_object->company_id;
                        if (SettingsService::areSmtpSettingsConfigured($companyId)) {
                            Mail::to($recipient)->send($mail);
                        }
                    } catch (\Exception $ex) {
                    }
                }
            }

            if ($form_object->form_options["user-downloads-results-as-pdf"] == "on") {
                $recordForPdf = Record::firstWhere("id", $return_data["record-id"]);
                $return_data["pdfLink"] = route("get-pdf-of-submited-record", [
                    "recordId" => $recordForPdf["id"],
                    "strId" => $recordForPdf["str_id"],
                ]);
            }

            $companyId = $form_object->company_id;

            $company = Company::firstWhere("id", $companyId);
            $form = Form::firstWhere("id", $form_object->id);
            $record = Record::firstWhere("id", $return_data["record-id"]);
            if (is_array($predefinedValues) && isset($predefinedValues['user_id']) && isset($predefinedValues['course_db_id'])) {
                $record->moodle_user_id = (int)$predefinedValues['user_id'];
                $record->moodle_course_id = (int)$predefinedValues['course_db_id'];
                $moodleUrl = Setting::where('company_id', $form->company_id)->where('name', 'moodle-base-url')->first();
                $apiToken = Setting::where('company_id', $form->company_id)->where('name', 'predefined-values-secret')->first();
                if(isset($moodleUrl) && !empty($moodleUrl->value) && isset($apiToken) && !empty($apiToken->value)) {
                    $moodle_url = rtrim($moodleUrl->value, '/') . '/local/formwerk/form_submitted.php';
                    // post to moodle
                    $client = new GuzzleHttp\Client();
                    try {
                        $response = $client->post($moodle_url, [
                            'form_params' => [
                                'user_id' => $record->moodle_user_id,
                                'form_id' => $form_object->id,
                                'course_id' => (int)$predefinedValues['course_db_id'],
                            ],
                            'headers' => ["X-Formwerk-Api-Token" => $apiToken->value]
                        ]);
                        $res = json_decode($response->getBody());
                        if(isset($res->success) && $res->success) {
                            $record->sent_to_moodle = 1;
                        }
                    } catch (\Exception $ex) {
                    }
                }
            }
            if (
                array_key_exists(
                    "generate-xml-on-save",
                    $form_object->form_options
                ) && $form_object->form_options["generate-xml-on-save"] === "on"
            ) {
                $xmlFullFileName = XMLController::generateXMLFileName(
                    $company,
                    $form,
                    $record,
                    true,
                );
                $record->xml_file_name = $xmlFullFileName;
                $xml = XMLController::createXMLForEntry($company->id, $record->id, $form_object);

                $dom = new DOMDocument("1.0");
                $dom->preserveWhiteSpace = false;
                $dom->formatOutput = true;
                $dom->loadXML($xml);
                $parsedXml = $dom->saveXML();
                XMLController::saveXMLFile($xmlFullFileName, $parsedXml);

                if (
                    array_key_exists(
                        "transport-xml-via-ftp",
                        $form_object->form_options
                    )
                    && ($form_object->form_options["transport-xml-via-ftp"] === "on")
                    && SettingsService::areSftpSettingsConfigured($companyId)
                ) {
                    $sftpSettings = SettingsService::getSftpSettings($companyId);

                    $sftpPath = $sftpSettings["sftp-path"];
                    if (!str_ends_with($sftpPath, "/")) {
                        $sftpPath .= "/";
                    }

                    $sftpFullFileName = $sftpPath . substr(
                        $xmlFullFileName,
                        strrpos($xmlFullFileName, '/') + 1,
                    );
                    // need to test this
                    try {
                        config([
                            'filesystems.disks.tenant_ftp' => [
                                'driver' => $sftpSettings["sftp-port"] === 22 ? 'sftp' : 'ftp',
                                'host' => $sftpSettings["sftp-host"],
                                'username' => $sftpSettings["sftp-username"],
                                'password' => $sftpSettings["sftp-password"],
                                // 'root' => $sftpSettings["sftp-path"],
                                'port' => $sftpSettings["sftp-port"],
                            ],
                        ]);
                        Storage::disk('tenant_ftp')->put($sftpFullFileName, $xml);
                    } catch (\Exception $ex) {
                    }
                }
                if (
                    array_key_exists(
                        "xml-webhook-integration-enable",
                        $form_object->form_options
                    )
                    && array_key_exists(
                    'xml-webhook-integration',
                        $form_object->form_options
                    )
                    && ($form_object->form_options["xml-webhook-integration-enable"] === "on")
                    && filter_var($form_object->form_options["xml-webhook-integration"], FILTER_VALIDATE_URL)
                ) {
                    try {
                        $xmlWebhookUrl = $form_object->form_options["xml-webhook-integration"];
                        $xmlToken = "";
                        $options = [
                            'headers' => [
                                'Content-Type' => 'text/xml; charset=UTF8'
                            ],
                            'body' => $parsedXml
                        ];
                        if(
                        array_key_exists('xml-webhook-integration-security', $form_object->form_options)
                        && !empty($form_object->form_options["xml-webhook-integration-security"])
                        ){
                            $xmlToken = $form_object->form_options["xml-webhook-integration-security"];
                            $options['headers']['Authorization'] = $xmlToken;
                        }

                        $client = new GuzzleHttp\Client();
                        $client->request('POST', $xmlWebhookUrl, $options);
                    } catch(Exception $e) {

                    }
                }
            }

            if (
                array_key_exists(
                    "generate-csv-on-save",
                    $form_object->form_options
                ) && $form_object->form_options["generate-csv-on-save"] === "on"
            ) {
                $csvFullFileName = CSVController::generateCSVFileName(
                    $company,
                    $form,
                    $record,
                    true,
                );

                $record->csv_file_name = $csvFullFileName;

                $csvParts = CSVController::createCSVForEntry($companyId, $record->id, $form_object->form_options['encoding-csv']);
                CSVController::saveCSVFile($csvFullFileName, $csvParts, $form_object->form_options['csv-saving-priority'] === 'append');
                $csv = "";
                if($csvParts['includeHeader'] === 'on') {
                    $csv .= $csvParts['header'] . "\n";
                }
                $csv .= $csvParts['body'];
                if (
                    array_key_exists(
                        "transport-csv-via-ftp",
                        $form_object->form_options
                    )
                    && ($form_object->form_options["transport-csv-via-ftp"] === "on")
                    && SettingsService::areSftpSettingsConfigured($companyId)
                ) {
                    $sftpSettings = SettingsService::getSftpSettings($companyId);

                    $sftpPath = $sftpSettings["sftp-path"];
                    if (!str_ends_with($sftpPath, "/")) {
                        $sftpPath .= "/";
                    }

                    $sftpFullFileName = $sftpPath . substr(
                        $csvFullFileName,
                        strrpos($csvFullFileName, '/') + 1,
                    );
                    // need to test this
                    try {
                        config([
                            'filesystems.disks.tenant_csv_ftp' => [
                                'driver' => $sftpSettings["sftp-port"] === 22 ? 'sftp' : 'ftp',
                                'host' => $sftpSettings["sftp-host"],
                                'username' => $sftpSettings["sftp-username"],
                                'password' => $sftpSettings["sftp-password"],
                                // 'root' => $sftpSettings["sftp-path"],
                                'port' => $sftpSettings["sftp-port"],
                            ],
                        ]);
                        Storage::disk('tenant_csv_ftp')->put($sftpFullFileName, $csv);
                    } catch (\Exception $ex) {
                    }
                }
            }

            if (
                array_key_exists(
                    "setup-custom-report",
                    $form_object->form_options
                ) && $form_object->form_options["setup-custom-report"] === "on"
            ) {
                $report = CustomReportController::createReportForEntry(
                    $companyId,
                    $record->id
                );
                $reportFileName = CustomReportController::generateFileName(
                    $company,
                    $form,
                    $record,
                    true,
                );
                $record->custom_report_file_name = $reportFileName;
                if ($form_object->form_options['report-saving-priority'] === 'append' && Storage::disk('private')->exists($reportFileName)) {
                    Storage::disk("private")->append($reportFileName, $report);
                } else {
                    Storage::disk("private")->put($reportFileName, $report);
                }
            }
            $record->save();
        }

        if (!empty($callback)) {
            return $callback . '(' . json_encode($return_data) . ')';
        } else {
            return json_encode($return_data);
        }
    }

    public function uploadProgress(Request $request)
    {
        $callback = '';
        if ($request->has('callback')) {
            header("Content-type: text/javascript");
            $callback = preg_replace('/[^a-zA-Z0-9_]/', '', $request->input('callback'));
        }
        if ($request->has('upload-id')) {
            $upload_id = preg_replace('/[^a-zA-Z0-9-]/', '', $request->input('upload-id'));
            if (!empty($upload_id)) {
                #$uploads = $wpdb->get_results( "SELECT * FROM ".$wpdb->prefix."leform_uploads WHERE deleted = '0' AND upload_id = '".esc_sql($upload_id)."' AND str_id = '' AND status != '".esc_sql(LEFORM_UPLOAD_STATUS_DELETED)."'", ARRAY_A);
                $uploads = Upload::where('deleted', 0)
                    ->where('upload_id', $upload_id)
                    ->where('str_id', '')
                    ->where('status', '!=', 2)
                    ->get();
                if (!empty($uploads)) {
                    #if (count($uploads) > 0) {
                    $return_data = ['status' => 'OK', 'result' => []];
                    foreach ($uploads as $upload_details) {
                        switch ($upload_details['status']) {
                                #case LEFORM_UPLOAD_STATUS_ERROR:
                            case 1:
                                $file_data = [
                                    'status' => 'ERROR',
                                    'message' => $upload_details['message'],
                                    'name' => $upload_details['filename_original']
                                ];
                                #$wpdb->query( "DELETE FROM ".$wpdb->prefix."leform_uploads WHERE id = '".esc_sql($upload_details['id'])."'");
                                Upload::where('id', $upload_details['id'])->delete();
                                break;
                            default:
                                $str_id = LeformService::random_string(16);
                                #$wpdb->query( "UPDATE ".$wpdb->prefix."leform_uploads SET str_id='".esc_sql($str_id)."' WHERE id = '".esc_sql($upload_details['id'])."'");
                                Upload::where('id', $upload_details['id'])->update([
                                    'str_id' => $str_id,
                                ]);
                                $file_data = [
                                    'status' => 'OK',
                                    'uid' => $str_id,
                                    'name' => $upload_details['filename_original']
                                ];
                                break;
                        }
                        $return_data['result'][] = $file_data;
                    }
                } else {
                    if ($request->has('last-request')) {
                        return [
                            $request->has('last-request'),
                            $request->input('last-request'),
                        ];
                        $return_data = ['status' => 'ERROR'];
                    } else {
                        $return_data = ['status' => 'LOADING'];
                        $key = ini_get("session.upload_progress.prefix") . $upload_id;

                        if ($request->session()->has($key) && !empty($request->session()->get($key))) {
                            foreach ($request->session()->get($key)['files'] as $file) {
                                $return_data['progress'][] = [
                                    'name' => $file['name'],
                                    'bytes_processed' => $file['bytes_processed']
                                ];
                            }
                        }
                    }
                }
            } else {
                $return_data = ['status' => 'ERROR', 'message' => __('Invalid request.')];
            }
        } else {
            $return_data = ['status' => 'ERROR', 'message' => __('Invalid request.')];
        }
        if (!empty($callback)) {
            return $callback . '(' . json_encode($return_data) . ')';
        } else {
            return json_encode($return_data);
        }
    }

    public function toogleFormStatus(Request $request)
    {
        $callback = '';
        if (isset($_REQUEST['callback'])) {
            header("Content-type: text/javascript");
            $callback = preg_replace('/[^a-zA-Z0-9_]/', '', $_REQUEST['callback']);
        }
        $form_id = null;
        if ($request->has('form-id')) {
            $form_id = intval($request->input('form-id'));
            #$form_details = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."leform_forms WHERE deleted = '0' AND id = '".esc_sql($form_id)."'", ARRAY_A);
            $form_details = Form::where('deleted', 0)
                ->where('id', $form_id)
                ->first();

            if (
                !empty($form_details)
                && !$this->compareUserWithFormCreator($request->user(), $form_details)
            ) {
                return response('Form does not belong to user', 403);
            }

            if (empty($form_details)) {
                $form_id = null;
            }
        }
        if (empty($form_id)) {
            $return_data = [
                'status' => 'ERROR',
                'message' => __('Requested form not found.'),
            ];
            if (!empty($callback)) {
                return $callback . '(' . json_encode($return_data) . ')';
            } else {
                return json_encode($return_data);
            }
        }
        if ($request->input('form-status') == 'active') {
            #$wpdb->query("UPDATE ".$wpdb->prefix."leform_forms SET active = '0' WHERE deleted = '0' AND id = '".esc_sql($form_id)."'");
            Form::where('deleted', 0)
                ->where('id', $form_id)
                ->update(['active' => 0]);

            $return_data = [
                'status' => 'OK',
                'message' => __('The form successfully deactivated.'),
                'form_action' => 'Activate',
                'form_action_doing' => 'Activating...',
                'form_status' => 'inactive',
                'form_status_label' => 'No',
            ];
        } else {
            #$wpdb->query("UPDATE ".$wpdb->prefix."leform_forms SET active = '1' WHERE deleted = '0' AND id = '".esc_sql($form_id)."'");
            Form::where('deleted', 0)
                ->where('id', $form_id)
                ->update(['active' => 1]);
            $return_data = [
                'status' => 'OK',
                'message' => __('The form successfully activated.'),
                'form_action' => 'Deactivate',
                'form_action_doing' => 'Deactivating...',
                'form_status' => 'active',
                'form_status_label' => 'Yes',
            ];
        }
        if (!empty($callback)) {
            return $callback . '(' . json_encode($return_data) . ')';
        } else {
            return json_encode($return_data);
        }
    }

    public function duplicateForm(Request $request)
    {
        $user = $request->user();
        $callback = '';
        if (isset($_REQUEST['callback'])) {
            header("Content-type: text/javascript");
            $callback = preg_replace('/[^a-zA-Z0-9_]/', '', $_REQUEST['callback']);
        }
        $form_id = null;
        if ($request->has('form-id')) {
            $form_id = intval($request->input('form-id'));
            #$form_details = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."leform_forms WHERE deleted = '0' AND id = '".esc_sql($form_id)."'", ARRAY_A);
            $form_details = Form::where('deleted', 0)
                ->where('id', $form_id)
                ->first();
            if (empty($form_details)) {
                $form_id = null;
            } else {
                if (!$this->compareUserWithFormCreator($user, $form_details)) {
                    return response('Form does not belong to user', 403);
                }
            }
        }
        if (empty($form_id)) {
            $return_data = [
                'status' => 'ERROR',
                'message' => __('Requested form not found.'),
            ];
            if (!empty($callback)) {
                return $callback . '(' . json_encode($return_data) . ')';
            } else {
                return json_encode($return_data);
            }
        }
        #$wpdb->query("INSERT INTO ".$wpdb->prefix."leform_forms (name,options,pages,elements,active,created,modified,deleted) VALUES (
        #    '".esc_sql($form_details['name'])."','".esc_sql($form_details['options'])."','".esc_sql($form_details['pages'])."','".esc_sql($form_details['elements'])."','".esc_sql($form_details['active'])."','".esc_sql(time())."','".esc_sql(time())."','0')");
        Form::create([
            'name' => $form_details['name'],
            'options' => $form_details['options'],
            'pages' => $form_details['pages'],
            'elements' => $form_details['elements'],
            'active' => $form_details['active'],
            'folder_id' => $form_details['folder_id'],
            'created' => time(),
            'modified' => time(),
            'deleted' => 0,
            'user_id' => $user->id,
            'short_link' => Faker::create()->bothify('???##'),
        ]);

        $return_data = [
            'status' => 'OK',
            'message' => __('The form successfully duplicated.'),
        ];
        if (!empty($callback)) {
            return $callback . '(' . json_encode($return_data) . ')';
        } else {
            return json_encode($return_data);
        }
    }

    public function toggleFormShareable(Request $request)
    {
        $user = $request->user();
        $formId = intval($request->input('form-id', null));
        $form = Form::where('deleted', 0)
            ->where('id', $formId)
            ->first();
        if (empty($form)) {
            return [
                "status" => "ERROR",
                "message" => __("Form not found"),
            ];
        } else {
            if (!$this->compareUserWithFormCreator($user, $form)) {
                return [
                    "status" => "ERROR",
                    "message" => __("Form does not belong to user"),
                ];
            } else {
                $form->shareable = !$form->shareable;
                $form->share_date = $form->shareable
                    ? date("Y-m-d H:i:s", time())
                    : null;
                $form->save();
                return [
                    "status" => "OK",
                    "message" => $form->shareable
                        ? __("Form became shareable")
                        : __("Form became unshareable"),
                ];
            }
        }
    }

    public function deleteForm(Request $request)
    {
        $callback = '';
        if (isset($_REQUEST['callback'])) {
            header("Content-type: text/javascript");
            $callback = preg_replace('/[^a-zA-Z0-9_]/', '', $_REQUEST['callback']);
        }
        $form_id = null;
        if ($request->has('form-id')) {
            $form_id = intval($request->input('form-id'));
            #$form_details = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."leform_forms WHERE deleted = '0' AND id = '".esc_sql($form_id)."'", ARRAY_A);
            $form_details = Form::where('deleted', 0)
                ->where('id', $form_id)
                ->first();
            if (empty($form_details)) {
                $form_id = null;
            } else {
                if (!$this->compareUserWithFormCreator($request->user(), $form_details)) {
                    return response('Form does not belong to user', 403);
                }
            }
        }
        if (empty($form_id)) {
            $return_data = [
                'status' => 'ERROR',
                'message' => __('Requested form not found.'),
            ];
            if (!empty($callback)) {
                return $callback . '(' . json_encode($return_data) . ')';
            } else {
                return json_encode($return_data);
            }
        }
        #$wpdb->query("UPDATE ".$wpdb->prefix."leform_forms SET deleted = '1' WHERE deleted = '0' AND id = '".esc_sql($form_id)."'");
        Form::where('deleted', 0)
            ->where('id', $form_id)
            ->update(['deleted' => 1]);
        $return_data = [
            'status' => 'OK',
            'message' => __('The form successfully deleted.'),
        ];
        if (!empty($callback)) {
            return $callback . '(' . json_encode($return_data) . ')';
        } else {
            return json_encode($return_data);
        }
    }

    public function fileUpload(Request $request, LeformService $leform)
    {
        $response = [];
        if (
            $request->has('upload-id')
            && $request->has('form-id')
            && $request->has('element-id')
        ) {
            $upload_id = preg_replace('/[^a-zA-Z0-9-]/', '', $request->input('upload-id'));
            if (!empty($upload_id)) {
                $form_object = new LeformFormService(intval($request->input('form-id')));
                if (!empty($form_object->id)) {
                    $element_idx = false;
                    for ($i = 0; $i < sizeof($form_object->form_elements); $i++) {
                        if (
                            $form_object->form_elements[$i]['id'] == $request->input('element-id')
                            && $form_object->form_elements[$i]['type'] == 'file'
                        ) {
                            $element_idx = $i;
                        }
                    }

                    if ($element_idx !== false) {
                        if ($request->hasFile('files') && sizeof($request->file('files')) > 0) {
                            $allowed_extensions_raw = explode(
                                ',',
                                $form_object->form_elements[$element_idx]['allowed-extensions']
                            );
                            $allowed_extensions = [];
                            foreach ($allowed_extensions_raw as $extension) {
                                $extension = trim(trim($extension), '.');
                                if (!empty($extension)) {
                                    $allowed_extensions[] = strtolower($extension);
                                }
                            }

                            $str_id = $leform->random_string(16);
                            foreach ($request->file('files') as $file) {
                                if ($file->getError() == UPLOAD_ERR_OK) {
                                    $filename_original = $file->getClientOriginalName();
                                    $upload_details = Upload::where('deleted', 0)
                                        ->where('upload_id', $upload_id)
                                        ->where('filename_original', $filename_original)
                                        ->first();
                                    if (empty($upload_details)) {
                                        $ext = pathinfo($filename_original, PATHINFO_EXTENSION);
                                        $ext = strtolower($ext);
                                        $max_size = intval($form_object->form_elements[$element_idx]['max-size']) * 1024 * 1024;

                                        if (
                                            (!empty($allowed_extensions) && !in_array($ext, $allowed_extensions))
                                            || substr($ext, 0, 3) == "php"
                                        ) {
                                            $response[] = [
                                                'status' => 'ERROR',
                                                'message' => $form_object->form_elements[$element_idx]['allowed-extensions-error'],
                                                'name' => $filename_original,
                                            ];
                                            Upload::create([
                                                'record_id' => 0,
                                                'form_id' => $form_object->id,
                                                'element_id' => 0,
                                                'upload_id' => $upload_id,
                                                'str_id' => $str_id,
                                                'status' => 1, #LEFORM_UPLOAD_STATUS_ERROR
                                                'message' => $form_object->form_elements[$element_idx]['allowed-extensions-error'],
                                                'filename' => '',
                                                'filename_original' => $filename_original,
                                                'created' => time(),
                                                'deleted' => 0,
                                            ]);
                                            #$wpdb->query( "INSERT INTO ".$wpdb->prefix."leform_uploads ( record_id, form_id, element_id, upload_id, str_id, status, message, filename, filename_original, created, deleted) VALUES ( '0', '".esc_sql($form_object->id)."', '0', '".esc_sql($upload_id)."', '', '".esc_sql(LEFORM_UPLOAD_STATUS_ERROR)."', '".esc_sql($form_object->form_elements[$element_idx]['allowed-extensions-error'])."', '', '".esc_sql($filename_original)."', '".esc_sql(time())."', '0')");
                                        } else if ($max_size > 0 && $file->getSize() > $max_size) {
                                            $response[] = [
                                                'status' => 'ERROR',
                                                'message' => $form_object->form_elements[$element_idx]['max-size-error'],
                                                'name' => $filename_original,
                                            ];
                                            Upload::create([
                                                'record_id' => 0,
                                                'form_id' => $form_object->id,
                                                'element_id' => 0,
                                                'upload_id' => $upload_id,
                                                'str_id' => $str_id,
                                                'status' => 1, #LEFORM_UPLOAD_STATUS_ERROR
                                                'message' => $form_object->form_elements[$element_idx]['max-size-error'],
                                                'filename' => '',
                                                'filename_original' => $filename_original,
                                                'created' => time(),
                                                'deleted' => 0,
                                            ]);
                                            #$wpdb->query(" INSERT INTO ".$wpdb->prefix."leform_uploads ( record_id, form_id, element_id, upload_id, str_id, status, message, filename, filename_original, created, deleted) VALUES ( '0', '".esc_sql($form_object->id)."', '0', '".esc_sql($upload_id)."', '', '".esc_sql(LEFORM_UPLOAD_STATUS_ERROR)."', '".esc_sql($form_object->form_elements[$element_idx]['max-size-error'])."', '', '".esc_sql($filename_original)."', '".esc_sql(time())."', '0')");
                                        } else {
                                            #$filename = '_'.$leform->random_string(32).(!empty($ext) ? '.'.$ext : '');
                                            #$moved = $file->storeAs('uploads', $filename);
                                            $path = $file->store('uploads', 'public');
                                            $filename = str_replace('uploads/', '', $path);

                                            if ($path) {
                                                $response[] = [
                                                    'status' => 'OK',
                                                    'uid' => $str_id,
                                                    'name' => $filename_original,
                                                ];
                                                #DB::enableQueryLog();
                                                Upload::create([
                                                    'record_id' => 0,
                                                    'form_id' => $form_object->id,
                                                    'element_id' => 0,
                                                    'upload_id' => $upload_id,
                                                    'str_id' => $str_id,
                                                    'status' => 0, #LEFORM_UPLOAD_STATUS_OK
                                                    'message' => '',
                                                    'filename' => $filename,
                                                    'filename_original' => $filename_original,
                                                    'created' => time(),
                                                    'deleted' => 0,
                                                ]);
                                                #$wpdb->query(" INSERT INTO ".$wpdb->prefix."leform_uploads ( record_id, form_id, element_id, upload_id, str_id, status, message, filename, filename_original, created, deleted) VALUES ( '0', '".esc_sql($form_object->id)."', '0', '".esc_sql($upload_id)."', '', '".esc_sql(LEFORM_UPLOAD_STATUS_OK)."', '', '".esc_sql($filename)."', '".esc_sql($filename_original)."', '".esc_sql(time())."', '0')");
                                            } else {
                                                $response[] = [
                                                    'status' => 'ERROR',
                                                    'message' => 'Can not move uploaded file.',
                                                    'name' => $filename_original,
                                                ];
                                                Upload::create([
                                                    'record_id' => 0,
                                                    'form_id' => $form_object->id,
                                                    'element_id' => 0,
                                                    'upload_id' => $upload_id,
                                                    'str_id' => $str_id,
                                                    'status' => 1, #LEFORM_UPLOAD_STATUS_ERROR
                                                    'message' => 'Can not move uploaded file.',
                                                    'filename' => $filename,
                                                    'filename_original' => $filename_original,
                                                    'created' => time(),
                                                    'deleted' => 0,
                                                ]);
                                                #$wpdb->query("INSERT INTO ".$wpdb->prefix."leform_uploads ( record_id, form_id, element_id, upload_id, str_id, status, message, filename, filename_original, created, deleted) VALUES ( '0', '".esc_sql($form_object->id)."', '0', '".esc_sql($upload_id)."', '', '".esc_sql(LEFORM_UPLOAD_STATUS_ERROR)."', '".esc_sql(esc_html__('Can not move uploaded file.', 'leform'))."', '".esc_sql($filename)."', '".esc_sql($filename_original)."', '".esc_sql(time())."', '0')");
                                            }
                                        }
                                    }
                                } else {
                                    $filename_original = $file->getClientOriginalName();
                                    # $wpdb->query("INSERT INTO ".$wpdb->prefix."leform_uploads ( record_id, form_id, element_id, upload_id, str_id, status, message, filename, filename_original, created, deleted) VALUES ( '0', '".esc_sql($form_object->id)."', '0', '".esc_sql($upload_id)."', '', '".esc_sql(LEFORM_UPLOAD_STATUS_ERROR)."', '".esc_sql(esc_html__('Can not process uploaded file.', 'leform'))."', '', '".esc_sql($filename_original)."', '".esc_sql(time())."', '0')");
                                    Upload::create([
                                        'record_id' => 0,
                                        'form_id' => $form_object->id,
                                        'element_id' => 0,
                                        'upload_id' => $upload_id,
                                        'str_id' => $str_id,
                                        'status' => 1, #LEFORM_UPLOAD_STATUS_ERROR
                                        'message' => 'Can not process uploaded file',
                                        'filename' => '',
                                        'filename_original' => $filename_original,
                                        'created' => time(),
                                        'deleted' => 0,
                                    ]);
                                }
                            }
                        }
                    }
                }
            }
        }
        return $response;
    }

    public function downloadRecordPdfAfterSubmit($recordId, $strId)
    {
        if (!$recordId || !$strId) {
            return response("", 400);
        }

        $record = Record::where("id", $recordId)
            ->where("str_id", $strId)
            ->first();

        if (!$record) {
            return response("", 404);
        }

        $recordForPdf = RecordPdfService::getDecodedRecord(
            $record["id"]
        );
        $formForPdf = RecordPdfService::getDecodedForm(
            $recordForPdf["form_id"]
        );

        $pdfFile = RecordPdfService::generateRecordPdf(
            $recordForPdf,
            $formForPdf
        );
        $pdfFileName = RecordPdfService::generateRecordPdfName(
            $recordForPdf,
            $formForPdf
        );
        return $pdfFile;
    }

    public function uploadSelectImageFieldImages(Request $request)
    {
        $user = $request->user();
        $formId = $request->input("form_id", null);

        if (!is_numeric($formId)) {
            return [
                'status' => "ERROR",
                'message' => __("Form id is required"),
            ];
        }

        $formDetails = Form::where("id", $formId)
            ->where("deleted", 0)
            ->where("user_id", $user->id)
            ->first();

        if (!$formDetails) {
            return [
                'status' => "ERROR",
                'message' => __("Form not found"),
            ];
        }

        # if the form doesn't belong to the user redirect to creating a new form
        if ($formDetails->company_id != $user->company_id) {
            return redirect()->route("create-form", ["page" => "formwerk"]);
        };

        if (
            !$request->hasFile('file')
            || !$request->file('file')->isValid()
        ) {
            return [
                'status' => "ERROR",
                'message' => __("File is required"),
            ];
        }

        if (
            !in_array(
                $request->file('file')->extension(),
                ["jpg", "jpeg", "png", "webp"]
            )
        ) {
            return [
                'status' => "ERROR",
                'message' => __("Extension not allowed"),
            ];
        }

        $path = $request->file('file')->store("select-image-input-images", "public");

        return [
            'status' => "SUCCESS",
            'path' => Storage::url($path),
        ];
    }

    public function downloadAttachmentFromEmail($hash)
    {
        try {
            $id = Crypt::decrypt($hash);
            $file = Upload::firstWhere('id', $id);
            if ($file) {
                $filePath = "uploads/$file->filename";
                $fileExists = Storage::disk("public")->exists($filePath);
                if ($fileExists) {
                    $path = Storage::disk('public')->path($filePath);
                    return response()->download($path, $file->filename_original);
                } else {
                    return response(__("File not found"), 404);
                }
            } else {
                return response(__("File not found"), 404);
            }
        } catch (\Exception $ex) {
            return response(__("File not found"), 404);
        }
    }

    public function updateParent(Request $request, $id, $parentId = 0)
    {
        $data = [];
        $parentId = 1 * $parentId;
        $form = Form::findOrFail($id);
        if ($form->folder_id !== $parentId) {
            if ($parentId) {
                $data['folder_id'] = $parentId;
            } else {
                $data['folder_id'] = null;
            }
            $form->update($data);
        }
        return $form;
    }
}
