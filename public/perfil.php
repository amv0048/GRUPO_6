<?php 
session_start();
require "../src/sesion/conexion.php";

error_reporting(E_ALL);
ini_set("display_errors", 1);

if (!isset($_SESSION["id"])) {
    header("Location: login.html");
    exit();
}

// ── SELECT INICIAL PARA PLACEHOLDERS ────────────────────────
if (isset($_SESSION["user"])) {
    $consulta = $_conexion->prepare("SELECT * FROM Usuario WHERE id_adoptante = ?");
    $consulta->bind_param("i", $_SESSION["id"]);
    $consulta->execute();
    $datos = $consulta->get_result()->fetch_assoc();
    $consulta->close();
    $tipo = "usuario";
    //var_dump($datos);
    //echo "gola";
} else {
    $consulta = $_conexion->prepare("SELECT * FROM Protectora WHERE id_protectora = ?");
    $consulta->bind_param("i", $_SESSION["id"]);
    $consulta->execute();
    $datos = $consulta->get_result()->fetch_assoc();
    $consulta->close();
    $tipo = "protectora";
}

// ── LÓGICA DE ACTUALIZACIÓN ──────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    if ($tipo == "usuario") {

        $nombre   = htmlspecialchars(trim($_POST["nombre"]));
        $apellido = htmlspecialchars(trim($_POST["apellido"]));
        $email    = htmlspecialchars(trim($_POST["email"]));
        $numero   = htmlspecialchars(trim($_POST["numero"]));
        $pass_nueva  = trim($_POST["pass_nueva"]);
        $pass_nueva2 = trim($_POST["pass_nueva2"]);

        $campos = [];
        $valores = [];
        $tipos = "";

        if ($nombre != "") {
            $campos[] = "nombre = ?";
            $valores[] = $nombre;
            $tipos .= "s";
        }
        if ($apellido != "") {
            $campos[] = "apellido = ?";
            $valores[] = $apellido;
            $tipos .= "s";
        }
        if ($email != "") {
            if (!preg_match("/^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/", $email)) {
                $err_pass = "El email no tiene un formato válido";
            } else {
                $campos[] = "email = ?";
                $valores[] = $email;
                $tipos .= "s";
            }
        }
        if ($numero != "") {
            $campos[] = "numero = ?";
            $valores[] = $numero;
            $tipos .= "s";
        }
        if ($pass_nueva != "") {
            if (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/", $pass_nueva)) {
                $err_pass = "Mínimo 8 caracteres, una mayúscula, una minúscula y un número";
            } elseif ($pass_nueva != $pass_nueva2) {
                $err_pass = "Las contraseñas no coinciden";
            } else {
                $campos[] = "contrasena = ?";
                $valores[] = password_hash($pass_nueva, PASSWORD_DEFAULT);
                $tipos .= "s";
            }
        }

        if (!isset($err_pass) && !empty($campos)) {
            $valores[] = $_SESSION["id"];
            $tipos .= "i";
            $sql = "UPDATE Usuario SET " . implode(", ", $campos) . " WHERE id_adoptante = ?";
            $consulta = $_conexion->prepare($sql);
            $consulta->bind_param($tipos, ...$valores);
            if ($consulta->execute()) {
                if ($nombre != "") $_SESSION["user"] = $nombre;
                if ($email != "")  $_SESSION["email"] = $email;
                $ok = "Perfil actualizado correctamente";
                // Refrescamos $datos con los nuevos valores
                $consulta2 = $_conexion->prepare("SELECT * FROM Usuario WHERE id_adoptante = ?");
                $consulta2->bind_param("i", $_SESSION["id"]);
                $consulta2->execute();
                $datos = $consulta2->get_result()->fetch_assoc();
                $consulta2->close();
            } else {
                $err_db = "No se ha podido actualizar el perfil";
            }
            $consulta->close();
        }

    } else {

        $nombre_protectora = htmlspecialchars(trim($_POST["nombre_protectora"]));
        $email             = htmlspecialchars(trim($_POST["email"]));
        $telefono          = htmlspecialchars(trim($_POST["telefono"]));
        $ciudad            = htmlspecialchars(trim($_POST["ciudad"]));
        $localidad         = htmlspecialchars(trim($_POST["localidad"]));
        $direccion         = htmlspecialchars(trim($_POST["direccion"]));
        $pass_nueva        = trim($_POST["pass_nueva"]);
        $pass_nueva2       = trim($_POST["pass_nueva2"]);

        $campos = [];
        $valores = [];
        $tipos = "";

        if ($nombre_protectora != "") {
            $campos[] = "nombre_protectora = ?";
            $valores[] = $nombre_protectora;
            $tipos .= "s";
        }
        if ($email != "") {
            if (!preg_match("/^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/", $email)) {
                $err_pass = "El email no tiene un formato válido";
            } else {
                $campos[] = "email = ?";
                $valores[] = $email;
                $tipos .= "s";
            }
        }
        if ($telefono != "") {
            $campos[] = "telefono = ?";
            $valores[] = $telefono;
            $tipos .= "s";
        }
        if ($ciudad != "") {
            $campos[] = "ciudad = ?";
            $valores[] = $ciudad;
            $tipos .= "s";
        }
        if ($localidad != "") {
            $campos[] = "localidad = ?";
            $valores[] = $localidad;
            $tipos .= "s";
        }
        if ($direccion != "") {
            $campos[] = "direccion = ?";
            $valores[] = $direccion;
            $tipos .= "s";
        }
        if ($pass_nueva != "") {
            if (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/", $pass_nueva)) {
                $err_pass = "Mínimo 8 caracteres, una mayúscula, una minúscula y un número";
            } elseif ($pass_nueva != $pass_nueva2) {
                $err_pass = "Las contraseñas no coinciden";
            } else {
                $campos[] = "contrasena = ?";
                $valores[] = password_hash($pass_nueva, PASSWORD_DEFAULT);
                $tipos .= "s";
            }
        }

        if (!isset($err_pass) && !empty($campos)) {
            $valores[] = $_SESSION["id"];
            $tipos .= "i";
            $sql = "UPDATE Protectora SET " . implode(", ", $campos) . " WHERE id_protectora = ?";
            $consulta = $_conexion->prepare($sql);
            $consulta->bind_param($tipos, ...$valores);
            if ($consulta->execute()) {
                if ($nombre_protectora != "") $_SESSION["protectora"] = $nombre_protectora;
                if ($email != "")            $_SESSION["email"] = $email;
                $ok = "Perfil actualizado correctamente";
                // Refrescamos $datos con los nuevos valores
                $consulta2 = $_conexion->prepare("SELECT * FROM Protectora WHERE id_protectora = ?");
                $consulta2->bind_param("i", $_SESSION["id"]);
                $consulta2->execute();
                $datos = $consulta2->get_result()->fetch_assoc();
                $consulta2->close();
            } else {
                $err_db = "No se ha podido actualizar el perfil";
            }
            $consulta->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil</title>
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/registro.css">
</head>
<body>

<header>
    <!--Logo que rederige al index-->
        <nav class="hBotones">
            <a class="hBoton" href="" target="_self">PROTECTORAS</a>
            <a class="hBoton" href="" target="_self">COLABORADORES</a>
        </nav>
        <nav id="header-izq">
            <a href="index.html" target="_self">
            <img src="" alt="logo" 
             width=""> <!--Todo esto está en blanco hasta que tengamos el logo listo y el index-->
        </a>
        </nav>
        
        <nav class="hBotones"> 
            <a class="hBoton" href="" target="_self">URGENTE</a>
            <a class="hBoton" href="registro.html" target="_self">REGISTRATE!</a>
            <a  href="" target="_self" id="boton-destacado">INICIA SESIÓN</a>

        </nav>
    </header>
    <br><br><br>

<?php if (isset($ok)){ ?>
    <div class="error" style="background:#EAF3DE; border-color:#97C459; color:#173404"><?= $ok ?></div>
<?php }; ?>
<?php if (isset($err_pass)){ ?>
    <div class="error"><?= $err_pass ?></div>
<?php }; ?>
<?php if (isset($err_db)){ ?>
    <div class="error"><?= $err_db ?></div>
<?php }; ?>

<?php if ($tipo == "usuario"){ ?>
    <div id="estructura">
    <form action="perfil.php" method="POST" id="registro">
        <input type="text" name="nombre" class="formu-diseno"
            placeholder="<?= $datos['nombre'] ?>">
            <br>
        <input type="text" name="apellido" class="formu-diseno"
            placeholder="<?= $datos['apellido'] ?>">
            <br>
        <input type="text" name="email" class="formu-diseno"
            placeholder="<?= $datos['email'] ?>">
            <br>
        <input type="text" name="numero" class="formu-diseno"
            placeholder="<?= $datos['numero'] ? $datos['numero'] : 'Teléfono';?>">
            <br>
        <input type="password" name="pass_nueva" class="formu-diseno"
            placeholder="Nueva contraseña (vacío para no cambiar)">
            <br>
        <input type="password" name="pass_nueva2" class="formu-diseno"
            placeholder="Repite la nueva contraseña">
            <br>
        <input type="submit" value="GUARDAR CAMBIOS">
    </form>

<?php }else{ ?>

    <form action="perfil.php" method="POST" id="registro">
        <input type="text" name="nombre_protectora" class="formu-diseno"
            placeholder="<?= $datos['nombre_protectora'] ?>">
            <br>
        <input type="text" name="email" class="formu-diseno"
            placeholder="<?= $datos['email'] ?>">
            <br>
        <input type="text" name="telefono" class="formu-diseno"
            placeholder="<?= $datos['telefono'] ? $datos['telefono'] : 'Teléfono' ?>">
            <br>
        <input type="text" name="ciudad" class="formu-diseno"
            placeholder="<?= $datos['ciudad'] ?>">
            <br>
        <input type="text" name="localidad" class="formu-diseno"
            placeholder="<?= $datos['localidad'] ?>">
            <br>
        <input type="text" name="direccion" class="formu-diseno"
            placeholder="<?= $datos['direccion'] ?>">
            <br>
        <input type="password" name="pass_nueva" class="formu-diseno"
            placeholder="Nueva contraseña (vacío para no cambiar)">
            <br>
        <input type="password" name="pass_nueva2" class="formu-diseno"
            placeholder="Repite la nueva contraseña">
            <br>
        <input type="submit" value="GUARDAR CAMBIOS">
    </form>
    </div>
<?php } ?>


<a href="index.php">Volver al Index</a>

</body>
</html>