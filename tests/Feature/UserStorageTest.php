<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class UserStorageTest extends TestCase
{
    use RefreshDatabase;

    const FILENAME = 'test.txt';

    /**
     * A basic authenticated user file storage feature test.
     */
    public function test_can_store_a_file_in_the_users_disk(): void
    {
        Storage::fake('users');
        $user = User::factory()->create();

        /** @var Illuminate\Filesystem\FilesystemAdapter */
        $disk = Storage::disk('users');
        $file = UploadedFile::fake()->create(self::FILENAME);

        $disk->putFileAs($user->id, $file, self::FILENAME);

        $disk->assertExists("$user->id/".self::FILENAME);
    }
}
