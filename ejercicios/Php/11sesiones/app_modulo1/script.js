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

  let registros = [];
  let conceptosMap = {}; // Mapa de códigos a nombres de conceptos
  let ordenActual = { columna: 'LegajoEmpleado', direccion: 'asc' }; // Estado del ordenamiento

  const formatearNumero = (valor) => {
    const num = Number(valor || 0);
    return num.toLocaleString('es-AR', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2
    });
  };

  // ===== Referencias a filtros =====
  const filtros = {
    legajo  : $('#f_legajo'),
    fecha   : $('#f_fecha'),
    mes     : $('#f_mes'),
    sueldo  : $('#f_sueldo'),
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
      fecha   : (filtros.fecha?.value || '').trim(),
      mes     : norm(filtros.mes?.value),
      sueldo  : (filtros.sueldo?.value || '').trim(),
      monto   : (filtros.monto?.value || '').trim(),
    };
    if (f.legajo   && !norm(r.LegajoEmpleado).includes(f.legajo)) return false;
    if (f.fecha    && !String(r.Fecha_liquidacion || '').includes(f.fecha)) return false;
    if (f.mes      && !norm(r.MesDeLiquidacion).includes(f.mes)) return false;
    // Mejorado: buscar sueldo exacto o aproximado (permite buscar por coincidencia)
    if (f.sueldo) {
      const sueldoStr = String(r.SueldoBasico || '');
      const sueldoFormateado = formatearNumero(r.SueldoBasico);
      if (!sueldoStr.includes(f.sueldo) && !sueldoFormateado.includes(f.sueldo)) return false;
    }
    // Mejorado: buscar monto por texto formateado o valor numérico
    if (f.monto) {
      const montoStr = String(r.Monto_no_remunerativo_1 || '');
      const montoFormateado = formatearNumero(r.Monto_no_remunerativo_1);
      if (!montoStr.includes(f.monto) && !montoFormateado.includes(f.monto)) return false;
    }
    return true;
  };

  const renderFiltrado = () => {
    let lista = (registros || []).filter(pasaFiltros);
    
    // Mapa de orden para meses
    const ordenMeses = {
      'Enero': 1, 'Febrero': 2, 'Marzo': 3, 'Abril': 4, 'Mayo': 5, 'Junio': 6,
      'Julio': 7, 'Agosto': 8, 'Septiembre': 9, 'Octubre': 10, 'Noviembre': 11, 'Diciembre': 12
    };
    
    // Aplicar ordenamiento
    if (ordenActual.columna) {
      lista.sort((a, b) => {
        let valA, valB;
        
        switch(ordenActual.columna) {
          case 'LegajoEmpleado':
            valA = a.LegajoEmpleado || '';
            valB = b.LegajoEmpleado || '';
            break;
          case 'ApellidoYNombres':
            valA = a.ApellidoYNombres || '';
            valB = b.ApellidoYNombres || '';
            break;
          case 'Fecha_liquidacion':
            valA = a.Fecha_liquidacion || '';
            valB = b.Fecha_liquidacion || '';
            break;
          case 'MesDeLiquidacion':
            // Extraer el mes y convertirlo a número
            const mesA = (a.MesDeLiquidacion || '').split(' ')[0];
            const mesB = (b.MesDeLiquidacion || '').split(' ')[0];
            valA = ordenMeses[mesA] || 0;
            valB = ordenMeses[mesB] || 0;
            break;
          case 'SueldoBasico':
            valA = Number(a.SueldoBasico) || 0;
            valB = Number(b.SueldoBasico) || 0;
            break;
          case 'concepto_no_remunerativo_1':
            valA = conceptosMap[a.concepto_no_remunerativo_1] || a.concepto_no_remunerativo_1 || '';
            valB = conceptosMap[b.concepto_no_remunerativo_1] || b.concepto_no_remunerativo_1 || '';
            break;
          case 'Monto_no_remunerativo_1':
            valA = Number(a.Monto_no_remunerativo_1) || 0;
            valB = Number(b.Monto_no_remunerativo_1) || 0;
            break;
          default:
            valA = a[ordenActual.columna] || '';
            valB = b[ordenActual.columna] || '';
        }
        
        // Comparar
        let comparacion = 0;
        if (typeof valA === 'number' && typeof valB === 'number') {
          comparacion = valA - valB;
        } else {
          comparacion = String(valA).localeCompare(String(valB), 'es-AR');
        }
        
        return ordenActual.direccion === 'asc' ? comparacion : -comparacion;
      });
    }
    
    tbody.innerHTML = '';
    if (!lista.length) {
      tbody.innerHTML = '<tr><td colspan="10">Sin resultados</td></tr>';
      if (pie) pie.innerHTML = '';
      return;
    }
    const frag = document.createDocumentFragment();
    for (const reg of lista) {
      const tr = document.createElement('tr');
      
      // Guardar datos en dataset ANTES de crear el HTML
      tr.dataset.legajo = reg.LegajoEmpleado || '';
      tr.dataset.concepto = reg.concepto_no_remunerativo_1 || '';
      tr.dataset.apellidoYNombres = reg.ApellidoYNombres || '';
      tr.dataset.fechaLiquidacion = reg.Fecha_liquidacion || '';
      tr.dataset.mesDeLiquidacion = reg.MesDeLiquidacion || '';
      tr.dataset.sueldoBasico = reg.SueldoBasico || '0';
      tr.dataset.montoNoRemunerativo = reg.Monto_no_remunerativo_1 || '0';
      
      // Obtener nombre del concepto desde el mapa
      const codigoConcepto = reg.concepto_no_remunerativo_1 || '';
      const nombreConcepto = conceptosMap[codigoConcepto] || codigoConcepto;
      
      // Crear celdas manualmente para preservar dataset
      const celdas = [
        reg.LegajoEmpleado,
        reg.ApellidoYNombres,
        reg.Fecha_liquidacion,
        reg.MesDeLiquidacion,
        formatearNumero(reg.SueldoBasico),
        nombreConcepto,
        formatearNumero(reg.Monto_no_remunerativo_1),
        Number(reg.pdf_bytes) > 0 ? '<button class="btn-accion" data-accion="pdf">PDF</button>' : '-',
        '<button class="btn-accion btn-modi" data-accion="modi">Modi</button>',
        '<button class="btn-accion btn-borrar" data-accion="borrar">Borrar</button>'
      ];
      
      celdas.forEach((contenido, i) => {
        const td = document.createElement('td');
        if (i === 4 || i === 6) td.style.textAlign = 'right'; // Números a la derecha
        if (i >= 7) td.style.textAlign = 'center'; // Botones al centro
        td.innerHTML = contenido;
        tr.appendChild(td);
      });
      
      frag.appendChild(tr);
    }
    tbody.appendChild(frag);
    if (pie) pie.innerHTML = '';
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
        // Guardar en el mapa para usar en la tabla
        conceptosMap[it.CodigoConcepto] = it.Descripcion;
      });
    } catch (e) {
      console.error(e);
    }
  };

  // ===== Cargar lista (con alerta de variables) =====
  const cargaTabla = async () => {
    // Asegurar que los conceptos estén cargados primero
    if (Object.keys(conceptosMap).length === 0) {
      await cargarConceptos();
    }
    
    // Restaurar el valor por defecto del orden
    const ordenInput = $('#orden');
    if (ordenInput) ordenInput.value = 'LegajoEmpleado';
    
    const params = armarParams();
    alert(
      'Variables a enviar:\n' +
      'orden = ' + params.get('orden')
    );

    tbody.innerHTML = '<tr><td colspan="10">Cargando…</td></tr>';
    try {
      const r = await fetch('salidaJsonLiquidaciones.php', {
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
    const legajoInput = $('#LegajoEmpleado');
    if (legajoInput) {
      legajoInput.disabled = false;
      legajoInput.style.backgroundColor = '';
    }
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
    
    // Deshabilitar el campo legajo para modificaciones (no se puede cambiar la PK)
    const legajoInput = $('#LegajoEmpleado');
    if (legajoInput) {
      legajoInput.disabled = true;
      legajoInput.style.backgroundColor = '#f0f0f0';
    }
    
    mostrarModal('modalForm');
  };

  const mostrarPDF = async (legajo) => {
    try {
      const r = await fetch('pdf.php?legajo=' + encodeURIComponent(legajo));
      
      if (!r.ok) { 
        const texto = await r.text();
        alert('Error del servidor: ' + texto); 
        return; 
      }
      
      const contentType = r.headers.get('content-type');
      if (!contentType || !contentType.includes('application/pdf')) {
        const texto = await r.text();
        alert('Error: ' + texto);
        return;
      }
      
      const blob = await r.blob();
      if (blob.size === 0) {
        alert('El PDF está vacío');
        return;
      }
      
      // Mostrar PDF en el modal
      const url = URL.createObjectURL(blob);
      const ifr = $('#iframePDF');
      if (ifr) { 
        ifr.src = url; 
        mostrarModal('modalPDF'); 
      } else { 
        window.open(url, '_blank'); 
      }
      
    } catch (e) {
      alert('Error al obtener el PDF: ' + e.message);
    }
  };

  const enviarFormulario = async (ev) => {
    ev.preventDefault();
    const esAlta = ($('#accion').value === 'alta');
    const url = esAlta ? 'alta.php' : 'modi.php';
    
    // Validar archivo PDF si se seleccionó
    const pdfInput = $('#pdf_liquidacion');
    if (pdfInput && pdfInput.files.length > 0) {
      const archivo = pdfInput.files[0];
      if (archivo.type !== 'application/pdf') {
        alert('Solo se permiten archivos PDF');
        return;
      }
      console.log('PDF seleccionado:', archivo.name, 'Tamaño:', archivo.size);
    }
    
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
    const ordenInput = $('#orden');
    if (ordenInput) ordenInput.value = '';
    // Resetear ordenamiento
    ordenActual = { columna: 'LegajoEmpleado', direccion: 'asc' };
    $$('#tabla thead th[data-orden]').forEach(t => {
      t.classList.remove('orden-asc', 'orden-desc');
    });
    renderFiltrado();
  };

  // ===== Listeners =====
  $('#btnBuscar')?.addEventListener('click', cargaTabla);
  $('#btnVaciar')?.addEventListener('click', () => { registros = []; renderFiltrado(); });
  $('#btnAlta')?.addEventListener('click', abrirAlta);
  $('#btnLimpiarFiltros')?.addEventListener('click', limpiarFiltros);

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
      const reg = {
        LegajoEmpleado:             tr.dataset.legajo,
        ApellidoYNombres:           tr.dataset.apellidoYNombres,
        Fecha_liquidacion:          tr.dataset.fechaLiquidacion,
        MesDeLiquidacion:           tr.dataset.mesDeLiquidacion,
        SueldoBasico:               tr.dataset.sueldoBasico,
        concepto_no_remunerativo_1: tr.dataset.concepto || '', // Usar el código guardado
        Monto_no_remunerativo_1:    tr.dataset.montoNoRemunerativo
      };
      return abrirModi(reg);
    }
  });

  // Orden por click en TH
  $('#tabla thead')?.addEventListener('click', (ev) => {
    const th = ev.target.closest('[data-orden]'); 
    if (!th) return;
    
    const columna = th.dataset.orden;
    
    // Si es la misma columna, cambiar dirección; si no, reiniciar a ascendente
    if (ordenActual.columna === columna) {
      ordenActual.direccion = ordenActual.direccion === 'asc' ? 'desc' : 'asc';
    } else {
      ordenActual.columna = columna;
      ordenActual.direccion = 'asc';
    }
    
    // Sincronizar con el input de orden
    const ordenInput = $('#orden');
    if (ordenInput) ordenInput.value = columna;
    
    // Actualizar indicador visual en el thead
    $$('#tabla thead th[data-orden]').forEach(t => {
      t.classList.remove('orden-asc', 'orden-desc');
    });
    th.classList.add(`orden-${ordenActual.direccion}`);
    
    // Renderizar inmediatamente
    renderFiltrado();
  });

  // Filtros en vivo
  Object.values(filtros).forEach(inp => {
    if (!inp) return;
    const evName = (inp.tagName === 'SELECT') ? 'change' : 'input';
    inp.addEventListener(evName, actualizarFiltrado);
  });

  // Marcar ordenamiento inicial visual
  const thLegajo = $('#tabla thead th[data-orden="LegajoEmpleado"]');
  if (thLegajo) thLegajo.classList.add('orden-asc');

  // Primera carga: combos
  cargarConceptos();
})();
