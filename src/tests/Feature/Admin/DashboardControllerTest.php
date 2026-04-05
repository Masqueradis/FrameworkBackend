<?php

declare(strict_types=1);

namespace Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function testAdminDashboardLoadsCorrectly(): void
    {
        $user = User::factory()->create();

        Category::factory()->count(3)->create();
        Product::factory()->count(6)->create();

        $response = $this->actingAs($user)->get(route('admin.dashboard'));

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
