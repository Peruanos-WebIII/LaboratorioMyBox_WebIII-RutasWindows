<?php
session_start();

if (!isset($_SESSION["autenticado"]) || $_SESSION["autenticado"] != "SI") {
    header("Location: ../index.php");
    exit();
}

require_once('conexion.inc');

$archivoId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($archivoId <= 0) {
    header("Location: ../carpetas.php");
    exit();
}

// mi usuario_id
$sqlMiId = "SELECT id FROM usuarios WHERE usuario = ? LIMIT 1";
$stmtMi  = $conex->prepare($sqlMiId);
$stmtMi->bind_param("s", $_SESSION['usuario']);
$stmtMi->execute();
$resMi = $stmtMi->get_result();
$miRow = $resMi->fetch_assoc();
$stmtMi->close();

$miId = $miRow ? (int)$miRow['id'] : 0;
if ($miId <= 0) {
    header("Location: ../carpetas.php");
    exit();
}

// validar que el archivo sea mío
$sqlVal = "SELECT id, nombre, extension FROM archivos WHERE id = ? AND usuario_id = ? LIMIT 1";
$stmtV = $conex->prepare($sqlVal);
$stmtV->bind_param("ii", $archivoId, $miId);
$stmtV->execute();
$resV = $stmtV->get_result();
$archivoRow = $resV ? $resV->fetch_assoc() : null;
$stmtV->close();

if (!$archivoRow) {
    header("Location: ../carpetas.php");
    exit();
}

$nombreArchivo = $archivoRow['nombre'];
if (!empty($archivoRow['extension']) && stripos($nombreArchivo, '.') === false) {
    $nombreArchivo .= '.' . $archivoRow['extension'];
}

$error = '';
$okMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuarioDestino = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';
    $permiso = isset($_POST['permiso']) ? trim($_POST['permiso']) : 'lectura';

    if ($usuarioDestino === '') {
        $error = "Debe elegir un usuario.";
    } else {
        // buscar id destino
        $sqlU = "SELECT id FROM usuarios WHERE usuario = ? LIMIT 1";
        $stU = $conex->prepare($sqlU);
        $stU->bind_param("s", $usuarioDestino);
        $stU->execute();
        $resU = $stU->get_result();
        $rowU = $resU ? $resU->fetch_assoc() : null;
        $stU->close();

        $destId = $rowU ? (int)$rowU['id'] : 0;

        if ($destId <= 0) {
            $error = "El usuario destino no existe.";
        } elseif ($destId === $miId) {
            $error = "No puede compartirse a usted mismo.";
        } else {
            // evitar duplicado
            $sqlChk = "SELECT 1 FROM compartidos
                       WHERE archivo_id = ? AND propietario_id = ? AND compartido_con_id = ? AND tipo='archivo'
                       LIMIT 1";
            $stC = $conex->prepare($sqlChk);
            $stC->bind_param("iii", $archivoId, $miId, $destId);
            $stC->execute();
            $resC = $stC->get_result();
            $ya = ($resC && $resC->num_rows > 0);
            $stC->close();

            if ($ya) {
                $error = "Ya está compartido con ese usuario.";
            } else {
                // insertar
                $sqlIns = "INSERT INTO compartidos (archivo_id, directorio_id, propietario_id, compartido_con_id, permiso, tipo)
                           VALUES (?, NULL, ?, ?, ?, 'archivo')";
                $stI = $conex->prepare($sqlIns);
                $stI->bind_param("iiis", $archivoId, $miId, $destId, $permiso);

                if ($stI->execute()) {
                    $okMsg = "Archivo compartido correctamente.";
                } else {
                    $error = "No se pudo compartir.";
                }
                $stI->close();
            }
        }
    }
}

// lista de usuarios
$sqlUsers = "SELECT usuario FROM usuarios WHERE id <> ? ORDER BY usuario ASC";
$stL = $conex->prepare($sqlUsers);
$stL->bind_param("i", $miId);
$stL->execute();
$resL = $stL->get_result();
$stL->close();
?>
<!doctype html>
<html>
<head>
    <?php include_once('../partes/encabe.inc'); ?>
    <title>Compartir</title>
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
            <strong>Compartir archivo</strong>
        </div>
        <div class="panel-body">
            <p><strong>Archivo:</strong> <?php echo htmlspecialchars($nombreArchivo); ?></p>

            <?php if ($error !== ''): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($okMsg !== ''): ?>
                <div class="alert alert-success"><?php echo htmlspecialchars($okMsg); ?></div>
            <?php endif; ?>

            <form method="post">
                <label><strong>Usuario destino</strong></label>
                <select name="usuario" class="form-control" required>
                    <option value="">-- Seleccione --</option>
                    <?php if ($resL): while ($u = $resL->fetch_assoc()): ?>
                        <option value="<?php echo htmlspecialchars($u['usuario']); ?>"><?php echo htmlspecialchars($u['usuario']); ?></option>
                    <?php endwhile; endif; ?>
                </select>

                <br>

                <label><strong>Permiso</strong></label>
                <select name="permiso" class="form-control">
                    <option value="lectura">Lectura</option>
                    <option value="escritura">Escritura</option>
                </select>

                <br>
                <button class="btn btn-info" type="submit">Compartir</button>
                <a class="btn btn-default" href="../carpetas.php">Volver</a>
            </form>
        </div>
    </div>
</main>

<?php include_once('../partes/final.inc'); ?>
</body>
</html>
