<?php
require_once('codigos/conexion.inc');

$mensajeError = '';

if (
    isset($_POST['txtUsua'], $_POST['txtContra'], $_POST['txtNomb'], $_POST['txtEmail'])
) {
    $usuario = trim($_POST['txtUsua']);
    $contra  = trim($_POST['txtContra']);
    $nombre  = trim($_POST['txtNomb']);
    $email   = trim($_POST['txtEmail']);

    // Validaciones mínimas
    if ($usuario === '' || $contra === '' || $nombre === '' || $email === '') {
        $mensajeError = "Todos los campos son obligatorios.";
    } else {
        // Verificar si el usuario ya existe
        $sqlExiste = "SELECT id FROM usuarios WHERE usuario = ? LIMIT 1";
        $stmt = mysqli_prepare($conex, $sqlExiste);

        if (!$stmt) {
            $mensajeError = "Error preparando validación: " . mysqli_error($conex);
        } else {
            mysqli_stmt_bind_param($stmt, "s", $usuario);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_store_result($stmt);

            if (mysqli_stmt_num_rows($stmt) > 0) {
                $mensajeError = "Ese usuario ya existe. Pruebe con otro.";
                mysqli_stmt_close($stmt);
            } else {
                mysqli_stmt_close($stmt);

                // 2) Insertar usuario
                $sqlInsert = "INSERT INTO usuarios(usuario, contra, nombre, email)
                             VALUES (?, SHA2(?,256), ?, ?)";

                $stmt2 = mysqli_prepare($conex, $sqlInsert);

                if (!$stmt2) {
                    $mensajeError = "Error preparando registro: " . mysqli_error($conex);
                } else {
                    mysqli_stmt_bind_param($stmt2, "ssss", $usuario, $contra, $nombre, $email);

                    if (!mysqli_stmt_execute($stmt2)) {
                        $mensajeError = "Error al registrar: " . mysqli_stmt_error($stmt2);
                    } else {
                        session_start();
                        $_SESSION["autenticado"] = "SI";
                        $_SESSION["nombre"] = $nombre;
                        $_SESSION["usuario"] = $usuario;

                        // Redirigir
                        header("Location: codigos/creadir.php");
                        exit();
                    }

                    mysqli_stmt_close($stmt2);
                }
            }
        }
    }
}
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
            <img src="imagenes/encabe.png" alt="logo institucional" width="100%">
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

            <?php if ($mensajeError !== ''): ?>
                <div class="alert alert-danger">
                    <?php echo htmlspecialchars($mensajeError); ?>
                </div>
            <?php endif; ?>

            <form action="<?php echo $_SERVER['PHP_SELF']; ?>" method="post">
                <fieldset>
                    <label>Usuario:</label>
                    <input type="text" name="txtUsua" size="22" maxlength="15" required /><br>

                    <label>Contrase&ntilde;a:</label>
                    <input type="password" name="txtContra" size="22" maxlength="15" required /><br>

                    <label>Nombre Completo:</label>
                    <input type="text" name="txtNomb" size="40" maxlength="30" required /><br>

                    <label>Correo Electrónico:</label>
                    <input type="text" name="txtEmail" size="55" maxlength="50" required /><br>
                </fieldset>
                <input type="submit" value="Aceptar" />
            </form>
        </div>
    </div>
</main>

<footer class="row"></footer>
<?php include_once('partes/final.inc'); ?>
</body>
</html>
