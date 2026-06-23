<?php

namespace App\Http\Controllers;

use App\Models\CatEmpleado;
use App\Models\CatOperador;
use App\Models\OperadorDriveFlag;
use App\Models\TblAdjunto;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class ExpedienteController extends Controller
{
    private string $tabla;
    private string $bucket;
    private string $disco;

    private string $tablaOperador;
    private string $bucketOperador;

    public function __construct()
    {
        $this->tabla  = config('expediente_docs.tabla_bd');   // 'EMPLEADO'
        $this->bucket = config('expediente_docs.bucket');     // 'expedientes'
        $this->disco  = config('expediente_docs.disco');      // 'public'

        $this->tablaOperador  = config('expediente_docs.tabla_bd_operador');  // 'OPERADOR'
        $this->bucketOperador = config('expediente_docs.bucket_operador');    // 'expedientes_operadores'
    }

    /*
    |--------------------------------------------------------------------------
    | INDEX — Lista UNIFICADA: catempleados (default) + catoperadores
    |--------------------------------------------------------------------------
    | catempleados manda. Un operador de catoperadores solo se agrega a la
    | lista si su CURP o Nombre NO coincide con nadie ya en catempleados —
    | así no se duplica el mismo operador en dos filas.
    */
    public function index(Request $request)
    {
        $busqueda  = $request->input('q');
        $soloDrive = $request->boolean('drive');
        $totalDocs = collect(config('expediente_docs.documentos'))->filter(fn($d) => $d['obligatorio'] ?? true)->count();

        $todos = $this->unificados();

        $filtrados = $todos
            ->when($busqueda, function (Collection $c) use ($busqueda) {
                $b = mb_strtolower($busqueda);
                return $c->filter(fn($r) => str_contains(mb_strtolower($r->Nombre ?? ''), $b)
                    || str_contains(mb_strtolower($r->CURP ?? ''), $b));
            })
            ->when($soloDrive, fn(Collection $c) => $c->filter(fn($r) => $r->envio))
            ->sortBy(fn($r) => $r->Nombre)
            ->values();

        $perPage = 25;
        $page    = (int) ($request->input('page', 1));
        $page    = max(1, $page);

        $empleados = new LengthAwarePaginator(
            $filtrados->forPage($page, $perPage)->values(),
            $filtrados->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        $clavesObligatorias = collect(config('expediente_docs.documentos'))
            ->filter(fn($d) => $d['obligatorio'] ?? true)->keys();

        $idsEmpPagina = $empleados->getCollection()->where('origen', 'EMPLEADO')->pluck('id');
        $idsOpPagina  = $empleados->getCollection()->where('origen', 'OPERADOR')->pluck('id');

        $adjuntosEmp = TblAdjunto::where('Tabla', $this->tabla)
            ->whereIn('IdRegTab', $idsEmpPagina)->where('Estatus', '!=', 'ELIMINADO')->get()->groupBy('IdRegTab');
        $adjuntosOp = TblAdjunto::where('Tabla', $this->tablaOperador)
            ->whereIn('IdRegTab', $idsOpPagina)->where('Estatus', '!=', 'ELIMINADO')->get()->groupBy('IdRegTab');

        $completitud = [];
        foreach ($empleados as $row) {
            $docs  = $row->origen === 'EMPLEADO'
                ? $adjuntosEmp->get($row->id, collect())
                : $adjuntosOp->get($row->id, collect());

            $tipos    = $docs->map(fn($d) => $d->tipoDocumento())->unique()->filter()
                             ->intersect($clavesObligatorias)->values();
            $count    = $tipos->count();
            $pct      = $totalDocs > 0 ? (int) round($count / $totalDocs * 100) : 0;
            $completo = $count >= $totalDocs;
            $enviados = $docs->filter(fn($d) => !is_null($d->EnvioDrive)
                && $clavesObligatorias->contains($d->tipoDocumento()))->count();

            $completitud[$row->origen . '_' . $row->id] = [
                'count'    => $count,
                'total'    => $totalDocs,
                'pct'      => $pct,
                'completo' => $completo,
                'enviados' => $enviados,
            ];
        }

        // ── Stats globales sobre todo el conjunto unificado (no solo la página) ──
        $adjuntosGlobalEmp = TblAdjunto::where('Tabla', $this->tabla)
            ->whereIn('IdRegTab', $todos->where('origen', 'EMPLEADO')->pluck('id'))
            ->where('Estatus', '!=', 'ELIMINADO')->get()->groupBy('IdRegTab');
        $adjuntosGlobalOp = TblAdjunto::where('Tabla', $this->tablaOperador)
            ->whereIn('IdRegTab', $todos->where('origen', 'OPERADOR')->pluck('id'))
            ->where('Estatus', '!=', 'ELIMINADO')->get()->groupBy('IdRegTab');

        $completosGlobal = $filtradosVacio = 0;
        foreach ($todos as $row) {
            $docs  = $row->origen === 'EMPLEADO'
                ? $adjuntosGlobalEmp->get($row->id, collect())
                : $adjuntosGlobalOp->get($row->id, collect());
            $tipos = $docs->map(fn($d) => $d->tipoDocumento())->unique()->filter()->intersect($clavesObligatorias);
            if ($tipos->count() >= $totalDocs) $completosGlobal++;
        }

        $statsGlobales = [
            'completos'         => $completosGlobal,
            'incompletos'       => $todos->count() - $completosGlobal,
            'total'             => $todos->count(),
            'marcadosParaDrive' => $todos->filter(fn($r) => $r->envio)->count(),
        ];

        return view('expedientes.index', compact('empleados', 'completitud', 'busqueda', 'soloDrive', 'totalDocs', 'statsGlobales'));
    }

    /*
    |--------------------------------------------------------------------------
    | SHOW — Expediente de un operador del catálogo EMPLEADO (catempleados)
    |--------------------------------------------------------------------------
    */
    public function show(int $id)
    {
        $empleado = CatEmpleado::findOrFail($id);
        return $this->mostrarExpediente($empleado, 'EMPLEADO', $this->tabla, 'expedientes.show');
    }

    /*
    |--------------------------------------------------------------------------
    | SHOW OPERADOR — Expediente de un registro proveniente de catoperadores
    |--------------------------------------------------------------------------
    */
    public function showOperador(int $id)
    {
        $operador = CatOperador::findOrFail($id);
        return $this->mostrarExpediente($operador, 'OPERADOR', $this->tablaOperador, 'expedientes.show');
    }

    private function mostrarExpediente($registro, string $origen, string $tablaAdj, string $vista)
    {
        $esOperador = $origen === 'OPERADOR';
        $id         = $esOperador ? $registro->IdOper : $registro->IdEmpleado;
        $nombre     = $esOperador ? $registro->Operador : $registro->Nombre;

        // catoperadores guarda la vigencia de licencia en 'VencimientoLic', no 'FechaVL'
        $catalogo = collect(config('expediente_docs.documentos'))->map(function ($doc) use ($esOperador) {
            if ($esOperador && ($doc['campo_vigencia'] ?? null) === 'FechaVL') {
                $doc['campo_vigencia'] = 'VencimientoLic';
            }
            return $doc;
        })->all();

        $adjuntos = TblAdjunto::deTabla($tablaAdj, $id)->activo()->orderByDesc('Creado')->get();

        $porTipo = [];
        foreach ($adjuntos as $adj) {
            $tipo = $adj->tipoDocumento();
            if ($tipo && !isset($porTipo[$tipo])) {
                $porTipo[$tipo] = $adj;
            }
        }

        $obligatorios = collect($catalogo)->filter(fn($d) => $d['obligatorio'] ?? true)->keys();
        $totalDocs    = $obligatorios->count();
        $subidos      = collect(array_keys($porTipo))->intersect($obligatorios)->count();
        $subidosTotal = collect(array_keys($porTipo))->intersect(array_keys($catalogo))->count();
        $pct          = $totalDocs > 0 ? (int) round($subidos / $totalDocs * 100) : 0;
        $completo     = $subidos >= $totalDocs;

        $envio = $esOperador
            ? (bool) OperadorDriveFlag::where('IdOper', $id)->value('envio')
            : (bool) $registro->envio;

        return view($vista, [
            'empleado'     => $registro,
            'origen'       => $origen,
            'id'           => $id,
            'nombreMostrar'=> $nombre,
            'envio'        => $envio,
            'catalogo'     => $catalogo,
            'porTipo'      => $porTipo,
            'totalDocs'    => $totalDocs,
            'subidos'      => $subidos,
            'subidosTotal' => $subidosTotal,
            'pct'          => $pct,
            'completo'     => $completo,
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | STORE — Subir / reemplazar un documento (EMPLEADO)
    |--------------------------------------------------------------------------
    */
    public function store(Request $request, int $id)
    {
        CatEmpleado::findOrFail($id);
        return $this->guardarDocumento($request, $id, $this->tabla, $this->bucket);
    }

    public function storeOperador(Request $request, int $id)
    {
        CatOperador::findOrFail($id);
        return $this->guardarDocumento($request, $id, $this->tablaOperador, $this->bucketOperador);
    }

    private function guardarDocumento(Request $request, int $id, string $tablaAdj, string $bucket)
    {
        $maxMb = config('expediente_docs.max_mb', 10);
        $tipos = array_keys(config('expediente_docs.documentos'));

        $request->validate([
            'tipo'    => ['required', 'in:' . implode(',', $tipos)],
            'archivo' => ['required', 'file', "max:{$maxMb}024"],
        ]);

        $tipo    = $request->tipo;
        $archivo = $request->file('archivo');

        $ext          = $archivo->getClientOriginalExtension();
        $nombreGuarda = strtoupper($tipo) . "_{$id}_" . time() . '.' . $ext;

        $carpeta      = $bucket . DIRECTORY_SEPARATOR . $id;
        $rutaRelativa = $carpeta . DIRECTORY_SEPARATOR . $nombreGuarda;

        $archivo->storeAs($carpeta, $nombreGuarda, $this->disco);

        TblAdjunto::deTabla($tablaAdj, $id)
            ->porTipo($tipo)
            ->activo()
            ->update(['Estatus' => 'REEMPLAZADO', 'updated_at' => now()]);

        TblAdjunto::create([
            'Tabla'            => $tablaAdj,
            'IdRegTab'         => $id,
            'Bucket'           => $bucket,
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
    | DESTROY — Eliminar lógico de un documento (compartido EMPLEADO/OPERADOR)
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
        $empleado = CatEmpleado::findOrFail($id);
        return $this->generarZip($request, $id, $empleado->Nombre, $this->tabla);
    }

    public function downloadZipOperador(Request $request, int $id)
    {
        $operador = CatOperador::findOrFail($id);
        return $this->generarZip($request, $id, $operador->Operador, $this->tablaOperador);
    }

    private function generarZip(Request $request, int $id, string $nombre, string $tablaAdj)
    {
        $catalogo = config('expediente_docs.documentos');
        $adjuntos = TblAdjunto::deTabla($tablaAdj, $id)->activo()->get();

        if ($adjuntos->isEmpty()) {
            return back()->with('error', 'El operador no tiene documentos subidos aún.');
        }

        $tiposPermitidos = $request->has('tipos')
            ? collect($request->input('tipos', []))->filter(fn($t) => isset($catalogo[$t]))->values()
            : collect($catalogo)->filter(fn($d) => $d['zip'] ?? true)->keys();

        $nombreLimpio = Str::slug($nombre, '_');
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
    | EXPORT MASIVO ZIP — Solo operadores con expediente COMPLETO (unificado)
    |--------------------------------------------------------------------------
    */
    public function exportMasivo(Request $request)
    {
        $catalogo  = config('expediente_docs.documentos');
        $totalDocs = collect($catalogo)->filter(fn($d) => $d['obligatorio'] ?? true)->count();

        $tiposPermitidos = $request->has('tipos')
            ? collect($request->input('tipos', []))->filter(fn($t) => isset($catalogo[$t]))->values()
            : collect($catalogo)->filter(fn($d) => $d['drive'] ?? true)->keys();

        $todos = $this->unificados();

        $adjuntosEmp = TblAdjunto::where('Tabla', $this->tabla)->activo()->get()->groupBy('IdRegTab');
        $adjuntosOp  = TblAdjunto::where('Tabla', $this->tablaOperador)->activo()->get()->groupBy('IdRegTab');

        $completos = $todos->filter(function ($row) use ($adjuntosEmp, $adjuntosOp, $catalogo, $totalDocs) {
            $docs  = $row->origen === 'EMPLEADO' ? $adjuntosEmp->get($row->id, collect()) : $adjuntosOp->get($row->id, collect());
            $tipos = $docs->map(fn($d) => $d->tipoDocumento())->unique()->filter()->intersect(array_keys($catalogo));
            return $tipos->count() >= $totalDocs;
        });

        if ($completos->isEmpty()) {
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

        foreach ($completos as $row) {
            $nombreLimpio = Str::slug($row->Nombre, '_');
            $docs         = $row->origen === 'EMPLEADO' ? $adjuntosEmp->get($row->id, collect()) : $adjuntosOp->get($row->id, collect());

            foreach ($docs as $adj) {
                $tipo = $adj->tipoDocumento();
                if (!$tiposPermitidos->contains($tipo)) continue;

                $rutaFisica = Storage::disk($this->disco)->path($adj->FullFileName);
                if (!file_exists($rutaFisica)) continue;

                $nombreDoc   = $catalogo[$tipo]['nombre'] ?? $tipo;
                $ext         = pathinfo($adj->OriginalFileName, PATHINFO_EXTENSION);
                $nombreEnZip = "{$nombreLimpio}_{$row->id}/{$nombreDoc}.{$ext}";
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
    | Flujo asíncrono sobre el conjunto UNIFICADO (catempleados + catoperadores
    | no duplicados). Soporta 70+ operadores sin timeout:
    |   1. Copia archivos a carpeta temporal (PHP, rápido)
    |   2. Escribe script .bat/.sh con el comando rclone
    |   3. Lanza el script en background (no bloquea el request)
    |   4. Retorna JSON con sync_id
    |   5. El frontend hace polling a syncDriveStatus() cada 3 s
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

        $adjuntosEmp = TblAdjunto::where('Tabla', $this->tabla)->activo()->get()->groupBy('IdRegTab');
        $adjuntosOp  = TblAdjunto::where('Tabla', $this->tablaOperador)->activo()->get()->groupBy('IdRegTab');

        $marcadosConDocs = $this->unificados()
            ->filter(fn($r) => $r->envio)
            ->filter(fn($r) => ($r->origen === 'EMPLEADO' ? $adjuntosEmp : $adjuntosOp)->has($r->id));

        if ($marcadosConDocs->isEmpty()) {
            return response()->json(
                ['error' => 'Ningún operador marcado para Drive tiene documentos almacenados.'], 422
            );
        }

        $sinCurp = $marcadosConDocs->filter(fn($r) => empty(trim((string) $r->CURP)));

        if ($sinCurp->isNotEmpty()) {
            $nombres = $sinCurp->pluck('Nombre')->implode(', ');
            return response()->json([
                'error' => "Los siguientes operadores no tienen CURP registrado y deben completarlo antes de enviar a Drive: {$nombres}.",
            ], 422);
        }

        $syncId  = 'drive_' . uniqid('', true);
        $syncDir = storage_path('app/temp/' . $syncId);
        $tmpBase = $syncDir . DIRECTORY_SEPARATOR . 'files';
        mkdir($tmpBase, 0755, true);

        $totalArchivos = 0;
        $idsEnviados   = [];

        foreach ($marcadosConDocs as $row) {
            $curp      = $row->CURP ?? $row->id;
            $carpetaOp = $tmpBase . DIRECTORY_SEPARATOR . "{$curp}_{$siglas}";
            if (!is_dir($carpetaOp)) mkdir($carpetaOp, 0755, true);

            $docs = $row->origen === 'EMPLEADO' ? $adjuntosEmp->get($row->id, collect()) : $adjuntosOp->get($row->id, collect());

            foreach ($docs as $adj) {
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
            'operadores_count' => $marcadosConDocs->count(),
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
            'operadores' => $marcadosConDocs->count(),
        ]);
    }

    /*
    |--------------------------------------------------------------------------
    | SYNC DRIVE STATUS — Polling del job en background
    |--------------------------------------------------------------------------
    */
    public function syncDriveStatus(string $syncId)
    {
        if (!preg_match('/^drive_[a-z0-9_.]+$/i', $syncId)) {
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
    | UPDATE VIGENCIA — Guarda fecha de vencimiento de licencia
    |--------------------------------------------------------------------------
    */
    public function updateVigencia(Request $request, int $id)
    {
        $request->validate([
            'campo' => ['required', 'string', 'in:FechaVL'],
            'fecha' => ['nullable', 'date'],
        ]);

        $empleado = CatEmpleado::findOrFail($id);
        $empleado->update([$request->campo => $request->fecha ?: null]);

        return back()->with('success', 'Fecha de vencimiento actualizada.');
    }

    public function updateVigenciaOperador(Request $request, int $id)
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

    public function toggleEnvioOperador(Request $request, int $id)
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

        $empleado = CatEmpleado::findOrFail($id);
        $empleado->update(['CURP' => strtoupper(trim($request->curp))]);

        return back()->with('success', 'CURP actualizado correctamente.');
    }

    public function updateCurpOperador(Request $request, int $id)
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

        $empleado = CatEmpleado::findOrFail($id);
        $empleado->update(['Nombre' => strtoupper(trim($request->nombre))]);

        return back()->with('success', 'Nombre actualizado correctamente.');
    }

    public function updateNombreOperador(Request $request, int $id)
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
        $empleado = CatEmpleado::findOrFail($id);
        $nombre   = $empleado->Nombre;
        $empleado->update(['Estatus' => 'B']);

        return redirect()->route('expedientes.index')
            ->with('success', "Operador \"{$nombre}\" dado de baja correctamente.");
    }

    public function darDeBajaOperador(int $id)
    {
        $operador = CatOperador::findOrFail($id);
        $nombre   = $operador->Operador;
        $operador->update(['Estatus' => 'B']);

        return redirect()->route('expedientes.index')
            ->with('success', "Operador \"{$nombre}\" dado de baja correctamente.");
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers — conjunto unificado catempleados + catoperadores (sin duplicar)
    |--------------------------------------------------------------------------
    */
    private function unificados(): Collection
    {
        $empleados = CatEmpleado::all()->map(fn($e) => (object) [
            'origen'  => 'EMPLEADO',
            'id'      => $e->IdEmpleado,
            'Nombre'  => $e->Nombre,
            'CURP'    => $e->CURP,
            'Unidad'  => $e->Unidad,
            'envio'   => (bool) $e->envio,
        ]);

        $curpsEmp   = $empleados->pluck('CURP')->filter()->map(fn($c) => mb_strtoupper(trim($c)))->flip();
        $nombresEmp = $empleados->pluck('Nombre')->filter()->map(fn($n) => mb_strtoupper(trim($n)))->flip();

        // catoperadores no tiene columna 'envio'; la marca vive en operador_drive_flags (cmwsirus)
        $idsEnvioOperador = OperadorDriveFlag::where('envio', 1)->pluck('IdOper');

        $operadores = CatOperador::all()
            ->reject(function ($o) use ($curpsEmp, $nombresEmp) {
                $curp   = mb_strtoupper(trim((string) $o->CURP));
                $nombre = mb_strtoupper(trim((string) $o->Operador));
                return ($curp && $curpsEmp->has($curp)) || ($nombre && $nombresEmp->has($nombre));
            })
            ->map(fn($o) => (object) [
                'origen'  => 'OPERADOR',
                'id'      => $o->IdOper,
                'Nombre'  => $o->Operador,
                'CURP'    => $o->CURP,
                'Unidad'  => $o->Unidad,
                'envio'   => $idsEnvioOperador->contains($o->IdOper),
            ]);

        return $empleados->concat($operadores)->values();
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
