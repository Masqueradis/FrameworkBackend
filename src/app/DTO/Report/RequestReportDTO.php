<?php

declare(strict_types=1);

namespace App\DTO\Report;

use Spatie\LaravelData\Attributes\Validation\In;
use Spatie\LaravelData\Data;

class RequestReportDTO extends Data
{
    public function __construct(
        #[In(['sales', 'inventory'])]
        public string $type,
        public string $date_from,
        public string $date_to,
    ) {}
}
