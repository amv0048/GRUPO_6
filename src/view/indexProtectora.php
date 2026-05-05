<?php
session_start();
require "../sesion/conexion.php";
require_once "../model/AnimalModel.php";
require_once "../model/AdopcionModel.php";
require_once "../model/CrowdfundingModel.php";

// Solo protectoras pueden ver esta página
if (!isset($_SESSION['id']) || isset($_SESSION['user'])) {
    header('Location: /src/view/index.php');
    exit();
}
$id_protectora = (int)$_SESSION['id'];

$animalModel       = new AnimalModel($_conexion);
$adopcionModel     = new AdopcionModel($_conexion);
$crowdfundingModel = new CrowdfundingModel($_conexion);

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
$animales_arr = $animalModel->getByProtectora($id_protectora, [
    'especie'       => $especie_filtro,
    'raza'          => $raza_filtro,
    'sexo'          => $sexo_filtro,
    'color'         => $color_filtro,
    'edad_min'      => $edad_min,
    'edad_max'      => $edad_max,
    'peso_min'      => $peso_min,
    'peso_max'      => $peso_max,
    'compat_perros' => $compat_perros,
    'compat_gatos'  => $compat_gatos,
    'compat_ninos'  => $compat_ninos,
]);

// ── OPCIONES DE FILTRO (solo animales de esta protectora) ──────
$filter_opts = $animalModel->getFilterOptionsByProtectora($id_protectora);
$especies = $filter_opts['especies'];
$razas    = $filter_opts['razas'];
$colores  = $filter_opts['colores'];

// ── CONTADORES PARA EL PANEL ───────────────────────────────────
$cnt_pendientes = $adopcionModel->countPendientesByProtectora($id_protectora);

// ── PRÓXIMAS CITAS ────────────────────────────────────────────
$proximas_idx = $adopcionModel->getProximasCitasByProtectora($id_protectora, 5);

// ── CROWDFUNDING: tablas + CRUD ───────────────────────────────

$crowd_ok    = '';
$crowd_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crowd_action'])) {
    $act = $_POST['crowd_action'];
    if ($act === 'crear' || $act === 'editar') {
        $cf_titulo = trim($_POST['titulo']        ?? '');
        $cf_animal = trim($_POST['animal_nombre'] ?? '');
        $cf_desc   = trim($_POST['descripcion']   ?? '');
        $cf_meta   = (float)($_POST['meta_euros'] ?? 0);
        $cf_foto   = trim($_POST['foto']          ?? '');
        if ($cf_titulo === '' || $cf_desc === '' || $cf_meta <= 0) {
            $crowd_error = 'Completa los campos obligatorios (título, descripción y meta).';
        } elseif ($act === 'crear') {
            $crowdfundingModel->crear($id_protectora, $cf_titulo, $cf_animal, $cf_desc, $cf_meta, $cf_foto)
                ? $crowd_ok = 'Caso publicado correctamente.'
                : $crowd_error = 'Error al guardar.';
        } else {
            $cf_id = (int)($_POST['id_caso'] ?? 0);
            $crowdfundingModel->actualizar($cf_id, $id_protectora, $cf_titulo, $cf_animal, $cf_desc, $cf_meta, $cf_foto)
                ? $crowd_ok = 'Caso actualizado.'
                : $crowd_error = 'Error al actualizar.';
        }
    } elseif ($act === 'eliminar') {
        $cf_id = (int)($_POST['id_caso'] ?? 0);
        $crowdfundingModel->eliminar($cf_id, $id_protectora)
            ? $crowd_ok = 'Caso eliminado.'
            : $crowd_error = 'Error al eliminar.';
    } elseif ($act === 'toggle') {
        $cf_id = (int)($_POST['id_caso'] ?? 0);
        $crowdfundingModel->toggleActivo($cf_id, $id_protectora)
            ? $crowd_ok = 'Estado del caso actualizado.'
            : $crowd_error = 'Error al actualizar.';
    }
}

$casos_arr = $crowdfundingModel->getCasosByProtectora($id_protectora);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Go Catch · Mis animales</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,900&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/material-design-iconic-font/2.2.0/css/material-design-iconic-font.min.css">
    <link rel="stylesheet" href="/public/css/header.css">
    <link rel="stylesheet" href="/public/css/indexProtectora.css">
</head>
<body>

<!-- ══════════════════════════════════════════
     HEADER
══════════════════════════════════════════ -->
<header>
    <nav class="hBotones">
        <a class="hBoton" href="/src/view/listaAnimal.php">LISTA ANIMAL</a>
    </nav>

    <nav id="header-izq">
        <a href="/src/view/indexProtectora.php">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200" width="40" height="40" role="img" aria-label="Go Catch"><rect x="0" y="0" width="200" height="200" rx="36" ry="36" fill="#C97041"/><text x="110" y="148" font-family="'Fraunces', serif" font-weight="900" font-size="145" fill="#FFFFFF" text-anchor="middle">gc</text></svg>
        </a>
    </nav>

    <nav class="hBotones">
        <a class="hBoton" href="/src/view/urgente.php">URGENTE</a>
        <a class="hBoton" href="/src/view/perfil.php">
            <i class="zmdi zmdi-account"></i>
            <?= htmlspecialchars($_SESSION['nombre']) ?>
        </a>
        <a href="/src/sesion/logout.php" id="boton-destacado">CERRAR SESIÓN</a>
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
            <a href="/src/view/solicitudes-protectora.php" class="cta-btn cta-secondary" style="position:relative">
                Solicitudes de adopción
                <?php if ($cnt_pendientes > 0): ?>
                    <span style="position:absolute;top:-8px;right:-8px;background:#CA7842;color:#fff;font-size:10px;font-weight:700;min-width:20px;height:20px;border-radius:10px;display:flex;align-items:center;justify-content:center;padding:0 4px"><?= $cnt_pendientes ?></span>
                <?php endif; ?>
            </a>
            <a href="/src/view/disponibilidad.php" class="cta-btn cta-secondary">
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
        <a href="/src/view/solicitudes-protectora.php" class="panel-card panel-card-sol">
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
        <a href="/src/view/disponibilidad.php" class="panel-card panel-card-cal">
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

        <!-- Crowdfunding -->
        <a href="#crowdfunding" class="panel-card panel-card-crowd">
            <i class="zmdi zmdi-money-box"></i>
            <div class="panel-card-info">
                <p class="panel-card-titulo">Crowdfunding</p>
                <p class="panel-card-sub">
                    <?php $n_casos = count($casos_arr); ?>
                    <?= $n_casos > 0 ? "$n_casos caso" . ($n_casos != 1 ? 's' : '') . " publicado" . ($n_casos != 1 ? 's' : '') : 'Crea tu primer caso' ?>
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
                    <a href="/src/view/indexProtectora.php" id="filtro-reset">✕ Limpiar</a>
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
                   href="/src/view/ficha-animal.php?id=<?= (int)$anim['id_animal'] ?>">

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
        <a href="/src/view/listaAnimal.php">Añadir animal</a>
    </div>
    <?php endif; ?>

</section>


<!-- ══════════════════════════════════════════
     CROWDFUNDING (GESTIÓN PROTECTORA)
══════════════════════════════════════════ -->
<section id="crowdfunding">
    <div class="crowd-header">
        <div>
            <p class="seccion-etiqueta">Recaudación de fondos</p>
            <h2 class="seccion-titulo">Mis casos de Crowdfunding</h2>
        </div>
        <button id="btn-nuevo-crowd" type="button">
            <i class="zmdi zmdi-plus"></i> Nuevo caso
        </button>
    </div>

    <?php if ($crowd_ok): ?>
    <div class="crowd-msg crowd-ok"><i class="zmdi zmdi-check"></i> <?= htmlspecialchars($crowd_ok) ?></div>
    <?php endif; ?>
    <?php if ($crowd_error): ?>
    <div class="crowd-msg crowd-err"><i class="zmdi zmdi-alert-circle"></i> <?= htmlspecialchars($crowd_error) ?></div>
    <?php endif; ?>

    <?php if (empty($casos_arr)): ?>
    <p class="crowd-vacio">Aún no has creado ningún caso de crowdfunding. ¡Pulsa "Nuevo caso" para empezar!</p>
    <?php else: ?>
    <div class="crowd-grid">
        <?php foreach ($casos_arr as $caso):
            $pct = $caso['meta_euros'] > 0
                ? min(100, round($caso['recaudado'] / $caso['meta_euros'] * 100))
                : 0;
        ?>
        <div class="crowd-card <?= $caso['activo'] ? '' : 'crowd-card-inactivo' ?>">
            <?php if (!empty($caso['foto'])): ?>
            <div class="crowd-card-foto">
                <img src="<?= htmlspecialchars($caso['foto']) ?>" alt="">
            </div>
            <?php endif; ?>
            <div class="crowd-card-body">
                <div class="crowd-card-top">
                    <h3><?= htmlspecialchars($caso['titulo']) ?></h3>
                    <?php if ($caso['animal_nombre']): ?>
                    <span class="crowd-animal-tag"><?= htmlspecialchars($caso['animal_nombre']) ?></span>
                    <?php endif; ?>
                    <span class="crowd-estado-tag <?= $caso['activo'] ? 'activo' : 'inactivo' ?>">
                        <?= $caso['activo'] ? 'Activo' : 'Pausado' ?>
                    </span>
                </div>
                <p class="crowd-desc"><?= nl2br(htmlspecialchars($caso['descripcion'])) ?></p>
                <div class="crowd-progress-wrap">
                    <div class="crowd-progress-bar">
                        <div class="crowd-progress-fill" style="width:<?= $pct ?>%"></div>
                    </div>
                    <div class="crowd-progress-info">
                        <span><?= number_format($caso['recaudado'], 2, ',', '.') ?> €</span>
                        <span><?= $pct ?>% de <?= number_format($caso['meta_euros'], 2, ',', '.') ?> €</span>
                    </div>
                </div>
                <div class="crowd-acciones">
                    <button class="crowd-btn-edit" type="button"
                        data-id="<?= $caso['id_caso'] ?>"
                        data-titulo="<?= htmlspecialchars($caso['titulo'], ENT_QUOTES) ?>"
                        data-animal="<?= htmlspecialchars($caso['animal_nombre'] ?? '', ENT_QUOTES) ?>"
                        data-desc="<?= htmlspecialchars($caso['descripcion'], ENT_QUOTES) ?>"
                        data-meta="<?= $caso['meta_euros'] ?>"
                        data-foto="<?= htmlspecialchars($caso['foto'] ?? '', ENT_QUOTES) ?>">
                        <i class="zmdi zmdi-edit"></i> Editar
                    </button>
                    <form method="POST" style="display:inline"
                          onsubmit="return confirm('¿Cambiar el estado de este caso?')">
                        <input type="hidden" name="crowd_action" value="toggle">
                        <input type="hidden" name="id_caso" value="<?= $caso['id_caso'] ?>">
                        <button type="submit" class="crowd-btn-toggle">
                            <i class="zmdi zmdi-<?= $caso['activo'] ? 'pause' : 'play' ?>"></i>
                            <?= $caso['activo'] ? 'Pausar' : 'Activar' ?>
                        </button>
                    </form>
                    <form method="POST" style="display:inline"
                          onsubmit="return confirm('¿Eliminar este caso? Esta acción no se puede deshacer.')">
                        <input type="hidden" name="crowd_action" value="eliminar">
                        <input type="hidden" name="id_caso" value="<?= $caso['id_caso'] ?>">
                        <button type="submit" class="crowd-btn-del">
                            <i class="zmdi zmdi-delete"></i> Eliminar
                        </button>
                    </form>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>


<!-- Modal Crowdfunding -->
<div id="crowd-modal" class="crowd-modal-overlay" style="display:none" role="dialog" aria-modal="true">
    <div class="crowd-modal">
        <div class="crowd-modal-header">
            <h3 id="crowd-modal-title">Nuevo caso de crowdfunding</h3>
            <button id="crowd-modal-close" type="button" aria-label="Cerrar">
                <i class="zmdi zmdi-close"></i>
            </button>
        </div>
        <form method="POST" id="crowd-form">
            <input type="hidden" name="crowd_action" id="crowd-form-action" value="crear">
            <input type="hidden" name="id_caso"      id="crowd-form-id"     value="">

            <div class="crowd-form-group">
                <label>Título del caso *</label>
                <input type="text" name="titulo" id="cf-titulo" required
                       placeholder="Ej: Operación urgente para Luna">
            </div>
            <div class="crowd-form-group">
                <label>Nombre del animal</label>
                <input type="text" name="animal_nombre" id="cf-animal"
                       placeholder="Ej: Luna">
            </div>
            <div class="crowd-form-group">
                <label>Descripción *</label>
                <textarea name="descripcion" id="cf-desc" required rows="4"
                    placeholder="Describe la situación del animal y para qué se usarán los fondos..."></textarea>
            </div>
            <div class="crowd-form-group">
                <label>Meta económica (€) *</label>
                <input type="number" name="meta_euros" id="cf-meta" required
                       min="1" step="0.01" placeholder="Ej: 2000">
            </div>
            <div class="crowd-form-group">
                <label>URL de la foto (opcional)</label>
                <input type="url" name="foto" id="cf-foto"
                       placeholder="https://...">
            </div>
            <div class="crowd-form-actions">
                <button type="button" id="crowd-modal-cancel">Cancelar</button>
                <button type="submit" id="crowd-form-submit">
                    <i class="zmdi zmdi-check"></i> Publicar caso
                </button>
            </div>
        </form>
    </div>
</div>



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

// ── CROWDFUNDING MODAL ────────────────────────────────────────
const crowdModal  = document.getElementById('crowd-modal');
const crowdAction = document.getElementById('crowd-form-action');
const crowdFormId = document.getElementById('crowd-form-id');
const crowdMTitle = document.getElementById('crowd-modal-title');
const crowdSubmit = document.getElementById('crowd-form-submit');

function openCrowdModal(mode, data) {
    crowdModal.style.display = 'flex';
    document.getElementById('crowd-form').reset();
    if (mode === 'crear') {
        crowdAction.value = 'crear';
        crowdMTitle.textContent = 'Nuevo caso de crowdfunding';
        crowdSubmit.innerHTML = '<i class="zmdi zmdi-check"></i> Publicar caso';
        crowdFormId.value = '';
    } else {
        crowdAction.value = 'editar';
        crowdMTitle.textContent = 'Editar caso';
        crowdSubmit.innerHTML = '<i class="zmdi zmdi-floppy"></i> Guardar cambios';
        crowdFormId.value          = data.id;
        document.getElementById('cf-titulo').value = data.titulo;
        document.getElementById('cf-animal').value = data.animal;
        document.getElementById('cf-desc').value   = data.desc;
        document.getElementById('cf-meta').value   = data.meta;
        document.getElementById('cf-foto').value   = data.foto;
    }
}

document.getElementById('btn-nuevo-crowd')
    .addEventListener('click', () => openCrowdModal('crear'));

document.querySelectorAll('.crowd-btn-edit').forEach(btn => {
    btn.addEventListener('click', () => openCrowdModal('editar', {
        id:     btn.dataset.id,
        titulo: btn.dataset.titulo,
        animal: btn.dataset.animal,
        desc:   btn.dataset.desc,
        meta:   btn.dataset.meta,
        foto:   btn.dataset.foto,
    }));
});

document.getElementById('crowd-modal-close')
    .addEventListener('click', () => crowdModal.style.display = 'none');
document.getElementById('crowd-modal-cancel')
    .addEventListener('click', () => crowdModal.style.display = 'none');
crowdModal.addEventListener('click', e => {
    if (e.target === crowdModal) crowdModal.style.display = 'none';
});
</script>

</body>
</html>
