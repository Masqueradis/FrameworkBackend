<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers;

use App\DTO\Comment\CommentDTO;
use App\Enums\CommentStatus;
use App\Models\Comment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class CommentControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_guest_cannot_add_comment(): void
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
    public function test_authenticated_user_can_add_comment(): void
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

    #[Test]
    public function test_strips_html_tags_from_content(): void
    {
        $payload = [
            'content' => '<h1>Awesome</h1> <script>alert("XSS")</script> product!',
            'rating' => 5,
        ];

        $prepared = CommentDTO::prepareForValidation($payload);

        $this->assertEquals('Awesome alert("XSS") product!', $prepared['content']);
    }

    #[Test]
    public function test_returns_payload_as_is_if_content_missing(): void
    {
        $payload = [
            'status' => CommentStatus::Pending->value,
            'rating' => 5,
        ];

        $prepared = CommentDTO::prepareForValidation($payload);

        $this->assertArrayNotHasKey('content', $prepared);
        $this->assertEquals(5, $prepared['rating']);
    }

    #[Test]
    public function test_user_can_delete_own_comment(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $comment = Comment::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'content' => 'This is a test comment',
            'rating' => 5,
            'status' => CommentStatus::Approved->value,
        ]);

        $response = $this->actingAs($user)->delete(route('comments.destroy', $comment));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Review deleted successfully.');
        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    #[Test]
    public function test_user_cannot_delete_others_comment(): void
    {
        $owner = User::factory()->create();
        $badguy = User::factory()->create();
        $product = Product::factory()->create();

        $comment = Comment::create([
            'user_id' => $owner->id,
            'product_id' => $product->id,
            'content' => 'This is a test comment',
            'rating' => 5,
            'status' => CommentStatus::Approved->value,
        ]);

        $response = $this->actingAs($badguy)->delete(route('comments.destroy', $comment));

        $response->assertStatus(Response::HTTP_FOUND);
        $this->assertDatabaseHas('comments', ['id' => $comment->id]);
    }
}
