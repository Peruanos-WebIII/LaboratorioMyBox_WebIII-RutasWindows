<?php
	//Inicio la sesión
	session_start();

	//Utiliza los datos de sesion comprueba que el usuario este autenticado
	if ($_SESSION["autenticado"] != "SI") {
		header("Location: ../index.php");
		exit(); //fin del script
	}

	// Obtener parámetros
	$carpetaABorrar = isset($_GET['dir']) ? $_GET['dir'] : '';
	$carpetaActual = isset($_GET['carpeta']) ? $_GET['carpeta'] : '';

	if (empty($carpetaABorrar)) {
		header("Location: ../carpetas.php");
		exit();
	}

	//declara ruta carpeta del usuario
	$rutaBase = "d:\\mybox";
	$rutaUsuario = $rutaBase.'\\'.$_SESSION["usuario"];

	// Construir la ruta actual
	$rutaActual = $rutaUsuario;
	if (!empty($carpetaActual)) {
		$rutaActual = $rutaUsuario . '\\' . $carpetaActual;
	}

	// Ruta de la carpeta a borrar
	$rutaBorrar = $rutaActual . '\\' . $carpetaABorrar;

	// Verifica que las rutas sean válidas y estén dentro de la carpeta del usuario
	$rutaRealizada = realpath($rutaActual);
	$rutaBorrarRealizada = realpath($rutaBorrar);
	$rutaRealUsuario = realpath($rutaUsuario);
	
	if ($rutaRealizada === false || $rutaBorrarRealizada === false || 
	    strpos($rutaRealizada, $rutaRealUsuario) !== 0 || 
	    strpos($rutaBorrarRealizada, $rutaRealUsuario) !== 0) {
		header("Location: ../carpetas.php");
		exit();
	}

	// Función recursiva para borrar carpetas con contenido
	function borrarCarpetaRecursiva($ruta) {
		if (!is_dir($ruta)) return false;
		
		$archivos = scandir($ruta);
		foreach ($archivos as $archivo) {
			if ($archivo != "." && $archivo != "..") {
				$rutaArchivo = $ruta . '\\' . $archivo;
				if (is_dir($rutaArchivo)) {
					borrarCarpetaRecursiva($rutaArchivo);
				} else {
					unlink($rutaArchivo);
				}
			}
		}
		return rmdir($ruta);
	}

	// Intentar borrar la carpeta
	if (borrarCarpetaRecursiva($rutaBorrar)) {
		$mensaje = "Carpeta eliminada exitosamente.";
		$tipo = "success";
	} else {
		$mensaje = "No se pudo eliminar la carpeta. Verifique los permisos.";
		$tipo = "danger";
	}

	// Redirigir a carpetas.php con parámetro de carpeta actual
	$urlRetorno = "../carpetas.php" . (!empty($carpetaActual) ? "?carpeta=" . urlencode($carpetaActual) : "");
	
	// Mostrar mensaje y redirigir
	echo "<!doctype html>";
	echo "<html>";
	echo "<head>";
	echo "<meta charset='UTF-8'>";
	echo "<title>Eliminar Carpeta</title>";
	echo "</head>";
	echo "<body class='container cuerpo'>";
	echo "<div class='alert alert-" . $tipo . "'>";
	echo "<h3>" . htmlspecialchars($mensaje) . "</h3>";
	echo "</div>";
	echo "<script language='JavaScript'>";
	echo "setTimeout(function() { location.href='" . $urlRetorno . "'; }, 2000);";
	echo "</script>";
	echo "</body>";
	echo "</html>";
?>
