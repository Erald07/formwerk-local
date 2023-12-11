<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Validation;

class ValidationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Validation::factory(5)->create();
    }
}
