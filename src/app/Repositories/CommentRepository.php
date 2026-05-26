<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\CommentStatus;
use App\Models\Comment;
use App\Models\Product;
use App\Repositories\Contracts\CommentRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class CommentRepository implements CommentRepositoryInterface
{

    public function create(array $data): Comment
    {
        return Comment::create($data);
    }

    public function update(Comment $comment, array $data): bool
    {
        return $comment->update($data);
    }

    public function delete(Comment $comment): ?bool
    {
        return $comment->delete();
    }

    public function findByUserAndProduct(int $userId, int $productId): ?Comment
    {
        return Comment::where('user_id', $userId)
            ->where('product_id', $productId)
            ->first();
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

    public function getPendingCommentsForModeration(): Collection
    {
        return Comment::where('status', CommentStatus::Pending->value)
            ->oldest()
            ->get();
    }

    public function getPendingProductsForModeration(): Collection
    {
        return Product::whereHas('comments', function ($query) {
            $query->where('status', CommentStatus::Pending->value);
        })
        ->withCount(['comments as pending_count' => function ($query) {
            $query->where('status', CommentStatus::Pending->value);
        }])
        ->get();
    }

    public function getByUserId(int $userId, array $relations = ['product']): Collection
    {
        return Comment::with($relations)
            ->where('user_id', $userId)
            ->latest()
            ->get();
    }
}
