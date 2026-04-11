<!-- DE MOMENTO EN DESUSO  -->

<?php
session_start();
require "../src/sesion/conexion.php";

error_reporting(E_ALL);
ini_set("display_errors", 1);

// Solo protectoras pueden acceder a esta página
if (!isset($_SESSION["id"])) {
    header("Location: login.html");
    exit();
}

if (isset($_SESSION["user"])) {
    // Es un usuario adoptante, redirigir a su perfil
    header("Location: perfil.php");
    exit();
}

// ── SELECT INICIAL PARA PLACEHOLDERS ────────────────────────
$consulta = $_conexion->prepare("SELECT * FROM Protectora WHERE id_protectora = ?");
$consulta->bind_param("i", $_SESSION["id"]);
$consulta->execute();
$datos = $consulta->get_result()->fetch_assoc();
$consulta->close();

if (!$datos) {
    header("Location: login.html");
    exit();
}

// ── ELIMINAR PERFIL ──────────────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST['action'] ?? '') === 'eliminar') {
    $del = $_conexion->prepare("DELETE FROM Protectora WHERE id_protectora = ?");
    $del->bind_param("i", $_SESSION["id"]);
    $del->execute();
    $del->close();
    session_destroy();
    header("Location: index.php");
    exit();
}

// ── LÓGICA DE ACTUALIZACIÓN ──────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $nombre_protectora = htmlspecialchars(trim($_POST["nombre_protectora"]));
    $email             = htmlspecialchars(trim($_POST["email"]));
    $telefono          = htmlspecialchars(trim($_POST["telefono"]));
    $ciudad            = htmlspecialchars(trim($_POST["ciudad"]));
    $localidad         = htmlspecialchars(trim($_POST["localidad"]));
    $direccion         = htmlspecialchars(trim($_POST["direccion"]));
    $pass_nueva        = trim($_POST["pass_nueva"]);
    $pass_nueva2       = trim($_POST["pass_nueva2"]);

    $campos  = [];
    $valores = [];
    $tipos   = "";

    if ($nombre_protectora != "") {
        $campos[]  = "nombre_protectora = ?";
        $valores[] = $nombre_protectora;
        $tipos    .= "s";
    }
    if ($email != "") {
        if (!preg_match("/^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/", $email)) {
            $err_pass = "El email no tiene un formato válido";
        } else {
            $campos[]  = "email = ?";
            $valores[] = $email;
            $tipos    .= "s";
        }
    }
    if ($telefono != "") {
        $campos[]  = "telefono = ?";
        $valores[] = $telefono;
        $tipos    .= "s";
    }
    if ($ciudad != "") {
        $campos[]  = "ciudad = ?";
        $valores[] = $ciudad;
        $tipos    .= "s";
    }
    if ($localidad != "") {
        $campos[]  = "localidad = ?";
        $valores[] = $localidad;
        $tipos    .= "s";
    }
    if ($direccion != "") {
        $campos[]  = "direccion = ?";
        $valores[] = $direccion;
        $tipos    .= "s";
    }
    if ($pass_nueva != "") {
        if (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/", $pass_nueva)) {
            $err_pass = "Mínimo 8 caracteres, una mayúscula, una minúscula y un número";
        } elseif ($pass_nueva != $pass_nueva2) {
            $err_pass = "Las contraseñas no coinciden";
        } else {
            $campos[]  = "contrasena = ?";
            $valores[] = password_hash($pass_nueva, PASSWORD_DEFAULT);
            $tipos    .= "s";
        }
    }

    if (!isset($err_pass) && !empty($campos)) {
        $valores[] = $_SESSION["id"];
        $tipos    .= "i";
        $sql       = "UPDATE Protectora SET " . implode(", ", $campos) . " WHERE id_protectora = ?";
        $consulta  = $_conexion->prepare($sql);
        $consulta->bind_param($tipos, ...$valores);
        if ($consulta->execute()) {
            if ($nombre_protectora != "") $_SESSION["protectora"] = $nombre_protectora;
            if ($email != "")            $_SESSION["email"]       = $email;
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

// Ciudades disponibles (igual que en registro.html)
$ciudades = [
    "almeria" => "Almería",
    "cadiz"   => "Cádiz",
    "cordoba" => "Córdoba",
    "granada" => "Granada",
    "huelva"  => "Huelva",
    "jaen"    => "Jaén",
    "malaga"  => "Málaga",
    "sevilla" => "Sevilla",
];
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
    <style>
        /* ── Select ciudad con mismo estilo que inputs ── */
        select.form-control {
            appearance: none;
            -webkit-appearance: none;
            cursor: pointer;
            padding-right: 24px;
        }
        select.form-control option[value=""] { color: #aaa; }
    </style>
</head>
<body>

<header>
    <nav class="hBotones">
        <a class="hBoton" href="" target="_self">PROTECTORAS</a>
        <a class="hBoton" href="" target="_self">COLABORADORES</a>
    </nav>
    <nav id="header-izq">
        <a href="index.php" target="_self">
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

        <!-- CABECERA CON LOGO/FOTO -->
        <div id="perfil-header">
            <div id="foto-perfil-container">
                <div id="foto-perfil">
                    <img src="<?= isset($datos["logo"]) && $datos["logo"] ? htmlspecialchars($datos["logo"]) : '../img/profile/default/oficiales/1.jpg' ?>"
                         alt="Logo de la protectora" id="foto-img">
                </div>
                <label for="foto-input" id="foto-label">
                    <i class="zmdi zmdi-camera"></i>
                </label>
                <input type="file" name="foto" id="foto-input" accept="image/*" style="display:none">
            </div>

            <h2 id="perfil-nombre"><?= htmlspecialchars($datos['nombre_protectora']) ?></h2>
            <p id="perfil-tipo">Protectora</p>
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

        <!-- FORMULARIO PROTECTORA -->
        <div id="perfil-form-area">
            <form action="perfilprotectora.php" method="POST" id="registro">

                <!-- Nombre de la protectora -->
                <div class="form-wrapper">
                    <input type="text" name="nombre_protectora" class="form-control"
                           placeholder="<?= htmlspecialchars($datos['nombre_protectora']) ?>">
                    <i class="zmdi zmdi-shield-check"></i>
                </div>

                <!-- Email -->
                <div class="form-wrapper">
                    <input type="text" name="email" class="form-control"
                           placeholder="<?= htmlspecialchars($datos['email']) ?>">
                    <i class="zmdi zmdi-email"></i>
                </div>

                <!-- Teléfono -->
                <div class="form-wrapper">
                    <input type="text" name="telefono" class="form-control"
                           placeholder="<?= $datos['telefono'] ? htmlspecialchars($datos['telefono']) : 'Teléfono' ?>">
                    <i class="zmdi zmdi-phone"></i>
                </div>

                <!-- Ciudad + Localidad -->
                <div class="form-grid">
                    <div class="form-wrapper">
                        <select name="ciudad" class="form-control">
                            <option value="">
                                <?= $datos['ciudad'] ? htmlspecialchars($ciudades[$datos['ciudad']] ?? $datos['ciudad']) : 'Ciudad' ?>
                            </option>
                            <?php foreach ($ciudades as $valor => $etiqueta): ?>
                                <option value="<?= $valor ?>"
                                    <?= ($datos['ciudad'] === $valor) ? 'selected' : '' ?>>
                                    <?= $etiqueta ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <i class="zmdi zmdi-caret-down"></i>
                    </div>
                    <div class="form-wrapper">
                        <input type="text" name="localidad" class="form-control"
                               placeholder="<?= $datos['localidad'] ? htmlspecialchars($datos['localidad']) : 'Localidad' ?>">
                        <i class="zmdi zmdi-map"></i>
                    </div>
                </div>

                <!-- Dirección -->
                <div class="form-wrapper">
                    <input type="text" name="direccion" class="form-control"
                           placeholder="<?= $datos['direccion'] ? htmlspecialchars($datos['direccion']) : 'Dirección' ?>">
                    <i class="zmdi zmdi-home"></i>
                </div>

                <!-- Sección contraseña -->
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
        </div>

        <!-- VOLVER -->
        <div id="perfil-volver">
            <a href="index.php">← Volver al inicio</a>
        </div>

        <!-- ELIMINAR PERFIL -->
        <div id="perfil-eliminar">
            <button type="button" id="btn-eliminar-perfil">
                <i class="zmdi zmdi-delete"></i> Eliminar perfil
            </button>
        </div>

    </div>
</div>

<!-- MODAL CONFIRMACIÓN -->
<div id="modal-eliminar" class="modal-overlay">
    <div class="modal-box">
        <i class="zmdi zmdi-alert-circle modal-icono"></i>
        <p class="modal-titulo">¿Eliminar perfil?</p>
        <p class="modal-msg">Esta acción es irreversible. Desaparecerán todos los datos de la protectora y sus animales.</p>
        <div class="modal-acciones">
            <button type="button" id="modal-cancelar" class="modal-btn modal-btn-cancelar">Cancelar</button>
            <form method="POST" style="margin:0">
                <input type="hidden" name="action" value="eliminar">
                <button type="submit" class="modal-btn modal-btn-confirmar">Sí, eliminar</button>
            </form>
        </div>
    </div>
</div>

<style>
#perfil-eliminar {
    text-align: center;
    padding: 8px 40px 32px;
}
#btn-eliminar-perfil {
    background: none;
    border: none;
    color: #e74c3c;
    font-family: 'Poppins', sans-serif;
    font-size: 12px;
    font-weight: 600;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    letter-spacing: 0.5px;
    opacity: 0.75;
    transition: opacity 0.2s;
}
#btn-eliminar-perfil:hover { opacity: 1; text-decoration: underline; }

.modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0, 0, 0, 0.55);
    align-items: center;
    justify-content: center;
    z-index: 1000;
}
.modal-overlay.activo { display: flex; }
.modal-box {
    background: #fff;
    border-radius: 12px;
    padding: 40px 36px 32px;
    max-width: 380px;
    width: 90%;
    text-align: center;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
}
.modal-icono {
    font-size: 2.4rem;
    color: #e74c3c;
    margin-bottom: 12px;
    display: block;
}
.modal-titulo {
    font-size: 16px;
    font-weight: 700;
    color: #0D2D51;
    margin-bottom: 8px;
}
.modal-msg {
    font-size: 13px;
    color: #666;
    line-height: 1.6;
    margin-bottom: 0;
}
.modal-acciones {
    display: flex;
    gap: 12px;
    justify-content: center;
    margin-top: 28px;
}
.modal-btn {
    padding: 10px 26px;
    border-radius: 4px;
    font-family: 'Poppins', sans-serif;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    border: none;
    letter-spacing: 0.5px;
    transition: background 0.2s, transform 0.15s;
}
.modal-btn-cancelar { background: #f0f0f0; color: #555; }
.modal-btn-cancelar:hover { background: #e0e0e0; }
.modal-btn-confirmar { background: #e74c3c; color: #fff; }
.modal-btn-confirmar:hover { background: #c0392b; transform: scale(1.02); }
</style>

<script>
document.getElementById('btn-eliminar-perfil').addEventListener('click', function () {
    document.getElementById('modal-eliminar').classList.add('activo');
});
document.getElementById('modal-cancelar').addEventListener('click', function () {
    document.getElementById('modal-eliminar').classList.remove('activo');
});
document.getElementById('modal-eliminar').addEventListener('click', function (e) {
    if (e.target === this) this.classList.remove('activo');
});
</script>

</body>
</html>