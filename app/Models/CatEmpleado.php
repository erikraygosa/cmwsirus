<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CatEmpleado extends Model
{
    protected $connection = 'mysql2';
    protected $table = 'catempleados';
    protected $primaryKey = 'IdEmpleado';
    public $incrementing = false;
    public $timestamps = false;

    protected static function booted(): void
    {
        static::addGlobalScope('activos', function (Builder $query) {
            $query->where('Estatus', 'A');
        });
    }

    protected $fillable = [
        'IdEmpresa', 'IdEmpleado', 'Unidad', 'IdRuta', 'IdPuesto',
        'CURP', 'Nombre', 'Direccion', 'Telefono',
        'FechaNac', 'FechaIng', 'NumAfilIMSS',
        'Estatus', 'Foto', 'FechaVL', 'envio',
    ];

    public static function nextId(): int
    {
        return (int)(self::max('IdEmpleado') ?? 0) + 1;
    }
}