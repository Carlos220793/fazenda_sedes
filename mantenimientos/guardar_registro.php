<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/auth.php';
requireLoginOrExit();

header('Content-Type: application/json; charset=utf-8');

header('Access-Control-Allow-Origin: *');

$cn = new mysqli("10.110.6.148", "BaseDatos", "sysadm1n2207", "mantenimientos");
if ($cn->connect_error) {
  echo json_encode(['success'=>false, 'error'=>'❌ Error de conexión: '.$cn->connect_error]);
  exit;
}
$cn->set_charset('utf8mb4');

$input = json_decode(file_get_contents("php://input"), true);
if (!$input) {
  echo json_encode(['success'=>false, 'error'=>'No se recibió ningún dato']);
  exit;
}

$tipo               = trim($input["tipo"]               ?? '');
$placa              = trim($input["placa"]              ?? '');
$marca              = trim($input["marca"]              ?? '');
$modelo             = trim($input["modelo"]             ?? '');
$serial             = trim($input["serial"]             ?? '');
$fecha              = trim($input["fecha"]              ?? ''); 
$tecnico            = trim($input["tecnico"]            ?? '');
$tipo_mantenimiento = trim($input["tipoMantenimiento"]  ?? '');
$estado             = trim($input["estado"]             ?? 'Pendiente');
$ubicacion          = trim($input["ubicacion"]          ?? '');
$centro_costo       = trim($input["centroCosto"]        ?? '');
$url_ticket         = trim($input["urlTicket"]          ?? '');
$observaciones      = trim($input["observaciones"]      ?? '');


$usuario_registro   = $_SESSION['usuario'] ?? 'sede_user';


$sede_id = userSedeId();
if (isAdmin()) {
  $override = isset($input['sede_id']) ? (int)$input['sede_id'] : 0;
  if ($override > 0) {
    $sede_id = $override;
  } else {
    $sede_id = 5; 
  }
}
if ($sede_id === null) {
  echo json_encode(['success'=>false, 'error'=>'Sede no definida en sesión']);
  exit;
}


$sede_nombre = '';
if (!empty($input['sede_nombre']) && is_string($input['sede_nombre'])) {
  $sede_nombre = trim($input['sede_nombre']);
} elseif (!empty($_SESSION['sede_nombre'])) {
  $sede_nombre = trim((string)$_SESSION['sede_nombre']);
} else {
  
  if ($q = $cn->prepare("SELECT nombre FROM sedes WHERE id_sede = ?")) {
    $q->bind_param("i", $sede_id);
    if ($q->execute()) {
      $q->bind_result($nombre);
      if ($q->fetch()) $sede_nombre = $nombre;
    }
    $q->close();
  }
}

if ($sede_nombre !== '') {
  $usuario_registro = $sede_nombre;
}


$sql = "INSERT INTO registros
          (tipo, placa, marca, modelo, serial, fecha, tecnico, tipo_mantenimiento, estado, ubicacion,
           centro_costo, url_ticket, observaciones, fecha_registro, usuario_registro, sede_id)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?, NOW(), ?, ?)";

$stmt = $cn->prepare($sql);
if (!$stmt) {
  echo json_encode(['success'=>false, 'error'=>'❌ Prepare falló: '.$cn->error]);
  exit;
}

$stmt->bind_param(
  "ssssssssssssssi",
  $tipo, $placa, $marca, $modelo, $serial, $fecha, $tecnico,
  $tipo_mantenimiento, $estado, $ubicacion, $centro_costo, $url_ticket,
  $observaciones, $usuario_registro, $sede_id
);

$ok = $stmt->execute();

echo json_encode([
  'success' => $ok,
  'mensaje' => $ok ? 'Guardado exitosamente' : null,
  'id'      => $ok ? $stmt->insert_id : null,
  'error'   => $ok ? null : ('❌ Error al guardar: '.$stmt->error)
], JSON_UNESCAPED_UNICODE);

$stmt->close();
$cn->close();
