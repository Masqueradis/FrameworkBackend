<?php

namespace Tests\Feature\Policy;

use App\Models\Category;
use App\Models\User;
use App\Policies\CategoryPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class CategoryPolicyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function testUnsupportedActionsAlwaysReturnFalse(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $policy = new CategoryPolicy();

        $this->assertTrue($policy->viewAny($user));
        $this->assertTrue($policy->view($user, $category));
        $this->assertFalse($policy->restore($user, $category));
        $this->assertFalse($policy->forceDelete($user, $category));
    }
}
