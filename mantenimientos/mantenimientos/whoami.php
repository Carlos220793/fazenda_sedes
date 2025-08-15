<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
echo json_encode($_SESSION, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
