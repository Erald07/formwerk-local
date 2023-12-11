<?php

namespace Database\Factories;

use App\Models\Validation;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Factories\Factory;

class ValidationFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Validation::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'type' => $this->faker->lexify(str_repeat('?', 8)),
            'hash' => Hash::make(Str::random(10)),
            'created' => 1,
        ];
    }
}
