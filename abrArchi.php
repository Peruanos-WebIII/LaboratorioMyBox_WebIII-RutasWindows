<?php
// abrArchi.php
session_start();

// Verificar autenticación
if (!isset($_SESSION["autenticado"]) || $_SESSION["autenticado"] != "SI") {
    header("Location: index.php");
    exit();
}

// Parámetros
$archivo     = isset($_GET['arch'])    ? $_GET['arch']    : '';
$carpetaURL  = isset($_GET['carpeta']) ? $_GET['carpeta'] : '';
$owner       = isset($_GET['owner']) && $_GET['owner'] !== ''
                ? $_GET['owner']                  // propietario explícito (archivo compartido)
                : $_SESSION['usuario'];           // por defecto, yo mismo

if ($archivo === '') {
    echo "Archivo no especificado.";
    exit();
}

// MISMA ruta base que en carpetas.php / creadir.php / agrearchi.php
$rutaBase         = "C:" . DIRECTORY_SEPARATOR . "xampp" . DIRECTORY_SEPARATOR . "htdocs"
                  . DIRECTORY_SEPARATOR . "LaboratorioMyBox_WebIII-RutasWindows"
                  . DIRECTORY_SEPARATOR . "mybox";
$rutaUsuarioOwner = $rutaBase . DIRECTORY_SEPARATOR . $owner;

// Directorio del propietario debe existir
if (!is_dir($rutaUsuarioOwner)) {
    echo "No existe el directorio del propietario.";
    exit();
}

// Carpeta (subdirectorio) donde está el archivo
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
    echo "No tiene permiso para acceder a este archivo.";
    exit();
}

// Si el archivo es compartido (owner != usuario actual), validar en la BD
if ($owner !== $_SESSION['usuario']) {
    require_once('codigos/conexion.inc');

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
        echo "No tiene permiso para acceder a este archivo compartido.";
        exit();
    }
    mysqli_stmt_close($stmt);
}

// Mostrar el archivo
$mime = mime_content_type($rutaArchivo);
if (!$mime) {
    $mime = 'application/octet-stream';
}

// Si es PDF, se abre embebido; si no, lo forzamos como descarga “inline”
if ($mime === 'application/pdf') {
    header('Content-Type: ' . $mime);
    readfile($rutaArchivo);
} else {
    header('Content-Type: ' . $mime);
    header('Content-Disposition: inline; filename="' . basename($rutaArchivo) . '"');
    header('Content-Length: ' . filesize($rutaArchivo));
    readfile($rutaArchivo);
}

exit();
