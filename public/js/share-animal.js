/* ══════════════════════════════════════════════════
   COMPARTIR ANIMAL — Modal + plantilla Stories (Diseño A2)
══════════════════════════════════════════════════ */
(function () {
    'use strict';

    // ── 1. Leer datos inyectados desde PHP ──
    const dataEl = document.getElementById('share-data');
    if (!dataEl) return;
    let SHARE = {};
    try { SHARE = JSON.parse(dataEl.textContent); }
    catch (e) { console.error('share-data inválido', e); return; }

    // ── 2. Elementos del DOM ──
    const modal      = document.getElementById('share-modal');
    const btnAbrir   = document.getElementById('btn-abrir-compartir');
    if (!modal || !btnAbrir) return;

    const cerrarBtns = modal.querySelectorAll('[data-cerrar-share]');
    const btnCerrar  = modal.querySelector('.share-modal-cerrar');

    const lnkWA      = document.getElementById('share-whatsapp');
    const lnkTW      = document.getElementById('share-twitter');
    const lnkTG      = document.getElementById('share-telegram');
    const lnkMail    = document.getElementById('share-email');
    const btnCopy    = document.getElementById('share-copiar');
    const btnStory   = document.getElementById('share-stories');

    const secStory   = document.getElementById('share-stories-section');
    const canvas     = document.getElementById('story-canvas');
    const loading    = document.getElementById('story-loading');
    const btnDescarg = document.getElementById('btn-stories-descargar');
    const btnVolver  = document.getElementById('btn-stories-volver');
    const toast      = document.getElementById('share-toast');

    // ── 3. Construir URLs y mensajes ──
    const URL_PUB = SHARE.url;
    const TITLE   = SHARE.title;
    const TXT_WA  = `Mira a ${SHARE.nombre}, busca un hogar 🐾\n${URL_PUB}`;
    const TXT_TW  = `${SHARE.nombre} busca un hogar para siempre 🐾 #adopciónanimal #GoCatch`;
    const TXT_TG  = `${SHARE.nombre} busca un hogar — entra en Go Catch:`;

    lnkWA.href   = `https://wa.me/?text=${encodeURIComponent(TXT_WA)}`;
    lnkTW.href   = `https://twitter.com/intent/tweet?text=${encodeURIComponent(TXT_TW)}&url=${encodeURIComponent(URL_PUB)}`;
    lnkTG.href   = `https://t.me/share/url?url=${encodeURIComponent(URL_PUB)}&text=${encodeURIComponent(TXT_TG)}`;
    lnkMail.href = `mailto:?subject=${encodeURIComponent(TITLE)}&body=${encodeURIComponent(TXT_WA)}`;

    // ── 4. Abrir/cerrar modal ──
    function abrir() {
        modal.classList.add('abierto');
        modal.setAttribute('aria-hidden', 'false');
        document.body.style.overflow = 'hidden';
    }
    function cerrar() {
        modal.classList.remove('abierto');
        modal.setAttribute('aria-hidden', 'true');
        document.body.style.overflow = '';
        // resetear sección stories si estaba abierta
        secStory.hidden = true;
    }
    btnAbrir.addEventListener('click', () => {
        // Si el navegador soporta Web Share API y es móvil, usarla directamente
        if (navigator.share && /Mobi|Android|iPhone/i.test(navigator.userAgent)) {
            navigator.share({
                title: TITLE,
                text:  `${SHARE.nombre} busca un hogar`,
                url:   URL_PUB
            }).catch(() => abrir()); // si cancelan o falla, abrir modal
        } else {
            abrir();
        }
    });
    cerrarBtns.forEach(b => b.addEventListener('click', cerrar));
    if (btnCerrar) btnCerrar.addEventListener('click', cerrar);
    document.addEventListener('keydown', e => {
        if (e.key === 'Escape' && modal.classList.contains('abierto')) cerrar();
    });

    // ── 5. Copiar enlace ──
    btnCopy.addEventListener('click', async () => {
        try {
            await navigator.clipboard.writeText(URL_PUB);
            mostrarToast('Enlace copiado');
        } catch (e) {
            // fallback
            const ta = document.createElement('textarea');
            ta.value = URL_PUB;
            document.body.appendChild(ta);
            ta.select();
            document.execCommand('copy');
            document.body.removeChild(ta);
            mostrarToast('Enlace copiado');
        }
    });

    function mostrarToast(msg) {
        toast.textContent = msg;
        toast.classList.add('visible');
        setTimeout(() => toast.classList.remove('visible'), 2200);
    }

    // ── 6. Stories: intentar compartir directo (Web Share API con file)
    //       y, si no se puede, mostrar la sección con descarga manual.
    let storyGenerada = false;

    /** Detecta si el navegador puede compartir un PNG generado al vuelo.
     *  Lo soportan iOS Safari 16.4+, Android Chrome y la mayoría de móviles
     *  modernos. NO lo soporta el escritorio (Chrome/Firefox) en general. */
    function puedeCompartirImagen() {
        if (!navigator.canShare) return false;
        try {
            const probe = new File([new Blob()], 'p.png', { type: 'image/png' });
            return navigator.canShare({ files: [probe] });
        } catch { return false; }
    }

    btnStory.addEventListener('click', async () => {
        // Generamos el canvas siempre (también para fallback desktop)
        secStory.hidden = false;
        secStory.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        if (!storyGenerada) {
            await generarStoryCanvas();
            storyGenerada = true;
        }

        // Si el navegador permite compartir archivos directamente, lo intentamos
        if (puedeCompartirImagen()) {
            try {
                const blob = await new Promise(res =>
                    canvas.toBlob(res, 'image/png', 0.95));
                if (!blob) throw new Error('Canvas vacío');
                const file = new File(
                    [blob],
                    `gocatch-${SHARE.nombre.toLowerCase().replace(/\s+/g, '-')}.png`,
                    { type: 'image/png' }
                );
                await navigator.share({
                    files: [file],
                    title: SHARE.title,
                    text:  `${SHARE.nombre} busca un hogar 🐾  ${URL_PUB}`
                });
                // Compartido con éxito → cerrar todo el modal
                cerrar();
                return;
            } catch (err) {
                // El usuario canceló o falló: dejamos visible la sección
                // de descarga como fallback silencioso
                if (err && err.name !== 'AbortError') {
                    console.warn('share() falló:', err);
                }
            }
        }
        // Fallback: ya está visible la sección de descarga
    });
    btnVolver.addEventListener('click', () => {
        secStory.hidden = true;
    });

    /* ══════════════════════════════════════════════════
       GENERAR PLANTILLA DE STORIES (Diseño A2)
       Replica el render Python: banda navy arriba,
       foto con corte diagonal, panel navy abajo con
       Luna + meta + QR
    ══════════════════════════════════════════════════ */
    async function generarStoryCanvas() {
        loading.style.display = 'flex';
        const ctx = canvas.getContext('2d');
        const W = canvas.width;   // 1080
        const H = canvas.height;  // 1920

        // Asegurarse de que las fuentes están cargadas antes de dibujar
        // (sin esto Canvas usaría serif por defecto en vez de Fraunces)
        try {
            await Promise.all([
                document.fonts.load('900 150px Fraunces'),
                document.fonts.load('900 56px Fraunces'),
                document.fonts.load('bold 24px Poppins'),
                document.fonts.load('400 32px Poppins'),
            ]);
            await document.fonts.ready;
        } catch (e) { /* seguir aunque falle */ }

        // Paleta
        const NAVY_DARK   = '#0D2D51';
        const NAVY_MID    = '#124076';
        const TERRACOTA   = '#CA7842';
        const TERRACOTA_S = '#EDA677';
        const WHITE       = '#FFFFFF';
        const SAFE_TOP    = 280;
        const HEADER_H    = 420;

        // ── 1. Fondo navy (cubre todo, será header + panel) ──
        ctx.fillStyle = NAVY_DARK;
        ctx.fillRect(0, 0, W, H);

        // ── 2. Header (logo + go catch + EN ADOPCIÓN) ──
        const header_y = SAFE_TOP + 30;  // y=310

        // Logo gc cuadrado terracota
        const logoSize = 80;
        drawRoundedRect(ctx, 60, header_y, logoSize, logoSize, logoSize * 0.18, TERRACOTA);
        ctx.fillStyle = WHITE;
        ctx.font = `900 ${logoSize * 0.78}px Fraunces, serif`;
        ctx.textBaseline = 'middle';
        ctx.fillText('gc', 60 + 10, header_y + logoSize / 2 + 4);

        // "go catch"
        ctx.fillStyle = WHITE;
        ctx.font = '900 56px Fraunces, serif';
        ctx.textBaseline = 'top';
        ctx.fillText('go catch', 60 + logoSize + 20, header_y + 16);

        // Badge EN ADOPCIÓN
        const badgeTxt = SHARE.estado === 'DISPONIBLE' ? 'EN ADOPCIÓN' : SHARE.estado;
        ctx.font = 'bold 24px Poppins, sans-serif';
        const badgePadX = 28;
        const badgeW = ctx.measureText(badgeTxt).width + badgePadX * 2;
        const badgeH = 56;
        const badgeX = W - badgeW - 60;
        const badgeY = header_y + (logoSize - badgeH) / 2;
        drawRoundedRect(ctx, badgeX, badgeY, badgeW, badgeH, badgeH / 2, TERRACOTA);
        ctx.fillStyle = WHITE;
        ctx.textBaseline = 'middle';
        ctx.fillText(badgeTxt, badgeX + badgePadX, badgeY + badgeH / 2 + 1);

        // Línea decorativa terracota fina justo bajo el header
        ctx.fillStyle = TERRACOTA;
        ctx.fillRect(0, HEADER_H - 4, W, 4);

        // ── 3. Foto con corte diagonal ──
        const photo_y_start    = HEADER_H;       // 420
        const photo_y_end_l    = 1230;
        const photo_y_end_r    = 1330;
        const photo_h          = photo_y_end_r - photo_y_start; // 910

        try {
            const imgPet = await cargarImagen(SHARE.foto);
            // Calcular cover
            const srcRatio = imgPet.width / imgPet.height;
            const tgtRatio = W / photo_h;
            let sx, sy, sw, sh;
            if (srcRatio > tgtRatio) {
                sh = imgPet.height;
                sw = imgPet.height * tgtRatio;
                sx = (imgPet.width - sw) / 2;
                sy = 0;
            } else {
                sw = imgPet.width;
                sh = imgPet.width / tgtRatio;
                sx = 0;
                sy = Math.max(0, (imgPet.height - sh) / 3); // ligeramente hacia arriba
            }
            // Recortar al polígono diagonal
            ctx.save();
            ctx.beginPath();
            ctx.moveTo(0, photo_y_start);
            ctx.lineTo(W, photo_y_start);
            ctx.lineTo(W, photo_y_end_r);
            ctx.lineTo(0, photo_y_end_l);
            ctx.closePath();
            ctx.clip();
            ctx.drawImage(imgPet, sx, sy, sw, sh, 0, photo_y_start, W, photo_h);
            ctx.restore();
        } catch (e) {
            // Si falla la imagen, dejar fondo navy en esa zona
            console.warn('No se pudo cargar la imagen del animal:', e);
        }

        // ── 4. Línea acento terracota siguiendo la diagonal ──
        const offset = 14;
        const line_thick = 6;
        ctx.fillStyle = 'rgba(202, 120, 66, 0.86)';
        ctx.beginPath();
        ctx.moveTo(0, photo_y_end_l + offset);
        ctx.lineTo(W, photo_y_end_r + offset);
        ctx.lineTo(W, photo_y_end_r + offset + line_thick);
        ctx.lineTo(0, photo_y_end_l + offset + line_thick);
        ctx.closePath();
        ctx.fill();

        // ── 5. Contenido del panel navy ──
        const content_y0 = 1380;

        // Nombre grande (Luna o el que sea)
        ctx.fillStyle = WHITE;
        ctx.font = '900 150px Fraunces, serif';
        ctx.textBaseline = 'top';
        ctx.fillText(SHARE.nombre, 60, content_y0);

        // Línea decorativa terracota bajo el nombre
        ctx.fillStyle = TERRACOTA;
        ctx.fillRect(60, content_y0 + 165, 140, 5);

        // Meta: edad · ciudad · protectora
        ctx.font = '400 32px Poppins, sans-serif';
        const partes = [];
        if (SHARE.edad) partes.push({ t: SHARE.edad, c: WHITE });
        if (SHARE.ciudad) {
            if (partes.length) partes.push({ t: '  ·  ', c: TERRACOTA_S });
            partes.push({ t: SHARE.ciudad, c: WHITE });
        }
        if (SHARE.protectora) {
            if (partes.length) partes.push({ t: '  ·  ', c: TERRACOTA_S });
            partes.push({ t: SHARE.protectora, c: WHITE });
        }
        let cur_x = 60;
        const meta_y = content_y0 + 198;
        partes.forEach(p => {
            ctx.fillStyle = p.c;
            ctx.fillText(p.t, cur_x, meta_y);
            cur_x += ctx.measureText(p.t).width;
        });

        // Subtexto
        ctx.fillStyle = '#DCDCDC';
        ctx.font = '400 26px Poppins, sans-serif';
        ctx.fillText('Busca un hogar para siempre', 60, meta_y + 56);

        // ── 6. Tarjeta QR a la derecha ──
        try {
            const qrImg = await generarQR(URL_PUB, 200);
            const pad = 18;
            const cw  = 200 + pad * 2;
            const ch  = 200 + pad * 2 + 40;
            const cx  = W - cw - 60;
            const cy  = content_y0 + 10;
            drawRoundedRect(ctx, cx, cy, cw, ch, 16, WHITE);
            ctx.drawImage(qrImg, cx + pad, cy + pad, 200, 200);

            ctx.fillStyle = NAVY_DARK;
            ctx.font = 'bold 22px Poppins, sans-serif';
            const lbl = 'Escanéame';
            const lw  = ctx.measureText(lbl).width;
            ctx.fillText(lbl, cx + (cw - lw) / 2, cy + pad + 200 + 8);

            ctx.fillStyle = TERRACOTA_S;
            ctx.font = '400 22px Poppins, sans-serif';
            const lbl2 = `Conoce a ${SHARE.nombre}`;
            const lw2  = ctx.measureText(lbl2).width;
            ctx.fillText(lbl2, cx + (cw - lw2) / 2, cy + ch + 16);
        } catch (e) {
            console.warn('No se pudo generar QR:', e);
        }

        // Preparar URL de descarga
        canvas.toBlob(blob => {
            if (!blob) return;
            const url = URL.createObjectURL(blob);
            btnDescarg.href = url;
        }, 'image/png');

        loading.style.display = 'none';
    }

    /* ── Helpers ── */
    function drawRoundedRect(ctx, x, y, w, h, r, fill) {
        ctx.beginPath();
        ctx.moveTo(x + r, y);
        ctx.lineTo(x + w - r, y);
        ctx.quadraticCurveTo(x + w, y, x + w, y + r);
        ctx.lineTo(x + w, y + h - r);
        ctx.quadraticCurveTo(x + w, y + h, x + w - r, y + h);
        ctx.lineTo(x + r, y + h);
        ctx.quadraticCurveTo(x, y + h, x, y + h - r);
        ctx.lineTo(x, y + r);
        ctx.quadraticCurveTo(x, y, x + r, y);
        ctx.closePath();
        if (fill) { ctx.fillStyle = fill; ctx.fill(); }
    }

    function cargarImagen(src) {
        return new Promise((resolve, reject) => {
            const img = new Image();
            img.crossOrigin = 'anonymous';
            img.onload  = () => resolve(img);
            img.onerror = reject;
            img.src = src;
        });
    }

    /* ── QR mediante librería externa cargada bajo demanda ── */
    let qrLibPromise = null;
    function cargarLibQR() {
        if (qrLibPromise) return qrLibPromise;
        qrLibPromise = new Promise((resolve, reject) => {
            if (window.QRCode) return resolve(window.QRCode);
            const s = document.createElement('script');
            s.src = 'https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js';
            s.onload  = () => resolve(window.QRCode);
            s.onerror = reject;
            document.head.appendChild(s);
        });
        return qrLibPromise;
    }

    async function generarQR(url, size) {
        const QR = await cargarLibQR();
        const tmp = document.createElement('canvas');
        await QR.toCanvas(tmp, url, {
            errorCorrectionLevel: 'M',
            margin: 1,
            width: size,
            color: { dark: '#000000', light: '#FFFFFF' }
        });
        return tmp;
    }
})();
