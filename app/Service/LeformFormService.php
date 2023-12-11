<?php

namespace App\Service;

use App\Service\LeformService;
use App\Service\SettingsService;
use App\Models\FieldValue;
use App\Models\Preview;
use App\Models\Webfont;
use App\Models\Record;
use App\Models\Upload;
use App\Models\Stat;
use App\Models\Form;
use DateTime;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class LeformFormService
{
    var $form_options, $form_pages, $leform, $form_elements, $form_inputs, $form_logic, $form_dependencies, $id = null, $name, $company_id, $form_dynamic_name_values;
    var $cache_html = null, $cache_style = null, $cache_uids = [], $cache_time = null;
    var $form_data = [], $form_info = [];
    var $preview = false;
    var $record_id = 0;
    var $mappedFormElements = [];
    var $predefinedValues = [];
    var $systemVariables = [];

    function __construct($_id, $_preview = false, $_include_deleted = false)
    {
        $this->leform = new LeformService;
        $this->preview = $_preview;
        $_id = intval($_id);
        if ($_preview) {
            $form_details = null;
            #$wpdb->get_row(
            #    "SELECT * FROM ".$wpdb->prefix."leform_previews
            #    WHERE ".(!$_include_deleted ? "deleted = '0' AND " : "")."form_id = '".esc_sql($_id)."'", ARRAY_A);
            if (!$_include_deleted) {
                $form_details = Preview::where('form_id', $_id)
                    ->where('deleted', 0)
                    ->first();
            } else {
                $form_details = Preview::where('form_id', $_id)
                    ->first();
            }
        } else {
            #$form_details = $wpdb->get_row(
            #    "SELECT * FROM ".$wpdb->prefix."leform_forms
            #    WHERE ".(!$_include_deleted ? "deleted = '0' AND " : "")."id = '".esc_sql($_id)."' AND active = '1'", ARRAY_A);
            if (!$_include_deleted) {
                $form_details = Form::where('id', $_id)
                    ->where('deleted', 0)
                    ->where('active', 1)
                    ->first();
            } else {
                $form_details = Form::where('id', $_id)
                    ->where('active', 1)
                    ->first();
            }
        }

        if (empty($form_details)) {
            return;
        }

        if ($_preview) {
            $this->id = $form_details['form_id'];
        } else {
            $this->id = $form_details['id'];
            $this->cache_html = $form_details['cache_html'];
            $this->cache_style = $form_details['cache_style'];
            $this->cache_uids = json_decode($form_details['cache_uids'], true);
            $this->cache_time = $form_details['cache_time'];
        }
        $this->name = $form_details['name'];
        $this->company_id = $form_details['company_id'];

        $default_form_options = $this->leform->getDefaultFormOptions();
        $this->form_options = json_decode($form_details['options'], true);
        $this->form_dynamic_name_values = (
            ($form_details['dynamic_name_values'] === null)
            ? []
            : json_decode($form_details['dynamic_name_values'])
        );

        if (!empty($this->form_options)) {
            $this->form_options = array_merge($default_form_options, $this->form_options);
        } else {
            $this->form_options = $default_form_options;
        }

        $this->form_pages = json_decode($form_details['pages'], true);
        $default_page_options = $this->leform->getDefaultFormOptions("page");
        if (is_array($this->form_pages)) {
            foreach ($this->form_pages as $key => $form_page) {
                if (is_array($form_page)) {
                    $this->form_pages[$key] = array_merge($default_page_options, $form_page);
                } else {
                    unset($this->form_pages[$key]);
                }
            }
            $this->form_pages = array_values($this->form_pages);
        } else {
            $this->form_pages = [];
        }

        $this->form_elements = json_decode($form_details['elements'], true);
        if (is_array($this->form_elements)) {
            foreach ($this->form_elements as $key => $form_element_raw) {
                $element_options = json_decode($form_element_raw, true);
                if (
                    is_array($element_options)
                    && array_key_exists('type', $element_options)
                ) {
                    $default_element_options = $this->leform->getDefaultFormOptions($element_options['type']);
                    $element_options = array_merge($default_element_options, $element_options);
                    $this->form_elements[$key] = $element_options;
                } else {
                    unset($this->form_elements[$key]);
                }
            }
            $this->form_elements = array_values($this->form_elements);
        } else {
            $this->form_elements = [];
        }

        $this->form_inputs = [];
        for ($i = 0; $i < sizeof($this->form_elements); $i++) {
            if (
                array_key_exists($this->form_elements[$i]['type'], $this->leform->toolbarTools)
                && $this->leform->toolbarTools[$this->form_elements[$i]['type']]['type'] == 'input'
            ) {
                $this->form_inputs[] = $this->form_elements[$i]['id'];
            }
        }
        $mapElementByName = [];
        foreach ($this->form_elements as $form_element) {
            if (isset($form_element['name']) && !isset($mapElementByName[$form_element['name']])) {
                $mapElementByName[$form_element['name']] = $form_element['id'];
            }
            if (!array_key_exists($form_element['type'], $this->leform->toolbarTools)) {
                continue;
            }
            $this->mappedFormElements[$form_element['id']] = $form_element;
        }
        $this->form_logic = [];
        $this->form_dependencies = [];
        for ($i = 0; $i < sizeof($this->form_elements); $i++) {
            if (
                array_key_exists('logic-enable', $this->form_elements[$i])
                && $this->form_elements[$i]['logic-enable'] == 'on'
                && array_key_exists('logic', $this->form_elements[$i])
                && is_array($this->form_elements[$i]['logic'])
                && array_key_exists('rules', $this->form_elements[$i]['logic'])
                && is_array($this->form_elements[$i]['logic']['rules'])
            ) {
                $logic = [
                    'action' => $this->form_elements[$i]['logic']['action'],
                    'operator' => $this->form_elements[$i]['logic']['operator'],
                    'rules' => []
                ];
                foreach ($this->form_elements[$i]['logic']['rules'] as $rule) {
                    if (in_array($rule['field'], $this->form_inputs)) {
                        $logic['rules'][] = $rule;
                        if (
                            !array_key_exists($rule['field'], $this->form_dependencies)
                            || !is_array($this->form_dependencies[$rule['field']])
                            || !in_array($this->form_elements[$i]['id'], $this->form_dependencies[$rule['field']])
                        ) {
                            $this->form_dependencies[$rule['field']][] = $this->form_elements[$i]['id'];
                        }

                        $otherDependencies = [];
                        preg_match_all("/\{\{form_.*\}\}/", $rule['token'], $otherDependencies);
                        foreach ($otherDependencies[0] as $index => $name) {
                            $name = str_replace("{{form_", "", $name);
                            $name = str_replace("}}", "", $name);
                            if (isset($mapElementByName[$name])) {
                                $element_id = intval($mapElementByName[$name]);
                                $this->form_dependencies[$element_id][] = $this->form_elements[$i]['id'];
                            }
                        }
                    }
                }
                if (!empty($logic['rules'])) {
                    $this->form_logic[$this->form_elements[$i]['id']] = $logic;
                }
            }
        }
        for ($i = 0; $i < sizeof($this->form_pages); $i++) {
            if (
                array_key_exists('logic-enable', $this->form_pages[$i])
                && $this->form_pages[$i]['logic-enable'] == 'on'
                && array_key_exists('logic', $this->form_pages[$i])
                && is_array($this->form_pages[$i]['logic'])
                && array_key_exists('rules', $this->form_pages[$i]['logic'])
                && is_array($this->form_pages[$i]['logic']['rules'])
            ) {
                $logic = [
                    'action' => $this->form_pages[$i]['logic']['action'],
                    'operator' => $this->form_pages[$i]['logic']['operator'],
                    'rules' => []
                ];
                foreach ($this->form_pages[$i]['logic']['rules'] as $rule) {
                    if (in_array($rule['field'], $this->form_inputs)) {
                        $logic['rules'][] = $rule;
                    }
                }
                if (!empty($logic['rules'])) {
                    $this->form_logic[$this->form_pages[$i]['id']] = $logic;
                }
            }
        }
        if (
            array_key_exists('confirmations', $this->form_options)
            && is_array($this->form_options['confirmations'])
        ) {
            for ($i = 0; $i < sizeof($this->form_options['confirmations']); $i++) {
                if (
                    array_key_exists('logic-enable', $this->form_options['confirmations'][$i])
                    && $this->form_options['confirmations'][$i]['logic-enable'] == 'on'
                    && array_key_exists('logic', $this->form_options['confirmations'][$i])
                    && is_array($this->form_options['confirmations'][$i]['logic'])
                    && array_key_exists('rules', $this->form_options['confirmations'][$i]['logic'])
                    && is_array($this->form_options['confirmations'][$i]['logic']['rules'])
                ) {
                    $logic = [
                        'action' => $this->form_options['confirmations'][$i]['logic']['action'],
                        'operator' => $this->form_options['confirmations'][$i]['logic']['operator'],
                        'rules' => []
                    ];
                    foreach ($this->form_options['confirmations'][$i]['logic']['rules'] as $rule) {
                        if (in_array($rule['field'], $this->form_inputs)) {
                            $logic['rules'][] = $rule;
                        }
                    }
                    if (!empty($logic['rules'])) {
                        $this->form_logic['confirmation-' . $i] = $logic;
                    }
                }
            }
        }
        if (array_key_exists('notifications', $this->form_options) && is_array($this->form_options['notifications'])) {
            for ($i = 0; $i < sizeof($this->form_options['notifications']); $i++) {
                if (array_key_exists('logic-enable', $this->form_options['notifications'][$i]) && $this->form_options['notifications'][$i]['logic-enable'] == 'on' && array_key_exists('logic', $this->form_options['notifications'][$i]) && is_array($this->form_options['notifications'][$i]['logic']) && array_key_exists('rules', $this->form_options['notifications'][$i]['logic']) && is_array($this->form_options['notifications'][$i]['logic']['rules'])) {
                    $logic = [
                        'action' => $this->form_options['notifications'][$i]['logic']['action'],
                        'operator' => $this->form_options['notifications'][$i]['logic']['operator'],
                        'rules' => []
                    ];
                    foreach ($this->form_options['notifications'][$i]['logic']['rules'] as $rule) {
                        if (in_array($rule['field'], $this->form_inputs)) {
                            $logic['rules'][] = $rule;
                        }
                    }
                    if (!empty($logic['rules'])) {
                        $this->form_logic['notification-' . $i] = $logic;
                    }
                }
            }
        }
        if (array_key_exists('integrations', $this->form_options) && is_array($this->form_options['integrations'])) {
            for ($i = 0; $i < sizeof($this->form_options['integrations']); $i++) {
                if (array_key_exists('logic-enable', $this->form_options['integrations'][$i]) && $this->form_options['integrations'][$i]['logic-enable'] == 'on' && array_key_exists('logic', $this->form_options['integrations'][$i]) && is_array($this->form_options['integrations'][$i]['logic']) && array_key_exists('rules', $this->form_options['integrations'][$i]['logic']) && is_array($this->form_options['integrations'][$i]['logic']['rules'])) {
                    $logic = [
                        'action' => $this->form_options['integrations'][$i]['logic']['action'],
                        'operator' => $this->form_options['integrations'][$i]['logic']['operator'],
                        'rules' => []
                    ];
                    foreach ($this->form_options['integrations'][$i]['logic']['rules'] as $rule) {
                        if (in_array($rule['field'], $this->form_inputs)) {
                            $logic['rules'][] = $rule;
                        }
                    }
                    if (!empty($logic['rules'])) {
                        $this->form_logic['integration-' . $i] = $logic;
                    }
                }
            }
        }
    }

    public function setVariables($predifenedValues = [], $systemVariables = [])
    {
        $allVariables = evo_get_all_variables($predifenedValues);
        $this->predefinedValues = $allVariables;
        $this->systemVariables = $systemVariables;
    }

    protected function _leform_input_sort($_parent, $_parent_col, $_page_id, $_page_name)
    {
        $input_fields = [];
        $fields = [];
        $idxs = [];
        $seqs = [];
        for ($i = 0; $i < sizeof($this->form_elements); $i++) {
            if (empty($this->form_elements[$i])) {
                continue;
            }
            if (
                $this->form_elements[$i]["_parent"] == $_parent
                && ($this->form_elements[$i]["_parent-col"] == $_parent_col || $_parent == "")
            ) {
                $idxs[] = $i;
                $seqs[] = intval($this->form_elements[$i]["_seq"]);
            }
        }
        if (empty($idxs)) {
            return $input_fields;
        }
        for ($i = 0; $i < sizeof($seqs); $i++) {
            $sorted = -1;
            for ($j = 0; $j < sizeof($seqs) - 1; $j++) {
                if ($seqs[$j] > $seqs[$j + 1]) {
                    $sorted = $seqs[$j];
                    $seqs[$j] = $seqs[$j + 1];
                    $seqs[$j + 1] = $sorted;
                    $sorted = $idxs[$j];
                    $idxs[$j] = $idxs[$j + 1];
                    $idxs[$j + 1] = $sorted;
                }
            }
            if ($sorted == -1) {
                break;
            }
        }
        for ($k = 0; $k < sizeof($idxs); $k++) {
            $i = $idxs[$k];
            if (empty($this->form_elements[$i])) {
                continue;
            }
            if (
                array_key_exists($this->form_elements[$i]['type'], $this->leform->toolbarTools)
                && $this->leform->toolbarTools[$this->form_elements[$i]['type']]['type'] == 'input'
            ) {
                $input_fields[] = array_merge(
                    $this->form_elements[$i],
                    ['page-id' => $_page_id, 'page-name' => $_page_name]
                ); //array('id' => $this->form_elements[$i]['id'], 'name' => $this->form_elements[$i]['name'], 'page-id' => $_page_id, 'page-name' => $_page_name);
            } else if ($this->form_elements[$i]['type'] == "columns") {
                for ($j = 0; $j < $this->form_elements[$i]['_cols']; $j++) {
                    $fields = $this->_leform_input_sort($this->form_elements[$i]['id'], $j, $_page_id, $_page_name);
                    if (!empty($fields)) {
                        $input_fields = array_merge($input_fields, $fields);
                    }
                }
            }
        }
        return $input_fields;
    }
    protected function _get_children_ids($_parent)
    {
        $children = [];
        for ($i = 0; $i < sizeof($this->form_elements); $i++) {
            if (empty($this->form_elements[$i])) {
                continue;
            }
            if ($this->form_elements[$i]['_parent'] == $_parent) {
                if ($this->form_elements[$i]['type'] == 'columns') {
                    $children = array_merge($children, $this->_get_children_ids($this->form_elements[$i]['id']));
                } else {
                    $children[] = $this->form_elements[$i]['id'];
                }
            }
        }
        return $children;
    }

    public function get_pages()
    {
        if (empty($this->id)) {
            return false;
        }
        $pages = [];
        for ($i = 0; $i < sizeof($this->form_pages); $i++) {
            if (!empty($this->form_pages[$i]) && is_array($this->form_pages[$i])) {
                $pages[$this->form_pages[$i]['id']] = $this->_get_children_ids($this->form_pages[$i]['id']);
            }
        }
        return $pages;
    }

    protected function _filter_value($_value, $_filters)
    {
        if (!is_array($_filters) || empty($_filters)) {
            return $_value;
        }

        $values = [];
        if (is_array($_value)) {
            $values = $_value;
        } else {
            $values[] = $_value;
        }

        foreach ($values as $key => $value) {
            foreach ($_filters as $filter) {
                switch ($filter['type']) {
                    case 'trim':
                        $value = trim($value);
                        break;
                    case 'alpha':
                        if ($filter['properties']['whitespace-allowed'] == 'on') {
                            $value = preg_replace('/[^\p{L}\s]/u', '', $value);
                        } else {
                            $value = preg_replace('/[^\p{L}]/u', '', $value);
                        }
                        break;
                    case 'alphanumeric':
                        if ($filter['properties']['whitespace-allowed'] == 'on') {
                            $value = preg_replace('/[^\p{L}0-9\s]/u', '', $value);
                        } else {
                            $value = preg_replace('/[^\p{L}0-9]/u', '', $value);
                        }
                        break;
                    case 'digits':
                        if ($filter['properties']['whitespace-allowed'] == 'on') {
                            $value = preg_replace('/[^0-9\s]/', '', $value);
                        } else {
                            $value = preg_replace('/[^0-9]/', '', $value);
                        }
                        break;
                    case 'regex':
                        $value_tmp = preg_replace($filter['properties']['pattern'], '', $value);
                        if ($value_tmp != null) {
                            $value = $value_tmp;
                        }
                        break;
                    case 'strip-tags':
                        $value = strip_tags($value, $filter['properties']['tags-allowed']);
                        break;
                    default:
                        break;
                }
            }
            $values[$key] = $value;
        }
        if (is_array($_value)) {
            return $values;
        } else {
            return $values[0];
        }
    }

    public function set_form_data($_form_data)
    {
        if (empty($this->id)) {
            return false;
        }
        $this->form_data = [];
        foreach ($this->form_elements as $form_element) {
            if (!array_key_exists($form_element['type'], $this->leform->toolbarTools)) {
                continue;
            }
            switch ($form_element['type']) {
                case 'text':
                case 'password':
                case 'email':
                case 'textarea':
                case 'select':
                case 'checkbox':
                case 'imageselect':
                case 'radio':
                case 'tile':
                case 'multiselect':
                case 'date':
                case 'time':
                case 'file':
                case 'hidden':
                case 'star-rating':
                case 'signature':
                case 'rangeslider':
                case 'number':
                case 'numspinner':
                case 'matrix':
                    if (
                        array_key_exists(
                            'leform-' . $form_element['id'],
                            $_form_data
                        )
                    ) {
                        if (array_key_exists('filters', $form_element)) {
                            $this->form_data[$form_element['id']] = $this->_filter_value(
                                $_form_data['leform-' . $form_element['id']],
                                $form_element['filters']
                            );
                        } else {
                            $this->form_data[$form_element['id']] = $_form_data['leform-' . $form_element['id']];
                        }
                    } else {
                        $this->form_data[$form_element['id']] = null;
                    }
                    break;
                case 'repeater-input':
                    $repeaterInputValue = [];

                    $starRatingFieldIndexes = [];
                    $timeFieldIndexes = [];
                    foreach ($form_element["fields"] as $index => $field) {
                        if ($field["type"] === "star-rating") {
                            $starRatingFieldIndexes[] = $index;
                        }
                        if ($field["type"] === "time") {
                            $timeFieldIndexes[] = $index;
                        }
                    }

                    $inputRowIndex = 0;
                    $baseElementAccessor = 'leform-' . $form_element['id'] . '-';
                    while (array_key_exists($baseElementAccessor . $inputRowIndex, $_form_data)) {
                        $fullElementAccessor = $baseElementAccessor . $inputRowIndex;

                        $rowValues = $_form_data[$fullElementAccessor];

                        if (count($starRatingFieldIndexes) > 0) {
                            foreach ($starRatingFieldIndexes as $starRaingIndex) {
                                if (array_key_exists($fullElementAccessor . "-" . $starRaingIndex, $_form_data)) {
                                    $rowValues[$starRaingIndex] = $_form_data[$fullElementAccessor . "-" . $starRaingIndex];
                                } else {
                                    $rowValues[$starRaingIndex] = 0;
                                }
                            }
                        }

                        if (count($timeFieldIndexes) > 0) {
                            foreach ($timeFieldIndexes as $timeFieldIndex) {
                                if (
                                    array_key_exists($fullElementAccessor, $_form_data)
                                    && array_key_exists($timeFieldIndex, $_form_data[$fullElementAccessor])
                                    && ($_form_data[$fullElementAccessor][$timeFieldIndex] !== "10")
                                ) {
                                    $rowValues[$timeFieldIndex] = $_form_data[$fullElementAccessor][$timeFieldIndex];
                                } else {
                                    $rowValues[$timeFieldIndex] = "";
                                }
                            }
                        }

                        $repeaterInputValue[] = $rowValues;

                        $inputRowIndex++;
                    }

                    $this->form_data[$form_element['id']] = $repeaterInputValue;

                    break;
                case 'iban-input':
                    $inputValues = new \StdClass();
                    $inputValues->iban = '';
                    $inputValues->bic = '';
                    $values = isset($_form_data['ibanInput']) && is_array($_form_data['ibanInput']['leform-' . $form_element['id']]) ? $_form_data['ibanInput']['leform-' . $form_element['id']] : [];
                    if(isset($values['iban'])) {
                        $inputValues->iban = $values['iban'];   
                    }
                    if (isset($values['bic'])) {
                        $inputValues->bic = $values['bic'];
                    }

                    $this->form_data[$form_element['id']] = $inputValues;

                    break;
                default:
                    break;
            }
        }
    }

    public function set_form_info()
    {
        if (empty($this->id)) {
            return false;
        }

        $this->form_info = [
            'page-title' => array_key_exists('page-title', $_REQUEST) ? $_REQUEST['page-title'] : '',
            'url' => $_SERVER['HTTP_REFERER'],
            'ip' => $_SERVER['REMOTE_ADDR'],
            'user-agent' => $_SERVER['HTTP_USER_AGENT']
        ];
        if (array_key_exists('misc-save-ip', $this->form_options) && $this->form_options['misc-save-ip'] != 'on') {
            $this->form_info['ip'] = '';
        }
        if (array_key_exists('misc-save-user-agent', $this->form_options) && $this->form_options['misc-save-user-agent'] != 'on') {
            $this->form_info['user-agent'] = '';
        }
    }

    public function is_element_visible($_element_id)
    {
        $predefinedValues = [];
        if (isset($this->predefinedValues)) {
            if (is_array($this->predefinedValues)) {
                $predefinedValues = $this->predefinedValues;
            } else {
                try {
                    $predefinedValues = json_decode($this->predefinedValues);
                } catch (Exception $e) {
                }
            }
        }
        $allVariables = evo_get_all_variables($predefinedValues);

        $systemVariables = [];
        if (isset($this->systemVariables)) {
            if (is_array($this->systemVariables)) {
                $systemVariables = $this->systemVariables;
            } else {
                try {
                    $systemVariables = json_decode($this->systemVariables);
                } catch (Exception $e) {
                }
            }
        }

        $formVariables = [];
        foreach ($this->form_elements as $data) {
            if (isset($this->form_data[$data['id']]) && isset($data['name'])) {
                $name = $data['name'];
                $formVariables["{{form_" . $name . "}}"] = $this->form_data[$data['id']];
            }
        }

        $logic_rules = [];
        if (array_key_exists($_element_id, $this->form_logic) && isset($this->form_logic[$_element_id]['rules']) && is_array($this->form_logic[$_element_id]['rules'])) {
            for ($i = 0; $i < sizeof($this->form_logic[$_element_id]['rules']); $i++) {
                $field_values = (array)$this->form_data[$this->form_logic[$_element_id]['rules'][$i]['field']];
                $bool_value = false;

                $valueToCompare = $this->form_logic[$_element_id]['rules'][$i]['token'];

                $variables = array_merge($allVariables, $systemVariables, $formVariables);
                $valueToCompare = evo_replace_system_variables($valueToCompare, $variables);
                switch ($this->form_logic[$_element_id]['rules'][$i]['rule']) {
                    case 'is':
                        if (in_array($valueToCompare, $field_values)) $logic_rules[] = true;
                        else $logic_rules[] = false;
                        break;
                    case 'is-not':
                        if (!in_array($valueToCompare, $field_values)) $logic_rules[] = true;
                        else $logic_rules[] = false;
                        break;
                    case 'is-empty':
                        for ($j = 0; $j < sizeof($field_values); $j++) {
                            if (!empty($field_values[$j])) {
                                $bool_value = true;
                                break;
                            }
                        }
                        $logic_rules[] = !$bool_value;
                        break;
                    case 'is-not-empty':
                        for ($j = 0; $j < sizeof($field_values); $j++) {
                            if (!empty($field_values[$j])) {
                                $bool_value = true;
                                break;
                            }
                        }
                        $logic_rules[] = $bool_value;
                        break;
                    case 'is-greater':
                        for ($j = 0; $j < sizeof($field_values); $j++) {
                            if (floatval($field_values[$j]) > floatval($valueToCompare)) {
                                $bool_value = true;
                                break;
                            }
                        }
                        $logic_rules[] = $bool_value;
                        break;
                    case 'is-less':
                        for ($j = 0; $j < sizeof($field_values); $j++) {
                            if (floatval($field_values[$j]) < floatval($valueToCompare)) {
                                $bool_value = true;
                                break;
                            }
                        }
                        $logic_rules[] = $bool_value;
                        break;
                    case 'contains':
                        for ($j = 0; $j < sizeof($field_values); $j++) {
                            if (!empty($valueToCompare) && strpos($field_values[$j], $valueToCompare) !== false) {
                                $bool_value = true;
                                break;
                            }
                        }
                        $logic_rules[] = $bool_value;
                        break;
                    case 'starts-with':
                        for ($j = 0; $j < sizeof($field_values); $j++) {
                            if (!empty($valueToCompare) && substr($field_values[$j], 0, strlen($valueToCompare)) == $valueToCompare) {
                                $bool_value = true;
                                break;
                            }
                        }
                        $logic_rules[] = $bool_value;
                        break;
                    case 'ends-with':
                        for ($j = 0; $j < sizeof($field_values); $j++) {
                            if (!empty($valueToCompare) && substr($field_values[$j], strlen($field_values[$j]) - strlen($valueToCompare)) == $valueToCompare) {
                                $bool_value = true;
                                break;
                            }
                        }
                        $logic_rules[] = $bool_value;
                        break;
                    default:
                        break;
                }
            }
            $bool_value = false;
            if ($this->form_logic[$_element_id]['operator'] == "and") {
                if (!in_array(false, $logic_rules)) $bool_value = true;
            } else {
                if (in_array(true, $logic_rules)) $bool_value = true;
            }
            if ($this->form_logic[$_element_id]['action'] == 'hide') $bool_value = !$bool_value;

            if (!$bool_value) return false;
        } else $bool_value = true;

        if ($bool_value && array_key_exists($_element_id, $this->mappedFormElements)) {
            $form_element = $this->mappedFormElements[$_element_id];
            if (array_key_exists("_parent", $form_element) && array_key_exists($form_element["_parent"], $this->mappedFormElements)) {
                $bool_value = $this->is_element_visible($form_element["_parent"]);
            }
        }

        return $bool_value;
    }

    public function is_page_visible($_page_id)
    {
        return $this->is_element_visible($_page_id);
    }

    protected function _prepare_ranges($_ranges)
    {
        $raw_ranges = explode(',', $_ranges);
        $sanitized_ranges = [];
        foreach ($raw_ranges as $range) {
            $range = trim($range);
            if (strlen($range) == 0) {
                continue;
            }
            $range_parts = explode('...', $range);
            if (sizeof($range_parts) == 1) {
                $range_parts[0] = trim($range_parts[0]);
                if (strlen($range_parts[0]) > 0 && is_numeric($range_parts[0])) {
                    $sanitized_ranges[] = $range_parts[0];
                } else {
                    continue;
                }
            } else if (sizeof($range_parts) == 2) {
                $range_parts[0] = trim($range_parts[0]);
                $range_parts[1] = trim($range_parts[1]);
                if (strlen($range_parts[0]) == 0) {
                    $range_parts[0] = -2147483648;
                } else if (!is_numeric($range_parts[0])) {
                    continue;
                }

                if (strlen($range_parts[1]) == 0) {
                    $range_parts[1] = 2147483647;
                } else if (!is_numeric($range_parts[1])) {
                    continue;
                }

                if ($range_parts[0] < $range_parts[1]) {
                    $sanitized_ranges[] = $range_parts[0] . '...' . $range_parts[1];
                } else if ($range_parts[0] > $range_parts[1]) {
                    $sanitized_ranges[] = $range_parts[1] . '...' . $range_parts[0];
                } else {
                    $sanitized_ranges[] = $range_parts[0];
                }
            } else {
                continue;
            }
        }
        do {
            $finish = true;
            for ($i = 0; $i < sizeof($sanitized_ranges) - 1; $i++) {
                $range = explode('...', $sanitized_ranges[$i]);
                $val1 = $range[0];
                $range = explode('...', $sanitized_ranges[$i + 1]);
                $val2 = $range[0];
                if ($val2 < $val1) {
                    $val1 = $sanitized_ranges[$i];
                    $sanitized_ranges[$i] = $sanitized_ranges[$i + 1];
                    $sanitized_ranges[$i + 1] = $val1;
                    $finish = false;
                }
            }
        } while ($finish === false);
        do {
            $finish = true;
            for ($i = 0; $i < sizeof($sanitized_ranges) - 1; $i++) {
                $range1 = explode('...', $sanitized_ranges[$i]);
                if (sizeof($range1) == 1) $range1[1] = $range1[0];
                $range2 = explode('...', $sanitized_ranges[$i + 1]);
                if (sizeof($range2) == 1) $range2[1] = $range2[0];
                if ($range1[1] >= $range2[0]) {
                    $max = max($range1[1], $range2[1]);
                    if ($range1[0] == $max) $sanitized_ranges[$i + 1] = $max;
                    else $sanitized_ranges[$i + 1] = $range1[0] . '...' . $max;
                    unset($sanitized_ranges[$i]);
                    $finish = false;
                }
            }
            $sanitized_ranges = array_values($sanitized_ranges);
        } while ($finish === false);
        return implode(',', $sanitized_ranges);
    }

    public function get_next_page_id($_page_id)
    {
        if (empty($this->id)) {
            return false;
        }
        $next_page_id = null;
        $current_found = false;
        foreach ($this->form_pages as $key => $page) {
            if ($current_found) {
                if ($this->is_page_visible($page['id'])) {
                    $next_page_id = $page['id'];
                    break;
                }
            }
            if ($page['id'] == $_page_id) $current_found = true;
        }
        if (!$current_found) {
            return false;
        }
        if (empty($next_page_id)) {
            return true;
        }
        return $next_page_id;
    }

    protected function _validate_value($_value, $_validators, $_field_id = null)
    {
        if (!is_array($_validators) || empty($_validators)) return null;
        $values = [];
        if (is_array($_value)) $values = $_value;
        else $values[] = $_value;
        foreach ($values as $key => $value) {
            foreach ($_validators as $validator) {
                $match = true;
                $old = ['{value}'];
                $new = [$value];
                switch ($validator['type']) {
                    case 'alpha':
                        if ($validator['properties']['whitespace-allowed'] == 'on') $match = !preg_match('/[^\p{L}\s]/u', $value);
                        else $match = !preg_match('/[^\p{L}]/u', $value);
                        break;
                    case 'alphanumeric':
                        if ($validator['properties']['whitespace-allowed'] == 'on') $match = !preg_match('/[^\p{L}0-9\s]/u', $value);
                        else $match = !preg_match('/[^\p{L}0-9]/u', $value);
                        break;
                    case 'date':
                        if (!empty($value)) $match = $this->leform->validate_date($value, $this->form_options['datetime-args-date-format']);
                        else $match = true;
                        break;
                    case 'digits':
                        if ($validator['properties']['whitespace-allowed'] == 'on') $match = !preg_match('/[^0-9\s]/', $value);
                        else $match = !preg_match('/[^0-9]/', $value);
                        break;
                    case 'email':
                        $match = $this->leform->validate_email($value, true) || empty($value);
                        break;
                    case 'equal':
                        if (strlen($value) > 0) {
                            $match = ($validator['properties']['token'] == $value);
                            $old[] = '{token}';
                            $new[] = $validator['properties']['token'];
                        } else $match = true;
                        break;
                    case 'equal-field':
                        if (strlen($value) > 0) {
                            $match = !array_key_exists($validator['properties']['token'], $this->form_data) || (array_key_exists($validator['properties']['token'], $this->form_data) && $this->form_data[$validator['properties']['token']] == $value);
                            /*
                                $old[] = '{token}';
                                $new[] = $validator['properties']['token'];
                            */
                        } else $match = true;
                        break;
                    case 'greater':
                        $match = (floatval($validator['properties']['min']) < floatval($value)) || empty($value);
                        $old[] = '{min}';
                        $new[] = $validator['properties']['min'];
                        break;
                    case 'in-array':
                        $tokens = explode("\n", $validator['properties']['values']);
                        foreach ($tokens as $tkey => $tvalue) $tokens[$tkey] = strtolower(trim($tvalue));
                        $tokens = array_unique($tokens);
                        if ($validator['properties']['invert'] == 'on')    $match = !in_array(strtolower($value), $tokens);
                        else $match = in_array(strtolower($value), $tokens);
                        break;
                    case 'length':
                        if (strlen($value) > 0) {
                            $match = (strlen($value) >= $validator['properties']['min'] || empty($validator['properties']['min'])) && (strlen($value) <= $validator['properties']['max'] || empty($validator['properties']['max']));
                            $old = array_merge($old, ['{min}', '{max}']);
                            $new = array_merge($new, [$validator['properties']['min'], $validator['properties']['max']]);
                        } else $match = true;
                        break;
                    case 'less':
                        $match = (floatval($validator['properties']['max']) > floatval($value)) || empty($value);
                        $old[] = '{max}';
                        $new[] = $validator['properties']['max'];
                        break;
                    case 'prevent-duplicates':
                        #$record_details = $wpdb->get_row("SELECT * FROM ".$wpdb->prefix."leform_records WHERE deleted = '0' AND form_id = '".esc_sql($this->id)."' AND unique_keys LIKE '%{".esc_sql($wpdb->esc_like($_field_id.':'.$value))."}%'", ARRAY_A);
                        $record_details = Record::where('deleted', 0)
                            ->where('form_id', $this->id)
                            ->where('unique_keys', 'LIKE', '%{' . $_field_id . ':' . $value . '}%')
                            ->get();
                        if (empty($record_details)) $match = true;
                        else $match = false;
                        break;
                    case 'regex':
                        if ($validator['properties']['invert'] == 'on') $match = preg_match($validator['properties']['pattern'], $value);
                        else $match = !preg_match($validator['properties']['pattern'], $value);
                        break;
                    case 'time':
                        if (!empty($value)) $match = $this->leform->validate_time($value, $this->form_options['datetime-args-time-format']);
                        else $match = true;
                        break;
                    case 'url':
                        $match = preg_match('~^((http(s)?://)|(//))[a-z0-9-]+(.[a-z0-9-]+)*(:[0-9]+)?(/.*)?$~i', $value) || empty($value);
                        break;
                    default:
                        break;
                }
                if (!$match) {
                    $message = empty($validator['properties']['error']) ? $this->leform->validatorsMeta[$validator['type']]['properties']['error']['value'] : $validator['properties']['error'];
                    return str_replace($old, $new, $message);
                }
            }
        }
        return null;
    }

    public function validate_form_data()
    {
        if (empty($this->id)) {
            return false;
        }
        $errors = [];
        foreach ($this->form_elements as $form_element) {
            $element_error = null;
            if (!array_key_exists($form_element['type'], $this->leform->toolbarTools)) {
                continue;
            }
            if (!$this->is_element_visible($form_element['id'])) {
                continue;
            }
            switch ($form_element['type']) {
                case 'text':
                case 'password':
                case 'email':
                case 'textarea':
                case 'select':
                case 'checkbox':
                case 'imageselect':
                case 'radio':
                case 'tile':
                case 'multiselect':
                case 'date':
                case 'time':
                case 'file':
                case 'hidden':
                case 'star-rating':
                case 'signature':
                case 'number':
                case 'numspinner':
                    if (array_key_exists($form_element['id'], $this->form_data)) {
                        $value = $this->form_data[$form_element['id']];
                    } else {
                        $value = null;
                    }
                    if (array_key_exists('required', $form_element) && $form_element['required'] == "on" && empty($value) && $value != '0') {
                        $errors[$form_element['id']] = $form_element['required-error'];
                    } else if (array_key_exists('validators', $form_element)) {
                        $element_error = $this->_validate_value($value, $form_element['validators'], $form_element['id']);
                        if (!empty($element_error)) {
                            $errors[$form_element['id']] = $element_error;
                        }
                    }
                    break;
                case 'matrix':
                    if (array_key_exists($form_element['id'], $this->form_data)) {
                        $value = $this->form_data[$form_element['id']];
                    } else {
                        $value = null;
                    }
                    /*
                     * $value => "x--y"[]
                     * */
                    if (
                        array_key_exists('required', $form_element)
                        && $form_element['required'] == "on"
                        && empty($value)
                        && $value != '0'
                    ) {
                        $errors[$form_element['id']] = $form_element['required-error'];
                    } else if (array_key_exists('validators', $form_element)) {
                        $element_error = $this->_validate_value(
                            $value,
                            $form_element['validators'],
                            $form_element['id']
                        );
                        if (!empty($element_error)) {
                            $errors[$form_element['id']] = $element_error;
                        }
                    }

                    if (
                        array_key_exists('required', $form_element)
                        && $form_element['required'] == "on"
                    ) {
                        $hasValueForEveryRow = true;
                        foreach ($form_element['left'] as $leftOption) {
                            if ($hasValueForEveryRow === false) {
                                break;
                            }
                            $found = false;
                            if ($value) {
                                foreach ($value as $singleValue) {
                                    if ($found) {
                                        break;
                                    }
                                    $found = preg_match(
                                        "/" . $leftOption["value"] . "--/",
                                        $singleValue
                                    );
                                }
                            }

                            if (!$found) {
                                $hasValueForEveryRow = false;
                            }
                        }

                        if (!$hasValueForEveryRow) {
                            $errors[$form_element['id']] = __('validation.matrix.select_each_row');
                        }
                    }

                    break;
                case 'rangeslider':
                    if (array_key_exists($form_element['id'], $this->form_data)) {
                        $values = explode(':', $this->form_data[$form_element['id']]);
                    } else {
                        $values = [null];
                    }
                    if (array_key_exists('validators', $form_element)) {
                        foreach ($values as $value) {
                            $element_error = $this->_validate_value($value, $form_element['validators'], $form_element['id']);
                            if (!empty($element_error)) {
                                $errors[$form_element['id']] = $element_error;
                                break;
                            }
                        }
                    }
                    break;
                case 'iban-input':
                    if (array_key_exists($form_element['id'], $this->form_data)) {
                        $value = (array) $this->form_data[$form_element['id']];
                    } else {
                        $value = [
                            'iban' => '',
                            'bic' => '',
                        ];
                    }
                    /*
                     * $value => "x--y"[]
                     * */
                    if (
                        array_key_exists('required', $form_element)
                        && $form_element['required'] == "on"
                        && (
                            !is_array($value) ||
                            empty($value['iban']) ||
                            empty($value['bic'])
                        ) 
                    ) {
                        $errors[$form_element['id']] = $form_element['required-error'];
                    }

                    break;
                
                default:
                    break;
            }
            if ($form_element['type'] == 'password' && empty($element_error)) {
                if ($form_element['capital-mandatory'] == "on") {
                    $temp = trim(preg_replace('/[^A-Z]/', '', $this->form_data[$form_element['id']]));
                    if (empty($temp)) {
                        $errors[$form_element['id']] = $form_element['capital-mandatory-error'];
                    }
                }
                if ($form_element['digit-mandatory'] == "on") {
                    $temp = trim(preg_replace('/[^0-9]/', '', $this->form_data[$form_element['id']]));
                    if (empty($temp)) {
                        $errors[$form_element['id']] = $form_element['digit-mandatory-error'];
                    }
                }
                if ($form_element['special-mandatory'] == "on") {
                    $temp = trim(preg_replace('/[^a-zA-Z0-9]/', '', $this->form_data[$form_element['id']]));
                    $temp2 = trim($this->form_data[$form_element['id']]);
                    if ($temp == $temp2) {
                        $errors[$form_element['id']] = $form_element['special-mandatory-error'];
                    }
                }
                if (strlen($this->form_data[$form_element['id']]) < $form_element['min-length']) {
                    $errors[$form_element['id']] = $form_element['min-length-error'];
                }
            } else if ($form_element['type'] == 'date' && empty($element_error)) {
                $date = $this->leform->validate_date($this->form_data[$form_element['id']], $this->form_options['datetime-args-date-format']);
                if ($date) {
                    $ref_date = null;
                    switch ($form_element['min-date-type']) {
                        case 'yesterday':
                            $ref_date = new DateTime(date('Y-m-d', time() + 3600 * $this->leform->gmt_offset - 2 * 3600 * 24) . ' 00:00');
                            break;
                        case 'today':
                            $ref_date = new DateTime(date('Y-m-d', time() + 3600 * $this->leform->gmt_offset - 1 * 3600 * 24) . ' 00:00');
                            break;
                        case 'tomorrow':
                            $ref_date = new DateTime(date('Y-m-d', time() + 3600 * $this->leform->gmt_offset) . ' 00:00');
                            break;
                        case 'offset':
                            $ref_date = new DateTime(date('Y-m-d', time() + 3600 * $this->leform->gmt_offset + (intval($form_element['min-date-offset']) - 1) * 3600 * 24) . ' 00:00');
                            break;
                        case 'date':
                            $ref_date = $this->leform->validate_date($form_element['min-date-date'], $this->form_options['datetime-args-date-format']);
                            break;
                        case 'field':
                            if (array_key_exists($form_element['min-date-field'], $this->form_data)) {
                                $ref_date = $this->leform->validate_date($this->form_data[$form_element['min-date-field']], $this->form_options['datetime-args-date-format']);
                            }
                            break;
                        default:
                            break;
                    }
                    if (!empty($ref_date) && $ref_date > $date) {
                        $errors[$form_element['id']] = str_replace('{value}', $this->form_data[$form_element['id']], $form_element['min-date-error']);
                    }
                    $ref_date = null;
                    switch ($form_element['max-date-type']) {
                        case 'yesterday':
                            $ref_date = new DateTime(date('Y-m-d', time() + 3600 * $this->leform->gmt_offset) . ' 23:59');
                            break;
                        case 'today':
                            $ref_date = new DateTime(date('Y-m-d', time() + 3600 * $this->leform->gmt_offset + 1 * 3600 * 24) . ' 23:59');
                            break;
                        case 'tomorrow':
                            $ref_date = new DateTime(date('Y-m-d', time() + 3600 * $this->leform->gmt_offset + 2 * 3600 * 24) . ' 23:59');
                            break;
                        case 'offset':
                            $ref_date = new DateTime(date('Y-m-d', time() + 3600 * $this->leform->gmt_offset + (intval($form_element['max-date-offset']) + 1) * 3600 * 24) . ' 00:00');
                            break;
                        case 'date':
                            $ref_date = $this->leform->validate_date($form_element['max-date-date'], $this->form_options['datetime-args-date-format']);
                            break;
                        case 'field':
                            if (array_key_exists($form_element['max-date-field'], $this->form_data)) {
                                $ref_date = $this->leform->validate_date($this->form_data[$form_element['max-date-field']], $this->form_options['datetime-args-date-format']);
                            }
                            break;
                        default:
                            break;
                    }
                    if (!empty($ref_date) && $ref_date < $date) {
                        $errors[$form_element['id']] = str_replace('{value}', $this->form_data[$form_element['id']], $form_element['max-date-error']);
                    }
                }
            } else if ($form_element['type'] == 'time' && empty($element_error)) {
                $time = $this->leform->validate_time($this->form_data[$form_element['id']], $this->form_options['datetime-args-time-format']);
                if ($time) {
                    $ref_time = null;
                    switch ($form_element['min-time-type']) {
                        case 'time':
                            $ref_time = $this->leform->validate_time($form_element['min-time-time'], $this->form_options['datetime-args-time-format']);
                            break;
                        case 'field':
                            if (array_key_exists($form_element['min-time-field'], $this->form_data)) {
                                $ref_time = $this->leform->validate_time($this->form_data[$form_element['min-time-field']], $this->form_options['datetime-args-time-format']);
                            }
                            break;
                        default:
                            break;
                    }
                    if (!empty($ref_time) && $ref_time > $time) {
                        $errors[$form_element['id']] = str_replace('{value}', $this->form_data[$form_element['id']], $form_element['min-time-error']);
                    }
                    $ref_time = null;
                    switch ($form_element['max-time-type']) {
                        case 'time':
                            $ref_time = $this->leform->validate_time($form_element['max-time-time'], $this->form_options['datetime-args-time-format']);
                            break;
                        case 'field':
                            if (array_key_exists($form_element['max-time-field'], $this->form_data)) {
                                $ref_time = $this->leform->validate_time($this->form_data[$form_element['max-time-field']], $this->form_options['datetime-args-time-format']);
                            }
                            break;
                        default:
                            break;
                    }
                    if (!empty($ref_time) && $ref_time < $time) {
                        $errors[$form_element['id']] = str_replace('{value}', $this->form_data[$form_element['id']], $form_element['max-time-error']);
                    }
                }
            } else if ($form_element['type'] == 'signature' && empty($element_error)) {
                /*
                if (!empty($this->form_data[$form_element['id']])) {
                    if (substr($this->form_data[$form_element['id']], 0, strlen('data:image/png;base64,')) != 'data:image/png;base64,') {
                        $errors[$form_element['id']] = 'Invalid signature image 1.';
                    } else {
                        try {
                            $data = base64_decode(substr($this->form_data[$form_element['id']], strlen('data:image/png;base64,')));
                            if ($data === false) {
                                $errors[$form_element['id']] = 'Invalid signature image 3.';
                            } else {
                                $image = imagecreatefromstring($data);
                                if ($image === false) {
                                    $errors[$form_element['id']] = 'Invalid signature image 4.';
                                } else {
                                    $width = imagesx($image);
                                    $height = imagesy($image);
                                    if ($width === false || $height === false || $width > 1200 || $height > 600) {
                                        $errors[$form_element['id']] = 'Invalid signature image size.';
                                    }
                                }
                            }
                        } catch (Exception $e) {
                            $errors[$form_element['id']] = 'Invalid signature image 2.';
                        }
                    }
                }
                */
            }
        }

        if (!empty($errors)) {
            return $errors;
        }

        return [];
    }

    public function get_confirmation()
    {
        if (empty($this->id)) {
            return false;
        }
        $confirmation = [];
        if (array_key_exists('confirmations', $this->form_options) && is_array($this->form_options['confirmations'])) {
            for ($i = 0; $i < sizeof($this->form_options['confirmations']); $i++) {
                if ($this->is_element_visible('confirmation-' . $i)) {
                    $confirmation = $this->form_options['confirmations'][$i];
                    break;
                }
            }
        }
        return $confirmation;
    }

    private function getFormNameDynamicValuesForEntry($dynamicValues = [])
    {
        $associativeDynamicValues = [];
        foreach ($this->form_dynamic_name_values as $dynamicValueName) {
            $trimmedDynamicValueName = trim($dynamicValueName);
            $associativeDynamicValues[$dynamicValueName] = (
                (is_array($dynamicValues)
                    && array_key_exists($trimmedDynamicValueName, $dynamicValues)
                )
                ? $dynamicValues[$trimmedDynamicValueName]
                : null
            );
        }
        return $associativeDynamicValues;
    }

    private function getEntryFieldValue($fieldId, $fields)
    {
        $value = $fields[$fieldId];
        if ($value && !empty($value)) {
            $fieldId = 1 * $fieldId;
            $key = array_search($fieldId, array_column($this->form_elements, 'id'));
            $element = false;
            if ($key !== false) {
                $element = $this->form_elements[$key];
            }
            if ($element) {
                switch ($element["type"]) {
                    case 'select':
                        $element['options'] = FormService::getExternalValuesAsArray($element);
                        $options = $element['options'];
                        $o_key = array_search($value, array_column($options, 'value'));
                        if ($o_key  !== false) {
                            $option = $options[$o_key];
                            return $option['label'];
                        }
                        return '';
                    case 'radio':
                        $options = $element["options"];
                        $o_key = array_search($value, array_column($options, 'value'));
                        if ($o_key  !== false) {
                            $option = $options[$o_key];
                            return $option['label'];
                        }
                        return '';
                    case 'checkbox':
                    case 'multiselect':
                        $options = $element["options"];
                        $value = is_array($value) ? $value : [];
                        $values = [];
                        foreach ($value as $val) {
                            $o_key = array_search($val, array_column($options, 'value'));
                            if ($o_key  !== false) {
                                $option = $options[$o_key];
                                $values[] = $option['label'];
                            }
                        }
                        return implode(" | ", $values);
                    case 'repeater-input':
                        $fields = $element["fields"];
                        if (is_array($fields) && count($fields) > 0) {
                            $field = $fields[0];
                            $values = [];
                            switch ($field['type']) {
                                case 'text':
                                case 'email':
                                case 'number':
                                case 'date':
                                case 'time':
                                case 'select':
                                    $value = is_array($value) ? $value : [];
                                    foreach ($value as $val) {
                                        if (count($val) > 0) {
                                            $values[] = $val[0];
                                        }
                                    }
                                    break;
                                default:
                                    break;
                            }
                            return implode(" | ", $values);
                        }
                        return '';

                    case 'html':
                        $htmlExternal = "";
                        if (
                            isset($element['external-datasource']) &&
                            isset($element['external-datasource-url']) &&
                            $element['external-datasource'] === 'on'
                        ) {
                            // $element["content"]  = FormService::getExternalValuesAsString(
                            //     $element,
                            //     $element["content"],
                            //     []
                            // );
                            $url =  preg_replace("/{{(\d+).+?}}/", '{{$1}}', $element['external-datasource-url']);
                            $path = isset($element['external-datasource-path']) ? $element['external-datasource-path'] : '';

                            $htmlExternal = "
                                <span class='html-external-datasource' data-url='$url' data-path='$path'>
                                    <input type='hidden' class='html-external-datasource-transformed' data-path='$path' value=''/>
                                </span>
                            ";
                        }
                        if (is_string($element["content"]) || is_numeric($element["content"])) {
                            return " <div class='leform-element-html-container'>" . $element["content"] . " $htmlExternal</div>";
                        }
                        return '';
                    default:
                        if (is_string($value) || is_numeric($value)) {
                            return $value;
                        }
                        if (is_array($value)) {
                            return implode(" | ", $value);
                        }
                        return '';
                }
            }
        }
        return '';
    }

    public function save_data()
    {
        if (empty($this->id)) {
            return false;
        }
        $fields = [];
        foreach ($this->form_data as $field_id => $field_value) {
            if ($this->is_element_visible($field_id)) {
                $fields[$field_id] = $field_value;
            }
        }

        # password is unset here
        foreach ($this->form_elements as $form_element) {
            if (
                array_key_exists('save', $form_element)
                && $form_element['save'] == 'off'
                && array_key_exists($form_element['id'], $fields)
                && $form_element['type'] != 'password'
            ) {
                unset($fields[$form_element['id']]);
            }
        }

        $field_keys = array_keys($fields);
        $unique_keys = '';
        $all_uploads = [];
        foreach ($this->form_elements as $form_element) {
            if ($form_element['type'] == 'file') {
                $str_ids = [];
                $arr_fields = (array)$fields;
                if (isset($arr_fields[$form_element['id']])) {
                    foreach ($arr_fields[$form_element['id']] as $key => $file_str_id) {
                        $file_str_id = trim($file_str_id);
                        if (!empty($file_str_id)) {
                            $str_ids[] = $file_str_id;
                        }
                    }
                }
                if (!empty($str_ids)) {
                    #$uploads = $wpdb->get_results( "SELECT * FROM ".$wpdb->prefix."leform_uploads WHERE deleted = '0' AND upload_id != '' AND status = '".."' AND str_id IN ('".."')", ARRAY_A);
                    $uploads = Upload::where('deleted', 0)
                        ->where('upload_id', '!=', '')
                        ->where('status', 0) #LEFORM_UPLOAD_STATUS_OK
                        ->whereIn('str_id', $str_ids)
                        ->get();
                    $fields[$form_element['id']] = [];
                    foreach ($uploads as $upload_details) {
                        $fields[$form_element['id']][] = $upload_details['id'];
                    }
                    $this->form_data[$form_element['id']] = $fields[$form_element['id']];
                    $all_uploads = array_merge($all_uploads, $fields[$form_element['id']]);
                    $esc_array = [];
                    foreach ((array)$fields[$form_element['id']] as $array_value) {
                        $esc_array[] = $array_value;
                    }
                    #$wpdb->query("UPDATE ".$wpdb->prefix."leform_uploads SET form_id = '".esc_sql($this->id)."', element_id = '".esc_sql($form_element['id'])."', upload_id = '', str_id = '' WHERE id IN ('".implode("', '", $esc_array)."')");
                    Upload::whereIn('id', $esc_array)->update([
                        'form_id' => $this->id,
                        'element_id' => $form_element['id'],
                        'upload_id' => '',
                        'str_id' => '',
                    ]);
                }
            }
            if (
                array_key_exists('id', $form_element)
                && in_array($form_element['id'], $field_keys)
            ) {
                if (
                    array_key_exists('validators', $form_element)
                    && is_array($form_element['validators'])
                ) {
                    foreach ($form_element['validators'] as $validator) {
                        if ($validator['type'] == 'prevent-duplicates') {
                            foreach ((array)$fields[$form_element['id']] as $value) {
                                $unique_keys .= '{' . $form_element['id'] . ':' . $value . '}';
                            }
                            break;
                        }
                    }
                }
            }
        }
        $str_id = $this->leform->random_string(24);

        if ($this->form_options['double-enable'] == 'on') {
            $status = 1;
        } else {
            $status = 0;
        }

        #$wpdb->query("INSERT INTO ".$wpdb->prefix."leform_records (form_id, personal_data_keys, unique_keys, fields, info, status, str_id, amount, currency, created, deleted) VALUES ('" .esc_sql($this->id)."', '','" .esc_sql($unique_keys)."','" .esc_sql(json_encode($fields))."','" .esc_sql(json_encode($this->form_info))."','" .$status."','" .esc_sql($str_id)."', '0', 'USD','" .esc_sql(time())."', '0')");
        $created_record = new Record;
        $created_record->form_id = $this->id;
        $created_record->company_id = $this->company_id;
        $created_record->personal_data_keys = '';
        $created_record->unique_keys = $unique_keys;
        $created_record->fields = json_encode($fields);
        $created_record->info = json_encode($this->form_info);
        $created_record->status = $status;
        $created_record->status = 0;
        $created_record->str_id = $str_id;
        $created_record->amount = 0;
        $created_record->currency = 'USD';
        $created_record->created = time();
        $created_record->deleted = 0;
        $created_record->predefined_values = json_encode($this->predefinedValues);
        $created_record->system_variables = json_encode($this->systemVariables);
        $created_record->primary_field_id = $this->form_options["key-fields-primary"];
        if (
            $this->form_options["key-fields-primary"]
            && array_key_exists($this->form_options["key-fields-primary"], $fields)
        ) {
            $created_record->primary_field_value = $this->getEntryFieldValue(
                $this->form_options["key-fields-primary"],
                $fields
            ); //$fields[$this->form_options["key-fields-primary"]];
        }
        $created_record->secondary_field_id = $this->form_options["key-fields-secondary"];
        if (
            $this->form_options["key-fields-secondary"]
            && array_key_exists($this->form_options["key-fields-secondary"], $fields)
        ) {
            $created_record->secondary_field_value = $this->getEntryFieldValue(
                $this->form_options["key-fields-secondary"],
                $fields
            );
        }

        if ($this->form_options["has-dynamic-name-values"] === "on") {
            $dynamicFormNameWithValues = $this->form_options["dynamic-name-values"];
            foreach ($this->form_dynamic_name_values as $dynamicValueName) {
                $allowedValueTypes = ["boolean", "integer", "double", "string"];
                $value = (
                    (is_array($this->predefinedValues)
                        && array_key_exists($dynamicValueName, $this->predefinedValues)
                        && in_array(
                            gettype($this->predefinedValues[$dynamicValueName]),
                            $allowedValueTypes
                        )
                    )
                    ? $this->predefinedValues[$dynamicValueName]
                    : ""
                );
                $dynamicFormNameWithValues = preg_replace(
                    "/{{" . $dynamicValueName . "}}/",
                    $value,
                    $dynamicFormNameWithValues
                );
            }

            $created_record->dynamic_form_name = $this->form_options["dynamic-name-values"];
            if (trim($dynamicFormNameWithValues)) {
                $created_record->dynamic_form_name_with_values = trim($dynamicFormNameWithValues);
            }
            $created_record->dynamic_form_name_values = json_encode(
                $this->getFormNameDynamicValuesForEntry($this->predefinedValues)
            );
        }
        $created_record->save();

        #$record_id = $created_record->id;
        $record_id = $created_record['id'];
        $this->record_id = $record_id;

        $datestamp = date('Ymd', time() + 3600 * $this->leform->gmt_offset);
        $timestamp = date('h', time() + 3600 * $this->leform->gmt_offset);

        foreach ($fields as $field_id => $field_value) {
            if (is_array($field_value)) {
                foreach ($field_value as $option) {
                    #$wpdb->query("INSERT INTO ".$wpdb->prefix."leform_fieldvalues ( form_id, record_id, field_id, value, datestamp, deleted) VALUES ( '" .esc_sql($this->id)."','" .esc_sql($record_id)."','" .esc_sql($field_id)."','" .esc_sql($option)."','" .esc_sql($datestamp)."', '0')");
                    FieldValue::create([
                        'form_id' => $this->id,
                        'record_id' => $record_id,
                        'field_id' => $field_id,
                        'value' => (gettype($option) === "array")
                            ? json_encode($option)
                            : $option,
                        'datestamp' => $datestamp,
                        'deleted' => 0,
                    ]);
                }
            } else if (is_object($field_value)){
                FieldValue::create([
                    'form_id' => $this->id,
                    'record_id' => $record_id,
                    'field_id' => $field_id,
                    'value' => json_encode($field_value),
                    'datestamp' => $datestamp,
                    'deleted' => 0,
                ]);
            } else {
                #$wpdb->query("INSERT INTO ".$wpdb->prefix."leform_fieldvalues ( form_id, record_id, field_id, value, datestamp, deleted) VALUES ( '" .esc_sql($this->id)."','" .esc_sql($record_id)."','" .esc_sql($field_id)."','" .esc_sql($field_value)."','" .esc_sql($datestamp)."', '0')");
                FieldValue::create([
                    'form_id' => $this->id,
                    'record_id' => $record_id,
                    'field_id' => $field_id,
                    'value' => $field_value,
                    'datestamp' => $datestamp,
                    'deleted' => 0,
                ]);
            }
        }

        if (!empty($all_uploads)) {
            $file_num = 1;

            $esc_array = [];
            foreach ((array)$all_uploads as $array_value) {
                $esc_array[] = $array_value;
            }
            #$uploads = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."leform_uploads WHERE id IN ('".implode("', '", $esc_array)."') ORDER BY element_id ASC", ARRAY_A);
            $uploads = Upload::whereIn('id', $esc_array)
                ->orderBy('element_id', 'asc')
                ->get();

            foreach ($uploads as $upload_details) {
                $ext = pathinfo($upload_details['filename'], PATHINFO_EXTENSION);
                $ext = strtolower($ext);
                $filename = $record_id . '-' . $upload_details['element_id'] . '-' . $file_num . (!empty($ext) ? '.' . $ext : '');
                /* 
                $upload_dir = wp_upload_dir();
                rename(
                    $upload_dir["basedir"].'/'.LEFORM_UPLOADS_DIR.'/uploads/'.$this->id.'/'.$upload_details['filename'],
                    $upload_dir["basedir"].'/'.LEFORM_UPLOADS_DIR.'/uploads/'.$this->id.'/'.$filename
                );
                 */
                Storage::move(
                    'public/uploads/' . $upload_details['filename'],
                    'public/uploads/' . $filename,
                );

                #$wpdb->query("UPDATE ".$wpdb->prefix."leform_uploads SET record_id = '".esc_sql($record_id)."', filename = '".esc_sql($filename)."' WHERE id = '".esc_sql($upload_details['id'])."'");
                Upload::where('id', $upload_details['id'])->update([
                    'record_id' => $record_id,
                    'filename' => $filename,
                ]);
                $file_num++;
            }
        }
        #$stats_details = $wpdb->get_row( "SELECT * FROM ".$wpdb->prefix."leform_stats WHERE form_id = '".esc_sql($this->id)."' AND datestamp = '".esc_sql($datestamp)."' AND timestamp = '".esc_sql($timestamp)."'", ARRAY_A);
        $stats_details = Stat::where('form_id', $this->id)
            ->where('datestamp', $datestamp)
            ->where('timestamp', $timestamp)
            ->first();
        if (!empty($stats_details)) {
            #$wpdb->query("UPDATE ".$wpdb->prefix."leform_stats SET submits = submits + 1 WHERE id = '".esc_sql($stats_details['id'])."'");
            Stat::where('id', $stats_details['id'])->update([
                'submits' => DB::raw('submits + 1')
            ]);
        } else {
            #$wpdb->query("INSERT INTO ".$wpdb->prefix."leform_stats ( form_id, impressions, submits, confirmed, payments, datestamp, timestamp, deleted) VALUES ( '".esc_sql($this->id)."', '0', '1', '0', '0', '".esc_sql($datestamp)."', '".esc_sql($timestamp)."', '0')");
            Stat::create([
                'form_id' => $this->id,
                'impressions' => 0,
                'submits' => 1,
                'confirmed' => 0,
                'payments' => 0,
                'datestamp' => $datestamp,
                'timestamp' => $timestamp,
                'deleted' => 0,
            ]);
        }
        return ['str-id' => $str_id, 'id' => $record_id];
    }

    protected function _build_style_text($_key, $_important = false)
    {
        $style = "";
        if (array_key_exists($_key . "-family", $this->form_options) && $this->form_options[$_key . "-family"] != "") {
            $style .= "font-family:'" . $this->form_options[$_key . "-family"] . "','arial'" . ($_important ? " !important" : "") . ";";
        }
        if (array_key_exists($_key . "-size", $this->form_options)) {
            $size = intval($this->form_options[$_key . "-size"]);
            if ($size >= 8 && $size <= 64) {
                $style .= "font-size:" . $size . "px" . ($_important ? " !important" : "") . ";";
            }
        }
        if (array_key_exists($_key . "-color", $this->form_options) && $this->form_options[$_key . "-color"] != "") {
            $style .= "color:" . $this->form_options[$_key . "-color"] . ($_important ? " !important" : "") . ";";
        }
        if (array_key_exists($_key . "-bold", $this->form_options) && $this->form_options[$_key . "-bold"] == "on") {
            $style .= "font-weight:bold" . ($_important ? " !important" : "") . ";";
        } else {
            $style .= "font-weight:normal" . ($_important ? " !important" : "") . ";";
        }
        if (array_key_exists($_key . "-italic", $this->form_options) && $this->form_options[$_key . "-italic"] == "on") {
            $style .= "font-style:italic" . ($_important ? " !important" : "") . ";";
        } else {
            $style .= "font-style:normal" . ($_important ? " !important" : "") . ";";
        }
        if (array_key_exists($_key . "-underline", $this->form_options) && $this->form_options[$_key . "-underline"] == "on") {
            $style .= "text-decoration:underline" . ($_important ? " !important" : "") . ";";
        } else {
            $style .= "text-decoration:none" . ($_important ? " !important" : "") . ";";
        }
        if (array_key_exists($_key . "-align", $this->form_options) && $this->form_options[$_key . "-align"] != "") {
            $style .= "text-align:" . $this->form_options[$_key . "-align"] . ";";
        }
        return ["style" => $style, "webfont" => $this->form_options[$_key . "-family"]];
    }

    protected function _build_style_background($_key, $_important = false)
    {
        $style = "";
        $hposition = "left";
        $vposition = "top";
        $color1 = "transparent";
        $color2 = "transparent";
        $direction = "to bottom";
        if (array_key_exists($_key . "-color", $this->form_options) && $this->form_options[$_key . "-color"] != "")
            $color1 = $this->form_options[$_key . "-color"];

        if (array_key_exists($_key . "-gradient", $this->form_options) && $this->form_options[$_key . "-gradient"] == "2shades") {
            $style .= "background-color:" . $color1 . ($_important ? " !important" : "") . ";background-image:linear-gradient(to bottom,rgba(255,255,255,.05) 0,rgba(255,255,255,.05) 50%,rgba(0,0,0,.05) 51%,rgba(0,0,0,.05) 100%)" . ($_important ? " !important" : "") . ";";
        } else if (array_key_exists($_key . "-gradient", $this->form_options) && ($this->form_options[$_key . "-gradient"] == "horizontal" || $this->form_options[$_key . "-gradient"] == "vertical" || $this->form_options[$_key . "-gradient"] == "diagonal")) {
            if (array_key_exists($_key . "-color2", $this->form_options) && $this->form_options[$_key . "-color2"] != "") {
                $color2 = $this->form_options[$_key . "-color2"];
            }
            if ($this->form_options[$_key . "-gradient"] == "horizontal") {
                $direction = "to right";
            } else if ($this->form_options[$_key . "-gradient"] == "diagonal") {
                $direction = "to bottom right";
            }
            $style .= "background-image:linear-gradient(" . $direction . "," . $color1 . "," . $color2 . ")" . ($_important ? " !important" : "") . ";";
        } else if (array_key_exists($_key . "-image", $this->form_options) && $this->form_options[$_key . "-image"] != "") {
            $style .= "background-color:" . $color1 . ($_important ? " !important" : "") . ";background-image:url('" . $this->form_options[$_key . "-image"] . "')" . ($_important ? " !important" : "") . ";";
            if (array_key_exists($_key . "-size", $this->form_options) && $this->form_options[$_key . "-size"] != "") {
                $style .= "background-size:" . $this->form_options[$_key . "-size"] . ($_important ? " !important" : "") . ";";
            }
            if (array_key_exists($_key . "-repeat", $this->form_options) && $this->form_options[$_key . "-repeat"] != "") {
                $style .= "background-repeat:" . $this->form_options[$_key . "-repeat"] . ($_important ? " !important" : "") . ";";
            }
            if (array_key_exists($_key . "-horizontal-position", $this->form_options) && $this->form_options[$_key . "-horizontal-position"] != "") {
                switch ($this->form_options[$_key . "-horizontal-position"]) {
                    case 'center':
                        $hposition = "center";
                        break;
                    case 'right':
                        $hposition = "right";
                        break;
                    default:
                        $hposition = "left";
                        break;
                }
            }
            if (array_key_exists($_key . "-vertical-position", $this->form_options) && $this->form_options[$_key . "-vertical-position"] != "") {
                switch ($this->form_options[$_key . "-vertical-position"]) {
                    case 'middle':
                        $vposition = "center";
                        break;
                    case 'bottom':
                        $vposition = "bottom";
                        break;
                    default:
                        $vposition = "top";
                        break;
                }
            }
            $style .= "background-position: " . $hposition . " " . $vposition . ($_important ? " !important" : "") . ";";
        } else {
            $style .= "background-color:" . $color1 . ($_important ? " !important" : "") . ";background-image:none" . ($_important ? " !important" : "") . ";";
        }
        return $style;
    }

    protected function _build_style_border($_key, $_important = false)
    {
        $style = "";
        if (array_key_exists($_key . "-width", $this->form_options)) {
            $size = intval($this->form_options[$_key . "-width"]);
            if ($size >= 0 && $size <= 16) {
                $style .= "border-width:" . $size . "px" . ($_important ? " !important" : "") . ";";
            }
        }
        if (array_key_exists($_key . "-style", $this->form_options) && $this->form_options[$_key . "-style"] != "") {
            $style .= "border-style:" . $this->form_options[$_key . "-style"] . ($_important ? " !important" : "") . ";";
        }
        if (array_key_exists($_key . "-color", $this->form_options) && $this->form_options[$_key . "-color"] != "") {
            $style .= "border-color:" . $this->form_options[$_key . "-color"] . ($_important ? " !important" : "") . ";";
        }
        if (array_key_exists($_key . "-radius", $this->form_options)) {
            $size = intval($this->form_options[$_key . "-radius"]);
            if ($size >= 0 && $size <= 100) {
                $style .= "border-radius:" . $size . "px" . ($_important ? " !important" : "") . ";";
            }
        }
        if (array_key_exists($_key . "-top", $this->form_options) && $this->form_options[$_key . "-top"] != "on") {
            $style .= "border-top:none !important;";
        }
        if (array_key_exists($_key . "-left", $this->form_options) && $this->form_options[$_key . "-left"] != "on") {
            $style .= "border-left:none !important;";
        }
        if (array_key_exists($_key . "-right", $this->form_options) && $this->form_options[$_key . "-right"] != "on") {
            $style .= "border-right:none !important;";
        }
        if (array_key_exists($_key . "-bottom", $this->form_options) && $this->form_options[$_key . "-bottom"] != "on") {
            $style .= "border-bottom:none !important;";
        }
        return $style;
    }

    protected function _build_shadow($_key, $_important = false)
    {
        $style = "box-shadow:none;";
        $color = "transparent";
        $shadow_style = "regular";
        if (array_key_exists($_key . "-size", $this->form_options) && $this->form_options[$_key . "-size"] != "") {
            if (array_key_exists($_key . "-color", $this->form_options) && $this->form_options[$_key . "-color"] != "") {
                $color = $this->form_options[$_key . "-color"];
            }
            if (array_key_exists($_key . "-style", $this->form_options) && $this->form_options[$_key . "-style"] != "") {
                $shadow_style = $this->form_options[$_key . "-style"];
            }
            switch ($shadow_style) {
                case 'solid':
                    if ($this->form_options[$_key . "-size"] == "tiny") {
                        $style = "box-shadow: 1px 1px 0px 0px " . $color . ($_important ? " !important" : "") . ";";
                    } else if ($this->form_options[$_key . "-size"] == "small") {
                        $style = "box-shadow: 2px 2px 0px 0px " . $color . ($_important ? " !important" : "") . ";";
                    } else if ($this->form_options[$_key . "-size"] == "medium") {
                        $style = "box-shadow: 4px 4px 0px 0px " . $color . ($_important ? " !important" : "") . ";";
                    } else if ($this->form_options[$_key . "-size"] == "large") {
                        $style = "box-shadow: 6px 6px 0px 0px " . $color . ($_important ? " !important" : "") . ";";
                    } else if ($this->form_options[$_key . "-size"] == "huge") {
                        $style = "box-shadow: 8px 8px 0px 0px " . $color . ($_important ? " !important" : "") . ";";
                    }
                    break;
                case 'inset':
                    if ($this->form_options[$_key . "-size"] == "tiny") {
                        $style = "box-shadow: inset 0px 0px 15px -9px " . $color . ($_important ? " !important" : "") . ";";
                    } else if ($this->form_options[$_key . "-size"] == "small") {
                        $style = "box-shadow: inset 0px 0px 15px -8px " . $color . ($_important ? " !important" : "") . ";";
                    } else if ($this->form_options[$_key . "-size"] == "medium") {
                        $style = "box-shadow: inset 0px 0px 15px -7px " . $color . ($_important ? " !important" : "") . ";";
                    } else if ($this->form_options[$_key . "-size"] == "large") {
                        $style = "box-shadow: inset 0px 0px 15px -6px " . $color . ($_important ? " !important" : "") . ";";
                    } else if ($this->form_options[$_key . "-size"] == "huge") {
                        $style = "box-shadow: inset 0px 0px 15px -5px " . $color . ($_important ? " !important" : "") . ";";
                    }
                    break;
                default:
                    if ($this->form_options[$_key . "-size"] == "tiny") {
                        $style = "box-shadow: 1px 1px 15px -9px " . $color . ($_important ? " !important" : "") . ";";
                    } else if ($this->form_options[$_key . "-size"] == "small") {
                        $style = "box-shadow: 1px 1px 15px -8px " . $color . ($_important ? " !important" : "") . ";";
                    } else if ($this->form_options[$_key . "-size"] == "medium") {
                        $style = "box-shadow: 1px 1px 15px -7px " . $color . ($_important ? " !important" : "") . ";";
                    } else if ($this->form_options[$_key . "-size"] == "large") {
                        $style = "box-shadow: 1px 1px 15px -6px " . $color . ($_important ? " !important" : "") . ";";
                    } else if ($this->form_options[$_key . "-size"] == "huge") {
                        $style = "box-shadow: 1px 1px 15px -5px " . $color . ($_important ? " !important" : "") . ";";
                    }
                    break;
            }
        }
        return $style;
    }

    protected function _build_style_padding($_key, $_spacing = 0)
    {
        $style = "";
        $integer = 0;
        if (array_key_exists($_key . "-top", $this->form_options)) {
            $integer = max(intval($this->form_options[$_key . "-top"]) - $_spacing, 0);
            if ($integer >= 0 && $integer <= 300) {
                $style .= "padding-top:" . $integer . "px;";
            }
        }
        if (array_key_exists($_key . "-right", $this->form_options)) {
            $integer = max(intval($this->form_options[$_key . "-right"]) - $_spacing, 0);
            if ($integer >= 0 && $integer <= 300) {
                $style .= "padding-right:" . $integer . "px;";
            }
        }
        if (array_key_exists($_key . "-bottom", $this->form_options)) {
            $integer = max(intval($this->form_options[$_key . "-bottom"]) - $_spacing, 0);
            if ($integer >= 0 && $integer <= 300) {
                $style .= "padding-bottom:" . $integer . "px;";
            }
        }
        if (array_key_exists($_key . "-left", $this->form_options)) {
            $integer = max(intval($this->form_options[$_key . "-left"]) - $_spacing, 0);
            if ($integer >= 0 && $integer <= 300) {
                $style .= "padding-left:" . $integer . "px;";
            }
        }
        return $style;
    }

    protected function _build_hidden($_parent, $predefinedValues)
    {
        $allVariables = evo_get_all_variables($predefinedValues);
        $html = '';
        for ($i = 0; $i < sizeof($this->form_elements); $i++) {
            if (empty($this->form_elements[$i])) {
                continue;
            }
            if ($this->form_elements[$i]["type"] != "hidden") {
                continue;
            }
            if ($this->form_elements[$i]["_parent"] != $_parent) {
                continue;
            }
            $defaultVal = $this->replaceWithPredefinedValues(
                $this->form_elements[$i]["default"],
                $allVariables,
            );
            $html .= "<input class='leform-hidden' type='hidden' name='leform-" . $this->form_elements[$i]['id'] . "'" . ($this->form_elements[$i]['dynamic-default'] == 'on' ? " data-dynamic='" . $this->form_elements[$i]['dynamic-parameter'] . "'" : "") . " data-id='" . $this->form_elements[$i]['id'] . "' data-default='" . $defaultVal . "' value='" . $defaultVal . "' />";
        }
        return $html;
    }

    function leform_build_progress($_current_page, $_uid)
    {
        $html = "";
        $total_pages = $this->form_options["progress-confirmation-enable"] == "on" ? 0 : -1;
        $total_pages += sizeof($this->form_pages);
        if ($this->form_options["progress-enable"] == "on" && ($this->form_pages[$_current_page]['type'] != 'page-confirmation' || $this->form_options["progress-confirmation-enable"] == "on")) {
            if ($this->form_options["progress-type"] == 'progress-2') {
                $html = "<div class='leform-progress leform-progress-" . $this->form_options["progress-position"] . " leform-progress-" . $this->id . " leform-progress-" . $_uid . "' data-page=" . $this->form_pages[$_current_page]['id'] . "><ul class='leform-progress-t2" . ($this->form_options["progress-striped"] == "on" ? " leform-progress-stripes" : "") . "'>";
                for ($i = 0; $i < $total_pages; $i++) {
                    $html .= "<li" . ($i < $_current_page ? " class='leform-progress-t2-passed'" : ($i == $_current_page ? " class='leform-progress-t2-active'" : "")) . " style='width:" . number_format(floor(10000 / $total_pages) / 100, 2, '.', '') . "%;'><span>" . ($i + 1) . "</span>" . ($this->form_options["progress-label-enable"] == "on" ? "<label>" . $this->form_pages[$i]['name'] . "</label>" : "") . "</li>";
                }
                $html .= "</ul></div>";
            } else {
                $width = intval(100 * ($_current_page + 1) / $total_pages);
                $html = "<div class='leform-progress leform-progress-" . $this->form_options["progress-position"] . " leform-progress-" . $this->id . " leform-progress-" . $_uid . "' data-page=" . $this->form_pages[$_current_page]['id'] . "><div class='leform-progress-t1" . ($this->form_options["progress-striped"] == "on" ? " leform-progress-stripes" : "") . "'><div><div style='width:" . $width . "%'>" . $width . "%</div></div>" . ($this->form_options["progress-label-enable"] == "on" ? "<label>" . $this->form_pages[$_current_page]['name'] . "</label>" : "") . "</div></div>";
            }
        }
        return $html;
    }

    protected function replaceWithPredefinedValues($string, $predefinedValues = [])
    {
        if ($predefinedValues === null) {
            $string = preg_replace("/\{\{[A-z1-9-_]*\}\}/", "", $string);
            return $string;
        }
        $allowedTypes = ["boolean", "integer", "double", "string"];
        foreach ($predefinedValues as $key => $value) {
            if (in_array(gettype($value), $allowedTypes)) {
                $string = preg_replace("/\{\{$key\}\}/", $value, $string);
            }
        }
        $string = preg_replace("/\{\{[A-z1-9-_]*\}\}/", "", $string);
        return $string;
    }

    protected function _build_children($_parent, $_parent_col, $predefinedValues)
    {
        $html = '';
        $style = '';
        $uids = [];
        $properties = [];
        $allVariables = $predefinedValues;

        $idxs = [];
        $seqs = [];
        for ($i = 0; $i < sizeof($this->form_elements); $i++) {
            if (empty($this->form_elements[$i])) continue;
            if ($this->form_elements[$i]["_parent"] == $_parent && ($this->form_elements[$i]["_parent-col"] == $_parent_col || $_parent == "")) {
                $idxs[] = $i;
                $seqs[] = intval($this->form_elements[$i]["_seq"]);
            }
        }
        if (empty($idxs)) {
            return ["html" => "", "style" => "", "uids" => []];
        }
        for ($i = 0; $i < sizeof($seqs); $i++) {
            $sorted = -1;
            for ($j = 0; $j < sizeof($seqs) - 1; $j++) {
                if ($seqs[$j] > $seqs[$j + 1]) {
                    $sorted = $seqs[$j];
                    $seqs[$j] = $seqs[$j + 1];
                    $seqs[$j + 1] = $sorted;
                    $sorted = $idxs[$j];
                    $idxs[$j] = $idxs[$j + 1];
                    $idxs[$j + 1] = $sorted;
                }
            }
            if ($sorted == -1) break;
        }

        $oneWayBindingMap = [];
        foreach ($this->form_elements as $element) {
            $isCorrectType = in_array($element["type"], ["text", "email", "textarea", "link-button"]);
            $bindField = null;
            if (
                array_key_exists("bind-field", $element)
                && is_numeric($element["bind-field"])
            ) {
                $bindField = intval($element["bind-field"]);
            }
            if (
                $isCorrectType
                && $bindField
                && $bindField !== $element["id"]
                && $this->getElementByField($this->form_elements, "id", $bindField)
            ) {
                if (!array_key_exists($bindField, $oneWayBindingMap)) {
                    $oneWayBindingMap[$bindField] = [];
                }
                $oneWayBindingMap[$bindField][] = $element["id"];
            }
        }

        for ($k = 0; $k < sizeof($idxs); $k++) {
            $i = $idxs[$k];
            $icon = "";
            $options = "";
            $extra_class = "";
            $column_label_class = "";
            $column_input_class = "";
            $properties = [];
            if (empty($this->form_elements[$i])) continue;
            if (array_key_exists('label-style-position', $this->form_elements[$i])) {
                $properties["label-style-position"] = $this->form_elements[$i]["label-style-position"];
                if ($properties["label-style-position"] == "") $properties["label-style-position"] = $this->form_options["label-style-position"];
                if ($properties["label-style-position"] == "") $properties["label-style-position"] = "top";
                if ($this->form_elements[$i]["label-style-position"] == "left" || $this->form_elements[$i]["label-style-position"] == "right") $properties["label-style-width"] = $this->form_elements[$i]["label-style-width"];
                else $properties["label-style-width"] = "";
                if ($properties["label-style-width"] == "") $properties["label-style-width"] = $this->form_options["label-style-width"];
                $properties["label-style-width"] = intval($properties["label-style-width"]);
                if ($properties["label-style-width"] < 1 || $properties["label-style-width"] > 11) $properties["label-style-width"] = 3;
                if ($properties["label-style-position"] == "left" || $properties["label-style-position"] == "right") {
                    $column_label_class = " leform-col-" . $properties["label-style-width"];
                    $column_input_class = " leform-col-" . (12 - $properties["label-style-width"]);
                }
            }
            if (array_key_exists('icon-left-icon', $this->form_elements[$i])) {
                if ($this->form_elements[$i]["icon-left-icon"] != "") {
                    if (
                        array_key_exists("input-icon-display", $this->form_options)
                        && $this->form_options["input-icon-display"] === "show"
                    ) {
                        $extra_class .= " leform-icon-left";
                        $icon .= "<i class='leform-icon-left " . $this->form_elements[$i]["icon-left-icon"] . "'></i>";
                        $options = "";
                        if ($this->form_elements[$i]["icon-left-size"] != "") {
                            $options .= "font-size:" . $this->form_elements[$i]["icon-left-size"] . "px;";
                        }
                        if (!empty($options)) {
                            $style .= ".leform-form-" . $this->id . " .leform-element-" . $this->form_elements[$i]['id'] . " div.leform-input>i.leform-icon-left{" . $options . "}";
                        }
                    }
                }
            }
            if (array_key_exists('icon-right-icon', $this->form_elements[$i])) {
                if ($this->form_elements[$i]["icon-right-icon"] != "") {
                    if (
                        array_key_exists("input-icon-display", $this->form_options)
                        && $this->form_options["input-icon-display"] === "show"
                    ) {
                        $extra_class .= " leform-icon-right";
                        $icon .= "<i class='leform-icon-right " . $this->form_elements[$i]["icon-right-icon"] . "'></i>";
                        $options = "";
                        if ($this->form_elements[$i]["icon-right-size"] != "") {
                            $options .= "font-size:" . $this->form_elements[$i]["icon-right-size"] . "px;";
                        }
                        if (!empty($options)) {
                            $style .= ".leform-form-" . $this->id . " .leform-element-" . $this->form_elements[$i]['id'] . " div.leform-input>i.leform-icon-right{" . $options . "}";
                        }
                    }
                }
            }

            if (
                array_key_exists("css", $this->form_elements[$i])
                && sizeof($this->form_elements[$i]["css"]) > 0
            ) {
                $elementPropertiesMeta = $this->leform->getElementPropertiesMeta();
                if (
                    array_key_exists(
                        $this->form_elements[$i]["type"],
                        $elementPropertiesMeta
                    )
                    && array_key_exists(
                        "css",
                        $elementPropertiesMeta[$this->form_elements[$i]["type"]]
                    )
                ) {
                    for ($j = 0; $j < sizeof($this->form_elements[$i]["css"]); $j++) {
                        if (
                            !empty($this->form_elements[$i]["css"][$j]["css"])
                            && !empty($this->form_elements[$i]["css"][$j]["selector"])
                        ) {
                            if (
                                array_key_exists(
                                    $this->form_elements[$i]["css"][$j]["selector"],
                                    $elementPropertiesMeta[$this->form_elements[$i]["type"]]["css"]["selectors"]
                                )
                            ) {
                                $properties["css-class"] = $elementPropertiesMeta[$this->form_elements[$i]["type"]]["css"]["selectors"][$this->form_elements[$i]["css"][$j]["selector"]]["front-class"];
                                $properties["css-class"] = str_replace(
                                    ["{element-id}", "{form-id}"],
                                    [$this->form_elements[$i]['id'], $this->id],
                                    $properties["css-class"]
                                );
                                $style .= $properties["css-class"] . "{" . $this->form_elements[$i]["css"][$j]["css"] . "}";
                            }
                        }
                    }
                }
            }
            $properties["tooltip-label"] = "";
            $properties["tooltip-description"] = "";
            $properties["tooltip-input"] = "";
            if (array_key_exists("tooltip", $this->form_elements[$i]) && trim($this->form_elements[$i]["tooltip"]) != "") {
                if (array_key_exists("tooltip-anchor", $this->form_options) && $this->form_options["tooltip-anchor"] != "" && $this->form_options["tooltip-anchor"] != "none") {
                    switch ($this->form_options["tooltip-anchor"]) {
                        case 'description':
                            $properties["tooltip-description"] = " <span class='leform-tooltip-anchor leform-if leform-if-help-circled' title='" .
                                $this->replaceWithPredefinedValues(
                                    $this->form_elements[$i]["tooltip"],
                                    $allVariables,
                                )
                                . "'></span>";
                            break;
                        case 'input':
                            $properties["tooltip-input"] = " title='" .
                                $this->replaceWithPredefinedValues(
                                    $this->form_elements[$i]["tooltip"],
                                    $allVariables,
                                )
                                . "'";
                            break;
                        default:
                            $properties["tooltip-label"] = "
                                <span
                                    class='fa fa-info rounded-full border-2 ml-3'
                                    style='padding: 2px 9px; font-size: 0.7em; color: " . ($this->form_options["html-headings-color"]
                            ) . "; border-color: " . ($this->form_options["html-headings-color"]
                            ) . ";'
                                    title='" . $this->replaceWithPredefinedValues(
                                $this->form_elements[$i]["tooltip"],
                                $allVariables,
                            ) . "'
                                ></span>
                            ";
                            break;
                    }
                }
            }

            $properties["required-label-left"] = "";
            $properties["required-label-right"] = "";
            $properties["required-description-left"] = "";
            $properties["required-description-right"] = "";
            if (array_key_exists("required", $this->form_elements[$i]) && trim($this->form_elements[$i]["required"]) == "on") {
                if (array_key_exists("required-position", $this->form_options) && $this->form_options["required-position"] != "" && $this->form_options["required-position"] != "none" && array_key_exists("required-text", $this->form_options) && $this->form_options["required-text"] != "") {
                    switch ($this->form_options["required-position"]) {
                        case 'label-left':
                        case 'label-right':
                        case 'description-left':
                        case 'description-right':
                            $properties["required-" . $this->form_options["required-position"]] = "<span class='leform-required-symbol leform-required-symbol-" . $this->form_options["required-position"] . "'>" . $this->form_options["required-text"] . "</span>";
                            break;
                        default:
                            break;
                    }
                }
            }

            $bindedFields = array_key_exists($this->form_elements[$i]['id'], $oneWayBindingMap)
                ? $oneWayBindingMap[$this->form_elements[$i]['id']]
                : [];
            $bindedFieldsAttribute = count($bindedFields) > 0
                ? "data-binded-fields='" . implode(",", $bindedFields) . "'"
                : "";

            $depsAttribute = array_key_exists($this->form_elements[$i]['id'], $this->form_dependencies) ? 
             "data-deps='".implode(',', $this->form_dependencies[$this->form_elements[$i]['id']])."'" : '';

            if (array_key_exists($this->form_elements[$i]["type"], $this->leform->toolbarTools)) {
                switch ($this->form_elements[$i]["type"]) {
                    case "button":
                    case "link-button":
                        $icon = "";
                        if (array_key_exists("button-style-size", $this->form_elements[$i]) && $this->form_elements[$i]['button-style-size'] != "") $properties['size'] = $this->form_elements[$i]['button-style-size'];
                        else $properties['size'] = $this->form_options['button-style-size'];
                        if (array_key_exists("button-style-width", $this->form_elements[$i]) && $this->form_elements[$i]['button-style-width'] != "") $properties['width'] = $this->form_elements[$i]['button-style-width'];
                        else $properties['width'] = $this->form_options['button-style-width'];
                        if (
                            array_key_exists("button-style-position", $this->form_elements[$i])
                            && $this->form_elements[$i]['button-style-position'] != ""
                        ) {
                            $properties['position'] = $this->form_elements[$i]['button-style-position'];
                        } else {
                            $properties['position'] = $this->form_options['button-style-position'];
                        }
                        $label = '<span>' .
                            $this->replaceWithPredefinedValues(
                                $this->form_elements[$i]["label"],
                                $allVariables,
                            )
                            . '</span>';
                        if (array_key_exists("icon-left", $this->form_elements[$i]) && $this->form_elements[$i]["icon-left"] != "") $label = "<i class='leform-icon-left " . $this->form_elements[$i]["icon-left"] . "'></i>" . $label;
                        if (array_key_exists("icon-right", $this->form_elements[$i]) && $this->form_elements[$i]["icon-right"] != "") $label .= "<i class='leform-icon-right " . $this->form_elements[$i]["icon-right"] . "'></i>";

                        $properties['style-attr'] = "";
                        if (array_key_exists("colors-background", $this->form_elements[$i]) && $this->form_elements[$i]["colors-background"] != "") $properties['style-attr'] .= "background-color:" . $this->form_elements[$i]["colors-background"] . ";";
                        if (array_key_exists("colors-border", $this->form_elements[$i]) && $this->form_elements[$i]["colors-border"] != "") $properties['style-attr'] .= "border-color:" . $this->form_elements[$i]["colors-border"] . ";";
                        if (array_key_exists("colors-text", $this->form_elements[$i]) && $this->form_elements[$i]["colors-text"] != "") $properties['style-attr'] .= "color:" . $this->form_elements[$i]["colors-text"] . ";";
                        if ($properties['style-attr'] != "") $style .= ".leform-form-" . $this->id . " .leform-element-" . $this->form_elements[$i]['id'] . " .leform-button, .leform-form-" . $this->id . " .leform-element-" . $this->form_elements[$i]['id'] . " .leform-button:visited{" . $properties['style-attr'] . "}";

                        $properties['style-attr'] = "";
                        if (array_key_exists("colors-hover-background", $this->form_elements[$i]) && $this->form_elements[$i]["colors-hover-background"] != "") $properties['style-attr'] .= "background-color:" . $this->form_elements[$i]["colors-hover-background"] . ";";
                        if (array_key_exists("colors-hover-border", $this->form_elements[$i]) && $this->form_elements[$i]["colors-hover-border"] != "") $properties['style-attr'] .= "border-color:" . $this->form_elements[$i]["colors-hover-border"] . ";";
                        if (array_key_exists("colors-hover-text", $this->form_elements[$i]) && $this->form_elements[$i]["colors-hover-text"] != "") $properties['style-attr'] .= "color:" . $this->form_elements[$i]["colors-hover-text"] . ";";
                        if ($properties['style-attr'] != "") $style .= ".leform-form-" . $this->id . " .leform-element-" . $this->form_elements[$i]['id'] . " .leform-button:hover, .leform-form-" . $this->id . " .leform-element-" . $this->form_elements[$i]['id'] . " .leform-button:focus{" . $properties['style-attr'] . "}";

                        $properties['style-attr'] = "";
                        if (array_key_exists("colors-active-background", $this->form_elements[$i]) && $this->form_elements[$i]["colors-active-background"] != "") $properties['style-attr'] .= "background-color:" . $this->form_elements[$i]["colors-active-background"] . ";";
                        if (array_key_exists("colors-active-border", $this->form_elements[$i]) && $this->form_elements[$i]["colors-active-border"] != "") $properties['style-attr'] .= "border-color:" . $this->form_elements[$i]["colors-active-border"] . ";";
                        if (array_key_exists("colors-active-text", $this->form_elements[$i]) && $this->form_elements[$i]["colors-active-text"] != "") $properties['style-attr'] .= "color:" . $this->form_elements[$i]["colors-active-text"] . ";";
                        if ($properties['style-attr'] != "") $style .= ".leform-form-" . $this->id . " .leform-element-" . $this->form_elements[$i]['id'] . " .leform-button:active{" . $properties['style-attr'] . "}";

                        $properties['extra_attr'] = '';
                        if ($this->form_elements[$i]["type"] == 'button') {
                            if ($this->form_elements[$i]['button-type'] == 'submit') $properties['extra_attr'] = " href='#' onclick='return leform_submit(this);'";
                            else if ($this->form_elements[$i]['button-type'] == 'next') $properties['extra_attr'] = " href='#' onclick='return leform_submit(this, \"next\");'";
                            else if ($this->form_elements[$i]['button-type'] == 'prev') $properties['extra_attr'] = " href='#' onclick='return leform_submit(this, \"prev\");'";
                        } else {
                            $properties['extra_attr'] = " href='" . $this->form_elements[$i]['link'] . "'" . ($this->form_elements[$i]['new-tab'] == "on" ? " target='_blank'" : "");
                        }

                        if ($this->form_elements[$i]["type"] == 'button') {
                            $html .= "<div class='leform-element leform-element-" . $this->form_elements[$i]['id'] . " leform-ta-" . $properties['position'] . "' data-type='" . $this->form_elements[$i]["type"] . "'><a class='leform-button leform-button-" . $this->form_options["button-active-transform"] . " leform-button-" . $properties['width'] . " leform-button-" . $properties['size'] . " " . $this->form_elements[$i]["css-class"] . "'" . $properties['extra_attr'] . " data-label='" .
                                $this->replaceWithPredefinedValues(
                                    $this->form_elements[$i]["label"],
                                    $allVariables,
                                )
                                . "' data-loading='" . $this->form_elements[$i]["label-loading"] . "'>" . $label . "</a></div>";
                        } else {
                            $html .= "<div class='leform-element leform-element-" . $this->form_elements[$i]['id'] . " leform-ta-"
                                . $properties['position'] .
                                "' data-type='" . $this->form_elements[$i]["type"] . "'><a class='leform-button leform-button-" . $this->form_options["button-active-transform"] . " leform-button-" . $properties['width'] . " leform-button-" . $properties['size'] . " " . $this->form_elements[$i]["css-class"] . "'" . $properties['extra_attr'] . ">" . $label . "</a></div>";
                        }
                        break;

                    case "file":
                        if (array_key_exists("button-style-size", $this->form_elements[$i]) && $this->form_elements[$i]['button-style-size'] != "") $properties['size'] = $this->form_elements[$i]['button-style-size'];
                        else $properties['size'] = $this->form_options['button-style-size'];
                        if (array_key_exists("button-style-width", $this->form_elements[$i]) && $this->form_elements[$i]['button-style-width'] != "") $properties['width'] = $this->form_elements[$i]['button-style-width'];
                        else $properties['width'] = $this->form_options['button-style-width'];
                        if (array_key_exists("button-style-position", $this->form_elements[$i]) && $this->form_elements[$i]['button-style-position'] != "") $properties['position'] = $this->form_elements[$i]['button-style-position'];
                        else $properties['position'] = $this->form_options['button-style-position'];
                        $label = '<span>' . $this->form_elements[$i]["button-label"] . '</span>';
                        if (array_key_exists("icon-left", $this->form_elements[$i]) && $this->form_elements[$i]["icon-left"] != "") $label = "<i class='leform-icon-left " . $this->form_elements[$i]["icon-left"] . "'></i>" . $label;
                        if (array_key_exists("icon-right", $this->form_elements[$i]) && $this->form_elements[$i]["icon-right"] != "") $label .= "<i class='leform-icon-right " . $this->form_elements[$i]["icon-right"] . "'></i>";

                        $properties['style-attr'] = "";
                        if (array_key_exists("colors-background", $this->form_elements[$i]) && $this->form_elements[$i]["colors-background"] != "") $properties['style-attr'] .= "background-color:" . $this->form_elements[$i]["colors-background"] . ";";
                        if (array_key_exists("colors-border", $this->form_elements[$i]) && $this->form_elements[$i]["colors-border"] != "") $properties['style-attr'] .= "border-color:" . $this->form_elements[$i]["colors-border"] . ";";
                        if (array_key_exists("colors-text", $this->form_elements[$i]) && $this->form_elements[$i]["colors-text"] != "") $properties['style-attr'] .= "color:" . $this->form_elements[$i]["colors-text"] . ";";
                        if ($properties['style-attr'] != "") $style .= ".leform-form-" . $this->id . " .leform-element-" . $this->form_elements[$i]['id'] . " .leform-button, .leform-form-" . $this->id . " .leform-element-" . $this->form_elements[$i]['id'] . " .leform-button:visited{" . $properties['style-attr'] . "}";

                        $properties['style-attr'] = "";
                        if (array_key_exists("colors-hover-background", $this->form_elements[$i]) && $this->form_elements[$i]["colors-hover-background"] != "") $properties['style-attr'] .= "background-color:" . $this->form_elements[$i]["colors-hover-background"] . ";";
                        if (array_key_exists("colors-hover-border", $this->form_elements[$i]) && $this->form_elements[$i]["colors-hover-border"] != "") $properties['style-attr'] .= "border-color:" . $this->form_elements[$i]["colors-hover-border"] . ";";
                        if (array_key_exists("colors-hover-text", $this->form_elements[$i]) && $this->form_elements[$i]["colors-hover-text"] != "") $properties['style-attr'] .= "color:" . $this->form_elements[$i]["colors-hover-text"] . ";";
                        if ($properties['style-attr'] != "") $style .= ".leform-form-" . $this->id . " .leform-element-" . $this->form_elements[$i]['id'] . " .leform-button:hover, .leform-form-" . $this->id . " .leform-element-" . $this->form_elements[$i]['id'] . " .leform-button:focus{" . $properties['style-attr'] . "}";

                        $properties['style-attr'] = "";
                        if (array_key_exists("colors-active-background", $this->form_elements[$i]) && $this->form_elements[$i]["colors-active-background"] != "") $properties['style-attr'] .= "background-color:" . $this->form_elements[$i]["colors-active-background"] . ";";
                        if (array_key_exists("colors-active-border", $this->form_elements[$i]) && $this->form_elements[$i]["colors-active-border"] != "") $properties['style-attr'] .= "border-color:" . $this->form_elements[$i]["colors-active-border"] . ";";
                        if (array_key_exists("colors-active-text", $this->form_elements[$i]) && $this->form_elements[$i]["colors-active-text"] != "") $properties['style-attr'] .= "color:" . $this->form_elements[$i]["colors-active-text"] . ";";
                        if ($properties['style-attr'] != "") $style .= ".leform-form-" . $this->id . " .leform-element-" . $this->form_elements[$i]['id'] . " .leform-button:active{" . $properties['style-attr'] . "}";

                        $accept_raw = explode(',', $this->form_elements[$i]['allowed-extensions']);
                        $accept = [];
                        foreach ($accept_raw as $extension) {
                            $extension = trim(trim($extension), '.');
                            if (!empty($extension)) $accept[] = '.' . strtolower($extension);
                        }
                        $upload_template = '
                            <div class="leform-uploader leform-ta-' . $properties['position'] . '" id="%%upload-id%%">
                                <a class="leform-button leform-button-' . $this->form_options["button-active-transform"] . ' leform-button-' . $properties['width'] . ' leform-button-' . $properties['size'] . ' ' . $this->form_elements[$i]["css-class"] . '" onclick="jQuery(this).parent().find(\'.leform-uploader-error\').remove(); jQuery(this).parent().find(\'input[type=file]\').click(); return false;">' . $label . '</a>
                                <div class="leform-uploader-engine">
                                    <form
                                        class="custom-file-uploader"
                                        action="' . route("file-upload") . '"
                                        method="POST"
                                        enctype="multipart/form-data"
                                        onsubmit="return leform_uploader_start(this);"
                                        style="display: none !important; width: 0 !important; height: 0 !important;"
                                    >
                                        <input type="hidden" name="_token" value="' . csrf_token() . '" />
                                        <!--
                                            <input type="hidden" name="action" value="leform-upload" />
                                        -->
                                        <input type="hidden" value="%%upload-id%%" name="' . ini_get("session.upload_progress.name") . '" />
                                        <input type="hidden" name="upload-id" value="%%upload-id%%" />
                                        <input type="hidden" name="form-id" value="' . $this->id . '" />
                                        <input type="hidden" name="element-id" value="' . $this->form_elements[$i]['id'] . '" />
                                        <input type="file" name="files[]"' . (!empty($accept) ? ' accept="' . implode(', ', $accept) . '"' : '') . ' multiple="multiple" onchange="jQuery(this).parent().submit();" style="display: none !important; width: 0 !important; height: 0 !important;" />
                                        <input type="submit" value="Upload" style="display: none !important; width: 0 !important; height: 0 !important;" />
                                    </form>
                                    <!--
                                        <iframe
                                            data-loading="false"
                                            id="leform-iframe-%%upload-id%%"
                                            name="leform-iframe-%%upload-id%%"
                                            src="about:blank"
                                            onload="leform_uploader_finish(this);"
                                            style="display: none !important; width: 0 !important; height: 0 !important;"
                                        ></iframe>
                                    -->
                                </div>
                            </div>';
                        $html .= "<div class='leform-element leform-element-" . $this->form_elements[$i]['id'] . ($properties["label-style-position"] != "" ? " leform-element-label-" . $properties["label-style-position"] : "") . ($this->form_elements[$i]['description-style-position'] != "" ? " leform-element-description-" . $this->form_elements[$i]['description-style-position'] : "") . "' data-type='" . $this->form_elements[$i]["type"] . "' data-deps='" . (array_key_exists($this->form_elements[$i]['id'], $this->form_dependencies) ? implode(',', $this->form_dependencies[$this->form_elements[$i]['id']]) : '') . "' data-id='" . $this->form_elements[$i]['id'] . "' data-max-files='" . intval($this->form_elements[$i]['max-files']) . "' data-max-files-error='" . $this->form_elements[$i]['max-files-error'] . "' data-max-size='" . intval($this->form_elements[$i]['max-size']) . "' data-max-size-error='" . $this->form_elements[$i]['max-size-error'] . "' data-allowed-extensions='" . implode(',', $accept) . "' data-allowed-extensions-error='" . $this->form_elements[$i]['allowed-extensions-error'] . "'><div class='leform-column-label" . $column_label_class . "'><label class='leform-label" . ($this->form_elements[$i]['label-style-align'] != "" ? " leform-ta-" . $this->form_elements[$i]['label-style-align'] : "") . "'>" . $properties["required-label-left"] .
                            $this->replaceWithPredefinedValues(
                                $this->form_elements[$i]["label"],
                                $allVariables,
                            )
                            . $properties["required-label-right"] . $properties["tooltip-label"] . "</label></div><div class='leform-column-input" . $column_input_class . "'><div class='leform-upload-input'" . $properties["tooltip-input"] . "><div class='leform-uploader-files'></div><div class='leform-uploaders'></div><input type='hidden' class='leform-uploader-template' value='" . base64_encode($upload_template) . "' /></div><label class='leform-description" . ($this->form_elements[$i]['description-style-align'] != "" ? " leform-ta-" . $this->form_elements[$i]['description-style-align'] : "") . "'>" . $properties["required-description-left"] .
                            $this->replaceWithPredefinedValues(
                                $this->form_elements[$i]["description"],
                                $allVariables,
                            )
                            . $properties["required-description-right"] . $properties["tooltip-description"] . "</label></div></div>";
                        break;

                    case "email":
                        if ($this->form_elements[$i]['input-style-size'] != "") $extra_class .= " leform-input-" . $this->form_elements[$i]['input-style-size'];
                        $html .= "<div class='leform-element leform-element-" . $this->form_elements[$i]['id'] . ($properties["label-style-position"] != "" ? " leform-element-label-" . $properties["label-style-position"] : "") . ($this->form_elements[$i]['description-style-position'] != "" ? " leform-element-description-" . $this->form_elements[$i]['description-style-position'] : "") . "' data-type='" . $this->form_elements[$i]["type"] . "' data-deps='" . (array_key_exists($this->form_elements[$i]['id'], $this->form_dependencies) ? implode(',', $this->form_dependencies[$this->form_elements[$i]['id']]) : '') . "'" . ($this->form_elements[$i]['dynamic-default'] == 'on' ? " data-dynamic='" . $this->form_elements[$i]['dynamic-parameter'] . "'" : "") . " data-id='" . $this->form_elements[$i]['id'] . "'
                            " . $bindedFieldsAttribute . "
                            ><div class='leform-column-label" . $column_label_class . "'><label class='leform-label" . ($this->form_elements[$i]['label-style-align'] != "" ? " leform-ta-" . $this->form_elements[$i]['label-style-align'] : "") . "'>" . $properties["required-label-left"] . $this->replaceWithPredefinedValues($this->form_elements[$i]["label"], $allVariables) . $properties["required-label-right"] . $properties["tooltip-label"] . "</label></div><div class='leform-column-input" . $column_input_class . "'><div class='leform-input" . $extra_class . "'" . $properties["tooltip-input"] . ">" . $icon . "<input type='email' name='leform-" . $this->form_elements[$i]['id'] . "' class='" . ($this->form_elements[$i]['input-style-align'] != "" ? "leform-ta-" . $this->form_elements[$i]['input-style-align'] . " " : "") . $this->form_elements[$i]["css-class"] . "' placeholder='" . $this->replaceWithPredefinedValues($this->form_elements[$i]["placeholder"], $allVariables) . "' autocomplete='" . $this->form_elements[$i]["autocomplete"] . "' data-default='" . $this->replaceWithPredefinedValues($this->form_elements[$i]["default"], $allVariables) . "' value='" . $this->replaceWithPredefinedValues($this->form_elements[$i]["default"], $allVariables) . "' aria-label='" . $this->replaceWithPredefinedValues($this->form_elements[$i]["label"], $allVariables) . "' data-input-name='" . $this->form_elements[$i]["name"] . "' oninput='leform_input_changed(this);' onfocus='jQuery(this).closest(\".leform-input\").find(\".leform-element-error\").fadeOut(300, function(){jQuery(this).remove();});'" . ($this->form_elements[$i]["readonly"] == 'on' ? " readonly='readonly'" : "") . " /></div><label class='leform-description" . ($this->form_elements[$i]['description-style-align'] != "" ? " leform-ta-" . $this->form_elements[$i]['description-style-align'] : "") . "'>" . $properties["required-description-left"] . $this->replaceWithPredefinedValues($this->form_elements[$i]["description"], $allVariables) . $properties["required-description-right"] . $properties["tooltip-description"] . "</label></div></div>";
                        break;

                    case "text": {
                            $masked = $this->leform->options['mask-enable'] == "on"
                                && array_key_exists("mask-mask", $this->form_elements[$i])
                                && !empty($this->form_elements[$i]["mask-mask"]);

                            if ($this->form_elements[$i]['input-style-size'] != "") {
                                $extra_class .= " leform-input-" . $this->form_elements[$i]['input-style-size'];
                            }

                            $this->form_elements[$i]["default"] = FormService::getExternalValuesAsString(
                                $this->form_elements[$i],
                                $this->form_elements[$i]["default"],
                                []
                            );
                            $html .= "
                            <div
                                class='leform-element leform-element-" . $this->form_elements[$i]['id'] . ($properties["label-style-position"] != "" ? " leform-element-label-" . $properties["label-style-position"] : "") . ($this->form_elements[$i]['description-style-position'] != "" ? " leform-element-description-" . $this->form_elements[$i]['description-style-position'] : "") . "'
                                data-type='" . $this->form_elements[$i]["type"] . "'
                                data-deps='" . (array_key_exists($this->form_elements[$i]['id'], $this->form_dependencies) ? implode(',', $this->form_dependencies[$this->form_elements[$i]['id']]) : '') . "'"
                                . ($this->form_elements[$i]['dynamic-default'] == 'on' ? " data-dynamic='" . $this->form_elements[$i]['dynamic-parameter'] . "'" : "") . "
                                data-id='" . $this->form_elements[$i]['id'] . "'
                                " . $bindedFieldsAttribute . "
                            >
                                <div class='leform-column-label" . $column_label_class . "'>
                                    <label class='leform-label" . ($this->form_elements[$i]['label-style-align'] != "" ? " leform-ta-" . $this->form_elements[$i]['label-style-align'] : "") . "'>
                                        " . $this->replaceWithPredefinedValues(
                                    $properties["required-label-left"]
                                        . $this->form_elements[$i]["label"]
                                        . $properties["required-label-right"]
                                        . $properties["tooltip-label"],
                                    $allVariables,
                                ) . "
                                    </label>
                                </div>
                                <div class='leform-column-input" . $column_input_class . "'>
                                    <div class='leform-input" . $extra_class . "'" . $this->replaceWithPredefinedValues(
                                    $properties["tooltip-input"],
                                    $allVariables,
                                ) . ">
                                        " . $icon . "
                                        <input
                                            type='text'
                                            name='leform-" . $this->form_elements[$i]['id'] . "'
                                            class='" . ($this->form_elements[$i]['input-style-align'] != "" ? "leform-ta-" . $this->form_elements[$i]['input-style-align'] . " " : "") . ($masked ? "leform-mask " : "") . $this->form_elements[$i]["css-class"] . "'
                                            placeholder='" . $this->replaceWithPredefinedValues(
                                    $this->form_elements[$i]["placeholder"],
                                    $allVariables,
                                ) . "'
                                            autocomplete='" . $this->form_elements[$i]["autocomplete"] . "'
                                            data-default='" . $this->replaceWithPredefinedValues(
                                    $this->form_elements[$i]["default"],
                                    $allVariables,
                                ) . "'" . ($masked ? "data-xmask='" . $this->form_elements[$i]["mask-mask"] . "'" : "") . "
                                            value='" . $this->replaceWithPredefinedValues(
                                    $this->form_elements[$i]["default"],
                                    $allVariables,
                                ) . "'
                                            aria-label='" . $this->replaceWithPredefinedValues(
                                    $this->form_elements[$i]["label"],
                                    $allVariables,
                                ) . "'
                                            oninput='leform_input_changed(this);' 
                                            data-input-name='" . $this->form_elements[$i]["name"] . "'
                                            onfocus='jQuery(this).closest(\".leform-input\").find(\".leform-element-error\").fadeOut(300, function(){jQuery(this).remove();});'" . ($this->form_elements[$i]["readonly"] == 'on' ? " readonly='readonly'" : "") . "
                                        />
                                    </div>
                                    <label class='leform-description" . ($this->form_elements[$i]['description-style-align'] != "" ? " leform-ta-" . $this->form_elements[$i]['description-style-align'] : "") . "'>
                                        " . $this->replaceWithPredefinedValues(
                                    $properties["required-description-left"]
                                        . $this->form_elements[$i]["description"]
                                        . $properties["required-description-right"]
                                        . $properties["tooltip-description"],
                                    $allVariables
                                ) . "
                                    </label>
                                </div>
                            </div>
                        ";
                            break;
                        }

                    case "number":
                        if ($this->form_elements[$i]['input-style-size'] != "") $extra_class .= " leform-input-" . $this->form_elements[$i]['input-style-size'];
                        $html .= "<div class='leform-element leform-element-" . $this->form_elements[$i]['id'] . ($properties["label-style-position"] != "" ? " leform-element-label-" . $properties["label-style-position"] : "") . ($this->form_elements[$i]['description-style-position'] != "" ? " leform-element-description-" . $this->form_elements[$i]['description-style-position'] : "") . "' data-type='" . $this->form_elements[$i]["type"] . "' data-deps='" . (array_key_exists($this->form_elements[$i]['id'], $this->form_dependencies) ? implode(',', $this->form_dependencies[$this->form_elements[$i]['id']]) : '') . "'" . ($this->form_elements[$i]['dynamic-default'] == 'on' ? " data-dynamic='" . $this->form_elements[$i]['dynamic-parameter'] . "'" : "") . " data-id='" . $this->form_elements[$i]['id'] . "'><div class='leform-column-label" . $column_label_class . "'><label class='leform-label" . ($this->form_elements[$i]['label-style-align'] != "" ? " leform-ta-" . $this->form_elements[$i]['label-style-align'] : "") . "'>" . $properties["required-label-left"] . $this->replaceWithPredefinedValues($this->form_elements[$i]["label"], $allVariables) . $properties["required-label-right"] . $properties["tooltip-label"] . "</label></div><div class='leform-column-input" . $column_input_class . "'><div class='leform-input" . $extra_class . "'" . $properties["tooltip-input"] . ">" . $icon . "<input type='text' inputmode='numeric' pattern='[0-9]*' name='leform-" . $this->form_elements[$i]['id'] . "' class='leform-number" . ($this->form_elements[$i]['input-style-align'] != "" ? " leform-ta-" . $this->form_elements[$i]['input-style-align'] . " " : "") . $this->form_elements[$i]["css-class"] . "' placeholder='" . $this->replaceWithPredefinedValues($this->form_elements[$i]["placeholder"], $allVariables) . "' data-default='" .
                            $this->replaceWithPredefinedValues(
                                $this->form_elements[$i]["number-value3"],
                                $allVariables
                            ) . "' data-min='" . $this->form_elements[$i]["number-value1"] . "' data-max='" . $this->form_elements[$i]["number-value2"] . "' data-decimal='" . $this->form_elements[$i]["decimal"] . "' data-value='" .
                            $this->replaceWithPredefinedValues(
                                $this->form_elements[$i]["number-value3"],
                                $allVariables
                            ) . "' value='" . $this->replaceWithPredefinedValues(
                                $this->form_elements[$i]["number-value3"],
                                $allVariables
                            ) . "' aria-label='" . $this->replaceWithPredefinedValues($this->form_elements[$i]["label"], $allVariables) . "' oninput='leform_input_changed(this);' data-input-name='" . $this->form_elements[$i]["name"] . "' onblur='leform_number_unfocused(this);' onfocus='jQuery(this).closest(\".leform-input\").find(\".leform-element-error\").fadeOut(300, function(){jQuery(this).remove();});'" . ($this->form_elements[$i]["readonly"] == 'on' ? " readonly='readonly'" : "") . " /></div><label class='leform-description" . ($this->form_elements[$i]['description-style-align'] != "" ? " leform-ta-" . $this->form_elements[$i]['description-style-align'] : "") . "'>" . $properties["required-description-left"] . $this->replaceWithPredefinedValues($this->form_elements[$i]["description"], $allVariables) . $properties["required-description-right"] . $properties["tooltip-description"] . "</label></div></div>";
                        break;

                    case "numspinner":
                        $ranges = $this->_prepare_ranges($this->form_elements[$i]["number-advanced-value2"]);
                        if ($this->form_elements[$i]['input-style-size'] != "") $extra_class .= " leform-input-" . $this->form_elements[$i]['input-style-size'];
                        $html .= "<div class='leform-element leform-element-" . $this->form_elements[$i]['id'] . ($properties["label-style-position"] != "" ? " leform-element-label-" . $properties["label-style-position"] : "") . ($this->form_elements[$i]['description-style-position'] != "" ? " leform-element-description-" . $this->form_elements[$i]['description-style-position'] : "") . "' data-type='" . $this->form_elements[$i]["type"] . "' data-deps='" . (array_key_exists($this->form_elements[$i]['id'], $this->form_dependencies) ? implode(',', $this->form_dependencies[$this->form_elements[$i]['id']]) : '') . "'" . ($this->form_elements[$i]['dynamic-default'] == 'on' ? " data-dynamic='" . $this->form_elements[$i]['dynamic-parameter'] . "'" : "") . " data-id='" . $this->form_elements[$i]['id'] . "'><div class='leform-column-label" . $column_label_class . "'><label class='leform-label" . ($this->form_elements[$i]['label-style-align'] != "" ? " leform-ta-" . $this->form_elements[$i]['label-style-align'] : "") . "'>" . $properties["required-label-left"] . $this->replaceWithPredefinedValues($this->form_elements[$i]["label"], $allVariables) . $properties["required-label-right"] . $properties["tooltip-label"] . "</label></div><div class='leform-column-input" . $column_input_class . "'><div class='leform-input leform-icon-left leform-icon-right" . $extra_class . "'" . $properties["tooltip-input"] . "><i class='leform-icon-left leform-if leform-if-minus leform-numspinner-minus' onclick='leform_numspinner_dec(this);'></i><i class='leform-icon-right leform-if leform-if-plus leform-numspinner-plus' onclick='leform_numspinner_inc(this);'></i><input type='text' readonly='readonly' name='leform-" . $this->form_elements[$i]['id'] . "' class='leform-number" . ($this->form_elements[$i]['input-style-align'] != "" ? " leform-ta-" . $this->form_elements[$i]['input-style-align'] . " " : "") . $this->form_elements[$i]["css-class"] . "' placeholder='...' data-mode='" . ($this->form_elements[$i]["simple-mode"] == 'on' ? 'simple' : 'advanced') . "' data-default='" . ($this->form_elements[$i]["simple-mode"] == 'on' ? number_format($this->form_elements[$i]["number-value2"], $this->form_elements[$i]["decimal"], '.', '') : number_format($this->form_elements[$i]["number-advanced-value1"], $this->form_elements[$i]["decimal"], '.', '')) . "'" . ($this->form_elements[$i]["simple-mode"] == 'on' ? " data-min='" . $this->form_elements[$i]["number-value1"] . "' data-max='" . $this->form_elements[$i]["number-value3"] . "'" : " data-range='" . $ranges . "'") . " data-step='" . ($this->form_elements[$i]["simple-mode"] == 'on' ? $this->form_elements[$i]["number-value4"] : $this->form_elements[$i]["number-advanced-value3"]) . "' data-decimal='" . $this->form_elements[$i]["decimal"] . "' data-value='" . ($this->form_elements[$i]["simple-mode"] == 'on' ? number_format($this->form_elements[$i]["number-value2"], $this->form_elements[$i]["decimal"], '.', '') : number_format($this->form_elements[$i]["number-advanced-value1"], $this->form_elements[$i]["decimal"], '.', '')) . "' value='" . ($this->form_elements[$i]["simple-mode"] == 'on' ? number_format($this->form_elements[$i]["number-value2"], $this->form_elements[$i]["decimal"], '.', '') : number_format($this->form_elements[$i]["number-advanced-value1"], $this->form_elements[$i]["decimal"], '.', '')) . "'" . ($this->form_elements[$i]["readonly"] == 'on' ? " data-readonly='on'" : " data-readonly='off'") . " aria-label='" . $this->replaceWithPredefinedValues($this->form_elements[$i]["label"], $allVariables) . "' /></div><label class='leform-description" . ($this->form_elements[$i]['description-style-align'] != "" ? " leform-ta-" . $this->form_elements[$i]['description-style-align'] : "") . "'>" . $properties["required-description-left"] . $this->replaceWithPredefinedValues($this->form_elements[$i]["description"], $allVariables) . $properties["required-description-right"] . $properties["tooltip-description"] . "</label></div></div>";
                        break;

                    case "password":
                        if ($this->form_elements[$i]['input-style-size'] != "") $extra_class .= " leform-input-" . $this->form_elements[$i]['input-style-size'];
                        $html .= "<div class='leform-element leform-element-" . $this->form_elements[$i]['id'] . ($properties["label-style-position"] != "" ? " leform-element-label-" . $properties["label-style-position"] : "") . ($this->form_elements[$i]['description-style-position'] != "" ? " leform-element-description-" . $this->form_elements[$i]['description-style-position'] : "") . "' data-type='" . $this->form_elements[$i]["type"] . "' data-deps='" . (array_key_exists($this->form_elements[$i]['id'], $this->form_dependencies) ? implode(',', $this->form_dependencies[$this->form_elements[$i]['id']]) : '') . "' data-id='" . $this->form_elements[$i]['id'] . "'><div class='leform-column-label" . $column_label_class . "'><label class='leform-label" . ($this->form_elements[$i]['label-style-align'] != "" ? " leform-ta-" . $this->form_elements[$i]['label-style-align'] : "") . "'>" . $properties["required-label-left"] . $this->replaceWithPredefinedValues($this->form_elements[$i]["label"], $allVariables) . $properties["required-label-right"] . $properties["tooltip-label"] . "</label></div><div class='leform-column-input" . $column_input_class . "'><div class='leform-input" . $extra_class . "'" . $properties["tooltip-input"] . ">" . $icon . "<input type='password' name='leform-" . $this->form_elements[$i]['id'] . "' class='" . ($this->form_elements[$i]['input-style-align'] != "" ? "leform-ta-" . $this->form_elements[$i]['input-style-align'] . " " : "") . $this->form_elements[$i]["css-class"] . "' placeholder='" . $this->replaceWithPredefinedValues($this->form_elements[$i]["placeholder"], $allVariables) . "' data-default='' value='' aria-label='" . $this->replaceWithPredefinedValues($this->form_elements[$i]["label"], $allVariables) . "' data-input-name='" . $this->form_elements[$i]["name"] . "' oninput='leform_input_changed(this);' onfocus='jQuery(this).closest(\".leform-input\").find(\".leform-element-error\").fadeOut(300, function(){jQuery(this).remove();});' /></div><label class='leform-description" . ($this->form_elements[$i]['description-style-align'] != "" ? " leform-ta-" . $this->form_elements[$i]['description-style-align'] : "") . "'>" . $properties["required-description-left"] . $this->replaceWithPredefinedValues($this->form_elements[$i]["description"], $allVariables) . $properties["required-description-right"] . $properties["tooltip-description"] . "</label></div></div>";
                        break;

                    case "date":
                        $value = '';
                        $default = '';
                        $offset = 0;
                        if (array_key_exists("default", $this->form_elements[$i])) {
                            $default = $this->replaceWithPredefinedValues(
                                $this->form_elements[$i]["default"],
                                $allVariables
                            );
                        }
                        if (array_key_exists("default-type", $this->form_elements[$i])) {
                            $default = '';
                            if ($this->form_elements[$i]["default-type"] == "date") {
                                /*
                                $default = $this->form_elements[$i]['default-date'];
                                $value = $this->form_elements[$i]['default-date'];
                                 */
                                $default = $this->replaceWithPredefinedValues(
                                    $this->form_elements[$i]["default-date"],
                                    $allVariables
                                );
                                $value = $this->replaceWithPredefinedValues(
                                    $this->form_elements[$i]["default-date"],
                                    $allVariables
                                );
                            } else if ($this->form_elements[$i]["default-type"] == "offset") {
                                /*
                                $default = $this->form_elements[$i]['default-type'];
                                $offset = $this->form_elements[$i]['default-offset'];
                                */

                                $default = $this->form_elements[$i]['default-type'];
                                $offset = $this->replaceWithPredefinedValues(
                                    $this->form_elements[$i]["default-offset"],
                                    $allVariables
                                );
                            } else if ($this->form_elements[$i]["default-type"] != "none") {
                                $default = $this->form_elements[$i]['default-type'];
                            }
                        }
                        if ($this->form_elements[$i]['input-style-size'] != "") $extra_class .= " leform-input-" . $this->form_elements[$i]['input-style-size'];
                        $html .= "<div class='leform-element leform-element-" . $this->form_elements[$i]['id'] . ($properties["label-style-position"] != "" ? " leform-element-label-" . $properties["label-style-position"] : "") . ($this->form_elements[$i]['description-style-position'] != "" ? " leform-element-description-" . $this->form_elements[$i]['description-style-position'] : "") . "' data-type='" . $this->form_elements[$i]["type"] . "' data-deps='" . (array_key_exists($this->form_elements[$i]['id'], $this->form_dependencies) ? implode(',', $this->form_dependencies[$this->form_elements[$i]['id']]) : '') . "'" . ($this->form_elements[$i]['dynamic-default'] == 'on' ? " data-dynamic='" . $this->form_elements[$i]['dynamic-parameter'] . "'" : "") . " data-id='" . $this->form_elements[$i]['id'] . "'
                            " . $bindedFieldsAttribute . "><div class='leform-column-label" . $column_label_class . "'><label class='leform-label" . ($this->form_elements[$i]['label-style-align'] != "" ? " leform-ta-" . $this->form_elements[$i]['label-style-align'] : "") . "'>" . $properties["required-label-left"] . $this->replaceWithPredefinedValues($this->form_elements[$i]["label"], $allVariables) . $properties["required-label-right"] . $properties["tooltip-label"] . "</label></div><div class='leform-column-input" . $column_input_class . "'><div class='leform-input" . $extra_class . "'" . $properties["tooltip-input"] . ">" . $icon . "<input type='text' name='leform-" . $this->form_elements[$i]['id'] . "' class='leform-date " . ($this->form_elements[$i]['input-style-align'] != "" ? "leform-ta-" . $this->form_elements[$i]['input-style-align'] . " " : "") . $this->form_elements[$i]["css-class"] . "' placeholder='" . $this->replaceWithPredefinedValues($this->form_elements[$i]["placeholder"], $allVariables) . "' autocomplete='" . $this->form_elements[$i]["autocomplete"] . "' data-default='" . $default . "' data-offset='" . $offset . "' value='" . $value . "' aria-label='" . $this->replaceWithPredefinedValues($this->form_elements[$i]["label"], $allVariables) . "' data-input-name='" . $this->form_elements[$i]["name"] . "' oninput='leform_input_changed(this);' onfocus='jQuery(this).closest(\".leform-input\").find(\".leform-element-error\").fadeOut(300, function(){jQuery(this).remove();});'" . ($this->form_elements[$i]["readonly"] == 'on' ? " readonly='readonly'" : "") . " data-format='" . $this->form_options['datetime-args-date-format'] . "' data-locale='" . $this->form_options['datetime-args-locale'] . "' data-min-type='" . $this->form_elements[$i]['min-date-type'] . "' data-min-value='" . ($this->form_elements[$i]['min-date-type'] == 'date' ? $this->form_elements[$i]['min-date-date'] : ($this->form_elements[$i]['min-date-type'] == 'field' ? $this->form_elements[$i]['min-date-field'] : ($this->form_elements[$i]['min-date-type'] == 'offset' ? $this->form_elements[$i]['min-date-offset'] : ''))) . "' data-max-type='" . $this->form_elements[$i]['max-date-type'] . "' data-max-value='" . ($this->form_elements[$i]['max-date-type'] == 'date' ? $this->form_elements[$i]['max-date-date'] : ($this->form_elements[$i]['max-date-type'] == 'field' ? $this->form_elements[$i]['max-date-field'] : ($this->form_elements[$i]['max-date-type'] == 'offset' ? $this->form_elements[$i]['max-date-offset'] : ''))) . "' /></div><label class='leform-description" . ($this->form_elements[$i]['description-style-align'] != "" ? " leform-ta-" . $this->form_elements[$i]['description-style-align'] : "") . "'>" . $properties["required-description-left"] . $this->replaceWithPredefinedValues($this->form_elements[$i]["description"], $allVariables) . $properties["required-description-right"] . $properties["tooltip-description"] . "</label></div></div>";
                        break;

                    case "time":
                        if ($this->form_elements[$i]['input-style-size'] != "") $extra_class .= " leform-input-" . $this->form_elements[$i]['input-style-size'];
                        $html .= "<div class='leform-element leform-element-" . $this->form_elements[$i]['id'] . ($properties["label-style-position"] != "" ? " leform-element-label-" . $properties["label-style-position"] : "") . ($this->form_elements[$i]['description-style-position'] != "" ? " leform-element-description-" . $this->form_elements[$i]['description-style-position'] : "") . "' data-type='" . $this->form_elements[$i]["type"] . "' data-deps='" . (array_key_exists($this->form_elements[$i]['id'], $this->form_dependencies) ? implode(',', $this->form_dependencies[$this->form_elements[$i]['id']]) : '') . "'" . ($this->form_elements[$i]['dynamic-default'] == 'on' ? " data-dynamic='" . $this->form_elements[$i]['dynamic-parameter'] . "'" : "") . " data-id='" . $this->form_elements[$i]['id'] . "'><div class='leform-column-label" . $column_label_class . "'><label class='leform-label" . ($this->form_elements[$i]['label-style-align'] != "" ? " leform-ta-" . $this->form_elements[$i]['label-style-align'] : "") . "'>" . $properties["required-label-left"] . $this->replaceWithPredefinedValues($this->form_elements[$i]["label"], $allVariables) . $properties["required-label-right"] . $properties["tooltip-label"] . "</label></div><div class='leform-column-input" . $column_input_class . "'><div class='leform-input" . $extra_class . "'" . $properties["tooltip-input"] . ">" . $icon . "<input type='text' name='leform-" . $this->form_elements[$i]['id'] . "' class='leform-time " . ($this->form_elements[$i]['input-style-align'] != "" ? "leform-ta-" . $this->form_elements[$i]['input-style-align'] . " " : "") . $this->form_elements[$i]["css-class"] . "' placeholder='" . $this->replaceWithPredefinedValues($this->form_elements[$i]["placeholder"], $allVariables) . "' data-default='" . $this->replaceWithPredefinedValues($this->form_elements[$i]["default"], $allVariables) . "' value='" . $this->replaceWithPredefinedValues($this->form_elements[$i]["default"], $allVariables) . "' aria-label='" . $this->replaceWithPredefinedValues($this->form_elements[$i]["label"], $allVariables) . "' data-input-name='" . $this->form_elements[$i]["name"] . "' data-input-name='" . $this->form_elements[$i]["name"] . "' oninput='leform_input_changed(this);' onfocus='jQuery(this).closest(\".leform-input\").find(\".leform-element-error\").fadeOut(300, function(){jQuery(this).remove();});'" . ($this->form_elements[$i]["readonly"] == 'on' ? " readonly='readonly'" : "") . " data-interval='" . $this->form_elements[$i]['interval'] . "' data-format='" . $this->form_options['datetime-args-time-format'] . "' data-locale='" . $this->form_options['datetime-args-locale'] . "' data-min-type='" . $this->form_elements[$i]['min-time-type'] . "' data-min-value='" . ($this->form_elements[$i]['min-time-type'] == 'time' ? $this->form_elements[$i]['min-time-time'] : ($this->form_elements[$i]['min-time-type'] == 'field' ? $this->form_elements[$i]['min-time-field'] : '')) . "' data-max-type='" . $this->form_elements[$i]['max-time-type'] . "' data-max-value='" . ($this->form_elements[$i]['max-time-type'] == 'time' ? $this->form_elements[$i]['max-time-time'] : ($this->form_elements[$i]['max-time-type'] == 'field' ? $this->form_elements[$i]['max-time-field'] : '')) . "' /></div><label class='leform-description" . ($this->form_elements[$i]['description-style-align'] != "" ? " leform-ta-" . $this->form_elements[$i]['description-style-align'] : "") . "'>" . $properties["required-description-left"] . $this->replaceWithPredefinedValues($this->form_elements[$i]["description"], $allVariables) . $properties["required-description-right"] . $properties["tooltip-description"] . "</label></div></div>";
                        break;

                    case "textarea":
                        $properties["textarea-height"] = $this->form_elements[$i]["textarea-style-height"];
                        if ($properties["textarea-height"] == "") $properties["textarea-height"] = $this->form_options["textarea-height"];
                        if ($properties["textarea-height"] == "") $properties["textarea-height"] = 160;

                        $this->form_elements[$i]["default"] = FormService::getExternalValuesAsString(
                            $this->form_elements[$i],
                            $this->form_elements[$i]["default"],
                            []
                        );
                        $style .= ".leform-form-" . $this->id . " .leform-element-" . $this->form_elements[$i]['id'] . " div.leform-input {height:" . $properties["textarea-height"] . "px; line-height:2.5;} .leform-form-" . $this->id . " .leform-element-" . $this->form_elements[$i]['id'] . " div.leform-input textarea{line-height:1.4;}";
                        $html .= "<div class='leform-element leform-element-" . $this->form_elements[$i]['id'] . ($properties['label-style-position'] != "" ? " leform-element-label-" . $properties["label-style-position"] : "") . ($this->form_elements[$i]['description-style-position'] != "" ? " leform-element-description-" . $this->form_elements[$i]['description-style-position'] : "") . "' data-type='" . $this->form_elements[$i]["type"] . "' data-deps='" . (array_key_exists($this->form_elements[$i]['id'], $this->form_dependencies) ? implode(',', $this->form_dependencies[$this->form_elements[$i]['id']]) : '') . "'" . ($this->form_elements[$i]['dynamic-default'] == 'on' ? " data-dynamic='" . $this->form_elements[$i]['dynamic-parameter'] . "'" : "") . " data-id='" . $this->form_elements[$i]['id'] . "'
                            " . $bindedFieldsAttribute . "
                            ><div class='leform-column-label" . $column_label_class . "'><label class='leform-label" . ($this->form_elements[$i]['label-style-align'] != "" ? " leform-ta-" . $this->form_elements[$i]['label-style-align'] : "") . "'>" . $properties["required-label-left"] . $this->replaceWithPredefinedValues($this->form_elements[$i]["label"], $allVariables) . $properties["required-label-right"] . $properties["tooltip-label"] . "</label></div><div class='leform-column-input" . $column_input_class . "'><div class='leform-input" . $extra_class . "'" . $properties["tooltip-input"] . ">" . $icon . "<textarea name='leform-" . $this->form_elements[$i]['id'] . "' class='" . ($this->form_elements[$i]['textarea-style-align'] != "" ? "leform-ta-" . $this->form_elements[$i]['textarea-style-align'] . " " : "") . $this->form_elements[$i]["css-class"] . "' placeholder='" . $this->replaceWithPredefinedValues($this->form_elements[$i]["placeholder"], $allVariables) . "' aria-label='" . $this->replaceWithPredefinedValues($this->form_elements[$i]["label"], $allVariables) . "' data-default='" . base64_encode($this->replaceWithPredefinedValues($this->form_elements[$i]["default"], $allVariables)) . "' data-input-name='" . $this->form_elements[$i]["name"] . "' oninput='leform_input_changed(this);' onfocus='jQuery(this).closest(\".leform-input\").find(\".leform-element-error\").fadeOut(300, function(){jQuery(this).remove();});'" . ($this->form_elements[$i]["readonly"] == 'on' ? " readonly='readonly'" : "") . (array_key_exists("maxlength", $this->form_elements[$i]) && intval($this->form_elements[$i]["maxlength"]) > 0 ? " maxlength='" . intval($this->form_elements[$i]["maxlength"]) . "'" : "") . ">" . $this->replaceWithPredefinedValues($this->form_elements[$i]["default"], $allVariables) . "</textarea></div><label class='leform-description" . ($this->form_elements[$i]['description-style-align'] != "" ? " leform-ta-" . $this->form_elements[$i]['description-style-align'] : "") . "'>" . $properties["required-description-left"] . $this->replaceWithPredefinedValues($this->form_elements[$i]["description"], $allVariables) . $properties["required-description-right"] . $properties["tooltip-description"] . "</label></div></div>";
                        break;

                    case "signature":
                        $properties["height"] = $this->form_elements[$i]["height"];
                        if (empty($properties["height"])) {
                            $properties["height"] = 220;
                        }
                        $style .= ".leform-form-" . $this->id . " .leform-element-" . $this->form_elements[$i]['id'] . " div.leform-input {height:auto;} .leform-form-" . $this->id . " .leform-element-" . $this->form_elements[$i]['id'] . " div.leform-input div.leform-signature-box {height:" . $properties["height"] . "px;}";
                        $html .= view("components.leform.components.signature-url-provider", [
                            "element" => $this->form_elements[$i],
                            "properties" => $properties,
                            "column_label_class" => $column_label_class,
                            "form_dependencies" => $this->form_dependencies,
                            "form_options" => $this->form_options,
                            "column_input_class" => $column_input_class,
                            "extra_class" => $extra_class,
                            "form_id" => $this->id,
                            "predefinedValues" => $predefinedValues,
                            "smtpSettingsConfigured" => SettingsService::areSmtpSettingsConfigured($this->company_id),
                            "smsSettingsConfigured" => SettingsService::areSmsSettingsConfigured($this->company_id)
                        ]);
                        break;

                    case "rangeslider":
                        $style .= ".leform-form-" . $this->id . " .leform-element-" . $this->form_elements[$i]['id'] . " div.leform-input{height:auto;line-height:1;}";
                        $options = ($this->form_elements[$i]["readonly"] == "on" ?  "data-from-fixed='true' data-to-fixed='true'" : "") . " " . ($this->form_elements[$i]["double"] == "on" ? "data-type='double'" : "data-type='single'") . " " . ($this->form_elements[$i]["grid-enable"] == "on" ? "data-grid='true'" : "data-grid='false'") . " " . ($this->form_elements[$i]["min-max-labels"] == "on" ? "data-hide-min-max='false'" : "data-hide-min-max='true'") . " data-skin='" . $this->form_options['rangeslider-skin'] . "' data-min='" . $this->form_elements[$i]["range-value1"] . "' data-max='" . $this->form_elements[$i]["range-value2"] . "' data-step='" . $this->form_elements[$i]["range-value3"] . "' data-from='" .
                            $this->replaceWithPredefinedValues(
                                $this->form_elements[$i]["handle"],
                                $allVariables
                            )
                            . "'data-to='" .
                            $this->replaceWithPredefinedValues(
                                $this->form_elements[$i]["handle2"],
                                $allVariables
                            )
                            . "' data-prefix='" . $this->form_elements[$i]["prefix"] . "' data-postfix='" . $this->form_elements[$i]["postfix"] . "' data-input-values-separator=':'";
                        $html .= "
                            <div class='leform-element leform-element-" . $this->form_elements[$i]['id'] . ($properties["label-style-position"] != "" ? " leform-element-label-" . $properties["label-style-position"] : "") . ($this->form_elements[$i]['description-style-position'] != "" ? " leform-element-description-" . $this->form_elements[$i]['description-style-position'] : "") . "' data-type='" . $this->form_elements[$i]["type"] . "' data-deps='" . (array_key_exists($this->form_elements[$i]['id'], $this->form_dependencies) ? implode(',', $this->form_dependencies[$this->form_elements[$i]['id']]) : '') . "'" . " data-id='" . $this->form_elements[$i]['id'] . "'><div class='leform-column-label" . $column_label_class . "'><label class='leform-label" . ($this->form_elements[$i]['label-style-align'] != "" ? " leform-ta-" . $this->form_elements[$i]['label-style-align'] : "") . "'>" . $properties["required-label-left"] . $this->replaceWithPredefinedValues($this->form_elements[$i]["label"], $allVariables) . $properties["required-label-right"] . $properties["tooltip-label"] . "</label></div><div class='leform-column-input" . $column_input_class . "'><div class='leform-input leform-rangeslider" . $extra_class . "'" . $properties["tooltip-input"] . ">
                                <input
                                    type='text'
                                    name='leform-" . $this->form_elements[$i]['id'] . "'
                                    class='leform-rangeslider " . $this->form_elements[$i]["css-class"] . "'
                                    " . $options . "
                                    data-default='" . $this->replaceWithPredefinedValues(
                            $this->form_elements[$i]["handle"],
                            $allVariables
                        ) . ($this->form_elements[$i]["double"] == 'on' ? ':' . $this->replaceWithPredefinedValues(
                            $this->form_elements[$i]["handle2"],
                            $allVariables
                        ) : '') . "'
                                    value='" . $this->replaceWithPredefinedValues(
                            $this->form_elements[$i]["handle"],
                            $allVariables
                        ) . ($this->form_elements[$i]["double"] == 'on' ? ':' . $this->replaceWithPredefinedValues(
                            $this->form_elements[$i]["handle2"],
                            $allVariables
                        ) : '') . "'
                                    aria-label='" . $this->replaceWithPredefinedValues($this->form_elements[$i]["label"], $allVariables) . "'
                                />
                            </div><label class='leform-description" . ($this->form_elements[$i]['description-style-align'] != "" ? " leform-ta-" . $this->form_elements[$i]['description-style-align'] : "") . "'>" . $properties["required-description-left"] . $this->replaceWithPredefinedValues($this->form_elements[$i]["description"], $allVariables) . $properties["required-description-right"] . $properties["tooltip-description"] . "</label></div></div>
                        ";
                        break;

                    case "select":
                        $options = "";
                        $default = "";
                        if ($this->form_elements[$i]["please-select-option"] == "on") $options .= "<option value=''>" . $this->form_elements[$i]["please-select-text"] . "</option>";
                        $this->form_elements[$i]["options"] = FormService::getExternalValuesAsArray($this->form_elements[$i]);
                        for ($j = 0; $j < sizeof($this->form_elements[$i]["options"]); $j++) {
                            $selected = "";
                            if (array_key_exists("default", $this->form_elements[$i]["options"][$j]) && $this->form_elements[$i]["options"][$j]["default"] == "on") {
                                $selected = " selected='selected'";
                                $default = $this->form_elements[$i]["options"][$j]["value"];
                            }
                            $options .= "<option value='" . $this->form_elements[$i]["options"][$j]["value"] . "'" . $selected . ">" . $this->form_elements[$i]["options"][$j]["label"] . "</option>";
                        }
                        if ($this->form_elements[$i]['input-style-size'] != "") $extra_class .= " leform-input-" . $this->form_elements[$i]['input-style-size'];
                        $html .= "<div  $bindedFieldsAttribute class='leform-element leform-element-" . $this->form_elements[$i]['id'] . ($properties["label-style-position"] != "" ? " leform-element-label-" . $properties["label-style-position"] : "") . ($this->form_elements[$i]['description-style-position'] != "" ? " leform-element-description-" . $this->form_elements[$i]['description-style-position'] : "") . "' data-type='" . $this->form_elements[$i]["type"] . "' data-deps='" . (array_key_exists($this->form_elements[$i]['id'], $this->form_dependencies) ? implode(',', $this->form_dependencies[$this->form_elements[$i]['id']]) : '') . "'" . ($this->form_elements[$i]['dynamic-default'] == 'on' ? " data-dynamic='" . $this->form_elements[$i]['dynamic-parameter'] . "'" : "") . " data-id='" . $this->form_elements[$i]['id'] . "'><div class='leform-column-label" . $column_label_class . "'><label class='leform-label" . ($this->form_elements[$i]['label-style-align'] != "" ? " leform-ta-" . $this->form_elements[$i]['label-style-align'] : "") . "'>" . $properties["required-label-left"] . $this->replaceWithPredefinedValues($this->form_elements[$i]["label"], $allVariables) . $properties["required-label-right"] . $properties["tooltip-label"] . "</label></div><div class='leform-column-input" . $column_input_class . "'><div class='leform-input" . $extra_class . "'" . $properties["tooltip-input"] . ">
                            <div class='absolute right-4'><i class='fas fa-chevron-down' style='font-size: 1.3em; color: #488fd8'></i></div>
                            <select name='leform-" . $this->form_elements[$i]['id'] . "' class='" . ($this->form_elements[$i]['input-style-align'] != "" ? "leform-ta-" . $this->form_elements[$i]['input-style-align'] . " " : "") . $this->form_elements[$i]["css-class"] . "' data-default='" . $default . "' autocomplete='" . $this->form_elements[$i]["autocomplete"] . "' aria-label='" . $this->replaceWithPredefinedValues($this->form_elements[$i]["label"], $allVariables) . "' data-input-name='" . $this->form_elements[$i]["name"] . "' onchange='leform_input_changed(this);' onclick='jQuery(this).closest(\".leform-input\").find(\".leform-element-error\").fadeOut(300, function(){jQuery(this).remove();});'>
                                " . $options . "
                            </select></div><label class='leform-description" . ($this->form_elements[$i]['description-style-align'] != "" ? " leform-ta-" . $this->form_elements[$i]['description-style-align'] : "") . "'>" . $properties["required-description-left"] . $this->replaceWithPredefinedValues($this->form_elements[$i]["description"], $allVariables) . $properties["required-description-right"] . $properties["tooltip-description"] . "</label></div></div>
                        ";
                        break;

                    case "checkbox":
                        $options = "";
                        $id = $this->leform->random_string(16);
                        $uids[] = $id;
                        $style .= ".leform-form-" . $this->id . " .leform-element-" . $this->form_elements[$i]['id'] . " div.leform-input{height:auto;line-height:1;}";
                        if ($this->form_options["checkbox-view"] === "inverted") {
                            $style .= "
                                .leform-form-" . $this->id . " .leform-element-" . $this->form_elements[$i]['id'] . " div.leform-input .leform-checkbox.leform-checkbox-inverted + label {
                                    background-color: " . $this->form_options["checkbox-radio-unchecked-color-color2"] . ";
                                    border-color: " . $this->form_options["checkbox-radio-unchecked-color-color1"] . ";
                                }

                                .leform-form-" . $this->id . " .leform-element-" . $this->form_elements[$i]['id'] . " div.leform-input .leform-checkbox.leform-checkbox-inverted:checked + label {
                                    background-color: " . $this->form_options["checkbox-radio-unchecked-color-color1"] . ";
                                    border-color: " . $this->form_options["checkbox-radio-unchecked-color-color1"] . ";
                                }

                                .leform-form-" . $this->id . " .leform-element-" . $this->form_elements[$i]['id'] . " div.leform-input .leform-checkbox.leform-checkbox-inverted:checked + label::after {
                                    color: white;
                                }
                            ";
                        }

                        $properties['checkbox-size'] = $this->form_options['checkbox-radio-style-size'];
                        if (empty($this->form_elements[$i]['checkbox-style-position'])) $properties['checkbox-position'] = $this->form_options['checkbox-radio-style-position'];
                        else $properties['checkbox-position'] = $this->form_elements[$i]['checkbox-style-position'];
                        if (empty($this->form_elements[$i]['checkbox-style-align'])) $properties['checkbox-align'] = $this->form_options['checkbox-radio-style-align'];
                        else $properties['checkbox-align'] = $this->form_elements[$i]['checkbox-style-align'];
                        if (empty($this->form_elements[$i]['checkbox-style-layout'])) $properties['checkbox-layout'] = $this->form_options['checkbox-radio-style-layout'];
                        else $properties['checkbox-layout'] = $this->form_elements[$i]['checkbox-style-layout'];
                        $extra_class .= " leform-cr-layout-" . $properties['checkbox-layout'] . " leform-cr-layout-" . $properties['checkbox-align'];

                        for ($j = 0; $j < sizeof($this->form_elements[$i]["options"]); $j++) {
                            $selected = "";
                            if (array_key_exists("default", $this->form_elements[$i]["options"][$j]) && $this->form_elements[$i]["options"][$j]["default"] == "on") $selected = " checked='checked'";
                            $option = "<div class='leform-cr-box'><input class='leform-checkbox leform-checkbox-" . $this->form_options["checkbox-view"] . " leform-checkbox-" . $properties["checkbox-size"] . "' type='checkbox' name='leform-" . $this->form_elements[$i]['id'] . "[]' id='" . "leform-checkbox-" . $id . "-" . $i . "-" . $j . "' value='" . $this->form_elements[$i]["options"][$j]["value"] . "'" . $selected . " data-default='" . (empty($selected) ? 'off' : 'on') . "' data-input-name='" . $this->form_elements[$i]["name"] . "' onchange='leform_input_changed(this);' /><label for='" . "leform-checkbox-" . $id . "-" . $i . "-" . $j . "' onclick='jQuery(this).closest(\".leform-input\").find(\".leform-element-error\").fadeOut(300, function(){jQuery(this).remove();});'></label></div>";
                            if ($properties['checkbox-position'] == "left") $option .= "<div class='leform-cr-label leform-ta-" . $properties['checkbox-align'] . "'><label for='" . "leform-checkbox-" . $id . "-" . $i . "-" . $j . "' onclick='jQuery(this).closest(\".leform-input\").find(\".leform-element-error\").fadeOut(300, function(){jQuery(this).remove();});'>" . $this->form_elements[$i]["options"][$j]["label"] . "</label></div>";
                            else $option = "<div class='leform-cr-label leform-ta-" . $properties['checkbox-align'] . "'><label for='" . "leform-checkbox-" . $id . "-" . $i . "-" . $j . "' onclick='jQuery(this).closest(\".leform-input\").find(\".leform-element-error\").fadeOut(300, function(){jQuery(this).remove();});'>" . $this->form_elements[$i]["options"][$j]["label"] . "</label></div>" . $option;
                            $options .= "<div class='leform-cr-container leform-cr-container-" . $properties["checkbox-size"] . " leform-cr-container-" . $properties["checkbox-position"] . "'>" . $option . "</div>";
                        }
                        $html .= "<div class='leform-element leform-element-" . $this->form_elements[$i]['id'] . ($properties["label-style-position"] != "" ? " leform-element-label-" . $properties["label-style-position"] : "") . ($this->form_elements[$i]['description-style-position'] != "" ? " leform-element-description-" . $this->form_elements[$i]['description-style-position'] : "") . "' data-type='" . $this->form_elements[$i]["type"] . "' data-deps='" . (array_key_exists($this->form_elements[$i]['id'], $this->form_dependencies) ? implode(',', $this->form_dependencies[$this->form_elements[$i]['id']]) : '') . "'" . ($this->form_elements[$i]['dynamic-default'] == 'on' ? " data-dynamic='" . $this->form_elements[$i]['dynamic-parameter'] . "'" : "") . " data-id='" . $this->form_elements[$i]['id'] . "'><div class='leform-column-label" . $column_label_class . "'><label class='leform-label" . ($this->form_elements[$i]['label-style-align'] != "" ? " leform-ta-" . $this->form_elements[$i]['label-style-align'] : "") . "'>" . $properties["required-label-left"] . $this->replaceWithPredefinedValues($this->form_elements[$i]["label"], $allVariables) . $properties["required-label-right"] . $properties["tooltip-label"] . "</label></div><div class='leform-column-input" . $column_input_class . "'><div class='leform-input" . $extra_class . "'" . $properties["tooltip-input"] . ">" . $options . "</div><label class='leform-description" . ($this->form_elements[$i]['description-style-align'] != "" ? " leform-ta-" . $this->form_elements[$i]['description-style-align'] : "") . "'>" . $properties["required-description-left"] . $this->replaceWithPredefinedValues($this->form_elements[$i]["description"], $allVariables) . $properties["required-description-right"] . $properties["tooltip-description"] . "</label></div></div>";
                        break;

                    case "matrix":
                        $options = "";
                        $id = $this->leform->random_string(16);
                        $uids[] = $id;
                        $style .= ".leform-form-" . $this->id . " .leform-element-" . $this->form_elements[$i]['id'] . " div.leform-input{height:auto;line-height:1;}";
                        if ($this->form_options["checkbox-view"] === "inverted") {
                            $style .= "
                                .leform-element-" . $this->form_elements[$i]['id'] . " div.leform-input .leform-checkbox-inverted + label {
                                    background-color: " . $this->form_options["checkbox-radio-unchecked-color-color2"] . ";
                                    border-color: " . $this->form_options["checkbox-radio-unchecked-color-color1"] . ";
                                }

                                .leform-element-" . $this->form_elements[$i]['id'] . " div.leform-input .leform-checkbox-inverted:checked + label {
                                    background-color: " . $this->form_options["checkbox-radio-unchecked-color-color1"] . ";
                                    border-color: " . $this->form_options["checkbox-radio-unchecked-color-color1"] . ";
                                }

                                .leform-element-" . $this->form_elements[$i]['id'] . " div.leform-input .leform-checkbox-inverted:checked + label::after {
                                    color: white;
                                }
                            ";
                        }

                        $properties['checkbox-size'] = $this->form_options['checkbox-radio-style-size'];
                        $properties['checkbox-position'] = $this->form_options['checkbox-radio-style-position'];
                        $properties['checkbox-align'] = $this->form_options['checkbox-radio-style-align'];
                        $properties['checkbox-layout'] = $this->form_options['checkbox-radio-style-layout'];

                        $extra_class .= " leform-cr-layout-" . $properties['checkbox-layout'] . " leform-cr-layout-" . $properties['checkbox-align'];

                        $topOptions = "";
                        foreach ($this->form_elements[$i]['top'] as $leftOption) {
                            $topOptions .= "
                                <div class='pb-3'>"
                                . $leftOption['label'] .
                                "</div>
                            ";
                        }
                        $topOptions = "
                            <div class='grid grid-cols-" .
                            (count($this->form_elements[$i]["top"]) + 2)
                            . " gap-2'>
                                <div class='col-span-2'></div>
                                $topOptions
                            </div>
                        ";

                        $isCheckbox = $this->form_elements[$i]['multi-select'] === 'on';

                        $checkboxOptions = "";
                        for ($j = 0; $j < sizeof($this->form_elements[$i]["left"]); $j++) {
                            $row = "";
                            foreach ($this->form_elements[$i]["top"] as $elementKey => $element) {
                                $classlist = "";
                                $value = $this->form_elements[$i]["left"][$j]["value"]
                                    . "--" . $element["value"];
                                $inputType = ($isCheckbox) ? 'checkbox' : 'radio';

                                if ($isCheckbox) {
                                    $classlist = implode(' ', [
                                        "leform-checkbox",
                                        "leform-checkbox-" . $this->form_options["checkbox-view"],
                                        "leform-checkbox-" . $properties["checkbox-size"],
                                    ]);
                                } else {
                                    $classlist = implode(' ', [
                                        "leform-radio",
                                        "leform-radio-" . $this->form_options["radio-view"],
                                        "leform-radio-" . $properties["checkbox-size"],
                                    ]);
                                }

                                $checkboxOption = "
                                    <div class='leform-cr-box'>
                                        <input
                                            class='$classlist'
                                            type='$inputType'
                                            name='leform-" . $this->form_elements[$i]['id'] . "[]'
                                            id='leform-checkbox-$id-$i-$j-$elementKey'
                                            value='$value'
                                            data-default='" . (empty($selected) ? 'off' : 'on') . "'
                                            onchange='leform_input_changed(this);'
                                            data-input-name='" . $this->form_elements[$i]["name"] . "'
                                        />
                                        <label
                                            for='leform-checkbox-$id-$i-$j-$elementKey'
                                            onclick='jQuery(this).closest(\".leform-input\").find(\".leform-element-error\").fadeOut(300, function(){jQuery(this).remove();});'
                                        ></label>
                                    </div>
                                ";
                                $row .= $checkboxOption;
                            }
                            $row = "
                                <form class='grid grid-cols-" .
                                (count($this->form_elements[$i]["top"]) + 2)
                                . " gap-2'>
                                    <div class='col-span-2'>"
                                . $this->form_elements[$i]['left'][$j]['label'] .
                                "</div>
                                    $row
                                </form>
                            ";

                            $checkboxOptions .= "
                                <div class='leform-cr-container leform-cr-container-"
                                . $properties["checkbox-size"] .
                                " leform-cr-container-"
                                . $properties["checkbox-position"] .
                                "'>
                                    $row
                                </div>
                            ";
                        }

                        $options = $topOptions . $checkboxOptions;

                        $html .= "
                            <div
                                class='leform-element leform-element-" . $this->form_elements[$i]['id'] . (
                            (array_key_exists('label-style-position', $properties)
                                && $properties["label-style-position"] != ""
                            )
                            ? " leform-element-label-" . $properties["label-style-position"]
                            : ""
                        ) . (
                            (array_key_exists('description-style-position', $properties)
                                && $this->form_elements[$i]['description-style-position'] != ""
                            )
                            ? " leform-element-description-" . $this->form_elements[$i]['description-style-position']
                            : ""
                        ) . "'
                                data-type='" . $this->form_elements[$i]["type"] . "'
                                data-deps='" . (array_key_exists($this->form_elements[$i]['id'], $this->form_dependencies) ? implode(',', $this->form_dependencies[$this->form_elements[$i]['id']]) : '') . "'
                                " . (
                            (array_key_exists('dynamic-default', $this->form_elements[$i])
                                && $this->form_elements[$i]['dynamic-default'] == 'on'
                            )
                            ? " data-dynamic='" . $this->form_elements[$i]['dynamic-parameter'] . "'"
                            : ""
                        ) . "
                                data-id='" . $this->form_elements[$i]['id'] . "'
                            >
                                <div class='leform-column-label" . $column_label_class . "'>
                                    <label
                                        class='leform-label" . (
                            (array_key_exists('label-style-align', $this->form_elements[$i])
                                && $this->form_elements[$i]['label-style-align'] != ""
                            )
                            ? " leform-ta-" . $this->form_elements[$i]['label-style-align']
                            : ""
                        ) . "'
                                    >
                                        " . $properties["required-label-left"]
                            . $this->replaceWithPredefinedValues($this->form_elements[$i]["label"], $allVariables)
                            . $properties["required-label-right"]
                            . $properties["tooltip-label"] . "
                                    </label>
                                </div>
                                <div class='leform-column-input" . $column_input_class . "'>
                                    <div class='leform-input inline-block" . $extra_class . "'" . $properties["tooltip-input"] . ">
                                        " . $options . "
                                    </div>
                                    <label
                                        class='leform-description" . (
                                (array_key_exists('description-style-align', $this->form_elements[$i])
                                    && $this->form_elements[$i]['description-style-align'] != ""
                                )
                                ? " leform-ta-" . $this->form_elements[$i]['description-style-align']
                                : ""
                            ) . "'
                                    >
                                        " . $properties["required-description-left"]
                            . (array_key_exists('description', $this->form_elements[$i])
                                ? $this->replaceWithPredefinedValues($this->form_elements[$i]["description"], $allVariables)
                                : ''
                            )
                            . $properties["required-description-right"]
                            . $properties["tooltip-description"] . "
                                    </label>
                                </div>
                            </div>
                        ";
                        break;

                    case "imageselect":
                        $options = "";
                        $id = $this->leform->random_string(16);
                        $uids[] = $id;
                        $style .= ".leform-form-" . $this->id . " .leform-element-" . $this->form_elements[$i]['id'] . " div.leform-input{height:auto;line-height:1;}";
                        $properties["image-width"] = intval($this->form_elements[$i]['image-style-width']);
                        if ($properties["image-width"] <= 0 || $properties["image-width"] >= 600) $properties["image-width"] = 120;
                        $properties["image-height"] = intval($this->form_elements[$i]['image-style-height']);
                        if ($properties["image-height"] <= 0 || $properties["image-height"] >= 600) $properties["image-height"] = 120;

                        $properties["label-height"] = intval($this->form_elements[$i]['label-height']);
                        if ($properties["label-height"] <= 0 || $properties["label-height"] >= 200 || $this->form_elements[$i]['label-enable'] != 'on') $properties["label-height"] = 0;

                        if ($this->form_options['imageselect-selected-scale'] == 'on') {
                            $scale = min(floatval(($properties["image-width"] + 8) / $properties["image-width"]), floatval(($properties["image-height"] + 8) / $properties["image-height"]));
                            $style .= ".leform-form-" . $this->id . " .leform-element-" . $this->form_elements[$i]['id'] . " div.leform-input .leform-imageselect:checked+label {transform: scale(" . number_format($scale, 2, '.', '') . ");}";
                        }
                        $extra_class .= ' leform-ta-' . $this->form_options['imageselect-style-align'] . ' leform-imageselect-' . $this->form_options['imageselect-style-effect'];

                        $imageHeight = 0;
                        $labelHeight = 0;
                        if ($properties["image-height"]) {
                            $imageHeight = $properties["image-height"];
                        }
                        if ($properties["label-height"]) {
                            $labelHeight = $properties["label-height"];
                        }
                        $totalHeight = $labelHeight + $imageHeight;

                        $style .= ".leform-form-" . $this->id . " .leform-element-" . $this->form_elements[$i]['id'] . " div.leform-input .leform-imageselect+label { width:" . $properties["image-width"] . "px; height:" . $totalHeight . "px; } ";
                        $style .= ".leform-form-" . $this->id . " .leform-element-" . $this->form_elements[$i]['id'] . " div.leform-input .leform-imageselect+label  span.leform-imageselect-image {height:" . $properties["image-height"] . "px;background-size:" . $this->form_elements[$i]['image-style-size'] . ";}";

                        for ($j = 0; $j < sizeof($this->form_elements[$i]["options"]); $j++) {
                            $selected = "";
                            if (array_key_exists("default", $this->form_elements[$i]["options"][$j]) && $this->form_elements[$i]["options"][$j]["default"] == "on") $selected = " checked='checked'";
                            $properties['image-label'] = "";
                            if ($properties["label-height"] > 0) {
                                $properties['image-label'] = "<span class='leform-imageselect-label'>" . $this->form_elements[$i]["options"][$j]["label"] . "</span>";
                            }
                            $options .= "<input class='leform-imageselect' type='" . $this->form_elements[$i]['mode'] . "' name='leform-" . $this->form_elements[$i]['id'] . ($this->form_elements[$i]['mode'] == 'checkbox' ? "[]" : "") . "' id='" . "leform-imageselect-" . $id . "-" . $i . "-" . $j . "' value='" . $this->form_elements[$i]["options"][$j]["value"] . "'" . $selected . " data-default='" . (empty($selected) ? 'off' : 'on') . "' data-input-name='" . $this->form_elements[$i]["name"] . "' onchange='leform_input_changed(this);' /><label for='" . "leform-imageselect-" . $id . "-" . $i . "-" . $j . "' onclick='jQuery(this).closest(\".leform-input\").find(\".leform-element-error\").fadeOut(300, function(){jQuery(this).remove();});'><span class='leform-imageselect-image' style='background-image: url(" . $this->form_elements[$i]["options"][$j]["image"] . ");'></span>" . $properties['image-label'] . "</label>";
                        }
                        if ($this->form_elements[$i]['mode'] == 'radio') $options = '<form>' . $options . '</form>';

                        $html .= "<div class='leform-element leform-element-" . $this->form_elements[$i]['id'] . ($properties["label-style-position"] != "" ? " leform-element-label-" . $properties["label-style-position"] : "") . ($this->form_elements[$i]['description-style-position'] != "" ? " leform-element-description-" . $this->form_elements[$i]['description-style-position'] : "") . "' data-type='" . $this->form_elements[$i]["type"] . "' data-deps='" . (array_key_exists($this->form_elements[$i]['id'], $this->form_dependencies) ? implode(',', $this->form_dependencies[$this->form_elements[$i]['id']]) : '') . "'" . ($this->form_elements[$i]['dynamic-default'] == 'on' ? " data-dynamic='" . $this->form_elements[$i]['dynamic-parameter'] . "'" : "") . " data-id='" . $this->form_elements[$i]['id'] . "'><div class='leform-column-label" . $column_label_class . "'><label class='leform-label" . ($this->form_elements[$i]['label-style-align'] != "" ? " leform-ta-" . $this->form_elements[$i]['label-style-align'] : "") . "'>" . $properties["required-label-left"] . $this->replaceWithPredefinedValues($this->form_elements[$i]["label"], $allVariables) . $properties["required-label-right"] . $properties["tooltip-label"] . "</label></div><div class='leform-column-input" . $column_input_class . "'><div class='leform-input" . $extra_class . "'" . $properties["tooltip-input"] . ">" . $options . "</div><label class='leform-description" . ($this->form_elements[$i]['description-style-align'] != "" ? " leform-ta-" . $this->form_elements[$i]['description-style-align'] : "") . "'>" . $properties["required-description-left"] . $this->replaceWithPredefinedValues($this->form_elements[$i]["description"], $allVariables) . $properties["required-description-right"] . $properties["tooltip-description"] . "</label></div></div>";
                        break;

                    case "multiselect":
                        $options = "";
                        $id = $this->leform->random_string(16);
                        $uids[] = $id;
                        $style .= ".leform-form-" . $this->id . " .leform-element-" . $this->form_elements[$i]['id'] . " div.leform-input{height:auto;line-height:1;}";
                        if (!empty($this->form_elements[$i]['multiselect-style-height'])) $style .= ".leform-form-" . $this->id . " .leform-element-" . $this->form_elements[$i]['id'] . " div.leform-multiselect {height:" . intval($this->form_elements[$i]['multiselect-style-height']) . "px;}";
                        if (!empty($this->form_elements[$i]['multiselect-style-align'])) $properties['align'] = $this->form_elements[$i]['multiselect-style-align'];
                        else if (!empty($this->form_options['multiselect-style-align'])) $properties['align'] = $this->form_options['multiselect-style-align'];
                        else $properties['align'] = 'left';
                        $options = "";
                        for ($j = 0; $j < sizeof($this->form_elements[$i]["options"]); $j++) {
                            $selected = "";
                            if (array_key_exists("default", $this->form_elements[$i]["options"][$j]) && $this->form_elements[$i]["options"][$j]["default"] == "on") $selected = " checked='checked'";
                            $options .= "<input type='checkbox' name='leform-" . $this->form_elements[$i]['id'] . "[]' id='" . "leform-checkbox-" . $id . "-" . $i . "-" . $j . "' value='" . $this->form_elements[$i]["options"][$j]["value"] . "'" . $selected . " data-default='" . (empty($selected) ? 'off' : 'on') . "' onchange='leform_multiselect_changed(this);' /><label for='" . "leform-checkbox-" . $id . "-" . $i . "-" . $j . "' onclick='jQuery(this).closest(\".leform-input\").find(\".leform-element-error\").fadeOut(300, function(){jQuery(this).remove();});'>" . $this->form_elements[$i]["options"][$j]["label"] . "</label>";
                        }
                        $html .= "<div class='leform-element leform-element-" . $this->form_elements[$i]['id'] . ($properties["label-style-position"] != "" ? " leform-element-label-" . $properties["label-style-position"] : "") . ($this->form_elements[$i]['description-style-position'] != "" ? " leform-element-description-" . $this->form_elements[$i]['description-style-position'] : "") . "' data-type='" . $this->form_elements[$i]["type"] . "' data-deps='" . (array_key_exists($this->form_elements[$i]['id'], $this->form_dependencies) ? implode(',', $this->form_dependencies[$this->form_elements[$i]['id']]) : '') . "'" . ($this->form_elements[$i]['dynamic-default'] == 'on' ? " data-dynamic='" . $this->form_elements[$i]['dynamic-parameter'] . "'" : "") . " data-id='" . $this->form_elements[$i]['id'] . "'><div class='leform-column-label" . $column_label_class . "'><label class='leform-label" . ($this->form_elements[$i]['label-style-align'] != "" ? " leform-ta-" . $this->form_elements[$i]['label-style-align'] : "") . "'>" . $properties["required-label-left"] . $this->replaceWithPredefinedValues($this->form_elements[$i]["label"], $allVariables) . $properties["required-label-right"] . $properties["tooltip-label"] . "</label></div><div class='leform-column-input" . $column_input_class . "'><div class='leform-input'" . $properties["tooltip-input"] . "><div class='leform-multiselect leform-ta-" . $properties["align"] . "' data-max-allowed='" . (intval($this->form_elements[$i]['max-allowed']) > 0 ? intval($this->form_elements[$i]['max-allowed']) : '0') . "'>" . $options . "</div></div><label class='leform-description" . ($this->form_elements[$i]['description-style-align'] != "" ? " leform-ta-" . $this->form_elements[$i]['description-style-align'] : "") . "'>" . $properties["required-description-left"] . $this->replaceWithPredefinedValues($this->form_elements[$i]["description"], $allVariables) . $properties["required-description-right"] . $properties["tooltip-description"] . "</label></div></div>";
                        break;

                    case "radio":
                        $options = "";
                        $id = $this->leform->random_string(16);
                        $uids[] = $id;
                        $style .= ".leform-form-" . $this->id . " .leform-element-" . $this->form_elements[$i]['id'] . " div.leform-input{height:auto;line-height:1;}";
                        $properties['radio-size'] = $this->form_options['checkbox-radio-style-size'];
                        if (empty($this->form_elements[$i]['radio-style-position'])) $properties['radio-position'] = $this->form_options['checkbox-radio-style-position'];
                        else $properties['radio-position'] = $this->form_elements[$i]['radio-style-position'];
                        if (empty($this->form_elements[$i]['radio-style-align'])) $properties['radio-align'] = $this->form_options['checkbox-radio-style-align'];
                        else $properties['radio-align'] = $this->form_elements[$i]['radio-style-align'];
                        if (empty($this->form_elements[$i]['radio-style-layout'])) $properties['radio-layout'] = $this->form_options['checkbox-radio-style-layout'];
                        else $properties['radio-layout'] = $this->form_elements[$i]['radio-style-layout'];
                        $extra_class .= " leform-cr-layout-" . $properties['radio-layout'] . " leform-cr-layout-" . $properties['radio-align'];

                        for ($j = 0; $j < sizeof($this->form_elements[$i]["options"]); $j++) {
                            $selected = "";
                            if (array_key_exists("default", $this->form_elements[$i]["options"][$j]) && $this->form_elements[$i]["options"][$j]["default"] == "on") $selected = " checked='checked'";
                            $option = "<div class='leform-cr-box'><input class='leform-radio leform-radio-" . $this->form_options["radio-view"] . " leform-radio-" . $properties["radio-size"] . "' type='radio' name='leform-" . $this->form_elements[$i]['id'] . "' id='" . "leform-radio-" . $id . "-" . $i . "-" . $j . "' value='" . $this->form_elements[$i]["options"][$j]["value"] . "'" . $selected . " data-default='" . (empty($selected) ? 'off' : 'on') . "' data-input-name='" . $this->form_elements[$i]["name"] . "' onchange='leform_input_changed(this);' /><label for='" . "leform-radio-" . $id . "-" . $i . "-" . $j . "' onclick='jQuery(this).closest(\".leform-input\").find(\".leform-element-error\").fadeOut(300, function(){jQuery(this).remove();});'></label></div>";
                            if ($properties['radio-position'] == "left") $option .= "<div class='leform-cr-label leform-ta-" . $properties['radio-align'] . "'><label for='" . "leform-radio-" . $id . "-" . $i . "-" . $j . "' onclick='jQuery(this).closest(\".leform-input\").find(\".leform-element-error\").fadeOut(300, function(){jQuery(this).remove();});'>" . $this->form_elements[$i]["options"][$j]["label"] . "</label></div>";
                            else $option = "<div class='leform-cr-label leform-ta-" . $properties['radio-align'] . "'><label for='" . "leform-radio-" . $id . "-" . $i . "-" . $j . "' onclick='jQuery(this).closest(\".leform-input\").find(\".leform-element-error\").fadeOut(300, function(){jQuery(this).remove();});'>" . $this->form_elements[$i]["options"][$j]["label"] . "</label></div>" . $option;
                            $options .= "<div class='leform-cr-container leform-cr-container-" . $properties["radio-size"] . " leform-cr-container-" . $properties["radio-position"] . "'>" . $option . "</div>";
                        }
                        $html .= "<div class='leform-element leform-element-" . $this->form_elements[$i]['id'] . ($properties["label-style-position"] != "" ? " leform-element-label-" . $properties["label-style-position"] : "") . ($this->form_elements[$i]['description-style-position'] != "" ? " leform-element-description-" . $this->form_elements[$i]['description-style-position'] : "") . "' data-type='" . $this->form_elements[$i]["type"] . "' data-deps='" . (array_key_exists($this->form_elements[$i]['id'], $this->form_dependencies) ? implode(',', $this->form_dependencies[$this->form_elements[$i]['id']]) : '') . "'" . ($this->form_elements[$i]['dynamic-default'] == 'on' ? " data-dynamic='" . $this->form_elements[$i]['dynamic-parameter'] . "'" : "") . " data-id='" . $this->form_elements[$i]['id'] . "'><div class='leform-column-label" . $column_label_class . "'><label class='leform-label" . ($this->form_elements[$i]['label-style-align'] != "" ? " leform-ta-" . $this->form_elements[$i]['label-style-align'] : "") . "'>" . $properties["required-label-left"] . $this->replaceWithPredefinedValues($this->form_elements[$i]["label"], $allVariables) . $properties["required-label-right"] . $properties["tooltip-label"] . "</label></div><div class='leform-column-input" . $column_input_class . "'><div class='leform-input" . $extra_class . "'" . $properties["tooltip-input"] . "><form>" . $options . "</form></div><label class='leform-description" . ($this->form_elements[$i]['description-style-align'] != "" ? " leform-ta-" . $this->form_elements[$i]['description-style-align'] : "") . "'>" . $properties["required-description-left"] . $this->replaceWithPredefinedValues($this->form_elements[$i]["description"], $allVariables) . $properties["required-description-right"] . $properties["tooltip-description"] . "</label></div></div>";
                        break;

                    case "tile":
                        $options = "";
                        $id = $this->leform->random_string(16);
                        $uids[] = $id;
                        $style .= ".leform-form-" . $this->id . " .leform-element-" . $this->form_elements[$i]['id'] . " div.leform-input{height:auto;line-height:1;}";
                        if (array_key_exists("tile-style-size", $this->form_elements[$i]) && $this->form_elements[$i]['tile-style-size'] != "") $properties['size'] = $this->form_elements[$i]['tile-style-size'];
                        else $properties['size'] = $this->form_options['tile-style-size'];
                        if (array_key_exists("tile-style-width", $this->form_elements[$i]) && $this->form_elements[$i]['tile-style-width'] != "") $properties['width'] = $this->form_elements[$i]['tile-style-width'];
                        else $properties['width'] = $this->form_options['tile-style-width'];
                        if (array_key_exists("tile-style-position", $this->form_elements[$i]) && $this->form_elements[$i]['tile-style-position'] != "") $properties['position'] = $this->form_elements[$i]['tile-style-position'];
                        else $properties['position'] = $this->form_options['tile-style-position'];
                        if (array_key_exists("tile-style-layout", $this->form_elements[$i]) && $this->form_elements[$i]['tile-style-layout'] != "") $properties['layout'] = $this->form_elements[$i]['tile-style-layout'];
                        else $properties['layout'] = $this->form_options['tile-style-layout'];
                        $extra_class .= " leform-tile-layout-" . $properties['layout'] . " leform-tile-layout-" . $properties['position'] . " leform-tile-transform-" . $this->form_options['tile-selected-transform'];
                        for ($j = 0; $j < sizeof($this->form_elements[$i]["options"]); $j++) {
                            $selected = "";
                            if (array_key_exists("default", $this->form_elements[$i]["options"][$j]) && $this->form_elements[$i]["options"][$j]["default"] == "on") $selected = " checked='checked'";
                            $option = "<div class='leform-tile-box'><input class='leform-tile leform-tile-" . $properties["size"] . "' type='" . $this->form_elements[$i]['mode'] . "' name='leform-" . $this->form_elements[$i]['id'] . ($this->form_elements[$i]['mode'] == 'checkbox' ? "[]" : "") . "' id='" . "leform-tile-" . $id . "-" . $i . "-" . $j . "' value='" . $this->form_elements[$i]["options"][$j]["value"] . "'" . $selected . " data-default='" . (empty($selected) ? 'off' : 'on') . "' onchange='leform_input_changed(this);' data-input-name='" . $this->form_elements[$i]["name"] . "' /><label for='" . "leform-tile-" . $id . "-" . $i . "-" . $j . "' onclick='jQuery(this).closest(\".leform-input\").find(\".leform-element-error\").fadeOut(300, function(){jQuery(this).remove();});'>" . $this->form_elements[$i]["options"][$j]["label"] . "</label></div>";
                            $options .= "<div class='leform-tile-container leform-tile-" . $properties["width"] . "'>" . $option . "</div>";
                        }
                        if ($this->form_elements[$i]['mode'] == 'radio') $options = '<form>' . $options . '</form>';

                        $html .= "<div class='leform-element leform-element-" . $this->form_elements[$i]['id'] . ($properties["label-style-position"] != "" ? " leform-element-label-" . $properties["label-style-position"] : "") . ($this->form_elements[$i]['description-style-position'] != "" ? " leform-element-description-" . $this->form_elements[$i]['description-style-position'] : "") . "' data-type='" . $this->form_elements[$i]["type"] . "' data-deps='" . (array_key_exists($this->form_elements[$i]['id'], $this->form_dependencies) ? implode(',', $this->form_dependencies[$this->form_elements[$i]['id']]) : '') . "'" . ($this->form_elements[$i]['dynamic-default'] == 'on' ? " data-dynamic='" . $this->form_elements[$i]['dynamic-parameter'] . "'" : "") . " data-id='" . $this->form_elements[$i]['id'] . "'><div class='leform-column-label" . $column_label_class . "'><label class='leform-label" . ($this->form_elements[$i]['label-style-align'] != "" ? " leform-ta-" . $this->form_elements[$i]['label-style-align'] : "") . "'>" . $properties["required-label-left"] . $this->replaceWithPredefinedValues($this->form_elements[$i]["label"], $allVariables) . $properties["required-label-right"] . $properties["tooltip-label"] . "</label></div><div class='leform-column-input" . $column_input_class . "'><div class='leform-input" . $extra_class . "'" . $properties["tooltip-input"] . "><form>" . $options . "</form></div><label class='leform-description" . ($this->form_elements[$i]['description-style-align'] != "" ? " leform-ta-" . $this->form_elements[$i]['description-style-align'] : "") . "'>" . $properties["required-description-left"] . $this->replaceWithPredefinedValues($this->form_elements[$i]["description"], $allVariables) . $properties["required-description-right"] . $properties["tooltip-description"] . "</label></div></div>";
                        break;

                    case "star-rating":
                        $options = "";
                        $id = $this->leform->random_string(16);
                        $uids[] = $id;
                        $style .= ".leform-form-" . $this->id . " .leform-element-" . $this->form_elements[$i]['id'] . " div.leform-input{height:auto;line-height:1;}";

                        if (
                            $this->form_options["star-rating-color"]
                            && $this->form_elements[$i]["overwrite-global-theme-colour"] !== "on"
                        ) {
                            $a = null;
                            if ($this->form_options["filled-star-rating-mode"] === "on") {
                                $style .= ".leform-star-rating > label {
                                    color: " . $this->form_elements[$i]['star-style-color-unrated'] . " !important;
                                }";
                                $a = $this->form_elements[$i]['star-style-color-unrated'];
                            } else {
                                $style .= ".leform-star-rating > label {
                                    color: " . $this->form_options['star-rating-color'] . " !important;
                                }";
                                $a = $this->form_options['star-rating-color'];
                            }
                            $style .= "
                                .leform-star-rating>input:checked~label,
                                .leform-star-rating:not(:checked)>label:hover,
                                .leform-star-rating:not(:checked)>label:hover~label {
                                    color: " . $this->form_options['star-rating-color'] . " !important;
                                }
                            ";
                        } else {
                            if (!empty($this->form_elements[$i]['star-style-color-unrated'])) {
                                $style .= ".leform-form-" . $this->id . " .leform-element-" . $this->form_elements[$i]['id'] . " .leform-star-rating>label{color:" . $this->form_elements[$i]['star-style-color-unrated'] . " !important;}";
                            }
                            if (!empty($this->form_elements[$i]['star-style-color-rated'])) {
                                $style .= ".leform-form-" . $this->id . " .leform-element-" . $this->form_elements[$i]['id'] . " .leform-star-rating>input:checked~label, .leform-form-" . $this->id . " .leform-element-" . $this->form_elements[$i]['id'] . " .leform-star-rating:not(:checked)>label:hover, .leform-form-" . $this->id . " .leform-element-" . $this->form_elements[$i]['id'] . " .leform-star-rating:not(:checked)>label:hover~label{color:" . $this->form_elements[$i]['star-style-color-rated'] . " !important;}";
                            }
                        }

                        $options = "";

                        $starRatingDefaultValue = 0;
                        if ($this->form_elements[$i]['dynamic-default'] == "on") {
                            $replacedDynamicValue = $this->replaceWithPredefinedValues(
                                $this->form_elements[$i]["dynamic-parameter"],
                                $allVariables
                            );
                            if ($replacedDynamicValue) {
                                $starRatingDefaultValue = $replacedDynamicValue;
                            } else {
                                if ($this->form_elements[$i]['default']) {
                                    $starRatingDefaultValue = $this->form_elements[$i]['default'];
                                }
                            }
                        } else {
                            if ($this->form_elements[$i]['default']) {
                                $starRatingDefaultValue = $this->form_elements[$i]['default'];
                            }
                        }


                        for ($j = $this->form_elements[$i]['total-stars']; $j > 0; $j--) {
                            $options .= "<input type='radio' name='leform-" . $this->form_elements[$i]['id'] . "' id='" . "leform-radio-" . $id . "-" . $i . "-" . $j . "' value='" . $j . "'" .
                                ($starRatingDefaultValue == $j ? " checked='checked'" : "")
                                . " data-default='" .
                                ($starRatingDefaultValue == $j ? 'on' : 'off')
                                . "' onchange='leform_input_changed(this);' data-input-name='" . $this->form_elements[$i]["name"] . "' /><label for='" . "leform-radio-" . $id . "-" . $i . "-" . $j . "' onclick='jQuery(this).closest(\".leform-input\").find(\".leform-element-error\").fadeOut(300, function(){jQuery(this).remove();});'></label>";
                        }
                        $extra_class = "";
                        if (!empty($this->form_elements[$i]['star-style-size'])) {
                            $extra_class .= " leform-star-rating-" . $this->form_elements[$i]['star-style-size'];
                        }
                        if ($this->form_options["filled-star-rating-mode"] === "on") {
                            $extra_class .= " filled";
                        }

                        $html .= "<div class='leform-element leform-element-" . $this->form_elements[$i]['id'] . ($properties["label-style-position"] != "" ? " leform-element-label-" . $properties["label-style-position"] : "") . ($this->form_elements[$i]['description-style-position'] != "" ? " leform-element-description-" . $this->form_elements[$i]['description-style-position'] : "") . "' data-type='" . $this->form_elements[$i]["type"] . "' data-deps='" . (array_key_exists($this->form_elements[$i]['id'], $this->form_dependencies) ? implode(',', $this->form_dependencies[$this->form_elements[$i]['id']]) : '') . "'" . ($this->form_elements[$i]['dynamic-default'] == 'on' ? " data-dynamic='" .
                            $this->replaceWithPredefinedValues(
                                $this->form_elements[$i]["dynamic-parameter"],
                                $allVariables
                            )
                            . "'" : "") . " data-id='" . $this->form_elements[$i]['id'] . "'><div class='leform-column-label" . $column_label_class . "'><label class='leform-label" . ($this->form_elements[$i]['label-style-align'] != "" ? " leform-ta-" . $this->form_elements[$i]['label-style-align'] : "") . "'>" . $properties["required-label-left"] . $this->replaceWithPredefinedValues($this->form_elements[$i]["label"], $allVariables) . $properties["required-label-right"] . $properties["tooltip-label"] . "</label></div><div class='leform-column-input" . $column_input_class . "'><div class='leform-input'" . $properties["tooltip-input"] . "><form class='leform-ta-" . $this->form_elements[$i]['star-style-position'] . "'><fieldset class='leform-star-rating" . $extra_class . "'>" . $options . "</fieldset></form></div><label class='leform-description" . ($this->form_elements[$i]['description-style-align'] != "" ? " leform-ta-" . $this->form_elements[$i]['description-style-align'] : "") . "'>" . $properties["required-description-left"] . $this->replaceWithPredefinedValues($this->form_elements[$i]["description"], $allVariables) . $properties["required-description-right"] . $properties["tooltip-description"] . "</label></div></div>";
                        break;

                    case "html":
                        if ($this->form_options["html-headings-color"]) {
                            $style .= "
                                .leform-element-" . $this->form_elements[$i]["id"] . " h1,
                                .leform-element-" . $this->form_elements[$i]["id"] . " h2,
                                .leform-element-" . $this->form_elements[$i]["id"] . " h3,
                                .leform-element-" . $this->form_elements[$i]["id"] . " h4,
                                .leform-element-" . $this->form_elements[$i]["id"] . " h5,
                                .leform-element-" . $this->form_elements[$i]["id"] . " h6 {
                                    color: " . $this->form_options["html-headings-color"] . ";
                                }
                            ";
                        }

                        if ($this->form_options["html-paragraph-color"]) {
                            $style .= "
                                .leform-element-" . $this->form_elements[$i]["id"] . " p {
                                    color: " . $this->form_options["html-paragraph-color"] . ";
                                }
                            ";
                        }

                        if ($this->form_options["html-hr-color"]) {
                            $style .= "
                                .leform-element-" . $this->form_elements[$i]["id"] . " hr {
                                    border-color: " . $this->form_options["html-hr-color"] . ";
                                }
                            ";
                        }

                        if ($this->form_options["html-hr-height"]) {
                            $style .= "
                                .leform-element-" . $this->form_elements[$i]["id"] . " hr {
                                    border-top-width: " . $this->form_options["html-hr-height"] . "px;
                                }
                            ";
                        }

                        /*
                            $expressionMatches = [];
                            preg_match_all(
                                '/{{(\d+)|Expression}}/',
                                $this->form_elements[$i]["content"],
                                $expressionMatches
                            );
                            for ($matchIndex = 0; $matchIndex < count($expressionMatches[0]); $matchIndex++) {
                                if (
                                    !empty($expressionMatches[0][$matchIndex])
                                    && !empty($expressionMatches[1][$matchIndex])
                                ) {
                                    print_r($expressionMatches[1][$matchIndex]);
                                    echo ",";
                                    $a = "
                                        <span
                                            class='leform-var leform-var-". $expressionMatches[1][$matchIndex] ."'
                                            data-id='". $expressionMatches[1][$matchIndex] ."'
                                        ></span>
                                    ";
                                }
                            }
                            die;
                         */

                        $content = $this->replaceWithPredefinedValues(
                            $this->form_elements[$i]["content"],
                            $allVariables
                        );
                        $replacement = "
                            <span
                                class='leform-var leform-var-$1'
                                data-id='$1'
                            ></span>
                        ";

                        $content = preg_replace(
                            "/{{(\d+)(|.+?)}}/",
                            $replacement,
                            $content
                        );
                        $htmlExternal = '';
                        if (
                            isset($this->form_elements[$i]['external-datasource']) &&
                            isset($this->form_elements[$i]['external-datasource-url']) &&
                            $this->form_elements[$i]['external-datasource'] === 'on'
                        ) {
                            // $content = FormService::getExternalValuesAsString(
                            //   $this->form_elements[$i],
                            //   $content,
                            //   []
                            // );

                            $url = preg_replace("/{{(\d+).+?}}/", '{{$1}}', $this->form_elements[$i]['external-datasource-url']);
                            $path = isset($this->form_elements[$i]['external-datasource-path']) ? $this->form_elements[$i]['external-datasource-path'] : '';

                            $htmlExternal = "
                                <span class='html-external-datasource' data-url='$url' data-path='$path'>
                                    <input type='hidden' class='html-external-datasource-transformed' data-path='$path' value=''/>
                                </span>
                            ";
                        }
                        $html .= "
                            <div class='leform-element-html-container'>
                                <div
                                    class='leform-element leform-element-" . $this->form_elements[$i]['id'] . " leform-element-html'
                                    data-type='" . $this->form_elements[$i]["type"] . "'
                                >"
                            . $content .
                            "<div class='leform-element-cover'></div>
                                </div>
                                $htmlExternal
                            </div>
                        ";
                        break;

                    case "columns": {
                            $hasDynamicValues = (
                                (array_key_exists("has-dynamic-values", $this->form_elements[$i])
                                    && $this->form_elements[$i]["has-dynamic-values"] === "on"
                                )
                                ? true
                                : false
                            );
                            $dynamicValueName = (
                                ($hasDynamicValues
                                    && array_key_exists("dynamic-value", $this->form_elements[$i])
                                    && $this->form_elements[$i]["dynamic-value"] !== ""
                                )
                                ? $this->form_elements[$i]["dynamic-value"]
                                : null
                            );
                            $dynamicValueIndex = (
                                ($hasDynamicValues
                                    && array_key_exists("dynamic-value-index", $this->form_elements[$i])
                                    && $this->form_elements[$i]["dynamic-value-index"] !== ""
                                )
                                ? (intval($this->form_elements[$i]["dynamic-value-index"]) - 1)
                                : null
                            );

                            $dynamicValues = null;
                            if ($hasDynamicValues) {
                                if (
                                    $dynamicValueName !== null
                                    && is_array($allVariables)
                                    && $dynamicValueIndex !== null
                                    && array_key_exists($dynamicValueName, $allVariables)
                                    && is_array($allVariables[$dynamicValueName])
                                    && array_key_exists($dynamicValueIndex, $allVariables[$dynamicValueName])
                                ) {
                                    $dynamicValues = $allVariables[$dynamicValueName][(int)$dynamicValueIndex];
                                } else {
                                    break;
                                }
                            } else {
                                $dynamicValues = $allVariables;
                            }

                            $options = "";
                            for ($j = 0; $j < $this->form_elements[$i]['_cols']; $j++) {
                                $properties = $this->_build_children(
                                    $this->form_elements[$i]['id'],
                                    $j,
                                    $dynamicValues
                                );
                                $style .= $properties["style"];
                                $uids = array_merge($uids, $properties["uids"]);
                                $options .= "<div class='leform-col leform-col-" . $this->form_elements[$i]["widths-" . $j] . "'><div class='leform-elements' _data-parent='" . $this->form_elements[$i]['id'] . "' _data-parent-col='" . $j . "'>" . $properties["html"] . "</div></div>";
                            }
                            $html .= "<div class='leform-row leform-element leform-element-" . $this->form_elements[$i]['id'] . " " . $this->form_elements[$i]["css-class"] . "' data-type='" . $this->form_elements[$i]["type"] . "'>" . $options . "</div>";
                            break;
                        }

                    case "repeater-input":
                        $fields = $this->form_elements[$i]["fields"];

                        $hasStarRating = false;
                        foreach ($fields as $field) {
                            if ($field["type"] === "star-rating") {
                                $hasStarRating = true;
                                break;
                            }
                        }

                        $style .= "
                            .leform-element-" . $this->form_elements[$i]["id"] . " thead td {
                                font-weight: bold;
                            }

                            .leform-element-" . $this->form_elements[$i]["id"] . " .add-row {
                                background-color: #FFFFFF;
                                border-color: " . ($this->form_options["html-headings-color"]
                            ? $this->form_options["html-headings-color"]
                            : $this->form_options["input-text-style-color"]
                        ) . ";
                            }

                            .leform-element-" . $this->form_elements[$i]["id"] . " .add-row:hover {
                                background-color: " . ($this->form_options["html-headings-color"]
                            ? $this->form_options["html-headings-color"]
                            : $this->form_options["input-text-style-color"]
                        ) . ";
                            }

                            .leform-element-" . $this->form_elements[$i]["id"] . " .add-row span,
                            .leform-element-" . $this->form_elements[$i]["id"] . " .add-row i
                            {
                                color: " . ($this->form_options["html-headings-color"]
                            ? $this->form_options["html-headings-color"]
                            : $this->form_options["input-text-style-color"]
                        ) . ";
                            }

                            .leform-element-" . $this->form_elements[$i]["id"] . " .add-row:hover span,
                            .leform-element-" . $this->form_elements[$i]["id"] . " .add-row:hover i
                            {
                                color: #FFFFFF;
                            }

                            .leform-element-" . $this->form_elements[$i]["id"] . " .add-row span {
                                font-weight: bold;
                            }

                            .leform-element-" . $this->form_elements[$i]["id"] . " .add-row i {
                                font-size: 16px;
                            }
                        ";

                        if (
                            $hasStarRating
                            && $this->form_options["star-rating-color"]
                        ) {
                            $a = null;
                            if ($this->form_options["filled-star-rating-mode"] === "on") {
                                /*
                                $style .= ".leform-star-rating > label {
                                    color: ". $this->form_elements[$i]['star-style-color-unrated'] ." !important;
                                }";
                                $a = $this->form_elements[$i]['star-style-color-unrated'];
                                 */
                            } else {
                                $style .= ".leform-star-rating > label {
                                    color: " . $this->form_options['star-rating-color'] . " !important;
                                }";
                                $a = $this->form_options['star-rating-color'];
                            }
                            $style .= "
                                .leform-star-rating>input:checked~label,
                                .leform-star-rating:not(:checked)>label:hover,
                                .leform-star-rating:not(:checked)>label:hover~label {
                                    color: " . $this->form_options['star-rating-color'] . " !important;
                                }
                            ";
                        }

                        $tableHead = "";
                        foreach ($fields as $fieldIndex => $field) {
                            $tableHead .= "
                                <td
                                    class='" . ($this->form_elements[$i]["has-borders"] === "on" ? "border-2" : ""
                            ) . " pb-1 pt-0 " . (
                                ($fieldIndex === 0)
                                ? "pr-2 pl-0"
                                : (($fieldIndex === (count($fields) - 1))
                                    ? "pl-2 pr-0"
                                    : "px-2")
                            ) . "'
                                    style='border-color: #d5d9dd;'
                                >
                                    {$field["name"]}
                                </td>
                            ";
                        }
                        $tableHead = "
                            <thead>
                                <tr>
                                    {$tableHead}
                                    <td
                                        class='" . ($this->form_elements[$i]["has-borders"] === "on" ? "border-2" : ""
                        ) . " w-10'
                                        style='border-color: #d5d9dd;'
                                    ></td>
                                </tr>
                            </thead>
                        ";

                        $tableBody = $this->renderRepeaterInputFieldRow(
                            $fields,
                            0,
                            $i,
                            $properties
                        );
                        $tableBody = "<tbody>{$tableBody}</tbody>";

                        $footerTotalsExpression = "";
                        if ($this->form_elements[$i]["has-footer"] === "on") {
                            $footerTotalsExpression = $this->form_elements[$i]["footer-tolals"];

                            $expressionReplacement = "
                                <span
                                    class='leform-repeater-expression-var leform-repeater-expression-var'
                                    data-expression='$1'
                                ></span>
                            ";
                            $generalReplacement = "
                                <span
                                    class='leform-var leform-var-$1'
                                    data-id='$1'
                                ></span>
                            ";
                            $columnReplacement = "
                                <span
                                    class='leform-repeater-total-var'
                                    data-id='$1'
                                ></span>
                            ";

                            $footerTotalsExpression = preg_replace_callback(
                                "/{%(.+?)%}/",
                                function ($match) {
                                    $match = $match[1];
                                    $match = preg_replace(
                                        "/{{(\d+).+?}}/",
                                        '{$1}',
                                        $match
                                    );
                                    $match = preg_replace(
                                        "/\[\[(\d+).+?(\]\])/",
                                        "[$1]",
                                        $match
                                    );
                                    $match = trim($match);
                                    $match = "
                                        <span
                                            class='leform-repeater-expression-var leform-repeater-expression-var'
                                            data-expression='" . $match . "'
                                        ></span>
                                    ";
                                    return $match;
                                },
                                $footerTotalsExpression
                            );

                            $footerTotalsExpression = preg_replace(
                                "/{{(\d+).+?}}/",
                                $generalReplacement,
                                $footerTotalsExpression
                            );
                            $footerTotalsExpression = preg_replace(
                                "/\[\[(\d+).+?(\]\])/",
                                $columnReplacement,
                                $footerTotalsExpression
                            );
                        }

                        $tableFoot = "
                            <tfoot>
                                <tr class='" .
                            (count($fields) >= intval($this->form_elements[$i]["add-row-width"])
                                ? (($this->form_elements[$i]["has-borders"]) === "on" ? "border-2" : "")
                                : ""
                            )
                            . "'>
                                    <td
                                        class='py-2 pr-2" .
                            (count($fields) >= intval($this->form_elements[$i]["add-row-width"])
                                ? ""
                                : (($this->form_elements[$i]["has-borders"]) === "on" ? "border-2" : "")
                            )
                            . "'
                                        colspan='" . $this->form_elements[$i]["add-row-width"] . "'
                                    >
                                        <button class='add-row " . ($this->form_elements[$i]["has-borders"] === "on" ? "border-2" : ""
                            ) . " w-full px-4 py-3 rounded-md flex justify-between items-center focus:outline-none border-2'>
                                            <span>" . $this->form_elements[$i]["add-row-label"] . "</span>
                                            <i class='fa fa-plus'></i>
                                        </button>
                                    </td>
                                    " . (count($fields) >= intval($this->form_elements[$i]["add-row-width"])
                                ? "<td colspan='999'></td>"
                                : "")
                            . ($this->form_elements[$i]["has-footer"] === "on"
                                ? "
                                            <tr>
                                                <td class='py-2 " . ($this->form_elements[$i]["has-borders"] === "on" ? "border-2" : ""
                                ) . "' colspan='999'>
                                                    " . $footerTotalsExpression . "
                                                </td>
                                            </tr>
                                        "
                                : "")
                            . "
                                </tr>
                            </tfoot>
                        ";

                        $html .= "
                            <div
                                class='leform-element leform-element-"
                            . $this->form_elements[$i]['id']
                            . (array_key_exists("label-style-position", $properties)
                                && $properties["label-style-position"] != ""
                                ? " leform-element-label-"
                                . $properties["label-style-position"]
                                : ""
                            ) .
                            (array_key_exists("description-style-position", $this->form_elements[$i])
                                && $this->form_elements[$i]["description-style-position"] != ""
                                ? " leform-element-description-"
                                . $this->form_elements[$i]['description-style-position']
                                : ""
                            )
                            . "'
                                data-type='" . $this->form_elements[$i]["type"] . "'
                                data-deps='" . (array_key_exists($this->form_elements[$i]['id'], $this->form_dependencies)
                                ? implode(',', $this->form_dependencies[$this->form_elements[$i]['id']])
                                : ''
                            ) . "'
                                data-id='" . $this->form_elements[$i]['id'] . "'
                            >
                                <div class='leform-column-label" . $column_label_class . "'>
                                    <label class='leform-label" . (
                                (array_key_exists("label-style-align", $this->form_elements[$i])
                                    && $this->form_elements[$i]['label-style-align'] != ""
                                )
                                ? " leform-ta-" . $this->form_elements[$i]['label-style-align']
                                : ""
                            ) . "'>
                                        " . $this->replaceWithPredefinedValues(
                                $properties["required-label-left"]
                                    . $this->form_elements[$i]["label"]
                                    . $properties["required-label-right"]
                                    . $properties["tooltip-label"],
                                $allVariables,
                            ) . "
                                    </label>
                                </div>
                                <div class='leform-column-input $column_input_class'>
                                    <div>
                                        <table class='w-full'>
                                            {$tableHead}
                                            {$tableBody}
                                            {$tableFoot}
                                        </table>
                                    </div>

                                    <div class='leform-input $extra_class' style='height: 0px;'></div>

                                    <label class='leform-description" . (
                                (array_key_exists("description-style-align", $this->form_elements[$i])
                                    && $this->form_elements[$i]['description-style-align'] != ""
                                )
                                ? " leform-ta-" . $this->form_elements[$i]['description-style-align']
                                : ""
                            ) . "'>
                                        " . $this->replaceWithPredefinedValues(
                                $properties["required-description-left"]
                                    . $this->form_elements[$i]["description"]
                                    . $properties["required-description-right"]
                                    . $properties["tooltip-description"],
                                $allVariables
                            ) . "
                                    </label>
                                </div>
                            </div>
                        ";
                        break;

                    case "background-image":
                        $html .= array_key_exists("image", $this->form_elements[$i]) ?  "
                            <div
                                class='leform-element leform-element-" . $this->form_elements[$i]['id'] . "'
                                data-type='" . $this->form_elements[$i]["type"] . "'
                            >
                                <img src='" . $this->form_elements[$i]["image"] . "' />
                            </div>
                        " : "";
                        break;
                    case "iban-input":
                        $html .= LeformService::renderIbanInput(
                            $this->form_elements[$i], 
                            ['iban' => '', 'bic' => ''], 
                            true, 
                            $allVariables,
                            $bindedFieldsAttribute,
                            $depsAttribute
                        );
                        break;

                    default:
                        break;
                }
            }
        }
        return ["html" => $html, "style" => $style, 'uids' => $uids];
    }

    private function accessOrReturnString($property, $array, $defaultValue = 0)
    {
        if (array_key_exists($property, $array)) {
            return $array[$property];
        } else {
            if ($defaultValue !== null) {
                return $defaultValue;
            } else {
                return "";
            }
        }
    }
    private function renderRepeaterInputFieldRow(
        $fields,
        $rowIndex,
        $formFieldIndex,
        $properties
    ) {
        $form_options = $this->form_options;
        $elementPropertiesMeta = $this->leform->getElementPropertiesMeta();

        $row = "";
        foreach ($fields as $columnIndex => $field) {
            $component = "";
            $containerStyle = "";
            switch ($field["type"]) {
                case "text":
                case "email":
                case "password":
                    $component = "
                        <input
                            name='leform-" . $this->form_elements[$formFieldIndex]['id'] . "-" . $rowIndex . "[]'
                            class='w-full'
                            type='" . $this->accessOrReturnString("type", $field) . "'
                            placeholder='" . $this->accessOrReturnString("placeholder", $field) . "'
                            value='" . $this->accessOrReturnString("defaultValue", $field) . "'
                            data-default-value='" . $this->accessOrReturnString("defaultValue", $field) . "'
                            oninput='leform_input_changed(this);'
                            data-input-name='" . $field["name"] . "'
                        />
                    ";
                    break;
                case "number":
                    $component = "
                        <input
                            name='leform-" . $this->form_elements[$formFieldIndex]['id'] . "-" . $rowIndex . "[]'
                            class='w-full'
                            type='number'
                            placeholder='" . $this->accessOrReturnString("placeholder", $field) . "'
                            value='" . $this->accessOrReturnString("defaultValue", $field) . "'
                            data-default-value='" . $this->accessOrReturnString("defaultValue", $field) . "'
                            oninput='leform_input_changed(this);'
                            data-input-name='" . $field["name"] . "'
                            style='border-color: " .
                        $this->form_options["input-border-style-color"]
                        . "; border-radius: " .
                        $this->form_options["input-border-style-radius"]
                        . "px;'
                        />
                    ";
                    break;
                case "select":
                    $options = "";
                    $field["options"] = FormService::getExternalValuesAsArray($field);
                    foreach ($field["options"] as $j => $option) {
                        $selected = ($field["defaultValue"] === $j)
                            ? "selected"
                            : "";
                        $options .= "
                            <option
                                value='{$option}'
                                {$selected}
                            >
                                {$option}
                            </option>
                        ";
                    }
                    $component = "
                        <select
                            name='leform-" . $this->form_elements[$formFieldIndex]['id'] . "-" . $rowIndex . "[]'
                            class='w-full'
                            onchange='leform_input_changed(this);'
                            data-input-name='" . $field["name"] . "'
                        >
                            <option value='' selected disabled>"
                        . $this->accessOrReturnString("placeholder", $field) .
                        "</option>
                            {$options}
                        </select>
                    ";
                    break;
                case "date":
                    $compIcon = '<i class="leform-icon-right fas fa-calendar-alt"></i>';
                    $component = "
                        $compIcon
                        <input
                            name='leform-" . $this->form_elements[$formFieldIndex]['id'] . "-" . $rowIndex . "[]'
                            type='text'
                            class='leform-date w-full'
                            data-default='" . $field["defaultValue"] . "'
                            value='" . $field["defaultValue"] . "'
                            oninput='leform_input_changed(this);'
                            data-format='" . $this->form_options['datetime-args-date-format'] . "' 
                            data-locale='" . $this->form_options['datetime-args-locale'] . "
                            data-input-name='" . $field["name"] . "'
                        />
                    ";
                    break;
                case "time":
                    $component = "
                        <input
                            name='leform-" . $this->form_elements[$formFieldIndex]['id'] . "-" . $rowIndex . "[]'
                            type='text'
                            class='leform-time w-full'
                            " . (array_key_exists("defaultValue", $field)
                        ? "data-default='" . $field["defaultValue"] . "'"
                        : "")
                        . "
                            " . (array_key_exists("defaultValue", $field)
                            ? "value='" . $field["defaultValue"] . "'"
                            : "")
                        . "
                            oninput='leform_input_changed(this);'
                            data-input-name='" . $field["name"] . "'
                        />
                    ";
                    break;
                case "rangeslider":
                    if (isset($style)) {
                        $style .= "#leform-element-{$formFieldIndex} div.leform-input{height:auto;line-height:1;}";
                    } else {
                        $style = "#leform-element-{$formFieldIndex} div.leform-input{height:auto;line-height:1;}";
                    }

                    $containerStyle = "style='width: 150px;'";

                    $options = [
                        [
                            "data-type",
                            $elementPropertiesMeta["rangeslider"]["double"] === "on"
                                ? "double"
                                : "single"
                        ],
                        [
                            "data-grid",
                            $elementPropertiesMeta["rangeslider"]["grid-enable"] === "on"
                                ? "true"
                                : "false"
                        ],
                        [
                            "data-hide-min-max",
                            $elementPropertiesMeta["rangeslider"]["min-max-labels"] === "on"
                                ? "false"
                                : "true"
                        ],
                        ["data-skin", $form_options["rangeslider-skin"]],
                        [
                            "data-from",
                            $this->accessOrReturnString("defaultValue", $field)
                                || $this->accessOrReturnString("min", $field, 0)
                        ],
                        ["data-min", $this->accessOrReturnString("min", $field, 0)],
                        ["data-max", $this->accessOrReturnString("max", $field, 100)],
                    ];

                    $rangeSliderOptions = "";
                    foreach ($options as $option) {
                        /* $rangeSliderOptions .= implode("=", $option); */
                        $rangeSliderOptions .= $option[0] . "='" . $option[1] . "' ";
                    }

                    $component = "
                        <input
                            name='leform-" . $this->form_elements[$formFieldIndex]['id'] . "-" . $rowIndex . "[]'
                            type='text'
                            class='leform-rangeslider w-full'
                            {$rangeSliderOptions}
                        />
                    ";
                    break;
                case "star-rating":
                    $starRatingOptions = "";
                    $starCount = $this->accessOrReturnString("starCount", $field, 5);

                    for ($j = $starCount; $j > 0; $j--) {
                        $starRatingOptions .= "
                            <input
                                type='radio'
                                id='leform-stars-" .
                            $this->form_elements[$formFieldIndex]['id']
                            . "-" .
                            $rowIndex
                            . "-" .
                            $columnIndex
                            . "-" . $j . "'
                                name='leform-" .
                            $this->form_elements[$formFieldIndex]['id']
                            . "-" .
                            $rowIndex
                            . "-" .
                            $columnIndex
                            . "'
                                value='$j'
                                onchange='leform_input_changed(this);'
                                data-input-name='" . $field["name"] . "'
                            />
                            <label for='leform-stars-" .
                            $this->form_elements[$formFieldIndex]['id']
                            . "-" .
                            $rowIndex
                            . "-" .
                            $columnIndex
                            . "-$j'>
                            </label>
                        ";
                    }

                    $component = "
                        <fieldset class='leform-star-rating'>
                            $starRatingOptions
                        </fieldset>
                    ";
                    break;
                case "link-button":
                    $component = "
                        <a
                            class='px-3 leform-button leform-button-"
                        . $form_options['button-active-transform'] .
                        " leform-button-"
                        . $this->accessOrReturnString("width", $properties) .
                        " leform-button-"
                        . $this->accessOrReturnString("size", $properties) .
                        "'
                            href='" . $this->accessOrReturnString("href", $field) . "'
                            target='_blank'
                            onclick='return false;'
                        >"
                        . $this->accessOrReturnString("buttonText", $field) .
                        "</a>
                        <div class='leform-element-cover'></div>
                    ";
                    break;
                case "html": {
                        $htmlContent = "";

                        if (array_key_exists("content", $field)) {
                            $replacement = "
                            <span
                                class='leform-var leform-var-$1'
                                data-id='$1'
                            ></span>
                        ";
                            $htmlContent = $field["content"];
                            $htmlContent = preg_replace("/{{(\d+).+?}}/", $replacement, $htmlContent);
                            /* $htmlContent = preg_replace("/\[\[(\d+).+?(\]\])/", $replacement, $htmlContent); */
                        }
                        $htmlExternal = "";
                        if (
                            isset($field['external-datasource']) &&
                            isset($field['external-datasource-url']) &&
                            $field['external-datasource'] === 'on'
                        ) {
                            // $htmlContent = FormService::getExternalValuesAsString(
                            //   $field['external-datasource-url'],
                            //   $htmlContent,
                            //   []
                            // );
                            $url = preg_replace("/{{(\d+).+?}}/", '{{$1}}', $field['external-datasource-url']);
                            $path = isset($field['external-datasource-path']) ? $field['external-datasource-path'] : '';

                            $htmlExternal = "
                                <span class='html-external-datasource' data-url='$url' data-path='$path'>
                                    <input type='hidden' class='html-external-datasource-transformed' data-path='$path' value=''/>
                                </span>
                            ";
                        }
                        $component = "
                         <div class='leform-element-html-container'>
                            $htmlContent 
                            <div class='leform-element-cover'></div>
                            $htmlExternal
                        </div>
                        ";

                        break;
                    }
            }
            $row .= "
                <td class='py-1 " . (
                ($columnIndex === 0)
                ? "pr-2 pl-0"
                : (($columnIndex === (count($fields) - 1))
                    ? "pl-2 pr-0"
                    : "px-2")
            ) . " " . ($this->form_elements[$formFieldIndex]["has-borders"] === "on" ? "border-2" : ""
            ) . "' data-type='" . $field["type"] . "'>
                    <div class='leform-input' $containerStyle>
                        $component
                    </div>
                </td>
            ";
        }
        return "
            <tr>
                $row
                <td class='" . ($this->form_elements[$formFieldIndex]["has-borders"] === "on" ? "border-2" : ""
        ) . " w-10'>
                    <div class='flex justify-center'>
                        <button
                            class='remove-row bg-red-400 rounded-xl w-6 h-6 flex justify-center items-center hidden'
                            style='color: white;'
                        >
                            -
                        </button>
                    </div>
                </td>
            </tr>
        ";
    }

    public function get_form_html($predefinedValues = null)
    {
        if (empty($this->id)) {
            return false;
        }

        $allVariables = evo_get_all_variables($predefinedValues);
        #$update_time = get_option('leform-update-time', time());
        $update_time = time();
        if (
            $this->preview == false
            && $update_time < $this->cache_time
            && !empty($this->cache_html)
            && !empty($this->cache_style)
        ) {
            $style = $this->cache_style;
            $html = $this->cache_html;
            if (is_array($this->cache_uids) && !empty($this->cache_uids)) {
                foreach ($this->cache_uids as $uid) {
                    $new_uid = $this->leform->random_string(17);
                    $style = str_replace($uid, $new_uid, $style);
                    $html = str_replace($uid, $new_uid, $html);
                }
            }
            return ['style' => $style, 'html' => $html];
        }

        $style = '';
        $html = '';

        if (
            array_key_exists("element-spacing", $this->form_options)
            && $this->form_options["element-spacing"] != ""
        ) {
            $element_spacing = intval(intval($this->form_options["element-spacing"]) / 2);
            if ($element_spacing > 0) {
                $style .= ".leform-form-" . $this->id . " .leform-element, .leform-progress-" . $this->id . " {padding:" . $element_spacing . "px;}";
            }
        }

        if ($this->form_options["progress-enable"] == "on") {
            if ($this->form_options["progress-type"] == 'progress-2') {
                if (array_key_exists("progress-color-color1", $this->form_options) && $this->form_options["progress-color-color1"] != "") $style .= ".leform-progress-" . $this->id . " ul.leform-progress-t2,.leform-progress-" . $this->id . " ul.leform-progress-t2>li>span{background-color:" . $this->form_options["progress-color-color1"] . ";}.leform-progress-" . $this->id . " ul.leform-progress-t2>li>label{color:" . $this->form_options["progress-color-color1"] . ";}";
                if (array_key_exists("progress-color-color2", $this->form_options) && $this->form_options["progress-color-color2"] != "") $style .= ".leform-progress-" . $this->id . " ul.leform-progress-t2>li.leform-progress-t2-active>span,.leform-progress-" . $this->id . " ul.leform-progress-t2>li.leform-progress-t2-passed>span{background-color:" . $this->form_options["progress-color-color2"] . ";}";
                if (array_key_exists("progress-color-color3", $this->form_options) && $this->form_options["progress-color-color3"] != "") $style .= ".leform-progress-" . $this->id . " ul.leform-progress-t2>li>span{color:" . $this->form_options["progress-color-color3"] . ";}";
                if (array_key_exists("progress-color-color4", $this->form_options) && $this->form_options["progress-color-color4"] != "") $style .= ".leform-progress-" . $this->id . " ul.leform-progress-t2>li.leform-progress-t2-active>label{color:" . $this->form_options["progress-color-color4"] . ";}";
            } else {
                if (array_key_exists("progress-color-color1", $this->form_options) && $this->form_options["progress-color-color1"] != "") $style .= ".leform-progress-" . $this->id . " div.leform-progress-t1>div{background-color:" . $this->form_options["progress-color-color1"] . ";}";
                if (array_key_exists("progress-color-color2", $this->form_options) && $this->form_options["progress-color-color2"] != "") $style .= ".leform-progress-" . $this->id . " div.leform-progress-t1>div>div{background-color:" . $this->form_options["progress-color-color2"] . ";}";
                if (array_key_exists("progress-color-color3", $this->form_options) && $this->form_options["progress-color-color3"] != "") $style .= ".leform-progress-" . $this->id . " div.leform-progress-t1>div>div{color:" . $this->form_options["progress-color-color3"] . ";}";
                if (array_key_exists("progress-color-color4", $this->form_options) && $this->form_options["progress-color-color4"] != "") $style .= ".leform-progress-" . $this->id . " div.leform-progress-t1>label{color:" . $this->form_options["progress-color-color4"] . ";}";
            }
            $style .= ".leform-progress-" . $this->id . "{max-width:" . $this->form_options["max-width-value"] . $this->form_options["max-width-unit"] . ";}";
        }

        $important = $this->leform->advancedOptions['important-enable'] == 'on' ? true : false;

        $webfonts = [];
        $text_style = $this->_build_style_text("text-style");
        if ($text_style["webfont"] != "" && !in_array($text_style["webfont"], $webfonts)) $webfonts[] = $text_style["webfont"];
        $style_attr = $text_style["style"];
        $style .= ".leform-form-" . $this->id . ", .leform-form-" . $this->id . " *, .leform-progress-" . $this->id . ", .leform-tooltipster-content-" . $this->id . " {" . $style_attr . "}";

        $style_attr = $this->_build_style_background("inline-background-style");
        $style_attr .= $this->_build_style_border("inline-border-style");
        $style_attr .= $this->_build_shadow("inline-shadow");
        $style_attr .= $this->_build_style_padding("inline-padding", $element_spacing);
        $style .= ".leform-inline .leform-form-" . $this->id . "{" . $style_attr . "}";

        $style_attr = $this->_build_style_background("popup-background-style");
        $style_attr .= $this->_build_style_border("popup-border-style");
        $style_attr .= $this->_build_shadow("popup-shadow");
        $style_attr .= $this->_build_style_padding("popup-padding", $element_spacing);
        $style .= "#leform-popup-" . $this->id . " .leform-form-" . $this->id . "{" . $style_attr . "}";
        $style .= "#leform-popup-" . $this->id . " .leform-popup-close {color:" . $this->form_options["popup-close-color-color1"] . "} #leform-popup-" . $this->id . " .leform-popup-close:hover {color:" . $this->form_options["popup-close-color-color2"] . "}";

        $text_style = $this->_build_style_text("label-text-style");
        if ($text_style["webfont"] != "" && !in_array($text_style["webfont"], $webfonts)) $webfonts[] = $text_style["webfont"];
        $style_attr = $text_style["style"];
        $style .= ".leform-form-" . $this->id . " .leform-element label.leform-label,.leform-form-" . $this->id . " .leform-element label.leform-label span.leform-required-symbol{" . $style_attr . "}";
        $text_style = $this->_build_style_text("description-text-style");
        if ($text_style["webfont"] != "" && !in_array($text_style["webfont"], $webfonts)) $webfonts[] = $text_style["webfont"];
        $style_attr = $text_style["style"];
        $style .= ".leform-form-" . $this->id . " .leform-element label.leform-description,.leform-form-" . $this->id . " .leform-element label.leform-description span.leform-required-symbol{" . $style_attr . "}";
        $text_style = $this->_build_style_text("required-text-style");
        if ($text_style["webfont"] != "" && !in_array($text_style["webfont"], $webfonts)) $webfonts[] = $text_style["webfont"];
        $style_attr = $text_style["style"];
        $style .= ".leform-form-" . $this->id . " .leform-element label.leform-label span.leform-required-symbol,.leform-form-" . $this->id . " .leform-element label.leform-description span.leform-required-symbol{" . $style_attr . "}";

        $text_style = $this->_build_style_text("input-text-style", $important);
        if ($text_style["webfont"] != "" && !in_array($text_style["webfont"], $webfonts)) $webfonts[] = $text_style["webfont"];
        $style_attr = $text_style["style"];
        $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input div.leform-signature-box span i{" . $style_attr . "}";
        $style_attr .= $this->_build_style_background("input-background-style", $important);
        $style_attr .= $this->_build_style_border("input-border-style", $important);
        $style_attr .= $this->_build_shadow("input-shadow", $important);
        $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input div.leform-signature-box,.leform-form-" . $this->id . " .leform-element div.leform-input div.leform-multiselect,.leform-form-" . $this->id . " .leform-element div.leform-input input[type='text'],.leform-form-" . $this->id . " .leform-element div.leform-input input[type='email'],.leform-form-" . $this->id . " .leform-element div.leform-input input[type='password'],.leform-form-" . $this->id . " .leform-element div.leform-input select,.leform-form-" . $this->id . " .leform-element div.leform-input select option,.leform-form-" . $this->id . " .leform-element div.leform-input textarea{" . $style_attr . "}";
        if (array_key_exists("input-text-style-color", $this->form_options) && $this->form_options["input-text-style-color"] != "") $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input ::placeholder{color:" . $this->form_options["input-text-style-color"] . "; opacity: 0.9;} .leform-form-" . $this->id . " .leform-element div.leform-input ::-ms-input-placeholder{color:" . $this->form_options["input-text-style-color"] . "; opacity: 0.9;}";
        $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input div.leform-multiselect::-webkit-scrollbar-thumb{background-color:" . $this->form_options["input-border-style-color"] . ";}";
        if ($this->form_options["input-hover-inherit"] == "off") {
            $text_style = $this->_build_style_text("input-hover-text-style", $important);
            if ($text_style["webfont"] != "" && !in_array($text_style["webfont"], $webfonts)) $webfonts[] = $text_style["webfont"];
            $style_attr = $text_style["style"];
            $style_attr .= $this->_build_style_background("input-hover-background-style", $important);
            $style_attr .= $this->_build_style_border("input-hover-border-style", $important);
            $style_attr .= $this->_build_shadow("input-hover-shadow", $important);
            $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input input[type='text']:hover,.leform-form-" . $this->id . " .leform-element div.leform-input input[type='email']:hover,.leform-form-" . $this->id . " .leform-element div.leform-input input[type='password']:hover,.leform-form-" . $this->id . " .leform-element div.leform-input select:hover,.leform-form-" . $this->id . " .leform-element div.leform-input select:hover option,.leform-form-" . $this->id . " .leform-element div.leform-input textarea:hover{" . $style_attr . "}";
        }
        if ($this->form_options["input-focus-inherit"] == "off") {
            $text_style = $this->_build_style_text("input-focus-text-style", $important);
            if ($text_style["webfont"] != "" && !in_array($text_style["webfont"], $webfonts)) $webfonts[] = $text_style["webfont"];
            $style_attr = $text_style["style"];
            $style_attr .= $this->_build_style_background("input-focus-background-style", $important);
            $style_attr .= $this->_build_style_border("input-focus-border-style", $important);
            $style_attr .= $this->_build_shadow("input-focus-shadow", $important);
            $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input input[type='text']:focus,.leform-form-" . $this->id . " .leform-element div.leform-input input[type='email']:focus,.leform-form-" . $this->id . " .leform-element div.leform-input input[type='password']:focus,.leform-form-" . $this->id . " .leform-element div.leform-input select:focus,.leform-form-" . $this->id . " .leform-element div.leform-input select:focus option,.leform-form-" . $this->id . " .leform-element div.leform-input textarea:focus{" . $style_attr . "}";
        }
        $style_attr = "";
        if ($this->form_options["input-icon-size"] != "") {
            $style_attr .= "font-size:" . $this->form_options["input-icon-size"] . "px;";
        }
        if ($this->form_options["input-icon-color"] != "") {
            $style_attr .= "color:" . $this->form_options["input-icon-color"] . ";";
        }
        if ($this->form_options['input-icon-position'] != 'outside') {
            if ($this->form_options["input-icon-background"] != "") {
                $style_attr .= "background:" . $this->form_options["input-icon-background"] . ";";
            }
            if ($this->form_options["input-icon-border"] != "") {
                $style_attr .= "border-color:" . $this->form_options["input-icon-border"] . ";border-style:solid;";
                if (array_key_exists("input-border-style-width", $this->form_options)) {
                    $size = intval($this->form_options["input-border-style-width"]);
                    if ($size >= 0 && $size <= 16) $style_attr .= "border-width:" . $size . "px;";
                }
            }
            if (array_key_exists("input-border-style-radius", $this->form_options)) {
                $size = intval($this->form_options["input-border-style-radius"]);
                if ($size >= 0 && $size <= 100) $style_attr .= "border-radius:" . $size . "px;";
            }
            if ($this->form_options["input-icon-background"] != "" || $this->form_options["input-icon-border"] != "") {
                $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input.leform-icon-left input[type='text'],.leform-form-" . $this->id . " .leform-element div.leform-input.leform-icon-left input[type='email'],.leform-form-" . $this->id . " .leform-element div.leform-input.leform-icon-left input[type='password'],.leform-form-" . $this->id . " .leform-element div.leform-input.leform-icon-left textarea {padding-left: 56px !important;}";
                $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input.leform-icon-right input[type='text'],.leform-form-" . $this->id . " .leform-element div.leform-input.leform-icon-right input[type='email'],.leform-form-" . $this->id . " .leform-element div.leform-input.leform-icon-right input[type='password'],.leform-form-" . $this->id . " .leform-element div.leform-input.leform-icon-right textarea {padding-right: 56px !important;}";
            }
        }
        if (!empty($style_attr)) {
            $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input>i.leform-icon-left, .leform-form-" . $this->id . " .leform-element div.leform-input>i.leform-icon-right{" . $style_attr . "}";
        }
        $text_style = $this->_build_style_text("button-text-style");
        if ($text_style["webfont"] != "" && !in_array($text_style["webfont"], $webfonts)) $webfonts[] = $text_style["webfont"];
        $style_attr = $text_style["style"];
        $style_attr .= $this->_build_style_background("button-background-style");
        $style_attr .= $this->_build_style_border("button-border-style");
        $style_attr .= $this->_build_shadow("button-shadow");
        $style .= ".leform-form-" . $this->id . " .leform-element .leform-button,.leform-form-" . $this->id . " .leform-element .leform-button:visited{" . $style_attr . "}";
        if ($this->form_options["button-hover-inherit"] == "off") {
            $text_style = $this->_build_style_text("button-hover-text-style");
            if ($text_style["webfont"] != "" && !in_array($text_style["webfont"], $webfonts)) $webfonts[] = $text_style["webfont"];
            $style_attr = $text_style["style"];
            $style_attr .= $this->_build_style_background("button-hover-background-style");
            $style_attr .= $this->_build_style_border("button-hover-border-style");
            $style_attr .= $this->_build_shadow("button-hover-shadow");
            $style .= ".leform-form-" . $this->id . " .leform-element .leform-button:hover,.leform-form-" . $this->id . " .leform-element .leform-button:focus{" . $style_attr . "}";
        }
        if ($this->form_options["button-active-inherit"] == "off") {
            $text_style = $this->_build_style_text("button-active-text-style");
            if ($text_style["webfont"] != "" && !in_array($text_style["webfont"], $webfonts)) $webfonts[] = $text_style["webfont"];
            $style_attr = $text_style["style"];
            $style_attr .= $this->_build_style_background("button-active-background-style");
            $style_attr .= $this->_build_style_border("button-active-border-style");
            $style_attr .= $this->_build_shadow("button-active-shadow");
            $style .= ".leform-form-" . $this->id . " .leform-element .leform-button:active{" . $style_attr . "}";
        }

        $style_attr = $this->_build_style_border("imageselect-border-style");
        $style_attr .= $this->_build_shadow("imageselect-shadow");
        $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input .leform-imageselect+label{" . $style_attr . "}";
        if ($this->form_options["imageselect-hover-inherit"] == "off") {
            $style_attr = $this->_build_style_border("imageselect-hover-border-style");
            $style_attr .= $this->_build_shadow("imageselect-hover-shadow");
            $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input .leform-imageselect+label:hover{" . $style_attr . "}";
        }
        if ($this->form_options["imageselect-selected-inherit"] == "off") {
            $style_attr = $this->_build_style_border("imageselect-selected-border-style");
            $style_attr .= $this->_build_shadow("imageselect-selected-shadow");
            $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input .leform-imageselect:checked+label{" . $style_attr . "}";
        }
        $text_style = $this->_build_style_text("imageselect-text-style");
        if ($text_style["webfont"] != "" && !in_array($text_style["webfont"], $webfonts)) $webfonts[] = $text_style["webfont"];
        $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input .leform-imageselect+label span.leform-imageselect-label{" . $text_style["style"] . "}";

        $style_attr = "";
        if (array_key_exists("checkbox-radio-unchecked-color-color2", $this->form_options) && $this->form_options["checkbox-radio-unchecked-color-color2"] != "") $style_attr .= "background-color:" . $this->form_options["checkbox-radio-unchecked-color-color2"] . ";";
        else $style_attr .= "background-color:transparent;";
        $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input input[type='checkbox'].leform-checkbox-tgl:checked+label:after{" . $style_attr . "}";
        if (array_key_exists("checkbox-radio-unchecked-color-color1", $this->form_options) && $this->form_options["checkbox-radio-unchecked-color-color1"] != "") $style_attr .= "border-color:" . $this->form_options["checkbox-radio-unchecked-color-color1"] . ";";
        else $style_attr .= "border-color:transparent;";
        if (array_key_exists("checkbox-radio-unchecked-color-color3", $this->form_options) && $this->form_options["checkbox-radio-unchecked-color-color3"] != "") $style_attr .= "color:" . $this->form_options["checkbox-radio-unchecked-color-color3"] . ";";
        else $style_attr .= "color:#ccc;";
        $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input input[type='checkbox'].leform-checkbox-classic+label,.leform-form-" . $this->id . " .leform-element div.leform-input input[type='checkbox'].leform-checkbox-fa-check+label,.leform-form-" . $this->id . " .leform-element div.leform-input input[type='checkbox'].leform-checkbox-square+label,.leform-form-" . $this->id . " .leform-element div.leform-input input[type='checkbox'].leform-checkbox-tgl+label{" . $style_attr . "}";
        $style_attr = "";
        if (array_key_exists("checkbox-radio-unchecked-color-color3", $this->form_options) && $this->form_options["checkbox-radio-unchecked-color-color3"] != "") $style_attr .= "background-color:" . $this->form_options["checkbox-radio-unchecked-color-color3"] . ";";
        else $style_attr .= "color:#ccc;";
        $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input input[type='checkbox'].leform-checkbox-square:checked+label:after{" . $style_attr . "}";
        $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input input[type='checkbox'].leform-checkbox-tgl:checked+label,.leform-form-" . $this->id . " .leform-element div.leform-input input[type='checkbox'].leform-checkbox-tgl+label:after{" . $style_attr . "}";
        if ($this->form_options["checkbox-radio-checked-inherit"] == "off") {
            $style_attr = "";
            if (array_key_exists("checkbox-radio-checked-color-color2", $this->form_options) && $this->form_options["checkbox-radio-checked-color-color2"] != "") $style_attr .= "background-color:" . $this->form_options["checkbox-radio-checked-color-color2"] . ";";
            else $style_attr .= "background-color:transparent;";
            if (array_key_exists("checkbox-radio-checked-color-color1", $this->form_options) && $this->form_options["checkbox-radio-checked-color-color1"] != "") $style_attr .= "border-color:" . $this->form_options["checkbox-radio-checked-color-color1"] . ";";
            else $style_attr .= "border-color:transparent;";
            if (array_key_exists("checkbox-radio-checked-color-color3", $this->form_options) && $this->form_options["checkbox-radio-checked-color-color3"] != "") $style_attr .= "color:" . $this->form_options["checkbox-radio-checked-color-color3"] . ";";
            else $style_attr .= "color:#ccc;";
            $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input input[type='checkbox'].leform-checkbox-classic:checked+label,.leform-form-" . $this->id . " .leform-element div.leform-input input[type='checkbox'].leform-checkbox-fa-check:checked+label,.leform-form-" . $this->id . " .leform-element div.leform-input input[type='checkbox'].leform-checkbox-square:checked+label, .leform-form-" . $this->id . " .leform-element div.leform-input input[type='checkbox'].leform-checkbox-tgl:checked+label{" . $style_attr . "}";
            $style_attr = "";
            if (array_key_exists("checkbox-radio-checked-color-color3", $this->form_options) && $this->form_options["checkbox-radio-checked-color-color3"] != "") $style_attr .= "background-color:" . $this->form_options["checkbox-radio-checked-color-color3"] . ";";
            else $style_attr .= "background-color:#ccc;";
            $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input input[type='checkbox'].leform-checkbox-square:checked+label:after{" . $style_attr . "}";
            $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input input[type='checkbox'].leform-checkbox-tgl:checked+label:after{" . $style_attr . "}";
        }

        $style_attr = "";
        if (array_key_exists("checkbox-radio-unchecked-color-color2", $this->form_options) && $this->form_options["checkbox-radio-unchecked-color-color2"] != "") $style_attr .= "background-color:" . $this->form_options["checkbox-radio-unchecked-color-color2"] . ";";
        else $style_attr .= "background-color:transparent;";
        if (array_key_exists("checkbox-radio-unchecked-color-color1", $this->form_options) && $this->form_options["checkbox-radio-unchecked-color-color1"] != "") $style_attr .= "border-color:" . $this->form_options["checkbox-radio-unchecked-color-color1"] . ";";
        else $style_attr .= "border-color:transparent;";
        if (array_key_exists("checkbox-radio-unchecked-color-color3", $this->form_options) && $this->form_options["checkbox-radio-unchecked-color-color3"] != "") $style_attr .= "color:" . $this->form_options["checkbox-radio-unchecked-color-color3"] . ";";
        else $style_attr .= "color:#ccc;";
        $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input input[type='radio'].leform-radio-classic+label,.leform-form-" . $this->id . " .leform-element div.leform-input input[type='radio'].leform-radio-fa-check+label,.leform-form-" . $this->id . " .leform-element div.leform-input input[type='radio'].leform-radio-dot+label{" . $style_attr . "}";
        $style_attr = "";
        if (array_key_exists("checkbox-radio-unchecked-color-color3", $this->form_options) && $this->form_options["checkbox-radio-unchecked-color-color3"] != "") $style_attr .= "background-color:" . $this->form_options["checkbox-radio-unchecked-color-color3"] . ";";
        else $style_attr .= "background-color:#ccc;";
        $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input input[type='radio'].leform-radio-dot:checked+label:after{" . $style_attr . "}";
        if ($this->form_options["checkbox-radio-checked-inherit"] == "off") {
            $style_attr = "";
            if (array_key_exists("checkbox-radio-checked-color-color2", $this->form_options) && $this->form_options["checkbox-radio-checked-color-color2"] != "") $style_attr .= "background-color:" . $this->form_options["checkbox-radio-checked-color-color2"] . ";";
            else $style_attr .= "background-color:transparent;";
            if (array_key_exists("checkbox-radio-checked-color-color1", $this->form_options) && $this->form_options["checkbox-radio-checked-color-color1"] != "") $style_attr .= "border-color:" . $this->form_options["checkbox-radio-checked-color-color1"] . ";";
            else $style_attr .= "border-color:transparent;";
            if (array_key_exists("checkbox-radio-checked-color-color3", $this->form_options) && $this->form_options["checkbox-radio-checked-color-color3"] != "") $style_attr .= "color:" . $this->form_options["checkbox-radio-checked-color-color3"] . ";";
            else $style_attr .= "color:#ccc;";
            $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input input[type='radio'].leform-radio-classic:checked+label,.leform-form-" . $this->id . " .leform-element div.leform-input input[type='radio'].leform-radio-fa-check:checked+label,.leform-form-" . $this->id . " .leform-element div.leform-input input[type='radio'].leform-radio-dot:checked+label{" . $style_attr . "}";
            $style_attr = "";
            if (array_key_exists("checkbox-radio-checked-color-color3", $this->form_options) && $this->form_options["checkbox-radio-checked-color-color3"] != "") $style_attr .= "background-color:" . $this->form_options["checkbox-radio-checked-color-color3"] . ";";
            else $style_attr .= "background-color:#ccc;";
            $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input input[type='radio'].leform-radio-dot:checked+label:after{" . $style_attr . "}";
        }

        $style_attr = "";
        if (array_key_exists("multiselect-style-hover-background", $this->form_options) && $this->form_options["multiselect-style-hover-background"] != "") $style_attr .= "background-color:" . $this->form_options['multiselect-style-hover-background'] . ";";
        if (array_key_exists("multiselect-style-hover-color", $this->form_options) && $this->form_options["multiselect-style-hover-color"] != "") $style_attr .= "color:" . $this->form_options['multiselect-style-hover-color'] . ";";
        if (!empty($style_attr)) $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input div.leform-multiselect>input[type='checkbox']+label:hover{" . $style_attr . "}";
        $style_attr = "";
        if (array_key_exists("multiselect-style-selected-background", $this->form_options) && $this->form_options["multiselect-style-selected-background"] != "") $style_attr .= "background-color:" . $this->form_options['multiselect-style-selected-background'] . ";";
        if (array_key_exists("multiselect-style-selected-color", $this->form_options) && $this->form_options["multiselect-style-selected-color"] != "") $style_attr .= "color:" . $this->form_options['multiselect-style-selected-color'] . ";";
        if (!empty($style_attr)) $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input div.leform-multiselect>input[type='checkbox']:checked+label{" . $style_attr . "}";
        if (array_key_exists("multiselect-style-height", $this->form_options) && $this->form_options["multiselect-style-height"] != "") $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input div.leform-multiselect{height:" . intval($this->form_options['multiselect-style-height']) . "px;}";

        if ($this->leform->options['range-slider-enable'] == 'on') {
            if (array_key_exists("rangeslider-color-color1", $this->form_options) && $this->form_options["rangeslider-color-color1"] != "") $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input.leform-rangeslider .irs-line,.leform-form-" . $this->id . " .leform-element div.leform-input.leform-rangeslider .irs-min,.leform-form-" . $this->id . " .leform-element div.leform-input.leform-rangeslider .irs-max,.leform-form-" . $this->id . " .leform-element div.leform-input.leform-rangeslider .irs-grid-pol{background-color:" . $this->form_options['rangeslider-color-color1'] . " !important;}";
            if (array_key_exists("rangeslider-color-color2", $this->form_options) && $this->form_options["rangeslider-color-color2"] != "") $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input.leform-rangeslider .irs-grid-text,.leform-form-" . $this->id . " .leform-element div.leform-input.leform-rangeslider .irs-min,.leform-form-" . $this->id . " .leform-element div.leform-input.leform-rangeslider .irs-max{color:" . $this->form_options["rangeslider-color-color2"] . " !important;}";
            if (array_key_exists("rangeslider-color-color3", $this->form_options) && $this->form_options["rangeslider-color-color3"] != "") $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input.leform-rangeslider .irs-bar{background-color:" . $this->form_options["rangeslider-color-color3"] . " !important;}";
            if (array_key_exists("rangeslider-color-color4", $this->form_options) && $this->form_options["rangeslider-color-color4"] != "") {
                $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input.leform-rangeslider .irs-single,.leform-form-" . $this->id . " .leform-element div.leform-input.leform-rangeslider .irs-from,.leform-form-" . $this->id . " .leform-element div.leform-input.leform-rangeslider .irs-to{background-color:" . $this->form_options["rangeslider-color-color4"] . " !important;}";
                //$style .= ".leform-form-".$this->id." .leform-element div.leform-input.leform-rangeslider .irs-single:before,.leform-form-".$this->id." .leform-element div.leform-input.leform-rangeslider .irs-from:before,.leform-form-".$this->id." .leform-element div.leform-input.leform-rangeslider .irs-to:before{border-top-color:".$this->form_options["rangeslider-color-color4"]." !important;}";
                switch ($this->form_options["rangeslider-skin"]) {
                    case 'sharp':
                        $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input.leform-rangeslider .irs--sharp .irs-handle,.leform-form-" . $this->id . " .leform-element div.leform-input.leform-rangeslider .irs--sharp .irs-handle:hover,.leform-form-" . $this->id . " .leform-element div.leform-input.leform-rangeslider .irs--sharp .irs-handle.state_hover{background-color:" . $this->form_options["rangeslider-color-color4"] . " !important;}";
                        $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input.leform-rangeslider .irs--sharp .irs-handle > i:first-child,.leform-form-" . $this->id . " .leform-element div.leform-input.leform-rangeslider .irs--sharp .irs-handle:hover > i:first-child,.leform-form-" . $this->id . " .leform-element div.leform-input.leform-rangeslider .irs--sharp .irs-handle.state_hover > i:first-child{border-top-color:" . $this->form_options["rangeslider-color-color4"] . " !important;}";
                        break;
                    case 'round':
                        $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input.leform-rangeslider .irs--round .irs-handle,.leform-form-" . $this->id . " .leform-element div.leform-input.leform-rangeslider .irs--round .irs-handle:hover,.leform-form-" . $this->id . " .leform-element div.leform-input.leform-rangeslider .irs--round .irs-handle.state_hover{border-color:" . $this->form_options["rangeslider-color-color4"] . " !important;}";
                        break;
                    default:
                        $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input.leform-rangeslider .irs--flat .irs-handle > i:first-child,.leform-form-" . $this->id . " .leform-element div.leform-input.leform-rangeslider .irs--flat .irs-handle:hover > i:first-child,.leform-form-" . $this->id . " .leform-element div.leform-input.leform-rangeslider .irs--flat .irs-handle.state_hover > i:first-child{background-color:" . $this->form_options["rangeslider-color-color4"] . " !important;}";
                        break;
                }
            }
            if (array_key_exists("rangeslider-color-color5", $this->form_options) && $this->form_options["rangeslider-color-color5"] != "") {
                $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input.leform-rangeslider .irs-single,.leform-form-" . $this->id . " .leform-element div.leform-input.leform-rangeslider .irs-from,.leform-form-" . $this->id . " .leform-element div.leform-input.leform-rangeslider .irs-to{color:" . $this->form_options["rangeslider-color-color5"] . " !important;}";
                if ($this->form_options["rangeslider-skin"] == "round") {
                    $style .= ".leform-form-" . $this->id . " .leform-element div.leform-input.leform-rangeslider .irs--round .irs-handle,.leform-form-" . $this->id . " .leform-element div.leform-input.leform-rangeslider .irs--round .irs-handle:hover,.leform-form-" . $this->id . " .leform-element div.leform-input.leform-rangeslider .irs--round .irs-handle.state_hover{background-color:" . $this->form_options["rangeslider-color-color5"] . " !important;}";
                }
            }
        }

        $text_style = $this->_build_style_text("tile-text-style");
        if ($text_style["webfont"] != "" && !in_array($text_style["webfont"], $webfonts)) $webfonts[] = $text_style["webfont"];
        $style_attr = $text_style["style"];
        $style_attr .= $this->_build_style_background("tile-background-style");
        $style_attr .= $this->_build_style_border("tile-border-style");
        $style_attr .= $this->_build_shadow("tile-shadow");
        $style .= ".leform-form-" . $this->id . " .leform-element input[type='checkbox'].leform-tile+label, .leform-form-" . $this->id . " .leform-element input[type='radio'].leform-tile+label {" . $style_attr . "}";
        if ($this->form_options["tile-hover-inherit"] == "off") {
            $text_style = $this->_build_style_text("tile-hover-text-style");
            if ($text_style["webfont"] != "" && !in_array($text_style["webfont"], $webfonts)) $webfonts[] = $text_style["webfont"];
            $style_attr = $text_style["style"];
            $style_attr .= $this->_build_style_background("tile-hover-background-style");
            $style_attr .= $this->_build_style_border("tile-hover-border-style");
            $style_attr .= $this->_build_shadow("tile-hover-shadow");
            $style .= ".leform-form-" . $this->id . " .leform-element input[type='checkbox'].leform-tile+label:hover, .leform-form-" . $this->id . " .leform-element input[type='radio'].leform-tile+label:hover{" . $style_attr . "}";
        }
        if ($this->form_options["tile-selected-inherit"] == "off") {
            $text_style = $this->_build_style_text("tile-selected-text-style");
            if ($text_style["webfont"] != "" && !in_array($text_style["webfont"], $webfonts)) $webfonts[] = $text_style["webfont"];
            $style_attr = $text_style["style"];
            $style_attr .= $this->_build_style_background("tile-selected-background-style");
            $style_attr .= $this->_build_style_border("tile-selected-border-style");
            $style_attr .= $this->_build_shadow("tile-selected-shadow");
            $style .= ".leform-form-" . $this->id . " .leform-element input[type='checkbox'].leform-tile:checked+label, .leform-form-" . $this->id . " .leform-element input[type='radio'].leform-tile:checked+label{" . $style_attr . "}";
        }

        $text_style = $this->_build_style_text("error-text-style");
        if ($text_style["webfont"] != "" && !in_array($text_style["webfont"], $webfonts)) $webfonts[] = $text_style["webfont"];
        $style_attr = $text_style["style"];
        $style_attr .= $this->_build_style_background("error-background-style");
        $style .= ".leform-form-" . $this->id . " .leform-element .leform-input .leform-element-error,.leform-uploader-error{" . $style_attr . "}";

        $collapse = 480;
        if (array_key_exists("responsiveness-size", $this->form_options) && in_array($this->form_options['responsiveness-size'], [480, 768, 1024, 'custom'])) {
            if ($this->form_options['responsiveness-size'] == 'custom') {
                $collapse = intval($this->form_options['responsiveness-custom']);
            } else $collapse = $this->form_options['responsiveness-size'];
        }
        $id = $this->leform->random_string(16);
        $uids = [$id];
        $xd = $this->form_options["cross-domain"];
        for ($i = 0; $i < sizeof($this->form_elements); $i++) {
            if (array_key_exists('type', $this->form_elements[$i]) &&  $this->form_elements[$i]['type'] == 'signature') {
                $xd = 'off';
                break;
            }
        }
        for ($i = 0; $i < sizeof($this->form_pages); $i++) {
            if (!empty($this->form_pages[$i]) && is_array($this->form_pages[$i])) {
                $output = $this->_build_children(
                    $this->form_pages[$i]['id'],
                    0,
                    $allVariables
                );
                $progress = $this->leform_build_progress($i, $id);
                $style .= $output["style"];
                $hidden = $this->_build_hidden($this->form_pages[$i]['id'], $allVariables);
                $html .= ($this->form_options["progress-position"] == "outside" ? $progress : '') . '<div class="leform-form leform-form-' . $this->id . ' leform-form-' . $id . ' leform-form-input-' . $this->form_options['input-size'] . ' leform-form-icon-' . $this->form_options['input-icon-position'] . ' leform-form-description-' . $this->form_options['description-style-position'] . '" data-session="' . ($this->form_options['session-enable'] == 'on' ? intval($this->form_options['session-length']) : '0') . '" data-collapse="' . $collapse . '" data-id="' . $id . '" data-form-id="' . $this->id . '" data-title="' . $this->form_options["name"] . '" data-page="' . $this->form_pages[$i]['id'] . '" data-xd="' . $xd . '" data-tooltip-theme="' . (array_key_exists("tooltip-theme", $this->form_options) ? $this->form_options["tooltip-theme"] : "dark") . '" style="display:none;"><div class="leform-form-inner">' . ($this->form_options["progress-position"] == "outside" ? '' : $progress) . $output["html"] . $hidden . '</div></div>';
                $uids = array_merge($uids, $output['uids']);
            }
        }
        $html .= '<input type="hidden" id="leform-logic-' . $id . '" value=\'' . json_encode($this->form_logic) . '\' />';

        if (
            array_key_exists('math-expressions', $this->form_options)
            && !empty($this->form_options['math-expressions'])
        ) {
            foreach ($this->form_options['math-expressions'] as $math_expression) {
                $formValuesData = [];
                $formValuesIds = [];
                $formValuesMatches = [];
                preg_match_all(
                    '/{{(\d+)\|.+?}}/',
                    $math_expression["expression"],
                    $formValuesMatches
                );
                for ($j = 0; $j < sizeof($formValuesMatches[0]); $j++) {
                    if (
                        !empty($formValuesMatches[0][$j])
                        && !empty($formValuesMatches[1][$j])
                    ) {
                        $formValuesData[$formValuesMatches[0][$j]] = '{' . $formValuesMatches[1][$j] . '}';
                        $formValuesIds[] = $formValuesMatches[1][$j];
                    }
                }
                $expression = $math_expression["expression"];
                $expression = strtr($math_expression["expression"], $formValuesData);

                $rowValuesData = [];
                $rowValuesIds = [];
                $rowValuesMatches = [];
                preg_match_all(
                    '/\[\[(\d+)\|[^\|]+\|(\d+)\|[^\|]+\]\]/',
                    $math_expression["expression"],
                    $rowValuesMatches
                );
                for ($j = 0; $j < sizeof($rowValuesMatches[0]); $j++) {
                    if (
                        !empty($rowValuesMatches[0][$j])
                        && !empty($rowValuesMatches[1][$j])
                        && !empty($rowValuesMatches[2][$j])
                    ) {
                        $rowValuesData[$rowValuesMatches[0][$j]] = '['
                            . $rowValuesMatches[1][$j]
                            . "|"
                            . $rowValuesMatches[2][$j]
                            . ']';
                        $rowValuesIds[] = $rowValuesMatches[1][$j]
                            . "|"
                            . $rowValuesMatches[2][$j];
                    }
                }
                $expression = strtr($expression, $rowValuesData);

                $rowTotalsData = [];
                $rowTotalsIds = [];
                $rowTotalsMatches = [];
                preg_match_all(
                    '/\{\[(\d+)\|[^\|]+\|(\d+)\|[^\|]+\]\}/',
                    $math_expression["expression"],
                    $rowTotalsMatches
                );
                for ($j = 0; $j < sizeof($rowTotalsMatches[0]); $j++) {
                    if (
                        !empty($rowTotalsMatches[0][$j])
                        && !empty($rowTotalsMatches[1][$j])
                        && !empty($rowTotalsMatches[2][$j])
                    ) {
                        $rowTotalsData[$rowTotalsMatches[0][$j]] = '{['
                            . $rowTotalsMatches[1][$j]
                            . "|"
                            . $rowTotalsMatches[2][$j]
                            . ']}';
                        $rowTotalsIds[] = $rowTotalsMatches[1][$j]
                            . "|"
                            . $rowTotalsMatches[2][$j];
                    }
                }
                $expression = strtr($expression, $rowTotalsData);

                $html .= '
                    <input
                        class="leform-math"
                        data-expression="' . $expression . '"
                        data-id="' . $math_expression["id"] . '"

                        data-form-values-ids="' . implode(',', $formValuesIds) . '"
                        data-row-values-ids="' . implode(',', $rowValuesIds) . '"
                        data-row-totals-ids="' . implode(',', $rowTotalsIds) . '"

                        data-decimal="' . $math_expression["decimal-digits"] . '"
                        data-default="' . $math_expression["default"] . '"
                        type="hidden"
                        name="leform-math-' . $math_expression['id'] . '"
                        value="' . $math_expression["default"] . '"
                    />
                ';
            }
        }

        /*
         * DEPRECATED AT THE LAST MINUTEEE
        foreach ($this->form_elements as $formElement) {
            if ($formElement["type"] === "repeater-input") {
                if ($formElement["has-footer"] === "on") {
                    $footerTotals = $formElement["footer-tolals"];
                    $formattedFooterTotals = $footerTotals;

                    $expressions = [];
                    preg_match_all(
                        "{%(.+?)%}",
                        $formElement["footer-tolals"],
                        $expressions,
                    );
                    $expressions = $expressions[1];
                    $expressions = array_map("trim", $expressions);

                    foreach ($expressions as $expression) {
                        $formattedExpression = $expression;
                        $formattedExpression = preg_replace(
                            '/\{\{(\d+)(|.+?)\}\}/',
                            '{$1}',
                            $formattedExpression
                        );
                        $formattedExpression = preg_replace(
                            '/\[\[(\d+)(|.+?)\]\]/',
                            "[$1]",
                            $formattedExpression
                        );
                        $html .= '
                            <input
                                class="leform-repeater-totals-math"
                                data-expression="'.$formattedExpression.'"
                                data-repeater-input-id="'.$formElement["id"].'"
                                data-decimal="2"
                                data-default="0"
                                value="0"
                                type="hidden"
                            />
                        ';

                        $formattedFooterTotals = strtr(
                            $formattedFooterTotals,
                            [$expression => $formattedExpression],
                        );
                    }

                    $dynamicColumnTotals = [];
                    preg_match_all(
                        '/\[\[(\d+)(|.+?)\]\]/',
                        $formattedFooterTotals,
                        $dynamicColumnTotals,
                    );

                    foreach ($dynamicColumnTotals[1] as $columnTotal) {
                        $html .= '
                            <input
                                class="leform-repeater-column-total"
                                data-repeater-input-id="'.$formElement["id"].'"
                                data-column="'. $columnTotal .'"
                                data-decimal="0"
                                data-default="0"
                                value="0"
                                type="hidden"
                            />
                        ';
                    };
                }

                foreach ($formElement["expressions"] as $expression) {
                    $columnIndexes = [];
                    preg_match_all(
                        '/\[\[(\d+)(|.+?)\]\]/',
                        $expression["expression"],
                        $columnIndexes
                    );
                    $columnIndexes = $columnIndexes[1];
                    $expressionContent = preg_replace(
                        '/\[\[(\d+)(|.+?)\]\]/',
                        "[$1]",
                        $expression["expression"]
                    );
                    $html .= '
                        <input
                            class="leform-repeater-math"
                            data-expression="'.$expressionContent.'"
                            data-id="'.$expression["id"].'"
                            data-ids="'.implode(',', $columnIndexes).'"
                            data-repeater-input-id="'.$formElement["id"].'"
                            data-decimal="'.$expression["decimalDigits"].'"
                            data-default="'.$expression["default"].'"
                            data-row="1"
                            type="hidden"
                            name="leform-math-'.$expression["id"].'"
                            value="'.$expression["default"].'"
                        />
                    ';
                }
            }
        }
         */

        $style = '<style>' . $style . '</style>';
        if (!empty($webfonts)) {
            $webfonts = array_unique($webfonts);
            $esc_array = [];
            foreach ($webfonts as $array_value) {
                $esc_array[] = $array_value;
            }
            #$webfonts_array = $wpdb->get_results("SELECT * FROM ".$wpdb->prefix."leform_webfonts WHERE family IN ('".implode("', '", $esc_array)."') AND deleted = '0' ORDER BY family", ARRAY_A);
            $webfonts_array = Webfont::whereIn('family', $esc_array)
                ->where('deleted', 0)
                #->sortBy('family')
                ->get();
            if (!empty($webfonts_array)) {
                $families = [];
                $subsets = [];
                foreach ($webfonts_array as $webfont) {
                    $families[] = str_replace(' ', '+', $webfont['family']) . ':' . $webfont['variants'];
                    $webfont_subsets = explode(',', $webfont['subsets']);
                    if (!empty($webfont_subsets) && is_array($webfont_subsets)) $subsets = array_merge($subsets, $webfont_subsets);
                }
                $subsets = array_unique($subsets);
                $query = '?family=' . implode('|', $families);
                if (!empty($subsets)) $query .= '&subset=' . implode(',', $subsets);
                $style = '<link href="//fonts.googleapis.com/css' . $query . '" rel="stylesheet" type="text/css">' . $style;
            }
        }

        #$html .= apply_filters('leform_form_suffix', '', $id, $this);
        #$html .= $this->leform->front_form_suffix('', $id, $this);

        if ($this->preview == false) {
            #$wpdb->query("UPDATE ".$wpdb->prefix."leform_forms SET
            #  cache_style = '".esc_sql($style)."',
            #  cache_html = '".esc_sql($html)."',
            #  cache_uids = '".esc_sql(json_encode($uids))."',
            #  cache_time = '".esc_sql(time())."'
            #  WHERE id = '".esc_sql($this->id)."'");
            Form::where('id', $this->id)->update([
                'cache_style' => $style,
                'cache_html' => $html,
                'cache_uids' => $uids,
                'cache_time' => time(),
            ]);
        }

        return ['style' => $style, 'html' => $html];
    }

    function input_fields_sort()
    {
        $input_fields = [];
        $fields = [];
        for ($i = 0; $i < sizeof($this->form_pages); $i++) {
            if (
                !empty($this->form_pages[$i])
                && is_array($this->form_pages[$i])
            ) {
                $fields = $this->_leform_input_sort(
                    $this->form_pages[$i]['id'],
                    0,
                    $this->form_pages[$i]['id'],
                    $this->form_pages[$i]['name']
                );
                if (!empty($fields)) {
                    $input_fields = array_merge($input_fields, $fields);
                }
            }
        }
        return $input_fields;
    }

    function get_field_editor($_field_id, $_value = '')
    {
        $html = '';
        if (array_key_exists($_field_id, $this->form_data)) {
            $type = 'text';
            foreach ($this->form_elements as $form_element) {
                if (
                    is_array($form_element)
                    && array_key_exists('id', $form_element)
                    && $form_element['id'] == $_field_id
                ) {
                    $type = $form_element['type'];
                    break;
                }
            }
            if ($type == 'imageselect' || $type == 'tile') {
                if ($form_element['mode'] == 'radio') {
                    $type = 'radio';
                } else {
                    $type = 'checkbox';
                }
            }
            switch ($type) {
                case 'text':
                case 'email':
                case 'password':
                case 'hidden':
                case 'date':
                case 'time':
                case 'rangeslider':
                case 'number':
                case 'numspinner':
                    $html = '<input type="text" value="' . $_value . '" name="value" />';
                    break;
                case 'textarea':
                    $html = '<textarea name="value">' . $_value . '</textarea>';
                    break;
                case 'select':
                    $options = "";
                    if (
                        array_key_exists("please-select-option", $form_element)
                        && $form_element["please-select-option"] == "on"
                    ) {
                        $options .= "<option value=''>" . $form_element["please-select-text"] . "</option>";
                    }
                    $form_element["options"] = FormService::getExternalValuesAsArray($form_element);
                    for ($j = 0; $j < sizeof($form_element["options"]); $j++) {
                        $options .= "<option value='" . $form_element["options"][$j]["value"] . "'" . ($_value == $form_element["options"][$j]["value"] ? ' selected="selected"' : '') . ">" . $form_element["options"][$j]["label"] . "</option>";
                    }
                    $html = '<select name="value">' . $options . '</select>';
                    break;
                case 'radio':
                    $options = "";
                    if (
                        array_key_exists("please-select-option", $form_element)
                        && $form_element["please-select-option"] == "on"
                    ) {
                        $options .= "<option value=''>" . $form_element["please-select-text"] . "</option>";
                    }
                    for ($j = 0; $j < sizeof($form_element["options"]); $j++) {
                        $options .= "<option value='" . $form_element["options"][$j]["value"] . "'" . ($_value == $form_element["options"][$j]["value"] ? ' selected="selected"' : '') . ">" . $form_element["options"][$j]["label"] . "</option>";
                    }
                    $html = '<select name="value">' . $options . '</select>';
                    break;
                case 'checkbox':
                case 'multiselect':
                    $options = "";
                    $total = 0;
                    for ($j = 0; $j < sizeof($form_element["options"]); $j++) {
                        $id = $this->leform->random_string(16);
                        $html .= "<div class='leform-cr-box'><input class='leform-checkbox leform-checkbox-fa-check leform-checkbox-medium' type='checkbox' name='value[]' id='" . $id . "' value='" . $form_element["options"][$j]["value"] . "'" . (in_array($form_element["options"][$j]["value"], (array)$_value) ? ' checked="checked"' : '') . " /><label for='" . $id . "'></label> &nbsp; <label for='" . $id . "'>" . $form_element["options"][$j]["label"] . "</label></div>";
                        $total++;
                    }
                    if ($total > 10) {
                        $html = '<div class="leform-record-field-editor-scrollbox">' . $html . '</div>';
                    }
                    break;
                case 'matrix':
                    $options = "";
                    $total = 0;
                    /* aaaaaaaaaaaaaaaaaaaaaaaaaaaaaaa */
                    $html .= "<div class='grid grid-cols-" . (sizeof($form_element["top"]) + 2) . "'>";

                    $isCheckbox = $form_element['multi-select'] === 'on';

                    $topOptions = "";
                    foreach ($form_element["top"] as $element) {
                        $topOptions .= "
                            <div class='pb-3'>"
                            . $element['label'] .
                            "</div>
                        ";
                    }
                    $topOptions = "
                        <div class='grid grid-cols-"
                        . (sizeof($form_element["top"]) + 2) .
                        "'>
                            <div class='col-span-2'></div>
                            $topOptions
                        </div>
                    ";

                    $bodyOptions = "";
                    for ($j = 0; $j < sizeof($form_element["left"]); $j++) {
                        $row = "";
                        for ($k = 0; $k < sizeof($form_element["top"]); $k++) {
                            $inputValue = $form_element["left"][$j]["value"]
                                . "--" .
                                $form_element["top"][$k]["value"];
                            $classlist = "";
                            $inputType = ($isCheckbox) ? 'checkbox' : 'radio';

                            if ($isCheckbox) {
                                $classlist = implode(" ", [
                                    "leform-checkbox",
                                    "leform-checkbox-fa-check",
                                    "leform-checkbox-medium",
                                ]);
                            } else {
                                $classlist = implode(" ", [
                                    "leform-radio",
                                    "leform-radio-fa-check",
                                    "leform-radio-medium",
                                ]);
                            }

                            $row .= "
                                <div class='leform-cr-box pb-2'>
                                    <input
                                        class='$classlist'
                                        type='$inputType'
                                        name='value[]'
                                        id='$inputValue'
                                        value='$inputValue'
                                        " . (in_array($inputValue, (array)$_value)
                                ? ' checked="checked"'
                                : ''
                            ) . "
                                    />
                                    <label for='$inputValue'></label>
                                </div>
                            ";
                        }
                        $total++;
                        $bodyOptions .= "
                            <form class='grid grid-cols-"
                            . (sizeof($form_element["top"]) + 2) .
                            "'>
                                <div class='col-span-2'>
                                    " . $form_element['left'][$j]['label'] . "
                                </div>
                                $row
                            </form>
                        ";
                    }

                    $html = $topOptions . $bodyOptions;
                    if ($total > 10) {
                        $html = "
                            <div class='leform-record-field-editor-scrollbox'>
                                $html
                            </div>
                        ";
                    }
                    break;

                case "star-rating":
                    $options = "";
                    $id = $this->leform->random_string(16);
                    for ($j = $form_element['total-stars']; $j > 0; $j--) {
                        $options .= "<input type='radio' name='value' id='" . $id . "-" . $j . "' value='" . $j . "'" . ($_value == $j ? " checked='checked'" : "") . " /><label for='" . $id . "-" . $j . "'></label>";
                    }
                    $html .= "<form><fieldset class='leform-star-rating'>" . $options . "</fieldset></form>";
                    break;
                case "repeater-input":
                    $head = "";
                    $body = "";

                    foreach ($form_element['fields'] as $field) {
                        if ($this->leform->shouldHideRepeaterFieldInEntries($field)) {
                            continue;
                        }
                        $head .= "
                            <td class='border-2 border-gray-400 px-2 py-1'>
                                {$field["name"]}
                            </td>
                        ";
                    }
                    $head = "
                        <thead>
                            <tr>
                                <td class='border-2 border-gray-400 px-2 py-1'></td>
                                $head
                            </tr>
                        </thead>
                    ";
                    if (is_array($_value)) {
                        for ($rowIndex = 0; $rowIndex < count($_value); $rowIndex++) {
                            $row = "";
                            foreach ($form_element["fields"] as $fieldIndex => $field) {
                                if (
                                    $this->leform->shouldHideRepeaterFieldInEntries(
                                        $form_element["fields"][$fieldIndex]
                                    )
                                ) {
                                    continue;
                                }

                                $fieldValue = "";
                                if (
                                    array_key_exists($rowIndex, $_value)
                                    && array_key_exists($fieldIndex, $_value[$rowIndex])
                                ) {
                                    $fieldValue = $_value[$rowIndex][$fieldIndex];
                                }

                                $cell = "";

                                $inputName = "value[$rowIndex][$fieldIndex]";
                                switch ($form_element["fields"][$fieldIndex]["type"]) {
                                    case "text":
                                    case "password":
                                    case "number":
                                        $cell = "
                                            <input
                                                name='$inputName'
                                                type='text'
                                                value='" . $fieldValue . "'
                                            />
                                        ";
                                        break;
                                    case "email":
                                        $cell = "
                                            <input
                                                name='$inputName'
                                                type='email'
                                                value='" . $fieldValue . "'
                                            />
                                        ";
                                        break;
                                    case "star-rating":
                                        $starRatingOptions = "";
                                        $starCount = $form_element['fields'][$fieldIndex]['starCount'];
                                        for ($starIndex = 0; $starIndex <= $starCount; $starIndex++) {
                                            $starRatingOptions .= "
                                                <option " . (($starIndex === intval($fieldValue))
                                                ? "selected"
                                                : ""
                                            ) . ">" .
                                                ($starIndex)
                                                . "</option>
                                            ";
                                        }
                                        $cell = "
                                            <select name='$inputName'>
                                                $starRatingOptions
                                            </select>
                                        ";
                                        break;
                                    case "select":
                                        $selectOptions = "";
                                        $form_element['fields'][$fieldIndex]["options"] = FormService::getExternalValuesAsArray($form_element['fields'][$fieldIndex]);
                                        foreach ($form_element['fields'][$fieldIndex]['options'] as $option) {
                                            $selectOptions .= "
                                                <option " . (($fieldValue === $option) ? "selected" : "") . ">
                                                    $option
                                                </option>
                                            ";
                                        }
                                        $cell = "
                                            <select name='$inputName'>
                                                <option selected value=''></option>
                                                $selectOptions
                                            </select>
                                        ";
                                        break;
                                    case "date":
                                        $cell = "
                                            <input
                                                name='$inputName'
                                                type='date'
                                                value='" . $fieldValue . "'
                                            />
                                        ";
                                        break;
                                    case "time":
                                        $cell = "
                                            <input
                                                name='$inputName'
                                                type='time'
                                                value='" . $fieldValue . "'
                                            />
                                        ";
                                        break;
                                    case "rangeslider":
                                        $cell = "
                                            <input
                                                name='$inputName'
                                                type='number'
                                                value='" . $fieldValue . "'
                                                min='" . $form_element["fields"][$fieldIndex]["min"] . "'
                                                max='" . $form_element["fields"][$fieldIndex]["max"] . "'
                                            />
                                        ";
                                        break;
                                    default:
                                        break;
                                }

                                $cell = "<td class='border-2 border-gray-400 px-2 py-1'>$cell</td>";
                                $row .= $cell;
                            }
                            $row = "
                                <tr>
                                    <td class='border-2 border-gray-400 px-2 py-1'>" . ($rowIndex + 1) . "</td>
                                    $row
                                </tr>
                            ";
                            $body .= $row;
                        }
                    }
                    $body = "<tbody>$body</tbody>";

                    $html .= "
                        <form>
                            <table>
                                $head
                                $body
                            </table>
                        </form>
                    ";
                    break;
                case "iban-input":
                    $html .= "
                        <form>
                            ". LeformService::renderIbanInput($form_element, $_value, false, ["user_iban" => 3]) ."
                        </form>
                    ";
                    break;
                case 'file':
                case 'signature':
                default:
                    return ['status' => 'ERROR', 'message' => 'This field can not be edited.'];
                    break;
            }
        } else {
            $html = '<input type="text" value="' . $_value . '" name="value" />';
        }
        return ['status' => 'OK', 'html' => $html];
    }

    static function shortcode_handler($_atts)
    {
        if (!isset($_atts['id']) || empty($_atts['id'])) {
            return '';
        }
        $preview = false;
        if (array_key_exists('preview', $_atts) && $_atts['preview'] == true) {
            $preview = true;
        }

        $form_object = new LeformFormService(intval($_atts['id']), $preview);

        if (
            array_key_exists('xd', $_atts)
            && $_atts['xd'] === true
            && $form_object->form_options['cross-domain'] !== 'on'
        ) {
            return '<div class="leform-xd-forbidden">' . 'Cross-domain calls are not allowed for this form</div>';
        }

        if (array_key_exists("predefinedValues", $_atts)) {
            $form = $form_object->get_form_html($_atts["predefinedValues"]);
        } else {
            $form = $form_object->get_form_html();
        }

        if ($form === false) {
            return '';
        }
        if (!empty($form_object->form_options['max-width-value'])) {
            $style_attr = ' style="max-width:' . $form_object->form_options['max-width-value'] . $form_object->form_options['max-width-unit'] . ';margin: 0 ' . (($form_object->form_options['max-width-position'] == 'center' || $form_object->form_options['max-width-position'] == 'left') ? 'auto' : '0') . ' 0 ' . (($form_object->form_options['max-width-position'] == 'center' || $form_object->form_options['max-width-position'] == 'right') ? 'auto' : '0') . ';"';
        } else {
            $style_attr = '';
        }
        $dl = '';
        /* 
        if (
            array_key_exists('dl', $_atts)
            && $_atts['dl'] == 'on'
            && defined("LEFORM_ALLOW_FORM_EXPORT')
            && LEFORM_ALLOW_FORM_EXPORT === true
        ) {
            $dl = '<div class="leform-dl"'.$style_attr.'><a href="'.rtrim(get_bloginfo('url'), '/').'/?leform-dl='.intval($_atts['id']).'" rel="nofollow"><i class="fas fa-download"></i> '.'Download Form'.'</a></div>';
        } else {
            $dl = '';
        }
         */

        if ($preview) {
            return $form['style']
                . $dl
                . '<div class="preview-of-form" style="margin-top: 80px;">'
                . '<div class="leform-inline leform-container"'
                . $style_attr
                . '>'
                . $form['html']
                . '</div>'
                . '</div>';
        } else {
            return $form['style']
                . $dl
                . '<div class="leform-inline leform-container"'
                . $style_attr
                . '>'
                . $form['html']
                . '</div>';
        }
    }

    public static function getElementByField($elements, $field, $value)
    {
        foreach ($elements as $element) {
            if (
                array_key_exists($field, $element)
                && $element[$field] === $value
            ) {
                return $element;
            }
        }
    }

    public static function getFieldByName($elements, $name)
    {
        foreach ($elements as $element) {
            $element = json_decode(json_encode($element), true);
            if (
                $element["type"] === "columns"
                && array_key_exists("properties", $element)
            ) {
                return self::getFieldByName(
                    $element["properties"]["elements"],
                    $name
                );
            } else if (isset($element["name"]) && $element["name"] === $name) {
                return $element;
            }
        }
    }

    public static function getValueForVariableField($variable, $elements, $values, $options)
    {
        $variable = str_replace("form_", "", $variable);
        $element = LeformFormService::getFieldByName($elements, $variable);


        if ($element !== null && array_key_exists($element["id"], $values)) {

            switch ($element['type']) {
                case 'date':
                    return LeformFormService::getDateValue($options, (object) $element, $values[$element["id"]]);
                case 'number':
                    return !empty($values[$element["id"]]) ? number_format($values[$element["id"]], 2, ".", "") : "";
                default:
                    $value = $values[$element["id"]];
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
                    return $value;
            }
        } else {
            return "";
        }
    }

    public static function replaceFormValues($string, $values, $elements, $options)
    {
        return preg_replace_callback(
            "/{{(.+?)}}/",
            function ($matches) use ($values, $elements, $options) {
                $variable = $matches[1];
                if (str_starts_with($variable, "form_")) {
                    $value = LeformFormService::getValueForVariableField(
                        $variable,
                        $elements,
                        $values,
                        $options
                    );
                    if (!is_array($value)) {
                        return $value;
                    }
                }
                return $matches[0];
            },
            $string
        );
    }

    public static function getFormElements($form)
    {
        $elements = [];
        $form_elements = [];
        switch (gettype($form["elements"])) {
            case "array": {
                    $form_elements = $form["elements"];
                    break;
                }
            case "object": {
                    $form_elements = (array) $form["elements"];
                    break;
                }
            default:
            case "string": {
                    $form_elements = json_decode($form["elements"], true);
                    break;
                }
        }
        foreach ($form_elements as $element) {
            switch (gettype($element)) {
                case "array": {
                        $elements[] = $element;
                        break;
                    }
                case "object": {
                        $elements[] = (array) $element;
                        break;
                    }
                default:
                case "string": {
                        $elements[] = json_decode($element, true);
                        break;
                    }
            }
        }
        return $elements;
    }

    public static function getDateValue($formOptions, $element, $value = "")
    {
        if (
            isset($element->{'xml-date-format'}) &&
            !empty($element->{'xml-date-format'}) &&
            !empty($value)
        ) {
            $value = date($element->{'xml-date-format'}, strtotime($value));
        } else if (
            isset($formOptions['xml-date-format']) &&
            !empty($formOptions['xml-date-format']) &&
            !empty($value)
        ) {
            $value = date($formOptions['xml-date-format'], strtotime($value));
        }

        return $value;
    }
}
