<?php

namespace Database\Factories;

use App\Models\Style;
use Illuminate\Database\Eloquent\Factories\Factory;

class StyleFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Style::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'name' => $this->faker->lexify('?????'),
            'options' => '{}',
            'user_id' => $this->faker->randomNumber(3, false), # gonna be turned to foreign keys most likely
        ];
    }
}
