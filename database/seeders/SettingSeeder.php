<?php

namespace Database\Seeders;

use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Seeder;

use App\Models\Setting;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $companies = \App\Models\Company::all();
        

        $companies->each(function ($company){
            $setting = new Setting();
            $setting->company_id = $company->id;
            $setting->name = 'smtp-host';
            $setting->value = '';
            $setting->save();

            $setting = new Setting();
            $setting->company_id = $company->id;
            $setting->name = 'smtp-username';
            $setting->value = '';
            $setting->save();

            $setting = new Setting();
            $setting->company_id = $company->id;
            $setting->name = 'smtp-password';
            $setting->value = '';
            $setting->save();

            $setting = new Setting();
            $setting->company_id = $company->id;
            $setting->name = 'smtp-protocol';
            $setting->value = 'tls';
            $setting->save();

            $setting = new Setting();
            $setting->company_id = $company->id;
            $setting->name = 'smtp-port';
            $setting->value = '';
            $setting->save();

            Setting::factory()->create([ 'name' => 'smtp-host', 'value' => '' ]);
            Setting::factory()->create([ 'name' => 'smtp-username', 'value' => '' ]);
            Setting::factory()->create([ 'name' => 'smtp-password', 'value' => '' ]);
            Setting::factory()->create([ 'name' => 'smtp-protocol', 'value' => 'tls' ]);
            Setting::factory()->create([ 'name' => 'smtp-port', 'value' => '587' ]);
        });
    }
}
