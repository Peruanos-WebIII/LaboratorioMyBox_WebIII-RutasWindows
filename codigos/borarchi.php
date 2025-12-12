<?php
// borarchi.php

// Parámetros
$archivo      = isset($_GET['archi'])   ? $_GET['archi']   : '';
$carpetaActual = isset($_GET['carpeta']) ? $_GET['carpeta'] : '';

//Inicio la sesión
session_start();

// Verifica autenticación
if (!isset($_SESSION["autenticado"]) || $_SESSION["autenticado"] != "SI") {
    header("Location: ../index.php");
    exit();
}

// Validar que venga el nombre de archivo
if ($archivo === '') {
    echo "<h3>No se especificó el archivo a eliminar.</h3>";
    exit();
}

// Ruta base (misma que en carpetas.php, agrearchi.php, abrArchi.php)
$rutaBase    = "C:" . DIRECTORY_SEPARATOR . "xampp" . DIRECTORY_SEPARATOR . "htdocs" . DIRECTORY_SEPARATOR . "LaboratorioMyBox_WebIII-RutasWindows" . DIRECTORY_SEPARATOR . "mybox";
$rutaUsuario = $rutaBase . DIRECTORY_SEPARATOR . $_SESSION["usuario"];

// Verificar que exista la carpeta del usuario
if (!is_dir($rutaUsuario)) {
    echo "<h3>Carpeta de usuario no encontrada.</h3>";
    exit();
}

// Determinar carpeta actual
$ruta = $rutaUsuario;
if (!empty($carpetaActual)) {
    $carpetaActualSistema = str_replace('/', DIRECTORY_SEPARATOR, $carpetaActual);
    $ruta = $rutaUsuario . DIRECTORY_SEPARATOR . $carpetaActualSistema;
}

// Normalizar nombre del archivo (evita rutas con ../)
$archivoSeguro = basename($archivo);
$rutaArchivo   = $ruta . DIRECTORY_SEPARATOR . $archivoSeguro;

// Verifica que la ruta sea válida y esté dentro de la carpeta del usuario
$rutaRealizada        = realpath($ruta);
$rutaArchivoRealizada = realpath($rutaArchivo);
$rutaRealUsuario      = realpath($rutaUsuario);

if ($rutaRealizada === false || 
    $rutaArchivoRealizada === false || 
    strpos($rutaRealizada, $rutaRealUsuario) !== 0 || 
    strpos($rutaArchivoRealizada, $rutaRealUsuario) !== 0) {
    
    header("Location: ../carpetas.php");
    exit();
}

// Intentar eliminar el fichero
echo "<h3>";
if (@unlink($rutaArchivo)) {
    echo "Se ha eliminado el fichero.";
} else {
    echo "NO se pudo eliminar el fichero.";
}
echo "</h3>";

//Retorna al punto de invocación
$urlRetorno = "../carpetas.php" . (!empty($carpetaActual) ? "?carpeta=" . urlencode($carpetaActual) : "");
echo "<script language='JavaScript'>";
echo "setTimeout(function() { location.href='" . $urlRetorno . "'; }, 2000);";
echo "</script>";
?>
