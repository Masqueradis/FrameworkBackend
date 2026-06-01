<?php

declare(strict_types=1);

namespace Tests\Feature\Controllers;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ProfileControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function testEditReturnsView(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('profile.edit'));

        $response->assertOk();
        $response->assertViewIs('profile.edit');
    }

    #[Test]
    public function testUpdateModifiesProfileAndRedirects(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->from(route('profile.edit'))
            ->patch(route('profile.update'), [
                'name' => 'Updated Name'
            ]);

        $response->assertSessionHasNoErrors();
        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('message', 'Profile updated successfully.');
        $this->assertEquals('Updated Name', $user->fresh()->name);
    }

    #[Test]
    public function testDestroyDeletesAccountAndRedirects(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->from(route('profile.edit'))
            ->delete(route('profile.destroy'));

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('message', 'Account deleted successfully.');
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    #[Test]
    public function testDestroyAvatarDeletesAvatarAndRedirects(): void
    {
        Storage::fake('minio');
        $user = User::factory()->create(['avatar_path' => 'avatars/fake-avatar.jpg']);

        $response = $this->actingAs($user)
            ->from(route('profile.edit'))
            ->delete(route('profile.avatar.destroy'));

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('success', 'Avatar deleted successfully.');
        $this->assertNull($user->fresh()->avatar_path);
    }
}
