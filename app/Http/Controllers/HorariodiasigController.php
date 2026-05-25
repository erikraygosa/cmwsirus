<?php

namespace App\Http\Controllers;
use Illuminate\Support\Carbon;
use App\Models\PlanServ;

use Illuminate\Http\Request;

class HorariodiasigController extends Controller
{
    public function index()
    {
        $fechaProgramada = Carbon::tomorrow()->format('d-m-Y');
        $fecha = Carbon::now()->addDay();
      

        $pservicios = PlanServ::whereDate('fecha', $fecha)
        ->orderBy('Ruta')
        ->get();
        
        
      
        
      
        return view('horariodiasig.index',compact('fechaProgramada', 'pservicios'));
    }
}
