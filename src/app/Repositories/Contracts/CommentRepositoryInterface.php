<?php

declare(strict_types=1);

namespace App\Repositories\Contracts;

use App\Enums\CommentStatus;
use App\Models\Comment;
use App\Models\Product;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface CommentRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): ?Comment;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Comment $comment, array $data): bool;

    public function delete(Comment $comment): ?bool;

    public function findByUserAndProduct(int $userId, int $productId): ?Comment;

    public function updateStatus(Comment $comment, CommentStatus $status): bool;

    /**
     * @return Collection<int, Comment>
     */
    public function getApprovedForProduct(int $productId): Collection;

    /**
     * @return Collection<int, Comment>
     */
    public function getPendingCommentsForModeration(): Collection;

    /**
     * @return LengthAwarePaginator<int, Product>
     */
    public function getPendingProductsForModeration(int $perPage = 15): LengthAwarePaginator;

    /**
     * @param  array<int, string>  $relations
     * @return Collection<int, Comment>
     */
    public function getByUserId(int $userId, array $relations = ['product']): Collection;
}
