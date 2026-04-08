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
                $_SESSION["nombre"] = $nombre;
                if ($nombre != "") $_SESSION["user"] = $nombre;
                if ($email != "")  $_SESSION["email"] = $email;
                $ok = "Perfil actualizado correctamente";
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
    <title>Mi Perfil · Go Catch</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/material-design-iconic-font/2.2.0/css/material-design-iconic-font.min.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/perfil.css">
</head>
<body>

<header>
    <nav class="hBotones">
        <a class="hBoton" href="" target="_self">PROTECTORAS</a>
        <a class="hBoton" href="" target="_self">COLABORADORES</a>
    </nav>
    <nav id="header-izq">
        <a href="index.html" target="_self">
            <img src="../img/profile/default/oficiales/logo.svg" alt="Go Catch" height="40">
        </a>
    </nav>
    <nav class="hBotones">
        <a class="hBoton" href="" target="_self">URGENTE</a>
        <a class="hBoton" href="registro.html" target="_self">REGÍSTRATE</a>
        <a href="" target="_self" id="boton-destacado">INICIA SESIÓN</a>
    </nav>
</header>

<div id="padre-nuestro">
    <div id="estructura">

        <!-- CABECERA CON FOTO -->
        <div id="perfil-header">
            <div id="foto-perfil-container">
                <div id="foto-perfil">
                    <img src="<?= isset($datos["foto_perfil"]) ? $datos["foto_perfil"] : '../img/profile/default/1.jpg' ?>"
                         alt="Foto de perfil" id="foto-img">
                </div>
                <label for="foto-input" id="foto-label">
                    <i class="zmdi zmdi-camera"></i>
                </label>
                <input type="file" name="foto" id="foto-input" accept="image/*" style="display:none">
            </div>

            <?php if ($tipo == "usuario"): ?>
                <h2 id="perfil-nombre"><?= htmlspecialchars($datos['nombre'] . ' ' . $datos['apellido']) ?></h2>
                <p id="perfil-tipo">Adoptante</p>
            <?php else: ?>
                <h2 id="perfil-nombre"><?= htmlspecialchars($datos['nombre_protectora']) ?></h2>
                <p id="perfil-tipo">Protectora</p>
            <?php endif; ?>
        </div>

        <!-- MENSAJES DE RESPUESTA -->
        <?php if (isset($ok)): ?>
            <div class="msg-ok"><?= $ok ?></div>
        <?php endif; ?>
        <?php if (isset($err_pass)): ?>
            <div class="msg-error"><?= $err_pass ?></div>
        <?php endif; ?>
        <?php if (isset($err_db)): ?>
            <div class="msg-error"><?= $err_db ?></div>
        <?php endif; ?>

        <!-- FORMULARIO -->
        <div id="perfil-form-area">

            <?php if ($tipo == "usuario"): ?>
            <form action="perfil.php" method="POST" id="registro">

                <div class="form-grid">
                    <div class="form-wrapper">
                        <input type="text" name="nombre" class="form-control"
                               placeholder="<?= htmlspecialchars($datos['nombre']) ?>">
                        <i class="zmdi zmdi-account"></i>
                    </div>
                    <div class="form-wrapper">
                        <input type="text" name="apellido" class="form-control"
                               placeholder="<?= htmlspecialchars($datos['apellido']) ?>">
                        <i class="zmdi zmdi-account"></i>
                    </div>
                </div>

                <div class="form-wrapper">
                    <input type="text" name="email" class="form-control"
                           placeholder="<?= htmlspecialchars($datos['email']) ?>">
                    <i class="zmdi zmdi-email"></i>
                </div>

                <div class="form-wrapper">
                    <input type="text" name="numero" class="form-control"
                           placeholder="<?= $datos['numero'] ? htmlspecialchars($datos['numero']) : 'Teléfono' ?>">
                    <i class="zmdi zmdi-phone"></i>
                </div>

                <p class="seccion-label">Cambiar contraseña</p>

                <div class="form-wrapper">
                    <input type="password" name="pass_nueva" class="form-control"
                           placeholder="Nueva contraseña (vacío para no cambiar)">
                    <i class="zmdi zmdi-lock"></i>
                </div>
                <div class="form-wrapper">
                    <input type="password" name="pass_nueva2" class="form-control"
                           placeholder="Repite la nueva contraseña">
                    <i class="zmdi zmdi-lock-outline"></i>
                </div>

                <button type="submit">GUARDAR CAMBIOS <i class="zmdi zmdi-check"></i></button>
            </form>

            <?php else: ?>
            <form action="perfil.php" method="POST" id="registro">

                <div class="form-wrapper">
                    <input type="text" name="nombre_protectora" class="form-control"
                           placeholder="<?= htmlspecialchars($datos['nombre_protectora']) ?>">
                    <i class="zmdi zmdi-shield-check"></i>
                </div>
                <div class="form-wrapper">
                    <input type="text" name="email" class="form-control"
                           placeholder="<?= htmlspecialchars($datos['email']) ?>">
                    <i class="zmdi zmdi-email"></i>
                </div>
                <div class="form-wrapper">
                    <input type="text" name="telefono" class="form-control"
                           placeholder="<?= $datos['telefono'] ? htmlspecialchars($datos['telefono']) : 'Teléfono' ?>">
                    <i class="zmdi zmdi-phone"></i>
                </div>

                <div class="form-grid">
                    <div class="form-wrapper">
                        <input type="text" name="ciudad" class="form-control"
                               placeholder="<?= htmlspecialchars($datos['ciudad']) ?>">
                        <i class="zmdi zmdi-pin"></i>
                    </div>
                    <div class="form-wrapper">
                        <input type="text" name="localidad" class="form-control"
                               placeholder="<?= htmlspecialchars($datos['localidad']) ?>">
                        <i class="zmdi zmdi-map"></i>
                    </div>
                </div>

                <div class="form-wrapper">
                    <input type="text" name="direccion" class="form-control"
                           placeholder="<?= htmlspecialchars($datos['direccion']) ?>">
                    <i class="zmdi zmdi-home"></i>
                </div>

                <p class="seccion-label">Cambiar contraseña</p>

                <div class="form-wrapper">
                    <input type="password" name="pass_nueva" class="form-control"
                           placeholder="Nueva contraseña (vacío para no cambiar)">
                    <i class="zmdi zmdi-lock"></i>
                </div>
                <div class="form-wrapper">
                    <input type="password" name="pass_nueva2" class="form-control"
                           placeholder="Repite la nueva contraseña">
                    <i class="zmdi zmdi-lock-outline"></i>
                </div>

                <button type="submit">GUARDAR CAMBIOS <i class="zmdi zmdi-check"></i></button>
            </form>
            <?php endif; ?>

        </div>

        <!-- VOLVER -->
        <div id="perfil-volver">
            <a href="index.php">← Volver al inicio</a>
        </div>

    </div>
</div>

</body>
</html>
