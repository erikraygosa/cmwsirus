<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccidenteImagen extends Model
{
    protected $connection = 'mysql2';

    // O
    public function getConnectionName()
    {
        return 'mysql2';
    }
    use HasFactory;
    protected $primaryKey = 'id';
    protected $table = 'tbladjuntos';
}
