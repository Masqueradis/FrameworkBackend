<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers;

use App\Models\Category;
use App\Models\Permission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class AdminCategoryControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
        Permission::firstOrCreate(['name' => 'access-panel']);
        Permission::firstOrCreate(['name' => 'manage-categories']);
        Role::firstOrCreate(['name' => 'admin']);

        $this->admin->assignRole('admin');
        $this->admin->givePermissionTo('access-panel');
        $this->admin->givePermissionTo('manage-categories');
        $this->category = Category::factory()->create();

    }

    #[Test]
    public function test_displays_categories_index(): void
    {
        Category::factory()->count(3)->create();

        $response = $this->actingAs($this->admin)->get(route('admin.categories.index'));

        $response->assertStatus(Response::HTTP_OK)
            ->assertViewIs('admin.categories.index')
            ->assertViewHas('categories');
    }

    #[Test]
    public function test_displays_categories_create(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.categories.create'));

        $response->assertStatus(Response::HTTP_OK)
            ->assertViewIs('admin.categories.form')
            ->assertViewHas('categories');
    }

    #[Test]
    public function test_stores_new_category(): void
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
    public function test_displays_categories_edit(): void
    {
        $category = Category::factory()->create();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.categories.edit', $category));

        $response->assertStatus(Response::HTTP_OK)
            ->assertViewIs('admin.categories.form')
            ->assertViewHasAll(['categories', 'category']);
    }

    #[Test]
    public function test_updates_category(): void
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

    public function test_deletes_category(): void
    {
        $category = Category::factory()->create();

        $response = $this->actingAs($this->admin)
            ->delete(route('admin.categories.destroy', $category));

        $response->assertRedirect(route('admin.categories.index'))
            ->assertSessionHas('success');

        $this->assertSoftDeleted('categories', ['id' => $category->id]);
    }
}
