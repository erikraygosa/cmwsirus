@php
  $lados = [
    ['k'=>'TblImagenDel',    'label'=>'Frente'],
    ['k'=>'TblImagenTra',    'label'=>'Atrás'],
    ['k'=>'TblImagenIzq',    'label'=>'Costado Izquierdo'],
    ['k'=>'TblImagenDer',    'label'=>'Costado Derecho'],
    ['k'=>'TblImagenEspIzq', 'label'=>'Espejo Izquierdo'],
    ['k'=>'TblImagenEspDer', 'label'=>'Espejo Derecho'],
  ];
@endphp

<div id="contenedorLados" class="d-none">
  <div id="bannerSinDoc" class="alert alert-warning d-none mt-2">
    Haga clic en <strong>Crear Folio</strong> para crearle un folio nuevo a la unidad seleccionada y poder subir fotos.
  </div>

  @foreach($lados as $lado)
    <div class="mb-4" data-lado="{{ $lado['k'] }}">
      <div class="d-flex align-items-center justify-content-between">
        <h5 class="mb-2">{{ $lado['label'] }}</h5>
        <div>
          <input type="file" accept="image/*" capture="environment" class="d-none" id="inp_{{ $lado['k'] }}" multiple>
          <button type="button" class="btn btn-sm btn-primary" data-choose="{{ $lado['k'] }}">Añadir fotos</button>
        </div>
      </div>
      <div id="grid_{{ $lado['k'] }}" class="row row-cols-2 row-cols-md-4 g-2 mb-2"></div>
      <div id="edit_{{ $lado['k'] }}" class="row row-cols-1 row-cols-md-2 g-3"></div>
    </div>
  @endforeach
</div>
