<?php
session_start();
require "../sesion/conexion.php";
require_once "../model/AnimalModel.php";
require_once "../helpers/media.php";

if (!isset($_SESSION["id"])) {
    header("Location: /public/login.html");
    exit();
}
if (isset($_SESSION["user"])) {
    header("Location: /src/view/index.php");
    exit();
}

$id_protectora = $_SESSION["id"];
$animalModel = new AnimalModel($_conexion);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $d = [
        'nombre' => htmlspecialchars(trim($_POST["nombre"] ?? '')),
        'especie' => htmlspecialchars(trim($_POST["especie"] ?? '')),
        'raza' => htmlspecialchars(trim($_POST["raza"] ?? '')),
        'sexo' => in_array($_POST["sexo"] ?? '', ["M", "H"], true) ? $_POST["sexo"] : null,
        'color' => htmlspecialchars(trim($_POST["color"] ?? '')),
        'edad' => is_numeric($_POST["edad"] ?? null) ? (int)$_POST["edad"] : null,
        'peso' => is_numeric($_POST["peso"] ?? null) ? (float)$_POST["peso"] : null,
        'fecha_entrada' => !empty($_POST["fecha_entrada"]) ? $_POST["fecha_entrada"] : null,
        'descripcion' => htmlspecialchars(trim($_POST["descripcion"] ?? '')),
        'id_estado' => is_numeric($_POST["id_estado"] ?? null) ? (int)$_POST["id_estado"] : null,
        'compat_perros' => isset($_POST["compat_perros"]) ? 1 : 0,
        'compat_gatos' => isset($_POST["compat_gatos"]) ? 1 : 0,
        'compat_ninos' => isset($_POST["compat_ninos"]) ? 1 : 0,
    ];

    $id_nuevo = $animalModel->create($d, $id_protectora);

    if ($id_nuevo !== false) {
        $carpeta_animal = media_animal_dir($id_protectora, $id_nuevo);
        if (!is_dir($carpeta_animal)) {
            mkdir($carpeta_animal, 0755, true);
        }

        if (!empty($_FILES['foto']['name']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            $ext_ok = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $ext_ok, true)) {
                $archivo = 'foto_1_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['foto']['tmp_name'], $carpeta_animal . '/' . $archivo)) {
                    $ruta = media_animal_url($id_protectora, $id_nuevo, $archivo);
                    $animalModel->addFoto($id_nuevo, $ruta, 1);
                }
            }
        }

        header("Location: /src/view/listaAnimal.php?added=1");
        exit();
    }

    $err_db = "No se pudo registrar el animal. Intentalo de nuevo.";
}

$estados = $animalModel->getEstados();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anadir Animal · Go Catch</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,900&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/material-design-iconic-font/2.2.0/css/material-design-iconic-font.min.css">
    <link rel="stylesheet" href="/public/css/header.css">
    <link rel="stylesheet" href="/public/css/perfil.css">
    <link rel="stylesheet" href="/public/css/addAnimal.css">
</head>
<body>

<header>
    <nav class="hBotones">
        <a class="hBoton" href="" target="_self">PROTECTORAS</a>
        <a class="hBoton" href="/src/view/colaboradores.php" target="_self">COLABORADORES</a>
    </nav>
    <nav id="header-izq">
        <a href="/src/view/index.php" target="_self" aria-label="Go Catch">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200" width="40" height="40" role="img" aria-label="Go Catch">
                <rect x="0" y="0" width="200" height="200" rx="36" ry="36" fill="#C97041"/>
                <text x="110" y="148" font-family="'Fraunces', serif" font-weight="900" font-size="145" fill="#FFFFFF" text-anchor="middle">gc</text>
            </svg>
        </a>
    </nav>
    <nav class="hBotones">
        <a class="hBoton" href="/src/view/urgente.php" target="_self">URGENTE</a>
        <a class="hBoton" href="/src/view/perfil.php" target="_self">MI PERFIL</a>
        <a href="/src/view/listaAnimal.php" target="_self" id="boton-destacado">MIS ANIMALES</a>
    </nav>
</header>

<div id="padre-nuestro">
    <div id="estructura">

        <div id="perfil-header">
            <h2 id="perfil-nombre">Anadir Animal</h2>
            <p id="perfil-tipo">Nueva ficha</p>
        </div>

        <?php if (isset($err_db)): ?>
            <div class="msg-error"><?= htmlspecialchars($err_db) ?></div>
        <?php endif; ?>

        <div id="perfil-form-area">
            <form action="addAnimal.php" method="POST" id="registro" enctype="multipart/form-data">

                <p class="form-section-title">Datos basicos</p>

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
                        <input type="number" name="edad" class="form-control" placeholder="Edad (anos)" min="0" max="30">
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
                                <option value="<?= (int)$e['id_estado'] ?>"><?= htmlspecialchars($e['nombre']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <i class="zmdi zmdi-caret-down"></i>
                    </div>
                    <div class="form-wrapper">
                        <input type="date" name="fecha_entrada" class="form-control">
                        <i class="zmdi zmdi-calendar-note"></i>
                    </div>
                </div>

                <p class="form-section-title">Descripcion</p>

                <div class="form-wrapper">
                    <textarea name="descripcion" class="form-control" placeholder="Descripcion del animal, caracter, historia o necesidades..."></textarea>
                </div>

                <p class="form-section-title">Compatibilidades</p>

                <div class="compat-group">
                    <label class="compat-item">
                        <input type="checkbox" name="compat_ninos">
                        <i class="zmdi zmdi-mood"></i> Ninos
                    </label>
                    <label class="compat-item">
                        <input type="checkbox" name="compat_perros">
                        <i class="zmdi zmdi-paw"></i> Perros
                    </label>
                    <label class="compat-item">
                        <input type="checkbox" name="compat_gatos">
                        <i class="zmdi zmdi-toys"></i> Gatos
                    </label>
                </div>

                <p class="form-section-title">Foto principal</p>

                <div class="subir-foto-area" style="border:2px dashed #ddd;border-radius:8px;padding:20px;text-align:center;margin-bottom:20px;transition:border-color .2s">
                    <label for="foto-input" style="cursor:pointer;font-size:13px;color:#999;font-weight:500">
                        <i class="zmdi zmdi-camera-add" style="font-size:24px;display:block;margin-bottom:6px;color:#CA7842"></i>
                        Subir foto principal
                    </label>
                    <input type="file" name="foto" id="foto-input" accept="image/jpeg,image/png,image/gif,image/webp" style="display:none">
                </div>

                <div id="foto-preview-wrapper" style="display:none;margin-bottom:20px">
                    <img id="foto-preview" alt="Previsualizacion de la foto" style="width:100%;max-width:260px;height:180px;object-fit:cover;border-radius:8px;border:1px solid #eee;display:block">
                </div>

                <button type="submit">ANADIR ANIMAL <i class="zmdi zmdi-check"></i></button>
            </form>
        </div>

        <div id="perfil-volver">
            <a href="/src/view/listaAnimal.php">← Volver a mis animales</a>
        </div>
    </div>
</div>

<script>
const fotoInput = document.getElementById('foto-input');
const fotoPreviewWrapper = document.getElementById('foto-preview-wrapper');
const fotoPreview = document.getElementById('foto-preview');

if (fotoInput && fotoPreviewWrapper && fotoPreview) {
    fotoInput.addEventListener('change', function () {
        const file = this.files && this.files[0];
        if (!file) {
            fotoPreviewWrapper.style.display = 'none';
            fotoPreview.removeAttribute('src');
            return;
        }

        const reader = new FileReader();
        reader.onload = function (event) {
            fotoPreview.src = event.target.result;
            fotoPreviewWrapper.style.display = 'block';
        };
        reader.readAsDataURL(file);
    });
}
</script>

</body>
</html>
