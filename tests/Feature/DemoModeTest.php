<?php

use App\Models\Category;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * Ruta del archivo SQLite que usa la demo durante las pruebas.
 */
function demoDatabasePath(): string
{
    return storage_path('framework/testing/demo.sqlite');
}

/**
 * Borra la base de datos de la demo y los archivos que SQLite deja al lado.
 */
function deleteDemoDatabase(): void
{
    $path = demoDatabasePath();

    File::delete([$path, $path.'.tmp', $path.'-wal', $path.'-shm']);
}

beforeEach(function () {
    Config::set('database.connections.demo', array_merge(
        Config::get('database.connections.demo'),
        ['database' => demoDatabasePath()],
    ));

    closeDemoConnections();
    deleteDemoDatabase();
});

afterEach(function () {
    closeDemoConnections();
    deleteDemoDatabase();
});

/**
 * Cierra los PDO de la demo: en Windows no se puede borrar un archivo abierto.
 */
function closeDemoConnections(): void
{
    DB::purge('demo');
    DB::purge('demo_build');
}

test('la entrada a la demo falla si la base de datos no existe', function () {
    $this->get('/demo')->assertStatus(503);
});

test('sin la cookie de demo el dashboard sigue pidiendo login', function () {
    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('la demo usa su propia base de datos y permite escribir en ella', function () {
    $this->artisan('demo:refresh')->assertSuccessful();

    $this->withCookie(config('demo.cookie'), '1')
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('demo', true));

    $this->withCookie(config('demo.cookie'), '1')
        ->post(route('categories.store'), [
            'name' => 'Categoría demo',
            'type' => 'expense',
        ])
        ->assertRedirect();

    expect(Category::on('demo')->where('name', 'Categoría demo')->exists())->toBeTrue()
        ->and(Category::where('name', 'Categoría demo')->exists())->toBeFalse();
});

test('el login de la demo no se guarda en la sesión real', function () {
    $this->artisan('demo:refresh')->assertSuccessful();

    $this->withCookie(config('demo.cookie'), '1')
        ->get(route('dashboard'))
        ->assertOk();

    // Sin la cookie y sin guardas cacheadas, la sesión no debe autenticar a nadie.
    $this->defaultCookies = [];
    $this->app['auth']->forgetGuards();

    $this->get(route('dashboard'))->assertRedirect(route('login'));
});

test('la demo limita las escrituras y bloquea la ip cuando se abusa', function () {
    $this->artisan('demo:refresh')->assertSuccessful();

    $limit = (int) config('demo.throttle.writes_per_minute');

    foreach (range(1, $limit) as $i) {
        $this->withCookie(config('demo.cookie'), '1')
            ->post(route('categories.store'), [
                'name' => "Categoría {$i}",
                'type' => 'expense',
            ])
            ->assertRedirect();
    }

    $this->withCookie(config('demo.cookie'), '1')
        ->post(route('categories.store'), [
            'name' => 'Categoría extra',
            'type' => 'expense',
        ])
        ->assertStatus(429);

    // La IP queda bloqueada, también para lecturas.
    $this->withCookie(config('demo.cookie'), '1')
        ->get(route('dashboard'))
        ->assertStatus(429);
});
