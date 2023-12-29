<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class ZipUserFiles implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    const USER_STORAGE = 'user';

    /**
     * Create a new instance.
     *
     * @param  int  $user_id     User ID to archive files for.
     * @param  array  $files       One or more file names or patterns to compress.
     * @param  string  $destination Destination zip file path.
     * @param  bool  $delete      Whether to delete the uncompressed files. Default true.
     */
    public function __construct(
        private int $user_id,
        private array $files,
        private string $destination,
        private bool $delete = true
    ) {
    }

    public function handle(): void
    {
        $userDirectory = self::USER_STORAGE.'/'.$this->user_id.'/';
        $userDestination = $userDirectory.$this->destination;

        if (Storage::directoryMissing($userDestination)) {
            Storage::createDirectory($userDestination);
        }

        $zip = new ZipArchive();
        $zip->open(
            Storage::path($userDestination),
            ZipArchive::CREATE | ZipArchive::OVERWRITE
        );

        foreach ($this->files as $file) {
            $zip->addFile(
                Storage::path($userDirectory.$file),
                basename($file)
            );
        }

        $zip->close();

        if (! $this->delete) {
            return;
        }

        foreach ($this->files as $file) {
            Storage::delete($userDirectory.$file);
        }
    }
}
