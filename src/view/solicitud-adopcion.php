<?php
session_start();
require "../sesion/conexion.php";
require_once "../model/AnimalModel.php";
require_once "../model/UsuarioModel.php";
require_once "../model/AdopcionModel.php";

if (!isset($_SESSION['id']) || !isset($_SESSION['user'])) {
    header("Location: /public/login.html?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: /src/view/index.php");
    exit();
}

$id_animal     = (int) $_GET['id'];
$animalModel   = new AnimalModel($_conexion);
$usuarioModel  = new UsuarioModel($_conexion);
$adopcionModel = new AdopcionModel($_conexion);

$animal = $animalModel->getForSolicitud($id_animal);
if (!$animal || $animal['estado'] !== 'DISPONIBLE') {
    header("Location: /src/view/ficha-animal.php?id=" . $id_animal);
    exit();
}

$usuario = $usuarioModel->getBasico((int)$_SESSION['id']);

$errores = [];
$enviado = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if ($adopcionModel->existeSolicitudPendiente($id_animal, (int)$_SESSION['id'])) {
        $errores[] = "Ya tienes una solicitud pendiente para este animal.";
    } else {
        $f_nombre   = trim($_POST['nombre']    ?? '');
        $f_apellido = trim($_POST['apellido']  ?? '');
        $f_email    = trim($_POST['email']     ?? '');
        $f_telefono = trim($_POST['telefono']  ?? '');
        $f_dni      = trim($_POST['dni']       ?? '');
        $f_vivienda = trim($_POST['tipo_vivienda'] ?? '');
        $f_jardin   = isset($_POST['tiene_jardin'])   ? 1 : 0;
        $f_metros   = trim($_POST['metros_vivienda']  ?? '');
        $f_ninos    = isset($_POST['tiene_ninos'])    ? 1 : 0;
        $f_edades   = trim($_POST['edades_ninos']     ?? '');
        $f_animales = isset($_POST['tiene_animales']) ? 1 : 0;
        $f_desc_ani = trim($_POST['desc_animales']    ?? '');
        $f_horas    = (int) ($_POST['horas_solo']     ?? 0);
        $f_exp      = isset($_POST['experiencia'])    ? 1 : 0;
        $f_motiv    = trim($_POST['motivacion']       ?? '');
        $f_visita   = isset($_POST['acepta_visita'])       ? 1 : 0;
        $f_seguim   = isset($_POST['acepta_seguimiento'])  ? 1 : 0;

        if (empty($f_nombre))   $errores[] = "El nombre es obligatorio.";
        if (empty($f_apellido)) $errores[] = "El apellido es obligatorio.";
        if (!filter_var($f_email, FILTER_VALIDATE_EMAIL)) $errores[] = "El email no es válido.";
        if (!in_array($f_vivienda, ['piso','casa','chalet','otro'])) $errores[] = "Selecciona un tipo de vivienda.";
        if (empty($f_motiv))    $errores[] = "La motivación es obligatoria.";
        if (!$f_visita)         $errores[] = "Debes aceptar la posible visita al domicilio.";

        if (empty($errores)) {
            $id_nueva_solicitud = $adopcionModel->createSolicitud([
                'id_animal'          => $id_animal,
                'id_adoptante'       => (int)$_SESSION['id'],
                'nombre'             => $f_nombre,
                'apellido'           => $f_apellido,
                'email'              => $f_email,
                'telefono'           => $f_telefono,
                'dni'                => $f_dni,
                'tipo_vivienda'      => $f_vivienda,
                'tiene_jardin'       => $f_jardin,
                'metros_vivienda'    => $f_metros,
                'tiene_ninos'        => $f_ninos,
                'edades_ninos'       => $f_edades,
                'tiene_animales'     => $f_animales,
                'desc_animales'      => $f_desc_ani,
                'horas_solo'         => $f_horas,
                'experiencia'        => $f_exp,
                'motivacion'         => $f_motiv,
                'acepta_visita'      => $f_visita,
                'acepta_seguimiento' => $f_seguim,
            ]);
            if ($id_nueva_solicitud !== false) {
                $enviado = true;
            } else {
                $errores[] = "Error al guardar la solicitud. Inténtalo de nuevo.";
            }
        }
    }
}

$pre_nombre   = $usuario['nombre']   ?? '';
$pre_apellido = $usuario['apellido'] ?? '';
$pre_email    = $usuario['email']    ?? '';
$pre_telefono = $usuario['numero']   ?? '';

$sexo_map = ['M' => 'Macho', 'H' => 'Hembra'];
$sexo_txt = $sexo_map[$animal['sexo']] ?? '';
$edad_txt = $animal['edad'] !== null ? $animal['edad'] . ' año' . ($animal['edad'] != 1 ? 's' : '') : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitud de adopción · <?= htmlspecialchars($animal['nombre']) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,900&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/material-design-iconic-font/2.2.0/css/material-design-iconic-font.min.css">
    <link rel="stylesheet" href="/public/css/header.css">
    <link rel="stylesheet" href="/public/css/solicitud-adopcion.css">
</head>
<body>

<!-- HEADER -->
<header>
    <nav class="hBotones">
        <a class="hBoton" href="#protectoras">PROTECTORAS</a>
        <a class="hBoton" href="/src/view/colaboradores.php">COLABORADORES</a>
    </nav>
    <nav id="header-izq">
        <a href="/src/view/index.php" target="_self">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200" width="40" height="40" role="img" aria-label="Go Catch"><rect x="0" y="0" width="200" height="200" rx="36" ry="36" fill="#C97041"/><text x="110" y="148" font-family="'Fraunces', serif" font-weight="900" font-size="145" fill="#FFFFFF" text-anchor="middle">gc</text></svg>
        </a>
    </nav>
    <nav class="hBotones">
        <a class="hBoton" href="/src/view/urgente.php">URGENTE</a>
        <?php if (isset($_SESSION['id'])): ?>
            <a class="hBoton" href="/src/view/perfil.php">
                <i class="zmdi zmdi-account"></i>
                <?= htmlspecialchars($_SESSION["nombre"] ?? '') ?>
            </a>
            <a href="/src/sesion/logout.php" id="boton-destacado">CERRAR SESIÓN</a>
        <?php else: ?>
            <a class="hBoton" href="/public/registro.html">REGÍSTRATE</a>
            <a href="/public/login.html" id="boton-destacado">INICIA SESIÓN</a>
        <?php endif; ?>
    </nav>
</header>

<div id="page-wrapper">

    <!-- BREADCRUMB -->
    <div class="breadcrumb">
        <a href="/src/view/index.php"><i class="zmdi zmdi-home"></i> Inicio</a>
        <span>/</span>
        <a href="index.php#animales">Animales</a>
        <span>/</span>
        <a href="/src/view/ficha-animal.php?id=<?= $id_animal ?>"><?= htmlspecialchars($animal['nombre']) ?></a>
        <span>/</span>
        Solicitud de adopción
    </div>

    <!-- RESUMEN DEL ANIMAL -->
    <div class="animal-resumen">
        <div class="animal-resumen-foto">
            <?php if (!empty($animal['foto'])): ?>
                <img src="<?= htmlspecialchars($animal['foto']) ?>" alt="<?= htmlspecialchars($animal['nombre']) ?>">
            <?php else: ?>
                <i class="zmdi zmdi-paw"></i>
            <?php endif; ?>
        </div>
        <div class="animal-resumen-info">
            <h2><?= htmlspecialchars($animal['nombre']) ?></h2>
            <p><?= htmlspecialchars($animal['nombre_protectora'] ?? '') ?></p>
            <div class="tags">
                <?php if ($animal['especie']): ?>
                    <span class="tag"><?= htmlspecialchars(ucfirst($animal['especie'])) ?></span>
                <?php endif; ?>
                <?php if ($sexo_txt): ?>
                    <span class="tag"><?= htmlspecialchars($sexo_txt) ?></span>
                <?php endif; ?>
                <?php if ($edad_txt): ?>
                    <span class="tag"><?= htmlspecialchars($edad_txt) ?></span>
                <?php endif; ?>
                <?php if ($animal['raza']): ?>
                    <span class="tag"><?= htmlspecialchars($animal['raza']) ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- FORMULARIO -->
    <div class="form-card">
        <div class="form-card-header">
            <h1><i class="zmdi zmdi-home" style="color:#CA7842; margin-right:8px"></i>Solicitud de adopción</h1>
            <p>Rellena el formulario con sinceridad. La protectora revisará tu solicitud y se pondrá en contacto contigo lo antes posible.</p>
        </div>

        <div class="form-card-body">

            <?php if ($enviado): ?>
            <!-- ── ÉXITO ── -->
            <div class="exito-wrapper">
                <div class="exito-icono">
                    <i class="zmdi zmdi-check-circle"></i>
                </div>
                <h2>¡Solicitud enviada!</h2>
                <p>
                    Tu solicitud para adoptar a <strong><?= htmlspecialchars($animal['nombre']) ?></strong> ha sido enviada correctamente.
                    La protectora <strong><?= htmlspecialchars($animal['nombre_protectora'] ?? '') ?></strong> revisará tu solicitud.
                    Ahora puedes reservar una fecha para la entrevista de adopción.
                </p>
                <?php if (!empty($id_nueva_solicitud)): ?>
                <a href="/src/view/reservar-cita.php?solicitud=<?= $id_nueva_solicitud ?>" class="btn-primario" style="margin: 20px auto 0; max-width: 340px">
                    <i class="zmdi zmdi-calendar-check"></i>
                    RESERVAR FECHA DE ENTREVISTA
                </a>
                <?php endif; ?>
                <div class="exito-btns">
                    <a href="/src/view/ficha-animal.php?id=<?= $id_animal ?>" class="btn-secundario">
                        <i class="zmdi zmdi-arrow-left"></i> Volver a la ficha
                    </a>
                    <a href="/src/view/index.php" class="btn-primario" style="max-width:none">
                        <i class="zmdi zmdi-home"></i> Ir al inicio
                    </a>
                </div>
            </div>

            <?php else: ?>

            <?php if (!empty($errores)): ?>
            <div class="alerta alerta-error">
                <strong>Por favor corrige los siguientes errores:</strong>
                <ul>
                    <?php foreach ($errores as $e): ?>
                        <li><?= htmlspecialchars($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <div class="aviso-box">
                <i class="zmdi zmdi-info"></i>
                <p>Esta solicitud no garantiza la adopción. La protectora evaluará tu perfil y podrá solicitar una visita a tu domicilio antes de tomar una decisión.</p>
            </div>

            <form method="POST" action="solicitud-adopcion.php?id=<?= $id_animal ?>" novalidate>

                <!-- ── 1. DATOS PERSONALES ── -->
                <div class="form-seccion">
                    <p class="seccion-titulo"><i class="zmdi zmdi-account"></i> Datos personales</p>
                    <div class="form-grid">
                        <div class="campo">
                            <label>Nombre <span class="req">*</span></label>
                            <input type="text" name="nombre" value="<?= htmlspecialchars($_POST['nombre'] ?? $pre_nombre) ?>" required>
                        </div>
                        <div class="campo">
                            <label>Apellidos <span class="req">*</span></label>
                            <input type="text" name="apellido" value="<?= htmlspecialchars($_POST['apellido'] ?? $pre_apellido) ?>" required>
                        </div>
                        <div class="campo">
                            <label>Email <span class="req">*</span></label>
                            <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? $pre_email) ?>" required>
                        </div>
                        <div class="campo">
                            <label>Teléfono</label>
                            <input type="tel" name="telefono" value="<?= htmlspecialchars($_POST['telefono'] ?? $pre_telefono) ?>" placeholder="Ej: 600 000 000">
                        </div>
                        <div class="campo">
                            <label>DNI / NIE</label>
                            <input type="text" name="dni" value="<?= htmlspecialchars($_POST['dni'] ?? '') ?>" placeholder="Ej: 12345678A">
                        </div>
                    </div>
                </div>

                <!-- ── 2. SITUACIÓN DE VIVIENDA ── -->
                <div class="form-seccion">
                    <p class="seccion-titulo"><i class="zmdi zmdi-city"></i> Situación de vivienda</p>

                    <div class="form-grid">
                        <div class="campo col-full">
                            <label>Tipo de vivienda <span class="req">*</span></label>
                            <div class="opciones-row" id="vivienda-opciones">
                                <?php
                                $viviendas = ['piso' => 'Piso', 'casa' => 'Casa', 'chalet' => 'Chalet', 'otro' => 'Otro'];
                                $sel_viv = $_POST['tipo_vivienda'] ?? '';
                                foreach ($viviendas as $val => $label):
                                ?>
                                <label class="opcion-radio <?= $sel_viv === $val ? 'sel' : '' ?>">
                                    <input type="radio" name="tipo_vivienda" value="<?= $val ?>" <?= $sel_viv === $val ? 'checked' : '' ?> required>
                                    <?= $label ?>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="campo">
                            <label>Superficie aproximada (m²)</label>
                            <input type="number" name="metros_vivienda" min="10" max="1000"
                                   value="<?= htmlspecialchars($_POST['metros_vivienda'] ?? '') ?>"
                                   placeholder="Ej: 80">
                        </div>

                        <div class="campo">
                            <label>¿Tiene jardín o terraza?</label>
                            <div class="opciones-row">
                                <label class="opcion-check <?= isset($_POST['tiene_jardin']) ? 'sel' : '' ?>" id="check-jardin">
                                    <input type="checkbox" name="tiene_jardin" id="input-jardin" <?= isset($_POST['tiene_jardin']) ? 'checked' : '' ?>>
                                    <i class="zmdi zmdi-flower"></i> Sí
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── 3. CONVIVENCIA ── -->
                <div class="form-seccion">
                    <p class="seccion-titulo"><i class="zmdi zmdi-accounts"></i> Convivencia</p>
                    <div class="form-grid">

                        <div class="campo">
                            <label>¿Hay niños en casa?</label>
                            <div class="opciones-row">
                                <label class="opcion-check <?= isset($_POST['tiene_ninos']) ? 'sel' : '' ?>" id="check-ninos">
                                    <input type="checkbox" name="tiene_ninos" id="input-ninos" <?= isset($_POST['tiene_ninos']) ? 'checked' : '' ?>>
                                    <i class="zmdi zmdi-mood"></i> Sí
                                </label>
                            </div>
                            <div class="subgrupo <?= isset($_POST['tiene_ninos']) ? 'visible' : '' ?>" id="sub-ninos">
                                <div class="campo">
                                    <label>Edades de los niños</label>
                                    <input type="text" name="edades_ninos"
                                           value="<?= htmlspecialchars($_POST['edades_ninos'] ?? '') ?>"
                                           placeholder="Ej: 3, 7 y 10 años">
                                </div>
                            </div>
                        </div>

                        <div class="campo">
                            <label>¿Tienes otros animales?</label>
                            <div class="opciones-row">
                                <label class="opcion-check <?= isset($_POST['tiene_animales']) ? 'sel' : '' ?>" id="check-animales">
                                    <input type="checkbox" name="tiene_animales" id="input-animales" <?= isset($_POST['tiene_animales']) ? 'checked' : '' ?>>
                                    <i class="zmdi zmdi-paw"></i> Sí
                                </label>
                            </div>
                            <div class="subgrupo <?= isset($_POST['tiene_animales']) ? 'visible' : '' ?>" id="sub-animales">
                                <div class="campo">
                                    <label>Describe tus animales</label>
                                    <input type="text" name="desc_animales"
                                           value="<?= htmlspecialchars($_POST['desc_animales'] ?? '') ?>"
                                           placeholder="Ej: Un perro labrador de 4 años, castrado">
                                </div>
                            </div>
                        </div>

                        <div class="campo">
                            <label>Horas diarias que el animal estaría solo</label>
                            <select name="horas_solo">
                                <?php
                                $sel_horas = (int)($_POST['horas_solo'] ?? -1);
                                $opciones_horas = [
                                    '' => 'Selecciona...',
                                    '0' => 'Nunca (siempre hay alguien)',
                                    '2' => 'Menos de 2 horas',
                                    '4' => '2 – 4 horas',
                                    '6' => '4 – 6 horas',
                                    '8' => '6 – 8 horas',
                                    '9' => 'Más de 8 horas',
                                ];
                                foreach ($opciones_horas as $val => $lbl):
                                ?>
                                    <option value="<?= $val ?>" <?= $sel_horas == $val ? 'selected' : '' ?>><?= $lbl ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="campo">
                            <label>¿Tienes experiencia con animales?</label>
                            <div class="opciones-row">
                                <label class="opcion-check <?= isset($_POST['experiencia']) ? 'sel' : '' ?>" id="check-exp">
                                    <input type="checkbox" name="experiencia" id="input-exp" <?= isset($_POST['experiencia']) ? 'checked' : '' ?>>
                                    <i class="zmdi zmdi-check"></i> Sí
                                </label>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- ── 4. MOTIVACIÓN ── -->
                <div class="form-seccion">
                    <p class="seccion-titulo"><i class="zmdi zmdi-comment-text"></i> Motivación</p>
                    <div class="campo">
                        <label>¿Por qué quieres adoptar a <?= htmlspecialchars($animal['nombre']) ?>? <span class="req">*</span></label>
                        <textarea name="motivacion" rows="5" placeholder="Cuéntanos por qué crees que <?= htmlspecialchars($animal['nombre']) ?> encajaría en tu hogar y qué puedes ofrecerle..."><?= htmlspecialchars($_POST['motivacion'] ?? '') ?></textarea>
                    </div>
                </div>

                <!-- ── 5. COMPROMISOS ── -->
                <div class="form-seccion">
                    <p class="seccion-titulo"><i class="zmdi zmdi-shield-check"></i> Compromisos</p>
                    <div class="checks-legales">
                        <label class="check-legal">
                            <input type="checkbox" name="acepta_visita" id="input-visita" <?= isset($_POST['acepta_visita']) ? 'checked' : '' ?> required>
                            <span>Acepto que la protectora pueda realizar una visita a mi domicilio para verificar que es un entorno adecuado para el animal. <span class="req">*</span></span>
                        </label>
                        <label class="check-legal">
                            <input type="checkbox" name="acepta_seguimiento" <?= isset($_POST['acepta_seguimiento']) ? 'checked' : '' ?>>
                            <span>Acepto recibir comunicaciones de seguimiento post-adopción para asegurar el bienestar del animal.</span>
                        </label>
                    </div>
                </div>

                <!-- ── BOTÓN ENVIAR ── -->
                <button type="submit" class="btn-primario" style="width:100%; margin-top:8px; padding:15px;">
                    <i class="zmdi zmdi-send"></i>
                    ENVIAR SOLICITUD
                </button>

            </form>
            <?php endif; ?>

        </div>
    </div>

    <div class="volver">
        <a href="/src/view/ficha-animal.php?id=<?= $id_animal ?>">← Volver a la ficha de <?= htmlspecialchars($animal['nombre']) ?></a>
    </div>

</div>

<script>
/* Estilo visual de opciones radio/check */
document.querySelectorAll('.opcion-radio input[type=radio]').forEach(input => {
    input.addEventListener('change', function () {
        document.querySelectorAll(`input[name="${this.name}"]`).forEach(r => {
            r.closest('.opcion-radio').classList.remove('sel');
        });
        this.closest('.opcion-radio').classList.add('sel');
    });
});

/* Checkboxes con toggle visual + subgrupos */
function toggleCheck(inputId, labelId, subgrupoId) {
    const input   = document.getElementById(inputId);
    const label   = document.getElementById(labelId);
    const subg    = subgrupoId ? document.getElementById(subgrupoId) : null;
    if (!input || !label) return;

    label.addEventListener('click', function () {
        setTimeout(() => {
            label.classList.toggle('sel', input.checked);
            if (subg) subg.classList.toggle('visible', input.checked);
        }, 0);
    });
}

toggleCheck('input-jardin',   'check-jardin',   null);
toggleCheck('input-ninos',    'check-ninos',     'sub-ninos');
toggleCheck('input-animales', 'check-animales',  'sub-animales');
toggleCheck('input-exp',      'check-exp',       null);

/* Visita check visual */
const inputVisita = document.getElementById('input-visita');
if (inputVisita) {
    inputVisita.addEventListener('change', function () {
        this.closest('.check-legal').style.color = this.checked ? '#2e7d12' : '';
    });
}
</script>

</body>
</html>
