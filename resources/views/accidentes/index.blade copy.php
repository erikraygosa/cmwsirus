@extends('adminlte::page')


@section('title', 'Dashboard')


@section('content_header')










    <h1>Accidentes</h1>

@stop

@section('content')


@php

function verificarTipo($tipo) {
    if ($tipo == 'E') {
        return 'Externo';
    } elseif ($tipo == 'I') {
        return 'Interno';
    } else {
        return 'Error: Tipo no reconocido';
    }
}

@endphp



@if (session('success'))
    <div class="alert alert-success" id="success-message">
        {{ session('success') }}
    </div>

<script>

var successMessage = document.querySelector('#success-message');
setTimeout(function() {
  successMessage.style.display = "none";
  }, 3000);


</script>
@endif

    <p> Accidentes</p>


    <div id="modal3" class="modal" style="display:none; height: 30%; weight: 30%; margin-top: 10%" >
  <div class="modal-content">
  <span id="boton-cerrar3" class="cerrar-modal" style="">&times;</span>
  <form action="{{route('accidentes.cambiarEstado')}}" method="post">
    @csrf
  <div class="container">
    <h2>Estado del accidente</h2>
  <input style="display:none" name="id_accidente" id="modal3-input" value="0">
    <p>Estado: <b id="modal3-estado">Cerrado</b></p>
    <label>Cambiar estado a:</label>
    <select name="modal3-select">
      <option value="abierto">Abierto</option>
      <option value="procesando">Procesando</option>
      <option value="presupuestado">Presupuestado</option>
      <option value="cerrado">Cerrado</option>
    </select>

    <button class="btn btn-primary" name="btn">Cambiar estado</button>


</form>
  </div>
</div>











</div>










<div id="modal" style="display: none;" class="modal">
    <span id="boton-cerrar" class="cerrar-modal" style="">&times;</span>

  <form id="formulario-imagenes" action="{{route('accidente.imagenStore')}}" class="dropzone" method="POST" enctype="multipart/form-data">
  <h2>Cargar imágenes del accidente</h2>

  <label>Accidente:   <p id="modal-p">319</p></label> <br>

    <input value="asd" style="display:none" name="accidente_id" id="modal-input">
    <div class="fallback" style="margin-left: 30%" >
      <input type="file" name="imagenes[]">
    </div>
  </form>

</div>




<div id="successModal" style="display: none" class="modal pt-4 ">
  <div class="modal-content bg-success ">
    <h2 class="text-white">Guardado correctamente</h2>
  </div>
</div>

    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="thead">
                                    <tr>
                                        <th>Folio</th>

										<th>Tipo</th>
										<th>Propietario</th>
										<th>Conductor</th>
										<th>Ruta</th>
										<th>Operador</th>
										<th>Observaciones</th>

                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($accidentes as $accidente)
                                        <tr>


											<td>{{ $accidente->Folio }}</td>
											<td>{{ verificarTipo($accidente->Tipo) }}</td>
											<td>{{ $accidente->Propietario }}</td>
											<td>{{ $accidente->Conductor }}</td>
											<td>{{ $accidente->Ruta }}</td>
											<td>{{ $accidente->Operador }}</td>
                                            <td>{{ $accidente->Observ }}</td>
                                            <td>
                                            <a class="btn btn-sm btn-primary " href="{{ route('accidentes.show', $accidente->id) }}"><i class="fa fa-fw fa-eye"></i> {{ __('Show') }}</a>
                                            <button class="btn btn-primary" onclick="abrirModal({{ $accidente->IdHojaServ }})"><i class="fas fa-image"></i></button>
                                            <button class="btn btn-secondary mt-1" onclick="abrirModal2({{ $accidente->IdHojaServ }})"><i class="fas fa-images"></i></button>
                                            <button class="btn btn-info mt-1" onclick="abrirModal3({{ $accidente->IdHojaServ }},{{$accidente->Estatus}})"><i class="fas fa-toggle-on"></i></button>
                                            <a class="btn btn-danger mt-1" href="{{ route('accidentes.generarpdf') }}?id={{$accidente->IdHojaServ}}"> <i class="fas fa-file-pdf"></i></a>
                                          </td>


                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>







                    <div id="modal2" class="modal" style="display:none">
  <div class="modal-content">
  <span id="boton-cerrar2" class="cerrar-modal" style="">&times;</span>
  <form>
  <div class="container">
    <h2>Imagenes existentes</h2>
  <table class="table gallery" id="table-images">
    <thead>
      <tr>
        <th>Imagen</th>
        <th>Acción</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>

        <td>

        </td>
      </tr>
      <!-- Agregar más filas para más imágenes -->
    </tbody>
  </table>
</form>
  </div>
</div>









@stop

@section('css')

    <!--Gallery Image-->



    <link rel="stylesheet" href="/css/admin_custom.css">
          <!--BAGGUETE -->
          <script src="{{ asset('js/baguetteBox.js') }}"></script>

        <link href="{{ asset('css/baguetteBox.css') }}" rel="stylesheet">

    <style>
.modal {

  position: fixed;
  top: 0;
  left: 0;


  display: flex;
  justify-content: center;
  align-items: center;
  transform: translate(60%, 20%);
  width: 50%;
  height: 80%;

}

.modal h2 {
  color: black;
}

.modal form {
  background-color: white;
  padding: 20px;
  border-radius: 5px;
}

.modal input[type="file"] {
  margin-bottom: 10px;
}


.dz-error-message {
  display: none;
}

.cerrar-modal {
  position: absolute;
  top: 0;
  right: 0;
  font-size: 28px;
  margin-right: 20px;
  margin-top: 10px;
  cursor: pointer;
}

.modal {
  overflow-y: auto;
}



        </style>

        <!-- Magnific Popup core CSS file -->
        <link rel="stylesheet" href="{{ asset('css/magnific-popup.css') }}">


@stop

@section('js')

<script src="https://unpkg.com/dropzone@5/dist/min/dropzone.min.js"></script>
<link rel="stylesheet" href="https://unpkg.com/dropzone@5/dist/min/dropzone.min.css" type="text/css" />

<script>

function showSuccessModal() {
  var modal3 = document.getElementById("successModal");
  console.log(modal3);
  modal3.style.display = "block";
  setTimeout(function() {
    modal3.style.display = "none";
  }, 1500);
}





//DROPZONE CONTROLLER

Dropzone.options.formularioImagenes = {

  paramName: "imagen", // nombre del campo en el formulario
  maxFilesize: 10, // tamaño máximo en MB
  acceptedFiles: "image/*", // aceptar sólo imágenes
  addRemoveLinks: true, // agregar enlace para eliminar archivos
  dictRemoveFile: "Eliminar archivo", // texto del enlace para eliminar archivos
  dictDefaultMessage: "Arrastra tus imágenes aquí o haz clic para seleccionarlas", // texto para mostrar en el área de Dropzone
  dictFileTooBig: "El archivo es demasiado grande 5 MB. El tamaño máximo es 4 MB.",
  headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }, // texto de error para archivos demasiado grandes

  init: function() {
    // Inicializa el objeto Dropzone
    formularioImagenes = this;
  },

  success: function (file, response) {
    console.log(response);

    document.getElementById("modal").style.display = "none";
    formularioImagenes.removeAllFiles();
    showSuccessModal();
  }
};



</script>



<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.0/js/bootstrap.min.js"></script>




<!-- jQuery 1.7.2+ or Zepto.js 1.0+ -->
<script src="//ajax.googleapis.com/ajax/libs/jquery/1.9.1/jquery.min.js"></script>

<!-- Magnific Popup core JS file -->
<script src="{{asset('js/magnific-popup.js') }}"></script>

    <script>


// Obtener el botón de cerrar
var botonCerrar = document.getElementById("boton-cerrar");

// Agregar evento click al botón de cerrar
botonCerrar.addEventListener("click", function() {
  // Ocultar el modal
  document.getElementById("modal").style.display = "none";
});


var botonCerrar2 = document.getElementById("boton-cerrar2");

// Agregar evento click al botón de cerrar
botonCerrar2.addEventListener("click", function() {
  // Ocultar el modal
  document.getElementById("modal2").style.display = "none";
});



var botonCerrar3 = document.getElementById("boton-cerrar3");

// Agregar evento click al botón de cerrar
botonCerrar3.addEventListener("click", function() {
  // Ocultar el modal
  document.getElementById("modal3").style.display = "none";
});




const modal = document.querySelector('#modal');



function abrirModal(id){
    var p = document.querySelector('#modal-p');
    var input = document.querySelector('#modal-input');
    console.log(modal);
    input.value = id;
    p.textContent = id;
    modal.style.display = 'block';

}

function abrirModal2(id){
    const modal2 = document.querySelector('#modal2');
    modal2.style.display = 'block';

    const table = document.getElementById("table-images");
    while (table.rows.length > 0) {
    table.deleteRow(0);
}

    const request = new XMLHttpRequest();
    request.onreadystatechange = function() {
        if (this.readyState === XMLHttpRequest.DONE && this.status === 200) {
            // La petición ha tenido éxito
            const response = JSON.parse(this.responseText);


            for (let i = 0; i < response.length; i++) {
                const newRow = table.insertRow();
                const cell1 = newRow.insertCell();
                const cell2 = newRow.insertCell();

                cell1.innerHTML = `<a href="{{ asset('.${response[i].ruta}') }}" data-caption="Image caption">
            <img src="{{ asset('.${response[i].ruta}') }}" style="width: 20%; height: 20%;" alt="First image">
          </a>`;










            cell2.innerHTML = `<a type='submit' class='btn btn-danger' href="imagenesAccidente/eliminar/${response[i].id_imagen}">Eliminar</a>`;


                }

                baguetteBox.run('.gallery');

            // Aquí puedes procesar la respuesta
        }
    };
    request.open('GET', `{{route('accidente.imagenGet')}}?accidente_id=${id}`);
    request.send();

}

function abrirModal3(id, estado){

    var estadoAccidente = "";

    var inputId = document.querySelector('#modal3-input');
    inputId.setAttribute('value', id);



    switch(estado){

      case 1:
        estadoAccidente = "Abierto";
        break;

        case 2:
        estadoAccidente = "Procesando";
        break;

        case 3:
        estadoAccidente = "Presupuestado";
        break;

        case 4:
          estadoAccidente = "Cerrado";
          break;
    }



    var modal3 = document.querySelector('#modal3');
    var modal3Estado = document.querySelector('#modal3-estado');


    modal3Estado.innerHTML = estadoAccidente;


    modal3.style.display = 'block';


}








    // Get the modal
/*var modal = document.getElementById("modal");

// Get the close button
var closeBtn = modal.querySelector(".close");

// Open the modal by default
modal.style.display = "block";

// Close the modal when the close button is clicked
closeBtn.addEventListener("click", function() {
  modal.style.display = "none";
}); */
</script>


@stop
