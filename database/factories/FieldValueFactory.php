<?php

namespace Database\Factories;

use App\Models\FieldValue;
use Illuminate\Database\Eloquent\Factories\Factory;

class FieldValueFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = FieldValue::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'value' => $this->faker->bothify('?????-#####'),
            'datestamp' => $this->faker->randomNumber(8, true),
            'record_id' => $this->faker->randomNumber(3, false),
            'field_id' => $this->faker->randomNumber(3, false), 
        ];
    }
}
