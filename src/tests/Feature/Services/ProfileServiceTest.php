<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\DTO\User\UpdateProfileDTO;
use App\Models\User;
use App\Services\ProfileService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class ProfileServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProfileService $profileService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->profileService = app(ProfileService::class);
    }

    #[Test]
    public function testCanUpdateNickname(): void
    {
        $user = User::factory()->create(['name' => 'Old name']);
        $dto = new UpdateProfileDTO(name: 'New name', avatar: null);

        $this->profileService->updateProfile($user, $dto);

        $this->assertEquals('New name', $user->fresh()->name);
    }

    #[Test]
    public function testCanUploadAvatarAndDeleteOldOne(): void
    {
        Storage::fake('minio');

        $user = User::factory()->create(['avatar_path' => 'avatars/old_avatar.jpg']);
        Storage::disk('minio')->put('avatars/old_avatar.jpg', 'old content');

        $file = UploadedFile::fake()->image('new_avatar.jpg');
        $dto = new UpdateProfileDTO(name: 'Awesome name', avatar: $file);

        $this->profileService->updateProfile($user, $dto);

        Storage::disk('minio')->assertMissing('avatars/old_avatar.jpg');

        $newAvatarPath = $user->fresh()->avatar_path;
        $this->assertNotNull($newAvatarPath);
        Storage::disk('minio')->assertExists($newAvatarPath);
    }

    #[Test]
    public function testCanHardDeleteAccountAndAvatar(): void
    {
        Storage::fake('minio');
        $user = User::factory()->create(['avatar_path' => 'avatars/to_delete.jpg']);
        Storage::disk('minio')->put('avatars/to_delete.jpg', 'content');

        $this->profileService->deleteAccount($user);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);

        Storage::disk('minio')->assertMissing('avatars/to_delete.jpg');
    }

    #[Test]
    public function testResolveAvatarPathThrowsExceptionOnInvalidFile(): void
    {
        $user = User::factory()->create();

        $file = Mockery::mock(UploadedFile::class);
        $file->shouldReceive('isValid')->andReturn(false);
        $file->shouldReceive('getErrorMessage')->andReturn('File too large');

        $dto = UpdateProfileDTO::from(['name' => 'Test', 'avatar' => $file]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Upload error: File too large');

        $this->profileService->updateProfile($user, $dto);
    }

    #[Test]
    public function testResolveAvatarPathThrowsExceptionIfStorageFails(): void
    {
        $user = User::factory()->create();

        $file = Mockery::mock(UploadedFile::class);
        $file->shouldReceive('isValid')->andReturn(true);
        $file->shouldReceive('store')->andReturn(false);

        $dto = UpdateProfileDTO::from(['name' => 'Test', 'avatar' => $file]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('MinIO rejected the connection');

        $this->profileService->updateProfile($user, $dto);
    }

    #[Test]
    public function testDeleteAvatarExitsEarlyIfNoAvatar(): void
    {
        $user = User::factory()->create(['avatar_path' => null]);

        $this->profileService->deleteAvatar($user);

        $this->assertNull($user->avatar_path);
    }
}
