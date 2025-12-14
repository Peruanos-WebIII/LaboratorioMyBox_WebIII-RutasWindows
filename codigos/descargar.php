<?php
session_start();

if (!isset($_SESSION["autenticado"]) || $_SESSION["autenticado"] != "SI") {
    header("Location: ../index.php");
    exit();
}

require_once('conexion.inc'); // estás dentro de /codigos

$archivoId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($archivoId <= 0) {
    header("Location: ../carpetas.php");
    exit();
}

// mi usuario_id
$sqlMiId = "SELECT id FROM usuarios WHERE usuario = ? LIMIT 1";
$stmtMi  = $conex->prepare($sqlMiId);
$stmtMi->bind_param("s", $_SESSION['usuario']);
$stmtMi->execute();
$resMi = $stmtMi->get_result();
$miRow = $resMi->fetch_assoc();
$stmtMi->close();

$miId = $miRow ? (int)$miRow['id'] : 0;
if ($miId <= 0) {
    header("Location: ../carpetas.php");
    exit();
}

// dueño o compartido
$sql = "
    SELECT a.nombre, a.mime, a.contenido, a.extension, a.tamano_bytes
    FROM archivos a
    WHERE a.id = ?
      AND (
            a.usuario_id = ?
            OR EXISTS (
                SELECT 1
                FROM compartidos c
                WHERE c.archivo_id = a.id
                  AND c.compartido_con_id = ?
                  AND c.tipo = 'archivo'
            )
      )
    LIMIT 1
";

$stmt = $conex->prepare($sql);
$stmt->bind_param("iii", $archivoId, $miId, $miId);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$stmt->close();

if (!$row) {
    header("Location: ../carpetas.php");
    exit();
}

$mime = $row['mime'] ?: 'application/octet-stream';
$nombre = $row['nombre'];
if (!empty($row['extension']) && stripos($nombre, '.') === false) {
    $nombre .= '.' . $row['extension'];
}

$size = (int)($row['tamano_bytes'] ?? 0);

if (ob_get_length()) {
    ob_end_clean();
}

header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . basename($nombre) . '"');
if ($size > 0) header('Content-Length: ' . $size);
header('Pragma: no-cache');
header('Expires: 0');
header('Cache-Control: no-cache, must-revalidate');

echo $row['contenido'];
exit();
