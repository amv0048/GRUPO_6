<?php
session_start();
require "../src/sesion/conexion.php";

error_reporting(E_ALL);
ini_set("display_errors", 1);

// Solo protectoras pueden acceder
if (!isset($_SESSION["id"])) {
    header("Location: login.html");
    exit();
}
if (isset($_SESSION["user"])) {
    header("Location: index.php");
    exit();
}

$id_protectora = $_SESSION["id"];

// ── ELIMINAR ANIMAL ──────────────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "eliminar") {
    $id_animal = (int) $_POST["id_animal"];

    // Verificamos que el animal pertenece a esta protectora antes de borrar
    $check = $_conexion->prepare("SELECT id_animal FROM Animales WHERE id_animal = ? AND id_protectora = ?");
    $check->bind_param("ii", $id_animal, $id_protectora);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $del = $_conexion->prepare("DELETE FROM Animales WHERE id_animal = ?");
        $del->bind_param("i", $id_animal);
        if ($del->execute()) {
            $msg_ok  = "Animal eliminado correctamente.";
        } else {
            $msg_err = "No se pudo eliminar el animal.";
        }
        $del->close();
    } else {
        $msg_err = "No tienes permiso para eliminar ese animal.";
    }
    $check->close();
}

// ── CARGA DE ANIMALES ────────────────────────────────────────
$consulta = $_conexion->prepare(
    "SELECT a.id_animal, a.especie, a.raza, a.sexo, a.color, a.peso, a.edad,
            a.fecha_entrada, a.descripcion,
            a.compatibilidad_perros, a.compatibilidad_gatos, a.compatibilidad_ninos,
            e.nombre AS estado
     FROM Animales a
     LEFT JOIN EstadoAnimal e ON a.id_estado = e.id_estado
     WHERE a.id_protectora = ?
     ORDER BY a.id_animal DESC"
);
$consulta->bind_param("i", $id_protectora);
$consulta->execute();
$animales = $consulta->get_result()->fetch_all(MYSQLI_ASSOC);
$consulta->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Animales · Go Catch</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
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

        /* ── CONTENEDOR PRINCIPAL ── */
        #main-wrapper {
            max-width: 1100px;
            margin: 0 auto;
            padding: 36px 24px 60px;
        }

        /* ── CABECERA DE SECCIÓN ── */
        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 24px;
        }
        .section-header h2 {
            color: #fff;
            font-size: 20px;
            font-weight: 700;
            letter-spacing: 0.5px;
        }
        .section-header span {
            font-size: 13px;
            color: #EDA677;
            font-weight: 500;
        }

        /* ── BOTÓN AÑADIR ── */
        .btn-add {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #CA7842;
            color: #fff;
            font-family: 'Poppins', sans-serif;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: 1px;
            padding: 10px 20px;
            border-radius: 6px;
            border: none;
            cursor: pointer;
            text-decoration: none;
            transition: background 0.2s, transform 0.15s;
        }
        .btn-add:hover {
            background: #b56a32;
            transform: translateY(-1px);
            color: #fff;
        }

        /* ── MENSAJES ── */
        .msg-ok, .msg-err {
            padding: 10px 16px;
            border-radius: 6px;
            font-size: 12px;
            margin-bottom: 20px;
        }
        .msg-ok  { background: #EAF3DE; border: 1px solid #97C459; color: #173404; }
        .msg-err { background: #FCEBEB; border: 1px solid #F09595; color: #8B0000; }

        /* ── TARJETA / TABLA ── */
        .card {
            background: #fff;
            border-radius: 8px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.35);
            overflow: hidden;
        }

        /* ── ESTADO VACÍO ── */
        .empty-state {
            padding: 60px 24px;
            text-align: center;
            color: #aaa;
        }
        .empty-state i {
            font-size: 48px;
            display: block;
            margin-bottom: 14px;
            color: #ddd;
        }
        .empty-state p { font-size: 14px; margin-bottom: 20px; }

        /* ── TABLE ── */
        .tabla-wrapper { overflow-x: auto; }

        table {
            width: 100%;
            border-collapse: collapse;
            min-width: 860px;
        }
        thead tr {
            background: #0D2D51;
            color: #EDA677;
        }
        thead th {
            padding: 13px 14px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            text-align: left;
            white-space: nowrap;
        }
        tbody tr {
            border-bottom: 1px solid #f0f0f0;
            transition: background 0.15s;
        }
        tbody tr:last-child { border-bottom: none; }
        tbody tr:hover { background: #fafafa; }
        tbody td {
            padding: 12px 14px;
            font-size: 12px;
            color: #444;
            vertical-align: middle;
        }

        /* ── BADGES ── */
        .badge {
            display: inline-block;
            padding: 3px 9px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 600;
            letter-spacing: 0.5px;
            text-transform: uppercase;
            white-space: nowrap;
        }
        .badge-disponible { background: #EAF3DE; color: #2e7d12; }
        .badge-adoptado   { background: #e8f0fe; color: #1a5cd6; }
        .badge-reservado  { background: #FFF8E1; color: #b8860b; }
        .badge-en_acogida { background: #F3E5F5; color: #7b1fa2; }
        .badge-default    { background: #f0f0f0; color: #777; }

        .compat-icons { display: flex; gap: 6px; }
        .compat-icons i {
            font-size: 15px;
            color: #ccc;
        }
        .compat-icons i.activo { color: #CA7842; }

        /* ── BOTONES ACCIÓN ── */
        .acciones { display: flex; gap: 8px; white-space: nowrap; }

        .btn-editar, .btn-eliminar {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 6px 12px;
            border-radius: 5px;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 0.5px;
            cursor: pointer;
            border: none;
            font-family: 'Poppins', sans-serif;
            transition: background 0.2s, transform 0.15s;
            text-decoration: none;
        }
        .btn-editar {
            background: #e8f0fe;
            color: #1a5cd6;
        }
        .btn-editar:hover {
            background: #c8d8fc;
            transform: translateY(-1px);
            color: #1a5cd6;
        }
        .btn-eliminar {
            background: #FCEBEB;
            color: #c0392b;
        }
        .btn-eliminar:hover {
            background: #f5c6c6;
            transform: translateY(-1px);
        }

        /* ── VOLVER ── */
        .volver {
            margin-top: 28px;
            text-align: center;
        }
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

<header>
    <nav class="hBotones">
        <a class="hBoton" href="" target="_self">PROTECTORAS</a>
        <a class="hBoton" href="" target="_self">COLABORADORES</a>
    </nav>
    <nav id="header-izq">
        <a href="index.php" target="_self">
            <img src="../img/profile/default/oficiales/logo.svg" alt="Go Catch" height="40">
        </a>
    </nav>
    <nav class="hBotones">
        <a class="hBoton" href="" target="_self">URGENTE</a>
        <a class="hBoton" href="perfil.php" target="_self">MI PERFIL</a>
        <a href="listaAnimal.php" target="_self" id="boton-destacado">MIS ANIMALES</a>
    </nav>
</header>

<div id="main-wrapper">

    <!-- CABECERA -->
    <div class="section-header">
        <div>
            <h2>Mis Animales</h2>
            <span><?= count($animales) ?> animal<?= count($animales) !== 1 ? 'es' : '' ?> registrado<?= count($animales) !== 1 ? 's' : '' ?></span>
        </div>
        <a href="addAnimal.php" class="btn-add">
            <i class="zmdi zmdi-plus"></i> AÑADIR ANIMAL
        </a>
    </div>

    <!-- MENSAJES -->
    <?php if (isset($msg_ok)): ?>
        <div class="msg-ok"><?= $msg_ok ?></div>
    <?php endif; ?>
    <?php if (isset($msg_err)): ?>
        <div class="msg-err"><?= $msg_err ?></div>
    <?php endif; ?>

    <!-- TABLA / ESTADO VACÍO -->
    <div class="card">

        <?php if (empty($animales)): ?>
            <div class="empty-state">
                <i class="zmdi zmdi-collection-item-3"></i>
                <p>Todavía no tienes ningún animal registrado.</p>
                <a href="addAnimal.php" class="btn-add">
                    <i class="zmdi zmdi-plus"></i> AÑADIR PRIMER ANIMAL
                </a>
            </div>
        <?php else: ?>
            <div class="tabla-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Especie</th>
                            <th>Raza</th>
                            <th>Sexo</th>
                            <th>Color</th>
                            <th>Edad</th>
                            <th>Peso (kg)</th>
                            <th>Entrada</th>
                            <th>Estado</th>
                            <th>Compat.</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($animales as $a): ?>
                        <tr>
                            <td><?= $a['id_animal'] ?></td>
                            <td><?= htmlspecialchars($a['especie'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($a['raza']    ?? '—') ?></td>
                            <td>
                                <?php
                                    $sexo_map = ['M' => 'Macho', 'H' => 'Hembra'];
                                    echo htmlspecialchars($sexo_map[$a['sexo']] ?? '—');
                                ?>
                            </td>
                            <td><?= htmlspecialchars($a['color'] ?? '—') ?></td>
                            <td><?= $a['edad'] !== null ? $a['edad'] . ' años' : '—' ?></td>
                            <td><?= $a['peso'] !== null ? number_format($a['peso'], 2) : '—' ?></td>
                            <td><?= $a['fecha_entrada'] ?? '—' ?></td>
                            <td>
                                <?php
                                    $estado     = strtolower($a['estado'] ?? '');
                                    $badge_map  = [
                                        'disponible' => 'badge-disponible',
                                        'adoptado'   => 'badge-adoptado',
                                        'reservado'  => 'badge-reservado',
                                        'en_acogida' => 'badge-en_acogida',
                                    ];
                                    $badge_class = $badge_map[$estado] ?? 'badge-default';
                                ?>
                                <span class="badge <?= $badge_class ?>">
                                    <?= htmlspecialchars($a['estado'] ?? '—') ?>
                                </span>
                            </td>
                            <td>
                                <div class="compat-icons">
                                    <i class="zmdi zmdi-mood <?= $a['compatibilidad_ninos']  ? 'activo' : '' ?>" title="Niños"></i>
                                    <i class="zmdi zmdi-paw   <?= $a['compatibilidad_perros'] ? 'activo' : '' ?>" title="Perros"></i>
                                    <i class="zmdi zmdi-toys  <?= $a['compatibilidad_gatos']  ? 'activo' : '' ?>" title="Gatos"></i>
                                </div>
                            </td>
                            <td>
                                <div class="acciones">
                                    <a href="editAnimal.php?id=<?= $a['id_animal'] ?>" class="btn-editar">
                                        <i class="zmdi zmdi-edit"></i> Editar
                                    </a>
                                    <form method="POST" action="listaAnimal.php"
                                          onsubmit="return confirm('¿Seguro que quieres eliminar este animal?')">
                                        <input type="hidden" name="action"    value="eliminar">
                                        <input type="hidden" name="id_animal" value="<?= $a['id_animal'] ?>">
                                        <button type="submit" class="btn-eliminar">
                                            <i class="zmdi zmdi-delete"></i> Eliminar
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    </div>

    <div class="volver">
        <a href="index.php">← Volver al inicio</a>
    </div>

</div>

</body>
</html>
