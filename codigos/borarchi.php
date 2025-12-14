<?php
session_start();

if (!isset($_SESSION["autenticado"]) || $_SESSION["autenticado"] != "SI") {
    header("Location: ../index.php");
    exit();
}

require_once('conexion.inc');

$archivoId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$carpetaActual = isset($_GET['carpeta']) ? $_GET['carpeta'] : '';

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

// validar que sea mío
$sqlVal = "SELECT id FROM archivos WHERE id = ? AND usuario_id = ? LIMIT 1";
$stmtV = $conex->prepare($sqlVal);
$stmtV->bind_param("ii", $archivoId, $miId);
$stmtV->execute();
$resV = $stmtV->get_result();
$ok = ($resV && $resV->num_rows > 0);
$stmtV->close();

if (!$ok) {
    header("Location: ../carpetas.php");
    exit();
}

// borrar compartidos del archivo
$sqlDelC = "DELETE FROM compartidos WHERE archivo_id = ?";
$stmtC = $conex->prepare($sqlDelC);
$stmtC->bind_param("i", $archivoId);
$stmtC->execute();
$stmtC->close();

// borrar archivo
$sqlDel = "DELETE FROM archivos WHERE id = ? AND usuario_id = ?";
$stmtD = $conex->prepare($sqlDel);
$stmtD->bind_param("ii", $archivoId, $miId);
$stmtD->execute();
$stmtD->close();

$urlRetorno = "../carpetas.php" . (!empty($carpetaActual) ? "?carpeta=" . urlencode($carpetaActual) : "");
header("Location: " . $urlRetorno);
exit();
