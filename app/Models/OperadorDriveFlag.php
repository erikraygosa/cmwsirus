<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OperadorDriveFlag extends Model
{
    protected $table = 'operador_drive_flags';

    protected $fillable = ['IdOper', 'envio'];
}
