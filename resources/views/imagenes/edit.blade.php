{{-- resources/views/imagenes/edit.blade.php --}}
@extends('adminlte::page')

@section('title', "Editar folio $folio")

@section('content_header')
  <div class="d-flex align-items-center justify-content-between">
    <h1 class="mb-0">Editar folio</h1>
    <a href="{{ route('imagenes.index') }}" class="btn btn-outline-secondary">
      <i class="fa fa-arrow-left mr-1"></i> Volver a la lista
    </a>
  </div>
@stop

@section('content')
@php
  $DOC  = is_array($doc) ? (object)$doc : $doc;
  $est  = $DOC->Estatus ?? 'ABIERTO';
  $cls  = $est==='ABIERTO' ? 'badge-warning'
         : ($est==='EN PROCESO' ? 'badge-info'
         : ($est==='CERRADO' ? 'badge-success'
         : ($est==='CANCELADO' ? 'badge-dark' : 'badge-secondary')));
  $LOCK = ($est !== 'ABIERTO');

  $LADOS = [
    'TblImagenDel'    => 'Frente',
    'TblImagenTra'    => 'Atrás',
    'TblImagenIzq'    => 'Costado Izquierdo',
    'TblImagenDer'    => 'Costado Derecho',
    'TblImagenEspIzq' => 'Espejo Izquierdo',
    'TblImagenEspDer' => 'Espejo Derecho',
    'TblImagenInt'    => 'Interiores',
  ];

  // ✅ Totales requeridos por sección (para mostrar "actual / total")
  $TOTAL_REQ = [
    'TblImagenInt' => (int)($DOC->TotalDetInt ?? 0),
  ];
@endphp

<div class="mb-2">
  <strong>Folio:</strong> {{ $folio }}
  &nbsp;&nbsp; <strong>Fecha:</strong> {{ \Illuminate\Support\Carbon::parse($DOC->Fecha ?? $DOC->Creado ?? now())->toDateString() }}
  &nbsp;&nbsp; <strong>Unidad:</strong> <span id="docUnidadText">{{ $DOC->Unidad ?? $DOC->IdUnidad ?? '' }}</span>
  <span id="docUnidadId" class="d-none">{{ $DOC->IdUnidad ?? '' }}</span>
  &nbsp;&nbsp; <strong>Estatus:</strong> <span class="badge {{ $cls }}">{{ $est }}</span>
</div>

<div class="d-flex align-items-center gap-2" id="docComentarioWrap">
  <input id="docComentario" type="text" class="form-control" placeholder="Comentario del folio"
         value="{{ $DOC->Comentarios ?? '' }}" {{ $LOCK ? 'disabled' : '' }}>
  <button id="btnGuardarDocComentario" class="btn btn-outline-primary" {{ $LOCK ? 'disabled' : '' }}>
    Guardar
  </button>
</div>
<div class="text-muted mt-2">Solo se puede editar mientras el folio está ABIERTO.</div>
@isset($totalesLinea)
  <div class="small mt-1">{{ $totalesLinea }}</div>
@endisset

<hr>

@push('css')
<link rel="stylesheet" href="{{ asset('css/imagenes.css') }}">
<style>
  .thumb{position:relative;border:1px solid #e1e1e1;border-radius:8px;overflow:hidden}
  .thumb img{display:block;width:420px;max-width:100%;height:230px;object-fit:cover}
  .thumb .badge{position:absolute;left:8px;top:8px}
  .thumb .actions{position:absolute;right:10px;top:10px;display:flex;gap:6px;z-index:5}

  .thumb-duo{display:grid;grid-template-columns:1fr 1fr;gap:.6rem}
  .thumb-duo .pane{position:relative;border:1px solid #e1e1e1;border-radius:8px;overflow:hidden}
  .thumb-duo img{display:block;width:100%;height:230px;object-fit:cover}
  .pane-actions{position:absolute;right:.35rem;top:.35rem;display:flex;gap:.35rem;z-index:5}

  .editor-card{border:1px solid #ddd;border-radius:.5rem;overflow:hidden;margin-top:.5rem}
  .editor-toolbar{background:#f8f9fa;padding:.5rem;display:flex;flex-wrap:wrap;gap:.5rem;align-items:center}
  .editor-toolbar .ctrl{display:flex;align-items:center;gap:.35rem}
  .editor-toolbar .ctrl label{margin:0;font-size:.85rem;color:#6c757d;min-width:92px}
  .editor-canvas-wrap{position:relative;width:100%}
  .editor-canvas-wrap img{display:block;width:100%;height:auto}
  .editor-canvas{position:absolute;left:0;top:0;z-index:2;touch-action:none}

  .btn-xs{padding:.15rem .35rem;font-size:.75rem;line-height:1.2;border-radius:.2rem}

  /* Modal full en móvil y z-index para quedar al frente */
  .modal-mobile-full{ width:100%!important; max-width:100%!important; }
  .swal2-container{ z-index:3005 !important; }

  /* Modal de Cámara: contenedor con tamaño fijo (4:3) */
  #camBox{ width: 100%; max-width: 540px; margin: 0 auto; }
  #camFrame{ position:relative; width:100%; background:#000; border-radius:10px; overflow:hidden; }
  #camFrame::before{ content:''; display:block; padding-top:133.33%; } /* 4:3 */
  #camVideo, #camPreview{
    position:absolute; left:0; top:0; width:100%; height:100%;
    object-fit:cover; border-radius:10px;
  }
  /* La previsualización debe mostrar todo el encuadre */
  #camPreview{ display:none; object-fit:contain; background:#000; }
</style>
@endpush

@foreach($LADOS as $key => $label)
  @php
    $lista = $adjuntos->get($key, collect())->map(fn($r)=> is_array($r)?(object)$r:$r);
    $totalReq = (int) data_get($TOTAL_REQ, $key, 0);
    $totalAct = $lista->count();
  @endphp

  <div class="d-flex justify-content-between align-items-center mt-4">
    <div>
      <h4 class="mb-0">{{ $label }}</h4>

      @if($totalReq > 0)
        <div class="text-muted small">
          {{ $totalAct }} / {{ $totalReq }}
        </div>
      @endif
    </div>

    <div>
      <button class="btn btn-primary" data-choose="{{ $key }}" {{ $LOCK ? 'disabled' : '' }}>
        Añadir fotos
      </button>
      <input id="inp_{{ $key }}" type="file"
             accept="image/*;capture=camera,image/png,image/jpeg" capture="environment"
             multiple hidden>
    </div>
  </div>

  <div class="row mt-2" id="grid_{{ $key }}">
    @forelse($lista as $it)
      @php
        $badge = $it->Estatus==='PENDIENTE' ? 'badge-warning'
               : ($it->Estatus==='ATENDIDO' ? 'badge-info'
               : ($it->Estatus==='DOCUMENTADO' ? 'badge-primary'
               : ($it->Estatus==='CONFIRMADO' ? 'badge-success' : 'badge-secondary')));

        $evUrl = data_get($it, 'Evidencia.Url') ?? data_get($it, 'EvidenciaUrl');
        $hasEv = !empty($evUrl);

        $canDelete    = !$LOCK && ($it->Estatus==='PENDIENTE');
        $canConfirm   = !$LOCK && ($it->Estatus==='DOCUMENTADO');
        $canUnconfirm = !$LOCK && ($it->Estatus==='CONFIRMADO');
        $canMarkup    = !$LOCK && ($it->Estatus==='PENDIENTE');
        $canEvUp      = !$LOCK && ($it->Estatus==='ATENDIDO') && !$hasEv;
        $canEvDel     = !$LOCK && ($it->Estatus==='DOCUMENTADO') && $hasEv;
      @endphp

      <div class="col-12 col-md-6 col-lg-6 mb-4">
        @if($hasEv)
          <div class="thumb-duo">
            <div class="pane">
              <a href="{{ $it->Url }}" target="_blank" rel="noopener">
                <img src="{{ $it->Url }}?v={{ now()->timestamp }}" alt="">
              </a>
              <span class="badge {{ $badge }}">{{ $it->Estatus }}</span>
            </div>
            <div class="pane">
              <a href="{{ $evUrl }}" target="_blank" rel="noopener">
                <img src="{{ $evUrl }}?v={{ now()->timestamp }}" alt="">
              </a>
              <span class="badge badge-dark">EVIDENCIA</span>
              @if($canEvDel)
                <div class="pane-actions">
                  <button class="btn btn-xs btn-outline-danger" data-ev-delete="{{ $it->Id }}" title="Eliminar evidencia">
                    <i class="fa fa-trash"></i>
                  </button>
                </div>
              @endif
            </div>
          </div>

          <div class="mt-2 d-flex gap-2">
            @if($canConfirm)
              <button class="btn btn-sm btn-success" data-confirm="{{ $it->Id }}" title="Confirmar">✓ Confirmar</button>
            @endif
            @if($canUnconfirm)
              <button class="btn btn-sm btn-warning" data-unconfirm="{{ $it->Id }}" title="Revertir a DOCUMENTADO">↩ Revertir</button>
            @endif
          </div>
        @else
          <div class="thumb">
            <a href="{{ $it->Url }}" target="_blank" rel="noopener">
              <img src="{{ $it->Url }}?v={{ now()->timestamp }}" alt="">
            </a>
            <span class="badge {{ $badge }}">{{ $it->Estatus }}</span>
            <div class="actions">
              @if($canMarkup)
                <button class="btn btn-sm btn-light border" title="Editar marcas"
                        data-markup="{{ $it->Id }}" data-url="{{ $it->Url }}" data-tabla="{{ $key }}"
                        data-cmt="{{ $it->Comentarios ?? '' }}">
                  <i class="fa fa-pen"></i>
                </button>
              @endif
              @if($canConfirm)
                <button class="btn btn-sm btn-success" data-confirm="{{ $it->Id }}" title="Confirmar">✓</button>
              @endif
              @if($canUnconfirm)
                <button class="btn btn-sm btn-warning" data-unconfirm="{{ $it->Id }}" title="Revertir">↩</button>
              @endif
              @if($canDelete)
                <button class="btn btn-sm btn-danger" data-delete="{{ $it->Id }}" title="Eliminar">✕</button>
              @endif
              @if($canEvUp)
                <button class="btn btn-sm btn-outline-primary"
                        data-ev-upload="{{ $it->Id }}"
                        data-tabla="{{ $key }}"
                        data-unidad-id="{{ $DOC->IdUnidad ?? '' }}"
                        data-unidad-text="{{ $DOC->Unidad ?? '' }}"
                        title="Subir evidencia (requiere QR)">
                  <i class="fa fa-paperclip"></i>
                </button>
              @endif
            </div>
          </div>
        @endif

        <div class="input-group input-group-sm mt-2">
          <input class="form-control" value="{{ $it->Comentarios ?? '' }}"
                 placeholder="Descripción del daño"
                 {{ (!$LOCK && $it->Estatus==='PENDIENTE') ? '' : 'disabled' }}
                 data-cmt="{{ $it->Id }}">
          <div class="input-group-append">
            <button class="btn btn-outline-secondary" data-cmt-save="{{ $it->Id }}"
              {{ (!$LOCK && $it->Estatus==='PENDIENTE') ? '' : 'disabled' }}>Guardar</button>
          </div>
        </div>
      </div>
    @empty
      <div class="col-12 text-muted">Sin imágenes.</div>
    @endforelse
  </div>

  <div class="row mt-2" id="edit_{{ $key }}"></div>
@endforeach

{{-- ===== Modal QR Evidencia (siempre al frente) ===== --}}
<div class="modal fade" id="qrEviModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-mobile-full" role="document">
    <div class="modal-content">
      <div class="modal-header d-flex justify-content-between align-items-center">
        <h5 class="modal-title">Validar unidad (QR)</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
          <span>&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div id="qrEviReader" style="width:100%"></div>
        <small class="text-muted d-block mt-2">
          Escanea el QR de la unidad para continuar con la carga de evidencia.
        </small>
      </div>
    </div>
  </div>
</div>

{{-- ===== Modal Cámara (solo fotos, sin galería) ===== --}}
<div class="modal fade" id="camModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-mobile-full" role="document">
    <div class="modal-content">
      <div class="modal-header d-flex justify-content-between align-items-center">
        <h5 class="modal-title">Tomar foto</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
          <span>&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div id="camBox">
          <div id="camFrame">
            <video id="camVideo" autoplay playsinline muted></video>
            <img id="camPreview" alt="captura">
            <canvas id="camCanvas" class="d-none"></canvas>
          </div>
        </div>
        <div class="mt-3 d-flex gap-2">
          <button id="camShot" class="btn btn-primary">Tomar foto</button>
          <button id="camUse"  class="btn btn-success" disabled>Usar foto</button>
          <button id="camCancel" class="btn btn-outline-secondary" data-dismiss="modal">Cancelar</button>
        </div>
        <small class="text-muted d-block mt-2">
          Se intenta usar la cámara trasera (iOS/Android). En desktop el flujo está bloqueado.
        </small>
      </div>
    </div>
  </div>
</div>
@stop

@push('js')
<script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
(function(){
  const RAW = @json($perms ?: new \stdClass);
  const S = v => String(v || '').trim().toUpperCase() === 'S';
  const P = {
    subirImagenes   : typeof RAW.subirImagenes   === 'boolean' ? RAW.subirImagenes   : S(RAW.SubirFotosFolImg),
    borrarImagenes  : typeof RAW.borrarImagenes  === 'boolean' ? RAW.borrarImagenes  : S(RAW.EliminarFotosFolImg),
    subirIEvidencia : typeof RAW.subirIEvidencia === 'boolean' ? RAW.subirIEvidencia : S(RAW.SubirFotosEviFolImg),
    borrarEvidencia : typeof RAW.borrarEvidencia === 'boolean' ? RAW.borrarEvidencia : S(RAW.EliminarFotosEviFolImg),
  };

  const folio = {{ json_encode($folio) }};
  const CSRF  = '{{ csrf_token() }}';
  const ok    = (t)=>Swal.fire({icon:'success',title:t,timer:900,showConfirmButton:false});
  const err   = (t)=>Swal.fire({icon:'error',title:'Error',text:t||'Ocurrió un error'});

  function isMobileUA(){ const ua = navigator.userAgent || ''; return /Android|iPhone|iPad|iPod/i.test(ua); }

  const MAX_LONG_SIDE=1600, MAX_MEGA_PIXELS=3.0, MAX_BYTES=900*1024, JPEG_QUALITY_MIN=0.5, JPEG_QUALITY_START=0.9;

  function imgFromBlob(blob){
    return new Promise((resolve,reject)=>{
      const url = URL.createObjectURL(blob);
      const img = new Image();
      img.onload = ()=>{ URL.revokeObjectURL(url); resolve(img); };
      img.onerror= ()=>{ URL.revokeObjectURL(url); reject(new Error('No se pudo leer la imagen')); };
      img.src = url;
    });
  }
  function calcTargetSize(w,h){
    const maxPx = MAX_MEGA_PIXELS * 1e6;
    let scale = Math.sqrt(maxPx / (w*h));
    if (scale > 1) scale = 1;
    const longSide = Math.max(w,h);
    const scaleLong = MAX_LONG_SIDE / longSide;
    const finalScale = Math.min(scale, isFinite(scaleLong) ? Math.min(scaleLong,1) : 1);
    return { tw: Math.max(1, Math.round(w*finalScale)), th: Math.max(1, Math.round(h*finalScale)) };
  }
  async function compressToLimit(srcBlob){
    const img = await imgFromBlob(srcBlob);
    const { tw, th } = calcTargetSize(img.naturalWidth || img.width, img.naturalHeight || img.height);
    const cv = document.createElement('canvas'); cv.width = tw; cv.height = th;
    const cx = cv.getContext('2d', { alpha: false }); cx.drawImage(img, 0, 0, tw, th);
    let q = JPEG_QUALITY_START;
    let out = await new Promise(r => cv.toBlob(r, 'image/jpeg', q));
    if(!out) out = new Blob([], {type:'image/jpeg'});
    while (out.size > MAX_BYTES && q > JPEG_QUALITY_MIN) {
      q = Math.max(JPEG_QUALITY_MIN, q - 0.1);
      out = await new Promise(r => cv.toBlob(r, 'image/jpeg', q));
      if(!out) break;
    }
    return out || srcBlob;
  }

  function dataURLtoBlob(dataURL){
    const parts = dataURL.split(',');
    const mime = parts[0].match(/:(.*?);/)[1] || 'image/png';
    const bstr = atob(parts[1]); let n = bstr.length; const u8 = new Uint8Array(n);
    while(n--) u8[n] = bstr.charCodeAt(n);
    return new Blob([u8], {type:mime});
  }
  function addNoCache(u){ if(!u) return u; if(/^blob:|^data:/i.test(u)) return u; const s = u.includes('?') ? '&' : '?'; return u + s + 'v=' + Date.now(); }

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
          if(xhr.status === 413){ reject(new Error('La imagen excede el límite del servidor (HTTP 413).')); return; }
          if(xhr.status >= 200 && xhr.status < 300) resolve(json);
          else reject(new Error(json.error || 'HTTP '+xhr.status));
        }
      };
      xhr.onerror = ()=> reject(new Error('Error de red al subir'));
      xhr.send(formData);
    });
  }

  /* =================== QR Evidencia =================== */
  let qrEvi = null; const $qrModal = $('#qrEviModal');
  function normalizeUnit(s){ s = String(s||'').trim(); const noSpaces = s.replace(/\s+/g,'').toUpperCase(); const onlyDigits=(s.match(/\d+/g)||[]).join(''); return {raw:s,noSpaces,onlyDigits}; }
  function matchUnidad(scanned, unidadId, unidadText){
    const S=normalizeUnit(scanned), I=normalizeUnit(unidadId), T=normalizeUnit(unidadText);
    if (I.onlyDigits && S.onlyDigits && I.onlyDigits === S.onlyDigits) return true;
    if (T.noSpaces && (S.noSpaces === T.noSpaces || S.noSpaces.includes(T.noSpaces))) return true;
    return false;
  }
  async function startQREvi(){
    return new Promise((resolve,reject)=>{
      $qrModal.off('shown.bs.modal hidden.bs.modal');
      $qrModal.on('shown.bs.modal', async function(){
        try{
          qrEvi = new Html5Qrcode("qrEviReader");
          const cams = await Html5Qrcode.getCameras();
          const chosen = cams.find(c => /back|rear|environment/i.test(c.label||'')) || cams[0];
          if(!chosen){ throw new Error('No se encontró cámara disponible'); }
          await qrEvi.start(chosen.id || chosen.deviceId || chosen.cameraId, { fps: 10, qrbox: { width: 240, height: 240 } }, (txt)=>{
            stopQREvi(); $qrModal.modal('hide'); resolve(txt);
          });
        }catch(e){ stopQREvi(); $qrModal.modal('hide'); reject(e); }
      });
      $qrModal.on('hidden.bs.modal', ()=>{ if(qrEvi){ stopQREvi(); reject(new Error('cancelado')); } });
      $qrModal.modal({backdrop:true, keyboard:true, show:true});
    });
  }
  function stopQREvi(){ try{ qrEvi?.stop().then(()=>qrEvi.clear()); }catch(_){} finally{ qrEvi=null; } }

  /* =================== Cámara (solo fotos) =================== */
  let _camStream=null, _shotBlob=null;
  async function openCameraModal() {
    return new Promise(async (resolve, reject)=>{
      if(!isMobileUA()){ reject(new Error('Solo disponible en dispositivos móviles.')); return; }
      const $m = $('#camModal');
      const video=document.getElementById('camVideo'), preview=document.getElementById('camPreview'), canvas=document.getElementById('camCanvas');
      const btnShot=document.getElementById('camShot'), btnUse=document.getElementById('camUse');

      _shotBlob=null; btnUse.disabled=true; preview.style.display='none'; video.style.display='block';

      async function start(){
        try{
          _camStream = await navigator.mediaDevices.getUserMedia({
            video:{ facingMode:{ideal:'environment'}, width:{ideal:1280}, height:{ideal:960} }, audio:false
          });
          video.srcObject=_camStream;
        }catch(e){ stopCam(); $m.modal('hide'); reject(new Error('No se pudo acceder a la cámara')); }
      }
      function stopCam(){ try{ _camStream?.getTracks()?.forEach(t=>t.stop()); }catch(_){} _camStream=null; }

      function takeShot(){
        const track=_camStream?.getVideoTracks?.()[0];
        if(!video.videoWidth || !track) return;
        const vw=video.videoWidth, vh=video.videoHeight, TW=1280, TH=960;
        canvas.width=TW; canvas.height=TH;
        const cx=canvas.getContext('2d',{alpha:false});
        const s=Math.max(TW/vw, TH/vh); const dw=Math.round(vw*s), dh=Math.round(vh*s);
        const dx=Math.round((TW-dw)/2), dy=Math.round((TH-dh)/2);
        cx.drawImage(video, dx, dy, dw, dh);
        stopCam();
        canvas.toBlob((b)=>{
          _shotBlob=b; btnUse.disabled=!_shotBlob;
          const blobURL=URL.createObjectURL(b); preview.src=blobURL; preview.onload=()=>URL.revokeObjectURL(blobURL);
          video.style.display='none'; preview.style.display='block';

          // Aviso para guiar al usuario
          Swal.fire({
            icon:'info',
            title:'Foto lista',
            text:'Haga clic en "Usar foto" para continuar.',
            timer:2500,
            showConfirmButton:false
          });
        }, 'image/jpeg', 0.9);
      }

      $m.off('shown.bs.modal hidden.bs.modal'); $m.on('shown.bs.modal', start);
      $m.on('hidden.bs.modal', ()=>{ stopCam(); if(!_shotBlob) reject(new Error('cancelado')); });

      btnShot.onclick=takeShot;
      btnUse.onclick =()=>{ if(_shotBlob){ $m.modal('hide'); resolve(_shotBlob); } };

      $m.modal({backdrop:true, keyboard:true, show:true});
    });
  }

  /* ===== Guardar comentario del folio ===== */
  document.getElementById('btnGuardarDocComentario')?.addEventListener('click', async ()=>{
    const comentario=(document.getElementById('docComentario').value||'').trim();
    try{
      const r=await fetch("{{ url('imagenes/folio') }}/"+folio+"/comentario",{
        method:'PATCH', headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':CSRF},
        body:JSON.stringify({comentarios:comentario})
      });
      const j=await r.json(); if(!r.ok||!j.ok) throw new Error(j.error||''); ok('Comentario guardado');
    }catch(e){ err(e.message); }
  });

  /* ===== Añadir fotos por lado (solo cámara) ===== */
  document.querySelectorAll('[data-choose]').forEach(btn=>{
    if(!P.subirImagenes) btn.style.display='none';
    btn.addEventListener('click', async ()=>{
      if(!P.subirImagenes) return err('No puedes subir imágenes.');
      if(!isMobileUA()) return err('Solo se permite tomar foto desde la cámara de un dispositivo móvil.');
      try{
        const blob=await openCameraModal();
        const file=new File([blob],'captura.jpg',{type:'image/jpeg'});
        const k=btn.dataset.choose; const wrap=document.getElementById('edit_'+k);
        createEditorNew(wrap,k,file);
      }catch(e){ if(e?.message!=='cancelado') err(e.message||'No se pudo capturar la foto'); }
    });
  });

  // Inputs hidden (compatibilidad)
  ['TblImagenDel','TblImagenTra','TblImagenIzq','TblImagenDer','TblImagenEspIzq','TblImagenEspDer','TblImagenInt'].forEach(k=>{
    const inp=document.getElementById('inp_'+k); const wrap=document.getElementById('edit_'+k);
    inp?.addEventListener('change', ()=>{ const files=Array.from(inp.files||[]); files.forEach(f=>createEditorNew(wrap,k,f)); inp.value=''; });
  });

  /* ===== Editor GENERICO ===== */
  function mountEditor({rootId, baseImgUrl, initialComment}){
    const img=document.getElementById(rootId+'_img');
    const cv=document.getElementById(rootId+'_cv'); const ctx=cv.getContext('2d');
    const tool=document.getElementById(rootId+'_tool'); const color=document.getElementById(rootId+'_color'); const size=document.getElementById(rootId+'_size');
    const undo=document.getElementById(rootId+'_undo'); const clear=document.getElementById(rootId+'_clear'); const desc=document.getElementById(rootId+'_desc');

    img.onload=()=>{ const r=img.getBoundingClientRect(); const w=Math.max(1,Math.round(r.width)), h=Math.max(1,Math.round(r.height)), dpr=window.devicePixelRatio||1;
      cv.width=Math.round(w*dpr); cv.height=Math.round(h*dpr); cv.style.width=w+'px'; cv.style.height=h+'px'; ctx.setTransform(dpr,0,0,dpr,0,0); };
    img.src=addNoCache(baseImgUrl);
    if(initialComment) desc.value=initialComment;

    const actions=[]; let drawing=false, sx=0, sy=0, preview=null;
    const draw=a=>{
      ctx.lineCap='round'; ctx.lineJoin='round'; ctx.strokeStyle=a.color; ctx.lineWidth=a.size; ctx.fillStyle=a.color;
      if(a.type==='free'){ if(a.points.length<2) return; ctx.beginPath(); ctx.moveTo(a.points[0].x,a.points[0].y); for(let i=1;i<a.points.length;i++) ctx.lineTo(a.points[i].x,a.points[i].y); ctx.stroke(); }
      if(a.type==='cross'){ const {x,y}=a.center; const d=a.size*3; ctx.beginPath(); ctx.moveTo(x-d,y-d); ctx.lineTo(x+d,y+d); ctx.stroke(); ctx.beginPath(); ctx.moveTo(x+d,y-d); ctx.lineTo(x-d,y+d); ctx.stroke(); }
      if(a.type==='circle'){ const r=Math.hypot(a.end.x-a.start.x,a.end.y-a.start.y); ctx.beginPath(); ctx.arc(a.start.x,a.start.y,r,0,Math.PI*2); ctx.stroke(); }
      if(a.type==='ellipse'){ const cx=(a.start.x+a.end.x)/2, cy=(a.start.y+a.end.y)/2; const rx=Math.abs(a.end.x-a.start.x)/2, ry=Math.abs(a.end.y-a.start.y)/2; ctx.beginPath(); ctx.ellipse(cx,cy,rx,ry,0,0,Math.PI*2); ctx.stroke(); }
      if(a.type==='line'){ ctx.beginPath(); ctx.moveTo(a.start.x,a.start.y); ctx.lineTo(a.end.x,a.end.y); ctx.stroke(); }
      if(a.type==='rect'){ const w=a.end.x-a.start.x, h=a.end.y-a.start.y; ctx.strokeRect(a.start.x,a.start.y,w,h); }
    };
    const redraw=()=>{ ctx.clearRect(0,0,cv.width,cv.height); actions.forEach(draw); if(preview) draw(preview); };
    const xy=e=>{ const r=cv.getBoundingClientRect(); const ex=(e.clientX||e.touches?.[0]?.clientX); const ey=(e.clientY||e.touches?.[0]?.clientY); return {x:ex-r.left,y:ey-r.top}; };

    cv.addEventListener('pointerdown', e=>{ const p=xy(e); sx=p.x; sy=p.y; drawing=true; preview=null;
      if(tool.value==='free'){ actions.push({type:'free',color:color.value,size:+size.value,points:[p]}); redraw(); }
      else if(tool.value==='cross'){ actions.push({type:'cross',color:color.value,size:+size.value,center:p}); drawing=false; preview=null; redraw(); }});
    cv.addEventListener('pointermove', e=>{ if(!drawing) return; const p=xy(e);
      if(tool.value==='free'){ actions.at(-1).points.push(p); redraw(); }
      else{ const base={color:color.value,size:+size.value,start:{x:sx,y:sy},end:p};
        if(tool.value==='circle')  preview={type:'circle', ...base};
        if(tool.value==='ellipse') preview={type:'ellipse',...base};
        if(tool.value==='line')    preview={type:'line',   ...base};
        if(tool.value==='rect')    preview={type:'rect',   ...base};
        redraw();
      }});
    const commit=()=>{ if(preview){ actions.push(preview); preview=null; redraw(); } };
    cv.addEventListener('pointerup',()=>{ drawing=false; commit(); });
    cv.addEventListener('pointerleave',()=>{ drawing=false; commit(); });

    undo.onclick = ()=>{ actions.pop(); redraw(); };
    clear.onclick= ()=>{ actions.length=0; preview=null; redraw(); };

    return {
      getComment: ()=> (desc.value||'').trim(),
      async getUploadBlob(){
        const r=img.getBoundingClientRect(); const w=Math.round(r.width), h=Math.round(r.height); const dpr=window.devicePixelRatio||1;
        const off=document.createElement('canvas'); off.width=Math.round(w*dpr); off.height=Math.round(h*dpr);
        const ox=off.getContext('2d'); ox.setTransform(dpr,0,0,dpr,0,0);
        ox.drawImage(img,0,0,w,h); ox.drawImage(cv,0,0,w,h);
        const dataURL=off.toDataURL('image/png'); const rawBlob=dataURLtoBlob(dataURL);
        return await compressToLimit(rawBlob);
      }
    };
  }

  /* ===== NUEVA imagen ===== */
  function createEditorNew(container, tabla_lado, file){
    if(!P.subirImagenes) return err('No puedes subir imágenes.');
    const id='ed_'+Math.random().toString(36).slice(2,9);
    container.insertAdjacentHTML('beforeend', `
      <div class="col-12" id="${id}_col">
        <div class="editor-card">
          <div class="editor-toolbar">
            <div class="ctrl"><label>Tipo de Marca</label>
              <select id="${id}_tool" class="form-control form-control-sm" style="width:auto">
                <option value="free">Libre</option>
                <option value="line">Línea</option>
                <option value="rect">Rectángulo</option>
                <option value="ellipse">Elipse</option>
                <option value="circle">Círculo</option>
                <option value="cross">Cruz</option>
              </select>
            </div>
            <div class="ctrl"><label>Color</label><input id="${id}_color" type="color" value="#ff0000"></div>
            <div class="ctrl"><label>Grosor</label>
              <select id="${id}_size" class="form-control form-control-sm" style="width:auto">
                <option>2</option><option selected>3</option><option>4</option><option>6</option><option>8</option><option>12</option>
              </select>
            </div>
            <div class="ctrl" style="flex:1 1 260px;min-width:240px">
              <label>Comentario</label>
              <input id="${id}_desc" type="text" class="form-control form-control-sm" placeholder="Descripción del daño">
            </div>
            <button id="${id}_undo" class="btn btn-secondary btn-sm">Deshacer</button>
            <button id="${id}_clear" class="btn btn-outline-secondary btn-sm">Limpiar</button>
            <button id="${id}_discard" class="btn btn-danger btn-sm">Eliminar</button>
            <button id="${id}_save" class="btn btn-primary btn-sm">Guardar</button>
          </div>
          <div class="editor-canvas-wrap">
            <img id="${id}_img" alt="">
            <canvas id="${id}_cv" class="editor-canvas"></canvas>
          </div>
          <div class="progress-wrap">
            <div class="progress" id="${id}_pwrap"><div class="progress-bar" id="${id}_pbar" style="width:0%"></div></div>
            <div class="progress-info" id="${id}_ptxt">Preparando...</div>
          </div>
        </div>
      </div>
    `);

    const url=URL.createObjectURL(file);
    document.getElementById(id+'_img').src=url;

    const editor=mountEditor({ rootId:id, baseImgUrl:url, initialComment:'' });

    const pwrap=document.getElementById(id+'_pwrap');
    const pbar=document.getElementById(id+'_pbar');
    const ptxt=document.getElementById(id+'_ptxt');
    const setP=(pct,t)=>{ pbar.style.width=pct+'%'; ptxt.textContent=t||pct+'%'; pwrap.classList.add('show'); ptxt.classList.add('show'); };

    document.getElementById(id+'_discard').onclick=()=>{ document.getElementById(id+'_col')?.remove(); URL.revokeObjectURL(url); };

    // Evitar duplicados al guardar
    let savingNew = false;
    document.getElementById(id+'_save').onclick=async ()=>{
      if (savingNew) return;
      savingNew = true;

      const saveBtn=document.getElementById(id+'_save');
      const undoBtn=document.getElementById(id+'_undo');
      const clearBtn=document.getElementById(id+'_clear');
      const discardBtn=document.getElementById(id+'_discard');

      const comentario=editor.getComment();
      if(!comentario){
        err('Agrega un comentario.');
        savingNew=false;
        return;
      }
      try{
        saveBtn.disabled=undoBtn.disabled=clearBtn.disabled=discardBtn.disabled=true;
        setP(1,'Comprimiendo...'); const blob=await editor.getUploadBlob();
        const fd=new FormData(); fd.append('tabla_lado',tabla_lado); fd.append('IdRegTab',String(folio)); fd.append('comentarios',comentario); fd.append('file',blob,'image.jpg');
        setP(10,'Subiendo...');
        const j=await uploadWithProgress({
          url:"{{ route('imagenes.adjuntos.store') }}",
          formData:fd, headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'},
          onProgress:(pct)=>setP(Math.max(10,pct),`Subiendo ${pct}%`)
        });
        if(!j.ok) throw new Error(j.error||'Error');
        setP(100,'Completado'); ok('Imagen guardada'); location.reload();
      }catch(e){
        err(e.message||'No se pudo subir');
        savingNew=false;
        // re-habilita solo si falló
        try{ saveBtn.disabled=undoBtn.disabled=clearBtn.disabled=discardBtn.disabled=false; }catch(_){}
      }
    };
  }

  /* ===== EXISTENTE (editar con marcas) ===== */
  function createEditorExisting({container, tabla_lado, idAdjunto, imageUrl, initialComment}){
    const id='ed_'+Math.random().toString(36).slice(2,9);
    container.insertAdjacentHTML('beforeend', `
      <div class="col-12" id="${id}_col">
        <div class="editor-card">
          <div class="editor-toolbar">
            <div class="ctrl"><label>Tipo de Marca</label>
              <select id="${id}_tool" class="form-control form-control-sm" style="width:auto">
                <option value="free">Libre</option>
                <option value="line">Línea</option>
                <option value="rect">Rectángulo</option>
                <option value="ellipse">Elipse</option>
                <option value="circle">Círculo</option>
                <option value="cross">Cruz</option>
              </select>
            </div>
            <div class="ctrl"><label>Color</label><input id="${id}_color" type="color" value="#ff0000"></div>
            <div class="ctrl"><label>Grosor</label>
              <select id="${id}_size" class="form-control form-control-sm" style="width:auto">
                <option>2</option><option selected>3</option><option>4</option><option>6</option><option>8</option><option>12</option>
              </select>
            </div>
            <div class="ctrl" style="flex:1 1 260px;min-width:240px">
              <label>Comentario</label>
              <input id="${id}_desc" type="text" class="form-control form-control-sm" placeholder="Descripción del daño" value="${(initialComment||'').replace(/"/g,'&quot;')}">
            </div>
            <button id="${id}_undo" class="btn btn-secondary btn-sm">Deshacer</button>
            <button id="${id}_clear" class="btn btn-outline-secondary btn-sm">Limpiar</button>
            <button id="${id}_discard" class="btn btn-danger btn-sm">Eliminar</button>
            <button id="${id}_save" class="btn btn-primary btn-sm">Guardar</button>
          </div>
          <div class="editor-canvas-wrap">
            <img id="${id}_img" alt="">
            <canvas id="${id}_cv" class="editor-canvas"></canvas>
          </div>
          <div class="progress-wrap">
            <div class="progress" id="${id}_pwrap"><div class="progress-bar" id="${id}_pbar" style="width:0%"></div></div>
            <div class="progress-info" id="${id}_ptxt">Preparando...</div>
          </div>
        </div>
      </div>
    `);

    const editor=mountEditor({ rootId:id, baseImgUrl:imageUrl, initialComment });

    const pwrap=document.getElementById(id+'_pwrap'), pbar=document.getElementById(id+'_pbar'), ptxt=document.getElementById(id+'_ptxt');
    const setP=(pct,t)=>{ pbar.style.width=pct+'%'; ptxt.textContent=t||pct+'%'; pwrap.classList.add('show'); ptxt.classList.add('show'); };

    document.getElementById(id+'_discard').onclick=()=> document.getElementById(id+'_col')?.remove();

    // Evitar duplicados al guardar
    let savingUpd = false;
    document.getElementById(id+'_save').onclick=async ()=>{
      if (savingUpd) return;
      savingUpd = true;

      const saveBtn=document.getElementById(id+'_save');
      const undoBtn=document.getElementById(id+'_undo');
      const clearBtn=document.getElementById(id+'_clear');
      const discardBtn=document.getElementById(id+'_discard');

      const comentario=editor.getComment();
      if(!comentario){
        err('Agrega un comentario.');
        savingUpd=false;
        return;
      }
      try{
        saveBtn.disabled=undoBtn.disabled=clearBtn.disabled=discardBtn.disabled=true;
        setP(1,'Comprimiendo...'); const blob=await editor.getUploadBlob();
        const fd=new FormData(); fd.append('tabla_lado',tabla_lado); fd.append('IdRegTab',String(folio)); fd.append('comentarios',comentario); fd.append('file',blob,'image.jpg');
        setP(10,'Subiendo...');
        const j=await uploadWithProgress({
          url:"{{ route('imagenes.adjuntos.store') }}",
          formData:fd, headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'},
          onProgress:(pct)=> setP(Math.max(10,pct), `Subiendo ${pct}%`)
        });
        if(!j.ok) throw new Error(j.error||'Error al subir');

        await fetch("{{ url('imagenes/adjuntos') }}/"+idAdjunto, { method:'DELETE', headers:{'Accept':'application/json','X-CSRF-TOKEN':CSRF} });

        ok('Imagen actualizada'); location.reload();
      }catch(e){
        err(e.message||'Falló la operación');
        savingUpd=false;
        try{ saveBtn.disabled=undoBtn.disabled=clearBtn.disabled=discardBtn.disabled=false; }catch(_){}
      }
    };
  }

  /* ===== Guardar comentario de imagen ===== */
  document.querySelectorAll('[data-cmt-save]').forEach(btn=>{
    btn.addEventListener('click', async ()=>{
      const id=btn.dataset.cmtSave; const val=(document.querySelector(`[data-cmt="${id}"]`)?.value||'').trim();
      try{
        const r=await fetch("{{ url('imagenes/adjuntos') }}/"+id+"/comentario",{
          method:'PATCH', headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':CSRF},
          body:JSON.stringify({comentarios:val})
        }); const j=await r.json(); if(!r.ok||!j.ok) throw 0; ok('Comentario guardado');
      }catch(_){ err('No se pudo guardar'); }
    });
  });

  /* ===== Confirmar / Revertir / Eliminar imagen ===== */
  document.querySelectorAll('[data-confirm]').forEach(b=>{
    b.addEventListener('click', async ()=>{
      const id=b.dataset.confirm;
      const c=await Swal.fire({icon:'question',title:'¿Confirmar?',showCancelButton:true,confirmButtonText:'Sí'});
      if(!c.isConfirmed) return;
      await fetch("{{ url('imagenes/adjuntos') }}/"+id+"/confirmar",{method:'PATCH',headers:{'Accept':'application/json','X-CSRF-TOKEN':CSRF}});
      ok('Confirmado'); location.reload();
    });
  });
  document.querySelectorAll('[data-unconfirm]').forEach(b=>{
    b.addEventListener('click', async ()=>{
      const id=b.dataset.unconfirm;
      await fetch("{{ url('imagenes/adjuntos') }}/"+id+"/revertir",{method:'PATCH',headers:{'Accept':'application/json','X-CSRF-TOKEN':CSRF}});
      ok('Revertido'); location.reload();
    });
  });
  document.querySelectorAll('[data-delete]').forEach(b=>{
    b.addEventListener('click', async ()=>{
      const id=b.dataset.delete;
      const c=await Swal.fire({icon:'question',title:'¿Eliminar imagen?',showCancelButton:true,confirmButtonText:'Sí, eliminar'});
      if(!c.isConfirmed) return;
      await fetch("{{ url('imagenes/adjuntos') }}/"+id,{method:'DELETE',headers:{'Accept':'application/json','X-CSRF-TOKEN':CSRF}});
      ok('Eliminado'); location.reload();
    });
  });

  /* ===== Editar marcas (imagen existente) ===== */
  document.querySelectorAll('[data-markup]').forEach(btn=>{
    btn.addEventListener('click', ()=>{
      const id     = btn.dataset.markup;
      const tabla  = btn.dataset.tabla;
      const url    = btn.dataset.url;
      const cmt    = btn.getAttribute('data-cmt') || '';
      const wrap   = document.getElementById('edit_'+tabla);
      if(!wrap) return;
      wrap.innerHTML = '';
      createEditorExisting({
        container      : wrap,
        tabla_lado     : tabla,
        idAdjunto      : id,
        imageUrl       : url,
        initialComment : cmt
      });
      try{ wrap.scrollIntoView({behavior:'smooth', block:'start'}); }catch(_){}
    });
  });

  /* ===== Evidencia: subir / eliminar ===== */
  document.querySelectorAll('[data-ev-upload]').forEach(btn=>{
    btn.addEventListener('click', async ()=>{
      if(!P.subirIEvidencia) return err('No puedes subir evidencia.');
      if(!isMobileUA()){ err('Para subir evidencia debes realizar el proceso desde un dispositivo móvil.'); return; }

      const unidadId = btn.getAttribute('data-unidad-id') || document.getElementById('docUnidadId')?.textContent || '';
      const unidadText = btn.getAttribute('data-unidad-text') || document.getElementById('docUnidadText')?.textContent || '';

      try{
        const txt=await startQREvi();
        if(!matchUnidad(txt,unidadId,unidadText)){
          Swal.fire({icon:'error', title:'Unidad no coincide',
                     html:`Leído: <b>${(txt||'').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[m]))}</b>`});
          return;
        }
      }catch(_){ return; }

      const picker=document.createElement('input');
      picker.type='file'; picker.accept='image/*'; picker.capture='environment';
      picker.onchange=async ()=>{
        const f=picker.files?.[0]; if(!f) return;
        try{
          let blob=f; try{ const tmp=await compressToLimit(f); blob=new File([tmp],'evidencia.jpg',{type:'image/jpeg'}); }catch(_){}
          const fd=new FormData(); fd.append('file', blob);
          const id=btn.dataset.evUpload;
          const r=await fetch("{{ url('imagenes/adjuntos') }}/"+id+"/evidencia",{method:'POST',headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'},body:fd});
          if(r.status===413) throw new Error('La evidencia excede el límite del servidor (HTTP 413).');
          const j=await r.json(); if(!r.ok||!j.ok) throw new Error(j.error||'No se pudo subir la evidencia');
          ok('Evidencia subida'); location.reload();
        }catch(e){ err(e.message||'No se pudo subir la evidencia'); }
      };
      picker.click();
    });
  });

  document.querySelectorAll('[data-ev-delete]').forEach(btn=>{
    btn.addEventListener('click', async ()=>{
      if(!P.borrarEvidencia) return err('No puedes borrar evidencia.');
      const id=btn.dataset.evDelete;
      const c=await Swal.fire({icon:'question',title:'¿Eliminar evidencia?',text:'Regresará a ATENDIDO.',showCancelButton:true,confirmButtonText:'Sí'});
      if(!c.isConfirmed) return;
      await fetch("{{ url('imagenes/adjuntos') }}/"+id+"/evidencia",{method:'DELETE',headers:{'X-CSRF-TOKEN':CSRF,'Accept':'application/json'}});
      ok('Evidencia eliminada'); location.reload();
    });
  });

  /* ===== Bloqueo suave del botón "atrás" para no salir del folio ===== */
  (function lockBackOnEdit(){
    const stayUrl = location.href;
    try { history.pushState({folio: {{ $folio }}}, '', stayUrl); } catch(_) {}
    window.addEventListener('popstate', () => {
      try { history.pushState({folio: {{ $folio }}}, '', stayUrl); } catch(_) {}
      Swal.fire({
        icon:'info',
        title:'Estás editando un folio',
        text:'Usa “Volver a la lista” para salir sin perder el contexto.',
        timer:2000, showConfirmButton:false
      });
    });
  })();

})();
</script>
@endpush
