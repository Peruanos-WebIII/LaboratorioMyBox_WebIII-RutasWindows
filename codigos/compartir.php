<?php
// codigos/compartir.php

session_start();

// Verifica autenticación
if (!isset($_SESSION["autenticado"]) || $_SESSION["autenticado"] != "SI") {
    header("Location: ../index.php");
    exit();
}

// Parámetros de la URL
$archivo       = isset($_GET['arch'])    ? $_GET['arch']    : '';
$carpetaActual = isset($_GET['carpeta']) ? $_GET['carpeta'] : '';

// Validar que venga el nombre del archivo
if ($archivo === '') {
    header("Location: ../carpetas.php");
    exit();
}

// Incluir conexión a BD (misma que index.php / registrar.php)
require_once('../codigos/conexion.inc'); // OJO: conexion.inc está dentro de /codigos

// --- RUTAS ---
// Ruta base igual que en carpetas.php / descargar.php / agrearchi.php / abrArchi.php
$rutaBase    = "C:" . DIRECTORY_SEPARATOR . "xampp" . DIRECTORY_SEPARATOR . "htdocs" . DIRECTORY_SEPARATOR . "LaboratorioMyBox_WebIII-RutasWindows" . DIRECTORY_SEPARATOR . "mybox";
$rutaUsuario = $rutaBase . DIRECTORY_SEPARATOR . $_SESSION["usuario"];

// Asegurarse de que existe la carpeta del usuario
if (!is_dir($rutaUsuario)) {
    header("Location: ../carpetas.php");
    exit();
}

// Determinar carpeta actual en el sistema
$ruta = $rutaUsuario;
if (!empty($carpetaActual)) {
    $carpetaActualSistema = str_replace('/', DIRECTORY_SEPARATOR, $carpetaActual);
    $ruta = $rutaUsuario . DIRECTORY_SEPARATOR . $carpetaActualSistema;
}

// Normalizar nombre de archivo
$archivoSeguro = basename($archivo);
$rutaArchivo   = $ruta . DIRECTORY_SEPARATOR . $archivoSeguro;

// Verificar que ruta y archivo sean válidos y estén dentro de la carpeta del usuario
$rutaRealizada        = realpath($ruta);
$rutaArchivoRealizada = realpath($rutaArchivo);
$rutaRealUsuario      = realpath($rutaUsuario);

if ($rutaRealizada === false ||
    $rutaArchivoRealizada === false ||
    strpos($rutaRealizada, $rutaRealUsuario) !== 0 ||
    strpos($rutaArchivoRealizada, $rutaRealUsuario) !== 0 ||
    !is_file($rutaArchivoRealizada)) {

    header("Location: ../carpetas.php");
    exit();
}

// Ruta relativa que vamos a guardar en la BD
$ruta_relativa = !empty($carpetaActual)
    ? $carpetaActual . '/' . $archivoSeguro
    : $archivoSeguro;

// Si el formulario fue enviado
$mensajeError   = '';
$mensajeExito   = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario_compartido = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';
    $tipo_permiso       = isset($_POST['permiso']) ? trim($_POST['permiso']) : 'lectura';
    $propietario        = $_SESSION['usuario'];

    if ($usuario_compartido === '') {
        $mensajeError = "Debe seleccionar un usuario para compartir.";
    } elseif ($usuario_compartido === $propietario) {
        $mensajeError = "No tiene sentido compartir un archivo con usted mismo.";
    } else {
        // Insertar en BD (tabla compartidos)
        $sql = "INSERT INTO compartidos (propietario, ruta_relativa, usuario_compartido, tipo)
                VALUES (?, ?, ?, ?)";
        
        if ($stmt = mysqli_prepare($conex, $sql)) {
            mysqli_stmt_bind_param($stmt, "ssss", $propietario, $ruta_relativa, $usuario_compartido, $tipo_permiso);
            
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_close($stmt);
                // Redirige de vuelta a carpetas.php
                $urlRetorno = "../carpetas.php" . (!empty($carpetaActual) ? "?carpeta=" . urlencode($carpetaActual) : "");
                header("Location: " . $urlRetorno);
                exit();
            } else {
                $mensajeError = "Error al registrar el archivo compartido en la base de datos.";
            }
        } else {
            $mensajeError = "Error al preparar la consulta de compartido.";
        }
    }
}

// Obtener lista de usuarios posibles para compartir (excepto el actual)
$listaUsuarios = [];
$sqlUsuarios   = "SELECT usuario, nombre FROM usuarios WHERE usuario <> ? ORDER BY usuario";

if ($stmtUsuarios = mysqli_prepare($conex, $sqlUsuarios)) {
    mysqli_stmt_bind_param($stmtUsuarios, "s", $_SESSION['usuario']);
    mysqli_stmt_execute($stmtUsuarios);
    $resultado = mysqli_stmt_get_result($stmtUsuarios);
    while ($fila = mysqli_fetch_assoc($resultado)) {
        $listaUsuarios[] = $fila;
    }
    mysqli_stmt_close($stmtUsuarios);
}
?>
<!doctype html>
<html>
<head>
    <?php include_once('../partes/encabe.inc'); ?>
    <title>Compartir archivo</title>
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
                <strong>Compartir archivo</strong>
            </div>
            <div class="panel-body">
                <p><strong>Archivo:</strong> <?php echo htmlspecialchars($archivoSeguro); ?></p>
                <p><strong>Ruta relativa:</strong> <?php echo htmlspecialchars($ruta_relativa); ?></p>

                <?php if ($mensajeError !== ''): ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars($mensajeError); ?>
                    </div>
                <?php endif; ?>

                <?php if ($mensajeExito !== ''): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($mensajeExito); ?>
                    </div>
                <?php endif; ?>

                <?php if (count($listaUsuarios) === 0): ?>
                    <div class="alert alert-warning">
                        No hay otros usuarios registrados para compartir archivos.
                    </div>
                <?php else: ?>
                    <form action="<?php echo $_SERVER['PHP_SELF'] . '?arch=' . urlencode($archivo) . '&carpeta=' . urlencode($carpetaActual); ?>" method="post">
                        <fieldset>
                            <label for="usuario"><strong>Usuario con quien compartir</strong></label><br>
                            <select name="usuario" id="usuario" required>
                                <option value="">-- Seleccione un usuario --</option>
                                <?php foreach ($listaUsuarios as $u): ?>
                                    <option value="<?php echo htmlspecialchars($u['usuario']); ?>">
                                        <?php echo htmlspecialchars($u['usuario'] . ' - ' . $u['nombre']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <br><br>

                            <label><strong>Permiso</strong></label><br>
                            <label>
                                <input type="radio" name="permiso" value="lectura" checked>
                                Solo lectura
                            </label>
                            &nbsp;&nbsp;
                            <label>
                                <input type="radio" name="permiso" value="lectura_escritura">
                                Lectura / escritura
                            </label>
                            <br><br>

                            <input type="submit" value="Compartir" class="btn btn-primary" />
                            <a href="../carpetas.php<?php echo (!empty($carpetaActual) ? '?carpeta=' . urlencode($carpetaActual) : ''); ?>" class="btn btn-default">
                                Cancelar
                            </a>
                        </fieldset>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer class="row"></footer>
    <?php include_once('../partes/final.inc'); ?>
</body>
</html>
