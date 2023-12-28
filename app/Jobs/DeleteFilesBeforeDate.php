<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Storage;

/**
 * Delete files with a given disk, optionally within a path prefix, before a given date.
 */
class DeleteFilesBeforeDate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    protected int $date;

    protected $disk;

    /**
     * Delete files with a given disk, optionally within a path prefix, before a given date.
     *
     * @param  string  $diskName Disk to search in.
     * @param  int|string  $date     Date to search before.
     * @param  string  $prefix   Path prefix for files to search for.
     */
    public function __construct(
        protected string $diskName,
        int|string $date = 'now',
        protected string $prefix = ''
    ) {
        $this->date = is_string($date) ? strtotime($date) : $date;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $this->disk = Storage::disk($this->diskName);
        foreach ($this->files() as $file) {
            $this->disk->delete($file);
        }
    }

    /**
     * Get the files to delete in a memory-friendly way.
     */
    private function files(): \Generator
    {
        foreach ($this->disk->allFiles($this->prefix) as $file) {
            if ($this->disk->lastModified($file) < $this->date) {
                yield $file;
            }
        }
    }
}
