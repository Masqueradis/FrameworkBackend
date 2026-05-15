<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\DTO\Comment\CommentDTO;
use App\Enums\CommentStatus;
use App\Models\Comment;
use App\Models\Product;
use App\Models\User;
use App\Repositories\Contracts\CommentRepositoryInterface;
use App\Services\CommentService;
use Mockery;
use Mockery\Adapter\Phpunit\MockeryPHPUnitIntegration;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class CommentServiceTest extends TestCase
{
    use MockeryPHPUnitIntegration;

    private CommentRepositoryInterface $repositoryMock;
    private CommentService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repositoryMock = Mockery::mock(CommentRepositoryInterface::class);
        $this->service = new CommentService($this->repositoryMock);
    }

    #[Test]
    public function testAddCommentWithPendingStatusByDefault(): void
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
    public function testApproveAComment(): void
    {
        $comment = Comment::make(['status' => CommentStatus::Pending]);

        $this->repositoryMock->shouldReceive('updateStatus')
            ->once()
            ->with($comment, CommentStatus::Approved)
            ->andReturn(true);

        $this->service->approve($comment);
    }

    #[Test]
    public function testRejectAComment(): void
    {
        $comment = Comment::make(['status' => CommentStatus::Pending]);

        $this->repositoryMock->shouldReceive('updateStatus')
            ->once()
            ->with($comment, CommentStatus::Rejected)
            ->andReturn(true);

        $this->service->reject($comment);
    }

    #[Test]
    public function testUpdateExistingCommentAndResetsStatusToPending(): void
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
}
