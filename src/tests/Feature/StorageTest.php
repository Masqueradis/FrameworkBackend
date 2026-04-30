<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class StorageTest extends TestCase
{
    #[Test]
    public function testCanWriteAndReadFileToMinio(): void
    {
        Storage::fake('minio');
        Storage::disk('minio')->put('example.txt', 'Hello World');
        $content = Storage::disk('minio')->get("example.txt");

        Storage::disk('minio')->assertExists('example.txt');
        $this->assertStringContainsString('Hello World', $content);
    }
}
