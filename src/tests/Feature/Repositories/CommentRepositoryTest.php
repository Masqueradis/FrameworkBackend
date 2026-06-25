<?php

declare(strict_types=1);

namespace Tests\Feature\Repositories;

use App\Enums\CommentStatus;
use App\Models\Comment;
use App\Models\Product;
use App\Models\User;
use App\Repositories\CommentRepository;
use App\Repositories\Contracts\CommentRepositoryInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CommentRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private CommentRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = $this->app->make(CommentRepository::class);
    }

    #[Test]
    public function test_create_a_new_comment(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        $data = [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'content' => 'Awesome product',
            'rating' => 5,
        ];

        $comment = $this->repository->create($data);

        $this->assertInstanceOf(Comment::class, $comment);
        $this->assertEquals('Awesome product', $comment->content);
        $this->assertEquals(5, $comment->rating);
        $this->assertDatabaseHas('comments', ['id' => $comment->id]);
    }

    #[Test]
    public function test_get_only_approved_comments_for_product(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        Comment::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'status' => CommentStatus::Approved,
            'content' => 'Awesome product',
            'rating' => 5,
        ]);
        Comment::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'status' => CommentStatus::Approved,
            'content' => 'Awesome product',
            'rating' => 5,
        ]);
        Comment::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'status' => CommentStatus::Rejected,
            'content' => 'Awesome product',
            'rating' => 5,
        ]);
        Comment::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'status' => CommentStatus::Pending,
            'content' => 'Awesome product',
            'rating' => 5,
        ]);

        $approvedComments = $this->repository->getApprovedForProduct($product->id);

        $this->assertCount(2, $approvedComments);
        $this->assertTrue($approvedComments->every(fn ($comment) => $comment->status->isApproved()));
    }

    #[Test]
    public function test_get_all_pending_comments_for_moderation(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        Comment::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'status' => CommentStatus::Approved,
            'content' => 'Awesome product',
            'rating' => 5,
        ]);
        Comment::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'status' => CommentStatus::Pending,
            'content' => 'Awesome product',
            'rating' => 5,
        ]);

        $pendingComment = $this->repository->getPendingCommentsForModeration();

        $this->assertCount(1, $pendingComment);
        $this->assertTrue($pendingComment->every(fn ($comment) => $comment->status->isPending()));
    }

    #[Test]
    public function test_can_update_an_existing_comment(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $comment = Comment::create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'content' => 'Awesome product',
            'rating' => 4,
            'status' => CommentStatus::Approved,
        ]);

        $repository = app(CommentRepositoryInterface::class);

        $result = $repository->update($comment, [
            'content' => 'New comment',
            'rating' => 5,
            'status' => CommentStatus::Pending,
        ]);

        $this->assertTrue($result);

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'content' => 'New comment',
            'rating' => 5,
        ]);
    }

    #[Test]
    public function test_get_by_user_id_returns_comments_with_relations(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();

        Comment::factory()->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'status' => CommentStatus::Approved->value,
        ]);

        $repository = new CommentRepository;
        $comments = $repository->getByUserId($user->id);

        $this->assertCount(1, $comments);

        $firstComment = $comments->first();

        $this->assertInstanceOf(Comment::class, $firstComment);

        $this->assertTrue($firstComment->relationLoaded('product'));
        $this->assertEquals($user->id, $firstComment->user_id);
    }
}
