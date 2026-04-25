<?php
session_start();
require "../src/sesion/conexion.php";

error_reporting(E_ALL);
ini_set("display_errors", 1);

// ── VALIDAR SESIÓN ───────────────────────────────────────────
if (!isset($_SESSION['id']) || !isset($_SESSION['user'])) {
    header("Location: login.html?redirect=" . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

// ── VALIDAR PARÁMETRO ────────────────────────────────────────
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id_animal = (int) $_GET['id'];

// ── OBTENER DATOS DEL ANIMAL ─────────────────────────────────
$stmt = $_conexion->prepare(
    "SELECT a.id_animal, a.nombre, a.especie, a.raza, a.sexo, a.edad, a.id_protectora,
            a.id_estado, e.nombre AS estado,
            p.nombre_protectora, p.email AS email_protectora, p.telefono AS tel_protectora,
            (SELECT g.ruta FROM Galeria g WHERE g.id_animal = a.id_animal ORDER BY g.es_principal DESC, g.id_foto ASC LIMIT 1) AS foto
     FROM Animales a
     LEFT JOIN EstadoAnimal e ON a.id_estado = e.id_estado
     LEFT JOIN Protectora   p ON a.id_protectora = p.id_protectora
     WHERE a.id_animal = ?"
);
$stmt->bind_param("i", $id_animal);
$stmt->execute();
$animal = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$animal || $animal['estado'] !== 'DISPONIBLE') {
    header("Location: ficha-animal.php?id=" . $id_animal);
    exit();
}

// ── OBTENER DATOS DEL USUARIO ────────────────────────────────
$stmt_u = $_conexion->prepare("SELECT nombre, apellido, email, numero FROM Usuario WHERE id_adoptante = ?");
$stmt_u->bind_param("i", $_SESSION['id']);
$stmt_u->execute();
$usuario = $stmt_u->get_result()->fetch_assoc();
$stmt_u->close();

// ── CREAR TABLA SI NO EXISTE ─────────────────────────────────
$_conexion->query("
    CREATE TABLE IF NOT EXISTS SolicitudAdopcion (
        id_solicitud      INT AUTO_INCREMENT PRIMARY KEY,
        id_animal         INT NOT NULL,
        id_adoptante      INT NOT NULL,
        fecha_solicitud   DATETIME DEFAULT CURRENT_TIMESTAMP,
        nombre            VARCHAR(100) NOT NULL,
        apellido          VARCHAR(100) NOT NULL,
        email             VARCHAR(150) NOT NULL,
        telefono          VARCHAR(20),
        dni               VARCHAR(20),
        tipo_vivienda     ENUM('piso','casa','chalet','otro') NOT NULL,
        tiene_jardin      TINYINT(1) DEFAULT 0,
        metros_vivienda   VARCHAR(20),
        tiene_ninos       TINYINT(1) DEFAULT 0,
        edades_ninos      VARCHAR(100),
        tiene_animales    TINYINT(1) DEFAULT 0,
        desc_animales     TEXT,
        horas_solo        TINYINT UNSIGNED,
        experiencia       TINYINT(1) DEFAULT 0,
        motivacion        TEXT NOT NULL,
        acepta_visita     TINYINT(1) DEFAULT 0,
        acepta_seguimiento TINYINT(1) DEFAULT 0,
        estado_solicitud  ENUM('PENDIENTE','APROBADA','RECHAZADA') DEFAULT 'PENDIENTE',
        FOREIGN KEY (id_animal)    REFERENCES Animales(id_animal) ON DELETE CASCADE,
        FOREIGN KEY (id_adoptante) REFERENCES Usuario(id_adoptante) ON DELETE CASCADE
    )
");

// ── PROCESAR FORMULARIO ──────────────────────────────────────
$errores   = [];
$enviado   = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Comprobar si ya tiene solicitud pendiente para este animal
    $chk = $_conexion->prepare(
        "SELECT id_solicitud FROM SolicitudAdopcion WHERE id_animal = ? AND id_adoptante = ? AND estado_solicitud = 'PENDIENTE'"
    );
    $chk->bind_param("ii", $id_animal, $_SESSION['id']);
    $chk->execute();
    $ya_existe = $chk->get_result()->num_rows > 0;
    $chk->close();

    if ($ya_existe) {
        $errores[] = "Ya tienes una solicitud pendiente para este animal.";
    } else {
        // Recoger y sanear campos
        $f_nombre    = trim($_POST['nombre']    ?? '');
        $f_apellido  = trim($_POST['apellido']  ?? '');
        $f_email     = trim($_POST['email']     ?? '');
        $f_telefono  = trim($_POST['telefono']  ?? '');
        $f_dni       = trim($_POST['dni']       ?? '');
        $f_vivienda  = trim($_POST['tipo_vivienda'] ?? '');
        $f_jardin    = isset($_POST['tiene_jardin'])   ? 1 : 0;
        $f_metros    = trim($_POST['metros_vivienda']  ?? '');
        $f_ninos     = isset($_POST['tiene_ninos'])    ? 1 : 0;
        $f_edades    = trim($_POST['edades_ninos']     ?? '');
        $f_animales  = isset($_POST['tiene_animales']) ? 1 : 0;
        $f_desc_ani  = trim($_POST['desc_animales']    ?? '');
        $f_horas     = (int) ($_POST['horas_solo']     ?? 0);
        $f_exp       = isset($_POST['experiencia'])    ? 1 : 0;
        $f_motiv     = trim($_POST['motivacion']       ?? '');
        $f_visita    = isset($_POST['acepta_visita'])       ? 1 : 0;
        $f_seguim    = isset($_POST['acepta_seguimiento'])  ? 1 : 0;

        // Validaciones básicas
        if (empty($f_nombre))   $errores[] = "El nombre es obligatorio.";
        if (empty($f_apellido)) $errores[] = "El apellido es obligatorio.";
        if (!filter_var($f_email, FILTER_VALIDATE_EMAIL)) $errores[] = "El email no es válido.";
        if (!in_array($f_vivienda, ['piso','casa','chalet','otro'])) $errores[] = "Selecciona un tipo de vivienda.";
        if (empty($f_motiv))    $errores[] = "La motivación es obligatoria.";
        if (!$f_visita)         $errores[] = "Debes aceptar la posible visita al domicilio.";

        if (empty($errores)) {
            $ins = $_conexion->prepare(
                "INSERT INTO SolicitudAdopcion
                    (id_animal, id_adoptante, nombre, apellido, email, telefono, dni,
                     tipo_vivienda, tiene_jardin, metros_vivienda,
                     tiene_ninos, edades_ninos, tiene_animales, desc_animales,
                     horas_solo, experiencia, motivacion, acepta_visita, acepta_seguimiento)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)"
            );
            $ins->bind_param(
                "iissssssisisisisisi",
                $id_animal, $_SESSION['id'],
                $f_nombre, $f_apellido, $f_email, $f_telefono, $f_dni,
                $f_vivienda, $f_jardin, $f_metros,
                $f_ninos, $f_edades, $f_animales, $f_desc_ani,
                $f_horas, $f_exp, $f_motiv,
                $f_visita, $f_seguim
            );
            if ($ins->execute()) {
                $enviado     = true;
                $id_nueva_solicitud = (int)$_conexion->insert_id;
            } else {
                $errores[] = "Error al guardar la solicitud. Inténtalo de nuevo.";
            }
            $ins->close();
        }
    }
}

// Datos para pre-rellenar el formulario
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
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
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

        #page-wrapper {
            max-width: 820px;
            margin: 0 auto;
            padding: 36px 24px 60px;
        }

        /* ── BREADCRUMB ── */
        .breadcrumb {
            font-size: 11px;
            color: #EDA677;
            margin-bottom: 22px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .breadcrumb a { color: #EDA677; transition: color .2s; }
        .breadcrumb a:hover { color: #fff; }
        .breadcrumb span { color: #fff; opacity: .5; }

        /* ── TARJETA ANIMAL RESUMEN ── */
        .animal-resumen {
            display: flex;
            align-items: center;
            gap: 18px;
            background: rgba(255,255,255,.07);
            border: 1px solid rgba(255,255,255,.12);
            border-radius: 10px;
            padding: 16px 20px;
            margin-bottom: 28px;
        }
        .animal-resumen-foto {
            width: 72px;
            height: 72px;
            border-radius: 8px;
            overflow: hidden;
            background: #1a3f6a;
            flex-shrink: 0;
        }
        .animal-resumen-foto img { width: 100%; height: 100%; object-fit: cover; }
        .animal-resumen-foto i   { font-size: 30px; color: rgba(255,255,255,.2); display:flex; align-items:center; justify-content:center; height:100%; }
        .animal-resumen-info h2  { font-size: 17px; font-weight: 800; color: #fff; }
        .animal-resumen-info p   { font-size: 11px; color: #EDA677; margin-top: 3px; }
        .animal-resumen-info .tags { display:flex; gap:6px; margin-top:8px; flex-wrap:wrap; }
        .tag {
            font-size: 10px;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 20px;
            background: rgba(255,255,255,.1);
            color: rgba(255,255,255,.7);
        }

        /* ── FORMULARIO CARD ── */
        .form-card {
            background: #fff;
            border-radius: 12px;
            box-shadow: 0 20px 60px rgba(0,0,0,.35);
            overflow: hidden;
        }

        .form-card-header {
            background: #0D2D51;
            padding: 22px 28px;
            border-bottom: 2px solid #CA7842;
        }
        .form-card-header h1 {
            color: #fff;
            font-size: 17px;
            font-weight: 800;
            letter-spacing: .5px;
        }
        .form-card-header p {
            color: rgba(255,255,255,.6);
            font-size: 11px;
            margin-top: 5px;
            line-height: 1.6;
        }

        .form-card-body { padding: 28px; }

        /* ── SECCIONES ── */
        .seccion-titulo {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1.2px;
            color: #999;
            margin: 0 0 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .seccion-titulo::after {
            content: '';
            flex: 1;
            height: 1px;
            background: #f0f0f0;
        }
        .seccion-titulo i { color: #CA7842; font-size: 14px; }

        .form-seccion { margin-bottom: 28px; }

        /* ── GRID DE CAMPOS ── */
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }
        .form-grid.tres { grid-template-columns: 1fr 1fr 1fr; }
        .col-full { grid-column: 1 / -1; }

        @media (max-width: 600px) {
            .form-grid, .form-grid.tres { grid-template-columns: 1fr; }
        }

        /* ── CAMPOS ── */
        .campo { display: flex; flex-direction: column; gap: 5px; }
        .campo label {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .8px;
            color: #888;
        }
        .campo label .req { color: #CA7842; }

        .campo input,
        .campo select,
        .campo textarea {
            font-family: 'Poppins', sans-serif;
            font-size: 13px;
            color: #333;
            border: 1.5px solid #e0e0e0;
            border-radius: 6px;
            padding: 10px 13px;
            outline: none;
            transition: border-color .2s;
            background: #fafafa;
            width: 100%;
        }
        .campo input:focus,
        .campo select:focus,
        .campo textarea:focus {
            border-color: #CA7842;
            background: #fff;
        }
        .campo textarea { resize: vertical; min-height: 90px; }

        /* ── RADIO / CHECKBOX GROUP ── */
        .opciones-row {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .opcion-radio, .opcion-check {
            display: flex;
            align-items: center;
            gap: 7px;
            padding: 8px 14px;
            border: 1.5px solid #e0e0e0;
            border-radius: 20px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 500;
            color: #555;
            transition: border-color .2s, background .2s, color .2s;
            user-select: none;
        }
        .opcion-radio:hover, .opcion-check:hover { border-color: #CA7842; color: #CA7842; }
        .opcion-radio input, .opcion-check input { display: none; }
        .opcion-radio.sel, .opcion-check.sel {
            border-color: #CA7842;
            background: #FFF1E6;
            color: #CA7842;
        }

        /* ── SUBGRUPO CONDICIONAL ── */
        .subgrupo {
            margin-top: 12px;
            padding: 14px 16px;
            background: #f9f9f9;
            border-left: 3px solid #EDA677;
            border-radius: 0 6px 6px 0;
            display: none;
        }
        .subgrupo.visible { display: block; }

        /* ── AVISO ── */
        .aviso-box {
            display: flex;
            gap: 12px;
            background: #FFF8E1;
            border: 1px solid #FFD54F;
            border-radius: 8px;
            padding: 14px 16px;
            margin-bottom: 20px;
        }
        .aviso-box i { color: #F9A825; font-size: 18px; flex-shrink: 0; margin-top: 1px; }
        .aviso-box p { font-size: 11px; color: #7a5f00; line-height: 1.6; }

        /* ── CHECK LEGAL ── */
        .checks-legales { display: flex; flex-direction: column; gap: 10px; }
        .check-legal {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            font-size: 12px;
            color: #555;
            line-height: 1.5;
        }
        .check-legal input[type=checkbox] {
            width: 16px;
            height: 16px;
            accent-color: #CA7842;
            flex-shrink: 0;
            margin-top: 2px;
        }

        /* ── ERRORES / ÉXITO ── */
        .alerta {
            border-radius: 8px;
            padding: 14px 18px;
            margin-bottom: 20px;
            font-size: 12px;
            line-height: 1.6;
        }
        .alerta-error {
            background: #FFEBEE;
            border: 1px solid #EF9A9A;
            color: #b71c1c;
        }
        .alerta ul { margin: 6px 0 0 16px; padding: 0; }

        /* ── ÉXITO ── */
        .exito-wrapper {
            text-align: center;
            padding: 40px 28px;
        }
        .exito-icono {
            width: 72px;
            height: 72px;
            background: #EAF3DE;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 18px;
        }
        .exito-icono i { font-size: 36px; color: #2e7d12; }
        .exito-wrapper h2 { font-size: 20px; font-weight: 800; color: #0D2D51; }
        .exito-wrapper p  { font-size: 12px; color: #777; margin-top: 8px; line-height: 1.7; max-width: 440px; margin-left: auto; margin-right: auto; }
        .exito-btns { display: flex; gap: 12px; justify-content: center; margin-top: 24px; flex-wrap: wrap; }

        /* ── BOTONES ── */
        .btn-primario {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 13px 28px;
            background: #CA7842;
            color: #fff;
            border: none;
            border-radius: 6px;
            font-family: 'Poppins', sans-serif;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 1px;
            cursor: pointer;
            transition: background .2s, transform .15s;
            text-decoration: none;
        }
        .btn-primario:hover { background: #b56a32; transform: translateY(-1px); color: #fff; }

        .btn-secundario {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 11px 22px;
            background: transparent;
            color: #CA7842;
            border: 1.5px solid #CA7842;
            border-radius: 6px;
            font-family: 'Poppins', sans-serif;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: background .2s, color .2s;
            text-decoration: none;
        }
        .btn-secundario:hover { background: #CA7842; color: #fff; }

        .volver { margin-top: 24px; text-align: center; }
        .volver a { font-size: 12px; font-weight: 500; color: #EDA677; transition: color .2s; }
        .volver a:hover { color: #fff; }
    </style>
</head>
<body>

<!-- HEADER -->
<header>
    <nav class="hBotones">
        <a class="hBoton" href="#protectoras">PROTECTORAS</a>
        <a class="hBoton" href="">COLABORADORES</a>
    </nav>
    <nav id="header-izq">
        <a href="index.php" target="_self">
            <img src="../img/profile/default/oficiales/logo.svg" alt="Go Catch" height="40">
        </a>
    </nav>
    <nav class="hBotones">
        <a class="hBoton" href="urgente.php">URGENTE</a>
        <?php if (isset($_SESSION['id'])): ?>
            <a class="hBoton" href="perfil.php">
                <i class="zmdi zmdi-account"></i>
                <?= htmlspecialchars($_SESSION["nombre"] ?? '') ?>
            </a>
            <a href="../src/sesion/logout.php" id="boton-destacado">CERRAR SESIÓN</a>
        <?php else: ?>
            <a class="hBoton" href="registro.html">REGÍSTRATE</a>
            <a href="login.html" id="boton-destacado">INICIA SESIÓN</a>
        <?php endif; ?>
    </nav>
</header>

<div id="page-wrapper">

    <!-- BREADCRUMB -->
    <div class="breadcrumb">
        <a href="index.php"><i class="zmdi zmdi-home"></i> Inicio</a>
        <span>/</span>
        <a href="index.php#animales">Animales</a>
        <span>/</span>
        <a href="ficha-animal.php?id=<?= $id_animal ?>"><?= htmlspecialchars($animal['nombre']) ?></a>
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
                <a href="reservar-cita.php?solicitud=<?= $id_nueva_solicitud ?>" class="btn-primario" style="margin: 20px auto 0; max-width: 340px">
                    <i class="zmdi zmdi-calendar-check"></i>
                    RESERVAR FECHA DE ENTREVISTA
                </a>
                <?php endif; ?>
                <div class="exito-btns">
                    <a href="ficha-animal.php?id=<?= $id_animal ?>" class="btn-secundario">
                        <i class="zmdi zmdi-arrow-left"></i> Volver a la ficha
                    </a>
                    <a href="index.php" class="btn-primario" style="max-width:none">
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
        <a href="ficha-animal.php?id=<?= $id_animal ?>">← Volver a la ficha de <?= htmlspecialchars($animal['nombre']) ?></a>
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
