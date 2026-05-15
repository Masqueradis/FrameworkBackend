<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\ApiController;
use App\Models\Comment;
use App\Repositories\CommentRepository;
use App\Repositories\Contracts\CommentRepositoryInterface;
use App\Services\CommentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CommentModerationController extends ApiController
{
    public function __construct(
        private readonly CommentService $commentService,
    ) {}

    public function index(CommentRepositoryInterface $commentRepo): View
    {
        $comments = $commentRepo->getPendingForModeration();

        return view('admin.comments.index', compact('comments'));
    }

    public function approve(Comment $comment): RedirectResponse
    {
        $this->commentService->approve($comment);

        return back()->with('success', 'Comment approved successfully.');
    }

    public function reject(Comment $comment): RedirectResponse
    {
        $this->commentService->reject($comment);

        return back()->with('success', 'Comment rejected successfully.');
    }

    public function destroy(Comment $comment): RedirectResponse
    {
        $this->commentService->deleteComment($comment);

        return back()->with('success', 'Comment deleted successfully.');
    }
}
