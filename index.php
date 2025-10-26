<?php 
    require_once('codigos/conexion.inc'); 
    $Accion_Formulario = $_SERVER['PHP_SELF'];
    if((isset($_POST['txtUsua'])) && (isset($_POST['txtContra']))) {
       
        $auxSql = sprintf("select nombre, usuario from usuarios Where usuario = '%s' and contra = password('%s')", $_POST['txtUsua'],$_POST['txtContra']);
        $regis = mysqli_query($conex,$auxSql);
		
        //libera los inputs del cache     
        unset($_POST['txtUsua']);
        unset($_POST['txtContra']); 	 
	
        if(mysqli_num_rows($regis) > 0){
            $tupla = mysqli_fetch_assoc($regis);
          
            //usuario y clave correctos, se define una sesion y datos de interes
            session_start();
            $_SESSION["autenticado"]= "SI";          
            $_SESSION["nombre"]=$tupla['nombre'];
		    $_SESSION["usuario"]=$tupla['usuario'];
		   
            header("location: carpetas.php");
        }else {
            header("location: errores/400.php");
            exit(); 
        }	 
    }
?>
<!doctype html>
<html>
<head>
	<?php include_once('partes/encabe.inc'); ?>
    <title>Ingreso al Sitio</title>
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
		<div class="panel panel-primary logueo">
			<div class="panel-heading">
				<strong>Autentificación</strong>  
			</div>
			<div class="panel-body">
				<form action="<?php echo $Accion_Formulario; ?>" method="post">
			    	<fieldset>
			    		<label>Usuario:</label><input type="text" name="txtUsua" size="22" maxlength="15" required /><br>			    	
			    		<label>Contrase&ntilde;a:</label><input type="password" name="txtContra" size="22" maxlength="15" required />
			    	</fieldset>
			      	<input type="submit" value="Aceptar" />
				</form>
			</div>								
		</div>   
   		<br>
		<a href="registrar.php">Registrarse Aquí</a>			
    </main>
            
    <footer class="row">
    	
    </footer>
	<?php include_once('partes/final.inc'); ?>        
</body>
</html>