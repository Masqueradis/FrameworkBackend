<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StorageTest extends TestCase
{
    #[Test]
    public function test_can_write_and_read_file_to_minio(): void
    {
        Storage::fake('minio');
        Storage::disk('minio')->put('example.txt', 'Hello World');
        $content = Storage::disk('minio')->get('example.txt');

        Storage::disk('minio')->assertExists('example.txt');
        $this->assertStringContainsString('Hello World', $content);
    }
}
