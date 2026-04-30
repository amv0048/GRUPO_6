<?php
session_start();
require "../sesion/conexion.php";
require_once "../model/AnimalModel.php";

// Solo protectoras pueden acceder
if (!isset($_SESSION["id"])) {
    header("Location: ../../public/login.html");
    exit();
}
if (isset($_SESSION["user"])) {
    header("Location: index.php");
    exit();
}

$id_protectora = $_SESSION["id"];
$animalModel   = new AnimalModel($_conexion);

// ── ELIMINAR ANIMAL ──────────────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "eliminar") {
    $id_animal = (int) $_POST["id_animal"];
    if ($animalModel->delete($id_animal, $id_protectora)) {
        $msg_ok  = "Animal eliminado correctamente.";
    } else {
        $msg_err = "No tienes permiso para eliminar ese animal o no existe.";
    }
}

// ── CARGA DE ANIMALES ────────────────────────────────────────
$animales = $animalModel->getAllByProtectora($id_protectora);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Animales · Go Catch</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,900&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/material-design-iconic-font/2.2.0/css/material-design-iconic-font.min.css">
    <link rel="stylesheet" href="../../public/css/header.css">
    <link rel="stylesheet" href="../../public/css/listaAnimal.css">
</head>
<body>

<header>
    <nav class="hBotones">
        <a class="hBoton" href="" target="_self">PROTECTORAS</a>
        <a class="hBoton" href="" target="_self">COLABORADORES</a>
    </nav>
    <nav id="header-izq">
        <a href="index.php" target="_self">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200" width="40" height="40" role="img" aria-label="Go Catch"><rect x="0" y="0" width="200" height="200" rx="36" ry="36" fill="#C97041"/><text x="110" y="148" font-family="'Fraunces', serif" font-weight="900" font-size="145" fill="#FFFFFF" text-anchor="middle">gc</text></svg>
        </a>
    </nav>
    <nav class="hBotones">
        <a class="hBoton" href="urgente.php" target="_self">URGENTE</a>
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
                            <th>Foto</th>
                            <th>Nombre</th>
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
                            <td>
                                <?php if (!empty($a['foto'])): ?>
                                    <img src="<?= htmlspecialchars($a['foto']) ?>"
                                         alt="foto"
                                         style="width:70px;height:56px;object-fit:cover;border-radius:6px;display:block;">
                                <?php else: ?>
                                    <div style="width:70px;height:56px;background:#f0f0f0;border-radius:6px;display:flex;align-items:center;justify-content:center;">
                                        <i class="zmdi zmdi-collection-item-3" style="color:#ccc;font-size:22px;"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td><strong><?= htmlspecialchars($a['nombre'] ?? '—') ?></strong></td>
                            <td><?= htmlspecialchars($a['especie'] ?? '—') ?></td>
                            <td><?= htmlspecialchars($a['raza'] ?? '—') ?></td>
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
                                    <a href="ficha-animal.php?id=<?= $a['id_animal'] ?>" class="btn-info">
                                        <i class="zmdi zmdi-eye"></i> Info
                                    </a>
                                    <a href="editAnimal.php?id=<?= $a['id_animal'] ?>" class="btn-editar">
                                        <i class="zmdi zmdi-edit"></i> Editar
                                    </a>
                                    <form method="POST" action="listaAnimal.php"
                                          onsubmit="return confirm('¿Seguro que quieres eliminar este animal?')"
                                          style="margin:0">
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
