/*
 * Script de la SPA de liquidaciones de sueldos.
 *
 * Este archivo se basó en el script original de 11sesiones/script.js. Se
 * adaptaron las URL para que consuman los servicios del módulo PHP de
 * esta carpeta (php/listar.php, php/alta.php, etc.) y se implementó
 * el flujo de carga de PDF en dos etapas conforme a los requisitos.
 *
 * Se eliminaron las alertas y mensajes de depuración del código
 * original para no romper la experiencia de usuario, salvo una
 * confirmación previa al alta/modificación y un alerta con la
 * respuesta del servidor.
 */

(() => {
  /* ===== Helpers ===== */
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

  // ===== Estado en memoria =====
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

  // ===== Filtrado en cliente =====
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
      pie.innerHTML   = 'Total: 0';
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
        <td>${Number(reg.pdf_bytes) > 0 ? '<button class="boton-accion" data-accion="pdf">PDF</button>' : '-'}</td>
        <td><button class="boton-accion" data-accion="modi">Modi</button></td>
        <td><button class="boton-accion" data-accion="borrar">Borrar</button></td>
      `;
      frag.appendChild(tr);
    }
    tbody.appendChild(frag);
    pie.innerHTML = `Total: ${lista.length}`;
  };

  const actualizarFiltrado = debounce(renderFiltrado);

  // ===== Preparar parámetros de orden =====
  const armarParams = () => {
    const p = new URLSearchParams();
    p.append('orden', $('#orden').value);
    return p;
  };

  // ===== Catálogo de conceptos fijos =====
  const conceptos = [
    { codigo: '01', desc: 'Indemnizaciones' },
    { codigo: '02', desc: 'Asignaciones familiares' },
    { codigo: '03', desc: 'Viáticos' },
    { codigo: '04', desc: 'Adicional por presentismo' },
  ];

  const cargarConceptos = () => {
    const sel = $('#CodConceptoNoRem');
    if (!sel) return;
    sel.innerHTML = '';
    conceptos.forEach(it => {
      const o = document.createElement('option');
      // Mostrar "código – descripción" y persistir exactamente esa combinación
      o.value = `${it.codigo} – ${it.desc}`;
      o.textContent = `${it.codigo} – ${it.desc}`;
      sel.appendChild(o);
    });
  };

  // ===== Cargar lista desde el servidor =====
  const cargaTabla = async () => {
    const params = armarParams();
    tbody.innerHTML = '<tr><td colspan="10">Cargando…</td></tr>';
    try {
      const r = await fetch('salidaJsonLiquidacionesConPrepare.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: params.toString()
      });
      const json = await r.json();
      if (!json || json.error) {
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

  // ===== Mostrar PDF en un iframe =====
  const mostrarPDF = async (legajo) => {
    try {
      const r = await fetch('pdf.php?legajo=' + encodeURIComponent(legajo));
      if (!r.ok) {
        alert('No hay documento PDF registrado');
        return;
      }
      const json = await r.json();
      if (!json.ok) {
        alert(json.error || 'Sin PDF');
        return;
      }
      const url = 'data:application/pdf;base64,' + json.base64;
      const ifr = $('#iframePDF');
      if (ifr) {
        ifr.src = url;
        mostrarModal('modalPDF');
      } else {
        window.open(url, '_blank');
      }
    } catch (e) {
      console.error(e);
      alert('Error al obtener el PDF.');
    }
  };

  // ===== Abrir formularios =====
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
    $('#CodConceptoNoRem').value        = reg.concepto_no_remunerativo_1 || '';
    $('#Monto_no_remunerativo_1').value = reg.Monto_no_remunerativo_1;
    $('#pdf_liquidacion').value         = '';
    mostrarModal('modalForm');
  };

  // ===== Envío del formulario (alta o modi) =====
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
    }
    // Eliminar el PDF del envío inicial (segunda etapa se gestiona aparte)
    fd.delete('pdf_liquidacion');
    try {
      const r = await fetch(url, { method: 'POST', body: fd });
      const json = await r.json();
      alert('Respuesta del servidor:\n' + (json.estado || JSON.stringify(json)));
      $('#textoRespuesta').textContent = json.estado || JSON.stringify(json);
      // Si la operación fue exitosa y el usuario subió un PDF, ejecutamos la segunda etapa
      if (json.ok) {
        const fileInput = $('#pdf_liquidacion');
        if (fileInput && fileInput.files && fileInput.files.length > 0) {
          const fd2 = new FormData();
          fd2.append('LegajoEmpleado', leg);
          fd2.append('pdf_liquidacion', fileInput.files[0]);
          try {
            const r2 = await fetch('cargarPdfs.php', { method: 'POST', body: fd2 });
            const json2 = await r2.json();
            alert('Actualizar PDF:\n' + (json2.estado || JSON.stringify(json2)));
          } catch (err) {
            console.error(err);
            alert('Error al actualizar el PDF.');
          }
        }
      }
      ocultarModal('modalForm'); mostrarModal('modalRespuesta');
      await cargaTabla();
    } catch (e) {
      console.error(e);
      $('#textoRespuesta').textContent = 'Error de comunicación con el servidor.';
      ocultarModal('modalForm'); mostrarModal('modalRespuesta');
    }
  };

  // ===== Eliminar registro =====
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

  // ===== Limpiar filtros =====
  const limpiarFiltros = () => {
    Object.values(filtros).forEach(f => { if (f) f.value = ''; });
    renderFiltrado();
  };
  // Exponer backup para el onclick del HTML
  window.__limpiarFiltros = limpiarFiltros;

  // ===== Listeners globales =====
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

  // Primera carga: combos y tabla
  cargarConceptos();
  cargaTabla();
})();

// -----------------------------------------------------------------------------
// Preguntas técnicas para el profesor
// 1) En este flujo la actualización del campo PDF se realiza en una
//    llamada HTTP independiente tras la inserción/modificación de los
//    datos simples. ¿Qué ventajas y desventajas encuentra en separar
//    la carga del BLOB de la transacción principal? ¿Cómo impactaría en
//    concurrencia y en la consistencia si se perdiera la segunda
//    llamada?
//
// 2) Los filtros de búsqueda se realizan íntegramente en el cliente
//    utilizando JavaScript. ¿Qué modificaciones serían necesarias para
//    delegar el filtrado y la paginación al servidor usando
//    parámetros GET/POST? Considere implicancias de rendimiento y de
//    seguridad frente a inyecciones SQL.