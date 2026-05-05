<?php
session_start();
require "../sesion/conexion.php";
require_once "../model/AnimalModel.php";

if (!isset($_SESSION["id"])) { header("Location: /public/login.html"); exit(); }
if (isset($_SESSION["user"])) { header("Location: /src/view/index.php"); exit(); }

$id_protectora = $_SESSION["id"];

if (!isset($_GET["id"]) || !is_numeric($_GET["id"])) {
    header("Location: /src/view/listaAnimal.php"); exit();
}
$id_animal   = (int) $_GET["id"];
$animalModel = new AnimalModel($_conexion);

$animal = $animalModel->getByIdYProtectora($id_animal, $id_protectora);
if (!$animal) { header("Location: /src/view/listaAnimal.php"); exit(); }

$fotos = $animalModel->getGaleria($id_animal);
$foto_principal = null;
foreach ($fotos as $f) {
    if ($f['es_principal']) { $foto_principal = $f; break; }
}

// ── PROCESAR POST ────────────────────────────────────────────
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $accion = $_POST['accion'] ?? 'editar';

    if ($accion === 'set_principal' && isset($_POST['id_foto'])) {
        $animalModel->setPrincipal((int)$_POST['id_foto'], $id_animal);
        header("Location: /src/view/editAnimal.php?id=$id_animal&ok=principal"); exit();
    }

    if ($accion === 'eliminar_foto' && isset($_POST['id_foto'])) {
        $id_foto_del = (int)$_POST['id_foto'];
        $foto_del    = $animalModel->getFoto($id_foto_del, $id_animal);
        if ($foto_del) {
            $ruta_fisica = realpath(__DIR__ . '/../../' . $foto_del['ruta']);
            if ($ruta_fisica && is_file($ruta_fisica)) unlink($ruta_fisica);
            $animalModel->deleteFoto($id_foto_del);
            if ($foto_del['es_principal']) {
                $siguiente = $animalModel->getNextFoto($id_animal);
                if ($siguiente) $animalModel->setPrincipal($siguiente['id_foto'], $id_animal);
            }
        }
        header("Location: /src/view/editAnimal.php?id=$id_animal&ok=foto_eliminada"); exit();
    }

    if ($accion === 'subir_foto') {
        if (!empty($_FILES['foto_nueva']['name']) && $_FILES['foto_nueva']['error'] === UPLOAD_ERR_OK) {
            $ext_ok = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            $ext    = strtolower(pathinfo($_FILES['foto_nueva']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, $ext_ok)) {
                $carpeta = realpath(__DIR__ . '/../../img') . '/protectoras/protectora_' . $id_protectora . '/animal_' . $id_animal;
                if (!is_dir($carpeta)) mkdir($carpeta, 0755, true);
                $archivo = 'foto_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['foto_nueva']['tmp_name'], $carpeta . '/' . $archivo)) {
                    $ruta         = 'img/protectoras/protectora_' . $id_protectora . '/animal_' . $id_animal . '/' . $archivo;
                    $es_principal = empty($fotos) ? 1 : 0;
                    $animalModel->addFoto($id_animal, $ruta, $es_principal);
                }
            }
        }
        header("Location: /src/view/editAnimal.php?id=$id_animal&ok=foto_subida"); exit();
    }

    $d = [
        'nombre'        => htmlspecialchars(trim($_POST["nombre"])),
        'especie'       => htmlspecialchars(trim($_POST["especie"])),
        'raza'          => htmlspecialchars(trim($_POST["raza"])),
        'sexo'          => in_array($_POST["sexo"], ["M", "H"]) ? $_POST["sexo"] : null,
        'color'         => htmlspecialchars(trim($_POST["color"])),
        'edad'          => is_numeric($_POST["edad"])  ? (int)$_POST["edad"]   : null,
        'peso'          => is_numeric($_POST["peso"])  ? (float)$_POST["peso"] : null,
        'fecha_entrada' => !empty($_POST["fecha_entrada"]) ? $_POST["fecha_entrada"] : null,
        'descripcion'   => htmlspecialchars(trim($_POST["descripcion"])),
        'id_estado'     => is_numeric($_POST["id_estado"]) ? (int)$_POST["id_estado"] : null,
        'compat_perros' => isset($_POST["compat_perros"]) ? 1 : 0,
        'compat_gatos'  => isset($_POST["compat_gatos"])  ? 1 : 0,
        'compat_ninos'  => isset($_POST["compat_ninos"])  ? 1 : 0,
    ];

    if ($animalModel->update($d, $id_animal, $id_protectora)) {
        header("Location: /src/view/listaAnimal.php?edited=1"); exit();
    } else {
        $err_db = "No se pudo actualizar el animal.";
    }
}

$estados = $animalModel->getEstados();

$ok_msg = '';
if (isset($_GET['ok'])) {
    $msgs = ['principal' => 'Foto principal actualizada', 'foto_subida' => 'Foto añadida correctamente', 'foto_eliminada' => 'Foto eliminada'];
    $ok_msg = $msgs[$_GET['ok']] ?? '';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Animal · Go Catch</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,900&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/material-design-iconic-font/2.2.0/css/material-design-iconic-font.min.css">
    <link rel="stylesheet" href="/public/css/header.css">
    <link rel="stylesheet" href="/public/css/perfil.css">
    <link rel="stylesheet" href="/public/css/editAnimal.css">
</head>
<body>

<header>
    <nav class="hBotones">
        <a class="hBoton" href="" target="_self">PROTECTORAS</a>
        <a class="hBoton" href="" target="_self">COLABORADORES</a>
    </nav>
    <nav id="header-izq">
        <a href="/src/view/index.php" target="_self">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200" width="40" height="40" role="img" aria-label="Go Catch"><rect x="0" y="0" width="200" height="200" rx="36" ry="36" fill="#C97041"/><text x="110" y="148" font-family="'Fraunces', serif" font-weight="900" font-size="145" fill="#FFFFFF" text-anchor="middle">gc</text></svg>
        </a>
    </nav>
    <nav class="hBotones">
        <a class="hBoton" href="" target="_self">URGENTE</a>
        <a class="hBoton" href="/src/view/perfil.php" target="_self">MI PERFIL</a>
        <a href="/src/view/listaAnimal.php" target="_self" id="boton-destacado">MIS ANIMALES</a>
    </nav>
</header>

<div id="padre-nuestro">
    <div id="estructura">

        <div id="perfil-header">
            <h2 id="perfil-nombre"><?= htmlspecialchars($animal['nombre'] ?? 'Animal') ?></h2>
            <p id="perfil-tipo">Editar ficha · ID #<?= $id_animal ?></p>
        </div>

        <?php if ($ok_msg): ?>
            <div class="msg-ok"><?= $ok_msg ?></div>
        <?php endif; ?>
        <?php if (isset($err_db)): ?>
            <div class="msg-error"><?= $err_db ?></div>
        <?php endif; ?>

        <div id="perfil-form-area">

            <!-- ── FOTOS ── -->
            <p class="form-section-title" style="margin-top:0;border:none;padding:0">Fotos</p>

            <?php if (!empty($fotos)): ?>
            <div class="galeria-grid">
                <?php foreach ($fotos as $f): ?>
                <div class="galeria-item <?= $f['es_principal'] ? 'es-principal' : '' ?>">
                    <img src="<?= htmlspecialchars($f['ruta']) ?>" alt="foto">
                    <?php if ($f['es_principal']): ?>
                        <span class="badge-principal">Principal</span>
                    <?php endif; ?>
                    <div class="foto-acciones">
                        <?php if (!$f['es_principal']): ?>
                        <form method="POST" action="editAnimal.php?id=<?= $id_animal ?>" style="flex:1;margin:0">
                            <input type="hidden" name="accion" value="set_principal">
                            <input type="hidden" name="id_foto" value="<?= $f['id_foto'] ?>">
                            <button type="submit" class="btn-foto btn-set-principal">★ Principal</button>
                        </form>
                        <?php else: ?>
                            <span style="flex:1"></span>
                        <?php endif; ?>
                        <form method="POST" action="editAnimal.php?id=<?= $id_animal ?>" style="flex:1;margin:0"
                              onsubmit="return confirm('¿Eliminar esta foto?')">
                            <input type="hidden" name="accion" value="eliminar_foto">
                            <input type="hidden" name="id_foto" value="<?= $f['id_foto'] ?>">
                            <button type="submit" class="btn-foto btn-eliminar-foto">✕ Borrar</button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
                <p style="font-size:12px;color:#999;margin-bottom:16px">Sin fotos aún.</p>
            <?php endif; ?>

            <form method="POST" action="editAnimal.php?id=<?= $id_animal ?>"
                  enctype="multipart/form-data" id="form-subir-foto">
                <input type="hidden" name="accion" value="subir_foto">
                <div class="subir-foto-area">
                    <label for="foto-nueva-input">
                        <i class="zmdi zmdi-camera-add"></i>
                        Añadir foto nueva
                    </label>
                    <input type="file" name="foto_nueva" id="foto-nueva-input"
                           accept="image/jpeg,image/png,image/gif,image/webp">
                </div>
            </form>

            <!-- ── DATOS ── -->
            <form action="editAnimal.php?id=<?= $id_animal ?>" method="POST" id="registro">
                <input type="hidden" name="accion" value="editar">

                <p class="form-section-title">Datos básicos</p>

                <div class="form-wrapper">
                    <input type="text" name="nombre" class="form-control"
                           value="<?= htmlspecialchars($animal['nombre'] ?? '') ?>" required>
                    <i class="zmdi zmdi-account"></i>
                </div>

                <div class="form-grid">
                    <div class="form-wrapper">
                        <select name="especie" class="form-control" required>
                            <option value="" disabled <?= !$animal['especie'] ? 'selected' : '' ?>>Especie</option>
                            <option value="Perro" <?= ($animal['especie'] === 'Perro') ? 'selected' : '' ?>>Perro</option>
                            <option value="Gato"  <?= ($animal['especie'] === 'Gato')  ? 'selected' : '' ?>>Gato</option>
                            <option value="Otro"  <?= ($animal['especie'] === 'Otro')  ? 'selected' : '' ?>>Otro</option>
                        </select>
                        <i class="zmdi zmdi-caret-down"></i>
                    </div>
                    <div class="form-wrapper">
                        <input type="text" name="raza" class="form-control" placeholder="Raza"
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
                        <input type="text" name="color" class="form-control" placeholder="Color"
                               value="<?= htmlspecialchars($animal['color'] ?? '') ?>">
                        <i class="zmdi zmdi-palette"></i>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="form-wrapper">
                        <input type="number" name="edad" class="form-control" placeholder="Edad (años)" min="0" max="30"
                               value="<?= $animal['edad'] !== null ? $animal['edad'] : '' ?>">
                        <i class="zmdi zmdi-calendar"></i>
                    </div>
                    <div class="form-wrapper">
                        <input type="number" name="peso" class="form-control" placeholder="Peso (kg)" step="0.01" min="0"
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
                        <input type="checkbox" name="compat_ninos" <?= $animal['compatibilidad_ninos']  ? 'checked' : '' ?>>
                        <i class="zmdi zmdi-mood"></i> Niños
                    </label>
                    <label class="compat-item">
                        <input type="checkbox" name="compat_perros" <?= $animal['compatibilidad_perros'] ? 'checked' : '' ?>>
                        <i class="zmdi zmdi-paw"></i> Perros
                    </label>
                    <label class="compat-item">
                        <input type="checkbox" name="compat_gatos" <?= $animal['compatibilidad_gatos']  ? 'checked' : '' ?>>
                        <i class="zmdi zmdi-toys"></i> Gatos
                    </label>
                </div>

                <button type="submit">GUARDAR CAMBIOS <i class="zmdi zmdi-check"></i></button>
            </form>
        </div>

        <div id="perfil-volver">
            <a href="/src/view/listaAnimal.php">← Volver a mis animales</a>
        </div>
    </div>
</div>

<script>
document.getElementById('foto-nueva-input').addEventListener('change', function() {
    if (this.files.length > 0) document.getElementById('form-subir-foto').submit();
});
</script>

</body>
</html>
