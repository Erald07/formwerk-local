<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

use App\Models\Role;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('roles')->delete();
        $companies = \App\Models\Company::all();
        $users = \App\Models\User::all();
        $companies->each(function ($company){
            $admin = new Role();
            $admin->company_id   = $company->id;
            $admin->name = 'administrator';
            $admin->display_name   = 'Administrator';
            $admin->description = 'Company Admin';
            $admin->save();

            $employee = new Role();
            $employee->company_id   = $company->id;
            $employee->name = 'employee';
            $employee->display_name = 'Employee';
            $employee->description = 'Company Employee';
            $employee->save();
        });
    }
}
