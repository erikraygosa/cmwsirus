<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Models\PlanServ;
use Illuminate\Support\Facades\Auth;

class HorarioOperadorController extends Controller
{
    public function index()
    {

        $fechaProgramada = Carbon::tomorrow()->format('d-m-Y');
        $fecha = Carbon::now()->addDay();
        $user = Auth::user();

        $pservicios = PlanServ::whereDate('fecha', $fecha)
        ->where('Operador', $user->name)
        ->get();
        
      
        

        return view('horariosope.index',compact('fechaProgramada', 'pservicios', ));
    }
    public function store(Request $request)
    {
        $fechaini = $request->fecha_inicio;
        $fechaProgramada = $fechaini;
        $fecha = Carbon::now();
        $fecha2 = Carbon::now()->addDay();

        $pservicios = PlanServ::where('fecha',$fechaini)
       
        ->get();
        
       
        

        return view('horarios.index',compact('fechaProgramada', 'pservicios', ));
    }
}
