<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Credenciales para XAMPP
$user = "root";
$pass = "";  // vacío por defecto

$mysqli = @new mysqli('localhost', $user, $pass, 'mybox');

if ($mysqli->connect_errno) {
  http_response_code(500);
  echo "DB_CONNECT_ERROR: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
  exit;
}

echo "DB_OK";
