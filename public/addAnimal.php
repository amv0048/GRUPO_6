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

// ── PROCESAR FORMULARIO ──────────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $nombre      = htmlspecialchars(trim($_POST["nombre"]));
    $especie     = htmlspecialchars(trim($_POST["especie"]));
    $raza        = htmlspecialchars(trim($_POST["raza"]));
    $sexo        = in_array($_POST["sexo"], ["M", "H"]) ? $_POST["sexo"] : null;
    $color       = htmlspecialchars(trim($_POST["color"]));
    $edad        = is_numeric($_POST["edad"])  ? (int)$_POST["edad"]  : null;
    $peso        = is_numeric($_POST["peso"])  ? (float)$_POST["peso"] : null;
    $fecha       = !empty($_POST["fecha_entrada"]) ? $_POST["fecha_entrada"] : null;
    $descripcion = htmlspecialchars(trim($_POST["descripcion"]));
    $id_estado   = is_numeric($_POST["id_estado"]) ? (int)$_POST["id_estado"] : null;

    $compat_perros = isset($_POST["compat_perros"]) ? 1 : 0;
    $compat_gatos  = isset($_POST["compat_gatos"])  ? 1 : 0;
    $compat_ninos  = isset($_POST["compat_ninos"])  ? 1 : 0;

    $sql = "INSERT INTO Animales
                (id_protectora, id_estado, nombre, especie, raza, sexo, color, peso, edad,
                 fecha_entrada, descripcion,
                 compatibilidad_perros, compatibilidad_gatos, compatibilidad_ninos)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $_conexion->prepare($sql);
    // 14 params: id_protectora(i), id_estado(i), nombre(s), especie(s), raza(s), sexo(s),
    //            color(s), peso(d), edad(i), fecha(s), descripcion(s),
    //            compat_perros(i), compat_gatos(i), compat_ninos(i)
    $stmt->bind_param(
        "iisssssdissiii",
        $id_protectora, $id_estado, $nombre, $especie, $raza, $sexo,
        $color, $peso, $edad, $fecha, $descripcion,
        $compat_perros, $compat_gatos, $compat_ninos
    );

    if ($stmt->execute()) {
        $id_nuevo = $_conexion->insert_id;
        $stmt->close();

        // ── CREAR CARPETA DEL ANIMAL ────────────────────────────
        $carpeta_animal = realpath(__DIR__ . '/../img') . '/protectoras/protectora_' . $id_protectora . '/animal_' . $id_nuevo;
        if (!is_dir($carpeta_animal)) {
            mkdir($carpeta_animal, 0755, true);
        }
        // ────────────────────────────────────────────────────────

        // ── SUBIR FOTO PRINCIPAL ────────────────────────────────
        if (!empty($_FILES['foto']['name']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $ext_ok  = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $ext     = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $ext_ok)) {
                $archivo = 'foto_1_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['foto']['tmp_name'], $carpeta_animal . '/' . $archivo)) {
                    $ruta = '../img/protectoras/protectora_' . $id_protectora . '/animal_' . $id_nuevo . '/' . $archivo;
                    $ins  = $_conexion->prepare(
                        "INSERT INTO Galeria (id_animal, ruta, es_principal) VALUES (?, ?, 1)"
                    );
                    $ins->bind_param("is", $id_nuevo, $ruta);
                    $ins->execute();
                    $ins->close();
                }
            }
        }

        header("Location: listaAnimal.php?added=1");
        exit();
    } else {
        $err_db = "No se pudo registrar el animal. Inténtalo de nuevo.";
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
    <title>Añadir Animal · Go Catch</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,900&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/material-design-iconic-font/2.2.0/css/material-design-iconic-font.min.css">
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/perfil.css">
    <link rel="stylesheet" href="css/addAnimal.css">
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

<div id="padre-nuestro">
    <div id="estructura">

        <!-- CABECERA -->
        <div id="perfil-header">
            <h2 id="perfil-nombre">Añadir Animal</h2>
            <p id="perfil-tipo">Nueva ficha</p>
        </div>

        <!-- MENSAJES -->
        <?php if (isset($err_db)): ?>
            <div class="msg-error"><?= $err_db ?></div>
        <?php endif; ?>

        <!-- FORMULARIO -->
        <div id="perfil-form-area">
            <form action="addAnimal.php" method="POST" id="registro" enctype="multipart/form-data">

                <p class="form-section-title">Datos básicos</p>

                <div class="form-wrapper">
                    <input type="text" name="nombre" class="form-control" placeholder="Nombre del animal" required>
                    <i class="zmdi zmdi-account"></i>
                </div>

                <div class="form-grid">
                    <div class="form-wrapper">
                        <select name="especie" class="form-control" required>
                            <option value="" disabled selected hidden>Especie</option>
                            <option value="Perro">Perro</option>
                            <option value="Gato">Gato</option>
                            <option value="Otro">Otro</option>
                        </select>
                        <i class="zmdi zmdi-caret-down"></i>
                    </div>
                    <div class="form-wrapper">
                        <input type="text" name="raza" class="form-control" placeholder="Raza">
                        <i class="zmdi zmdi-collection-item-3"></i>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-wrapper">
                        <select name="sexo" class="form-control" required>
                            <option value="" disabled selected hidden>Sexo</option>
                            <option value="M">Macho</option>
                            <option value="H">Hembra</option>
                        </select>
                        <i class="zmdi zmdi-caret-down"></i>
                    </div>
                    <div class="form-wrapper">
                        <input type="text" name="color" class="form-control" placeholder="Color">
                        <i class="zmdi zmdi-palette"></i>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-wrapper">
                        <input type="number" name="edad" class="form-control" placeholder="Edad (años)" min="0" max="30">
                        <i class="zmdi zmdi-calendar"></i>
                    </div>
                    <div class="form-wrapper">
                        <input type="number" name="peso" class="form-control" placeholder="Peso (kg)" step="0.01" min="0">
                        <i class="zmdi zmdi-balance"></i>
                    </div>
                </div>

                <p class="form-section-title">Estado y fecha</p>

                <div class="form-grid">
                    <div class="form-wrapper">
                        <select name="id_estado" class="form-control" required>
                            <option value="" disabled selected hidden>Estado</option>
                            <?php foreach ($estados as $e): ?>
                                <option value="<?= $e['id_estado'] ?>"><?= htmlspecialchars($e['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <i class="zmdi zmdi-caret-down"></i>
                    </div>
                    <div class="form-wrapper">
                        <input type="date" name="fecha_entrada" class="form-control">
                        <i class="zmdi zmdi-calendar-note"></i>
                    </div>
                </div>

                <p class="form-section-title">Descripción</p>

                <div class="form-wrapper">
                    <textarea name="descripcion" class="