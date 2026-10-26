<?php

namespace App\Services\Combustible;

use Illuminate\Database\Connection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class Database
{
    public function connection(): Connection
    {
        $name = config('combustible.connection');
        if (! $name) {
            $matches = collect(config('database.connections'))->filter(fn ($c) => in_array($c['driver'] ?? '', ['mysql', 'mariadb'], true)
                && ($c['database'] ?? null) === config('combustible.database'));
            if ($matches->count() !== 1) {
                throw new RuntimeException('Configure COMBUSTIBLE_DB_CONNECTION con la conexión existente a bdcbcsirus. No hay una selección única.');
            }
            $name = $matches->keys()->first();
        }
        $connection = DB::connection($name);
        if (app()->environment('testing') && $connection->getDriverName() === 'sqlite') {
            return $connection; // Sólo dobles de prueba; no valida MyISAM.
        }
        if (! in_array($connection->getDriverName(), ['mysql', 'mariadb'], true)
            || $connection->selectOne('SELECT DATABASE() AS db')->db !== config('combustible.database')) {
            throw new RuntimeException('La conexión de combustible debe apuntar a bdcbcsirus en MySQL.');
        }

        // Limita la espera por MDL (p.ej. si un ALTER TABLE retiene un metadata lock).
        // No afecta la duración de consultas SQL normales.
        $connection->statement('SET SESSION lock_wait_timeout = 5');

        return $connection;
    }

    public function locked(callable $callback, string $action = 'CREAR'): mixed
    {
        // Las claves únicas (uq_carga_token, uq_carga_activa, PK de tblcostos) y los hashes de
        // versión cubren todos los invariantes sin bloquear tablas completas. LOCK TABLES en MyISAM
        // serializa lecturas y escrituras de todos los clientes mientras el bloqueo está activo;
        // con varias tablas bloqueadas a la vez, un único request largo bloquea el módulo entero.
        // El journal de escritura anticipada y las restricciones de unicidad garantizan idempotencia,
        // detección de conflictos y recuperación sin esa serialización.
        $db = $this->connection();
        $mysql = $db->getDriverName() !== 'sqlite';
        // Impide que Laravel reintente sentencias en una sesión nueva si se pierde la conexión.
        // MyISAM no tiene rollback: una sentencia reintentada puede duplicar escrituras.
        if ($mysql) {
            $db->setReconnector(fn () => throw new RuntimeException('Se perdió la conexión durante la operación. Reintente con el mismo identificador.'));
        }
        try {
            return $callback($db);
        } finally {
            if ($mysql) {
                $db->setReconnector(fn ($connection) => DB::reconnect($connection->getNameWithReadWriteType()));
            }
        }
    }
}
