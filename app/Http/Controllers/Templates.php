<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Form;
use Faker\Factory as Faker;

class Templates extends Controller
{
    public function index() {
        $forms = Form::withoutGlobalScope("company")
            ->where("shareable", 1)
            ->leftJoin('companies', 'company_id', '=', 'companies.id')
            ->select('laravel_uap_leform_forms.*', 'companies.company_name')
            ->get();

        return view("templates", [
            "forms" => $forms,
            "frontendTranslations" => __("frontend_translations")
        ]);
    }

    public function copy(Request $request) {
        $user = $request->user();
        $form_id = null;
        if ($request->has('form-id')) {
            $form_id = intval($request->input('form-id'));
            $form_details = Form::withoutGlobalScope("company")
                ->where("shareable", 1)
                ->where('deleted', 0)
                ->where('id', $form_id)
                ->first();
            if (empty($form_details)) {
                $form_id = null;
            }
        }
        if (empty($form_id)) {
            $return_data = [
                'status' => 'ERROR',
                'message' => __('Requested form not found.'),
            ];
            return $return_data;
        }
        $currentTime = time();
        $newForm = Form::create([
            'name' => $form_details['name'],
            'options' => $form_details['options'],
            'pages' => $form_details['pages'],
            'elements' => $form_details['elements'],
            'active' => $form_details['active'],
            'created' => $currentTime,
            'modified' => $currentTime,
            'deleted' => 0,
            'user_id' => $user->id,
            'company_id' => $user->company_id,
            'short_link' => Faker::create()->bothify('???##'),
            'share_date' => null,
            'share_form_id' => $form_details['id'],
            'share_user_id' => $form_details['user_id'],
            'share_company_id' => $form_details['company_id']
        ]);

        $return_data = [
            'status' => 'OK',
            'message' => __('The form successfully duplicated.'),
            'id' => $newForm->id
        ];

        return $return_data;
    }
}
