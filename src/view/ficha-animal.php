<?php
session_start();
require "../sesion/conexion.php";
require_once "../model/AnimalModel.php";

// ── VALIDAR PARÁMETRO ────────────────────────────────────────
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: /src/view/index.php");
    exit();
}

$id_animal   = (int) $_GET['id'];
$animalModel = new AnimalModel($_conexion);

$animal = $animalModel->getById($id_animal);
if (!$animal) {
    header("Location: /src/view/index.php");
    exit();
}

$fotos = $animalModel->getGaleria($id_animal);

$total_fotos = count($fotos);

// ── DATOS PROCESADOS ─────────────────────────────────────────
$sexo_map  = ['M' => 'Macho', 'H' => 'Hembra'];
$sexo_txt  = $sexo_map[$animal['sexo']] ?? '—';
$edad_txt  = $animal['edad'] !== null ? $animal['edad'] . ' año' . ($animal['edad'] != 1 ? 's' : '') : 'Desconocida';
$peso_txt  = $animal['peso'] !== null ? number_format($animal['peso'], 2) . ' kg' : 'Desconocido';
$estado    = $animal['estado'] ?? '';
$badge_map = [
    'DISPONIBLE' => ['clase' => 'badge-disponible', 'icono' => 'zmdi-check-circle'],
    'ADOPTADO'   => ['clase' => 'badge-adoptado',   'icono' => 'zmdi-home'],
    'RESERVADO'  => ['clase' => 'badge-reservado',  'icono' => 'zmdi-time'],
    'EN_ACOGIDA' => ['clase' => 'badge-en_acogida', 'icono' => 'zmdi-accounts'],
];
$badge = $badge_map[$estado] ?? ['clase' => 'badge-default', 'icono' => 'zmdi-help'];

$fecha_txt = '—';
if (!empty($animal['fecha_entrada'])) {
    $d = DateTime::createFromFormat('Y-m-d', $animal['fecha_entrada']);
    if ($d) $fecha_txt = $d->format('d/m/Y');
}

// ── COMPATIBILIDADES: solo las que aplican ───────────────────
$compats = [];
if ($animal['compatibilidad_ninos'])  $compats[] = ['icono' => 'zmdi-mood',  'texto' => 'Niños'];
if ($animal['compatibilidad_perros']) $compats[] = ['icono' => 'zmdi-paw',   'texto' => 'Perros'];
if ($animal['compatibilidad_gatos'])  $compats[] = ['icono' => 'zmdi-toys',  'texto' => 'Gatos'];
$tiene_compats = !empty($compats);

// ── CAMPOS FUTUROS (pendientes de BBDD) ─────────────────────
// Cuando se añadan a la tabla, leer de $animal['historia'] y $animal['necesidades_especiales']
$historia           = null; // $animal['historia'] ?? null;
$necesidades        = null; // $animal['necesidades_especiales'] ?? null;
$tiene_necesidades  = !empty($necesidades);

// ── URL PARA COMPARTIR ───────────────────────────────────────
$share_scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
$share_host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
$share_url    = $share_scheme . '://' . $share_host . '/src/view/ficha-animal.php?id=' . $id_animal;
$share_title  = $animal['nombre'] . ' busca un hogar · Go Catch';
$share_desc   = trim(
    ucfirst($animal['especie'] ?? 'Animal')
    . (!empty($animal['raza']) ? ' · ' . $animal['raza'] : '')
    . ' · ' . $edad_txt
    . (!empty($animal['ciudad']) ? ' · ' . $animal['ciudad'] : '')
);
$share_img    = $total_fotos > 0 ? $fotos[0]['ruta'] : '';

if ($share_img !== '' && preg_match('#^https?://#i', $share_img)) {
    $share_img_abs = $share_img;
} elseif ($share_img !== '') {
    $share_img_abs = $share_scheme . '://' . $share_host . '/' . ltrim($share_img, '/');
} else {
    $share_img_abs = '';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($animal['nombre']) ?> · Go Catch</title>
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= htmlspecialchars($share_title) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($share_desc) ?>">
    <meta property="og:url" content="<?= htmlspecialchars($share_url) ?>">
    <meta property="og:site_name" content="Go Catch">
    <?php if ($share_img_abs !== ''): ?>
    <meta property="og:image" content="<?= htmlspecialchars($share_img_abs) ?>">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:image" content="<?= htmlspecialchars($share_img_abs) ?>">
    <?php else: ?>
    <meta name="twitter:card" content="summary">
    <?php endif; ?>
    <meta name="twitter:title" content="<?= htmlspecialchars($share_title) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($share_desc) ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,900&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/material-design-iconic-font/2.2.0/css/material-design-iconic-font.min.css">
    <link rel="stylesheet" href="/public/css/header.css">
    <link rel="stylesheet" href="/public/css/ficha-animal.css">
</head>
<body>

<!-- HEADER -->
<header>
    <nav class="hBotones">
        <a class="hBoton" href="#protectoras">PROTECTORAS</a>
        <a class="hBoton" href="/src/view/colaboradores.php">COLABORADORES</a>
    </nav>
    <nav id="header-izq">
        <a href="/src/view/index.php" target="_self">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200" width="40" height="40" role="img" aria-label="Go Catch"><rect x="0" y="0" width="200" height="200" rx="36" ry="36" fill="#C97041"/><text x="110" y="148" font-family="'Fraunces', serif" font-weight="900" font-size="145" fill="#FFFFFF" text-anchor="middle">gc</text></svg>
        </a>
    </nav>
    <nav class="hBotones">
        <a class="hBoton" href="/src/view/urgente.php">URGENTE</a>
        <?php if (isset($_SESSION['id'])): ?>
            <a class="hBoton" href="/src/view/perfil.php">
                <i class="zmdi zmdi-account"></i>
                <?= htmlspecialchars($_SESSION["nombre"] ?? '') ?>
            </a>
            <a href="/src/sesion/logout.php" id="boton-destacado">CERRAR SESIÓN</a>
        <?php else: ?>
            <a class="hBoton" href="/public/registro.html">REGÍSTRATE</a>
            <a href="/public/login.html" id="boton-destacado">INICIA SESIÓN</a>
        <?php endif; ?>
    </nav>
</header>

<div id="ficha-wrapper">

    <!-- BREADCRUMB -->
    <div class="breadcrumb">
        <a href="/src/view/index.php"><i class="zmdi zmdi-home"></i> Inicio</a>
        <span>/</span>
        <a href="index.php#animales">Animales</a>
        <span>/</span>
        <?= htmlspecialchars($animal['nombre']) ?>
    </div>

    <!-- GRID PRINCIPAL -->
    <div class="ficha-grid">

        <!-- ══ COLUMNA IZQUIERDA ══ -->
        <div class="galeria-col">

            <!-- Foto grande con flechas -->
            <div class="foto-grande-wrapper">

                <?php if ($total_fotos > 0): ?>
                    <img src="<?= htmlspecialchars($fotos[0]['ruta']) ?>"
                         alt="<?= htmlspecialchars($animal['nombre']) ?>"
                         id="img-principal">

                    <!-- Flecha anterior -->
                    <button class="foto-flecha prev" id="btn-prev"
                            <?= $total_fotos <= 1 ? 'disabled' : '' ?>
                            aria-label="Foto anterior">
                        <i class="zmdi zmdi-chevron-left"></i>
                    </button>

                    <!-- Flecha siguiente -->
                    <button class="foto-flecha next" id="btn-next"
                            <?= $total_fotos <= 1 ? 'disabled' : '' ?>
                            aria-label="Foto siguiente">
                        <i class="zmdi zmdi-chevron-right"></i>
                    </button>

                    <?php if ($total_fotos > 1): ?>
                    <span class="foto-counter" id="foto-counter">1 / <?= $total_fotos ?></span>
                    <?php endif; ?>

                <?php else: ?>
                    <svg class="foto-placeholder-svg" xmlns="http://www.w3.org/2000/svg"
                         viewBox="0 0 80 80" fill="#fff">
                        <ellipse cx="40" cy="54" rx="18" ry="15"/>
                        <ellipse cx="20" cy="36" rx="9"  ry="11"/>
                        <ellipse cx="34" cy="27" rx="9"  ry="11"/>
                        <ellipse cx="50" cy="27" rx="9"  ry="11"/>
                        <ellipse cx="64" cy="36" rx="9"  ry="11"/>
                    </svg>
                <?php endif; ?>
            </div>

            <!-- Carrusel de miniaturas -->
            <?php if ($total_fotos > 1): ?>
            <div class="thumbnails-wrapper">
                <button class="thumb-flecha prev" id="thumb-prev" disabled aria-label="Anterior">
                    <i class="zmdi zmdi-chevron-left"></i>
                </button>

                <div class="thumbnails-track-outer" id="thumbs-outer">
                    <div class="thumbnails-track" id="thumbs-track">
                        <?php foreach ($fotos as $i => $f): ?>
                            <div class="thumb <?= $i === 0 ? 'activo' : '' ?>"
                                 data-index="<?= $i ?>"
                                 data-src="<?= htmlspecialchars($f['ruta']) ?>">
                                <img src="<?= htmlspecialchars($f['ruta']) ?>"
                                     alt="Foto <?= $i + 1 ?>">
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <button class="thumb-flecha next" id="thumb-next" aria-label="Siguiente">
                    <i class="zmdi zmdi-chevron-right"></i>
                </button>
            </div>
            <?php endif; ?>

            <!-- Compatibilidades (solo si hay alguna) -->
            <?php if ($tiene_compats): ?>
            <div class="compat-block">
                <p class="compat-block-titulo">Compatible con</p>
                <div class="compat-row">
                    <?php foreach ($compats as $c): ?>
                        <span class="compat-item">
                            <i class="zmdi <?= $c['icono'] ?>"></i>
                            <?= $c['texto'] ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

        </div>
        <!-- fin columna izquierda -->


        <!-- ══ COLUMNA DERECHA ══ -->
        <div class="info-col">

            <!-- Cabecera azul -->
            <div class="info-header">
                <div class="info-header-top">
                    <div>
                        <h1 class="animal-nombre-grande">
                            <?= htmlspecialchars($animal['nombre']) ?>
                        </h1>
                        <p class="animal-especie-raza">
                            <?= htmlspecialchars(
                                ucfirst($animal['especie'] ?? 'Animal') .
                                (!empty($animal['raza']) ? ' · ' . $animal['raza'] : '')
                            ) ?>
                        </p>
                    </div>
                    <span class="badge <?= $badge['clase'] ?>">
                        <i class="zmdi <?= $badge['icono'] ?>"></i>
                        <?= htmlspecialchars($estado) ?>
                    </span>
                </div>
                <?php if ($animal['nombre_protectora']): ?>
                <div class="protectora-mini">
                    <i class="zmdi zmdi-pin"></i>
                    <span><?= htmlspecialchars($animal['nombre_protectora']) ?></span>
                    <?php if ($animal['ciudad']): ?>
                        · <?= htmlspecialchars($animal['ciudad']) ?>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Cuerpo -->
            <div class="info-body">

                <!-- ─ DATOS BÁSICOS (siempre visibles) ─ -->
                <p class="seccion-titulo">Características</p>
                <div class="atributos-grid">
                    <div class="atributo">
                        <span class="atributo-label">Sexo</span>
                        <span class="atributo-valor"><?= htmlspecialchars($sexo_txt) ?></span>
                    </div>
                    <div class="atributo">
                        <span class="atributo-label">Edad</span>
                        <span class="atributo-valor"><?= htmlspecialchars($edad_txt) ?></span>
                    </div>
                    <div class="atributo">
                        <span class="atributo-label">Peso</span>
                        <span class="atributo-valor"><?= htmlspecialchars($peso_txt) ?></span>
                    </div>
                    <div class="atributo">
                        <span class="atributo-label">Color</span>
                        <span class="atributo-valor">
                            <?= htmlspecialchars(!empty($animal['color']) ? ucfirst($animal['color']) : '—') ?>
                        </span>
                    </div>
                    <div class="atributo">
                        <span class="atributo-label">Raza</span>
                        <span class="atributo-valor">
                            <?= htmlspecialchars(!empty($animal['raza']) ? $animal['raza'] : '—') ?>
                        </span>
                    </div>
                    <div class="atributo">
                        <span class="atributo-label">En protectora desde</span>
                        <span class="atributo-valor"><?= htmlspecialchars($fecha_txt) ?></span>
                    </div>
                </div>

                <!-- ─ ACORDEÓN ─ -->
                <div class="accordion">

                    <!-- Descripción -->
                    <?php if (!empty($animal['descripcion'])): ?>
                    <div class="accordion-item">
                        <button class="accordion-trigger" data-target="panel-desc">
                            <span class="accordion-trigger-left">
                                <i class="zmdi zmdi-comment-text"></i>
                                Descripción
                            </span>
                            <i class="zmdi zmdi-chevron-down accordion-chevron"></i>
                        </button>
                        <div class="accordion-panel" id="panel-desc">
                            <div class="accordion-contenido">
                                <p class="texto-bloque">
                                    <?= nl2br(htmlspecialchars($animal['descripcion'])) ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Historia -->
                    <div class="accordion-item">
                        <button class="accordion-trigger" data-target="panel-historia">
                            <span class="accordion-trigger-left">
                                <i class="zmdi zmdi-book"></i>
                                Historia
                            </span>
                            <i class="zmdi zmdi-chevron-down accordion-chevron"></i>
                        </button>
                        <div class="accordion-panel" id="panel-historia">
                            <div class="accordion-contenido">
                                <?php if (!empty($historia)): ?>
                                    <p class="texto-bloque"><?= nl2br(htmlspecialchars($historia)) ?></p>
                                <?php else: ?>
                                    <div class="coming-soon-box">
                                        <div class="coming-soon-icon"><i class="zmdi zmdi-book"></i></div>
                                        <div class="coming-soon-text">
                                            <p>Próximamente disponible</p>
                                            <p>La historia de <?= htmlspecialchars($animal['nombre']) ?> estará disponible en breve.</p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Necesidades especiales: solo si hay datos -->
                    <?php if ($tiene_necesidades): ?>
                    <div class="accordion-item">
                        <button class="accordion-trigger" data-target="panel-necesidades">
                            <span class="accordion-trigger-left">
                                <i class="zmdi zmdi-star"></i>
                                Necesidades especiales
                            </span>
                            <i class="zmdi zmdi-chevron-down accordion-chevron"></i>
                        </button>
                        <div class="accordion-panel" id="panel-necesidades">
                            <div class="accordion-contenido">
                                <p class="texto-bloque"><?= nl2br(htmlspecialchars($necesidades)) ?></p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                </div>
                <!-- fin acordeón -->

                <!-- ─ BOTONES ─ -->
                <div class="botones-area">
                    <?php if ($estado === 'DISPONIBLE'): ?>
                        <a href="<?= isset($_SESSION['id']) && isset($_SESSION['user'])
                                        ? "solicitud-adopcion.php?id=$id_animal"
                                        : 'login.html?redirect=' . urlencode("solicitud-adopcion.php?id=$id_animal") ?>"
                           class="btn-adoptar">
                            <i class="zmdi zmdi-home"></i>
                            QUIERO ADOPTARLO
                        </a>
                    <?php else: ?>
                        <span class="btn-adoptar disabled">
                            <i class="zmdi zmdi-close-circle"></i>
                            NO DISPONIBLE PARA ADOPCIÓN
                        </span>
                    <?php endif; ?>

                    <?php if (!empty($animal['email_protectora']) || !empty($animal['telefono'])): ?>
                        <a href="<?= !empty($animal['email_protectora'])
                                        ? 'mailto:' . htmlspecialchars($animal['email_protectora'])
                                        : 'tel:'    . htmlspecialchars($animal['telefono']) ?>"
                           class="btn-contactar">
                            <i class="zmdi zmdi-email"></i>
                            CONTACTAR CON LA PROTECTORA
                        </a>
                    <?php endif; ?>

                    <button type="button" class="btn-compartir" id="btn-compartir">
                        <i class="zmdi zmdi-share"></i>
                        COMPARTIR
                    </button>
                </div>

            </div><!-- .info-body -->
        </div><!-- .info-col -->

    </div><!-- .ficha-grid -->


    <!-- ══ TARJETA PROTECTORA ══ -->
    <?php if ($animal['nombre_protectora']): ?>
    <div class="protectora-card">
        <p class="seccion-titulo" style="margin-bottom:18px">Protectora responsable</p>
        <div class="protectora-card-header">
            <div class="protectora-logo">
                <?php if (!empty($animal['logo'])): ?>
                    <img src="<?= htmlspecialchars($animal['logo']) ?>"
                         alt="<?= htmlspecialchars($animal['nombre_protectora']) ?>">
                <?php else: ?>
                    <i class="zmdi zmdi-shield-check"></i>
                <?php endif; ?>
            </div>
            <div>
                <p class="protectora-info-nombre">
                    <?= htmlspecialchars($animal['nombre_protectora']) ?>
                </p>
                <?php if ($animal['ciudad'] || $animal['localidad']): ?>
                <p class="protectora-info-lugar">
                    <i class="zmdi zmdi-pin"></i>
                    <?= htmlspecialchars(
                        implode(', ', array_filter([$animal['localidad'], $animal['ciudad']]))
                    ) ?>
                </p>
                <?php endif; ?>
            </div>
        </div>
        <?php if (!empty($animal['telefono'])): ?>
        <div class="protectora-detalle-row">
            <i class="zmdi zmdi-phone"></i>
            <span><?= htmlspecialchars($animal['telefono']) ?></span>
        </div>
        <?php endif; ?>
        <?php if (!empty($animal['email_protectora'])): ?>
        <div class="protectora-detalle-row">
            <i class="zmdi zmdi-email"></i>
            <span><?= htmlspecialchars($animal['email_protectora']) ?></span>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- VOLVER -->
    <div class="volver">

        <?php
        if(isset($_SESSION["nombre"]) and !isset($_SESSION["user"])) {
            echo "<a style='color: #a6ff00' href='listaAnimal.php'>Volver a la Lista de Animales</a>";
            echo "<br>";
            echo "<br>";
        }
        ?>
        <a href="/src/view/index.php">← Volver al inicio</a>
    </div>

</div><!-- #ficha-wrapper -->


<!-- ══ MODAL COMPARTIR ══════════════════════════════════════ -->
<div class="share-modal" id="share-modal">
    <div class="share-modal-backdrop" onclick="cerrarCompartir()"></div>
    <div class="share-modal-dialog">

        <button class="share-modal-cerrar" onclick="cerrarCompartir()" aria-label="Cerrar">
            <i class="zmdi zmdi-close"></i>
        </button>

        <p class="share-modal-titulo">Comparte a <?= htmlspecialchars($animal['nombre']) ?></p>
        <p class="share-modal-sub">Ayúdale a encontrar un hogar difundiéndolo en tus redes</p>

        <div class="share-redes" id="share-redes-main">
            <a class="share-red whatsapp" id="link-whatsapp" href="#" target="_blank" rel="noopener noreferrer">
                <i class="zmdi zmdi-whatsapp"></i>
                <span>WhatsApp</span>
            </a>
            <a class="share-red facebook" id="link-facebook" href="#" target="_blank" rel="noopener noreferrer">
                <i class="zmdi zmdi-facebook"></i>
                <span>Facebook</span>
            </a>
            <a class="share-red twitter" id="link-twitter" href="#" target="_blank" rel="noopener noreferrer">
                <i class="zmdi zmdi-twitter"></i>
                <span>X / Twitter</span>
            </a>
            <a class="share-red telegram" id="link-telegram" href="#" target="_blank" rel="noopener noreferrer">
                <i class="zmdi zmdi-airplane-alt"></i>
                <span>Telegram</span>
            </a>
            <a class="share-red email" id="link-email" href="#">
                <i class="zmdi zmdi-email"></i>
                <span>Email</span>
            </a>
            <button class="share-red copiar" onclick="copiarEnlace()">
                <i class="zmdi zmdi-link"></i>
                <span>Copiar</span>
            </button>
            <button class="share-red stories" onclick="mostrarStories()">
                <i class="zmdi zmdi-collection-image"></i>
                <span>Stories</span>
            </button>
        </div>

        <!-- Sección Stories (oculta por defecto) -->
        <div class="share-stories-section" id="stories-section" style="display:none">
            <div class="share-stories-preview-wrap">
                <canvas id="stories-canvas" style="display:block;width:100%;height:100%"></canvas>
                <div class="share-stories-loading" id="stories-loading">
                    <i class="zmdi zmdi-spinner zmdi-spin"></i>&nbsp; Generando imagen…
                </div>
            </div>
            <div class="share-stories-acciones">
                <a id="btn-descargar-story" class="btn-stories-descargar" href="#"
                   download="<?= htmlspecialchars($animal['nombre']) ?>-gocatch.png">
                    <i class="zmdi zmdi-download"></i> Descargar imagen
                </a>
                <button class="btn-stories-volver" onclick="volverDesdeStories()">
                    ← Volver
                </button>
            </div>
            <p class="share-stories-ayuda">
                Descarga esta imagen y compártela en tus Stories de Instagram, WhatsApp o Facebook para dar más visibilidad a <strong><?= htmlspecialchars($animal['nombre']) ?></strong>.
            </p>
        </div>

        <div class="share-toast" id="share-toast">¡Enlace copiado! ✓</div>

    </div>
</div>
<!-- ════════════════════════════════════════════════════════ -->

<script id="share-data" type="application/json"><?= json_encode([
    'url' => $share_url,
    'title' => $share_title,
    'description' => $share_desc,
    'name' => $animal['nombre'],
    'species' => $animal['especie'] ?? '',
    'status' => $estado,
    'photo' => $share_img,
], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) ?></script>


<script>
/* ══════════════════════════════════════════════════
   GALERÍA — fotos con flechas + carrusel miniaturas
══════════════════════════════════════════════════ */
(function () {
    const FOTOS = <?= json_encode(array_column($fotos, 'ruta')) ?>;
    const TOTAL = FOTOS.length;
    if (TOTAL === 0) return;

    let current = 0;

    const imgPrincipal  = document.getElementById('img-principal');
    const btnPrev       = document.getElementById('btn-prev');
    const btnNext       = document.getElementById('btn-next');
    const counter       = document.getElementById('foto-counter');
    const thumbsTrack   = document.getElementById('thumbs-track');
    const thumbPrev     = document.getElementById('thumb-prev');
    const thumbNext     = document.getElementById('thumb-next');
    const thumbsOuter   = document.getElementById('thumbs-outer');

    // Anchura visible del carrusel de miniaturas (nº de thumbs que caben)
    const THUMB_W   = 72 + 8; // width + gap
    let thumbOffset = 0;      // cuántos thumbs hemos desplazado

    function visibleThumbs() {
        if (!thumbsOuter) return 4;
        return Math.floor(thumbsOuter.offsetWidth / THUMB_W);
    }

    /* ── Actualizar foto grande ── */
    function irA(idx) {
        current = idx;

        // Fade suave
        imgPrincipal.style.opacity = '0';
        setTimeout(() => {
            imgPrincipal.src = FOTOS[current];
            imgPrincipal.style.opacity = '1';
        }, 150);

        // Contador
        if (counter) counter.textContent = (current + 1) + ' / ' + TOTAL;

        // Flechas principales
        if (btnPrev) btnPrev.disabled = (current === 0);
        if (btnNext) btnNext.disabled = (current === TOTAL - 1);

        // Miniatura activa
        if (thumbsTrack) {
            thumbsTrack.querySelectorAll('.thumb').forEach((t, i) => {
                t.classList.toggle('activo', i === current);
            });
            // Si la miniatura activa no es visible, desplazar el carrusel
            const vis = visibleThumbs();
            if (current < thumbOffset) {
                thumbOffset = current;
                moverThumbs();
            } else if (current >= thumbOffset + vis) {
                thumbOffset = current - vis + 1;
                moverThumbs();
            }
        }
    }

    /* ── Mover carrusel de miniaturas ── */
    function moverThumbs() {
        if (!thumbsTrack) return;
        thumbsTrack.style.transform = `translateX(-${thumbOffset * THUMB_W}px)`;
        const vis = visibleThumbs();
        if (thumbPrev) thumbPrev.disabled = (thumbOffset === 0);
        if (thumbNext) thumbNext.disabled = (thumbOffset + vis >= TOTAL);
    }

    /* ── Eventos flechas foto grande ── */
    if (btnPrev) btnPrev.addEventListener('click', () => { if (current > 0) irA(current - 1); });
    if (btnNext) btnNext.addEventListener('click', () => { if (current < TOTAL - 1) irA(current + 1); });

    /* ── Eventos flechas carrusel miniaturas ── */
    if (thumbPrev) thumbPrev.addEventListener('click', () => {
        if (thumbOffset > 0) { thumbOffset--; moverThumbs(); }
    });
    if (thumbNext) thumbNext.addEventListener('click', () => {
        const vis = visibleThumbs();
        if (thumbOffset + vis < TOTAL) { thumbOffset++; moverThumbs(); }
    });

    /* ── Clic en miniatura ── */
    if (thumbsTrack) {
        thumbsTrack.querySelectorAll('.thumb').forEach((thumb, i) => {
            thumb.addEventListener('click', () => irA(i));
        });
    }

    /* ── Estado inicial ── */
    if (btnPrev) btnPrev.disabled = true; // empieza en la primera
    moverThumbs();
})();


/* ══════════════════════════════════════════════════
   ACORDEÓN
══════════════════════════════════════════════════ */
document.querySelectorAll('.accordion-trigger').forEach(btn => {
    btn.addEventListener('click', function () {
        const panelId = this.dataset.target;
        const panel   = document.getElementById(panelId);
        if (!panel) return;

        const estaAbierto = panel.classList.contains('abierto');

        // Cerrar todos
        document.querySelectorAll('.accordion-panel').forEach(p => p.classList.remove('abierto'));
        document.querySelectorAll('.accordion-trigger').forEach(b => b.classList.remove('abierto'));

        // Abrir el pulsado si estaba cerrado
        if (!estaAbierto) {
            panel.classList.add('abierto');
            this.classList.add('abierto');
        }
    });
});
</script>


<script>
/* ══════════════════════════════════════════════════
   COMPARTIR — modal + redes + stories
══════════════════════════════════════════════════ */
(function () {

    const URL_PAG = <?= json_encode($share_url) ?>;
    const NOMBRE  = <?= json_encode($animal['nombre']) ?>;
    const ESPECIE = <?= json_encode($animal['especie'] ?? '') ?>;
    const ESTADO  = <?= json_encode($estado) ?>;
    const FOTO    = <?= json_encode($total_fotos > 0 ? $fotos[0]['ruta'] : '') ?>;
    const TEXTO   = `¡${NOMBRE} necesita un hogar! ${ESPECIE ? '(' + ESPECIE + ')' : ''} 🐾 Encuéntralo en Go Catch`;

    /* ── Rellenar hrefs al abrir ── */
    function rellenarLinks() {
        const u   = encodeURIComponent(URL_PAG);
        const txt = encodeURIComponent(TEXTO);

        document.getElementById('link-whatsapp').href = `https://wa.me/?text=${encodeURIComponent(TEXTO + '\n\n' + URL_PAG)}`;
        document.getElementById('link-facebook').href = `https://www.facebook.com/sharer/sharer.php?u=${u}`;
        document.getElementById('link-twitter').href  = `https://twitter.com/intent/tweet?text=${txt}&url=${u}`;
        document.getElementById('link-telegram').href = `https://t.me/share/url?url=${u}&text=${txt}`;
        document.getElementById('link-email').href    =
            `mailto:?subject=${encodeURIComponent('¡Adopta a ' + NOMBRE + '! - Go Catch')}` +
            `&body=${encodeURIComponent(TEXTO + '\n\n' + URL_PAG)}`;
    }

    /* ── Abrir / cerrar modal ── */
    window.abrirCompartir = function () {
        rellenarLinks();
        document.getElementById('share-modal').classList.add('abierto');
        document.body.style.overflow = 'hidden';
    };

    window.cerrarCompartir = function () {
        document.getElementById('share-modal').classList.remove('abierto');
        document.body.style.overflow = '';
        volverDesdeStories();
    };

    document.addEventListener('keydown', e => { if (e.key === 'Escape') cerrarCompartir(); });

    /* ── Conectar el botón al modal ── */
    document.getElementById('btn-compartir')
        ?.addEventListener('click', abrirCompartir);

    /* ── Copiar enlace ── */
    window.copiarEnlace = function () {
        const ok = () => mostrarToast('¡Enlace copiado! ✓');
        if (navigator.clipboard) {
            navigator.clipboard.writeText(URL_PAG).then(ok).catch(fallbackCopy);
        } else {
            fallbackCopy();
        }
    };

    function fallbackCopy() {
        const el = document.createElement('input');
        el.value = URL_PAG;
        document.body.appendChild(el);
        el.select();
        document.execCommand('copy');
        document.body.removeChild(el);
        mostrarToast('¡Enlace copiado! ✓');
    }

    function mostrarToast(msg) {
        const t = document.getElementById('share-toast');
        t.textContent = msg;
        t.classList.add('visible');
        setTimeout(() => t.classList.remove('visible'), 2400);
    }

    /* ── Stories ── */
    window.mostrarStories = function () {
        document.getElementById('share-redes-main').style.display = 'none';
        document.getElementById('stories-section').style.display  = 'block';
        document.getElementById('stories-loading').style.display  = 'flex';
        dibujarStory();
    };

    window.volverDesdeStories = function () {
        const r = document.getElementById('share-redes-main');
        const s = document.getElementById('stories-section');
        if (r) r.style.display = 'grid';
        if (s) s.style.display = 'none';
        const l = document.getElementById('stories-loading');
        if (l) l.style.display = 'flex';
    };

    /* ── Polyfill roundRect ── */
    if (!CanvasRenderingContext2D.prototype.roundRect) {
        CanvasRenderingContext2D.prototype.roundRect = function (x, y, w, h, r) {
            this.beginPath();
            this.moveTo(x + r, y);
            this.lineTo(x + w - r, y);
            this.quadraticCurveTo(x + w, y, x + w, y + r);
            this.lineTo(x + w, y + h - r);
            this.quadraticCurveTo(x + w, y + h, x + w - r, y + h);
            this.lineTo(x + r, y + h);
            this.quadraticCurveTo(x, y + h, x, y + h - r);
            this.lineTo(x, y + r);
            this.quadraticCurveTo(x, y, x + r, y);
            this.closePath();
        };
    }

    function dibujarStory() {
        const canvas  = document.getElementById('stories-canvas');
        const loading = document.getElementById('stories-loading');
        const btnDl   = document.getElementById('btn-descargar-story');
        const ctx     = canvas.getContext('2d');

        canvas.width  = 540;
        canvas.height = 960;

        function render(fotoImg) {
            /* fondo navy */
            ctx.fillStyle = '#0D2D51';
            ctx.fillRect(0, 0, 540, 960);

            /* gradiente decorativo terracota (esquina superior izquierda) */
            const gDec = ctx.createRadialGradient(0, 0, 0, 0, 0, 320);
            gDec.addColorStop(0, 'rgba(202,120,66,0.45)');
            gDec.addColorStop(1, 'rgba(202,120,66,0)');
            ctx.fillStyle = gDec;
            ctx.fillRect(0, 0, 540, 960);

            /* círculo decorativo (esquina inferior derecha) */
            ctx.beginPath();
            ctx.arc(500, 900, 200, 0, Math.PI * 2);
            ctx.fillStyle = 'rgba(18,64,118,0.55)';
            ctx.fill();

            if (fotoImg) {
                /* foto centrada en la zona superior */
                const zoneY = 110, zoneH = 500;
                const ratio = Math.max(540 / fotoImg.width, zoneH / fotoImg.height);
                const dw    = fotoImg.width  * ratio;
                const dh    = fotoImg.height * ratio;
                ctx.save();
                ctx.beginPath();
                ctx.rect(0, zoneY, 540, zoneH);
                ctx.clip();
                ctx.drawImage(fotoImg, (540 - dw) / 2, zoneY + (zoneH - dh) / 2, dw, dh);
                ctx.restore();

                /* gradiente de fusión foto → fondo */
                const gFade = ctx.createLinearGradient(0, zoneY + zoneH * 0.5, 0, zoneY + zoneH);
                gFade.addColorStop(0, 'rgba(13,45,81,0)');
                gFade.addColorStop(1, 'rgba(13,45,81,1)');
                ctx.fillStyle = gFade;
                ctx.fillRect(0, zoneY + zoneH * 0.5, 540, zoneH * 0.5);
            } else {
                /* pata decorativa cuando no hay foto */
                ctx.font      = '240px serif';
                ctx.textAlign = 'center';
                ctx.fillStyle = 'rgba(202,120,66,0.15)';
                ctx.fillText('🐾', 270, 490);
            }

            ctx.textAlign = 'center';

            /* nombre del animal */
            ctx.font      = 'bold 62px Georgia, serif';
            ctx.fillStyle = '#fff';
            ctx.shadowColor = 'rgba(0,0,0,0.4)';
            ctx.shadowBlur  = 8;
            ctx.fillText(NOMBRE, 270, fotoImg ? 680 : 540);
            ctx.shadowBlur = 0;

            /* especie */
            if (ESPECIE) {
                ctx.font      = '600 26px Arial, sans-serif';
                ctx.fillStyle = '#EDA677';
                ctx.fillText(ESPECIE.toUpperCase(), 270, fotoImg ? 718 : 580);
            }

            /* badge estado */
            const bY  = fotoImg ? 762 : 626;
            const bTx = ESTADO || 'DISPONIBLE';
            ctx.font = 'bold 17px Arial, sans-serif';
            const bW = ctx.measureText(bTx).width + 40;
            ctx.fillStyle = '#1a7a3a';
            ctx.beginPath();
            ctx.roundRect(270 - bW / 2, bY - 26, bW, 36, 18);
            ctx.fill();
            ctx.fillStyle = '#fff';
            ctx.fillText(bTx, 270, bY);

            /* separador */
            ctx.strokeStyle = 'rgba(202,120,66,0.4)';
            ctx.lineWidth   = 1;
            ctx.beginPath();
            ctx.moveTo(80, 860);
            ctx.lineTo(460, 860);
            ctx.stroke();

            /* logo Go Catch */
            ctx.font      = 'bold 28px Georgia, serif';
            ctx.fillStyle = '#fff';
            ctx.fillText('Go Catch', 270, 896);

            ctx.font      = '600 14px Arial, sans-serif';
            ctx.fillStyle = 'rgba(237,166,119,0.9)';
            ctx.fillText('Adopción responsable', 270, 918);

            ctx.font      = '13px Arial, sans-serif';
            ctx.fillStyle = 'rgba(255,255,255,0.38)';
            ctx.fillText('¡Comparte y ayuda a este animal a encontrar un hogar!', 270, 944);

            loading.style.display = 'none';
            btnDl.href = canvas.toDataURL('image/png');
        }

        if (FOTO) {
            const img       = new Image();
            img.crossOrigin = 'anonymous';
            img.onload      = () => render(img);
            img.onerror     = () => render(null);
            img.src         = FOTO;
        } else {
            render(null);
        }
    }

})();
</script>

</body>
</html>
