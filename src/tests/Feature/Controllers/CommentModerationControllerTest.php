<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers;

use App\Enums\CommentStatus;
use App\Models\Comment;
use App\Models\Product;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class CommentModerationControllerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Comment $comment;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'admin']);
        $product = Product::factory()->create();

        $this->admin = User::factory()->create();
        $this->admin->assignRole('admin');

        $this->comment = Comment::create([
            'user_id' => $this->admin->id,
            'product_id' => $product->id,
            'content' => 'Test comment',
            'status' => CommentStatus::Pending,
            'rating' => 5,
        ]);
    }

    #[Test]
    public function testNonAdminCannotModerateComments(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->patch(route('admin.comments.approve', ['comment' => $this->comment->id]));

        $response->assertStatus(Response::HTTP_FOUND);
    }

    #[Test]
    public function testAdminCanApproveComment(): void
    {
        $response = $this->actingAs($this->admin)->patch(route('admin.comments.approve', ['comment' => $this->comment->id]));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Comment approved successfully.');
        $this->assertEquals(CommentStatus::Approved, $this->comment->fresh()->status);
    }

    #[Test]
    public function testAdminCanRejectComment(): void
    {
        $response = $this->actingAs($this->admin)->patch(route('admin.comments.reject', ['comment' => $this->comment->id]));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Comment rejected successfully.');
        $this->assertEquals(CommentStatus::Rejected, $this->comment->fresh()->status);
    }

    #[Test]
    public function testDisplaysView(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        Comment::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'content' => 'Test comment',
            'status' => CommentStatus::Pending,
            'rating' => 5,
        ]);

        $response = $this->actingAs($this->admin)->get(route('admin.comments.index'));

        $response->assertOk();
        $response->assertViewIs('admin.comments.index');
        $response->assertViewHas('products');
    }

    #[Test]
    public function testAdminCanDeleteAnyComment(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $comment = Comment::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'content' => 'Test comment',
            'status' => CommentStatus::Pending,
            'rating' => 5,
        ]);

        $response = $this->actingAs($this->admin)->delete(route('admin.comments.destroy', $comment));

        $response->assertRedirect();
        $response->assertSessionHas('success', 'Comment deleted successfully.');
        $this->assertDatabaseMissing('comments', [
            'id' => $comment->id,
        ]);
    }

    #[Test]
    public function testAdminCanViewProductCommentsForModeration(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        Comment::factory()->create([
            'product_id' => $product->id,
            'status' => CommentStatus::Pending->value,
        ]);

        $response = $this->actingAs($this->admin)
            ->get(route('admin.comments.show', $product));

        $response->assertStatus(Response::HTTP_OK);
        $response->assertViewIs('admin.comments.show');
        $response->assertViewHasAll(['product', 'comments']);
    }
}
