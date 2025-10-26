<?php
    //Inicio la sesión
    session_start();

    //Utiliza los datos de sesion comprueba que el usuario este autenticado
    if($_SESSION["autenticado"] != "SI") {
        header("Location: index.php");
        exit(); //fin del scrip
    }

    // Variables de ruta
    $rutaBase = "C:" . DIRECTORY_SEPARATOR . "xampp" . DIRECTORY_SEPARATOR . "htdocs" . DIRECTORY_SEPARATOR . "mybox";
    $rutaUsuario = $rutaBase . DIRECTORY_SEPARATOR . $_SESSION["usuario"];
    
    // Obtener la carpeta actual desde GET (si no existe, usar la carpeta del usuario)
    $carpetaActualURL = isset($_GET['carpeta']) ? $_GET['carpeta'] : '';
    $carpetaActualSistema = str_replace('/', DIRECTORY_SEPARATOR, $carpetaActualURL);
    $ruta = $rutaUsuario;
    
    if (!empty($carpetaActualSistema)) {
        $ruta = $rutaUsuario . DIRECTORY_SEPARATOR . $carpetaActualSistema;
    }

    // Verifica que la ruta sea válida y esté dentro de la carpeta del usuario
    $rutaRealizada = realpath($ruta);
    $rutaRealUsuario = realpath($rutaUsuario);
    
    if ($rutaRealizada === false || strpos($rutaRealizada, $rutaRealUsuario) !== 0) {
        $ruta = $rutaUsuario;
        $carpetaActualURL = '';
        $carpetaActualSistema = '';
    }

    $datos = explode(DIRECTORY_SEPARATOR, $rutaBase);
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
        <div class="panel panel-primary">
            <div class="panel-heading">
                <strong>Mi Cajón de Archivos</strong>
                <?php 
                    if (!empty($carpetaActualURL)) {
                        echo " > " . htmlspecialchars($carpetaActualURL);
                    }
                ?>
            </div>
            <div class="panel-body">
                <?php
                    // Botones de acción (creadir.php (en la carpeta de codigos) y agrearchi.php (en la raíz))
                    $params = !empty($carpetaActualURL) ? '?carpeta=' . urlencode($carpetaActualURL) : '';
                    echo '<div class="btn-group" role="group">';
                    echo '<a href="./codigos/creadir.php' . $params . '" class="btn btn-success">Crear Carpeta</a>';
                    echo '&nbsp;&nbsp;';
                    echo '<a href="agrearchi.php' . $params . '" class="btn btn-primary">Agregar Archivo</a>';
                    echo '</div>';
                    
                    // Botón para volver atrás si estamos dentro de una carpeta
                    if (!empty($carpetaActualURL)) {
                        echo '<br><br>';
                        echo '<a href="./carpetas.php" class="btn btn-info">Volver Atrás</a>';
                        echo '<br><br>';
                    }
                    
                    echo '<table class="table table-striped">';
                        echo '<tr>';
                            echo '<th>Nombre</th>';
                            echo '<th>Tipo</th>';
                            echo '<th>Tamaño</th>';
                            echo '<th>Último acceso</th>';
                            echo '<th>Acciones</th>';
                        echo '</tr>';
                        
                        // Verificar que la carpeta existe antes de abrirla
                        if (!is_dir($ruta)) {
                            echo '<tr><td colspan="5"><em>La carpeta no existe o ha sido eliminada.</em></td></tr>';
                        } else {
                            $directorio = opendir($ruta);
                            $archivos = array();
                            $carpetas = array();
                            
                            // Separar archivos y carpetas
                            while($elem = readdir($directorio)){
                                if(($elem!='.') and ($elem!='..')){
                                    if (is_dir($ruta . DIRECTORY_SEPARATOR . $elem)) {
                                        $carpetas[] = $elem;
                                    } else {
                                        $archivos[] = $elem;
                                    }
                                }
                            }
                            closedir($directorio);
                            
                            // Mostrar primero las carpetas
                            sort($carpetas);
                            foreach ($carpetas as $carpeta) {
                                $rutaCarpeta = $ruta . DIRECTORY_SEPARATOR . $carpeta;
                                $nuevaCarpetaURL = empty($carpetaActualURL) ? $carpeta : $carpetaActualURL . '/' . $carpeta;
                                
                                echo '<tr>';
                                    echo '<td><strong><a href="carpetas.php?carpeta=' . urlencode($nuevaCarpetaURL) . '">' . htmlspecialchars($carpeta) . '</a></strong></td>';
                                    echo '<td><span class="glyphicon glyphicon-folder-open"></span> Carpeta</td>';
                                    echo '<td>-</td>';
                                    echo '<td>'.date("d/m/y h:i:s",fileatime($rutaCarpeta)).'</td>';
                                    echo '<td>';
                                        echo '<a href="./codigos/borrardir.php?dir=' . urlencode($carpeta) . '&carpeta=' . urlencode($carpetaActualURL) . '" class="btn btn-danger btn-sm" onclick="return confirm(\'¿Está seguro de que desea eliminar esta carpeta y su contenido?\')">Borrar</a>';
                                    echo '</td>';
                                echo '</tr>';
                            }
                            
                            // Mostrar luego los archivos
                            sort($archivos);
                            foreach ($archivos as $archivo) {
                                $rutaArchivo = $ruta . DIRECTORY_SEPARATOR . $archivo;
                                echo '<tr>';
                                    echo '<td><a href="./codigos/descargar.php?arch=' . urlencode($archivo) . '&carpeta=' . urlencode($carpetaActualURL) . '">' . htmlspecialchars($archivo) . '</a></td>';
                                        echo '<td><span class="glyphicon glyphicon-file"></span> Archivo</td>';
                                        echo '<td>'.filesize($rutaArchivo).' bytes</td>';
                                        echo '<td>'.date("d/m/y h:i:s",fileatime($rutaArchivo)).'</td>';
                                        echo '<td>';
                                            echo '<a href="./codigos/descargar.php?arch=' . urlencode($archivo) . '&carpeta=' . urlencode($carpetaActualURL) . '" class="btn btn-success btn-sm">Descargar</a>';
                                            echo '&nbsp;';
                                            echo '<a href="./codigos/compartir.php?arch=' . urlencode($archivo) . '&carpeta=' . urlencode($carpetaActualURL) . '" class="btn btn-info btn-sm">Compartir</a>';
                                            echo '&nbsp;';
                                            echo '<a href="./codigos/borarchi.php?archi=' . urlencode($archivo) . '&carpeta=' . urlencode($carpetaActualURL) . '" class="btn btn-danger btn-sm" onclick="return confirm(\'¿Está seguro?\')">Borrar</a>';
                                        echo '</td>';
                                    echo '</tr>';
                            }
                            
                            $totalItems = count($carpetas) + count($archivos);
                            
                            if($totalItems == 0)
                                echo '<tr><td colspan="5"><em>La carpeta se encuentra vacía</em></td></tr>';
                        }
                    echo '</table>';
                ?>
            </div>
        </div>
    </main>

    <footer class="row">

    </footer>
    <?php include_once('partes/final.inc'); ?>
</body>
</html>
