<div class="card mt-3" id="cardFolios">
  <div class="card-header d-flex align-items-center">
    <span class="h5 mb-0">Folios de Imágenes</span>
    <div class="d-flex gap-2 align-items-center ml-auto">
      <label class="mb-0 mr-2">Mostrar:</label>
      <select id="filtroEstatus" class="form-control form-control-sm" style="width:180px">
        <option value="ABIERTO" selected>ABIERTO</option>
      
        <option value="CERRADO">CERRADO</option>
        <option value="ALL">TODOS</option>
      </select>
    </div>
  </div>
  <div class="card-body">
    <div class="table-responsive">
      <table id="tblFolios" class="table table-striped table-hover w-100">
        <thead>
          <tr>
              <th></th> 
            <th>Fecha</th>
            <th>Folio</th>
            <th>Unidad</th>
            <th>Comentarios</th>
            <th>Estatus</th>
            <th>Totales</th>
            <th class="text-center">Acciones</th>
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>
</div>
