<?php
// parámetros de conexión
$servidor  = "127.0.0.1";
$basedatos = "mybox2";
$usuario   = "root";
$contra    = "";

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $conex = new mysqli($servidor, $usuario, $contra, $basedatos);
    $conex->set_charset("utf8mb4");
} catch (mysqli_sql_exception $e) {
    http_response_code(500);
    echo "<h3 style='color:red'>Error: No se puede conectar a MySQL.</h3>";
    echo "<strong>Detalle:</strong> " . htmlspecialchars($e->getMessage());
    exit;
}
?>
