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
    unset($fuel['database'], $existing['database']);
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
