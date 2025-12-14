<?php
session_start();

if (!isset($_SESSION["autenticado"]) || $_SESSION["autenticado"] != "SI") {
    header("Location: index.php");
    exit();
}

require_once('codigos/conexion.inc');

$MAX_BYTES = 20 * 1024 * 1024;
$ROOT_NAME = "_ROOT_";

function limpiarRuta($ruta) {
    $ruta = trim((string)$ruta);
    $ruta = str_replace('\\', '/', $ruta);
    $ruta = preg_replace('#/+#', '/', $ruta);
    $ruta = trim($ruta, '/');
    return $ruta;
}

function bytesToMB($bytes) {
    return number_format($bytes / (1024 * 1024), 2);
}

function obtenerUsuarioId(mysqli $conex, string $usuario): int {
    $sql = "SELECT id FROM usuarios WHERE usuario = ? LIMIT 1";
    $stmt = $conex->prepare($sql);
    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();
    return $row ? (int)$row['id'] : 0;
}

/**
 * Crea/obtiene el directorio ROOT lógico del usuario.
 * (No crea carpeta física, solo registro en BD.)
 */
function obtenerRootId(mysqli $conex, int $usuarioId, string $rootName): int {
    $sql = "SELECT id FROM directorios
            WHERE usuario_id = ? AND parent_id IS NULL AND nombre = ?
            LIMIT 1";
    $stmt = $conex->prepare($sql);
    $stmt->bind_param("is", $usuarioId, $rootName);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    $stmt->close();

    if ($row) return (int)$row['id'];

    $sqlIns = "INSERT INTO directorios (nombre, parent_id, usuario_id, creado_en)
               VALUES (?, NULL, ?, NOW())";
    $stmt2 = $conex->prepare($sqlIns);
    $stmt2->bind_param("si", $rootName, $usuarioId);
    $stmt2->execute();
    $newId = (int)$stmt2->insert_id;
    $stmt2->close();

    return $newId;
}

/**
 * Devuelve id del directorio para ruta tipo "docs/sub".
 * Crea directorios faltantes. El primer nivel cuelga del ROOT.
 */
function obtenerDirectorioIdPorRuta(mysqli $conex, int $usuarioId, int $rootId, string $rutaRelativa): int {
    $rutaRelativa = limpiarRuta($rutaRelativa);

    if ($rutaRelativa === '') return $rootId;

    $partes = explode('/', $rutaRelativa);
    $parentId = $rootId;

    foreach ($partes as $nombre) {
        $nombre = trim($nombre);
        if ($nombre === '') continue;

        $sql = "SELECT id FROM directorios
                WHERE usuario_id = ? AND parent_id = ? AND nombre = ?
                LIMIT 1";
        $stmt = $conex->prepare($sql);
        $stmt->bind_param("iis", $usuarioId, $parentId, $nombre);
        $stmt->execute();
        $res = $stmt->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $stmt->close();

        if ($row) {
            $parentId = (int)$row['id'];
        } else {
            $sqlIns = "INSERT INTO directorios (nombre, parent_id, usuario_id, creado_en)
                       VALUES (?, ?, ?, NOW())";
            $stmt2 = $conex->prepare($sqlIns);
            $stmt2->bind_param("sii", $nombre, $parentId, $usuarioId);
            $stmt2->execute();
            $parentId = (int)$stmt2->insert_id;
            $stmt2->close();
        }
    }

    return $parentId;
}

// Contexto
$carpetaActualURL = isset($_GET['carpeta']) ? limpiarRuta($_GET['carpeta']) : '';
$Accion_Formulario = $_SERVER['PHP_SELF'];

$usuarioActual = $_SESSION['usuario'];
$usuarioId = obtenerUsuarioId($conex, $usuarioActual);

if ($usuarioId <= 0) {
    $error = "Usuario inválido. Vuelva a iniciar sesión.";
} else {
    $rootId = obtenerRootId($conex, $usuarioId, $ROOT_NAME);
    $directorioId = obtenerDirectorioIdPorRuta($conex, $usuarioId, $rootId, $carpetaActualURL);
}

if (isset($_POST["OC_Aceptar"]) && $_POST["OC_Aceptar"] === "frmArchi" && !isset($error)) {

    if (!isset($_FILES['txtArchi'])) {
        $error = "No se recibió el archivo. Intente de nuevo.";
    } else {

        $uploadError = $_FILES['txtArchi']['error'];

        if ($uploadError !== UPLOAD_ERR_OK) {
            switch ($uploadError) {
                case UPLOAD_ERR_INI_SIZE:
                case UPLOAD_ERR_FORM_SIZE:
                    $error = "El archivo excede el tamaño permitido. Máximo 20MB.";
                    break;
                case UPLOAD_ERR_PARTIAL:
                    $error = "El archivo se subió parcialmente. Intente de nuevo.";
                    break;
                case UPLOAD_ERR_NO_FILE:
                    $error = "Debe seleccionar un archivo.";
                    break;
                default:
                    $error = "Error al subir el archivo (código $uploadError).";
                    break;
            }
        } else {

            $nombreOriginal = $_FILES['txtArchi']['name'] ?? '';
            $tmpName        = $_FILES['txtArchi']['tmp_name'] ?? '';
            $size           = (int)($_FILES['txtArchi']['size'] ?? 0);

            if ($nombreOriginal === '') {
                $error = "Debe seleccionar un archivo.";
            } elseif ($size <= 0) {
                $error = "El archivo parece vacío o no se pudo leer su tamaño.";
            } elseif ($size > $MAX_BYTES) {
                $error = "El archivo pesa " . bytesToMB($size) . "MB y supera el máximo permitido (20MB).";
            } elseif (strpbrk($nombreOriginal, '<>:"|?*\\/') !== false) {
                $error = "El nombre del archivo contiene caracteres no permitidos.";
            } else {

                $nombreSeguro = basename(str_replace(' ', '_', $nombreOriginal));
                $ext = strtolower(pathinfo($nombreSeguro, PATHINFO_EXTENSION));

                $mime = mime_content_type($tmpName);
                if (!$mime) $mime = "application/octet-stream";

                // Validar duplicado en el mismo directorio
                $sqlDup = "SELECT id FROM archivos
                           WHERE usuario_id = ? AND directorio_id = ? AND nombre = ?
                           LIMIT 1";
                $stmtD = $conex->prepare($sqlDup);
                $stmtD->bind_param("iis", $usuarioId, $directorioId, $nombreSeguro);
                $stmtD->execute();
                $resD = $stmtD->get_result();
                $dup = $resD && $resD->fetch_assoc();
                $stmtD->close();

                if ($dup) {
                    $error = "El archivo ya existe en esta carpeta. Use otro nombre o elimínelo.";
                } else {

                    $contenido = file_get_contents($tmpName);
                    if ($contenido === false) {
                        $error = "No se pudo leer el contenido del archivo.";
                    } else {

                        // INSERT con directorio_id SIEMPRE (incluyendo raíz = rootId)
                        $sqlIns = "INSERT INTO archivos
                            (nombre, extension, mime, tamano_bytes, contenido, comentario, creado_en, directorio_id, usuario_id)
                            VALUES (?, ?, ?, ?, ?, NULL, NOW(), ?, ?)";

                        $stmt = $conex->prepare($sqlIns);
                        $nullBlob = null;

                        // nombre(s), ext(s), mime(s), size(i), contenido(b), directorioId(i), usuarioId(i)
                        $stmt->bind_param("sssibii", $nombreSeguro, $ext, $mime, $size, $nullBlob, $directorioId, $usuarioId);
                        $stmt->send_long_data(4, $contenido);

                        if ($stmt->execute()) {
                            $stmt->close();

                            $destino = "carpetas.php";
                            if ($carpetaActualURL !== '') {
                                $destino .= "?carpeta=" . urlencode($carpetaActualURL);
                            }
                            header("Location: " . $destino);
                            exit();
                        } else {
                            $error = "No se pudo guardar en la BD: " . $stmt->error;
                            $stmt->close();
                        }
                    }
                }
            }
        }
    }
}
?>
<!doctype html>
<html>
<head>
    <?php include_once('partes/encabe.inc'); ?>
    <title>Agregar archivos</title>
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
    <div class="panel panel-primary datos1">
        <div class="panel-heading">
            <strong>Agregar archivo (máximo 20MB)</strong>
        </div>
        <div class="panel-body">

            <?php if (isset($error)): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form action="<?php echo $Accion_Formulario . ($carpetaActualURL !== '' ? '?carpeta=' . urlencode($carpetaActualURL) : ''); ?>"
                  method="post"
                  enctype="multipart/form-data"
                  name="frmArchi">
                <input type="hidden" name="MAX_FILE_SIZE" value="<?php echo $MAX_BYTES; ?>">

                <fieldset>
                    <label><strong>Archivo</strong></label>
                    <input name="txtArchi" type="file" id="txtArchi" size="60" required />
                    <small class="text-muted">Tamaño máximo permitido: 20MB</small>
                    <br><br>

                    <input type="submit" name="Submit" value="Cargar" class="btn btn-primary" />
                    <a href="carpetas.php<?php echo $carpetaActualURL !== '' ? '?carpeta=' . urlencode($carpetaActualURL) : ''; ?>"
                       class="btn btn-default">Cancelar</a>
                </fieldset>

                <input type="hidden" name="OC_Aceptar" value="frmArchi" />
            </form>

        </div>
    </div>
</main>

<footer class="row"></footer>
<?php include_once('partes/final.inc'); ?>
</body>
</html>
