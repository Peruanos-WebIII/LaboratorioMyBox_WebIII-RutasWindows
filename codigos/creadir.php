<?php
// codigos/creadir.php

//Inicio la sesión
session_start();

//Utiliza los datos de sesion, comprueba que el usuario este autenticado
if (!isset($_SESSION["autenticado"]) || $_SESSION["autenticado"] != "SI") {
    header("Location: ../index.php");
    exit(); //fin del script
}

// --- RUTAS ---
// Ruta base: dentro de htdocs, en la carpeta del laboratorio + mybox
$rutaBase    = "C:" . DIRECTORY_SEPARATOR . "xampp" . DIRECTORY_SEPARATOR . "htdocs" . DIRECTORY_SEPARATOR . "LaboratorioMyBox_WebIII-RutasWindows" . DIRECTORY_SEPARATOR . "mybox";
$rutaUsuario = $rutaBase . DIRECTORY_SEPARATOR . $_SESSION["usuario"];

// Si la carpeta del usuario no existe, crearla
if (!is_dir($rutaUsuario)) {
    mkdir($rutaUsuario, 0777, true);
}

// Obtener la carpeta actual desde GET (si no existe, usar la carpeta del usuario)
$carpetaActualURL     = isset($_GET['carpeta']) ? $_GET['carpeta'] : '';
$carpetaActualSistema = str_replace('/', DIRECTORY_SEPARATOR, $carpetaActualURL);
$rutaActual           = $rutaUsuario;

if (!empty($carpetaActualSistema)) {
    $rutaActual = $rutaUsuario . DIRECTORY_SEPARATOR . $carpetaActualSistema;
}

// Verifica que la ruta sea válida y esté dentro de la carpeta del usuario
$rutaRealActual  = realpath($rutaActual);
$rutaRealUsuario = realpath($rutaUsuario);

if ($rutaRealActual === false || strpos($rutaRealActual, $rutaRealUsuario) !== 0) {
    // Ruta sospechosa, regresamos a carpetas
    header("Location: ../carpetas.php");
    exit();
}

$Accion_Formulario = $_SERVER['PHP_SELF'];

$mensajeError = '';
$mensajeOk    = '';

// Procesar creación de carpeta
if (isset($_POST["OC_Aceptar"]) && $_POST["OC_Aceptar"] === "frmCarpeta") {
    $nombreCarpeta = trim($_POST["txtNombreCarpeta"]);

    // Validar nombre de carpeta
    if ($nombreCarpeta === '') {
        $mensajeError = "El nombre de la carpeta no puede estar vacío.";
    } elseif (strpbrk($nombreCarpeta, '<>:"|?*\\/') !== false) {
        // strpbrk devuelve FALSE si NO encuentra ninguno de esos caracteres
        $mensajeError = "El nombre de la carpeta contiene caracteres no permitidos.";
    } else {
        // Construir ruta completa de la nueva carpeta
        $rutaNuevaCarpeta = $rutaRealActual . DIRECTORY_SEPARATOR . $nombreCarpeta;

        // Evitar que ya exista
        if (is_dir($rutaNuevaCarpeta)) {
            $mensajeError = "Ya existe una carpeta con ese nombre.";
        } else {
            // Intentar crear la carpeta
            if (mkdir($rutaNuevaCarpeta, 0777, false)) {
                $mensajeOk = "Carpeta creada exitosamente.";
                // Redirigir de vuelta a carpetas.php en la misma carpeta donde estaba
                $urlDestino = "../carpetas.php";
                if (!empty($carpetaActualURL)) {
                    $urlDestino .= "?carpeta=" . urlencode($carpetaActualURL);
                }
                header("Location: " . $urlDestino);
                exit();
            } else {
                $mensajeError = "No se pudo crear la carpeta. Verifique los permisos.";
            }
        }
    }
}
?>
<!doctype html>
<html>
<head>
    <?php include_once('../partes/encabe.inc'); ?>
    <title>Crear Carpeta</title>
</head>
<body class="container cuerpo">
    <header class="row">
        <div class="row">
            <div class="col-lg-6 col-sm-6">
                <img src="../imagenes/encabe.png" alt="logo institucional" width="100%">
            </div>
        </div>
        <div class="row">
            <?php include_once('../partes/menu.inc'); ?>
        </div>
        <br />
    </header>

    <main class="row">
        <div class="panel panel-primary datos1">
            <div class="panel-heading">
                <strong>Crear Nueva Carpeta</strong>
            </div>
            <div class="panel-body">
                <?php if ($mensajeError !== ''): ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars($mensajeError); ?>
                    </div>
                <?php endif; ?>

                <?php if ($mensajeOk !== ''): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($mensajeOk); ?>
                    </div>
                <?php endif; ?>

                <form action="<?php echo $Accion_Formulario . (!empty($carpetaActualURL) ? '?carpeta=' . urlencode($carpetaActualURL) : ''); ?>" method="post" name="frmCarpeta">
                    <fieldset>
                        <label><strong>Nombre de la Carpeta</strong></label>
                        <input name="txtNombreCarpeta" type="text" id="txtNombreCarpeta" size="60" required />
                        <br><br>
                        <input type="submit" name="Submit" value="Crear Carpeta" class="btn btn-primary" />
                        <a href="../carpetas.php<?php echo !empty($carpetaActualURL) ? '?carpeta=' . urlencode($carpetaActualURL) : ''; ?>" class="btn btn-default">Cancelar</a>
                    </fieldset>
                    <input type="hidden" name="OC_Aceptar" value="frmCarpeta" />
                </form>
            </div>
        </div>
    </main>

    <footer class="row"></footer>
    <?php include_once('../partes/final.inc'); ?>
</body>
</html>
