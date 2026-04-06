<?php

declare(strict_types=1);

namespace Tests\Feature\Policy;

use App\Models\Product;
use App\Models\User;
use App\Policies\ProductPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ProductPolicyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function testUnsupportedActionsAlwaysReturnFalse(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create();
        $policy = new ProductPolicy();

        $this->assertFalse($policy->viewAny($user));
        $this->assertFalse($policy->view($user, $product));
        $this->assertFalse($policy->restore($user, $product));
        $this->assertFalse($policy->forceDelete($user, $product));
    }
}
