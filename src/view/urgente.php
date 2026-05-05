<?php
session_start();
require "../sesion/conexion.php";
require_once "../model/UrgenteModel.php";

// ── DIRECTORIO DE UPLOADS ────────────────────────────────────
$upload_dir = "../../img/urgente/";
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

$msg_ok       = null;
$msg_err      = null;
$urgenteModel = new UrgenteModel($_conexion);

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
                    $foto_ruta = "../../img/urgente/" . $nombre_archivo;
                } else {
                    $msg_err = "No se pudo guardar la imagen. Inténtalo de nuevo.";
                }
            }
        }

        if (!$msg_err) {
            if ($urgenteModel->createPublicacion([
                'tipo'       => $tipo,      'nombre'     => $nombre,
                'especie'    => $especie,   'descripcion' => $descripcion,
                'foto'       => $foto_ruta, 'contacto'   => $contacto,
                'telefono'   => $telefono,  'ciudad'     => $ciudad,
            ])) {
                $msg_ok = "¡Publicación enviada correctamente!";
            } else {
                $msg_err = "Error al guardar la publicación. Inténtalo de nuevo.";
            }
        }
    }
}

// ════════════════════════════════════════════════════════════
//  POST: NUEVO COMENTARIO
// ════════════════════════════════════════════════════════════
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["action"]) && $_POST["action"] === "comentar") {
    $id_pub     = isset($_POST["id_publicacion"]) ? (int)$_POST["id_publicacion"] : 0;
    $texto      = trim($_POST["texto"] ?? '');
    $nombre_aut = trim($_POST["nombre_autor"] ?? '');

    if ($id_pub && strlen($texto) >= 2 && strlen($nombre_aut) >= 2) {
        $urgenteModel->addComentario($id_pub, $texto, $nombre_aut);
    }
    header("Location: /src/view/urgente.php#pub-" . $id_pub);
    exit();
}

// ════════════════════════════════════════════════════════════
//  FILTRO
// ════════════════════════════════════════════════════════════
$filtro = isset($_GET["tipo"]) && in_array($_GET["tipo"], ["PERDIDO", "ENCONTRADO"])
            ? $_GET["tipo"]
            : "";

// ════════════════════════════════════════════════════════════
//  CARGAR PUBLICACIONES Y COMENTARIOS
// ════════════════════════════════════════════════════════════
$pubs            = $urgenteModel->getPublicaciones($filtro);
$comentarios_map = $urgenteModel->getComentarios(array_column($pubs, 'id_publicacion'));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Urgente · Go Catch</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,900&family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/material-design-iconic-font/2.2.0/css/material-design-iconic-font.min.css">
    <link rel="stylesheet" href="/public/css/header.css">
    <link rel="stylesheet" href="/public/css/urgente.css">
</head>
<body>

<!-- HEADER -->
<header>
    <nav class="hBotones">
        <a class="hBoton" href="index.php#protectoras">PROTECTORAS</a>
        <a class="hBoton" href="">COLABORADORES</a>
    </nav>
    <nav id="header-izq">
        <a href="/src/view/index.php" target="_self">
            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 200" width="40" height="40" role="img" aria-label="Go Catch"><rect x="0" y="0" width="200" height="200" rx="36" ry="36" fill="#C97041"/><text x="110" y="148" font-family="'Fraunces', serif" font-weight="900" font-size="145" fill="#FFFFFF" text-anchor="middle">gc</text></svg>
        </a>
    </nav>
    <nav class="hBotones">
        <a class="hBoton" href="/src/view/urgente.php" style="color:#fff;">URGENTE</a>
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
    <a class="filtro-btn todos <?= $filtro === '' ? 'activo' : '' ?>" href="/src/view/urgente.php">
        <i class="zmdi zmdi-view-list"></i> Todos
    </a>
    <a class="filtro-btn perdidos <?= $filtro === 'PERDIDO' ? 'activo' : '' ?>" href="/src/view/urgente.php?tipo=PERDIDO">
        <i class="zmdi zmdi-alert-circle"></i> Perdidos
    </a>
    <a class="filtro-btn encontrados <?= $filtro === 'ENCONTRADO' ? 'activo' : '' ?>" href="/src/view/urgente.php?tipo=ENCONTRADO">
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
