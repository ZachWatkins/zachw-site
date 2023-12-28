<?php

use Illuminate\Support\Facades\DB;

if (! function_exists('delete_files_before')) {
    /**
     * Delete files before a given date, optionally within a given path pattern.
     *
     * @param  int|string  $date     Date to search before.
     * @param  string  $pathLike Path pattern for files to search in.
     */
    function delete_files_before(int|string $date, string $pathLike = ''): void
    {
        if (is_string($date)) {
            $date = strtotime($date);
        }
        $disk = \Illuminate\Support\Facades\Storage::disk();
        foreach ($disk->allFiles($pathLike) as $file) {
            if ($disk->lastModified($file) < $date) {
                $disk->delete($file);
            }
        }
    }
}

trait SetsPdoTimeout
{
    private static string $pdo_timeout_config_key;

    private static string $pdo_timeout_attribute;

    private static $pdo_timeout_config_original;

    private static function setPdoTimeout(int $value = -1)
    {
        $key = self::$pdo_timeout_config_key || self::getPdoTimeoutConfigKey();
        if ($value < 0) {
            $value = self::$pdo_timeout_config_original;
        }
        config([$key => $value]);
        if (DB::connection() && DB::connection()->getPdo()) {
            DB::connection()->getPdo()->setAttribute(self::$pdo_timeout_attribute, $value);
        }
    }

    private static function getPdoTimeoutConfigKey(): string
    {
        if (self::$pdo_timeout_config_key) {
            return self::$pdo_timeout_config_key;
        }
        $connection = config('database.default');
        self::$pdo_timeout_config_key = 'database.connections.'.$connection.'.options.'.constant(self::getTimeoutAttrName());
        $config_value = config(self::$pdo_timeout_config_key);
        if (! is_numeric($config_value) || $config_value < 0) {
            $config_value = 0;
        }
        self::$pdo_timeout_config_original = $config_value;

        return self::$pdo_timeout_config_key;
    }

    private static function getTimeoutAttrName(): string
    {
        if (self::$pdo_timeout_attribute) {
            return self::$pdo_timeout_attribute;
        }
        // Ordered by vendor-specific first for feature detection.
        $timeout_attrs = [
            'PDO::SQLSRV_ATTR_QUERY_TIMEOUT',
            'PDO::ATTR_TIMEOUT',
        ];
        foreach ($timeout_attrs as $attribute) {
            if (! defined($attribute) || constant($attribute) === null) {
                continue;
            }
            try {
                if (DB::connection()->getPdo()) {
                    // Test attribute support.
                    $value = DB::connection()->getPdo()->getAttribute(constant($attribute));
                    DB::connection()->getPdo()->setAttribute($attribute, $value + 1);
                    DB::connection()->getPdo()->setAttribute($attribute, $value);

                    return self::$pdo_timeout_attribute = $attribute;
                }
            } catch (\PDOException $e) {
                continue;
            }
        }

        return self::$pdo_timeout_attribute = 'PDO::TIMEOUT_ATTR_UNDEFINED';
    }
}

class Retryable_Query
{
    use SetsPdoTimeout;

    /**
     * Handle a retryable query.
     *
     * @author  Zachary K. Watkins <zwatkins.it@gmail.com>
     *
     * @param  callable  $callable A callable function which includes a query.
     * @param  int  $timeout  A parameter.
     * @return void
     */
    public static function handle(callable $callable, int $timeout = -1)
    {
        self::setPdoTimeout($timeout);
        try {
            $result = $callable();
            self::setPdoTimeout();

            return $result;
        } catch (\PDOException $exception) {
            if (! self::canRetry($exception) || ! self::reconnect()) {
                throw $exception;
            }
        }

        $retryTimeout = function ($attempt) {
            $jitter = \rand(110, 90) / 100;
            $wait_ms = [5000, 8000, 15000, 30000, 60000];
            $timeout_sec = [10, 15, 20, 30, 60];
            self::setPdoTimeout($timeout_sec[$attempt - 1] * $jitter);

            return $wait_ms[$attempt - 1] * $jitter;
        };

        $result = retry(6, $callable, $retryTimeout, fn ($exception) => self::canRetry($exception));

        self::setPdoTimeout();

        return $result;
    }

    /**
     * Attempt to reconnect to the database if an exception is retryable.
     *
     * @author Zachary K. Watkins <zwatkins.it@gmail.com>
     */
    private static function reconnect(): bool
    {
        $i = 0;
        $reconnector = function () use (&$i) {
            $j = $i++;
            $reconnect_timeout_sec = [25, 30, 45, 60, 120];
            $reconnect_wait_sec = [5, 10, 15, 30, 60];
            self::setPdoTimeout($reconnect_timeout_sec[$j]);
            DB::disconnect();
            sleep($reconnect_wait_sec[$j]);
            DB::reconnect();
            self::setPdoTimeout();

            return true;
        };

        try {
            return retry(6, $reconnector, 0, fn ($exception) => self::canRetry($exception));
        } catch (\PDOException $exception) {
            self::setPdoTimeout();

            return false;
        }
    }

    /**
     * Reads PDOException error codes and compares them against the configured retryable codes.
     *
     * @author Zachary K. Watkins <zwatkins.it@gmail.com>
     *
     * @param  \PDOException  $exception The thrown exception.
     * @return bool Whether the query can be retried.
     */
    private static function canRetry(PDOException $exception): bool
    {
        $dbconnection = config('database.default');
        $retryable = config("database.connections.{$dbconnection}.retryable_codes", []);
        $code = (string) $exception->getCode();
        $subcode = (string) $exception->errorInfo[1];
        $message = $exception->getMessage();
        if (isset($retryable[$code])) {
            if ($retryable[$code] === true || in_array($subcode, $retryable, true)) {
                return true;
            }
        } elseif (strpos($message, 'nable to connect') !== false) {
            return true;
        } elseif (strpos($message, 'onnection timed out') !== false) {
            return true;
        }

        return false;
    }
}
