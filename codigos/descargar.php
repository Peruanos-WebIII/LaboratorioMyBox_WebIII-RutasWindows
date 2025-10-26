<?php
	//Inicio la sesión
	session_start();

	//Utiliza los datos de sesion comprueba que el usuario este autenticado
	if ($_SESSION["autenticado"] != "SI") {
		header("Location: ../index.php");
		exit(); //fin del script
	}

	// Obtener parámetros
	$archivo = isset($_GET['arch']) ? $_GET['arch'] : '';
	$carpetaActual = isset($_GET['carpeta']) ? $_GET['carpeta'] : '';

	if (empty($archivo)) {
		header("Location: ../carpetas.php");
		exit();
	}

	//declara ruta carpeta del usuario
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

	// Verificar que el archivo existe y es un archivo (no un directorio)
	if (!file_exists($rutaArchivo) || !is_file($rutaArchivo)) {
		header("Location: ../carpetas.php");
		exit();
	}

	// Obtener información del archivo
	$nombreArchivo = basename($rutaArchivo);
	$tamanoArchivo = filesize($rutaArchivo);
	$tipoMime = mime_content_type($rutaArchivo);

	// Si mime_content_type no está disponible, usar una alternativa
	if (!$tipoMime || $tipoMime === false) {
		$tipoMime = 'application/octet-stream';
	}

	// Limpiar buffer de salida
	ob_end_clean();

	// Configurar headers para descargar el archivo
	header('Content-Type: ' . $tipoMime);
	header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
	header('Content-Length: ' . $tamanoArchivo);
	header('Pragma: no-cache');
	header('Expires: 0');
	header('Cache-Control: no-cache, must-revalidate');

	// Leer y enviar el archivo
	if (readfile($rutaArchivo) === false) {
		http_response_code(404);
		echo "Error al descargar el archivo.";
	}

	exit();
?>
