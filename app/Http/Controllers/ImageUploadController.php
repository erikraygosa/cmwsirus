<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class ImageUploadController extends Controller
{
    public function upload(Request $request)
    {
        // Validar la imagen y el nombre del archivo
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:jpeg,png,jpg,gif,pdf|max:25048',
            'filename' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 400);
        }

        // Obtener el archivo y el nombre del archivo
        $image = $request->file('file');
        $filename = $request->input('filename');

        // Asegurarse de que el nombre del archivo no contenga caracteres no permitidos
        $filename = preg_replace('[^a-zA-Z0-9_.-]', '', $filename);

        // Guardar la imagen con el nombre proporcionado
        $path = $image->storeAs('public/upload/', $filename);

        // Devolver la ruta de la imagen
        $url = Storage::url($path);
        return response()->json(['path' => $url], 201);
    }
    public function getImage($path)
    {
        // Construir la ruta completa al archivo
        $path = storage_path('app/public/upload/' . $path);
    
        // Verificar si el archivo existe
        if (!file_exists($path)) {
            return response()->json(['error' => 'File not found.'], 404);
        }
    
        // Obtener el contenido del archivo
        $file = file_get_contents($path);
        // Obtener el tipo MIME del archivo
        $type = mime_content_type($path);
    
        // Devolver la respuesta con el contenido del archivo y el tipo MIME correspondiente
        return response($file, 200)->header("Content-Type", $type);
    }
    public function deleteImage($path)
    {
        // Construir la ruta completa al archivo
        $path = 'public/upload/' . $path;
    
        // Verificar si el archivo existe
        if (!Storage::exists($path)) {
            return response()->json(['error' => 'File not found.'], 404);
        }
    
        // Eliminar el archivo
        Storage::delete($path);
        return response()->json(['message' => 'Archivo Borrado.'], 200);
    }
}
