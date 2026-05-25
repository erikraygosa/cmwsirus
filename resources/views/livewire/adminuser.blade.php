<div>
    <div class="card-header">
        <input class="form-control"  wire:model.live="search" placeholder="Buscar" type="text">
     
    </div>
    @if($users->count())
  <div class="card-body">
    <table class="table table-striped">
        <thead> 
            <tr> 
                <th>ID</th>
                <th>Nombre</th>
                <th>Email</th>
                <th></th>

            </tr>

        </thead>

        <tbody>
            @foreach ($users as $user)
            <tr>
                <td>{{$user->id}}</td>
                <td>{{$user->name}}</td>
                <td>{{$user->email}}</td>
                <td width="10px">
                    <a class="btn btn-primary" href="{{route('users.edit', $user)}}">Editar</a>
                </td>
            </tr>
        </tbody>
        @endforeach

    </table>
  </div>
  <div class="card-footer">
        {{$users->links()}}
  </div>
  @else
  <div class="card-body"> 
    <strong>No hay Registros</strong>
  </div>

  @endif
</div>
