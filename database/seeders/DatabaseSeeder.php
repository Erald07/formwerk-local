<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            CompanySeeder::class,
            RootUserSeeder::class,
            RoleSeeder::class,
            ClerkRoleSeeder::class,
            WebfontSeeder::class,
            RecordCompanyId::class,
        ]);
    }
}
