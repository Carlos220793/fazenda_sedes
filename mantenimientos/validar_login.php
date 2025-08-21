<?php
session_start();

header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=utf-8");

$usuarioAdmin = "Administrator";
$claveAdmin   = "Adm1n#2025";

$usuarioMeta  = "Administrador";
$claveMeta    = "sysadm1n";

$input = json_decode(file_get_contents("php://input"), true) ?? [];
$u = $input["usuario"] ?? "";
$c = $input["clave"]   ?? "";


function getSedeNombre(?mysqli $cn, $sedeId) {
  if (!$cn || !$sedeId) return null;
  if (!is_numeric($sedeId)) return null;
  $stmt = $cn->prepare("SELECT nombre FROM sedes WHERE id_sede = ? LIMIT 1");
  if (!$stmt) return null;
  $stmt->bind_param("i", $sedeId);
  $stmt->execute();
  $res = $stmt->get_result();
  $row = $res ? $res->fetch_assoc() : null;
  $stmt->close();
  return $row["nombre"] ?? null;
}


if ($u === $usuarioAdmin && $c === $claveAdmin) {
  $_SESSION = [];
  session_regenerate_id(true);
  $_SESSION["autenticado"] = true;
  $_SESSION["admin"]       = true;
  $_SESSION["rol"]         = "admin";
  unset($_SESSION["sede_id"]);

  
  $sedeNombre = "Regional Meta_Fincas";

  echo json_encode([
    "success"      => true,
    "role"         => "admin",
    "sede_id"      => null,
    "sede_nombre"  => $sedeNombre   // ADD
  ]);
  exit;
}


if ($u === $usuarioMeta && $c === $claveMeta) {
  $_SESSION = [];
  session_regenerate_id(true);
  $_SESSION["autenticado"] = true;
  unset($_SESSION["admin"]);
  $_SESSION["rol"]     = "user";
  $_SESSION["sede_id"] = 5;

 
  $sedeNombre = null;
  
  $tmp = @new mysqli("10.110.6.148", "BaseDatos", "sysadm1n2207", "mantenimientos");
  if (!$tmp->connect_error) {
    $tmp->set_charset("utf8mb4");
    $sedeNombre = getSedeNombre($tmp, 5);
    $tmp->close();
  }

  echo json_encode([
    "success"      => true,
    "role"         => "user",
    "sede_id"      => 5,
    "sede_nombre"  => $sedeNombre   
  ]);
  exit;
}


$cn = @new mysqli("10.110.6.148", "BaseDatos", "sysadm1n2207", "mantenimientos");
if (!$cn->connect_error) {
  $cn->set_charset("utf8mb4");
  $stmt = $cn->prepare("SELECT id_usuario, usuario, clave, rol, id_sede FROM usuarios WHERE usuario = ? LIMIT 1");
  $stmt->bind_param("s", $u);
  $stmt->execute();
  $res  = $stmt->get_result();
  $user = $res->fetch_assoc();
  $stmt->close();

  if ($user) {

    $hashInput = hash("sha256", $c);
    $ok = hash_equals($user["clave"], $hashInput) || $user["clave"] === $c;

    if ($ok) {
      $_SESSION = [];
      session_regenerate_id(true);
      $_SESSION["autenticado"] = true;
      $_SESSION["usuario_id"]  = (int)$user["id_usuario"];
      $_SESSION["usuario"]     = $user["usuario"];

      
      $sedeId = isset($user["id_sede"]) ? (int)$user["id_sede"] : null;
      $sedeNombre = null;

      if (($user["rol"] ?? "") === "admin") {
        $_SESSION["admin"] = true;
        $_SESSION["rol"]   = "admin";
        unset($_SESSION["sede_id"]);

        
        $sedeNombre = "Regional Meta_Fincas";

        echo json_encode([
          "success"      => true,
          "role"         => "admin",
          "sede_id"      => null,
          "sede_nombre"  => $sedeNombre   // ADD
        ]);
      } else {
        unset($_SESSION["admin"]);
        $_SESSION["rol"]     = "user";
        $_SESSION["sede_id"] = $sedeId;

       
        if (!empty($sedeId)) {
          $sedeNombre = getSedeNombre($cn, $sedeId);
        }

        echo json_encode([
          "success"      => true,
          "role"         => "user",
          "sede_id"      => $_SESSION["sede_id"],
          "sede_nombre"  => $sedeNombre   // ADD
        ]);
      }
      $cn->close();
      exit;
    }
  }
  $cn->close();
}


$_SESSION = [];
session_destroy();
http_response_code(401);
echo json_encode([
  "success" => false,
  "error"   => "Usuario o contraseña no válidos"
]);
