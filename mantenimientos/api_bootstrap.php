<?php
// api_bootstrap.php
// 👇 En producción, evita imprimir errores; mándalos a log
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log'); // ruta ajustable

header('Content-Type: application/json; charset=utf-8');

// Evita que cualquier echo/notice previo rompa el JSON
if (ob_get_level() === 0) { ob_start(); }

// Manejo de CORS (ajusta si front y back están en distinto origen)
$ORIGIN = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
// ⚠️ SI tu front está en otro dominio/puerto, ponlo explícito:
$allowedOrigins = [
    'http://10.110.6.148',
  'http://localhost:5173',
  'http://localhost:3000',
  'https://tu-frontend.com',
  // agrega el tuyo
];
if (in_array($ORIGIN, $allowedOrigins, true)) {
  header("Access-Control-Allow-Origin: $ORIGIN");
  header('Access-Control-Allow-Credentials: true');
} else {
  // Mismo origen: podrías dejarlo con * si NO usas cookies
  header('Access-Control-Allow-Origin: *');
}
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Responder preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}

// Convierte *warnings/notices* en JSON
set_error_handler(function($errno, $errstr, $errfile, $errline) {
  // loguea y responde limpio
  error_log("PHP ERROR [$errno] $errstr in $errfile:$errline");
  if (!headers_sent()) { http_response_code(500); }
  // Limpia cualquier salida y devuelve JSON de error
  while (ob_get_level() > 0) ob_end_clean();
  echo json_encode(['success'=>false,'error'=>'Error interno (ver log)']);
  exit;
});

// Evita cerrar la etiqueta PHP al final del archivo para que no se imprima nada extra.
