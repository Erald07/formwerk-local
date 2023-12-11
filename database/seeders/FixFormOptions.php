<?php

namespace Database\Seeders;

use App\Models\Form;
use Illuminate\Database\Seeder;

class FixFormOptions extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        //
        $forms = Form::withoutGlobalScope('deleted')->get();
        foreach ($forms as $form) {
            $options = json_decode($form->options, true);
            $options['show-on-api']= 'on';
            $form->options = json_encode($options);
            $form->save();
        }
    }
}
