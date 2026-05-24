<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\Comment\CommentDTO;
use App\Enums\CommentStatus;
use App\Models\Comment;
use App\Models\Product;
use App\Models\User;
use App\Repositories\Contracts\CommentRepositoryInterface;

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

}
