<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DTO\Comment\UpdateCommentDTO;
use App\Models\Comment;
use App\Services\CommentService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserReviewController extends ApiController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly CommentService $commentService,
    ) {}

    public function update(Comment $comment, UpdateCommentDTO $dto): JsonResponse
    {
        $this->authorize('update', $comment);

        $this->commentService->updateComment($comment, $dto);

        return response()->json(['message' => 'Review sent for moderation.']);
    }
}
