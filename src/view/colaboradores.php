<?php
session_start();
require "../sesion/conexion.php";
require_once "../model/ColaboradorModel.php";

$colaboradorModel = new ColaboradorModel($_conexion);
$colaboradores    = $colaboradorModel->getAll();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Colaboradores · Go Catch</title>
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
     HERO
══════════════════════════════════════════ -->
<section id="colab-hero">
    <div id="colab-hero-content">
        <h1>Nuestros <span>Colaboradores</span></h1>
        <p>Veterinarios, adiestradores, peluquerías y más profesionales comprometidos<br>con el bienestar animal que puedes encontrar en nuestra red</p>
    </div>
</section>


<!-- ══════════════════════════════════════════
     LISTA DE COLABORADORES
══════════════════════════════════════════ -->
<section id="colab-lista">

    <?php if (!empty($colaboradores)): ?>
    <div id="colab-grid">
        <?php foreach ($colaboradores as $c): ?>
        <a class="colab-card"
           href="/src/view/ficha-colaborador.php?id=<?= (int)$c['id_colaborador'] ?>">

            <div class="colab-avatar">
                <i class="zmdi zmdi-account"></i>
            </div>

            <div class="colab-body">
                <div class="colab-header-row">
                    <p class="colab-nombre"><?= htmlspecialchars($c['nombre']) ?></p>
                    <?php if ($c['suscripcion'] === 'premium'): ?>
                    <span class="colab-badge colab-badge-premium">
                        <i class="zmdi zmdi-star"></i> Premium
                    </span>
                    <?php endif; ?>
                </div>

                <?php if ($c['profesion']): ?>
                <p class="colab-profesion"><?= htmlspecialchars($c['profesion']) ?></p>
                <?php endif; ?>

                <?php if ($c['ubicacion']): ?>
                <p class="colab-dato">
                    <i class="zmdi zmdi-pin"></i>
                    <?= htmlspecialchars($c['ubicacion']) ?>
                </p>
                <?php endif; ?>

                <?php if ($c['telefono']): ?>
                <p class="colab-dato">
                    <i class="zmdi zmdi-phone"></i>
                    <?= htmlspecialchars($c['telefono']) ?>
                </p>
                <?php endif; ?>

                <?php if ($c['web']): ?>
                <p class="colab-dato">
                    <i class="zmdi zmdi-globe-alt"></i>
                    <?= htmlspecialchars($c['web']) ?>
                </p>
                <?php endif; ?>
            </div>

        </a>
        <?php endforeach; ?>
    </div>

    <?php else: ?>
    <div class="colab-vacio">
        <i class="zmdi zmdi-accounts"></i>
        <p>Aún no hay colaboradores registrados.</p>
        <a href="/src/view/index.php" class="colab-volver">← Volver al inicio</a>
    </div>
    <?php endif; ?>

</section>


<div class="colab-footer-link">
    <a href="/src/view/index.php">← Volver al inicio</a>
</div>

</body>
</html>
