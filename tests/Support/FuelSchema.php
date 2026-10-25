<?php

namespace Tests\Support;

use Illuminate\Support\Facades\DB;

class FuelSchema
{
    public static function connect(): void
    {
        $mysql = getenv('FUEL_MYSQL_TEST') === '1';
        config(['database.connections.fuel_test' => $mysql ? [
            'driver' => 'mysql', 'host' => '127.0.0.1', 'port' => 33075,
            'database' => 'bdcmcsirus', 'username' => 'root', 'password' => '',
            'charset' => 'utf8mb4', 'collation' => 'utf8mb4_unicode_ci', 'strict' => true,
        ] : ['driver' => 'sqlite', 'database' => ':memory:', 'foreign_key_constraints' => true]]);
        config(['combustible.connection' => 'fuel_test']);
        DB::purge('fuel_test');
    }

    public static function prepare(): void
    {
        self::connect();
        $db = DB::connection('fuel_test');
        $mysql = getenv('FUEL_MYSQL_TEST') === '1';
        if ($mysql) {
            if (! str_starts_with($db->selectOne('SELECT VERSION() AS v')->v, '5.7.')) {
                throw new \RuntimeException('La suite MyISAM requiere MySQL 5.7 en el contenedor de prueba, puerto 33075.');
            }
            // Exclusivamente el contenedor local desechable de prueba. Nunca usar conexiones de la aplicación.
            foreach (['tblcargascombustible', 'tblavisospreciodiesel', 'tblcostos', 'catunidades', 'tblexcepciones'] as $table) {
                $db->statement('DROP TABLE IF EXISTS '.$table);
            }
        }
        $id = $mysql ? 'INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
        $engine = $mysql ? ' ENGINE=MyISAM DEFAULT CHARSET=latin1' : '';
        $db->statement("CREATE TABLE tblcargascombustible (
            IdCarga $id, FechaCarga DATE NOT NULL, FechaTrabajo DATE NOT NULL, Turno CHAR(2) NOT NULL DEFAULT 'PM',
            IdUnidad INT NOT NULL, Unidad VARCHAR(6) NOT NULL, Litros DECIMAL(11,3) NOT NULL,
            IdCosto TINYINT NOT NULL DEFAULT 2, FechaPrecio DATE NOT NULL, PrecioLitro DECIMAL(9,3) NOT NULL,
            PorcDto DECIMAL(9,4) NOT NULL DEFAULT 0, Importe DECIMAL(14,2) NOT NULL,
            Observaciones VARCHAR(255), Estatus CHAR(1) NOT NULL DEFAULT 'A', ControlDuplicado TINYINT DEFAULT 1,
            TokenRegistro CHAR(36) NOT NULL UNIQUE, UsuarioRegistro VARCHAR(150) NOT NULL, FechaRegistro DATETIME NOT NULL,
            UsuarioModificacion VARCHAR(150), FechaModificacion DATETIME,
            UsuarioCancelacion VARCHAR(150), FechaCancelacion DATETIME, MotivoCancelacion VARCHAR(255),
            UNIQUE (IdUnidad, FechaCarga, FechaTrabajo, Turno, Litros, ControlDuplicado)
        )$engine");
        $db->statement("CREATE TABLE tblavisospreciodiesel (IdAviso $id, IdUsuario BIGINT NOT NULL,
            Usuario VARCHAR(150) NOT NULL, Fecha DATE NOT NULL, FechaAviso DATETIME NOT NULL,
            Respuesta VARCHAR(15) NOT NULL DEFAULT 'MOSTRADO', FechaRespuesta DATETIME, UNIQUE (IdUsuario, Fecha))$engine");
        $db->statement("CREATE TABLE tblcostos (IdCosto TINYINT NOT NULL, Fecha DATE NOT NULL, Costo DECIMAL(9,3), PorcDto DECIMAL(9,4) DEFAULT 0, PRIMARY KEY (IdCosto, Fecha))$engine");
        $db->statement("CREATE TABLE catunidades (IdUnidad $id, Unidad VARCHAR(6) NOT NULL, Estatus CHAR(1))$engine");
        $db->statement("CREATE TABLE tblexcepciones (Fecha DATETIME, Usuario VARCHAR(30), Modulo VARCHAR(30), Excepcion VARCHAR(30), Datos TINYTEXT)$engine");
        $db->table('catunidades')->insert([['IdUnidad' => 1, 'Unidad' => '001', 'Estatus' => 'A'], ['IdUnidad' => 2, 'Unidad' => '002', 'Estatus' => 'I']]);
        $db->table('tblcostos')->insert([
            ['IdCosto' => 2, 'Fecha' => '2026-01-01', 'Costo' => '24.567', 'PorcDto' => '0.0000'],
            ['IdCosto' => 2, 'Fecha' => '2026-02-01', 'Costo' => '25.123', 'PorcDto' => '0.0000'],
        ]);
    }
}
