<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Adjunto extends Model
{
     protected $connection = 'mysql2';
    protected $table = 'tbladjuntos';
    protected $primaryKey = 'Id';
    public $timestamps = false;

    protected $fillable = [
        'Tabla','IdRegTab','Bucket','FullFileName','OriginalFileName','Peso','Creado',
    ];
}