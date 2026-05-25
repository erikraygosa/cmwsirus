<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Operadores extends Model
{
    protected $connection = 'mysql2';

    // O
    public function getConnectionName()
    {
        return 'mysql2';
    }
    use HasFactory;
    protected $primaryKey = 'IdOperador';
    protected $table = 'catoperadores';

    public function Planserv()
    {
        return $this->hasMany(PlanServ::class);
    }
}
