<?php
session_start();

if (!isset($_SESSION["autenticado"]) || $_SESSION["autenticado"] != "SI") {
    header("Location: ../index.php");
    exit();
}

require_once('conexion.inc');

function limpiarRuta(string $ruta): string {
    $ruta = trim($ruta);
    $ruta = str_replace('\\', '/', $ruta);
    $ruta = preg_replace('#/+#', '/', $ruta);
    $ruta = trim($ruta, '/');
    return $ruta;
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

function obtenerDirectorioIdPorRuta(mysqli $conex, int $usuarioId, string $rutaRelativa): ?int {
    $rutaRelativa = limpiarRuta($rutaRelativa);
    if ($rutaRelativa === '') return null;

    $partes = explode('/', $rutaRelativa);
    $parentId = null;

    foreach ($partes as $nombre) {
        $nombre = trim($nombre);
        if ($nombre === '') continue;

        if ($parentId === null) {
            $sql = "SELECT id FROM directorios WHERE usuario_id = ? AND parent_id IS NULL AND nombre = ? LIMIT 1";
            $st = $conex->prepare($sql);
            $st->bind_param("is", $usuarioId, $nombre);
        } else {
            $sql = "SELECT id FROM directorios WHERE usuario_id = ? AND parent_id = ? AND nombre = ? LIMIT 1";
            $st = $conex->prepare($sql);
            $st->bind_param("iis", $usuarioId, $parentId, $nombre);
        }

        $st->execute();
        $res = $st->get_result();
        $row = $res ? $res->fetch_assoc() : null;
        $st->close();

        if ($row) {
            $parentId = (int)$row['id'];
        } else {
            // crear
            if ($parentId === null) {
                $sqlIns = "INSERT INTO directorios (nombre, parent_id, usuario_id) VALUES (?, NULL, ?)";
                $st2 = $conex->prepare($sqlIns);
                $st2->bind_param("si", $nombre, $usuarioId);
            } else {
                $sqlIns = "INSERT INTO directorios (nombre, parent_id, usuario_id) VALUES (?, ?, ?)";
                $st2 = $conex->prepare($sqlIns);
                $st2->bind_param("sii", $nombre, $parentId, $usuarioId);
            }
            $st2->execute();
            $parentId = (int)$st2->insert_id;
            $st2->close();
        }
    }

    return $parentId;
}

$usuarioId = getUsuarioId($conex, $_SESSION['usuario']);
if ($usuarioId <= 0) {
    echo "Usuario inválido.";
    exit();
}

$carpetaActual = isset($_GET['carpeta']) ? limpiarRuta($_GET['carpeta']) : '';
$parentId = obtenerDirectorioIdPorRuta($conex, $usuarioId, $carpetaActual); // puede ser null (raíz)

$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = isset($_POST['nombre']) ? trim($_POST['nombre']) : '';

    if ($nombre === '') {
        $error = "Debe indicar un nombre de carpeta.";
    } elseif (preg_match('/[<>:"\/\\\\|?*\x00-\x1F]/', $nombre)) {
        $error = "Nombre inválido (caracteres no permitidos).";
    } else {

        // validar duplicado en el mismo parent
        if ($parentId === null) {
            $sqlChk = "SELECT 1 FROM directorios WHERE usuario_id = ? AND parent_id IS NULL AND nombre = ? LIMIT 1";
            $st = $conex->prepare($sqlChk);
            $st->bind_param("is", $usuarioId, $nombre);
        } else {
            $sqlChk = "SELECT 1 FROM directorios WHERE usuario_id = ? AND parent_id = ? AND nombre = ? LIMIT 1";
            $st = $conex->prepare($sqlChk);
            $st->bind_param("iis", $usuarioId, $parentId, $nombre);
        }

        $st->execute();
        $res = $st->get_result();
        $existe = ($res && $res->num_rows > 0);
        $st->close();

        if ($existe) {
            $error = "Esa carpeta ya existe.";
        } else {
            if ($parentId === null) {
                $sqlIns = "INSERT INTO directorios (nombre, parent_id, usuario_id) VALUES (?, NULL, ?)";
                $st2 = $conex->prepare($sqlIns);
                $st2->bind_param("si", $nombre, $usuarioId);
            } else {
                $sqlIns = "INSERT INTO directorios (nombre, parent_id, usuario_id) VALUES (?, ?, ?)";
                $st2 = $conex->prepare($sqlIns);
                $st2->bind_param("sii", $nombre, $parentId, $usuarioId);
            }

            if ($st2->execute()) {
                $st2->close();
                $dest = "../carpetas.php" . ($carpetaActual !== '' ? "?carpeta=" . urlencode($carpetaActual) : "");
                header("Location: " . $dest);
                exit();
            } else {
                $error = "No se pudo crear la carpeta.";
                $st2->close();
            }
        }
    }
}
?>
<!doctype html>
<html>
<head>
    <?php include_once('../partes/encabe.inc'); ?>
    <title>Crear carpeta</title>
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
    <div class="panel panel-primary datos3">
        <div class="panel-heading">
            <strong>Crear Nueva Carpeta</strong>
        </div>
        <div class="panel-body">
            <?php if ($error !== ''): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <form method="post" action="<?php echo $_SERVER['PHP_SELF'] . ($carpetaActual !== '' ? '?carpeta=' . urlencode($carpetaActual) : ''); ?>">
                <label><strong>Nombre de la Carpeta</strong></label>
                <input type="text" name="nombre" class="form-control" required>
                <br>
                <button type="submit" class="btn btn-primary">Crear Carpeta</button>
                <a class="btn btn-default" href="../carpetas.php<?php echo $carpetaActual !== '' ? '?carpeta=' . urlencode($carpetaActual) : ''; ?>">Cancelar</a>
            </form>
        </div>
    </div>
</main>

<?php include_once('../partes/final.inc'); ?>
</body>
</html>
