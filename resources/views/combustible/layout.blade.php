@extends('adminlte::page')
@section('title', 'Combustible')
@section('content_header')
<h1>Cargas de combustible</h1>
@stop
@section('content')
<nav class="mb-3" aria-label="Combustible">
<a class="btn btn-outline-secondary" href="{{ route('combustible.index') }}">Consulta</a>
@can('combustible_crear')<a class="btn btn-primary" href="{{ route('combustible.create') }}">Capturar</a>@endcan
@can('combustible_precios')<a class="btn btn-outline-primary" href="{{ route('combustible.prices') }}">Precios de diésel</a>@endcan
@can('combustible_auditoria')<a class="btn btn-outline-secondary" href="{{ route('combustible.audit') }}">Auditoría</a>@endcan
</nav>
<div id="fuel-message" class="alert d-none" role="status" aria-live="polite"></div>
@if($errors->any())<div class="alert alert-danger">{{ $errors->first() }}</div>@endif
@yield('fuel_content')
@stop
@section('js')
<script>
window.fuelMessage = function(message, ok=false) {
 const el=document.getElementById('fuel-message'); el.textContent=message; el.className='alert '+(ok?'alert-success':'alert-danger'); el.scrollIntoView({block:'nearest'});
};
window.fuelRequest = async function(url, options={}) {
 const response=await fetch(url, {...options, headers:{'Accept':'application/json','X-CSRF-TOKEN':@json(csrf_token()), ...options.headers}});
 const data=await response.json().catch(()=>({message:'Respuesta inesperada del servidor. Conserve los datos y el identificador para reintentar.'}));
 if(!response.ok) throw new Error((Object.values(data.errors||{}).flat().join(' ')||data.message||'No se pudo completar la solicitud.')+(data.operacion?' Identificador: '+data.operacion:''));
 return data;
};
document.querySelectorAll('form[data-fuel-mutation]').forEach(form=>form.addEventListener('submit',async event=>{
 event.preventDefault(); if(form.dataset.busy==='1') return;
 if(form.dataset.confirm && !confirm(form.dataset.confirm)) return;
 form.dataset.busy='1'; const button=form.querySelector('[type="submit"]'); button.disabled=true;
 try {
  const data=await fuelRequest(form.action,{method:'POST',body:new FormData(form)});
  fuelMessage(data.message,true);
  form.dataset.completed='1'; form.dispatchEvent(new Event('fuel:success'));
  if(form.dataset.redirect) location.assign(form.dataset.redirect);
  else location.reload();
 } catch(error) {fuelMessage(error.message);button.disabled=false;form.dataset.busy='0';}
}));
@can('combustible_precios')
@if(request()->routeIs('combustible.index','combustible.create','combustible.edit'))
fuelRequest(@json(route('combustible.notice')),{method:'POST'}).then(async data=>{
 if(!data.mostrar) return;
 const dialog=document.createElement('dialog');
 dialog.innerHTML='<h2>Precio de diésel de hoy</h2><p>No hay un precio registrado para hoy. ¿Desea actualizarlo?</p><button type="button" id="fuel-price-now" class="btn btn-primary">Actualizar precio</button> <button type="button" id="fuel-price-later" class="btn btn-secondary">Ahora no</button>';
 document.body.append(dialog);
 const omit=async()=>{await fuelRequest(@json(route('combustible.notice.omit')),{method:'POST'});dialog.close();dialog.remove();};
 dialog.querySelector('#fuel-price-now').onclick=()=>location.assign(@json(route('combustible.prices')));
 dialog.querySelector('#fuel-price-later').onclick=()=>omit().catch(e=>fuelMessage(e.message));
 dialog.addEventListener('cancel',event=>{event.preventDefault();omit().catch(e=>fuelMessage(e.message));});
 dialog.showModal();
}).catch(error=>fuelMessage(error.message));
@endif
@endcan
</script>
@yield('fuel_js')
@stop
