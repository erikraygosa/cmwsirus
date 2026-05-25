<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Empresa extends Model
{
    protected $connection = 'mysql3';

    // O
    public function getConnectionName()
    {
        return 'mysql3';
    }
    use HasFactory;
    protected $primaryKey = 'IdEmpresa';
    protected $table = 'tbldatosempresa';
}
