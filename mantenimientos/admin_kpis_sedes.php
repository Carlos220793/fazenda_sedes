<?php
session_start();

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=utf-8");

function out($arr, $code=200){
  http_response_code($code);
  echo json_encode($arr, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
  exit;
}
function normalize_date($d){
  if(!$d) return null;
  $d = trim($d);
  if (preg_match('~^\d{2}/\d{2}/\d{4}$~',$d)) {
    [$dd,$mm,$yy] = explode('/',$d);
    if (checkdate((int)$mm,(int)$dd,(int)$yy)) return sprintf('%04d-%02d-%02d',$yy,$mm,$dd);
    return null;
  }
  if (preg_match('~^\d{4}-\d{2}-\d{2}$~',$d)) {
    [$yy,$mm,$dd] = explode('-',$d);
    if (checkdate((int)$mm,(int)$dd,(int)$yy)) return $d;
  }
  return null;
}

/* Seguridad: solo admin */
if (!isset($_SESSION["rol"]) || $_SESSION["rol"] !== "admin") {
  out(["ok"=>false, "error"=>"Acceso denegado (solo admin)"], 403);
}

/* Conexión */
$cn = @new mysqli("10.110.6.148", "BaseDatos", "sysadm1n2207", "mantenimientos");
if ($cn->connect_error) out(["ok"=>false, "error"=>"Sin conexión a base de datos", "mysql"=>$cn->connect_error], 500);
$cn->set_charset("utf8mb4");

/* Filtros */
$fromIn   = $_GET["from"]   ?? null;
$toIn     = $_GET["to"]     ?? null;
$typeIn   = $_GET["type"]   ?? null;   // Preventivo | Correctivo | all
$statusIn = $_GET["status"] ?? null;   // all | abierto | cerrado | pendiente | en_progreso | finalizado

$from = normalize_date($fromIn);
$to   = normalize_date($toIn);

$type = (isset($typeIn) && strtolower($typeIn) !== "all") ? trim($typeIn) : null;

/* Mapeo de estado pedido → estados reales en BD */
$status = null;           // nombre del filtro que vino
$statusSet = [];          // lista para IN (...)
if (isset($statusIn) && strtolower($statusIn) !== "all") {
  $status = strtolower(trim($statusIn));
  if ($status === "abierto") {
    $statusSet = ["Pendiente", "En progreso"];
  } elseif ($status === "cerrado") {
    $statusSet = ["Finalizado"];
  } elseif ($status === "pendiente") {
    $statusSet = ["Pendiente"];
  } elseif ($status === "en_progreso") {
    $statusSet = ["En progreso"];
  } elseif ($status === "finalizado") {
    $statusSet = ["Finalizado"];
  }
}

/* WHERE dinámico */
$where  = [];
$params = [];
$types  = "";

if ($from) { $where[]="r.fecha >= ?";                          $params[]=$from." 00:00:00"; $types.="s"; }
if ($to)   { $where[]="r.fecha < DATE_ADD(?, INTERVAL 1 DAY)"; $params[]=$to;               $types.="s"; }
if ($type) {
  $where[] = "r.tipo_mantenimiento = ?";
  $params[] = $type;
  $types   .= "s";
}
if (!empty($statusSet)) {
  $place = implode(",", array_fill(0, count($statusSet), "?"));
  $where[] = "r.estado IN ($place)";
  foreach ($statusSet as $st) { $params[] = $st; $types .= "s"; }
}

/* Agregación por sede con desglose completo */
$sql = "
  SELECT
    s.id_sede,
    s.nombre,
    SUM(r.estado = 'Pendiente')   AS pendientes,
    SUM(r.estado = 'En progreso') AS en_progreso,
    SUM(r.estado = 'Finalizado')  AS finalizados,
    COUNT(*)                      AS total
  FROM registros r
  JOIN sedes s ON s.id_sede = r.sede_id
  ".(count($where) ? "WHERE ".implode(" AND ", $where) : "")."
  GROUP BY s.id_sede, s.nombre
  ORDER BY total DESC, s.nombre ASC
";

$stmt = $cn->prepare($sql);
if(!$stmt) out(["ok"=>false,"error"=>"Error al preparar consulta","mysql"=>$cn->error],500);
if($types && !$stmt->bind_param($types, ...$params)) out(["ok"=>false,"error"=>"Error al vincular parámetros"],500);
if(!$stmt->execute()) out(["ok"=>false,"error"=>"No se pudo ejecutar la consulta","mysql"=>$stmt->error],500);

$res = $stmt->get_result();
$por_sede = [];
$total_global = 0;

while ($row = $res->fetch_assoc()) {
  $row["id_sede"]     = (int)$row["id_sede"];
  $row["pendientes"]  = (int)$row["pendientes"];
  $row["en_progreso"] = (int)$row["en_progreso"];
  $row["finalizados"] = (int)$row["finalizados"];
  $row["abiertos"]    = $row["pendientes"] + $row["en_progreso"];
  $row["total"]       = (int)$row["total"];
  $por_sede[] = $row;
  $total_global += $row["total"];
}

$sede_lider = $por_sede[0] ?? null;
$segundo    = $por_sede[1] ?? null;

$porcentaje_lider = ($sede_lider && $total_global>0) ? round($sede_lider["total"]/$total_global, 4) : 0.0;
$brecha           = ($sede_lider && $segundo) ? max(0, $sede_lider["total"] - $segundo["total"]) : 0;

out([
  "ok" => true,
  "filtros" => [
    "from_input"=>$fromIn, "to_input"=>$toIn, "type_input"=>$typeIn, "status_input"=>$statusIn,
    "from"=>$from, "to"=>$to, "type_usado"=>$type, "status_usado"=>$status, "status_set"=>$statusSet
  ],
  "resumen" => [
    "total_global"      => $total_global,
    "sede_lider"        => $sede_lider ? [
      "id_sede"   => $sede_lider["id_sede"],
      "nombre"    => $sede_lider["nombre"],
      "total"     => $sede_lider["total"],
      "porcentaje"=> $porcentaje_lider
    ] : null,
    "brecha_vs_segundo" => $brecha
  ],
  "por_sede" => $por_sede,
  "meta" => ["generated_at"=>date("Y-m-d H:i:s"), "tz"=>date_default_timezone_get()]
]);
