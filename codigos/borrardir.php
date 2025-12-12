<?php
// Inicio la sesión
session_start();

// Verifica que el usuario esté autenticado
if (!isset($_SESSION["autenticado"]) || $_SESSION["autenticado"] != "SI") {
    header("Location: ../index.php");
    exit(); // fin del script
}

// Obtener parámetros
$carpetaABorrar  = isset($_GET['dir'])     ? $_GET['dir']     : '';
$carpetaActual   = isset($_GET['carpeta']) ? $_GET['carpeta'] : '';

if ($carpetaABorrar === '') {
    header("Location: ../carpetas.php");
    exit();
}

// MISMA ruta base que en carpetas.php / creadir.php / agrearchi.php / abrArchi.php
$rutaBase    = "C:" . DIRECTORY_SEPARATOR . "xampp" . DIRECTORY_SEPARATOR . "htdocs"
             . DIRECTORY_SEPARATOR . "LaboratorioMyBox_WebIII-RutasWindows"
             . DIRECTORY_SEPARATOR . "mybox";
$rutaUsuario = $rutaBase . DIRECTORY_SEPARATOR . $_SESSION["usuario"];

// Construir la ruta actual (donde estoy parado en carpetas.php)
$rutaActual = $rutaUsuario;
if (!empty($carpetaActual)) {
    // la carpetaActual viene estilo URL (con /), la pasamos a separador del sistema
    $carpetaActualSistema = str_replace('/', DIRECTORY_SEPARATOR, $carpetaActual);
    $rutaActual           = $rutaUsuario . DIRECTORY_SEPARATOR . $carpetaActualSistema;
}

// Ruta de la carpeta a borrar
$rutaBorrar = $rutaActual . DIRECTORY_SEPARATOR . $carpetaABorrar;

// Verificamos que todo esté dentro de la carpeta del usuario (seguridad)
$rutaRealUsuario = realpath($rutaUsuario);
$rutaRealActual  = realpath($rutaActual);
$rutaRealBorrar  = realpath($rutaBorrar);

if ($rutaRealUsuario === false || $rutaRealActual === false || $rutaRealBorrar === false ||
    strpos($rutaRealActual, $rutaRealUsuario) !== 0 ||
    strpos($rutaRealBorrar,  $rutaRealUsuario) !== 0) {
    header("Location: ../carpetas.php");
    exit();
}

// Función recursiva para borrar carpeta con su contenido
function borrarCarpetaRecursiva($rutaDir)
{
    if (!is_dir($rutaDir)) {
        return false;
    }

    $items = scandir($rutaDir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') {
            continue;
        }

        $rutaItem = $rutaDir . DIRECTORY_SEPARATOR . $item;

        if (is_dir($rutaItem)) {
            borrarCarpetaRecursiva($rutaItem);
        } else {
            @unlink($rutaItem);
        }
    }

    return @rmdir($rutaDir);
}

// Intentar borrar la carpeta
if (borrarCarpetaRecursiva($rutaBorrar)) {
    $mensaje = "Carpeta eliminada exitosamente.";
    $tipo    = "success";
} else {
    $mensaje = "No se pudo eliminar la carpeta. Verifique los permisos.";
    $tipo    = "danger";
}

// Armar URL de retorno
$urlRetorno = "../carpetas.php";
if (!empty($carpetaActual)) {
    $urlRetorno .= "?carpeta=" . urlencode($carpetaActual);
}

// Mostrar mensaje y redirigir a carpetas.php
?>
<!doctype html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Eliminar Carpeta</title>
    <link rel="stylesheet" href="../estilos/bootstrap.css">
    <link rel="stylesheet" href="../estilos/formatos.css">
</head>
<body class="container cuerpo">
    <div class="alert alert-<?php echo htmlspecialchars($tipo); ?>" style="margin-top:20px;">
        <h3><?php echo htmlspecialchars($mensaje); ?></h3>
    </div>
    <script>
        setTimeout(function () {
            location.href = '<?php echo $urlRetorno; ?>';
        }, 2000);
    </script>
</body>
</html>
