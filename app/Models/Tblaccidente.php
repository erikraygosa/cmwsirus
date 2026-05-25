<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tblaccidente extends Model
{
    use HasFactory;

    protected $connection = 'mysql2';

    // O
    public function getConnectionName()
    {
        return 'mysql2';
    }

    protected $primaryKey = 'IdHojaServ';
    public $timestamps = false;
}
