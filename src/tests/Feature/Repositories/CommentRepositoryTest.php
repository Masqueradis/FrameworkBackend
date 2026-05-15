<?php

declare(strict_types=1);

namespace Tests\Feature\Repositories;

use App\Enums\CommentStatus;
use App\Models\Comment;
use App\Models\Product;
use App\Models\User;
use App\Repositories\CommentRepository;
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
    public function testCreateANewComment(): void
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
    public function testGetOnlyApprovedCommentsForProduct(): void
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
    public function testGetAllPendingCommentsForModeration(): void
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

        $pendingComment = $this->repository->getPendingForModeration();

        $this->assertCount(1, $pendingComment);
        $this->assertTrue($pendingComment->every(fn ($comment) => $comment->status->isPending()));
    }
}
