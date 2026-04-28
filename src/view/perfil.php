<?php
session_start();
require "../sesion/conexion.php";

error_reporting(E_ALL);
ini_set("display_errors", 1);

if (!isset($_SESSION["id"])) {
    header("Location: ../../public/login.html");
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

// ── CANCELAR CITA ────────────────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST['action'] ?? '') === 'cancelar_cita') {
    $id_cita = (int)($_POST['id_cita'] ?? 0);
    $tbl_ok  = $_conexion->query("SHOW TABLES LIKE 'CitaEntrevista'")->num_rows > 0;
    if ($tbl_ok && $id_cita > 0) {
        if ($tipo === 'usuario') {
            // Adoptante solo puede cancelar sus propias citas
            $q = $_conexion->prepare(
                "UPDATE CitaEntrevista c
                 JOIN SolicitudAdopcion s ON c.id_solicitud = s.id_solicitud
                 SET c.estado = 'CANCELADA'
                 WHERE c.id_cita = ? AND s.id_adoptante = ?"
            );
            $q->bind_param("ii", $id_cita, $_SESSION['id']);
        } else {
            // Protectora solo puede cancelar citas de sus animales
            $q = $_conexion->prepare(
                "UPDATE CitaEntrevista c
                 JOIN DisponibilidadProtectora d ON c.id_disponibilidad = d.id_disponibilidad
                 SET c.estado = 'CANCELADA'
                 WHERE c.id_cita = ? AND d.id_protectora = ?"
            );
            $q->bind_param("ii", $id_cita, $_SESSION['id']);
        }
        $q->execute();
        $q->close();
    }
    header("Location: perfil.php");
    exit();
}

// ── ELIMINAR PERFIL ──────────────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] === "POST" && ($_POST['action'] ?? '') === 'eliminar') {
    if ($tipo === 'usuario') {
        $del = $_conexion->prepare("DELETE FROM Usuario WHERE id_adoptante = ?");
    } else {
        $del = $_conexion->prepare("DELETE FROM Protectora WHERE id_protectora = ?");
    }
    $del->bind_param("i", $_SESSION["id"]);
    $del->execute();
    $del->close();
    session_destroy();
    header("Location: index.php");
    exit();
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

        // ── FOTO DE PERFIL USUARIO ───────────────────────────────
        if (!empty($_FILES['foto']['name']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $ext_ok = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $ext    = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $ext_ok)) {
                $carpeta = realpath(__DIR__ . '/../img/userPerfil') . '/user_' . $_SESSION["id"];
                if (!is_dir($carpeta)) {
                    mkdir($carpeta, 0755, true);
                }
                $archivo = 'perfil_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['foto']['tmp_name'], $carpeta . '/' . $archivo)) {
                    $ruta_foto = '../../img/userPerfil/user_' . $_SESSION["id"] . '/' . $archivo;
                    $campos[]  = "foto_perfil = ?";
                    $valores[] = $ruta_foto;
                    $tipos    .= "s";
                }
            }
        }
        // ────────────────────────────────────────────────────────

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

        // ── FOTO DE PERFIL PROTECTORA ────────────────────────────
        if (!empty($_FILES['foto']['name']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $ext_ok = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $ext    = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $ext_ok)) {
                $carpeta = realpath(__DIR__ . '/../img/protectoras') . '/protectora_' . $_SESSION["id"] . '/foto_perfil';
                if (!is_dir($carpeta)) {
                    mkdir($carpeta, 0755, true);
                }
                $archivo = 'perfil_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['foto']['tmp_name'], $carpeta . '/' . $archivo)) {
                    $ruta_foto = '../../img/protectoras/protectora_' . $_SESSION["id"] . '/foto_perfil/' . $archivo;
                    $campos[]  = "logo = ?";
                    $valores[] = $ruta_foto;
                    $tipos    .= "s";
                }
            }
        }
        // ────────────────────────────────────────────────────────

        if (!isset($err_pass) && !empty($campos)) {
            $valores[] = $_SESSION["id"];
            $tipos .= "i";
            $sql = "UPDATE Protectora SET " . implode(", ", $campos) . " WHERE id_protectora = ?";
            $consulta = $_conexion->prepare($sql);
            $consulta->bind_param($tipos, ...$valores);
            if ($consulta->execute()) {
                if ($nombre_protectora != ""){
                    $_SESSION["protectora"] = $nombre_protectora;
                    $_SESSION["nombre"] = $nombre_protectora;
                } 
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
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,900&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/material-design-iconic-font/2.2.0/css/material-design-iconic-font.min.css">
    <link rel="stylesheet" href="../../public/css/header.css">
    <link rel="stylesheet" href="../../public/css/perfil.css">
</head>
<body>

<header>
    <nav class="hBotones">
        <a class="hBoton" href="" target="_self">PROTECTORAS</a>
        <a class="hBoton" href="" target="_self">COLABORADORES</a>
    </nav>
    <nav id="header-izq">
        <a href="index.php" target="_self">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200" width="40" height="40" role="img" aria-label="Go Catch"><rect x="0" y="0" width="200" height="200" rx="36" ry="36" fill="#C97041"/><text x="110" y="148" font-family="'Fraunces', serif" font-weight="900" font-size="145" fill="#FFFFFF" text-anchor="middle">gc</text></svg>
        </a>
    </nav>
    <nav class="hBotones">
        <a class="hBoton" href="" target="_self">URGENTE</a>
        <a class="hBoton" href="../../public/registro.html" target="_self">REGÍSTRATE</a>
        <a href="" target="_self" id="boton-destacado">INICIA SESIÓN</a>
    </nav>
</header>

<div id="padre-nuestro">
    <div id="estructura">

        <!-- CABECERA CON FOTO -->
        <div id="perfil-header">
            <div id="foto-perfil-container">
                <div id="foto-perfil">
                <img src="<?php
                    if ($tipo == 'usuario') {
                        echo isset($datos["foto_perfil"]) && $datos["foto_perfil"] ? htmlspecialchars($datos["foto_perfil"]) : '../../img/profile/default/1.jpg';
                    } else {
                        echo isset($datos["logo"]) && $datos["logo"] ? htmlspecialchars($datos["logo"]) : '../../img/profile/default/1.jpg';
                    }
                ?>" alt="Foto de perfil" id="foto-img">
                </div>
                <label for="foto-input" id="foto-label">
                    <i class="zmdi zmdi-camera"></i>
                </label>
                <input type="file" name="foto" id="foto-input" accept="image/*" style="display:none" form="registro">
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
            <form action="perfil.php" method="POST" id="registro" enctype="multipart/form-data">

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
            <form action="perfil.php" method="POST" id="registro" enctype="multipart/form-data">

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

        <!-- CITAS / SOLICITUDES -->
        <?php
        $tbl_sol = $_conexion->query("SHOW TABLES LIKE 'SolicitudAdopcion'");
        $tbl_cit = $_conexion->query("SHOW TABLES LIKE 'CitaEntrevista'");
        $hay_tablas = ($tbl_sol && $tbl_sol->num_rows > 0) && ($tbl_cit && $tbl_cit->num_rows > 0);

        if ($hay_tablas && $tipo === 'usuario'):
            // Solicitudes del adoptante con cita si la hay
            $sol_q = $_conexion->prepare(
                "SELECT s.id_solicitud, s.id_animal, s.estado_solicitud, s.fecha_solicitud,
                        a.nombre AS nombre_animal, a.especie,
                        p.nombre_protectora,
                        c.id_cita, d.fecha AS fecha_cita, d.hora_inicio, d.hora_fin, c.estado AS estado_cita
                 FROM SolicitudAdopcion s
                 JOIN Animales a ON s.id_animal = a.id_animal
                 JOIN Protectora p ON a.id_protectora = p.id_protectora
                 LEFT JOIN CitaEntrevista c ON c.id_solicitud = s.id_solicitud AND c.estado != 'CANCELADA'
                 LEFT JOIN DisponibilidadProtectora d ON c.id_disponibilidad = d.id_disponibilidad
                 WHERE s.id_adoptante = ?
                 ORDER BY s.fecha_solicitud DESC"
            );
            $sol_q->bind_param("i", $_SESSION['id']);
            $sol_q->execute();
            $mis_solicitudes = $sol_q->get_result()->fetch_all(MYSQLI_ASSOC);
            $sol_q->close();

            if (!empty($mis_solicitudes)):
        ?>
        <div id="perfil-solicitudes">
            <p class="seccion-label">Mis solicitudes de adopción</p>
            <?php foreach ($mis_solicitudes as $sol):
                $badge_col = ['PENDIENTE'=>'#b8860b','APROBADA'=>'#2e7d12','RECHAZADA'=>'#c62828'];
                $badge_bg  = ['PENDIENTE'=>'#FFF8E1','APROBADA'=>'#EAF3DE','RECHAZADA'=>'#FFEBEE'];
                $col = $badge_col[$sol['estado_solicitud']] ?? '#777';
                $bg  = $badge_bg[$sol['estado_solicitud']]  ?? '#f5f5f5';
            ?>
            <div class="sol-perfil-item">
                <div class="sol-perfil-row">
                    <div>
                        <a href="ficha-animal.php?id=<?= (int)$sol['id_animal'] ?>" class="sol-perfil-animal">
                            <?= htmlspecialchars($sol['nombre_animal']) ?>
                            <small><?= htmlspecialchars(ucfirst($sol['especie'] ?? '')) ?></small>
                        </a>
                        <p class="sol-perfil-protectora"><?= htmlspecialchars($sol['nombre_protectora']) ?></p>
                    </div>
                    <span class="sol-perfil-badge" style="color:<?= $col ?>;background:<?= $bg ?>">
                        <?= $sol['estado_solicitud'] ?>
                    </span>
                </div>
                <?php if ($sol['id_cita']): ?>
                <div class="sol-perfil-cita-row">
                    <div class="sol-perfil-cita">
                        <i class="zmdi zmdi-calendar-check"></i>
                        <?php $fd = new DateTime($sol['fecha_cita']); ?>
                        Entrevista: <strong><?= $fd->format('d/m/Y') ?> a las <?= substr($sol['hora_inicio'],0,5) ?></strong>
                    </div>
                    <form method="POST" style="margin:0">
                        <input type="hidden" name="action"  value="cancelar_cita">
                        <input type="hidden" name="id_cita" value="<?= (int)$sol['id_cita'] ?>">
                        <button type="submit" class="sol-perfil-btn-cancelar"
                                onclick="return confirm('¿Cancelar esta entrevista?')">
                            <i class="zmdi zmdi-close"></i> Cancelar
                        </button>
                    </form>
                </div>
                <?php elseif ($sol['estado_solicitud'] !== 'RECHAZADA'): ?>
                <a href="reservar-cita.php?solicitud=<?= (int)$sol['id_solicitud'] ?>" class="sol-perfil-btn-cita">
                    <i class="zmdi zmdi-calendar-plus"></i> Reservar entrevista
                </a>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; endif; ?>

        <?php if ($hay_tablas && $tipo === 'protectora'):
            // Citas de la protectora
            $cit_q = $_conexion->prepare(
                "SELECT d.fecha, d.hora_inicio, d.hora_fin,
                        a.nombre AS nombre_animal, s.nombre AS nombre_adoptante, s.apellido, s.telefono,
                        c.estado AS estado_cita
                 FROM CitaEntrevista c
                 JOIN DisponibilidadProtectora d ON c.id_disponibilidad = d.id_disponibilidad
                 JOIN SolicitudAdopcion s ON c.id_solicitud = s.id_solicitud
                 JOIN Animales a ON s.id_animal = a.id_animal
                 WHERE d.id_protectora = ? AND d.fecha >= CURDATE() AND c.estado != 'CANCELADA'
                 ORDER BY d.fecha, d.hora_inicio LIMIT 8"
            );
            $cit_q->bind_param("i", $_SESSION['id']);
            $cit_q->execute();
            $mis_citas = $cit_q->get_result()->fetch_all(MYSQLI_ASSOC);
            $cit_q->close();

            // Solicitudes pendientes count
            $pend_q = $_conexion->prepare(
                "SELECT COUNT(*) AS n FROM SolicitudAdopcion s
                 JOIN Animales a ON s.id_animal = a.id_animal
                 WHERE a.id_protectora = ? AND s.estado_solicitud = 'PENDIENTE'"
            );
            $pend_q->bind_param("i", $_SESSION['id']);
            $pend_q->execute();
            $n_pend = (int)$pend_q->get_result()->fetch_assoc()['n'];
            $pend_q->close();
        ?>
        <div id="perfil-solicitudes">
            <p class="seccion-label">Gestión de adopciones</p>
            <div class="protectora-accesos">
                <a href="solicitudes-protectora.php" class="prot-acc-btn">
                    <i class="zmdi zmdi-inbox"></i>
                    <span>Solicitudes<?php if ($n_pend > 0): ?> <strong>(<?= $n_pend ?>)</strong><?php endif; ?></span>
                </a>
                <a href="disponibilidad.php" class="prot-acc-btn">
                    <i class="zmdi zmdi-calendar-alt"></i>
                    <span>Disponibilidad</span>
                </a>
            </div>
            <?php if (!empty($mis_citas)): ?>
            <p class="sol-sub-label">Próximas entrevistas</p>
            <?php foreach ($mis_citas as $c):
                $fd = new DateTime($c['fecha']);
            ?>
            <div class="sol-perfil-item">
                <div class="sol-perfil-row">
                    <div>
                        <p class="sol-perfil-animal"><?= htmlspecialchars($c['nombre_animal']) ?></p>
                        <p class="sol-perfil-protectora">
                            <?= htmlspecialchars($c['nombre_adoptante'] . ' ' . $c['apellido']) ?>
                            <?= $c['telefono'] ? '· ' . htmlspecialchars($c['telefono']) : '' ?>
                        </p>
                    </div>
                    <div style="display:flex;align-items:center;gap:10px;flex-shrink:0">
                        <div class="sol-perfil-cita" style="text-align:right;flex-direction:column;align-items:flex-end">
                            <strong><?= $fd->format('d/m/Y') ?></strong>
                            <span><?= substr($c['hora_inicio'],0,5) ?> – <?= substr($c['hora_fin'],0,5) ?></span>
                        </div>
                        <form method="POST" style="margin:0">
                            <input type="hidden" name="action"  value="cancelar_cita">
                            <input type="hidden" name="id_cita" value="<?= (int)$c['id_cita'] ?>">
                            <button type="submit" class="sol-perfil-btn-cancelar"
                                    onclick="return confirm('¿Cancelar la entrevista con <?= htmlspecialchars(addslashes($c['nombre_adoptante'])) ?>?')">
                                <i class="zmdi zmdi-close"></i>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>

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
        <p class="modal-msg">Esta acción es irreversible. Desaparecerán todos tus datos.</p>
        <div class="modal-acciones">
            <button type="button" id="modal-cancelar" class="modal-btn modal-btn-cancelar">Cancelar</button>
            <form method="POST" style="margin:0">
                <input type="hidden" name="action" value="eliminar">
                <button type="submit" class="modal-btn modal-btn-confirmar">Sí, eliminar</button>
            </form>
        </div>
    </div>
</div>


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

<script>
document.getElementById('foto-input').addEventListener('change', function(e) {
    const file = e.target.files[0];
    if (!file) return;
    // Previsualizar
    const reader = new FileReader();
    reader.onload = function(ev) {
        document.getElementById('foto-img').src = ev.target.result;
    };
    reader.readAsDataURL(file);
    // Submit directo — el archivo ya está en el input, no hace falta esperar al reader
    document.getElementById('registro').submit();
});
</script>

</body>
</html>
