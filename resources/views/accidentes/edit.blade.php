@extends('adminlte::page')

@section('title', 'Fotos del accidente')

@section('content_header')
    <h1>Fotos del accidente — Folio {{ $accidente->Folio ?? $id }}</h1>
@stop

@section('content')

@if ($errors->any())
<div class="alert alert-danger">
    <ul class="mb-0">@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif
@if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
@if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

<div class="mb-3">
    <a href="{{ route('accidentes.index') }}" class="btn btn-secondary">← Regresar</a>
    @if(isset($accidente->Folio))
    <a href="{{ route('registro.mostrar', ['id' => $accidente->Folio, 'tipo' => $accidente->Tipo]) }}" class="btn btn-primary ml-2">Editar datos del accidente</a>
    @endif
</div>

{{-- Subir nuevas fotos --}}
<div class="card mb-4">
    <div class="card-header"><h5 class="mb-0">Agregar archivos</h5></div>
    <div class="card-body">
        <form method="POST" action="{{ route('accidente.imagenStore') }}" enctype="multipart/form-data" id="form-upload">
            @csrf
            <input type="hidden" name="accidente_id" value="{{ $id }}">

            <div class="form-group">
                <label for="imagen">Seleccionar archivo (imagen o PDF, máx. 25 MB)</label>
                <div class="custom-file">
                    <input type="file" class="custom-file-input" name="imagen" id="imagen"
                           accept="image/jpeg,image/png,image/gif,application/pdf">
                    <label class="custom-file-label" for="imagen">Elegir archivo…</label>
                </div>
            </div>

            <div id="preview-container" class="mb-3 d-none">
                <p class="mb-1"><strong>Vista previa:</strong></p>
                <img id="img-preview" src="" alt="preview" class="img-thumbnail" style="max-height:200px;">
                <span id="pdf-preview" class="d-none"><i class="fas fa-file-pdf fa-3x text-danger"></i> <span id="pdf-name"></span></span>
            </div>

            <button type="submit" class="btn btn-success">
                <i class="fas fa-upload"></i> Subir archivo
            </button>
        </form>
    </div>
</div>

{{-- Listado de fotos existentes --}}
<div class="card">
    <div class="card-header"><h5 class="mb-0">Archivos existentes ({{ count($imagenes) }})</h5></div>
    <div class="card-body">
        @if($imagenes->isEmpty())
            <p class="text-muted">No hay archivos registrados para este accidente.</p>
        @else
        <div class="row">
            @foreach($imagenes as $img)
            @php
                $url = 'http://89.117.77.220/controlmant/' . $img->FullFileName;
                $ext = strtolower(pathinfo($img->OriginalFileName, PATHINFO_EXTENSION));
                $esPdf = $ext === 'pdf';
            @endphp
            <div class="col-sm-6 col-md-4 col-lg-3 mb-3">
                <div class="card h-100">
                    <div class="card-body text-center p-2">
                        @if($esPdf)
                            <a href="{{ $url }}" target="_blank" class="d-block mb-2">
                                <i class="fas fa-file-pdf fa-4x text-danger"></i>
                            </a>
                        @else
                            <a href="{{ $url }}" target="_blank">
                                <img src="{{ $url }}" alt="foto" class="img-fluid img-thumbnail mb-2"
                                     style="max-height:120px;object-fit:cover;"
                                     onerror="this.style.display='none';this.nextElementSibling.classList.remove('d-none')">
                                <i class="fas fa-image fa-4x text-secondary d-none"></i>
                            </a>
                        @endif
                        <p class="small text-muted mb-1">{{ $img->OriginalFileName }}</p>
                        <p class="small text-muted mb-2">{{ $img->Creado }}</p>
                        <form method="POST" action="{{ route('accidente.imagenDelete', $img->id) }}"
                              onsubmit="return confirm('¿Eliminar este archivo?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                <i class="fas fa-trash"></i> Eliminar
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif
    </div>
</div>

@stop

@section('js')
<script>
document.getElementById('imagen').addEventListener('change', function(){
    var file = this.files[0];
    var label = document.querySelector('.custom-file-label');
    label.textContent = file ? file.name : 'Elegir archivo…';

    var previewContainer = document.getElementById('preview-container');
    var imgPreview = document.getElementById('img-preview');
    var pdfPreview = document.getElementById('pdf-preview');
    var pdfName = document.getElementById('pdf-name');

    if(!file){ previewContainer.classList.add('d-none'); return; }

    previewContainer.classList.remove('d-none');
    if(file.type === 'application/pdf'){
        imgPreview.classList.add('d-none');
        pdfPreview.classList.remove('d-none');
        pdfName.textContent = file.name;
    } else {
        pdfPreview.classList.add('d-none');
        imgPreview.classList.remove('d-none');
        imgPreview.src = URL.createObjectURL(file);
    }
});
</script>
@stop
