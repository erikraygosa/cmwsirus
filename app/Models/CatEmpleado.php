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
        'IdEmpleado', 'IdPuesto', 'CURP', 'Nombre', 'Direccion',
        'Telefono', 'FechaNac', 'FechaIng', 'FechaVL', 'NumAfilIMSS',
        'Licencia', 'CursoATY', 'LicVerif', 'email', 'NumNomina',
        'Sexo', 'TipoSangre', 'NumHijos', 'Escolaridad', 'HablaMaya',
        'EstadoCivil', 'INE', 'EmpresaAnt', 'UniMedTmpEA', 'TmpLabEA',
        'MotivoBajaEA', 'RecibeFintoEA', 'FchRecibeFinto', 'ComoSupoVacante',
        'INFONAVIT', 'FONACOT', 'Observ', 'YUTONG', 'ConoceRutas',
        'Contrato', 'TjtHSBC', 'CLABE', 'Password', 'NIPCarga',
        'NumPant', 'NumCami', 'ImpartioInducc', 'TelefonoEmer', 'IdVinden',
    ];

    public static function nextId(): int
    {
        return (int)(self::max('IdEmpleado') ?? 0) + 1;
    }
}