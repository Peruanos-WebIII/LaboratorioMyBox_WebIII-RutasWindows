<?php
	require_once('codigos/conexion.inc');
	if(isset($_POST['txtUsua']) && isset($_POST['txtContra']) && isset($_POST['txtNomb']) && isset($_POST['txtEmail'])){
		//Crea la instrucción para registrar el usuario
		$AuxSql = sprintf("insert into usuarios(usuario,contra,nombre,email) values('%s',sha2('%s',256),'%s','%s')",
						  trim($_POST['txtUsua']),
						  trim($_POST['txtContra']),
						  trim($_POST['txtNomb']),
						  trim($_POST['txtEmail']));
		try{
			$Regis = mysqli_query($conex,$AuxSql,MYSQLI_STORE_RESULT);
			if($Regis == false){
                throw new Exception('Error al registrar el usuario');
            }else {
                session_start();
                $_SESSION["autenticado"] = "SI";
                $_SESSION["nombre"] = trim($_POST['txtNomb']);
                $_SESSION["usuario"] = trim($_POST['txtUsua']);
                header("location: codigos/creadir.php");
                exit();
            }
		}catch (Exception $e) {
    		echo 'Excepción capturada: ',  $e->getMessage(), "\n";
		}finally{
			unset($_POST['txtUsua']);
	        unset($_POST['txtContra']);
			unset($_POST['txtNomb']);
	   		unset($_POST['txtEmail']);
		}
	}//fin del if principal
?>
<!doctype html>
<html>
<head>
	<?php include_once('partes/encabe.inc'); ?>
    <title>Registrarse al Sitio</title>
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
		<div class="panel panel-primary datos3">
			<div class="panel-heading">
				<strong>Datos Generales</strong>
			</div>
			<div class="panel-body">
				<form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
			    	<fieldset>
			    		<label>Usuario:</label><input type="text" name="txtUsua" size="22" maxlength="15" required /><br>
			    		<label>Contrase&ntilde;a:</label><input type="password" name="txtContra" size="22" maxlength="15" required /><br>
			    		<label>Nombre Completo:</label><input type="text" name="txtNomb" size="40" maxlength="30" required /><br>
			    		<label>Correo Electrónico:</label><input type="text" name="txtEmail" size="55" maxlength="50" required /><br>
			    	</fieldset>
			      	<input type="submit" value="Aceptar" />
				</form>
			</div>
		</div>
    </main>

    <footer class="row">

    </footer>
	<?php include_once('partes/final.inc'); ?>
</body>
</html>
