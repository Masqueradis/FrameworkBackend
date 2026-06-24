<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers;

use App\Enums\CommentStatus;
use App\Http\Controllers\UserReviewController;
use App\Models\Comment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserReviewControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_index_displays_user_comments(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        Comment::factory()->count(3)->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);

        $response = $this->actingAs($user)
            ->get(action([UserReviewController::class, 'index']));

        $response->assertOk();
        $response->assertViewIs('profile.reviews');
        $response->assertViewHas('comments');

        $this->assertCount(3, $response->viewData('comments'));
    }

    #[Test]
    public function test_update_modifies_comment_and_redirects(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $comment = Comment::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'content' => 'Old',
            'rating' => 1,
        ]);

        $response = $this->actingAs($user)
            ->from('/reviews')
            ->patch(action([UserReviewController::class, 'update'], $comment->id), [
                'content' => 'New content',
                'rating' => 5,
            ]);

        $response->assertRedirect('/reviews');
        $response->assertSessionHas('message', 'Review sent for moderation.');

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'content' => 'New content',
            'rating' => 5,
            'status' => CommentStatus::Pending->value,
        ]);
    }
}
