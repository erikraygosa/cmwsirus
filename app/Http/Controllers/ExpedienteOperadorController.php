<?php

namespace App\Http\Controllers;

use App\Models\CatEmpleado;
use App\Models\CatOperador;
use App\Models\OperadorDriveFlag;
use App\Models\TblAdjunto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class ExpedienteOperadorController extends Controller
{
    private string $tabla;
    private string $bucket;
    private string $disco;

    public function __construct()
    {
        $this->tabla  = config('expediente_docs.tabla_bd_operador');   // 'OPERADOR'
        $this->bucket = config('expediente_docs.bucket_operador');     // 'expedientes_operadores'
        $this->disco  = config('expediente_docs.disco');               // 'public'
    }

    /*
    |--------------------------------------------------------------------------
    | INDEX — Lista de operadores activos (catoperadores) con % de completitud
    |--------------------------------------------------------------------------
    */
    public function index(Request $request)
    {
        $busqueda  = $request->input('q');
        $soloDrive = $request->boolean('drive');
        $totalDocs = collect(config('expediente_docs.documentos'))->filter(fn($d) => $d['obligatorio'] ?? true)->count();

        $idsEnvio = OperadorDriveFlag::where('envio', 1)->pluck('IdOper');

        $operadores = CatOperador::when($busqueda, function ($q) use ($busqueda) {
                $q->where(function ($sub) use ($busqueda) {
                    $sub->where('Operador', 'like', "%{$busqueda}%")
                        ->orWhere('CURP', 'like', "%{$busqueda}%");
                });
            })
            ->when($soloDrive, function ($q) use ($idsEnvio) {
                $q->whereIn('IdOper', $idsEnvio);
            })
            ->orderBy('Operador')
            ->paginate(25)
            ->withQueryString();

        // Para cada operador calcula cuántos tipos de doc tiene subidos
        $adjuntosPorOperador = TblAdjunto::where('Tabla', $this->tabla)
            ->whereIn('IdRegTab', $operadores->pluck('IdOper'))
            ->where('Estatus', '!=', 'ELIMINADO')
            ->get()
            ->groupBy('IdRegTab');

        $clavesObligatorias = collect(config('expediente_docs.documentos'))
            ->filter(fn($d) => $d['obligatorio'] ?? true)->keys();

        // ── Duplicado: ¿este CURP/Nombre ya tiene expediente en el sistema viejo? ──
        $curpsPagina = $operadores->pluck('CURP')->filter()->values();
        $nombresPagina = $operadores->pluck('Operador')->filter()->values();

        $empleadosViejos = CatEmpleado::where(function ($q) use ($curpsPagina, $nombresPagina) {
                $q->whereIn('CURP', $curpsPagina)->orWhereIn('Nombre', $nombresPagina);
            })
            ->get(['IdEmpleado', 'CURP', 'Nombre']);

        $adjuntosViejos = TblAdjunto::where('Tabla', config('expediente_docs.tabla_bd'))
            ->whereIn('IdRegTab', $empleadosViejos->pluck('IdEmpleado'))
            ->where('Estatus', '!=', 'ELIMINADO')
            ->get(['IdRegTab', 'Comentarios'])
            ->groupBy('IdRegTab');

        $nombresCatalogo = collect(config('expediente_docs.documentos'))->map(fn($d) => $d['nombre'] ?? null);

        // Mapa CURP/Nombre → nombres de los documentos ya capturados en el sistema anterior
        $docsAnteriorPorCurp   = [];
        $docsAnteriorPorNombre = [];
        foreach ($empleadosViejos as $empViejo) {
            $docsEmp = $adjuntosViejos->get($empViejo->IdEmpleado, collect());
            if ($docsEmp->isEmpty()) continue;

            $nombresDocs = $docsEmp->map(fn($d) => $d->tipoDocumento())
                ->unique()->filter()
                ->map(fn($t) => $nombresCatalogo[$t] ?? $t)
                ->values();

            if ($empViejo->CURP)   $docsAnteriorPorCurp[$empViejo->CURP]     = $nombresDocs;
            if ($empViejo->Nombre) $docsAnteriorPorNombre[$empViejo->Nombre] = $nombresDocs;
        }

        // Construye mapa: IdOper → ['count' => N, 'pct' => NN, ...]  (solo página actual)
        $completitud = [];
        foreach ($operadores as $op) {
            $docs      = $adjuntosPorOperador->get($op->IdOper, collect());
            $tipos     = $docs->map(fn($d) => $d->tipoDocumento())->unique()->filter()
                             ->intersect($clavesObligatorias)->values();
            $count     = $tipos->count();
            $pct       = $totalDocs > 0 ? (int) round($count / $totalDocs * 100) : 0;
            $completo  = $count >= $totalDocs;

            $enviados = $docs->filter(fn($d) => !is_null($d->EnvioDrive)
                && $clavesObligatorias->contains($d->tipoDocumento()))->count();

            $docsAnterior = $docsAnteriorPorCurp[$op->CURP] ?? $docsAnteriorPorNombre[$op->Operador] ?? null;

            $completitud[$op->IdOper] = [
                'count'             => $count,
                'total'             => $totalDocs,
                'pct'               => $pct,
                'completo'          => $completo,
                'enviados'          => $enviados,
                'envio'             => $idsEnvio->contains($op->IdOper),
                'duplicado_anterior'=> !is_null($docsAnterior),
                'docs_anterior'     => $docsAnterior,
            ];
        }

        // ── Stats globales: todos los operadores activos (no solo la página) ──
        $todosIds = CatOperador::pluck('IdOper');

        $todosAdjGlobal = TblAdjunto::where('Tabla', $this->tabla)
            ->whereIn('IdRegTab', $todosIds)
            ->where('Estatus', '!=', 'ELIMINADO')
            ->get(['IdRegTab', 'Comentarios', 'EnvioDrive'])
            ->groupBy('IdRegTab');

        $completosGlobal = 0;
        foreach ($todosIds as $idOp) {
            $tipos = $todosAdjGlobal->get($idOp, collect())
                ->map(fn($d) => $d->tipoDocumento())
                ->unique()->filter()
                ->intersect($clavesObligatorias);
            if ($tipos->count() >= $totalDocs) {
                $completosGlobal++;
            }
        }

        $statsGlobales = [
            'completos'        => $completosGlobal,
            'incompletos'      => $todosIds->count() - $completosGlobal,
            'total'            => $todosIds->count(),
            'marcadosParaDrive'=> $idsEnvio->count(),
        ];

        return view('expedientes_operadores.index', compact('operadores', 'completitud', 'busqueda', 'soloDrive', 'totalDocs', 'statsGlobales'));
    }

    /*
    |--------------------------------------------------------------------------
    | SHOW — Expediente de un operador (todos sus documentos)
    |--------------------------------------------------------------------------
    */
    public function show(int $id)
    {
        $operador = CatOperador::findOrFail($id);

        // catoperadores guarda la vigencia de licencia en 'VencimientoLic', no 'FechaVL'
        $catalogo = collect(config('expediente_docs.documentos'))->map(function ($doc) {
            if (($doc['campo_vigencia'] ?? null) === 'FechaVL') {
                $doc['campo_vigencia'] = 'VencimientoLic';
            }
            return $doc;
        })->all();

        $adjuntos = TblAdjunto::deTabla($this->tabla, $id)->activo()->orderByDesc('Creado')->get();

        $porTipo = [];
        foreach ($adjuntos as $adj) {
            $tipo = $adj->tipoDocumento();
            if ($tipo && !isset($porTipo[$tipo])) {
                $porTipo[$tipo] = $adj;
            }
        }

        $obligatorios  = collect($catalogo)->filter(fn($d) => $d['obligatorio'] ?? true)->keys();
        $totalDocs     = $obligatorios->count();
        $subidos       = collect(array_keys($porTipo))->intersect($obligatorios)->count();
        $subidosTotal  = collect(array_keys($porTipo))->intersect(array_keys($catalogo))->count();
        $pct           = $totalDocs > 0 ? (int) round($subidos / $totalDocs * 100) : 0;
        $completo      = $subidos >= $totalDocs;

        // ¿Ya existe expediente con estos documentos en el sistema viejo (catempleados)?
        $duplicadoAnterior = $this->buscarDuplicadoAnterior($operador);

        $envio = OperadorDriveFlag::where('IdOper', $id)->value('envio') ?? false;

        return view('expedientes_operadores.show', compact(
            'operador', 'catalogo', 'porTipo', 'totalDocs', 'subidos', 'subidosTotal', 'pct', 'completo', 'duplicadoAnterior', 'envio'
        ));
    }

    /*
    |--------------------------------------------------------------------------
    | STORE — Subir / reemplazar un documento
    |--------------------------------------------------------------------------
    */
    public function store(Request $request, int $id)
    {
        $maxMb = config('expediente_docs.max_mb', 10);
        $tipos = array_keys(config('expediente_docs.documentos'));

        $request->validate([
            'tipo'    => ['required', 'in:' . implode(',', $tipos)],
            'archivo' => ['required', 'file', "max:{$maxMb}024"],
        ]);

        CatOperador::findOrFail($id);
        $tipo    = $request->tipo;
        $archivo = $request->file('archivo');

        $ext          = $archivo->getClientOriginalExtension();
        $nombreGuarda = strtoupper($tipo) . "_{$id}_" . time() . '.' . $ext;

        $carpeta      = $this->bucket . DIRECTORY_SEPARATOR . $id;
        $rutaRelativa = $carpeta . DIRECTORY_SEPARATOR . $nombreGuarda;

        $archivo->storeAs($carpeta, $nombreGuarda, $this->disco);

        TblAdjunto::deTabla($this->tabla, $id)
            ->porTipo($tipo)
            ->activo()
            ->update(['Estatus' => 'REEMPLAZADO', 'updated_at' => now()]);

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
    */
    public function downloadZip(Request $request, int $id)
    {
        $operador = CatOperador::findOrFail($id);
        $catalogo = config('expediente_docs.documentos');
        $adjuntos = TblAdjunto::deTabla($this->tabla, $id)->activo()->get();

        if ($adjuntos->isEmpty()) {
            return back()->with('error', 'El operador no tiene documentos subidos aún.');
        }

        $tiposPermitidos = $request->has('tipos')
            ? collect($request->input('tipos', []))->filter(fn($t) => isset($catalogo[$t]))->values()
            : collect($catalogo)->filter(fn($d) => $d['zip'] ?? true)->keys();

        $nombreLimpio = Str::slug($operador->Operador, '_');
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
    */
    public function exportMasivo(Request $request)
    {
        $catalogo  = config('expediente_docs.documentos');
        $totalDocs = collect($catalogo)->filter(fn($d) => $d['obligatorio'] ?? true)->count();

        $tiposPermitidos = $request->has('tipos')
            ? collect($request->input('tipos', []))->filter(fn($t) => isset($catalogo[$t]))->values()
            : collect($catalogo)->filter(fn($d) => $d['drive'] ?? true)->keys();

        $todosAdjuntos = TblAdjunto::where('Tabla', $this->tabla)
            ->activo()
            ->get()
            ->groupBy('IdRegTab');

        $operadoresCompletos = CatOperador::all()
            ->filter(function ($op) use ($todosAdjuntos, $catalogo, $totalDocs) {
                $docs  = $todosAdjuntos->get($op->IdOper, collect());
                $tipos = $docs->map(fn($d) => $d->tipoDocumento())
                              ->unique()->filter()
                              ->intersect(array_keys($catalogo));
                return $tipos->count() >= $totalDocs;
            });

        if ($operadoresCompletos->isEmpty()) {
            return back()->with('error', 'No hay operadores con expediente completo para exportar.');
        }

        $zipNombre = 'ExpedientesMasivo_Operadores_' . now()->format('Ymd_His') . '.zip';
        $zipRuta   = storage_path("app/temp/{$zipNombre}");

        if (!is_dir(storage_path('app/temp'))) {
            mkdir(storage_path('app/temp'), 0755, true);
        }

        $zip = new ZipArchive();
        if ($zip->open($zipRuta, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return back()->with('error', 'No se pudo crear el archivo ZIP masivo.');
        }

        foreach ($operadoresCompletos as $op) {
            $nombreLimpio = Str::slug($op->Operador, '_');
            $adjuntos     = $todosAdjuntos->get($op->IdOper, collect());

            foreach ($adjuntos as $adj) {
                $tipo = $adj->tipoDocumento();
                if (!$tiposPermitidos->contains($tipo)) continue;

                $rutaFisica = Storage::disk($this->disco)->path($adj->FullFileName);
                if (!file_exists($rutaFisica)) continue;

                $nombreDoc   = $catalogo[$tipo]['nombre'] ?? $tipo;
                $ext         = pathinfo($adj->OriginalFileName, PATHINFO_EXTENSION);
                $nombreEnZip = "{$nombreLimpio}_{$op->IdOper}/{$nombreDoc}.{$ext}";
                $zip->addFile($rutaFisica, $nombreEnZip);
            }
        }

        $zip->close();

        return response()->download($zipRuta, $zipNombre)->deleteFileAfterSend(true);
    }

    /*
    |--------------------------------------------------------------------------
    | SYNC DRIVE — Lanza rclone en background y retorna sync_id para polling
    |--------------------------------------------------------------------------
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

        $idsEnvio = OperadorDriveFlag::where('envio', 1)->pluck('IdOper');

        $todosAdjuntos = TblAdjunto::where('Tabla', $this->tabla)
            ->activo()->get()->groupBy('IdRegTab');

        $operadoresConDocs = CatOperador::whereIn('IdOper', $idsEnvio)->get()
            ->filter(fn($op) => $todosAdjuntos->has($op->IdOper));

        if ($operadoresConDocs->isEmpty()) {
            return response()->json(
                ['error' => 'Ningún operador marcado para Drive tiene documentos almacenados.'], 422
            );
        }

        // ── Validar que todos tengan CURP registrado ──────────
        $sinCurp = $operadoresConDocs->filter(fn($op) => empty(trim((string) $op->CURP)));

        if ($sinCurp->isNotEmpty()) {
            $nombres = $sinCurp->pluck('Operador')->implode(', ');
            return response()->json([
                'error' => "Los siguientes operadores no tienen CURP registrado y deben completarlo antes de enviar a Drive: {$nombres}.",
            ], 422);
        }

        // ── Evitar duplicar lo que ya se envió desde el sistema viejo (catempleados) por CURP ──
        $curpsYaEnviadasViejo = TblAdjunto::where('Tabla', config('expediente_docs.tabla_bd'))
            ->whereNotNull('EnvioDrive')
            ->whereIn('IdRegTab', CatEmpleado::whereIn('CURP', $operadoresConDocs->pluck('CURP'))->pluck('IdEmpleado'))
            ->get(['IdRegTab'])
            ->pluck('IdRegTab')
            ->unique();

        if ($curpsYaEnviadasViejo->isNotEmpty()) {
            $curpsViejas = CatEmpleado::whereIn('IdEmpleado', $curpsYaEnviadasViejo)->pluck('CURP');
            $operadoresConDocs = $operadoresConDocs->reject(fn($op) => $curpsViejas->contains($op->CURP));
        }

        if ($operadoresConDocs->isEmpty()) {
            return response()->json(
                ['error' => 'Todos los operadores marcados ya fueron enviados a Drive desde el sistema anterior.'], 422
            );
        }

        $syncId  = 'drive_op_' . uniqid('', true);
        $syncDir = storage_path('app/temp/' . $syncId);
        $tmpBase = $syncDir . DIRECTORY_SEPARATOR . 'files';
        mkdir($tmpBase, 0755, true);

        $totalArchivos = 0;
        $idsEnviados   = [];

        foreach ($operadoresConDocs as $op) {
            $curp      = $op->CURP ?? $op->IdOper;
            $carpetaOp = $tmpBase . DIRECTORY_SEPARATOR . "{$curp}_{$siglas}";
            if (!is_dir($carpetaOp)) mkdir($carpetaOp, 0755, true);

            foreach ($todosAdjuntos->get($op->IdOper, collect()) as $adj) {
                $tipo = $adj->tipoDocumento();
                if (!$tipo || !is_null($adj->EnvioDrive)) continue;

                $rutaFisica = Storage::disk($this->disco)->path($adj->FullFileName);
                if (!file_exists($rutaFisica)) continue;

                $nomenclatura = $catalogo[$tipo]['nomenclatura'] ?? $tipo;
                $ext          = pathinfo($adj->OriginalFileName, PATHINFO_EXTENSION);
                copy($rutaFisica, $carpetaOp . DIRECTORY_SEPARATOR . "{$curp}_{$nomenclatura}.{$ext}");
                $idsEnviados[] = $adj->Id;
                $totalArchivos++;
            }
        }

        if ($totalArchivos === 0) {
            $this->limpiarDirectorio($syncDir);
            return response()->json(
                ['error' => 'Todos los documentos ya fueron enviados al Drive anteriormente.'], 422
            );
        }

        file_put_contents($syncDir . DIRECTORY_SEPARATOR . 'state.json', json_encode([
            'ids_enviados'     => $idsEnviados,
            'total_archivos'   => $totalArchivos,
            'operadores_count' => $operadoresConDocs->count(),
            'carpeta_drive'    => $carpetaDrive,
            'sync_dir'         => $syncDir,
        ]));

        $rcloneBin     = env('RCLONE_BIN', 'rclone');
        $rcloneConf    = env('RCLONE_CONF');
        $remoteNombre  = env('RCLONE_REMOTE', 'gdrive_cliente');
        $driveFolderId = env('DRIVE_FOLDER_ID');

        $destDrive  = "{$remoteNombre}:{$carpetaDrive}";
        $confFlag   = $rcloneConf    ? '--config '              . escapeshellarg($rcloneConf)    : '';
        $folderFlag = $driveFolderId ? '--drive-root-folder-id ' . escapeshellarg($driveFolderId) : '';

        $logFile      = $syncDir . DIRECTORY_SEPARATOR . 'rclone.log';
        $exitCodeFile = $syncDir . DIRECTORY_SEPARATOR . 'exitcode.txt';
        $isWindows    = strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';

        if ($isWindows) {
            $bat  = "@echo off\r\n";
            $bat .= sprintf(
                '%s copy %s %s %s %s --transfers=4 > %s 2>&1' . "\r\n",
                escapeshellarg($rcloneBin),
                escapeshellarg($tmpBase),
                escapeshellarg($destDrive),
                $confFlag, $folderFlag,
                escapeshellarg($logFile)
            );
            $bat .= 'echo %ERRORLEVEL% > ' . escapeshellarg($exitCodeFile) . "\r\n";
            $script = $syncDir . '\\run.bat';
            file_put_contents($script, $bat);
            pclose(popen('start /B cmd /c ' . escapeshellarg($script), 'r'));
        } else {
            $sh  = "#!/bin/bash\n";
            $sh .= sprintf(
                '%s copy %s %s %s %s --transfers=4 > %s 2>&1' . "\n",
                escapeshellarg($rcloneBin),
                escapeshellarg($tmpBase),
                escapeshellarg($destDrive),
                $confFlag, $folderFlag,
                escapeshellarg($logFile)
            );
            $sh .= 'echo $? > ' . escapeshellarg($exitCodeFile) . "\n";
            $script = $syncDir . '/run.sh';
            file_put_contents($script, $sh);
            chmod($script, 0755);
            exec("nohup {$script} > /dev/null 2>&1 &");
        }

        return response()->json([
            'sync_id'    => $syncId,
            'total'      => $totalArchivos,
            'operadores' => $operadoresConDocs->count(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | SYNC DRIVE STATUS — Polling del job en background
    |--------------------------------------------------------------------------
    */
    public function syncDriveStatus(string $syncId)
    {
        if (!preg_match('/^drive_op_[a-z0-9_.]+$/i', $syncId)) {
            return response()->json(['status' => 'error', 'message' => 'ID inválido.'], 400);
        }

        $syncDir      = storage_path('app/temp/' . $syncId);
        $exitCodeFile = $syncDir . DIRECTORY_SEPARATOR . 'exitcode.txt';
        $stateFile    = $syncDir . DIRECTORY_SEPARATOR . 'state.json';
        $logFile      = $syncDir . DIRECTORY_SEPARATOR . 'rclone.log';

        if (!is_dir($syncDir)) {
            return response()->json(['status' => 'error', 'message' => 'Job no encontrado.'], 404);
        }

        if (!file_exists($exitCodeFile)) {
            return response()->json(['status' => 'running']);
        }

        $exitCode = (int) trim(file_get_contents($exitCodeFile));
        $state    = json_decode(file_get_contents($stateFile), true);
        $log      = file_exists($logFile) ? file_get_contents($logFile) : '';

        $this->limpiarDirectorio($syncDir);

        if ($exitCode === 0) {
            if (!empty($state['ids_enviados'])) {
                TblAdjunto::whereIn('Id', $state['ids_enviados'])
                    ->update(['EnvioDrive' => now(), 'updated_at' => now()]);
            }
            return response()->json([
                'status'  => 'done',
                'message' => "✅ Enviados {$state['total_archivos']} archivos de {$state['operadores_count']} operadores a Drive/{$state['carpeta_drive']}",
            ]);
        }

        return response()->json([
            'status'  => 'error',
            'message' => "rclone terminó con código {$exitCode}.",
            'output'  => $log,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE VIGENCIA — Guarda fecha de vencimiento de licencia (VencimientoLic)
    |--------------------------------------------------------------------------
    */
    public function updateVigencia(Request $request, int $id)
    {
        $request->validate([
            'campo' => ['required', 'string', 'in:VencimientoLic'],
            'fecha' => ['nullable', 'date'],
        ]);

        $operador = CatOperador::findOrFail($id);
        $operador->update([$request->campo => $request->fecha ?: null]);

        return back()->with('success', 'Fecha de vencimiento actualizada.');
    }

    /*
    |--------------------------------------------------------------------------
    | TOGGLE ENVIO — Marca / desmarca operador para envío a Drive
    |--------------------------------------------------------------------------
    | catoperadores no tiene columna 'envio'; la marca vive en la tabla local
    | operador_drive_flags (cmwsirus) para no requerir ALTER en bdcsirus.
    */
    public function toggleEnvio(Request $request, int $id)
    {
        CatOperador::findOrFail($id);
        $envio = $request->boolean('envio');

        OperadorDriveFlag::updateOrCreate(['IdOper' => $id], ['envio' => $envio]);

        return back()->with('success',
            $envio
                ? 'Operador marcado para envío a Drive.'
                : 'Operador desmarcado del envío a Drive.'
        );
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE CURP — Agrega / corrige el CURP de un operador
    |--------------------------------------------------------------------------
    */
    public function updateCurp(Request $request, int $id)
    {
        $request->validate([
            'curp' => ['required', 'string', 'size:18', 'regex:/^[A-Z]{4}[0-9]{6}[HM][A-Z]{5}[0-9A-Z]{2}$/i'],
        ], [
            'curp.size'  => 'El CURP debe tener exactamente 18 caracteres.',
            'curp.regex' => 'El formato del CURP no es válido.',
        ]);

        $operador = CatOperador::findOrFail($id);
        $operador->update(['CURP' => strtoupper(trim($request->curp))]);

        return back()->with('success', 'CURP actualizado correctamente.');
    }

    /*
    |--------------------------------------------------------------------------
    | UPDATE NOMBRE — Corrige el nombre del operador
    |--------------------------------------------------------------------------
    */
    public function updateNombre(Request $request, int $id)
    {
        $request->validate([
            'nombre' => ['required', 'string', 'min:3', 'max:200'],
        ], [
            'nombre.min' => 'El nombre debe tener al menos 3 caracteres.',
        ]);

        $operador = CatOperador::findOrFail($id);
        $operador->update(['Operador' => strtoupper(trim($request->nombre))]);

        return back()->with('success', 'Nombre actualizado correctamente.');
    }

    /*
    |--------------------------------------------------------------------------
    | DAR DE BAJA — Cambia Estatus a "B" (desaparece de la lista activa)
    |--------------------------------------------------------------------------
    */
    public function darDeBaja(int $id)
    {
        $operador = CatOperador::findOrFail($id);
        $nombre   = $operador->Operador;
        $operador->update(['Estatus' => 'B']);

        return redirect()->route('expedientes-operadores.index')
            ->with('success', "Operador \"{$nombre}\" dado de baja correctamente.");
    }

    /*
    |--------------------------------------------------------------------------
    | Helper — busca si el operador (por CURP o Nombre) ya tiene expediente
    | con documentos en el sistema viejo (catempleados / tbladjuntos EMPLEADO)
    |--------------------------------------------------------------------------
    */
    private function buscarDuplicadoAnterior(CatOperador $operador): ?array
    {
        $empleado = CatEmpleado::where('CURP', $operador->CURP)
            ->orWhere('Nombre', $operador->Operador)
            ->first();

        if (!$empleado) {
            return null;
        }

        $docsViejos = TblAdjunto::delEmpleado($empleado->IdEmpleado)->activo()->get();

        if ($docsViejos->isEmpty()) {
            return null;
        }

        $catalogo = config('expediente_docs.documentos');
        $nombresDocs = $docsViejos->map(fn($d) => $d->tipoDocumento())
            ->unique()->filter()
            ->map(fn($t) => $catalogo[$t]['nombre'] ?? $t)
            ->values();

        return [
            'IdEmpleado' => $empleado->IdEmpleado,
            'Nombre'     => $empleado->Nombre,
            'CURP'       => $empleado->CURP,
            'totalDocs'  => $docsViejos->count(),
            'docs'       => $nombresDocs,
        ];
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
