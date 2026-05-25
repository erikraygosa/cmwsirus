<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;

class ImagenController extends Controller
{
    private string $connCat = 'mysql2';
    private string $connImg = 'mysql2';

    /* ================== VISTA ================== */
    public function index()
    {
        // Usa SIEMPRE el helper que lee desde catunidades y normaliza la columna visible
        $unidades = $this->unidadesForSelect();
        $perms    = $this->permisosDeUsuarioActual();

        // OJO: no repitas 'unidades' en compact
        return view('imagenes.index', compact('unidades', 'perms'));
    }

    /* ================== PERMISOS ================== */
    private function normUser(?string $u): string
    {
        $u = trim((string)$u);
        $u = mb_strtolower($u, 'UTF-8');
        $u = Str::ascii($u);
        $u = preg_replace('/\s+/', ' ', $u);
        return trim($u);
    }

    private function usuarioActual(): ?string
    {
        return optional(auth()->user())->name ?? session('usuario');
    }

    /** Retorna: subirImagenes, borrarImagenes, subirIEvidencia, borrarEvidencia (booleans) */
    private function permisosDeUsuarioActual(): array
    {
        $raw = $this->usuarioActual();
        if (!$raw) {
            return ['subirImagenes'=>false,'borrarImagenes'=>false,'subirIEvidencia'=>false,'borrarEvidencia'=>false];
        }
        $login = $this->normUser($raw);

        if ($login === 'admin') {
            return ['subirImagenes'=>true,'borrarImagenes'=>true,'subirIEvidencia'=>true,'borrarEvidencia'=>true];
        }

        $rows = DB::connection($this->connImg)
            ->table('tblusuarios')
            ->select([
                'Usuario',
                'SubirFotosFolImg',
                'EliminarFotosFolImg',
                'SubirFotosEviFolImg',
                'EliminarFotosEviFolImg',
            ])
            ->get();

        $found = $rows->first(fn($r)=> $this->normUser($r->Usuario ?? '') === $login);
        $S = fn($v)=> strtoupper(trim((string)$v)) === 'S';

        if (!$found) {
            return ['subirImagenes'=>false,'borrarImagenes'=>false,'subirIEvidencia'=>false,'borrarEvidencia'=>false];
        }

        return [
            'subirImagenes'   => $S($found->SubirFotosFolImg ?? 'N'),
            'borrarImagenes'  => $S($found->EliminarFotosFolImg ?? 'N'),
            'subirIEvidencia' => $S($found->SubirFotosEviFolImg ?? 'N'),
            'borrarEvidencia' => $S($found->EliminarFotosEviFolImg ?? 'N'),
        ];
    }

    /* ================== HELPERS ================== */
    private function empresaRFC(): ?string
    {
        $rfc = DB::connection($this->connImg)->table('tbldatosempresa')->value('RFC');
        return $rfc ? trim($rfc) : null;
    }

    private function unidadClavePorId(int $id): ?string
    {
        $u = DB::connection($this->connCat)->table('catunidades')->where('IdUnidad', $id)->value('Unidad');
        return $u ? trim($u) : null;
    }

    private function contarAdjuntos(int $folio): int
    {
        return (int) DB::connection($this->connImg)->table('tbladjuntos')->where('IdRegTab', $folio)->count();
    }

    /** FullFileName = "<RFC>/<ID>.<ext>" -> URL pública + path escapa/encode */
    private function publicUrlFromFullFileName(?string $fullFileName): array
    {
        $full = trim((string) $fullFileName);
        if ($full === '') return ['url' => null, 'path' => null];

        $full     = str_replace('\\', '/', $full);
        $relative = 'upload/controlmant/' . ltrim($full, '/');

        $url        = str_replace('\\', '/', Storage::disk('public')->url($relative));
        $pathForUrl = implode('/', array_map('rawurlencode', explode('/', $relative)));

        return ['url' => $url, 'path' => $pathForUrl];
    }

    /** ¿Se puede cerrar el folio? -> todas las imágenes (no evidencia) deben estar CONFIRMADAS */
    private function puedeCerrarFolio(int $folio): bool
    {
        $tablas = [
            'TblImagenDel','TblImagenTra','TblImagenIzq',
            'TblImagenDer','TblImagenEspIzq','TblImagenEspDer','TblImagenInt'
        ];

        $totalValidas = (int) DB::connection($this->connImg)->table('tbladjuntos')
            ->where('IdRegTab', $folio)
            ->whereIn('Tabla', $tablas)
            ->whereNotNull('Estatus')->where('Estatus','<>','')
            ->where('Estatus','<>','CANCELADO')
            ->where('Estatus','<>','EVIDENCIA')
            ->count();

        $confirmadas = (int) DB::connection($this->connImg)->table('tbladjuntos')
            ->where('IdRegTab', $folio)
            ->whereIn('Tabla', $tablas)
            ->where('Estatus', 'CONFIRMADO')
            ->count();

        return $totalValidas > 0 && $totalValidas === $confirmadas;
    }

    /* ================== FOLIO ================== */
    public function ajaxCrearAbierto(Request $r)
    {
        try {
            $r->validate([
                'IdUnidad'    => 'required|integer',
                'Fecha'       => 'nullable|date',
                'Comentarios' => 'nullable|string',
            ]);

            $idUnidad    = (int) $r->IdUnidad;
            $unidadClave = $this->unidadClavePorId($idUnidad);
            if (!$unidadClave) {
                return response()->json(['ok' => false, 'error' => 'Unidad no encontrada'], 404);
            }

            return DB::connection($this->connImg)->transaction(function () use ($r, $idUnidad, $unidadClave) {
                // Limpia folios ABIERTO sin fotos
                $folios = DB::connection($this->connImg)
                    ->table('tblimagen')
                    ->select('Folio')
                    ->where('Estatus', 'ABIERTO')
                    ->where(function ($q) use ($idUnidad, $unidadClave) {
                        $q->where('IdUnidad', $idUnidad)->orWhere('Unidad', $unidadClave);
                    })
                    ->orderByDesc('Creado')
                    ->get();

                foreach ($folios as $f) {
                    if ($this->contarAdjuntos((int) $f->Folio) === 0) {
                        DB::connection($this->connImg)->table('tblimagen')
                            ->where('Folio', $f->Folio)->delete();
                    }
                }

                // Crear nuevo ABIERTO
                $folio = DB::connection($this->connImg)->table('tblimagen')->insertGetId([
                    'Fecha'              => $r->Fecha ?: now()->toDateString(),
                    'IdUnidad'           => $idUnidad,
                    'Unidad'             => $unidadClave,
                    'TotalDetDel'        => 0, 'TotalDetDelAtnd'    => 0,
                    'TotalDetTra'        => 0, 'TotalDetTraAtnd'    => 0,
                    'TotalDetIzq'        => 0, 'TotalDetIzqAtnd'    => 0,
                    'TotalDetDer'        => 0, 'TotalDetDerAtnd'    => 0,
                    'TotalDetEspIzq'     => 0, 'TotalDetEspIzqAtnd' => 0,
                    'TotalDetEspDer'     => 0, 'TotalDetEspDerAtnd' => 0,
                    'TotalDetInt'        => 0, 'TotalDetIntAtnd'       => 0,
                    'Comentarios'        => $r->input('Comentarios'),
                    'Estatus'            => 'ABIERTO',
                    'Creado'             => now(),
                ]);

                return response()->json(['ok' => true, 'folio' => $folio], 201);
            });
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['ok' => false, 'error' => 'Server error: '.$e->getMessage()], 500);
        }
    }

    public function create(Request $request)
    {
        // ⚠️ Antes leías TblUnidad: eso causaba el error. Ahora reusamos el helper.
        $unidades = $this->unidadesForSelect();

        // Usa tus permisos reales; dejo ejemplo liberal si quieres forzar acceso aquí:
        $perms = (object)$this->permisosDeUsuarioActual();

        // Preselección opcional desde ?unidad= (IdUnidad) y ?u_text= (texto)
        $pre = [
            'id'   => $request->query('unidad'),
            'text' => $request->query('u_text'),
        ];

        return view('imagenes.create', compact('unidades','perms','pre'));
    }

    public function cerrar($folio)
    {
        $row = DB::connection($this->connImg)->table('tblimagen')->where('Folio', $folio)->first();
        if (!$row) return back()->withErrors('Folio no encontrado');

        if (!$this->puedeCerrarFolio((int)$row->Folio)) {
            return back()->withErrors('Todas las imagenes deben estar CONFIRMADAS.');
        }

        DB::connection($this->connImg)->table('tblimagen')->where('Folio', $folio)->update(['Estatus' => 'CERRADO']);
        return back()->with('success', "Folio #{$folio} cerrado.");
    }

    /** Eliminar folio (solo ABIERTO y sin imágenes) */
    public function eliminarFolio($folio)
    {
        $row = DB::connection($this->connImg)->table('tblimagen')->where('Folio', $folio)->first();
        if (!$row) return response()->json(['ok' => false, 'error' => 'Folio no encontrado'], 404);

        if ($row->Estatus !== 'ABIERTO') {
            return response()->json(['ok' => false, 'error' => 'No se puede eliminar: el ciclo ya inició.'], 422);
        }
        if ($this->contarAdjuntos((int)$folio) > 0) {
            return response()->json(['ok' => false, 'error' => 'No se puede eliminar: el folio tiene imágenes.'], 422);
        }

        DB::connection($this->connImg)->table('tblimagen')->where('Folio', $folio)->delete();
        return response()->json(['ok' => true]);
    }

    /** Cambiar estatus del folio con reglas */
    public function cambiarEstatusFolio($folio, Request $request)
    {
        $request->validate(['estatus' => 'required|string|in:ABIERTO,EN PROCESO,CERRADO,CANCELADO']);
        $nuevo = $request->input('estatus');

        $row = DB::connection($this->connImg)->table('tblimagen')->where('Folio', $folio)->first();
        if (!$row) {
            return response()->json(['ok'=>false,'type'=>'danger','message'=>'Folio no encontrado'], 404);
        }

        if ($nuevo === 'CANCELADO') {
            if ($row->Estatus !== 'ABIERTO') {
                return response()->json(['ok'=>false,'type'=>'warning','message'=>'Solo folios ABIERTO pueden cancelarse.'], 422);
            }
            $tieneAtendidos = DB::connection($this->connImg)->table('tbladjuntos')
                ->where('IdRegTab', $folio)
                ->whereIn('Estatus', ['ATENDIDO', 'DOCUMENTADO', 'CONFIRMADO'])
                ->exists();
            if ($tieneAtendidos) {
                return response()->json(['ok'=>false,'type'=>'warning','message'=>'No se puede cancelar: ya hay adjuntos atendidos/documentados/confirmados.'], 422);
            }
        }

        if ($nuevo === 'CERRADO' && !$this->puedeCerrarFolio((int) $folio)) {
            return response()->json(['ok'=>false,'type'=>'warning','message'=>'No se puede cerrar: todas las imágenes deben estar CONFIRMADAS.'], 422);
        }

        DB::connection($this->connImg)->table('tblimagen')->where('Folio', $folio)->update(['Estatus' => $nuevo]);
        return response()->json(['ok' => true]);
    }

    /** Estado de unidad (panel superior) + adjuntos por lado */
    public function ajaxEstadoUnidad($idUnidad)
    {
        try {
            $id          = (int) $idUnidad;
            $unidadClave = $this->unidadClavePorId($id);
            if (!$unidadClave) {
                return response()->json(['ok' => true, 'doc' => null, 'adjuntos' => []]);
            }

            $doc = DB::connection($this->connImg)->table('tblimagen')
                ->where('Estatus', 'ABIERTO')
                ->where(function ($q) use ($id, $unidadClave) {
                    $q->where('IdUnidad', $id)->orWhere('Unidad', $unidadClave);
                })
                ->orderByDesc('Creado')
                ->first();

            if (!$doc) {
                return response()->json(['ok' => true, 'doc' => null, 'adjuntos' => []]);
            }

            $tablas = [
                'TblImagenDel','TblImagenTra','TblImagenIzq',
                'TblImagenDer','TblImagenEspIzq','TblImagenEspDer','TblImagenInt',
            ];

            // IMÁGENES (excluye evidencia)
            $imgs = DB::connection($this->connImg)->table('tbladjuntos')
                ->where('IdRegTab', $doc->Folio)
                ->whereIn('Tabla', $tablas)
                ->whereNotNull('Estatus')->where('Estatus','<>','')
                ->where('Estatus','<>','EVIDENCIA')
                ->orderByDesc('created_at')
                ->get();

            // EVIDENCIAS por lado (Estatus=EVIDENCIA)
            $evids = DB::connection($this->connImg)->table('tbladjuntos')
                ->where('IdRegTab', $doc->Folio)
                ->whereIn('Tabla', $tablas)
                ->where('Estatus', 'EVIDENCIA')
                ->orderBy('created_at')
                ->get()
                ->groupBy('Tabla');

            // Pegar evidencia 1-a-1
            $grouped = collect($imgs)->groupBy('Tabla')->map(function ($g, $tabla) use ($evids) {
                $evList = ($evids[$tabla] ?? collect())->values();
                $evIdx  = 0;

                return $g->map(function ($r) use ($evList, &$evIdx) {
                    $built = $this->publicUrlFromFullFileName($r->FullFileName);

                    $evidencia = null;
                    if ($evIdx < $evList->count()) {
                        $ev = $evList[$evIdx];
                        $b2 = $this->publicUrlFromFullFileName($ev->FullFileName ?? null);
                        $evidencia = [
                            'Id'  => (int)$ev->Id,
                            'Url' => $b2['url'],
                            'Path'=> $b2['path'],
                        ];
                        $evIdx++;
                    }

                    return [
                        'Id'          => (int) $r->Id,
                        'Tabla'       => $r->Tabla,
                        'FullPath'    => $r->FullFileName,
                        'Bucket'      => 'ControlMant',
                        'Url'         => $built['url'],
                        'Path'        => $built['path'],
                        'Comentarios' => $r->Comentarios,
                        'Estatus'     => $r->Estatus,
                        'IdDocRel'    => $r->IdDocRel,
                        'Evidencia'   => $evidencia,
                        'created_at'  => $r->created_at,
                    ];
                })->values();
            });

            $puedeCerrar = $this->puedeCerrarFolio((int)$doc->Folio);

            return response()->json([
                'ok'  => true,
                'doc' => [
                    'Folio'        => (int) $doc->Folio,
                    'Fecha'        => (string) $doc->Fecha,
                    'Estatus'      => (string) $doc->Estatus,
                    'Unidad'       => (string) $doc->Unidad,
                    'Comentarios'  => (string) ($doc->Comentarios ?? ''),
                    'puede_cerrar' => $puedeCerrar,
                    'totales'      => [
                        'Del'    => [(int) $doc->TotalDetDel,    (int) $doc->TotalDetDelAtnd],
                        'Tra'    => [(int) $doc->TotalDetTra,    (int) $doc->TotalDetTraAtnd],
                        'Izq'    => [(int) $doc->TotalDetIzq,    (int) $doc->TotalDetIzqAtnd],
                        'Der'    => [(int) $doc->TotalDetDer,    (int) $doc->TotalDetDerAtnd],
                        'EspIzq' => [(int) $doc->TotalDetEspIzq, (int) $doc->TotalDetEspIzqAtnd],
                        'EspDer' => [(int) $doc->TotalDetEspDer, (int) $doc->TotalDetEspDerAtnd],
                        'Int' => [(int) $doc->TotalDetInt, (int) $doc->TotalDetIntAtnd],

                    ],
                ],
                'adjuntos' => $grouped,
            ]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['ok' => false, 'error' => 'Server error: '.$e->getMessage()], 500);
        }
    }

    // Elige la mejor columna para mostrar la unidad según exista en la tabla
    private function unidadesForSelect(): \Illuminate\Support\Collection

    {
        $conn = DB::connection($this->connCat);
        $schemaCols = $conn->getSchemaBuilder()->getColumnListing('catunidades');

        // orden de preferencia
        $prefer = ['Unidad','unidad','Descripcion','descripcion','Nombre','nombre'];

        $labelCol = collect($prefer)->first(fn($c) => in_array($c, $schemaCols, true)) ?? 'Unidad';

        // arma el select con alias "Unidad"
        $selectExpr = $labelCol === 'Unidad'
            ? 'Unidad as Unidad'
            : ($labelCol === 'unidad'
                ? 'unidad as Unidad'
                : DB::raw("`{$labelCol}` as Unidad"));

        return $conn->table('catunidades')
            ->select('IdUnidad', $selectExpr)
            ->orderBy($labelCol)
            ->get();
    }

    public function edit(string $folio)
    {
        // Cabecera del folio
        $doc = DB::connection($this->connImg)
            ->table('tblimagen')
            ->where('Folio', $folio)
            ->first();

        if (!$doc) abort(404, 'Folio no encontrado');

        $tablas = [
            'TblImagenDel','TblImagenTra','TblImagenIzq',
            'TblImagenDer','TblImagenEspIzq','TblImagenEspDer','TblImagenInt',
        ];

        // Traer IMÁGENES (excluye evidencia)
        $imgs = DB::connection($this->connImg)->table('tbladjuntos')
            ->where('IdRegTab', $folio)
            ->whereIn('Tabla', $tablas)
            ->where('Estatus', '<>', 'EVIDENCIA')
            ->orderBy('Id', 'asc')
            ->get();

        // Para cada imagen, buscar evidencia por IdDocRel
        $imgs = $imgs->map(function($r){
            $ev = DB::connection($this->connImg)->table('tbladjuntos')
                ->where('IdRegTab', $r->IdRegTab)
                ->where('Tabla', $r->Tabla)
                ->where('Estatus', 'EVIDENCIA')
                ->where('IdDocRel', $r->Id)
                ->orderBy('Id', 'asc')
                ->first();

            $b1 = $this->publicUrlFromFullFileName($r->FullFileName);
            $r->Url  = $b1['url'];
            $r->Path = $b1['path'];

            if ($ev) {
                $b2 = $this->publicUrlFromFullFileName($ev->FullFileName);
                $r->Evidencia = (object)[
                    'Id'   => (int)$ev->Id,
                    'Url'  => $b2['url'],
                    'Path' => $b2['path'],
                ];
            } else {
                $r->Evidencia = null;
            }

            return $r;
        });

        $adjuntos = $imgs->groupBy('Tabla');

        $lados = $tablas;
        $cont = [];
        foreach ($lados as $k) {
            $lista = $adjuntos->get($k, collect());
            $cont[$k] = [
                'tot'   => $lista->count(),
                'atend' => $lista->where('Estatus', 'ATENDIDO')->count(),
            ];
        }
        $totalesLinea = sprintf(
            'Del %d/%d · Tra %d/%d · Izq %d/%d · Der %d/%d · EspIzq %d/%d · EspDer %d/%d · Int %d/%d',
            $cont['TblImagenDel']['atend'],    $cont['TblImagenDel']['tot'],
            $cont['TblImagenTra']['atend'],    $cont['TblImagenTra']['tot'],
            $cont['TblImagenIzq']['atend'],    $cont['TblImagenIzq']['tot'],
            $cont['TblImagenDer']['atend'],    $cont['TblImagenDer']['tot'],
            $cont['TblImagenEspIzq']['atend'], $cont['TblImagenEspIzq']['tot'],
            $cont['TblImagenEspDer']['atend'], $cont['TblImagenEspDer']['tot'],
            $cont['TblImagenInt']['atend'],    $cont['TblImagenInt']['tot']
          );

        $perms = $this->permisosDeUsuarioActual();

        return view('imagenes.edit', [
            'folio'        => (int)$folio,
            'doc'          => $doc,
            'adjuntos'     => $adjuntos,
            'totalesLinea' => $totalesLinea,
            'perms'        => $perms,
        ]);
    }

    /* ================== ADJUNTOS (PNG EDITADO) ================== */

    /** SUBIR imagen editada (PNG combinado) */
    public function store(Request $request)
    {
        $perms = $this->permisosDeUsuarioActual();
        if (!$perms['subirImagenes']) {
            return response()->json(['ok'=>false,'error'=>'Sin permiso para subir imágenes'], 403);
        }

        $request->validate([
            'tabla_lado' => 'required|string|in:TblImagenDel,TblImagenTra,TblImagenIzq,TblImagenDer,TblImagenEspIzq,TblImagenEspDer,TblImagenInt',
            'IdRegTab'    => 'required|integer',
            'file'        => 'required|file|mimes:jpg,jpeg,png|max:25048', // 24.5MB
            'comentarios' => 'nullable|string',
        ]);

        $folio  = (int) $request->input('IdRegTab');
        $tabla  = $request->input('tabla_lado');
        $file   = $request->file('file');
        $coment = $request->input('comentarios');
        $now    = Carbon::now();

        $rfc = $this->empresaRFC();
        if (!$rfc) return response()->json(['ok' => false, 'error' => 'RFC no configurado'], 422);

        $nuevoId = (int) DB::connection($this->connImg)->table('tbladjuntos')->max('Id') + 1;

        $relativeDir  = 'upload/controlmant/' . $rfc;
        $finalName    = $nuevoId . '.png';
        $fullFileName = $rfc . '/' . $finalName;

        Storage::disk('public')->makeDirectory($relativeDir);
        Storage::disk('public')->putFileAs($relativeDir, $file, $finalName);

        $original = $file->getClientOriginalName();
        $original = mb_convert_encoding($original, 'UTF-8', 'auto');
        $originalSafe = Str::ascii($original);
        $originalSafe = preg_replace('/[^\w\.\-\s]/u', '', $originalSafe);
        $originalSafe = trim(mb_substr($originalSafe, 0, 255));
        if ($originalSafe === '') $originalSafe = 'imagen_'.$nuevoId.'.png';

        DB::connection($this->connImg)->table('tbladjuntos')->insert([
            'Id'               => $nuevoId,
            'Tabla'            => $tabla,
            'IdRegTab'         => $folio,
            'Bucket'           => 'ControlMant',
            'FullFileName'     => $fullFileName,
            'OriginalFileName' => $originalSafe,
            'Comentarios'      => $coment,
            'Estatus'          => 'PENDIENTE',
            'IdDocRel'         => null,
            'created_at'       => $now,
            'updated_at'       => $now,
            'Creado'           => $now->format('Y-m-d H:i:s'),
        ]);

        $this->recalcularTotales($folio);

        $built = $this->publicUrlFromFullFileName($fullFileName);
        return response()->json(['ok'=>true,'id'=>$nuevoId,'url'=>$built['url'],'path'=>$built['path']], 201);
    }

    /** PATCH: sobrescribir PNG de un adjunto existente (editor inline) */
   public function actualizarMarkup($id, Request $request)
{
    $perms = $this->permisosDeUsuarioActual();
    if (!$perms['subirImagenes']) {
        return response()->json(['ok'=>false,'error'=>'Sin permiso para editar imágenes'], 403);
    }

    $row = DB::connection($this->connImg)->table('tbladjuntos')->where('Id', $id)->first();
    if (!$row) return response()->json(['ok'=>false,'error'=>'Adjunto no encontrado'],404);

    // Solo imágenes de los lados
    if (!in_array($row->Tabla, [
        'TblImagenDel','TblImagenTra','TblImagenIzq','TblImagenDer','TblImagenEspIzq','TblImagenEspDer','TblImagenInt'
    ], true)) {
        return response()->json(['ok'=>false,'error'=>'No es una imagen'], 422);
    }

    // === Recibir PNG desde el editor (file o dataURL) ===
    $pngBinary = null;

    if ($request->hasFile('file')) {
        $request->validate(['file' => 'file|mimetypes:image/png|max:25048']);
        $pngBinary = file_get_contents($request->file('file')->getRealPath());
    } elseif ($request->filled('markup_data_url')) {
        $request->validate(['markup_data_url' => 'string']);
        $dataUrl = (string)$request->input('markup_data_url');
        if (!\Illuminate\Support\Str::startsWith($dataUrl, 'data:image/png;base64,')) {
            return response()->json(['ok'=>false,'error'=>'Formato inválido (se espera PNG)'], 422);
        }
        $pngBinary = base64_decode(substr($dataUrl, strpos($dataUrl, ',') + 1), true);
    } else {
        return response()->json(['ok'=>false,'error'=>'Falta archivo PNG o data URL'], 422);
    }

    if (!$pngBinary) return response()->json(['ok'=>false,'error'=>'PNG inválido'], 422);

    // === Rutas actuales ===
    $currentFull = trim((string)$row->FullFileName, '/');   // ej: ABC123/45.jpg
    $rfc         = strtok($currentFull, '/');               // ABC123
    $name        = substr($currentFull, strlen($rfc) + 1);  // 45.jpg
    $dotPos      = strrpos($name, '.');
    $baseId      = $dotPos !== false ? substr($name, 0, $dotPos) : $name; // "45"

    $oldRelPath  = 'upload/controlmant/' . $currentFull;       // storage/app/public/upload/controlmant/ABC123/45.jpg
    $newFull     = $rfc . '/' . $baseId . '.png';              // ABC123/45.png
    $newRelPath  = 'upload/controlmant/' . $newFull;

    // Asegura carpeta
    \Storage::disk('public')->makeDirectory('upload/controlmant/'.$rfc);

    // === Sobrescribe archivo ===
    if (str_ends_with(strtolower($currentFull), '.png')) {
        // Ya era PNG → solo sobrescribe
        \Storage::disk('public')->put($oldRelPath, $pngBinary);
    } else {
        // Era JPG/JPEG → borra el viejo y guarda como PNG
        \Storage::disk('public')->delete($oldRelPath);
        \Storage::disk('public')->put($newRelPath, $pngBinary);

        // Actualiza path en BD al .png
        DB::connection($this->connImg)->table('tbladjuntos')
            ->where('Id', $id)
            ->update(['FullFileName' => $newFull, 'updated_at' => now()]);
    }

    // Si venía comentario nuevo, opcionalmente guárdalo
    if ($request->filled('comentarios')) {
        DB::connection($this->connImg)->table('tbladjuntos')
            ->where('Id', $id)
            ->update(['Comentarios' => $request->input('comentarios'), 'updated_at' => now()]);
    } else {
        DB::connection($this->connImg)->table('tbladjuntos')
            ->where('Id', $id)
            ->update(['updated_at' => now()]);
    }

    // Devuelve URL pública del archivo ya sobreescrito
    $finalFull = str_ends_with(strtolower($currentFull), '.png') ? $currentFull : $newFull;
    $b = $this->publicUrlFromFullFileName($finalFull);

    return response()->json(['ok'=>true, 'url'=>$b['url']]);
}


    /** DELETE: eliminar imagen (solo PENDIENTE) */
    public function eliminarAdjunto($id)
    {
        $perms = $this->permisosDeUsuarioActual();
        if (!$perms['borrarImagenes']) {
            return response()->json(['ok'=>false,'error'=>'Sin permiso para borrar imágenes'], 403);
        }

        $row = DB::connection($this->connImg)->table('tbladjuntos')->where('Id', $id)->first();
        if (!$row) return response()->json(['ok' => false, 'error' => 'Adjunto no encontrado'], 404);

        if (!in_array($row->Tabla, ['TblImagenDel','TblImagenTra','TblImagenIzq','TblImagenDer','TblImagenEspIzq','TblImagenEspDer','TblImagenInt'], true)) {
            return response()->json(['ok'=>false,'error'=>'No es una imagen'], 422);
        }
        if (($row->Estatus ?? '') !== 'PENDIENTE') {
            return response()->json(['ok' => false, 'error' => 'Solo adjuntos PENDIENTE pueden eliminarse'], 422);
        }

        // Si esta imagen tiene evidencia pegada (mismo folio+tabla), elimínala primero
        $ev = DB::connection($this->connImg)->table('tbladjuntos')
            ->where('IdRegTab', $row->IdRegTab)
            ->where('Tabla', $row->Tabla)
            ->where('Estatus', 'EVIDENCIA')
            ->where('IdDocRel', $row->Id)

            ->first();
        if ($ev) $this->eliminarEvidenciaInterno((int)$ev->Id);

        $relative = 'upload/controlmant/' . trim($row->FullFileName, '/');
        Storage::disk('public')->delete($relative);

        DB::connection($this->connImg)->table('tbladjuntos')->where('Id', $id)->delete();

        $this->recalcularTotales((int) $row->IdRegTab);

        return response()->json(['ok' => true]);
    }

    /** PATCH: ATENDIDO|DOCUMENTADO -> CONFIRMADO */
    public function confirmarAdjunto($id)
    {
        $row = DB::connection($this->connImg)->table('tbladjuntos')->where('Id', $id)->first();
        if (!$row) return response()->json(['ok' => false, 'error' => 'Adjunto no encontrado'], 404);

        if (!in_array($row->Estatus, ['ATENDIDO','DOCUMENTADO'], true)) {
            return response()->json(['ok' => false, 'error' => 'Solo ATENDIDO/DOCUMENTADO pueden confirmarse.'], 422);
        }

        DB::connection($this->connImg)->table('tbladjuntos')->where('Id', $id)->update([
            'Estatus'    => 'CONFIRMADO',
            'updated_at' => Carbon::now(),
        ]);

        $this->recalcularTotales((int) $row->IdRegTab);
        return response()->json(['ok' => true]);
    }

    /** PATCH: CONFIRMADO -> DOCUMENTADO (revertir) */
    public function volverAdjuntoADocumentado($id)
    {
        $row = DB::connection($this->connImg)->table('tbladjuntos')->where('Id', $id)->first();
        if (!$row) return response()->json(['ok' => false, 'error' => 'Adjunto no encontrado'], 404);

        if ($row->Estatus !== 'CONFIRMADO') {
            return response()->json(['ok' => false, 'error' => 'Solo CONFIRMADO puede regresar a DOCUMENTADO'], 422);
        }

        DB::connection($this->connImg)->table('tbladjuntos')->where('Id', $id)->update([
            'Estatus'    => 'DOCUMENTADO',
            'updated_at' => now(),
        ]);

        $this->recalcularTotales((int) $row->IdRegTab);
        return response()->json(['ok'=>true]);
    }

    /** PATCH: comentario de adjunto (solo si está PENDIENTE) */
    public function actualizarComentario($id, Request $request)
    {
        $request->validate(['comentarios' => 'nullable|string']);

        $row = DB::connection($this->connImg)->table('tbladjuntos')->where('Id', $id)->first();
        if (!$row) return response()->json(['ok' => false, 'error' => 'Adjunto no encontrado'], 404);

        if (($row->Estatus ?? '') !== 'PENDIENTE') {
            return response()->json(['ok' => false, 'error' => 'El comentario solo se edita si está PENDIENTE'], 422);
        }

        DB::connection($this->connImg)
            ->table('tbladjuntos')
            ->where('Id', $id)
            ->update([
                'Comentarios' => $request->input('comentarios'),
                'updated_at'  => Carbon::now(),
            ]);

        return response()->json(['ok' => true]);
    }

    /* ================== EVIDENCIA ================== */

    /** POST: subir evidencia (ver reglas) */
    public function subirEvidencia($adjuntoId, Request $request)
    {
        $perms = $this->permisosDeUsuarioActual();
        if (!$perms['subirIEvidencia']) {
            return response()->json(['ok'=>false,'error'=>'Sin permiso para subir evidencia'], 403);
        }

        $request->validate([
            'file' => 'required|file|mimes:pdf,jpg,jpeg,png|max:25048',
        ]);

        $img = DB::connection($this->connImg)
            ->table('tbladjuntos')->where('Id', $adjuntoId)->first();

        if (!$img) return response()->json(['ok'=>false,'error'=>'Imagen no encontrada'],404);

        if (!in_array($img->Tabla, [
            'TblImagenDel','TblImagenTra','TblImagenIzq',
            'TblImagenDer','TblImagenEspIzq','TblImagenEspDer','TblImagenInt',
        ], true)) {
            return response()->json(['ok'=>false,'error'=>'El adjunto no es una imagen'],422);
        }

        if ($img->Estatus !== 'ATENDIDO') {
            return response()->json(['ok'=>false,'error'=>'Solo imágenes ATENDIDO pueden documentarse'],422);
        }

        $file = $request->file('file');
        $ext  = strtolower($file->getClientOriginalExtension());
        $rfc  = $this->empresaRFC();
        if (!$rfc) return response()->json(['ok'=>false,'error'=>'RFC no configurado'],422);

        $newId = (int) DB::connection($this->connImg)->table('tbladjuntos')->max('Id') + 1;

        $relativeDir  = 'upload/controlmant/' . $rfc;
        $finalName    = $newId . '.' . $ext;
        $fullFileName = $rfc . '/' . $finalName;

        Storage::disk('public')->makeDirectory($relativeDir);
        Storage::disk('public')->putFileAs($relativeDir, $file, $finalName);

        $original = $file->getClientOriginalName();
        $original = mb_convert_encoding($original, 'UTF-8', 'auto');
        $originalSafe = Str::ascii($original);
        $originalSafe = preg_replace('/[^\w\.\-\s]/u', '', $originalSafe);
        $originalSafe = trim(mb_substr($originalSafe, 0, 255));
        if ($originalSafe === '') $originalSafe = 'evidencia_'.$newId.'.'.$ext;

        $now = now();
        DB::connection($this->connImg)->table('tbladjuntos')->insert([
            'Id'               => $newId,
            'Tabla'            => $img->Tabla,
            'IdRegTab'         => $img->IdRegTab,
            'Bucket'           => 'ControlMant',
            'FullFileName'     => $fullFileName,
            'OriginalFileName' => $originalSafe,
            'Comentarios'      => 'Evidencia',
            'IdDocRel'         => $img->Id,
            'Estatus'          => 'EVIDENCIA',
            'created_at'       => $now,
            'updated_at'       => $now,
            'Creado'           => $now->format('Y-m-d H:i:s'),
        ]);

        // La imagen pasa a DOCUMENTADO
        DB::connection($this->connImg)->table('tbladjuntos')
            ->where('Id', $adjuntoId)
            ->update(['Estatus'=>'DOCUMENTADO','updated_at'=>$now]);

        $b = $this->publicUrlFromFullFileName($fullFileName);
        return response()->json(['ok'=>true, 'evidenciaId'=>$newId, 'url'=>$b['url']]);
    }
    
    /** DELETE: eliminar evidencia (id = id de la IMAGEN), regresa imagen a ATENDIDO */
        public function eliminarEvidencia($adjuntoId)
        {
            $perms = $this->permisosDeUsuarioActual();
            if (!$perms['borrarEvidencia']) {
                return response()->json(['ok'=>false,'error'=>'Sin permiso para borrar evidencia'], 403);
            }

            $img = DB::connection($this->connImg)->table('tbladjuntos')->where('Id', $adjuntoId)->first();
            if (!$img) return response()->json(['ok'=>false,'error'=>'Imagen no encontrada'],404);

            if (!in_array($img->Tabla, [
                'TblImagenDel','TblImagenTra','TblImagenIzq',
                'TblImagenDer','TblImagenEspIzq','TblImagenEspDer','TblImagenInt',
            ], true)) {
                return response()->json(['ok'=>false,'error'=>'El adjunto no es una imagen'],422);
            }

            // Buscar evidencia pegada a ESTA imagen (por IdDocRel)
            $ev = DB::connection($this->connImg)->table('tbladjuntos')
                ->where('IdRegTab', $img->IdRegTab)
                ->where('Tabla', $img->Tabla)
                ->where('Estatus', 'EVIDENCIA')
                ->where('IdDocRel', $img->Id)
                ->orderByDesc('created_at')
                ->first();

            if ($ev) {
                $this->eliminarEvidenciaInterno((int)$ev->Id);
            }

            // Regresar la imagen a ATENDIDO
            DB::connection($this->connImg)->table('tbladjuntos')
                ->where('Id', $adjuntoId)
                ->update(['Estatus'=>'ATENDIDO','updated_at'=>now()]);

            // Recalcular totales del folio
            $this->recalcularTotales((int) $img->IdRegTab);

            return response()->json(['ok'=>true]);
        }

        /** Helper: elimina archivo+registro de evidencia por Id */
        private function eliminarEvidenciaInterno(int $evidId): void
        {
            $ev = DB::connection($this->connImg)->table('tbladjuntos')->where('Id', $evidId)->first();
            if (!$ev) return;

            $relative = 'upload/controlmant/' . trim((string)$ev->FullFileName, '/');
            Storage::disk('public')->delete($relative);

            DB::connection($this->connImg)->table('tbladjuntos')->where('Id', $evidId)->delete();
        }


    /* ================== LISTAS / TOTALES / DATATABLE ================== */

    /** Lista de adjuntos de un folio (para el modal) */
    public function ajaxAdjuntosList($folio)
    {
        try {
            $folio = (int) $folio;

            $doc = DB::connection($this->connImg)->table('tblimagen')->where('Folio', $folio)->first();
            if (!$doc) return response()->json(['ok' => false, 'error' => 'Folio no encontrado'], 404);

            $estatus = request('estatus'); // PENDIENTE/ATENDIDO/DOCUMENTADO/CONFIRMADO/TODOS
            $tablas  = [
                'TblImagenDel','TblImagenTra','TblImagenIzq',
                'TblImagenDer','TblImagenEspIzq','TblImagenEspDer','TblImagenInt',

            ];

            $q = DB::connection($this->connImg)->table('tbladjuntos')
                ->where('IdRegTab', $folio)
                ->whereIn('Tabla', $tablas)
                ->whereNotNull('Estatus')->where('Estatus','<>','')
                ->where('Estatus','<>','EVIDENCIA')
                ->orderByDesc('created_at');

            if ($estatus && $estatus !== 'TODOS') {
                $q->where('Estatus', $estatus);
            }

            $imgs = $q->get();

            $evids = DB::connection($this->connImg)->table('tbladjuntos')
                ->where('IdRegTab', $folio)
                ->whereIn('Tabla', $tablas)
                ->where('Estatus', 'EVIDENCIA')
                ->orderBy('created_at')
                ->get()
                ->groupBy('Tabla');

            $byTabla = $imgs->groupBy('Tabla');
            $list = collect();
            foreach ($byTabla as $tabla => $arr) {
                $evList = ($evids[$tabla] ?? collect())->values();
                $evIdx  = 0;
                foreach ($arr as $r) {
                    $built = $this->publicUrlFromFullFileName($r->FullFileName);
                    $evidencia = null;
                    if ($evIdx < $evList->count()) {
                        $ev = $evList[$evIdx];
                        $b2 = $this->publicUrlFromFullFileName($ev->FullFileName ?? null);
                        $evidencia = [
                            'Id'  => (int)$ev->Id,
                            'Url' => $b2['url'],
                            'Path'=> $b2['path'],
                        ];
                        $evIdx++;
                    }
                    $list->push([
                        'Id'          => (int) $r->Id,
                        'Tabla'       => $r->Tabla,
                        'Estatus'     => $r->Estatus,
                        'FullPath'    => $r->FullFileName,
                        'Bucket'      => 'ControlMant',
                        'Url'         => $built['url'],
                        'Path'        => $built['path'],
                        'Comentarios' => $r->Comentarios,
                        'IdDocRel'    => $r->IdDocRel,
                        'Evidencia'   => $evidencia,
                        'created_at'  => $r->created_at,
                    ]);
                }
            }

            return response()->json(['ok' => true, 'data' => $list->values()]);
        } catch (\Throwable $e) {
            report($e);
            return response()->json(['ok' => false, 'error' => 'Server error: '.$e->getMessage()], 500);
        }
    }

    /** Recalcular totales por lado en tblimagen (excluye EVIDENCIA) */
    private function recalcularTotales(int $folio): void
    {
        $map = [
            'TblImagenDel'    => ['TotalDetDel',    'TotalDetDelAtnd'],
            'TblImagenTra'    => ['TotalDetTra',    'TotalDetTraAtnd'],
            'TblImagenIzq'    => ['TotalDetIzq',    'TotalDetIzqAtnd'],
            'TblImagenDer'    => ['TotalDetDer',    'TotalDetDerAtnd'],
            'TblImagenEspIzq' => ['TotalDetEspIzq', 'TotalDetEspIzqAtnd'],
            'TblImagenEspDer' => ['TotalDetEspDer', 'TotalDetEspDerAtnd'],
            'TblImagenInt' => ['TotalDetInt', 'TotalDetIntAtnd'],

        ];

        $totales = [
            'TotalDetDel'       => 0, 'TotalDetDelAtnd'    => 0,
            'TotalDetTra'       => 0, 'TotalDetTraAtnd'    => 0,
            'TotalDetIzq'       => 0, 'TotalDetIzqAtnd'    => 0,
            'TotalDetDer'       => 0, 'TotalDetDerAtnd'    => 0,
            'TotalDetEspIzq'    => 0, 'TotalDetEspIzqAtnd' => 0,
            'TotalDetEspDer'    => 0, 'TotalDetEspDerAtnd' => 0,
            'TotalDetInt'        => 0,'TotalDetIntAtnd' => 0,

        ];

        foreach ($map as $tabla => [$tKey, $aKey]) {
            $total = (int) DB::connection($this->connImg)->table('tbladjuntos')
                ->where('IdRegTab', $folio)->where('Tabla', $tabla)
                ->whereNotNull('Estatus')->where('Estatus', '<>', '')
                ->where('Estatus','<>','EVIDENCIA')
                ->count();

            $atnd = (int) DB::connection($this->connImg)->table('tbladjuntos')
                ->where('IdRegTab', $folio)->where('Tabla', $tabla)
                ->whereIn('Estatus', ['ATENDIDO','DOCUMENTADO','CONFIRMADO'])->count();

            $totales[$tKey] = $total;
            $totales[$aKey] = $atnd;
        }

        DB::connection($this->connImg)
            ->table('tblimagen')->where('Folio', $folio)->update($totales);
    }

    /** Endpoint para DataTable de folios */
public function dt(Request $r)
{
    $draw   = (int) $r->input('draw', 1);
    $start  = (int) $r->input('start', 0);
    $length = (int) $r->input('length', 10);
    $search = trim((string) data_get($r->input('search'), 'value', ''));
    $estatusFiltro = $r->input('estatus'); // ABIERTO por default desde la vista

    // Índices visibles en la vista (con FECHA):
    // 0=control, 1=Folio, 2=Fecha, 3=Unidad, 4=Comentarios, 5=Estatus, 6=Totales, 7=Acciones
    // Solo ordenables realmente desde BD:
    $orderableMap = [
        1 => 'Folio',
        2 => 'Fecha',
        3 => 'Unidad',
        4 => 'Comentarios',
        5 => 'Estatus',
    ];

    $reqOrderIdx = (int) data_get($r->input('order'), '0.column', 1);
    $reqOrderDir = strtolower((string) data_get($r->input('order'), '0.dir', 'desc')) === 'asc' ? 'asc' : 'desc';

    $orderCol = $orderableMap[$reqOrderIdx] ?? 'Folio';
    $orderDir = $reqOrderDir;

    // Consulta base
    $q = DB::connection($this->connImg)
        ->table('tblimagen')
        ->select([
            'Folio','Fecha','Unidad','Comentarios','Estatus',
            'TotalDetDel','TotalDetDelAtnd',
            'TotalDetTra','TotalDetTraAtnd',
            'TotalDetIzq','TotalDetIzqAtnd',
            'TotalDetDer','TotalDetDerAtnd',
            'TotalDetEspIzq','TotalDetEspIzqAtnd',
            'TotalDetEspDer','TotalDetEspDerAtnd',
            'TotalDetInt','TotalDetIntAtnd',

        ]);

    if ($estatusFiltro && $estatusFiltro !== 'TODOS') {
        $q->where('Estatus', $estatusFiltro);
    }

    if ($search !== '') {
        $q->where(function($w) use ($search){
            $w->where('Unidad', 'like', "%{$search}%")
              ->orWhere('Comentarios', 'like', "%{$search}%")
              ->orWhere('Folio', $search);
            // Si quieres buscar por fecha, agrega algo como:
            // ->orWhereDate('Fecha', $search);
        });
    }

    $recordsFiltered = (clone $q)->count();

    $q->orderBy($orderCol, $orderDir);

    $rows = $q->skip($start)->take($length)->get();

    $recordsTotal = DB::connection($this->connImg)->table('tblimagen')->count();

    $data = $rows->map(function($r){
        // Normaliza la fecha a YYYY-MM-DD (o deja tal cual si ya viene así)
        $fecha = '';
        if (!empty($r->Fecha)) {
            try {
                $fecha = \Illuminate\Support\Carbon::parse($r->Fecha)->toDateString();
            } catch (\Throwable $e) {
                $fecha = (string) $r->Fecha; // fallback
            }
        }

        return [
            'Folio'       => (int)    $r->Folio,
            'Fecha'       => $fecha,                  // <<<<<< ✅ ahora sí viaja al front
            'Unidad'      => (string) $r->Unidad,
            'Comentarios' => (string) ($r->Comentarios ?? ''),
            'Estatus'     => (string) $r->Estatus,
            'totales'     => [
                'Del'    => [(int)$r->TotalDetDel,    (int)$r->TotalDetDelAtnd],
                'Tra'    => [(int)$r->TotalDetTra,    (int)$r->TotalDetTraAtnd],
                'Izq'    => [(int)$r->TotalDetIzq,    (int)$r->TotalDetIzqAtnd],
                'Der'    => [(int)$r->TotalDetDer,    (int)$r->TotalDetDerAtnd],
                'EspIzq' => [(int)$r->TotalDetEspIzq, (int)$r->TotalDetEspIzqAtnd],
                'EspDer' => [(int)$r->TotalDetEspDer, (int)$r->TotalDetEspDerAtnd],
                'Int' => [(int)$r->TotalDetInt, (int)$r->TotalDetIntAtnd],

            ],
        ];
    })->values();

    return response()->json([
        'draw'            => $draw,
        'recordsTotal'    => $recordsTotal,
        'recordsFiltered' => $recordsFiltered,
        'data'            => $data,
    ]);
}


    /** PATCH: comentario del folio (solo ABIERTO) */
    public function actualizarComentarioFolio($folio, Request $request)
    {
        $request->validate(['comentarios' => 'nullable|string']);

        $row = DB::connection($this->connImg)->table('tblimagen')->where('Folio', $folio)->first();
        if (!$row) return response()->json(['ok' => false, 'error' => 'Folio no encontrado'], 404);

        if ($row->Estatus !== 'ABIERTO') {
            return response()->json(['ok' => false, 'error' => 'Solo folios ABIERTO pueden editar comentario'], 422);
        }

        DB::connection($this->connImg)->table('tblimagen')
            ->where('Folio', $folio)
            ->update([
                'Comentarios' => $request->input('comentarios'),
             ]);

        return response()->json(['ok' => true]);
    }
}
