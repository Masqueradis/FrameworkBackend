<?php

namespace Feature\Api;

use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Response;
use PHPUnit\Framework\Attributes\Test;

class CategoryApiTest extends TestCase
{
    use refreshDatabase;

    #[Test]
    public function testCanGetAllCategories(): void
    {
        Category::factory()->count(3)->create();

        $response = $this->getJson('/api/categories');

        $response->assertStatus(Response::HTTP_OK)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    '*' => ['id', 'name']
                ]
            ]);

        $this->assertCount(3, $response->json('data'));
    }
}
