<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\Comment;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ModelRelationsTest extends TestCase
{
    #[Test]
    public function testHasCorrectRelations(): void
    {
        $this->assertInstanceOf(BelongsTo::class, new Order()->user());
        $this->assertInstanceOf(BelongsTo::class, new OrderItem()->order());
        $this->assertInstanceOf(BelongsTo::class, new Payment()->order());
        $this->assertInstanceOf(BelongsTo::class, new Comment()->user());
        $this->assertInstanceOf(BelongsTo::class, new Comment()->product());
    }
}
