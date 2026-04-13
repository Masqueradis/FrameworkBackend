<?php

declare(strict_types=1);

namespace Feature\Repositories;

use App\Models\Category;
use App\Repositories\CategoryRepository;
use App\ValueObjects\CategoryId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class CategoryRepositoryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function itFindsCategoryById(): void
    {
        $category = Category::factory()->create();
        $repository = new CategoryRepository();

        $foundCategory = $repository->findById(new CategoryId($category->id));

        $this->assertNotNull($foundCategory);
        $this->assertEquals($category->id, $foundCategory->id);
    }

    #[Test]
    public function itReturnsNullIfCategoryNotFound(): void
    {
        $repository = new CategoryRepository();

        $foundCategory = $repository->findById(new CategoryId(1));

        $this->assertNull($foundCategory);
    }
}
