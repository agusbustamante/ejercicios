  (function(){
    /* ===== Helpers modales ===== */
    function mostrarModal(id){
      const n = document.getElementById(id);
      if (!n) return;
      n.classList.remove('oculto');
      n.setAttribute('aria-hidden', 'false');
    }
    function ocultarModal(id){
      const n = document.getElementById(id);
      if (!n) return;
      n.classList.add('oculto');
      n.setAttribute('aria-hidden', 'true');
    }

    /* ===== Params para Ajax ===== */
    function armarParams(){
      const p = new URLSearchParams();
      p.append('orden', document.getElementById('orden').value);

      // Añadir automáticamente todos los inputs/selects dentro de la fila de filtros
      // Cada elemento debe tener un id; el parámetro enviado será el id con su valor.
      const filaFiltros = document.querySelector('#tabla thead tr.filtros');
      if (filaFiltros) {
        const controles = filaFiltros.querySelectorAll('input, select, textarea');
        controles.forEach(ctrl => {
          if (!ctrl.id) return; // necesita id para enviar
          // enviar aunque esté vacío (opcional): sólo enviamos si hay valor para reducir ruido
          if (ctrl.value !== null && String(ctrl.value).trim() !== '') {
            p.append(ctrl.id, ctrl.value);
          }
        });
      }
      return p;
    }

    /* ===== Render tabla ===== */
    function renderTabla(list){
      const tbody = document.getElementById('tbody');
      tbody.innerHTML = '';
      if(!list || !list.length){
        tbody.innerHTML = '<tr><td colspan="10">Sin resultados</td></tr>';
        return;
      }
      const frag = document.createDocumentFragment();
      list.forEach(reg=>{
        const tr = document.createElement('tr');
        tr.dataset.legajo = reg.LegajoEmpleado;

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
        bm.type='button'; bm.className='boton-accion'; bm.dataset.accion='modi'; bm.textContent='Modi'; tdM.appendChild(bm); tr.appendChild(tdM);

        // Borrar
        const tdD=document.createElement('td'); const bd=document.createElement('button');
        bd.type='button'; bd.className='boton-accion'; bd.dataset.accion='borrar'; bd.textContent='Borrar'; tdD.appendChild(bd); tr.appendChild(tdD);

        frag.appendChild(tr);
      });
      tbody.appendChild(frag);
    }

    /* ===== Ajax: cargar lista ===== */
    async function cargaTabla(){
      const params = armarParams();

      // Alerta requerida por el profesor
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
        // Si algo vino mal en la conexión, mostralo
        if (json && json.error) {
          alert('Respuesta listar:\n' + JSON.stringify(json, null, 2));
        }
  // Si el servidor devolvió la lista, aplicamos además un filtrado cliente por seguridad
  const lista = json.liquidaciones || [];
  const listaFiltrada = aplicarFiltrosCliente(lista);
  renderTabla(listaFiltrada);
        document.getElementById('pie').innerHTML = 'Alumno: <strong>Bustamante Agustin</strong> · Total: ' + (json.cuenta ?? 0);
      }catch(err){
        console.error(err);
        document.getElementById('tbody').innerHTML = '<tr><td colspan="10">Error de comunicación con el servidor.</td></tr>';
      }
    }

    /* ===== Combo de conceptos: 1ª alerta con JSON de familias ===== */
    async function cargarConceptos(){
      try{
        const resp = await fetch('salidaJsonConceptos.php');
        const json = await resp.json();

        // Alerta “JSON de familias (datos iniciales)”
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

    /* ===== Alta / Modi ===== */
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

    /* ===== Ver PDF: alerta llegada de binario ===== */
    async function mostrarPDF(legajo){
      try{
        const r = await fetch('pdf.php?legajo=' + encodeURIComponent(legajo));
        if (!r.ok) { alert('No hay documento PDF registrado'); return; }
        const blob = await r.blob();

        // Alerta con la “llegada del binario”
        alert('PDF recibido. Tamaño: ' + blob.size + ' bytes');

        const url = URL.createObjectURL(blob);
        const iframe = document.getElementById('iframePDF');
        if (iframe) { iframe.src = url; mostrarModal('modalPDF'); } else { window.open(url, '_blank'); }
      }catch(err){ console.error(err); alert('Error al obtener el PDF.'); }
    }

    /* ===== Envío del formulario: confirmaciones y alerta de respuesta ===== */
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
        cargaTabla();
      }catch(err){
        console.error(err);
        document.getElementById('textoRespuesta').textContent = 'Error de comunicación con el servidor.';
        ocultarModal('modalForm'); mostrarModal('modalRespuesta');
      }
    }

    /* ===== Baja con confirmación y alerta respuesta ===== */
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
        mostrarModal('modalRespuesta'); cargaTabla();
      }catch(err){ console.error(err); alert('Error al eliminar.'); }
    }

    /* ===== Delegaciones y botones ===== */
    document.getElementById('tbody').addEventListener('click', (ev)=>{
      const btn = ev.target.closest('button'); if(!btn) return;
      const acc = btn.dataset.accion; const tr = btn.closest('tr'); const legajo = tr.dataset.legajo;
      if(acc==='pdf'){ mostrarPDF(legajo);
      }else if(acc==='modi'){
        const c = tr.querySelectorAll('td');
        const reg = { LegajoEmpleado:c[0].textContent, ApellidoYNombres:c[1].textContent,
                      Fecha_liquidacion:c[2].textContent, MesDeLiquidacion:c[3].textContent,
                      SueldoBasico:c[4].textContent, Monto_no_remunerativo_1:c[6].textContent };
        abrirModi(reg);
      }else if(acc==='borrar'){ eliminarRegistro(legajo); }
    });

    document.querySelector('#tabla thead').addEventListener('click', (ev)=>{
      const th = ev.target.closest('[data-orden]'); if(!th) return;
      document.getElementById('orden').value = th.dataset.orden;
    });

    // Botones principales (estos listeners pueden no enganchar si el script se ejecuta antes del DOM;
    // por eso más abajo agregamos un DOMContentLoaded que asegura el enganche)
    const btnBuscar = document.getElementById('btnBuscar');
    const btnVaciar = document.getElementById('btnVaciar');
    const btnAlta   = document.getElementById('btnAlta');

    if (btnBuscar) btnBuscar.addEventListener('click', cargaTabla);
    if (btnVaciar) btnVaciar.addEventListener('click', ()=>{
      const tbody = document.getElementById('tbody');
      const pie   = document.getElementById('pie');
      if (tbody) tbody.innerHTML = '<tr><td colspan="10">Sin datos</td></tr>';
      if (pie)   pie.innerHTML   = 'Alumno: <strong>Bustamante Agustin</strong> · Total: 0';
    });
    if (btnAlta)   btnAlta.addEventListener('click',   abrirAlta);

    document.getElementById('btnCancelarForm').addEventListener('click', ()=> ocultarModal('modalForm'));
    document.getElementById('btnCerrarPDF').addEventListener('click', ()=>{ document.getElementById('iframePDF').src=''; ocultarModal('modalPDF'); });
    document.getElementById('btnCerrarRespuesta').addEventListener('click', ()=> ocultarModal('modalRespuesta'));
    document.getElementById('formLiquidacion').addEventListener('submit', enviarFormulario);

    // 1ª carga: conceptos
    cargarConceptos();

    // Exporto funciones clave para los wrappers (respaldo onClick)
    window.cargaTabla = cargaTabla;
    window.abrirAlta  = abrirAlta;
  })();

// Función auxiliar: aplica filtros del DOM (fila .filtros) sobre la lista recibida
function aplicarFiltrosCliente(lista){
  if (!Array.isArray(lista) || lista.length === 0) return lista;
  const filaFiltros = document.querySelector('#tabla thead tr.filtros');
  if (!filaFiltros) return lista;

  const controles = Array.from(filaFiltros.querySelectorAll('input, select, textarea'))
    .filter(c=> c && c.id && String(c.value).trim() !== '');
  if (controles.length === 0) return lista;

  const valores = controles.map(c=> ({ id: c.id, value: String(c.value).trim().toLowerCase() }));

  // Filtrado flexible: para cada registro, todos los filtros deben coincidir en al menos
  // un campo del registro (búsqueda por substring, case-insensitive).
  return lista.filter(reg => {
    const hay = valores.every(f => {
      const v = f.value;
      // buscar en todas las propiedades del registro
      return Object.values(reg).some(prop => String(prop||'').toLowerCase().includes(v));
    });
    return hay;
  });
}

// ===== BLOQUE "SEGURO": listeners garantizados + wrappers globales =====

document.addEventListener('DOMContentLoaded', () => {
      const btnBuscar = document.getElementById('btnBuscar');
      const btnVaciar = document.getElementById('btnVaciar');
      const btnAlta   = document.getElementById('btnAlta');

      // Wrappers globales como respaldo (llamados por los onclick de los botones)
      window.__cargaTabla = () => {
        if (typeof window.cargaTabla === 'function') return window.cargaTabla();
        alert('No se encontró la función cargaTabla(). Verifica que el script principal esté cargando.');
      };

      window.__vaciarTabla = () => {
        const tbody = document.getElementById('tbody');
        const pie   = document.getElementById('pie');
        if (tbody) tbody.innerHTML = '<tr><td colspan="10">Sin datos</td></tr>';
        if (pie)   pie.innerHTML   = 'Alumno: <strong>Bustamante Agustin</strong> · Total: 0';
      };

      window.__abrirAlta = () => {
        if (typeof window.abrirAlta === 'function') return window.abrirAlta();
        alert('No se encontró la función abrirAlta(). Verifica que el script principal esté cargando.');
      };

      // Enganche “garantizado”
      if (btnBuscar) btnBuscar.addEventListener('click', window.__cargaTabla, { once:false });
      if (btnVaciar) btnVaciar.addEventListener('click', window.__vaciarTabla, { once:false });
      if (btnAlta)   btnAlta.addEventListener('click',   window.__abrirAlta,   { once:false });

      console.log('[ABM] Listeners enganchados ');
    });

    // ===== FILTROS POR COLUMNA =====
const filtros = {
  legajo: document.getElementById('f_legajo'),
  nombre: document.getElementById('f_nombre'),
  fecha: document.getElementById('f_fecha'),
  mes: document.getElementById('f_mes'),
  sueldo: document.getElementById('f_sueldo'),
  concepto: document.getElementById('f_concepto'),
  monto: document.getElementById('f_monto')
};

// Guarda los registros cargados para filtrar localmente
let registrosCargados = [];

// Reemplazá dentro de tu función renderTabla(json.liquidaciones||[]) esto:
function renderTabla(list){
  registrosCargados = list; // guardamos los originales
  actualizarTablaFiltrada();
}

function actualizarTablaFiltrada(){
  const tbody=document.getElementById('tbody');
  tbody.innerHTML='';
  const filtro = {
    legajo: filtros.legajo.value.toLowerCase(),
    nombre: filtros.nombre.value.toLowerCase(),
    fecha: filtros.fecha.value,
    mes: filtros.mes.value.toLowerCase(),
    sueldo: filtros.sueldo.value,
    concepto: filtros.concepto.value.toLowerCase(),
    monto: filtros.monto.value
  };

  const filtrados = registrosCargados.filter(r=>{
    if(filtro.legajo && !r.LegajoEmpleado.toLowerCase().includes(filtro.legajo)) return false;
    if(filtro.nombre && !r.ApellidoYNombres.toLowerCase().includes(filtro.nombre)) return false;
    if(filtro.fecha && !r.Fecha_liquidacion.includes(filtro.fecha)) return false;
    if(filtro.mes && !r.MesDeLiquidacion.toLowerCase().includes(filtro.mes)) return false;
    if(filtro.sueldo && Number(r.SueldoBasico)<Number(filtro.sueldo)) return false;
    if(filtro.concepto && !r.concepto_no_remunerativo_1.toLowerCase().includes(filtro.concepto)) return false;
    if(filtro.monto && Number(r.Monto_no_remunerativo_1)<Number(filtro.monto)) return false;
    return true;
  });

  if(filtrados.length===0){
    tbody.innerHTML='<tr><td colspan="10">Sin resultados</td></tr>';
    document.getElementById('pie').innerHTML='Alumno: <strong>Bustamante Agustin</strong> · Total: 0';
    return;
  }

  filtrados.forEach(reg=>{
    const tr=document.createElement('tr');
    tr.innerHTML=`
      <td>${reg.LegajoEmpleado}</td>
      <td>${reg.ApellidoYNombres}</td>
      <td>${reg.Fecha_liquidacion}</td>
      <td>${reg.MesDeLiquidacion}</td>
      <td>${Number(reg.SueldoBasico||0).toFixed(2)}</td>
      <td>${reg.concepto_no_remunerativo_1||''}</td>
      <td>${Number(reg.Monto_no_remunerativo_1||0).toFixed(2)}</td>
      <td>${reg.pdf_bytes>0?'<button class="boton-accion" data-accion="pdf">PDF</button>':'-'}</td>
      <td><button class="boton-accion" data-accion="modi">Modi</button></td>
      <td><button class="boton-accion" data-accion="borrar">Borrar</button></td>`;
    tbody.appendChild(tr);
  });

  document.getElementById('pie').innerHTML=`Alumno: <strong>Bustamante Agustin</strong> · Total: ${filtrados.length}`;
}

// Eventos
Object.values(filtros).forEach(inp=>inp.addEventListener('input', actualizarTablaFiltrada));

// Botón limpiar filtros
document.getElementById('btnLimpiar').addEventListener('click',()=>{
  Object.values(filtros).forEach(f=>f.value='');
  actualizarTablaFiltrada();
});
