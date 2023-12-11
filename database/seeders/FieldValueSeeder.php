<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\FieldValue;
use App\Models\Form;
use App\Models\User;
use Faker\Factory as Faker;

class FieldValueSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $faker = Faker::create();
        $user = User::where('name', 'root')->first();
        $form = new Form;
        $form->name = $faker->numerify('form-with-values-############');
        $form->options = '{}';
        $form->pages = '[]';
        $form->elements = '[]';
        $form->user_id = $user->id;
        $form->save();

        FieldValue::factory(5)->create(['form_id' => $form->id]);
    }
}
