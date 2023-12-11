<?php

namespace App\Http\Controllers;

use App\Service\RecordPdfService;
use App\Service\SettingsService;
use App\Service\WebfontService;
use App\Service\LeformService;
use App\Service\SystemVariablesService;
use App\Models\WebhookSecurityHash;
use App\Models\FormBackground;
use App\Models\FieldValue;
use App\Models\Webfont;
use App\Models\Preview;
use App\Models\Record;
use App\Models\Upload;
use App\Models\Style;
use App\Models\Form;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Http\Request;

use Faker\Factory as Faker;
use Firebase\JWT\JWT;

class FormHelperController extends Controller
{
  protected function getFormNameDynamicValues($formOptions)
  {
    $dynamicNameValues = array();
    if ($formOptions["has-dynamic-name-values"]) {
        $dynamicNameValuesMatches = [];
        preg_match_all(
            "/{{(.+?)}}/",
            $formOptions["dynamic-name-values"],
            $dynamicNameValuesMatches
        );
        $dynamicNameValues = $dynamicNameValuesMatches[1];
    }
    return $dynamicNameValues;
  }

  protected function renderForm($form, $token, $allQueryData)
  {
    if(!is_array($allQueryData)) {
      $allQueryData = [];
    }
    $getParameters = [];
    foreach($allQueryData as $key => $value) {
      $getParameters["get_$key"] = $value;
    } 
    // dd($getParameters);
    return view("testing", [
      "id" => $form->id,
      "customCss" => json_decode($form->options, true)["custom-css"],
      "jwtToken" => $token,
      "systemVariables" => SystemVariablesService::getSystemVariables(time()),
      "jwtVariables" => $this->getPredefinedValuesFromJWT(
          $form->{'company_id'},
          $token
      ),
      'getVariables' => $getParameters,
      "frontendTranslations" => __("frontend_translations")
    ]);
  }

  protected function getFormShortLink($shortLink)
  {
    return route('form-from-short-url', ["shortUrl" => $shortLink]);
  }

  protected function getFormLongLink($formName, $shortLink)
  {
    return route('form-from-name-and-id', [
        "formName" => Str::slug($formName),
        "shortUrl" => $shortLink,
    ]);
  }

  protected function compareUserWithFormCreator($user, $form)
  {
    return $form->company_id == $user->company_id;
  }

  protected function doesFormBelongToUser(Request $request, $formId)
  {
    $user = $request->user();
    $form = Form::where('id', $formId)
      ->where('deleted', 0)
      ->first();

    return $this->compareUserWithFormCreator($user, $form);
  }

  protected function cleanUnusedPdfFormBackgrounds($formId)
  {
    $form = Form::where("id", $formId)->first();

    $formOptions = json_decode($form->options, true);

    $unusedFormBackgrounds = FormBackground::where('form_id', $formId)
      ->where('filename', '!=', $formOptions['form-background-first-page-file'])
      ->where('filename', '!=', $formOptions['form-background-other-page-file'])
      ->get();

    $unusedFormBackgroundsIds = [];
    foreach ($unusedFormBackgrounds as $background) {
      if (Storage::disk('public')->exists($background->filename)) {
        Storage::disk('public')->delete($background->filename);
      }

      $unusedFormBackgroundsIds[] = $background->id;
    }

    FormBackground::whereIn('id', $unusedFormBackgroundsIds)
      ->delete();
  }

  protected function prepareRecordForWebhook($recordId)
  {
    $record = Record::where("id", $recordId)->first();
    $form = Form::where("id", $record->form_id)->first();

    $fields = json_decode($record->fields, true);

    $elements = json_decode($form->elements, true);
    $parsedElements = [];

    foreach ($elements as $element) {
      $parsedElement = json_decode($element, true);
      $parsedElements[$parsedElement["id"]] = $parsedElement;
    }

    $values = [];
    foreach ($fields as $id => $value) {
      $values[] = [
        "name" => $parsedElements[$id]["name"],
        "value" => $value,
      ];
    }

    return $values;
  }

  protected function getPredefinedValuesFromJWT($companyId, $token)
  {
    $predefinedValues = [];
    if ($companyId && $token) {
        $hasSettingsConfigured = SettingsService::arePredefinedValuesSettingsConfigured(
            $companyId
        );
        if ($hasSettingsConfigured) {
            try {
                $predefinedValuesSettings = SettingsService::getPredefinedValuesSettings(
                    $companyId
                );
                $secret = $predefinedValuesSettings["predefined-values-secret"];
                $tks = explode('.', $token);
                list($headb64, $bodyb64, $cryptob64) = $tks;
                $predefinedValues = (array) JWT::jsonDecode(JWT::urlsafeB64Decode($bodyb64));
                // $predefinedValues = (array) JWT::decode($token, $secret, ["HS256"]);
            } catch (\Exception $e) {
                $predefinedValues = [];
            }
        }
    }
    return $predefinedValues;
  }

  public function formFromShortUrl(Request $request, $shortUrl)
  {
      $allQueryData = $request->all();
      $jwtToken = null;
      if(isset($allQueryData['token'])) {
        $jwtToken = $allQueryData['token'];
        unset($allQueryData['token']);
      }
      $form = Form::where('deleted', 0)
          ->where('active', 1)
          ->where('short_link', $shortUrl)
          ->first();

      if (!$form) {
          return redirect()->route('not-found');
      }

      return $this->renderForm($form, $jwtToken, $allQueryData);
  }

  public function formFromNameAndId(Request $request, $formName, $shortUrl)
  {
    $allQueryData = $request->all();
    $jwtToken = null;
    if (isset($allQueryData['token'])) {
      $jwtToken = $allQueryData['token'];
      unset($allQueryData['token']);
    }
      $form = Form::where('deleted', 0)
          ->where('active', 1)
          ->where('short_link', $shortUrl)
          ->first();

      if (
          !$form
          || Str::slug($form->name) !== $formName
      ) {
          return redirect()->route('not-found');
      }

      return $this->renderForm($form, $jwtToken, $allQueryData);
  }

  public function adminStyleLoad(Request $request, LeformService $leform)
  {
    $callback = '';
    if ($request->has('callback')) {
      header("Content-type: text/javascript");
      $callback = preg_replace('/[^a-zA-Z0-9_]/', '', $request->input('callback'));
    }

    $style_id = null;
    if ($request->has('id')) {
      $style_id = $request->input('id');
      if (substr($style_id, 0, strlen('native-')) == 'native-') {
        $leform_native_styles = $leform->leform_native_styles;

        if (array_key_exists($style_id, $leform_native_styles)) {
          $return_data = [
            'status' => 'OK',
            'message' => __('Theme successfully applied.'),
            'options' => $leform_native_styles[$style_id]['options']
          ];
          if (!empty($callback)) {
            return $callback . '(' . json_encode($return_data) . ')';
          } else {
            return json_encode($return_data);
          }
        } else {
          $return_data = [
            'status' => 'ERROR',
            'message' => __('Requested theme not found.'),
          ];
          if (!empty($callback)) {
            return $callback . '(' . json_encode($return_data) . ')';
          } else {
            return json_encode($return_data);
          }
        }
      } else {
        $style_id = intval($style_id);
        #$style_details = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."leform_styles WHERE deleted = '0' AND id = '".esc_sql($style_id)."'", ARRAY_A);
        $style_details = Style::where('deleted', 0)
          ->where('id', $style_id)
          ->first();

        if (empty($style_details)) {
          $style_id = null;
        }

        if (empty($style_id)) {
          $return_data = [
            'status' => 'ERROR',
            'message' => __('Requested theme not found.'),
          ];
          if (!empty($callback)) {
            return $callback . '(' . json_encode($return_data) . ')';
          } else {
            return json_encode($return_data);
          }
        }
        $form_options = $leform->getDefaultFormOptions();
        $style_options = json_decode($style_details['options'], true);
        if (is_array($style_options)) {
          $style_options = array_intersect_key($style_options, $form_options);
        } else {
          $return_data = [
            'status' => 'ERROR',
            'message' => __('Invalid style parameters.'),
          ];
          if (!empty($callback)) {
            return $callback . '(' . json_encode($return_data) . ')';
          } else {
            return json_encode($return_data);
          }
        }
        $return_data = [
          'status' => 'OK',
          'message' => __('Theme successfully applied.'),
          'options' => $style_options
        ];
        if (!empty($callback)) {
          return $callback . '(' . json_encode($return_data) . ')';
        } else {
          return json_encode($return_data);
        }
      }
    } else {
      $return_data = [
        'status' => 'ERROR',
        'message' => __('Requested theme not found.'),
      ];
      if (!empty($callback)) {
        return $callback . '(' . json_encode($return_data) . ')';
      } else {
        return json_encode($return_data);
      }
    }
  }

  function adminStyleSave(Request $request, LeformService $leform)
  {
    $callback = '';
    if ($request->has('callback')) {
      header("Content-type: text/javascript");
      $callback = preg_replace('/[^a-zA-Z0-9_]/', '', $request->input('callback'));
    }

    $user = $request->user();

    if (
      $request->has('id')
      && substr($request->input('id'), 0, strlen('native-')) == 'native-'
    ) {
      $return_data = [
        'status' => 'ERROR',
        'message' => __('Can not save native theme.'),
      ];
      if (!empty($callback)) {
        return $callback . '(' . json_encode($return_data) . ')';
      } else {
        return json_encode($return_data);
      }
    }

    $form_options = $leform->getDefaultFormOptions();
    if ($request->has('options')) {
      $form_options_new = json_decode(base64_decode(trim(stripslashes(
        $request->input('options')
      ))), true);
      if (is_array($form_options_new)) {
        $form_options = array_intersect_key($form_options_new, $form_options);
      } else {
        $return_data = [
          'status' => 'ERROR',
          'message' => __('Invalid style parameters.'),
        ];
        if (!empty($callback)) {
          return $callback . '(' . json_encode($return_data) . ')';
        } else {
          return json_encode($return_data);
        }
      }
    }
    $style_id = null;
    if ($request->has('id')) {
      $style_id = intval($request->input('id'));
      #$style_details = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."leform_styles WHERE deleted = '0' AND id = '".esc_sql($style_id)."'", ARRAY_A);
      $style_details = Style::where('deleted', 0)
        ->where('id', $style_id)
        ->first();
      if (empty($style_details)) {
        $style_id = null;
      }
    }
    if (!empty($style_id)) {
      #$wpdb->query("UPDATE ".$wpdb->prefix."leform_styles SET options = '".esc_sql(json_encode($form_options))."' WHERE deleted = '0' AND id = '".esc_sql($style_id)."'");
      Style::where('deleted', 0)
        ->where('id', $style_id)
        ->update(['options' => json_encode($form_options)]);
    } else {
      $style_name = 'Nameless theme';
      if (
        $request->has('name')
        && !empty($request->input('name'))
      ) {
        $style_name = base64_decode($request->input('name'));
      } else if (
        $request->has('form-name')
        && !empty($request->input('form-name'))
      ) {
        $style_name = base64_decode($request->input('form-name')) . ' theme';
      } else {
        $style_name = 'Nameless theme';
      }
      #$wpdb->query("INSERT INTO ".$wpdb->prefix."leform_styles (name, options, type, deleted) VALUES ('".esc_sql($style_name)."', '".esc_sql(json_encode($form_options))."', '".esc_sql(LEFORM_STYLE_TYPE_USER)."', '0')");
      Style::create([
        'name' => $style_name,
        'options' => json_encode($form_options),
        'type' => 0, #LEFORM_STYLE_TYPE_USER
        'deleted' => 0,
        'user_id' => $user->id,
      ]);
    }

    $styles = $leform->get_styles();
    $return_data = [
      'status' => 'OK',
      'message' => __('Theme successfully saved.'),
      'styles' => $styles
    ];

    if (!empty($callback)) {
      return $callback . '(' . json_encode($return_data) . ')';
    } else {
      return json_encode($return_data);
    }
  }

  public function formBackgroundPdfUpload(Request $request)
  {
    $file = $request->file("file");
    if (!$file) {
      return response(__('File not sent correctly'), 400);
    }

    if (!$file->isValid()) {
      return response(__('File not valid'), 400);
    }

    if ($file->extension() != 'pdf') {
      return response(__('File needs to be an pdf'), 400);
    }

    $path = $file->store('public');

    $formId = null;
    if ($request->has('form_id')) {
      $form = Form::where('id', $request->input('form_id'))
        ->first();

      if ($form) {
        $formId = $form->id;
      }
    }

    FormBackground::create([
      'form_id' => $formId,
      'filename' => $path,
      'filename_original' => $file->getClientOriginalName(),
    ]);

    return ['filename' => $path];
  }
}
