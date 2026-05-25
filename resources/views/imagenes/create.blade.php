{{-- resources/views/imagenes/create.blade.php --}}
@extends('adminlte::page')

@section('title', 'Nuevo folio - Captura de imagen')

@section('content_header')
  <h1>Captura de Imagen (Nuevo folio)</h1>
@stop

@section('content')
  @include('imagenes.partials._alerts')

  <div class="card">
    <div class="card-body">
      {{-- Selector de unidad + acciones (reusa el partial ya corregido) --}}
      @include('imagenes.partials._selector_unidad', ['unidades' => $unidades])

      <hr>

      {{-- Estado del documento (folio/fecha/estatus/comentario/totales) --}}
      @include('imagenes.partials._estado_folio')

      {{-- Contenedor por lados (inputs y grids) --}}
      @include('imagenes.partials._lados')
    </div>
  </div>

  {{-- Modal QR de selección (existente) --}}
  @include('imagenes.modals._qr')
@stop

@push('css')
  <link rel="stylesheet" href="{{ asset('css/imagenes.css') }}">
  <style>
    .pane .pane-actions{ position:absolute; right:.35rem; top:.35rem; display:flex; gap:.35rem; z-index:2; }
    .thumb{ position:relative; border:1px solid #e1e1e1; border-radius:6px; overflow:hidden; }
    .thumb img{ width:100%; height:160px; object-fit:cover; display:block; }
    .thumb .badge{ position:absolute; left:6px; top:6px; }
    .editor-card{ border:1px solid #ddd; border-radius:.5rem; overflow:hidden; }
    .editor-toolbar{ background:#f8f9fa; padding:.5rem; display:flex; flex-wrap:wrap; gap:.5rem; align-items:center; }
    .editor-toolbar .ctrl{ display:flex; align-items:center; gap:.35rem; }
    .editor-toolbar .ctrl label{ margin:0; font-size:.75rem; color:#6c757d; min-width:74px; }
    .editor-canvas-wrap{ position:relative; width:100%; }
    .editor-canvas-wrap img{ width:100%; height:auto; display:block; }
    .editor-canvas{ position:absolute; left:0; top:0; z-index:2; touch-action:none; }
    .progress-wrap{ padding:.5rem .75rem .75rem; }
    .progress{ height:6px; display:none; }
    .progress.show{ display:block; }
    .progress-info{ font-size:.75rem; color:#6c757d; display:none; }
    .progress-info.show{ display:block; }
    .swal2-container{ z-index:3005 !important; }
  </style>
@endpush

@push('js')
  <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <script>
  // ===== Permisos (si los usas)
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
  (function(){
    const P = window.USER_PERMS || {};

    /* ====== Helpers ====== */
    const $ = sel => document.querySelector(sel);
    const warn = (t,m='')=>Swal.fire({icon:'warning',title:t,text:m});
    const ok   = (t,m='')=>Swal.fire({icon:'success',title:t,text:m,timer:1100,showConfirmButton:false});
    const escapeHtml = s => (s||'').replace(/[&<>"']/g, m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));
    const addNoCache = url => !url ? url : (url + (url.includes('?')?'&':'?') + 'v=' + Date.now());

    /* ====== Elements ====== */
    const $slc  = $('#slcUnidad');
    const $btnCrear = $('#btnCrearDoc');
    const $btnLeerQR = $('#btnLeerQR');
    const $btnDeshacer = $('#btnDeshacerUnidad');

    const estadoDoc   = $('#estadoDoc');
    const bannerSinDoc= $('#bannerSinDoc');
    const docFolio    = $('#docFolio');
    const docFecha    = $('#docFecha');
    const docUnidad   = $('#docUnidad');
    const docEstatus  = $('#docEstatus');
    const docTotales  = $('#docTotales');

    const docComentarioWrap  = $('#docComentarioWrap');
    const docComentarioInp   = $('#docComentario');
    const docComentarioLbl   = $('#docComentarioLabel');
    const btnGuardarDocCmt   = $('#btnGuardarDocComentario');
    const frmCerrar          = $('#frmCerrar');

    /* ====== Estado local ====== */
    let estadoActualDoc = null;

    /* ====== UI inicial ====== */
    function resetUI(){
      $('#contenedorLados').classList.add('d-none');
      estadoDoc.classList.add('d-none');
      bannerSinDoc.classList.add('d-none');
      $btnDeshacer.classList.add('d-none');
      $btnCrear.disabled = true;
      $btnLeerQR.disabled = false;
      $slc.disabled = false;
      $slc.value = '';
      limpiarGrids();
    }
    function afterUnidadSelected(){
      $('#contenedorLados').classList.remove('d-none');
      estadoDoc.classList.add('d-none');
      bannerSinDoc.classList.remove('d-none');
      $btnDeshacer.classList.remove('d-none');
      $btnCrear.disabled = false;
      $btnLeerQR.disabled = false;
    }
    resetUI();

    $slc.addEventListener('change', ()=>{
      if($slc.value){ afterUnidadSelected(); }
      else { resetUI(); }
    });

    $btnDeshacer.addEventListener('click', resetUI);

    /* ====== Crear Folio ABIERTO ====== */
    $btnCrear.addEventListener('click', async ()=>{
      if(!$slc.value) return warn('Selecciona una unidad');
      const conf = await Swal.fire({icon:'question', title:'¿Crear Folio?', text:'Se abrirá un folio ABIERTO para la unidad seleccionada.', showCancelButton:true, confirmButtonText:'Sí, crear'});
      if(!conf.isConfirmed) return;
      $btnCrear.disabled = true;
      try{
        const r = await fetch(`{{ route('imagenes.ajax.crearAbierto') }}`,{
          method:'POST',
          headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
          body: JSON.stringify({ IdUnidad: $slc.value })
        });
        const j = await r.json();
        if(!j.ok) throw new Error(j.error || 'No se pudo crear el folio');
        await cargarUnidad($slc.value);
        ok('Folio creado');
        $btnCrear.disabled = true; $btnLeerQR.disabled = true; $slc.disabled = true;
      }catch(e){
        Swal.fire({icon:'error', title:'Error', text:e.message});
        $btnCrear.disabled = false;
      }
    });

    /* ====== Guardar comentario del folio ====== */
    btnGuardarDocCmt?.addEventListener('click', async ()=>{
      const folio = docFolio.textContent;
      const val = (docComentarioInp.value||'').trim();
      if(!folio) return;
      if(!val) return warn('Falta comentario','Escribe un comentario general.');
      const r = await fetch(`{{ url('imagenes/folio') }}/${folio}/comentario`, {
        method:'PATCH', headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
        body: JSON.stringify({ comentarios: val })
      });
      const j = await r.json();
      if(!j.ok) return Swal.fire({icon:'error',title:'Error',text:j.error||'No se pudo guardar'});
      ok('Comentario guardado');
      await cargarUnidad($slc.value);
    });

    /* ====== Cargar estado de unidad (folio abierto si existe) ====== */
    async function cargarUnidad(idUnidad){
      const url = `{{ url('imagenes/ajax/estado-unidad') }}/${idUnidad}`;
      estadoDoc.classList.add('d-none');
      bannerSinDoc.classList.add('d-none');
      try{
        const r = await fetch(url, {headers:{'Accept':'application/json'}});
        const j = await r.json();

        limpiarGrids();

        if(!j.doc){
          // no hay folio abierto
          estadoActualDoc = null;
          bannerSinDoc.classList.remove('d-none');
          $btnCrear.disabled=false; $btnLeerQR.disabled=false; $slc.disabled=false;
          return;
        }

        // Sí hay folio
        estadoActualDoc = j.doc;

        estadoDoc.classList.remove('d-none');
        docFolio.textContent  = j.doc.Folio;
        docFecha.textContent  = j.doc.Fecha;
        docUnidad.textContent = j.doc.Unidad || '';
        docEstatus.textContent = j.doc.Estatus;
        docEstatus.className = 'badge ' + (j.doc.Estatus==='ABIERTO' ? 'badge-warning' :
                            j.doc.Estatus==='EN PROCESO' ? 'badge-info' :
                            j.doc.Estatus==='CERRADO' ? 'badge-success' : 'badge-secondary');

        docComentarioInp.value = j.doc.Comentarios || '';
        docComentarioLbl.textContent = j.doc.Comentarios || '(sin comentario)';
        if (j.doc.Estatus === 'ABIERTO') { docComentarioWrap.classList.remove('d-none'); }
        else { docComentarioWrap.classList.add('d-none'); }

        docTotales.textContent =
          `Del ${j.doc.totales.Del[0]}/${j.doc.totales.Del[1]} · `+
          `Tra ${j.doc.totales.Tra[0]}/${j.doc.totales.Tra[1]} · `+
          `Izq ${j.doc.totales.Izq[0]}/${j.doc.totales.Izq[1]} · `+
          `Der ${j.doc.totales.Der[0]}/${j.doc.totales.Der[1]} · `+
          `EspIzq ${j.doc.totales.EspIzq[0]}/${j.doc.totales.EspIzq[1]} · `+
          `EspDer ${j.doc.totales.EspDer[0]}/${j.doc.totales.EspDer[1]}`;

        frmCerrar.action = `{{ url('imagenes/cerrar') }}/${j.doc.Folio}`;

        // Render por lados
        const lados = ['TblImagenDel','TblImagenTra','TblImagenIzq','TblImagenDer','TblImagenEspIzq','TblImagenEspDer'];
        lados.forEach(k=>{
          const grid = document.getElementById('grid_'+k);
          const arr  = (j.adjuntos && j.adjuntos[k]) ? j.adjuntos[k] : [];
          arr.filter(it => it.Estatus !== 'EVIDENCIA').forEach(item=>{
            const col = document.createElement('div');
            col.className = 'col';
            col.innerHTML = `
              <div class="thumb">
                <a href="${encodeURI(item.Url)}" target="_blank" rel="noopener">
                  <img src="${encodeURI(addNoCache(item.Url))}" alt="">
                </a>
                <span class="badge ${ item.Estatus==='PENDIENTE' ? 'badge-warning' :
                                      item.Estatus==='ATENDIDO' ? 'badge-info' :
                                      item.Estatus==='DOCUMENTADO' ? 'badge-primary' :
                                      item.Estatus==='CONFIRMADO' ? 'badge-success' : 'badge-secondary' }">${item.Estatus}</span>
              </div>
              <div class="input-group input-group-sm mt-1">
                <input class="form-control form-control-sm" placeholder="Descripción del daño" value="${escapeHtml(item.Comentarios||'')}" ${item.Estatus==='PENDIENTE'?'':'disabled'} data-cmt-local="${item.Id}">
                <div class="input-group-append">
                  <button class="btn btn-outline-primary" ${item.Estatus==='PENDIENTE'?'':'disabled'} data-savecmt-local="${item.Id}">Guardar</button>
                </div>
              </div>`;
            grid.appendChild(col);
          });
        });

        // Guardar comentario de tarjeta (solo PENDIENTE)
        document.querySelectorAll('[data-savecmt-local]').forEach(btn=>{
          btn.onclick = async ()=>{
            const id = btn.getAttribute('data-savecmt-local');
            const inp = document.querySelector(`[data-cmt-local="${id}"]`);
            const val = (inp?.value||'').trim();
            if(!val) return warn('Falta comentario','Agrega un comentario.');
            try{
              const r = await fetch(`{{ url('imagenes/adjuntos') }}/${id}/comentario`, {
                method:'PATCH',
                headers:{'Content-Type':'application/json','Accept':'application/json','X-CSRF-TOKEN':'{{ csrf_token() }}'},
                body: JSON.stringify({ comentarios: val })
              });
              const jx = await r.json(); if(!jx.ok) throw new Error(jx.error||'Error');
              ok('Comentario actualizado');
            }catch(e){ Swal.fire({icon:'error', title:'Error', text:e.message}); }
          };
        });

        // Bloqueos de UI
        $btnCrear.disabled = true; $btnLeerQR.disabled = true; $slc.disabled = true; $btnDeshacer.classList.remove('d-none');
      }catch(e){
        limpiarGrids(); bannerSinDoc.classList.remove('d-none'); estadoActualDoc = null;
      }
    }

    /* ====== Inputs “Añadir fotos” (sólo cámara en móvil, acepta PNG/JPG en PC) ====== */
    document.querySelectorAll('[data-choose]').forEach(btn=>{
      if(!P.subirImagenes) btn.style.display = 'none';
      btn.addEventListener('click', ()=>{
        if(!P.subirImagenes) return warn('Sin permiso','No puedes subir imágenes.');
        const folio = docFolio.textContent;
        if(!folio) return warn('Primero crea/carga un Folio ABIERTO.');
        const k = btn.getAttribute('data-choose');
        document.getElementById('inp_'+k).click();
      });
    });

    // configura accept/capture y crea editores por archivo seleccionado
    ;['TblImagenDel','TblImagenTra','TblImagenIzq','TblImagenDer','TblImagenEspIzq','TblImagenEspDer'].forEach(k=>{
      const inp = document.getElementById('inp_'+k);
      const wrap = document.getElementById('edit_'+k);
      if(!inp) return;
      inp.setAttribute('accept','image/*');     // PNG/JPG/GIF desde PC
      inp.setAttribute('capture','environment'); // trasera en móvil (best effort)
      inp.addEventListener('change', ()=>{
        const folio = docFolio.textContent;
        if(!folio){ warn('Primero crea un Folio ABIERTO.'); inp.value=''; return; }
        const files = Array.from(inp.files||[]);
        files.forEach(f => createEditorCard(wrap, k, f));
        inp.value='';
      });
    });

    /* ====== Uploader + editor (NUEVA imagen) ====== */
    function dataURLtoBlob(dataURL){
      const parts = dataURL.split(',');
      const mime = parts[0].match(/:(.*?);/)[1] || 'image/png';
      const bstr = atob(parts[1]); let n = bstr.length; const u8 = new Uint8Array(n);
      while(n--) u8[n] = bstr.charCodeAt(n);
      return new Blob([u8], {type:mime});
    }
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

    function createEditorCard(container, tabla_lado, file){
      if(!P.subirImagenes) return warn('Sin permiso','No puedes subir imágenes.');
      const id = 'ed_' + Math.random().toString(36).slice(2,9);
      container.insertAdjacentHTML('beforeend', `
        <div class="col" id="${id}_col">
          <div class="editor-card">
            <div class="editor-toolbar">
              <div class="ctrl"><label>Marca</label>
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
                <input type="text" class="form-control form-control-sm" id="${id}_desc" placeholder="Descripción del daño (obligatorio)">
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

      // Guardar: render base + marcas -> PNG -> subir
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

        const progWrap=document.getElementById(id+'_prog_wrap');
        const progBar =document.getElementById(id+'_prog');
        const progTxt=document.getElementById(id+'_prog_txt');
        function setProgress(pct, text){ progBar.style.width = pct + '%'; progTxt.textContent = text || (pct + '%'); }
        progWrap.classList.add('show'); progTxt.classList.add('show'); setProgress(1,'Preparando...');
        [saveBtn, undoBtn, clearBtn, discardBtn, toolSel, colorIn, sizeSel, descInp].forEach(el => el.disabled = true);

        try{
          const json = await uploadWithProgress({
            url: `{{ route('imagenes.adjuntos.store') }}`,
            formData: fd,
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
            onProgress: (pct)=> setProgress(pct, `Subiendo ${pct}%`)
          });
          if(!json.ok) throw new Error(json.error || 'Error al subir');
          setProgress(100,'Completado');
          colNode?.remove();
          await cargarUnidad($slc.value);
          ok('Imagen guardada');
        }catch(e){
          Swal.fire({icon:'error', title:'Error al subir', text:e.message || 'Falló la subida'});
          [saveBtn, undoBtn, clearBtn, discardBtn, toolSel, colorIn, sizeSel, descInp].forEach(el => el.disabled = false);
          progTxt.textContent = 'Error';
        }
      };
    }

    function limpiarGrids(){
      document.querySelectorAll('[id^="grid_"]').forEach(n => n.innerHTML = '');
      document.querySelectorAll('[id^="edit_"]').forEach(n => n.innerHTML = '');
    }

    /* ====== QR de selección (modal existente) ====== */
    let qr;
    $('#qrModal').on('shown.bs.modal', function(){
      if(qr) return;
      qr = new Html5Qrcode("qrReader");
      Html5Qrcode.getCameras().then(cams=>{
        if(!cams || !cams.length){ warn('No se encontraron cámaras'); return; }
        let chosen = cams.find(c => /back|rear|environment/i.test(c.label||'')) || cams[0];
        const camId = chosen.id || chosen.deviceId || chosen.cameraId;
        qr.start(camId, { fps: 10, qrbox: { width: 220, height: 220 } }, (text) => {
          $('#btnCerrarQR').trigger('click');
          const t = (text||'').trim().toLowerCase();
          // 1) por Id exacto
          let opt = $slc.querySelector(`option[value="${text.trim()}"]`);
          if(!opt){
            // 2) por texto visible
            for(const o of $slc.options){ if(o.text.toLowerCase().includes(t)){ opt=o; break; } }
          }
          if(opt){ $slc.value = opt.value; afterUnidadSelected(); }
          else { Swal.fire({icon:'info', title:'QR leído', text:text + ' (no coincide con ninguna unidad)'}); }
        });
      }).catch(()=> Swal.fire({icon:'error', title:'No fue posible acceder a la cámara'}));
    }).on('hidden.bs.modal', function(){ stopQR(); });

    document.getElementById('btnStopQR')?.addEventListener('click', stopQR);
    function stopQR(){ if(qr){ qr.stop().then(()=>{ qr.clear(); qr=null; }).catch(()=>{ try{qr.clear();}catch(_){ } qr=null; }); } }
  })();
  </script>
@endpush
