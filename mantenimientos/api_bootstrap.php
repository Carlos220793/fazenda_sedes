<?php

ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/php_errors.log'); 

header('Content-Type: application/json; charset=utf-8');


if (ob_get_level() === 0) { ob_start(); }


$ORIGIN = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';

$allowedOrigins = [
    'http://10.110.6.148',
  'http://localhost:5173',
  'http://localhost:3000',
  'https://tu-frontend.com',
  
];
if (in_array($ORIGIN, $allowedOrigins, true)) {
  header("Access-Control-Allow-Origin: $ORIGIN");
  header('Access-Control-Allow-Credentials: true');
} else {
  
  header('Access-Control-Allow-Origin: *');
}
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');


if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
  http_response_code(204);
  exit;
}


set_error_handler(function($errno, $errstr, $errfile, $errline) {
  
  error_log("PHP ERROR [$errno] $errstr in $errfile:$errline");
  if (!headers_sent()) { http_response_code(500); }
  
  while (ob_get_level() > 0) ob_end_clean();
  echo json_encode(['success'=>false,'error'=>'Error interno (ver log)']);
  exit;
});


