<div id="estadoDoc" class="mb-3 d-none">
  <div class="d-flex flex-wrap align-items-center doc-meta">
    <div class="mr-3"><strong>Folio:</strong> <span id="docFolio"></span></div>
    <div class="mr-3"><strong>Fecha:</strong> <span id="docFecha"></span></div>
    <div class="mr-3"><strong>Unidad:</strong> <span id="docUnidad"></span></div>
    <div class="mr-3 d-flex align-items-center">
      <strong class="mr-2">Estatus:</strong>
      <span id="docEstatus" class="badge badge-warning">ABIERTO</span>
    </div>
  </div>

  {{-- Comentario del folio (editable solo si ABIERTO) --}}
  <div id="docComentarioWrap" class="mt-2 d-none">
    <div class="input-group input-group-sm" style="max-width:640px">
      <input id="docComentario" class="form-control" placeholder="Comentario del folio">
      <div class="input-group-append">
        <button class="btn btn-outline-primary" id="btnGuardarDocComentario">Guardar</button>
      </div>
    </div>
    <small class="text-muted">Solo se puede editar mientras el folio está ABIERTO.</small>
  </div>
  <div id="docComentarioTexto" class="mt-2 d-none">
    <i class="fa fa-comment mr-1"></i>
    <span id="docComentarioLabel" class="small text-muted"></span>
  </div>

  <div class="small text-muted mt-2" id="docTotales"></div>

  <div id="sugerirCierre" class="alert alert-info mt-3 d-none">
    Todas las imágenes  están <strong>CONFIRMADAS</strong>. ¿Deseas cerrar el folio?
    <form class="d-inline" id="frmCerrar" method="POST">
      @csrf
      <button class="btn btn-sm btn-success">Cerrar folio</button>
    </form>
  </div>
</div>
