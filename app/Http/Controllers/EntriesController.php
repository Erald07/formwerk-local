<?php

namespace App\Http\Controllers;

use App\Service\LeformFormService;
use App\Service\RecordPdfService;
use App\Service\LeformService;
use Illuminate\Http\Request;
use App\Models\FieldValue;
use App\Models\Record;
use App\Models\Form;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class EntriesController extends Controller
{
  public function entries(Request $request, LeformService $leform)
  {
    $form_id = $request->query('form', null);
    $dynamic_value = $request->query('dynamic-value', null);
    $search = $request->query("search");
    $archived = $request->query("archived") === '1';
    $primaryFieldValue = $request->query("primary_field_value");
    $secondaryFieldValue = $request->query("secondary_field_value");
    $sort_by = $request->query("sort_by", null);
    $sort_order = $request->query("sort_order", null);

    $forms = Form::get();

    $allowedFormIds = [];

    foreach ($forms as $form) {
      $allowedFormIds[] = $form['id'];
    }

    if ($form_id) {
      if (!in_array($form_id, $allowedFormIds)) {
        return response(__('Form does not belong to user'), 403);
      }
    }
    $mainQuery = Record::withoutGlobalScope('deleted')
      ->with('form');
      if($archived) {
        $mainQuery->whereNotNull('deleted_at');
        $mainQuery->withTrashed();
      }
    $rows = $mainQuery->when($form_id, function ($query, $value) {
            return $query->where('form_id', $value);
        }, function ($query) use ($allowedFormIds) {
            return $query->whereIn('form_id', $allowedFormIds);
        })
        ->when($dynamic_value, function ($query, $value) {
            return $query->where('dynamic_form_name_with_values', $value);
        })
        ->when($primaryFieldValue, function ($query, $value) {
            return $query->where('primary_field_value', $value);
        })
        ->when($secondaryFieldValue, function ($query, $value) {
            return $query->where('secondary_field_value', $value);
        })
        ->when($search, function ($query, $value) use ($form_id) {
            $search = "%". $value ."%";
            return $query->where(function ($query) use ($search, $form_id) {
                return $query->orWhere("primary_field_value", "like", $search)
                    ->orWhere("secondary_field_value", "like", $search)
                    ->orWhereExists(function ($query) use ($search) {
                        $query->from('laravel_uap_leform_forms')
                            ->whereColumn('laravel_uap_leform_forms.id', 'laravel_uap_leform_records.form_id')
                            ->where("name", "like", $search);
                    })
                    ->orWhere("amount", "like", $search)
                    ->orWhere("created_at", "like", $search)
                    ->orWhereExists(function ($query) use ($search) {
                        $query->from('laravel_uap_leform_fieldvalues')
                            ->whereColumn('laravel_uap_leform_records.id', 'laravel_uap_leform_fieldvalues.record_id')
                            ->whereColumn('laravel_uap_leform_records.form_id', 'laravel_uap_leform_fieldvalues.form_id')
                            ->where("value", "like", $search);
                    });
            });
        })
        ->when($sort_by && $sort_order, function ($query) use ($sort_by, $sort_order) {
            if ($sort_by === "form") {
                return $query->orderBy(
                    Form::select("name")
                        ->whereColumn("id", "form_id")
                        ->orderBy("name")
                        ->limit(1),
                    $sort_order
                );
            } else {
                return $query->orderBy($sort_by, $sort_order);
            }
        }, function ($query) {
            return $query->orderBy('created_at', 'desc');
        })
        ->paginate(20)
        ->withQueryString();

    if (count($rows) === 0 && $request->has('page')) {
        return redirect()->route(
            "entries",
            ["page" => intval($request->input('page')) - 1]
        );
    }

    $form_entries_map = [];
    $form_entries_map = Record::groupBy("dynamic_form_name_with_values")
        ->select("dynamic_form_name_with_values")
        ->having("dynamic_form_name_with_values", "!=", "")
        ->when($form_id, function ($query, $value) {
            return $query->where("form_id", $value);
        })
        ->get()
        ->toArray();
    $form_entries_map_array = [];
    foreach ($form_entries_map as $dynamicName) {
        $form_entries_map_array[] = $dynamicName["dynamic_form_name_with_values"];
    }

    if (
      $form_id !== null
      && $dynamic_value !== null
      && !in_array($dynamic_value, $form_entries_map_array)
    ) {
      return redirect()->route("entries", ["form" => $form_id]);
    }

    if (
      $form_id === null
      && $dynamic_value !== null
    ) {
      return redirect()->route("entries");
    }

    $selectedForm = null;
    if ($form_id !== null) {
      $selectedForm = Form::firstWhere("id", $form_id);
    }

    $filters = [
        "search" => $search,
        "form" => $form_id,
        "dynamic_value" => $dynamic_value,
        "primary_field_value" => $request->query("primary_field_value"),
        "secondary_field_value" => $request->query("secondary_field_value"),
        "sort_by" => $request->query("sort_by"),
        "sort_order" => $request->query("sort_order"),
    ];

    $hasFilters = false;
    foreach ($filters as $filterKey => $filterValue) {
        if ($filterValue !== null) {
            $hasFilters = true;
        }
    }

    return view('entries', [
      'form_id' => $form_id,
      'dynamic_value' => $dynamic_value,
      'search_query' => '',
      'forms' => $forms,
      'rows' => $rows,
      'leform' => $leform,
      'form_entries_map' => $form_entries_map_array,
      "selectedForm" => $selectedForm,
      "frontendTranslations" => __("frontend_translations"),
      "filters" => $filters,
      "hasFilters" => $hasFilters,
    ]);
  }

  public function entriesDetails(Request $request, LeformService $leform)
  {
    $callback = '';
    if (isset($_REQUEST['callback'])) {
      header("Content-type: text/javascript");
      $callback = preg_replace('/[^a-zA-Z0-9_]/', '', $_REQUEST['callback']);
    }

    $record_id = null;
    if (array_key_exists('record-id', $_REQUEST)) {
      $record_id = intval($_REQUEST['record-id']);
    }

    $record = Record::with('form')
      ->where('deleted', 0)
      ->where('id', $record_id)
      ->first();

    if (
      $record
      && $record['form']['company_id'] != $request->user()->company_id
    ) {
      return response(__('Form does not belong to user'), 403);
    }

    $return_data = $leform->log_record_details_html($record_id);

    if (!empty($callback)) {
      return $callback . '(' . json_encode($return_data) . ')';
    } else {
      return json_encode($return_data);
    }
  }

  public function entriesActions(Request $request, LeformService $leform)
  {
    if ($request->has('action')) {
      $action = $request->input('action');
      switch ($action) {
        case 'delete':
          if ($request->has('ids')) {
            $this->deleteAllEntries($request->input('ids'), $leform);
          }
      }
    }
  }

  public function deleteEntry(Request $request, LeformService $leform)
  {
    $callback = '';
    if ($request->has('callback')) {
      header("Content-type: text/javascript");
      $callback = preg_replace('/[^a-zA-Z0-9_]/', '', $request->input('callback'));
    }

    $record_id = null;
    if ($request->has('record-id')) {
      $record_id = intval($request->input('record-id'));

      $record_details = Record::with('form')
        ->where('deleted', 0)
        ->where('id', $record_id)
        ->first();

      if (empty($record_details)) {
        $record_id = null;
      } else {
        if ($record_details['form']['company_id'] != $request->user()->company_id) {
          return response(__('Form does not belong to user'), 403);
        }
      }
    }

    if (empty($record_id)) {
      $return_data = [
        'status' => 'ERROR',
        'message' => __('Requested record not found.'),
      ];
      if (!empty($callback)) {
        return $callback . '(' . json_encode($return_data) . ')';
      } else {
        return json_encode($return_data);
      }
    }

    $this->deleteFullRecord($record_details, $leform);

    // delete files
    $this->deleteFiles($record_details);

    $return_data = [
      'status' => 'OK',
      'message' => __('The record successfully deleted.'),
    ];
    if (!empty($callback)) {
      return $callback . '(' . json_encode($return_data) . ')';
    } else {
      return json_encode($return_data);
    }
  }

  private function deleteAllEntries($ids, $leform)
  {
    foreach ($ids as $id) {
      $record_id = null;
      if ($id) {
        $record_id = intval($id);
        $record_details = Record::with('form')
          ->where('deleted', 0)
          ->where('id', $record_id)
          ->first();

        if (empty($record_details)) {
          $record_id = null;
        }
      }
      if (!empty($record_id)) {
        $this->deleteFullRecord($record_details, $leform);
      }
    }
  }

  private function deleteFullRecord($record_details, $leform)
  {
    $record_id = $record_details['id'];
    $leform->uploads_delete($record_id);
    $leform->delete_generated_files($record_details);
    Record::where('deleted', 0)
      ->where('id', $record_id)
      ->update(['deleted' => 1, 'deleted_at' => now()]);
    FieldValue::where('deleted', 0)
      ->where('record_id', $record_id)
      ->update(['deleted' => 1, 'deleted_at' => now()]);
  }

  private function deleteFiles($record)
  {
    //, 'csv_file_name', 'custom_report_file_name'
    $fileTypes = ['xml_file_name'];
    foreach ($fileTypes as $fileType) {
      if (isset($record[$fileType]) && !empty($record[$fileType])) {
        if (Storage::disk("private")->exists($record[$fileType])) {
          Storage::disk("private")->delete($record[$fileType]);
        }
        $record[$fileType] = null;
      }
    }
    return $record;
  }

  public function recordFieldLoadEditor(Request $request)
  {
    $callback = '';
    if ($request->has('callback')) {
      header("Content-type: text/javascript");
      $callback = preg_replace('/[^a-zA-Z0-9_]/', '', $request->input('callback'));
    }

    $record_id = null;
    if ($request->has('record-id')) {
      $record_id = intval($request->input('record-id'));

      $record_details = Record::with('form')
        ->where('deleted', 0)
        ->where('id', $record_id)
        ->first();


      if (empty($record_details)) {
        $record_id = null;
      } else {
        if ($record_details['form']['company_id'] != $request->user()->company_id) {
          return response(__('Form does not belong to user'), 403);
        }
      }
    }

    if (empty($record_id)) {
      $return_data = [
        'status' => 'ERROR',
        'message' => __('Requested record not found.'),
      ];
      if (!empty($callback)) {
        return $callback . '(' . json_encode($return_data) . ')';
      } else {
        return json_encode($return_data);
      }
    }

    $field_id = null;
    if ($request->has('field-id')) {
      $field_id = intval($request->input('field-id'));
    }

    $fields = json_decode($record_details['fields'], true);
    if (
      empty($field_id)
      || empty($fields)
      || !array_key_exists($field_id, $fields)
    ) {
      $return_data = [
        'status' => 'ERROR',
        'message' => __('Requested field not found.'),
      ];
      if (!empty($callback)) {
        return $callback . '(' . json_encode($return_data) . ')';
      } else {
        return json_encode($return_data);
      }
    }

    $form_object = new LeformFormService($record_details['form_id'], false);

    if (empty($form_object->id)) {
      $form_id = null;
    } else {
      $form_id = $form_object->id;
    }

    if (empty($form_id)) {
      $return_data = [
        'status' => 'ERROR',
        'message' => __('Form does not exist.'),
      ];
      if (!empty($callback)) {
        return $callback . '(' . json_encode($return_data) . ')';
      } else {
        return json_encode($return_data);
      }
    }

    $form_object->form_data = json_decode($record_details['fields'], true);
    $return_data = $form_object->get_field_editor($field_id, $fields[$field_id]);
    if ($return_data['status'] == 'OK') {
      $return_data['html'] .= '<div class="leform-record-field-editor-buttons"><a class="leform-admin-button" href="#" onclick="return leform_record_field_save(this);"><i class="fas fa-save"></i><label>'
        . __('Save')
        . '</label></a><a class="leform-admin-button leform-admin-button-gray" href="#" onclick="return leform_record_field_cancel_editor(this);"><i class="fas fa-times"></i><label>'
        . __('Cancel')
        . '</label></a></div>';
    }

    if (!empty($callback)) {
      return $callback . '(' . json_encode($return_data) . ')';
    } else {
      return json_encode($return_data);
    }
  }

  private function updatePrimaryFieldValueOnFieldUpdate($record, $updatedFieldId, $value)
  {
    if (intval($record->primary_field_id) === $updatedFieldId) {
        Record::where('id', $record['id'])
            ->update([
                'primary_field_value' => is_array($value)
                    ? implode(", ", $value)
                    : $value
            ]);
    }
  }

  private function updateSecondaryFieldValueOnFieldUpdate($record, $updatedFieldId, $value)
  {
    if (intval($record->secondary_field_id) === $updatedFieldId) {
        Record::where('id', $record['id'])
            ->update([
                'secondary_field_value' => is_array($value)
                    ? implode(", ", $value)
                    : $value
            ]);
    }
  }

  public function recordFieldSave(Request $request, LeformService $leform)
  {
    $callback = '';
    if ($request->has('callback')) {
      header("Content-type: text/javascript");
      $callback = preg_replace('/[^a-zA-Z0-9_]/', '', $request->input('callback'));
    }

    $record_id = null;
    if ($request->has('record-id')) {
      $record_id = intval($request->input('record-id'));

      #$record_details = $wpdb->get_row( "SELECT * FROM ".$wpdb->prefix."leform_records WHERE deleted = '0' AND id = '".esc_sql($record_id)."'", ARRAY_A);
      $record_details = Record::with('form')
        ->where('deleted', 0)
        ->where('id', $record_id)
        ->first();

      if (empty($record_details)) {
        $record_id = null;
      } else {
        if ($record_details['form']['company_id'] != $request->user()->company_id) {
          return response(__('Form does not belong to user'), 403);
        }
      }
    }

    if (empty($record_id)) {
      $return_data = [
        'status' => 'ERROR',
        'message' => __('Requested record not found.'),
      ];
      if (!empty($callback)) {
        return $callback . '(' . json_encode($return_data) . ')';
      } else {
        return json_encode($return_data);
      }
    }

    $field_id = null;
    if ($request->has('field-id')) {
      $field_id = intval($request->input('field-id'));
    }

    $fields = json_decode($record_details['fields'], true);
    if (
      empty($field_id)
      || empty($fields)
      || !array_key_exists($field_id, $fields)
    ) {
      $return_data = [
        'status' => 'ERROR',
        'message' => __('Requested field not found.'),
      ];
      if (!empty($callback)) {
        return $callback . '(' . json_encode($return_data) . ')';
      } else {
        return json_encode($return_data);
      }
    }

    if (!array_key_exists('value', $_REQUEST)) {
      $return_data = [
        'status' => 'ERROR',
        'message' => __('New value not found.'),
      ];
      if (!empty($callback)) {
        return $callback . '(' . json_encode($return_data) . ')';
      } else {
        return json_encode($return_data);
      }
    }

    $value = [];
    parse_str(base64_decode($_REQUEST['value']), $value);
    $fields[$field_id] = $value['value'];

    Record::where('deleted', 0)
      ->where('id', $record_details['id'])
      ->update(['fields' => json_encode($fields)]);

    FieldValue::where('deleted', 0)
      ->where('record_id', $record_details['id'])
      ->where('field_id', $field_id)
      ->delete();

    $datestamp = date('Ymd', time() + 3600 * $leform->gmt_offset);
    if (is_array($value['value'])) {
      foreach ($value['value'] as $option) {
        FieldValue::create([
          'form_id' => $record_details['form_id'],
          'record_id' => $record_details['id'],
          'field_id' => $field_id,
          'value' => is_array($option) ? json_encode($option) : $option,
          'datestamp' => $datestamp,
          'deleted' => 0,
        ]);
      }
    } else {
      FieldValue::create([
        'form_id' => $record_details['form_id'],
        'record_id' => $record_details['id'],
        'field_id' => $field_id,
        'value' => $value['value'],
        'datestamp' => $datestamp,
        'deleted' => 0,
      ]);
    }

    $this->updatePrimaryFieldValueOnFieldUpdate(
        $record_details,
        $field_id,
        $value["value"]
    );
    $this->updateSecondaryFieldValueOnFieldUpdate(
        $record_details,
        $field_id,
        $value["value"]
    );

    $form = Form::where('id', $record_details['form_id'])
      ->first();

    $fields = json_decode($record_details['fields'], true);
    $formElements = json_decode($form['elements'], true);
    $changingElement = null;

    foreach ($formElements as $element) {
      $parsedElement = json_decode($element, true);

      if ($parsedElement['id'] == $field_id) {
        $changingElement = $parsedElement;
        break;
      }
    }

    if ($changingElement['type'] == 'matrix') {
      $html = LeformService::renderMatrixElement($changingElement, $value['value']);
    } else if ($changingElement['type'] == 'repeater-input') {
      $html = LeformService::renderRepeaterInput($changingElement, $value['value']);
    } else if ($changingElement['type'] == 'iban-input') {
      $html = LeformService::renderIbanInput($changingElement, $value['value'], false, ["user_iban" => 1]);
    } else if (is_array($value['value'])) {
      foreach ($value['value'] as $key => $values_value) {
        $values_value = trim($values_value);
        if ($values_value == "") {
          $value['value'][$key] = "-";
        } else {
          $value['value'][$key] = $values_value;
        }
      }
      $html = implode("<br />", $value['value']);
    } else if ($value['value'] != "") {
      $value_strings = explode("\n", $value['value']);
      foreach ($value_strings as $key => $values_value) {
        $value_strings[$key] = trim($values_value);
      }
      $html = implode("<br />", $value_strings);
    } else {
      $html = "-";
    }

    $return_data = [
      'status' => 'OK',
      'html' => $html,
      'message' => __('The field value successfully saved.'),
    ];

    if (!empty($callback)) {
      return $callback . '(' . json_encode($return_data) . ')';
    } else {
      return json_encode($return_data);
    }
  }

  public function recordFieldEmpty(Request $request, LeformService $leform)
  {
    $callback = '';
    if ($request->has('callback')) {
      header("Content-type: text/javascript");
      $callback = preg_replace('/[^a-zA-Z0-9_]/', '', $request->input('callback'));
    }

    $record_id = null;
    if ($request->has('record-id')) {
      $record_id = intval($request->input('record-id'));

      #$record_details = $wpdb->get_row( "SELECT * FROM ".$wpdb->prefix."leform_records WHERE deleted = '0' AND id = '".esc_sql($record_id)."'", ARRAY_A);
      $record_details = Record::with('form')
        ->where('deleted', 0)
        ->where('id', $record_id)
        ->first();

      if (empty($record_details)) {
        $record_id = null;
      } else {
        if ($record_details['form']['company_id'] != $request->user()->company_id) {
          return response(__('Form does not belong to user'), 403);
        }
      }
    }

    if (empty($record_id)) {
      $return_data = [
        'status' => 'ERROR',
        'message' => __('Requested record not found.'),
      ];
      if (!empty($callback)) {
        return $callback . '(' . json_encode($return_data) . ')';
      } else {
        return json_encode($return_data);
      }
    }

    $field_id = null;
    if ($request->has('field-id')) {
      $field_id = intval($request->input('field-id'));
    }

    $fields = json_decode($record_details['fields'], true);
    if (
      empty($field_id)
      || empty($fields)
      || !array_key_exists($field_id, $fields)
    ) {
      $return_data = [
        'status' => 'ERROR',
        'message' => __('Requested field not found.'),
      ];
      if (!empty($callback)) {
        return $callback . '(' . json_encode($return_data) . ')';
      } else {
        return json_encode($return_data);
      }
    }

    $fields[$field_id] = "";

    Record::where('deleted', 0)
      ->where('id', $record_details['id'])
      ->update(['fields' => json_encode($fields)]);

    FieldValue::where('deleted', 0)
      ->where('record_id', $record_details['id'])
      ->where('field_id', $field_id)
      ->update(['value' => '']);

    DB::raw("
            DELETE t1 FROM laravel_uap_leform_fieldvalues t1
            INNER JOIN laravel_uap_leform_fieldvalues t2
            WHERE
                t1.id < t2.id
                AND t1.record_id = t2.record_id
                AND t1.field_id = t2.field_id
                AND t1.value = ''
                AND t2.value = ''
        ");

    $this->updatePrimaryFieldValueOnFieldUpdate(
        $record_details,
        $field_id,
        ""
    );
    $this->updateSecondaryFieldValueOnFieldUpdate(
        $record_details,
        $field_id,
        ""
    );

    $leform->uploads_delete($record_details['id'], $field_id);

    $return_data = [
      'status' => 'OK',
      'message' => __('The field successfully emptied.'),
    ];
    if (!empty($callback)) {
      return $callback . '(' . json_encode($return_data) . ')';
    } else {
      return json_encode($return_data);
    }
  }

  public function recordFieldRemove(Request $request, LeformService $leform)
  {
    $callback = '';
    if ($request->has('callback')) {
      header("Content-type: text/javascript");
      $callback = preg_replace('/[^a-zA-Z0-9_]/', '', $request->input('callback'));
    }

    $record_id = null;
    if ($request->has('record-id')) {
      $record_id = intval($request->input('record-id'));

      #$record_details = $wpdb->get_row( "SELECT * FROM ".$wpdb->prefix."leform_records WHERE deleted = '0' AND id = '".esc_sql($record_id)."'", ARRAY_A);
      $record_details = Record::with('form')
        ->where('deleted', 0)
        ->where('id', $record_id)
        ->first();

      if (empty($record_details)) {
        $record_id = null;
      } else {
        if ($record_details['form']['company_id'] != $request->user()->company_id) {
          return response(__('Form does not belong to user'), 403);
        }
      }
    }

    if (empty($record_id)) {
      $return_data = [
        'status' => 'ERROR',
        'message' => __('Requested record not found.'),
      ];
      if (!empty($callback)) {
        return $callback . '(' . json_encode($return_data) . ')';
      } else {
        return json_encode($return_data);
      }
    }

    $field_id = null;
    if ($request->has('field-id')) {
      $field_id = intval($request->input('field-id'));
    }

    $fields = json_decode($record_details['fields'], true);
    if (
      empty($field_id)
      || empty($fields)
      || !array_key_exists($field_id, $fields)
    ) {
      $return_data = [
        'status' => 'ERROR',
        'message' => __('Requested field not found.'),
      ];
      if (!empty($callback)) {
        return $callback . '(' . json_encode($return_data) . ')';
      } else {
        return json_encode($return_data);
      }
    }

    unset($fields[$field_id]);

    Record::where('deleted', 0)
      ->where('id', $record_details['id'])
      ->update(['fields' => json_encode($fields)]);

    FieldValue::where('deleted', 0)
      ->where('record_id', $record_details['id'])
      ->where('field_id', $field_id)
      ->update(['deleted' => 1]);;

    $leform->uploads_delete($record_details['id'], $field_id);

    $this->updatePrimaryFieldValueOnFieldUpdate(
        $record_details,
        $field_id,
        null
    );
    $this->updateSecondaryFieldValueOnFieldUpdate(
        $record_details,
        $field_id,
        null
    );

    $return_data = [
      'status' => 'OK',
      'message' => __('The field successfully emptied.'),
    ];

    if (!empty($callback)) {
      return $callback . '(' . json_encode($return_data) . ')';
    } else {
      return json_encode($return_data);
    }
  }

  public function recordPdfDownload(Request $request, $recordId)
  {
    $record = RecordPdfService::getDecodedRecord($recordId);
    $form = RecordPdfService::getDecodedForm($record["form_id"]);

    if (!$record) {
      return response(__("Record not found"), 404);
    }

    if (!$form) {
      return response(__("Form of record not found"), 404);
    }

    $pdfFile = RecordPdfService::generateRecordPdf($record, $form);
    $pdfFileName = RecordPdfService::generateRecordPdfName($record, $form);

    return $pdfFile;
    return response()->streamDownload(function () use ($pdfFile) {
      echo $pdfFile;
    }, $pdfFileName);
  }
}
