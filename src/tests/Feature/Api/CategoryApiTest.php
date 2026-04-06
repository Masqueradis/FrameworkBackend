<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;

class CategoryApiTest extends TestCase
{
    use refreshDatabase;

    private User $admin;
    private User $customer;
    public function setUp(): void
    {
        parent::setUp();

        $adminRole = Role::factory()->create(['name' => 'admin']);
        $customerRole = Role::factory()->create(['name' => 'customer']);

        $editCatalogPermission = Permission::factory()->create(['name' => 'edit-catalog']);

        $adminRole->permissions()->attach($editCatalogPermission);

        $this->admin = User::factory()->create();
        $this->admin->roles()->attach($adminRole);

        $this->customer = User::factory()->create();
        $this->customer->roles()->attach($customerRole);
    }

    #[Test]
    public function testCanGetAllCategories(): void
    {
        Category::factory()->count(3)->create();

        $response = $this->getJson('/api/categories');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'name'],
                ],
            ]);

        $this->assertCount(3, $response->json('data'));
    }

    #[Test]
    public function testCanGetSpecificCategory(): void
    {
        $category = Category::factory()->create(['name' => 'Category']);

        $response = $this->getJson("/api/categories/{$category->id}");

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonPath('data.id', $category->id)
            ->assertJsonPath('data.name', 'Category');
    }

    #[Test]
    public function testCanCreateCategory(): void
    {
        $payload = [
            'name' => 'New Category',
            'parent_id' => null,
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->postJson('/api/categories', $payload);

        $response->assertStatus(Response::HTTP_CREATED);
        $this->assertDatabaseHas('categories', $payload);
    }

    #[Test]
    public function testCanUpdateCategory(): void
    {
        $category = Category::factory()->create(['name' => 'Old Category']);

        $payload = [
            'name' => 'New Category',
            'parent_id' => null,
        ];

        $response = $this->actingAs($this->admin, 'api')
            ->putJson("/api/categories/{$category->id}", $payload);

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseHas('categories', [
            'id' => $category->id,
            'name' => 'New Category',
        ]);
    }

    #[Test]
    public function testCanDeleteCategory(): void
    {
        $category = Category::factory()->create();

        $response = $this->actingAs($this->admin, 'api')
            ->deleteJson("/api/categories/{$category->id}");

        $response->assertStatus(Response::HTTP_NO_CONTENT);
        $this->assertSoftDeleted('categories', ['id' => $category->id]);
    }
}
