<?php
session_start();
require "../src/sesion/conexion.php";

// ── FILTROS (GET) ──────────────────────────────────────────────
$especie_filtro = isset($_GET['especie']) ? trim($_GET['especie']) : '';
$ciudad_filtro  = isset($_GET['ciudad'])  ? trim($_GET['ciudad'])  : '';

// ── PROTECTORAS PARA EL MAPA ───────────────────────────────────
$protectoras_arr = [];
$res_prot = $_conexion->query(
    "SELECT id_protectora, nombre_protectora, ciudad, localidad, direccion, telefono, logo
     FROM Protectora ORDER BY nombre_protectora"
);


if ($res_prot) {
    while ($row = $res_prot->fetch_assoc()) {
        $protectoras_arr[] = $row;
    }
}

// ── ANIMALES DISPONIBLES ───────────────────────────────────────
$sql = "SELECT a.id_animal, a.nombre, a.especie, a.raza, a.edad, a.sexo,
               g.ruta AS foto,
               p.nombre_protectora, p.ciudad
        FROM Animales a
        JOIN EstadoAnimal e ON a.id_estado = e.id_estado
        JOIN Protectora p   ON a.id_protectora = p.id_protectora
        LEFT JOIN Galeria g ON a.id_animal = g.id_animal AND g.es_principal = 1
        WHERE e.nombre = 'DISPONIBLE'";

$params = [];
$types  = '';
if ($especie_filtro !== '') {
    $sql .= " AND a.especie = ?";
    $params[] = $especie_filtro;
    $types   .= 's';
}
if ($ciudad_filtro !== '') {
    $sql .= " AND p.ciudad = ?";
    $params[] = $ciudad_filtro;
    $types   .= 's';
}
$sql .= " ORDER BY a.fecha_entrada DESC LIMIT 10";

$animales_arr = []; // FLAG PROBAR ANIMALES 
if ($params) {
    $stmt = $_conexion->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $res_anim = $stmt->get_result();
} else {
    $res_anim = $_conexion->query($sql);
}
if ($res_anim) {
    while ($row = $res_anim->fetch_assoc()) {
        $animales_arr[] = $row;
    }
}

// ── LIKES DEL USUARIO ─────────────────────────────────────────
// TODO ESTO HACE QUE PETE CUANDO HACE LOGIN UN USER, SE CARGA EL INDEX NO SE AUN POQUE 
$liked_ids = [];
if (isset($_SESSION['id']) && isset($_SESSION['user'])) {
    $uid = (int)$_SESSION['id'];
    $res_likes = $_conexion->query(
        "SELECT id_animal FROM Likes WHERE id_adoptante = $uid"
    );
    if ($res_likes) {
        while ($r = $res_likes->fetch_assoc()) $liked_ids[] = (int)$r['id_animal'];
    }
}

// ── OPCIONES DE FILTRO ─────────────────────────────────────────
$especies = [];
$res_esp = $_conexion->query(
    "SELECT DISTINCT especie FROM Animales WHERE especie IS NOT NULL ORDER BY especie"
);
if ($res_esp) {
    while ($row = $res_esp->fetch_assoc()) $especies[] = $row['especie'];
}

$ciudades = [];
$res_ciu = $_conexion->query(
    "SELECT DISTINCT ciudad FROM Protectora WHERE ciudad IS NOT NULL ORDER BY ciudad"
);
if ($res_ciu) {
    while ($row = $res_ciu->fetch_assoc()) $ciudades[] = $row['ciudad'];
}

// ── NOMBRE DE SESIÓN ───────────────────────────────────────────
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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/material-design-iconic-font/2.2.0/css/material-design-iconic-font.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/index.css">
</head>
<body>

<!-- ══════════════════════════════════════════
     HEADER
══════════════════════════════════════════ -->
<header>
    <nav class="hBotones">
        <a class="hBoton" href="#protectoras">PROTECTORAS</a>
        <a class="hBoton" href="">COLABORADORES</a>
        <!--FUMADA MIA -->
        <?php  
        if(!isset($_SESSION["user"]) and isset($_SESSION["nombre"])){
            echo "<a class='hBoton' href='listaAnimal.php'>LISTA ANIMAL</a>";
        }
        ?>
    </nav>

    <nav id="header-izq">
        <a href="index.php">
            <img src="../img/profile/default/oficiales/logo.svg" alt="Go Catch" height="40">
        </a>
    </nav>




    <nav class="hBotones">
        <a class="hBoton" href="#animales">URGENTE</a>
        <?php if (isset($_SESSION['id'])): ?>
            <a class="hBoton" href="perfil.php">
                <i class="zmdi zmdi-account"></i>
                <?= htmlspecialchars($_SESSION["nombre"]) //TODO NOMBRE?>
            </a>
            <a href="../src/sesion/logout.php" id="boton-destacado">CERRAR SESIÓN</a>
        <?php else: ?>
            <a class="hBoton" href="registro.html">REGÍSTRATE</a>
            <a href="login.html" id="boton-destacado">INICIA SESIÓN</a>
        <?php endif; ?>
    </nav>
</header>


<!-- ══════════════════════════════════════════
     HERO
══════════════════════════════════════════ -->
<section id="hero">
    <div id="hero-content">
        <h1>Adopta. Conecta.<br><span>Cambia Una Vida</span></h1>
        <p>Encuentra al nuevo miembro de tu familia</p>
        <div id="hero-cta">
            <a href="#animales" class="cta-btn cta-primary">Ver animales</a>
            <a href="registro.html" class="cta-btn cta-secondary">Únete a nosotros</a>
            <a class="cta-btn cta-primary" href="pdf/BOE-204_Codigo_de_Proteccion_y_Bienestar_Animal.pdf" target="_blank">Ver ley de bienestar animal</a>
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
     FILTRO
══════════════════════════════════════════ -->
<section id="filtro">
    <form method="GET" action="index.php" id="filtro-form">

        <div class="filtro-campo">
            <i class="zmdi zmdi-assignment"></i>
            <select name="especie" class="filtro-select">
                <option value="">Todos los animales</option>
                <?php foreach ($especies as $esp): ?>
                    <option value="<?= htmlspecialchars($esp) ?>"
                        <?= $especie_filtro === $esp ? 'selected' : '' ?>>
                        <?= htmlspecialchars(ucfirst($esp)) ?>
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

        <button type="submit" id="filtro-btn">
            <i class="zmdi zmdi-search"></i> Buscar
        </button>

        <?php if ($especie_filtro || $ciudad_filtro): ?>
            <a href="index.php" id="filtro-reset">✕ Limpiar filtros</a>
        <?php endif; ?>

    </form>
</section>


<!-- ══════════════════════════════════════════
     MAPA
══════════════════════════════════════════ -->
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


<!-- ══════════════════════════════════════════
     PROTECTORAS
══════════════════════════════════════════ -->
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
           href="perfilProtectora.php?id=<?= (int)$p['id_protectora'] ?>">

            <div class="protectora-logo">
                <?php if (!empty($p['logo'])): ?>
                    <img src="<?= htmlspecialchars($p['logo']) ?>"
                         alt="<?= htmlspecialchars($p['nombre_protectora']) ?>">
                <?php endif; ?>
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


<!-- ══════════════════════════════════════════
     SCRIPTS
══════════════════════════════════════════ -->
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
/* ─── DATOS DE PHP ──────────────────────────────────────────── */
const PROTECTORAS = <?= json_encode($protectoras_arr, JSON_UNESCAPED_UNICODE) ?>;

/* ─── MAPA LEAFLET ──────────────────────────────────────────── */
const map = L.map('mapa', { zoomControl: true }).setView([40.4, -3.7], 6);

L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
    attribution: '© <a href="https://openstreetmap.org">OpenStreetMap</a>',
    maxZoom: 18
}).addTo(map);

// Icono personalizado con huella SVG
const pawIcon = L.divIcon({
    html: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80 80"
                width="38" height="38" style="filter:drop-shadow(0 2px 4px rgba(0,0,0,0.4))">
        <ellipse cx="40" cy="54" rx="18" ry="15" fill="#CA7842"/>
        <ellipse cx="20" cy="36" rx="9"  ry="11" fill="#CA7842"/>
        <ellipse cx="34" cy="27" rx="9"  ry="11" fill="#CA7842"/>
        <ellipse cx="50" cy="27" rx="9"  ry="11" fill="#CA7842"/>
        <ellipse cx="64" cy="36" rx="9"  ry="11" fill="#CA7842"/>
    </svg>`,
    className: 'paw-marker',
    iconSize:   [38, 38],
    iconAnchor: [19, 38],
    popupAnchor:[0, -42]
});

// Cola de geocodificación (Nominatim: máx. 1 req/s)
let geoIdx = 0;
const markers = [];

function geocodeNext() {
    if (geoIdx >= PROTECTORAS.length) return;
    const p = PROTECTORAS[geoIdx++];

    // Construir dirección lo más completa posible
    const parts = [p.direccion, p.localidad, p.ciudad, 'España']
        .filter(v => v && v.trim() !== '');
    if (parts.length === 0) { setTimeout(geocodeNext, 200); return; }

    const query = encodeURIComponent(parts.join(', '));
    fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${query}&limit=1`, {
        headers: { 'Accept-Language': 'es' }
    })
    .then(r => r.json())
    .then(data => {
        if (data && data.length > 0) {
            const lat = parseFloat(data[0].lat);
            const lng = parseFloat(data[0].lon);
            const m = L.marker([lat, lng], { icon: pawIcon }).addTo(map);
            m.bindPopup(`
                <div class="map-popup">
                    <strong>${p.nombre_protectora}</strong><br>
                    ${p.direccion  ? p.direccion + '<br>'  : ''}
                    ${p.localidad  ? p.localidad + ', '    : ''}${p.ciudad || ''}<br>
                    ${p.telefono   ? '📞 ' + p.telefono   : ''}
                </div>`);
            markers.push(m);
        }
    })
    .catch(() => {})
    .finally(() => setTimeout(geocodeNext, 1150));
}

if (PROTECTORAS.length > 0) geocodeNext();

/* ─── GEOLOCALIZACIÓN DEL USUARIO ──────────────────────────── */
document.getElementById('btn-localizar').addEventListener('click', () => {
    if (!navigator.geolocation) {
        alert('Tu navegador no soporta geolocalización.');
        return;
    }
    navigator.geolocation.getCurrentPosition(
        pos => {
            map.setView([pos.coords.latitude, pos.coords.longitude], 11);
            L.circle([pos.coords.latitude, pos.coords.longitude], {
                radius: 800,
                color: '#CA7842',
                fillColor: '#CA7842',
                fillOpacity: 0.12,
                weight: 2
            }).addTo(map);
        },
        () => alert('No se pudo obtener tu ubicación.')
    );
});

/* ─── LIKES ─────────────────────────────────────────────────── */
document.addEventListener('click', function(e) {
    const btn = e.target.closest('.btn-like');
    if (!btn) return;
    e.preventDefault();
    e.stopPropagation();

    const id = btn.dataset.id;

    fetch('like.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id_animal=' + encodeURIComponent(id)
    })
    .then(r => r.json())
    .then(data => {
        if (data.error === 'not_logged_in') {
            window.location.href = 'login.html';
            return;
        }
        // Actualiza original y clones con el mismo data-id
        document.querySelectorAll(`.btn-like[data-id="${id}"]`).forEach(b => {
            const icon = b.querySelector('i');
            if (data.liked) {
                b.classList.add('liked');
                icon.className = 'zmdi zmdi-favorite';
            } else {
                b.classList.remove('liked');
                icon.className = 'zmdi zmdi-favorite-outline';
            }
        });
    })
    .catch(() => {});
});

/* ─── CARRUSEL ──────────────────────────────────────────────── */
const TIENE_DB_ANIMALES = <?= json_encode(!empty($animales_arr)) ?>;

function initCarousel() {
    const track = document.getElementById('carousel-track');
    if (!track) return;

    // Tarjetas originales (sin clones previos)
    const originals = Array.from(track.querySelectorAll('.animal-card'));
    if (!originals.length) return;

    const N     = originals.length;        // 10
    const CLONE = Math.min(5, N);          // clones en cada extremo

    // ── Clonar al INICIO: copias de las últimas CLONE tarjetas ──
    const fragBefore = document.createDocumentFragment();
    originals.slice(-CLONE).forEach(c => {
        const cl = c.cloneNode(true);
        cl.setAttribute('aria-hidden', 'true');
        fragBefore.appendChild(cl);
    });
    track.insertBefore(fragBefore, track.firstChild);

    // ── Clonar al FINAL: copias de las primeras CLONE tarjetas ──
    const fragAfter = document.createDocumentFragment();
    originals.slice(0, CLONE).forEach(c => {
        const cl = c.cloneNode(true);
        cl.setAttribute('aria-hidden', 'true');
        fragAfter.appendChild(cl);
    });
    track.appendChild(fragAfter);

    // current apunta al índice en el track completo (con clones)
    let current = CLONE;   // empezar en la primera tarjeta real
    let autoId;

    function cardW() {
        const gap = parseFloat(getComputedStyle(track).gap) || 0;
        return track.children[0].offsetWidth + gap;
    }

    function moveTo(idx, animate = true) {
        current = idx;
        if (animate) {
            track.style.transition = 'transform 0.45s cubic-bezier(0.4, 0, 0.2, 1)';
        } else {
            track.style.transition = 'none';
            track.getBoundingClientRect(); // forzar layout para que transition:none se aplique antes del transform
        }
        track.style.transform = `translateX(-${current * cardW()}px)`;
    }

    // Cuando una transición termina, comprobamos si estamos en zona clonada
    // y saltamos silenciosamente a la zona real equivalente
    track.addEventListener('transitionend', () => {
        if (current >= CLONE + N) moveTo(current - N, false); // pasamos del último → volver al primero real
        if (current < CLONE)      moveTo(current + N, false); // pasamos del primero → ir al último real
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

    moveTo(CLONE, false); // posición inicial sin animación
    startAuto();
}

/* ─── DEMO: imágenes de APIs externas cuando la BD está vacía ── */

// Extrae la raza del perro desde la URL de dog.ceo
// Ej: .../breeds/golden-retriever/... → "Golden Retriever"
function razaDesdeUrl(url) {
    const m = url.match(/breeds\/([^\/]+)\//);
    if (!m) return 'Mestizo';
    return m[1].split('-').map(w => w[0].toUpperCase() + w.slice(1)).join(' ');
}

const CIUDADES_DEMO = ['Sevilla', 'Madrid', 'Barcelona', 'Valencia', 'Málaga', 'Granada', 'Bilbao'];
function ciudadDemo() {
    return CIUDADES_DEMO[Math.floor(Math.random() * CIUDADES_DEMO.length)];
}

function crearTarjeta(url, tipo, raza, ciudad) {
    return `<a class="animal-card" href="#">
        <div class="animal-foto">
            <img src="${url}" alt="${tipo} - ${raza}" loading="lazy">
        </div>
        <div class="animal-info">
            <p class="animal-nombre">${tipo} · ${raza}</p>
            <p class="animal-detalle">Disponible · ${ciudad}</p>
        </div>
    </a>`;
}

if (!TIENE_DB_ANIMALES) {
    // Llamada en paralelo a las dos APIs
    Promise.all([
        fetch('https://dog.ceo/api/breeds/image/random/5').then(r => r.json()),
        fetch('https://api.thecatapi.com/v1/images/search?limit=5').then(r => r.json())
    ])
    .then(([dogs, cats]) => {
        const tarjetas = [];

        // Perros — dog.ceo devuelve {message: [urls]}
        (dogs.message || []).forEach(url => {
            tarjetas.push(crearTarjeta(url, 'Perro', razaDesdeUrl(url), ciudadDemo()));
        });

        // Gatos — thecatapi devuelve [{url, id, ...}]
        (cats || []).forEach(cat => {
            tarjetas.push(crearTarjeta(cat.url, 'Gato', 'Doméstico', ciudadDemo()));
        });

        // Mezclar perros y gatos aleatoriamente
        tarjetas.sort(() => Math.random() - 0.5);

        const track = document.getElementById('carousel-track');
        track.innerHTML = tarjetas.join('');

        initCarousel();
    })
    .catch(() => {
        // Si las APIs fallan, mostrar estado vacío
        document.getElementById('carousel-wrapper').innerHTML = `
            <div id="sin-animales">
                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80 80" fill="currentColor"
                     style="width:56px;height:56px;opacity:.25;margin:0 auto 16px;display:block">
                    <ellipse cx="40" cy="54" rx="18" ry="15"/>
                    <ellipse cx="20" cy="36" rx="9"  ry="11"/>
                    <ellipse cx="34" cy="27" rx="9"  ry="11"/>
                    <ellipse cx="50" cy="27" rx="9"  ry="11"/>
                    <ellipse cx="64" cy="36" rx="9"  ry="11"/>
                </svg>
                <p>No hay animales disponibles en este momento.</p>
                <a href="index.php">Reintentar</a>
            </div>`;
    });
} else {
    // La BD tiene animales: iniciar carrusel directamente
    initCarousel();
}
</script>

</b