<?php
session_start();
require "../src/sesion/conexion.php";

// ── ACCESO: solo admins ──────────────────────────────────────
if (!isset($_SESSION['user']) || !isset($_SESSION['admin']) || $_SESSION['admin'] != 1) {
    header('Location: index.php');
    exit();
}

// ── BÚSQUEDA ─────────────────────────────────────────────────
$busqueda = isset($_GET['busqueda']) ? trim($_GET['busqueda']) : '';

if ($busqueda !== '') {
    $sql = "SELECT id_adoptante, nombre, apellido, email, admin, baneado
            FROM Usuario
            WHERE nombre LIKE ? OR apellido LIKE ? OR email LIKE ?
            ORDER BY nombre ASC";
    $like = "%$busqueda%";
    $stmt = $_conexion->prepare($sql);
    $stmt->bind_param("sss", $like, $like, $like);
} else {
    $sql = "SELECT id_adoptante, nombre, apellido, email, admin, baneado
            FROM Usuario
            ORDER BY nombre ASC";
    $stmt = $_conexion->prepare($sql);
}

$stmt->execute();
$result = $stmt->get_result();
$usuarios = [];
while ($row = $result->fetch_assoc()) {
    $usuarios[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moderación · Go Catch</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,900&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/material-design-iconic-font/2.2.0/css/material-design-iconic-font.min.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/perfil.css">
    <link rel="stylesheet" href="css/moderacion.css">
</head>
<body>

<!-- HEADER -->
<header>
    <nav class="hBotones">
        <a class="hBoton" href="index.php">INICIO</a>
        <a class="hBoton" href="#protectoras">PROTECTORAS</a>
    </nav>

    <nav id="header-izq">
        <a href="index.php">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200" width="40" height="40" role="img" aria-label="Go Catch"><rect x="0" y="0" width="200" height="200" rx="36" ry="36" fill="#C97041"/><text x="110" y="148" font-family="'Fraunces', serif" font-weight="900" font-size="145" fill="#FFFFFF" text-anchor="middle">gc</text></svg>
        </a>
    </nav>

    <nav class="hBotones">
        <?php if (isset($_SESSION['admin']) && $_SESSION['admin'] == 1): ?>
            <a class="hBoton" href="moderacion.php"><i class="zmdi zmdi-shield-security"></i> MODERACIÓN</a>
        <?php endif; ?>
        <a class="hBoton" href="perfil.php">
            <i class="zmdi zmdi-account"></i>
            <?= htmlspecialchars($_SESSION["nombre"]) ?>
        </a>
        <a href="../src/sesion/logout.php" id="boton-destacado">CERRAR SESIÓN</a>
    </nav>
</header>

<!-- CONTENIDO -->
<div id="padre-moderacion">
    <div class="mod-container">

        <div class="mod-header">
            <h1><i class="zmdi zmdi-shield-security"></i> Panel de Moderación</h1>
            <form class="search-form" method="GET" action="moderacion.php">
                <input
                    type="text"
                    name="busqueda"
                    placeholder="Buscar por nombre o email..."
                    value="<?= htmlspecialchars($busqueda) ?>"
                >
                <button type="submit"><i class="zmdi zmdi-search"></i> Buscar</button>
            </form>
        </div>

        <div class="mod-body">

            <?php if ($busqueda !== ''): ?>
                <p class="results-info">
                    <span><?= count($usuarios) ?></span>
                    resultado<?= count($usuarios) !== 1 ? 's' : '' ?> para
                    "<span><?= htmlspecialchars($busqueda) ?></span>"
                    — <a href="moderacion.php">limpiar búsqueda</a>
                </p>
            <?php else: ?>
                <p class="results-info">
                    Total: <span><?= count($usuarios) ?></span> usuario<?= count($usuarios) !== 1 ? 's' : '' ?> registrado<?= count($usuarios) !== 1 ? 's' : '' ?>
                </p>
            <?php endif; ?>

            <?php if (empty($usuarios)): ?>
                <div class="empty-state">
                    <i class="zmdi zmdi-accounts"></i>
                    <p>No se encontraron usuarios con ese criterio.</p>
                </div>
            <?php else: ?>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Nombre</th>
                            <th>Email</th>
                            <th>Estado</th>
                            <th>Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($usuarios as $u): ?>
                        <tr>
                            <td><?= (int)$u['id_adoptante'] ?></td>
                            <td>
                                <?= htmlspecialchars($u['nombre'] . ' ' . ($u['apellido'] ?? '')) ?>
                                <?php if ($u['admin']): ?>
                                    <span class="badge badge-admin">ADMIN</span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($u['email']) ?></td>
                            <td>
                                <?php if ($u['baneado']): ?>
                                    <span class="badge badge-baneado">BANEADO</span>
                                <?php else: ?>
                                    <span class="badge badge-activo">Activo</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($u['baneado'] || $u['admin']): ?>
                                    <button class="btn-banear ya-baneado" disabled>
                                        <?= $u['admin'] ? 'Admin' : 'Ya baneado' ?>
                                    </button>
                                <?php else: ?>
                                    <a class="btn-banear" href="banear.php?id=<?= (int)$u['id_adoptante'] ?>">
                                        <i class="zmdi zmdi-block-alt"></i> Banear
                                    </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>

        </div>
    </div>
</div>

</body>
</html>
