<?php

namespace Database\Factories;

use App\Models\District;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Client>
 */
class ClientFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tipoDoc' => 6,
            'numDoc' => $this->faker->randomElement([10, 20]) . $this->faker->randomNumber(9),
            'rznSocial' => $this->faker->company,
            'direccion' => $this->faker->address,
            'branch_id' => 1,
            'company_id' => 1
        ];
    }
}
