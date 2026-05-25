<div class="modal fade" id="adjuntosModal" tabindex="-1" role="dialog" aria-labelledby="adjuntosLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable modal-mobile-full" role="document">
    <div class="modal-content">
      <div class="modal-header d-flex justify-content-between align-items-center">
        <h5 class="modal-title">Adjuntos del Folio <span id="adjFolio"></span></h5>
        <div class="d-flex align-items-center gap-2">
          <label class="mb-0 mr-1">Estatus:</label>
          <select id="filtroAdjEstatus" class="form-control form-control-sm" style="width:150px">
            <option value="TODOS">TODOS</option>
            <option value="PENDIENTE" selected>PENDIENTE</option>
            <option value="ATENDIDO">ATENDIDO</option>
            <option value="DOCUMENTADO">DOCUMENTADO</option>
            <option value="CONFIRMADO">CONFIRMADO</option>
          </select>
          <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal">Cerrar</button>
        </div>
      </div>
      <div class="modal-body">
        <div id="adjuntosWrap" class="row row-cols-1 row-cols-md-4 g-2"></div>
      </div>
    </div>
  </div>
</div>
