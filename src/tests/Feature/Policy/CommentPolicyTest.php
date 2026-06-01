<?php

namespace Tests\Feature\Policy;

use App\Enums\UserRole;
use App\Models\Comment;
use App\Models\Role;
use App\Models\User;
use App\Policies\CommentPolicy;
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

    #[Test]
    public function testAdminCanDoAnythingWithComments(): void
    {
        $admin = User::factory()->create();
        Role::firstOrCreate(['name' => UserRole::Admin->value, 'guard_name' => 'web']);
        $admin->assignRole(UserRole::Admin->value);

        $comment = Comment::factory()->create();

        $this->assertTrue($admin->can('update', $comment));
        $this->assertTrue($admin->can('delete', $comment));
    }

    #[Test]
    public function testUserCanDeleteOwnCommentButNotOthers(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();

        $ownComment = Comment::factory()->create(['user_id' => $user->id]);
        $otherComment = Comment::factory()->create(['user_id' => $otherUser->id]);

        $policy = new CommentPolicy();

        $this->assertTrue($policy->delete($user, $ownComment));
        $this->assertFalse($policy->delete($user, $otherComment));
    }
}
