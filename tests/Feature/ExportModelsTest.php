<?php

namespace Tests\Feature;

use App\Jobs\ModelsToCSV;
use App\Models\Model;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExportModelsTest extends TestCase
{
    use RefreshDatabase;

    protected const QUERY = [
        'select' => ['name', 'date', 'location', 'lat', 'long', 'user_id'],
        'where' => ['user_id', '=', '{user_id}'],
        'orderBy' => 'id',
    ];

    protected const MODEL_COUNT = 10;

    protected const DISK = 'users';

    protected const FILE_NAME = 'test-models.csv';

    public function test_export_models(): void
    {
        // Define the disk type to avoid linting errors.
        /** @var \Illuminate\Filesystem\FilesystemAdapter */
        $disk = Storage::disk(name: self::DISK);

        $user = User::factory()->create();
        Model::factory()
            ->count(self::MODEL_COUNT)
            ->create(['user_id' => $user->id]);
        $query = self::QUERY;
        $query['where'][2] = $user->id;

        $this->assertTrue(
            $disk->fileMissing($user->id.'/'.self::FILE_NAME),
            'The CSV file should not exist before the test.'
        );

        ModelsToCSV::dispatch(
            Model::class,
            self::DISK,
            $user->id.'/'.self::FILE_NAME,
            $query
        );

        $this->assertTrue(
            $disk->fileExists($user->id.'/'.self::FILE_NAME),
            'The CSV file was not created.'
        );

        // Examine the file contents.
        $contents = $disk->get($user->id.'/'.self::FILE_NAME);
        $records = array_filter(explode(PHP_EOL, $contents));
        $header = array_shift($records);

        $this->assertEquals(
            self::QUERY['select'],
            explode(',', $header),
            'The header column names do not match the select clause.'
        );

        $this->assertEquals(
            self::MODEL_COUNT,
            count($records),
            'Expected '.self::MODEL_COUNT.' records, found '.count($records)
        );

        $this->assertTrue(
            $disk->delete($user->id.'/'.self::FILE_NAME),
            'The user directory could not be deleted at the end of the test.'
        );
    }

    public function tearDown(): void
    {
        // Define the disk type to avoid linting errors.
        /** @var \Illuminate\Filesystem\FilesystemAdapter */
        $disk = Storage::disk(self::DISK);
        $user = User::firstOr(fn () => User::factory()->create());
        if ($disk->exists($user->id.'/'.self::FILE_NAME)) {
            $disk->delete($user->id.'/'.self::FILE_NAME);
        }
    }
}
