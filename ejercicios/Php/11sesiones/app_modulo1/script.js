(() => {
  // ===== Selectores y helpers =====
  const $  = (s, c = document) => c.querySelector(s);
  const $$ = (s, c = document) => Array.from(c.querySelectorAll(s));

  const tbody = $('#tbody');
  const pie   = $('#pie');

  const mostrarModal = (id) => {
    const n = $('#' + id);
    if (!n) return;
    n.classList.remove('oculto');
    n.setAttribute('aria-hidden','false');
  };
  const ocultarModal = (id) => {
    const n = $('#' + id);
    if (!n) return;
    n.classList.add('oculto');
    n.setAttribute('aria-hidden','true');
  };

  // ===== Estado en memoria (para filtros cliente) =====
  let registros = [];

  // ===== Referencias a filtros =====
  const filtros = {
    legajo  : $('#f_legajo'),
    nombre  : $('#f_nombre'),
    fecha   : $('#f_fecha'),
    mes     : $('#f_mes'),
    sueldo  : $('#f_sueldo'),
    concepto: $('#f_concepto'),
    monto   : $('#f_monto'),
  };

  // ===== Utilidades =====
  const norm = v => String(v ?? '').toLowerCase();
  const debounce = (fn, ms = 160) => {
    let t;
    return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); };
  };

  // ===== Filtrado =====
  const pasaFiltros = (r) => {
    const f = {
      legajo  : norm(filtros.legajo?.value),
      nombre  : norm(filtros.nombre?.value),
      fecha   : (filtros.fecha?.value || '').trim(),
      mes     : norm(filtros.mes?.value),
      sueldo  : (filtros.sueldo?.value || '').trim(),
      concepto: norm(filtros.concepto?.value),
      monto   : (filtros.monto?.value || '').trim(),
    };
    if (f.legajo   && !norm(r.LegajoEmpleado).includes(f.legajo)) return false;
    if (f.nombre   && !norm(r.ApellidoYNombres).includes(f.nombre)) return false;
    if (f.fecha    && !String(r.Fecha_liquidacion || '').includes(f.fecha)) return false;
    if (f.mes      && !norm(r.MesDeLiquidacion).includes(f.mes)) return false;
    if (f.sueldo   && Number(r.SueldoBasico)               < Number(f.sueldo)) return false;
    if (f.concepto && !norm(r.concepto_no_remunerativo_1||'').includes(f.concepto)) return false;
    if (f.monto    && Number(r.Monto_no_remunerativo_1)    < Number(f.monto)) return false;
    return true;
  };

  const renderFiltrado = () => {
    const lista = (registros || []).filter(pasaFiltros);
    tbody.innerHTML = '';
    if (!lista.length) {
      tbody.innerHTML = '<tr><td colspan="10">Sin resultados</td></tr>';
      pie.innerHTML   = 'Alumno: <strong>Bustamante Agustin</strong> · Total: 0';
      return;
    }
    const frag = document.createDocumentFragment();
    for (const reg of lista) {
      const tr = document.createElement('tr');
      tr.dataset.legajo = reg.LegajoEmpleado;
      tr.innerHTML = `
        <td>${reg.LegajoEmpleado}</td>
        <td>${reg.ApellidoYNombres}</td>
        <td>${reg.Fecha_liquidacion}</td>
        <td>${reg.MesDeLiquidacion}</td>
        <td>${Number(reg.SueldoBasico || 0).toFixed(2)}</td>
        <td>${reg.concepto_no_remunerativo_1 || ''}</td>
        <td>${Number(reg.Monto_no_remunerativo_1 || 0).toFixed(2)}</td>
        <td style="text-align:center;">${Number(reg.pdf_bytes) > 0 ? '<button class="btn-accion" data-accion="pdf">PDF</button>' : '-'}</td>
        <td style="text-align:center;"><button class="btn-accion btn-modi" data-accion="modi">Modi</button></td>
        <td style="text-align:center;"><button class="btn-accion btn-borrar" data-accion="borrar">Borrar</button></td>
      `;
      frag.appendChild(tr);
    }
    tbody.appendChild(frag);
    pie.innerHTML = `Alumno: <strong>Bustamante Agustin</strong> · Total: ${lista.length}`;
  };

  const actualizarFiltrado = debounce(renderFiltrado);

  // ===== Params POST =====
  const armarParams = () => {
    const p = new URLSearchParams();
    p.append('orden', $('#orden').value);

    return p;
  };

  // ===== Cargar conceptos (alerta inicial requerida) =====
  const cargarConceptos = async () => {
    try {
      const r = await fetch('salidaJsonConceptos.php');
      const json = await r.json();
      alert('JSON de familias (datos iniciales):\n' + JSON.stringify(json));
      const sel = $('#CodConceptoNoRem');
      sel.innerHTML = '';
      (json.conceptos || []).forEach(it => {
        const o = document.createElement('option');
        o.value = it.CodigoConcepto;
        o.textContent = `${it.CodigoConcepto} – ${it.Descripcion}`;
        sel.appendChild(o);
      });
    } catch (e) {
      console.error(e);
    }
  };

  // ===== Cargar lista (con alerta de variables) =====
  const cargaTabla = async () => {
    // Restaurar el valor por defecto del orden
    const ordenInput = $('#orden');
    if (ordenInput) ordenInput.value = 'LegajoEmpleado';
    
    const params = armarParams();
    alert(
      'Variables a enviar (antes de Ajax):\n' +
      'orden = ' + params.get('orden') + '\n' +
      'f_liquidaciones_mes_num = ' + (params.get('f_liquidaciones_mes_num') || '(vacío)')
    );

    tbody.innerHTML = '<tr><td colspan="10">Cargando…</td></tr>';
    try {
      const r = await fetch('salidaJsonLiquidacionesConPrepare.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: params.toString()
      });
      const json = await r.json();
      if (json?.error) {
        alert('Respuesta listar:\n' + JSON.stringify(json, null, 2));
        registros = [];
        renderFiltrado();
        return;
      }
      registros = json.liquidaciones || [];
      renderFiltrado();
    } catch (e) {
      console.error(e);
      tbody.innerHTML = '<tr><td colspan="10">Error de comunicación con el servidor.</td></tr>';
    }
  };

  // ===== ABM =====
  const abrirAlta = () => {
    $('#tituloForm').textContent = 'Alta de liquidación';
    $('#accion').value = 'alta';
    $('#legajoOriginal').value = '';
    $('#formLiquidacion').reset();
    mostrarModal('modalForm');
  };

  const abrirModi = (reg) => {
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
  };

  const mostrarPDF = async (legajo) => {
    try {
      const r = await fetch('pdf.php?legajo=' + encodeURIComponent(legajo));
      if (!r.ok) { alert('No hay documento PDF registrado'); return; }
      const blob = await r.blob();
      alert('PDF recibido. Tamaño: ' + blob.size + ' bytes');
      const url = URL.createObjectURL(blob);
      const ifr = $('#iframePDF');
      if (ifr) { ifr.src = url; mostrarModal('modalPDF'); }
      else { window.open(url, '_blank'); }
    } catch (e) {
      console.error(e);
      alert('Error al obtener el PDF.');
    }
  };

  const enviarFormulario = async (ev) => {
    ev.preventDefault();
    const esAlta = ($('#accion').value === 'alta');
    const url = esAlta ? 'alta.php' : 'modi.php';
    const fd  = new FormData($('#formLiquidacion'));
    const leg = $('#LegajoEmpleado').value;

    if (esAlta) {
      if (!confirm('¿Estás seguro de insertar el registro? ' + leg)) return;
    } else {
      if (!confirm('¿Estás seguro que desea modificar el registro ' + leg + '?')) return;
      fd.append('LegajoEmpleadoOriginal', $('#legajoOriginal').value);
    }

    try {
      const r = await fetch(url, { method: 'POST', body: fd });
      const json = await r.json();
      alert('Respuesta del servidor:\n' + (json.estado || JSON.stringify(json)));
      $('#textoRespuesta').textContent = json.estado || JSON.stringify(json);
      ocultarModal('modalForm'); mostrarModal('modalRespuesta');
      await cargaTabla();
    } catch (e) {
      console.error(e);
      $('#textoRespuesta').textContent = 'Error de comunicación con el servidor.';
      ocultarModal('modalForm'); mostrarModal('modalRespuesta');
    }
  };

  const eliminarRegistro = async (legajo) => {
    if (!confirm('¿Estás seguro que desea borrar eliminar el ' + legajo + '?')) return;
    try {
      const p = new URLSearchParams(); p.append('LegajoEmpleado', legajo);
      const r = await fetch('baja.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: p.toString()
      });
      const json = await r.json();
      alert('Respuesta de la baja:\n' + (json.estado || JSON.stringify(json)));
      $('#textoRespuesta').textContent = json.estado || JSON.stringify(json);
      mostrarModal('modalRespuesta'); await cargaTabla();
    } catch (e) {
      console.error(e);
      alert('Error al eliminar.');
    }
  };

  // ===== Limpiar filtros (botón de encabezado) =====
  const limpiarFiltros = () => {
    Object.values(filtros).forEach(f => { if (f) f.value = ''; });
    // Limpiar también el campo de orden
    const ordenInput = $('#orden');
    if (ordenInput) ordenInput.value = '';
    // Mostramos todo SIN re-llamar al server (si querés, cambiá por cargaTabla();)
    renderFiltrado();
  };

  // Exponer backup para el onclick del HTML
  window.__limpiarFiltros = limpiarFiltros;

  // ===== Listeners =====
  $('#btnBuscar')?.addEventListener('click', cargaTabla);
  $('#btnVaciar')?.addEventListener('click', () => { registros = []; renderFiltrado(); });
  $('#btnAlta')?.addEventListener('click', abrirAlta);
  $('#btnLimpiarFiltros')?.addEventListener('click', limpiarFiltros);

  // Delegación global (respaldo por si algún listener no engancha por cache/timing)
  document.addEventListener('click', (ev) => {
    const t = ev.target.closest('#btnLimpiarFiltros');
    if (t) limpiarFiltros();
  });

  // Cerrar modales
  $('#btnCancelarForm')?.addEventListener('click', () => ocultarModal('modalForm'));
  $('#btnCerrarPDF')?.addEventListener('click', () => { $('#iframePDF').src = ''; ocultarModal('modalPDF'); });
  $('#btnCerrarRespuesta')?.addEventListener('click', () => ocultarModal('modalRespuesta'));
  $('#formLiquidacion')?.addEventListener('submit', enviarFormulario);

  // Delegación: acciones en tabla
  tbody?.addEventListener('click', (ev) => {
    const btn = ev.target.closest('button'); if (!btn) return;
    const tr  = btn.closest('tr');
    const leg = tr?.dataset?.legajo;
    const acc = btn.dataset.accion;
    if (acc === 'pdf')   return mostrarPDF(leg);
    if (acc === 'borrar') return eliminarRegistro(leg);
    if (acc === 'modi') {
      const c = tr.querySelectorAll('td');
      const reg = {
        LegajoEmpleado:             c[0].textContent,
        ApellidoYNombres:           c[1].textContent,
        Fecha_liquidacion:          c[2].textContent,
        MesDeLiquidacion:           c[3].textContent,
        SueldoBasico:               c[4].textContent,
        concepto_no_remunerativo_1: c[5].textContent,
        Monto_no_remunerativo_1:    c[6].textContent
      };
      return abrirModi(reg);
    }
  });

  // Orden por click en TH
  $('#tabla thead')?.addEventListener('click', (ev) => {
    const th = ev.target.closest('[data-orden]'); if (!th) return;
    $('#orden').value = th.dataset.orden;
  });

  // Filtros en vivo
  Object.values(filtros).forEach(inp => {
    if (!inp) return;
    const evName = (inp.tagName === 'SELECT') ? 'change' : 'input';
    inp.addEventListener(evName, actualizarFiltrado);
  });

  // Primera carga: combos
  cargarConceptos();
})();
