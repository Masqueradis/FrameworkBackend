<?php

namespace Tests\Feature\Policy;

use App\Models\Category;
use App\Models\User;
use App\Policies\CategoryPolicy;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CategoryPolicyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function test_unsupported_actions_always_return_false(): void
    {
        $user = User::factory()->create();
        $category = Category::factory()->create();
        $policy = new CategoryPolicy;

        $this->assertTrue($policy->viewAny($user));
        $this->assertTrue($policy->view($user, $category));
        $this->assertFalse($policy->restore($user, $category));
        $this->assertFalse($policy->forceDelete($user, $category));
    }
}
