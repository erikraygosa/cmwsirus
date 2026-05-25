<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Empleado extends Model
{
    protected $connection = 'mysql2';

    // O
    public function getConnectionName()
    {
        return 'mysql2';
    }
    use HasFactory;
    protected $primaryKey = 'IdEmpleado';
    protected $table = 'catempleados';


    public $timestamps = false; 

     protected $fillable = [
        'img_biometrico',
        'img_url',
        'Nombre',
        'Estatus',
        'Licencia',   // <-- agrega esto
        'FechaVL', 
        // ... otros campos si planeas usarlos
    ];
}