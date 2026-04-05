<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
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
        return [
            'category_id' => Category::factory(),
            'name' => fake()->word(3, true),
            'slug' => fake()->slug(),
            'sku' => fake()->unique()->bothify('SKU-####-????'),
            'description' => fake()->sentence(),
            'price' => fake()->randomFloat(2, 100, 15000),
            'stock' => fake()->numberBetween(0, 100),
            'available' => fake()->boolean(70),
            'attributes' => [
                'Brand' => fake()->randomElement(['Palit', 'MSI', 'Kingstone', 'Chieftec', 'Deepcool', 'Intel', 'AMD']),
                'Socket' => fake()->randomElement(['AM4', 'AM5', 'LGA1700', 'LGA1200']),
                'Memory Type' => fake()->randomElement(['DDR4', 'DDR5', 'GDDR6']),
                'Form Factor' => fake()->randomElement(['ATX', 'Micro-ATX', 'Mini-ITX']),
            ],
        ];
    }
}
