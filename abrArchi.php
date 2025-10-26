<?php
	$archivo = $_GET['arch'];

	//Inicio la sesión
	session_start();

	//Utiliza los datos de sesion comprueba que el usuario este autenticado
	if ($_SESSION["autenticado"] != "SI") {
		header("Location: index.php");
		exit(); //fin del script
	}

	//Declara ruta carpeta del usuario
	$ruta = "d:\\mybox";
	$ruta = $ruta.'/'.$_SESSION["usuario"].'/'.$archivo;

	$file = fopen($ruta,"r");
	$contenido = fread($file, filesize($ruta));
	$mime = mime_content_type($ruta);

	//header("Content-type: application/pdf");
    if($mime == 'application/pdf'){
        header("Content-type: ". $mime);
        echo $contenido;
    }else{
        header("Content-Disposition: attachment; filename=".$archivo);
        header("Content-type: ". $mime);
		header("Content-length: ".filesize($ruta));
		readfile($ruta);
    }
?>
