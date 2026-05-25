{{-- resources/views/imagenes/index.blade.php --}}
@extends('adminlte::page')

@section('title', 'Capturar Imagen')

@section('content_header')
  <h1>Módulo de Imagen</h1>
@stop

@section('content')
  @include('imagenes.partials._alerts')

  {{-- ======================== Selector de unidad + acciones ======================== --}}
  <div class="card mb-3">
    <div class="card-body">
      <div class="row g-2 align-items-end">
        <div class="col-lg-7">
          <label for="slcUnidad" class="mb-1">Unidad</label>
          <div class="input-group">
            <select id="slcUnidad" class="form-control">
              <option value="">— Selecciona —</option>
              @foreach($unidades as $u)
                <option value="{{ $u->IdUnidad }}">{{ $u->Unidad }}</option>
              @endforeach
            </select>
            <div class="input-group-append">
              <button id="btnLeerQR" class="btn btn-outline-secondary" type="button" title="Escanear QR de unidad">
                <i class="fa fa-qrcode"></i><span class="d-none d-sm-inline"> QR</span>
              </button>
              <button id="btnCrearDoc" class="btn btn-primary" type="button" disabled title="Crear folio">
                <i class="fa fa-plus"></i><span class="d-none d-sm-inline"> Crear folio</span>
              </button>
            </div>
          </div>
        </div>

        <div class="col-lg-3">
          <label for="filtroEstatus" class="mb-1">Estatus</label>
          <select id="filtroEstatus" class="form-control">
            <option value="ABIERTO" selected>ABIERTO</option>
            {{-- Eliminado EN PROCESO --}}
            <option value="CERRADO">CERRADO</option>
            <option value="CANCELADO">CANCELADO</option>
            <option value="TODOS">TODOS</option>
          </select>
        </div>
      </div>
    </div>
  </div>

  {{-- ======================== DataTable de Folios ======================== --}}
  <div class="card" id="cardFolios">
    <div class="card-body">
      <table id="tblFolios" class="table table-striped w-100">
        <thead>
          <tr>
            <th></th>          {{-- control responsive --}}
            <th>Folio</th>     {{-- SIEMPRE visible --}}
            <th>Fecha</th>     {{-- NUEVA: SIEMPRE visible --}}
            <th>Unidad</th>
            <th>Comentarios</th>
            <th>Estatus</th>
            <th>Totales</th>
            <th>Acciones</th>  {{-- SIEMPRE visible --}}
          </tr>
        </thead>
        <tbody></tbody>
      </table>
    </div>
  </div>

  {{-- ======================== Modal QR ======================== --}}
  <div class="modal fade" id="qrCheckModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-mobile-full" role="document">
      <div class="modal-content">
        <div class="modal-header d-flex justify-content-between align-items-center">
          <h5 class="modal-title">Escanear QR de unidad</h5>
          <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
            <span>&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <div id="qrCheckReader" style="width:100%"></div>
          <small class="text-muted d-block mt-2">
            Enfoca el QR de la unidad. Al reconocerlo, se seleccionará automáticamente en el combo.
          </small>
        </div>
      </div>
    </div>
  </div>
@stop

@push('css')
  <link rel="stylesheet" href="https://cdn.datatables.net/2.0.0/css/dataTables.dataTables.css"/>
  <link rel="stylesheet" href="https://cdn.datatables.net/responsive/3.0.0/css/responsive.dataTables.css"/>
  <style>
    .dataTables_wrapper table { table-layout: fixed; width: 100% !important; }
    .dataTables_wrapper td, .dataTables_wrapper th { overflow: visible; text-overflow: initial; word-break: break-word; }
    table.dataTable { overflow: visible !important; }
    .dt-control, .dtr-control { width: 18px; }
    table.dataTable thead{ background:linear-gradient(to right,#4A00E0,#3488f7); color:#fff; }
    .status-inline{ width:140px!important; height:32px; padding:.25rem .5rem; font-size:.875rem; }
    @media (max-width:576px){
      .modal-mobile-full{ width:100%!important; max-width:100%!important; height:100%; margin:0; }
      .modal-mobile-full .modal-content{ height:100vh; border-radius:0; display:flex; flex-direction:column; }
      .modal-mobile-full .modal-body{ padding:.5rem 1rem; height:calc(100vh - 120px); overflow:auto; }
    }
    .swal2-container{ z-index:3005 !important; }
  </style>
@endpush

@push('js')
  <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
  <script src="https://cdn.datatables.net/2.0.0/js/dataTables.js"></script>
  <script src="https://cdn.datatables.net/responsive/3.0.0/js/dataTables.responsive.js"></script>

  <script>
    const RAW = @json($perms ?: new \stdClass);
    const S = v => String(v || '').trim().toUpperCase() === 'S';
    window.USER_PERMS = {
      subirImagenes   : typeof RAW.subirImagenes   === 'boolean' ? RAW.subirImagenes   : S(RAW.SubirFotosFolImg),
      borrarImagenes  : typeof RAW.borrarImagenes  === 'boolean' ? RAW.borrarImagenes  : S(RAW.EliminarFotosFolImg),
      subirIEvidencia : typeof RAW.subirIEvidencia === 'boolean' ? RAW.subirIEvidencia : S(RAW.SubirFotosEviFolImg),
      borrarEvidencia : typeof RAW.borrarEvidencia === 'boolean' ? RAW.borrarEvidencia : S(RAW.EliminarFotosEviFolImg),
    };
  </script>

  <script>
  (function () {
    const $slc       = document.getElementById('slcUnidad');
    const $btnCrear  = document.getElementById('btnCrearDoc');
    const $btnLeerQR = document.getElementById('btnLeerQR');
    const selFiltro  = document.getElementById('filtroEstatus');

    const warn = (t,m='')=>Swal.fire({icon:'warning',title:t,text:m});
    const ok   = (t,m='')=>Swal.fire({icon:'success',title:t,text:m,timer:1200,showConfirmButton:false});
    const escapeHtml = s => (s || '').replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));

    // FIX etiquetas “Unidad” vacías
    (function fixUnidadLabels(){
      if(!$slc) return;
      for(let i=1;i<$slc.options.length;i++){
        const op = $slc.options[i];
        if(op && op.textContent.trim()==='Unidad') op.textContent = op.value;
      }
    })();

    function toggleCrear(){ $btnCrear.disabled = !($slc && $slc.value); }
    $slc?.addEventListener('change', toggleCrear);
    toggleCrear();

    /* =================== DataTable =================== */
    let foliosDT;

    function renderTotalesResumen(row){
      const map = row.totales || {
        Del:    [row.TotalDetDel ?? 0,    row.TotalDetDelAtnd ?? 0],
        Tra:    [row.TotalDetTra ?? 0,    row.TotalDetTraAtnd ?? 0],
        Izq:    [row.TotalDetIzq ?? 0,    row.TotalDetIzqAtnd ?? 0],
        Der:    [row.TotalDetDer ?? 0,    row.TotalDetDerAtnd ?? 0],
        EspIzq: [row.TotalDetEspIzq ?? 0, row.TotalDetEspIzqAtnd ?? 0],
        EspDer: [row.TotalDetEspDer ?? 0, row.TotalDetEspDerAtnd ?? 0],
        Int:    [row.TotalDetInt ?? 0, row.TotalDetIntAtnd ?? 0],

      };
      const order = ['Del','Tra','Izq','Der','EspIzq','EspDer','Int'];
      return order.map(k=>{
        const v = map[k] || [0,0];
        const tot = Number(v?.[0]||0);
        const atn = Number(v?.[1]||0);
        return `${k} ${atn}/${tot}`;
      }).join(' · ');
    }

    function fmtFecha(row){
      // Toma row.Fecha o row.Creado; muestra YYYY-MM-DD
      const raw = (row.Fecha || row.Creado || '').toString();
      return raw ? raw.slice(0,10) : '';
    }

    function initFoliosDT() {
      if (foliosDT) { foliosDT.destroy(); document.querySelector('#tblFolios tbody').innerHTML=''; }

      foliosDT = new DataTable('#tblFolios', {
        responsive: {
          details: { type: 'column', target: 0 },
          breakpoints: [
            { name:'desktop', width: Infinity },
            { name:'lg',      width: 1200     },
            { name:'md',      width: 992      },
            { name:'sm',      width: 768      },
            { name:'xs',      width: 576      },
            { name:'xxs',     width: 420      },
          ]
        },

        // ✅ Folio, Fecha y Acciones SIEMPRE visibles (en móvil también)
        columnDefs: [
          { targets: 0, className: 'dtr-control', orderable: false, responsivePriority: 10000 }, // control
          { targets: 1, className: 'all',                 responsivePriority: 1 }, // Folio
          { targets: 2, className: 'all',                 responsivePriority: 2 }, // Fecha
          { targets: 7, className: 'all text-center',     responsivePriority: 3 }, // Acciones
          { targets: 5,                                      responsivePriority: 4 }, // Estatus
          { targets: 6,                                      responsivePriority: 5 }, // Totales
          { targets: 3,                                      responsivePriority: 6 }, // Unidad
          { targets: 4,                                      responsivePriority: 7 }, // Comentarios
        ],

        serverSide: true,
        processing: true,
        order: [[1,'desc']], // seguir ordenando por Folio desc
        ajax: { url: `{{ route('imagenes.dt') }}`, data: d => { d.estatus = selFiltro?.value || 'ABIERTO'; } },
        columns: [
          { data: null, defaultContent: '' },           // 0 control
          { data: 'Folio' },                            // 1 Folio
          { data: null, render: (_, __, row) => fmtFecha(row) }, // 2 Fecha (de row.Fecha o row.Creado)
          { data: 'Unidad' },                           // 3 Unidad
          { data: 'Comentarios',
            render: d => d ? `<span class="text-truncate d-inline-block" style="max-width:240px" title="${escapeHtml(d)}">${escapeHtml(d)}</span>` : ''
          },                                            // 4 Comentarios
          { data: 'Estatus', render: d => {
              const cls = d==='ABIERTO' ? 'badge-warning'
                       // Eliminado EN PROCESO
                       : d==='CERRADO' ? 'badge-success'
                       : d==='CANCELADO' ? 'badge-dark' : 'badge-secondary';
              return `<span class="badge ${cls}">${d}</span>`;
          }},                                           // 5 Estatus
          { data: null, render: row => renderTotalesResumen(row) }, // 6 Totales
          { data:null, orderable:false, searchable:false, render: row => { // 7 Acciones
              const options = `
                <option value="ABIERTO"     ${row.Estatus==='ABIERTO'?'selected':''}>ABIERTO</option>
                {{-- Eliminado EN PROCESO --}}
                <option value="CERRADO"     ${row.Estatus==='CERRADO'?'selected':''}>CERRADO</option>
                <option value="CANCELADO"   ${row.Estatus==='CANCELADO'?'selected':''}>CANCELADO</option>`;

              const editUrl = `{{ route('imagenes.edit','__F__') }}`.replace('__F__', row.Folio);
              const btnVer = `<a class="btn btn-sm btn-primary" href="${editUrl}" title="Ver/editar imágenes">
                                <i class="fa fa-folder-open"></i>
                              </a>`;

              return `<div class="d-flex align-items-center gap-2 justify-content-center flex-wrap">
                        ${btnVer}
                        <select class="form-control form-control-sm status-inline"
                                data-set="${row.Folio}" data-current="${row.Estatus}">${options}</select>
                      </div>`;
          }}
        ],
        language: {
          sProcessing:"Procesando...", sLengthMenu:"Mostrar _MENU_", sZeroRecords:"Sin resultados",
          sEmptyTable:"Ningún dato disponible", sInfo:"Mostrando _START_ a _END_ de _TOTAL_",
          sInfoEmpty:"Mostrando 0 a 0 de 0", sInfoFiltered:"(filtrado de _MAX_)",
          sSearch:"Buscar:", oPaginate:{ sFirst:"Primero", sLast:"Último", sNext:"Siguiente", sPrevious:"Anterior" }
        }
      });

      // Recalcular responsive al cambiar tamaño
      window.addEventListener('resize', ()=> foliosDT?.responsive?.recalc());
    }

    selFiltro?.addEventListener('change', ()=> foliosDT?.ajax?.reload());
    initFoliosDT();

    // Cambiar estatus inline
    document.addEventListener('change', async (ev)=>{
      const sel = ev.target.closest('select.status-inline'); if(!sel) return;
      const folio=sel.getAttribute('data-set'); const before=sel.getAttribute('data-current'); const current=sel.value;
      if(current===before) return;
      try{
        sel.disabled=true;
        const r = await fetch(`{{ route('imagenes.folio.estatus', ['folio'=>'__F__']) }}`.replace('__F__', folio), {
          method:'PATCH',
          headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
          body: JSON.stringify({ estatus: current })
        });
        const j = await r.json();
        if(!r.ok || !j.ok) throw new Error(j.message || j.error || `HTTP ${r.status}`);
        foliosDT.ajax.reload(null,false);
        ok('Estatus actualizado');
      }catch(e){
        Swal.fire({icon:'error',title:'Error',text:e.message||'No se pudo actualizar'});
        sel.value=before;
      }finally{ sel.disabled=false; }
    });

    /* =================== Crear folio (junto al QR) =================== */
    $btnCrear?.addEventListener('click', async ()=>{
      if(!$slc.value) return warn('Selecciona una unidad');
      const conf = await Swal.fire({icon:'question', title:'¿Crear Folio?', showCancelButton:true, confirmButtonText:'Sí'});
      if(!conf.isConfirmed) return;
      const res = await fetch(`{{ route('imagenes.ajax.crearAbierto') }}`, {
        method:'POST',
        headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
        body: JSON.stringify({ IdUnidad: $slc.value })
      });
      const j = await res.json();
      if(!j.ok){ Swal.fire({icon:'error',title:'Error',text:j.error||'No se pudo crear'}); return; }
      ok('Folio creado');
      foliosDT?.ajax?.reload(null,false);
      if (j.folio) window.location.href = `{{ route('imagenes.edit','__F__') }}`.replace('__F__', j.folio);
    });

    /* =================== QR: seleccionar unidad por escaneo =================== */
    let qrCheck=null;
    function normalizeUnit(s){
      s = String(s||'').trim();
      const noSpaces  = s.replace(/\s+/g,'').toUpperCase();
      const onlyDigits= (s.match(/\d+/g)||[]).join('');
      return { raw:s, noSpaces, onlyDigits };
    }
    function findUnidadOptionByScan(txt){
      const S = normalizeUnit(txt);
      for (const op of $slc.options) {
        const T = normalizeUnit(op.textContent || '');
        const I = normalizeUnit(op.value || '');
        if (!op.value) continue;
        if (I.onlyDigits && S.onlyDigits && I.onlyDigits === S.onlyDigits) return op.value;
        if (T.noSpaces && (S.noSpaces === T.noSpaces || S.noSpaces.includes(T.noSpaces))) return op.value;
      }
      return null;
    }
    async function startQR(){
      return new Promise((resolve,reject)=>{
        const $m = $('#qrCheckModal');
        $m.off('shown.bs.modal hidden.bs.modal');
        $m.on('shown.bs.modal', async function(){
          try{
            qrCheck = new Html5Qrcode("qrCheckReader");
            const cams = await Html5Qrcode.getCameras();
            const chosen = cams.find(c => /back|rear|environment/i.test(c.label||'')) || cams[0];
            await qrCheck.start(chosen.id || chosen.deviceId || chosen.cameraId, { fps: 10, qrbox: { width: 220, height: 220 } }, (txt)=>{
              const val = findUnidadOptionByScan(txt);
              if(!val){
                Swal.fire({icon:'error', title:'Unidad no encontrada', html:`Leído: <b>${escapeHtml(txt)}</b>`});
                return;
              }
              stopQR(); $m.modal('hide'); resolve(val);
            });
          }catch(e){ stopQR(); $m.modal('hide'); reject(e); }
        });
        $m.on('hidden.bs.modal', ()=>{ if(qrCheck){ stopQR(); reject(new Error('cancelado')); } });
        $m.modal({backdrop:true, keyboard:true, show:true});
      });
    }
    function stopQR(){ try{ qrCheck?.stop().then(()=>qrCheck.clear()); }catch(_){} finally{ qrCheck=null; } }

    $btnLeerQR?.addEventListener('click', async ()=>{
      try{
        const val = await startQR();
        $slc.value = val;
        toggleCrear();
        ok('Unidad seleccionada por QR');
      }catch(_){}
    });

  })();
  </script>
@endpush
