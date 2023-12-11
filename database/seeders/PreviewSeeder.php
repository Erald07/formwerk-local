<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Preview;

class PreviewSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Preview::factory(5)->create();
    }
}
