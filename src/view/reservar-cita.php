<?php
session_start();
require "../sesion/conexion.php";

// Solo usuarios adoptantes
if (!isset($_SESSION['id']) || !isset($_SESSION['user'])) {
    header('Location: ../../public/login.html');
    exit();
}
$id_adoptante = (int)$_SESSION['id'];

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
    <link rel="stylesheet" href="../../public/css/header.css">
    <link rel="stylesheet" href="../../public/css/reservar-cita.css">
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
        <a href="../sesion/logout.php" id="boton-destacado">CERRAR SESIÓN</a>
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
