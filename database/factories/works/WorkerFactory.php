<?php

namespace Database\Factories\works;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\works\Worker>
 */
class WorkerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->name,
            'tipoDoc' => $this->faker->randomElement([1, 2]),
            'numDoc' => $this->faker->randomNumber(8),
            'email' => $this->faker->unique()->safeEmail,
            'cargo' => fake()->randomElement(['Reparador', 'Inspector', 'Asistente', 'Despachador', 'Checker', 'Soldador']),
            'branch_id' => 1
        ];
    }
}
