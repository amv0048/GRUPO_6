<?php
session_start();
require "../src/sesion/conexion.php";

// Solo protectoras pueden ver esta página
if (!isset($_SESSION['id']) || isset($_SESSION['user'])) {
    header('Location: index.php');
    exit();
}
$id_protectora = (int)$_SESSION['id'];

// ── FILTROS (GET) ──────────────────────────────────────────────
$especie_filtro = isset($_GET['especie']) ? trim($_GET['especie']) : '';
$raza_filtro    = isset($_GET['raza'])    ? trim($_GET['raza'])    : '';
$sexo_filtro    = isset($_GET['sexo'])    ? trim($_GET['sexo'])    : '';
$color_filtro   = isset($_GET['color'])   ? trim($_GET['color'])   : '';
$edad_min       = (isset($_GET['edad_min']) && $_GET['edad_min'] !== '') ? (int)$_GET['edad_min'] : '';
$edad_max       = (isset($_GET['edad_max']) && $_GET['edad_max'] !== '') ? (int)$_GET['edad_max'] : '';
$peso_min       = (isset($_GET['peso_min']) && $_GET['peso_min'] !== '') ? (float)$_GET['peso_min'] : '';
$peso_max       = (isset($_GET['peso_max']) && $_GET['peso_max'] !== '') ? (float)$_GET['peso_max'] : '';
$compat_perros  = !empty($_GET['compat_perros']);
$compat_gatos   = !empty($_GET['compat_gatos']);
$compat_ninos   = !empty($_GET['compat_ninos']);

// ── ANIMALES DE ESTA PROTECTORA ────────────────────────────────
$sql = "SELECT a.id_animal, a.nombre, a.especie, a.raza, a.edad, a.sexo,
               g.ruta AS foto,
               p.nombre_protectora, p.ciudad,
               e.nombre AS estado
        FROM Animales a
        JOIN EstadoAnimal e ON a.id_estado = e.id_estado
        JOIN Protectora p   ON a.id_protectora = p.id_protectora
        LEFT JOIN Galeria g ON a.id_animal = g.id_animal AND g.es_principal = 1
        WHERE a.id_protectora = ?";

$params = [$id_protectora];
$types  = 'i';

if ($especie_filtro !== '') { $sql .= " AND a.especie = ?"; $params[] = $especie_filtro; $types .= 's'; }
if ($raza_filtro !== '')    { $sql .= " AND a.raza = ?";    $params[] = $raza_filtro;    $types .= 's'; }
if ($sexo_filtro !== '')    { $sql .= " AND a.sexo = ?";    $params[] = $sexo_filtro;    $types .= 's'; }
if ($color_filtro !== '')   { $sql .= " AND a.color = ?";   $params[] = $color_filtro;   $types .= 's'; }
if ($edad_min !== '') { $sql .= " AND a.edad >= ?"; $params[] = $edad_min; $types .= 'i'; }
if ($edad_max !== '') { $sql .= " AND a.edad <= ?"; $params[] = $edad_max; $types .= 'i'; }
if ($peso_min !== '') { $sql .= " AND a.peso >= ?"; $params[] = $peso_min; $types .= 'd'; }
if ($peso_max !== '') { $sql .= " AND a.peso <= ?"; $params[] = $peso_max; $types .= 'd'; }
if ($compat_perros) $sql .= " AND a.compatibilidad_perros = 1";
if ($compat_gatos)  $sql .= " AND a.compatibilidad_gatos = 1";
if ($compat_ninos)  $sql .= " AND a.compatibilidad_ninos = 1";
$sql .= " ORDER BY a.fecha_entrada DESC LIMIT 10";

$animales_arr = [];
$stmt = $_conexion->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$res_anim = $stmt->get_result();
if ($res_anim) {
    while ($row = $res_anim->fetch_assoc()) $animales_arr[] = $row;
}

// ── OPCIONES DE FILTRO (solo animales de esta protectora) ──────
$especies = [];
$res_esp = $_conexion->prepare(
    "SELECT DISTINCT especie FROM Animales WHERE id_protectora = ? AND especie IS NOT NULL ORDER BY especie"
);
$res_esp->bind_param('i', $id_protectora);
$res_esp->execute();
$r = $res_esp->get_result();
if ($r) while ($row = $r->fetch_assoc()) $especies[] = $row['especie'];

$razas = [];
$res_raza = $_conexion->prepare(
    "SELECT DISTINCT raza FROM Animales WHERE id_protectora = ? AND raza IS NOT NULL AND raza != '' ORDER BY raza"
);
$res_raza->bind_param('i', $id_protectora);
$res_raza->execute();
$r = $res_raza->get_result();
if ($r) while ($row = $r->fetch_assoc()) $razas[] = $row['raza'];

$colores = [];
$res_col = $_conexion->prepare(
    "SELECT DISTINCT color FROM Animales WHERE id_protectora = ? AND color IS NOT NULL AND color != '' ORDER BY color"
);
$res_col->bind_param('i', $id_protectora);
$res_col->execute();
$r = $res_col->get_result();
if ($r) while ($row = $r->fetch_assoc()) $colores[] = $row['color'];

// ── CONTADORES PARA EL PANEL ───────────────────────────────────
$cnt_pendientes = 0;
$st_cnt = $_conexion->prepare(
    "SELECT COUNT(*) AS n FROM SolicitudAdopcion s
     JOIN Animales a ON s.id_animal = a.id_animal
     WHERE a.id_protectora = ? AND s.estado_solicitud = 'PENDIENTE'"
);
$st_cnt->bind_param("i", $id_protectora);
$st_cnt->execute();
$row_cnt = $st_cnt->get_result()->fetch_assoc();
$cnt_pendientes = (int)($row_cnt['n'] ?? 0);
$st_cnt->close();

// ── PRÓXIMAS CITAS ────────────────────────────────────────────
$proximas_idx = [];
// Solo si las tablas existen
$tbl_check = $_conexion->query("SHOW TABLES LIKE 'CitaEntrevista'");
if ($tbl_check && $tbl_check->num_rows > 0) {
    $pc = $_conexion->prepare(
        "SELECT d.fecha, d.hora_inicio, a.nombre AS nombre_animal,
                s.nombre AS nombre_adoptante, s.apellido
         FROM CitaEntrevista c
         JOIN DisponibilidadProtectora d ON c.id_disponibilidad = d.id_disponibilidad
         JOIN SolicitudAdopcion s ON c.id_solicitud = s.id_solicitud
         JOIN Animales a ON s.id_animal = a.id_animal
         WHERE d.id_protectora = ? AND d.fecha >= CURDATE() AND c.estado != 'CANCELADA'
         ORDER BY d.fecha, d.hora_inicio LIMIT 5"
    );
    $pc->bind_param("i", $id_protectora);
    $pc->execute();
    $proximas_idx = $pc->get_result()->fetch_all(MYSQLI_ASSOC);
    $pc->close();
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Go Catch · Mis animales</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/material-design-iconic-font/2.2.0/css/material-design-iconic-font.min.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/indexProtectora.css">
</head>
<body>

<!-- ══════════════════════════════════════════
     HEADER
══════════════════════════════════════════ -->
<header>
    <nav class="hBotones">
        <a class="hBoton" href="listaAnimal.php">LISTA ANIMAL</a>
    </nav>

    <nav id="header-izq">
        <a href="indexProtectora.php">
            <img src="../img/profile/default/oficiales/logo.svg" alt="Go Catch" height="40">
        </a>
    </nav>

    <nav class="hBotones">
        <a class="hBoton" href="urgente.php">URGENTE</a>
        <a class="hBoton" href="perfil.php">
            <i class="zmdi zmdi-account"></i>
            <?= htmlspecialchars($_SESSION['nombre']) ?>
        </a>
        <a href="../src/sesion/logout.php" id="boton-destacado">CERRAR SESIÓN</a>
    </nav>
</header>


<!-- ══════════════════════════════════════════
     HERO
══════════════════════════════════════════ -->
<section id="hero">
    <div id="hero-content">
        <h1>Bienvenida,<br><span><?= htmlspecialchars($_SESSION['nombre']) ?></span></h1>
        <p>Gestiona y da visibilidad a tus animales</p>
        <div id="hero-cta">
            <a href="#animales" class="cta-btn cta-primary">Ver mis animales</a>
            <a href="solicitudes-protectora.php" class="cta-btn cta-secondary" style="position:relative">
                Solicitudes de adopción
                <?php if ($cnt_pendientes > 0): ?>
                    <span style="position:absolute;top:-8px;right:-8px;background:#CA7842;color:#fff;font-size:10px;font-weight:700;min-width:20px;height:20px;border-radius:10px;display:flex;align-items:center;justify-content:center;padding:0 4px"><?= $cnt_pendientes ?></span>
                <?php endif; ?>
            </a>
            <a href="disponibilidad.php" class="cta-btn cta-secondary">
                <i class="zmdi zmdi-calendar" style="margin-right:6px"></i>Disponibilidad
            </a>
        </div>
    </div>
</section>


<!-- ══════════════════════════════════════════
     OBJETIVOS
══════════════════════════════════════════ -->
<section id="objetivos">
    <p class="seccion-etiqueta">Nuestros objetivos</p>
    <div id="objetivos-grid">
        <div class="objetivo-card">
            <i class="zmdi zmdi-time-restore-setting"></i>
            <p>Dar segundas oportunidades</p>
        </div>
        <div class="objetivo-card">
            <i class="zmdi zmdi-globe-alt"></i>
            <p>Unir corazones</p>
        </div>
        <div class="objetivo-card">
            <i class="zmdi zmdi-home"></i>
            <p>Llevar animales a hogares</p>
        </div>
    </div>
</section>


<!-- ══════════════════════════════════════════
     PANEL DE GESTIÓN
══════════════════════════════════════════ -->
<section id="panel-gestion">
    <div class="panel-gestion-grid">

        <!-- Solicitudes -->
        <a href="solicitudes-protectora.php" class="panel-card panel-card-sol">
            <i class="zmdi zmdi-inbox"></i>
            <div class="panel-card-info">
                <p class="panel-card-titulo">Solicitudes de adopción</p>
                <p class="panel-card-sub">
                    <?php if ($cnt_pendientes > 0): ?>
                        <span class="panel-badge"><?= $cnt_pendientes ?> pendiente<?= $cnt_pendientes > 1 ? 's' : '' ?></span>
                    <?php else: ?>
                        Sin solicitudes nuevas
                    <?php endif; ?>
                </p>
            </div>
            <i class="zmdi zmdi-chevron-right panel-card-arrow"></i>
        </a>

        <!-- Disponibilidad -->
        <a href="disponibilidad.php" class="panel-card panel-card-cal">
            <i class="zmdi zmdi-calendar-alt"></i>
            <div class="panel-card-info">
                <p class="panel-card-titulo">Disponibilidad para entrevistas</p>
                <p class="panel-card-sub">
                    <?php if (!empty($proximas_idx)): ?>
                        <?php $p = $proximas_idx[0]; $fd = new DateTime($p['fecha']); ?>
                        Próxima: <?= $fd->format('d/m') ?> <?= substr($p['hora_inicio'],0,5) ?>
                    <?php else: ?>
                        Gestiona tus horarios disponibles
                    <?php endif; ?>
                </p>
            </div>
            <i class="zmdi zmdi-chevron-right panel-card-arrow"></i>
        </a>

        <?php if (!empty($proximas_idx)): ?>
        <!-- Mini lista de citas -->
        <div class="panel-card panel-card-citas" style="cursor:default">
            <i class="zmdi zmdi-calendar-check"></i>
            <div class="panel-card-info" style="flex:1">
                <p class="panel-card-titulo">Próximas entrevistas</p>
                <?php foreach ($proximas_idx as $c):
                    $fd = new DateTime($c['fecha']);
                ?>
                <p class="panel-card-cita-item">
                    <strong><?= $fd->format('d/m') ?> <?= substr($c['hora_inicio'],0,5) ?></strong>
                    — <?= htmlspecialchars($c['nombre_animal']) ?>
                    · <?= htmlspecialchars($c['nombre_adoptante'] . ' ' . $c['apellido']) ?>
                </p>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>
</section>

<style>
#panel-gestion { padding: 0 60px 48px; }
.panel-gestion-grid { display: flex; flex-direction: column; gap: 10px; }
.panel-card {
    display: flex; align-items: center; gap: 16px;
    background: rgba(255,255,255,.07); border: 1px solid rgba(255,255,255,.12);
    border-radius: 8px; padding: 16px 20px; text-decoration: none;
    transition: background .2s, border-color .2s;
}
.panel-card:hover { background: rgba(255,255,255,.12); border-color: #CA7842; }
.panel-card > .zmdi:first-child { font-size: 22px; color: #CA7842; flex-shrink: 0; }
.panel-card-info { flex: 1; }
.panel-card-titulo { font-size: 13px; font-weight: 700; color: #fff; }
.panel-card-sub    { font-size: 11px; color: #a8b8cc; margin-top: 2px; }
.panel-card-arrow  { font-size: 18px; color: rgba(255,255,255,.3); }
.panel-badge {
    display: inline-block; background: #CA7842; color: #fff;
    font-size: 10px; font-weight: 700; padding: 2px 9px; border-radius: 10px;
}
.panel-card-cita-item { font-size: 11px; color: #a8b8cc; margin-top: 4px; }
.panel-card-cita-item strong { color: #EDA677; }
@media (max-width: 960px) { #panel-gestion { padding: 0 32px 40px; } }
@media (max-width: 640px) { #panel-gestion { padding: 0 20px 32px; } }
</style>


<!-- ══════════════════════════════════════════
     FILTRO
══════════════════════════════════════════ -->
<section id="filtro">
    <form method="GET" action="indexProtectora.php" id="filtro-form">

        <!-- Fila 1: selects -->
        <div class="filtro-fila">

            <div class="filtro-campo">
                <i class="zmdi zmdi-assignment"></i>
                <select name="especie" class="filtro-select">
                    <option value="">Todas las especies</option>
                    <?php foreach ($especies as $esp): ?>
                        <option value="<?= htmlspecialchars($esp) ?>"
                            <?= $especie_filtro === $esp ? 'selected' : '' ?>>
                            <?= htmlspecialchars(ucfirst($esp)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filtro-campo">
                <i class="zmdi zmdi-label"></i>
                <select name="raza" class="filtro-select">
                    <option value="">Todas las razas</option>
                    <?php foreach ($razas as $r): ?>
                        <option value="<?= htmlspecialchars($r) ?>"
                            <?= $raza_filtro === $r ? 'selected' : '' ?>>
                            <?= htmlspecialchars($r) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="filtro-campo">
                <i class="zmdi zmdi-male-female"></i>
                <select name="sexo" class="filtro-select">
                    <option value="">Cualquier sexo</option>
                    <option value="M" <?= $sexo_filtro === 'M' ? 'selected' : '' ?>>Macho</option>
                    <option value="H" <?= $sexo_filtro === 'H' ? 'selected' : '' ?>>Hembra</option>
                </select>
            </div>

            <div class="filtro-campo">
                <i class="zmdi zmdi-palette"></i>
                <select name="color" class="filtro-select">
                    <option value="">Cualquier color</option>
                    <?php foreach ($colores as $col): ?>
                        <option value="<?= htmlspecialchars($col) ?>"
                            <?= $color_filtro === $col ? 'selected' : '' ?>>
                            <?= htmlspecialchars(ucfirst($col)) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

        </div>

        <!-- Fila 2: rangos + compatibilidades + acciones -->
        <div class="filtro-fila">

            <div class="filtro-rango">
                <span class="filtro-rango-label">
                    <i class="zmdi zmdi-time"></i> Edad
                </span>
                <input type="number" name="edad_min" class="filtro-input"
                       placeholder="Mín" min="0" max="30"
                       value="<?= htmlspecialchars($edad_min) ?>">
                <span class="filtro-sep">—</span>
                <input type="number" name="edad_max" class="filtro-input"
                       placeholder="Máx" min="0" max="30"
                       value="<?= htmlspecialchars($edad_max) ?>">
                <span class="filtro-rango-unit">años</span>
            </div>

            <div class="filtro-rango">
                <span class="filtro-rango-label">
                    <i class="zmdi zmdi-balance"></i> Peso
                </span>
                <input type="number" name="peso_min" class="filtro-input"
                       placeholder="Mín" min="0" max="200" step="0.1"
                       value="<?= htmlspecialchars($peso_min) ?>">
                <span class="filtro-sep">—</span>
                <input type="number" name="peso_max" class="filtro-input"
                       placeholder="Máx" min="0" max="200" step="0.1"
                       value="<?= htmlspecialchars($peso_max) ?>">
                <span class="filtro-rango-unit">kg</span>
            </div>

            <div class="filtro-compat">
                <span class="filtro-rango-label">
                    <i class="zmdi zmdi-mood"></i> Compatible con
                </span>
                <label class="filtro-check">
                    <input type="checkbox" name="compat_perros" value="1"
                           <?= $compat_perros ? 'checked' : '' ?>> Perros
                </label>
                <label class="filtro-check">
                    <input type="checkbox" name="compat_gatos" value="1"
                           <?= $compat_gatos ? 'checked' : '' ?>> Gatos
                </label>
                <label class="filtro-check">
                    <input type="checkbox" name="compat_ninos" value="1"
                           <?= $compat_ninos ? 'checked' : '' ?>> Niños
                </label>
            </div>

            <div class="filtro-acciones">
                <button type="submit" id="filtro-btn">
                    <i class="zmdi zmdi-search"></i> Buscar
                </button>
                <?php if ($especie_filtro || $raza_filtro || $sexo_filtro || $color_filtro || $edad_min !== '' || $edad_max !== '' || $peso_min !== '' || $peso_max !== '' || $compat_perros || $compat_gatos || $compat_ninos): ?>
                    <a href="indexProtectora.php" id="filtro-reset">✕ Limpiar</a>
                <?php endif; ?>
            </div>

        </div>

    </form>
</section>


<!-- ══════════════════════════════════════════
     CARRUSEL DE ANIMALES
══════════════════════════════════════════ -->
<section id="animales">

    <div id="carousel-wrapper">

        <button class="carousel-btn" id="prev-btn" type="button" aria-label="Anterior">
            <i class="zmdi zmdi-chevron-left"></i>
        </button>

        <div id="carousel-track-container">
            <div id="carousel-track">

                <?php foreach ($animales_arr as $anim): ?>
                <a class="animal-card"
                   href="ficha-animal.php?id=<?= (int)$anim['id_animal'] ?>">

                    <div class="animal-foto">
                        <?php if (!empty($anim['foto'])): ?>
                            <img src="<?= htmlspecialchars($anim['foto']) ?>"
                                 alt="<?= htmlspecialchars(ucfirst($anim['especie']) . ' ' . $anim['raza']) ?>"
                                 loading="lazy">
                        <?php else: ?>
                            <div class="animal-foto-placeholder">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80 80">
                                    <ellipse cx="40" cy="54" rx="18" ry="15" fill="currentColor"/>
                                    <ellipse cx="20" cy="36" rx="9"  ry="11" fill="currentColor"/>
                                    <ellipse cx="34" cy="27" rx="9"  ry="11" fill="currentColor"/>
                                    <ellipse cx="50" cy="27" rx="9"  ry="11" fill="currentColor"/>
                                    <ellipse cx="64" cy="36" rx="9"  ry="11" fill="currentColor"/>
                                </svg>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($anim['estado'])): ?>
                        <span class="animal-estado estado-<?= strtolower(htmlspecialchars($anim['estado'])) ?>">
                            <?= htmlspecialchars(ucfirst(strtolower($anim['estado']))) ?>
                        </span>
                        <?php endif; ?>
                    </div>

                    <div class="animal-info">
                        <p class="animal-nombre">
                            <?= htmlspecialchars($anim['nombre']) ?>
                        </p>
                        <p class="animal-detalle">
                            <?php
                            $detalle = ucfirst($anim['especie'] ?? '');
                            if ($anim['raza']) $detalle .= ' · ' . $anim['raza'];
                            $edad_txt = $anim['edad']
                                ? $anim['edad'] . ' año' . ($anim['edad'] != 1 ? 's' : '')
                                : 'Edad desconocida';
                            $detalle .= ' · ' . $edad_txt;
                            echo htmlspecialchars($detalle);
                            ?>
                        </p>
                    </div>

                </a>
                <?php endforeach; ?>

            </div><!-- #carousel-track -->
        </div><!-- #carousel-track-container -->

        <button class="carousel-btn" id="next-btn" type="button" aria-label="Siguiente">
            <i class="zmdi zmdi-chevron-right"></i>
        </button>

    </div><!-- #carousel-wrapper -->

    <?php if (empty($animales_arr)): ?>
    <div id="sin-animales">
        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80 80" fill="currentColor">
            <ellipse cx="40" cy="54" rx="18" ry="15"/>
            <ellipse cx="20" cy="36" rx="9"  ry="11"/>
            <ellipse cx="34" cy="27" rx="9"  ry="11"/>
            <ellipse cx="50" cy="27" rx="9"  ry="11"/>
            <ellipse cx="64" cy="36" rx="9"  ry="11"/>
        </svg>
        <p>Aún no has registrado ningún animal.</p>
        <a href="listaAnimal.php">Añadir animal</a>
    </div>
    <?php endif; ?>

</section>


<!-- ══════════════════════════════════════════
     SCRIPTS
══════════════════════════════════════════ -->
<script>
const TIENE_ANIMALES = <?= json_encode(!empty($animales_arr)) ?>;

function initCarousel() {
    const track = document.getElementById('carousel-track');
    if (!track) return;

    const originals = Array.from(track.querySelectorAll('.animal-card'));
    if (!originals.length) return;

    const N     = originals.length;
    const CLONE = Math.min(5, N);

    const fragBefore = document.createDocumentFragment();
    originals.slice(-CLONE).forEach(c => {
        const cl = c.cloneNode(true);
        cl.setAttribute('aria-hidden', 'true');
        fragBefore.appendChild(cl);
    });
    track.insertBefore(fragBefore, track.firstChild);

    const fragAfter = document.createDocumentFragment();
    originals.slice(0, CLONE).forEach(c => {
        const cl = c.cloneNode(true);
        cl.setAttribute('aria-hidden', 'true');
        fragAfter.appendChild(cl);
    });
    track.appendChild(fragAfter);

    let current = CLONE;
    let autoId;

    function cardW() {
        const gap = parseFloat(getComputedStyle(track).gap) || 0;
        return track.children[0].offsetWidth + gap;
    }

    function moveTo(idx, animate = true) {
        current = idx;
        track.style.transition = animate
            ? 'transform 0.45s cubic-bezier(0.4, 0, 0.2, 1)'
            : 'none';
        if (!animate) track.getBoundingClientRect();
        track.style.transform = `translateX(-${current * cardW()}px)`;
    }

    track.addEventListener('transitionend', () => {
        if (current >= CLONE + N) moveTo(current - N, false);
        if (current < CLONE)      moveTo(current + N, false);
    });

    function startAuto() {
        clearInterval(autoId);
        autoId = setInterval(() => moveTo(current + 1), 3800);
    }

    const prevBtn = document.getElementById('prev-btn');
    const nextBtn = document.getElementById('next-btn');
    if (prevBtn) prevBtn.addEventListener('click', () => { moveTo(current - 1); startAuto(); });
    if (nextBtn) nextBtn.addEventListener('click', () => { moveTo(current + 1); startAuto(); });

    window.addEventListener('resize', () => moveTo(current, false));

    moveTo(CLONE, false);
    startAuto();
}

if (TIENE_ANIMALES) initCarousel();
</script>

</body>
</html>
