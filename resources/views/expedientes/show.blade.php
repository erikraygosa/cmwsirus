@extends('adminlte::page')

@section('title', 'Expediente — ' . $empleado->Nombre)

@section('content_header')
    <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap:.5rem;">
        <div>
            <a href="{{ route('expedientes.index') }}" class="btn btn-sm btn-outline-secondary mr-2">
                <i class="fas fa-arrow-left"></i>
            </a>
            <span style="font-size:1.1rem;font-weight:600;">
                <i class="fas fa-folder-open text-primary mr-1"></i>
                {{ $empleado->Nombre }}
            </span>
            <span class="badge badge-secondary ml-2">ID {{ $empleado->IdEmpleado }}</span>
            @if($completo)
                <span class="badge badge-success ml-1"><i class="fas fa-check mr-1"></i>Expediente completo</span>
            @else
                <span class="badge badge-warning ml-1">{{ $subidos }}/{{ $totalDocs }} obligatorios</span>
            @endif
            <span class="badge badge-secondary ml-1">{{ $subidosTotal }}/{{ count($catalogo) }} subidos</span>
            @if($empleado->envio)
                <span class="badge badge-teal ml-1">
                    <i class="fab fa-google-drive mr-1"></i>Marcado para Drive
                </span>
            @endif
        </div>
        <button type="button" class="btn btn-sm btn-outline-dark" data-toggle="modal" data-target="#modalZip">
            <i class="fas fa-file-archive mr-1"></i> Descargar ZIP
        </button>
    </div>
@stop

@section('content')

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle mr-1"></i> {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger alert-dismissible fade show">
            <strong>Error al subir:</strong>
            <ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    {{-- Barra de progreso --}}
    <div class="card card-outline card-{{ $completo ? 'success' : 'warning' }} mb-3">
        <div class="card-body py-2">
            <div class="d-flex align-items-center">
                <span class="mr-3 text-muted" style="white-space:nowrap;font-size:.85rem;">Obligatorios {{ $pct }}%</span>
                <div class="progress flex-grow-1" style="height:12px;">
                    <div class="progress-bar bg-{{ $completo ? 'success' : ($pct >= 50 ? 'warning' : 'danger') }}"
                         style="width:{{ $pct }}%; transition:width .6s ease;"></div>
                </div>
                <span class="ml-3 font-weight-bold" style="white-space:nowrap;">
                    {{ $subidos }}/{{ $totalDocs }}
                    <span class="text-muted font-weight-normal" style="font-size:.78rem;"> oblig.</span>
                    &nbsp;·&nbsp;
                    {{ $subidosTotal }}/{{ count($catalogo) }}
                    <span class="text-muted font-weight-normal" style="font-size:.78rem;"> total</span>
                </span>
            </div>
        </div>
    </div>

    {{-- Grid de documentos --}}
    <div class="row">
        @foreach ($catalogo as $clave => $doc)
            @php
                $adjunto   = $porTipo[$clave] ?? null;
                $subido    = !is_null($adjunto);
                $esImagen  = $adjunto && $adjunto->esImagen();
                $urlArch   = $adjunto ? $adjunto->urlPublica() : null;
                $esReverso = !empty($doc['reverso_de']);
                $adjId     = $adjunto?->Id ?? 0;

                // Vigencia desde CatEmpleado (si el tipo de doc tiene campo_vigencia)
                $campoVig   = $doc['campo_vigencia'] ?? null;
                $fechaVig   = $campoVig && $empleado->{$campoVig} ? \Carbon\Carbon::parse($empleado->{$campoVig}) : null;
                $diasParaVencer = $fechaVig ? now()->startOfDay()->diffInDays($fechaVig->startOfDay(), false) : null;
                $vigEstado  = null;
                if ($diasParaVencer !== null) {
                    if ($diasParaVencer < 0)        $vigEstado = 'vencida';
                    elseif ($diasParaVencer <= 15)  $vigEstado = 'critica';
                    elseif ($diasParaVencer <= 30)  $vigEstado = 'proxima';
                    else                            $vigEstado = 'vigente';
                }
                // Color del header: si hay vigencia vencida/crítica, forzar advertencia aunque esté subido
                $headerColor = match(true) {
                    $vigEstado === 'vencida'  => 'bg-danger',
                    $vigEstado === 'critica'  => 'bg-danger',
                    $vigEstado === 'proxima'  => 'bg-warning',
                    $subido                   => 'bg-success',
                    default                   => 'bg-warning',
                };
                $headerTextColor = ($headerColor === 'bg-warning') ? 'text-dark' : 'text-white';
            @endphp
            <div class="col-12 col-sm-6 col-lg-4 col-xl-3 mb-3">
                <div class="card h-100 border-{{ $subido ? ($vigEstado === 'vencida' || $vigEstado === 'critica' ? 'danger' : ($vigEstado === 'proxima' ? 'warning' : 'success')) : 'warning' }}"
                     style="border-width:2px;">

                    <div class="card-header py-2 {{ $headerColor }}">
                        <div class="d-flex align-items-center">
                            <i class="{{ $doc['icono'] }} mr-2 {{ $headerTextColor }}"></i>
                            <strong class="{{ $headerTextColor }}" style="font-size:.82rem;line-height:1.2;">
                                {{ $doc['nombre'] }}
                            </strong>
                            <span class="ml-auto">
                                @if($vigEstado === 'vencida' || $vigEstado === 'critica')
                                    <i class="fas fa-exclamation-triangle text-white"></i>
                                @elseif($subido)
                                    <i class="fas fa-check-circle text-white"></i>
                                @else
                                    <i class="fas fa-exclamation-circle text-dark"></i>
                                @endif
                            </span>
                        </div>
                    </div>

                    <div class="card-body p-2 d-flex flex-column">

                        @if($subido)
                            <div class="text-center mb-2" style="min-height:90px;display:flex;align-items:center;justify-content:center;">
                                @if($esImagen)
                                    <img src="{{ $urlArch }}" alt="{{ $doc['nombre'] }}"
                                         class="img-fluid rounded shadow-sm"
                                         style="max-height:90px;object-fit:cover;cursor:pointer;"
                                         data-toggle="modal" data-target="#modalPreview"
                                         data-src="{{ $urlArch }}"
                                         data-titulo="{{ $doc['nombre'] }}"
                                         data-nombre="{{ $adjunto->OriginalFileName }}">
                                @else
                                    <a href="{{ $urlArch }}" target="_blank" class="text-danger">
                                        <i class="fas fa-file-pdf fa-3x"></i>
                                        <div class="text-muted mt-1" style="font-size:.75rem;">{{ $adjunto->OriginalFileName }}</div>
                                    </a>
                                @endif
                            </div>
                            <div class="text-muted text-center mb-1" style="font-size:.72rem;">
                                {{ number_format($adjunto->Peso / 1024, 0) }} KB
                                &bull; {{ \Carbon\Carbon::parse($adjunto->Creado)->format('d/m/Y') }}
                            </div>
                            @if($adjunto->fueEnviado())
                                <div class="text-center mb-2">
                                    <span class="badge badge-success px-2 py-1" style="font-size:.68rem;"
                                          title="Enviado el {{ \Carbon\Carbon::parse($adjunto->EnvioDrive)->format('d/m/Y H:i') }}">
                                        <i class="fab fa-google-drive mr-1"></i>
                                        Drive {{ \Carbon\Carbon::parse($adjunto->EnvioDrive)->format('d/m/Y') }}
                                    </span>
                                </div>
                            @else
                                <div class="text-center mb-2">
                                    <span class="badge badge-light text-muted px-2 py-1" style="font-size:.68rem;">
                                        <i class="fab fa-google-drive mr-1"></i> Pendiente Drive
                                    </span>
                                </div>
                            @endif
                        @else
                            <div class="text-center text-muted py-3"
                                 style="min-height:90px;display:flex;flex-direction:column;align-items:center;justify-content:center;">
                                <i class="{{ $doc['icono'] }} fa-2x mb-1"></i>
                                <small>{{ $doc['descripcion'] }}</small>
                            </div>
                        @endif

                        {{-- Vigencia + editor de fecha (si el tipo tiene campo_vigencia) --}}
                        @if($campoVig)
                            <div class="mb-2">
                                {{-- Badge de estado actual --}}
                                @if($fechaVig)
                                    <div class="text-center mb-1">
                                        @if($vigEstado === 'vencida')
                                            <span class="badge badge-danger px-2 py-1" style="font-size:.72rem;">
                                                <i class="fas fa-times-circle mr-1"></i>
                                                Vencida el {{ $fechaVig->format('d/m/Y') }}
                                            </span>
                                        @elseif($vigEstado === 'critica')
                                            <span class="badge badge-danger px-2 py-1" style="font-size:.72rem;">
                                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                                Vence en {{ $diasParaVencer }} día(s) — {{ $fechaVig->format('d/m/Y') }}
                                            </span>
                                        @elseif($vigEstado === 'proxima')
                                            <span class="badge badge-warning text-dark px-2 py-1" style="font-size:.72rem;">
                                                <i class="fas fa-clock mr-1"></i>
                                                Vence en {{ $diasParaVencer }} días — {{ $fechaVig->format('d/m/Y') }}
                                            </span>
                                        @else
                                            <span class="badge badge-success px-2 py-1" style="font-size:.72rem;">
                                                <i class="fas fa-calendar-check mr-1"></i>
                                                Vigente hasta {{ $fechaVig->format('d/m/Y') }}
                                            </span>
                                        @endif
                                    </div>
                                @else
                                    <div class="text-center mb-1">
                                        <span class="badge badge-secondary px-2 py-1" style="font-size:.72rem;">
                                            <i class="fas fa-calendar-times mr-1"></i> Sin fecha de vencimiento
                                        </span>
                                    </div>
                                @endif

                                {{-- Mini-form para editar fecha --}}
                                <form action="{{ route('expedientes.vigencia.update', $empleado->IdEmpleado) }}"
                                      method="POST" class="d-flex align-items-center mt-1" style="gap:.3rem;">
                                    @csrf
                                    <input type="hidden" name="campo" value="{{ $campoVig }}">
                                    <input type="date"
                                           name="fecha"
                                           class="form-control form-control-sm flex-grow-1"
                                           value="{{ $fechaVig ? $fechaVig->format('Y-m-d') : '' }}"
                                           style="font-size:.75rem;">
                                    <button type="submit"
                                            class="btn btn-sm btn-outline-primary"
                                            title="Guardar fecha de vencimiento">
                                        <i class="fas fa-save"></i>
                                    </button>
                                    @if($fechaVig)
                                        <button type="submit"
                                                name="fecha"
                                                value=""
                                                class="btn btn-sm btn-outline-danger"
                                                title="Quitar fecha"
                                                onclick="return confirm('¿Quitar la fecha de vencimiento?')">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    @endif
                                </form>
                            </div>
                        @endif

                        <div class="mt-auto">
                            @if($subido)
                                <div class="btn-group w-100" role="group">
                                    <a href="{{ $urlArch }}" target="_blank"
                                       class="btn btn-sm btn-outline-secondary" title="Ver">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <button type="button"
                                            class="btn btn-sm btn-outline-primary btn-subir-doc"
                                            data-tipo="{{ $clave }}"
                                            data-nombre="{{ $doc['nombre'] }}"
                                            data-accept="{{ $doc['accept'] }}"
                                            title="Reemplazar">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                    <form action="{{ route('expedientes.destroy', ['idAdj' => $adjId]) }}"
                                          method="POST"
                                          style="flex:1;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit"
                                                class="btn btn-sm btn-outline-danger w-100"
                                                title="Eliminar"
                                                onclick="return confirm('¿Eliminar este documento del expediente?')">
                                            <i class="fas fa-trash-alt"></i>
                                        </button>
                                    </form>
                                </div>
                            @else
                                <div class="d-flex" style="gap:.25rem;">
                                    <button type="button"
                                            class="btn btn-sm btn-warning flex-grow-1 btn-subir-doc"
                                            data-tipo="{{ $clave }}"
                                            data-nombre="{{ $doc['nombre'] }}"
                                            data-accept="{{ $doc['accept'] }}">
                                        <i class="fas fa-upload mr-1"></i> Subir
                                    </button>
                                    @if(str_contains($doc['accept'], 'image'))
                                        <button type="button"
                                                class="btn btn-sm btn-outline-secondary btn-camara-doc"
                                                data-tipo="{{ $clave }}"
                                                data-nombre="{{ $doc['nombre'] }}"
                                                title="Usar cámara">
                                            <i class="fas fa-camera"></i>
                                        </button>
                                    @endif
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>

    {{-- MODAL SUBIR ARCHIVO --}}
    <div class="modal fade" id="modalSubir" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form id="formSubir"
                      action="{{ route('expedientes.store', $empleado->IdEmpleado) }}"
                      method="POST"
                      enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="tipo" id="inputTipo">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-upload mr-1"></i> Subir documento</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted mb-3">Documento: <strong id="labelTipoNombre"></strong></p>
                        <div id="previewContenedor" class="text-center mb-3 d-none">
                            <img id="previewImagen" src="" class="img-fluid rounded"
                                 style="max-height:200px;object-fit:contain;">
                            <div id="previewNombre" class="text-muted mt-1" style="font-size:.8rem;"></div>
                        </div>
                        <div class="custom-file">
                            <input type="file" class="custom-file-input" id="inputArchivo" name="archivo" required>
                            <label class="custom-file-label" for="inputArchivo">Seleccionar archivo…</label>
                        </div>
                        <small class="text-muted">Máximo {{ config('expediente_docs.max_mb') }} MB. Imagen o PDF.</small>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary" id="btnGuardar" disabled>
                            <i class="fas fa-save mr-1"></i> Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL CÁMARA --}}
    <div class="modal fade" id="modalCamara" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-camera mr-1"></i> Escanear con cámara</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-3">Documento: <strong id="labelTipoNombreCam"></strong></p>
                    <div class="row mb-3">
                        <div class="col-6">
                            <label class="text-muted" style="font-size:.78rem;font-weight:600;text-transform:uppercase;">Modo de color</label>
                            <div class="btn-group btn-group-sm w-100" id="btnGrupoColor">
                                <button type="button" class="btn btn-outline-secondary active" data-modo="color">
                                    <i class="fas fa-palette mr-1"></i> Color
                                </button>
                                <button type="button" class="btn btn-outline-secondary" data-modo="bn">
                                    <i class="fas fa-adjust mr-1"></i> B / N
                                </button>
                            </div>
                        </div>
                        <div class="col-6">
                            <label class="text-muted" style="font-size:.78rem;font-weight:600;text-transform:uppercase;">Formato de salida</label>
                            <div class="btn-group btn-group-sm w-100" id="btnGrupoFormato">
                                <button type="button" class="btn btn-outline-secondary active" data-fmt="pdf">
                                    <i class="fas fa-file-pdf mr-1"></i> PDF
                                </button>
                                <button type="button" class="btn btn-outline-secondary" data-fmt="imagen">
                                    <i class="fas fa-image mr-1"></i> Imagen
                                </button>
                            </div>
                        </div>
                    </div>
                    <div id="camPreviewBox" class="d-none mb-3 text-center">
                        <img id="camPreviewImg" src="" class="img-fluid rounded border"
                             style="max-height:260px;object-fit:contain;">
                        <div class="text-muted mt-1" style="font-size:.78rem;">
                            Vista previa — verifica que el texto sea legible antes de guardar
                        </div>
                    </div>
                    <input type="file" id="inputCamara" accept="image/*" capture="environment" class="d-none">
                    <div class="text-center">
                        <button type="button" id="btnAbrirCamara" class="btn btn-outline-primary">
                            <i class="fas fa-camera mr-1"></i> Tomar foto (cámara trasera)
                        </button>
                        <div class="text-muted mt-1" style="font-size:.75rem;">
                            Asegúrate de tener buena iluminación y encuadra bien el documento completo.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <small id="labelFormatoSalida" class="mr-auto text-info" style="font-size:.8rem;"></small>
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-success" id="btnGuardarCam" disabled>
                        <i class="fas fa-save mr-1"></i> Guardar
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- MODAL ZIP --}}
    <div class="modal fade" id="modalZip" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form action="{{ route('expedientes.zip', $empleado->IdEmpleado) }}" method="GET">
                    <div class="modal-header">
                        <h5 class="modal-title"><i class="fas fa-file-archive mr-1"></i> Descargar ZIP del expediente</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted mb-2" style="font-size:.85rem;">Selecciona qué documentos incluir:</p>
                        <div class="mb-2">
                            <a href="#" id="selTodos" class="text-primary mr-3" style="font-size:.8rem;">Seleccionar todos</a>
                            <a href="#" id="deselTodos" class="text-secondary" style="font-size:.8rem;">Deseleccionar todos</a>
                        </div>
                        <div class="row">
                            @foreach ($catalogo as $clave => $doc)
                                @php $tieneDoc = isset($porTipo[$clave]); @endphp
                                <div class="col-12 col-sm-6 mb-1">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox"
                                               class="custom-control-input chk-zip"
                                               name="tipos[]"
                                               value="{{ $clave }}"
                                               id="chk_{{ $clave }}"
                                               {{ ($doc['zip'] ?? true) && $tieneDoc ? 'checked' : '' }}
                                               {{ !$tieneDoc ? 'disabled' : '' }}>
                                        <label class="custom-control-label {{ !$tieneDoc ? 'text-muted' : '' }}"
                                               for="chk_{{ $clave }}" style="font-size:.82rem;">
                                            <i class="{{ $doc['icono'] }} mr-1"></i>
                                            {{ $doc['nombre'] }}
                                            @if(!$tieneDoc)
                                                <span class="badge badge-light" style="font-size:.65rem;">sin archivo</span>
                                            @endif
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-dark">
                            <i class="fas fa-download mr-1"></i> Descargar ZIP
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- MODAL PREVIEW --}}
    <div class="modal fade" id="modalPreview" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header py-2">
                    <h6 class="modal-title" id="modalPreviewTitulo"></h6>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body text-center p-2">
                    <img id="modalPreviewImg" src="" class="img-fluid rounded" style="max-height:80vh;">
                </div>
                <div class="modal-footer py-1">
                    <small id="modalPreviewNombre" class="text-muted mr-auto"></small>
                    <a id="modalPreviewLink" href="#" target="_blank" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-external-link-alt mr-1"></i> Abrir
                    </a>
                    <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

@stop

@section('css')
<style>
    .card { transition: box-shadow .2s; }
    .card:hover { box-shadow: 0 2px 12px rgba(0,0,0,.12); }
    #btnGrupoColor .btn.active,
    #btnGrupoFormato .btn.active { background-color:#343a40; color:#fff; border-color:#343a40; }
</style>
@stop

@section('js')
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script>
(function () {
    const { jsPDF } = window.jspdf;

    let _imgOriginal  = null;
    let _imgProcesada = null;
    let _modoColor    = 'color';
    let _fmtoSalida   = 'pdf';
    let _tipoCamara   = '';

    /* ── MODAL SUBIR ARCHIVO ─────────────────────────────────── */
    document.querySelectorAll('.btn-subir-doc').forEach(btn => {
        btn.addEventListener('click', function () {
            document.getElementById('inputTipo').value                               = this.dataset.tipo;
            document.getElementById('labelTipoNombre').textContent                  = this.dataset.nombre;
            document.getElementById('inputArchivo').accept                           = this.dataset.accept;
            document.getElementById('inputArchivo').value                            = '';
            document.getElementById('previewContenedor').classList.add('d-none');
            document.getElementById('btnGuardar').disabled                           = true;
            document.querySelector('#inputArchivo + .custom-file-label').textContent = 'Seleccionar archivo…';
            $('#modalSubir').modal('show');
        });
    });

    document.getElementById('inputArchivo').addEventListener('change', function () {
        const file = this.files[0];
        if (!file) return;
        document.querySelector('.custom-file-label').textContent = file.name;
        document.getElementById('btnGuardar').disabled = false;
        if (file.type.startsWith('image/')) {
            const r = new FileReader();
            r.onload = e => {
                document.getElementById('previewImagen').src = e.target.result;
                document.getElementById('previewNombre').textContent = file.name;
                document.getElementById('previewContenedor').classList.remove('d-none');
            };
            r.readAsDataURL(file);
        } else {
            document.getElementById('previewContenedor').classList.add('d-none');
        }
    });

    /* ── MODAL CÁMARA ────────────────────────────────────────── */
    document.querySelectorAll('.btn-camara-doc').forEach(btn => {
        btn.addEventListener('click', function () {
            _tipoCamara  = this.dataset.tipo;
            _imgOriginal = _imgProcesada = null;
            document.getElementById('labelTipoNombreCam').textContent = this.dataset.nombre;
            document.getElementById('inputCamara').value              = '';
            document.getElementById('camPreviewBox').classList.add('d-none');
            document.getElementById('btnGuardarCam').disabled         = true;
            actualizarLabel();
            $('#modalCamara').modal('show');
        });
    });

    document.querySelectorAll('#btnGrupoColor .btn').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('#btnGrupoColor .btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            _modoColor = this.dataset.modo;
            if (_imgOriginal) reprocesar();
        });
    });

    document.querySelectorAll('#btnGrupoFormato .btn').forEach(btn => {
        btn.addEventListener('click', function () {
            document.querySelectorAll('#btnGrupoFormato .btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            _fmtoSalida = this.dataset.fmt;
            actualizarLabel();
        });
    });

    function actualizarLabel() {
        document.getElementById('labelFormatoSalida').textContent =
            _fmtoSalida === 'pdf' ? 'Se guardará como PDF carta' : 'Se guardará como imagen JPEG';
    }

    document.getElementById('btnAbrirCamara').addEventListener('click', () => {
        document.getElementById('inputCamara').click();
    });

    document.getElementById('inputCamara').addEventListener('change', function () {
        const file = this.files[0];
        if (!file) return;
        const r = new FileReader();
        r.onload = e => { _imgOriginal = e.target.result; reprocesar(); };
        r.readAsDataURL(file);
    });

    function reprocesar() {
        procesarImagen(_imgOriginal, _modoColor, dataUrl => {
            _imgProcesada = dataUrl;
            document.getElementById('camPreviewImg').src = dataUrl;
            document.getElementById('camPreviewBox').classList.remove('d-none');
            document.getElementById('btnGuardarCam').disabled = false;
        });
    }

    function procesarImagen(dataUrl, modo, cb) {
        const img = new Image();
        img.onload = function () {
            const maxW  = 1800;
            const ratio = Math.min(1, maxW / img.width);
            const w = Math.round(img.width  * ratio);
            const h = Math.round(img.height * ratio);
            const canvas = document.createElement('canvas');
            canvas.width = w; canvas.height = h;
            const ctx = canvas.getContext('2d');
            ctx.drawImage(img, 0, 0, w, h);
            if (modo === 'bn') {
                const id = ctx.getImageData(0, 0, w, h);
                const d  = id.data;
                const factor = (259 * (60 + 255)) / (255 * (259 - 60));
                for (let i = 0; i < d.length; i += 4) {
                    let g = 0.299*d[i] + 0.587*d[i+1] + 0.114*d[i+2];
                    g = Math.max(0, Math.min(255, factor*(g-128)+128));
                    d[i] = d[i+1] = d[i+2] = g;
                }
                ctx.putImageData(id, 0, 0);
            }
            cb(canvas.toDataURL('image/jpeg', 0.92));
        };
        img.src = dataUrl;
    }

    function imagenAPdf(dataUrl, cb) {
        const img = new Image();
        img.onload = function () {
            const ori = img.width > img.height ? 'l' : 'p';
            const pdf = new jsPDF({ orientation: ori, unit: 'mm', format: 'letter' });
            const pw = pdf.internal.pageSize.getWidth();
            const ph = pdf.internal.pageSize.getHeight();
            const m = 10, dw = pw-m*2, dh = ph-m*2;
            const ir = img.width/img.height, pr = dw/dh;
            let fw, fh;
            if (ir > pr) { fw = dw; fh = dw/ir; } else { fh = dh; fw = dh*ir; }
            pdf.addImage(dataUrl, 'JPEG', m+(dw-fw)/2, m+(dh-fh)/2, fw, fh);
            cb(pdf.output('blob'));
        };
        img.src = dataUrl;
    }

    document.getElementById('btnGuardarCam').addEventListener('click', function () {
        if (!_imgProcesada) return;
        const btn = this;
        btn.disabled  = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Procesando…';

        const ts    = Date.now();
        const action = '{{ route('expedientes.store', $empleado->IdEmpleado) }}';
        const token  = document.querySelector('meta[name="csrf-token"]').content;

        const enviar = (blob, nombre) => {
            const fd = new FormData();
            fd.append('_token',  token);
            fd.append('tipo',    _tipoCamara);
            fd.append('archivo', blob, nombre);
            fetch(action, { method: 'POST', body: fd })
                .then(r => { if (r.ok || r.redirected) window.location.reload(); else throw new Error(); })
                .catch(() => {
                    btn.disabled  = false;
                    btn.innerHTML = '<i class="fas fa-save mr-1"></i> Guardar';
                    alert('Error al subir. Intenta de nuevo.');
                });
        };

        if (_fmtoSalida === 'pdf') {
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Generando PDF…';
            imagenAPdf(_imgProcesada, blob => enviar(blob, _tipoCamara + '_' + ts + '.pdf'));
        } else {
            fetch(_imgProcesada).then(r => r.blob()).then(blob => enviar(blob, _tipoCamara + '_' + ts + '.jpg'));
        }
    });

    /* Preview imagen */
    $('#modalPreview').on('show.bs.modal', function (e) {
        const btn = $(e.relatedTarget);
        $('#modalPreviewImg').attr('src',  btn.data('src'));
        $('#modalPreviewTitulo').text(      btn.data('titulo'));
        $('#modalPreviewNombre').text(      btn.data('nombre'));
        $('#modalPreviewLink').attr('href', btn.data('src'));
    });

    /* ZIP: seleccionar / deseleccionar todos */
    document.getElementById('selTodos').addEventListener('click', e => {
        e.preventDefault();
        document.querySelectorAll('.chk-zip:not(:disabled)').forEach(c => c.checked = true);
    });
    document.getElementById('deselTodos').addEventListener('click', e => {
        e.preventDefault();
        document.querySelectorAll('.chk-zip:not(:disabled)').forEach(c => c.checked = false);
    });

})();
</script>
@stop