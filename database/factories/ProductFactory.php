<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Product::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $categories = [
            'Eletrônicos',
            'Livros',
            'Roupas',
            'Alimentos',
            'Móveis',
            'Esportes',
            'Brinquedos',
            'Beleza',
            'Automotivo',
            'Ferramentas'
        ];

        $productNames = [
            'Smartphone',
            'Notebook',
            'Tablet',
            'Fone de Ouvido',
            'Mouse',
            'Teclado',
            'Monitor',
            'Câmera',
            'Livro',
            'Camiseta',
            'Calça',
            'Tênis',
            'Relógio',
            'Mochila',
            'Cadeira',
            'Mesa',
            'Luminária',
            'Bola',
            'Raquete',
            'Boneco'
        ];

        return [
            'sku' => strtoupper($this->faker->unique()->bothify('???-####')),
            'name' => $this->faker->randomElement($productNames) . ' ' . $this->faker->word(),
            'description' => $this->faker->sentence(10),
            'price' => $this->faker->randomFloat(2, 10, 5000),
            'category' => $this->faker->randomElement($categories),
            'status' => $this->faker->randomElement(['active', 'active', 'active', 'inactive']),
        ];
    }

    /**
     * Indicate that the product is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
    }

    /**
     * Indicate that the product is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'inactive',
        ]);
    }

    /**
     * Indicate that the product is in electronics category.
     */
    public function electronics(): static
    {
        return $this->state(fn (array $attributes) => [
            'category' => 'Eletrônicos',
            'name' => $this->faker->randomElement([
                'Smartphone Samsung',
                'Notebook Dell',
                'Tablet Apple',
                'Fone de Ouvido Sony',
                'Mouse Logitech',
                'Teclado Mecânico',
                'Monitor LG',
                'Câmera Canon'
            ]),
        ]);
    }
}
