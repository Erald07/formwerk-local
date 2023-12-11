<?php

namespace Database\Factories;

use App\Models\Webfont;
use Illuminate\Database\Eloquent\Factories\Factory;

class WebfontFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Webfont::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'family' => $this->faker->lexify(str_repeat('?', 8)),
            'variants' => $this->faker->lexify(str_repeat('?', 8)),
            'subsets' => $this->faker->lexify(str_repeat('?', 8)),
            'source' => $this->faker->lexify(str_repeat('?', 8)),
        ];
    }
}
