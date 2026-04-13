<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
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
        $attributeTemplates = [
            [
                'GPU' => fake()->randomElement(['RTX 4090', 'RTX 4080', 'RTX 4070', 'RTX 4060']),
                'VRAM' => fake()->randomElement(['8 GB', '6 GB', '4 GB', '3 GB', '2 GB', '1 GB']),
                'Memory Type' => fake()->randomElement(['GDDR6', 'GDDR6X']),
                'TDP' => fake()->numberBetween(100, 450),
            ],
            [
                'Form Factor' => fake()->randomElement(['ATX', 'Micro-ATX']),
                'Color' => fake()->randomElement(['Red', 'Green', 'Blue']),
                'Material' => fake()->randomElement(['Aluminum', 'Steel']),
            ],
            [
                'CPU' => fake()->randomElement(['Ryzen 7', 'Ryzen 5', 'i5', 'i7']),
                'RAM type' => fake()->randomElement(['DDR4', 'DDR5']),
                'Frequency' => fake()->randomElement(['2,1', '4,3', '5,1'])
            ],
        ];

        return [
            'user_id' => User::factory(),
            'category_id' => Category::factory(),
            'name' => fake()->words(3, true),
            'slug' => fake()->slug(),
            'sku' => fake()->unique()->bothify('SKU-####-????'),
            'description' => fake()->sentence(),
            'price' => fake()->randomFloat(2, 100, 15000),
            'stock' => fake()->numberBetween(0, 100),
            'available' => fake()->boolean(70),
            'attributes' => fake()->randomElement($attributeTemplates),
        ];
    }
}
