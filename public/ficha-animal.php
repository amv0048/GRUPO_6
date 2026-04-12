<?php
session_start();
require "../src/sesion/conexion.php";

error_reporting(E_ALL);
ini_set("display_errors", 1);

// ── VALIDAR PARÁMETRO ────────────────────────────────────────
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id_animal = (int) $_GET['id'];

// ── CONSULTA PRINCIPAL ───────────────────────────────────────
$stmt = $_conexion->prepare(
    "SELECT a.id_animal, a.nombre, a.especie, a.raza, a.sexo, a.color,
            a.peso, a.edad, a.fecha_entrada, a.descripcion,
            a.compatibilidad_perros, a.compatibilidad_gatos, a.compatibilidad_ninos,
            e.nombre AS estado,
            p.nombre_protectora, p.ciudad, p.localidad, p.telefono, p.email AS email_protectora, p.logo
     FROM Animales a
     LEFT JOIN EstadoAnimal e ON a.id_estado = e.id_estado
     LEFT JOIN Protectora   p ON a.id_protectora = p.id_protectora
     WHERE a.id_animal = ?"
);
$stmt->bind_param("i", $id_animal);
$stmt->execute();
$animal = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$animal) {
    header("Location: index.php");
    exit();
}

// ── GALERÍA DE FOTOS ─────────────────────────────────────────
$stmt_g = $_conexion->prepare(
    "SELECT ruta, es_principal FROM Galeria WHERE id_animal = ? ORDER BY es_principal DESC, id_foto ASC"
);
$stmt_g->bind_param("i", $id_animal);
$stmt_g->execute();
$fotos = $stmt_g->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt_g->close();

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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/material-design-iconic-font/2.2.0/css/material-design-iconic-font.min.css">
    <link rel="stylesheet" href="css/header.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body {
            font-family: 'Poppins', sans-serif;
            font-size: 13px;
            color: #333;
            margin: 0;
            background: #0D2D51;
            min-height: 100vh;
        }
        p, h1, h2, h3 { margin: 0; }
        a { text-decoration: none; }

        /* ══ LAYOUT ══ */
        #ficha-wrapper {
            max-width: 1100px;
            margin: 0 auto;
            padding: 36px 24px 60px;
        }

        /* ══ BREADCRUMB ══ */
        .breadcrumb {
            font-size: 11px;
            color: #EDA677;
            margin-bottom: 22px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .breadcrumb a { color: #EDA677; transition: color 0.2s; }
        .breadcrumb a:hover { color: #fff; }
        .breadcrumb span { color: #fff; opacity: 0.5; }

        /* ══ GRID PRINCIPAL ══ */
        .ficha-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 28px;
            align-items: start;
        }
        @media (max-width: 760px) {
            .ficha-grid { grid-template-columns: 1fr; }
        }

        /* ══════════════════════════════════════
           COLUMNA IZQUIERDA
        ══════════════════════════════════════ */
        .galeria-col { display: flex; flex-direction: column; gap: 12px; }

        /* ── FOTO GRANDE CON FLECHAS ── */
        .foto-grande-wrapper {
            position: relative;
            width: 100%;
            aspect-ratio: 4/3;
            border-radius: 10px;
            overflow: hidden;
            background: #1a3f6a;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .foto-grande-wrapper img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
            transition: opacity 0.25s;
        }
        .foto-placeholder-svg {
            opacity: 0.18;
            width: 80px;
            height: 80px;
        }

        /* Flechas sobre la foto */
        .foto-flecha {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 38px;
            height: 38px;
            background: rgba(13, 45, 81, 0.72);
            border: none;
            border-radius: 50%;
            color: #fff;
            font-size: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            transition: background 0.2s, opacity 0.2s;
            z-index: 2;
            backdrop-filter: blur(2px);
            padding: 0;
        }
        .foto-flecha:hover:not(:disabled) { background: rgba(202, 120, 66, 0.88); }
        .foto-flecha:disabled {
            opacity: 0.25;
            cursor: default;
        }
        .foto-flecha.prev { left: 10px; }
        .foto-flecha.next { right: 10px; }

        /* Contador de fotos */
        .foto-counter {
            position: absolute;
            bottom: 10px;
            right: 12px;
            background: rgba(0,0,0,0.5);
            color: #fff;
            font-size: 10px;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 10px;
            letter-spacing: 0.5px;
            z-index: 2;
        }

        /* ── CARRUSEL DE MINIATURAS ── */
        .thumbnails-wrapper {
            position: relative;
        }
        .thumbnails-track-outer {
            overflow: hidden;
            width: 100%;
        }
        .thumbnails-track {
            display: flex;
            gap: 8px;
            transition: transform 0.3s ease;
        }
        .thumb {
            width: 72px;
            height: 72px;
            border-radius: 6px;
            overflow: hidden;
            cursor: pointer;
            border: 2.5px solid transparent;
            transition: border-color 0.2s, transform 0.15s;
            flex-shrink: 0;
        }
        .thumb img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }
        .thumb.activo   { border-color: #CA7842; }
        .thumb:hover    { border-color: #EDA677; transform: translateY(-2px); }

        /* Flechas del carrusel de miniaturas */
        .thumb-flecha {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 26px;
            height: 26px;
            background: #0D2D51;
            border: none;
            border-radius: 50%;
            color: #EDA677;
            font-size: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            z-index: 3;
            transition: background 0.2s;
            padding: 0;
        }
        .thumb-flecha:hover:not(:disabled) { background: #CA7842; color: #fff; }
        .thumb-flecha:disabled { opacity: 0.25; cursor: default; }
        .thumb-flecha.prev { left: -14px; }
        .thumb-flecha.next { right: -14px; }

        /* ── COMPATIBILIDADES (columna izquierda) ── */
        .compat-block {
            background: #fff;
            border-radius: 10px;
            padding: 16px 18px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.25);
        }
        .compat-block-titulo {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: #999;
            margin-bottom: 12px;
        }
        .compat-row {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .compat-item {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 7px 14px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 600;
            background: #EAF3DE;
            color: #2e7d12;
        }
        .compat-item i { font-size: 16px; color: #2e7d12; }


        /* ══════════════════════════════════════
           COLUMNA DERECHA
        ══════════════════════════════════════ */
        .info-col {
            background: #fff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.35);
        }

        /* Cabecera azul */
        .info-header {
            background: #0D2D51;
            padding: 24px 28px 20px;
        }
        .info-header-top {
            display: flex;
            align-items: flex-start;
            justify-content: space-between;
            gap: 12px;
        }
        .animal-nombre-grande {
            color: #fff;
            font-size: 24px;
            font-weight: 800;
            letter-spacing: 0.5px;
            line-height: 1.2;
        }
        .animal-especie-raza {
            color: #EDA677;
            font-size: 12px;
            font-weight: 500;
            margin-top: 4px;
        }

        /* Badges */
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            white-space: nowrap;
            flex-shrink: 0;
        }
        .badge-disponible { background: #EAF3DE; color: #2e7d12; }
        .badge-adoptado   { background: #e8f0fe; color: #1a5cd6; }
        .badge-reservado  { background: #FFF8E1; color: #b8860b; }
        .badge-en_acogida { background: #F3E5F5; color: #7b1fa2; }
        .badge-default    { background: #f0f0f0; color: #777; }

        /* Protectora pequeña en cabecera */
        .protectora-mini {
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: 14px;
            color: rgba(255,255,255,0.6);
            font-size: 11px;
        }
        .protectora-mini i { color: #CA7842; font-size: 14px; }
        .protectora-mini span { color: rgba(255,255,255,0.85); font-weight: 500; }

        /* Cuerpo */
        .info-body { padding: 24px 28px; }

        /* ── ATRIBUTOS BÁSICOS ── */
        .seccion-titulo {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: #999;
            margin-bottom: 14px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .seccion-titulo::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #f0f0f0;
        }

        .atributos-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px 20px;
            margin-bottom: 24px;
        }
        .atributo {
            display: flex;
            flex-direction: column;
            gap: 2px;
        }
        .atributo-label {
            font-size: 10px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #aaa;
        }
        .atributo-valor {
            font-size: 13px;
            font-weight: 600;
            color: #333;
        }

        /* ══════════════════════════════════════
           ACORDEÓN
        ══════════════════════════════════════ */
        .accordion {
            border-top: 1px solid #f0f0f0;
        }

        .accordion-item {
            border-bottom: 1px solid #f0f0f0;
        }

        .accordion-trigger {
            width: 100%;
            background: none;
            border: none;
            padding: 14px 0;
            display: flex;
            align-items: center;
            justify-content: space-between;
            cursor: pointer;
            font-family: 'Poppins', sans-serif;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: #777;
            transition: color 0.2s;
            gap: 8px;
        }
        .accordion-trigger:hover { color: #CA7842; }
        .accordion-trigger.abierto { color: #0D2D51; }

        .accordion-trigger-left {
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .accordion-trigger-left i {
            font-size: 15px;
            color: #CA7842;
        }

        .accordion-chevron {
            font-size: 16px;
            transition: transform 0.28s ease;
            color: #ccc;
            flex-shrink: 0;
        }
        .accordion-trigger.abierto .accordion-chevron {
            transform: rotate(180deg);
            color: #CA7842;
        }

        .accordion-panel {
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.35s ease, padding 0.25s ease;
        }
        .accordion-panel.abierto {
            max-height: 600px;
        }

        .accordion-contenido {
            padding-bottom: 18px;
        }

        /* Texto descripción / historia */
        .texto-bloque {
            font-size: 13px;
            color: #555;
            line-height: 1.75;
        }

        /* Coming soon dentro de acordeón */
        .coming-soon-box {
            display: flex;
            align-items: center;
            gap: 14px;
            background: #f9f9f9;
            border: 1.5px dashed #e0e0e0;
            border-radius: 8px;
            padding: 16px 18px;
        }
        .coming-soon-icon {
            width: 38px;
            height: 38px;
            background: #f0f0f0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .coming-soon-icon i { font-size: 17px; color: #ccc; }
        .coming-soon-text p:first-child { font-size: 12px; font-weight: 600; color: #bbb; }
        .coming-soon-text p:last-child  { font-size: 11px; color: #ccc; margin-top: 2px; }

        /* ══════════════════════════════════════
           BOTONES
        ══════════════════════════════════════ */
        .botones-area { padding-top: 20px; }

        .btn-adoptar {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            width: 100%;
            padding: 14px;
            background: #CA7842;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-family: 'Poppins', sans-serif;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 1.2px;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.2s, transform 0.15s;
            margin-bottom: 12px;
        }
        .btn-adoptar:hover {
            background: #b56a32;
            transform: translateY(-1px);
            color: #fff;
        }
        .btn-adoptar.disabled {
            background: #ccc;
            cursor: not-allowed;
            pointer-events: none;
        }
        .btn-adoptar i { font-size: 16px; }

        .btn-contactar {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 11px;
            background: transparent;
            color: #CA7842;
            border: 1.5px solid #CA7842;
            border-radius: 6px;
            font-family: 'Poppins', sans-serif;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 0.8px;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.2s, color 0.2s;
        }
        .btn-contactar:hover { background: #CA7842; color: #fff; }


        /* ══════════════════════════════════════
           TARJETA PROTECTORA
        ══════════════════════════════════════ */
        .protectora-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.35);
            padding: 22px 28px;
            margin-top: 28px;
        }
        .protectora-card-header {
            display: flex;
            align-items: center;
            gap: 14px;
            margin-bottom: 14px;
        }
        .protectora-logo {
            width: 52px;
            height: 52px;
            border-radius: 8px;
            overflow: hidden;
            background: #f0f0f0;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .protectora-logo img { width: 100%; height: 100%; object-fit: cover; }
        .protectora-logo i   { font-size: 24px; color: #ccc; }
        .protectora-info-nombre {
            font-size: 15px;
            font-weight: 700;
            color: #0D2D51;
        }
        .protectora-info-lugar {
            font-size: 11px;
            color: #999;
            margin-top: 3px;
            display: flex;
            align-items: center;
            gap: 4px;
        }
        .protectora-info-lugar i { color: #CA7842; font-size: 13px; }
        .protectora-detalle-row {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 12px;
            color: #666;
            margin-top: 10px;
        }
        .protectora-detalle-row i { color: #CA7842; font-size: 15px; width: 18px; }

        /* ══════════════════════════════════════
           VOLVER
        ══════════════════════════════════════ */
        .volver { margin-top: 28px; text-align: center; }
        .volver a {
            font-size: 12px;
            font-weight: 500;
            color: #EDA677;
            transition: color 0.2s;
        }
        .volver a:hover { color: #fff; }
    </style>
</head>
<body>

<!-- HEADER -->
<header>
    <nav class="hBotones">
        <a class="hBoton" href="#protectoras">PROTECTORAS</a>
        <a class="hBoton" href="">COLABORADORES</a>
    </nav>
    <nav id="header-izq">
        <a href="index.php" target="_self">
            <img src="../img/profile/default/oficiales/logo.svg" alt="Go Catch" height="40">
        </a>
    </nav>
    <nav class="hBotones">
        <a class="hBoton" href="index.php#animales">URGENTE</a>
        <?php if (isset($_SESSION['id'])): ?>
            <a class="hBoton" href="perfil.php">
                <i class="zmdi zmdi-account"></i>
                <?= htmlspecialchars($_SESSION["nombre"] ?? '') ?>
            </a>
            <a href="../src/sesion/logout.php" id="boton-destacado">CERRAR SESIÓN</a>
        <?php else: ?>
            <a class="hBoton" href="registro.html">REGÍSTRATE</a>
            <a href="login.html" id="boton-destacado">INICIA SESIÓN</a>
        <?php endif; ?>
    </nav>
</header>

<div id="ficha-wrapper">

    <!-- BREADCRUMB -->
    <div class="breadcrumb">
        <a href="index.php"><i class="zmdi zmdi-home"></i> Inicio</a>
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
                        <a href="<?= isset($_SESSION['id']) ? '#' : 'login.html' ?>"
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
        <a href="index.php">← Volver al inicio</a>
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
