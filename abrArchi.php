<?php
session_start();

if (!isset($_SESSION["autenticado"]) || $_SESSION["autenticado"] != "SI") {
    header("Location: index.php");
    exit();
}

require_once('codigos/conexion.inc'); // $conex

$archivoId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($archivoId <= 0) {
    echo "Archivo no especificado.";
    exit();
}

// Obtener mi usuario_id 
$sqlMiId = "SELECT id FROM usuarios WHERE usuario = ? LIMIT 1";
$stmtMi  = $conex->prepare($sqlMiId);
$stmtMi->bind_param("s", $_SESSION['usuario']);
$stmtMi->execute();
$resMi = $stmtMi->get_result();
$miRow = $resMi->fetch_assoc();
$stmtMi->close();

$miId = $miRow ? (int)$miRow['id'] : 0;
if ($miId <= 0) {
    echo "Usuario inválido.";
    exit();
}

// Validar permiso y traer contenido 
$sql = "
    SELECT a.nombre, a.mime, a.contenido, a.extension, OCTET_LENGTH(a.contenido) AS tam
    FROM archivos a
    WHERE a.id = ?
      AND (
            a.usuario_id = ?
            OR EXISTS (
                SELECT 1
                FROM compartidos c
                WHERE c.archivo_id = a.id
                  AND c.compartido_con_id = ?
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
    echo "No tiene permiso para acceder a este archivo.";
    exit();
}

// Preparar nombre
$mime = !empty($row['mime']) ? $row['mime'] : 'application/octet-stream';

$nombre = $row['nombre'];
if (!empty($row['extension']) && stripos($nombre, '.') === false) {
    $nombre .= '.' . $row['extension'];
}

$tam = isset($row['tam']) ? (int)$row['tam'] : 0;

// Limpiar buffers por si algo imprimió antes
while (ob_get_level()) {
    ob_end_clean();
}

$esPDF    = ($mime === 'application/pdf');
$esImagen = (stripos($mime, 'image/') === 0);

// PDF e imágenes = inline
// Otros = descarga
$disposition = ($esPDF || $esImagen) ? 'inline' : 'attachment';

// Headers
header('Content-Type: ' . $mime);
header('Content-Disposition: ' . $disposition . '; filename="' . basename($nombre) . '"');
if ($tam > 0) {
    header('Content-Length: ' . $tam);
}
header('Pragma: no-cache');
header('Expires: 0');
header('Cache-Control: no-cache, must-revalidate');

// Enviar contenido (BLOB)
echo $row['contenido'];
exit();
?>
