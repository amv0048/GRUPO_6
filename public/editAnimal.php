<?php
session_start();
require "../src/sesion/conexion.php";

error_reporting(E_ALL);
ini_set("display_errors", 1);

// Solo protectoras
if (!isset($_SESSION["id"])) {
    header("Location: login.html");
    exit();
}
if (isset($_SESSION["user"])) {
    header("Location: index.php");
    exit();
}

$id_protectora = $_SESSION["id"];

// ── VALIDAR ID RECIBIDO ──────────────────────────────────────
if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: listaAnimal.php");
    exit();
}
$id_animal = (int) $_GET["id"];

// ── CARGAR DATOS DEL ANIMAL (y verificar pertenencia) ────────
$q = $_conexion->prepare(
    "SELECT a.*, e.id_estado AS estado_actual
     FROM Animales a
     LEFT JOIN EstadoAnimal e ON a.id_estado = e.id_estado
     WHERE a.id_animal = ? AND a.id_protectora = ?"
);
$q->bind_param("ii", $id_animal, $id_protectora);
$q->execute();
$animal = $q->get_result()->fetch_assoc();
$q->close();

if (!$animal) {
    // No existe o no pertenece a esta protectora
    header("Location: listaAnimal.php");
    exit();
}

// ── PROCESAR ACTUALIZACIÓN ───────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $especie     = htmlspecialchars(trim($_POST["especie"]));
    $raza        = htmlspecialchars(trim($_POST["raza"]));
    $sexo        = in_array($_POST["sexo"], ["M", "H"]) ? $_POST["sexo"] : null;
    $color       = htmlspecialchars(trim($_POST["color"]));
    $edad        = is_numeric($_POST["edad"])  ? (int)$_POST["edad"]   : null;
    $peso        = is_numeric($_POST["peso"])  ? (float)$_POST["peso"] : null;
    $fecha       = !empty($_POST["fecha_entrada"]) ? $_POST["fecha_entrada"] : null;
    $descripcion = htmlspecialchars(trim($_POST["descripcion"]));
    $id_estado   = is_numeric($_POST["id_estado"]) ? (int)$_POST["id_estado"] : null;

    $compat_perros = isset($_POST["compat_perros"]) ? 1 : 0;
    $compat_gatos  = isset($_POST["compat_gatos"])  ? 1 : 0;
    $compat_ninos  = isset($_POST["compat_ninos"])  ? 1 : 0;

    $sql = "UPDATE Animales SET
                id_estado = ?, especie = ?, raza = ?, sexo = ?, color = ?,
                peso = ?, edad = ?, fecha_entrada = ?, descripcion = ?,
                compatibilidad_perros = ?, compatibilidad_gatos = ?, compatibilidad_ninos = ?
            WHERE id_animal = ? AND id_protectora = ?";

    $stmt = $_conexion->prepare($sql);
    // 14 parámetros: id_estado(i), especie(s), raza(s), sexo(s), color(s),
    //                peso(d), edad(i), fecha_entrada(s), descripcion(s),
    //                compat_perros(i), compat_gatos(i), compat_ninos(i),
    //                id_animal(i), id_protectora(i)
    $stmt->bind_param(
        "issssdissiiiii",
        $id_estado, $especie, $raza, $sexo, $color,
        $peso, $edad, $fecha, $descripcion,
        $compat_perros, $compat_gatos, $compat_ninos,
        $id_animal, $id_protectora
    );

    if ($stmt->execute()) {
        $stmt->close();
        header("Location: listaAnimal.php?edited=1");
        exit();
    } else {
        $err_db = "No se pudo actualizar el animal. Inténtalo de nuevo.";
    }
    $stmt->close();
}

// ── CARGAR ESTADOS ───────────────────────────────────────────
$estados = $_conexion->query("SELECT * FROM EstadoAnimal ORDER BY id_estado")->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Animal · Go Catch</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/material-design-iconic-font/2.2.0/css/material-design-iconic-font.min.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/perfil.css">
    <style>
        #padre-nuestro { align-items: flex-start; padding: 40px 20px; }
        #estructura { max-width: 680px; }

        .form-section-title {
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #999;
            margin: 24px 0 14px;
            border-top: 1px solid #f0f0f0;
            padding-top: 20px;
        }
        .form-section-title:first-child { margin-top: 0; border-top: none; padding-top: 0; }

        select.form-control {
            appearance: none;
            -webkit-appearance: none;
            cursor: pointer;
            padding-right: 24px;
        }

        textarea.form-control {
            height: auto;
            min-height: 70px;
            resize: vertical;
            padding-top: 6px;
        }

        .compat-group {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            margin-bottom: 20px;
        }
        .compat-item {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            font-size: 12px;
            color: #555;
            font-weight: 500;
        }
        .compat-item input[type="checkbox"] {
            accent-color: #CA7842;
            width: 16px;
            height: 16px;
            cursor: pointer;
        }
        .compat-item i { color: #CA7842; font-size: 16px; }

        /* badge ID en la cabecera */
        .id-badge {
            display: inline-block;
            background: rgba(202,120,66,0.25);
            color: #EDA677;
            font-size: 11px;
            font-weight: 600;
            letter-spacing: 1px;
            padding: 3px 10px;
            border-radius: 20px;
            margin-top: 4px;
        }
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

<div id="padre-nuestro">
    <div id="estructura">

        <!-- CABECERA -->
        <div id="perfil-header">
            <h2 id="perfil-nombre">
                <?= htmlspecialchars($animal['especie'] ?? 'Animal') ?>
                <?= $animal['raza'] ? '— ' . htmlspecialchars($animal['raza']) : '' ?>
            </h2>
            <p id="perfil-tipo">Editar ficha</p>
            <span class="id-badge">ID #<?= $id_animal ?></span>
        </div>

        <!-- MENSAJES -->
        <?php if (isset($err_db)): ?>
            <div class="msg-error"><?= $err_db ?></div>
        <?php endif; ?>

        <!-- FORMULARIO -->
        <div id="perfil-form-area">
            <form action="editAnimal.php?id=<?= $id_animal ?>" method="POST" id="registro">

                <p class="form-section-title">Datos básicos</p>

                <div class="form-grid">
                    <div class="form-wrapper">
                        <input type="text" name="especie" class="form-control"
                               placeholder="Especie"
                               value="<?= htmlspecialchars($animal['especie'] ?? '') ?>">
                        <i class="zmdi zmdi-paw"></i>
                    </div>
                    <div class="form-wrapper">
                        <input type="text" name="raza" class="form-control"
                               placeholder="Raza"
                               value="<?= htmlspecialchars($animal['raza'] ?? '') ?>">
                        <i class="zmdi zmdi-collection-item-3"></i>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-wrapper">
                        <select name="sexo" class="form-control">
                            <option value="" disabled <?= !$animal['sexo'] ? 'selected' : '' ?>>Sexo</option>
                            <option value="M" <?= $animal['sexo'] === 'M' ? 'selected' : '' ?>>Macho</option>
                            <option value="H" <?= $animal['sexo'] === 'H' ? 'selected' : '' ?>>Hembra</option>
                        </select>
                        <i class="zmdi zmdi-caret-down"></i>
                    </div>
                    <div class="form-wrapper">
                        <input type="text" name="color" class="form-control"
                               placeholder="Color"
                               value="<?= htmlspecialchars($animal['color'] ?? '') ?>">
                        <i class="zmdi zmdi-palette"></i>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-wrapper">
                        <input type="number" name="edad" class="form-control"
                               placeholder="Edad (años)" min="0" max="30"
                               value="<?= $animal['edad'] !== null ? $animal['edad'] : '' ?>">
                        <i class="zmdi zmdi-calendar"></i>
                    </div>
                    <div class="form-wrapper">
                        <input type="number" name="peso" class="form-control"
                               placeholder="Peso (kg)" step="0.01" min="0"
                               value="<?= $animal['peso'] !== null ? $animal['peso'] : '' ?>">
                        <i class="zmdi zmdi-balance"></i>
                    </div>
                </div>

                <p class="form-section-title">Estado y fecha</p>

                <div class="form-grid">
                    <div class="form-wrapper">
                        <select name="id_estado" class="form-control">
                            <option value="" disabled <?= !$animal['id_estado'] ? 'selected' : '' ?>>Estado</option>
                            <?php foreach ($estados as $e): ?>
                                <option value="<?= $e['id_estado'] ?>"
                                    <?= $animal['id_estado'] == $e['id_estado'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($e['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <i class="zmdi zmdi-caret-down"></i>
                    </div>
                    <div class="form-wrapper">
                        <input type="date" name="fecha_entrada" class="form-control"
                               value="<?= $animal['fecha_entrada'] ?? '' ?>">
                        <i class="zmdi zmdi-calendar-note"></i>
                    </div>
                </div>

                <p class="form-section-title">Descripción</p>

                <div class="form-wrapper">
                    <textarea name="descripcion" class="form-control"
                              placeholder="Descripción del animal…"><?= htmlspecialchars($animal['descripcion'] ?? '') ?></textarea>
                </div>

                <p class="form-section-title">Compatibilidades</p>

                <div class="compat-group">
                    <label class="compat-item">
                        <input type="checkbox" name="compat_ninos"
                               <?= $animal['compatibilidad_ninos']  ? 'checked' : '' ?>>
                        <i class="zmdi zmdi-mood"></i> Niños
                    </label>
                    <label class="compat-item">
                        <input type="checkbox" name="compat_perros"
                               <?= $animal['compatibilidad_perros'] ? 'checked' : '' ?>>
                        <i class="zmdi zmdi-paw"></i> Perros
                    </label>
                    <label class="compat-item">
                        <input type="checkbox" name="compat_gatos"
                               <?= $animal['compatibilidad_gatos']  ? 'checked' : '' ?>>
                        <i class="zmdi zmdi-toys"></i> Gatos
                    </label>
                </div>

                <button type="submit">GUARDAR CAMBIOS <i class="zmdi zmdi-check"></i></button>
            </form>
        </div>

        <div id="perfil-volver">
            <a href="listaAnimal.php">← Volver a mis animales</a>
        </div>

    </div>
</div>

</body>
</html>
