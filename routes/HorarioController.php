<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\PlanServ;

class HorarioController extends Controller
{
    public function index()
    {

        $fechaProgramada = Carbon::tomorrow()->format('d-m-Y');
        $fecha = Carbon::now();
        $fecha2 = Carbon::now()->addDay();

        $pservicios = PlanServ::whereBetween('fecha', [$fecha, $fecha2])
       
        ->get();
        
       
        

        return view('horarios.index',compact('fechaProgramada', 'pservicios', ));
    }
    
    

    
}
