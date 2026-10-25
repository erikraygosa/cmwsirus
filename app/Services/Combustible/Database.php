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

        // ATTR_TIMEOUT sólo limita la conexión. MySQL usa otro límite para metadatos.
        $connection->statement('SET SESSION lock_wait_timeout = 5');

        return $connection;
    }

    public function locked(callable $callback): mixed
    {
        $db = $this->connection();
        $mysql = $db->getDriverName() !== 'sqlite';
        if ($mysql) {
            // MyISAM no tiene rollback. Incluimos todas las tablas utilizadas y no usamos alias.
            try {
                $db->statement('LOCK TABLES tblcargascombustible WRITE, tblcostos WRITE, catunidades READ, tblavisospreciodiesel WRITE, tblexcepciones WRITE');
            } catch (\Throwable $e) {
                // Si no se adquirieron todos, cerrar la sesión libera cualquier bloqueo parcial.
                // El callback aún no comenzó y no se ha realizado ninguna escritura de negocio.
                $db->disconnect();
                throw $e;
            }
            // Una reconexión perdería LOCK TABLES; nunca reintentar una sentencia sin su bloqueo.
            $db->setReconnector(fn () => throw new RuntimeException('Se perdió la conexión con los bloqueos. Reintente la operación completa con el mismo identificador.'));
        }
        try {
            return $callback($db);
        } finally {
            if ($mysql) {
                try {
                    $db->statement('UNLOCK TABLES');
                } finally {
                    $db->setReconnector(fn ($connection) => DB::reconnect($connection->getNameWithReadWriteType()));
                }
            }
        }
    }
}
