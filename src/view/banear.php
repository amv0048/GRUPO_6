<?php
session_start();
require "../sesion/conexion.php";
require_once "../model/UsuarioModel.php";

require "../PHPMailer.php";
require "../SMTP.php";
require "../Exception.php";
require "../config1.php";


use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// ── ACCESO: solo admins ──────────────────────────────────────
if (!isset($_SESSION['user']) || !isset($_SESSION['admin']) || $_SESSION['admin'] != 1) {
    header('Location: /src/view/index.php');
    exit();
}

// ── OBTENER USUARIO A BANEAR ─────────────────────────────────
$id_objetivo  = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_objetivo <= 0) {
    header('Location: /src/view/moderacion.php');
    exit();
}

$usuarioModel = new UsuarioModel($_conexion);
$usuario      = $usuarioModel->getParaBanear($id_objetivo);

if (!$usuario) {
    header('Location: /src/view/moderacion.php?error=noexiste');
    exit();
}
if ($usuario['admin']) {
    header('Location: /src/view/moderacion.php?error=admin');
    exit();
}
if ($usuario['baneado']) {
    header('Location: /src/view/moderacion.php?error=yabaneado');
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
        $usuarioModel->banear($id_objetivo, $motivo_tipo, $motivo_detalle, $id_admin);

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
    <link rel="stylesheet" href="/public/css/header.css">
    <link rel="stylesheet" href="/public/css/perfil.css">
    <link rel="stylesheet" href="/public/css/banear.css">
</head>
<body>

<!-- HEADER -->
<header>
    <nav class="hBotones">
        <a class="hBoton" href="/src/view/index.php">INICIO</a>
        <a class="hBoton" href="/src/view/moderacion.php">MODERACIÓN</a>
    </nav>

    <nav id="header-izq">
        <a href="/src/view/index.php">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200" width="40" height="40" role="img" aria-label="Go Catch"><rect x="0" y="0" width="200" height="200" rx="36" ry="36" fill="#C97041"/><text x="110" y="148" font-family="'Fraunces', serif" font-weight="900" font-size="145" fill="#FFFFFF" text-anchor="middle">gc</text></svg>
        </a>
    </nav>

    <nav class="hBotones">
        <a class="hBoton" href="/src/view/perfil.php">
            <i class="zmdi zmdi-account"></i>
            <?= htmlspecialchars($_SESSION["nombre"]) ?>
        </a>
        <a href="/src/sesion/logout.php" id="boton-destacado">CERRAR SESIÓN</a>
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
                <a href="/src/view/moderacion.php">← Volver a moderación</a>
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
                    <a class="btn-cancelar" href="/src/view/moderacion.php">Cancelar</a>
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
