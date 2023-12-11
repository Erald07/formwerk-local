<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

use App\Models\Role;
use Illuminate\Support\Facades\DB;

class ClerkRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        $companies = \App\Models\Company::all();
        $companies->each(function ($company) {
            $clerk = new Role();
            $clerk->company_id = $company->id;
            $clerk->name = 'clerk';
            $clerk->display_name = 'Clerk';
            $clerk->description = 'Company Clerk';
            $clerk->save();
        });
    }
}