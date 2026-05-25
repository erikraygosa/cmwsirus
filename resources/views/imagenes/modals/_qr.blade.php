<div class="modal fade" id="qrModal" tabindex="-1" role="dialog" aria-labelledby="qrModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header d-flex justify-content-between align-items-center">
        <h5 class="modal-title">Escanear QR de Unidad</h5>
        <div class="d-flex gap-2">
          <button type="button" class="btn btn-sm btn-outline-danger" id="btnStopQR">Detener cámara</button>
          <button type="button" class="btn btn-sm btn-secondary" data-dismiss="modal" id="btnCerrarQR">Cerrar</button>
        </div>
      </div>
      <div class="modal-body">
        <div id="qrReader" style="width:100%"></div>
        <small class="text-muted">Debe contener el IdUnidad (número) o la clave visible (p.ej. MO 001).</small>
      </div>
    </div>
  </div>
</div>
