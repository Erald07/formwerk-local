<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

use App\Models\Company;
use Illuminate\Support\Facades\DB;

class CompanySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('companies')->delete();
        DB::statement('ALTER TABLE companies AUTO_INCREMENT = 1');
        Company::create([
            'company_name' => 'Demo',
            'company_email' => 'demo@example.com',
            'company_phone' => '0000-000-000',
            'company_logo' => urlencode(Storage::url('favicon.png')),
            'company_favicon' => urlencode(Storage::url('favicon.png')),
        ]);

        Company::create([
            'company_name' => 'Codevider',
            'company_email' => 'pasho@codevider.com',
            'company_phone' => '0000-000-000',
            'company_logo' => urlencode(Storage::url('favicon.png')),
            'company_favicon' => urlencode(Storage::url('favicon.png')),
        ]);

    }
}
