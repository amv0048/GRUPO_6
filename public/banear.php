<?php
session_start();
require "../src/sesion/conexion.php";

require "../src/PHPMailer.php";
require "../src/SMTP.php";
require "../src/Exception.php";
require "../src/config1.php";


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// ── ACCESO: solo admins ──────────────────────────────────────
if (!isset($_SESSION['user']) || !isset($_SESSION['admin']) || $_SESSION['admin'] != 1) {
    header('Location: index.php');
    exit();
}

// ── OBTENER USUARIO A BANEAR ─────────────────────────────────
$id_objetivo = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_objetivo <= 0) {
    header('Location: moderacion.php');
    exit();
}

// Cargar datos del usuario
$stmt = $_conexion->prepare(
    "SELECT id_adoptante, nombre, apellido, email, admin, baneado FROM Usuario WHERE id_adoptante = ?"
);
$stmt->bind_param("i", $id_objetivo);
$stmt->execute();
$res = $stmt->get_result();
$usuario = $res->fetch_assoc();
$stmt->close();

// Validaciones de seguridad
if (!$usuario) {
    header('Location: moderacion.php?error=noexiste');
    exit();
}
if ($usuario['admin']) {
    header('Location: moderacion.php?error=admin');
    exit();
}
if ($usuario['baneado']) {
    header('Location: moderacion.php?error=yabaneado');
    exit();
}

// ── PROCESAR BANEO (POST) ────────────────────────────────────
$error = '';
$exito = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $motivo_tipo    = trim($_POST['motivo_tipo'] ?? '');
    $motivo_detalle = trim($_POST['motivo_detalle'] ?? '');
    $id_admin       = (int)$_SESSION['id'];

    if ($motivo_tipo === '') {
        $error = 'Selecciona el motivo del baneo.';
    } else {
        // Actualizar baneado = TRUE en Usuario
        $upd = $_conexion->prepare("UPDATE Usuario SET baneado = TRUE WHERE id_adoptante = ?");
        $upd->bind_param("i", $id_objetivo);
        $upd->execute();
        $upd->close();

        // Registrar en historial de baneos
        $ins = $_conexion->prepare(
            "INSERT INTO Baneos (id_adoptante, motivo_tipo, motivo_detalle, id_admin) VALUES (?, ?, ?, ?)"
        );
        $ins->bind_param("issi", $id_objetivo, $motivo_tipo, $motivo_detalle, $id_admin);
        $ins->execute();
        $ins->close();

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = MAIL_USER;
            $mail->Password   = MAIL_PASS;
            $mail->SMTPSecure = 'tls';
            $mail->Port       = 587;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom(MAIL_USER, 'Go Catch');
            $mail->addAddress($usuario['email'], $usuario['nombre']);  // email del baneado
            $mail->Subject = 'Baneo Go Catch';
            $mail->Body    = "Hola {$usuario['nombre']},\n\n"
                    . "Tu cuenta en Go Catch ha sido suspendida por el siguiente motivo:\n\n"
                    . "Motivo: {$motivo_tipo}\n\n"
                    . ($motivo_detalle !== '' ? "Detalle: {$motivo_detalle}\n\n" : '')
                    . "Si crees que esto es un error, contacta con nuestro equipo de soporte.\n\n"
                    . "El equipo de Go Catch";

            $mail->send();
        } catch (Exception $e) {
            // El baneo ya se ejecutó — el email fallando no lo deshace
            // Puedes loguear el error si quieres: error_log($mail->ErrorInfo);
        }





        $exito = true;



    }
}

$motivos = [
    'Lenguaje malsonante',
    'Información falsa',
    'Acoso a otros usuarios',
    'Contenido inapropiado',
    'Spam o publicidad no autorizada',
    'Suplantación de identidad',
    'Incumplimiento de términos de uso',
    'Otro motivo',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Banear usuario · Go Catch</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,900&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/material-design-iconic-font/2.2.0/css/material-design-iconic-font.min.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/perfil.css">
    <style>
        #padre-banear {
            min-height: calc(100vh - 85px);
            background: #0D2D51;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        .ban-card {
            width: 100%;
            max-width: 560px;
            background: #fff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.4);
        }

        /* Cabecera roja */
        .ban-header {
            background: #c62828;
            padding: 28px 36px 22px;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .ban-header h1 {
            color: #fff;
            font-size: 1.2rem;
            font-weight: 700;
        }

        .ban-header h1 i {
            margin-right: 8px;
        }

        .ban-header p {
            color: rgba(255,255,255,0.75);
            font-size: 0.82rem;
        }

        /* Ficha del usuario */
        .ban-user-info {
            background: #fdecea;
            border-left: 4px solid #c62828;
            padding: 14px 20px;
            margin: 24px 36px 0;
            border-radius: 6px;
            display: flex;
            flex-direction: column;
            gap: 2px;
        }

        .ban-user-info strong {
            color: #c62828;
            font-size: 0.95rem;
        }

        .ban-user-info span {
            color: #555;
            font-size: 0.82rem;
        }

        /* Formulario */
        .ban-form {
            padding: 24px 36px 32px;
            display: flex;
            flex-direction: column;
            gap: 18px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .form-group label {
            font-size: 0.82rem;
            font-weight: 600;
            color: #0D2D51;
            letter-spacing: 0.3px;
        }

        .form-group select,
        .form-group textarea {
            padding: 10px 14px;
            border: 2px solid #e0e0e0;
            border-radius: 7px;
            font-family: 'Poppins', sans-serif;
            font-size: 0.85rem;
            color: #333;
            outline: none;
            transition: border-color 0.2s;
            background: #fafafa;
        }

        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #CA7842;
            background: #fff;
        }

        .form-group textarea {
            resize: vertical;
            min-height: 110px;
        }

        /* Error inline */
        .error-msg {
            background: #fdecea;
            border-left: 4px solid #c62828;
            color: #c62828;
            padding: 10px 14px;
            border-radius: 6px;
            font-size: 0.82rem;
            font-weight: 500;
        }

        /* Botones */
        .ban-actions {
            display: flex;
            gap: 12px;
            justify-content: flex-end;
        }

        .btn-cancelar {
            padding: 10px 22px;
            border: 2px solid #ccc;
            border-radius: 7px;
            background: #fff;
            color: #555;
            font-family: 'Poppins', sans-serif;
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: border-color 0.2s, color 0.2s;
        }

        .btn-cancelar:hover {
            border-color: #999;
            color: #333;
        }

        .btn-confirmar-ban {
            padding: 10px 26px;
            background: #c62828;
            color: #fff;
            border: none;
            border-radius: 7px;
            font-family: 'Poppins', sans-serif;
            font-size: 0.85rem;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.2s, transform 0.1s;
        }

        .btn-confirmar-ban:hover {
            background: #b71c1c;
            transform: translateY(-1px);
        }

        /* Estado éxito */
        .ban-exito {
            padding: 36px;
            text-align: center;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 12px;
        }

        .ban-exito i {
            font-size: 3rem;
            color: #c62828;
        }

        .ban-exito h2 {
            font-size: 1.1rem;
            color: #0D2D51;
        }

        .ban-exito p {
            font-size: 0.85rem;
            color: #666;
        }

        .ban-exito a {
            margin-top: 8px;
            display: inline-block;
            padding: 10px 24px;
            background: #0D2D51;
            color: #fff;
            border-radius: 7px;
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
            transition: background 0.2s;
        }

        .ban-exito a:hover {
            background: #CA7842;
        }
    </style>
</head>
<body>

<!-- HEADER -->
<header>
    <nav class="hBotones">
        <a class="hBoton" href="index.php">INICIO</a>
        <a class="hBoton" href="moderacion.php">MODERACIÓN</a>
    </nav>

    <nav id="header-izq">
        <a href="index.php">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200" width="40" height="40" role="img" aria-label="Go Catch"><rect x="0" y="0" width="200" height="200" rx="36" ry="36" fill="#C97041"/><text x="110" y="148" font-family="'Fraunces', serif" font-weight="900" font-size="145" fill="#FFFFFF" text-anchor="middle">gc</text></svg>
        </a>
    </nav>

    <nav class="hBotones">
        <a class="hBoton" href="perfil.php">
            <i class="zmdi zmdi-account"></i>
            <?= htmlspecialchars($_SESSION["nombre"]) ?>
        </a>
        <a href="../src/sesion/logout.php" id="boton-destacado">CERRAR SESIÓN</a>
    </nav>
</header>

<!-- CONTENIDO -->
<div id="padre-banear">
    <div class="ban-card">

        <?php if ($exito): ?>
            <!-- Estado de éxito -->
            <div class="ban-header">
                <h1><i class="zmdi zmdi-block-alt"></i> Baneo ejecutado</h1>
            </div>
            <div class="ban-exito">
                <i class="zmdi zmdi-block-alt"></i>
                <h2>Usuario baneado correctamente</h2>
                <p>
                    <strong><?= htmlspecialchars($usuario['nombre'] . ' ' . ($usuario['apellido'] ?? '')) ?></strong>
                    ha sido baneado y no podrá iniciar sesión ni registrarse de nuevo con este email.
                </p>
                <a href="moderacion.php">← Volver a moderación</a>
            </div>

        <?php else: ?>
            <!-- Formulario de baneo -->
            <div class="ban-header">
                <h1><i class="zmdi zmdi-block-alt"></i> Banear usuario</h1>
                <p>Esta acción impide el acceso permanente del usuario a la plataforma.</p>
            </div>

            <!-- Info del usuario -->
            <div class="ban-user-info">
                <strong><?= htmlspecialchars($usuario['nombre'] . ' ' . ($usuario['apellido'] ?? '')) ?></strong>
                <span><?= htmlspecialchars($usuario['email']) ?></span>
                <span>ID: #<?= (int)$usuario['id_adoptante'] ?></span>
            </div>

            <!-- Formulario -->
            <form class="ban-form" method="POST" action="banear.php?id=<?= (int)$id_objetivo ?>">

                <?php if ($error !== ''): ?>
                    <div class="error-msg"><i class="zmdi zmdi-alert-circle"></i> <?= htmlspecialchars($error) ?></div>
                <?php endif; ?>

                <div class="form-group">
                    <label for="motivo_tipo">Motivo del baneo *</label>
                    <select name="motivo_tipo" id="motivo_tipo" required>
                        <option value="">— Selecciona un motivo —</option>
                        <?php foreach ($motivos as $m): ?>
                            <option value="<?= htmlspecialchars($m) ?>"
                                <?= (($_POST['motivo_tipo'] ?? '') === $m) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($m) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="motivo_detalle">Descripción detallada <span style="color:#999;font-weight:400">(opcional)</span></label>
                    <textarea
                        name="motivo_detalle"
                        id="motivo_detalle"
                        placeholder="Describe con más detalle el motivo del baneo..."
                    ><?= htmlspecialchars($_POST['motivo_detalle'] ?? '') ?></textarea>
                </div>

                <div class="ban-actions">
                    <a class="btn-cancelar" href="moderacion.php">Cancelar</a>
                    <button type="submit" class="btn-confirmar-ban">
                        <i class="zmdi zmdi-block-alt"></i> Confirmar baneo
                    </button>
                </div>

            </form>
        <?php endif; ?>

    </div>
</div>

</body>
</html>
