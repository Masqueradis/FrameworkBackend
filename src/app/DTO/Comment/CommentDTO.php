<?php

declare(strict_types=1);

namespace App\DTO\Comment;

use Spatie\LaravelData\Attributes\Validation\Between;
use Spatie\LaravelData\Attributes\Validation\Max;
use Spatie\LaravelData\Attributes\Validation\Min;
use Spatie\LaravelData\Data;

class CommentDTO extends Data
{
    public function __construct(
        #[Min(5), Max(1000)]
        public string $content,
        #[Between(1, 5)]
        public int $rating,
    ) {}

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public static function prepareForValidation(array $payload): array
    {
        if (isset($payload['content'])) {
            $payload['content'] = strip_tags((string) $payload['content']);
        }

        return $payload;
    }
}
