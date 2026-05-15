<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\CommentStatus;
use App\Models\Comment;
use App\Repositories\Contracts\CommentRepositoryInterface;
use Illuminate\Support\Collection;

class CommentRepository implements CommentRepositoryInterface
{

    public function create(array $data): Comment
    {
        return Comment::create($data);
    }

    public function updateStatus(Comment $comment, CommentStatus $status): bool
    {
        return $comment->update(['status' => $status]);
    }

    public function getApprovedForProduct(int $productId): Collection
    {
        return Comment::where('product_id', $productId)
            ->where('status', CommentStatus::Approved->value)
            ->latest()
            ->get();
    }

    public function getPendingForModeration(): Collection
    {
        return Comment::where('status', CommentStatus::Pending->value)
            ->oldest()
            ->get();
    }
}
