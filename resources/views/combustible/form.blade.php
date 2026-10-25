@extends('combustible.layout')
@section('fuel_content')
<div class="card card-body">
<h2 class="h4">{{ $row?'Editar carga #'.$row->IdCarga:'Nueva carga' }}</h2>
<form id="fuel-capture" method="post" action="{{ $row?route('combustible.update',$row->IdCarga):route('combustible.store') }}" data-fuel-mutation data-redirect="{{ route('combustible.index') }}">
@csrf
@if($row)@method('PUT')<input type="hidden" name="Operacion" value="{{ $token }}"><input type="hidden" name="Version" value="{{ $version }}">@else<input type="hidden" name="TokenRegistro" value="{{ $token }}">@endif
<div class="row">
<div class="col-md-4"><label for="FechaCarga">Fecha de carga</label><input id="FechaCarga" name="FechaCarga" type="date" class="form-control" value="{{ $row->FechaCarga??$today }}" required><small>Determina el precio aplicable.</small></div>
<div class="col-md-4"><label for="FechaTrabajo">Fecha de trabajo</label><input id="FechaTrabajo" name="FechaTrabajo" type="date" class="form-control" value="{{ $row->FechaTrabajo??$today }}" required><small>Puede corresponder a un día anterior trabajado.</small></div>
<div class="col-md-4"><label for="Turno">Turno</label><select id="Turno" name="Turno" class="form-control">@foreach(['AM','PM'] as $t)<option @selected(($row->Turno??'PM')===$t)>{{ $t }}</option>@endforeach</select></div>
</div>
<div class="mt-3"><label for="unit-search">Buscar unidad activa</label><input id="unit-search" type="search" class="form-control" placeholder="Escriba la unidad" autocomplete="off">
<label for="IdUnidad">Unidad</label><div class="input-group"><select id="IdUnidad" name="IdUnidad" class="form-control" required><option value="">Seleccione una unidad</option>@if($row)<option selected value="{{ $row->IdUnidad }}">{{ $row->Unidad }}</option>@endif</select><div class="input-group-append"><button id="qr-open" class="btn btn-outline-secondary" type="button" data-toggle="modal" data-target="#fuel-qr">Escanear QR</button></div></div></div>
<div class="row mt-3"><div class="col-md-4"><label for="Litros">Litros</label><input id="Litros" name="Litros" type="number" min="0.001" max="99999999.999" step="0.001" class="form-control" value="{{ $row->Litros??'' }}" required></div>
<div class="col-md-8"><label>Precio e importe</label><p id="fuel-quote" aria-live="polite">{{ $row?'Precio histórico: $'.$row->PrecioLitro.' · Importe: $'.$row->Importe.' · Precio del '.$row->FechaPrecio:'Ingrese fecha de carga y litros para consultar el precio.' }}</p></div></div>
<label for="Observaciones" class="mt-3">Observaciones (opcional)</label><textarea id="Observaciones" name="Observaciones" class="form-control" maxlength="255">{{ $row->Observaciones??'' }}</textarea>
<p class="text-muted mt-3">El servidor valida la unidad y calcula el importe. Las cargas idénticas activas se bloquean. Si falla el envío, conserve esta captura y reintente.</p>
<p class="text-muted">Identificador de envío: <code id="fuel-token-label">{{ $token }}</code></p>
<button type="submit" class="btn btn-primary">{{ $row?'Guardar cambios':'Registrar carga' }}</button>
<button id="fuel-discard" type="button" class="btn btn-outline-secondary">Descartar borrador</button>
</form>
</div>
<div class="modal fade" id="fuel-qr" tabindex="-1" role="dialog" aria-labelledby="fuel-qr-title" aria-hidden="true"><div class="modal-dialog"><div class="modal-content">
<div class="modal-header"><h2 class="h5" id="fuel-qr-title">Escanear QR de unidad</h2><button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">&times;</button></div>
<div class="modal-body"><div id="fuel-reader"></div><p id="fuel-camera-message" role="status">Se requiere permiso de cámara y HTTPS. También puede buscar la unidad manualmente.</p></div>
</div></div></div>
@stop
@section('fuel_js')
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script>
(()=>{
 const form=document.getElementById('fuel-capture'), select=document.getElementById('IdUnidad');
 const key='combustible:'+@json((string) auth()->id())+':'+@json($row?'editar:'.$row->IdCarga:'nueva');
 const fields={{ Illuminate\Support\Js::from(array_merge($row?['Operacion','Version']:['TokenRegistro'],['FechaCarga','FechaTrabajo','Turno','IdUnidad','Litros','Observaciones'])) }};
 const save=()=>{try {const data=Object.fromEntries(fields.map(name=>[name,form.elements[name].value]));data.label=select.selectedOptions[0]?.textContent||'';sessionStorage.setItem(key,JSON.stringify(data));}catch(e){}};
 try {const data=JSON.parse(sessionStorage.getItem(key)||'null');if(data){if(data.IdUnidad)select.add(new Option(data.label,data.IdUnidad));fields.forEach(name=>{if(data[name]!==undefined)form.elements[name].value=data[name];});}}catch(e){}
 document.getElementById('fuel-token-label').textContent=form.elements[@json($row?'Operacion':'TokenRegistro')].value;
 form.addEventListener('input',save);form.addEventListener('change',save);
 form.addEventListener('fuel:success',()=>{try{sessionStorage.removeItem(key);}catch(e){}});
 document.getElementById('fuel-discard').onclick=()=>{if(confirm('¿Descartar los datos pendientes y comenzar de nuevo? No deshace cargas ya guardadas.')){try{sessionStorage.removeItem(key);}catch(e){}location.reload();}};
 let searchId=0;
 async function search(){const id=++searchId;try{const rows=await fuelRequest(@json(route('combustible.units'))+'?q='+encodeURIComponent(document.getElementById('unit-search').value));if(id!==searchId)return;const value=select.value,label=select.selectedOptions[0]?.textContent;select.replaceChildren(new Option('Seleccione una unidad',''));rows.forEach(row=>select.add(new Option(row.Unidad,row.IdUnidad)));if(value&&!rows.some(r=>String(r.IdUnidad)===value))select.add(new Option(label,value));select.value=value;}catch(e){fuelMessage(e.message);}}
 let timer;document.getElementById('unit-search').addEventListener('input',()=>{clearTimeout(timer);timer=setTimeout(search,250);});search();
 const original={{ Illuminate\Support\Js::from($row?['date'=>$row->FechaCarga,'litres'=>(string)$row->Litros,'price'=>(string)$row->PrecioLitro,'amount'=>(string)$row->Importe,'priceDate'=>$row->FechaPrecio]:null) }};
 let quoteId=0;
 async function quote(){const id=++quoteId,el=document.getElementById('fuel-quote'),date=form.FechaCarga.value,litres=form.Litros.value;
 if(original&&date===original.date&&Number(litres)===Number(original.litres)){el.textContent='Precio histórico: $'+original.price+' · Importe: $'+original.amount+' · Precio del '+original.priceDate;return;}
 if(!date||!litres){el.textContent='Ingrese fecha de carga y litros para consultar el precio.';return;}
 try{const data=await fuelRequest(@json(route('combustible.quote'))+'?'+new URLSearchParams({FechaCarga:date,Litros:litres}));if(id===quoteId)el.textContent='Precio: $'+data.PrecioLitro+' · Importe: $'+data.Importe+' · Precio del '+data.FechaPrecio;}catch(e){if(id===quoteId)el.textContent=e.message;}}
 form.FechaCarga.addEventListener('change',quote);form.Litros.addEventListener('input',()=>{clearTimeout(timer);timer=setTimeout(quote,250);});quote();
 let scanner=null,opened=false,decoding=false,starting=null;
 async function stop(){opened=false;if(starting)await starting.catch(()=>{});if(scanner){try{if(scanner.isScanning)await scanner.stop();scanner.clear();}catch(e){}scanner=null;}}
 $('#fuel-qr').on('shown.bs.modal',()=>{
 opened=true;decoding=false;const msg=document.getElementById('fuel-camera-message');
 if(!window.isSecureContext||!navigator.mediaDevices||!window.Html5Qrcode){msg.textContent='Cámara no disponible. Use HTTPS, autorice la cámara o cierre y busque la unidad manualmente.';return;}
 scanner=new Html5Qrcode('fuel-reader');
 starting=scanner.start({facingMode:'environment'},{fps:10,qrbox:240},async code=>{
 if(decoding||!opened)return;decoding=true;await stop();
 try{const unit=await fuelRequest(@json(route('combustible.units'))+'?qr='+encodeURIComponent(code));if(!Array.from(select.options).some(o=>o.value===String(unit.IdUnidad)))select.add(new Option(unit.Unidad,unit.IdUnidad));select.value=unit.IdUnidad;save();$('#fuel-qr').modal('hide');}catch(e){msg.textContent=e.message;}
 },()=>{}).then(()=>{if(!opened&&scanner?.isScanning)return scanner.stop();}).catch(()=>{msg.textContent='No se pudo abrir la cámara. Revise los permisos o busque la unidad manualmente.';});
 });
 $('#fuel-qr').on('hide.bs.modal',()=>{stop();});
 document.addEventListener('visibilitychange',()=>{if(document.hidden){stop();$('#fuel-qr').modal('hide');}});
 window.addEventListener('pagehide',()=>{stop();});
})();
</script>
@stop
