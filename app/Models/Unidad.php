<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Unidad extends Model
{
    protected $connection = 'mysql2';

    // O
    public function getConnectionName()
    {
        return 'mysql2';
    }
    use HasFactory;
    protected $primaryKey = 'IdUnidad';
    protected $table = 'catunidades';

    public function planservs()
    {
        return $this->hasMany(PlanServ::class, 'IdUnidad');
    }

    // public function hojaserv()
    // {
    //     return $this->hasOne(HojaServ::class, 'IdUnidad');
    // }



    // public function cita()
    // {
    //     return $this->hasOne(Cita::class, 'IdUnidad');
    // }

    // public function tareacita()
    // {
    //     return $this->hasOne(TareaCita::class, 'IdUnidad');
    // }
}
