<?php
// Inicio la sesión
session_start();

// Verifica autenticación
if (!isset($_SESSION["autenticado"]) || $_SESSION["autenticado"] != "SI") {
    header("Location: index.php");
    exit();
}

// Conexión a BD (para compartidos)
require_once('codigos/conexion.inc'); // aquí se define $conex

// Función para escoger icono según extensión
function iconoArchivo($nombreArchivo) {
    $ext = strtolower(pathinfo($nombreArchivo, PATHINFO_EXTENSION));

    switch ($ext) {
        case 'doc':
        case 'docx':
            return '<span class="glyphicon glyphicon-file" style="color:#2b579a;" title="Documento Word"></span>';
        case 'pdf':
            return '<span class="glyphicon glyphicon-book" style="color:#d9534f;" title="Archivo PDF"></span>';
        case 'jpg':
        case 'jpeg':
        case 'png':
        case 'gif':
            return '<span class="glyphicon glyphicon-picture" style="color:#5cb85c;" title="Imagen"></span>';
        case 'xls':
        case 'xlsx':
            return '<span class="glyphicon glyphicon-list-alt" style="color:#5cb85c;" title="Hoja de cálculo"></span>';
        case 'txt':
            return '<span class="glyphicon glyphicon-align-left" title="Archivo de texto"></span>';
        default:
            return '<span class="glyphicon glyphicon-file" title="Archivo"></span>';
    }
}

// Rutas base (MISMA ruta que en creadir.php, agrearchi.php, abrArchi.php, etc.)
$rutaBase = "C:" . DIRECTORY_SEPARATOR . "xampp" . DIRECTORY_SEPARATOR . "htdocs" .
            DIRECTORY_SEPARATOR . "LaboratorioMyBox_WebIII-RutasWindows" . DIRECTORY_SEPARATOR . "mybox";
$rutaUsuario = $rutaBase . DIRECTORY_SEPARATOR . $_SESSION["usuario"];

// Asegurar que exista la carpeta del usuario
if (!is_dir($rutaUsuario)) {
    mkdir($rutaUsuario, 0777, true);
}

// Carpeta actual (si no, raíz del usuario)
$carpetaActualURL     = isset($_GET['carpeta']) ? $_GET['carpeta'] : '';
$carpetaActualSistema = str_replace('/', DIRECTORY_SEPARATOR, $carpetaActualURL);
$ruta                 = $rutaUsuario;

if (!empty($carpetaActualSistema)) {
    $ruta = $rutaUsuario . DIRECTORY_SEPARATOR . $carpetaActualSistema;
}

// Verificar que la ruta esté dentro del usuario
$rutaRealizada   = realpath($ruta);
$rutaRealUsuario = realpath($rutaUsuario);

if ($rutaRealizada === false || strpos($rutaRealizada, $rutaRealUsuario) !== 0) {
    $ruta = $rutaUsuario;
    $carpetaActualURL = '';
    $carpetaActualSistema = '';
}

$datos = explode(DIRECTORY_SEPARATOR, $rutaBase);
?>
<!doctype html>
<html>
<head>
    <?php include_once('partes/encabe.inc'); ?>
    <title>Ingreso al Sitio</title>
</head>
<body class="container cuerpo">
<header class="row">
    <div class="row">
        <div class="col-lg-6 col-sm-6">
            <img src="imagenes/encabe.png" alt="logo institucional" width="100%">
        </div>
    </div>
    <div class="row">
        <?php include_once('partes/menu.inc'); ?>
    </div>
    <br />
</header>

<main class="row">
    <div class="panel panel-primary">
        <div class="panel-heading">
            <strong>Mi Cajón de Archivos</strong>
            <?php
            if (!empty($carpetaActualURL)) {
                echo " > " . htmlspecialchars($carpetaActualURL);
            }
            ?>
        </div>

        <div class="panel-body">

            <?php
            // ---------------- FUNCIÓN ICONO ----------------
            if (!function_exists('iconoArchivo')) {
                function iconoArchivo($nombre) {
                    $ext = strtolower(pathinfo($nombre, PATHINFO_EXTENSION));

                    if (in_array($ext, ['jpg','jpeg','png','gif','bmp'])) {
                        return '<span class="glyphicon glyphicon-picture"></span>';
                    }
                    if ($ext === 'pdf') {
                        return '<span class="glyphicon glyphicon-book"></span>';
                    }
                    if (in_array($ext, ['doc','docx'])) {
                        return '<span class="glyphicon glyphicon-file"></span>';
                    }
                    if (in_array($ext, ['xls','xlsx'])) {
                        return '<span class="glyphicon glyphicon-list-alt"></span>';
                    }
                    if (in_array($ext, ['mp4','avi','mov'])) {
                        return '<span class="glyphicon glyphicon-film"></span>';
                    }

                    return '<span class="glyphicon glyphicon-file"></span>';
                }
            }

            // ---------------- BOTONES ----------------
            $params = !empty($carpetaActualURL) ? '?carpeta=' . urlencode($carpetaActualURL) : '';

            echo '<div class="btn-group" role="group">';
            echo '<a href="./codigos/creadir.php' . $params . '" class="btn btn-success">Crear Carpeta</a>';
            echo '&nbsp;&nbsp;';
            echo '<a href="agrearchi.php' . $params . '" class="btn btn-primary">Agregar Archivo</a>';
            echo '</div>';

            if (!empty($carpetaActualURL)) {
                echo '<br><br>';
                echo '<a href="./carpetas.php" class="btn btn-info">Volver Atrás</a>';
                echo '<br><br>';
            }

            // ---------------- TABLA: ARCHIVOS PROPIOS ----------------
            echo '<h4>Mis archivos</h4>';
            echo '<table class="table table-striped">';
            echo '<tr>';
            echo '<th>Icono</th>';
            echo '<th>Nombre</th>';
            echo '<th>Tipo</th>';
            echo '<th>Tamaño (MB)</th>';
            echo '<th>Último acceso</th>';
            echo '<th>Acciones</th>';
            echo '</tr>';

            if (!is_dir($ruta)) {
                echo '<tr><td colspan="6"><em>La carpeta no existe o ha sido eliminada.</em></td></tr>';
            } else {

                $directorio = opendir($ruta);
                $archivos = [];
                $carpetas = [];

                while ($elem = readdir($directorio)) {
                    if ($elem !== '.' && $elem !== '..') {
                        if (is_dir($ruta . DIRECTORY_SEPARATOR . $elem)) {
                            $carpetas[] = $elem;
                        } else {
                            $archivos[] = $elem;
                        }
                    }
                }
                closedir($directorio);

                // -------- Carpetas --------
                sort($carpetas);
                foreach ($carpetas as $carpeta) {

                    $rutaCarpeta = $ruta . DIRECTORY_SEPARATOR . $carpeta;
                    $nuevaCarpetaURL = empty($carpetaActualURL)
                        ? $carpeta
                        : $carpetaActualURL . '/' . $carpeta;

                    echo '<tr>';
                    echo '<td><span class="glyphicon glyphicon-folder-open" style="color:#337ab7;"></span></td>';
                    echo '<td><strong><a href="carpetas.php?carpeta=' . urlencode($nuevaCarpetaURL) . '">' . htmlspecialchars($carpeta) . '</a></strong></td>';
                    echo '<td>Carpeta</td>';
                    echo '<td>-</td>';
                    echo '<td>' . date("d/m/y H:i:s", fileatime($rutaCarpeta)) . '</td>';
                    echo '<td><a href="./codigos/borrardir.php?dir=' . urlencode($carpeta) .
                        '&carpeta=' . urlencode($carpetaActualURL) .
                        '" class="btn btn-danger btn-sm" onclick="return confirm(\'¿Está seguro de que desea eliminar esta carpeta y su contenido?\')">Borrar</a></td>';
                    echo '</tr>';
                }

                // -------- Archivos --------
                sort($archivos);
                foreach ($archivos as $archivo) {

                    $rutaArchivo   = $ruta . DIRECTORY_SEPARATOR . $archivo;
                    $sizeBytes     = filesize($rutaArchivo);
                    $sizeMB        = $sizeBytes / (1024 * 1024);
                    $sizeMBFormat  = number_format($sizeMB, 2);
                    $icono         = iconoArchivo($archivo);

                    echo '<tr>';
                    echo '<td>' . $icono . '</td>';

                    echo '<td><a href="abrArchi.php?arch=' . urlencode($archivo) .
                                        '&carpeta=' . urlencode($carpetaActualURL) .
                                        '&owner=' . urlencode($_SESSION["usuario"]) .
                                        '" target="_blank">' .
                                        htmlspecialchars($archivo) .
                        '</a></td>';

                    echo '<td>Archivo</td>';
                    echo '<td>' . $sizeMBFormat . ' MB</td>';
                    echo '<td>' . date("d/m/y H:i:s", fileatime($rutaArchivo)) . '</td>';

                    echo '<td>';
                    echo '<a href="./codigos/descargar.php?arch=' . urlencode($archivo) .
                                        '&carpeta=' . urlencode($carpetaActualURL) .
                                        '" class="btn btn-success btn-sm">Descargar</a>';
                    echo '&nbsp;';
                    echo '<a href="./codigos/compartir.php?arch=' . urlencode($archivo) .
                                        '&carpeta=' . urlencode($carpetaActualURL) .
                                        '" class="btn btn-info btn-sm">Compartir</a>';
                    echo '&nbsp;';
                    echo '<a href="./codigos/borarchi.php?archi=' . urlencode($archivo) .
                                        '&carpeta=' . urlencode($carpetaActualURL) .
                                        '" class="btn btn-danger btn-sm" onclick="return confirm(\'¿Está seguro?\')">Borrar</a>';
                    echo '</td>';

                    echo '</tr>';
                }

                if (count($carpetas) + count($archivos) === 0) {
                    echo '<tr><td colspan="6"><em>La carpeta se encuentra vacía</em></td></tr>';
                }
            }

            echo '</table>';

            // ---------------- TABLA COMPARTIDOS ----------------
            $usuarioActual = $_SESSION['usuario'];

            $sqlCompartidos = "SELECT propietario, ruta_relativa, tipo
                               FROM compartidos
                               WHERE usuario_compartido = ?";

            $stmt = $conex->prepare($sqlCompartidos);
            $stmt->bind_param("s", $usuarioActual);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result && $result->num_rows > 0) {

                echo '<hr>';
                echo '<h4>Archivos compartidos conmigo</h4>';

                echo '<table class="table table-striped">';
                echo '<tr>';
                echo '<th>Icono</th>';
                echo '<th>Nombre</th>';
                echo '<th>Propietario</th>';
                echo '<th>Permiso</th>';
                echo '<th>Acciones</th>';
                echo '</tr>';

                while ($row = $result->fetch_assoc()) {

                    $propietario  = $row['propietario'];
                    $rutaRelativa = $row['ruta_relativa'];
                    $tipoPermiso  = $row['tipo'];

                    if (strpos($rutaRelativa, '/') !== false) {
                        $carpetaRel = dirname($rutaRelativa);
                        $archivoRel = basename($rutaRelativa);
                    } else {
                        $carpetaRel = '';
                        $archivoRel = $rutaRelativa;
                    }

                    $icono = iconoArchivo($archivoRel);

                    echo '<tr>';
                    echo '<td>' . $icono . '</td>';
                    echo '<td>' . htmlspecialchars($archivoRel) . '</td>';
                    echo '<td>' . htmlspecialchars($propietario) . '</td>';
                    echo '<td>' . htmlspecialchars(ucfirst($tipoPermiso)) . '</td>';

                    echo '<td>';
                    echo '<a href="abrArchi.php?arch=' . urlencode($archivoRel) .
                        '&carpeta=' . urlencode($carpetaRel) .
                        '&owner=' . urlencode($propietario) .
                        '" target="_blank" class="btn btn-info btn-sm">Abrir</a>';

                    echo '&nbsp;';

                    echo '<a href="./codigos/descargar.php?arch=' . urlencode($archivoRel) .
                        '&carpeta=' . urlencode($carpetaRel) .
                        '&owner=' . urlencode($propietario) .
                        '" class="btn btn-success btn-sm">Descargar</a>';
                    echo '</td>';

                    echo '</tr>';
                }

                echo '</table>';
            }

            $stmt->close();
            ?>

        </div>
    </div>
</main>


<footer class="row"></footer>
<?php include_once('partes/final.inc'); ?>
</body>
</html>
