<?php
session_start();
require "../sesion/conexion.php";
require_once "../model/ColaboradorModel.php";

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: /src/view/colaboradores.php");
    exit();
}

$id               = (int)$_GET['id'];
$colaboradorModel = new ColaboradorModel($_conexion);
$colab            = $colaboradorModel->getById($id);

if (!$colab) {
    header("Location: /src/view/colaboradores.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($colab['nombre']) ?> · Go Catch</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,900&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/material-design-iconic-font/2.2.0/css/material-design-iconic-font.min.css">
    <link rel="stylesheet" href="/public/css/header.css">
    <link rel="stylesheet" href="/public/css/colaboradores.css">
</head>
<body>

<!-- ══════════════════════════════════════════
     HEADER
══════════════════════════════════════════ -->
<header>
    <nav class="hBotones">
        <a class="hBoton" href="/src/view/index.php#protectoras">PROTECTORAS</a>
        <a class="hBoton" href="/src/view/colaboradores.php">COLABORADORES</a>
        <?php if (isset($_SESSION['admin']) && $_SESSION['admin'] == 1): ?>
            <a class="hBoton" href="/src/view/moderacion.php">
                <i class="zmdi zmdi-shield-security"></i> MODERACIÓN
            </a>
        <?php endif; ?>
    </nav>

    <nav id="header-izq">
        <a href="/src/view/index.php" aria-label="Go Catch">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200" width="40" height="40" role="img" aria-label="Go Catch">
                <rect x="0" y="0" width="200" height="200" rx="36" ry="36" fill="#C97041"/>
                <text x="110" y="148"
                      font-family="'Fraunces', serif"
                      font-weight="900"
                      font-size="145"
                      fill="#FFFFFF"
                      text-anchor="middle">gc</text>
            </svg>
        </a>
    </nav>

    <nav class="hBotones">
        <a class="hBoton" href="/src/view/urgente.php">URGENTE</a>
        <?php if (isset($_SESSION['id'])): ?>
            <a class="hBoton" href="/src/view/perfil.php">
                <i class="zmdi zmdi-account"></i>
                <?= htmlspecialchars($_SESSION['nombre']) ?>
            </a>
            <a href="/src/sesion/logout.php" id="boton-destacado">CERRAR SESIÓN</a>
        <?php else: ?>
            <a class="hBoton" href="/public/registro.html">REGÍSTRATE</a>
            <a href="/public/login.html" id="boton-destacado">INICIA SESIÓN</a>
        <?php endif; ?>
    </nav>
</header>


<!-- ══════════════════════════════════════════
     FICHA DEL COLABORADOR
══════════════════════════════════════════ -->
<section id="ficha-colab">

    <div id="ficha-colab-card">

        <div id="ficha-colab-avatar">
            <i class="zmdi zmdi-account"></i>
        </div>

        <div id="ficha-colab-info">

            <div id="ficha-colab-title-row">
                <h1><?= htmlspecialchars($colab['nombre']) ?></h1>
                <?php if ($colab['suscripcion'] === 'premium'): ?>
                <span class="colab-badge colab-badge-premium">
                    <i class="zmdi zmdi-star"></i> Premium
                </span>
                <?php endif; ?>
            </div>

            <?php if ($colab['profesion']): ?>
            <p id="ficha-colab-profesion"><?= htmlspecialchars($colab['profesion']) ?></p>
            <?php endif; ?>

            <div id="ficha-colab-datos">

                <?php if ($colab['ubicacion']): ?>
                <div class="ficha-dato">
                    <i class="zmdi zmdi-pin"></i>
                    <span><?= htmlspecialchars($colab['ubicacion']) ?></span>
                </div>
                <?php endif; ?>

                <?php if ($colab['telefono']): ?>
                <div class="ficha-dato">
                    <i class="zmdi zmdi-phone"></i>
                    <a href="tel:<?= htmlspecialchars($colab['telefono']) ?>">
                        <?= htmlspecialchars($colab['telefono']) ?>
                    </a>
                </div>
                <?php endif; ?>

                <?php if ($colab['web']): ?>
                <div class="ficha-dato">
                    <i class="zmdi zmdi-globe-alt"></i>
                    <a href="<?= htmlspecialchars($colab['web']) ?>" target="_blank" rel="noopener noreferrer">
                        <?= htmlspecialchars($colab['web']) ?>
                    </a>
                </div>
                <?php endif; ?>

            </div>

            <div id="ficha-colab-acciones">
                <?php if ($colab['web']): ?>
                <a href="<?= htmlspecialchars($colab['web']) ?>"
                   target="_blank" rel="noopener noreferrer"
                   class="cta-btn cta-primary">
                    <i class="zmdi zmdi-globe-alt"></i> Visitar su web
                </a>
                <?php endif; ?>

                <?php if ($colab['telefono']): ?>
                <a href="tel:<?= htmlspecialchars($colab['telefono']) ?>"
                   class="cta-btn cta-secondary">
                    <i class="zmdi zmdi-phone"></i> Llamar
                </a>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <div class="ficha-colab-volver">
        <a href="/src/view/colaboradores.php">← Volver a colaboradores</a>
    </div>

</section>

</body>
</html>
