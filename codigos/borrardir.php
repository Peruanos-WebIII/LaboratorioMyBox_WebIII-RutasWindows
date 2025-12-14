<?php
session_start();

if (!isset($_SESSION["autenticado"]) || $_SESSION["autenticado"] != "SI") {
    header("Location: ../index.php");
    exit();
}

require_once('conexion.inc');

$dirId = isset($_GET['dirId']) ? (int)$_GET['dirId'] : 0;
$carpetaActual = isset($_GET['carpeta']) ? $_GET['carpeta'] : '';

if ($dirId <= 0) {
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

// validar que la carpeta sea mía
$sqlVal = "SELECT id FROM directorios WHERE id = ? AND usuario_id = ? LIMIT 1";
$stmtV = $conex->prepare($sqlVal);
$stmtV->bind_param("ii", $dirId, $miId);
$stmtV->execute();
$resV = $stmtV->get_result();
$ok = ($resV && $resV->num_rows > 0);
$stmtV->close();

if (!$ok) {
    header("Location: ../carpetas.php");
    exit();
}

function borrarDirectorioRecursivo(mysqli $conex, int $usuarioId, int $dirId): void {
    // borrar archivos del directorio
    $sqlFiles = "SELECT id FROM archivos WHERE usuario_id = ? AND directorio_id = ?";
    $stF = $conex->prepare($sqlFiles);
    $stF->bind_param("ii", $usuarioId, $dirId);
    $stF->execute();
    $resF = $stF->get_result();

    if ($resF) {
        while ($row = $resF->fetch_assoc()) {
            $fileId = (int)$row['id'];

            $sqlDelC = "DELETE FROM compartidos WHERE archivo_id = ?";
            $stC = $conex->prepare($sqlDelC);
            $stC->bind_param("i", $fileId);
            $stC->execute();
            $stC->close();

            $sqlDelF = "DELETE FROM archivos WHERE id = ? AND usuario_id = ?";
            $stDF = $conex->prepare($sqlDelF);
            $stDF->bind_param("ii", $fileId, $usuarioId);
            $stDF->execute();
            $stDF->close();
        }
    }
    $stF->close();

    // buscar subdirectorios y borrarlos primero
    $sqlDirs = "SELECT id FROM directorios WHERE usuario_id = ? AND parent_id = ?";
    $stD = $conex->prepare($sqlDirs);
    $stD->bind_param("ii", $usuarioId, $dirId);
    $stD->execute();
    $resD = $stD->get_result();

    if ($resD) {
        while ($row = $resD->fetch_assoc()) {
            borrarDirectorioRecursivo($conex, $usuarioId, (int)$row['id']);
        }
    }
    $stD->close();

    // borrar compartidos del directorio si existieran
    $sqlDelCD = "DELETE FROM compartidos WHERE directorio_id = ?";
    $stCD = $conex->prepare($sqlDelCD);
    $stCD->bind_param("i", $dirId);
    $stCD->execute();
    $stCD->close();

    // borrar el directorio
    $sqlDelDir = "DELETE FROM directorios WHERE id = ? AND usuario_id = ?";
    $stDel = $conex->prepare($sqlDelDir);
    $stDel->bind_param("ii", $dirId, $usuarioId);
    $stDel->execute();
    $stDel->close();
}

borrarDirectorioRecursivo($conex, $miId, $dirId);

$urlRetorno = "../carpetas.php" . (!empty($carpetaActual) ? "?carpeta=" . urlencode($carpetaActual) : "");
header("Location: " . $urlRetorno);
exit();
