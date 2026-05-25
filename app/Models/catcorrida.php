<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class catcorrida extends Model
{
    protected $connection = 'mysql2';

    
    
    protected $primaryKey = 'IdCorr';
    protected $table = 'catcorridas';

    public function ruta()
    {
        return $this->belongsTo(Catrutas::class, 'IdRuta', 'IdRuta');
    }
}
