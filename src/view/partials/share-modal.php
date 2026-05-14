<?php
/**
 * Partial: Modal de compartir animal
 * ──────────────────────────────────
 * Espera la variable $share definida en el contexto que lo incluye:
 *   $share = share_build_data($animal, $fotos, $edad_txt);
 *   include __DIR__ . '/partials/share-modal.php';
 *
 * Renderiza:
 *   - El modal con las 6 redes (WhatsApp, Twitter, Telegram, Email,
 *     Copiar enlace, Historia IG)
 *   - La sección Stories con canvas y descarga
 *   - El <script id="share-data"> con los datos para share-animal.js
 *
 * Requiere que el archivo padre haga:
 *   require_once __DIR__ . '/../helpers/share.php';
 */

if (!isset($share) || !is_array($share)) {
    // Sin datos no se puede renderizar el modal
    return;
}
?>
<!-- ══ MODAL COMPARTIR ══ -->
<div class="share-modal" id="share-modal" aria-hidden="true">
    <div class="share-modal-backdrop" data-cerrar-share></div>

    <div class="share-modal-dialog" role="dialog" aria-labelledby="share-titulo">
        <button class="share-modal-cerrar" data-cerrar-share aria-label="Cerrar">
            <i class="zmdi zmdi-close"></i>
        </button>

        <h2 class="share-modal-titulo" id="share-titulo">
            Ayuda a <?= htmlspecialchars($share['nombre']) ?> a encontrar un hogar
        </h2>
        <p class="share-modal-sub">Comparte su perfil donde más puedas</p>

        <!-- Opciones de red -->
        <div class="share-redes">
            <a class="share-red whatsapp" id="share-whatsapp"
               href="#" target="_blank" rel="noopener">
                <i class="zmdi zmdi-whatsapp"></i>
                <span>WhatsApp</span>
            </a>
            <a class="share-red twitter" id="share-twitter"
               href="#" target="_blank" rel="noopener">
                <i class="zmdi zmdi-twitter"></i>
                <span>Twitter / X</span>
            </a>
            <a class="share-red telegram" id="share-telegram"
               href="#" target="_blank" rel="noopener">
                <i class="zmdi zmdi-mail-send"></i>
                <span>Telegram</span>
            </a>
            <a class="share-red email" id="share-email" href="#">
                <i class="zmdi zmdi-email"></i>
                <span>Email</span>
            </a>
            <button class="share-red copiar" id="share-copiar" type="button">
                <i class="zmdi zmdi-link"></i>
                <span>Copiar enlace</span>
            </button>
            <button class="share-red stories" id="share-stories" type="button">
                <i class="zmdi zmdi-instagram"></i>
                <span>Historia IG</span>
            </button>
        </div>

        <!-- Sección Stories: solo se muestra como fallback si no se puede
             compartir directamente con navigator.share() -->
        <div class="share-stories-section" id="share-stories-section" hidden>
            <div class="share-stories-preview-wrap">
                <canvas id="story-canvas" width="1080" height="1920"
                        class="share-stories-preview"></canvas>
                <div class="share-stories-loading" id="story-loading">
                    Generando plantilla…
                </div>
            </div>

            <div class="share-stories-acciones">
                <a class="btn-stories-descargar" id="btn-stories-descargar"
                   download="gocatch-<?= htmlspecialchars(strtolower($share['nombre'])) ?>.png">
                    <i class="zmdi zmdi-download"></i>
                    Descargar imagen
                </a>
                <button class="btn-stories-volver" type="button" id="btn-stories-volver">
                    ← Volver
                </button>
            </div>

            <p class="share-stories-ayuda">
                1. Descarga la imagen<br>
                2. Abre Instagram → nueva historia → selecciona la imagen<br>
                3. Pega el enlace o haz que escaneen el QR
            </p>
        </div>

        <!-- Toast aviso copiado -->
        <div class="share-toast" id="share-toast">Enlace copiado</div>
    </div>
</div>

<!-- Datos para share-animal.js -->
<script id="share-data" type="application/json"><?= share_to_json($share) ?></script>
