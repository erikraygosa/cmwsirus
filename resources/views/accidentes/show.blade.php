@extends('adminlte::page')

@section('title', 'Descargar Imagenes')

@section('content_header')
    <h1>Descargar Imagenes</h1>
@stop

@section('content')
    <div class="container">
        <div class="card">
            <div class="card-body bg-white">
                <div class="table-responsive">
                    <table class="table table-striped table-hover" id="accidentes">
                        <thead class="thead">
                            <tr>
                                <th>Fecha</th>
                                <th>Folio</th>
                                <th>Miniatura</th>
                                <th>Descargar</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($accidentes as $accidente)
                                @php
                                    $base = 'https://csirus.appcontrolbus.com.mx/api/get/ControlMant';

                                    // Opción 1: viene todo en FullFileName (e.g. "MMA9010124P2/2072.jpg")
                                    if (!empty($accidente->FullFileName)) {
                                        // No codifiques el string completo para no convertir "/" en %2F
                                        $rel = ltrim($accidente->FullFileName, '/');
                                        $imgUrl = rtrim($base, '/') . '/' . $rel;
                                        $downloadName = basename($rel);
                                    }
                                    // Opción 2: vienen separados RFC/carpeta + archivo
                                    else {
                                        $rfc   = isset($accidente->Rfc) ? $accidente->Rfc : '';
                                        $file  = isset($accidente->NombreArchivo) ? $accidente->NombreArchivo : '';

                                        // Codifica solo cada segmento, no la "/"
                                        $rfcEnc  = rawurlencode($rfc);
                                        $fileEnc = rawurlencode($file);

                                        $imgUrl = rtrim($base, '/') . '/' . $rfcEnc . '/' . $fileEnc;
                                        $downloadName = $file ?: ('imagen_' . ($accidente->IdRegTab ?? ''));
                                    }
                                @endphp

                                <tr>
                                    <td>{{ $accidente->Creado }}</td>
                                    <td>{{ $accidente->IdRegTab }}</td>

                                    {{-- Miniatura que abre en nueva pestaña --}}
                                    <td style="max-width: 180px;">
                                        <a href="{{ $imgUrl }}" target="_blank" rel="noopener noreferrer" title="Ver imagen">
                                            <img
                                                src="{{ $imgUrl }}"
                                                alt="Imagen del folio {{ $accidente->IdRegTab }}"
                                                class="thumb-img"
                                                loading="lazy"
                                                width="150"
                                                height="100"
                                                onerror="this.onerror=null;this.src='data:image/svg+xml,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22150%22 height=%22100%22%3E%3Crect width=%22100%25%22 height=%22100%25%22 fill=%22%23e9ecef%22/%3E%3Ctext x=%2250%25%22 y=%2250%25%22 dominant-baseline=%22middle%22 text-anchor=%22middle%22 font-size=%2212%22 fill=%22%236c757d%22%3ESin vista previa%3C/text%3E%3C/svg%3E';"
                                            >
                                        </a>
                                    </td>

                                    {{-- Ver / Descargar --}}
                                    <td>
                                        <div class="btn-group" role="group" aria-label="Acciones">
                                            <a class="btn btn-sm btn-outline-primary"
                                               href="{{ $imgUrl }}"
                                               target="_blank"
                                               rel="noopener noreferrer"
                                               title="Abrir imagen">
                                                <i class="fa fa-fw fa-eye"></i> Ver
                                            </a>

                                            <a class="btn btn-sm btn-success"
                                               href="{{ $imgUrl }}"
                                               download="{{ $downloadName }}"
                                               title="Descargar imagen">
                                                <i class="fa fa-fw fa-download"></i> Descargar
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div> {{-- .table-responsive --}}
            </div>
        </div>
    </div>
@stop

@section('css')
<style>
/* Miniatura */
.thumb-img{
  display:block;
  width:150px;
  height:100px;
  object-fit:cover;
  border-radius:8px;
  border:1px solid #e1e1e1;
  background:#f8f9fa;
  box-shadow:0 1px 2px rgba(0,0,0,.04);
  transition:transform .12s ease;
}
.thumb-img:hover{ transform:scale(1.02); }
</style>
@stop

@section('js')
<script>
  console.log("Vista descargas lista: evitando %2F en FullFileName.");
</script>
@stop
