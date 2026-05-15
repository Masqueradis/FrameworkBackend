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
     * @return Comment
     */
    public function create(array $data): Comment;
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
