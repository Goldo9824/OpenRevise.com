<?php

namespace App\Support;

use App\Models\Admin\ErrorLog;
use Illuminate\Support\Str;
use Throwable;

/** Copies reported errors into the Admin DB so they show in the admin logs. */
class ErrorRecorder
{
    protected static bool $recording = false;

    public static function record(Throwable $e): void
    {
        if (static::$recording || app()->runningUnitTests()) {
            return;
        }

        static::$recording = true;

        try {
            ErrorLog::create([
                'level' => 'error',
                'message' => Str::limit($e->getMessage() ?: class_basename($e), 2000),
                'exception' => get_class($e),
                'file' => Str::limit(str_replace(base_path().'/', '', $e->getFile()), 290),
                'line' => $e->getLine(),
                'url' => app()->runningInConsole() ? 'console' : Str::limit(request()->fullUrl(), 490),
                'trace' => Str::limit($e->getTraceAsString(), 8000),
            ]);
        } catch (Throwable) {
            // The admin database may be the thing that failed; the log file still has it.
        } finally {
            static::$recording = false;
        }
    }
}
