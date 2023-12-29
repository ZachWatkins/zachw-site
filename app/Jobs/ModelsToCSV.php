<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class ModelsToCSV implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    const CHUNK_SIZE = 500;

    /**
     * Export models from a database to a CSV file on a disk.
     *
     * @param  string  $model The model to export.
     * @param  string  $disk The disk to export to.
     * @param  string  $destination The destination path to export to.
     * @param  array  $query {
     *     The query to apply to the model (optional).
     *
     *     @type array        $select  The Select clause.
     *     @type array        $where   The where clause.
     *     @type string|array $orderBy The orderBy clause. Accepts a column
     *                                 name or an array of arrays of column
     *                                 names and directions ('asc', 'desc').
     * }
     */
    public function __construct(
        protected string $model,
        protected string $disk,
        protected string $destination = 'models.csv',
        protected array $query = []
    ) {
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $disk_root = config("filesystems.disks.{$this->disk}.root");
        $csv_headers = $this->query['select']
            ?? Schema::getColumnListing((new $this->model())->getTable());

        $disk = Storage::disk($this->disk);
        $disk->makeDirectory(dirname($this->destination));

        $stream = fopen("$disk_root/$this->destination", 'w');

        fputcsv($stream, $csv_headers);

        foreach ($this->query()->lazy(self::CHUNK_SIZE) as $model) {
            // The model's attributes may have line breaks.
            fputcsv($stream, array_map(
                fn ($value) => str_replace("\n", ' ', $value),
                $model->toArray()
            ));
        }

        fclose($stream);
    }

    /**
     * Define the database query using the job parameters.
     */
    protected function query(): Builder
    {
        $model = new $this->model();
        $params = $this->query;

        // Define the database query.
        $query = $model::select($params['select'] ?? '*');

        // Handle the where clause.
        if (isset($params['where'])) {
            // If the where clause is a single-dimensional array, wrap it in
            // another array so that it can be passed to the where method.
            $first_key = array_key_first($params['where']);
            if (is_string($params['where'][$first_key])) {
                $params['where'] = [$params['where']];
            }
            $query->where($params['where']);
        }

        // Handle the orderby clause.
        if (isset($params['orderBy'])) {
            if (is_string($params['orderBy'])) {
                $query->orderBy($params['orderBy']);
            } else {
                foreach ($params['orderBy'] as $order) {
                    $query->orderBy($order[0], $order[1]);
                }
            }
        }

        return $query;
    }
}
