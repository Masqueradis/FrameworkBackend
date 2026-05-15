<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers;

use App\Enums\CommentStatus;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CommentControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function testGuestCannotAddComment(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $response = $this->post(route('comments.store', ['product' => $product->id]), [
            'content' => 'This is a test comment',
            'rating' => 5,
        ]);

        $response->assertRedirect(route('login'));
    }

    #[Test]
    public function testAuthenticatedUserCanAddComment(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $response = $this->actingAs($user)->post(route('comments.store', ['product' => $product->id]), [
            'content' => 'This is a test comment',
            'rating' => 5,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Review sent for moderation.');

        $this->assertDatabaseHas('comments', [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'content' => 'This is a test comment',
            'status' => CommentStatus::Pending->value,
            'rating' => 5,
        ]);
    }
}
