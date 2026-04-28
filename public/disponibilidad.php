<?php
session_start();
require "../src/sesion/conexion.php";

// Solo protectoras
if (!isset($_SESSION['id']) || isset($_SESSION['user'])) {
    header('Location: index.php');
    exit();
}
$id_protectora = (int)$_SESSION['id'];

// ── CREAR TABLAS SI NO EXISTEN ────────────────────────────────
$_conexion->query("
    CREATE TABLE IF NOT EXISTS DisponibilidadProtectora (
        id_disponibilidad INT AUTO_INCREMENT PRIMARY KEY,
        id_protectora     INT NOT NULL,
        fecha             DATE NOT NULL,
        hora_inicio       TIME NOT NULL,
        hora_fin          TIME NOT NULL,
        disponible        TINYINT(1) DEFAULT 1,
        UNIQUE KEY uniq_slot (id_protectora, fecha, hora_inicio),
        FOREIGN KEY (id_protectora) REFERENCES Protectora(id_protectora) ON DELETE CASCADE
    )
");
$_conexion->query("
    CREATE TABLE IF NOT EXISTS CitaEntrevista (
        id_cita              INT AUTO_INCREMENT PRIMARY KEY,
        id_solicitud         INT NOT NULL,
        id_disponibilidad    INT NOT NULL,
        fecha_reserva        DATETIME DEFAULT CURRENT_TIMESTAMP,
        estado               ENUM('PENDIENTE','CONFIRMADA','CANCELADA') DEFAULT 'PENDIENTE',
        notas                TEXT,
        FOREIGN KEY (id_solicitud)      REFERENCES SolicitudAdopcion(id_solicitud) ON DELETE CASCADE,
        FOREIGN KEY (id_disponibilidad) REFERENCES DisponibilidadProtectora(id_disponibilidad) ON DELETE CASCADE
    )
");

// ── ACCIONES POST ─────────────────────────────────────────────
$msg = '';
$msg_tipo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $accion = $_POST['accion'] ?? '';

    if ($accion === 'add_slot') {
        $fecha      = $_POST['fecha']      ?? '';
        $hora_ini   = $_POST['hora_inicio'] ?? '';
        $hora_fin   = $_POST['hora_fin']    ?? '';

        if ($fecha && $hora_ini && $hora_fin && $hora_fin > $hora_ini && $fecha >= date('Y-m-d')) {
            $ins = $_conexion->prepare(
                "INSERT IGNORE INTO DisponibilidadProtectora (id_protectora, fecha, hora_inicio, hora_fin)
                 VALUES (?, ?, ?, ?)"
            );
            $ins->bind_param("isss", $id_protectora, $fecha, $hora_ini, $hora_fin);
            if ($ins->execute() && $_conexion->affected_rows > 0) {
                $msg = 'Franja horaria añadida.'; $msg_tipo = 'ok';
            } else {
                $msg = 'Esa franja ya existe para ese día.'; $msg_tipo = 'err';
            }
            $ins->close();
        } else {
            $msg = 'Datos incorrectos. Comprueba la fecha (no puede ser en el pasado) y las horas.'; $msg_tipo = 'err';
        }
    }

    if ($accion === 'add_bloque') {
        // Añadir múltiples slots de duración fija en un rango
        $fecha      = $_POST['fecha']       ?? '';
        $desde      = $_POST['desde']       ?? '';
        $hasta      = $_POST['hasta']       ?? '';
        $duracion   = (int)($_POST['duracion'] ?? 30);

        if ($fecha && $desde && $hasta && $hasta > $desde && $fecha >= date('Y-m-d') && in_array($duracion, [30,60,90,120])) {
            $t_ini = strtotime("$fecha $desde");
            $t_fin = strtotime("$fecha $hasta");
            $ins   = $_conexion->prepare(
                "INSERT IGNORE INTO DisponibilidadProtectora (id_protectora, fecha, hora_inicio, hora_fin)
                 VALUES (?, ?, ?, ?)"
            );
            $added = 0;
            while ($t_ini + $duracion * 60 <= $t_fin) {
                $hi = date('H:i:s', $t_ini);
                $hf = date('H:i:s', $t_ini + $duracion * 60);
                $ins->bind_param("isss", $id_protectora, $fecha, $hi, $hf);
                $ins->execute();
                if ($_conexion->affected_rows > 0) $added++;
                $t_ini += $duracion * 60;
            }
            $ins->close();
            $msg = "$added franjas añadidas."; $msg_tipo = 'ok';
        } else {
            $msg = 'Datos incorrectos para el bloque.'; $msg_tipo = 'err';
        }
    }

    if ($accion === 'delete_slot') {
        $id_slot = (int)($_POST['id_slot'] ?? 0);
        // Solo si no tiene cita reservada y pertenece a esta protectora
        $del = $_conexion->prepare(
            "DELETE d FROM DisponibilidadProtectora d
             LEFT JOIN CitaEntrevista c
                   ON c.id_disponibilidad = d.id_disponibilidad
                  AND c.estado != 'CANCELADA'
             WHERE d.id_disponibilidad = ? AND d.id_protectora = ?
               AND c.id_cita IS NULL"
        );
        $del->bind_param("ii", $id_slot, $id_protectora);
        $del->execute();
        $msg = $_conexion->affected_rows > 0 ? 'Franja eliminada.' : 'No se puede eliminar (tiene cita reservada).';
        $msg_tipo = $_conexion->affected_rows > 0 ? 'ok' : 'err';
        $del->close();
    }

    header("Location: disponibilidad.php?mes=" . urlencode($_POST['mes'] ?? date('Y-m')) . "&msg=" . urlencode($msg) . "&tipo=" . $msg_tipo);
    exit();
}

if (isset($_GET['msg'])) { $msg = $_GET['msg']; $msg_tipo = $_GET['tipo'] ?? 'ok'; }

// ── MES SELECCIONADO ──────────────────────────────────────────
$mes_param = $_GET['mes'] ?? date('Y-m');
if (!preg_match('/^\d{4}-\d{2}$/', $mes_param)) $mes_param = date('Y-m');
[$anyo, $mes] = explode('-', $mes_param);
$anyo = (int)$anyo; $mes = (int)$mes;

$primer_dia = mktime(0,0,0,$mes,1,$anyo);
$ultimo_dia = mktime(0,0,0,$mes+1,0,$anyo);
$dias_en_mes = (int)date('d', $ultimo_dia);
$dia_semana_inicio = (int)date('N', $primer_dia); // 1=lunes

$mes_ant = date('Y-m', mktime(0,0,0,$mes-1,1,$anyo));
$mes_sig = date('Y-m', mktime(0,0,0,$mes+1,1,$anyo));

$meses_es = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

// ── SLOTS DEL MES ─────────────────────────────────────────────
$fecha_ini_mes = "$anyo-" . str_pad($mes,2,'0',STR_PAD_LEFT) . "-01";
$fecha_fin_mes = "$anyo-" . str_pad($mes,2,'0',STR_PAD_LEFT) . "-$dias_en_mes";

$st = $_conexion->prepare(
    "SELECT d.*, c.id_cita, c.estado AS estado_cita,
            s.nombre AS nombre_adoptante, s.apellido,
            a.nombre AS nombre_animal
     FROM DisponibilidadProtectora d
     LEFT JOIN CitaEntrevista c ON c.id_disponibilidad = d.id_disponibilidad AND c.estado != 'CANCELADA'
     LEFT JOIN SolicitudAdopcion s ON c.id_solicitud = s.id_solicitud
     LEFT JOIN Animales a ON s.id_animal = a.id_animal
     WHERE d.id_protectora = ? AND d.fecha BETWEEN ? AND ?
     ORDER BY d.fecha, d.hora_inicio"
);
$st->bind_param("iss", $id_protectora, $fecha_ini_mes, $fecha_fin_mes);
$st->execute();
$res = $st->get_result();
$slots_por_dia = [];
while ($row = $res->fetch_assoc()) {
    $slots_por_dia[$row['fecha']][] = $row;
}
$st->close();

// ── PRÓXIMAS CITAS (panel lateral) ───────────────────────────
$prox = $_conexion->prepare(
    "SELECT d.fecha, d.hora_inicio, d.hora_fin,
            s.nombre AS nombre_adoptante, s.apellido, s.email, s.telefono,
            a.nombre AS nombre_animal, c.estado AS estado_cita, c.id_cita
     FROM CitaEntrevista c
     JOIN DisponibilidadProtectora d ON c.id_disponibilidad = d.id_disponibilidad
     JOIN SolicitudAdopcion s ON c.id_solicitud = s.id_solicitud
     JOIN Animales a ON s.id_animal = a.id_animal
     WHERE d.id_protectora = ? AND d.fecha >= CURDATE() AND c.estado != 'CANCELADA'
     ORDER BY d.fecha, d.hora_inicio LIMIT 10"
);
$prox->bind_param("i", $id_protectora);
$prox->execute();
$proximas_citas = $prox->get_result()->fetch_all(MYSQLI_ASSOC);
$prox->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Disponibilidad para entrevistas · Go Catch</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,900&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/material-design-iconic-font/2.2.0/css/material-design-iconic-font.min.css">
    <link rel="stylesheet" href="css/header.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; font-size: 13px; color: #333; margin: 0; background: #0D2D51; min-height: 100vh; }
        p, h1, h2, h3 { margin: 0; }
        a { text-decoration: none; color: inherit; }

        #page-wrapper { max-width: 1200px; margin: 0 auto; padding: 36px 24px 60px; }

        .breadcrumb { font-size: 11px; color: #EDA677; margin-bottom: 22px; display: flex; align-items: center; gap: 6px; }
        .breadcrumb a { color: #EDA677; }
        .breadcrumb a:hover { color: #fff; }
        .breadcrumb span { color: #fff; opacity: .5; }

        .page-title { color: #fff; font-size: 22px; font-weight: 800; margin-bottom: 4px; }
        .page-sub   { color: rgba(255,255,255,.5); font-size: 12px; margin-bottom: 28px; }

        /* ── MENSAJE FLASH ── */
        .flash { padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; font-size: 12px; font-weight: 600; }
        .flash-ok  { background: #EAF3DE; color: #2e7d12; }
        .flash-err { background: #FFEBEE; color: #c62828; }

        /* ── LAYOUT DOS COLUMNAS ── */
        .layout { display: grid; grid-template-columns: 1fr 320px; gap: 24px; align-items: start; }
        @media (max-width: 900px) { .layout { grid-template-columns: 1fr; } }

        /* ── CARD GENÉRICA ── */
        .card { background: #fff; border-radius: 10px; box-shadow: 0 8px 30px rgba(0,0,0,.25); overflow: hidden; }
        .card-header { background: #0D2D51; padding: 16px 22px; border-bottom: 2px solid #CA7842; display: flex; align-items: center; justify-content: space-between; gap: 12px; }
        .card-header h2 { color: #fff; font-size: 14px; font-weight: 700; }
        .card-body { padding: 20px 22px; }

        /* ── NAVEGACIÓN MES ── */
        .mes-nav { display: flex; align-items: center; gap: 10px; }
        .mes-nav a {
            display: flex; align-items: center; justify-content: center;
            width: 32px; height: 32px; border-radius: 50%;
            background: rgba(255,255,255,.1); color: #EDA677;
            font-size: 18px; transition: background .2s;
        }
        .mes-nav a:hover { background: rgba(202,120,66,.4); }
        .mes-nav h2 { color: #fff; font-size: 15px; font-weight: 700; }

        /* ── CALENDARIO ── */
        .cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 4px; }
        .cal-dia-label {
            text-align: center; font-size: 10px; font-weight: 700;
            text-transform: uppercase; letter-spacing: .8px;
            color: #bbb; padding: 6px 0;
        }
        .cal-dia {
            min-height: 80px; border-radius: 6px;
            background: #f9f9f9; border: 1.5px solid transparent;
            padding: 6px; cursor: pointer; transition: border-color .2s, background .2s;
            position: relative;
        }
        .cal-dia:hover { border-color: #CA7842; background: #fff8f0; }
        .cal-dia.vacio { background: transparent; cursor: default; border-color: transparent; }
        .cal-dia.pasado { opacity: .4; cursor: not-allowed; }
        .cal-dia.hoy { border-color: #CA7842; background: #FFF1E6; }
        .cal-dia.tiene-slots { background: #f0f7e6; }
        .cal-dia.tiene-citas { background: #e8f0fe; border-color: #4a80d6; }

        .cal-num { font-size: 11px; font-weight: 700; color: #555; }
        .cal-dia.hoy .cal-num { color: #CA7842; }

        .cal-slot-dot {
            display: inline-flex; align-items: center; justify-content: center;
            width: 100%; margin-top: 3px;
            font-size: 9px; font-weight: 700; border-radius: 4px; padding: 1px 0;
        }
        .dot-libre  { background: #c8e6c9; color: #1b5e20; }
        .dot-citado { background: #bbdefb; color: #0d47a1; }

        /* ── PANEL AÑADIR SLOTS ── */
        .panel-slot {
            position: fixed; top: 0; right: -400px; width: 380px; height: 100vh;
            background: #fff; box-shadow: -6px 0 40px rgba(0,0,0,.3);
            z-index: 500; transition: right .35s cubic-bezier(.4,0,.2,1);
            display: flex; flex-direction: column;
            overflow-y: auto;
        }
        .panel-slot.abierto { right: 0; }
        .panel-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.4); z-index: 499; }
        .panel-overlay.activo { display: block; }

        .panel-slot-header {
            background: #0D2D51; padding: 18px 22px;
            display: flex; align-items: center; justify-content: space-between;
            flex-shrink: 0;
        }
        .panel-slot-header h3 { color: #fff; font-size: 15px; font-weight: 700; }
        .panel-cerrar { background: none; border: none; color: #EDA677; font-size: 22px; cursor: pointer; padding: 0; display: flex; }

        .panel-slot-body { padding: 22px; flex: 1; }

        .campo { display: flex; flex-direction: column; gap: 5px; margin-bottom: 16px; }
        .campo label { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .8px; color: #888; }
        .campo input, .campo select {
            font-family: 'Poppins', sans-serif; font-size: 13px; color: #333;
            border: 1.5px solid #e0e0e0; border-radius: 6px; padding: 9px 12px;
            outline: none; background: #fafafa; width: 100%; transition: border-color .2s;
        }
        .campo input:focus, .campo select:focus { border-color: #CA7842; background: #fff; }

        .sep-o { text-align: center; color: #ccc; font-size: 11px; font-weight: 600; margin: 18px 0; position: relative; }
        .sep-o::before, .sep-o::after { content: ''; position: absolute; top: 50%; width: 42%; height: 1px; background: #f0f0f0; }
        .sep-o::before { left: 0; } .sep-o::after { right: 0; }

        .btn-primario {
            width: 100%; padding: 12px; background: #CA7842; color: #fff; border: none;
            border-radius: 6px; font-family: 'Poppins', sans-serif; font-size: 13px;
            font-weight: 700; letter-spacing: .8px; cursor: pointer;
            transition: background .2s; display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-primario:hover { background: #b56a32; }
        .btn-secundario {
            width: 100%; padding: 10px; background: transparent; color: #CA7842;
            border: 1.5px solid #CA7842; border-radius: 6px;
            font-family: 'Poppins', sans-serif; font-size: 12px; font-weight: 600;
            cursor: pointer; transition: background .2s, color .2s;
            display: flex; align-items: center; justify-content: center; gap: 8px; margin-top: 10px;
        }
        .btn-secundario:hover { background: #CA7842; color: #fff; }

        /* Lista de slots del día en el panel */
        .slots-lista { margin-top: 22px; }
        .slots-lista-titulo { font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: .8px; color: #999; margin-bottom: 10px; }
        .slot-item {
            display: flex; align-items: center; justify-content: space-between;
            padding: 9px 12px; border-radius: 6px; margin-bottom: 6px;
            background: #f9f9f9; border: 1px solid #f0f0f0;
        }
        .slot-item.ocupado { background: #e8f0fe; border-color: #c5d8fc; }
        .slot-hora { font-size: 12px; font-weight: 700; color: #333; }
        .slot-quien { font-size: 11px; color: #888; margin-top: 2px; }
        .slot-del {
            background: #FFEBEE; color: #c62828; border: none; border-radius: 4px;
            padding: 4px 10px; font-size: 10px; font-weight: 700; cursor: pointer;
            font-family: 'Poppins', sans-serif; transition: background .2s;
        }
        .slot-del:hover { background: #ffcdd2; }
        .slot-badge-ocu { font-size: 9px; font-weight: 700; background: #1a5cd6; color: #fff; padding: 2px 8px; border-radius: 10px; }

        /* ── PANEL DERECHO: próximas citas ── */
        .sidebar-card { background: #fff; border-radius: 10px; box-shadow: 0 8px 30px rgba(0,0,0,.25); overflow: hidden; }
        .sidebar-header { background: #0D2D51; padding: 14px 18px; border-bottom: 2px solid #CA7842; }
        .sidebar-header h3 { color: #fff; font-size: 13px; font-weight: 700; }
        .sidebar-body { padding: 14px; }
        .cita-item { padding: 11px 12px; border-radius: 6px; border: 1px solid #f0f0f0; margin-bottom: 8px; }
        .cita-fecha-hora { font-size: 11px; font-weight: 700; color: #0D2D51; }
        .cita-animal  { font-size: 11px; color: #CA7842; font-weight: 600; margin-top: 2px; }
        .cita-persona { font-size: 11px; color: #777; margin-top: 1px; }
        .sin-citas { text-align: center; padding: 20px 10px; color: #bbb; font-size: 12px; }
        .sin-citas i { font-size: 28px; display: block; margin-bottom: 8px; }

        /* Leyenda */
        .leyenda { display: flex; gap: 14px; flex-wrap: wrap; margin-bottom: 14px; }
        .leyenda-item { display: flex; align-items: center; gap: 6px; font-size: 11px; color: #555; }
        .leyenda-dot { width: 12px; height: 12px; border-radius: 3px; }
        .ld-libre  { background: #c8e6c9; }
        .ld-citado { background: #bbdefb; }
        .ld-hoy    { border: 2px solid #CA7842; background: #FFF1E6; }
    </style>
</head>
<body>

<header>
    <nav class="hBotones">
        <a class="hBoton" href="indexProtectora.php">PANEL</a>
        <a class="hBoton" href="solicitudes-protectora.php">SOLICITUDES</a>
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
        <span>/</span> Disponibilidad para entrevistas
    </div>

    <h1 class="page-title">Disponibilidad para entrevistas</h1>
    <p class="page-sub">Define qué días y horas estás disponible para realizar entrevistas de adopción</p>

    <?php if ($msg): ?>
    <div class="flash flash-<?= $msg_tipo === 'ok' ? 'ok' : 'err' ?>"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>

    <div class="layout">

        <!-- CALENDARIO -->
        <div class="card">
            <div class="card-header">
                <nav class="mes-nav">
                    <a href="?mes=<?= $mes_ant ?>"><i class="zmdi zmdi-chevron-left"></i></a>
                    <h2><?= $meses_es[$mes] ?> <?= $anyo ?></h2>
                    <a href="?mes=<?= $mes_sig ?>"><i class="zmdi zmdi-chevron-right"></i></a>
                </nav>
                <div style="font-size:11px;color:rgba(255,255,255,.6)">
                    Haz clic en un día para gestionar franjas
                </div>
            </div>
            <div class="card-body">

                <div class="leyenda">
                    <span class="leyenda-item"><span class="leyenda-dot ld-hoy"></span> Hoy</span>
                    <span class="leyenda-item"><span class="leyenda-dot ld-libre"></span> Franjas libres</span>
                    <span class="leyenda-item"><span class="leyenda-dot ld-citado"></span> Con citas</span>
                </div>

                <div class="cal-grid">
                    <?php foreach (['Lun','Mar','Mié','Jue','Vie','Sáb','Dom'] as $d): ?>
                        <div class="cal-dia-label"><?= $d ?></div>
                    <?php endforeach; ?>

                    <?php
                    // Celdas vacías al inicio
                    for ($i = 1; $i < $dia_semana_inicio; $i++):
                    ?>
                        <div class="cal-dia vacio"></div>
                    <?php endfor; ?>

                    <?php for ($dia = 1; $dia <= $dias_en_mes; $dia++):
                        $fecha_str = "$anyo-" . str_pad($mes,2,'0',STR_PAD_LEFT) . "-" . str_pad($dia,2,'0',STR_PAD_LEFT);
                        $es_hoy    = $fecha_str === date('Y-m-d');
                        $es_pasado = $fecha_str < date('Y-m-d');
                        $slots     = $slots_por_dia[$fecha_str] ?? [];
                        $n_slots   = count($slots);
                        $n_citas   = count(array_filter($slots, fn($s) => !empty($s['id_cita'])));
                        $n_libres  = $n_slots - $n_citas;

                        $clases = ['cal-dia'];
                        if ($es_hoy) $clases[] = 'hoy';
                        if ($es_pasado) $clases[] = 'pasado';
                        if ($n_citas > 0) $clases[] = 'tiene-citas';
                        elseif ($n_slots > 0) $clases[] = 'tiene-slots';
                    ?>
                    <div class="<?= implode(' ', $clases) ?>"
                         <?= !$es_pasado ? "onclick=\"abrirPanel('$fecha_str')\"" : '' ?>>
                        <div class="cal-num"><?= $dia ?></div>
                        <?php if ($n_libres > 0): ?>
                            <div class="cal-slot-dot dot-libre"><?= $n_libres ?> libre<?= $n_libres > 1 ? 's' : '' ?></div>
                        <?php endif; ?>
                        <?php if ($n_citas > 0): ?>
                            <div class="cal-slot-dot dot-citado"><?= $n_citas ?> cita<?= $n_citas > 1 ? 's' : '' ?></div>
                        <?php endif; ?>
                    </div>
                    <?php endfor; ?>
                </div>

            </div>
        </div>

        <!-- SIDEBAR: PRÓXIMAS CITAS -->
        <div>
            <div class="sidebar-card">
                <div class="sidebar-header">
                    <h3><i class="zmdi zmdi-calendar-check" style="color:#CA7842;margin-right:6px"></i>Próximas entrevistas</h3>
                </div>
                <div class="sidebar-body">
                    <?php if (empty($proximas_citas)): ?>
                        <div class="sin-citas">
                            <i class="zmdi zmdi-calendar"></i>
                            <p>No hay entrevistas próximas</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($proximas_citas as $c):
                            $fd = new DateTime($c['fecha']);
                        ?>
                        <div class="cita-item">
                            <p class="cita-fecha-hora">
                                <i class="zmdi zmdi-calendar" style="color:#CA7842"></i>
                                <?= $fd->format('d/m/Y') ?> · <?= substr($c['hora_inicio'],0,5) ?>–<?= substr($c['hora_fin'],0,5) ?>
                            </p>
                            <p class="cita-animal"><i class="zmdi zmdi-paw"></i> <?= htmlspecialchars($c['nombre_animal']) ?></p>
                            <p class="cita-persona">
                                <i class="zmdi zmdi-account"></i>
                                <?= htmlspecialchars($c['nombre_adoptante'] . ' ' . $c['apellido']) ?>
                                <?php if ($c['telefono']): ?>· <?= htmlspecialchars($c['telefono']) ?><?php endif; ?>
                            </p>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div><!-- .layout -->
</div>

<!-- PANEL LATERAL: GESTIONAR DÍA -->
<div class="panel-overlay" id="overlay" onclick="cerrarPanel()"></div>
<div class="panel-slot" id="panel-slot">
    <div class="panel-slot-header">
        <h3 id="panel-titulo">Día seleccionado</h3>
        <button class="panel-cerrar" onclick="cerrarPanel()"><i class="zmdi zmdi-close"></i></button>
    </div>
    <div class="panel-slot-body">

        <!-- AÑADIR FRANJA INDIVIDUAL -->
        <form method="POST" id="form-add-slot">
            <input type="hidden" name="accion" value="add_slot">
            <input type="hidden" name="fecha" id="campo-fecha-slot">
            <input type="hidden" name="mes" value="<?= $mes_param ?>">

            <div class="campo">
                <label>Hora inicio</label>
                <input type="time" name="hora_inicio" id="slot-hi" required step="1800">
            </div>
            <div class="campo">
                <label>Hora fin</label>
                <input type="time" name="hora_fin" id="slot-hf" required step="1800">
            </div>
            <button type="submit" class="btn-primario">
                <i class="zmdi zmdi-plus"></i> Añadir franja
            </button>
        </form>

        <div class="sep-o">o añade un bloque</div>

        <!-- AÑADIR BLOQUE (varias franjas de golpe) -->
        <form method="POST" id="form-add-bloque">
            <input type="hidden" name="accion" value="add_bloque">
            <input type="hidden" name="fecha" id="campo-fecha-bloque">
            <input type="hidden" name="mes" value="<?= $mes_param ?>">

            <div class="campo">
                <label>Desde</label>
                <input type="time" name="desde" id="bloque-desde" step="1800" value="09:00">
            </div>
            <div class="campo">
                <label>Hasta</label>
                <input type="time" name="hasta" id="bloque-hasta" step="1800" value="13:00">
            </div>
            <div class="campo">
                <label>Duración por franja</label>
                <select name="duracion">
                    <option value="30">30 minutos</option>
                    <option value="60" selected>1 hora</option>
                    <option value="90">1h 30min</option>
                    <option value="120">2 horas</option>
                </select>
            </div>
            <button type="submit" class="btn-secundario">
                <i class="zmdi zmdi-time-interval"></i> Generar bloque
            </button>
        </form>

        <!-- FRANJAS EXISTENTES EN ESE DÍA -->
        <div class="slots-lista" id="slots-lista">
            <!-- Se rellena con JS -->
        </div>

    </div>
</div>

<script>
const SLOTS = <?= json_encode($slots_por_dia) ?>;

function abrirPanel(fecha) {
    document.getElementById('panel-titulo').textContent = formatFecha(fecha);
    document.getElementById('campo-fecha-slot').value   = fecha;
    document.getElementById('campo-fecha-bloque').value = fecha;

    // Rellenar lista de slots del día
    const lista   = document.getElementById('slots-lista');
    const diaSlot = SLOTS[fecha] || [];

    lista.innerHTML = diaSlot.length
        ? `<p class="slots-lista-titulo">Franjas de este día (${diaSlot.length})</p>` +
          diaSlot.map(s => {
              const ocupado = s.id_cita != null;
              const hi = s.hora_inicio.slice(0, 5);
              const hf = s.hora_fin.slice(0, 5);
              const quien = ocupado
                  ? `<p class="slot-quien">${s.nombre_adoptante} ${s.apellido} · ${s.nombre_animal}</p>`
                  : '';
              const accion = ocupado
                  ? `<span class="slot-badge-ocu">Reservada</span>`
                  : `<form method="POST" style="margin:0">
                        <input type="hidden" name="accion" value="delete_slot">
                        <input type="hidden" name="id_slot" value="${s.id_disponibilidad}">
                        <input type="hidden" name="mes" value="<?= $mes_param ?>">
                        <button class="slot-del" type="submit">Eliminar</button>
                     </form>`;
              return `<div class="slot-item ${ocupado ? 'ocupado' : ''}">
                          <div>
                              <p class="slot-hora">${hi} – ${hf}</p>
                              ${quien}
                          </div>
                          ${accion}
                      </div>`;
          }).join('')
        : `<p style="font-size:12px;color:#bbb;text-align:center;margin-top:16px">
              Sin franjas para este día
           </p>`;

    document.getElementById('panel-slot').classList.add('abierto');
    document.getElementById('overlay').classList.add('activo');
}

function cerrarPanel() {
    document.getElementById('panel-slot').classList.remove('abierto');
    document.getElementById('overlay').classList.remove('activo');
}

function formatFecha(s) {
    const [y, m, d] = s.split('-');
    const meses = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
    return `${parseInt(d)} de ${meses[parseInt(m)]} de ${y}`;
}

// Auto-rellenar hora fin = hora inicio + 1h
document.getElementById('slot-hi').addEventListener('change', function () {
    const [h, m] = this.value.split(':').map(Number);
    const total  = h * 60 + m + 60;
    const nh = String(Math.floor(total / 60) % 24).padStart(2, '0');
    const nm = String(total % 60).padStart(2, '0');
    document.getElementById('slot-hf').value = `${nh}:${nm}`;
});
</script>

</body>
</html>
