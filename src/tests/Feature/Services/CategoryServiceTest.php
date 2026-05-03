<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\DTO\CategorySaveDTO;
use App\Models\Category;
use App\Repositories\CategoryRepository;
use App\Services\CategoryService;
use App\ValueObjects\Id\CategoryId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CategoryServiceTest extends TestCase
{
    use RefreshDatabase;

    private CategoryService $categoryService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->categoryService = app(CategoryService::class);
    }

    #[Test]
    public function testCanCreateCategory(): void
    {
        $data = CategorySaveDTO::from([
            'name' => 'New Category',
            'parent_id' => null,
        ]);

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

        $data = new CategorySaveDTO(name: 'New Category', parent_id: new CategoryId($parent->id));

        $this->categoryService->updateCategory($category, $data);

        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'New Category',
            'parent_id' => $data->parent_id?->value,
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

        $result = $this->categoryService->getCategoriesForDropdown(new CategoryId($secondCategory->id));

        $this->assertCount(2, $result);
        $this->assertTrue($result->contains('id', $firstCategory->id));
        $this->assertTrue($result->contains('id', $thirdCategory->id));

        $this->assertFalse($result->contains('id', $secondCategory->id));
    }

    #[Test]
    public function testGetRootsReturnsOnlyCategoriesWithoutParents(): void
    {
        $repository = app(CategoryRepository::class);

        $root1 = Category::factory()->create(['parent_id' => null, 'name' => 'Root1']);
        $root2 = Category::factory()->create(['parent_id' => null, 'name' => 'Root2']);

        $child = Category::factory()->create(['parent_id' => $root1->id]);

        $roots = $repository->getRoots();

        $this->assertCount(2, $roots);
        $this->assertTrue($roots->contains($root1));
        $this->assertTrue($roots->contains($root2));
        $this->assertFalse($roots->contains($child));
    }
}
