<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CatzonaRuta extends Model
{
   
    
        use HasFactory;
    
        protected $connection = 'mysql2';
        protected $table     = 'catzonasruta';
        protected $primaryKey = 'IdZona';
    
        protected $fillable = ['IdRuta', 'Zona'];
    
}
