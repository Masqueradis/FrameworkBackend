<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\DTO\Comment\CommentDTO;
use App\DTO\Comment\UpdateCommentDTO;
use App\Enums\CommentStatus;
use App\Models\Comment;
use App\Models\Product;
use App\Models\User;
use App\Repositories\Contracts\CommentRepositoryInterface;
use App\Services\CommentService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CommentServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;
    use RefreshDatabase;

    private CommentRepositoryInterface $repositoryMock;

    private CommentService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repositoryMock = Mockery::mock(CommentRepositoryInterface::class);
        $this->service = new CommentService($this->repositoryMock);
    }

    #[Test]
    public function test_add_comment_with_pending_status_by_default(): void
    {
        $user = User::factory()->make(['id' => 1]);
        $product = Product::factory()->make(['id' => 1]);
        $dto = new CommentDTO(content: 'This is a valid clean review', rating: 5);

        $this->repositoryMock->shouldReceive('findByUserAndProduct')
            ->once()
            ->with($user->id, $product->id)
            ->andReturn(null);

        $expectedData = [
            'user_id' => $user->id,
            'product_id' => $product->id,
            'content' => $dto->content,
            'rating' => $dto->rating,
            'status' => CommentStatus::Pending->value,
        ];

        $this->repositoryMock->shouldReceive('create')
            ->once()
            ->with($expectedData)
            ->andReturn(new Comment($expectedData));

        $comment = $this->service->saveComment($user, $product, $dto);

        $this->assertEquals(CommentStatus::Pending, $comment->status);
    }

    #[Test]
    public function test_approve_a_comment(): void
    {
        $comment = Comment::make(['status' => CommentStatus::Pending]);

        $this->repositoryMock->shouldReceive('updateStatus')
            ->once()
            ->with($comment, CommentStatus::Approved)
            ->andReturn(true);

        $this->service->approve($comment);
    }

    #[Test]
    public function test_reject_a_comment(): void
    {
        $comment = Comment::make(['status' => CommentStatus::Pending]);

        $this->repositoryMock->shouldReceive('updateStatus')
            ->once()
            ->with($comment, CommentStatus::Rejected)
            ->andReturn(true);

        $this->service->reject($comment);
    }

    #[Test]
    public function test_update_existing_comment_and_resets_status_to_pending(): void
    {
        $user = User::factory()->make(['id' => 1]);
        $product = Product::factory()->make(['id' => 1]);
        $dto = new CommentDTO(content: 'This is a valid clean review', rating: 5);

        $existingComment = new Comment([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'content' => $dto->content,
            'rating' => $dto->rating,
            'status' => CommentStatus::Approved->value,
        ]);

        $this->repositoryMock->shouldReceive('findByUserAndProduct')
            ->once()
            ->with($user->id, $product->id)
            ->andReturn($existingComment);

        $this->repositoryMock->shouldReceive('update')
            ->once()
            ->with($existingComment, [
                'content' => $dto->content,
                'rating' => $dto->rating,
                'status' => CommentStatus::Pending->value,
            ])
            ->andReturn(true);

        $this->service->saveComment($user, $product, $dto);
    }

    #[Test]
    public function test_update_comment_from_profile_resets_status_to_pending(): void
    {
        $user = User::factory()->make();
        $product = Product::factory()->make(['id' => 1]);
        $comment = new Comment([
            'id' => 1,
            'user_id' => $user->id,
            'product_id' => $product->id,
            'content' => 'Old valid content',
            'rating' => 5,
            'status' => CommentStatus::Approved->value,
        ]);

        $dto = new UpdateCommentDTO(content: 'New invalid content', rating: null);

        $this->repositoryMock->shouldReceive('update')
            ->once()
            ->with($comment, [
                'content' => 'New invalid content',
                'status' => CommentStatus::Pending->value,
            ])
            ->andReturn(true);

        $result = $this->service->updateComment($comment, $dto);
        $this->assertTrue($result);
    }

    #[Test]
    public function test_update_comment_returns_false_if_no_attributes_to_update(): void
    {
        $comment = Comment::factory()->create();

        $dto = UpdateCommentDTO::from(['content' => null, 'rating' => null]);

        $result = $this->service->updateComment($comment, $dto);

        $this->assertFalse($result);
    }

    #[Test]
    public function test_get_user_comments(): void
    {
        $userId = 1;

        $expectedComments = Collection::make([
            new Comment(['id' => 1]),
            new Comment(['id' => 2]),
            new Comment(['id' => 3]),
        ]);

        $this->repositoryMock
            ->shouldReceive('getByUserId')
            ->once()
            ->with($userId, ['product'])
            ->andReturn($expectedComments);

        $result = $this->service->getUserComments($userId);

        $this->assertCount(3, $result);
    }
}
