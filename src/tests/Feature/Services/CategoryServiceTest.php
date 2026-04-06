<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Data\CategorySaveData;
use App\Models\Category;
use App\Services\CategoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class CategoryServiceTest extends TestCase
{
    use RefreshDatabase;

    private CategoryService $categoryService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->categoryService = new CategoryService();
    }

    #[Test]
    public function testCanCreateCategory(): void
    {
        $data = new CategorySaveData(name: 'New Category', parent_id: null);

        $category = $this->categoryService->createCategory($data);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'New Category',
            'parent_id' => null,
        ]);

        $this->assertStringStartsWith('new-category', $category->slug);
    }

    #[Test]
    public function testCanUpdateCategory(): void
    {
        $category = Category::factory()->create(['name' => 'Old Category']);
        $parent = Category::factory()->create(['name' => 'Parent']);

        $data = new CategorySaveData(name: 'New Category', parent_id: $parent->id);

        $this->categoryService->updateCategory($category, $data);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'New Category',
            'parent_id' => $parent->id,
        ]);
    }

    public function testCanDeleteCategory(): void
    {
        $category = Category::factory()->create(['name' => 'Category']);

        $this->categoryService->deleteCategory($category);

        $this->assertSoftDeleted('categories', [
            'id' => $category->id,
        ]);
    }

    #[Test]
    public function testReturnsPaginatedCategoriesWithParent(): void
    {
        $parent = Category::factory()->create(['name' => 'Parent']);
        Category::factory()->create(['parent_id' => $parent->id]);

        $paginator = $this->categoryService->getPaginatedCategoriesWithParent(10);

        $this->assertInstanceOf(LengthAwarePaginator::class, $paginator);
        $this->assertEquals(2, $paginator->total());

        $this->assertTrue($paginator->first()->relationLoaded('parent'));
    }

    #[Test]
    public function testReturnsCategoriesForDropdownAndExclude(): void
    {
        $firstCategory = Category::factory()->create(['name' => 'First Category']);
        $secondCategory = Category::factory()->create(['name' => 'Second Category']);
        $thirdCategory = Category::factory()->create(['name' => 'Third Category']);

        $result = $this->categoryService->getCategoriesForDropdown(excludeId: $secondCategory->id);

        $this->assertCount(2, $result);
        $this->assertTrue($result->contains('id', $firstCategory->id));
        $this->assertTrue($result->contains('id', $thirdCategory->id));

        $this->assertFalse($result->contains('id', $secondCategory->id));
    }
}
