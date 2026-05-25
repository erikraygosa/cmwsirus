<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AccidenteImagen;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;

use Illuminate\Support\Facades\Redirect;


class accidenteImagenController extends Controller
{
 
   
    function store(Request $request){
       
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,pdf|max:25048', // 5MB
        ]);
    
        if ($request->hasFile('image')) {
            $image = $request->file('image');
    
           
        
        // $folder = "MMA9010124P2";
        $fechaActual = Carbon::now()->toDateString();
        $currentDateTime = Carbon::now();

        // Formatear la fecha y la hora si es necesario
        $formattedDateTime = $currentDateTime->format('Y-m-d H:i:s');
        $folder = "MMA9010124P2";
         //OBTENER ULTIMO REGISTRO TBLADJUNTOS
         

         $ultimoIdResponse = DB::connection('mysql2')->select('SELECT id FROM `tbladjuntos` ORDER BY `id` DESC LIMIT 1');
         $ultimoId = $ultimoIdResponse[0]->id;
 
        $Id1 = $ultimoId+1;
        
        $id = $request->input('accidente_id');
        // $rutaDirectorio = 'public/ImagenesAccidentes/';
        // $nombreImagen = time() . '_' .  $id;
        // $request->file('imagen')->store($rutaDirectorio, $nombreImagen);
        $image1 = $request->file('imagen');  
        $imagenname = $Id1 . '.' . $image1->getClientOriginalExtension();
        

        $image = $request->imagen;
        $file = $request->file('imagen');
        $filename = $folder . '/' . $Id1 . '.' . $image1->getClientOriginalExtension();
        $path = $file->getPathname();
       
       
        $response = $file->storeAs('public/upload', $filename);
        // Enviar el archivo a la API
                        // $response = Http::attach('file', file_get_contents($path), $filename)
                        // ->post('http://89.117.77.220/api/upload', [
                        //     'filename' => $filename,
                        // ]);
                        dd($response);
        $extension = $image->getClientOriginalExtension();
       
      
       
        $imagen = new AccidenteImagen;
        $imagen->Id = $Id1;
        $imagen->Tabla = 'TblAccidentesExternos';
        $imagen->IdRegTab =  $id ;
        $imagen->Bucket = 'controlmant';
        $imagen->Creado = $formattedDateTime;
        // $imagen->FullFileName = 'MMA9010124P2' . '/' .  $rutaImagen;   //quitar la conca
        $imagen->FullFileName =  $folder . '/' . $imagenname;   //test
        $imagen->OriginalFileName = $imagenname; 
        // $imagen->Creado = $fechaActual;

      
        $imagen->save();
       
        if ($response->successful()) {
            // Mensaje de éxito
            return redirect()->route('accidentes.index')->with('success', 'Archivo subido exitosamente.');
        } else {
            // Mensaje de error
            return redirect()->route('accidentes.index')->with('error', 'Error al subir el archivo.');
        }
    } else {
        return response()->json(['error' => 'No se ha recibido ninguna imagen. Por favor, intente de nuevo.'], 400);
    }

    }      


    function getImages(Request $request){
        $id = $request->input('accidente_id');
        $imagenes = AccidenteImagen::where('id_accidente', $id)->select('id_imagen', 'ruta')->get();
        
        
        return $imagenes;
    } 


    public function delete($id){

        $imagen = AccidenteImagen::find($id); // Busca la imagen por el ID
        if ($imagen) { // Verifica si se encontró la imagen
            $imagen->delete(); // Elimina la imagen
           

        } else {
            
        }
        
        return redirect()->action('App\Http\Controllers\TblaccidenteController@index');
    }
}
