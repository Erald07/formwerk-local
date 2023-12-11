<?php

namespace Database\Factories;

use App\Models\Preview;
use App\Models\Form;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PreviewFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Preview::class;

    /**
     * Configure the model factory.
     *
     * @return $this
     */
    public function configure()
    {
        return $this->afterCreating(function (Preview $preview) {
            $preview->form->preview_id = $preview->id;
            $preview->form->save();
        });
    }

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $user = User::where('name', 'root')->first();
        $form = new Form;
        $form->name = $this->faker->numerify('form-with-preview-############');
        $form->options = '{}';
        $form->pages = '[]';
        $form->elements = '[]';
        $form->user_id = $user->id;
        $form->save();

        return [
            'name' => $this->faker->words(3, true),
            'options' => '{}',
            'pages' => '[]',
            'elements' => '[]',
            'form_id' => $form->id,
        ];
    }
}
