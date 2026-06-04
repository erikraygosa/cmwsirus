<?php

namespace App\Http\Controllers;

use App\Models\CatEmpleado;
use App\Models\TblAdjunto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class ExpedienteController extends Controller
{
    private string $tabla;
    private string $bucket;
    private string $disco;

    public function __construct()
    {
        $this->tabla  = config('expediente_docs.tabla_bd');   // 'EMPLEADO'
        $this->bucket = config('expediente_docs.bucket');     // 'expedientes'
        $this->disco  = config('expediente_docs.disco');      // 'public'
    }

    /*
    |--------------------------------------------------------------------------
    | INDEX — Lista de operadores activos con % de completitud
    |--------------------------------------------------------------------------
    */
    public function index(Request $request)
    {
        $busqueda  = $request->input('q');
        $totalDocs = collect(config('expediente_docs.documentos'))->filter(fn($d) => $d['obligatorio'] ?? true)->count();

        // Operadores activos (status = A)
        $empleados = CatEmpleado::when($busqueda, function ($q) use ($busqueda) {
                $q->where(function ($sub) use ($busqueda) {
                    $sub->where('Nombre', 'like', "%{$busqueda}%")
                        ->orWhere('CURP', 'like', "%{$busqueda}%");
                });
            })
            ->orderBy('Nombre')
            ->paginate(25)
            ->withQueryString();

        // Para cada empleado calcula cuántos tipos de doc tiene subidos
        $adjuntosPorEmpleado = TblAdjunto::where('Tabla', $this->tabla)
            ->whereIn('IdRegTab', $empleados->pluck('IdEmpleado'))
            ->where('Estatus', '!=', 'ELIMINADO')
            ->get()
            ->groupBy('IdRegTab');

        $clavesObligatorias = collect(config('expediente_docs.documentos'))
            ->filter(fn($d) => $d['obligatorio'] ?? true)->keys();

        // Construye mapa: IdEmpleado → ['count' => N, 'pct' => NN]  (solo página actual)
        $completitud = [];
        foreach ($empleados as $emp) {
            $docs      = $adjuntosPorEmpleado->get($emp->IdEmpleado, collect());
            $tipos     = $docs->map(fn($d) => $d->tipoDocumento())->unique()->filter()
                             ->intersect($clavesObligatorias)->values();
            $count     = $tipos->count();
            $pct       = $totalDocs > 0 ? (int) round($count / $totalDocs * 100) : 0;
            $completo  = $count >= $totalDocs;

            // Cuántos docs obligatorios ya fueron enviados al Drive
            $enviados = $docs->filter(fn($d) => !is_null($d->EnvioDrive)
                && $clavesObligatorias->contains($d->tipoDocumento()))->count();

            $completitud[$emp->IdEmpleado] = [
                'count'    => $count,
                'total'    => $totalDocs,
                'pct'      => $pct,
                'completo' => $completo,
                'enviados' => $enviados,
            ];
        }

        // ── Stats globales: todos los operadores activos (no solo la página) ──
        $todosIds = CatEmpleado::where('Estatus', 'A')->pluck('IdEmpleado');

        $todosAdjGlobal = TblAdjunto::where('Tabla', $this->tabla)
            ->whereIn('IdRegTab', $todosIds)
            ->where('Estatus', '!=', 'ELIMINADO')
            ->get(['IdRegTab', 'Comentarios', 'EnvioDrive'])
            ->groupBy('IdRegTab');

        $completosGlobal = 0;
        foreach ($todosIds as $idEmp) {
            $tipos = $todosAdjGlobal->get($idEmp, collect())
                ->map(fn($d) => $d->tipoDocumento())
                ->unique()->filter()
                ->intersect($clavesObligatorias);
            if ($tipos->count() >= $totalDocs) {
                $completosGlobal++;
            }
        }

        // Cuántos empleados completos tienen el flag envio=1
        $marcadosParaDrive = CatEmpleado::where('Estatus', 'A')->where('envio', 1)->count();

        $statsGlobales = [
            'completos'        => $completosGlobal,
            'incompletos'      => $todosIds->count() - $completosGlobal,
            'total'            => $todosIds->count(),
            'marcadosParaDrive'=> $marcadosParaDrive,
        ];

        return view('expedientes.index', compact('empleados', 'completitud', 'busqueda', 'totalDocs', 'statsGlobales'));
    }

    /*
    |--------------------------------------------------------------------------
    | SHOW — Expediente de un operador (todos sus documentos)
    |--------------------------------------------------------------------------
    */
    public function show(int $id)
    {
        $empleado  = CatEmpleado::findOrFail($id);
        $catalogo  = config('expediente_docs.documentos');

        // Traer todos los adjuntos activos del empleado
        $adjuntos  = TblAdjunto::delEmpleado($id)->activo()->orderByDesc('Creado')->get();

        // Indexar por tipo para acceso rápido en la vista
        $porTipo   = [];
        foreach ($adjuntos as $adj) {
            $tipo = $adj->tipoDocumento();
            if ($tipo && !isset($porTipo[$tipo])) {
                $porTipo[$tipo] = $adj;  // el más reciente por tipo
            }
        }

        $obligatorios  = collect($catalogo)->filter(fn($d) => $d['obligatorio'] ?? true)->keys();
        $totalDocs     = $obligatorios->count();
        $subidos       = collect(array_keys($porTipo))->intersect($obligatorios)->count();
        $subidosTotal  = collect(array_keys($porTipo))->intersect(array_keys($catalogo))->count();
        $pct       = $totalDocs > 0 ? (int) round($subidos / $totalDocs * 100) : 0;
        $completo  = $subidos >= $totalDocs;

        return view('expedientes.show', compact(
            'empleado', 'catalogo', 'porTipo', 'totalDocs', 'subidos', 'subidosTotal', 'pct', 'completo'
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | STORE — Subir / reemplazar un documento
    |--------------------------------------------------------------------------
    */
    public function store(Request $request, int $id)
    {
        $maxMb  = config('expediente_docs.max_mb', 10);
        $tipos  = array_keys(config('expediente_docs.documentos'));

        $request->validate([
            'tipo'    => ['required', 'in:' . implode(',', $tipos)],
            'archivo' => ['required', 'file', "max:{$maxMb}024"],   // max en KB
        ]);

        $empleado = CatEmpleado::findOrFail($id);
        $tipo     = $request->tipo;
        $archivo  = $request->file('archivo');

        // Nombre de archivo seguro: TIPO_IdEmpleado_timestamp.ext
        $ext          = $archivo->getClientOriginalExtension();
        $nombreGuarda = strtoupper($tipo) . "_{$id}_" . time() . '.' . $ext;

        // Ruta relativa dentro del disco: expedientes/042/INE_042_1700000000.jpg
        $carpeta      = $this->bucket . DIRECTORY_SEPARATOR . $id;
        $rutaRelativa = $carpeta . DIRECTORY_SEPARATOR . $nombreGuarda;

        // Guardar físicamente
        $archivo->storeAs($carpeta, $nombreGuarda, $this->disco);

        // Marcar como REEMPLAZADO cualquier doc anterior del mismo tipo
        TblAdjunto::delEmpleado($id)
            ->porTipo($tipo)
            ->activo()
            ->update(['Estatus' => 'REEMPLAZADO', 'updated_at' => now()]);

        // Insertar nuevo registro en tbladjuntos
        TblAdjunto::create([
            'Tabla'            => $this->tabla,
            'IdRegTab'         => $id,
            'Bucket'           => $this->bucket,
            'FullFileName'     => $rutaRelativa,
            'OriginalFileName' => $archivo->getClientOriginalName(),
            'Peso'             => $archivo->getSize(),
            'Comentarios'      => "TIPO:{$tipo}",
            'IdDocRel'         => null,
            'Estatus'          => 'ACTIVO',
            'Creado'           => now(),
            'created_at'       => now(),
            'updated_at'       => now(),
        ]);

        return back()->with('success', "Documento {$tipo} guardado correctamente.");
    }

    /*
    |--------------------------------------------------------------------------
    | DESTROY — Eliminar lógico de un documento
    |--------------------------------------------------------------------------
    */
    public function destroy(int $idAdj)
    {
        $adj = TblAdjunto::findOrFail($idAdj);
        $adj->update(['Estatus' => 'ELIMINADO', 'updated_at' => now()]);

        return back()->with('success', 'Documento eliminado del expediente.');
    }

    /*
    |--------------------------------------------------------------------------
    | DOWNLOAD ZIP — Expediente de un operador (docs seleccionados)
    |--------------------------------------------------------------------------
    | Query param: ?tipos[]=INE_FRENTE&tipos[]=LICENCIA_FRENTE   (opcional)
    | Sin parámetro → incluye todos los marcados con zip=true en el catálogo.
    */
    public function downloadZip(Request $request, int $id)
    {
        $empleado = CatEmpleado::findOrFail($id);
        $catalogo = config('expediente_docs.documentos');
        $adjuntos = TblAdjunto::delEmpleado($id)->activo()->get();

        if ($adjuntos->isEmpty()) {
            return back()->with('error', 'El operador no tiene documentos subidos aún.');
        }

        // Tipos a incluir: los del request, o por defecto los que tienen zip=true
        $tiposPermitidos = $request->has('tipos')
            ? collect($request->input('tipos', []))->filter(fn($t) => isset($catalogo[$t]))->values()
            : collect($catalogo)->filter(fn($d) => $d['zip'] ?? true)->keys();

        $nombreLimpio = Str::slug($empleado->Nombre, '_');
        $zipNombre    = "Expediente_{$nombreLimpio}_{$id}.zip";
        $zipRuta      = storage_path("app/temp/{$zipNombre}");

        if (!is_dir(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($zipRuta, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return back()->with('error', 'No se pudo crear el archivo ZIP.');
        }

        foreach ($adjuntos as $adj) {
            $tipo = $adj->tipoDocumento();
            if (!$tiposPermitidos->contains($tipo)) continue;

            $rutaFisica = Storage::disk($this->disco)->path($adj->FullFileName);
            if (!file_exists($rutaFisica)) continue;

            $nombreDoc   = $catalogo[$tipo]['nombre'] ?? $tipo;
            $ext         = pathinfo($adj->OriginalFileName, PATHINFO_EXTENSION);
            $nombreEnZip = "{$nombreLimpio}_{$id}/{$nombreDoc}.{$ext}";
            $zip->addFile($rutaFisica, $nombreEnZip);
        }

        $zip->close();

        return response()->download($zipRuta, $zipNombre)->deleteFileAfterSend(true);
    }

    /*
    |--------------------------------------------------------------------------
    | EXPORT MASIVO ZIP — Solo operadores con expediente COMPLETO
    |--------------------------------------------------------------------------
    | GET /expedientes/masivo/zip?tipos[]=INE_FRENTE&tipos[]=CURP ...
    | Sin parámetro → todos los marcados con drive=true.
    */
    public function exportMasivo(Request $request)
    {
        $catalogo    = config('expediente_docs.documentos');
        $totalDocs   = collect($catalogo)->filter(fn($d) => $d['obligatorio'] ?? true)->count();

        // Tipos a incluir en el ZIP masivo
        $tiposPermitidos = $request->has('tipos')
            ? collect($request->input('tipos', []))->filter(fn($t) => isset($catalogo[$t]))->values()
            : collect($catalogo)->filter(fn($d) => $d['drive'] ?? true)->keys();

        // Traer todos los adjuntos activos del módulo
        $todosAdjuntos = TblAdjunto::where('Tabla', $this->tabla)
            ->activo()
            ->get()
            ->groupBy('IdRegTab');

        // Filtrar solo los empleados con expediente completo
        $empleadosCompletos = CatEmpleado::where('Estatus', 'A')
            ->get()
            ->filter(function ($emp) use ($todosAdjuntos, $catalogo, $totalDocs) {
                $docs  = $todosAdjuntos->get($emp->IdEmpleado, collect());
                $tipos = $docs->map(fn($d) => $d->tipoDocumento())
                              ->unique()->filter()
                              ->intersect(array_keys($catalogo));
                return $tipos->count() >= $totalDocs;
            });

        if ($empleadosCompletos->isEmpty()) {
            return back()->with('error', 'No hay operadores con expediente completo para exportar.');
        }

        $zipNombre = 'ExpedientesMasivo_' . now()->format('Ymd_His') . '.zip';
        $zipRuta   = storage_path("app/temp/{$zipNombre}");

        if (!is_dir(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($zipRuta, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return back()->with('error', 'No se pudo crear el archivo ZIP masivo.');
        }

        foreach ($empleadosCompletos as $emp) {
            $nombreLimpio = Str::slug($emp->Nombre, '_');
            $adjuntos     = $todosAdjuntos->get($emp->IdEmpleado, collect());

            foreach ($adjuntos as $adj) {
                $tipo = $adj->tipoDocumento();
                if (!$tiposPermitidos->contains($tipo)) continue;

                $rutaFisica = Storage::disk($this->disco)->path($adj->FullFileName);
                if (!file_exists($rutaFisica)) continue;

                $nombreDoc   = $catalogo[$tipo]['nombre'] ?? $tipo;
                $ext         = pathinfo($adj->OriginalFileName, PATHINFO_EXTENSION);
                // Carpeta por operador dentro del ZIP
                $nombreEnZip = "{$nombreLimpio}_{$emp->IdEmpleado}/{$nombreDoc}.{$ext}";
                $zip->addFile($rutaFisica, $nombreEnZip);
            }
        }

        $zip->close();

        return response()->download($zipRuta, $zipNombre)->deleteFileAfterSend(true);
    }

    /*
    |--------------------------------------------------------------------------
    | SYNC DRIVE — Sube expedientes completos a Google Drive via rclone
    |--------------------------------------------------------------------------
    | Requiere rclone configurado en el servidor con el remote: gdrive_cliente
    | Configurar una vez con: rclone config
    | Documentación: https://rclone.org/drive/
    |
    | Flujo:
    |   1. Copia archivos seleccionados a carpeta temporal organizada por operador
    |   2. Ejecuta rclone copy apuntando al Drive del cliente
    |   3. Limpia la carpeta temporal
    |   4. Devuelve el output de rclone como resultado
    */
    public function syncDrive(Request $request)
    {
        $request->validate([
            'carpeta_drive' => ['required', 'string', 'max:200', 'regex:/^[a-zA-Z0-9_\- ]+$/'],
            'siglas'        => ['required', 'string', 'max:10', 'regex:/^[A-Z0-9]+$/i'],
        ]);

        $catalogo     = config('expediente_docs.documentos');
        $carpetaDrive = trim($request->carpeta_drive);
        $siglas       = strtoupper(trim($request->siglas));

        // ── 1. Solo adjuntos activos agrupados por empleado ───────
        $todosAdjuntos = TblAdjunto::where('Tabla', $this->tabla)
            ->activo()
            ->get()
            ->groupBy('IdRegTab');

        // ── 2. Empleados con envio=1 que tengan al menos 1 documento almacenado
        $empleadosConDocs = CatEmpleado::where('Estatus', 'A')
            ->where('envio', 1)
            ->get()
            ->filter(fn($emp) => $todosAdjuntos->has($emp->IdEmpleado));

        if ($empleadosConDocs->isEmpty()) {
            return back()->with('error', 'Ningún operador marcado para Drive tiene documentos almacenados.');
        }

        // ── 3. Construir carpeta temporal (solo docs no enviados aún) ──
        $tmpBase = storage_path('app/temp/drive_sync_' . time());
        if (!is_dir($tmpBase)) {
            mkdir($tmpBase, 0755, true);
        }

        $totalArchivos = 0;
        $idsEnviados   = [];   // [ adjunto_id, ... ]

        foreach ($empleadosConDocs as $emp) {
            $curp      = $emp->CURP ?? $emp->IdEmpleado;
            $carpetaOp = $tmpBase . DIRECTORY_SEPARATOR . "{$curp}_{$siglas}";

            if (!is_dir($carpetaOp)) {
                mkdir($carpetaOp, 0755, true);
            }

            $adjuntos = $todosAdjuntos->get($emp->IdEmpleado, collect());

            foreach ($adjuntos as $adj) {
                $tipo = $adj->tipoDocumento();
                if (!$tipo) continue;
                if (!is_null($adj->EnvioDrive)) continue;   // ya enviado, no sobrescribir

                $rutaFisica = Storage::disk($this->disco)->path($adj->FullFileName);
                if (!file_exists($rutaFisica)) continue;

                $nomenclatura = $catalogo[$tipo]['nomenclatura'] ?? $tipo;
                $ext          = pathinfo($adj->OriginalFileName, PATHINFO_EXTENSION);
                $destino      = $carpetaOp . DIRECTORY_SEPARATOR . "{$curp}_{$nomenclatura}.{$ext}";

                copy($rutaFisica, $destino);
                $idsEnviados[] = $adj->Id;
                $totalArchivos++;
            }
        }

        if ($totalArchivos === 0) {
            $this->limpiarDirectorio($tmpBase);
            return back()->with('error', 'Todos los documentos seleccionados ya fueron enviados al Drive anteriormente.');
        }

        // ── 4. Ejecutar rclone ────────────────────────────────────
        $remoteNombre  = env('RCLONE_REMOTE', config('expediente_docs.rclone_remote', 'gdrive_cliente'));
        $rcloneBin     = env('RCLONE_BIN', 'rclone');
        $rcloneConf    = env('RCLONE_CONF');
        $driveFolderId = env('DRIVE_FOLDER_ID');   // ID de la carpeta raíz en Drive

        // Si hay folder ID: apunta a la raíz del ID y usa carpeta_drive como subcarpeta
        // Si no: usa el nombre como ruta normal en el Drive
        if ($driveFolderId) {
            $destDrive  = "{$remoteNombre}:{$carpetaDrive}";
            $folderFlag = '--drive-root-folder-id=' . escapeshellarg($driveFolderId);
        } else {
            $destDrive  = "{$remoteNombre}:{$carpetaDrive}";
            $folderFlag = '';
        }

        $confFlag = $rcloneConf ? '--config ' . escapeshellarg($rcloneConf) : '';

        $cmd = sprintf(
            '%s copy %s %s %s %s --progress --transfers=4 --stats-one-line 2>&1',
            escapeshellarg($rcloneBin),
            escapeshellarg($tmpBase),
            escapeshellarg($destDrive),
            $confFlag,
            $folderFlag
        );

        $output    = [];
        $exitCode  = 0;
        exec($cmd, $output, $exitCode);

        $outputStr = implode("\n", $output);

        // ── 5. Limpiar carpeta temporal ───────────────────────────
        $this->limpiarDirectorio($tmpBase);

        // ── 6. Marcar docs como enviados y responder ──────────────
        if ($exitCode === 0) {
            if (!empty($idsEnviados)) {
                TblAdjunto::whereIn('Id', $idsEnviados)
                    ->update(['EnvioDrive' => now(), 'updated_at' => now()]);
            }

            $msg = "✅ Enviados {$totalArchivos} archivos de {$empleadosConDocs->count()} operadores "
                 . "a Drive/{$carpetaDrive} (carpetas: CURP_{$siglas}/)\n\n{$outputStr}";
            return back()->with('drive_resultado', $msg);
        }

        return back()->with('error',
            "rclone terminó con código {$exitCode}.\n\nOutput:\n{$outputStr}"
        );
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE VIGENCIA — Guarda fecha de vencimiento de licencia (FechaVL)
    |--------------------------------------------------------------------------
    */
    public function updateVigencia(Request $request, int $id)
    {
        $request->validate([
            'campo'  => ['required', 'string', 'in:FechaVL'],
            'fecha'  => ['nullable', 'date'],
        ]);

        $empleado = CatEmpleado::findOrFail($id);
        $empleado->update([$request->campo => $request->fecha ?: null]);

        return back()->with('success', 'Fecha de vencimiento actualizada.');
    }

    /*
    |--------------------------------------------------------------------------
    | TOGGLE ENVIO — Marca / desmarca empleado para envío a Drive
    |--------------------------------------------------------------------------
    */
    public function toggleEnvio(Request $request, int $id)
    {
        $empleado = CatEmpleado::findOrFail($id);
        $empleado->update(['envio' => $request->boolean('envio') ? 1 : 0]);

        return back()->with('success',
            $empleado->envio
                ? 'Operador marcado para envío a Drive.'
                : 'Operador desmarcado del envío a Drive.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Helper — eliminar directorio recursivo
    |--------------------------------------------------------------------------
    */
    private function limpiarDirectorio(string $dir): void
    {
        if (!is_dir($dir)) return;

        $archivos = array_diff(scandir($dir), ['.', '..']);
        foreach ($archivos as $archivo) {
            $ruta = $dir . DIRECTORY_SEPARATOR . $archivo;
            is_dir($ruta) ? $this->limpiarDirectorio($ruta) : unlink($ruta);
        }
        rmdir($dir);
    }
}