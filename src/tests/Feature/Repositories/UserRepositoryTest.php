<?php

declare(strict_types=1);

namespace Tests\Feature\Repositories;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Models\Role;
use App\Models\User;
use App\Repositories\UserRepository;
use App\ValueObjects\Id\UserId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UserRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private UserRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new UserRepository();

        Role::create(['name' => UserRole::Admin->value, 'guard_name' => 'web']);
        Role::create(['name' => UserRole::Seller->value, 'guard_name' => 'web']);
    }

    #[Test]
    public function testFindsUserById(): void
    {
        $user = User::factory()->create();
        $repository = new UserRepository();

        $foundUser = $repository->findById(new UserId($user->id));

        $this->assertNotNull($foundUser);
        $this->assertEquals($user->id, $foundUser->id);
    }

    #[Test]
    public function testReturnsNullIfUserNotFound(): void
    {
        $repository = new UserRepository();

        $foundUser = $repository->findById(new UserId(1));

        $this->assertNull($foundUser);
    }

    #[Test]
    public function testCanFindUserByRoleWithSpatie(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(UserRole::Admin->value);

        $seller = User::factory()->create();
        $seller->assignRole(UserRole::Seller->value);

        $adminsFromRepo = $this->repository->getByRole(UserRole::Admin);

        $this->assertCount(1, $adminsFromRepo);
        $this->assertTrue($adminsFromRepo->first()->isAdmin());
        $this->assertEquals($admin->id, $adminsFromRepo->first()->id);
    }

    #[Test]
    public function testCanUpdateUserStatus(): void
    {
        $user = User::factory()->create(['status' => UserStatus::Active]);

        $this->repository->updateStatus($user->id, UserStatus::Banned);

        $this->assertTrue($user->fresh()->isBanned());
    }

    #[Test]
    public function testCanSave2faSecret(): void
    {
        $user = User::factory()->create(['google2fa_secret' => null]);
        $secret = 'SECRET';

        $this->repository->update2faSecret($user->id, $secret);

        $this->assertEquals($secret, $user->fresh()->google2fa_secret);
    }
}
