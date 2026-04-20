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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/material-design-iconic-font/2.2.0/css/material-design-iconic-font.min.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/perfil.css">
    <style>
        #padre-moderacion {
            min-height: calc(100vh - 85px);
            background: #0D2D51;
            padding: 40px 24px;
        }

        .mod-container {
            max-width: 900px;
            margin: 0 auto;
            background: #fff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 20px 60px rgba(0,0,0,0.35);
        }

        .mod-header {
            background: #0D2D51;
            padding: 28px 36px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
            flex-wrap: wrap;
        }

        .mod-header h1 {
            color: #EDA677;
            font-size: 1.3rem;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .mod-header h1 i {
            margin-right: 8px;
        }

        /* Buscador */
        .search-form {
            display: flex;
            gap: 10px;
        }

        .search-form input {
            padding: 9px 14px;
            border: 2px solid #EDA677;
            border-radius: 7px;
            background: rgba(255,255,255,0.08);
            color: #fff;
            font-family: 'Poppins', sans-serif;
            font-size: 0.85rem;
            outline: none;
            min-width: 220px;
            transition: border-color 0.2s;
        }

        .search-form input::placeholder {
            color: rgba(255,255,255,0.45);
        }

        .search-form input:focus {
            border-color: #CA7842;
            background: rgba(255,255,255,0.12);
        }

        .search-form button {
            padding: 9px 18px;
            background: #CA7842;
            color: #fff;
            border: none;
            border-radius: 7px;
            font-family: 'Poppins', sans-serif;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
        }

        .search-form button:hover {
            background: #EDA677;
        }

        /* Tabla */
        .mod-body {
            padding: 28px 36px;
        }

        .results-info {
            font-size: 0.82rem;
            color: #888;
            margin-bottom: 16px;
        }

        .results-info span {
            color: #CA7842;
            font-weight: 600;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        thead th {
            background: #f5f5f5;
            color: #0D2D51;
            font-size: 0.78rem;
            font-weight: 700;
            letter-spacing: 0.6px;
            text-transform: uppercase;
            padding: 12px 14px;
            text-align: left;
            border-bottom: 2px solid #e0e0e0;
        }

        tbody tr {
            border-bottom: 1px solid #f0f0f0;
            transition: background 0.15s;
        }

        tbody tr:hover {
            background: #fdf6f1;
        }

        tbody td {
            padding: 12px 14px;
            font-size: 0.85rem;
            vertical-align: middle;
            color: #333;
        }

        /* Badge estado */
        .badge {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 12px;
            font-size: 0.72rem;
            font-weight: 600;
            letter-spacing: 0.3px;
        }

        .badge-activo {
            background: #e6f4ea;
            color: #2e7d32;
        }

        .badge-baneado {
            background: #fdecea;
            color: #c62828;
        }

        .badge-admin {
            background: #e8eaf6;
            color: #283593;
            margin-left: 6px;
        }

        /* Botón banear */
        .btn-banear {
            display: inline-block;
            padding: 7px 16px;
            background: #c62828;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-family: 'Poppins', sans-serif;
            font-size: 0.78rem;
            font-weight: 600;
            text-decoration: none;
            cursor: pointer;
            transition: background 0.2s, transform 0.1s;
        }

        .btn-banear:hover {
            background: #b71c1c;
            transform: translateY(-1px);
            color: #fff;
        }

        .btn-banear:disabled,
        .btn-banear.ya-baneado {
            background: #bbb;
            cursor: not-allowed;
            transform: none;
        }

        /* Sin resultados */
        .empty-state {
            text-align: center;
            padding: 48px 24px;
            color: #999;
        }

        .empty-state i {
            font-size: 2.5rem;
            color: #ccc;
            display: block;
            margin-bottom: 12px;
        }
    </style>
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
            <img src="../img/profile/default/oficiales/logo.svg" alt="Go Catch" height="40">
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
