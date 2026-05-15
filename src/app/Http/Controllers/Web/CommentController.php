<?php

declare(strict_types=1);

namespace App\Http\Controllers\Web;

use App\DTO\Comment\CommentDTO;
use App\Http\Controllers\ApiController;
use App\Models\Product;
use App\Models\User;
use App\Services\CommentService;
use Illuminate\Http\RedirectResponse;

class CommentController extends ApiController
{
    public function __construct(
        private readonly CommentService $commentService,
    ) {}

    public function store(CommentDTO $comment, Product $product): RedirectResponse
    {
        /** @var User $user */
        $user = auth()->user();

        $this->commentService->addComment($user, $product, $comment);

        return back()->with('success', 'Review sent for moderation.');
    }
}
