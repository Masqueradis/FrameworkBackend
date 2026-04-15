<?php

declare(strict_types=1);

namespace Tests\Feature\Repositories;

use App\Models\User;
use App\Repositories\UserRepository;
use App\ValueObjects\UserId;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class UserRepositoryTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function itFindsUserById(): void
    {
        $user = User::factory()->create();
        $repository = new UserRepository();

        $foundUser = $repository->findById(new UserId($user->id));

        $this->assertNotNull($foundUser);
        $this->assertEquals($user->id, $foundUser->id);
    }

    #[Test]
    public function itReturnsNullIfUserNotFound(): void
    {
        $repository = new UserRepository();

        $foundUser = $repository->findById(new UserId(1));

        $this->assertNull($foundUser);
    }
}
