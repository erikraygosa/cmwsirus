{{-- resources/views/imagenes/index.blade.php --}}
@extends('adminlte::page')

@section('title', 'Capturar Imagen')

@section('content_header')
  <h1>Módulo de Imagen</h1>
@stop

@section('content')
  {{-- Alertas (éxito/errores de sesión) --}}
  @include('imagenes.partials._alerts')

  <div class="card">
    <div class="card-body">
      {{-- Selector de unidad + acciones (QR / Crear Folio) --}}
      @include('imagenes.partials._selector_unidad', ['unidades' => $unidades])

      <hr>

      {{-- Estado del documento (folio/fecha/estatus/comentario/totales) --}}
      @include('imagenes.partials._estado_folio')

      {{-- Contenedor de lados (subida y edición) --}}
      @include('imagenes.partials._lados')
    </div>
  </div>

  {{-- DataTable de Folios --}}
  @include('imagenes.partials._tabla_folios')

  {{-- Modales existentes --}}
  @include('imagenes.modals._adjuntos')
  @include('imagenes.modals._qr')

  {{-- === Modal NUEVO: verificación QR antes de subir evidencia === --}}
  <div class="modal fade" id="qrCheckModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header d-flex justify-content-between align-items-center">
          <h5 class="modal-title">Verificar unidad</h5>
          <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
        </div>
        <div class="modal-body">
          <div id="qrCheckReader" style="width:100%"></div>
          <small class="text-muted">Escanea el QR de la unidad. Debe coincidir con la unidad del folio (p. ej. “607” o “CS018”).</small>
        </div>
      </div>
    </div>
  </div>
@stop

@push('css')
  {{-- DataTables --}}
  <link rel="stylesheet" href="https://cdn.datatables.net/2.0.0/css/dataTables.dataTables.css" />
  <link rel="stylesheet" href="https://cdn.datatables.net/responsive/3.0.0/css/responsive.bootstrap4.css" />
  {{-- Estilos propios --}}
  <link rel="stylesheet" href="{{ asset('css/imagenes.css') }}">
  <style>
    /* Tarjeta simple */
    /* Overlay para acciones sobre el pane de EVIDENCIA */
     .pane .pane-actions{ position:absolute; right:.35rem; top:.35rem; display:flex; gap:.35rem; z-index:2; }

    .thumb{ position:relative; border:1px solid #e1e1e1; border-radius:6px; overflow:hidden; }
    .thumb img{ width:100%; height:160px; object-fit:cover; display:block; }
    .thumb .badge{ position:absolute; left:6px; top:6px; }
    .thumb .actions{ position:absolute; right:6px; top:6px; display:flex; gap:4px; }

    /* Layout “antes / evidencia” */
    .thumb-duo{ display:grid; grid-template-columns:1fr 1fr; gap:.5rem }
    .thumb-duo .pane{ position:relative; border:1px solid #e1e1e1; border-radius:6px; overflow:hidden; }
    .thumb-duo img{ width:100%; height:160px; object-fit:cover; display:block; }
    .thumb-duo .badge{ position:absolute; top:.35rem; left:.35rem }
    .thumb .actions{ display:flex; gap:.35rem; flex-wrap:wrap; justify-content:center; margin-top:.4rem }

    /* Editor */
    .editor-card{ border:1px solid #ddd; border-radius:.5rem; overflow:hidden; }
    .editor-toolbar{ background:#f8f9fa; padding:.5rem; display:flex; flex-wrap:wrap; gap:.5rem; align-items:center; }
    .editor-toolbar .ctrl{ display:flex; align-items:center; gap:.35rem; }
    .editor-toolbar .ctrl label{ margin:0; font-size:.75rem; color:#6c757d; min-width:72px; }
    .editor-canvas-wrap{ position:relative; width:100%; }
    .editor-canvas-wrap img{ width:100%; height:auto; display:block; }
    .editor-canvas{ position:absolute; left:0; top:0; z-index:2; touch-action:none; }

    table.dataTable thead{ background:linear-gradient(to right,#4A00E0,#3488f7); color:#fff; }
    table.dataTable th{ text-align:center; vertical-align:middle; }

    .status-inline{ width:130px!important; height:32px; padding:.25rem .5rem; font-size:.875rem; }

    .progress-wrap{ padding:.5rem .75rem .75rem; }
    .progress{ height:6px; display:none; }
    .progress.show{ display:block; }
    .progress-info{ font-size:.75rem; color:#6c757d; display:none; }
    .progress-info.show{ display:block; }

    .doc-meta > div{ margin-right:14px; margin-top:4px; }

    .modal{ z-index:1060; }
    .modal-backdrop{ z-index:1055; }
    @media (max-width:576px){
      .modal-mobile-full{ width:100%!important; max-width:100%!important; height:100%; margin:0; }
      .modal-mobile-full .modal-content{ height:100vh; border-radius:0; display:flex; flex-direction:column; }
      .modal-mobile-full .modal-header{ position:sticky; top:0; z-index:2; }
      .modal-mobile-full .modal-body{ padding:.5rem 1rem; height:calc(100vh - 120px); overflow:auto; }
      #adjuntosWrap > .col{ flex:0 0 100%; max-width:100%; }
      .modal{ z-index:2000; }
      .modal-backdrop{ z-index:1990; }
    }

    /* SweetAlert2 por encima de los modales */
    .swal2-container{ z-index:3005 !important; }
  </style>
@endpush

@push('js')
  {{-- Libs --}}
  <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://cdn.datatables.net/2.0.0/js/dataTables.js"></script>
  <script src="https://cdn.datatables.net/responsive/3.0.0/js/dataTables.responsive.js"></script>
  <script src="https://cdn.datatables.net/responsive/3.0.0/js/responsive.bootstrap4.js"></script>

<script>
  const RAW = @json($perms ?: new \stdClass);

  const S = v => String(v || '').trim().toUpperCase() === 'S';

  window.USER_PERMS = {
    subirImagenes   : typeof RAW.subirImagenes   === 'boolean' ? RAW.subirImagenes   : S(RAW.SubirFotosFolImg),
    borrarImagenes  : typeof RAW.borrarImagenes  === 'boolean' ? RAW.borrarImagenes  : S(RAW.EliminarFotosFolImg),
    subirIEvidencia : typeof RAW.subirIEvidencia === 'boolean' ? RAW.subirIEvidencia : S(RAW.SubirFotosEviFolImg),
    borrarEvidencia : typeof RAW.borrarEvidencia === 'boolean' ? RAW.borrarEvidencia : S(RAW.EliminarFotosEviFolImg),
  };

  console.log('USER_PERMS', window.USER_PERMS);
</script>

<script>
(function(){
  const P = window.USER_PERMS || {};
  const $slc = document.getElementById('slcUnidad');
  const $btnCrear = document.getElementById('btnCrearDoc');
  const $btnLeerQR = document.getElementById('btnLeerQR');
  const $btnDeshacer = document.getElementById('btnDeshacerUnidad');

  const cardFolios  = document.getElementById('cardFolios');
  const estadoDoc   = document.getElementById('estadoDoc');
  const docFolio    = document.getElementById('docFolio');
  const docFecha    = document.getElementById('docFecha');
  const docUnidad   = document.getElementById('docUnidad');
  const docEstatus  = document.getElementById('docEstatus');
  const docTotales  = document.getElementById('docTotales');
  const sugerirCierre = document.getElementById('sugerirCierre');
  const frmCerrar   = document.getElementById('frmCerrar');
  const bannerSinDoc= document.getElementById('bannerSinDoc');

  const docComentarioWrap  = document.getElementById('docComentarioWrap');
  const docComentarioTexto = document.getElementById('docComentarioTexto');
  const docComentarioInp   = document.getElementById('docComentario');
  const docComentarioLbl   = document.getElementById('docComentarioLabel');
  const btnGuardarDocCmt   = document.getElementById('btnGuardarDocComentario');

  $('#adjuntosModal').appendTo('body');

  function escapeHtml(s){ return (s||'').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])); }
  function addNoCache(url){
    if(!url) return url;
    const sep = url.includes('?') ? '&' : '?';
    return url + sep + 'v=' + Date.now();
  }
  const warn = (title,text='')=>Swal.fire({icon:'warning',title,text});
  const info = (title,text='')=>Swal.fire({icon:'info',title,text});
  const ok   = (title,text='')=>Swal.fire({icon:'success',title,text,timer:1200,showConfirmButton:false});

  /* ==================== DataTable ==================== */
  let foliosDT;
  const selFiltro = document.getElementById('filtroEstatus');
  function sumaAdjuntos(t){
    const keys = ['Del','Tra','Izq','Der','EspIzq','EspDer'];
    return keys.reduce((acc,k)=> acc + (t?.[k]?.[0]||0), 0);
  }
  function initFoliosDT(){
    if (foliosDT) {
      foliosDT.destroy();
      document.querySelector('#tblFolios tbody').innerHTML = '';
    }
    foliosDT = new DataTable('#tblFolios', {
      responsive: true,
      autoWidth: false,
      processing: true,
      serverSide: true,
      order: [[1,'desc']],
      ajax: {
        url: `{{ route('imagenes.dt') }}`,
        data: d => { d.estatus = selFiltro.value || 'ABIERTO'; }
      },
      columns: [
        { data:'Fecha' },
        { data:'Folio' },
        { data:'Unidad' },
        { data:'Comentarios', render: d => d ? `<span class="text-truncate d-inline-block" style="max-width:240px" title="${escapeHtml(d)}">${escapeHtml(d)}</span>` : '' },
        { data:'Estatus', render: d => {
            const cls = d==='ABIERTO' ? 'badge-warning'
                     : d==='EN PROCESO' ? 'badge-info'
                     : d==='CERRADO' ? 'badge-success' : 'badge-secondary';
            return `<span class="badge ${cls}">${d}</span>`;
        }},
        { data:null, orderable:false, searchable:false, render: row => {
            const t=row.totales||{}; const fmt=k=>(t[k]?`${t[k][0]}/${t[k][1]}`:'0/0');
            return `<div class="small text-muted">Del ${fmt('Del')} · Tra ${fmt('Tra')} · Izq ${fmt('Izq')} · Der ${fmt('Der')} · EspIzq ${fmt('EspIzq')} · EspDer ${fmt('EspDer')}</div>`;
        }},
        { data:null, orderable:false, searchable:false, render: row => {
            const options = `
              <option value="ABIERTO" ${row.Estatus==='ABIERTO'?'selected':''}>ABIERTO</option>
              <option value="CERRADO" ${row.Estatus==='CERRADO'?'selected':''}>CERRADO</option>`;
            const btnEditCmt = row.Estatus==='ABIERTO'
              ? `<button class="btn btn-sm btn-secondary" data-edit-folio="${row.Folio}" data-cmt="${escapeHtml(row.Comentarios||'')}" title="Editar comentario"><i class="fa fa-edit"></i></button>`
              : '';
            const btnGoEdit = `<button class="btn btn-sm btn-outline-primary" title="Editar folio" data-goto="${row.Unidad}" data-gotoid="${row.Folio}"><i class="fa fa-folder-open"></i></button>`;
            /* >>> Cambio: pasamos la unidad visible y el Id de unidad para usar en QR */
            const btnVer = `<button class="btn btn-sm btn-primary" data-ver="${row.Folio}" data-u="${escapeHtml(row.Unidad||'')}" data-uid="${row.IdUnidad||''}" title="Ver imágenes"><i class="fa fa-images"></i></button>`;
            return `<div class="d-flex align-items-center gap-2 justify-content-center">
              ${btnVer}
              ${btnEditCmt}${btnGoEdit}
              <select class="form-control form-control-sm status-inline" data-set="${row.Folio}" data-current="${row.Estatus}">${options}</select>
            </div>`;
        }}
      ],
      language: {
        sProcessing:"Procesando...", sLengthMenu:"Mostrar _MENU_", sZeroRecords:"Sin resultados",
        sEmptyTable:"Ningún dato disponible", sInfo:"Mostrando _START_ a _END_ de _TOTAL_",
        sInfoEmpty:"Mostrando 0 a 0 de 0", sInfoFiltered:"(filtrado de _MAX_)", sSearch:"Buscar:",
        oPaginate:{ sFirst:"Primero", sLast:"Último", sNext:"Siguiente", sPrevious:"Anterior" }
      }
    });
  }

  selFiltro.addEventListener('change', ()=> foliosDT.ajax.reload());

  async function setEstatusFolio(folio, estatus){
    const r = await fetch(`{{ route('imagenes.folio.estatus', ['folio'=>'__F__']) }}`.replace('__F__', folio), {
      method:'PATCH',
      headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
      body: JSON.stringify({ estatus })
    });
    let j={}; try{ j=await r.json(); }catch(_){}
    if(!r.ok || !j.ok){ const err=new Error(j.message||j.error||`HTTP ${r.status}`); err.type=j.type||'warning'; throw err; }
    foliosDT.ajax.reload(null,false);
    return j;
  }

  /* >>> Cambio: almacenar unidad esperada al abrir desde DT y limpiarla al cerrar */
  let modalUnidadExpected = null;
  $('#adjuntosModal').on('hidden.bs.modal', function(){
    modalUnidadExpected = null;
  });

  // Ver imágenes
  document.addEventListener('click', (ev)=>{
    const btn = ev.target.closest('[data-ver]');
    if(!btn) return;
    /* >>> Cambio: guardar unidad (texto e id) para la verificación QR */
    modalUnidadExpected = {
      uText: (btn.dataset.u || '').trim(),
      uId  : (btn.dataset.uid || '').trim()
    };
    abrirAdjuntos(btn.getAttribute('data-ver'));
  });

  // Ir a edición del folio
  document.addEventListener('click', (ev)=>{
    const btn = ev.target.closest('[data-goto]'); if(!btn) return;
    const targetText = (btn.getAttribute('data-goto')||'').toLowerCase();
    let selected=false;
    for(const opt of $slc.options){
      if(opt.text.toLowerCase().includes(targetText)){ $slc.value=opt.value; selected=true; break; }
    }
    if(!selected) return warn('No se encontró la unidad del folio');
    cardFolios.classList.add('d-none');
    document.getElementById('contenedorLados').classList.remove('d-none');
    $btnDeshacer.classList.remove('d-none');
    $btnCrear.disabled=true; $btnLeerQR.disabled=true; $slc.disabled=true;
    cargarUnidad($slc.value);
  });

  // Editar comentario folio (inline en DT)
  document.addEventListener('click', async (ev)=>{
    const btn = ev.target.closest('[data-edit-folio]'); if(!btn) return;
    const folio = btn.getAttribute('data-edit-folio');
    const current = btn.getAttribute('data-cmt') || '';
    const resp = await Swal.fire({title:`Editar comentario (Folio ${folio})`, input:'textarea', inputValue:current, inputLabel:'Comentario', showCancelButton:true, confirmButtonText:'Guardar', cancelButtonText:'Cancelar'});
    if(!resp.isConfirmed) return;
    try{
      const j = await guardarComentarioFolio(folio, (resp.value||'').trim());
      if(!j.ok) throw new Error(j.error || 'No se pudo guardar');
      ok('Comentario actualizado');
      foliosDT?.ajax?.reload(null,false);
      if (docFolio.textContent === String(folio)) await cargarUnidad($slc.value);
    }catch(e){ Swal.fire({icon:'error', title:'Error', text:e.message}); }
  });

  // Cambiar estatus folio
  document.addEventListener('change', async (ev)=>{
    const sel = ev.target.closest('select.status-inline'); if(!sel) return;
    const folio=sel.getAttribute('data-set'); const before=sel.getAttribute('data-current'); const current=sel.value;
    if(current===before) return;
    try{
      sel.disabled=true; await setEstatusFolio(folio,current); sel.setAttribute('data-current',current); ok('Estatus actualizado');
    }catch(err){
      const icon = err.type==='danger'?'error': err.type==='warning'?'warning':'error';
      const swalOpts = {icon, text:err.message||'No se pudo actualizar', confirmButtonText:'OK', buttonsStyling:false,
        customClass:{confirmButton: err.type==='danger'?'btn btn-danger': err.type==='warning'?'btn btn-warning':'btn btn-primary'}};
      if(err.type==='danger') swalOpts.title='Operación no permitida';
      Swal.fire(swalOpts); sel.value=before;
    }finally{ sel.disabled=false; }
  });

  /* --------- Galería (modal) ---------- */
  const selAdjEstatus = document.getElementById('filtroAdjEstatus');
  if (selAdjEstatus && selAdjEstatus.options.length === 0) {
    selAdjEstatus.innerHTML = `
      <option value="PENDIENTE">PENDIENTE</option>
      <option value="ATENDIDO">ATENDIDO</option>
      <option value="DOCUMENTADO">DOCUMENTADO</option>
      <option value="CONFIRMADO">CONFIRMADO</option>
      <option value="TODOS">TODOS</option>`;
  }
  selAdjEstatus?.addEventListener('change', ()=>{
    const fol = document.getElementById('adjFolio').textContent || '';
    if(fol) cargarAdjuntosEnModal(fol);
  });

  async function abrirAdjuntos(folio){
    document.getElementById('adjFolio').textContent = folio;
    if (selAdjEstatus) selAdjEstatus.value = 'PENDIENTE';
    const $m = $('#adjuntosModal'); if(!$m.parent().is('body')) $m.appendTo('body');
    $m.modal({ backdrop:true, keyboard:true, show:true });
    setTimeout(()=> $m.modal('handleUpdate'), 60);
    await cargarAdjuntosEnModal(folio);
  }

  // Tarjeta (isGrid=true en panel superior, false en modal)
  function buildThumbCard({item, isGrid}){
  const isImagen = ['TblImagenDel','TblImagenTra','TblImagenIzq','TblImagenDer','TblImagenEspIzq','TblImagenEspDer'].includes(item.Tabla);
  const preferred = item.Url;

  // Evidencia ligada
  const ev = item.Evidencia || null;
  const hasEv = !!(ev && ev.Url);
  const evUrl = hasEv ? ev.Url : null;

  const isPendiente   = item.Estatus==='PENDIENTE';
  const isAtendido    = item.Estatus==='ATENDIDO';
  const isDocumentado = item.Estatus==='DOCUMENTADO';
  const isConfirmado  = item.Estatus==='CONFIRMADO';

  const canUploadEv = isImagen && P.subirIEvidencia && !hasEv && isAtendido;   // solo ATENDIDO
  const canDeleteEv = isImagen && P.borrarEvidencia && hasEv && isDocumentado; // eliminar SOLO cuando hay evidencia y está DOCUMENTADO
  const canConfirm  = isImagen && isDocumentado;
  const canUnconfirm= isImagen && isConfirmado; // revertir a DOCUMENTADO
  const canDelete   = isImagen && P.borrarImagenes && isPendiente && !isConfirmado;
  const cmtEditable = isImagen && isPendiente;
  const canMarkup   = isGrid && isImagen && isPendiente && P.subirImagenes;    // SOLO en grid

  // Badges
  let mainBadgeClass='badge-secondary', mainBadgeText=item.Estatus;
  if(isPendiente) mainBadgeClass='badge-warning';
  else if(isAtendido) mainBadgeClass='badge-info';
  else if(isDocumentado) mainBadgeClass='badge-primary';
  else if(isConfirmado) mainBadgeClass='badge-success';

  const comentario = escapeHtml(item.Comentarios||'');

  // Contenido: cuando hay evidencia, el botón de eliminar vive SOBRE el pane derecho (EVIDENCIA)
  const content = hasEv ? `
    <div class="thumb-duo">
      <div class="pane">
        <a href="${encodeURI(preferred)}" target="_blank" rel="noopener"><img src="${encodeURI(addNoCache(preferred))}" alt=""></a>
        <span class="badge ${mainBadgeClass}">${mainBadgeText}</span>
      </div>
      <div class="pane">
        <a href="${encodeURI(evUrl)}" target="_blank" rel="noopener"><img src="${encodeURI(addNoCache(evUrl))}" alt=""></a>
        <span class="badge badge-dark">EVIDENCIA</span>
        ${canDeleteEv ? `
          <div class="pane-actions">
            <button class="btn btn-xs btn-outline-danger" data-ev-delete="${item.Id}" title="Eliminar evidencia (volverá a ATENDIDO)">
              <i class="fa fa-trash"></i>
            </button>
          </div>` : ''}
      </div>
    </div>
  ` : `
    <div class="thumb">
      <a href="${encodeURI(preferred)}" target="_blank" rel="noopener"><img src="${encodeURI(addNoCache(preferred))}" alt=""></a>
      <span class="badge ${mainBadgeClass}">${mainBadgeText}</span>
    </div>
  `;

  const where = isGrid ? 'local' : 'modal';
  const actions = `
    <div class="actions">
      ${canConfirm?`<button class="btn btn-xs btn-success" data-confirm="${isGrid?'':'modal-'}${item.Id}" title="Confirmar evidencia">✓</button>`:''}
      ${canUnconfirm?`<button class="btn btn-xs btn-warning" data-unconfirm="${isGrid?'':'modal-'}${item.Id}" title="↩ Revertir a DOCUMENTADO">↩</button>`:''}
      ${canDelete?`<button class="btn btn-xs btn-danger" data-delete="${isGrid?'':'modal-'}${item.Id}" title="Eliminar">✕</button>`:''}
      ${canMarkup?`<button class="btn btn-xs btn-outline-primary"
            data-markup-local="${item.Id}"
            data-img="${encodeURI(preferred)}"
            data-tabla="${item.Tabla}"
            data-cmt="${escapeHtml(item.Comentarios||'')}"
            title="Editar marcas"><i class="fa fa-pen"></i></button>`:''}
      ${canUploadEv?`<button class="btn btn-xs btn-outline-primary" data-ev-upload="${item.Id}" title="Subir evidencia"><i class="fa fa-paperclip"></i></button>`:''}
      ${(cmtEditable && !isGrid) ? `<button class="btn btn-xs btn-outline-secondary" data-editcmt-${where}="${item.Id}" title="Editar comentario"><i class="fa fa-comment"></i></button>` : ''}
    </div>
  `; // ← OJO: aquí ya NO se pinta el botón de eliminar evidencia

  const commentRow = cmtEditable ? `
    <div class="input-group input-group-sm">
      <input class="form-control form-control-sm" value="${comentario}" placeholder="Descripción del daño" data-cmt-${isGrid?'local':'m'}="${item.Id}">
      <div class="input-group-append"><button class="btn btn-outline-primary" data-savecmt-${isGrid?'local':'m'}="${item.Id}">Guardar</button></div>
    </div>` : `<div class="small ${isConfirmado?'text-body':'text-muted'}"><i class="fa fa-comment mr-1"></i>${comentario || '<em>Sin comentario</em>'}</div>`;

  return `
    <div class="col">
      ${content}
      ${actions}
      <div class="mt-1">${commentRow}</div>
    </div>
  `;
}


  async function cargarAdjuntosEnModal(folio){
    const wrap = document.getElementById('adjuntosWrap');
    wrap.innerHTML = '<div class="col-12 text-muted">Cargando...</div>';
    try{
      const est0 = selAdjEstatus?.value || 'PENDIENTE';
      const est  = est0 === 'TODOS' ? '' : est0;
      const url = `{{ route('imagenes.ajax.adjuntosList', ['folio'=>'__F__']) }}`.replace('__F__', folio) + `?estatus=${encodeURIComponent(est)}`;
      const r = await fetch(url, { headers:{'Accept':'application/json'} });
      const j = await r.json();
      if(!j.ok) throw new Error(j.error||'No se pudo obtener adjuntos');

      wrap.innerHTML = '';
      const list = (j.data || []).filter(it => it.Estatus !== 'EVIDENCIA');
      if(!list.length){ wrap.innerHTML = '<div class="col-12 text-center text-muted">Sin adjuntos con ese estatus.</div>'; return; }

      list.forEach(item=>{
        const col = document.createElement('div');
        col.className = 'col';
        col.innerHTML = buildThumbCard({item, isGrid:false});
        wrap.appendChild(col);
      });

      // Confirmar
     

      // Revertir CONFIRMADO -> DOCUMENTADO
      wrap.querySelectorAll('[data-ev-upload]').forEach(btn=>{
  btn.onclick = async ()=>{
    if(!P.subirIEvidencia) return warn('Sin permiso','No puedes subir evidencia.');
    const expected = getExpectedUnidad();
    if(!expected.uText && !expected.uId){ return warn('Folio sin unidad','No se pudo determinar la unidad del folio.'); }

    try { await requireQRForUnidad(expected); }
    catch(_) { return; } // cancelado o inválido

    // --- NUEVO: mantener user activation
    const okPick = await Swal.fire({
      icon:'info',
      title:'Evidencia validada',
      text:'Ahora elige el archivo de evidencia.',
      confirmButtonText:'Elegir archivo',
      showCancelButton:true
    });
    if(!okPick.isConfirmed) return;

    const id = btn.getAttribute('data-ev-upload');
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'application/pdf,image/*';
    input.onchange = async ()=>{
      const f = input.files?.[0]; if(!f) return;
      const fd = new FormData(); fd.append('file', f);
      try{
        const r = await fetch(`{{ url('imagenes/adjuntos') }}/${id}/evidencia`, { method:'POST', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}, body: fd });
        const j = await r.json(); if(!j.ok) throw new Error(j.error||'No se pudo subir la evidencia');
        ok('Evidencia subida');
        const fol = document.getElementById('adjFolio').textContent || '';
        if(fol) await cargarAdjuntosEnModal(fol);
        if($slc.value) await cargarUnidad($slc.value);
      }catch(e){ Swal.fire({icon:'error', title:'Error', text:e.message}); }
    };
    input.click(); // clic gatillado desde el botón de SweetAlert → conserva activación de usuario
  };
});


      // Eliminar adjunto
      wrap.querySelectorAll('[data-delete^="modal-"]').forEach(btn=>{
        btn.onclick = async ()=>{
          const id = btn.getAttribute('data-delete').replace('modal-','');
          const conf = await Swal.fire({icon:'question', title:'¿Eliminar adjunto?', showCancelButton:true, confirmButtonText:'Sí, eliminar'});
          if(!conf.isConfirmed) return;
          try{
            const r = await fetch(`{{ url('imagenes/adjuntos') }}/${id}`, { method:'DELETE', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}});
            const j2 = await r.json(); if(!j2.ok) throw new Error(j2.error||'Error');
            await cargarAdjuntosEnModal(folio); ok('Adjunto eliminado'); foliosDT?.ajax?.reload(null,false);
            if($slc.value) await cargarUnidad($slc.value);
          }catch(e){ Swal.fire({icon:'error', title:'Error', text:e.message}); }
        };
      });

      /* ====== SUBIR EVIDENCIA (MODAL) con verificación QR previa ====== */
      document.querySelectorAll('[data-ev-upload]').forEach(btn=>{
  btn.onclick = async ()=>{
    if(!P.subirIEvidencia) return warn('Sin permiso','No puedes subir evidencia.');
    const expected = getExpectedUnidad();
    if(!expected.uText && !expected.uId){ return warn('Folio sin unidad','No se pudo determinar la unidad del folio.'); }

    try { await requireQRForUnidad(expected); }
    catch(_) { return; }

    const okPick = await Swal.fire({
      icon:'info',
      title:'Evidencia validada',
      text:'Ahora elige el archivo de evidencia.',
      confirmButtonText:'Elegir archivo',
      showCancelButton:true
    });
    if(!okPick.isConfirmed) return;

    const id = btn.getAttribute('data-ev-upload');
    const input = document.createElement('input');
    input.type = 'file';
    input.accept = 'application/pdf,image/*';
    input.onchange = async ()=>{
      const f = input.files?.[0]; if(!f) return;
      const fd = new FormData(); fd.append('file', f);
      try{
        const r = await fetch(`{{ url('imagenes/adjuntos') }}/${id}/evidencia`, { method:'POST', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}, body: fd });
        const j = await r.json(); if(!j.ok) throw new Error(j.error||'No se pudo subir la evidencia');
        ok('Evidencia subida'); await cargarUnidad($slc.value);
        const fol = (document.getElementById('adjFolio')?.textContent||'').trim();
        if($('#adjuntosModal').hasClass('show') && fol){ try{ await cargarAdjuntosEnModal(fol); }catch(_){ } }
      }catch(e){ Swal.fire({icon:'error', title:'Error', text:e.message}); }
    };
    input.click();
  };
});

      // Evidencia: eliminar (regresa a ATENDIDO)
      wrap.querySelectorAll('[data-ev-delete]').forEach(btn=>{
        btn.onclick = async ()=>{
          const id = btn.getAttribute('data-ev-delete'); // id de la IMAGEN
          const conf = await Swal.fire({icon:'question', title:'¿Eliminar evidencia?', text:'El adjunto regresará a ATENDIDO.', showCancelButton:true, confirmButtonText:'Sí, eliminar'});
          if(!conf.isConfirmed) return;
          try{
            const r = await fetch(`{{ url('imagenes/adjuntos') }}/${id}/evidencia`, { method:'DELETE', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}});
            const j = await r.json(); if(!j.ok) throw new Error(j.error||'No se pudo eliminar la evidencia');
            ok('Evidencia eliminada');
            const fol = document.getElementById('adjFolio').textContent || '';
            if(fol) await cargarAdjuntosEnModal(fol);
            if($slc.value) await cargarUnidad($slc.value);
          }catch(e){ Swal.fire({icon:'error', title:'Error', text:e.message}); }
        };
      });

      // Comentarios (sin cerrar modal)
      wrap.querySelectorAll('[data-editcmt-modal]').forEach(btn=>{
        btn.onclick = ()=>{
          const id = btn.getAttribute('data-editcmt-modal');
          const inp = wrap.querySelector(`[data-cmtm="${id}"]`);
          const sv  = wrap.querySelector(`[data-savecmtm="${id}"]`);
          if(inp){ inp.removeAttribute('disabled'); inp.focus(); inp.select(); }
          if(sv) sv.disabled = false;
        };
      });
      wrap.querySelectorAll('[data-savecmtm]').forEach(btn=>{
        btn.onclick = async ()=>{
          const id = btn.getAttribute('data-savecmtm');
          const inp = wrap.querySelector(`[data-cmtm="${id}"]`);
          const val = (inp?.value||'').trim();
          if(!val) return warn('Falta comentario','Agrega un comentario.');
          try{
            const r = await fetch(`{{ url('imagenes/adjuntos') }}/${id}/comentario`, {
              method:'PATCH',
              headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
              body: JSON.stringify({ comentarios: val })
            });
            const j2 = await r.json(); if(!j2.ok) throw new Error(j2.error||'Error');
            ok('Comentario actualizado');
          }catch(e){ Swal.fire({icon:'error', title:'Error', text:e.message}); }
        };
      });

    }catch(e){
      Swal.fire({icon:'error', title:'Error', text: e.message||'No se pudo abrir la galería'});
    }
  }

  /* ==================== Captura (panel superior) ==================== */
  let estadoActualDoc = null;

  function applyUnidadUI(){
    if ($slc.value) {
      cardFolios.classList.add('d-none');
      document.getElementById('contenedorLados').classList.remove('d-none');
      estadoDoc.classList.add('d-none');
      bannerSinDoc.classList.remove('d-none');
      limpiarGrids();
      document.querySelectorAll('[data-choose]').forEach(b=> b.style.display = P.subirImagenes ? '' : 'none');
      $btnCrear.disabled=false; $btnLeerQR.disabled=false; $slc.disabled=false; $btnDeshacer.classList.remove('d-none');
    } else {
      resetSeleccionUnidadUI();
    }
  }
  function resetSeleccionUnidadUI(){
    cardFolios.classList.remove('d-none');
    document.getElementById('contenedorLados').classList.add('d-none');
    estadoDoc.classList.add('d-none');
    bannerSinDoc.classList.add('d-none');
    limpiarGrids();
    $btnDeshacer.classList.add('d-none');
    $btnLeerQR.disabled=false; $btnCrear.disabled=true; $slc.disabled=false; $slc.value='';
  }
  $slc.addEventListener('change', applyUnidadUI);

  $btnDeshacer.addEventListener('click', async ()=>{
    resetSeleccionUnidadUI();
    ok('Selección finalizada');
  });

  // Crear Folio
  $btnCrear.addEventListener('click', async ()=>{
    if(!$slc.value) return warn('Selecciona una unidad');
    const conf = await Swal.fire({icon:'question', title:'¿Crear Folio?', text:'Se abrirá un folio ABIERTO para la unidad seleccionada.', showCancelButton:true, confirmButtonText:'Sí, crear'});
    if(!conf.isConfirmed) return;
    $btnCrear.disabled = true;
    try{
      const res = await fetch(`{{ route('imagenes.ajax.crearAbierto') }}`, {
        method:'POST',
        headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
        body: JSON.stringify({ IdUnidad: $slc.value })
      });
      const j = await res.json();
      if(!j.ok) throw new Error(j.error||'No se pudo crear el folio');
      await cargarUnidad($slc.value);
      ok('Folio creado');
      $btnCrear.disabled = true; $btnLeerQR.disabled = true; $slc.disabled = true;
      foliosDT?.ajax?.reload(null,false);
    }catch(e){
      Swal.fire({icon:'error', title:'Error', text: e.message || 'No se pudo crear el folio'});
      $btnCrear.disabled = false;
    }
  });

  async function guardarComentarioFolio(folio, comentarios){
    const r = await fetch(`{{ url('imagenes/folio') }}/${folio}/comentario`, {
      method:'PATCH',
      headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
      body: JSON.stringify({ comentarios })
    });
    return await r.json();
  }

  document.getElementById('btnGuardarDocComentario')?.addEventListener('click', async ()=>{
    const folio = docFolio.textContent;
    const val = (docComentarioInp.value || '').trim();
    if(!folio) return;
    if(!val) return warn('Falta comentario','Escribe un comentario del folio.');
    try{
      btnGuardarDocCmt.disabled = true;
      const j = await guardarComentarioFolio(folio, val);
      if(!j.ok) throw new Error(j.error || 'No se pudo guardar');
      ok('Comentario guardado');
      foliosDT?.ajax?.reload(null,false);
      await cargarUnidad($slc.value);
    }catch(e){ Swal.fire({icon:'error', title:'Error', text:e.message}); }
    finally{ btnGuardarDocCmt.disabled = false; }
  });

  async function cargarUnidad(idUnidad){
    const url = `{{ url('imagenes/ajax/estado-unidad') }}/${idUnidad}`;
    estadoDoc.classList.add('d-none');
    bannerSinDoc.classList.add('d-none');

    try{
      const r = await fetch(url, {headers:{'Accept':'application/json'}});
      if(!r.ok) throw new Error('HTTP '+r.status);
      const j = await r.json();

      limpiarGrids();

      if(!j.doc){
        bannerSinDoc.classList.remove('d-none');
        estadoActualDoc = null;
        $btnCrear.disabled = false; $btnLeerQR.disabled = false; $slc.disabled = false; $btnDeshacer.classList.remove('d-none');
        return;
      }

      estadoActualDoc = j.doc;

      estadoDoc.classList.remove('d-none');
      docFolio.textContent  = j.doc.Folio;
      docFecha.textContent  = j.doc.Fecha;
      docUnidad.textContent = j.doc.Unidad || '';
      docEstatus.textContent = j.doc.Estatus;
      docEstatus.className = 'badge ' + (j.doc.Estatus==='ABIERTO' ? 'badge-warning' : (j.doc.Estatus==='EN PROCESO' ? 'badge-info' : (j.doc.Estatus==='CERRADO' ? 'badge-success' : 'badge-secondary')));

      docComentarioInp.value = j.doc.Comentarios || '';
      docComentarioLbl.textContent = j.doc.Comentarios || '(sin comentario)';
      if (j.doc.Estatus === 'ABIERTO') { docComentarioWrap.classList.remove('d-none'); docComentarioTexto.classList.add('d-none'); }
      else { docComentarioWrap.classList.add('d-none'); docComentarioTexto.classList.remove('d-none'); }

      docTotales.textContent =
        `Del ${j.doc.totales.Del[0]}/${j.doc.totales.Del[1]} · `+
        `Tra ${j.doc.totales.Tra[0]}/${j.doc.totales.Tra[1]} · `+
        `Izq ${j.doc.totales.Izq[0]}/${j.doc.totales.Izq[1]} · `+
        `Der ${j.doc.totales.Der[0]}/${j.doc.totales.Der[1]} · `+
        `EspIzq ${j.doc.totales.EspIzq[0]}/${j.doc.totales.EspIzq[1]} · `+
        `EspDer ${j.doc.totales.EspDer[0]}/${j.doc.totales.EspDer[1]}`;

      frmCerrar.action = `{{ url('imagenes/cerrar') }}/${j.doc.Folio}`;
      if(j.doc.puede_cerrar && j.doc.Estatus!=='CERRADO') sugerirCierre.classList.remove('d-none'); else sugerirCierre.classList.add('d-none');

      const lados = ['TblImagenDel','TblImagenTra','TblImagenIzq','TblImagenDer','TblImagenEspIzq','TblImagenEspDer'];
      lados.forEach(k=>{
        const grid = document.getElementById('grid_'+k);
        grid.innerHTML = '';
        const arr = (j.adjuntos && j.adjuntos[k]) ? j.adjuntos[k] : [];
        arr.filter(it => it.Estatus !== 'EVIDENCIA').forEach(item=>{
          const col = document.createElement('div');
          col.className = 'col';
          col.innerHTML = buildThumbCard({item, isGrid:true});
          grid.appendChild(col);
        });
      });

      // ==== Binds GRID ====
      document.querySelectorAll('[data-confirm]:not([data-confirm^="modal-"])').forEach(btn=>{
        btn.onclick = async ()=>{
          const id = btn.getAttribute('data-confirm');
          const conf = await Swal.fire({icon:'question', title:'¿Confirmar evidencia?', text:'El adjunto pasará a CONFIRMADO.', showCancelButton:true, confirmButtonText:'Sí, confirmar'});
          if(!conf.isConfirmed) return;
          try{
            const r = await fetch(`{{ url('imagenes/adjuntos') }}/${id}/confirmar`, { method:'PATCH', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}});
            const j2 = await r.json(); if(!j2.ok) throw new Error(j2.error||'Error');
            await cargarUnidad($slc.value); foliosDT.ajax.reload(null,false); ok('Adjunto confirmado');
          }catch(e){ Swal.fire({icon:'error', title:'Error', text:e.message}); }
        };
      });

      // REVERTIR (grid) -> DOCUMENTADO
      document.querySelectorAll('[data-unconfirm]:not([data-unconfirm^="modal-"])').forEach(btn=>{
        btn.onclick = async ()=>{
          const id = btn.getAttribute('data-unconfirm');
          const conf = await Swal.fire({icon:'question', title:'Revertir a DOCUMENTADO', text:'El adjunto volverá a DOCUMENTADO.', showCancelButton:true, confirmButtonText:'Sí, revertir'});
          if(!conf.isConfirmed) return;
          try{
            const r = await fetch(`{{ url('imagenes/adjuntos') }}/${id}/revertir`, { method:'PATCH', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}});
            const j2 = await r.json(); if(!j2.ok) throw new Error(j2.error||'Error');
            await cargarUnidad($slc.value); foliosDT.ajax.reload(null,false); ok('Adjunto revertido');
          }catch(e){ Swal.fire({icon:'error', title:'Error', text:e.message}); }
        };
      });

      document.querySelectorAll('[data-delete]:not([data-delete^="modal-"])').forEach(btn=>{
        btn.onclick = async ()=>{
          const conf = await Swal.fire({icon:'question', title:'¿Eliminar adjunto?', showCancelButton:true, confirmButtonText:'Sí, eliminar'});
          if(!conf.isConfirmed) return;
          const id = btn.getAttribute('data-delete');
          try{
            const r = await fetch(`{{ url('imagenes/adjuntos') }}/${id}`, { method:'DELETE', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}});
            const j2 = await r.json(); if(!j2.ok) throw new Error(j2.error||'Error');
            await cargarUnidad($slc.value); foliosDT.ajax.reload(null,false); ok('Adjunto eliminado');
          }catch(e){ Swal.fire({icon:'error', title:'Error', text:e.message}); }
        };
      });

      // Editar comentario (solo PENDIENTE) - input bajo la tarjeta
      document.querySelectorAll('[data-editcmt-local]').forEach(btn=>{
        btn.onclick = ()=>{
          const id = btn.getAttribute('data-editcmt-local');
          const inp = document.querySelector(`[data-cmt-local="${id}"]`);
          const sv  = document.querySelector(`[data-savecmt-local="${id}"]`);
          if(!inp) return; inp.removeAttribute('disabled'); sv&&(sv.disabled=false); inp.focus(); inp.select();
        };
      });
      document.querySelectorAll('[data-savecmt-local]').forEach(btn=>{
        btn.onclick = async ()=>{
          const id = btn.getAttribute('data-savecmt-local');
          const inp = document.querySelector(`[data-cmt-local="${id}"]`);
          const val = (inp?.value||'').trim();
          if(!val) return warn('Falta comentario','Agrega un comentario.');
          try{
            const r = await fetch(`{{ url('imagenes/adjuntos') }}/${id}/comentario`, { method:'PATCH', headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'}, body: JSON.stringify({ comentarios: val })});
            const j2 = await r.json(); if(!j2.ok) throw new Error(j2.error||'Error'); ok('Comentario actualizado');
          }catch(e){ Swal.fire({icon:'error', title:'Error', text:e.message}); }
        };
      });

      // === Abrir editor de marcas para una IMAGEN EXISTENTE (grid) ===
      document.querySelectorAll('[data-markup-local]').forEach(btn=>{
        btn.onclick = ()=>{
          const idAdj   = btn.getAttribute('data-markup-local');
          const imgUrl  = btn.getAttribute('data-img');
          const tabla   = btn.getAttribute('data-tabla');         // p.ej. TblImagenDel
          const coment  = btn.getAttribute('data-cmt') || '';
          const wrap    = document.getElementById('edit_'+tabla); // contenedor del editor de ese lado
          if(!wrap) return;
          createMarkupEditorExisting({
            container: wrap,
            tabla_lado: tabla,
            idAdjunto: idAdj,
            imageUrl: imgUrl,
            initialComment: coment
          });
        };
      });

      /* ====== SUBIR EVIDENCIA (GRID) con verificación QR previa ====== */
      document.querySelectorAll('[data-ev-upload]').forEach(btn=>{
        btn.onclick = async ()=>{
          if(!P.subirIEvidencia) return warn('Sin permiso','No puedes subir evidencia.');
          const expected = getExpectedUnidad(); // ← unidad esperada (texto y/o id)
          if(!expected.uText && !expected.uId){ return warn('Folio sin unidad','No se pudo determinar la unidad del folio.'); }

          try { await requireQRForUnidad(expected); }
          catch(_) { return; } // cancelado o inválido

          const id = btn.getAttribute('data-ev-upload');
          const input = document.createElement('input');
          input.type = 'file';
          input.accept = 'application/pdf,image/*';
          input.onchange = async ()=>{
            const f = input.files?.[0]; if(!f) return;
            const fd = new FormData(); fd.append('file', f);
            try{
              const r = await fetch(`{{ url('imagenes/adjuntos') }}/${id}/evidencia`, { method:'POST', headers:{'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json'}, body: fd });
              const j = await r.json(); if(!j.ok) throw new Error(j.error||'No se pudo subir la evidencia');
              ok('Evidencia subida'); await cargarUnidad($slc.value);
              const fol = (document.getElementById('adjFolio')?.textContent||'').trim();
              if($('#adjuntosModal').hasClass('show') && fol){ try{ await cargarAdjuntosEnModal(fol); }catch(_){ } }
            }catch(e){ Swal.fire({icon:'error', title:'Error', text:e.message}); }
          };
          input.click();
        };
      });

      // Eliminar evidencia (grid)
      document.querySelectorAll('[data-ev-delete]').forEach(btn=>{
        btn.onclick = async ()=>{
          const id = btn.getAttribute('data-ev-delete'); // id de la IMAGEN
          const conf = await Swal.fire({icon:'question', title:'¿Eliminar evidencia?', text:'El adjunto regresará a ATENDIDO.', showCancelButton:true, confirmButtonText:'Sí, eliminar'});
          if(!conf.isConfirmed) return;
          try{
            const r = await fetch("{{ url('imagenes/adjuntos') }}/" + id + "/evidencia", {
              method:'DELETE',
              headers:{ 'X-CSRF-TOKEN':'{{ csrf_token() }}', 'Accept':'application/json' }
            });
            const j = await r.json(); if(!j.ok) throw new Error(j.error||'No se pudo eliminar la evidencia');
            ok('Evidencia eliminada'); await cargarUnidad($slc.value);
            const fol = (document.getElementById('adjFolio')?.textContent || '').trim();
            if($('#adjuntosModal').hasClass('show') && fol){ try{ await cargarAdjuntosEnModal(fol); }catch(_){ } }
          }catch(e){
            Swal.fire({icon:'error', title:'Error', text:e.message});
          }
        };
      });

      $btnCrear.disabled = true; $btnLeerQR.disabled = true; $slc.disabled = true; $btnDeshacer.classList.remove('d-none');
    }catch(e){
      console.error('estado-unidad error:', e);
      limpiarGrids(); bannerSinDoc.classList.remove('d-none'); estadoActualDoc = null; $slc.disabled = false; $btnDeshacer.classList.remove('d-none');
    }
  }

  // ===== Botones “Añadir fotos”
  document.querySelectorAll('[data-choose]').forEach(btn=>{
    if(!P.subirImagenes) btn.style.display = 'none';
    btn.addEventListener('click', ()=>{
      if(!P.subirImagenes) return warn('Sin permiso','No puedes subir imágenes.');
      const k = btn.getAttribute('data-choose');
      const folio = docFolio.textContent;
      if(!folio) return warn('Primero crea/carga un Folio ABIERTO.');
      document.getElementById('inp_'+k).click();
    });
  });

  // Utilidad: DataURL -> Blob
  function dataURLtoBlob(dataURL){
    const parts = dataURL.split(',');
    const mime = parts[0].match(/:(.*?);/)[1] || 'image/png';
    const bstr = atob(parts[1]); let n = bstr.length; const u8 = new Uint8Array(n);
    while(n--) u8[n] = bstr.charCodeAt(n);
    return new Blob([u8], {type:mime});
  }

  // Uploader con progreso
  function uploadWithProgress({url, formData, headers={}, onProgress, method='POST'}){
    return new Promise((resolve, reject)=>{
      const xhr = new XMLHttpRequest();
      xhr.open(method, url, true);
      Object.entries(headers).forEach(([k,v])=> xhr.setRequestHeader(k, v));
      xhr.upload.onprogress = (e)=>{
        if(e.lengthComputable && typeof onProgress==='function'){
          const pct = Math.round((e.loaded / e.total) * 100);
          onProgress(pct, e.loaded, e.total);
        }
      };
      xhr.onreadystatechange = function(){
        if(xhr.readyState === 4){
          let json={}; try{ json = JSON.parse(xhr.responseText || '{}'); }catch(_){}
          if(xhr.status >= 200 && xhr.status < 300) resolve(json);
          else reject(new Error(json.error || `HTTP ${xhr.status}`));
        }
      };
      xhr.onerror = ()=> reject(new Error('Error de red al subir'));
      xhr.send(formData);
    });
  }

  // Inputs por lado
  ['TblImagenDel','TblImagenTra','TblImagenIzq','TblImagenDer','TblImagenEspIzq','TblImagenEspDer'].forEach(k=>{
    const inp = document.getElementById('inp_'+k);
    const wrap = document.getElementById('edit_'+k);
    inp.addEventListener('change', ()=>{
      const folio = docFolio.textContent;
      if(!folio){ warn('Primero crea un Folio ABIERTO.'); inp.value=''; return; }
      const files = Array.from(inp.files||[]);
      files.forEach(f => createEditorCard(wrap, k, f));
      inp.value='';
    });
  });

  // ===== Editor NUEVA imagen (crea adjunto)
  function createEditorCard(container, tabla_lado, file){
    if(!P.subirImagenes) return warn('Sin permiso','No puedes subir imágenes.');
    const id = 'ed_' + Math.random().toString(36).slice(2,9);
    container.insertAdjacentHTML('beforeend', `
      <div class="col" id="${id}_col">
        <div class="editor-card">
          <div class="editor-toolbar">
            <div class="ctrl"><label>Tipo de Marca</label>
              <select class="form-select form-select-sm" id="${id}_tool">
                <option value="free">Libre</option><option value="cross">Cruz</option>
                <option value="circle">Círculo</option><option value="ellipse">Elipse</option><option value="line">Línea</option>
              </select>
            </div>
            <div class="ctrl"><label>Color</label><input type="color" id="${id}_color" value="#ff0000"></div>
            <div class="ctrl"><label>Grosor</label>
              <select class="form-select form-select-sm" id="${id}_size"><option>2</option><option selected>3</option><option>4</option><option>6</option><option>8</option></select>
            </div>
            <div class="ctrl" style="flex:1 1 240px; min-width:240px;">
              <label>Comentario</label>
              <input type="text" class="form-control form-control-sm" id="${id}_desc" placeholder="Descripción del daño">
            </div>
            <button type="button" class="btn btn-secondary btn-sm" id="${id}_undo">Deshacer</button>
            <button type="button" class="btn btn-outline-secondary btn-sm" id="${id}_clear">Limpiar</button>
            <button type="button" class="btn btn-danger btn-sm" id="${id}_discard">Eliminar</button>
            <button type="button" class="btn btn-primary btn-sm" id="${id}_save">Guardar</button>
          </div>
          <div class="editor-canvas-wrap">
            <img id="${id}_img" alt="">
            <canvas id="${id}_canvas" class="editor-canvas"></canvas>
          </div>
          <div class="progress-wrap">
            <div class="progress" id="${id}_prog_wrap"><div class="progress-bar" id="${id}_prog" role="progressbar" style="width:0%"></div></div>
            <div class="progress-info" id="${id}_prog_txt">Preparando...</div>
          </div>
        </div>
      </div>
    `);

    const img=document.getElementById(id+'_img');
    const canvas=document.getElementById(id+'_canvas');
    const ctx=canvas.getContext('2d');
    const toolSel=document.getElementById(id+'_tool');
    const colorIn=document.getElementById(id+'_color');
    const sizeSel=document.getElementById(id+'_size');
    const undoBtn=document.getElementById(id+'_undo');
    const clearBtn=document.getElementById(id+'_clear');
    const saveBtn=document.getElementById(id+'_save');
    const descInp=document.getElementById(id+'_desc');
    const discardBtn=document.getElementById(id+'_discard');
    const colNode=document.getElementById(id+'_col');

    const progWrap=document.getElementById(id+'_prog_wrap');
    const progBar =document.getElementById(id+'_prog');
    const progTxt=document.getElementById(id+'_prog_txt');

    const url=URL.createObjectURL(file);
    img.onload=()=>{
      const rect = img.getBoundingClientRect();
      const w = Math.max(1, Math.round(rect.width));
      const h = Math.max(1, Math.round(rect.height));
      const dpr = window.devicePixelRatio || 1;
      canvas.width  = Math.round(w * dpr);
      canvas.height = Math.round(h * dpr);
      canvas.style.width  = w + 'px';
      canvas.style.height = h + 'px';
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
      URL.revokeObjectURL(url);
    };
    img.src=url;

    const actions=[]; let drawing=false, sx=0, sy=0, preview=null;
    const draw=a=>{
      ctx.lineCap='round'; ctx.lineJoin='round'; ctx.strokeStyle=a.color; ctx.lineWidth=a.size; ctx.fillStyle=a.color;
      if(a.type==='free'){ if(a.points.length<2) return; ctx.beginPath(); ctx.moveTo(a.points[0].x,a.points[0].y); for(let i=1;i<a.points.length;i++) ctx.lineTo(a.points[i].x,a.points[i].y); ctx.stroke(); }
      if(a.type==='cross'){ const {x,y}=a.center; const d=a.size*3; ctx.beginPath(); ctx.moveTo(x-d,y-d); ctx.lineTo(x+d,y+d); ctx.stroke(); ctx.beginPath(); ctx.moveTo(x+d,y-d); ctx.lineTo(x-d,y+d); ctx.stroke(); }
      if(a.type==='circle'){ const r=Math.hypot(a.end.x-a.start.x,a.end.y-a.start.y); ctx.beginPath(); ctx.arc(a.start.x,a.start.y,r,0,Math.PI*2); ctx.stroke(); }
      if(a.type==='ellipse'){ const cx=(a.start.x+a.end.x)/2, cy=(a.start.y+a.end.y)/2; const rx=Math.abs(a.end.x-a.start.x)/2, ry=Math.abs(a.end.y-a.start.y)/2; ctx.beginPath(); ctx.ellipse(cx,cy,rx,ry,0,0,Math.PI*2); ctx.stroke(); }
      if(a.type==='line'){ ctx.beginPath(); ctx.moveTo(a.start.x,a.start.y); ctx.lineTo(a.end.x,a.end.y); ctx.stroke(); }
    };
    const redraw=()=>{ ctx.clearRect(0,0,canvas.width,canvas.height); actions.forEach(draw); if(preview) draw(preview); };
    const getXY = e=>{ const r=canvas.getBoundingClientRect(); return { x:e.clientX-r.left, y:e.clientY-r.top }; };

    canvas.addEventListener('pointerdown', e=>{
      const p=getXY(e); sx=p.x; sy=p.y; drawing=true; preview=null;
      if(toolSel.value==='free'){ actions.push({type:'free', color:colorIn.value, size:+sizeSel.value, points:[p]}); redraw(); }
      else if(toolSel.value==='cross'){ actions.push({type:'cross', color:colorIn.value, size:+sizeSel.value, center:p}); drawing=false; preview=null; redraw(); }
    });
    canvas.addEventListener('pointermove', e=>{
      if(!drawing) return;
      const p=getXY(e);
      if(toolSel.value==='free'){ actions.at(-1).points.push(p); redraw(); }
      else{
        const common={color:colorIn.value,size:+sizeSel.value, start:{x:sx,y:sy}, end:p};
        if(toolSel.value==='circle')   preview={type:'circle',  ...common};
        if(toolSel.value==='ellipse')  preview={type:'ellipse', ...common};
        if(toolSel.value==='line')     preview={type:'line',    ...common};
        redraw();
      }
    });
    const commitPreview=()=>{ if(preview){ actions.push(preview); preview=null; redraw(); } };
    canvas.addEventListener('pointerup',   ()=>{ drawing=false; commitPreview(); });
    canvas.addEventListener('pointerleave',()=>{ drawing=false; commitPreview(); });

    undoBtn.onclick=()=>{ actions.pop(); redraw(); };
    clearBtn.onclick=()=>{ actions.length=0; preview=null; redraw(); };
    discardBtn.onclick=()=>{ colNode?.remove(); };

    // Guardar: render + enviar SOLO PNG combinado como file
    saveBtn.onclick = async ()=>{
      const folio = docFolio.textContent;
      if(!folio) return warn('No hay Folio ABIERTO.');
      const comentario = (descInp.value||'').trim();
      if(!comentario){ warn('Falta comentario','Agrega un comentario.'); descInp.focus(); return; }

      const tmp=document.createElement('canvas');
      const rect=img.getBoundingClientRect();
      const w=Math.round(rect.width), h=Math.round(rect.height);
      const dpr=window.devicePixelRatio||1;
      tmp.width=Math.round(w*dpr); tmp.height=Math.round(h*dpr);
      const tctx=tmp.getContext('2d');
      tctx.setTransform(dpr,0,0,dpr,0,0);
      tctx.drawImage(img,0,0,w,h); tctx.drawImage(canvas,0,0,w,h);
      const dataURL=tmp.toDataURL('image/png');
      const blob = dataURLtoBlob(dataURL);

      const fd=new FormData();
      fd.append('tabla_lado', tabla_lado);
      fd.append('IdRegTab', folio);
      fd.append('comentarios', comentario);
      fd.append('file', blob, 'image.png');

      function setProgress(pct, text){ progBar.style.width = pct + '%'; progTxt.textContent = text || (pct + '%'); }
      progWrap.classList.add('show'); progTxt.classList.add('show'); setProgress(1, 'Iniciando...');
      [saveBtn, undoBtn, clearBtn, discardBtn, toolSel, colorIn, sizeSel, descInp].forEach(el => el.disabled = true);

      try{
        const json = await uploadWithProgress({
          url: `{{ route('imagenes.adjuntos.store') }}`,
          formData: fd,
          headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
          onProgress: (pct)=> setProgress(pct, `Subiendo ${pct}%`)
        });
        if(!json.ok) throw new Error(json.error || 'Error');
        setProgress(100, 'Completado');
        colNode?.remove();
        await cargarUnidad(document.getElementById('slcUnidad').value);
        foliosDT.ajax.reload(null,false);
        ok('Imagen guardada');
      }catch(e){
        Swal.fire({icon:'error', title:'Error al subir', text:e.message || 'Falló la subida'});
        [saveBtn, undoBtn, clearBtn, discardBtn, toolSel, colorIn, sizeSel, descInp].forEach(el => el.disabled = false);
        progTxt.textContent = 'Error';
      }
    };
  }

  // >>> NUEVO: Editor para imagen EXISTENTE (abre con el botón "Editar marcas")
  function createMarkupEditorExisting({container, tabla_lado, idAdjunto, imageUrl, initialComment}){
    const id = 'ed_' + Math.random().toString(36).slice(2,9);
    container.insertAdjacentHTML('beforeend', `
      <div class="col" id="${id}_col">
        <div class="editor-card">
          <div class="editor-toolbar">
            <div class="ctrl"><label>Tipo de Marca</label>
              <select class="form-select form-select-sm" id="${id}_tool">
                <option value="free">Libre</option><option value="cross">Cruz</option>
                <option value="circle">Círculo</option><option value="ellipse">Elipse</option><option value="line">Línea</option>
              </select>
            </div>
            <div class="ctrl"><label>Color</label><input type="color" id="${id}_color" value="#ff0000"></div>
            <div class="ctrl"><label>Grosor</label>
              <select class="form-select form-select-sm" id="${id}_size"><option>2</option><option selected>3</option><option>4</option><option>6</option><option>8</option></select>
            </div>
            <div class="ctrl" style="flex:1 1 240px; min-width:240px;">
              <label>Comentario</label>
              <input type="text" class="form-control form-control-sm" id="${id}_desc" placeholder="Descripción del daño" value="${initialComment||''}">
            </div>
            <button type="button" class="btn btn-secondary btn-sm" id="${id}_undo">Deshacer</button>
            <button type="button" class="btn btn-outline-secondary btn-sm" id="${id}_clear">Limpiar</button>
            <button type="button" class="btn btn-danger btn-sm" id="${id}_discard">Eliminar</button>
            <button type="button" class="btn btn-primary btn-sm" id="${id}_save">Guardar</button>
          </div>
          <div class="editor-canvas-wrap">
            <img id="${id}_img" alt="">
            <canvas id="${id}_canvas" class="editor-canvas"></canvas>
          </div>
          <div class="progress-wrap">
            <div class="progress" id="${id}_prog_wrap"><div class="progress-bar" id="${id}_prog" role="progressbar" style="width:0%"></div></div>
            <div class="progress-info" id="${id}_prog_txt">Preparando...</div>
          </div>
        </div>
      </div>
    `);

    const img=document.getElementById(id+'_img');
    const canvas=document.getElementById(id+'_canvas');
    const ctx=canvas.getContext('2d');
    const toolSel=document.getElementById(id+'_tool');
    const colorIn=document.getElementById(id+'_color');
    const sizeSel=document.getElementById(id+'_size');
    const undoBtn=document.getElementById(id+'_undo');
    const clearBtn=document.getElementById(id+'_clear');
    const saveBtn=document.getElementById(id+'_save');
    const descInp=document.getElementById(id+'_desc');
    const discardBtn=document.getElementById(id+'_discard');
    const colNode=document.getElementById(id+'_col');

    const progWrap=document.getElementById(id+'_prog_wrap');
    const progBar =document.getElementById(id+'_prog');
    const progTxt=document.getElementById(id+'_prog_txt');

    img.onload=()=>{
      const rect = img.getBoundingClientRect();
      const w = Math.max(1, Math.round(rect.width));
      const h = Math.max(1, Math.round(rect.height));
      const dpr = window.devicePixelRatio || 1;
      canvas.width  = Math.round(w * dpr);
      canvas.height = Math.round(h * dpr);
      canvas.style.width  = w + 'px';
      canvas.style.height = h + 'px';
      ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
    };
    img.src = addNoCache(imageUrl);

    const actions=[]; let drawing=false, sx=0, sy=0, preview=null;
    const draw=a=>{
      ctx.lineCap='round'; ctx.lineJoin='round'; ctx.strokeStyle=a.color; ctx.lineWidth=a.size; ctx.fillStyle=a.color;
      if(a.type==='free'){ if(a.points.length<2) return; ctx.beginPath(); ctx.moveTo(a.points[0].x,a.points[0].y); for(let i=1;i<a.points.length;i++) ctx.lineTo(a.points[i].x,a.points[i].y); ctx.stroke(); }
      if(a.type==='cross'){ const {x,y}=a.center; const d=a.size*3; ctx.beginPath(); ctx.moveTo(x-d,y-d); ctx.lineTo(x+d,y+d); ctx.stroke(); ctx.beginPath(); ctx.moveTo(x+d,y-d); ctx.lineTo(x-d,y+d); ctx.stroke(); }
      if(a.type==='circle'){ const r=Math.hypot(a.end.x-a.start.x,a.end.y-a.start.y); ctx.beginPath(); ctx.arc(a.start.x,a.start.y,r,0,Math.PI*2); ctx.stroke(); }
      if(a.type==='ellipse'){ const cx=(a.start.x+a.end.x)/2, cy=(a.start.y+a.end.y)/2; const rx=Math.abs(a.end.x-a.start.x)/2, ry=Math.abs(a.end.y-a.start.y)/2; ctx.beginPath(); ctx.ellipse(cx,cy,rx,ry,0,0,Math.PI*2); ctx.stroke(); }
      if(a.type==='line'){ ctx.beginPath(); ctx.moveTo(a.start.x,a.start.y); ctx.lineTo(a.end.x,a.end.y); ctx.stroke(); }
    };
    const redraw=()=>{ ctx.clearRect(0,0,canvas.width,canvas.height); actions.forEach(draw); if(preview) draw(preview); };
    const getXY = e=>{ const r=canvas.getBoundingClientRect(); return { x:e.clientX-r.left, y:e.clientY-r.top }; };

    canvas.addEventListener('pointerdown', e=>{
      const p=getXY(e); sx=p.x; sy=p.y; drawing=true; preview=null;
      if(toolSel.value==='free'){ actions.push({type:'free', color:colorIn.value, size:+sizeSel.value, points:[p]}); redraw(); }
      else if(toolSel.value==='cross'){ actions.push({type:'cross', color:colorIn.value, size:+sizeSel.value, center:p}); drawing=false; preview=null; redraw(); }
    });
    canvas.addEventListener('pointermove', e=>{
      if(!drawing) return;
      const p=getXY(e);
      if(toolSel.value==='free'){ actions.at(-1).points.push(p); redraw(); }
      else{
        const common={color:colorIn.value,size:+sizeSel.value, start:{x:sx,y:sy}, end:p};
        if(toolSel.value==='circle')   preview={type:'circle',  ...common};
        if(toolSel.value==='ellipse')  preview={type:'ellipse', ...common};
        if(toolSel.value==='line')     preview={type:'line',    ...common};
        redraw();
      }
    });
    const commitPreview=()=>{ if(preview){ actions.push(preview); preview=null; redraw(); } };
    canvas.addEventListener('pointerup',   ()=>{ drawing=false; commitPreview(); });
    canvas.addEventListener('pointerleave',()=>{ drawing=false; commitPreview(); });

    undoBtn.onclick=()=>{ actions.pop(); redraw(); };
    clearBtn.onclick=()=>{ actions.length=0; preview=null; redraw(); };
    discardBtn.onclick=()=>{ colNode?.remove(); };

    saveBtn.onclick = async ()=>{
      const folio = docFolio.textContent;
      if(!folio) return warn('No hay Folio ABIERTO.');
      const comentario = (descInp.value||'').trim();
      if(!comentario){ warn('Falta comentario','Agrega un comentario.'); descInp.focus(); return; }

      // Render base + marcas
      const tmp=document.createElement('canvas');
      const rect=img.getBoundingClientRect();
      const w=Math.round(rect.width), h=Math.round(rect.height);
      const dpr=window.devicePixelRatio||1;
      tmp.width=Math.round(w*dpr); tmp.height=Math.round(h*dpr);
      const tctx=tmp.getContext('2d');
      tctx.setTransform(dpr,0,0,dpr,0,0);
      tctx.drawImage(img,0,0,w,h); tctx.drawImage(canvas,0,0,w,h);
      const dataURL=tmp.toDataURL('image/png');
      const blob = dataURLtoBlob(dataURL);

      const fd=new FormData();
      fd.append('tabla_lado', tabla_lado);
      fd.append('IdRegTab', folio);
      fd.append('comentarios', comentario);
      fd.append('file', blob, 'image.png');

      function setProgress(pct, text){ progBar.style.width = pct + '%'; progTxt.textContent = text || (pct + '%'); }
      progWrap.classList.add('show'); progTxt.classList.add('show'); setProgress(1, 'Subiendo...');
      [saveBtn, undoBtn, clearBtn, discardBtn, toolSel, colorIn, sizeSel, descInp].forEach(el => el.disabled = true);

      try{
        // 1) Subimos la imagen anotada como NUEVO adjunto
        const json = await uploadWithProgress({
          url: `{{ route('imagenes.adjuntos.store') }}`,
          formData: fd,
          headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
          onProgress: (pct)=> setProgress(pct, `Subiendo ${pct}%`)
        });
        if(!json.ok) throw new Error(json.error || 'Error al subir');

        // 2) Eliminamos la imagen original
        await fetch(`{{ url('imagenes/adjuntos') }}/${idAdjunto}`, {
          method:'DELETE',
          headers:{ 'X-CSRF-TOKEN':'{{ csrf_token() }}','Accept':'application/json' }
        });

        setProgress(100, 'Completado');
        colNode?.remove();
        await cargarUnidad(document.getElementById('slcUnidad').value);
        foliosDT.ajax.reload(null,false);
        ok('Imagen actualizada');
      }catch(e){
        Swal.fire({icon:'error', title:'Error', text:e.message || 'Falló la operación'});
        [saveBtn, undoBtn, clearBtn, discardBtn, toolSel, colorIn, sizeSel, descInp].forEach(el => el.disabled = false);
        progTxt.textContent = 'Error';
      }
    };
  }

  function limpiarGrids(){
    document.querySelectorAll('[id^="grid_"]').forEach(n => n.innerHTML = '');
    document.querySelectorAll('[id^="edit_"]').forEach(n => n.innerHTML = '');
  }

  /* ========== QR Selección de unidad (modal existente) ========== */
  let qr;
  const isMobile = /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent);
  $('#qrModal').on('shown.bs.modal', function(){
    if(qr) return;
    qr = new Html5Qrcode("qrReader");
    Html5Qrcode.getCameras().then(cams=>{
      if(!cams || !cams.length){ warn('No se encontraron cámaras'); return; }
      let chosen = cams[0];
      if(isMobile){
        const byLabel = cams.find(c => /back|rear|environment/i.test(c.label || ''));
        const byFacing = cams.find(c => (c.facingMode || '').toLowerCase() === 'environment');
        chosen = byLabel || byFacing || cams[cams.length-1] || cams[0];
      }
      const camId = chosen.id || chosen.deviceId || chosen.cameraId;
      qr.start(camId, { fps: 10, qrbox: { width: 220, height: 220 } }, (text) => {
        $('#btnCerrarQR').trigger('click');
        let selected=false;
        if(/^\d+$/.test(text)){
          const opt = $slc.querySelector(`option[value="${text}"]`);
          if(opt){ $slc.value=text; applyUnidadUI(); selected=true; }
        }
        if(!selected){
          const t = text.trim().toLowerCase();
          for(const opt of $slc.options){
            if(opt.text.toLowerCase().includes(t)){ $slc.value=opt.value; applyUnidadUI(); selected=true; break; }
          }
        }
        if(!selected) info('QR leído', text+' (no se encontró unidad)');
      });
    }).catch(()=> Swal.fire({icon:'error', title:'No fue posible acceder a la cámara'}));
  }).on('hidden.bs.modal', function(){ stopQR(); });
  document.getElementById('btnStopQR')?.addEventListener('click', stopQR);
  function stopQR(){ if(qr){ qr.stop().then(()=>{ qr.clear(); qr=null; }).catch(()=>{ try{qr.clear();}catch(_){} qr=null; }); } }

  /* ========== Helpers de verificación QR ANTES de subir evidencia (NUEVO) ========== */
  let qrCheck;
  function normalizeUnit(s){
    s = String(s||'').trim();
    const noSpaces = s.replace(/\s+/g,'').toUpperCase();
    const onlyDigits = (s.match(/\d+/g)||[]).join('');
    return { noSpaces, onlyDigits };
  }
  function unitMatches(scanned, expectedText, expectedId){
    const S = normalizeUnit(scanned);
    const T = normalizeUnit(expectedText||'');
    const I = normalizeUnit(expectedId||'');
    if (I.onlyDigits && S.onlyDigits && S.onlyDigits === I.onlyDigits) return true;
    if (T.noSpaces && (S.noSpaces === T.noSpaces || S.noSpaces.includes(T.noSpaces) || T.noSpaces.includes(S.noSpaces))) return true;
    return false;
  }
  /* >>> Cambio: priorizar la unidad que viene desde la DataTable al abrir el modal */
  function getExpectedUnidad(){
    if (modalUnidadExpected && (modalUnidadExpected.uText || modalUnidadExpected.uId)) {
      return { uText: modalUnidadExpected.uText, uId: modalUnidadExpected.uId };
    }
    const uTextDoc = (estadoActualDoc && (estadoActualDoc.Unidad || estadoActualDoc.unidad)) || (docUnidad?.textContent||'');
    const uIdDoc   = (estadoActualDoc && (estadoActualDoc.IdUnidad || estadoActualDoc.id_unidad)) || '';
    const selVal   = $slc?.value || '';
    const selText  = ($slc?.selectedOptions?.[0]?.text || '').trim();
    return {
      uText: (uTextDoc || selText || '').trim(),
      uId  : (uIdDoc || selVal || '').trim()
    };
  }
  async function requireQRForUnidad(expected){
  const expText = expected.uText;
  const expId   = expected.uId;

  return new Promise((resolve, reject)=>{
    const $m = $('#qrCheckModal');

    if(!$m.parent().is('body')) $m.appendTo('body');

    $m.off('show.bs.modal shown.bs.modal hidden.bs.modal');

    $m.on('show.bs.modal', function(){
      $m.css('z-index', 3005);
      setTimeout(()=> $('.modal-backdrop').last().addClass('qrcheck-backdrop'), 0);
    });

    $m.on('shown.bs.modal', async function(){
      try{
        qrCheck = new Html5Qrcode("qrCheckReader");
        const cams = await Html5Qrcode.getCameras();
        if(!cams || !cams.length) throw new Error('No hay cámaras disponibles');
        const chosen = cams.find(c => /back|rear|environment/i.test(c.label||'')) || cams[0];
        const camId = chosen.id || chosen.deviceId || chosen.cameraId;

        await qrCheck.start(camId, { fps: 10, qrbox: { width: 220, height: 220 } }, (text) => {
          const scanned = String(text||'').trim();
          const ok = unitMatches(scanned, expText, expId);
          if(!ok){
            Swal.fire({
              icon:'error',
              title:'Unidad incorrecta',
              html:`Leído: <b>${escapeHtml(scanned)}</b><br>Esperado: <b>${escapeHtml(expText||expId||'(desconocido)')}</b>`
            });
            return;
          }
          stopQRCheck();
          $m.modal('hide');
          resolve(true);
        });
      }catch(e){
        stopQRCheck();
        $m.modal('hide');
        reject(e);
      }
    });

    $m.on('hidden.bs.modal', function(){
      if(qrCheck){
        stopQRCheck();
        reject(new Error('Verificación cancelada'));
      }
      $('.modal-backdrop.qrcheck-backdrop').removeClass('qrcheck-backdrop');
    });

    $m.modal({backdrop:true, keyboard:true, show:true});
  });
}

  function stopQRCheck(){
    if(!qrCheck) return;
    qrCheck.stop().then(()=>{ qrCheck.clear(); qrCheck=null; })
      .catch(()=>{ try{ qrCheck.clear(); }catch(_){}
        qrCheck=null; });
  }

  // Inicio
  initFoliosDT();
  resetSeleccionUnidadUI();
})();
</script>
@endpush
