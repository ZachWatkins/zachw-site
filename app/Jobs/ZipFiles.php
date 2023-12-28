<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class ZipFiles implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new instance.
     *
     * @param  array  $files       One or more file names or patterns to compress.
     * @param  string  $destination Destination zip file path.
     * @param  bool  $delete      Whether to delete the uncompressed files. Default true.
     */
    public function __construct(
        private array $files,
        private string $destination,
        private bool $delete = true
    ) {
    }

    public function handle(): void
    {
        if (Storage::directoryMissing($this->destination)) {
            Storage::createDirectory($this->destination);
        }

        $zip = new ZipArchive();
        $zip->open(
            Storage::path($this->destination),
            ZipArchive::CREATE | ZipArchive::OVERWRITE
        );

        foreach ($this->files as $file) {
            $zip->addFile(
                Storage::path($file),
                basename($file)
            );
        }

        $zip->close();

        if (! $this->delete) {
            return;
        }

        foreach ($this->files as $file) {
            Storage::delete($file);
        }
    }
}
