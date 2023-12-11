<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\Webfont;
use App\Models\Company;
use App\Service\WebfontService;

class WebfontSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $webfontService = new WebfontService();
        $demoCompany = Company::where('company_email', 'demo@example.com')->first();
        
        foreach ($webfontService->webfonts['items'] as $font) {
            if (!empty($font['family'])) {
                $variants = '';
                if (!empty($font['variants']) && is_array($font['variants'])) {
                    foreach ($font['variants'] as $key => $var) {
                        if ($var == 'regular') {
                            $font['variants'][$key] = '400';
                        }
                        if ($var == 'italic') {
                            $font['variants'][$key] = '400italic';
                        }
                    }
                    $variants = implode(",", $font['variants']);
                }

                $subsets = '';
                if (!empty($font['subsets']) && is_array($font['subsets'])) {
                    $subsets = implode(",", $font['subsets']);
                }

                Webfont::create([
                    'family' => $font['family'],
                    'variants' => $variants,
                    'subsets' => $subsets,
                    'source' => 'google',
                    'deleted' => 0,
                    'company_id' => $demoCompany->id,
                ]);
            }
        }
    }
}
