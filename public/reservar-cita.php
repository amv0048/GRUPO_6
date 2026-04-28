<?php
session_start();
require "../src/sesion/conexion.php";

// Solo usuarios adoptantes
if (!isset($_SESSION['id']) || !isset($_SESSION['user'])) {
    header('Location: login.html');
    exit();
}
$id_adoptante = (int)$_SESSION['id'];

// ── GARANTIZAR TABLAS ─────────────────────────────────────────
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
        notas                TEXT
    )
");

// ── VALIDAR SOLICITUD ─────────────────────────────────────────
if (!isset($_GET['solicitud']) || !is_numeric($_GET['solicitud'])) {
    header('Location: index.php');
    exit();
}
$id_solicitud = (int)$_GET['solicitud'];

// Verificar que la solicitud pertenece a este usuario y está activa
$st = $_conexion->prepare(
    "SELECT s.*, a.nombre AS nombre_animal, a.especie, a.id_protectora,
            p.nombre_protectora, p.ciudad,
            (SELECT g.ruta FROM Galeria g WHERE g.id_animal = a.id_animal ORDER BY g.es_principal DESC LIMIT 1) AS foto_animal
     FROM SolicitudAdopcion s
     JOIN Animales a ON s.id_animal = a.id_animal
     JOIN Protectora p ON a.id_protectora = p.id_protectora
     WHERE s.id_solicitud = ? AND s.id_adoptante = ?"
);
$st->bind_param("ii", $id_solicitud, $id_adoptante);
$st->execute();
$solicitud = $st->get_result()->fetch_assoc();
$st->close();

if (!$solicitud) {
    header('Location: index.php');
    exit();
}

// ── COMPROBAR SI YA TIENE CITA ────────────────────────────────
$chk = $_conexion->prepare(
    "SELECT c.id_cita, d.fecha, d.hora_inicio, d.hora_fin
     FROM CitaEntrevista c
     JOIN DisponibilidadProtectora d ON c.id_disponibilidad = d.id_disponibilidad
     WHERE c.id_solicitud = ? AND c.estado != 'CANCELADA'"
);
$chk->bind_param("i", $id_solicitud);
$chk->execute();
$cita_existente = $chk->get_result()->fetch_assoc();
$chk->close();

// ── ACCIÓN: cancelar cita ────────────────────────────────────
$msg = ''; $msg_tipo = '';
$reservada = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'cancelar' && $cita_existente) {
    $upd = $_conexion->prepare(
        "UPDATE CitaEntrevista SET estado = 'CANCELADA'
         WHERE id_cita = ? AND id_solicitud = ?"
    );
    $upd->bind_param("ii", $cita_existente['id_cita'], $id_solicitud);
    $upd->execute();
    $upd->close();
    $cita_existente = null;
    $msg = 'La entrevista ha sido cancelada. Puedes reservar otra fecha cuando quieras.';
    $msg_tipo = 'ok';
    // Recargar slots
    $sl2 = $_conexion->prepare(
        "SELECT d.*
         FROM DisponibilidadProtectora d
         LEFT JOIN CitaEntrevista c ON c.id_disponibilidad = d.id_disponibilidad AND c.estado != 'CANCELADA'
         WHERE d.id_protectora = ? AND d.disponible = 1 AND d.fecha >= CURDATE() AND c.id_cita IS NULL
         ORDER BY d.fecha, d.hora_inicio"
    );
    $sl2->bind_param("i", $solicitud['id_protectora']);
    $sl2->execute();
    $r2 = $sl2->get_result();
    $slots_disponibles = [];
    while ($row = $r2->fetch_assoc()) $slots_disponibles[$row['fecha']][] = $row;
    $sl2->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$cita_existente && ($_POST['accion'] ?? '') !== 'cancelar') {
    $id_slot = (int)($_POST['id_disponibilidad'] ?? 0);

    // Verificar que el slot está disponible y pertenece a la protectora correcta
    $sv = $_conexion->prepare(
        "SELECT d.id_disponibilidad
         FROM DisponibilidadProtectora d
         LEFT JOIN CitaEntrevista c
               ON c.id_disponibilidad = d.id_disponibilidad
              AND c.estado != 'CANCELADA'
         WHERE d.id_disponibilidad = ?
           AND d.id_protectora     = ?
           AND d.disponible        = 1
           AND d.fecha            >= CURDATE()
           AND c.id_cita           IS NULL"
    );
    $sv->bind_param("ii", $id_slot, $solicitud['id_protectora']);
    $sv->execute();
    $slot_ok = $sv->get_result()->num_rows > 0;
    $sv->close();

    if ($slot_ok) {
        $ins = $_conexion->prepare(
            "INSERT INTO CitaEntrevista (id_solicitud, id_disponibilidad) VALUES (?, ?)"
        );
        $ins->bind_param("ii", $id_solicitud, $id_slot);
        if ($ins->execute()) {
            // Actualizar estado solicitud a APROBADA automáticamente
            $upd = $_conexion->prepare("UPDATE SolicitudAdopcion SET estado_solicitud = 'APROBADA' WHERE id_solicitud = ?");
            $upd->bind_param("i", $id_solicitud);
            $upd->execute();
            $upd->close();

            // Recargar cita
            $chk2 = $_conexion->prepare(
                "SELECT c.id_cita, d.fecha, d.hora_inicio, d.hora_fin
                 FROM CitaEntrevista c
                 JOIN DisponibilidadProtectora d ON c.id_disponibilidad = d.id_disponibilidad
                 WHERE c.id_solicitud = ? AND c.estado != 'CANCELADA'"
            );
            $chk2->bind_param("i", $id_solicitud);
            $chk2->execute();
            $cita_existente = $chk2->get_result()->fetch_assoc();
            $chk2->close();
            $reservada = true;
        } else {
            $msg = 'Error al reservar. Inténtalo de nuevo.'; $msg_tipo = 'err';
        }
        $ins->close();
    } else {
        $msg = 'Ese horario ya no está disponible. Por favor elige otro.'; $msg_tipo = 'err';
    }
}

// ── CARGAR SLOTS DISPONIBLES ─────────────────────────────────
$slots_disponibles = [];
if (!$cita_existente) {
    $sl = $_conexion->prepare(
        "SELECT d.*
         FROM DisponibilidadProtectora d
         LEFT JOIN CitaEntrevista c
               ON c.id_disponibilidad = d.id_disponibilidad
              AND c.estado != 'CANCELADA'
         WHERE d.id_protectora = ?
           AND d.disponible    = 1
           AND d.fecha        >= CURDATE()
           AND c.id_cita       IS NULL
         ORDER BY d.fecha, d.hora_inicio"
    );
    $sl->bind_param("i", $solicitud['id_protectora']);
    $sl->execute();
    $r = $sl->get_result();
    while ($row = $r->fetch_assoc()) {
        $slots_disponibles[$row['fecha']][] = $row;
    }
    $sl->close();
}

$meses_es = ['','Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic'];
$dias_es  = ['Dom','Lun','Mar','Mié','Jue','Vie','Sáb'];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reservar entrevista · Go Catch</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,900&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/material-design-iconic-font/2.2.0/css/material-design-iconic-font.min.css">
    <link rel="stylesheet" href="css/header.css">
    <style>
        *, *::before, *::after { box-sizing: border-box; }
        body { font-family: 'Poppins', sans-serif; font-size: 13px; color: #333; margin: 0; background: #0D2D51; min-height: 100vh; }
        p, h1, h2, h3 { margin: 0; }
        a { text-decoration: none; }

        #page-wrapper { max-width: 760px; margin: 0 auto; padding: 36px 24px 60px; }

        .breadcrumb { font-size: 11px; color: #EDA677; margin-bottom: 22px; display: flex; align-items: center; gap: 6px; }
        .breadcrumb a { color: #EDA677; } .breadcrumb a:hover { color: #fff; }
        .breadcrumb span { color: #fff; opacity: .5; }

        /* ── TARJETA ANIMAL ── */
        .animal-resumen {
            display: flex; align-items: center; gap: 16px;
            background: rgba(255,255,255,.07); border: 1px solid rgba(255,255,255,.12);
            border-radius: 10px; padding: 14px 18px; margin-bottom: 24px;
        }
        .animal-foto { width: 64px; height: 64px; border-radius: 8px; overflow: hidden; background: #1a3f6a; flex-shrink: 0; }
        .animal-foto img { width: 100%; height: 100%; object-fit: cover; }
        .animal-info h2 { color: #fff; font-size: 16px; font-weight: 800; }
        .animal-info p   { color: #EDA677; font-size: 11px; margin-top: 3px; }

        /* ── CARD ── */
        .card { background: #fff; border-radius: 12px; box-shadow: 0 20px 60px rgba(0,0,0,.35); overflow: hidden; }
        .card-header { background: #0D2D51; padding: 20px 26px; border-bottom: 2px solid #CA7842; }
        .card-header h1 { color: #fff; font-size: 16px; font-weight: 800; }
        .card-header p  { color: rgba(255,255,255,.55); font-size: 11px; margin-top: 5px; }
        .card-body { padding: 24px 26px; }

        /* ── ÉXITO ── */
        .exito-wrapper { text-align: center; padding: 36px 20px; }
        .exito-icono { width: 70px; height: 70px; background: #EAF3DE; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; margin-bottom: 16px; }
        .exito-icono i { font-size: 34px; color: #2e7d12; }
        .exito-wrapper h2 { font-size: 20px; font-weight: 800; color: #0D2D51; }
        .exito-wrapper p  { font-size: 12px; color: #777; margin-top: 8px; line-height: 1.7; }
        .cita-resumen {
            display: inline-flex; align-items: center; gap: 10px;
            background: #EAF3DE; border-radius: 8px; padding: 12px 22px; margin: 16px 0;
            font-size: 14px; font-weight: 700; color: #2e7d12;
        }

        /* ── SELECTOR DE FECHA/HORA ── */
        .flash { padding: 12px 16px; border-radius: 8px; margin-bottom: 18px; font-size: 12px; font-weight: 600; }
        .flash-err { background: #FFEBEE; color: #c62828; }

        .dias-lista { display: flex; flex-direction: column; gap: 20px; }

        .dia-grupo {
            background: #f9f9f9;
            border: 1px solid #ececec;
            border-radius: 10px;
            padding: 16px 18px;
        }
        .dia-label {
            display: flex; align-items: center; gap: 10px; margin-bottom: 14px;
        }
        .dia-badge {
            display: flex; flex-direction: column; align-items: center; justify-content: center;
            width: 44px; height: 44px; border-radius: 8px; background: #0D2D51; color: #fff; flex-shrink: 0;
        }
        .dia-badge-dia { font-size: 18px; font-weight: 800; line-height: 1; }
        .dia-badge-mes { font-size: 9px; font-weight: 600; text-transform: uppercase; color: #EDA677; }
        .dia-badge-dow { font-size: 10px; color: rgba(255,255,255,.6); margin-top: 1px; }
        .dia-nombre { font-size: 13px; font-weight: 700; color: #0D2D51; }
        .dia-protectora { font-size: 11px; color: #aaa; }

        .horas-grid { display: flex; gap: 10px; flex-wrap: wrap; }
        .hora-btn {
            padding: 12px 20px; border-radius: 8px;
            border: 1.5px solid #e0e0e0; background: #fff;
            font-family: 'Poppins', sans-serif; font-size: 13px; font-weight: 700; color: #555;
            cursor: pointer; transition: border-color .2s, background .2s, color .2s, box-shadow .2s;
            display: flex; flex-direction: column; align-items: center; gap: 3px;
            box-shadow: 0 1px 4px rgba(0,0,0,.06);
            min-width: 80px;
        }
        .hora-btn:hover { border-color: #CA7842; background: #FFF1E6; color: #CA7842; box-shadow: 0 3px 10px rgba(202,120,66,.15); }
        .hora-btn.sel   { border-color: #CA7842; background: #CA7842; color: #fff; box-shadow: 0 4px 12px rgba(202,120,66,.3); }
        .hora-duracion  { font-size: 10px; font-weight: 500; opacity: .75; }

        .sin-slots { text-align: center; padding: 40px 20px; color: #999; }
        .sin-slots i { font-size: 36px; display: block; margin-bottom: 12px; color: #ddd; }

        /* Botón confirmar */
        .btn-confirmar {
            width: 100%; margin-top: 20px; padding: 14px;
            background: #CA7842; color: #fff; border: none; border-radius: 6px;
            font-family: 'Poppins', sans-serif; font-size: 13px; font-weight: 700; letter-spacing: 1px;
            cursor: pointer; transition: background .2s;
            display: flex; align-items: center; justify-content: center; gap: 8px;
        }
        .btn-confirmar:hover { background: #b56a32; }
        .btn-confirmar:disabled { background: #ddd; cursor: not-allowed; }

        .btn-sec {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 10px 20px; border-radius: 6px; border: 1.5px solid #CA7842;
            color: #CA7842; background: transparent; font-family: 'Poppins', sans-serif;
            font-size: 12px; font-weight: 600; cursor: pointer; transition: background .2s, color .2s;
            text-decoration: none; margin-top: 10px;
        }
        .btn-sec:hover { background: #CA7842; color: #fff; }

        .volver { margin-top: 24px; text-align: center; }
        .volver a { font-size: 12px; font-weight: 500; color: #EDA677; }
        .volver a:hover { color: #fff; }

        .cita-ya { background: #e8f0fe; border-radius: 10px; padding: 24px 20px; text-align: center; }
        .cita-ya i { font-size: 32px; color: #1a5cd6; display: block; margin-bottom: 10px; }
        .cita-ya h3 { color: #0D2D51; font-size: 16px; font-weight: 700; }
        .cita-ya p { color: #555; font-size: 12px; margin-top: 6px; }
        .cita-ya .fecha-grande { font-size: 20px; font-weight: 800; color: #0D2D51; margin-top: 12px; }

        .btn-cancelar-cita {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 10px 18px; border-radius: 6px; border: 1.5px solid #e0a0a0;
            background: #fff0f0; color: #c62828;
            font-family: 'Poppins', sans-serif; font-size: 12px; font-weight: 600;
            cursor: pointer; transition: background .2s, color .2s;
        }
        .btn-cancelar-cita:hover { background: #c62828; color: #fff; border-color: #c62828; }

        /* Modal */
        .modal-overlay {
            display: none; position: fixed; inset: 0;
            background: rgba(0,0,0,.55); align-items: center; justify-content: center; z-index: 1000;
        }
        .modal-overlay.activo { display: flex; }
        .modal-box {
            background: #fff; border-radius: 12px; padding: 36px 32px 28px;
            max-width: 360px; width: 90%; text-align: center;
            box-shadow: 0 20px 60px rgba(0,0,0,.3);
        }
        .modal-icono { font-size: 2.2rem; display: block; margin-bottom: 12px; }
        .modal-titulo { font-size: 16px; font-weight: 800; color: #0D2D51; margin-bottom: 8px; }
        .modal-msg { font-size: 12px; color: #777; line-height: 1.6; margin-bottom: 20px; }
        .modal-acciones { display: flex; gap: 10px; justify-content: center; }
        .modal-btn {
            padding: 10px 22px; border-radius: 6px; border: none;
            font-family: 'Poppins', sans-serif; font-size: 12px; font-weight: 700;
            cursor: pointer; transition: background .2s;
        }
        .modal-btn-cancelar  { background: #f0f0f0; color: #555; }
        .modal-btn-cancelar:hover  { background: #e0e0e0; }
        .modal-btn-confirmar { background: #c62828; color: #fff; }
        .modal-btn-confirmar:hover { background: #b71c1c; }

        .flash-ok { background: #EAF3DE; color: #2e7d12; border-radius: 8px; padding: 12px 16px; margin-bottom: 16px; font-size: 12px; font-weight: 600; }
    </style>
</head>
<body>

<header>
    <nav class="hBotones">
        <a class="hBoton" href="#protectoras">PROTECTORAS</a>
        <a class="hBoton" href="">COLABORADORES</a>
    </nav>
    <nav id="header-izq">
        <a href="index.php">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200" width="40" height="40" role="img" aria-label="Go Catch"><rect x="0" y="0" width="200" height="200" rx="36" ry="36" fill="#C97041"/><text x="110" y="148" font-family="'Fraunces', serif" font-weight="900" font-size="145" fill="#FFFFFF" text-anchor="middle">gc</text></svg>
        </a>
    </nav>
    <nav class="hBotones">
        <a class="hBoton" href="urgente.php">URGENTE</a>
        <a class="hBoton" href="perfil.php">
            <i class="zmdi zmdi-account"></i>
            <?= htmlspecialchars($_SESSION['nombre'] ?? '') ?>
        </a>
        <a href="../src/sesion/logout.php" id="boton-destacado">CERRAR SESIÓN</a>
    </nav>
</header>

<div id="page-wrapper">

    <div class="breadcrumb">
        <a href="index.php"><i class="zmdi zmdi-home"></i> Inicio</a>
        <span>/</span>
        <a href="ficha-animal.php?id=<?= (int)$solicitud['id_animal'] ?>"><?= htmlspecialchars($solicitud['nombre_animal']) ?></a>
        <span>/</span>
        Reservar entrevista
    </div>

    <!-- RESUMEN ANIMAL -->
    <div class="animal-resumen">
        <div class="animal-foto">
            <?php if (!empty($solicitud['foto_animal'])): ?>
                <img src="<?= htmlspecialchars($solicitud['foto_animal']) ?>" alt="">
            <?php endif; ?>
        </div>
        <div class="animal-info">
            <h2><?= htmlspecialchars($solicitud['nombre_animal']) ?></h2>
            <p><?= htmlspecialchars($solicitud['nombre_protectora'] . ($solicitud['ciudad'] ? ' · ' . $solicitud['ciudad'] : '')) ?></p>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <h1><i class="zmdi zmdi-calendar-check" style="color:#CA7842;margin-right:8px"></i>Reservar entrevista</h1>
            <p>Selecciona el día y hora que mejor te venga para conocer al animal y hablar con la protectora</p>
        </div>
        <div class="card-body">

            <?php if ($reservada): ?>
            <!-- ÉXITO: cita recién reservada -->
            <div class="exito-wrapper">
                <div class="exito-icono"><i class="zmdi zmdi-check-circle"></i></div>
                <h2>¡Entrevista reservada!</h2>
                <?php
                $fd = new DateTime($cita_existente['fecha']);
                $dow = $dias_es[(int)$fd->format('w')];
                ?>
                <div class="cita-resumen">
                    <i class="zmdi zmdi-calendar"></i>
                    <?= $dow . ' ' . $fd->format('d/m/Y') ?> · <?= substr($cita_existente['hora_inicio'],0,5) ?> – <?= substr($cita_existente['hora_fin'],0,5) ?>
                </div>
                <p>La protectora <strong><?= htmlspecialchars($solicitud['nombre_protectora']) ?></strong> ha recibido tu reserva. Podrás ver todos los detalles en tu perfil.</p>
                <div style="display:flex;gap:12px;justify-content:center;margin-top:20px;flex-wrap:wrap">
                    <a href="perfil.php" class="btn-sec"><i class="zmdi zmdi-account"></i> Ver mi perfil</a>
                    <a href="index.php" style="display:inline-flex;align-items:center;gap:6px;padding:10px 20px;border-radius:6px;background:#CA7842;color:#fff;font-family:'Poppins',sans-serif;font-size:12px;font-weight:700">
                        <i class="zmdi zmdi-home"></i> Ir al inicio
                    </a>
                </div>
            </div>

            <?php elseif ($cita_existente): ?>
            <!-- YA TIENE CITA -->
            <?php
            $fd = new DateTime($cita_existente['fecha']);
            $dow = $dias_es[(int)$fd->format('w')];
            ?>
            <div class="cita-ya">
                <i class="zmdi zmdi-calendar-check"></i>
                <h3>Ya tienes una entrevista reservada</h3>
                <p class="fecha-grande">
                    <?= $dow . ' ' . $fd->format('d') . ' de ' . $meses_es[(int)$fd->format('m')] . ' de ' . $fd->format('Y') ?>
                </p>
                <p style="font-size:16px;font-weight:700;color:#0D2D51;margin-top:4px">
                    <?= substr($cita_existente['hora_inicio'],0,5) ?> – <?= substr($cita_existente['hora_fin'],0,5) ?>
                </p>
                <p style="margin-top:10px">Con <strong><?= htmlspecialchars($solicitud['nombre_protectora']) ?></strong></p>
                <div style="display:flex;gap:10px;justify-content:center;margin-top:16px;flex-wrap:wrap">
                    <a href="perfil.php" class="btn-sec" style="width:fit-content">
                        <i class="zmdi zmdi-account"></i> Ver en mi perfil
                    </a>
                    <button type="button" class="btn-cancelar-cita" onclick="document.getElementById('modal-cancelar-cita').classList.add('activo')">
                        <i class="zmdi zmdi-close-circle"></i> Cancelar entrevista
                    </button>
                </div>
            </div>

            <!-- Modal confirmación cancelación -->
            <div id="modal-cancelar-cita" class="modal-overlay">
                <div class="modal-box">
                    <i class="zmdi zmdi-alert-circle modal-icono" style="color:#CA7842"></i>
                    <p class="modal-titulo">¿Cancelar la entrevista?</p>
                    <p class="modal-msg">Podrás reservar otra fecha disponible después de cancelar.</p>
                    <div class="modal-acciones">
                        <button type="button" class="modal-btn modal-btn-cancelar"
                                onclick="document.getElementById('modal-cancelar-cita').classList.remove('activo')">
                            Volver
                        </button>
                        <form method="POST" style="margin:0">
                            <input type="hidden" name="accion" value="cancelar">
                            <button type="submit" class="modal-btn modal-btn-confirmar">Sí, cancelar</button>
                        </form>
                    </div>
                </div>
            </div>

            <?php else: ?>
            <!-- ELEGIR HORARIO -->
            <?php if ($msg): ?>
                <div class="flash flash-<?= $msg_tipo === 'ok' ? 'ok' : 'err' ?>"><?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>

            <form method="POST" id="form-reserva">
                <input type="hidden" name="id_disponibilidad" id="campo-slot" value="">

                <?php if (empty($slots_disponibles)): ?>
                <div class="sin-slots">
                    <i class="zmdi zmdi-calendar-close"></i>
                    <p>La protectora aún no tiene horarios disponibles para entrevistas.<br>Vuelve a intentarlo en unos días.</p>
                </div>

                <?php else: ?>
                <div class="dias-lista">
                    <?php foreach ($slots_disponibles as $fecha => $slots):
                        $fd  = new DateTime($fecha);
                        $dow = $dias_es[(int)$fd->format('w')];
                        $mes_txt = $meses_es[(int)$fd->format('m')];
                    ?>
                    <div class="dia-grupo">
                        <div class="dia-label">
                            <div class="dia-badge">
                                <span class="dia-badge-dia"><?= $fd->format('d') ?></span>
                                <span class="dia-badge-mes"><?= strtoupper($mes_txt) ?></span>
                                <span class="dia-badge-dow"><?= $dow ?></span>
                            </div>
                            <div>
                                <p class="dia-nombre"><?= $dow . ' ' . $fd->format('d') . ' de ' . $mes_txt ?></p>
                                <p class="dia-protectora"><?= htmlspecialchars($solicitud['nombre_protectora']) ?></p>
                            </div>
                        </div>
                        <div class="horas-grid">
                            <?php foreach ($slots as $slot):
                                $hi = substr($slot['hora_inicio'], 0, 5);
                                $hf = substr($slot['hora_fin'],    0, 5);
                                // Calcular duración
                                $dur = (strtotime($slot['hora_fin']) - strtotime($slot['hora_inicio'])) / 60;
                                $dur_txt = $dur >= 60 ? ($dur / 60) . 'h' : $dur . 'min';
                            ?>
                            <button type="button" class="hora-btn"
                                    onclick="seleccionarSlot(this, <?= (int)$slot['id_disponibilidad'] ?>)">
                                <span><?= $hi ?></span>
                                <span class="hora-duracion"><?= $dur_txt ?></span>
                            </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <button type="submit" class="btn-confirmar" id="btn-confirmar" disabled>
                    <i class="zmdi zmdi-calendar-check"></i>
                    CONFIRMAR RESERVA
                </button>
                <?php endif; ?>
            </form>

            <?php endif; ?>

        </div>
    </div>

    <div class="volver">
        <a href="ficha-animal.php?id=<?= (int)$solicitud['id_animal'] ?>">← Volver a la ficha de <?= htmlspecialchars($solicitud['nombre_animal']) ?></a>
    </div>

</div>

<script>
function seleccionarSlot(btn, id) {
    document.querySelectorAll('.hora-btn').forEach(b => b.classList.remove('sel'));
    btn.classList.add('sel');
    document.getElementById('campo-slot').value = id;
    document.getElementById('btn-confirmar').disabled = false;
}
</script>
</body>
</html>
