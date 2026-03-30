<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;
    #[Test]
    public function testCatGetPaginatedProducts(): void
    {
        $category = Category::factory()->create();
        Product::factory()->count(20)->create([
            'category_id' => $category->id,
            'available' => true,
        ]);

        $response = $this->getJson('/api/products');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'name',
                            'price',
                            'category' => ['id', 'name'],
                        ],
                    ],
                    'meta' => ['current_page', 'last_page', 'total'],
                ],
            ]);
        $this->assertCount(15, $response->json('data.data'));
    }

    #[Test]
    public function testCanFilterProductsByPrice(): void
    {
        $category = Category::factory()->create();

        Product::factory()->create(['price' => 100, 'category_id' => $category->id, 'available' => true]);
        Product::factory()->create(['price' => 5000, 'category_id' => $category->id, 'available' => true]);

        $response = $this->getJson('/api/products?min_price=1000');
        $response->assertStatus(Response::HTTP_OK);

        $this->assertCount(1, $response->json('data.data'));
        $this->assertEquals(5000, $response->json('data.data.0.price'));
    }
}
