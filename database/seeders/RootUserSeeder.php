<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\Role;
use App\Models\Company;

class RootUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $demoCompany = Company::where('company_email', 'demo@example.com')->first();
        $codeviderCompany = Company::where('company_email', 'pasho@codevider.com')->first();

        DB::table('users')->insert([
            'name' => 'Demo',
            'email' => 'demo@example.com',
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'remember_token' => Str::random(10),
            'created_at' => now(),
            'updated_at' => now(),
            'company_id' => $demoCompany->id,
        ]);

        DB::table('users')->insert([
            'name' => 'Pasho Toska',
            'email' => 'pasho@codevider.com',
            'email_verified_at' => now(),
            'password' => Hash::make('123456'),
            'remember_token' => Str::random(10),
            'created_at' => now(),
            'updated_at' => now(),
            'company_id' => $codeviderCompany->id,
        ]);
    }
}
