<?php
ini_set('display_errors', 0);
session_start();


$_SESSION = [];


if (ini_get("session.use_cookies")) {
  $p = session_get_cookie_params();
  setcookie(session_name(), '', time() - 42000, $p["path"], $p["domain"], $p["secure"], $p["httponly"]);
}


session_destroy();
session_write_close();


$isAjax = (
  (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
  || (isset($_SERVER['HTTP_ACCEPT']) && stripos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false)
);


$loginUrl = '/mantenimientos/index.html?logout=1';

if ($isAjax) {
  header('Content-Type: application/json; charset=utf-8');
  header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
  header('Pragma: no-cache');
  echo json_encode(['success' => true], JSON_UNESCAPED_UNICODE);
} else {
  header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
  header("Pragma: no-cache");
  header("Location: $loginUrl", true, 302);
}
exit;
