<?php

use App\Services\Combustible\Database;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    // Sin credenciales ni conexiones reales: todas las conexiones son dobles.
    config(['combustible.connection' => null, 'database.connections' => []]);
});

it('configures the real operating database name', function () {
    expect(config('combustible.database'))->toBe('bdcbcsirus');
});

it('uses the configured alias and checks the actual database on MySQL', function () {
    config(['combustible.connection' => 'operativa_configurable']);
    $connection = Mockery::mock(Connection::class);
    $connection->shouldReceive('getDriverName')->andReturn('mysql');
    $connection->shouldReceive('selectOne')->once()->with('SELECT DATABASE() AS db')
        ->andReturn((object) ['db' => 'bdcbcsirus']);
    $connection->shouldReceive('statement')->once()->with('SET SESSION lock_wait_timeout = 5')->andReturn(true);
    DB::shouldReceive('connection')->once()->with('operativa_configurable')->andReturn($connection);

    expect(app(Database::class)->connection())->toBe($connection);
});

it('discovers a unique matching MySQL connection without assuming its alias', function () {
    config(['database.connections' => [
        'otra' => ['driver' => 'mysql', 'database' => 'otra_base'],
        'operativa_descubierta' => ['driver' => 'mysql', 'database' => 'bdcbcsirus'],
    ]]);
    $connection = Mockery::mock(Connection::class);
    $connection->shouldReceive('getDriverName')->andReturn('mysql');
    $connection->shouldReceive('selectOne')->once()->with('SELECT DATABASE() AS db')
        ->andReturn((object) ['db' => 'bdcbcsirus']);
    $connection->shouldReceive('statement')->once()->with('SET SESSION lock_wait_timeout = 5')->andReturn(true);
    DB::shouldReceive('connection')->once()->with('operativa_descubierta')->andReturn($connection);

    expect(app(Database::class)->connection())->toBe($connection);
});

it('rejects an actual database different from the configured one', function () {
    config(['combustible.connection' => 'operativa_configurable']);
    $connection = Mockery::mock(Connection::class);
    $connection->shouldReceive('getDriverName')->andReturn('mysql');
    $connection->shouldReceive('selectOne')->once()->with('SELECT DATABASE() AS db')
        ->andReturn((object) ['db' => 'otra_base']);
    DB::shouldReceive('connection')->once()->with('operativa_configurable')->andReturn($connection);

    expect(fn () => app(Database::class)->connection())->toThrow(RuntimeException::class, 'bdcbcsirus');
});

it('compares the actual database against the configuration value', function () {
    config(['combustible.connection' => 'alias_de_prueba', 'combustible.database' => 'base_configurada']);
    $connection = Mockery::mock(Connection::class);
    $connection->shouldReceive('getDriverName')->andReturn('mysql');
    $connection->shouldReceive('selectOne')->once()->with('SELECT DATABASE() AS db')
        ->andReturn((object) ['db' => 'base_configurada']);
    $connection->shouldReceive('statement')->once()->with('SET SESSION lock_wait_timeout = 5')->andReturn(true);
    DB::shouldReceive('connection')->once()->with('alias_de_prueba')->andReturn($connection);

    expect(app(Database::class)->connection())->toBe($connection);
});

it('rejects an unsupported driver without querying a database', function () {
    config(['combustible.connection' => 'alias_de_prueba']);
    $connection = Mockery::mock(Connection::class);
    $connection->shouldReceive('getDriverName')->andReturn('pgsql');
    $connection->shouldNotReceive('selectOne');
    DB::shouldReceive('connection')->once()->with('alias_de_prueba')->andReturn($connection);

    expect(fn () => app(Database::class)->connection())->toThrow(RuntimeException::class);
});

it('requires explicit selection when automatic discovery is ambiguous', function () {
    config(['database.connections' => [
        'una' => ['driver' => 'mysql', 'database' => 'bdcbcsirus'],
        'otra' => ['driver' => 'mysql', 'database' => 'bdcbcsirus'],
    ]]);
    DB::shouldReceive('connection')->never();

    expect(fn () => app(Database::class)->connection())->toThrow(RuntimeException::class, 'COMBUSTIBLE_DB_CONNECTION');
});

it('provides a separate fuel alias reusing DB2 access without changing its database', function () {
    $settings = require base_path('config/database.php');
    $fuel = $settings['connections']['mysql_combustible'];
    $existing = $settings['connections']['mysql2'];
    expect($fuel['driver'])->toBe('mysql')->and($fuel['database'])->toBe('bdcbcsirus');
    expect($fuel['options'])->toBe(extension_loaded('pdo_mysql') ? [PDO::ATTR_TIMEOUT => 5] : []);
    unset($fuel['database'], $fuel['options'], $existing['database']);
    // Comparar huellas evita exponer credenciales incluso si la aserción falla.
    expect(hash('sha256', serialize($fuel)))->toBe(hash('sha256', serialize($existing)));
});

it('rejects mysql2 when it points to the other modules database', function () {
    config(['combustible.connection' => 'mysql2']);
    $connection = Mockery::mock(Connection::class);
    $connection->shouldReceive('getDriverName')->andReturn('mysql');
    $connection->shouldReceive('selectOne')->once()->with('SELECT DATABASE() AS db')
        ->andReturn((object) ['db' => 'bdcmcsirus']);
    DB::shouldReceive('connection')->once()->with('mysql2')->andReturn($connection);

    expect(fn () => app(Database::class)->connection())->toThrow(RuntimeException::class, 'bdcbcsirus');
});

it('aggregates MySQL totals in the server and preserves decimal precision and filters', function () {
    $connection = Mockery::mock(Connection::class);
    $connection->shouldReceive('getDriverName')->andReturn('mysql');
    $connection->shouldReceive('select')->once()->withArgs(function ($sql, $bindings, $read) {
        return str_contains($sql, 'SUM(Litros)') && str_contains($sql, 'SUM(Importe)')
            && ! str_contains($sql, 'select *') && $bindings === ['2026-01-01', '2026-01-31', 'PM', 'A'];
    })->andReturn([(object) ['litros' => '99999999999.999', 'importe' => '999999999999.99']]);
    $query = new \Illuminate\Database\Query\Builder($connection, new \Illuminate\Database\Query\Grammars\MySqlGrammar, new \Illuminate\Database\Query\Processors\Processor);
    $query->from('tblcargascombustible')->whereBetween('FechaTrabajo', ['2026-01-01', '2026-01-31'])->where('Turno', 'PM');
    $totals = app(\App\Services\Combustible\Reports::class)->totals($query);
    expect($totals)->toBe(['Litros' => '99999999999.999', 'Importe' => '999999999999.99'])
        ->and($query->getBindings())->toBe(['2026-01-01', '2026-01-31', 'PM']);
});

it('runs the callback directly with the connection for every action without acquiring table locks', function (string $action) {
    $connection = Mockery::mock(Connection::class);
    $connection->shouldNotReceive('statement')->withArgs(fn ($sql) => str_starts_with($sql, 'LOCK TABLES'));
    $connection->shouldNotReceive('statement')->with('UNLOCK TABLES');
    $service = Mockery::mock(Database::class)->makePartial();
    $service->shouldReceive('connection')->once()->andReturn($connection);

    expect($service->locked(fn ($db) => $db === $connection, $action))->toBeTrue();
})->with(['CREAR', 'EDITAR', 'CANCELAR', 'PRECIO', 'ELIMINAR_PRECIO']);

it('propagates callback exceptions without wrapping them', function () {
    $connection = Mockery::mock(Connection::class);
    $service = Mockery::mock(Database::class)->makePartial();
    $service->shouldReceive('connection')->once()->andReturn($connection);
    $error = new RuntimeException('Error en el callback');
    $caught = null;
    $executed = false;
    try {
        $service->locked(function () use (&$executed, $error) {
            $executed = true;
            throw $error;
        });
    } catch (Throwable $e) {
        $caught = $e;
    }
    expect($executed)->toBeTrue()->and($caught)->toBe($error);
});
