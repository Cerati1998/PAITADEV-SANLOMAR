<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $code = $this->faker->unique()->randomNumber(5);

        return [
            'codProducto' => $code,
            'codBarras' => $code,
            'unidad' => 'NIU',
            'mtoValor' => $this->faker->randomElement([50, 60, 70, 80, 90, 100]),
            'tipAfeIgv' => '10',
            'porcentajeIgv' => 18,
            'descripcion' => $this->faker->sentence,
            'branch_id' => 1,
            'company_id' => 1,
        ];
    }
}
