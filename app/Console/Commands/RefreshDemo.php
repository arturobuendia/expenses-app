<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

/**
 * Rebuilds the public demo database from scratch.
 *
 * The demo is built on a temporary file and swapped in atomically, so a reset
 * never leaves visitors staring at "no such table" errors while it runs.
 *
 * Note: it uses `migrate` (not `migrate:fresh`) because the temporary file is
 * always empty, and AppServiceProvider prohibits destructive commands in
 * production.
 */
class RefreshDemo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'demo:refresh';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Rebuild the demo database with fresh seeded data';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $path = Config::get('database.connections.demo.database');

        if (! is_string($path) || $path === '') {
            $this->error('La conexión "demo" no tiene una ruta válida (revisa DEMO_DB_PATH).');

            return self::FAILURE;
        }

        File::ensureDirectoryExists(dirname($path));

        $build = $path.'.tmp';

        // SQLiteConnector exige que el archivo exista antes de conectarse.
        File::delete([$build, $build.'-wal', $build.'-shm']);
        touch($build);

        $originalDefault = Config::get('database.default');

        Config::set('database.connections.demo_build', array_merge(
            Config::get('database.connections.demo'),
            ['database' => $build],
        ));

        // Los seeders no especifican conexión: apuntamos la default al archivo
        // temporal para que no toquen la base de datos real.
        Config::set('database.default', 'demo_build');

        try {
            $this->call('migrate', [
                '--database' => 'demo_build',
                '--seed' => true,
                '--force' => true,
            ]);

            User::on('demo_build')->forceCreate([
                'name' => config('demo.user.name'),
                'email' => config('demo.user.email'),
                'password' => Str::password(40),
                'email_verified_at' => now(),
            ]);
        } finally {
            Config::set('database.default', $originalDefault);
            DB::purge('demo_build');
            DB::purge('demo');
        }

        if (! $this->swap($build, $path)) {
            $this->error("No se pudo reemplazar {$path}. ¿Está abierto por el servidor?");

            return self::FAILURE;
        }

        $this->info("Demo reconstruida en {$path}.");

        return self::SUCCESS;
    }

    /**
     * Replaces the live demo file with the freshly built one.
     *
     * rename() is atomic on POSIX. On Windows it can fail when the file is
     * open, so fall back to deleting it first.
     */
    private function swap(string $build, string $path): bool
    {
        File::delete([$path.'-wal', $path.'-shm']);

        if (@rename($build, $path)) {
            return true;
        }

        if (@unlink($path) && @rename($build, $path)) {
            return true;
        }

        return false;
    }
}
