<?php
	//Inicio la sesión
	session_start();

	//Utiliza los datos de sesion comprueba que el usuario este autenticado
	if ($_SESSION["autenticado"] != "SI") {
		header("Location: ../index.php");
		exit(); //fin del script
	}

	//declara ruta carpeta del usuario
	$rutaBase = "d:\\mybox";
	$rutaUsuario = $rutaBase.'\\'.$_SESSION["usuario"];

	// Obtener la carpeta actual desde GET (si no existe, usar la carpeta del usuario)
	$carpetaActual = isset($_GET['carpeta']) ? $_GET['carpeta'] : '';
	$rutaActual = $rutaUsuario;
	
	if (!empty($carpetaActual)) {
		$rutaActual = $rutaUsuario . '\\' . $carpetaActual;
	}

	// Verifica que la ruta sea válida y esté dentro de la carpeta del usuario
	$rutaRealizada = realpath($rutaActual);
	$rutaRealUsuario = realpath($rutaUsuario);
	
	if ($rutaRealizada === false || strpos($rutaRealizada, $rutaRealUsuario) !== 0) {
		header("Location: ../carpetas.php");
		exit();
	}

	$Accion_Formulario = $_SERVER['PHP_SELF'];
	
	// Procesar creación de carpeta
	if ((isset($_POST["OC_Aceptar"])) && ($_POST["OC_Aceptar"] == "frmCarpeta")) {
		$nombreCarpeta = trim($_POST["txtNombreCarpeta"]);
		
		// Validar nombre de carpeta
		if (empty($nombreCarpeta)) {
			$error = "El nombre de la carpeta no puede estar vacío.";
		} elseif (preg_match('/[<>:"|?*\\\/]/', $nombreCarpeta)) {
			$error = "El nombre de la carpeta contiene caracteres no permitidos.";
		} else {
			$rutaNuevaCarpeta = $rutaActual . '\\' . $nombreCarpeta;
			
			// Crear la carpeta
			if (mkdir($rutaNuevaCarpeta, 0777, false)) {
				$exito = "Carpeta creada exitosamente.";
				// Redirigir después de 2 segundos
				header("Refresh: 2; URL=../carpetas.php" . (!empty($carpetaActual) ? "?carpeta=" . urlencode($carpetaActual) : ""));
			} else {
				$error = "No se pudo crear la carpeta. Verifique los permisos.";
			}
		}
	}
?>
<!doctype html>
<html>
<head>
	<?php include_once('../partes/encabe.inc'); ?>
    <title>Crear Carpeta</title>
</head>
<body class="container cuerpo">
	<header class="row">
        <div class="row">
        	<div class="col-lg-6 col-sm-6">
        		<img  src="../imagenes/encabe.png" alt="logo institucional" width="100%">
            </div>
        </div>
        <div class="row">
            <?php include_once('../partes/menu.inc'); ?>
        </div>
        <br />
    </header>

    <main class="row">
		<div class="panel panel-primary datos1">
			<div class="panel-heading">
				<strong>Crear Nueva Carpeta</strong>
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
					if (isset($exito)): 
				?>
					<div class="alert alert-success">
						<?php echo htmlspecialchars($exito); ?>
					</div>
				<?php 
					endif; 
				?>
				<form action="<?php echo $Accion_Formulario; ?><?php echo !empty($carpetaActual) ? '?carpeta=' . urlencode($carpetaActual) : ''; ?>" method="post" name="frmCarpeta">
					<fieldset>
           				<label><strong>Nombre de la Carpeta</strong></label>
           				<input name="txtNombreCarpeta" type="text" id="txtNombreCarpeta" size="60" required />
           				<br><br>
           				<input type="submit" name="Submit" value="Crear Carpeta" class="btn btn-primary" />
           				<a href="../carpetas.php<?php echo !empty($carpetaActual) ? '?carpeta=' . urlencode($carpetaActual) : ''; ?>" class="btn btn-default">Cancelar</a>
         			</fieldset>
         			<input type="hidden" name="OC_Aceptar" value="frmCarpeta" />
      			</form>
			</div>
		</div>
    </main>

    <footer class="row">

    </footer>
	<?php include_once('../partes/final.inc'); ?>
</body>
</html>
