(() => {
  // ===== Selectores y helpers =====
  const $  = (s, c = document) => c.querySelector(s);
  const $$ = (s, c = document) => Array.from(c.querySelectorAll(s));

  const tbody = $('#tbody');
  const pie   = $('#pie');

  /**
   * Muestra un elemento modal oculto. Los modales se usan para
   * formularios, visualización de PDFs y mensajes del servidor.
   *
   * @param {string} id Identificador del elemento modal a mostrar
   */
  const mostrarModal = (id) => {
    const n = $('#' + id);
    if (!n) return;
    n.classList.remove('oculto');
    n.setAttribute('aria-hidden','false');
  };

  /**
   * Oculta el modal indicado.
   *
   * @param {string} id Identificador del modal a ocultar
   */
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
  /**
   * Crea una función con debounce para limitar la frecuencia de ejecución.
   *
   * @param {Function} fn Función a debouncing
   * @param {number} ms Retardo en milisegundos
   */
  const debounce = (fn, ms = 160) => {
    let t;
    return (...a) => { clearTimeout(t); t = setTimeout(() => fn(...a), ms); };
  };

  // ===== Filtrado =====
  /**
   * Determina si un registro cumple con los filtros activos.
   *
   * @param {Object} r Registro a evaluar
   * @returns {boolean} true si pasa todos los filtros
   */
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
    if (f.sueldo   && Number(r.SueldoBasico)            < Number(f.sueldo)) return false;
    if (f.concepto && !norm(r.concepto_no_remunerativo_1||'').includes(f.concepto)) return false;
    if (f.monto    && Number(r.Monto_no_remunerativo_1) < Number(f.monto)) return false;
    return true;
  };

  /**
   * Renderiza la tabla filtrada en función de los registros en memoria
   * y de los filtros aplicados por el usuario.
   */
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
        <td>${Number(reg.pdf_bytes) > 0 ? '<button class="boton-accion" data-accion="pdf">PDF</button>' : '-'}</td>
        <td><button class="boton-accion" data-accion="modi">Modi</button></td>
        <td><button class="boton-accion" data-accion="borrar">Borrar</button></td>
      `;
      frag.appendChild(tr);
    }
    tbody.appendChild(frag);
    pie.innerHTML = `Alumno: <strong>Bustamante Agustin</strong> · Total: ${lista.length}`;
  };

  const actualizarFiltrado = debounce(renderFiltrado);

  // ===== Params POST =====
  /**
   * Construye los parámetros de orden y filtros que serán enviados al
   * servidor al cargar la tabla. Actualmente sólo se envía la columna
   * de orden. Para otros filtros consulte la documentación del
   * servidor.
   */
  const armarParams = () => {
    const p = new URLSearchParams();
    p.append('orden', $('#orden').value);
    // Si querés mes numérico (1..12) desde el server, descomentar:
    /*
    const mesTxt = norm(filtros.mes?.value);
    const mapa = {enero:1,febrero:2,marzo:3,abril:4,mayo:5,junio:6,julio:7,agosto:8,septiembre:9,octubre:10,noviembre:11,diciembre:12};
    if (mesTxt && mapa[mesTxt]) p.append('f_liquidaciones_mes_num', String(mapa[mesTxt]));
    */
    return p;
  };

  // ===== Cargar conceptos =====
  /**
   * Obtiene la lista de conceptos no remunerativos desde el servidor y
   * popula el desplegable del formulario de alta y modificación. Al
   * recibir la respuesta se muestra un alert para cumplir con la
   * consigna original de exponer el JSON.
   */
  const cargarConceptos = async () => {
    try {
      const r = await fetch('salidaJsonConceptos.php');
      const json = await r.json();
      // Mostrar el JSON bruto como alerta según pedido del profesor
      alert('JSON de conceptos no remunerativos (datos iniciales):\n' + JSON.stringify(json));
      const sel = $('#CodConceptoNoRem');
      sel.innerHTML = '';
      (json.conceptos || []).forEach(it => {
        const o = document.createElement('option');
        o.value = it.CodigoConcepto;
        // Se concatena código y descripción con guion largo para mayor legibilidad
        o.textContent = `${it.CodigoConcepto} – ${it.Descripcion}`;
        sel.appendChild(o);
      });
    } catch (e) {
      console.error(e);
    }
  };

  // ===== Cargar lista (con alerta de variables) =====
  /**
   * Realiza una petición al servidor para obtener las liquidaciones. Antes
   * de la llamada se genera un alert mostrando los parámetros que se
   * enviarán, cumpliendo con la consigna del ejercicio original.
   */
  const cargaTabla = async () => {
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
  /**
   * Prepara y abre el formulario para una nueva alta.
   */
  const abrirAlta = () => {
    $('#tituloForm').textContent = 'Alta de liquidación';
    $('#accion').value = 'alta';
    $('#legajoOriginal').value = '';
    $('#formLiquidacion').reset();
    mostrarModal('modalForm');
  };

  /**
   * Carga los valores del registro seleccionado en el formulario de
   * modificación y abre el modal.
   *
   * @param {Object} reg Registro con los campos de la liquidación
   */
  const abrirModi = (reg) => {
    $('#tituloForm').textContent = 'Modificar liquidación';
    $('#accion').value = 'modi';
    $('#legajoOriginal').value = reg.LegajoEmpleado;

    $('#LegajoEmpleado').value          = reg.LegajoEmpleado;
    $('#ApellidoYNombres').value        = reg.ApellidoYNombres;
    $('#Fecha_liquidacion').value       = reg.Fecha_liquidacion;
    $('#MesDeLiquidacion').value        = reg.MesDeLiquidacion;
    $('#SueldoBasico').value            = reg.SueldoBasico;
    // Se vacía el select para que el usuario vuelva a elegir la opción
    $('#CodConceptoNoRem').value        = '';
    $('#Monto_no_remunerativo_1').value = reg.Monto_no_remunerativo_1;
    $('#pdf_liquidacion').value         = '';
    mostrarModal('modalForm');
  };

  /**
   * Solicita el PDF asociado a un legajo y lo muestra en un iframe modal.
   *
   * @param {string} legajo Legajo de la liquidación
   */
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

  /**
   * Envío genérico del formulario para altas y modificaciones. Utiliza
   * FormData para soportar la carga de archivos PDF. Muestra
   * confirmaciones antes de enviar y utiliza alertas para mostrar la
   * respuesta del servidor, cumpliendo con la consigna original.
   *
   * @param {Event} ev Evento submit del formulario
   */
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

  /**
   * Solicita al servidor la eliminación de un registro por legajo. Muestra
   * confirmación previa y alerta con la respuesta.
   *
   * @param {string} legajo Legajo de la liquidación a eliminar
   */
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
  /**
   * Limpia todos los filtros de la tabla y re-renderiza con los datos
   * actuales sin volver a consultar al servidor.
   */
  const limpiarFiltros = () => {
    Object.values(filtros).forEach(f => { if (f) f.value = ''; });
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