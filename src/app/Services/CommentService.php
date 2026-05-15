<?php

declare(strict_types=1);

namespace App\Services;

use App\DTO\Comment\CommentDTO;
use App\Enums\CommentStatus;
use App\Models\Comment;
use App\Models\Product;
use App\Models\User;
use App\Repositories\Contracts\CommentRepositoryInterface;

class CommentService
{
    public function __construct(
        private CommentRepositoryInterface $commentRepository,
    ) {}

    public function addComment(User $user, Product $product, CommentDTO $comment): Comment
    {
        return $this->commentRepository->create([
            'user_id' => $user->id,
            'product_id' => $product->id,
            'content' => $comment->content,
            'rating' => $comment->rating,
            'status' => CommentStatus::Pending->value,
        ]);
    }

    public function approve(Comment $comment): void
    {
        $this->commentRepository->updateStatus($comment, CommentStatus::Approved);
    }

    public function reject(Comment $comment): void
    {
        $this->commentRepository->updateStatus($comment, CommentStatus::Rejected);
    }
}
