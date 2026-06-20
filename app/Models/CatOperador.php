<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class CatOperador extends Model
{
    protected $connection = 'mysql3';
    protected $table = 'catoperadores';
    protected $primaryKey = 'IdOper';
    public $incrementing = false;
    public $timestamps = false;

    protected static function booted(): void
    {
        static::addGlobalScope('activos', function (Builder $query) {
            $query->where('Estatus', 'A');
        });
    }

    protected $fillable = [
        'IdEmpresa', 'IdOper', 'Unidad', 'IdRuta', 'Ruta',
        'CURP', 'Operador', 'Direccion', 'Telefono',
        'FechaNac', 'FechaIng', 'NumAfilIMSS',
        'Estatus', 'VencimientoLic', 'IdOperOld',
    ];
}
