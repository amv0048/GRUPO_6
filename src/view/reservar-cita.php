<?php
session_start();
require "../sesion/conexion.php";
require_once "../model/AdopcionModel.php";

// Solo usuarios adoptantes
if (!isset($_SESSION['id']) || !isset($_SESSION['user'])) {
    header('Location: /public/login.html');
    exit();
}
$id_adoptante  = (int)$_SESSION['id'];
$adopcionModel = new AdopcionModel($_conexion);

if (!isset($_GET['solicitud']) || !is_numeric($_GET['solicitud'])) {
    header('Location: /src/view/index.php');
    exit();
}
$id_solicitud = (int)$_GET['solicitud'];

$solicitud = $adopcionModel->getSolicitudConAnimal($id_solicitud, $id_adoptante);
if (!$solicitud) {
    header('Location: /src/view/index.php');
    exit();
}

$cita_existente = $adopcionModel->getCitaExistente($id_solicitud);

$msg = ''; $msg_tipo = '';
$reservada = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['accion'] ?? '') === 'cancelar' && $cita_existente) {
    $adopcionModel->cancelarCitaPorSolicitud($cita_existente['id_cita'], $id_solicitud);
    $cita_existente = null;
    $msg = 'La entrevista ha sido cancelada. Puedes reservar otra fecha cuando quieras.';
    $msg_tipo = 'ok';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$cita_existente && ($_POST['accion'] ?? '') !== 'cancelar') {
    $id_slot = (int)($_POST['id_disponibilidad'] ?? 0);

    if ($adopcionModel->verificarSlotDisponible($id_slot, $solicitud['id_protectora'])) {
        if ($adopcionModel->reservarCita($id_solicitud, $id_slot)) {
            $adopcionModel->updateEstado($id_solicitud, 'APROBADA', $solicitud['id_protectora']);
            $cita_existente = $adopcionModel->getCitaExistente($id_solicitud);
            $reservada = true;
        } else {
            $msg = 'Error al reservar. Inténtalo de nuevo.'; $msg_tipo = 'err';
        }
    } else {
        $msg = 'Ese horario ya no está disponible. Por favor elige otro.'; $msg_tipo = 'err';
    }
}

$slots_disponibles = [];
if (!$cita_existente) {
    $slots_disponibles = $adopcionModel->getSlotsByProtectora($solicitud['id_protectora']);
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
    <link rel="stylesheet" href="/public/css/header.css">
    <link rel="stylesheet" href="/public/css/reservar-cita.css">
</head>
<body>

<header>
    <nav class="hBotones">
        <a class="hBoton" href="#protectoras">PROTECTORAS</a>
        <a class="hBoton" href="">COLABORADORES</a>
    </nav>
    <nav id="header-izq">
        <a href="/src/view/index.php">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200" width="40" height="40" role="img" aria-label="Go Catch"><rect x="0" y="0" width="200" height="200" rx="36" ry="36" fill="#C97041"/><text x="110" y="148" font-family="'Fraunces', serif" font-weight="900" font-size="145" fill="#FFFFFF" text-anchor="middle">gc</text></svg>
        </a>
    </nav>
    <nav class="hBotones">
        <a class="hBoton" href="/src/view/urgente.php">URGENTE</a>
        <a class="hBoton" href="/src/view/perfil.php">
            <i class="zmdi zmdi-account"></i>
            <?= htmlspecialchars($_SESSION['nombre'] ?? '') ?>
        </a>
        <a href="/src/sesion/logout.php" id="boton-destacado">CERRAR SESIÓN</a>
    </nav>
</header>

<div id="page-wrapper">

    <div class="breadcrumb">
        <a href="/src/view/index.php"><i class="zmdi zmdi-home"></i> Inicio</a>
        <span>/</span>
        <a href="/src/view/ficha-animal.php?id=<?= (int)$solicitud['id_animal'] ?>"><?= htmlspecialchars($solicitud['nombre_animal']) ?></a>
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
                    <a href="/src/view/perfil.php" class="btn-sec"><i class="zmdi zmdi-account"></i> Ver mi perfil</a>
                    <a href="/src/view/index.php" style="display:inline-flex;align-items:center;gap:6px;padding:10px 20px;border-radius:6px;background:#CA7842;color:#fff;font-family:'Poppins',sans-serif;font-size:12px;font-weight:700">
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
                    <a href="/src/view/perfil.php" class="btn-sec" style="width:fit-content">
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
        <a href="/src/view/ficha-animal.php?id=<?= (int)$solicitud['id_animal'] ?>">← Volver a la ficha de <?= htmlspecialchars($solicitud['nombre_animal']) ?></a>
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
