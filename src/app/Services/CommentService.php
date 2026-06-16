<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\Comment\CommentDTO;
use App\DTO\Comment\UpdateCommentDTO;
use App\Enums\CommentStatus;
use App\Models\Comment;
use App\Models\Product;
use App\Models\User;
use App\Repositories\Contracts\CommentRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

readonly class CommentService
{
    public function __construct(
        private CommentRepositoryInterface $commentRepository,
    ) {}

    public function saveComment(User $user, Product $product, CommentDTO $dto): ?Comment
    {
        $comment = $this->commentRepository->findByUserAndProduct($user->id, $product->id);

        $payload = [
            'content' => $dto->content,
            'rating' => $dto->rating,
            'status' => CommentStatus::Pending->value,
        ];

        if ($comment) {
            $this->commentRepository->update($comment, $payload);
            return $comment->refresh();
        }

        return $this->commentRepository->create(array_merge([
            'user_id' => $user->id,
            'product_id' => $product->id,
        ], $payload));
    }

    public function updateComment(Comment $comment, UpdateCommentDTO $dto): bool
    {
        $attributesToUpdate = collect([
            'content' => $dto->content,
            'rating' => $dto->rating,
        ])->filter()->toArray();

        if (empty($attributesToUpdate)) {
            return false;
        }

        $attributesToUpdate['status'] = CommentStatus::Pending->value;

        return $this->commentRepository->update($comment, $attributesToUpdate);
    }

    public function approve(Comment $comment): void
    {
        $this->commentRepository->updateStatus($comment, CommentStatus::Approved);
    }

    public function reject(Comment $comment): void
    {
        $this->commentRepository->updateStatus($comment, CommentStatus::Rejected);
    }

    public function deleteComment(Comment $comment): void
    {
        $this->commentRepository->delete($comment);
    }

    /**
     * @param int $userId
     * @return Collection<int, Comment>
     */
    public function getUserComments(int $userId): Collection
    {
        return $this->commentRepository->getByUserId($userId, ['product']);
    }

    /**
     * @param int $perPage
     * @return LengthAwarePaginator<int, Product>
     */
    public function getProductsWithPendingComments(int $perPage = 15): LengthAwarePaginator
    {
        return $this->commentRepository->getPendingProductsForModeration($perPage);
    }
}
