<?php

namespace Database\Factories;

use App\Models\Upload;
use Illuminate\Database\Eloquent\Factories\Factory;

class UploadFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Upload::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'record_id' => $this->faker->randomNumber(3, true), # gonna be turned to foreign keys most likely
            'form_id' => $this->faker->randomNumber(3, true), # gonna be turned to foreign keys most likely
            'element_id' => $this->faker->randomNumber(3, true), # gonna be turned to foreign keys most likely
            'upload_id' => $this->faker->numerify(str_repeat('#', 12)),
            'str_id' => $this->faker->numerify(str_repeat('#', 12)),
            'status' => 0,
            'message' => $this->faker->paragraph(),
            'filename' => $this->faker->lexify(str_repeat('?', 8) . '.???'),
            'filename_original' => $this->faker->lexify(str_repeat('?', 8) . '.???'),
        ];
    }
}
