<?php

namespace Database\Factories;

use App\Models\Record;
use Illuminate\Database\Eloquent\Factories\Factory;

class RecordFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Record::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'personal_data_keys' => '[]',
            'unique_keys' => '[]',
            'fields' => '[]',
            'info' => '[]',
            'status' => 1,
            'str_id' => $this->faker->randomNumber(8, true),
            'gateway_id' => $this->faker->randomNumber(4, true),
            'amount' => $this->faker->randomFloat(2, 10, 99),
            'currency' => $this->faker->lexify('US-??'),
        ];
    }
}
