@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
    <!-- <h1>Horarios</h1> -->
@stop

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <div class="card-header">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <span id="card_title">
                            {{ __('Horario del dia ' ) }} {{$fechaProgramada}}
                        </span>
                    </div>
                </div>
                @if ($message = Session::get('success'))
                    <div class="alert alert-success">
                        <p>{{ $message }}</p>
                    </div>
                @endif
                <div class="card-body">
                    <form action="{{ route('horarios.store') }}" method="POST">
                        @csrf
                        <label for="fecha_inicio">Fecha:</label>
                        <input type="date" name="fecha_inicio" id="fecha_inicio">
                        <button class="btn btn-sm btn-success" type="submit">Buscar Fecha</button>
                    </form>
                </div>
                @if ($pservicios->isEmpty())
                <div class="form-group text-center">
                <h1 class="badge bg-primary text-white fs-4 p-3">Seleccione su Fecha</h1>
                    </div>
                @else
                <div class="card-body">
                    <div class="table-responsive"> 
                        <table class="table table-striped table-hover" id="planservice">
                            <thead class="thead">
                                <tr>
                                    <th>Fecha</th>
                                    <th>Ruta</th>
                                    <th>Operador</th>
                                    <th>Unidad</th>
                                    <th>Corrida</th>
                                    <th>Turno</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($pservicios as $pservicio)
                                    <tr>
                                        <td>{{ $pservicio->Fecha }}</td>
                                        <td>{{ $pservicio->Ruta }}</td>
                                        <td>{{ $pservicio->Operador }}</td>
                                        <td>{{ $pservicio->Unidad }}</td>
                                        <td>{{ $pservicio->Corrida }}</td>
                                        <td>{{ $pservicio->Turno }}</td>
                                        <td>
                                        <a class="btn btn-sm btn-primary" href="{{ route('horarios.edit', $pservicio->ID) }}">
                                                    <i class="fa fa-fw fa-edit fa-lg"></i>
                                                </a>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Modal para editar -->


@endsection

@section('footer')
<div class="float-left d-none d-sm-block">
    <b>Versión</b> 1.0
</div>
<strong>© 2024 <a href="https://www.facebook.com/erik.raygosachac?mibextid=ZbWKwL">Control Mant Web</a> </strong> Todos los derechos reservados.
@stop

@section('css')
<link rel="stylesheet" href="/css/admin_custom.css">
<link rel="stylesheet" href="https://cdn.datatables.net/2.0.0/css/dataTables.dataTables.css" />
<link rel="stylesheet" href="https://cdn.datatables.net/responsive/3.0.0/css/responsive.bootstrap4.css" />
<style>
        table.dataTable thead {
            background: linear-gradient(to right, #4A00E0, #3488f7);
            color: white;

        }

        table.dataTable th {
            text-align: center;
            vertical-align: middle;
        }

        table.dataTable td {}

        /* Ajuste específico para la columna de fecha */
    </style>
@stop

@section('js')
<script src="https://cdn.datatables.net/2.0.0/js/dataTables.js"></script>
<script src="https://cdn.datatables.net/responsive/3.0.0/js/dataTables.responsive.js"></script>
<script src="https://cdn.datatables.net/responsive/3.0.0/js/responsive.bootstrap4.js"></script>


<script>

    $('#planservice').dataTable({
    responsive: true,
    autoWidth: false,
    order: [
        [0, 'desc']
    ],
    language: {
        "sProcessing": "Procesando...",
        "sLengthMenu": "Mostrar _MENU_ registros",
        "sZeroRecords": "No se encontraron resultados",
        "sEmptyTable": "Ningún dato disponible en esta tabla",
        "sInfo": "Mostrando registros del _START_ al _END_ de un total de _TOTAL_ registros",
        "sInfoEmpty": "Mostrando registros del 0 al 0 de un total de 0 registros",
        "sInfoFiltered": "(filtrado de un total de _MAX_ registros)",
        "sInfoPostFix": "",
        "sSearch": "Buscar:",
        "sUrl": "",
        "sInfoThousands": ",",
        "sLoadingRecords": "Cargando...",
        "oPaginate": {
            "sFirst": "Primero",
            "sLast": "Último",
            "sNext": "Siguiente",
            "sPrevious": "Anterior"
        },
    }
});
</script>
@stop
