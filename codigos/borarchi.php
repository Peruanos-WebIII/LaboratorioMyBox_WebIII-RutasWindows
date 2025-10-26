<?php
	$archivo = $_GET['archi'];
	$carpetaActual = isset($_GET['carpeta']) ? $_GET['carpeta'] : '';
	session_start();

	$rutaBase = "d:\\mybox";
	$rutaUsuario = $rutaBase.'\\'.$_SESSION["usuario"];
	
	// Construir la ruta del archivo
	$ruta = $rutaUsuario;
	if (!empty($carpetaActual)) {
		$ruta = $rutaUsuario . '\\' . $carpetaActual;
	}
	
	$rutaArchivo = $ruta . '\\' . $archivo;
	
	// Verifica que la ruta sea válida y esté dentro de la carpeta del usuario
	$rutaRealizada = realpath($ruta);
	$rutaArchivoRealizada = realpath($rutaArchivo);
	$rutaRealUsuario = realpath($rutaUsuario);
	
	if ($rutaRealizada === false || $rutaArchivoRealizada === false || 
	    strpos($rutaRealizada, $rutaRealUsuario) !== 0 || 
	    strpos($rutaArchivoRealizada, $rutaRealUsuario) !== 0) {
		header("Location: ../carpetas.php");
		exit();
	}

	/*Se intenta eliminar un fichero y se informa del resultado.*/
    echo "<h3>";
		if (@unlink($rutaArchivo)){
			echo ("Se ha eliminado el fichero.");
		} else {
			echo ("NO se pudo eliminar el fichero.");
		}
	echo "</h3>";

	//Retorna al punto de invocación
	$urlRetorno = "../carpetas.php" . (!empty($carpetaActual) ? "?carpeta=" . urlencode($carpetaActual) : "");
	echo "<script language='JavaScript'>";
	echo "setTimeout(function() { location.href='" . $urlRetorno . "'; }, 2000);";
	echo "</script>";
?>
