<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Response;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CatalogTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function testCatalogIndexLoadsWithFilters(): void
    {
        $parentCategory = Category::factory()->create();
        $childCategory = Category::factory()->create(['parent_id' => $parentCategory->id]);

        Product::factory()->create([
            'category_id' => $parentCategory->id,
            'price' => 100,
            'available' => true,
            'attributes' => ['RAM' => 'DDR5', 'Capacity' => '8 GB'],
        ]);

        Product::factory()->create([
            'category_id' => $childCategory->id,
            'price' => 500,
            'available' => true,
            'attributes' => ['RAM' => 'DDR4', 'Capacity' => '16 GB'],
        ]);

        $response = $this->get("/catalog?category_id={$parentCategory->id}");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertViewHas('products');
        $response->assertViewHas('categories');
        $response->assertViewHas('filtersData');

        $filtersData = $response->original->getData()['filtersData'];
        $this->assertEquals(100, $filtersData['min_price']);
        $this->assertEquals(500, $filtersData['max_price']);
    }
}
