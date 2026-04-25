<?php
session_start();
require "../src/sesion/conexion.php";

// Solo protectoras
if (!isset($_SESSION['id']) || isset($_SESSION['user'])) {
    header('Location: index.php');
    exit();
}
$id_protectora = (int)$_SESSION['id'];

// ── ACCIÓN: cambiar estado de solicitud ───────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion'], $_POST['id_solicitud'])) {
    $accion      = $_POST['accion'];
    $id_sol      = (int)$_POST['id_solicitud'];
    $estado_map  = ['aprobar' => 'APROBADA', 'rechazar' => 'RECHAZADA', 'pendiente' => 'PENDIENTE'];
    if (isset($estado_map[$accion])) {
        $nuevo = $estado_map[$accion];
        // Verificar que la solicitud pertenece a un animal de esta protectora
        $chk = $_conexion->prepare(
            "SELECT s.id_solicitud FROM SolicitudAdopcion s
             JOIN Animales a ON s.id_animal = a.id_animal
             WHERE s.id_solicitud = ? AND a.id_protectora = ?"
        );
        $chk->bind_param("ii", $id_sol, $id_protectora);
        $chk->execute();
        if ($chk->get_result()->num_rows > 0) {
            $upd = $_conexion->prepare("UPDATE SolicitudAdopcion SET estado_solicitud = ? WHERE id_solicitud = ?");
            $upd->bind_param("si", $nuevo, $id_sol);
            $upd->execute();
            $upd->close();
        }
        $chk->close();
    }
    header("Location: solicitudes-protectora.php" . (isset($_GET['estado']) ? '?estado=' . urlencode($_GET['estado']) : ''));
    exit();
}

// ── FILTRO DE ESTADO ──────────────────────────────────────────
$estado_filtro = $_GET['estado'] ?? 'PENDIENTE';
$estados_validos = ['PENDIENTE', 'APROBADA', 'RECHAZADA', 'TODAS'];
if (!in_array($estado_filtro, $estados_validos)) $estado_filtro = 'PENDIENTE';

// ── OBTENER SOLICITUDES ───────────────────────────────────────
$sql = "SELECT s.*, a.nombre AS nombre_animal, a.especie, a.raza,
               (SELECT g.ruta FROM Galeria g WHERE g.id_animal = a.id_animal ORDER BY g.es_principal DESC LIMIT 1) AS foto_animal,
               c.id_cita, c.estado AS estado_cita,
               d.fecha AS fecha_cita, d.hora_inicio AS hora_cita
        FROM SolicitudAdopcion s
        JOIN Animales a ON s.id_animal = a.id_animal
        LEFT JOIN CitaEntrevista c ON c.id_solicitud = s.id_solicitud
        LEFT JOIN DisponibilidadProtectora d ON c.id_disponibilidad = d.id_disponibilidad
        WHERE a.id_protectora = ?";
if ($estado_filtro !== 'TODAS') {
    $sql .= " AND s.estado_solicitud = ?";
}
$sql .= " ORDER BY s.fecha_solicitud DESC";

$solicitudes = [];
if ($estado_filtro !== 'TODAS') {
    $st = $_conexion->prepare($sql);
    $st->bind_param("is", $id_protectora, $estado_filtro);
} else {
    $st = $_conexion->prepare($sql);
    $st->bind_param("i", $id_protectora);
}
$st->execute();
$res = $st->get_result();
while ($row = $res->fetch_assoc()) $solicitudes[] = $row;
$st->close();

// ── CONTADORES POR ESTADO ─────────────────────────────────────
$contadores = ['PENDIENTE' => 0, 'APROBADA' => 0, 'RECHAZADA' => 0];
$st2 = $_conexion->prepare(
    "SELECT s.estado_solicitud, COUNT(*) AS total
     FROM SolicitudAdopcion s JOIN Animales a ON s.id_animal = a.id_animal
     WHERE a.id_protectora = ?
     GROUP BY s.estado_solicitud"
);
$st2->bind_param("i", $id_protectora);
$st2->execute();
$r2 = $st2->get_result();
while ($row = $r2->fetch_assoc()) $contadores[$row['estado_solicitud']] = (int)$row['total'];
$st2->close();

$vivienda_labels = ['piso' => 'Piso', 'casa' => 'Casa', 'chalet' => 'Chalet', 'otro' => 'Otro'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitudes de adopción · Go Catch</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/material-design-iconic-font/2.2.0/css/material-design-iconic-font.min.css">
    <link rel="stylesheet" href="css/header.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; font-size: 13px; color: #333; margin: 0; background: #0D2D51; min-height: 100vh; }
        p, h1, h2, h3 { margin: 0; }
        a { text-decoration: none; }

        #page-wrapper { max-width: 1100px; margin: 0 auto; padding: 36px 24px 60px; }

        .breadcrumb { font-size: 11px; color: #EDA677; margin-bottom: 22px; display: flex; align-items: center; gap: 6px; }
        .breadcrumb a { color: #EDA677; transition: color .2s; }
        .breadcrumb a:hover { color: #fff; }
        .breadcrumb span { color: #fff; opacity: .5; }

        /* ── PAGE HEADER ── */
        .page-title { color: #fff; font-size: 22px; font-weight: 800; margin-bottom: 6px; }
        .page-sub   { color: rgba(255,255,255,.5); font-size: 12px; margin-bottom: 24px; }

        /* ── TABS ── */
        .tabs { display: flex; gap: 8px; margin-bottom: 24px; flex-wrap: wrap; }
        .tab-btn {
            display: inline-flex; align-items: center; gap: 7px;
            padding: 8px 18px; border-radius: 20px;
            font-family: 'Poppins', sans-serif; font-size: 11px; font-weight: 700;
            letter-spacing: .8px; text-transform: uppercase;
            border: 1.5px solid rgba(255,255,255,.2); color: rgba(255,255,255,.6);
            background: transparent; cursor: pointer; transition: all .2s; text-decoration: none;
        }
        .tab-btn .badge-num {
            background: rgba(255,255,255,.15); color: #fff; font-size: 10px;
            padding: 1px 7px; border-radius: 10px; font-weight: 700;
        }
        .tab-btn:hover, .tab-btn.activo {
            background: rgba(255,255,255,.12); border-color: #CA7842; color: #EDA677;
        }
        .tab-btn.activo .badge-num { background: #CA7842; }

        /* ── GRID DE TARJETAS ── */
        .solicitudes-grid { display: flex; flex-direction: column; gap: 16px; }

        .sol-card {
            background: #fff; border-radius: 10px;
            box-shadow: 0 8px 30px rgba(0,0,0,.25);
            overflow: hidden;
        }

        /* Cabecera de la tarjeta */
        .sol-card-header {
            display: flex; align-items: center; gap: 14px;
            padding: 16px 20px; border-bottom: 1px solid #f0f0f0;
            background: #fafafa;
        }
        .sol-animal-foto { width: 48px; height: 48px; border-radius: 8px; overflow: hidden; background: #e0e8f0; flex-shrink: 0; }
        .sol-animal-foto img { width: 100%; height: 100%; object-fit: cover; }
        .sol-animal-foto i { font-size: 22px; color: #aaa; display: flex; align-items: center; justify-content: center; height: 100%; }
        .sol-animal-nombre { font-size: 14px; font-weight: 700; color: #0D2D51; }
        .sol-animal-detalle { font-size: 11px; color: #999; margin-top: 2px; }
        .sol-estado-badge {
            margin-left: auto; flex-shrink: 0;
            display: inline-flex; align-items: center; gap: 5px;
            padding: 4px 12px; border-radius: 20px;
            font-size: 10px; font-weight: 700; letter-spacing: .8px; text-transform: uppercase;
        }
        .estado-PENDIENTE  { background: #FFF8E1; color: #b8860b; }
        .estado-APROBADA   { background: #EAF3DE; color: #2e7d12; }
        .estado-RECHAZADA  { background: #FFEBEE; color: #c62828; }

        /* Cuerpo de la tarjeta */
        .sol-card-body { padding: 18px 20px; }

        .sol-adoptante-nombre { font-size: 15px; font-weight: 700; color: #0D2D51; margin-bottom: 4px; }
        .sol-fecha { font-size: 11px; color: #aaa; margin-bottom: 14px; }

        .sol-datos-grid {
            display: grid; grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
            gap: 10px 16px; margin-bottom: 14px;
        }
        .sol-dato { display: flex; flex-direction: column; gap: 2px; }
        .sol-dato-label { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .8px; color: #bbb; }
        .sol-dato-val   { font-size: 12px; font-weight: 600; color: #333; }

        .sol-motivacion {
            background: #f9f9f9; border-left: 3px solid #EDA677;
            border-radius: 0 6px 6px 0; padding: 10px 14px;
            font-size: 12px; color: #555; line-height: 1.7; margin-bottom: 14px;
        }
        .sol-motivacion-label { font-size: 9px; font-weight: 700; text-transform: uppercase; letter-spacing: .8px; color: #bbb; margin-bottom: 5px; }

        /* Cita concertada */
        .cita-box {
            display: flex; align-items: center; gap: 10px;
            background: #EAF3DE; border-radius: 6px; padding: 10px 14px; margin-bottom: 14px;
        }
        .cita-box i { color: #2e7d12; font-size: 16px; }
        .cita-box p { font-size: 12px; font-weight: 600; color: #2e7d12; }

        /* Acciones */
        .sol-acciones { display: flex; gap: 8px; flex-wrap: wrap; padding-top: 10px; border-top: 1px solid #f0f0f0; }

        .btn-accion {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 8px 16px; border-radius: 5px; border: none;
            font-family: 'Poppins', sans-serif; font-size: 11px; font-weight: 700;
            letter-spacing: .8px; text-transform: uppercase; cursor: pointer;
            transition: background .2s, transform .15s; text-decoration: none;
        }
        .btn-accion:hover { transform: translateY(-1px); }
        .btn-aprobar   { background: #EAF3DE; color: #2e7d12; }
        .btn-aprobar:hover  { background: #c8e6c9; }
        .btn-rechazar  { background: #FFEBEE; color: #c62828; }
        .btn-rechazar:hover { background: #ffcdd2; }
        .btn-pendiente { background: #FFF8E1; color: #b8860b; }
        .btn-pendiente:hover { background: #fff176; }
        .btn-ver-ficha { background: #e8f0fe; color: #1a5cd6; }
        .btn-ver-ficha:hover { background: #c5d8fc; }

        /* Vacío */
        .sin-solicitudes { text-align: center; padding: 60px 20px; color: rgba(255,255,255,.4); }
        .sin-solicitudes i { font-size: 48px; display: block; margin-bottom: 14px; opacity: .4; }
        .sin-solicitudes p { font-size: 14px; }

        /* Compat tags */
        .compat-tags { display: flex; gap: 5px; flex-wrap: wrap; margin-bottom: 14px; }
        .compat-tag {
            font-size: 10px; font-weight: 600;
            padding: 3px 10px; border-radius: 20px;
            background: #EAF3DE; color: #2e7d12;
        }
        .compat-tag.no { background: #f5f5f5; color: #aaa; }

        @media (max-width: 600px) {
            .sol-datos-grid { grid-template-columns: 1fr 1fr; }
        }
    </style>
</head>
<body>

<header>
    <nav class="hBotones">
        <a class="hBoton" href="indexProtectora.php">PANEL</a>
        <a class="hBoton" href="disponibilidad.php">DISPONIBILIDAD</a>
    </nav>
    <nav id="header-izq">
        <a href="indexProtectora.php">
            <img src="../img/profile/default/oficiales/logo.svg" alt="Go Catch" height="40">
        </a>
    </nav>
    <nav class="hBotones">
        <a class="hBoton" href="perfil.php">
            <i class="zmdi zmdi-account"></i>
            <?= htmlspecialchars($_SESSION['nombre']) ?>
        </a>
        <a href="../src/sesion/logout.php" id="boton-destacado">CERRAR SESIÓN</a>
    </nav>
</header>

<div id="page-wrapper">

    <div class="breadcrumb">
        <a href="indexProtectora.php"><i class="zmdi zmdi-home"></i> Panel</a>
        <span>/</span> Solicitudes de adopción
    </div>

    <h1 class="page-title">Solicitudes de adopción</h1>
    <p class="page-sub">Gestiona las solicitudes recibidas para tus animales</p>

    <!-- TABS -->
    <div class="tabs">
        <?php
        $tabs = [
            'PENDIENTE' => ['label' => 'Pendientes', 'icono' => 'zmdi-time'],
            'APROBADA'  => ['label' => 'Aprobadas',  'icono' => 'zmdi-check-circle'],
            'RECHAZADA' => ['label' => 'Rechazadas', 'icono' => 'zmdi-close-circle'],
            'TODAS'     => ['label' => 'Todas',      'icono' => 'zmdi-view-list'],
        ];
        foreach ($tabs as $est => $tab):
            $activo = $estado_filtro === $est ? 'activo' : '';
            $num    = $est === 'TODAS' ? array_sum($contadores) : ($contadores[$est] ?? 0);
        ?>
        <a href="?estado=<?= $est ?>" class="tab-btn <?= $activo ?>">
            <i class="zmdi <?= $tab['icono'] ?>"></i>
            <?= $tab['label'] ?>
            <span class="badge-num"><?= $num ?></span>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- LISTA -->
    <?php if (empty($solicitudes)): ?>
    <div class="sin-solicitudes">
        <i class="zmdi zmdi-inbox"></i>
        <p>No hay solicitudes <?= strtolower($estado_filtro === 'TODAS' ? '' : $estado_filtro . 's') ?> en este momento.</p>
    </div>
    <?php else: ?>
    <div class="solicitudes-grid">
        <?php foreach ($solicitudes as $s):
            $dt = new DateTime($s['fecha_solicitud']);
            $fecha_fmt = $dt->format('d/m/Y H:i');
        ?>
        <div class="sol-card">

            <!-- Cabecera: animal + estado -->
            <div class="sol-card-header">
                <div class="sol-animal-foto">
                    <?php if (!empty($s['foto_animal'])): ?>
                        <img src="<?= htmlspecialchars($s['foto_animal']) ?>" alt="">
                    <?php else: ?>
                        <i class="zmdi zmdi-paw"></i>
                    <?php endif; ?>
                </div>
                <div>
                    <p class="sol-animal-nombre"><?= htmlspecialchars($s['nombre_animal']) ?></p>
                    <p class="sol-animal-detalle">
                        <?= htmlspecialchars(ucfirst($s['especie'] ?? '') . (!empty($s['raza']) ? ' · ' . $s['raza'] : '')) ?>
                    </p>
                </div>
                <span class="sol-estado-badge estado-<?= $s['estado_solicitud'] ?>">
                    <?= $s['estado_solicitud'] ?>
                </span>
            </div>

            <!-- Cuerpo -->
            <div class="sol-card-body">

                <p class="sol-adoptante-nombre">
                    <?= htmlspecialchars($s['nombre'] . ' ' . $s['apellido']) ?>
                </p>
                <p class="sol-fecha">Solicitud recibida el <?= $fecha_fmt ?></p>

                <!-- Datos básicos -->
                <div class="sol-datos-grid">
                    <?php if ($s['email']): ?>
                    <div class="sol-dato">
                        <span class="sol-dato-label">Email</span>
                        <span class="sol-dato-val"><?= htmlspecialchars($s['email']) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($s['telefono']): ?>
                    <div class="sol-dato">
                        <span class="sol-dato-label">Teléfono</span>
                        <span class="sol-dato-val"><?= htmlspecialchars($s['telefono']) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($s['dni']): ?>
                    <div class="sol-dato">
                        <span class="sol-dato-label">DNI / NIE</span>
                        <span class="sol-dato-val"><?= htmlspecialchars($s['dni']) ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="sol-dato">
                        <span class="sol-dato-label">Vivienda</span>
                        <span class="sol-dato-val">
                            <?= htmlspecialchars($vivienda_labels[$s['tipo_vivienda']] ?? $s['tipo_vivienda']) ?>
                            <?= $s['metros_vivienda'] ? ' · ' . htmlspecialchars($s['metros_vivienda']) . ' m²' : '' ?>
                            <?= $s['tiene_jardin'] ? ' · Con jardín' : '' ?>
                        </span>
                    </div>
                    <div class="sol-dato">
                        <span class="sol-dato-label">Horas solo/día</span>
                        <span class="sol-dato-val">
                            <?php
                            $h = (int)$s['horas_solo'];
                            $horas_labels = [0=>'Nunca',2=>'< 2h',4=>'2–4h',6=>'4–6h',8=>'6–8h',9=>'> 8h'];
                            echo htmlspecialchars($horas_labels[$h] ?? $h . 'h');
                            ?>
                        </span>
                    </div>
                    <?php if ($s['tiene_ninos']): ?>
                    <div class="sol-dato">
                        <span class="sol-dato-label">Niños en casa</span>
                        <span class="sol-dato-val"><?= $s['edades_ninos'] ? htmlspecialchars($s['edades_ninos']) : 'Sí' ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if ($s['tiene_animales']): ?>
                    <div class="sol-dato">
                        <span class="sol-dato-label">Otros animales</span>
                        <span class="sol-dato-val"><?= $s['desc_animales'] ? htmlspecialchars($s['desc_animales']) : 'Sí' ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="sol-dato">
                        <span class="sol-dato-label">Experiencia animales</span>
                        <span class="sol-dato-val"><?= $s['experiencia'] ? 'Sí' : 'No' ?></span>
                    </div>
                </div>

                <!-- Compromisos -->
                <div class="compat-tags">
                    <span class="compat-tag">Acepta visita domicilio</span>
                    <?php if ($s['acepta_seguimiento']): ?>
                        <span class="compat-tag">Acepta seguimiento</span>
                    <?php endif; ?>
                </div>

                <!-- Motivación -->
                <?php if (!empty($s['motivacion'])): ?>
                <div class="sol-motivacion">
                    <p class="sol-motivacion-label">Motivación</p>
                    <?= nl2br(htmlspecialchars($s['motivacion'])) ?>
                </div>
                <?php endif; ?>

                <!-- Cita concertada -->
                <?php if ($s['id_cita']): ?>
                <div class="cita-box">
                    <i class="zmdi zmdi-calendar-check"></i>
                    <p>Entrevista concertada:
                        <?php
                        $fd = new DateTime($s['fecha_cita']);
                        echo $fd->format('d/m/Y') . ' a las ' . substr($s['hora_cita'], 0, 5);
                        ?>
                        (<?= htmlspecialchars($s['estado_cita']) ?>)
                    </p>
                </div>
                <?php endif; ?>

                <!-- Acciones -->
                <div class="sol-acciones">
                    <a href="ficha-animal.php?id=<?= (int)$s['id_animal'] ?>" class="btn-accion btn-ver-ficha" target="_blank">
                        <i class="zmdi zmdi-eye"></i> Ver animal
                    </a>
                    <?php if ($s['estado_solicitud'] !== 'APROBADA'): ?>
                    <form method="POST" style="margin:0">
                        <input type="hidden" name="id_solicitud" value="<?= (int)$s['id_solicitud'] ?>">
                        <input type="hidden" name="accion" value="aprobar">
                        <button class="btn-accion btn-aprobar" type="submit">
                            <i class="zmdi zmdi-check"></i> Aprobar
                        </button>
                    </form>
                    <?php endif; ?>
                    <?php if ($s['estado_solicitud'] !== 'RECHAZADA'): ?>
                    <form method="POST" style="margin:0">
                        <input type="hidden" name="id_solicitud" value="<?= (int)$s['id_solicitud'] ?>">
                        <input type="hidden" name="accion" value="rechazar">
                        <button class="btn-accion btn-rechazar" type="submit">
                            <i class="zmdi zmdi-close"></i> Rechazar
                        </button>
                    </form>
                    <?php endif; ?>
                    <?php if ($s['estado_solicitud'] !== 'PENDIENTE'): ?>
                    <form method="POST" style="margin:0">
                        <input type="hidden" name="id_solicitud" value="<?= (int)$s['id_solicitud'] ?>">
                        <input type="hidden" name="accion" value="pendiente">
                        <button class="btn-accion btn-pendiente" type="submit">
                            <i class="zmdi zmdi-time"></i> Marcar pendiente
                        </button>
                    </form>
                    <?php endif; ?>
                </div>

            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div>
</body>
</html>
