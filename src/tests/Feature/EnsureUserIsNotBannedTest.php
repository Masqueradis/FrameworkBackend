<?php

namespace Tests\Feature;

use App\Enums\UserStatus;
use App\Http\Middleware\EnsureUserIsNotBanned;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class EnsureUserIsNotBannedTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Route::post('/_test/comments', function () {
            return response()->json(['message' => 'Success']);
        })->middleware(['auth', EnsureUserIsNotBanned::class]);
    }

    #[Test]
    public function test_active_user_can_access_route(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Active->value]);

        $response = $this->actingAs($user)->postJson('/_test/comments');

        $response->assertOk();
    }

    #[Test]
    public function test_banned_user_gets403_with_custom_message(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Banned->value]);

        $response = $this->actingAs($user)->postJson('/_test/comments');

        $response->assertForbidden();
        $response->assertJsonPath('message', 'Your account is restricted. You cannot perform this action.');
    }

    #[Test]
    public function test_banned_user_redirects_back_with_alert_on_web_request(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Banned]);

        $response = $this->actingAs($user)
            ->from('/profile')
            ->post('/_test/comments');

        $response->assertRedirect('/profile');
        $response->assertSessionHas('error_alert', 'Your account is restricted. You cannot post reviews.');
    }
}
