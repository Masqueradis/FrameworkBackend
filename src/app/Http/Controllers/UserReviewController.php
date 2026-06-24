<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\DTO\Comment\UpdateCommentDTO;
use App\Models\Comment;
use App\Models\User;
use App\Services\CommentService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class UserReviewController extends ApiController
{
    use AuthorizesRequests;

    public function __construct(
        private readonly CommentService $commentService,
    ) {}

    public function index(): View
    {
        $user = auth()->user();
        assert($user instanceof User);

        $comments = $this->commentService->getUserComments($user->id);

        return view('profile.reviews', compact('comments'));
    }

    public function update(Comment $comment, UpdateCommentDTO $dto): RedirectResponse
    {
        $this->authorize('update', $comment);

        $this->commentService->updateComment($comment, $dto);

        return back()->with(['message' => 'Review sent for moderation.']);
    }
}
