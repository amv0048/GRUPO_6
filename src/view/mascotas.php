<?php
session_start();
require "../sesion/conexion.php";
require_once "../helpers/media.php";
require_once "../model/AnimalModel.php";
require_once "../model/LikeModel.php";

// ── FILTROS (GET) ─────────────────────────────────────────────
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

// ── PAGINACIÓN ────────────────────────────────────────────────
$por_pagina = 30;
$pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$offset = ($pagina - 1) * $por_pagina;

$animalModel = new AnimalModel($_conexion);
$likeModel   = new LikeModel($_conexion);

$filtros = [
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
];

$total_animales = $animalModel->countDisponibles($filtros);
$total_paginas  = max(1, (int)ceil($total_animales / $por_pagina));
if ($pagina > $total_paginas) {
    $pagina = $total_paginas;
    $offset = ($pagina - 1) * $por_pagina;
}

$animales_arr = $animalModel->getDisponiblesPaginado($filtros, $por_pagina, $offset);

// ── LIKES DEL USUARIO ─────────────────────────────────────────
$liked_ids = [];
if (isset($_SESSION['id']) && isset($_SESSION['user'])) {
    $liked_ids = $likeModel->getLikesByAdoptante((int)$_SESSION['id']);
}

// ── OPCIONES DE FILTRO ────────────────────────────────────────
$filter_opts = $animalModel->getFilterOptions();
$especies = $filter_opts['especies'];
$ciudades = $filter_opts['ciudades'];
$razas    = $filter_opts['razas'];
$colores  = $filter_opts['colores'];

// ── ¿HAY FILTROS ACTIVOS? ─────────────────────────────────────
$hay_filtros = ($especie_filtro || $ciudad_filtro || $raza_filtro || $sexo_filtro || $color_filtro
    || $edad_min !== '' || $edad_max !== '' || $peso_min !== '' || $peso_max !== ''
    || $compat_perros || $compat_gatos || $compat_ninos);

// ── HELPER: construir URL de paginación conservando filtros ───
function build_page_url(int $pagina, array $params): string {
    $base = [
        'especie'       => $params['especie']       ?? '',
        'ciudad'        => $params['ciudad']        ?? '',
        'raza'          => $params['raza']          ?? '',
        'sexo'          => $params['sexo']          ?? '',
        'color'         => $params['color']         ?? '',
        'edad_min'      => $params['edad_min']      ?? '',
        'edad_max'      => $params['edad_max']      ?? '',
        'peso_min'      => $params['peso_min']      ?? '',
        'peso_max'      => $params['peso_max']      ?? '',
        'compat_perros' => !empty($params['compat_perros']) ? '1' : '',
        'compat_gatos'  => !empty($params['compat_gatos'])  ? '1' : '',
        'compat_ninos'  => !empty($params['compat_ninos'])  ? '1' : '',
        'pagina'        => $pagina,
    ];
    // Quita pares vacíos para una URL más limpia.
    $base = array_filter($base, fn($v) => $v !== '' && $v !== null);
    return '/src/view/mascotas.php?' . http_build_query($base);
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mascotas · Go Catch</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,900&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/material-design-iconic-font/2.2.0/css/material-design-iconic-font.min.css">
    <link rel="stylesheet" href="/public/css/header.css">
    <link rel="stylesheet" href="/public/css/index.css">
    <link rel="stylesheet" href="/public/css/mascotas.css">
</head>
<body>

<!-- ═══════════════════════════ HEADER ═══════════════════════════ -->
<header>
    <nav class="hBotones">
        <a class="hBoton" href="/src/view/index.php#protectoras">PROTECTORAS</a>
        <a class="hBoton" href="/src/view/colaboradores.php">COLABORADORES</a>
        <?php
        if (!isset($_SESSION["user"]) && isset($_SESSION["nombre"])) {
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
                <?= htmlspecialchars($_SESSION["nombre"]) ?>
            </a>
            <a href="/src/sesion/logout.php" id="boton-destacado">CERRAR SESION</a>
        <?php else: ?>
            <a class="hBoton" href="/public/registro.html">REGÍSTRATE</a>
            <a href="/public/login.html" id="boton-destacado">INICIA SESIÓN</a>
        <?php endif; ?>
    </nav>
</header>


<!-- ═══════════════════════════ ENCABEZADO DE LA PÁGINA ═══════════════════════════ -->
<section id="mascotas-hero">
    <p class="seccion-etiqueta">Adopta</p>
    <h1 class="mascotas-titulo">Conoce a nuestras mascotas</h1>
    <p class="mascotas-subtitulo">
        Explora todas las mascotas disponibles para adopción y encuentra a tu nuevo compañero.
    </p>
</section>


<!-- ═══════════════════════════ FILTRO ═══════════════════════════ -->
<section id="filtro">
    <form method="GET" action="/src/view/mascotas.php" id="filtro-form">

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
                <?php if ($hay_filtros): ?>
                    <a href="/src/view/mascotas.php" id="filtro-reset">✕ Limpiar</a>
                <?php endif; ?>
            </div>

        </div>

    </form>
</section>


<!-- ═══════════════════════════ RESULTADOS ═══════════════════════════ -->
<section id="mascotas-grid-section">

    <div id="mascotas-resumen">
        <p>
            <strong><?= $total_animales ?></strong>
            mascota<?= $total_animales !== 1 ? 's' : '' ?>
            <?= $hay_filtros ? 'que coinciden con tu búsqueda' : 'disponibles' ?>
            <?php if ($total_paginas > 1): ?>
                · Página <?= $pagina ?> de <?= $total_paginas ?>
            <?php endif; ?>
        </p>
    </div>

    <?php if (empty($animales_arr)): ?>

        <div id="mascotas-vacio">
            <i class="zmdi zmdi-search"></i>
            <p>No hay mascotas que coincidan con los filtros seleccionados.</p>
            <a href="/src/view/mascotas.php">Ver todas las mascotas</a>
        </div>

    <?php else: ?>

        <div id="mascotas-grid">

            <?php foreach ($animales_arr as $anim): ?>
            <a class="mascota-card"
               href="/src/view/ficha-animal.php?id=<?= (int)$anim['id_animal'] ?>">

                <div class="mascota-foto">
                    <?php if (!empty($anim['foto'])): ?>
                        <img src="<?= htmlspecialchars($anim['foto']) ?>"
                             alt="<?= htmlspecialchars($anim['nombre']) ?>"
                             loading="lazy">
                    <?php else: ?>
                        <div class="mascota-foto-placeholder">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80 80">
                                <ellipse cx="40" cy="54" rx="18" ry="15" fill="currentColor"/>
                                <ellipse cx="20" cy="36" rx="9"  ry="11" fill="currentColor"/>
                                <ellipse cx="34" cy="27" rx="9"  ry="11" fill="currentColor"/>
                                <ellipse cx="50" cy="27" rx="9"  ry="11" fill="currentColor"/>
                                <ellipse cx="64" cy="36" rx="9"  ry="11" fill="currentColor"/>
                            </svg>
                        </div>
                    <?php endif; ?>

                    <?php $es_liked = in_array((int)$anim['id_animal'], $liked_ids); ?>
                    <button class="btn-like<?= $es_liked ? ' liked' : '' ?>"
                            data-id="<?= (int)$anim['id_animal'] ?>"
                            type="button"
                            aria-label="Me gusta">
                        <i class="zmdi zmdi-favorite<?= $es_liked ? '' : '-outline' ?>"></i>
                    </button>
                </div>

                <div class="mascota-info">
                    <p class="mascota-nombre"><?= htmlspecialchars($anim['nombre']) ?></p>

                    <p class="mascota-dato">
                        <i class="zmdi zmdi-time"></i>
                        <?= $anim['edad'] !== null && $anim['edad'] !== ''
                            ? (int)$anim['edad'] . ' año' . ((int)$anim['edad'] !== 1 ? 's' : '')
                            : 'Edad desconocida' ?>
                    </p>

                    <?php
                    $ubicacion = trim(implode(', ', array_filter([
                        $anim['localidad'] ?? null,
                        $anim['ciudad']    ?? null,
                    ])));
                    ?>
                    <?php if ($ubicacion !== ''): ?>
                    <p class="mascota-dato">
                        <i class="zmdi zmdi-pin"></i>
                        <?= htmlspecialchars($ubicacion) ?>
                    </p>
                    <?php endif; ?>
                </div>

            </a>
            <?php endforeach; ?>

        </div>

        <!-- ═══════════════════════════ PAGINACIÓN ═══════════════════════════ -->
        <?php if ($total_paginas > 1): ?>
        <nav id="mascotas-paginacion" aria-label="Paginación">

            <?php if ($pagina > 1): ?>
                <a class="pag-btn" href="<?= htmlspecialchars(build_page_url($pagina - 1, $filtros)) ?>">
                    <i class="zmdi zmdi-chevron-left"></i> Anterior
                </a>
            <?php else: ?>
                <span class="pag-btn pag-btn-disabled">
                    <i class="zmdi zmdi-chevron-left"></i> Anterior
                </span>
            <?php endif; ?>

            <?php
            // Ventana de páginas: muestra siempre 1 y total, y un rango alrededor de la actual.
            $rango = 2;
            $mostrar = [];
            for ($i = 1; $i <= $total_paginas; $i++) {
                if ($i === 1 || $i === $total_paginas || abs($i - $pagina) <= $rango) {
                    $mostrar[] = $i;
                }
            }
            $prev = 0;
            foreach ($mostrar as $i):
                if ($prev && $i - $prev > 1): ?>
                    <span class="pag-elipsis">…</span>
                <?php endif;
                if ($i === $pagina): ?>
                    <span class="pag-num pag-num-activa"><?= $i ?></span>
                <?php else: ?>
                    <a class="pag-num" href="<?= htmlspecialchars(build_page_url($i, $filtros)) ?>"><?= $i ?></a>
                <?php endif;
                $prev = $i;
            endforeach; ?>

            <?php if ($pagina < $total_paginas): ?>
                <a class="pag-btn" href="<?= htmlspecialchars(build_page_url($pagina + 1, $filtros)) ?>">
                    Siguiente <i class="zmdi zmdi-chevron-right"></i>
                </a>
            <?php else: ?>
                <span class="pag-btn pag-btn-disabled">
                    Siguiente <i class="zmdi zmdi-chevron-right"></i>
                </span>
            <?php endif; ?>

        </nav>
        <?php endif; ?>

    <?php endif; ?>

</section>


<!-- ═══════════════════════════ SCRIPT LIKE ═══════════════════════════ -->
<?php if (isset($_SESSION['id']) && isset($_SESSION['user'])): ?>
<script>
document.querySelectorAll('.btn-like').forEach(btn => {
    btn.addEventListener('click', function (e) {
        e.preventDefault();
        e.stopPropagation();
        const id = this.dataset.id;
        fetch('/src/controller/like.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'id_animal=' + encodeURIComponent(id)
        })
        .then(r => r.json())
        .then(data => {
            if (data && data.ok) {
                const icon = this.querySelector('i');
                if (data.liked) {
                    this.classList.add('liked');
                    icon.classList.remove('zmdi-favorite-outline');
                    icon.classList.add('zmdi-favorite');
                } else {
                    this.classList.remove('liked');
                    icon.classList.remove('zmdi-favorite');
                    icon.classList.add('zmdi-favorite-outline');
                }
            }
        })
        .catch(() => {});
    });
});
</script>
<?php endif; ?>

</body>
</html>
