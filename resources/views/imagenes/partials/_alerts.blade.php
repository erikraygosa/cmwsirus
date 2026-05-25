@if(session('success'))
  <div class="alert alert-success alert-dismissible fade show" role="alert" data-timeout="3000">
    {{ session('success') }}
    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span>&times;</span></button>
  </div>
@endif

@if($errors->any())
  <div class="alert alert-danger alert-dismissible fade show" role="alert" data-timeout="6000">
    {{ implode(' | ', $errors->all()) }}
    <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span>&times;</span></button>
  </div>
@endif
