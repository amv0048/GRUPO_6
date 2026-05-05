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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($animal['nombre']) ?> · Go Catch</title>
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
        <a class="hBoton" href="">COLABORADORES</a>
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

</body>
</html>
