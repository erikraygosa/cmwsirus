{{-- resources/views/imagenes/partials/_selector_unidad.blade.php --}}
<div class="d-flex flex-wrap align-items-end gap-2">
  <div class="flex-grow-1">
    <label for="slcUnidad" class="mb-1">Unidad</label>
    <select id="slcUnidad" class="form-control">
      <option value="">-- Selecciona unidad --</option>
      @foreach($unidades as $u)
        @php
          // Normaliza por si viene como array o stdClass/Eloquent:
          $obj = is_array($u) ? (object)$u : $u;
          $id  = $obj->IdUnidad ?? $obj->id ?? $obj->ID ?? '';
          // Texto visible: prioriza 'Unidad' y luego otros posibles campos
          $txt = $obj->Unidad
                 ?? $obj->Nombre
                 ?? $obj->Descripcion
                 ?? $obj->Codigo
                 ?? $id;
        @endphp
        <option value="{{ $id }}">{{ $txt }}</option>
      @endforeach
    </select>
  </div>

  <div class="ml-2 mb-3">
    <button id="btnLeerQR" class="btn btn-outline-secondary" disabled>
      <i class="fa fa-qrcode mr-1"></i> Leer QR
    </button>
    <button id="btnCrearDoc" class="btn btn-primary" disabled>
      <i class="fa fa-plus mr-1"></i> Crear folio
    </button>
    <button id="btnDeshacerUnidad" class="btn btn-light d-none">
      <i class="fa fa-undo mr-1"></i> Cambiar unidad
    </button>
  </div>
</div>
