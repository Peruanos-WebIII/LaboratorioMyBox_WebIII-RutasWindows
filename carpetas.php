<?php
session_start();

if (!isset($_SESSION["autenticado"]) || $_SESSION["autenticado"] != "SI") {
    header("Location: index.php");
    exit();
}

require_once('codigos/conexion.inc');

// Helpers 
function limpiarRuta(string $ruta): string {
    $ruta = trim($ruta);
    $ruta = str_replace('\\', '/', $ruta);
    $ruta = preg_replace('#/+#', '/', $ruta);
    $ruta = trim($ruta, '/');
    return $ruta;
}

function iconoArchivo(string $nombreArchivo): string {
    $ext = strtolower(pathinfo($nombreArchivo, PATHINFO_EXTENSION));

    if (in_array($ext, ['jpg','jpeg','png','gif','bmp','webp'])) return '🖼️';
    if ($ext === 'pdf') return '📕';
    if (in_array($ext, ['doc','docx'])) return '📝';
    if (in_array($ext, ['xls','xlsx','csv'])) return '📊';
    if (in_array($ext, ['txt','log'])) return '📄';
    if (in_array($ext, ['zip','rar','7z'])) return '🗜️';
    if (in_array($ext, ['mp4','avi','mov','mkv'])) return '🎞️';

    return '📄';
}

function getUsuarioId(mysqli $conex, string $usuario): int {
    $sql = "SELECT id FROM usuarios WHERE usuario = ? LIMIT 1";
    $st = $conex->prepare($sql);
    $st->bind_param("s", $usuario);
    $st->execute();
    $res = $st->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $st->close();
    return $row ? (int)$row['id'] : 0;
}

/**
 * Devuelve:
 *  - null si es raíz
 *  - int id del directorio si existe
 *  - null si NO existe (y no crea nada)
 */
function obtenerDirectorioIdPorRuta(mysqli $conex, int $usuarioId, string $rutaRelativa): ?int {
    $rutaRelativa = limpiarRuta($rutaRelativa);
    if ($rutaRelativa === '') return null;

    $partes = explode('/', $rutaRelativa);
    $parentId = null;

    foreach ($partes as $nombre) {
        $nombre = trim($nombre);
        if ($nombre === '') continue;

        if ($parentId === null) {
            $sql = "SELECT id FROM directorios
                    WHERE usuario_id = ? AND parent_id IS NULL AND nombre = ?
                    LIMIT 1";
            $st = $conex->prepare($sql);
            $st->bind_param("is", $usuarioId, $nombre);
        } else {
            $sql = "SELECT id FROM directorios
                    WHERE usuario_id = ? AND parent_id = ? AND nombre = ?
                    LIMIT 1";
            $st = $conex->prepare($sql);
            $st->bind_param("iis", $usuarioId, $parentId, $nombre);
        }

        $st->execute();
        $res = $st->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $st->close();

        if (!$row) {
            return null;
        }

        $parentId = (int)$row['id'];
    }

    return $parentId;
}

function rutaPadre(string $ruta): string {
    $ruta = limpiarRuta($ruta);
    if ($ruta === '') return '';
    $partes = explode('/', $ruta);
    array_pop($partes);
    return implode('/', $partes);
}

// Contexto
$usuarioActual = $_SESSION["usuario"];
$usuarioIdActual = getUsuarioId($conex, $usuarioActual);

if ($usuarioIdActual <= 0) {
    echo "Usuario inválido en BD.";
    exit();
}

$carpetaActualURL = isset($_GET['carpeta']) ? limpiarRuta($_GET['carpeta']) : '';
$directorioIdActual = obtenerDirectorioIdPorRuta($conex, $usuarioIdActual, $carpetaActualURL);

if ($carpetaActualURL !== '' && $directorioIdActual === null) {
    header("Location: carpetas.php");
    exit();
}

// -------------------- Consultas --------------------
// Subcarpetas
if ($directorioIdActual === null) {
    $sqlCarpetas = "SELECT id, nombre, creado_en
                    FROM directorios
                    WHERE usuario_id = ? AND parent_id IS NULL
                    ORDER BY nombre ASC";
    $stmtC = $conex->prepare($sqlCarpetas);
    $stmtC->bind_param("i", $usuarioIdActual);
} else {
    $sqlCarpetas = "SELECT id, nombre, creado_en
                    FROM directorios
                    WHERE usuario_id = ? AND parent_id = ?
                    ORDER BY nombre ASC";
    $stmtC = $conex->prepare($sqlCarpetas);
    $stmtC->bind_param("ii", $usuarioIdActual, $directorioIdActual);
}
$stmtC->execute();
$resCarpetas = $stmtC->get_result();

// Archivos
if ($directorioIdActual === null) {
    $sqlArchivos = "SELECT id, nombre, extension, mime, tamano_bytes, creado_en
                    FROM archivos
                    WHERE usuario_id = ? AND directorio_id IS NULL
                    ORDER BY nombre ASC";
    $stmtA = $conex->prepare($sqlArchivos);
    $stmtA->bind_param("i", $usuarioIdActual);
} else {
    $sqlArchivos = "SELECT id, nombre, extension, mime, tamano_bytes, creado_en
                    FROM archivos
                    WHERE usuario_id = ? AND directorio_id = ?
                    ORDER BY nombre ASC";
    $stmtA = $conex->prepare($sqlArchivos);
    $stmtA->bind_param("ii", $usuarioIdActual, $directorioIdActual);
}
$stmtA->execute();
$resArchivos = $stmtA->get_result();

?>
<!doctype html>
<html>
<head>
    <?php include_once('partes/encabe.inc'); ?>
    <title>MiBox</title>
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
            <?php if (!empty($carpetaActualURL)) echo " > " . htmlspecialchars($carpetaActualURL); ?>
        </div>

        <div class="panel-body">
            <?php
            // Botones
            $params = !empty($carpetaActualURL) ? '?carpeta=' . urlencode($carpetaActualURL) : '';
            echo '<div class="btn-group" role="group">';
            echo '<a href="./codigos/creadir.php' . $params . '" class="btn btn-success">Crear Carpeta</a>';
            echo '&nbsp;&nbsp;';
            echo '<a href="agrearchi.php' . $params . '" class="btn btn-primary">Agregar Archivo</a>';
            echo '</div>';

            if (!empty($carpetaActualURL)) {
                $padre = rutaPadre($carpetaActualURL);
                echo '<br><br>';
                echo '<a href="./carpetas.php' . ($padre !== '' ? '?carpeta=' . urlencode($padre) : '') . '" class="btn btn-info">Volver Atrás</a>';
                echo '<br><br>';
            }

            echo '<h4>Mis archivos</h4>';
            echo '<table class="table table-striped">';
            echo '<tr>';
            echo '<th>Icono</th>';
            echo '<th>Nombre</th>';
            echo '<th>Tipo</th>';
            echo '<th>Tamaño (MB)</th>';
            echo '<th>Fecha</th>';
            echo '<th>Acciones</th>';
            echo '</tr>';

            $hayAlgo = false;

            // Carpetas
            if ($resCarpetas && $resCarpetas->num_rows > 0) {
                $hayAlgo = true;
                while ($row = $resCarpetas->fetch_assoc()) {
                    $nombreCarpeta = $row['nombre'];
                    $nuevaCarpetaURL = $carpetaActualURL === '' ? $nombreCarpeta : ($carpetaActualURL . '/' . $nombreCarpeta);

                    echo '<tr>';
                    echo '<td>📁</td>';
                    echo '<td><strong><a href="carpetas.php?carpeta=' . urlencode($nuevaCarpetaURL) . '">' . htmlspecialchars($nombreCarpeta) . '</a></strong></td>';
                    echo '<td>Carpeta</td>';
                    echo '<td>-</td>';
                    echo '<td>' . htmlspecialchars($row['creado_en'] ?? '-') . '</td>';
                    echo '<td>';
                    echo '<a href="./codigos/borrardir.php?dirId=' . urlencode($row['id']) .
                         '&carpeta=' . urlencode($carpetaActualURL) .
                         '" class="btn btn-danger btn-sm" onclick="return confirm(\'¿Está seguro de que desea eliminar esta carpeta y su contenido?\')">Borrar</a>';
                    echo '</td>';
                    echo '</tr>';
                }
            }

            // Archivos
            if ($resArchivos && $resArchivos->num_rows > 0) {
                $hayAlgo = true;
                while ($row = $resArchivos->fetch_assoc()) {
                    $fileId = (int)$row['id'];
                    $nombre = $row['nombre'];
                    $ext = $row['extension'] ?? '';
                    $nombreConExt = $nombre;

                    if (!empty($ext) && stripos($nombre, '.') === false) {
                        $nombreConExt .= '.' . $ext;
                    }

                    $tamano = (int)($row['tamano_bytes'] ?? 0);
                    $tamanoMB = number_format($tamano / (1024 * 1024), 2);

                    echo '<tr>';
                    echo '<td>' . iconoArchivo($nombreConExt) . '</td>';
                    echo '<td><a href="abrArchi.php?id=' . urlencode($fileId) . '" target="_blank">' . htmlspecialchars($nombreConExt) . '</a></td>';
                    echo '<td>Archivo</td>';
                    echo '<td>' . $tamanoMB . ' MB</td>';
                    echo '<td>' . htmlspecialchars($row['creado_en'] ?? '-') . '</td>';
                    echo '<td>';
                    echo '<a href="./codigos/descargar.php?id=' . urlencode($fileId) . '" class="btn btn-success btn-sm">Descargar</a>';
                    echo '&nbsp;';
                    echo '<a href="./codigos/compartir.php?id=' . urlencode($fileId) . '" class="btn btn-info btn-sm">Compartir</a>';
                    echo '&nbsp;';
                    echo '<a href="./codigos/borarchi.php?id=' . urlencode($fileId) .
                         '&carpeta=' . urlencode($carpetaActualURL) .
                         '" class="btn btn-danger btn-sm" onclick="return confirm(\'¿Está seguro?\')">Borrar</a>';
                    echo '</td>';
                    echo '</tr>';
                }
            }

            if (!$hayAlgo) {
                echo '<tr><td colspan="6"><em>La carpeta se encuentra vacía</em></td></tr>';
            }

            echo '</table>';

            // COMPARTIDOS CONMIGO 
            $sqlCompartidos = "
                SELECT
                    a.id            AS archivo_id,
                    a.nombre        AS nombre,
                    a.extension     AS extension,
                    u.usuario       AS propietario_usuario,
                    c.permiso       AS permiso,
                    c.creado_en     AS compartido_en
                FROM compartidos c
                INNER JOIN archivos a ON a.id = c.archivo_id
                INNER JOIN usuarios u ON u.id = c.propietario_id
                WHERE c.compartido_con_id = ?
                  AND c.tipo = 'archivo'
                ORDER BY c.creado_en DESC
            ";

            $stmtS = $conex->prepare($sqlCompartidos);
            $stmtS->bind_param("i", $usuarioIdActual);
            $stmtS->execute();
            $resS = $stmtS->get_result();

            if ($resS && $resS->num_rows > 0) {
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

                while ($r = $resS->fetch_assoc()) {
                    $archivoId = (int)$r['archivo_id'];
                    $nombre = $r['nombre'];
                    $ext = $r['extension'] ?? '';
                    $nombreConExt = $nombre;
                    if (!empty($ext) && stripos($nombre, '.') === false) $nombreConExt .= '.' . $ext;

                    echo '<tr>';
                    echo '<td>' . iconoArchivo($nombreConExt) . '</td>';
                    echo '<td>' . htmlspecialchars($nombreConExt) . '</td>';
                    echo '<td>' . htmlspecialchars($r['propietario_usuario']) . '</td>';
                    echo '<td>' . htmlspecialchars(ucfirst($r['permiso'])) . '</td>';
                    echo '<td>';
                    echo '<a href="abrArchi.php?id=' . urlencode($archivoId) . '" target="_blank" class="btn btn-info btn-sm">Abrir</a>';
                    echo '&nbsp;';
                    echo '<a href="./codigos/descargar.php?id=' . urlencode($archivoId) . '" class="btn btn-success btn-sm">Descargar</a>';
                    echo '</td>';
                    echo '</tr>';
                }

                echo '</table>';
            }

            $stmtS->close();
            $stmtC->close();
            $stmtA->close();
            ?>
        </div>
    </div>
</main>

<footer class="row"></footer>
<?php include_once('partes/final.inc'); ?>
</body>
</html>
