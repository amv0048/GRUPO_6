<?php
session_start();
require "../src/sesion/conexion.php";

error_reporting(E_ALL);
ini_set("display_errors", 1);

// ── CREAR TABLAS SI NO EXISTEN ───────────────────────────────
$_conexion->query("
    CREATE TABLE IF NOT EXISTS PublicacionUrgente (
        id_publicacion    INT AUTO_INCREMENT PRIMARY KEY,
        tipo              ENUM('PERDIDO','ENCONTRADO') NOT NULL,
        nombre_animal     VARCHAR(100),
        especie           VARCHAR(50),
        descripcion       TEXT NOT NULL,
        foto              VARCHAR(255),
        nombre_contacto   VARCHAR(100),
        telefono_contacto VARCHAR(20),
        ciudad            VARCHAR(100),
        fecha             DATETIME DEFAULT CURRENT_TIMESTAMP,
        activo            BOOLEAN DEFAULT TRUE
    ) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci
");
$_conexion->query("
    CREATE TABLE IF NOT EXISTS ComentarioUrgente (
        id_comentario  INT AUTO_INCREMENT PRIMARY KEY,
        id_publicacion INT NOT NULL,
        texto          TEXT NOT NULL,
        nombre_autor   VARCHAR(100) NOT NULL,
        fecha          DATETIME DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_comentario_pub
            FOREIGN KEY (id_publicacion)
            REFERENCES PublicacionUrgente(id_publicacion)
            ON DELETE CASCADE
    ) CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci
");

// ── DIRECTORIO DE UPLOADS ────────────────────────────────────
$upload_dir = "../img/urgente/";
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

$msg_ok  = null;
$msg_err = null;

// ════════════════════════════════════════════════════════════
//  POST: NUEVA PUBLICACIÓN
// ════════════════════════════════════════════════════════════
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "publicar") {

    $tipo        = in_array($_POST["tipo"] ?? '', ["PERDIDO", "ENCONTRADO"]) ? $_POST["tipo"] : null;
    $descripcion = trim($_POST["descripcion"] ?? '');
    $nombre      = trim($_POST["nombre_animal"] ?? '');
    $especie     = trim($_POST["especie"] ?? '');
    $ciudad      = trim($_POST["ciudad"] ?? '');
    $contacto    = trim($_POST["nombre_contacto"] ?? '');
    $telefono    = trim($_POST["telefono_contacto"] ?? '');

    if (!$tipo || strlen($descripcion) < 5) {
        $msg_err = "Por favor, indica el tipo de publicación y una descripción.";
    } else {
        // Subida de foto (opcional)
        $foto_ruta = null;
        if (!empty($_FILES["foto"]["name"])) {
            $ext_permitidas = ["jpg", "jpeg", "png", "gif", "webp"];
            $ext = strtolower(pathinfo($_FILES["foto"]["name"], PATHINFO_EXTENSION));
            if (!in_array($ext, $ext_permitidas)) {
                $msg_err = "Formato de imagen no permitido. Usa JPG, PNG, GIF o WEBP.";
            } elseif ($_FILES["foto"]["size"] > 5 * 1024 * 1024) {
                $msg_err = "La imagen no puede superar los 5 MB.";
            } else {
                $nombre_archivo = uniqid("urg_", true) . "." . $ext;
                $ruta_destino   = $upload_dir . $nombre_archivo;
                if (move_uploaded_file($_FILES["foto"]["tmp_name"], $ruta_destino)) {
                    $foto_ruta = "../img/urgente/" . $nombre_archivo;
                } else {
                    $msg_err = "No se pudo guardar la imagen. Inténtalo de nuevo.";
                }
            }
        }

        if (!$msg_err) {
            $stmt = $_conexion->prepare(
                "INSERT INTO PublicacionUrgente
                    (tipo, nombre_animal, especie, descripcion, foto, nombre_contacto, telefono_contacto, ciudad)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
            );
            $stmt->bind_param("ssssssss",
                $tipo, $nombre, $especie, $descripcion,
                $foto_ruta, $contacto, $telefono, $ciudad
            );
            if ($stmt->execute()) {
                $msg_ok = "¡Publicación enviada correctamente!";
            } else {
                $msg_err = "Error al guardar la publicación. Inténtalo de nuevo.";
            }
            $stmt->close();
        }
    }
}

// ════════════════════════════════════════════════════════════
//  POST: NUEVO COMENTARIO
// ════════════════════════════════════════════════════════════
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "comentar") {
    $id_pub      = isset($_POST["id_publicacion"]) ? (int)$_POST["id_publicacion"] : 0;
    $texto       = trim($_POST["texto"] ?? '');
    $nombre_aut  = trim($_POST["nombre_autor"] ?? '');

    if ($id_pub && strlen($texto) >= 2 && strlen($nombre_aut) >= 2) {
        $stmt = $_conexion->prepare(
            "INSERT INTO ComentarioUrgente (id_publicacion, texto, nombre_autor) VALUES (?, ?, ?)"
        );
        $stmt->bind_param("iss", $id_pub, $texto, $nombre_aut);
        $stmt->execute();
        $stmt->close();
    }
    // Redirigir para evitar reenvío al refrescar
    header("Location: urgente.php#pub-" . $id_pub);
    exit();
}

// ════════════════════════════════════════════════════════════
//  FILTRO
// ════════════════════════════════════════════════════════════
$filtro = isset($_GET["tipo"]) && in_array($_GET["tipo"], ["PERDIDO", "ENCONTRADO"])
            ? $_GET["tipo"]
            : "";

// ════════════════════════════════════════════════════════════
//  CARGAR PUBLICACIONES
// ════════════════════════════════════════════════════════════
$sql_pubs = "SELECT p.*,
                (SELECT COUNT(*) FROM ComentarioUrgente c WHERE c.id_publicacion = p.id_publicacion) AS total_comentarios
             FROM PublicacionUrgente p
             WHERE p.activo = 1";
if ($filtro) {
    $sql_pubs .= " AND p.tipo = '" . $filtro . "'";
}
$sql_pubs .= " ORDER BY p.fecha DESC";

$result   = $_conexion->query($sql_pubs);
$pubs     = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];

// ════════════════════════════════════════════════════════════
//  CARGAR COMENTARIOS DE CADA PUBLICACIÓN
// ════════════════════════════════════════════════════════════
$comentarios_map = [];
if (!empty($pubs)) {
    $ids = implode(",", array_column($pubs, "id_publicacion"));
    $res_c = $_conexion->query(
        "SELECT * FROM ComentarioUrgente WHERE id_publicacion IN ($ids) ORDER BY fecha ASC"
    );
    if ($res_c) {
        foreach ($res_c->fetch_all(MYSQLI_ASSOC) as $c) {
            $comentarios_map[$c["id_publicacion"]][] = $c;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Urgente · Go Catch</title>
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
        input, textarea, select, button { font-family: 'Poppins', sans-serif; }

        /* ══ HERO ══ */
        #urgente-hero {
            background: #0D2D51;
            padding: 40px 24px 0;
            text-align: center;
        }
        #urgente-hero h1 {
            color: #fff;
            font-size: 28px;
            font-weight: 800;
            letter-spacing: 0.5px;
        }
        #urgente-hero h1 span { color: #CA7842; }
        #urgente-hero p {
            color: rgba(255,255,255,0.6);
            font-size: 13px;
            margin-top: 6px;
        }

        /* ══ FILTROS ══ */
        #filtros-bar {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            padding: 24px 24px 0;
            flex-wrap: wrap;
        }
        .filtro-btn {
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 700;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            border: 1.5px solid transparent;
            cursor: pointer;
            transition: all 0.2s;
            text-decoration: none;
        }
        .filtro-btn.todos {
            background: rgba(255,255,255,0.12);
            color: #fff;
            border-color: rgba(255,255,255,0.2);
        }
        .filtro-btn.todos:hover,
        .filtro-btn.todos.activo { background: #fff; color: #0D2D51; }

        .filtro-btn.perdidos {
            background: rgba(220,53,69,0.15);
            color: #ff6b7a;
            border-color: rgba(220,53,69,0.3);
        }
        .filtro-btn.perdidos:hover,
        .filtro-btn.perdidos.activo { background: #dc3545; color: #fff; border-color: #dc3545; }

        .filtro-btn.encontrados {
            background: rgba(46,125,18,0.15);
            color: #5fb541;
            border-color: rgba(46,125,18,0.3);
        }
        .filtro-btn.encontrados:hover,
        .filtro-btn.encontrados.activo { background: #2e7d12; color: #fff; border-color: #2e7d12; }

        /* ══ FEED ══ */
        #feed {
            max-width: 640px;
            margin: 28px auto 60px;
            padding: 0 16px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        /* ══ TARJETA PUBLICACIÓN ══ */
        .pub-card {
            background: #fff;
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0,0,0,0.28);
        }

        /* ─ Cabecera de la tarjeta ─ */
        .pub-head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 14px 18px 10px;
        }
        .pub-head-left { display: flex; align-items: center; gap: 10px; }
        .pub-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }
        .pub-avatar.perdido   { background: #fdecea; color: #dc3545; }
        .pub-avatar.encontrado { background: #EAF3DE; color: #2e7d12; }

        .pub-meta-nombre {
            font-size: 13px;
            font-weight: 700;
            color: #222;
        }
        .pub-meta-sub {
            font-size: 11px;
            color: #999;
            display: flex;
            align-items: center;
            gap: 6px;
            margin-top: 1px;
        }
        .pub-meta-sub i { font-size: 12px; color: #CA7842; }

        .badge-tipo {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 11px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 0.7px;
            text-transform: uppercase;
        }
        .badge-perdido   { background: #fdecea; color: #dc3545; }
        .badge-encontrado { background: #EAF3DE; color: #2e7d12; }

        /* ─ Foto ─ */
        .pub-foto {
            width: 100%;
            max-height: 420px;
            overflow: hidden;
        }
        .pub-foto img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            display: block;
        }

        /* ─ Cuerpo ─ */
        .pub-body { padding: 14px 18px; }

        .pub-descripcion {
            font-size: 13px;
            color: #333;
            line-height: 1.7;
            margin-bottom: 12px;
        }
        .pub-descripcion .pub-autor {
            font-weight: 700;
            color: #0D2D51;
            margin-right: 4px;
        }

        .pub-tags {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-bottom: 12px;
        }
        .pub-tag {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 11px;
            font-weight: 500;
            background: #f4f4f4;
            color: #666;
        }
        .pub-tag i { color: #CA7842; font-size: 13px; }

        /* ─ Footer de tarjeta ─ */
        .pub-footer {
            border-top: 1px solid #f0f0f0;
            padding: 10px 18px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .btn-comentarios {
            background: none;
            border: none;
            color: #888;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 4px 0;
            transition: color 0.2s;
        }
        .btn-comentarios:hover { color: #CA7842; }
        .btn-comentarios i { font-size: 15px; }
        .btn-comentarios.activo { color: #CA7842; }

        .pub-fecha {
            font-size: 11px;
            color: #bbb;
        }

        /* ── SECCIÓN DE COMENTARIOS ── */
        .comentarios-section {
            display: none;
            border-top: 1px solid #f5f5f5;
            background: #fafafa;
        }
        .comentarios-section.abierto { display: block; }

        .comentarios-lista {
            padding: 14px 18px 0;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .comentario {
            display: flex;
            gap: 10px;
        }
        .comentario-avatar {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background: #e8f0fe;
            color: #1a5cd6;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 700;
            flex-shrink: 0;
        }
        .comentario-burbuja {
            background: #fff;
            border-radius: 0 10px 10px 10px;
            padding: 8px 12px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.06);
            flex: 1;
        }
        .comentario-autor {
            font-size: 11px;
            font-weight: 700;
            color: #0D2D51;
            margin-bottom: 2px;
        }
        .comentario-texto {
            font-size: 12px;
            color: #444;
            line-height: 1.5;
        }
        .comentario-fecha {
            font-size: 10px;
            color: #bbb;
            margin-top: 4px;
        }

        /* Form comentario */
        .form-comentario {
            padding: 12px 18px 14px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .form-comentario-row {
            display: flex;
            gap: 8px;
        }
        .input-comentario {
            flex: 1;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 12px;
            color: #333;
            background: #fff;
            transition: border-color 0.2s;
            outline: none;
        }
        .input-comentario:focus { border-color: #CA7842; }
        .input-comentario::placeholder { color: #bbb; }

        .btn-enviar-comentario {
            background: #CA7842;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 8px 16px;
            font-size: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.2s;
            white-space: nowrap;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        .btn-enviar-comentario:hover { background: #b56a32; }

        /* ══ ESTADO VACÍO ══ */
        #feed-vacio {
            text-align: center;
            padding: 60px 20px;
            color: rgba(255,255,255,0.5);
        }
        #feed-vacio i { font-size: 52px; display: block; margin-bottom: 14px; opacity: 0.4; }
        #feed-vacio p { font-size: 14px; }

        /* ══ MENSAJES ══ */
        .msg-ok, .msg-err {
            max-width: 640px;
            margin: 16px auto 0;
            padding: 12px 18px;
            border-radius: 8px;
            font-size: 12px;
            text-align: center;
        }
        .msg-ok  { background: #EAF3DE; border: 1px solid #97C459; color: #173404; }
        .msg-err { background: #fdecea; border: 1px solid #F09595; color: #8B0000; }

        /* ══ BOTÓN FLOTANTE PUBLICAR ══ */
        #btn-publicar-fab {
            position: fixed;
            bottom: 32px;
            right: 32px;
            width: 56px;
            height: 56px;
            background: #CA7842;
            color: #fff;
            border: none;
            border-radius: 50%;
            font-size: 26px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 4px 18px rgba(202,120,66,0.55);
            transition: background 0.2s, transform 0.2s;
            z-index: 100;
        }
        #btn-publicar-fab:hover { background: #b56a32; transform: scale(1.08); }

        /* ══ MODAL ══ */
        #modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(10,25,45,0.72);
            z-index: 200;
            align-items: center;
            justify-content: center;
            padding: 20px;
            backdrop-filter: blur(3px);
        }
        #modal-overlay.abierto { display: flex; }

        #modal-box {
            background: #fff;
            border-radius: 14px;
            width: 100%;
            max-width: 520px;
            max-height: 90vh;
            overflow-y: auto;
            box-shadow: 0 24px 80px rgba(0,0,0,0.45);
            animation: modalIn 0.25s ease;
        }
        @keyframes modalIn {
            from { opacity: 0; transform: translateY(20px) scale(0.97); }
            to   { opacity: 1; transform: none; }
        }

        #modal-header {
            background: #0D2D51;
            padding: 20px 24px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        #modal-header h2 {
            color: #fff;
            font-size: 16px;
            font-weight: 700;
        }
        #modal-header p {
            color: #EDA677;
            font-size: 11px;
            margin-top: 2px;
        }
        #btn-cerrar-modal {
            background: none;
            border: none;
            color: rgba(255,255,255,0.6);
            font-size: 22px;
            cursor: pointer;
            padding: 0;
            line-height: 1;
            transition: color 0.2s;
        }
        #btn-cerrar-modal:hover { color: #fff; }

        #modal-body { padding: 22px 24px 24px; }

        /* Toggle PERDIDO / ENCONTRADO */
        .tipo-toggle {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        .tipo-btn {
            flex: 1;
            padding: 11px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 700;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            border: 2px solid transparent;
            cursor: pointer;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 7px;
        }
        .tipo-btn.perdido {
            background: #fdecea;
            color: #dc3545;
            border-color: #f5c6cb;
        }
        .tipo-btn.perdido:hover,
        .tipo-btn.perdido.activo {
            background: #dc3545;
            color: #fff;
            border-color: #dc3545;
        }
        .tipo-btn.encontrado {
            background: #EAF3DE;
            color: #2e7d12;
            border-color: #c3e6a0;
        }
        .tipo-btn.encontrado:hover,
        .tipo-btn.encontrado.activo {
            background: #2e7d12;
            color: #fff;
            border-color: #2e7d12;
        }

        /* Campos del modal */
        .modal-label {
            display: block;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #aaa;
            margin-bottom: 6px;
        }
        .modal-input, .modal-textarea, .modal-select {
            width: 100%;
            border: 1px solid #e0e0e0;
            border-radius: 8px;
            padding: 10px 12px;
            font-size: 13px;
            color: #333;
            background: #fff;
            margin-bottom: 16px;
            outline: none;
            transition: border-color 0.2s;
            font-family: 'Poppins', sans-serif;
        }
        .modal-input:focus,
        .modal-textarea:focus,
        .modal-select:focus { border-color: #CA7842; }
        .modal-textarea { resize: vertical; min-height: 90px; }

        .modal-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0 14px;
        }

        /* Upload foto */
        .upload-area {
            border: 2px dashed #e0e0e0;
            border-radius: 8px;
            padding: 20px;
            text-align: center;
            cursor: pointer;
            transition: border-color 0.2s, background 0.2s;
            margin-bottom: 16px;
            position: relative;
        }
        .upload-area:hover { border-color: #CA7842; background: #fffaf7; }
        .upload-area input[type="file"] {
            position: absolute;
            inset: 0;
            opacity: 0;
            cursor: pointer;
            width: 100%;
            height: 100%;
        }
        .upload-area i { font-size: 28px; color: #ddd; display: block; margin-bottom: 6px; }
        .upload-area p { font-size: 11px; color: #bbb; }
        #preview-foto {
            display: none;
            width: 100%;
            max-height: 180px;
            object-fit: cover;
            border-radius: 6px;
            margin-bottom: 16px;
        }

        /* Botón submit del modal */
        #btn-submit-pub {
            width: 100%;
            padding: 14px;
            background: #CA7842;
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 700;
            letter-spacing: 1px;
            cursor: pointer;
            transition: background 0.2s, transform 0.15s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            font-family: 'Poppins', sans-serif;
        }
        #btn-submit-pub:hover { background: #b56a32; transform: translateY(-1px); }

        /* Sección "sin comentarios" */
        .sin-comentarios {
            padding: 10px 18px 0;
            font-size: 11px;
            color: #ccc;
            font-style: italic;
        }
    </style>
</head>
<body>

<!-- HEADER -->
<header>
    <nav class="hBotones">
        <a class="hBoton" href="index.php#protectoras">PROTECTORAS</a>
        <a class="hBoton" href="">COLABORADORES</a>
    </nav>
    <nav id="header-izq">
        <a href="index.php" target="_self">
            <img src="../img/profile/default/oficiales/logo.svg" alt="Go Catch" height="40">
        </a>
    </nav>
    <nav class="hBotones">
        <a class="hBoton" href="urgente.php" style="color:#fff;">URGENTE</a>
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


<!-- HERO -->
<div id="urgente-hero">
    <h1>Mascotas <span>Urgentes</span></h1>
    <p>¿Has perdido o encontrado una mascota? Publícalo aquí y ayuda a reunirlos.</p>
</div>


<!-- MENSAJES -->
<?php if ($msg_ok): ?>
    <div class="msg-ok"><?= htmlspecialchars($msg_ok) ?></div>
<?php endif; ?>
<?php if ($msg_err): ?>
    <div class="msg-err"><?= htmlspecialchars($msg_err) ?></div>
<?php endif; ?>


<!-- FILTROS -->
<div id="filtros-bar">
    <a class="filtro-btn todos <?= $filtro === '' ? 'activo' : '' ?>" href="urgente.php">
        <i class="zmdi zmdi-view-list"></i> Todos
    </a>
    <a class="filtro-btn perdidos <?= $filtro === 'PERDIDO' ? 'activo' : '' ?>" href="urgente.php?tipo=PERDIDO">
        <i class="zmdi zmdi-alert-circle"></i> Perdidos
    </a>
    <a class="filtro-btn encontrados <?= $filtro === 'ENCONTRADO' ? 'activo' : '' ?>" href="urgente.php?tipo=ENCONTRADO">
        <i class="zmdi zmdi-check-circle"></i> Encontrados
    </a>
</div>


<!-- FEED -->
<div id="feed">

    <?php if (empty($pubs)): ?>
        <div id="feed-vacio">
            <i class="zmdi zmdi-paw"></i>
            <p>No hay publicaciones todavía.<br>¡Sé el primero en publicar!</p>
        </div>

    <?php else: ?>
        <?php foreach ($pubs as $pub): ?>
        <?php
            $tipo_pub   = $pub['tipo'];
            $css_tipo   = strtolower($tipo_pub);
            $icono_tipo = $tipo_pub === 'PERDIDO' ? 'zmdi-alert-circle' : 'zmdi-check-circle';
            $etiqueta   = $tipo_pub === 'PERDIDO'  ? 'Perdido'  : 'Encontrado';
            $id_p       = (int)$pub['id_publicacion'];
            $coms       = $comentarios_map[$id_p] ?? [];
            $n_coms     = (int)$pub['total_comentarios'];

            // Formato fecha
            $fecha_obj  = new DateTime($pub['fecha']);
            $ahora      = new DateTime();
            $diff       = $ahora->diff($fecha_obj);
            if ($diff->days === 0 && $diff->h === 0) {
                $fecha_fmt = "Hace " . $diff->i . " min";
            } elseif ($diff->days === 0) {
                $fecha_fmt = "Hace " . $diff->h . " h";
            } elseif ($diff->days < 7) {
                $fecha_fmt = "Hace " . $diff->days . " día" . ($diff->days > 1 ? "s" : "");
            } else {
                $fecha_fmt = $fecha_obj->format("d/m/Y");
            }

            $nombre_pub = !empty($pub['nombre_animal'])
                ? htmlspecialchars($pub['nombre_animal'])
                : ($tipo_pub === 'PERDIDO' ? 'Mascota perdida' : 'Mascota encontrada');
        ?>
        <div class="pub-card" id="pub-<?= $id_p ?>">

            <!-- Cabecera -->
            <div class="pub-head">
                <div class="pub-head-left">
                    <div class="pub-avatar <?= $css_tipo ?>">
                        <i class="zmdi <?= $icono_tipo ?>"></i>
                    </div>
                    <div>
                        <p class="pub-meta-nombre"><?= $nombre_pub ?></p>
                        <div class="pub-meta-sub">
                            <?php if (!empty($pub['ciudad'])): ?>
                                <i class="zmdi zmdi-pin"></i>
                                <?= htmlspecialchars($pub['ciudad']) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <span class="badge-tipo badge-<?= $css_tipo ?>">
                    <i class="zmdi <?= $icono_tipo ?>"></i>
                    <?= $etiqueta ?>
                </span>
            </div>

            <!-- Foto (si existe) -->
            <?php if (!empty($pub['foto'])): ?>
            <div class="pub-foto">
                <img src="<?= htmlspecialchars($pub['foto']) ?>"
                     alt="<?= $nombre_pub ?>"
                     loading="lazy">
            </div>
            <?php endif; ?>

            <!-- Cuerpo -->
            <div class="pub-body">
                <p class="pub-descripcion">
                    <?php if (!empty($pub['nombre_contacto'])): ?>
                        <span class="pub-autor"><?= htmlspecialchars($pub['nombre_contacto']) ?>:</span>
                    <?php endif; ?>
                    <?= nl2br(htmlspecialchars($pub['descripcion'])) ?>
                </p>

                <!-- Tags informativos -->
                <div class="pub-tags">
                    <?php if (!empty($pub['especie'])): ?>
                        <span class="pub-tag">
                            <i class="zmdi zmdi-paw"></i>
                            <?= htmlspecialchars(ucfirst($pub['especie'])) ?>
                        </span>
                    <?php endif; ?>
                    <?php if (!empty($pub['telefono_contacto'])): ?>
                        <span class="pub-tag">
                            <i class="zmdi zmdi-phone"></i>
                            <?= htmlspecialchars($pub['telefono_contacto']) ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Footer -->
            <div class="pub-footer">
                <button class="btn-comentarios" onclick="toggleComentarios(<?= $id_p ?>)">
                    <i class="zmdi zmdi-comment-outline"></i>
                    <?php if ($n_coms > 0): ?>
                        Ver <?= $n_coms ?> comentario<?= $n_coms !== 1 ? 's' : '' ?>
                    <?php else: ?>
                        Comentar
                    <?php endif; ?>
                </button>
                <span class="pub-fecha"><?= $fecha_fmt ?></span>
            </div>

            <!-- Sección de comentarios (oculta por defecto) -->
            <div class="comentarios-section" id="coms-<?= $id_p ?>">

                <!-- Lista de comentarios -->
                <div class="comentarios-lista" id="lista-coms-<?= $id_p ?>">
                    <?php if (empty($coms)): ?>
                        <p class="sin-comentarios">Sin comentarios aún. ¡Sé el primero!</p>
                    <?php else: ?>
                        <?php foreach ($coms as $c): ?>
                        <div class="comentario">
                            <div class="comentario-avatar">
                                <?= mb_strtoupper(mb_substr($c['nombre_autor'], 0, 1)) ?>
                            </div>
                            <div class="comentario-burbuja">
                                <p class="comentario-autor"><?= htmlspecialchars($c['nombre_autor']) ?></p>
                                <p class="comentario-texto"><?= nl2br(htmlspecialchars($c['texto'])) ?></p>
                                <p class="comentario-fecha">
                                    <?= (new DateTime($c['fecha']))->format('d/m/Y H:i') ?>
                                </p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- Form nuevo comentario -->
                <form class="form-comentario" method="POST" action="urgente.php">
                    <input type="hidden" name="action" value="comentar">
                    <input type="hidden" name="id_publicacion" value="<?= $id_p ?>">
                    <input type="text"
                           name="nombre_autor"
                           class="input-comentario"
                           placeholder="Tu nombre"
                           maxlength="100"
                           required>
                    <div class="form-comentario-row">
                        <input type="text"
                               name="texto"
                               class="input-comentario"
                               placeholder="Escribe un comentario…"
                               maxlength="500"
                               required>
                        <button type="submit" class="btn-enviar-comentario">
                            <i class="zmdi zmdi-send"></i>
                        </button>
                    </div>
                </form>

            </div>

        </div><!-- .pub-card -->
        <?php endforeach; ?>

    <?php endif; ?>

</div><!-- #feed -->


<!-- BOTÓN FLOTANTE -->
<button id="btn-publicar-fab" onclick="abrirModal()" title="Nueva publicación">
    <i class="zmdi zmdi-plus"></i>
</button>


<!-- ════════════════════════════════════════════════════════
     MODAL — NUEVA PUBLICACIÓN
════════════════════════════════════════════════════════ -->
<div id="modal-overlay" onclick="cerrarModalOverlay(event)">
    <div id="modal-box">

        <div id="modal-header">
            <div>
                <h2>Nueva publicación</h2>
                <p>Sin registro · visible para todos</p>
            </div>
            <button id="btn-cerrar-modal" onclick="cerrarModal()">
                <i class="zmdi zmdi-close"></i>
            </button>
        </div>

        <div id="modal-body">
            <form action="urgente.php" method="POST" enctype="multipart/form-data" id="form-pub">
                <input type="hidden" name="action" value="publicar">
                <input type="hidden" name="tipo" id="input-tipo" value="">

                <!-- Toggle tipo -->
                <div class="tipo-toggle">
                    <button type="button" class="tipo-btn perdido" onclick="seleccionarTipo('PERDIDO', this)">
                        <i class="zmdi zmdi-alert-circle"></i> He perdido una mascota
                    </button>
                    <button type="button" class="tipo-btn encontrado" onclick="seleccionarTipo('ENCONTRADO', this)">
                        <i class="zmdi zmdi-check-circle"></i> He encontrado una mascota
                    </button>
                </div>

                <!-- Descripción (obligatorio) -->
                <label class="modal-label">Descripción *</label>
                <textarea name="descripcion" class="modal-textarea"
                          placeholder="Describe a la mascota: color, tamaño, dónde la has visto, cuándo…"
                          required></textarea>

                <!-- Grid: nombre animal + especie -->
                <div class="modal-grid">
                    <div>
                        <label class="modal-label">Nombre (si lo conoces)</label>
                        <input type="text" name="nombre_animal" class="modal-input" placeholder="Ej. Toby">
                    </div>
                    <div>
                        <label class="modal-label">Especie</label>
                        <select name="especie" class="modal-select modal-input">
                            <option value="">— Selecciona —</option>
                            <option value="Perro">Perro</option>
                            <option value="Gato">Gato</option>
                            <option value="Otro">Otro</option>
                        </select>
                    </div>
                </div>

                <!-- Ciudad -->
                <label class="modal-label">Ciudad / Zona</label>
                <input type="text" name="ciudad" class="modal-input" placeholder="Ej. Málaga — Churriana">

                <!-- Foto -->
                <label class="modal-label">Foto (opcional)</label>
                <img id="preview-foto" src="" alt="Previsualización">
                <div class="upload-area" id="upload-area">
                    <input type="file" name="foto" id="input-foto"
                           accept="image/jpeg,image/png,image/gif,image/webp"
                           onchange="previsualizarFoto(this)">
                    <i class="zmdi zmdi-camera"></i>
                    <p>Haz clic para subir una foto<br><span style="font-size:10px">JPG, PNG, GIF, WEBP · máx. 5 MB</span></p>
                </div>

                <!-- Contacto -->
                <div class="modal-grid">
                    <div>
                        <label class="modal-label">Tu nombre</label>
                        <input type="text" name="nombre_contacto" class="modal-input" placeholder="Ej. María">
                    </div>
                    <div>
                        <label class="modal-label">Teléfono de contacto</label>
                        <input type="tel" name="telefono_contacto" class="modal-input" placeholder="Ej. 612 345 678">
                    </div>
                </div>

                <button type="submit" id="btn-submit-pub">
                    <i class="zmdi zmdi-send"></i> PUBLICAR
                </button>
            </form>
        </div>

    </div>
</div><!-- #modal-overlay -->


<script>
/* ── MODAL ── */
function abrirModal() {
    document.getElementById('modal-overlay').classList.add('abierto');
    document.body.style.overflow = 'hidden';
}
function cerrarModal() {
    document.getElementById('modal-overlay').classList.remove('abierto');
    document.body.style.overflow = '';
}
function cerrarModalOverlay(e) {
    if (e.target === document.getElementById('modal-overlay')) cerrarModal();
}
document.addEventListener('keydown', e => { if (e.key === 'Escape') cerrarModal(); });

/* ── TIPO PERDIDO / ENCONTRADO ── */
function seleccionarTipo(valor, btn) {
    document.getElementById('input-tipo').value = valor;
    document.querySelectorAll('.tipo-btn').forEach(b => b.classList.remove('activo'));
    btn.classList.add('activo');
}

/* ── PREVISUALIZAR FOTO ── */
function previsualizarFoto(input) {
    const preview = document.getElementById('preview-foto');
    const area    = document.getElementById('upload-area');
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = e => {
            preview.src = e.target.result;
            preview.style.display = 'block';
            area.style.display = 'none';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

/* ── COMENTARIOS: TOGGLE ── */
function toggleComentarios(id) {
    const section = document.getElementById('coms-' + id);
    const btn     = section.previousElementSibling.querySelector('.btn-comentarios');
    const abierto = section.classList.contains('abierto');
    section.classList.toggle('abierto', !abierto);
    btn.classList.toggle('activo', !abierto);
}

/* ── ABRIR COMENTARIOS DE LA PUBLICACIÓN ANCLADA EN LA URL ── */
document.addEventListener('DOMContentLoaded', () => {
    const hash = window.location.hash; // e.g. #pub-3
    if (hash && hash.startsWith('#pub-')) {
        const id = hash.replace('#pub-', '');
        const section = document.getElementById('coms-' + id);
        if (section) {
            section.classList.add('abierto');
            setTimeout(() => {
                document.getElementById('pub-' + id)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 200);
        }
    }
    // Si hay msg_ok, abrir modal de nuevo publicar no tiene sentido; pero sí mostrar el mensaje
    <?php if ($msg_ok): ?>
        // La publicación se envió bien — el modal ya está cerrado
    <?php endif; ?>
});
</script>

</body>
</html>
