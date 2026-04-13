<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Spatie\Permission\PermissionRegistrar;
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

        $this->artisan('passport:client', [
            '--personal' => true,
            '--name' => 'Test Client',
            '--provider' => 'users',
        ]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $customerRole = Role::create(['name' => 'customer', 'guard_name' => 'web']);

        $manageCategoriesPermission = Permission::create(['name' => 'manage categories', 'guard_name' => 'web']);

        $adminRole->givePermissionTo($manageCategoriesPermission);

        $this->admin = User::factory()->create();
        $this->admin->assignRole($adminRole);

        $this->customer = User::factory()->create();
        $this->customer->assignRole($customerRole);
    }

    #[Test]
    public function testCanGetAllCategories(): void
    {
        Category::factory()->count(3)->create();

        $response = $this->getJson('/api/v1/categories');

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

        $response = $this->getJson("/api/v1/categories/{$category->id}");

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
            ->postJson('/api/v1/categories', $payload);

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
            ->putJson("/api/v1/categories/{$category->id}", $payload);

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
            ->deleteJson("/api/v1/categories/{$category->id}");

        $response->assertStatus(Response::HTTP_NO_CONTENT);
        $this->assertSoftDeleted('categories', ['id' => $category->id]);
    }
}
