@extends('adminlte::page')

@section('title', 'Editar')

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
                    <span>Editar</span>
                    </div>
                </div>
                @if ($message = Session::get('success'))
                    <div class="alert alert-success">
                        <p>{{ $message }}</p>
                    </div>
                @endif
                <div class="card-body">
              
            <form id="editForm" method="POST" action="{{ route('horarios.update', $pservicios->ID) }}">  
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Editar Registro</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="idreg" name="idreg">
                    <input type="hidden" id="fecha" name="fecha">
                    <input type="hidden" id="turno" name="turno">
                    <input type="hidden" id="idunidad" name="idunidad">

                    <div class="form-group">
                    <div class="col-sm-5">
                        <label for="operador">Operador</label>
                        <!-- <input type="select" class="form-control" id="operador" name="operador"> -->
                        <select class="form-control" name="operador" id="operador">
                        <option value=""></option>
                        @foreach ($empleados as $empleado)
                        <option value="{{$empleado->IdOper}}">{{$empleado->Operador}}</option>

                        @endforeach
                        </select>
                    </div>
                    </div>
                <div class="modal-footer">
                    
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
        </div>
                </div>
                <div class="card-body">
                   
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para editar -->
<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form id="editForm" method="POST" action="{{ route('update-record') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Editar Registro</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="idreg" name="idreg">
                    <input type="hidden" id="fecha" name="fecha">
                    <input type="hidden" id="turno" name="turno">
                    <input type="hidden" id="idunidad" name="idunidad">

                    <div class="form-group">
                        <label for="operador">Operador</label>
                        <!-- <input type="select" class="form-control" id="operador" name="operador"> -->
                        <select class="form-control" name="operador" id="operador">
                        <option value=""></option>
                        @foreach ($empleados as $empleado)
                        <option value="{{$empleado->IdOper}}">{{$empleado->Operador}}</option>

                        @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                </div>
            </form>
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

     @if (Session::has('success'))
        <script>
            Swal.fire({
                title: '¡Éxito!',
                text: "{{ Session::get('success') }}",
                icon: 'success',
                confirmButtonText: 'OK'
            });
        </script>
    @endif

    @if (Session::has('error'))
        <script>
            Swal.fire({
                icon: "error",
                title: "Oops...",
                text: "{{ Session::get('error') }}",
               
            });
        </script>
    @endif
<script>
    $(document).ready(function() {
        $('#planservice').DataTable({
            responsive: true,
            autoWidth: false
        });

        $('#editModal').on('show.bs.modal', function (event) {
            var button = $(event.relatedTarget); // Botón que activó el modal
            var idreg = button.data('idreg');
            var fecha = button.data('fecha');
            var turno = button.data('turno');
            var idunidad = button.data('idunidad');
            var operador = button.data('operador');

            var modal = $(this);
            modal.find('.modal-body #idreg').val(idreg);
            modal.find('.modal-body #fecha').val(fecha);
            modal.find('.modal-body #turno').val(turno);
            modal.find('.modal-body #idunidad').val(idunidad);
            modal.find('.modal-body #operador').val(operador);
        });
    });
</script>
<script>
 $(document).ready(function() { 
        $("#operador").select2({
            placeholder: "Busqueda de Operador", // Opcional, para mostrar un placeholder
            allowClear: true // Permite limpiar la selección
        }); 
    });
</script>
@stop
