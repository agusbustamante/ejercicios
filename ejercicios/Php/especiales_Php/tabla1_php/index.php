<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Liquidaciones de Sueldos</title>
  <link rel="stylesheet" href="styles.css?v=9">
</head>
<body>
  <header class="barra-superior">
    <div class="barra-inner">
      <h1 class="titulo">Liquidaciones de Sueldos</h1>
      <div class="acciones-header">
        <!-- Input de ORDEN visible (se completa al clickear los <th>) -->
        <label style="display:flex;align-items:center;gap:.4rem">
          Orden:
          <input id="orden" value="LegajoEmpleado" style="width:140px;height:32px;padding:.2rem"/>
        </label>
        <button id="btnBuscar" class="boton">Cargar datos</button>
        <button id="btnVaciar" class="boton">Vaciar datos</button>
      </div>
    </div>
  </header>

  <main class="tabla-contenedor">
    <div class="tabla-wrapper">
      <table id="tabla" aria-label="Tabla de liquidaciones de sueldos">
        <thead>
          <!-- Fila 1: TÍTULOS (click = cambia orden) -->
          <tr>
            <th data-orden="LegajoEmpleado"            campo-dato="legajo">Legajo</th>
            <th data-orden="ApellidoYNombres"          campo-dato="apellido">Apellido y nombres</th>
            <th data-orden="Fecha_liquidacion"         campo-dato="fecha">Fecha</th>
            <th data-orden="MesDeLiquidacion"          campo-dato="mes">Mes</th>
            <th data-orden="SueldoBasico"              campo-dato="sueldo">Sueldo básico</th>
            <th data-orden="CodConceptoNoRem"          campo-dato="concepto">Concepto no rem. 1</th>
            <th data-orden="Monto_no_remunerativo_1"   campo-dato="monto">Monto no rem. 1</th>
            <th campo-dato="pdf">PDF</th>
          </tr>

          <!-- Fila 2: FILTROS — Mes es DESPLEGABLE fijo (Enero–Diciembre) -->
          <tr>
            <th></th>
            <th></th>
            <th></th>
            <th>
              <select id="f_liquidaciones_mes_num" style="min-width:160px;max-width:100%;">
                <option value="" selected>(todos)</option>
                <option value="1">Enero</option>
                <option value="2">Febrero</option>
                <option value="3">Marzo</option>
                <option value="4">Abril</option>
                <option value="5">Mayo</option>
                <option value="6">Junio</option>
                <option value="7">Julio</option>
                <option value="8">Agosto</option>
                <option value="9">Septiembre</option>
                <option value="10">Octubre</option>
                <option value="11">Noviembre</option>
                <option value="12">Diciembre</option>
              </select>
            </th>
            <th></th>
            <th></th>
            <th></th>
            <th></th>
          </tr>
        </thead>

        <tbody id="tbody">
          <tr><td colspan="8">Esperando respuesta del servidor…</td></tr>
        </tbody>

        <tfoot>
          <tr>
            <td id="pie" colspan="8">Alumno: <strong>Bustamante Agustin</strong> · Total: 0</td>
          </tr>
        </tfoot>
      </table>
    </div>
  </main>

  <footer class="pie">Programación en ambiente de redes – 2025 · Alumno: Bustamante Agustin</footer>

<script>
(function(){

  // --- Construir parámetros: ORDEN + FILTROS ---
  function armarParams(){
    const p = new URLSearchParams();
    p.append('orden', document.getElementById('orden').value);

    // Mes (1..12) desde el select fijo (único filtro visible)
    p.append('f_liquidaciones_mes_num', document.getElementById('f_liquidaciones_mes_num').value);

    return p;
  }

  // --- Render de filas ---
  function renderTabla(list){
    const tbody = document.getElementById('tbody');
    tbody.innerHTML = '';
    if(!list.length){
      tbody.innerHTML = '<tr><td colspan="8">Sin resultados</td></tr>';
      return;
    }
    const frag = document.createDocumentFragment();
    list.forEach(reg=>{
      const tr = document.createElement('tr');
      const cols = [
        ['legajo',  reg.LegajoEmpleado],
        ['apellido',reg.ApellidoYNombres],
        ['fecha',   reg.Fecha_liquidacion],
        ['mes',     reg.MesDeLiquidacion],
        ['sueldo',  Number(reg.SueldoBasico ?? 0).toFixed(2)],
        ['concepto',reg.CodConceptoNoRem ?? ''],
        ['monto',   Number(reg.Monto_no_remunerativo_1 ?? 0).toFixed(2)],
        ['pdf',     reg.pdf_liquidacion ?? '']
      ];
      cols.forEach(([k,v])=>{
        const td = document.createElement('td');
        td.setAttribute('campo-dato', k);
        td.textContent = v ?? '';
        tr.appendChild(td);
      });
      frag.appendChild(tr);
    });
    tbody.appendChild(frag);
  }

  // --- fetch(): POST + alert ANTES y DESPUÉS ---
  async function cargaTabla(){
    const params = armarParams();

    // antes del AJAX
    alert('Enviando (orden + filtros):\n' + params.toString());

    document.getElementById('tbody').innerHTML =
      '<tr><td colspan="8">Esperando respuesta del servidor…</td></tr>';

    try{
      const resp = await fetch('salidaJsonLiquidacionesConPrepare.php', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body: params.toString()
      });
      const json = await resp.json();

      // después del AJAX
      alert('JSON arribado:\n' + JSON.stringify(json, null, 2));

      renderTabla(json.liquidaciones || []);
      document.getElementById('pie').innerHTML =
        'Alumno: <strong>Bustamante Agustin</strong> · Total: ' + (json.cuenta ?? 0);
    }catch(err){
      console.error(err);
      alert('Error de comunicación con el servidor.');
    }
  }

  // --- Click en encabezados: setear ORDEN y recargar ---
  // Click en encabezados: REEMPLAZAR el input 'orden' por la columna clickeada (no acumula)
  document.querySelector('#tabla thead').addEventListener('click', (ev)=>{
    const th = ev.target.closest('[data-orden]');
    if(!th) return;
    const col = th.dataset.orden; // p.ej. Fecha_liquidacion
    const input = document.getElementById('orden');
    // Reemplazamos completamente el orden por la columna clickeada
    input.value = col;
    // no llamamos a cargaTabla() aquí: el usuario debe presionar "Cargar datos"
  });

  // --- Botones ---
  document.getElementById('btnBuscar').addEventListener('click', cargaTabla);
  document.getElementById('btnVaciar').addEventListener('click', ()=>{
    document.getElementById('tbody').innerHTML = '';
    document.getElementById('pie').innerHTML =
      'Alumno: <strong>Bustamante Agustin</strong> · Total: 0';
  });

  // Si querés primera carga automática:
  // cargaTabla();
})();
</script>
</body>
</html>
