<?php
// auth.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Inicia sesión en TODOS los endpoints que lo incluyan
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

/* === Utilidades de sesión/roles === */
function isLoggedIn(): bool {
  return !empty($_SESSION['autenticado']);
}
function isAdmin(): bool {
  // Marcadores compatibles con tu login
  if (!empty($_SESSION['admin'])) return true;
  return (($_SESSION['rol'] ?? '') === 'admin');
}
function userSedeId(): ?int {
  return isset($_SESSION['sede_id']) ? (int)$_SESSION['sede_id'] : null;
}
function requireLoginOrExit(): void {
  if (!isLoggedIn()) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success'=>false, 'error'=>'No autenticado']);
    exit;
  }
}

/* === (Opcional) Modo debug rápido ===
   Llama a cualquier endpoint con ?debug=1 para ver tu sesión. */
function maybeDebugAndExit(): void {
  if (isset($_GET['debug'])) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
      'session' => $_SESSION,
      'isAdmin' => isAdmin(),
      'sede_id' => userSedeId()
    ], JSON_UNESCAPED_UNICODE);
    exit;
  }
}
