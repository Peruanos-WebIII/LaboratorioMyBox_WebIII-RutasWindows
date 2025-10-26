<?php
    //Inicio la sesión
    session_start();

    //Utiliza los datos de sesion comprueba que el usuario este autenticado
    if ($_SESSION["autenticado"] != "SI") {
       header("Location: index.php");
        exit(); //fin del scrip
    }

	// Variables de ruta
	$rutaBase = "d:\\mybox";
	$rutaUsuario = $rutaBase.'\\'.$_SESSION["usuario"];
	
	// Obtener la carpeta actual desde GET (si no existe, usar la carpeta del usuario)
	$carpetaActual = isset($_GET['carpeta']) ? $_GET['carpeta'] : '';
	$ruta = $rutaUsuario;
	
	if (!empty($carpetaActual)) {
		$ruta = $rutaUsuario . '\\' . $carpetaActual;
	}

	// Verifica que la ruta sea válida y esté dentro de la carpeta del usuario
	$rutaRealizada = realpath($ruta);
	$rutaRealUsuario = realpath($rutaUsuario);
	
	if ($rutaRealizada === false || strpos($rutaRealizada, $rutaRealUsuario) !== 0) {
		header("Location: carpetas.php");
		exit();
	}

	$Accion_Formulario = $_SERVER['PHP_SELF'];
    if ((isset($_POST["OC_Aceptar"])) && ($_POST["OC_Aceptar"] == "frmArchi")) {
		$Sali = $_FILES['txtArchi']['name'];

		// Validar que se haya seleccionado un archivo
		if (empty($Sali)) {
			$error = "Debe seleccionar un archivo.";
		} else {
			// Limpiar espacios del nombre del archivo
			$Sali = str_replace(' ','_',$Sali);
			$rutaArchivo = $ruta . '\\' . $Sali;
			
			// Verificar si el archivo ya existe
			if (file_exists($rutaArchivo)) {
				$error = "El archivo ya existe. Por favor, use otro nombre o elimine el existente.";
			} else {
				// Intentar mover el archivo
				if (move_uploaded_file($_FILES['txtArchi']['tmp_name'], $rutaArchivo)) {
					// Cambiar permisos
					if (chmod($rutaArchivo, 0644)) {
						header("Location: carpetas.php" . (!empty($carpetaActual) ? "?carpeta=" . urlencode($carpetaActual) : ""));
						exit(); //fin del scrip
					} else {
						$error = 'No se pudo cambiar los permisos. Consulte a su administrador.';
					}
				} else {
					$error = 'No se pudo cargar el archivo. Intente de nuevo.';
				}
			}
		}
   }
?>
<!doctype html>
<html>
<head>
	<?php include_once('partes/encabe.inc'); ?>
    <title>Agregar archivos.</title>
</head>
<body class="container cuerpo">
	<header class="row">
        <div class="row">
        	<div class="col-lg-6 col-sm-6">
        		<img  src="imagenes/encabe.png" alt="logo institucional" width="100%">
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
				<strong>Agregar archivo</strong>
			</div>
			<div class="panel-body">
				<?php 
					if (isset($error)): 
				?>
					<div class="alert alert-danger">
						<?php echo htmlspecialchars($error); ?>
					</div>
				<?php 
					endif; 
				?>
				<form action="<?php echo $Accion_Formulario . (!empty($carpetaActual) ? '?carpeta=' . urlencode($carpetaActual) : ''); ?>" method="post" enctype="multipart/form-data" name="frmArchi">
					<fieldset>
           				<label><strong>Archivo</strong></label><input name="txtArchi" type="file" id="txtArchi" size="60" required />
           				<br><br>
           				<input type="submit" name="Submit" value="Cargar" class="btn btn-primary" />
           				<a href="carpetas.php<?php echo !empty($carpetaActual) ? '?carpeta=' . urlencode($carpetaActual) : ''; ?>" class="btn btn-default">Cancelar</a>
         			</fieldset>
         			<input type="hidden" name="OC_Aceptar" value="frmArchi" />
      			</form>
			</div>
		</div>
    </main>

    <footer class="row">

    </footer>
	<?php include_once('partes/final.inc'); ?>
</body>
</html>
