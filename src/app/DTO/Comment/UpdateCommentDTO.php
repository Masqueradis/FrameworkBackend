<?php

declare(strict_types=1);

namespace App\DTO\Comment;

use Spatie\LaravelData\Attributes\Validation\Between;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Data;

class UpdateCommentDTO extends Data
{
    public function __construct(
        #[Max(1000)]
        public readonly ?string $content,
        #[Between(1, 5)]
        public readonly ?int $rating,
    ) {}
}
