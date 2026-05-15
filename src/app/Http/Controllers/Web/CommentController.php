<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\DTO\Comment\CommentDTO;
use App\Http\Controllers\ApiController;
use App\Models\Comment;
use App\Models\Product;
use App\Models\User;
use App\Services\CommentService;
use Illuminate\Http\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

class CommentController extends ApiController
{
    public function __construct(
        private readonly CommentService $commentService,
    ) {}

    public function store(CommentDTO $comment, Product $product): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $this->commentService->saveComment($user, $product, $comment);

        return back()->with('success', 'Review sent for moderation.');
    }

    public function destroy(Comment $comment): RedirectResponse
    {
        if(auth()->id() !== $comment->user_id) {
            abort(Response::HTTP_FORBIDDEN, 'Unauthorized action.');
        }

        $this->commentService->deleteComment($comment);

        return back()->with('success', 'Review deleted successfully.');
    }
}
