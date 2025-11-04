(function(){
  // ===== Helpers modales =====
  function mostrarModal(id){
    const n = document.getElementById(id); if(!n) return;
    n.classList.remove('oculto'); n.setAttribute('aria-hidden','false');
  }
  function ocultarModal(id){
    const n = document.getElementById(id); if(!n) return;
    n.classList.add('oculto'); n.setAttribute('aria-hidden','true');
  }

  // ===== Estado en memoria (para filtros cliente) =====
  let registrosCargados = []; // última lista recibida del server

  // ===== Referencias de filtros =====
  const filtros = {
    legajo  : () => (document.getElementById('f_legajo')?.value || '').toLowerCase(),
    nombre  : () => (document.getElementById('f_nombre')?.value || '').toLowerCase(),
    fecha   : () => (document.getElementById('f_fecha')?.value || ''),
    mes     : () => (document.getElementById('f_mes')?.value || '').toLowerCase(),
    sueldo  : () => (document.getElementById('f_sueldo')?.value || ''),
    concepto: () => (document.getElementById('f_concepto')?.value || '').toLowerCase(),
    monto   : () => (document.getElementById('f_monto')?.value || '')
  };

  // ===== Armar params para el POST (solo orden + si querés mes numérico del servidor) =====
  function armarParams(){
    const p = new URLSearchParams();
    p.append('orden', document.getElementById('orden').value);

    // Si querés seguir usando el filtro de MES en el servidor por número (1..12),
    // descomenta este bloque y mapeá f_mes -> número:
    // const mesTxt = (document.getElementById('f_mes')?.value || '').toLowerCase();
    // const mapa = {enero:1,febrero:2,marzo:3,abril:4,mayo:5,junio:6,julio:7,agosto:8,septiembre:9,octubre:10,noviembre:11,diciembre:12};
    // if (mapa[mesTxt]) p.append('f_liquidaciones_mes_num', String(mapa[mesTxt]));

    return p;
  }

  // ===== Render base de la tabla (NO aplica filtros) =====
  function renderTablaBase(list){
    const tbody = document.getElementById('tbody');
    tbody.innerHTML = '';
    if(!list || !list.length){
      tbody.innerHTML = '<tr><td colspan="10">Sin resultados</td></tr>';
      document.getElementById('pie').innerHTML = 'Alumno: <strong>Bustamante Agustin</strong> · Total: 0';
      return;
    }

    const frag = document.createDocumentFragment();
    list.forEach(reg=>{
      const tr = document.createElement('tr');
      tr.dataset.legajo = reg.LegajoEmpleado;

      // celdas
      const cols = [
        reg.LegajoEmpleado,
        reg.ApellidoYNombres,
        reg.Fecha_liquidacion,
        reg.MesDeLiquidacion,
        (Number(reg.SueldoBasico||0)).toFixed(2),
        reg.concepto_no_remunerativo_1 || '',
        (Number(reg.Monto_no_remunerativo_1||0)).toFixed(2)
      ];
      cols.forEach(val=>{
        const td = document.createElement('td');
        td.textContent = (val ?? '');
        tr.appendChild(td);
      });

      // PDF
      const tdPdf = document.createElement('td');
      if (Number(reg.pdf_bytes) > 0) {
        const b = document.createElement('button');
        b.type='button'; b.className='boton-accion'; b.dataset.accion='pdf'; b.textContent='PDF'; b.title='Ver PDF';
        tdPdf.appendChild(b);
      } else { tdPdf.textContent='-'; }
      tr.appendChild(tdPdf);

      // Modi
      const tdM=document.createElement('td'); const bm=document.createElement('button');
      bm.type='button'; bm.className='boton-accion'; bm.dataset.accion='modi'; bm.textContent='Modi';
      tdM.appendChild(bm); tr.appendChild(tdM);

      // Borrar
      const tdD=document.createElement('td'); const bd=document.createElement('button');
      bd.type='button'; bd.className='boton-accion'; bd.dataset.accion='borrar'; bd.textContent='Borrar';
      tdD.appendChild(bd); tr.appendChild(tdD);

      frag.appendChild(tr);
    });
    tbody.appendChild(frag);

    document.getElementById('pie').innerHTML =
      'Alumno: <strong>Bustamante Agustin</strong> · Total: ' + list.length;
  }

  // ===== Aplica filtros del header sobre registrosCargados y renderiza =====
  function aplicarFiltrosYRender(){
    const lista = registrosCargados;
    const f = {
      legajo  : filtros.legajo(),
      nombre  : filtros.nombre(),
      fecha   : filtros.fecha(),
      mes     : filtros.mes(),
      sueldo  : filtros.sueldo(),
      concepto: filtros.concepto(),
      monto   : filtros.monto()
    };

    const filtrados = lista.filter(r=>{
      if (f.legajo   && !String(r.LegajoEmpleado||'').toLowerCase().includes(f.legajo)) return false;
      if (f.nombre   && !String(r.ApellidoYNombres||'').toLowerCase().includes(f.nombre)) return false;
      if (f.fecha    && !String(r.Fecha_liquidacion||'').includes(f.fecha)) return false;
      if (f.mes      && !String(r.MesDeLiquidacion||'').toLowerCase().includes(f.mes)) return false;
      if (f.sueldo   && Number(r.SueldoBasico) < Number(f.sueldo)) return false;
      if (f.concepto && !String(r.concepto_no_remunerativo_1||'').toLowerCase().includes(f.concepto)) return false;
      if (f.monto    && Number(r.Monto_no_remunerativo_1) < Number(f.monto)) return false;
      return true;
    });

    renderTablaBase(filtrados);
  }

  // ===== Cargar lista (AJAX) =====
  async function cargaTabla(){
    const params = armarParams();

    // A L E R T A 1 — variables a enviar
    alert(
      'Variables a enviar (antes de Ajax):\n' +
      'orden = ' + params.get('orden') + '\n' +
      'f_liquidaciones_mes_num = ' + (params.get('f_liquidaciones_mes_num')||'(vacío)')
    );

    document.getElementById('tbody').innerHTML = '<tr><td colspan="10">Cargando…</td></tr>';

    try{
      const resp = await fetch('salidaJsonLiquidacionesConPrepare.php', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: params.toString()
      });
      const json = await resp.json();

      if (json && json.error){
        // A L E R T A 2 — si hubo error de conexión SQL/DB
        alert('Respuesta listar:\n' + JSON.stringify(json, null, 2));
        registrosCargados = [];
        renderTablaBase([]);
        return;
      }

      // Guardamos para poder filtrar en cliente
      registrosCargados = json.liquidaciones || [];
      // Render con filtros actuales (si hay)
      aplicarFiltrosYRender();

    }catch(err){
      console.error(err);
      document.getElementById('tbody').innerHTML =
        '<tr><td colspan="10">Error de comunicación con el servidor.</td></tr>';
    }
  }

  // ===== Combo de conceptos + alerta requerida =====
  async function cargarConceptos(){
    try{
      const resp = await fetch('salidaJsonConceptos.php');
      const json = await resp.json();

      // A L E R T A 0 — JSON de familias (datos iniciales)
      alert('JSON de familias (datos iniciales):\n' + JSON.stringify(json));

      const sel = document.getElementById('CodConceptoNoRem');
      sel.innerHTML = '';
      (json.conceptos || []).forEach(item=>{
        const opt = document.createElement('option');
        opt.value = item.CodigoConcepto;
        opt.textContent = `${item.CodigoConcepto} – ${item.Descripcion}`;
        sel.appendChild(opt);
      });
    }catch(err){ console.error(err); }
  }

  // ===== Alta / Modi / PDF / Baja =====
  function abrirAlta(){
    document.getElementById('tituloForm').textContent = 'Alta de liquidación';
    document.getElementById('accion').value = 'alta';
    document.getElementById('legajoOriginal').value = '';
    document.getElementById('formLiquidacion').reset();
    mostrarModal('modalForm');
  }

  function abrirModi(reg){
    document.getElementById('tituloForm').textContent = 'Modificar liquidación';
    document.getElementById('accion').value = 'modi';
    document.getElementById('legajoOriginal').value = reg.LegajoEmpleado;

    document.getElementById('LegajoEmpleado').value = reg.LegajoEmpleado;
    document.getElementById('ApellidoYNombres').value = reg.ApellidoYNombres;
    document.getElementById('Fecha_liquidacion').value = reg.Fecha_liquidacion;
    document.getElementById('MesDeLiquidacion').value = reg.MesDeLiquidacion;
    document.getElementById('SueldoBasico').value = reg.SueldoBasico;
    document.getElementById('CodConceptoNoRem').value = '';
    document.getElementById('Monto_no_remunerativo_1').value = reg.Monto_no_remunerativo_1;
    document.getElementById('pdf_liquidacion').value = '';
    mostrarModal('modalForm');
  }

  async function mostrarPDF(legajo){
    try{
      const r = await fetch('pdf.php?legajo=' + encodeURIComponent(legajo));
      if (!r.ok) { alert('No hay documento PDF registrado'); return; }
      const blob = await r.blob();
      // A L E R T A — llegada de binario
      alert('PDF recibido. Tamaño: ' + blob.size + ' bytes');
      const url = URL.createObjectURL(blob);
      const iframe = document.getElementById('iframePDF');
      if (iframe) { iframe.src = url; mostrarModal('modalPDF'); }
      else { window.open(url, '_blank'); }
    }catch(err){ console.error(err); alert('Error al obtener el PDF.'); }
  }

  async function enviarFormulario(ev){
    ev.preventDefault();
    const form = ev.target;
    const accion = document.getElementById('accion').value;
    const url = (accion === 'alta') ? 'alta.php' : 'modi.php';
    const fd = new FormData(form);

    const leg = document.getElementById('LegajoEmpleado').value;
    if (accion === 'modi') {
      if (!confirm('¿Estás seguro que desea modificar el registro ' + leg + '?')) return;
      fd.append('LegajoEmpleadoOriginal', document.getElementById('legajoOriginal').value);
    } else {
      if (!confirm('¿Estás seguro de insertar el registro? ' + leg)) return;
    }

    try{
      const resp = await fetch(url, { method:'POST', body: fd });
      const json = await resp.json();

      alert('Respuesta del servidor:\n' + (json.estado || JSON.stringify(json)));
      document.getElementById('textoRespuesta').textContent = json.estado || JSON.stringify(json);
      ocultarModal('modalForm'); mostrarModal('modalRespuesta');
      await cargaTabla(); // refrescar
    }catch(err){
      console.error(err);
      document.getElementById('textoRespuesta').textContent = 'Error de comunicación con el servidor.';
      ocultarModal('modalForm'); mostrarModal('modalRespuesta');
    }
  }

  async function eliminarRegistro(legajo){
    if(!confirm('¿Estás seguro que desea borrar eliminar el ' + legajo + '?')) return;
    try{
      const params = new URLSearchParams(); params.append('LegajoEmpleado', legajo);
      const resp = await fetch('baja.php', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: params.toString()
      });
      const json = await resp.json();
      alert('Respuesta de la baja:\n' + (json.estado || JSON.stringify(json)));
      document.getElementById('textoRespuesta').textContent = json.estado || JSON.stringify(json);
      mostrarModal('modalRespuesta'); await cargaTabla();
    }catch(err){ console.error(err); alert('Error al eliminar.'); }
  }

  // ===== Delegaciones y listeners (una sola vez) =====
  document.addEventListener('DOMContentLoaded', ()=>{
    // Botones principales
    document.getElementById('btnBuscar')?.addEventListener('click', cargaTabla);
    document.getElementById('btnVaciar')?.addEventListener('click', ()=>{
      registrosCargados = [];
      renderTablaBase([]);
    });
    document.getElementById('btnAlta')?.addEventListener('click', abrirAlta);

    // Cerrar modales
    document.getElementById('btnCancelarForm')?.addEventListener('click', ()=> ocultarModal('modalForm'));
    document.getElementById('btnCerrarPDF')?.addEventListener('click', ()=>{ document.getElementById('iframePDF').src=''; ocultarModal('modalPDF'); });
    document.getElementById('btnCerrarRespuesta')?.addEventListener('click', ()=> ocultarModal('modalRespuesta'));
    document.getElementById('formLiquidacion')?.addEventListener('submit', enviarFormulario);

    // Clicks en tabla (PDF / Modi / Borrar)
    document.getElementById('tbody')?.addEventListener('click', (ev)=>{
      const btn = ev.target.closest('button'); if(!btn) return;
      const tr  = btn.closest('tr'); const legajo = tr?.dataset?.legajo;
      if (btn.dataset.accion === 'pdf')   return mostrarPDF(legajo);
      if (btn.dataset.accion === 'borrar') return eliminarRegistro(legajo);
      if (btn.dataset.accion === 'modi'){
        const c = tr.querySelectorAll('td');
        const reg = {
          LegajoEmpleado : c[0].textContent,
          ApellidoYNombres: c[1].textContent,
          Fecha_liquidacion: c[2].textContent,
          MesDeLiquidacion: c[3].textContent,
          SueldoBasico: c[4].textContent,
          Monto_no_remunerativo_1: c[6].textContent
        };
        return abrirModi(reg);
      }
    });

    // Orden por click en th
    document.querySelector('#tabla thead')?.addEventListener('click', (ev)=>{
      const th = ev.target.closest('[data-orden]'); if(!th) return;
      document.getElementById('orden').value = th.dataset.orden;
    });

    // Filtros — escuchar cambios y re-render
    const fila = document.querySelector('#tabla thead tr.filtros');
    if (fila){
      fila.querySelectorAll('input,select').forEach(ctrl=>{
        ctrl.addEventListener('input', aplicarFiltrosYRender);
        ctrl.addEventListener('change', aplicarFiltrosYRender);
      });

      // Botón limpiar
      document.getElementById('btnLimpiar')?.addEventListener('click', ()=>{
        fila.querySelectorAll('input').forEach(x=> x.value='');
        const mesSel = document.getElementById('f_mes'); if (mesSel) mesSel.value='';
        aplicarFiltrosYRender();
      });
    }

    // Cargar combo de conceptos al inicio
    cargarConceptos();
  });

})();
