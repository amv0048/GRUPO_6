<?php
session_start();
require "../sesion/conexion.php";
require_once "../model/ColaboradorModel.php";
require_once "../helpers/media.php";

// Solo admin
if (!isset($_SESSION['user']) || !isset($_SESSION['admin']) || $_SESSION['admin'] != 1) {
    header('Location: /src/view/index.php');
    exit();
}

$colaboradorModel = new ColaboradorModel($_conexion);
$msg_err = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre      = trim($_POST['nombre']      ?? '');
    $telefono    = trim($_POST['telefono']    ?? '') ?: null;
    $web         = trim($_POST['web']         ?? '') ?: null;
    $profesion   = trim($_POST['profesion']   ?? '') ?: null;
    $suscripcion = in_array($_POST['suscripcion'] ?? '', ['basica', 'premium'])
                   ? $_POST['suscripcion'] : 'basica';
    $ubicacion   = trim($_POST['ubicacion']   ?? '') ?: null;

    if (empty($nombre)) {
        $msg_err = "El nombre es obligatorio.";
    } else {
        $id = $colaboradorModel->create([
            'nombre'      => $nombre,
            'telefono'    => $telefono,
            'web'         => $web,
            'profesion'   => $profesion,
            'suscripcion' => $suscripcion,
            'ubicacion'   => $ubicacion,
            'foto'        => null,
        ]);

        if ($id === false) {
            $msg_err = "Error al guardar. Comprueba que el teléfono no esté ya registrado.";
        } else {
            // Subida de foto
            if (!empty($_FILES['foto']['name']) && $_FILES['foto']['error'] === UPLOAD_ERR_OK) {
                $ext_ok = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
                $ext    = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, $ext_ok) && $_FILES['foto']['size'] <= 5 * 1024 * 1024) {
                    $dir = media_img_root() . '/colaboradores/colab_' . $id . '/';
                    if (!is_dir($dir)) {
                        mkdir($dir, 0755, true);
                    }
                    $archivo = 'foto_' . time() . '.' . $ext;
                    if (move_uploaded_file($_FILES['foto']['tmp_name'], $dir . $archivo)) {
                        $colaboradorModel->updateFoto($id, '/img/colaboradores/colab_' . $id . '/' . $archivo);
                    }
                }
            }
            header("Location: /src/view/ficha-colaborador.php?id=$id");
            exit();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Añadir colaborador · Go Catch</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,900&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/material-design-iconic-font/2.2.0/css/material-design-iconic-font.min.css">
    <link rel="stylesheet" href="/public/css/header.css">
    <link rel="stylesheet" href="/public/css/add-colaborador.css">
</head>
<body>

<!-- HEADER -->
<header>
    <nav class="hBotones">
        <a class="hBoton" href="/src/view/index.php#protectoras">PROTECTORAS</a>
        <a class="hBoton" href="/src/view/colaboradores.php">COLABORADORES</a>
        <?php if (isset($_SESSION['admin']) && $_SESSION['admin'] == 1): ?>
            <a class="hBoton" href="/src/view/moderacion.php">
                <i class="zmdi zmdi-shield-security"></i> MODERACIÓN
            </a>
        <?php endif; ?>
    </nav>

    <nav id="header-izq">
        <a href="/src/view/index.php" aria-label="Go Catch">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200" width="40" height="40" role="img" aria-label="Go Catch">
                <rect x="0" y="0" width="200" height="200" rx="36" ry="36" fill="#C97041"/>
                <text x="110" y="148"
                      font-family="'Fraunces', serif"
                      font-weight="900"
                      font-size="145"
                      fill="#FFFFFF"
                      text-anchor="middle">gc</text>
            </svg>
        </a>
    </nav>

    <nav class="hBotones">
        <a class="hBoton" href="/src/view/urgente.php">URGENTE</a>
        <a class="hBoton" href="/src/view/perfil.php">
            <i class="zmdi zmdi-account"></i>
            <?= htmlspecialchars($_SESSION['nombre']) ?>
        </a>
        <a href="/src/sesion/logout.php" id="boton-destacado">CERRAR SESIÓN</a>
    </nav>
</header>


<!-- CONTENIDO -->
<div id="add-colab-page">
<div id="add-colab-card">

    <div id="add-colab-header">
        <h1><i class="zmdi zmdi-account-add" style="color:#EDA677;margin-right:8px"></i>Añadir colaborador</h1>
        <p>Nuevo perfil · visible para todos los visitantes</p>
    </div>

    <div id="add-colab-body">

        <?php if ($msg_err): ?>
        <div class="msg-err"><?= htmlspecialchars($msg_err) ?></div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data" action="add-colaborador.php">

            <!-- ── DATOS PRINCIPALES ── -->
            <p class="form-section-title">Datos principales</p>

            <div class="campo">
                <label>Nombre / Empresa <span class="req">*</span></label>
                <input type="text" name="nombre"
                       value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>"
                       placeholder="Ej: Clínica Veterinaria Huella Verde"
                       required>
            </div>

            <div class="form-grid-2" style="margin-top:16px">
                <div class="campo">
                    <label>Profesión / Categoría</label>
                    <input type="text" name="profesion"
                           value="<?= htmlspecialchars($_POST['profesion'] ?? '') ?>"
                           placeholder="Ej: Veterinaria">
                </div>
                <div class="campo">
                    <label>Ubicación</label>
                    <input type="text" name="ubicacion"
                           value="<?= htmlspecialchars($_POST['ubicacion'] ?? '') ?>"
                           placeholder="Ej: Sevilla">
                </div>
            </div>

            <!-- ── CONTACTO ── -->
            <p class="form-section-title">Contacto</p>

            <div class="form-grid-2">
                <div class="campo">
                    <label>Teléfono</label>
                    <input type="tel" name="telefono"
                           value="<?= htmlspecialchars($_POST['telefono'] ?? '') ?>"
                           placeholder="Ej: 600 123 456">
                </div>
                <div class="campo">
                    <label>Página web</label>
                    <input type="url" name="web"
                           value="<?= htmlspecialchars($_POST['web'] ?? '') ?>"
                           placeholder="https://ejemplo.es">
                </div>
            </div>

            <!-- ── SUSCRIPCIÓN ── -->
            <p class="form-section-title">Suscripción</p>

            <div class="campo" style="max-width:280px">
                <label>Tipo de suscripción</label>
                <select name="suscripcion">
                    <option value="basica"   <?= ($_POST['suscripcion'] ?? 'basica')  === 'basica'   ? 'selected' : '' ?>>Básica</option>
                    <option value="premium"  <?= ($_POST['suscripcion'] ?? '') === 'premium' ? 'selected' : '' ?>>Premium ⭐</option>
                </select>
            </div>

            <!-- ── FOTO ── -->
            <p class="form-section-title">Foto del colaborador</p>

            <div id="foto-preview-wrapper">
                <img id="foto-preview-img" src="" alt="Previsualización">
            </div>

            <div class="upload-area" id="upload-area">
                <input type="file" name="foto" id="input-foto"
                       accept="image/jpeg,image/png,image/gif,image/webp"
                       onchange="previsualizarFoto(this)">
                <i class="zmdi zmdi-camera"></i>
                <p>Haz clic para subir una foto<br>
                   <span style="font-size:10px">JPG, PNG, GIF, WEBP · máx. 5 MB · opcional</span></p>
            </div>

            <button type="submit" class="btn-submit">
                <i class="zmdi zmdi-save"></i>
                GUARDAR COLABORADOR
            </button>

        </form>

    </div><!-- #add-colab-body -->

</div><!-- #add-colab-card -->
</div><!-- #add-colab-page -->

<div class="add-colab-volver">
    <a href="/src/view/colaboradores.php">← Volver a colaboradores</a>
</div>

<script>
function previsualizarFoto(input) {
    const wrapper = document.getElementById('foto-preview-wrapper');
    const img     = document.getElementById('foto-preview-img');
    const area    = document.getElementById('upload-area');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            img.src = e.target.result;
            wrapper.style.display = 'block';
            area.style.display    = 'none';
        };
        reader.readAsDataURL(input.files[0]);
    }
}
</script>

</body>
</html>
