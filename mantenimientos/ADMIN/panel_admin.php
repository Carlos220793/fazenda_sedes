<?php
session_start();
if (empty($_SESSION['admin'])) {
  header("Location: /mantenimientos/index.html");
  exit;
}


$conexion = new mysqli("10.110.6.148", "BaseDatos", "sysadm1n2207", "mantenimientos");
if ($conexion->connect_error) {
  die("❌ Error de conexión: " . $conexion->connect_error);
}


$tablas = [
  "tipo_equipo"        => "Tipo de Equipo",
  "marca"              => "Marca",
  "modelo"             => "Modelo",
  "tecnico"            => "Técnico Responsable",
  "tipo_mantenimiento" => "Tipo de Mantenimiento",
  "estado"             => "Estado",
  "ubicacion"          => "Ubicación"
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1"/>
  <title>Panel Administrador</title>

  
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800&display=swap" rel="stylesheet">

  <style>
    :root{
      --bg:#f5f7fa; --card:#fff; --ink:#1f2937; --muted:#6b7280;
      --primary:#1976d2; --primary-600:#1565c0;
      --danger:#d32f2f; --danger-600:#b71c1c; --border:#e5e7eb;

      
      --card-radius: 14px;
      --shadow-sm: 0 4px 10px rgba(2, 6, 23, .06);
      --shadow-md: 0 10px 24px rgba(2, 6, 23, .08);
      --ink-2: #0f172a;
      --muted-2:#64748b;
    }

    *{box-sizing:border-box}
    html,body{height:100%}
    body{
      margin:0; background:var(--bg); color:var(--ink);
      font-family: Inter, system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
      font-size:14px;
    }

    
    .container{
      max-width: 1500px;
      margin: 32px auto 60px;
      padding: 18px;
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 12px;
      box-shadow: 0 8px 20px rgba(15,23,42,.06);
    }

    h1{ text-align:center; margin: 0 0 16px; font-size:20px; }
    .sub{ text-align:center; color:var(--muted); margin:0 0 18px; font-size:12px;}

    
    #alerta{ border-radius:10px; padding:10px 12px; margin:10px 0 18px; font-size:13px }

    
    .tabla-container{
      border:1px solid var(--border); border-radius:12px; padding:14px; margin: 18px 0 24px;
      background:#fff;
    }
    .tabla-container h2{ margin:0 0 10px; font-size:16px }

    
    .inline-form{ display:flex; gap:8px; align-items:center; flex-wrap:wrap; margin-bottom:10px }
    .input{
      height:32px; padding:0 10px; border:1px solid #d1d5db; border-radius:8px; font-size:13px; min-width:220px;
      transition: box-shadow .2s ease, border-color .2s ease;
    }
    .input:focus{ outline:none; border-color:#b7cdf6; box-shadow:0 0 0 3px rgba(59,130,246,.15); }
    .btn{
      height:32px; padding:0 10px; border:none; border-radius:8px; font-size:12px; font-weight:600; cursor:pointer; transition:.2s;
    }
    .btn.primary{ background:var(--primary); color:#fff }
    .btn.primary:hover{ background:var(--primary-600) }
    .btn.danger{ background:var(--danger); color:#fff }
    .btn.danger:hover{ background:var(--danger-600) }

    
    .table-wrap{ overflow:auto; border:1px solid var(--border); border-radius:10px }
    table{ border-collapse:collapse; width:100%; background:#fff; font-size:13px }
    th, td{ padding:8px 10px; border-bottom:1px solid var(--border) }
    th{ background:#f9fafb; text-align:left; position:sticky; top:0; z-index:1 }
    tr:nth-child(even) td{ background:#fafafa }

    
    .acciones{ white-space:nowrap }
    .acciones form{ display:inline-flex; gap:8px; align-items:center; margin:0 8px 0 0 }
    .acciones input[type="text"]{ height:30px; padding:0 8px; border:1px solid #d1d5db; border-radius:8px; width:200px; font-size:13px }
    .acciones .btn{ height:30px; padding:0 10px }

    
    .btn-logout{
      position:fixed; top:14px; right:18px; z-index:9999;
      background:var(--danger); color:#fff; padding:6px 12px; border-radius:8px;
      text-decoration:none; font-size:12px; font-weight:700; box-shadow:0 4px 14px rgba(0,0,0,.15)
    }
    .btn-logout:hover{ background:var(--danger-600) }

    @media (max-width:720px){
      .input{ min-width:180px }
      .acciones input[type="text"]{ width:160px }
    }
   
    td.acciones { width: 460px !important; }

   
    .acciones{ display: flex; gap: 10px; align-items: center; justify-content: flex-start; white-space: nowrap; }
    .acciones form{ margin: 0; }
    .acciones .btn{ min-width: 74px; text-align: center; }

    .table-wrap{ padding-right: 8px; }
    .titulo-admin {
      font-size: 38px; font-weight: 700; text-align: center; color: #1976d2; margin-bottom: 5px;
      animation: aparecer 1s ease-out forwards, brillo 3s linear infinite;
    }
    .subtitulo-admin {
      font-size: 14px; text-align: center; color: #555; margin-bottom: 25px; opacity: 0;
      animation: aparecer 1s ease-out forwards; animation-delay: 0.4s;
    }
    @keyframes aparecer { 0%{opacity:0;transform:translateY(15px)} 100%{opacity:1;transform:translateY(0)} }
    @keyframes brillo   { 0%,100%{text-shadow:0 0 5px #90caf9,0 0 10px #64b5f6} 50%{text-shadow:0 0 15px #64b5f6,0 0 25px #42a5f5} }

    
    .dash-card{
      background: linear-gradient(180deg,#ffffff,#fbfcff);
      border:1px solid var(--border); border-radius:var(--card-radius);
      padding:14px; box-shadow:var(--shadow-sm);
    }
    .filters-row{ display:flex; gap:12px; flex-wrap:wrap; margin-bottom:12px; align-items:center; }
    .filters-row .input, .filters-row select{
      height:36px; border-radius:10px; border:1px solid #d7dbe3; padding:0 12px; background:#fff; color:var(--ink);
      transition: box-shadow .2s ease, border-color .2s ease;
    }
    #btn-aplicar{ height:36px; border-radius:10px; padding:0 14px; box-shadow:var(--shadow-sm); }
    #btn-aplicar:hover{ box-shadow:var(--shadow-md); }

    .kpis-row{ display:grid; grid-template-columns:repeat(3,minmax(220px,1fr)); gap:12px; margin-bottom:12px; }
    .kpi-card{ display:grid; grid-template-rows:auto 1fr; gap:6px; }
    .kpi-card:hover{ box-shadow:var(--shadow-md); transition:box-shadow .25s ease; }
    .kpi-card b{ color:var(--muted-2); font-weight:600; letter-spacing:.2px; }
    .kpi-value{ font-size:28px; font-weight:800; color:var(--ink-2); }

    .chart-box{ height:320px; padding:6px 6px 0 6px; }

    
    #tabla-sedes-dash{ border-collapse: separate; border-spacing: 0; }
    #tabla-sedes-dash thead th{
      background:#f6f8fb; color:#334155; border-bottom:1px solid #e6eaf2;
    }
    #tabla-sedes-dash tbody tr td{ border-bottom:1px dashed #eaeef6; }
    #tabla-sedes-dash tbody tr:hover td{ background:#f9fbff; }
    #tabla-sedes-dash td:nth-child(n+2){ text-align: right; } 
  </style>

  
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
  <a href="../logout.php" class="btn-logout">🚪 Cerrar sesión</a>

  <div class="container">
    <h1 class="titulo-admin">Panel Administrator📊</h1>
    <p class="subtitulo-admin">⚙️Gestiona o modifica los valores que usa en el formulario principal</p>

    
    <section class="dash-card" id="dash-sedes">
     
      <div class="filters-row">
        <input type="date" id="filtro-from" class="input">
        <input type="date" id="filtro-to" class="input">

        <select id="filtro-type" class="input">
          <option value="all">Todos los tipos</option>
          <option value="Preventivo">Preventivo</option>
          <option value="Correctivo">Correctivo</option>
        </select>

        <select id="filtro-status" class="input">
          <option value="all">Todos</option>
          <option value="abierto">Abierto (Pendiente + En progreso)</option>
          <option value="pendiente">Pendiente</option>
          <option value="en_progreso">En progreso</option>
          <option value="finalizado">Finalizado</option>
          <option value="cerrado">Cerrado (Finalizado)</option>
        </select>

        <button class="btn primary" id="btn-aplicar">Aplicar</button>
      </div>

     
      <div class="kpis-row">
        <div class="kpi-card dash-card">
          <b>Total Global</b>
          <div class="kpi-value" id="kpi-total">0</div>
        </div>
        <div class="kpi-card dash-card">
          <b>Sede Líder</b>
          <div class="kpi-value" id="kpi-lider">-</div>
        </div>
        <div class="kpi-card dash-card">
          <b>Brecha vs 2°</b>
          <div class="kpi-value" id="kpi-brecha">0</div>
        </div>
      </div>

      
      <div class="dash-card chart-box">
        <canvas id="grafico-sedes"></canvas>
      </div>

      
      <div class="table-wrap" style="margin-top:12px">
        <table id="tabla-sedes-dash">
          <thead>
            <tr>
              <th>Sede</th>
              <th style="width:90px">Pend.</th>
              <th style="width:90px">En prog.</th>
              <th style="width:90px">Final.</th>
              <th style="width:90px">Abiertos</th>
              <th style="width:90px">Total</th>
              <th style="width:110px">% Cierre</th>
            </tr>
          </thead>
          <tbody></tbody>
        </table>
      </div>
    </section>
    

    <?php
      $mensaje = $_GET['msg'] ?? '';
      $tablaSel = $_GET['tabla'] ?? '';
      $textos = [
        'ok'           => 'Valor agregado correctamente.',
        'ok_edit'      => 'Valor actualizado correctamente.',
        'duplicado'    => 'Ese nombre ya existe.',
        'faltan_datos' => 'Faltan datos para completar la operación.',
        'error'        => 'Ocurrió un error, intenta de nuevo.'
      ];
      $clases = [
        'ok'           => 'background:#e8f5e9;border:1px solid #a5d6a7;color:#2e7d32;',
        'ok_edit'      => 'background:#e3f2fd;border:1px solid #90caf9;color:#1565c0;',
        'duplicado'    => 'background:#ffebee;border:1px solid #ef9a9a;color:#c62828;',
        'faltan_datos' => 'background:#fff3e0;border:1px solid #ffcc80;color:#ef6c00;',
        'error'        => 'background:#ffebee;border:1px solid #ef9a9a;color:#c62828;'
      ];
      if ($mensaje && isset($textos[$mensaje])): ?>
        <div id="alerta" style="<?= $clases[$mensaje] ?>">
          <?= htmlspecialchars($textos[$mensaje]) ?>
          <?php if ($tablaSel): ?>
            <span style="opacity:.7;margin-left:8px;">(Tabla: <?= htmlspecialchars($tablaSel) ?>)</span>
          <?php endif; ?>
        </div>
        <script>
          setTimeout(()=>{ var a=document.getElementById('alerta'); if(a) a.style.display='none'; }, 3500);
        </script>
    <?php endif; ?>

    <?php foreach ($tablas as $tabla => $titulo): ?>
      <div class="tabla-container" id="<?= $tabla ?>">
        <h2><?= $titulo ?></h2>

      
        <form method="POST" action="guardar_valor.php" class="inline-form">
          <input type="hidden" name="tabla" value="<?= $tabla ?>">
          <input class="input" type="text" name="nombre" placeholder="Nuevo valor" required>
          <button class="btn primary" type="submit">Agregar</button>
        </form>

        
        <div class="table-wrap">
          <table>
            <tr>
              <th style="width:80px">ID</th>
              <th>Nombre</th>
              <th style="width:460px">Acciones</th>
            </tr>
            <?php
              $res = $conexion->query("SELECT * FROM $tabla ORDER BY nombre");
              while ($fila = $res->fetch_assoc()):
            ?>
              <tr>
                <td><?= (int)$fila['id'] ?></td>
                <td><?= htmlspecialchars($fila['nombre']) ?></td>
                <td class="acciones">
                  
                  <form method="POST" action="editar_valor.php" class="inline-form" style="margin:0;">
                    <input type="hidden" name="tabla" value="<?= $tabla ?>">
                    <input type="hidden" name="id" value="<?= (int)$fila['id'] ?>">
                    <input class="input" type="text" name="nombre" value="<?= htmlspecialchars($fila['nombre']) ?>" required>
                    <button class="btn primary" type="submit">Editar</button>
                  </form>

                  
                  <form method="POST" action="eliminar_valor.php" style="display:inline;" onsubmit="return confirm('¿Seguro que deseas eliminar este valor?');">
                    <input type="hidden" name="tabla" value="<?= $tabla ?>">
                    <input type="hidden" name="id" value="<?= (int)$fila['id'] ?>">
                    <button class="btn danger" type="submit">Eliminar</button>
                  </form>
                </td>
              </tr>
            <?php endwhile; ?>
          </table>
        </div>
      </div>
    <?php endforeach; ?>

    <?php if (!empty($tablaSel)): ?>
      <script>
        const t = "<?= htmlspecialchars($tablaSel) ?>";
        const el = document.getElementById(t);
        if (el) el.scrollIntoView({ behavior: 'smooth', block: 'start' });
      </script>
    <?php endif; ?>
  </div>

  <script>
  
    let chartSedes = null;

    function cargarKpisSedes() {
      const from   = document.getElementById("filtro-from").value;
      const to     = document.getElementById("filtro-to").value;
      const type   = document.getElementById("filtro-type").value;
      const status = document.getElementById("filtro-status").value;

      const qs = new URLSearchParams({from, to, type, status}).toString();

     
      fetch("../admin_kpis_sedes.php?" + qs, { credentials: "same-origin" })
        .then(r => r.json())
        .then(data => {
          if (!data.ok) { console.error(data); alert(data.error || "Error"); return; }

         
          document.getElementById("kpi-total").textContent  = data.resumen.total_global ?? 0;
          document.getElementById("kpi-lider").textContent  =
            data.resumen.sede_lider ? `${data.resumen.sede_lider.nombre} (${data.resumen.sede_lider.total})` : "-";
          document.getElementById("kpi-brecha").textContent = data.resumen.brecha_vs_segundo ?? 0;

          
          const tbody = document.querySelector("#tabla-sedes-dash tbody");
          tbody.innerHTML = "";
          (data.por_sede || []).forEach(s => {
            const abiertos = (s.abiertos ?? (s.pendientes + s.en_progreso));
            const pct = s.total ? ((s.finalizados / s.total) * 100).toFixed(1) : "0.0";
            const tr = document.createElement("tr");
            tr.innerHTML = `
              <td>${s.nombre}</td>
              <td>${s.pendientes}</td>
              <td>${s.en_progreso}</td>
              <td>${s.finalizados}</td>
              <td>${abiertos}</td>
              <td>${s.total}</td>
              <td>${pct}%</td>
            `;
            tbody.appendChild(tr);
          });

         
          const labels = (data.por_sede || []).map(s => s.nombre);
          const values = (data.por_sede || []).map(s => s.total);
          const ctx = document.getElementById("grafico-sedes").getContext("2d");

         
          const grad = ctx.createLinearGradient(0, 0, 0, 320);
          grad.addColorStop(0,   "rgba(25,118,210,0.85)");
          grad.addColorStop(1.0, "rgba(25,118,210,0.25)");

          if (chartSedes) chartSedes.destroy();
          chartSedes = new Chart(ctx, {
            type: "bar",
            data: {
              labels,
              datasets: [{
                label: "Mantenimientos",
                data: values,
                backgroundColor: grad,
                borderColor: "rgba(25,118,210,.9)",
                borderWidth: 1,
                borderRadius: 10,
                barThickness: 26,
                hoverBackgroundColor: "rgba(25,118,210,0.95)"
              }]
            },
            options: {
              responsive: true,
              maintainAspectRatio: false,
              animation: { duration: 600, easing: "easeOutCubic" },
              plugins: {
                legend: { display: false },
                tooltip: {
                  backgroundColor: "#0f172a", titleColor: "#fff", bodyColor: "#e2e8f0",
                  padding: 10, displayColors: false
                }
              },
              scales: {
                x: { grid: { display: false }, ticks: { color: "#334155", font: { weight: 600 } } },
                y: { beginAtZero: true, grid: { color: "rgba(226,232,240,.6)", drawBorder: false }, ticks: { color: "#64748b" } }
              }
            }
          });
        })
        .catch(err => { console.error(err); alert("Error al cargar KPIs"); });
    }

    document.getElementById("btn-aplicar").addEventListener("click", cargarKpisSedes);
    document.addEventListener("DOMContentLoaded", cargarKpisSedes);
  </script>
</body>
</html>
