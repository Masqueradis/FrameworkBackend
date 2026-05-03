<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;

class CategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
        Role::firstOrCreate(['name' => 'admin']);
        $this->admin->assignRole('admin');
        $this->category = Category::factory()->create();
    }

    #[Test]
    public function testDisplaysCategoriesIndex(): void
    {
        Category::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)->get(route('admin.categories.index'));

        $response->assertStatus(Response::HTTP_OK)
            ->assertViewIs('admin.categories.index')
            ->assertViewHas('categories');
    }

    #[Test]
    public function testDisplaysCategoriesCreate(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.categories.create'));

        $response->assertStatus(Response::HTTP_OK)
            ->assertViewIs('admin.categories.form')
            ->assertViewHas('categories');
    }

    #[Test]
    public function testStoresNewCategory(): void
    {
        $payload = [
            'name' => 'New Category',
            'parent_id' => null,
        ];

        $response = $this->actingAs($this->admin)
            ->post(route('admin.categories.store'), $payload);

        $response->assertRedirect(route('admin.categories.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('categories', ['name' => 'New Category']);
    }

    #[Test]
    public function testDisplaysCategoriesEdit(): void
    {
        $category = Category::factory()->create();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.categories.edit', $category));

        $response->assertStatus(Response::HTTP_OK)
            ->assertViewIs('admin.categories.form')
            ->assertViewHasAll(['categories', 'category']);
    }

    #[Test]
    public function testUpdatesCategory(): void
    {
        $category = Category::factory()->create(['name' => 'Old Category']);

        $payload = [
            'name' => 'New Category',
            'parent_id' => null,
        ];

        $response = $this->actingAs($this->admin)
            ->put(route('admin.categories.update', $category), $payload);

        $response->assertRedirect(route('admin.categories.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('categories', ['id' => $category->id, 'name' => 'New Category']);
    }

    public function testDeletesCategory(): void
    {
        $category = Category::factory()->create();

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.categories.destroy', $category));

        $response->assertRedirect(route('admin.categories.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('categories', ['id' => $category->id]);
    }
}
