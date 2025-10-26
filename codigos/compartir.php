<?php
<?php
    session_start();
    
    if($_SESSION["autenticado"] != "SI") {
        header("Location: ../index.php");
        exit();
    }
    
    // Obtener parámetros
    $archivo = isset($_GET['arch']) ? $_GET['arch'] : '';
    $carpetaActual = isset($_GET['carpeta']) ? $_GET['carpeta'] : '';
    
    // Incluir conexión a BD
    include_once('../partes/conexion.inc');
    
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $usuario_compartido = $_POST['usuario'] ?? '';
        $tipo_permiso = $_POST['permiso'] ?? 'lectura';
        $propietario = $_SESSION['usuario'];
        $ruta_relativa = !empty($carpetaActual) ? $carpetaActual . '/' . $archivo : $archivo;
        
        // Insertar en BD
        $sql = "INSERT INTO compartidos (propietario, ruta_relativa, usuario_compartido, tipo) 
                VALUES (?, ?, ?, ?)";
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param("ssss", $propietario, $ruta_relativa, $usuario_compartido, $tipo_permiso);
        
        if ($stmt->execute()) {
            header("Location: ../carpetas.php" . (!empty($carpetaActual) ? "?carpeta=" . urlencode($carpetaActual) : ""));
            exit();
        }
    }
?>