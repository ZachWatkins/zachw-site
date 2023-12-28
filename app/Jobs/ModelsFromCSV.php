<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Storage;

class ModelsFromCSV implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    const DB_VALUE_LIMIT = 2000;

    const ROW_LIMIT = 1000;

    /**
     * Create a new job instance.
     *
     * @param  string  $model             Model class string.
     * @param  string  $source            Path to CSV file to import.
     * @param  array  $defaultAttributes Optional. Key values added to new model records.
     */
    public function __construct(
        protected string $model,
        protected string $source,
        protected array $defaultAttributes = []
    ) {
        if (! Storage::exists($source)) {
            throw new \InvalidArgumentException('File not found at '.$source);
        }
        $stream = fopen(storage_path('app/'.$source), 'r');
        $headers = fgetcsv($stream);
        fclose($stream);
        if (! $headers || count($headers) === 1) {
            throw new \InvalidArgumentException('The CSV file is missing a header on the first row');
        }
        if (! class_exists($model)) {
            throw new \InvalidArgumentException('Model not found: '.$model);
        }
    }

    public function handle(): void
    {
        $model = new $this->model();
        $abspath = storage_path('app/'.$this->source);
        $stream = fopen($abspath, 'r');
        $headers = fgetcsv($stream);
        $batch_size = (int) floor(self::DB_VALUE_LIMIT / count($headers));
        $batch = [];
        $line = 0;

        if (! $this->defaultAttributes) {

            while (($record = fgetcsv($stream))) {
                $batch[] = array_combine($headers, $record);
                $line++;
                if ($batch_size === $line) {
                    $model::insert($batch);
                    $batch = [];
                    $line = 0;
                }
            }

        } else {

            array_push($headers, ...array_keys($this->defaultAttributes));
            $attribute_values = array_values($this->defaultAttributes);

            while (($record = fgetcsv($stream))) {
                array_push($record, ...$attribute_values);
                $batch[] = array_combine($headers, $record);
                $line++;
                if ($batch_size === $line) {
                    $model::insert($batch);
                    $batch = [];
                    $line = 0;
                }
            }
        }

        fclose($stream);
        if ($batch) {
            $model::insert($batch);
        }

        unlink($abspath);
    }
}
