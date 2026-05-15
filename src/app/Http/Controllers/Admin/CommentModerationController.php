<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\ApiController;
use App\Models\Comment;
use App\Repositories\CommentRepository;
use App\Services\CommentService;
use Illuminate\Http\RedirectResponse;

class CommentModerationController extends ApiController
{
    public function __construct(
        private readonly CommentService $commentService,
    ) {}

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
}
