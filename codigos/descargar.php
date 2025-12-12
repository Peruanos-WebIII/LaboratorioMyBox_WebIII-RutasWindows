<?php
// codigos/descargar.php
session_start();

// Verificar autenticación
if (!isset($_SESSION["autenticado"]) || $_SESSION["autenticado"] != "SI") {
    header("Location: ../index.php");
    exit();
}

// Parámetros
$archivo    = isset($_GET['arch'])    ? $_GET['arch']    : '';
$carpetaURL = isset($_GET['carpeta']) ? $_GET['carpeta'] : '';
$owner      = isset($_GET['owner']) && $_GET['owner'] !== ''
                ? $_GET['owner']
                : $_SESSION['usuario'];

if ($archivo === '') {
    header("Location: ../carpetas.php");
    exit();
}

// MISMA ruta base
$rutaBase         = "C:" . DIRECTORY_SEPARATOR . "xampp" . DIRECTORY_SEPARATOR . "htdocs"
                  . DIRECTORY_SEPARATOR . "LaboratorioMyBox_WebIII-RutasWindows"
                  . DIRECTORY_SEPARATOR . "mybox";
$rutaUsuarioOwner = $rutaBase . DIRECTORY_SEPARATOR . $owner;

// Directorio del propietario debe existir
if (!is_dir($rutaUsuarioOwner)) {
    header("Location: ../carpetas.php");
    exit();
}

// Carpeta donde está el archivo
$carpetaSistema = '';
if (!empty($carpetaURL)) {
    $carpetaSistema = str_replace('/', DIRECTORY_SEPARATOR, $carpetaURL);
}

$ruta = $rutaUsuarioOwner;
if ($carpetaSistema !== '') {
    $ruta .= DIRECTORY_SEPARATOR . $carpetaSistema;
}

$rutaArchivo = $ruta . DIRECTORY_SEPARATOR . $archivo;

// Seguridad de rutas
$rutaRealUsuario = realpath($rutaUsuarioOwner);
$rutaRealArchivo = realpath($rutaArchivo);

if ($rutaRealUsuario === false || $rutaRealArchivo === false ||
    strpos($rutaRealArchivo, $rutaRealUsuario) !== 0) {
    header("Location: ../carpetas.php");
    exit();
}

// Si el archivo es compartido, validar en la BD
if ($owner !== $_SESSION['usuario']) {
    require_once('conexion.inc'); // estamos dentro de /codigos

    $rutaRelativa = $carpetaURL !== ''
        ? $carpetaURL . '/' . $archivo
        : $archivo;

    $sql  = "SELECT 1 FROM compartidos 
             WHERE propietario = ? 
               AND usuario_compartido = ?
               AND ruta_relativa = ?
             LIMIT 1";

    $stmt = mysqli_prepare($conex, $sql);
    mysqli_stmt_bind_param($stmt, "sss", $owner, $_SESSION['usuario'], $rutaRelativa);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);

    if (mysqli_stmt_num_rows($stmt) === 0) {
        // No autorizado
        header("Location: ../carpetas.php");
        exit();
    }
    mysqli_stmt_close($stmt);
}

// Verificar que el archivo exista
if (!file_exists($rutaArchivo) || !is_file($rutaArchivo)) {
    header("Location: ../carpetas.php");
    exit();
}

$nombreArchivo = basename($rutaArchivo);
$tamanoArchivo = filesize($rutaArchivo);
$tipoMime      = mime_content_type($rutaArchivo);

if (!$tipoMime) {
    $tipoMime = 'application/octet-stream';
}

// Limpiar buffer
if (ob_get_length()) {
    ob_end_clean();
}

// Headers de descarga
header('Content-Type: ' . $tipoMime);
header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
header('Content-Length: ' . $tamanoArchivo);
header('Pragma: no-cache');
header('Expires: 0');
header('Cache-Control: no-cache, must-revalidate');

readfile($rutaArchivo);
exit();
?>
