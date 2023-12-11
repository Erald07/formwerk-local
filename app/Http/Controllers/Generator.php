<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Form;

class Generator extends Controller
{
    public function index() {
        $forms = Form::with([
            'records' => function ($query) {
                $query->where('deleted', 0);
            }
        ])
            ->leftJoin('companies', 'share_company_id', '=', 'companies.id')
            ->select('laravel_uap_leform_forms.*', 'companies.company_name')
            ->get();

        return view("generator", [
            "forms" => $forms,
            "frontendTranslations" => __("frontend_translations")
        ]);
    }
}
