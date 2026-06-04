@extends('adminlte::page')

@section('title', 'Expedientes digitales')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap:.5rem;">
        <h1 class="m-0">
            <i class="fas fa-folder-open text-primary mr-2"></i>
            Expedientes digitales
        </h1>
        <span class="badge badge-secondary badge-pill px-3 py-2" style="font-size:.85rem;">
            {{ $empleados->total() }} operadores activos
        </span>
    </div>
@stop

@section('content')

    {{-- Alertas --}}
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
    @if (session('drive_resultado'))
        <div class="alert alert-info alert-dismissible fade show">
            <i class="fas fa-cloud mr-1"></i>
            <strong>Resultado Drive:</strong>
            <pre class="mb-0 mt-1" style="font-size:.78rem;white-space:pre-wrap;">{{ session('drive_resultado') }}</pre>
            <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
        </div>
    @endif

    {{-- Buscador --}}
    <div class="card card-outline card-primary mb-3">
        <div class="card-body py-2">
            <form method="GET" action="{{ route('expedientes.index') }}" class="form-inline" id="formBusqueda">
                <div class="input-group w-100">
                    <input type="text"
                           name="q"
                           id="inputBusqueda"
                           value="{{ $busqueda }}"
                           placeholder="Buscar por nombre o CURP..."
                           class="form-control"
                           autocomplete="off">
                    <div class="input-group-append">
                        <button class="btn btn-primary" type="submit">
                            <i class="fas fa-search"></i>
                            <span class="d-none d-sm-inline ml-1">Buscar</span>
                        </button>
                        @if($busqueda)
                            <a href="{{ route('expedientes.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i>
                                <span class="d-none d-sm-inline ml-1">Limpiar</span>
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Info-boxes resumen (stats sobre todos los operadores activos) --}}
    @php
        $totalCompletos   = $statsGlobales['completos'];
        $totalIncompletos = $statsGlobales['incompletos'];
    @endphp
    <div class="row mb-3">
        <div class="col-6 col-sm-4 col-md mb-2">
            <div class="info-box mb-0">
                <span class="info-box-icon bg-success"><i class="fas fa-check"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Completos</span>
                    <span class="info-box-number">{{ $totalCompletos }}</span>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-md mb-2">
            <div class="info-box mb-0">
                <span class="info-box-icon bg-warning"><i class="fas fa-exclamation-triangle"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Incompletos</span>
                    <span class="info-box-number">{{ $totalIncompletos }}</span>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-md mb-2">
            <div class="info-box mb-0">
                <span class="info-box-icon bg-primary"><i class="fas fa-file-alt"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Docs requeridos</span>
                    <span class="info-box-number">{{ $totalDocs }}</span>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-md mb-2">
            <div class="info-box mb-0">
                <span class="info-box-icon bg-info"><i class="fas fa-users"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Total operadores</span>
                    <span class="info-box-number">{{ $empleados->total() }}</span>
                </div>
            </div>
        </div>
        <div class="col-6 col-sm-4 col-md mb-2">
            <div class="info-box mb-0">
                <span class="info-box-icon bg-teal"><i class="fab fa-google-drive"></i></span>
                <div class="info-box-content">
                    <span class="info-box-text">Para Drive</span>
                    <span class="info-box-number">{{ $statsGlobales['marcadosParaDrive'] }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Barra exportación masiva --}}
    <div class="card card-outline card-dark mb-3">
        <div class="card-body py-2 d-flex align-items-center justify-content-between flex-wrap" style="gap:.5rem;">
            <div>
                <strong><i class="fas fa-archive mr-1"></i> Exportación masiva</strong>
                <span class="text-muted ml-2" style="font-size:.82rem;">
                    {{ $totalCompletos }} completo(s) &nbsp;·&nbsp;
                    {{ $statsGlobales['marcadosParaDrive'] }} marcado(s) para Drive
                </span>
            </div>
            <div class="d-flex flex-wrap" style="gap:.4rem;">
                <button type="button"
                        class="btn btn-sm btn-dark {{ $totalCompletos == 0 ? 'disabled' : '' }}"
                        data-toggle="modal" data-target="#modalMasivo"
                        {{ $totalCompletos == 0 ? 'disabled' : '' }}>
                    <i class="fas fa-file-archive mr-1"></i> ZIP masivo
                </button>
                <button type="button"
                        class="btn btn-sm btn-success {{ $statsGlobales['marcadosParaDrive'] == 0 ? 'disabled' : '' }}"
                        data-toggle="modal" data-target="#modalDrive"
                        {{ $statsGlobales['marcadosParaDrive'] == 0 ? 'disabled' : '' }}>
                    <i class="fas fa-cloud-upload-alt mr-1"></i> Enviar a Drive
                </button>
            </div>
        </div>
    </div>

    {{-- Tabla principal --}}
    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th width="60">#</th>
                            <th>Operador</th>
                            <th class="d-none d-lg-table-cell" width="90">Unidad</th>
                            <th class="d-none d-lg-table-cell">CURP</th>
                            <th width="200">Completitud</th>
                            <th width="80" class="text-center d-none d-md-table-cell" title="Marcado / enviado al Drive">Drive</th>
                            <th width="90" class="text-center">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($empleados as $emp)
                            @php
                                $info  = $completitud[$emp->IdEmpleado] ?? ['count'=>0,'total'=>$totalDocs,'pct'=>0,'completo'=>false];
                                $color = $info['completo'] ? 'success' : ($info['pct'] >= 50 ? 'warning' : 'danger');
                                $icono = $info['completo'] ? 'fa-check-circle text-success' : 'fa-exclamation-circle text-'.$color;
                            @endphp
                            <tr>
                                <td class="align-middle text-muted" style="font-size:.82rem;">
                                    {{ $emp->IdEmpleado }}
                                </td>
                                <td class="align-middle">
                                    <i class="fas {{ $icono }} mr-1"></i>
                                    <strong>{{ $emp->Nombre }}</strong>
                                </td>
                                <td class="align-middle d-none d-lg-table-cell text-muted" style="font-size:.82rem;">
                                    {{ $emp->Unidad ?? '—' }}
                                </td>
                                <td class="align-middle d-none d-lg-table-cell text-muted" style="font-size:.78rem;">
                                    {{ $emp->CURP ?? '—' }}
                                </td>
                                <td class="align-middle">
                                    <div class="d-flex align-items-center" style="gap:.4rem;">
                                        <div class="progress flex-grow-1" style="height:8px;min-width:60px;">
                                            <div class="progress-bar bg-{{ $color }}"
                                                 style="width:{{ $info['pct'] }}%"
                                                 title="{{ $info['count'] }}/{{ $info['total'] }} documentos">
                                            </div>
                                        </div>
                                        <small class="text-muted" style="white-space:nowrap;font-size:.75rem;">
                                            {{ $info['count'] }}/{{ $info['total'] }}
                                        </small>
                                    </div>
                                </td>
                                <td class="align-middle text-center d-none d-md-table-cell">
                                    {{-- Toggle envio a Drive --}}
                                    <form action="{{ route('expedientes.envio.toggle', $emp->IdEmpleado) }}"
                                          method="POST" class="d-inline form-envio" data-id="{{ $emp->IdEmpleado }}">
                                        @csrf
                                        <input type="hidden" name="envio" value="{{ $emp->envio ? 0 : 1 }}">
                                        <button type="submit"
                                                class="btn btn-sm {{ $emp->envio ? 'btn-teal' : 'btn-outline-secondary' }}"
                                                title="{{ $emp->envio ? 'Marcado para Drive — click para desmarcar' : 'Click para marcar para Drive' }}">
                                            <i class="fab fa-google-drive"></i>
                                            @if($info['enviados'] > 0)
                                                <span class="badge badge-light ml-1"
                                                      style="font-size:.65rem;">
                                                    {{ $info['enviados'] }}/{{ $info['total'] }}
                                                </span>
                                            @endif
                                        </button>
                                    </form>
                                </td>
                                <td class="align-middle text-center">
                                    <a href="{{ route('expedientes.show', $emp->IdEmpleado) }}"
                                       class="btn btn-sm btn-outline-primary"
                                       title="Ver expediente">
                                        <i class="fas fa-folder-open"></i>
                                        <span class="d-none d-xl-inline ml-1">Abrir</span>
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-5">
                                    <i class="fas fa-search fa-2x mb-2 d-block"></i>
                                    No se encontraron operadores{{ $busqueda ? " para \"$busqueda\"" : '' }}.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Paginación — withQueryString() preserva el buscador --}}
        @if ($empleados->hasPages())
            <div class="card-footer d-flex align-items-center justify-content-between flex-wrap" style="gap:.5rem;">
                <small class="text-muted">
                    Mostrando {{ $empleados->firstItem() }}–{{ $empleados->lastItem() }}
                    de {{ $empleados->total() }} operadores
                </small>
                <div>
                    {{-- Botones anterior / siguiente con clase Bootstrap --}}
                    <ul class="pagination pagination-sm mb-0">
                        {{-- Primera página --}}
                        <li class="page-item {{ $empleados->onFirstPage() ? 'disabled' : '' }}">
                            <a class="page-link" href="{{ $empleados->url(1) }}" title="Primera">
                                <i class="fas fa-angle-double-left"></i>
                            </a>
                        </li>
                        {{-- Anterior --}}
                        <li class="page-item {{ $empleados->onFirstPage() ? 'disabled' : '' }}">
                            <a class="page-link" href="{{ $empleados->previousPageUrl() ?? '#' }}" title="Anterior">
                                <i class="fas fa-angle-left"></i>
                            </a>
                        </li>

                        {{-- Páginas numéricas (ventana de 5) --}}
                        @php
                            $current    = $empleados->currentPage();
                            $last       = $empleados->lastPage();
                            $start      = max(1, $current - 2);
                            $end        = min($last, $current + 2);
                        @endphp

                        @if($start > 1)
                            <li class="page-item disabled"><span class="page-link">…</span></li>
                        @endif

                        @for($p = $start; $p <= $end; $p++)
                            <li class="page-item {{ $p == $current ? 'active' : '' }}">
                                <a class="page-link" href="{{ $empleados->url($p) }}">{{ $p }}</a>
                            </li>
                        @endfor

                        @if($end < $last)
                            <li class="page-item disabled"><span class="page-link">…</span></li>
                        @endif

                        {{-- Siguiente --}}
                        <li class="page-item {{ !$empleados->hasMorePages() ? 'disabled' : '' }}">
                            <a class="page-link" href="{{ $empleados->nextPageUrl() ?? '#' }}" title="Siguiente">
                                <i class="fas fa-angle-right"></i>
                            </a>
                        </li>
                        {{-- Última --}}
                        <li class="page-item {{ !$empleados->hasMorePages() ? 'disabled' : '' }}">
                            <a class="page-link" href="{{ $empleados->url($last) }}" title="Última">
                                <i class="fas fa-angle-double-right"></i>
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        @endif
    </div>

    {{-- ============================================================
         MODAL ZIP MASIVO
    ============================================================ --}}
    <div class="modal fade" id="modalMasivo" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form action="{{ route('expedientes.masivo.zip') }}" method="GET">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-file-archive mr-1"></i> ZIP masivo — expedientes completos
                        </h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert alert-info py-2 mb-3" style="font-size:.82rem;">
                            <i class="fas fa-info-circle mr-1"></i>
                            Se incluirán <strong>{{ $totalCompletos }}</strong> operador(es) con expediente completo.
                            El ZIP puede tardar varios segundos según el número de archivos.
                        </div>
                        <p class="text-muted mb-1" style="font-size:.85rem;font-weight:600;">
                            Documentos a incluir en cada carpeta de operador:
                        </p>
                        <div class="mb-2">
                            <a href="#" class="sel-masivo text-primary mr-3" style="font-size:.8rem;">Seleccionar todos</a>
                            <a href="#" class="desel-masivo text-secondary" style="font-size:.8rem;">Deseleccionar todos</a>
                        </div>
                        <div class="row">
                            @foreach(config('expediente_docs.documentos') as $clave => $doc)
                                <div class="col-12 col-sm-6 mb-1">
                                    <div class="custom-control custom-checkbox">
                                        <input type="checkbox"
                                               class="custom-control-input chk-masivo"
                                               name="tipos[]"
                                               value="{{ $clave }}"
                                               id="mchk_{{ $clave }}"
                                               {{ ($doc['zip'] ?? true) ? 'checked' : '' }}>
                                        <label class="custom-control-label" for="mchk_{{ $clave }}"
                                               style="font-size:.82rem;">
                                            <i class="{{ $doc['icono'] }} mr-1"></i> {{ $doc['nombre'] }}
                                        </label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-dark" id="btnGenerarZip">
                            <i class="fas fa-download mr-1"></i> Generar y descargar ZIP
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ============================================================
         MODAL DRIVE
    ============================================================ --}}
    <div class="modal fade" id="modalDrive" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form action="{{ route('expedientes.drive.sync') }}" method="POST" id="formDrive">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" style="font-size:1rem;">
                            <i class="fas fa-cloud-upload-alt mr-1"></i> Enviar expedientes a Google Drive
                        </h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">

                        <div class="alert alert-info py-2 mb-3" style="font-size:.82rem;">
                            <i class="fas fa-info-circle mr-1"></i>
                            Se enviarán <strong>todos los documentos almacenados</strong> de los
                            <strong>{{ $statsGlobales['marcadosParaDrive'] }}</strong> operador(es)
                            marcados con <i class="fab fa-google-drive"></i>.
                            Los que ya se enviaron antes <strong>no se sobrescriben</strong>.
                        </div>

                        {{-- Carpeta destino --}}
                        <div class="form-group mb-3">
                            <label class="font-weight-bold" style="font-size:.85rem;">
                                Subcarpeta en Google Drive
                            </label>
                            <input type="text"
                                   name="carpeta_drive"
                                   class="form-control"
                                   value="Expedientes_{{ now()->year }}"
                                   required
                                   placeholder="Ej: Expedientes_2026"
                                   autocomplete="off">
                            <small class="text-muted">
                                Se creará dentro de la carpeta Drive configurada.
                                Estructura: <code>Subcarpeta/CURP_SIGLAS/CURP_DOC.pdf</code>
                            </small>
                        </div>

                        {{-- Siglas empresa --}}
                        <div class="form-group mb-2">
                            <label class="font-weight-bold" style="font-size:.85rem;">
                                Siglas de la empresa
                            </label>
                            <input type="text"
                                   name="siglas"
                                   class="form-control"
                                   maxlength="10"
                                   placeholder="Ej: MO"
                                   required
                                   style="max-width:160px;text-transform:uppercase;"
                                   oninput="this.value=this.value.toUpperCase()"
                                   autocomplete="off">
                            <small class="text-muted">
                                Sufijo por operador: <code>CURP_<strong>SIGLAS</strong></code>
                            </small>
                        </div>

                        {{-- Progreso (visible solo al enviar) --}}
                        <div id="driveProgreso" class="d-none mt-3">
                            <div class="d-flex align-items-center p-2 bg-light rounded">
                                <div class="spinner-border spinner-border-sm text-success mr-2" role="status"></div>
                                <span style="font-size:.85rem;">
                                    Enviando… puede tardar 1–3 minutos. No cierres esta ventana.
                                </span>
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary btn-block-xs" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success btn-block-xs" id="btnEnviarDrive">
                            <i class="fas fa-cloud-upload-alt mr-1"></i> Enviar a Drive
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@stop

@section('css')
<style>
    .info-box           { min-height: 60px; }
    .info-box-icon      { font-size: 1.2rem; width: 50px; line-height: 50px; flex-shrink:0; }
    .info-box-content   { padding: 8px 10px; min-width: 0; }
    .info-box-text      { font-size: .75rem; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .info-box-number    { font-size: 1.3rem; }
    .page-link          { min-width: 34px; text-align: center; }
    .table td, .table th { vertical-align: middle !important; }
    @media (max-width: 575px) {
        .modal-footer { flex-direction: column-reverse; gap: .4rem; }
        .modal-footer .btn { width: 100%; margin: 0 !important; }
        .info-box-number { font-size: 1.1rem; }
    }
</style>
@stop

@section('js')
<script>
(function () {

    /* ── Seleccionar / deseleccionar en modal ZIP masivo ── */
    document.querySelector('.sel-masivo')?.addEventListener('click', e => {
        e.preventDefault();
        document.querySelectorAll('.chk-masivo').forEach(c => c.checked = true);
    });
    document.querySelector('.desel-masivo')?.addEventListener('click', e => {
        e.preventDefault();
        document.querySelectorAll('.chk-masivo').forEach(c => c.checked = false);
    });

    /* ── Feedback visual al enviar ZIP masivo ── */
    document.getElementById('btnGenerarZip')?.addEventListener('click', function () {
        const btn = this;
        // Pequeño delay para que el form se envíe primero
        setTimeout(() => {
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Generando ZIP…';
            btn.disabled  = true;
        }, 100);
    });

    /* ── Feedback visual al enviar a Drive ── */
    document.getElementById('formDrive')?.addEventListener('submit', function () {
        document.getElementById('btnEnviarDrive').disabled = true;
        document.getElementById('btnEnviarDrive').innerHTML =
            '<i class="fas fa-spinner fa-spin mr-1"></i> Enviando…';
        document.getElementById('driveProgreso').classList.remove('d-none');
    });

})();
</script>
@stop