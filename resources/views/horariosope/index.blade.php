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
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="planservice">
                                <thead class="thead">
                                    <tr>
                                 
                                      
									
										<th>Ruta</th>
                                        <th>Operador </th>
										<th>Unidad</th>
										<th>Corrida</th>
                                       
                                          <th>Turno</th>
									
										
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($pservicios as $pservicio)
                                        <tr>
                                      
                                         
                                            
									
											<td>{{ $pservicio->Ruta }}</td>
                                            <td>{{ $pservicio->Operador  }}</td>
											<td>{{ $pservicio->Unidad  }}</td>
                                            <td>{{ $pservicio->Corrida }}</td>
                                            
                                            <td>{{ $pservicio->Turno  }}</td>
											
                                        
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
    
@stop

@section('js')
<script src="https://cdn.datatables.net/2.0.0/js/dataTables.js"></script>
<script src="https://cdn.datatables.net/responsive/3.0.0/js/dataTables.responsive.js"></script>
<script src="https://cdn.datatables.net/responsive/3.0.0/js/responsive.bootstrap4.js"></script>
<script>
$('#planservice').dataTable({
    responsive: true,
    autoWidth: false
});
</script>
@stop


