<?php

namespace App\Support;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Locates and provides the public demo database.
 *
 * The demo does not need to survive a deploy, so its file lives on the
 * application filesystem (ephemeral in production) instead of a persistent
 * volume. Because of that, it is built on first use: the command writes a
 * temporary file and renames it, so concurrent visitors never see a half
 * built database.
 */
class DemoDatabase
{
    /**
     * Cache lock that serializes rebuilds.
     */
    private const LOCK = 'demo:refresh';

    /**
     * Configured path of the demo database, if any.
     */
    public static function path(): ?string
    {
        $path = config('database.connections.demo.database');

        return is_string($path) && $path !== '' ? $path : null;
    }

    /**
     * Whether the demo database file already exists.
     */
    public static function exists(): bool
    {
        $path = static::path();

        return $path !== null && is_file($path);
    }

    /**
     * Build the demo database when it is missing.
     *
     * Returns false when it could not be built, so the caller can answer 503
     * instead of leaking a "no such table" error.
     */
    public static function ensureExists(): bool
    {
        if (static::exists()) {
            return true;
        }

        try {
            Cache::lock(self::LOCK, 120)->block(15, function (): void {
                if (! static::exists()) {
                    Artisan::call('demo:refresh');
                }
            });
        } catch (Throwable $e) {
            report($e);
        }

        return static::exists();
    }
}
