<?php
session_start();
require "../sesion/conexion.php";
require_once "../model/ProtectoraModel.php";

// ── DETERMINAR MODO: edición propia vs vista pública ─────────
// Si llega ?id=X cualquiera puede ver; solo edita la protectora dueña.
// Sin ?id solo entra la protectora logueada.
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id_ver    = (int)$_GET['id'];
    $solo_vista = !isset($_SESSION['id'])
               || isset($_SESSION['user'])
               || (int)$_SESSION['id'] !== $id_ver;
} else {
    if (!isset($_SESSION['id'])) {
        header("Location: ../../public/login.html");
        exit();
    }
    if (isset($_SESSION['user'])) {
        header("Location: perfil.php");
        exit();
    }
    $id_ver     = (int)$_SESSION['id'];
    $solo_vista = false;
}

// ── CARGAR DATOS ─────────────────────────────────────────────
$protectoraModel = new ProtectoraModel($_conexion);
$datos = $protectoraModel->getById($id_ver);

if (!$datos) {
    header("Location: index.php");
    exit();
}

// ── ELIMINAR PERFIL (solo propietaria) ───────────────────────
if (!$solo_vista && $_SERVER["REQUEST_METHOD"] === "POST" && ($_POST['action'] ?? '') === 'eliminar') {
    $protectoraModel->delete($_SESSION["id"]);
    session_destroy();
    header("Location: index.php");
    exit();
}

// ── LÓGICA DE ACTUALIZACIÓN (solo propietaria) ───────────────
if (!$solo_vista && $_SERVER["REQUEST_METHOD"] == "POST") {

    $nombre_protectora = htmlspecialchars(trim($_POST["nombre_protectora"]));
    $email             = htmlspecialchars(trim($_POST["email"]));
    $telefono          = htmlspecialchars(trim($_POST["telefono"]));
    $ciudad            = htmlspecialchars(trim($_POST["ciudad"]));
    $localidad         = htmlspecialchars(trim($_POST["localidad"]));
    $direccion         = htmlspecialchars(trim($_POST["direccion"]));
    $pass_nueva        = trim($_POST["pass_nueva"]);
    $pass_nueva2       = trim($_POST["pass_nueva2"]);

    $campos  = [];
    $valores = [];
    $tipos   = "";

    if ($nombre_protectora != "") {
        $campos[]  = "nombre_protectora = ?";
        $valores[] = $nombre_protectora;
        $tipos    .= "s";
    }
    if ($email != "") {
        if (!preg_match("/^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/", $email)) {
            $err_pass = "El email no tiene un formato válido";
        } else {
            $campos[]  = "email = ?";
            $valores[] = $email;
            $tipos    .= "s";
        }
    }
    if ($telefono != "") {
        $campos[]  = "telefono = ?";
        $valores[] = $telefono;
        $tipos    .= "s";
    }
    if ($ciudad != "") {
        $campos[]  = "ciudad = ?";
        $valores[] = $ciudad;
        $tipos    .= "s";
    }
    if ($localidad != "") {
        $campos[]  = "localidad = ?";
        $valores[] = $localidad;
        $tipos    .= "s";
    }
    if ($direccion != "") {
        $campos[]  = "direccion = ?";
        $valores[] = $direccion;
        $tipos    .= "s";
    }
    if ($pass_nueva != "") {
        if (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/", $pass_nueva)) {
            $err_pass = "Mínimo 8 caracteres, una mayúscula, una minúscula y un número";
        } elseif ($pass_nueva != $pass_nueva2) {
            $err_pass = "Las contraseñas no coinciden";
        } else {
            $campos[]  = "contrasena = ?";
            $valores[] = password_hash($pass_nueva, PASSWORD_DEFAULT);
            $tipos    .= "s";
        }
    }

    if (!isset($err_pass) && !empty($campos)) {
        if ($protectoraModel->update($_SESSION["id"], $campos, $valores, $tipos)) {
            if ($nombre_protectora != "") $_SESSION["protectora"] = $nombre_protectora;
            if ($email != "")            $_SESSION["email"]       = $email;
            $ok = "Perfil actualizado correctamente";
            $datos = $protectoraModel->getById($_SESSION["id"]);
        } else {
            $err_db = "No se ha podido actualizar el perfil";
        }
    }
}

// Ciudades disponibles (igual que en registro.html)
$ciudades = [
    "almeria" => "Almería",
    "cadiz"   => "Cádiz",
    "cordoba" => "Córdoba",
    "granada" => "Granada",
    "huelva"  => "Huelva",
    "jaen"    => "Jaén",
    "malaga"  => "Málaga",
    "sevilla" => "Sevilla",
];

$es_adoptante = isset($_SESSION['id']) && isset($_SESSION['user']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Perfil · Go Catch</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,900&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/material-design-iconic-font/2.2.0/css/material-design-iconic-font.min.css">
    <link rel="stylesheet" href="../../public/css/header.css">
    <link rel="stylesheet" href="../../public/css/perfil.css">
    <link rel="stylesheet" href="../../public/css/perfilProtectora.css">
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
        <a class="hBoton" href="" target="_self">URGENTE</a>
        <a class="hBoton" href="../../public/registro.html" target="_self">REGÍSTRATE</a>
        <a href="" target="_self" id="boton-destacado">INICIA SESIÓN</a>
    </nav>
</header>

<div id="padre-nuestro">
    <div id="estructura">

        <!-- CABECERA CON LOGO/FOTO -->
        <div id="perfil-header">
            <div id="foto-perfil-container">
                <div id="foto-perfil">
                    <img src="<?= isset($datos["logo"]) && $datos["logo"] ? htmlspecialchars($datos["logo"]) : '../../img/profile/default/oficiales/1.jpg' ?>"
                         alt="Logo de la protectora" id="foto-img">
                </div>
                <?php if (!$solo_vista): ?>
                <label for="foto-input" id="foto-label">
                    <i class="zmdi zmdi-camera"></i>
                </label>
                <input type="file" name="foto" id="foto-input" accept="image/*" style="display:none">
                <?php endif; ?>
            </div>

            <h2 id="perfil-nombre"><?= htmlspecialchars($datos['nombre_protectora']) ?></h2>
            <p id="perfil-tipo">Protectora</p>
        </div>

        <!-- MENSAJES DE RESPUESTA -->
        <?php if (isset($ok)): ?>
            <div class="msg-ok"><?= $ok ?></div>
        <?php endif; ?>
        <?php if (isset($err_pass)): ?>
            <div class="msg-error"><?= $err_pass ?></div>
        <?php endif; ?>
        <?php if (isset($err_db)): ?>
            <div class="msg-error"><?= $err_db ?></div>
        <?php endif; ?>

        <!-- CONTENIDO: edición o vista según rol -->
        <div id="perfil-form-area">
        <?php if ($solo_vista): ?>

            <!-- ── VISTA PÚBLICA (solo lectura) ── -->
            <div class="info-publica">
                <?php if ($datos['email']): ?>
                <div class="info-fila">
                    <i class="zmdi zmdi-email"></i>
                    <span><?= htmlspecialchars($datos['email']) ?></span>
                </div>
                <?php endif; ?>
                <?php if ($datos['telefono']): ?>
                <div class="info-fila">
                    <i class="zmdi zmdi-phone"></i>
                    <span><?= htmlspecialchars($datos['telefono']) ?></span>
                </div>
                <?php endif; ?>
                <?php if ($datos['ciudad'] || $datos['localidad']): ?>
                <div class="info-fila">
                    <i class="zmdi zmdi-pin"></i>
                    <span><?= htmlspecialchars(implode(', ', array_filter([$datos['localidad'], $datos['ciudad']]))) ?></span>
                </div>
                <?php endif; ?>
                <?php if ($datos['direccion']): ?>
                <div class="info-fila">
                    <i class="zmdi zmdi-home"></i>
                    <span><?= htmlspecialchars($datos['direccion']) ?></span>
                </div>
                <?php endif; ?>
            </div>

            <?php if ($es_adoptante): ?>
            <div class="don-prot-cta">
                <div class="don-prot-cta-text">
                    <i class="zmdi zmdi-money-box"></i>
                    <div>
                        <p class="don-prot-titulo">Apoya a <?= htmlspecialchars($datos['nombre_protectora']) ?></p>
                        <p class="don-prot-sub">Tu donación ayuda a costear los cuidados de sus animales</p>
                    </div>
                </div>
                <button type="button" id="btn-donar-protectora"
                        data-id="<?= $id_ver ?>"
                        data-nombre="<?= htmlspecialchars($datos['nombre_protectora'], ENT_QUOTES) ?>">
                    <i class="zmdi zmdi-money"></i> Donar
                </button>
            </div>
            <?php endif; ?>

        <?php else: ?>

            <!-- ── FORMULARIO EDICIÓN (propietaria) ── -->
            <form action="perfilProtectora.php" method="POST" id="registro">

                <div class="form-wrapper">
                    <input type="text" name="nombre_protectora" class="form-control"
                           placeholder="<?= htmlspecialchars($datos['nombre_protectora']) ?>">
                    <i class="zmdi zmdi-shield-check"></i>
                </div>
                <div class="form-wrapper">
                    <input type="text" name="email" class="form-control"
                           placeholder="<?= htmlspecialchars($datos['email']) ?>">
                    <i class="zmdi zmdi-email"></i>
                </div>
                <div class="form-wrapper">
                    <input type="text" name="telefono" class="form-control"
                           placeholder="<?= $datos['telefono'] ? htmlspecialchars($datos['telefono']) : 'Teléfono' ?>">
                    <i class="zmdi zmdi-phone"></i>
                </div>

                <div class="form-grid">
                    <div class="form-wrapper">
                        <select name="ciudad" class="form-control">
                            <option value="">
                                <?= $datos['ciudad'] ? htmlspecialchars($ciudades[$datos['ciudad']] ?? $datos['ciudad']) : 'Ciudad' ?>
                            </option>
                            <?php foreach ($ciudades as $valor => $etiqueta): ?>
                                <option value="<?= $valor ?>"
                                    <?= ($datos['ciudad'] === $valor) ? 'selected' : '' ?>>
                                    <?= $etiqueta ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <i class="zmdi zmdi-caret-down"></i>
                    </div>
                    <div class="form-wrapper">
                        <input type="text" name="localidad" class="form-control"
                               placeholder="<?= $datos['localidad'] ? htmlspecialchars($datos['localidad']) : 'Localidad' ?>">
                        <i class="zmdi zmdi-map"></i>
                    </div>
                </div>

                <div class="form-wrapper">
                    <input type="text" name="direccion" class="form-control"
                           placeholder="<?= $datos['direccion'] ? htmlspecialchars($datos['direccion']) : 'Dirección' ?>">
                    <i class="zmdi zmdi-home"></i>
                </div>

                <p class="seccion-label">Cambiar contraseña</p>

                <div class="form-wrapper">
                    <input type="password" name="pass_nueva" class="form-control"
                           placeholder="Nueva contraseña (vacío para no cambiar)">
                    <i class="zmdi zmdi-lock"></i>
                </div>
                <div class="form-wrapper">
                    <input type="password" name="pass_nueva2" class="form-control"
                           placeholder="Repite la nueva contraseña">
                    <i class="zmdi zmdi-lock-outline"></i>
                </div>

                <button type="submit">GUARDAR CAMBIOS <i class="zmdi zmdi-check"></i></button>
            </form>

        <?php endif; ?>
        </div>

        <!-- VOLVER -->
        <div id="perfil-volver">
            <a href="index.php">← Volver al inicio</a>
        </div>

        <?php if (!$solo_vista): ?>
        <!-- ELIMINAR PERFIL -->
        <div id="perfil-eliminar">
            <button type="button" id="btn-eliminar-perfil">
                <i class="zmdi zmdi-delete"></i> Eliminar perfil
            </button>
        </div>
        <?php endif; ?>

    </div>
</div>

<?php if (!$solo_vista): ?>
<!-- MODAL CONFIRMACIÓN -->
<div id="modal-eliminar" class="modal-overlay">
    <div class="modal-box">
        <i class="zmdi zmdi-alert-circle modal-icono"></i>
        <p class="modal-titulo">¿Eliminar perfil?</p>
        <p class="modal-msg">Esta acción es irreversible. Desaparecerán todos los datos de la protectora y sus animales.</p>
        <div class="modal-acciones">
            <button type="button" id="modal-cancelar" class="modal-btn modal-btn-cancelar">Cancelar</button>
            <form method="POST" style="margin:0">
                <input type="hidden" name="action" value="eliminar">
                <button type="submit" class="modal-btn modal-btn-confirmar">Sí, eliminar</button>
            </form>
        </div>
    </div>
</div>


<script>
document.getElementById('btn-eliminar-perfil').addEventListener('click', function () {
    document.getElementById('modal-eliminar').classList.add('activo');
});
document.getElementById('modal-cancelar').addEventListener('click', function () {
    document.getElementById('modal-eliminar').classList.remove('activo');
});
document.getElementById('modal-eliminar').addEventListener('click', function (e) {
    if (e.target === this) this.classList.remove('activo');
});
</script>
<?php endif; ?>

<?php if ($es_adoptante): ?>
<!-- ── MODAL DONACIÓN A LA PROTECTORA ─────────────────── -->
<div id="don-prot-modal" class="don-prot-overlay" style="display:none" role="dialog" aria-modal="true">
    <div class="don-prot-modal">
        <div class="don-prot-header">
            <h3>Donar a la protectora</h3>
            <button id="don-prot-close" type="button" aria-label="Cerrar">
                <i class="zmdi zmdi-close"></i>
            </button>
        </div>
        <p id="don-prot-nombre-label" style="color:#EDA677;font-size:13px;margin-bottom:16px;font-weight:600"></p>

        <div class="don-prot-group">
            <label>Tu nombre <span style="color:#a8b8cc;font-weight:400">(opcional)</span></label>
            <input type="text" id="don-prot-input-nombre" placeholder="Anónimo">
        </div>
        <div class="don-prot-group">
            <label>Cantidad a donar (€)</label>
            <div class="don-prot-quick-row">
                <button type="button" class="don-prot-quick" data-v="5">5 €</button>
                <button type="button" class="don-prot-quick" data-v="10">10 €</button>
                <button type="button" class="don-prot-quick" data-v="25">25 €</button>
                <button type="button" class="don-prot-quick" data-v="50">50 €</button>
            </div>
            <input type="number" id="don-prot-cantidad" min="1" step="0.01" placeholder="Otra cantidad…">
        </div>
        <p class="don-prot-aviso">
            <i class="zmdi zmdi-info-outline"></i>
            La pasarela de pago estará disponible próximamente. Tu intención de donación quedará registrada.
        </p>
        <div class="don-prot-btns">
            <button type="button" id="don-prot-cancel">Cancelar</button>
            <button type="button" id="don-prot-submit">
                <i class="zmdi zmdi-money"></i> Confirmar donación
            </button>
        </div>
        <div id="don-prot-feedback" style="display:none;margin-top:12px;padding:10px 14px;border-radius:6px;font-size:13px"></div>
    </div>
</div>


<script>
(function () {
    const modal     = document.getElementById('don-prot-modal');
    const btnOpen   = document.getElementById('btn-donar-protectora');
    const labelNom  = document.getElementById('don-prot-nombre-label');
    const inputNom  = document.getElementById('don-prot-input-nombre');
    const inputCant = document.getElementById('don-prot-cantidad');
    const feedback  = document.getElementById('don-prot-feedback');
    const submit    = document.getElementById('don-prot-submit');
    let   protId    = null;

    btnOpen.addEventListener('click', () => {
        protId = btnOpen.dataset.id;
        labelNom.textContent = btnOpen.dataset.nombre;
        modal.style.display = 'flex';
        feedback.style.display = 'none';
        submit.disabled = false;
        inputNom.value = '';
        inputCant.value = '';
        document.querySelectorAll('.don-prot-quick').forEach(b => b.classList.remove('active'));
    });

    document.querySelectorAll('.don-prot-quick').forEach(btn => {
        btn.addEventListener('click', () => {
            inputCant.value = btn.dataset.v;
            document.querySelectorAll('.don-prot-quick').forEach(b => b.classList.remove('active'));
            btn.classList.add('active');
        });
    });

    function closeDonProt() { modal.style.display = 'none'; }
    document.getElementById('don-prot-close').addEventListener('click', closeDonProt);
    document.getElementById('don-prot-cancel').addEventListener('click', closeDonProt);
    modal.addEventListener('click', e => { if (e.target === modal) closeDonProt(); });

    submit.addEventListener('click', () => {
        const cant = parseFloat(inputCant.value);
        if (!cant || cant <= 0) {
            feedback.style.cssText = 'display:block;background:rgba(220,60,60,.12);color:#ffaaaa;padding:10px 14px;border-radius:6px;font-size:13px';
            feedback.textContent = 'Introduce una cantidad válida.';
            return;
        }
        submit.disabled = true;
        fetch('../controller/donacion-protectora.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: `id_protectora=${encodeURIComponent(protId)}&cantidad=${encodeURIComponent(cant)}&nombre=${encodeURIComponent(inputNom.value)}`
        })
        .then(r => r.json())
        .then(data => {
            feedback.style.display = 'block';
            feedback.style.padding = '10px 14px';
            feedback.style.borderRadius = '6px';
            feedback.style.fontSize = '13px';
            if (data.ok) {
                feedback.style.background = 'rgba(60,200,100,.12)';
                feedback.style.color = '#7dffb0';
                feedback.textContent = '¡Gracias! Tu intención de donación ha sido registrada. La pasarela de pago estará disponible próximamente.';
            } else {
                feedback.style.background = 'rgba(220,60,60,.12)';
                feedback.style.color = '#ffaaaa';
                feedback.textContent = data.error || 'Error al registrar la donación.';
                submit.disabled = false;
            }
        })
        .catch(() => {
            feedback.style.cssText = 'display:block;background:rgba(220,60,60,.12);color:#ffaaaa;padding:10px 14px;border-radius:6px;font-size:13px';
            feedback.textContent = 'Error de conexión.';
            submit.disabled = false;
        });
    });
})();
</script>
<?php endif; ?>

</body>
</html>