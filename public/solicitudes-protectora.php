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
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,900&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/material-design-iconic-font/2.2.0/css/material-design-iconic-font.min.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/solicitudes-protectora.css">
</head>
<body>

<header>
    <nav class="hBotones">
        <a class="hBoton" href="indexProtectora.php">PANEL</a>
        <a class="hBoton" href="disponibilidad.php">DISPONIBILIDAD</a>
    </nav>
    <nav id="header-izq">
        <a href="indexProtectora.php">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200" width="40" height="40" role="img" aria-label="Go Catch"><rect x="0" y="0" width="200" height="200" rx="36" ry="36" fill="#C97041"/><text x="110" y="148" font-family="'Fraunces', serif" font-weight="900" font-size="145" fill="#FFFFFF" text-anchor="middle">gc</text></svg>
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
