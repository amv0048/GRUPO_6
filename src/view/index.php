<?php
session_start();
require "../sesion/conexion.php";
require_once "../helpers/media.php";
require_once "../model/AnimalModel.php";
require_once "../model/ProtectoraModel.php";
require_once "../model/LikeModel.php";
require_once "../model/CrowdfundingModel.php";
require_once "../model/ColaboradorModel.php";

// â”€â”€ FILTROS (GET) â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
$especie_filtro = isset($_GET['especie']) ? trim($_GET['especie']) : '';
$ciudad_filtro  = isset($_GET['ciudad'])  ? trim($_GET['ciudad'])  : '';
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

$animalModel       = new AnimalModel($_conexion);
$protectoraModel   = new ProtectoraModel($_conexion);
$likeModel         = new LikeModel($_conexion);
$crowdfundingModel  = new CrowdfundingModel($_conexion);
$colaboradorModel   = new ColaboradorModel($_conexion);

// â”€â”€ PROTECTORAS PARA EL MAPA â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
$protectoras_arr = $protectoraModel->getAll();

// â”€â”€ ANIMALES DISPONIBLES â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
$animales_arr = $animalModel->getDisponibles([
    'especie'       => $especie_filtro,
    'ciudad'        => $ciudad_filtro,
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


// â”€â”€ LIKES DEL USUARIO â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
$liked_ids = [];
if (isset($_SESSION['id']) && isset($_SESSION['user'])) {
    $liked_ids = $likeModel->getLikesByAdoptante((int)$_SESSION['id']);
}

// â”€â”€ OPCIONES DE FILTRO â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
$filter_opts = $animalModel->getFilterOptions();
$especies = $filter_opts['especies'];
$ciudades = $filter_opts['ciudades'];
$razas    = $filter_opts['razas'];
$colores  = $filter_opts['colores'];

// â”€â”€ CROWDFUNDING ACTIVO â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
$crowd_casos = $crowdfundingModel->getCasosActivos(6);

// â”€â”€ COLABORADORES DESTACADOS â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
$colaboradores_preview = $colaboradorModel->getDestacados(4);

// â”€â”€ NOMBRE DE SESIÃ“N â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€â”€
$nombre_sesion = '';
if (isset($_SESSION['user']))       $nombre_sesion = $_SESSION['user'];
elseif (isset($_SESSION['protectora'])) $nombre_sesion = $_SESSION['protectora'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Go Catch · Adopta, conecta, cambia una vida</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,900&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/material-design-iconic-font/2.2.0/css/material-design-iconic-font.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="stylesheet" href="/public/css/header.css">
    <link rel="stylesheet" href="/public/css/index.css">
    <link rel="stylesheet" href="/public/css/colaboradores.css">
</head>
<body>

<!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
     HEADER
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
<header>
    <nav class="hBotones">
        <a class="hBoton" href="#protectoras">PROTECTORAS</a>
        <a class="hBoton" href="/src/view/colaboradores.php">COLABORADORES</a>
        <!--FUMADA MIA -->
        <?php
        if(!isset($_SESSION["user"]) and isset($_SESSION["nombre"])){
            echo "<a class='hBoton' href='listaAnimal.php'>LISTA ANIMAL</a>";
        }
        if (isset($_SESSION['admin']) && $_SESSION['admin'] == 1): ?>
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
                <?= htmlspecialchars($_SESSION["nombre"]) //TODO NOMBRE?>
            </a>
            <a href="/src/sesion/logout.php" id="boton-destacado">CERRAR SESION</a>
        <?php else: ?>
            <a class="hBoton" href="/public/registro.html">REGÍSTRATE</a>
            <a href="/public/login.html" id="boton-destacado">INICIA SESIÓN</a>
        <?php endif; ?>
    </nav>
</header>



<section id="hero">
    <div id="hero-content">
        <h1>Adopta. Conecta.<br><span>Cambia Una Vida</span></h1>
        <p>Encuentra al nuevo miembro de tu familia</p>
        <div id="hero-cta">
            <a href="#animales" class="cta-btn cta-primary">Ver animales</a>
            <a href="/public/registro.html" class="cta-btn cta-secondary">Únete a nosotros</a>
            <a class="cta-btn cta-primary" href="/public/pdf/BOE-204_Codigo_de_Proteccion_y_Bienestar_Animal.pdf" target="_blank">Ver ley de bienestar animal</a>
        </div>
    </div>
</section>


<!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
     OBJETIVOS
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
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


<!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
     FILTRO
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
<section id="filtro">
    <form method="GET" action="index.php" id="filtro-form">

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

            <div class="filtro-campo">
                <i class="zmdi zmdi-pin"></i>
                <select name="ciudad" class="filtro-select">
                    <option value="">Cualquier ciudad</option>
                    <?php foreach ($ciudades as $c): ?>
                        <option value="<?= htmlspecialchars($c) ?>"
                            <?= $ciudad_filtro === $c ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c) ?>
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
                <span class="filtro-sep">â€”</span>
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
                <span class="filtro-sep">â€”</span>
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
                <?php if ($especie_filtro || $ciudad_filtro || $raza_filtro || $sexo_filtro || $color_filtro || $edad_min !== '' || $edad_max !== '' || $peso_min !== '' || $peso_max !== '' || $compat_perros || $compat_gatos || $compat_ninos): ?>
                    <a href="/src/view/index.php" id="filtro-reset">âœ• Limpiar</a>
                <?php endif; ?>
            </div>

        </div>

    </form>
</section>


<!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
     MAPA
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
<section id="mapa-section">

    <div id="mapa-wrapper">
        <div id="mapa"></div>
    </div>

    <div id="mapa-label">
        <div id="mapa-label-content">
            <i class="zmdi zmdi-pin"></i>
            <h2>Mascotas<br>Cerca De Ti</h2>
            <p>Activa tu ubicación para ver las protectoras más cercanas a ti</p>
            <button id="btn-localizar" type="button">
                <i class="zmdi zmdi-my-location"></i> Usar mi ubicación
            </button>
        </div>
    </div>

</section>


<!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
     CROWDFUNDING (PÚBLICO)
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
<?php if (!empty($crowd_casos)): ?>
<section id="crowdfunding-public">
    <p class="seccion-etiqueta">Ayuda a cambiar vidas</p>
    <h2 class="seccion-titulo">Casos que necesitan tu apoyo</h2>

    <div class="crowd-pub-grid">
        <?php foreach ($crowd_casos as $caso):
            $pct = $caso['meta_euros'] > 0
                ? min(100, round($caso['recaudado'] / $caso['meta_euros'] * 100))
                : 0;
        ?>
        <div class="crowd-pub-card">
            <?php if (!empty($caso['foto'])): ?>
            <div class="crowd-pub-foto">
                <img src="<?= htmlspecialchars($caso['foto']) ?>" alt="<?= htmlspecialchars($caso['titulo']) ?>">
            </div>
            <?php else: ?>
            <div class="crowd-pub-foto crowd-pub-foto-placeholder">
                <i class="zmdi zmdi-money-box"></i>
            </div>
            <?php endif; ?>
            <div class="crowd-pub-body">
                <p class="crowd-pub-protectora"><?= htmlspecialchars($caso['nombre_protectora']) ?></p>
                <h3 class="crowd-pub-titulo"><?= htmlspecialchars($caso['titulo']) ?></h3>
                <?php if (!empty($caso['animal_nombre'])): ?>
                <p class="crowd-pub-animal">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80 80" width="12" height="12" fill="currentColor" style="vertical-align:middle;margin-right:4px"><ellipse cx="40" cy="54" rx="18" ry="15"/><ellipse cx="20" cy="36" rx="9" ry="11"/><ellipse cx="34" cy="27" rx="9" ry="11"/><ellipse cx="50" cy="27" rx="9" ry="11"/><ellipse cx="64" cy="36" rx="9" ry="11"/></svg>
                    <?= htmlspecialchars($caso['animal_nombre']) ?>
                </p>
                <?php endif; ?>
                <p class="crowd-pub-desc">
                    <?= htmlspecialchars(mb_substr($caso['descripcion'], 0, 120)) ?><?= mb_strlen($caso['descripcion']) > 120 ? 'â€¦' : '' ?>
                </p>
                <div class="crowd-pub-progress">
                    <div class="crowd-pub-bar">
                        <div class="crowd-pub-fill" style="width:<?= $pct ?>%"></div>
                    </div>
                    <div class="crowd-pub-nums">
                        <span><?= number_format($caso['recaudado'], 0, ',', '.') ?> â‚¬</span>
                        <span><?= $pct ?>% de <?= number_format($caso['meta_euros'], 0, ',', '.') ?> â‚¬</span>
                    </div>
                </div>
                <button class="crowd-btn-donar" type="button"
                    data-id="<?= $caso['id_caso'] ?>"
                    data-titulo="<?= htmlspecialchars($caso['titulo'], ENT_QUOTES) ?>">
                    <i class="zmdi zmdi-money"></i> Donar
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>

<!-- Modal Donación -->
<div id="don-modal" class="don-modal-overlay" style="display:none" role="dialog" aria-modal="true">
    <div class="don-modal">
        <div class="don-modal-header">
            <h3>Apoya este caso</h3>
            <button id="don-modal-close" type="button" aria-label="Cerrar">
                <i class="zmdi zmdi-close"></i>
            </button>
        </div>
        <p id="don-modal-titulo" style="color:#EDA677;font-size:13px;margin-bottom:16px;font-weight:600"></p>
        <div class="don-form-group">
            <label>Tu nombre <span style="color:#a8b8cc;font-weight:400">(opcional)</span></label>
            <input type="text" id="don-nombre" placeholder="Anónimo">
        </div>
        <div class="don-form-group">
            <label>Cantidad a donar (â‚¬)</label>
            <div class="don-quick-amounts">
                <button type="button" class="don-quick" data-v="5">5 â‚¬</button>
                <button type="button" class="don-quick" data-v="10">10 â‚¬</button>
                <button type="button" class="don-quick" data-v="25">25 â‚¬</button>
                <button type="button" class="don-quick" data-v="50">50 â‚¬</button>
            </div>
            <input type="number" id="don-cantidad" min="1" step="0.01" placeholder="Otra cantidadâ€¦">
        </div>
        <p class="don-aviso">
            <i class="zmdi zmdi-info-outline"></i>
            La pasarela de pago estará disponible próximamente. Tu intención de donación quedará registrada.
        </p>
        <div class="don-acciones">
            <button type="button" id="don-cancel">Cancelar</button>
            <button type="button" id="don-submit"><i class="zmdi zmdi-money"></i> Confirmar donación</button>
        </div>
        <div id="don-feedback" style="display:none;margin-top:12px;padding:10px 14px;border-radius:6px;font-size:13px"></div>
    </div>
</div>

<?php endif; ?>


<!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
     CARRUSEL DE ANIMALES
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
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

                        <?php
                        $es_liked = in_array((int)$anim['id_animal'], $liked_ids);
                        ?>
                        <button class="btn-like<?= $es_liked ? ' liked' : '' ?>"
                                data-id="<?= (int)$anim['id_animal'] ?>"
                                type="button"
                                aria-label="Me gusta">
                            <i class="zmdi zmdi-favorite<?= $es_liked ? '' : '-outline' ?>"></i>
                        </button>
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
                            if ($anim['ciudad']) $detalle .= ' · ' . $anim['ciudad'];
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

</section>


<!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
     PROTECTORAS
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
<section id="protectoras">
    <p class="seccion-etiqueta">Nuestras protectoras</p>
    <h2 class="seccion-titulo">Organizaciones que confían en nosotros</h2>
    <p class="seccion-subtitulo">
        Trabajamos con protectoras de toda España para encontrar hogar a cada animal
    </p>

    <?php if (!empty($protectoras_arr)): ?>
    <div id="protectoras-grid">
        <?php foreach ($protectoras_arr as $p): ?>
        <a class="protectora-card"
           href="/src/view/perfilProtectora.php?id=<?= (int)$p['id_protectora'] ?>">

            <div class="protectora-logo">
                <?php
                $logo_url = media_normalize_url($p['logo'] ?? null, '/img/profile/default/oficiales/1.jpg');
                ?>
                <img src="<?= htmlspecialchars($logo_url) ?>"
                     alt="<?= htmlspecialchars($p['nombre_protectora']) ?>">
            </div>

            <div class="protectora-info">
                <p class="protectora-nombre"><?= htmlspecialchars($p['nombre_protectora']) ?></p>

                <?php if ($p['ciudad'] || $p['localidad']): ?>
                <p class="protectora-dato">
                    <i class="zmdi zmdi-pin"></i>
                    <?= htmlspecialchars(
                        implode(', ', array_filter([$p['localidad'], $p['ciudad']]))
                    ) ?>
                </p>
                <?php endif; ?>

                <?php if ($p['direccion']): ?>
                <p class="protectora-dato">
                    <i class="zmdi zmdi-home"></i>
                    <?= htmlspecialchars($p['direccion']) ?>
                </p>
                <?php endif; ?>

                <?php if ($p['telefono']): ?>
                <p class="protectora-dato">
                    <i class="zmdi zmdi-phone"></i>
                    <?= htmlspecialchars($p['telefono']) ?>
                </p>
                <?php endif; ?>
            </div>

        </a>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <p class="protectoras-vacio">Aún no hay protectoras registradas.</p>
    <?php endif; ?>

</section>


<!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
     COLABORADORES
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
<?php if (!empty($colaboradores_preview)): ?>
<section id="colaboradores-preview">
    <p class="seccion-etiqueta">Nuestros colaboradores</p>
    <h2 class="seccion-titulo">Profesionales que apoyan la causa</h2>
    <p class="seccion-subtitulo">
        Veterinarios, adiestradores y más profesionales comprometidos con el bienestar animal
    </p>

    <div id="colab-grid-preview">
        <?php foreach ($colaboradores_preview as $c): ?>
        <a class="colab-preview-card"
           href="/src/view/ficha-colaborador.php?id=<?= (int)$c['id_colaborador'] ?>">

            <div class="colab-preview-avatar">
                <?php if (!empty($c['foto'])): ?>
                    <img src="<?= htmlspecialchars($c['foto']) ?>"
                         alt="<?= htmlspecialchars($c['nombre']) ?>"
                         style="width:100%;height:100%;object-fit:cover;border-radius:50%">
                <?php else: ?>
                    <i class="zmdi zmdi-account"></i>
                <?php endif; ?>
            </div>

            <?php if ($c['suscripcion'] === 'premium'): ?>
            <span class="colab-badge colab-badge-premium">
                <i class="zmdi zmdi-star"></i> Premium
            </span>
            <?php endif; ?>

            <p class="colab-preview-nombre"><?= htmlspecialchars($c['nombre']) ?></p>

            <?php if ($c['profesion']): ?>
            <p class="colab-preview-profesion"><?= htmlspecialchars($c['profesion']) ?></p>
            <?php endif; ?>

            <?php if ($c['ubicacion']): ?>
            <p class="colab-preview-dato">
                <i class="zmdi zmdi-pin"></i>
                <?= htmlspecialchars($c['ubicacion']) ?>
            </p>
            <?php endif; ?>

        </a>
        <?php endforeach; ?>
    </div>

    <div class="colaboradores-ver-todos">
        <a href="/src/view/colaboradores.php" class="cta-btn cta-secondary">
            Ver todos los colaboradores &nbsp;<i class="zmdi zmdi-arrow-right"></i>
        </a>
    </div>
</section>
<?php endif; ?>


<!-- â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•
     SCRIPTS
â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â•â• -->
<script id="index-page-data" type="application/json"><?= json_encode([
    'protectoras' => $protectoras_arr,
    'tieneDbAnimales' => !empty($animales_arr),
], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="/public/js/index.js"></script>

</body>
