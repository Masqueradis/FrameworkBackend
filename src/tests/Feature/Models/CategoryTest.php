<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_category_has_many_products(): void
    {
        $category = Category::factory()->create();

        $product = Product::factory()->create([
            'category_id' => $category->id,
        ]);

        $this->assertTrue($category->products->contains('id', $product->id));

        $this->assertInstanceOf(
            HasMany::class,
            $category->products()
        );
    }
}
