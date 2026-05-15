<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Enums\CommentStatus;
use App\Models\Comment;
use Illuminate\Support\Collection;

interface CommentRepositoryInterface
{
    /**
     * @param array<string, mixed> $data
     * @return ?Comment
     */
    public function create(array $data): ?Comment;

    /**
     * @param Comment $comment
     * @param array<string, mixed> $data
     * @return bool
     */
    public function update(Comment $comment, array $data): bool;
    public function delete(Comment $comment): ?bool;
    public function findByUserAndProduct(int $userId, int $productId): ?Comment;
    public function updateStatus(Comment $comment, CommentStatus $status): bool;
    /**
     * @param int $productId
     * @return Collection<int, Comment>
     */
    public function getApprovedForProduct(int $productId): Collection;
    /**
     * @return Collection<int, Comment>
     */
    public function getPendingForModeration(): Collection;
}
