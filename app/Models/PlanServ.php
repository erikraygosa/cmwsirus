<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlanServ extends Model
{
    protected $connection = 'mysql3';

    // O
    public function getConnectionName()
    {
        return 'mysql3';
    }
    
    use HasFactory;
    protected $primaryKey = 'ID';
    protected $table = 'tblprogramatrabajo';
    public $timestamps = false;

    protected $fillable = [
        'Fecha',
        'Ruta',
        'Unidad',
        'Corrida',
        'Turno',
        'Operador',
    ];

    public function Operadores()
    {
        return $this->belongsTo(Operadores::class);
    }
}
