<?php
if (!function_exists('checkRole')) {
    function checkRole($role)
    {
        $user = auth()->user();
        if ($user) {
            $u = \App\Models\Role::join('role_user', 'role_user.role_id', '=', 'roles.id')
                ->select('roles.id', 'roles.name')
                ->where('role_user.user_id', '=', $user->id)
                ->where('roles.name', '=', $role)
                ->first();
            if ($u) {
                return true;
            }
        }
        return false;
    }
}
if (!function_exists('company')) {
    function company()
    {
        if (auth()->user()) {
            $company = \App\Models\Company::find(auth()->user()->company_id);
            return $company;
        }

        return false;
    }
}
if (!function_exists('get_company_by_token')) {
    function get_company_by_token($token)
    {
        if ($token) {
            $apiToken = \App\Models\AccessToken::where('token', $token)->withoutGlobalScopes(['company'])->first();
            if ($apiToken) {
                $company = \App\Models\Company::find($apiToken->company_id);
                return $company;
            }
        }

        return false;
    }
}
if (!function_exists('replaceWithPredefinedValues')) {
    function replaceWithPredefinedValues($string, $predefinedValues = [])
    {
        if ($predefinedValues === null) {
            $string = preg_replace('/\{\{[A-z1-9-_]*\}\}/', '', $string);
            return $string;
        }
        $allowedTypes = ["boolean", "integer", "double", "string"];
        $allVariables = evo_get_all_variables($predefinedValues);
        foreach ($allVariables as $key => $value) {
            if (in_array(gettype($value), $allowedTypes)) {
                $string = preg_replace("/\{\{$key\}\}/", $value, $string);
            }
        }
        $string = preg_replace('/\{\{[A-z1-9-_]*\}\}/', '', $string);
        return $string;
    }
}

if (!function_exists('getSimplifiedElement')) {
    function getSimplifiedElement($element)
    {
        $element = json_decode(json_encode($element), true);
        $fields = ['id', 'name', 'type'];
        $simplified = [];
        foreach ($fields as $field) {
            $simplified[$field] = $element[$field];
        }
        if ($element["type"] == "columns") {
            $simplified["elements"] = array_map("getSimplifiedElement", $element["properties"]["elements"]);
        }
        return $simplified;
    }
}

if (!function_exists('printElement')) {
    function printElement($element, $extraFields = [])
    {
        $baseFields = ['id', 'name', 'type'];
        $print = [];
        foreach ($baseFields as $baseField) {
            if (is_object($element)) {
                $print[$baseField] = $element->{$baseField};
            } else if (is_array($element)) {
                $print[$baseField] = $element[$baseField];
            }
        }
        foreach ($extraFields as $extraField) {
            if (is_object($element) && property_exists($element, $extraField)) {
                $print[$extraField] = $element->{$extraField};
            } else if (is_array($element) && array_key_exists($extraField, $element)) {
                $print[$extraField] = $element[$extraField];
            }
        }
        return $print;
    }
}

if (!function_exists('getElementClasses')) {
    function getElementClasses($e)
    {
        $em = (array) $e;
        $properties = isset($e->properties) ? (array) $e->properties : [];
        $class = 'leform-element leform-element-' . $e->id;
        if (isset($properties['label-style-position']) && $properties['label-style-position'] != '') {
            $class .= ' leform-element-label-' . $properties['label-style-position'];
        }
        if (isset($em['description-style-position']) && $em['description-style-position'] != '') {
            $class .= ' leform-element-description-' . $em['description-style-position'];
        }
        if (isset($em['css-class']) && $em['css-class'] != '') {
            $class .= " " . $em['css-class'];
        }
        return $class;
    }
}

if (!function_exists('evo_replace_system_variables')) {
    function evo_replace_system_variables($valueToCompare, $systemVariables = [])
    {
        if (is_string($valueToCompare)) {
            foreach ($systemVariables as $key => $systemValue) {
                if (is_string($systemValue)) {
                    $valueToCompare = str_replace($key, is_array($systemValue) ? implode(", ", $systemValue) : $systemValue, $valueToCompare);
                }
            }
        }

        return $valueToCompare;
    }
}
if (!function_exists('evo_is_element_visible')) {
    function evo_is_element_visible($_element_id, $form_logic, $form_element, $rawElements, $record)
    {
        $predefinedValues = $record["predefined_values"]
            ? json_decode($record["predefined_values"], true)
            : [];
        $systemVariables = $record["system_variables"]
            ? json_decode($record["predefined_values"], true)
            : [];
        $formVariables = [];
        foreach ($rawElements as $data) {
            if (isset($data['value']) && isset($data['name'])) {
                $name = $data['name'];
                $formVariables["{{form_" . $name . "}}"] = $data["value"];
            }
        }
        if (!is_array($predefinedValues)) {
            $predefinedValues = [];
        }
        if (!is_array($systemVariables)) {
            $systemVariables = [];
        }
        $getVariables = [];
        if (isset($predefinedValues['__get_params']) && is_array($predefinedValues['__get_params'])) {
            $getVariables = $predefinedValues['__get_params'];
            unset($predefinedValues['__get_params']);
        }
        $variables = array_merge($predefinedValues, $systemVariables, $formVariables, $getVariables);
        $logic_rules = [];
        if (array_key_exists($_element_id, $form_logic) && isset($form_logic[$_element_id]['rules']) && is_array($form_logic[$_element_id]['rules'])) {
            for ($i = 0; $i < sizeof($form_logic[$_element_id]['rules']); $i++) {
                $field_ind = $form_logic[$_element_id]['rules'][$i]['field'];
                $field_data = isset($rawElements[$field_ind]) ? ((array)$rawElements[$field_ind]) : [];
                if (is_array($field_data) && isset($field_data['value']) && !empty($field_data['value'])) {
                    $field_values = $field_data['value'];
                    if (!is_array($field_values)) {
                        $field_values = [$field_values];
                    }
                    $bool_value = false;

                    $valueToCompare = $form_logic[$_element_id]['rules'][$i]['token'];
                    $valueToCompare = evo_replace_system_variables($valueToCompare, $variables);

                    switch ($form_logic[$_element_id]['rules'][$i]['rule']) {
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
                } else {
                    $logic_rules[] = false;
                }
            }
            $bool_value = false;
            if ($form_logic[$_element_id]['operator'] == "and") {
                if (!in_array(false, $logic_rules)) $bool_value = true;
            } else {
                if (in_array(true, $logic_rules)) $bool_value = true;
            }
            if ($form_logic[$_element_id]['action'] == 'hide') $bool_value = !$bool_value;

            if (!$bool_value) return false;
        } else $bool_value = true;
        $form_element = json_decode(json_encode($form_element), true);
        if ($form_element["id"] === $_element_id && array_key_exists("_parent", $form_element) && isset($rawElements[$form_element["_parent"]])) {
            $bool_value = $bool_value && evo_is_element_visible(
                $form_element["_parent"],
                $form_logic,
                $rawElements[$form_element["_parent"]],
                $rawElements,
                $record
            );
        }

        return $bool_value;
    }
}

if (!function_exists('evo_get_external_select_datasource')) {
    function evo_get_external_select_datasource($url, $path, $oldOptions)
    {
        try {
            //  Initiate curl
            $ch = curl_init();
            // Will return the response, if false it print the response
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            // Set the url
            curl_setopt($ch, CURLOPT_URL, $url);
            // Execute
            $result = curl_exec($ch);
            // Closing
            curl_close($ch);
            $result = json_decode($result, true);
            if (!empty($path)) {
                $paths = explode('.', $path);
                foreach ($paths as $p) {
                    if (isset($result[$p]) && is_array($result[$p])) {
                        $result = $result[$p];
                    }
                }
            }
            $options = [];
            if (is_array($result)) {
                foreach ($result as $value => $label) {
                    if (is_string($label)) {
                        $options[] = [
                            "default" => "off",
                            "label" => $label,
                            "value" => "$value",
                        ];
                    }
                }
            }
            return count($options) > 0 ? $options : $oldOptions;
        } catch (Exception $e) {
            return $oldOptions;
        }
    }
}

if (!function_exists('evo_get_external_text_datasource')) {
    function evo_get_external_text_datasource($url, $path, $oldData)
    {
        try {
            //  Initiate curl
            $ch = curl_init();
            // Will return the response, if false it print the response
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            // curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            // curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            // Set the url
            curl_setopt($ch, CURLOPT_URL, $url);
            // dd($url);
            // Execute
            $result = curl_exec($ch);
            // Closing
            curl_close($ch);
            $data = '';
            if (!empty($path)) {
                $result = json_decode($result, true);
                $paths = explode('.', $path);
                foreach ($paths as $p) {
                    if (isset($result[$p])) {
                        $result = $result[$p];
                    }
                }
                $data = $result;
            } else {
                $data = $result;
            }
            if(!empty($data) && is_string($data)) {
                return $data;
            }
            return $oldData;
        } catch (Exception $e) {
            return $oldData;
        }
    }
}

if (!function_exists('evo_get_variables')) {
    function evo_get_variables($predefinedValues)
    {
        if (!is_array($predefinedValues)) {
            $predefinedValues = [];
        }

        $getVariables = [];
        if (isset($predefinedValues['__get_params']) && is_array($predefinedValues['__get_params'])) {
            $getVariables = $predefinedValues['__get_params'];
            unset($predefinedValues['__get_params']);
        }

        return [$predefinedValues, $getVariables];
    }
}
if (!function_exists('evo_get_all_variables')) {
    function evo_get_all_variables($predefinedValues)
    {
        list($pv, $gv) = evo_get_variables($predefinedValues);
        $allVaribles = array_merge($pv, $gv);

        return $allVaribles;
    }
}