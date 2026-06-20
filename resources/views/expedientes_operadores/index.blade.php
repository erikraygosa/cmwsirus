@extends('adminlte::page')

@section('title', 'Expedientes digitales (Operadores)')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center flex-wrap" style="gap:.5rem;">
        <h1 class="m-0">
            <i class="fas fa-folder-open text-primary mr-2"></i>
            Expedientes digitales <small class="text-muted">— catoperadores</small>
        </h1>
        <span class="badge badge-secondary badge-pill px-3 py-2" style="font-size:.85rem;">
            {{ $operadores->total() }} operadores activos
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
            <form method="GET" action="{{ route('expedientes-operadores.index') }}" class="form-inline" id="formBusqueda">
                <div class="input-group w-100 flex-nowrap">
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
                        @if($busqueda || $soloDrive)
                            <a href="{{ route('expedientes-operadores.index') }}" class="btn btn-secondary">
                                <i class="fas fa-times"></i>
                                <span class="d-none d-sm-inline ml-1">Limpiar</span>
                            </a>
                        @endif
                    </div>
                </div>
                <div class="custom-control custom-checkbox mt-2 mt-sm-0 ml-sm-3">
                    <input type="checkbox" class="custom-control-input" id="chkSoloDrive" name="drive" value="1"
                           {{ $soloDrive ? 'checked' : '' }}
                           onchange="document.getElementById('formBusqueda').submit()">
                    <label class="custom-control-label" for="chkSoloDrive" style="font-size:.85rem;">
                        <i class="fab fa-google-drive mr-1"></i> Solo marcados para Drive
                    </label>
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
                    <span class="info-box-number">{{ $operadores->total() }}</span>
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
                            <th width="50">#</th>
                            <th>Operador</th>
                            <th class="d-none d-lg-table-cell" width="80">Unidad</th>
                            <th class="d-none d-md-table-cell">CURP</th>
                            <th>Completitud</th>
                            <th width="80" class="text-center d-none d-md-table-cell" title="Marcado / enviado al Drive">Drive</th>
                            <th width="110" class="text-center">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($operadores as $emp)
                            @php
                                $info  = $completitud[$emp->IdOper] ?? ['count'=>0,'total'=>$totalDocs,'pct'=>0,'completo'=>false];
                                $color = $info['completo'] ? 'success' : ($info['pct'] >= 50 ? 'warning' : 'danger');
                                $icono = $info['completo'] ? 'fa-check-circle text-success' : 'fa-exclamation-circle text-'.$color;
                            @endphp
                            <tr>
                                <td class="align-middle text-muted" style="font-size:.82rem;">
                                    {{ $emp->IdOper }}
                                </td>
                                <td class="align-middle">
                                    <i class="fas {{ $icono }} mr-1"></i>
                                    <strong>{{ $emp->Operador }}</strong>
                                    <button type="button"
                                            class="btn btn-link btn-editar-nombre p-0 ml-1"
                                            data-id="{{ $emp->IdOper }}"
                                            data-nombre="{{ $emp->Operador }}"
                                            title="Editar nombre"
                                            style="line-height:1;">
                                        <i class="fas fa-pen" style="font-size:.6rem;color:#bbb;"></i>
                                    </button>
                                    @if($info['duplicado_anterior'])
                                        <span class="badge badge-warning ml-1" style="font-size:.62rem;"
                                              title="Ya existe expediente con documentos en el sistema anterior (catempleados) para este mismo nombre/CURP. Evita duplicar el envío.">
                                            <i class="fas fa-exclamation-triangle mr-1"></i>Ya existe en sistema anterior
                                        </span>
                                    @endif
                                </td>
                                <td class="align-middle d-none d-lg-table-cell text-muted" style="font-size:.82rem;">
                                    {{ $emp->Unidad ?? '—' }}
                                </td>
                                <td class="align-middle d-none d-md-table-cell" style="font-size:.78rem;">
                                    @if($emp->CURP)
                                        <span class="text-muted">{{ $emp->CURP }}</span>
                                    @else
                                        <button type="button"
                                                class="btn btn-xs btn-outline-warning btn-editar-curp"
                                                data-id="{{ $emp->IdOper }}"
                                                data-nombre="{{ $emp->Operador }}"
                                                style="font-size:.72rem;padding:1px 6px;"
                                                title="Agregar CURP">
                                            <i class="fas fa-edit mr-1"></i>Sin CURP
                                        </button>
                                    @endif
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
                                    <form action="{{ route('expedientes-operadores.envio.toggle', $emp->IdOper) }}"
                                          method="POST" class="d-inline form-envio" data-id="{{ $emp->IdOper }}">
                                        @csrf
                                        <input type="hidden" name="envio" value="{{ $info['envio'] ? 0 : 1 }}">
                                        <button type="submit"
                                                class="btn btn-sm {{ $info['envio'] ? 'btn-teal' : 'btn-outline-secondary' }}"
                                                title="{{ $info['envio'] ? 'Marcado para Drive — click para desmarcar' : 'Click para marcar para Drive' }}">
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
                                    <div class="btn-group btn-group-sm" role="group">
                                        <a href="{{ route('expedientes-operadores.show', $emp->IdOper) }}"
                                           class="btn btn-outline-primary"
                                           title="Ver expediente">
                                            <i class="fas fa-folder-open"></i>
                                            <span class="d-none d-xl-inline ml-1">Abrir</span>
                                        </a>
                                        <button type="button"
                                                class="btn btn-outline-danger btn-dar-baja"
                                                data-id="{{ $emp->IdOper }}"
                                                data-nombre="{{ $emp->Operador }}"
                                                title="Dar de baja">
                                            <i class="fas fa-user-times"></i>
                                        </button>
                                    </div>
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
        @if ($operadores->hasPages())
            <div class="card-footer d-flex align-items-center justify-content-between flex-wrap" style="gap:.5rem;">
                <small class="text-muted">
                    Mostrando {{ $operadores->firstItem() }}–{{ $operadores->lastItem() }}
                    de {{ $operadores->total() }} operadores
                </small>
                <div>
                    {{-- Botones anterior / siguiente con clase Bootstrap --}}
                    <ul class="pagination pagination-sm mb-0">
                        {{-- Primera página --}}
                        <li class="page-item {{ $operadores->onFirstPage() ? 'disabled' : '' }}">
                            <a class="page-link" href="{{ $operadores->url(1) }}" title="Primera">
                                <i class="fas fa-angle-double-left"></i>
                            </a>
                        </li>
                        {{-- Anterior --}}
                        <li class="page-item {{ $operadores->onFirstPage() ? 'disabled' : '' }}">
                            <a class="page-link" href="{{ $operadores->previousPageUrl() ?? '#' }}" title="Anterior">
                                <i class="fas fa-angle-left"></i>
                            </a>
                        </li>

                        {{-- Páginas numéricas (ventana de 5) --}}
                        @php
                            $current    = $operadores->currentPage();
                            $last       = $operadores->lastPage();
                            $start      = max(1, $current - 2);
                            $end        = min($last, $current + 2);
                        @endphp

                        @if($start > 1)
                            <li class="page-item disabled"><span class="page-link">…</span></li>
                        @endif

                        @for($p = $start; $p <= $end; $p++)
                            <li class="page-item {{ $p == $current ? 'active' : '' }}">
                                <a class="page-link" href="{{ $operadores->url($p) }}">{{ $p }}</a>
                            </li>
                        @endfor

                        @if($end < $last)
                            <li class="page-item disabled"><span class="page-link">…</span></li>
                        @endif

                        {{-- Siguiente --}}
                        <li class="page-item {{ !$operadores->hasMorePages() ? 'disabled' : '' }}">
                            <a class="page-link" href="{{ $operadores->nextPageUrl() ?? '#' }}" title="Siguiente">
                                <i class="fas fa-angle-right"></i>
                            </a>
                        </li>
                        {{-- Última --}}
                        <li class="page-item {{ !$operadores->hasMorePages() ? 'disabled' : '' }}">
                            <a class="page-link" href="{{ $operadores->url($last) }}" title="Última">
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
                <form action="{{ route('expedientes-operadores.masivo.zip') }}" method="GET">
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
         MODAL EDITAR CURP
    ============================================================ --}}
    <div class="modal fade" id="modalEditarCurp" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-sm" role="document">
            <div class="modal-content">
                <form id="formEditarCurp" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="modal-header py-2">
                        <h6 class="modal-title"><i class="fas fa-id-card mr-1"></i> Agregar CURP</h6>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted mb-2" style="font-size:.82rem;" id="labelNombreCurp"></p>
                        <input type="text"
                               name="curp"
                               id="inputCurpEditar"
                               class="form-control text-uppercase"
                               maxlength="18"
                               placeholder="CURP (18 caracteres)"
                               oninput="this.value=this.value.toUpperCase()"
                               required
                               autocomplete="off">
                        <small class="text-muted">Ejemplo: ABCD123456HDFXXX01</small>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-sm btn-warning">
                            <i class="fas fa-save mr-1"></i> Guardar CURP
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ============================================================
         MODAL EDITAR NOMBRE
    ============================================================ --}}
    <div class="modal fade" id="modalEditarNombre" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-sm" role="document">
            <div class="modal-content">
                <form id="formEditarNombre" method="POST">
                    @csrf
                    @method('PATCH')
                    <div class="modal-header py-2">
                        <h6 class="modal-title"><i class="fas fa-user-edit mr-1"></i> Editar nombre</h6>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <p class="text-muted mb-2" style="font-size:.78rem;">Nombre actual:</p>
                        <p class="font-weight-bold mb-2" id="labelNombreActual" style="font-size:.85rem;"></p>
                        <input type="text"
                               name="nombre"
                               id="inputNombreEditar"
                               class="form-control text-uppercase"
                               maxlength="200"
                               placeholder="Nombre completo"
                               oninput="this.value=this.value.toUpperCase()"
                               required
                               autocomplete="off">
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-sm btn-primary">
                            <i class="fas fa-save mr-1"></i> Guardar nombre
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ============================================================
         MODAL CONFIRMAR BAJA
    ============================================================ --}}
    <div class="modal fade" id="modalBaja" tabindex="-1" role="dialog">
        <div class="modal-dialog modal-sm" role="document">
            <div class="modal-content">
                <form id="formBaja" method="POST">
                    @csrf
                    <div class="modal-header py-2 bg-danger">
                        <h6 class="modal-title text-white"><i class="fas fa-user-times mr-1"></i> Dar de baja</h6>
                        <button type="button" class="close text-white" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-1">¿Dar de baja al operador?</p>
                        <p class="font-weight-bold mb-1" id="labelNombreBaja"></p>
                        <small class="text-muted">Su estatus cambiará a "B" y dejará de aparecer en la lista activa.</small>
                    </div>
                    <div class="modal-footer py-2">
                        <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-sm btn-danger">
                            <i class="fas fa-user-times mr-1"></i> Confirmar baja
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ============================================================
         MODAL DRIVE
    ============================================================ --}}
    <div class="modal fade" id="modalDrive" tabindex="-1" role="dialog" data-backdrop="static" data-keyboard="false">
        <div class="modal-dialog modal-dialog-centered" role="document">
            <div class="modal-content">
                <form id="formDrive">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" style="font-size:1rem;">
                            <i class="fas fa-cloud-upload-alt mr-1"></i> Enviar expedientes a Google Drive
                        </h5>
                        <button type="button" class="close" data-dismiss="modal" id="btnCerrarDrive"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">

                        {{-- Formulario inicial --}}
                        <div id="driveFormulario">
                            <div class="alert alert-info py-2 mb-3" style="font-size:.82rem;">
                                <i class="fas fa-info-circle mr-1"></i>
                                Se enviarán <strong>todos los documentos almacenados</strong> de los
                                <strong>{{ $statsGlobales['marcadosParaDrive'] }}</strong> operador(es)
                                marcados con <i class="fab fa-google-drive"></i>.
                                Los que ya se enviaron antes <strong>no se sobrescriben</strong>.
                            </div>

                            <div class="form-group mb-3">
                                <label class="font-weight-bold" style="font-size:.85rem;">Subcarpeta en Google Drive</label>
                                <input type="text" name="carpeta_drive" id="carpetaDrive"
                                       class="form-control"
                                       value="Expedientes_{{ now()->year }}"
                                       required placeholder="Ej: Expedientes_2026" autocomplete="off">
                                <small class="text-muted">
                                    Estructura: <code>Subcarpeta/CURP_SIGLAS/CURP_DOC.pdf</code>
                                </small>
                            </div>

                            <div class="form-group mb-0">
                                <label class="font-weight-bold" style="font-size:.85rem;">Siglas de la empresa</label>
                                <input type="text" name="siglas" id="siglasEmpresa"
                                       class="form-control" maxlength="10"
                                       placeholder="Ej: MO" required
                                       style="max-width:160px;text-transform:uppercase;"
                                       oninput="this.value=this.value.toUpperCase()" autocomplete="off">
                                <small class="text-muted">Sufijo por operador: <code>CURP_<strong>SIGLAS</strong></code></small>
                            </div>
                        </div>

                        {{-- Progreso (visible durante el envío) --}}
                        <div id="driveProgreso" class="d-none">
                            <div class="text-center py-2">
                                <div class="spinner-border text-success mb-2" role="status" style="width:2.5rem;height:2.5rem;"></div>
                                <p class="font-weight-bold mb-1" id="driveProgresoTitulo">Preparando archivos…</p>
                                <p class="text-muted mb-0" style="font-size:.82rem;">
                                    Subiendo a Google Drive en segundo plano.<br>
                                    <strong>No cierres esta ventana.</strong>
                                </p>
                            </div>
                        </div>

                        {{-- Resultado --}}
                        <div id="driveResultado" class="d-none"></div>

                    </div>
                    <div class="modal-footer" id="driveFooter">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-success" id="btnEnviarDrive">
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

    /* ── Modal editar CURP ── */
    document.querySelectorAll('.btn-editar-curp').forEach(btn => {
        btn.addEventListener('click', function () {
            document.getElementById('labelNombreCurp').textContent = this.dataset.nombre;
            document.getElementById('inputCurpEditar').value       = '';
            document.getElementById('formEditarCurp').action       =
                '/expedientes-operadores/' + this.dataset.id + '/curp';
            $('#modalEditarCurp').modal('show');
        });
    });

    /* ── Modal editar nombre ── */
    document.querySelectorAll('.btn-editar-nombre').forEach(btn => {
        btn.addEventListener('click', function () {
            document.getElementById('labelNombreActual').textContent = this.dataset.nombre;
            document.getElementById('inputNombreEditar').value       = this.dataset.nombre;
            document.getElementById('formEditarNombre').action       =
                '/expedientes-operadores/' + this.dataset.id + '/nombre';
            $('#modalEditarNombre').modal('show');
        });
    });

    /* ── Modal dar de baja ── */
    document.querySelectorAll('.btn-dar-baja').forEach(btn => {
        btn.addEventListener('click', function () {
            document.getElementById('labelNombreBaja').textContent = this.dataset.nombre;
            document.getElementById('formBaja').action             =
                '/expedientes-operadores/' + this.dataset.id + '/baja';
            $('#modalBaja').modal('show');
        });
    });

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

    /* ── Drive: AJAX submit + polling de estado ── */
    (function () {
        const form         = document.getElementById('formDrive');
        const btnEnviar    = document.getElementById('btnEnviarDrive');
        const formulario   = document.getElementById('driveFormulario');
        const progreso     = document.getElementById('driveProgreso');
        const progresoTxt  = document.getElementById('driveProgresoTitulo');
        const resultado    = document.getElementById('driveResultado');
        const footer       = document.getElementById('driveFooter');
        const btnCerrar    = document.getElementById('btnCerrarDrive');
        const statusBase   = '{{ route("expedientes-operadores.drive.status", ["syncId" => "__ID__"]) }}';
        let   pollTimer    = null;

        function resetModal() {
            formulario.classList.remove('d-none');
            progreso.classList.add('d-none');
            resultado.classList.add('d-none');
            resultado.innerHTML = '';
            footer.innerHTML = `
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-success" id="btnEnviarDrive">
                    <i class="fas fa-cloud-upload-alt mr-1"></i> Enviar a Drive
                </button>`;
            footer.querySelector('[type=submit]')?.addEventListener('click', () => form.requestSubmit?.() || form.dispatchEvent(new Event('submit')));
        }

        $('#modalDrive').on('hidden.bs.modal', function () {
            if (pollTimer) { clearInterval(pollTimer); pollTimer = null; }
            resetModal();
        });

        form?.addEventListener('submit', async function (e) {
            e.preventDefault();

            const carpeta = document.getElementById('carpetaDrive').value.trim();
            const siglas  = document.getElementById('siglasEmpresa').value.trim();
            if (!carpeta || !siglas) return;

            // Mostrar progreso
            formulario.classList.add('d-none');
            progreso.classList.remove('d-none');
            progresoTxt.textContent = 'Preparando archivos…';
            footer.innerHTML = `<span class="text-muted" style="font-size:.8rem;">
                <i class="fas fa-lock mr-1"></i> Espera a que termine el envío
            </span>`;
            btnCerrar.classList.add('d-none');

            try {
                const token = document.querySelector('meta[name="csrf-token"]')?.content
                           || document.querySelector('input[name="_token"]')?.value || '';

                const res  = await fetch('{{ route("expedientes-operadores.drive.sync") }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': token, 'Accept': 'application/json' },
                    body: JSON.stringify({ carpeta_drive: carpeta, siglas: siglas }),
                });
                const data = await res.json();

                if (!res.ok) {
                    mostrarResultado('error', data.error || 'Error al iniciar el envío.');
                    return;
                }

                progresoTxt.textContent = `Subiendo ${data.total} archivos de ${data.operadores} operadores…`;

                // Polling cada 3 segundos
                const statusUrl = statusBase.replace('__ID__', data.sync_id);
                pollTimer = setInterval(async () => {
                    try {
                        const sr   = await fetch(statusUrl, { headers: { 'Accept': 'application/json' } });
                        const st   = await sr.json();

                        if (st.status === 'running') return;   // sigue corriendo

                        clearInterval(pollTimer); pollTimer = null;

                        if (st.status === 'done') {
                            mostrarResultado('success', st.message);
                        } else {
                            mostrarResultado('error', st.message + (st.output ? '\n\n' + st.output : ''));
                        }
                    } catch (_) { /* red, reintenta en siguiente tick */ }
                }, 3000);

            } catch (err) {
                mostrarResultado('error', 'Error de conexión: ' + err.message);
            }
        });

        function mostrarResultado(tipo, mensaje) {
            progreso.classList.add('d-none');
            btnCerrar.classList.remove('d-none');
            resultado.classList.remove('d-none');
            resultado.innerHTML = tipo === 'success'
                ? `<div class="alert alert-success mb-0"><i class="fas fa-check-circle mr-1"></i>${mensaje}</div>`
                : `<div class="alert alert-danger mb-0"><i class="fas fa-exclamation-circle mr-1"></i>
                   <strong>${mensaje.split('\n')[0]}</strong>
                   ${mensaje.includes('\n') ? '<pre style="font-size:.72rem;margin-top:.4rem;white-space:pre-wrap;">' + mensaje.split('\n').slice(1).join('\n').trim() + '</pre>' : ''}
                   </div>`;
            footer.innerHTML = `<button type="button" class="btn btn-${tipo === 'success' ? 'success' : 'secondary'}"
                data-dismiss="modal">${tipo === 'success' ? 'Listo ✓' : 'Cerrar'}</button>`;
            if (tipo === 'success') location.reload();
        }
    })();

})();
</script>
@stop