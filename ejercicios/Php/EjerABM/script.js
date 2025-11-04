// script.js
(() => {
  // ===== Helpers =====
  const $  = (s, c=document) => c.querySelector(s);
  const $$ = (s, c=document) => Array.from(c.querySelectorAll(s));

  const tbody = $('#tbody');
  const pie   = $('#pie');

  function mostrarModal(id){ const n = $('#'+id); if(!n) return; n.classList.remove('oculto'); n.setAttribute('aria-hidden','false'); }
  function ocultarModal(id){ const n = $('#'+id); if(!n) return; n.classList.add('oculto');   n.setAttribute('aria-hidden','true'); }

  // ===== Estado en memoria (para filtros cliente) =====
  let registros = []; // última lista recibida para filtrar localmente

  // ===== Referencias de filtros =====
  const filtros = {
    legajo  : $('#f_legajo'),
    nombre  : $('#f_nombre'),
    fecha   : $('#f_fecha'),
    mes     : $('#f_mes'),
    sueldo  : $('#f_sueldo'),
    concepto: $('#f_concepto'),
    monto   : $('#f_monto'),
  };
  const btnLimpiar = $('#btnLimpiar');

  // ===== Utilidades de filtrado =====
  const norm = v => String(v ?? '').toLowerCase();
  const debounce = (fn, ms=160) => { let t; return (...a)=>{ clearTimeout(t); t = setTimeout(()=>fn(...a), ms); }; };

  function pasaFiltros(r){
    const f = {
      legajo  : norm(filtros.legajo?.value),
      nombre  : norm(filtros.nombre?.value),
      fecha   : String(filtros.fecha?.value || '').trim(),   // aaaa-mm-dd o subcadena
      mes     : norm(filtros.mes?.value),                    // texto (Enero, ...)
      sueldo  : String(filtros.sueldo?.value || '').trim(), // num mínimo
      concepto: norm(filtros.concepto?.value),
      monto   : String(filtros.monto?.value || '').trim(),   // num mínimo
    };

    if (f.legajo  && !norm(r.LegajoEmpleado).includes(f.legajo)) return false;
    if (f.nombre  && !norm(r.ApellidoYNombres).includes(f.nombre)) return false;
    if (f.fecha   && !String(r.Fecha_liquidacion||'').includes(f.fecha)) return false;
    if (f.mes     && !norm(r.MesDeLiquidacion).includes(f.mes)) return false;
    if (f.sueldo  && Number(r.SueldoBasico) < Number(f.sueldo)) return false;
    if (f.concepto&& !norm(r.concepto_no_remunerativo_1||'').includes(f.concepto)) return false;
    if (f.monto   && Number(r.Monto_no_remunerativo_1) < Number(f.monto)) return false;

    return true;
  }

  function renderFiltrado(){
    const lista = (registros || []).filter(pasaFiltros);
    tbody.innerHTML = '';
    if (!lista.length){
      tbody.innerHTML = '<tr><td colspan="10">Sin resultados</td></tr>';
      pie.innerHTML   = 'Alumno: <strong>Bustamante Agustin</strong> · Total: 0';
      return;
    }
    const frag = document.createDocumentFragment();
    for (const reg of lista){
      const tr = document.createElement('tr');
      tr.dataset.legajo = reg.LegajoEmpleado;
      tr.innerHTML = `
        <td>${reg.LegajoEmpleado}</td>
        <td>${reg.ApellidoYNombres}</td>
        <td>${reg.Fecha_liquidacion}</td>
        <td>${reg.MesDeLiquidacion}</td>
        <td>${Number(reg.SueldoBasico||0).toFixed(2)}</td>
        <td>${reg.concepto_no_remunerativo_1 || ''}</td>
        <td>${Number(reg.Monto_no_remunerativo_1||0).toFixed(2)}</td>
        <td>${Number(reg.pdf_bytes)>0 ? '<button class="boton-accion" data-accion="pdf">PDF</button>' : '-'}</td>
        <td><button class="boton-accion" data-accion="modi">Modi</button></td>
        <td><button class="boton-accion" data-accion="borrar">Borrar</button></td>
      `;
      frag.appendChild(tr);
    }
    tbody.appendChild(frag);
    pie.innerHTML = `Alumno: <strong>Bustamante Agustin</strong> · Total: ${lista.length}`;
  }

  const actualizarFiltrado = debounce(renderFiltrado);

  // ===== Armar params para POST (orden + mes num opcional) =====
  function armarParams(){
    const p = new URLSearchParams();
    p.append('orden', $('#orden').value);

    // El backend ya acepta f_liquidaciones_mes_num (1..12). Mapeamos texto -> número.
    const mesTxt = norm(filtros.mes?.value);
    const mapa = {enero:1,febrero:2,marzo:3,abril:4,mayo:5,junio:6,julio:7,agosto:8,septiembre:9,octubre:10,noviembre:11,diciembre:12};
    if (mesTxt && mapa[mesTxt]) p.append('f_liquidaciones_mes_num', String(mapa[mesTxt]));

    return p;
  }

  // ===== Cargar conceptos (alerta #1 del profe) =====
  async function cargarConceptos(){
    try{
      const r = await fetch('salidaJsonConceptos.php');
      const json = await r.json();
      alert('JSON de familias (datos iniciales):\n' + JSON.stringify(json));
      const sel = $('#CodConceptoNoRem');
      sel.innerHTML = '';
      (json.conceptos||[]).forEach(it=>{
        const o = document.createElement('option');
        o.value = it.CodigoConcepto;
        o.textContent = `${it.CodigoConcepto} – ${it.Descripcion}`;
        sel.appendChild(o);
      });
    }catch(e){ console.error(e); }
  }

  // ===== Cargar lista (alerta “antes de Ajax” única) =====
  async function cargaTabla(){
    const params = armarParams();
    alert(
      'Variables a enviar (antes de Ajax):\n' +
      'orden = ' + params.get('orden') + '\n' +
      'f_liquidaciones_mes_num = ' + (params.get('f_liquidaciones_mes_num') || '(vacío)')
    );

    tbody.innerHTML = '<tr><td colspan="10">Cargando…</td></tr>';
    try{
      const r = await fetch('salidaJsonLiquidacionesConPrepare.php', {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: params.toString()
      });
      const json = await r.json();

      // Si el servidor envía error formal, lo mostramos 1 sola vez.
      if (json?.error){
        alert('Respuesta listar:\n' + JSON.stringify(json, null, 2));
        registros = [];
        renderFiltrado();
        return;
      }

      registros = json.liquidaciones || [];
      renderFiltrado(); // respeta lo que esté tipeado en los inputs
    }catch(e){
      console.error(e);
      tbody.innerHTML = '<tr><td colspan="10">Error de comunicación con el servidor.</td></tr>';
    }
  }

  // ===== ABM & PDF =====
  function abrirAlta(){
    $('#tituloForm').textContent = 'Alta de liquidación';
    $('#accion').value = 'alta';
    $('#legajoOriginal').value = '';
    $('#formLiquidacion').reset();
    mostrarModal('modalForm');
  }

  function abrirModi(reg){
    $('#tituloForm').textContent = 'Modificar liquidación';
    $('#accion').value = 'modi';
    $('#legajoOriginal').value = reg.LegajoEmpleado;
    $('#LegajoEmpleado').value          = reg.LegajoEmpleado;
    $('#ApellidoYNombres').value        = reg.ApellidoYNombres;
    $('#Fecha_liquidacion').value       = reg.Fecha_liquidacion;
    $('#MesDeLiquidacion').value        = reg.MesDeLiquidacion;
    $('#SueldoBasico').value            = reg.SueldoBasico;
    $('#CodConceptoNoRem').value        = '';
    $('#Monto_no_remunerativo_1').value = reg.Monto_no_remunerativo_1;
    $('#pdf_liquidacion').value         = '';
    mostrarModal('modalForm');
  }

  async function mostrarPDF(legajo){
    try{
      const r = await fetch('pdf.php?legajo=' + encodeURIComponent(legajo));
      if (!r.ok){ alert('No hay documento PDF registrado'); return; }
      const blob = await r.blob();
      alert('PDF recibido. Tamaño: ' + blob.size + ' bytes'); // alerta #PDF
      const url = URL.createObjectURL(blob);
      const ifr = $('#iframePDF');
      if (ifr){ ifr.src = url; mostrarModal('modalPDF'); }
      else { window.open(url, '_blank'); }
    }catch(e){ console.error(e); alert('Error al obtener el PDF.'); }
  }

  async function enviarFormulario(ev){
    ev.preventDefault();
    const esAlta = ($('#accion').value === 'alta');
    const url = esAlta ? 'alta.php' : 'modi.php';
    const fd  = new FormData($('#formLiquidacion'));
    const leg = $('#LegajoEmpleado').value;

    if (esAlta){
      if (!confirm('¿Estás seguro de insertar el registro? ' + leg)) return;
    } else {
      if (!confirm('¿Estás seguro que desea modificar el registro ' + leg + '?')) return;
      fd.append('LegajoEmpleadoOriginal', $('#legajoOriginal').value);
    }

    try{
      const r = await fetch(url, { method:'POST', body: fd });
      const json = await r.json();
      alert('Respuesta del servidor:\n' + (json.estado || JSON.stringify(json))); // alerta de respuesta
      $('#textoRespuesta').textContent = json.estado || JSON.stringify(json);
      ocultarModal('modalForm'); mostrarModal('modalRespuesta');
      await cargaTabla();
    }catch(e){
      console.error(e);
      $('#textoRespuesta').textContent = 'Error de comunicación con el servidor.';
      ocultarModal('modalForm'); mostrarModal('modalRespuesta');
    }
  }

  async function eliminarRegistro(legajo){
    if (!confirm('¿Estás seguro que desea borrar eliminar el ' + legajo + '?')) return;
    try{
      const p = new URLSearchParams(); p.append('LegajoEmpleado', legajo);
      const r = await fetch('baja.php', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: p.toString()
      });
      const json = await r.json();
      alert('Respuesta de la baja:\n' + (json.estado || JSON.stringify(json)));
      $('#textoRespuesta').textContent = json.estado || JSON.stringify(json);
      mostrarModal('modalRespuesta');
      await cargaTabla();
    }catch(e){ console.error(e); alert('Error al eliminar.'); }
  }

  // ===== Delegaciones y listeners (una sola vez) =====
  // Botones barra
  $('#btnBuscar')?.addEventListener('click', cargaTabla);
  $('#btnVaciar')?.addEventListener('click', ()=>{
    registros = [];
    renderFiltrado();
  });
  $('#btnAlta')?.addEventListener('click', abrirAlta);

  // Botones modales
  $('#btnCancelarForm')?.addEventListener('click', ()=> ocultarModal('modalForm'));
  $('#btnCerrarPDF')?.addEventListener('click', ()=>{ $('#iframePDF').src=''; ocultarModal('modalPDF'); });
  $('#btnCerrarRespuesta')?.addEventListener('click', ()=> ocultarModal('modalRespuesta'));
  $('#formLiquidacion')?.addEventListener('submit', enviarFormulario);

  // Clicks en tabla (PDF / Modi / Borrar) — delegación
  tbody?.addEventListener('click', (ev)=>{
    const btn = ev.target.closest('button'); if(!btn) return;
    const tr  = ev.target.closest('tr');
    const acc = btn.dataset.accion;
    const leg = tr?.dataset?.legajo;
    if (acc === 'pdf')    return mostrarPDF(leg);
    if (acc === 'borrar') return eliminarRegistro(leg);
    if (acc === 'modi'){
      const c = tr.querySelectorAll('td');
      const reg = {
        LegajoEmpleado            : c[0].textContent,
        ApellidoYNombres          : c[1].textContent,
        Fecha_liquidacion         : c[2].textContent,
        MesDeLiquidacion          : c[3].textContent,
        SueldoBasico              : c[4].textContent,
        concepto_no_remunerativo_1: c[5].textContent,
        Monto_no_remunerativo_1   : c[6].textContent
      };
      return abrirModi(reg);
    }
  });

  // Orden por click en th -> setea input #orden
  $('#tabla thead')?.addEventListener('click', (ev)=>{
    const th = ev.target.closest('[data-orden]'); if(!th) return;
    $('#orden').value = th.dataset.orden;
  });

  // Filtros en vivo
  Object.values(filtros).forEach(inp=>{
    if (!inp) return;
    const ev = (inp.tagName === 'SELECT') ? 'change' : 'input';
    inp.addEventListener(ev, actualizarFiltrado);
  });

  // Limpiar filtros
  btnLimpiar?.addEventListener('click', ()=>{
    Object.values(filtros).forEach(f => f && (f.value = ''));
    renderFiltrado();
  });

  // Primera carga: combos
  cargarConceptos();
})();
