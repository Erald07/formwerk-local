<?php

namespace Database\Factories;

use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

class TransactionFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Transaction::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'provider' => $this->faker->lexify('?????'),
            'payer_name' => $this->faker->name(),
            'payer_email' => $this->faker->email(),
            'gross' => $this->faker->randomFloat(2, 10, 29),
            'currency' => $this->faker->lexify('US-??'),
            'payment_status' => 'finished',
            'transaction_type' => $this->faker->lexify('?????'),
            'txn_id' => $this->faker->numerify(str_repeat('#', 12)),
            'record_id' => $this->faker->randomNumber(3, true), # gonna be turned to foreign keys most likely
        ];
    }
}
