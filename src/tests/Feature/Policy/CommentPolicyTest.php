<?php

namespace Tests\Feature\Policy;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class CommentPolicyTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function testUserCanUpdateOwnComment(): void
    {
        $user = User::factory()->create();
        $comment = Comment::factory()->create(['user_id' => $user->id]);

        $this->assertTrue($user->can('update', $comment));
    }

    #[Test]
    public function testUserCannotUpdateOthersComment(): void
    {
        $author = User::factory()->create();
        $badguy = User::factory()->create();
        $comment = Comment::factory()->create(['user_id' => $author->id]);

        $this->assertFalse($badguy->can('update', $comment));
    }
}
