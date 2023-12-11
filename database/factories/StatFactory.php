<?php

namespace Database\Factories;

use App\Models\Stat;
use Illuminate\Database\Eloquent\Factories\Factory;

class StatFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Stat::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'form_id' => $this->faker->randomNumber(3, true), # gonna be turned to foreign keys most likely
            'impressions' => $this->faker->randomNumber(6, true),
            'submits' => $this->faker->randomNumber(6, true),
            'confirmed' => $this->faker->randomNumber(6, true),
            'payments' => $this->faker->randomNumber(6, true),
            'datestamp' => $this->faker->randomNumber(6, true),
            'timestamp' => $this->faker->randomNumber(6, true),
        ];
    }
}
