<?php
session_start();

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Credentials: true");
header("Access-Control-Allow-Headers: Content-Type");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Content-Type: application/json; charset=utf-8");
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

if (!($_SESSION["autenticado"] ?? false)) {
  http_response_code(401);
  echo json_encode(["error"=>"no_auth"]);
  exit;
}

$cn = new mysqli("10.110.6.148", "BaseDatos", "sysadm1n2207", "mantenimientos");
if ($cn->connect_error) { http_response_code(500); die(json_encode(["error"=>"db_connect"])); }
$cn->set_charset("utf8mb4");

$rol    = $_SESSION["rol"] ?? "user";
$sedeId = $_SESSION["sede_id"] ?? null;


if (isset($_GET['debug'])) {
  echo json_encode(["rol"=>$rol, "sede_id"=>$sedeId, "session"=>$_SESSION]); exit;
}

$limit  = max(1, min((int)($_GET["limit"] ?? 50), 200));
$offset = max(0, (int)($_GET["offset"] ?? 0));

if ($rol === "admin") {
  $sql = "SELECT * FROM registros ORDER BY fecha DESC, id DESC LIMIT ? OFFSET ?";
  $stmt = $cn->prepare($sql);
  $stmt->bind_param("ii", $limit, $offset);
} else {
  if ($sedeId === null) { 
    http_response_code(400); echo json_encode(["error"=>"sede_missing"]); exit;
  }
  $sql = "SELECT * FROM registros WHERE sede_id = ? ORDER BY fecha DESC, id DESC LIMIT ? OFFSET ?";
  $stmt = $cn->prepare($sql);
  $stmt->bind_param("iii", $sedeId, $limit, $offset);
}

$stmt->execute();
$res  = $stmt->get_result();
echo json_encode($res->fetch_all(MYSQLI_ASSOC));
