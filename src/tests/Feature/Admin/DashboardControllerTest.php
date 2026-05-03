<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function testAdminDashboardLoadsCorrectly(): void
    {
        $admin = User::factory()->create();
        Role::firstOrCreate(['name' => 'admin']);
        $admin->assignRole('admin');
        $this->actingAs($admin);

        Category::factory()->count(3)->create();
        Product::factory()->count(6)->create();

        $response = $this->actingAs($admin)->get(route('admin.dashboard'));

        $response->assertStatus(Response::HTTP_OK)
            ->assertViewIs('admin.dashboard')
            ->assertViewHasAll(['productsCount', 'categoriesCount', 'usersCount']);
    }

    #[Test]
    public function testSellerDashboardLoadsCorrectly(): void
    {
        $seller = User::factory()->create();
        Role::firstOrCreate(['name' => 'seller']);
        $seller->assignRole('seller');
        $this->actingAs($seller);

        Category::factory()->count(3)->create();
        Product::factory()->count(6)->create();

        $response = $this->actingAs($seller)->get(route('admin.dashboard'));

        $response->assertStatus(Response::HTTP_OK)
            ->assertViewIs('admin.dashboard')
            ->assertViewHasAll(['productsCount', 'categoriesCount', 'usersCount']);
    }

    #[Test]
    public function testGuestCannotViewDashboard(): void
    {
        $response = $this->get(route('admin.dashboard'));
        $response->assertRedirect(route('login'));
    }
}
