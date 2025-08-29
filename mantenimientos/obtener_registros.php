<?php

$origin = $_SERVER['HTTP_ORIGIN'] ?? '';
$allowedOrigins = [
    'http://10.110.6.148',
    'http://localhost',          
];

if (in_array($origin, $allowedOrigins, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
}
header("Content-Type: application/json; charset=utf-8");


if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}


require_once __DIR__ . '/api_bootstrap.php';
require_once __DIR__ . '/auth.php';
requireLoginOrExit();
maybeDebugAndExit();


if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

$SESSION_IDLE_MAX = 7200; 

if (!empty($_SESSION['usuario_id'])) {
    $now = time();

   
    if (isset($_SESSION['last_activity']) && ($now - $_SESSION['last_activity'] > $SESSION_IDLE_MAX)) {
        $_SESSION = [];
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params['path'] ?? '/',
                $params['domain'] ?? '',
                $params['secure'] ?? false,
                $params['httponly'] ?? true
            );
        }
        session_destroy();
        http_response_code(401);
        echo json_encode(['error' => 'Sesión expirada']);
        exit;
    }

   
    $_SESSION['last_activity'] = $now;

   
    $params = session_get_cookie_params();
    setcookie(session_name(), session_id(), [
        'expires'  => $now + $SESSION_IDLE_MAX,
        'path'     => $params['path'] ?: '/',
        'domain'   => $params['domain'] ?? '',
        'secure'   => $params['secure'] ?? false,  
        'httponly' => $params['httponly'] ?? true,
        'samesite' => $params['samesite'] ?? 'Lax',
    ]);
}


$cn = new mysqli("10.110.6.148", "BaseDatos", "sysadm1n2207", "mantenimientos");
if ($cn->connect_error) {
    echo json_encode([
        'success' => false,
        'error' => '❌ Error de conexión: ' . $cn->connect_error
    ]);
    exit;
}
$cn->set_charset("utf8mb4");


function canonEstado($v) {
    $e = mb_strtolower(trim($v ?? ''), 'UTF-8');
    if ($e === 'pendiente') return 'Pendiente';
    if ($e === 'en progreso' || $e === 'en  progreso') return 'En progreso';
    if ($e === 'finalizado' || $e === 'finalizada') return 'Finalizado';
    return 'Pendiente';
}


if (isAdmin()) {
    $sql  = "SELECT * FROM registros ORDER BY fecha_registro DESC";
    $stmt = $cn->prepare($sql);
} else {
    $sede = userSedeId();
    if ($sede === null) {
        echo json_encode([]);
        exit;
    }
    $sql  = "SELECT * FROM registros WHERE sede_id = ? ORDER BY fecha_registro DESC";
    $stmt = $cn->prepare($sql);
    $stmt->bind_param("i", $sede);
}

$stmt->execute();
$res = $stmt->get_result();

$registros = [];
while ($fila = $res->fetch_assoc()) {
   
    $fila['estado'] = canonEstado($fila['estado'] ?? '');

   
    if (array_key_exists('tipo_mantenimiento', $fila)) $fila['tipoMantenimiento'] = $fila['tipo_mantenimiento'];
    if (array_key_exists('centro_costo', $fila))       $fila['centroCosto']       = $fila['centro_costo'];
    if (array_key_exists('usuario_registro', $fila))   $fila['usuarioRegistro']   = $fila['usuario_registro'];
    $fila['urlTicket'] = $fila['url_ticket'] ?? '';

   
    unset($fila['tipo_mantenimiento'], $fila['centro_costo'], $fila['usuario_registro'], $fila['url_ticket']);

    $registros[] = $fila;
}

echo json_encode($registros, JSON_UNESCAPED_UNICODE);
$cn->close();
