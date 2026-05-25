@extends('adminlte::page')

@section('Accidentes')
Accidentes
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-sm-12">
                <div class="card">
                    <div class="card-header">
                        <div style="display: flex; justify-content: space-between; align-items: center;">

                            <span id="card_title">
                                Accidentes
                            </span>

                            
                        </div>
                    </div>
                    @if ($message = Session::get('success'))
                        <div class="alert alert-success m-4">
                            <p>{{ $message }}</p>
                        </div>
                    @endif
                    @if ($errors->has('error'))
                            <div class="alert alert-danger">
                                {{ $errors->first('error') }}
                            </div>
                    @endif


                    <div class="card-body bg-white" >
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="accidentes">
                                <thead class="thead">
                                    <tr>
                                        <th>Fecha</th> 
                                        <th>Folio  </th>    
                                        <!-- <th>Hoja Serv  </th>   -->
										
										<th>Operador</th>
										
										<th>Ruta</th>
										<th>Conductor</th>
                                        <th>Tipo</th>
										<th>Observaciones</th>
                                        <th>User W</th>

                    <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($accidentes as $accidente)
                                        <tr>

                                            <td>{{ $accidente->FechaIni }}</td>
											<td>{{ $accidente->Folio }}</td>
                                            <!-- <td>{{ $accidente->IdHojaServ }}</td> -->
											
											<td>{{ $accidente->Operador }}</td>
											
											<td>{{ $accidente->Ruta }}</td>
											<td>{{ $accidente->Conductor }}</td>
                                            <td>{{ $accidente->Tipo }}</td>
                                            <td>{{ $accidente->Observ }}</td>
                                            <td>{{ $accidente->user_web }}</td>
                                            
                                            <td>
                                            <a class="btn btn-sm btn-primary" href="{{ route('accidentes.edit', $accidente->Folio) }}">
                                                    <i class="fa fa-fw fa-upload fa-lg"></i>
                                                </a>
                                                <a class="btn btn-sm btn-success" href="{{ route('accidentes.show', $accidente->Folio) }}">
                                                    <i class="fa fa-fw fa-eye fa-lg"></i>
                                                </a>
                                                <a class="btn btn-sm btn-danger mt-1" href="{{ route('accidentes.generarpdf') }}?id={{$accidente->Folio}}">
                                                 <i class="fa fa-fw fa-file-pdf fa-lg"></i>
                                                </a>
                                                @if($accidente->Estatus == 1)
                                                    <a class="btn btn-sm btn-warning mt-1" 
                                                    href="{{ route('registro.mostrar', ['id' => $accidente->Folio, 'tipo' => $accidente->Tipo]) }}">
                                                        <i class="fa fa-fw fa-edit fa-lg"></i> 
                                                    </a>
                                                @endif
                                          </td>                              
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            
            </div>
        </div>
    </div>
@endsection
@section('js')
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
$('#accidentes').dataTable({
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