(function () {
    const pageDataNode = document.getElementById('index-page-data');
    if (!pageDataNode) return;

    const pageData = JSON.parse(pageDataNode.textContent || '{}');
    const PROTECTORAS = Array.isArray(pageData.protectoras) ? pageData.protectoras : [];
    const TIENE_DB_ANIMALES = Boolean(pageData.tieneDbAnimales);

    const CITY_COORDS = {
        // ── ALMERÍA ──────────────────────────────────────────────
        "almeria": [36.8340, -2.4637], "almería": [36.8340, -2.4637],
        "roquetas de mar": [36.7642, -2.6148], "el ejido": [36.7762, -2.8127],
        "nijar": [36.9672, -2.2053], "níjar": [36.9672, -2.2053],
        "huercal overa": [37.3879, -1.9404], "huércal-overa": [37.3879, -1.9404],
        "vera": [37.2486, -1.8620], "adra": [36.7478, -3.0218],
        "berja": [36.8455, -2.9525], "cuevas del almanzora": [37.2986, -1.8907],
        "carboneras": [36.9997, -1.8942], "mojacar": [37.1424, -1.8469],
        "mojácar": [37.1424, -1.8469], "baza": [37.4944, -2.7700],
        "guadix": [37.2985, -3.1352], "laujar de andarax": [36.9994, -2.8972],

        // ── CÁDIZ ────────────────────────────────────────────────
        "cadiz": [36.5271, -6.2886], "cádiz": [36.5271, -6.2886],
        "jerez de la frontera": [36.6850, -6.1261], "jerez": [36.6850, -6.1261],
        "algeciras": [36.1408, -5.4534], "san fernando": [36.4770, -6.1993],
        "el puerto de santa maria": [36.5942, -6.2327], "el puerto": [36.5942, -6.2327],
        "chiclana de la frontera": [36.4194, -6.1474], "chiclana": [36.4194, -6.1474],
        "sanlucar de barrameda": [36.7766, -6.3529], "sanlúcar": [36.7766, -6.3529],
        "la linea de la concepcion": [36.1673, -5.3497], "la línea": [36.1673, -5.3497],
        "rota": [36.6246, -6.3598], "barbate": [36.1919, -5.9224],
        "tarifa": [36.0138, -5.6046], "vejer de la frontera": [36.2503, -5.9702],
        "conil de la frontera": [36.2779, -6.0887], "arcos de la frontera": [36.7481, -5.8138],
        "ubrique": [36.6813, -5.4508], "medina sidonia": [36.4569, -5.9278],
        "jimena de la frontera": [36.4355, -5.4536], "olvera": [36.9341, -5.2644],
        "zahara de los atunes": [36.1350, -5.8514], "los barrios": [36.1823, -5.4977],
        "san roque": [36.2108, -5.3845], "puerto real": [36.5262, -6.1917],

        // ── CÓRDOBA ──────────────────────────────────────────────
        "cordoba": [37.8882, -4.7794], "córdoba": [37.8882, -4.7794],
        "lucena": [37.4089, -4.4856], "pozoblanco": [38.3786, -4.8497],
        "cabra": [37.5130, -4.4411], "montilla": [37.5845, -4.6379],
        "puente genil": [37.3906, -4.7675], "priego de cordoba": [37.4362, -4.1964],
        "priego de córdoba": [37.4362, -4.1964], "baena": [37.6155, -4.3228],
        "palma del rio": [37.7041, -5.2794], "palma del río": [37.7041, -5.2794],
        "aguilar de la frontera": [37.5122, -4.6564], "rute": [37.3290, -4.3698],
        "montoro": [38.0152, -4.3779], "andújar": [38.0395, -4.0504],
        "hinojosa del duque": [38.4951, -5.1445], "peñarroya-pueblonuevo": [38.2993, -5.2645],
        "belalcazar": [38.5788, -5.1660], "belalcázar": [38.5788, -5.1660],

        // ── GRANADA ──────────────────────────────────────────────
        "granada": [37.1773, -3.5986],
        "motril": [36.7453, -3.5193], "armilla": [37.1341, -3.6115],
        "almunecar": [36.7340, -3.6930], "almuñécar": [36.7340, -3.6930],
        "loja": [37.1692, -4.1497], "guadix": [37.2985, -3.1352],
        "baza": [37.4944, -2.7700], "huescar": [37.8110, -2.5420],
        "huéscar": [37.8110, -2.5420], "santa fe": [37.1860, -3.7140],
        "maracena": [37.2080, -3.6310], "ogijares": [37.1162, -3.6073],
        "pinos puente": [37.2446, -3.7553], "salobrena": [36.7416, -3.5856],
        "salobreña": [36.7416, -3.5856], "orgiva": [36.8985, -3.4270],
        "órgiva": [36.8985, -3.4270], "montefrio": [37.3228, -4.0104],
        "montefrío": [37.3228, -4.0104], "alhama de granada": [37.0004, -3.9869],
        "iznalloz": [37.3890, -3.5313], "purchena": [37.3564, -2.3640],

        // ── HUELVA ───────────────────────────────────────────────
        "huelva": [37.2614, -6.9447],
        "almonte": [37.2685, -6.5167], "lepe": [37.2568, -7.2038],
        "moguer": [37.2722, -6.8403], "cartaya": [37.2767, -7.1524],
        "ayamonte": [37.2132, -7.4002], "isla cristina": [37.1987, -7.3187],
        "punta umbria": [37.1770, -6.9619], "punta umbría": [37.1770, -6.9619],
        "aracena": [37.8878, -6.5470], "valverde del camino": [37.5703, -6.7534],
        "nerva": [37.6946, -6.5424], "bollullos par del condado": [37.3316, -6.5333],
        "la palma del condado": [37.3900, -6.5461], "beas": [37.3900, -6.9278],
        "minas de riotinto": [37.6946, -6.5868], "zalamea la real": [37.6849, -6.6640],

        // ── JAÉN ─────────────────────────────────────────────────
        "jaen": [37.7796, -3.7849], "jaén": [37.7796, -3.7849],
        "linares": [38.0922, -3.6347], "ubeda": [38.0131, -3.3700],
        "úbeda": [38.0131, -3.3700], "baeza": [37.9953, -3.4692],
        "andujar": [38.0395, -4.0504], "andújar": [38.0395, -4.0504],
        "martos": [37.7215, -3.9703], "alcala la real": [37.4600, -3.9226],
        "alcalá la real": [37.4600, -3.9226], "villacarrillo": [38.1167, -3.0830],
        "mancha real": [37.7921, -3.6101], "jodar": [37.8354, -3.3493],
        "jódar": [37.8354, -3.3493], "quesada": [37.8407, -3.0667],
        "cazorla": [37.9197, -3.0060], "la carolina": [38.2780, -3.6125],
        "guarroman": [38.1737, -3.6904], "guarromán": [38.1737, -3.6904],
        "beas de segura": [38.2556, -2.8878],

        // ── MÁLAGA ───────────────────────────────────────────────
        "malaga": [36.7213, -4.4214], "málaga": [36.7213, -4.4214],
        "marbella": [36.5101, -4.8825], "fuengirola": [36.5403, -4.6254],
        "velez-malaga": [36.7808, -4.0997], "vélez-málaga": [36.7808, -4.0997],
        "mijas": [36.5972, -4.6378], "torremolinos": [36.6225, -4.4994],
        "benalmadena": [36.5997, -4.5188], "benalmádena": [36.5997, -4.5188],
        "estepona": [36.4278, -5.1462], "antequera": [37.0195, -4.5620],
        "ronda": [36.7452, -5.1659], "coin": [36.6596, -4.7612],
        "coín": [36.6596, -4.7612], "alhaurin de la torre": [36.6627, -4.5529],
        "alhaurín de la torre": [36.6627, -4.5529], "alhaurin el grande": [36.6400, -4.6912],
        "alhaurín el grande": [36.6400, -4.6912], "nerja": [36.7432, -3.8744],
        "torrox": [36.7604, -3.9514], "torre del mar": [36.7339, -4.0939],
        "cártama": [36.7155, -4.6386], "cartama": [36.7155, -4.6386],
        "archidona": [37.0988, -4.3963], "campillos": [37.0449, -4.8581],
        "colmenar": [36.9100, -4.3340], "periana": [36.9380, -4.1902],
        "casares": [36.4409, -5.2758], "manilva": [36.3762, -5.2412],
        "la linea": [36.1673, -5.3497], "san pedro de alcantara": [36.4884, -4.9990],
        "nueva andalucia": [36.5001, -4.9339], "puerto banus": [36.4865, -4.9567],

        // ── SEVILLA ──────────────────────────────────────────────
        "sevilla": [37.3891, -5.9845],
        "dos hermanas": [37.2829, -5.9218], "alcala de guadaira": [37.3384, -5.8426],
        "alcalá de guadaíra": [37.3384, -5.8426], "utrera": [37.1844, -5.7777],
        "mairena del aljarafe": [37.3509, -6.0619], "san juan de aznalfarache": [37.3588, -6.0211],
        "bormujos": [37.3619, -6.0776], "tomares": [37.3726, -6.0546],
        "coria del rio": [37.2945, -6.0561], "coria del río": [37.2945, -6.0561],
        "la rinconada": [37.4820, -5.9806], "carmona": [37.4710, -5.6452],
        "ecija": [37.5419, -5.0825], "écija": [37.5419, -5.0825],
        "marchena": [37.3342, -5.3895], "osuna": [37.2345, -5.1078],
        "morón de la frontera": [37.1267, -5.4549], "estepa": [37.2918, -4.8778],
        "lebrija": [36.9176, -6.0780], "las cabezas de san juan": [36.9882, -5.9394],
        "montellano": [37.0001, -5.5631], "gilena": [37.2091, -4.9526],
        "lora del rio": [37.6546, -5.5231], "lora del río": [37.6546, -5.5231],
        "peñaflor": [37.7109, -5.3582], "constantina": [37.8744, -5.6078],
        "cazalla de la sierra": [37.9344, -5.7588], "aznalcazar": [37.2593, -6.2700],
        "aznalcóllar": [37.5194, -6.2643], "pilas": [37.3008, -6.2979],
        "sanlúcar la mayor": [37.3870, -6.2005], "bollullos de la mitacion": [37.3338, -6.1427],
    };

    function normalizePlace(value) {
        return (value || "")
            .toString()
            .trim()
            .toLowerCase()
            .normalize("NFD")
            .replace(/[\u0300-\u036f]/g, "");
    }

    function escapeHtml(value) {
        return (value || "")
            .toString()
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#39;");
    }

    function initMap() {
        if (typeof L === "undefined") return;

        const mapElement = document.getElementById("mapa");
        const locateButton = document.getElementById("btn-localizar");
        if (!mapElement || !locateButton) return;

        const map = L.map("mapa", { zoomControl: true }).setView([40.4, -3.7], 6);

        L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png", {
            attribution: '&copy; <a href="https://openstreetmap.org">OpenStreetMap</a>',
            maxZoom: 18
        }).addTo(map);

        const pawIcon = L.divIcon({
            html: `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80 80"
                        width="38" height="38" style="filter:drop-shadow(0 2px 4px rgba(0,0,0,0.4))">
                <ellipse cx="40" cy="54" rx="18" ry="15" fill="#CA7842"/>
                <ellipse cx="20" cy="36" rx="9" ry="11" fill="#CA7842"/>
                <ellipse cx="34" cy="27" rx="9" ry="11" fill="#CA7842"/>
                <ellipse cx="50" cy="27" rx="9" ry="11" fill="#CA7842"/>
                <ellipse cx="64" cy="36" rx="9" ry="11" fill="#CA7842"/>
            </svg>`,
            className: "paw-marker",
            iconSize: [38, 38],
            iconAnchor: [19, 38],
            popupAnchor: [0, -42]
        });

        let userCircle = null;

        function resolveCoords(protectora) {
            const ciudad = normalizePlace(protectora.ciudad);
            const localidad = normalizePlace(protectora.localidad);
            return CITY_COORDS[localidad] || CITY_COORDS[ciudad] || null;
        }

        function buildGeoQuery(protectora) {
            return [protectora.direccion, protectora.localidad, protectora.ciudad, "Espana"]
                .filter(Boolean)
                .join(", ");
        }

        function addMarkerOffset(coords, index) {
            if (!index) return coords;
            const angle = (index % 8) * (Math.PI / 4);
            const ring = Math.floor(index / 8) + 1;
            const latOffset = Math.sin(angle) * 0.01 * ring;
            const lngOffset = Math.cos(angle) * 0.01 * ring;
            return [coords[0] + latOffset, coords[1] + lngOffset];
        }

        function sleep(ms) {
            return new Promise(resolve => setTimeout(resolve, ms));
        }

        async function geocodeQuery(query) {
            if (!query) return null;
            try {
                const response = await fetch(
                    `https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&countrycodes=es&limit=1`,
                    { headers: { "Accept-Language": "es" } }
                );
                if (!response.ok) return null;
                const data = await response.json();
                if (!Array.isArray(data) || !data.length) return null;
                return [parseFloat(data[0].lat), parseFloat(data[0].lon)];
            } catch {
                return null;
            }
        }

        async function resolveAllCoords() {
            const resolved = [];
            for (const protectora of PROTECTORAS) {
                // 1. Intentar diccionario local (sin petición de red)
                let coords = resolveCoords(protectora);

                if (!coords) {
                    // 2. Geocodificar con dirección + localidad + ciudad
                    const queryCompleta = buildGeoQuery(protectora);
                    coords = await geocodeQuery(queryCompleta);
                    await sleep(1100); // respetar rate limit Nominatim (1 req/s)
                }

                if (!coords && protectora.ciudad) {
                    // 3. Fallback: solo ciudad/provincia
                    coords = await geocodeQuery(protectora.ciudad + ", España");
                    await sleep(1100);
                }

                if (coords) {
                    resolved.push({ protectora, coords });
                }
            }
            return resolved;
        }

        function buildProtectoraUrl(protectora) {
            return `/src/view/perfilProtectora.php?id=${encodeURIComponent(protectora.id_protectora)}`;
        }

        function buildPopup(protectora) {
            const lines = [`<strong>${escapeHtml(protectora.nombre_protectora)}</strong>`];

            if (protectora.direccion) lines.push(escapeHtml(protectora.direccion));

            const ubicacion = [protectora.localidad, protectora.ciudad]
                .filter(Boolean)
                .map(escapeHtml)
                .join(", ");

            if (ubicacion) lines.push(ubicacion);
            if (protectora.telefono) lines.push(`Tel. ${escapeHtml(protectora.telefono)}`);

            lines.push(`<a href="${buildProtectoraUrl(protectora)}">Ver protectora</a>`);

            return `<div class="map-popup">${lines.join("<br>")}</div>`;
        }

        function clearDynamicLayers() {
            map.eachLayer((layer) => {
                if (!(layer instanceof L.TileLayer)) {
                    map.removeLayer(layer);
                }
            });
        }

        let markersRendered = false;

        async function renderStableMarkers() {
            if (markersRendered) return;
            markersRendered = true;

            clearDynamicLayers();

            const markerCoords = [];
            const overlappingMarkers = new Map();
            const protectorasConCoords = await resolveAllCoords();

            protectorasConCoords.forEach(({ protectora, coords }) => {
                const key = `${coords[0].toFixed(4)},${coords[1].toFixed(4)}`;
                const overlapIndex = overlappingMarkers.get(key) || 0;
                overlappingMarkers.set(key, overlapIndex + 1);

                const finalCoords = addMarkerOffset(coords, overlapIndex);
                markerCoords.push(finalCoords);

                const marker = L.marker(finalCoords, { icon: pawIcon }).addTo(map);
                marker.bindPopup(buildPopup(protectora));
                marker.on("click", () => {
                    window.location.href = buildProtectoraUrl(protectora);
                });
            });

            if (userCircle) {
                userCircle.addTo(map);
            }

            if (markerCoords.length === 1) {
                map.setView(markerCoords[0], 11);
            } else if (markerCoords.length > 1) {
                map.fitBounds(L.latLngBounds(markerCoords).pad(0.2));
            }

            map.invalidateSize();
        }

        locateButton.addEventListener("click", () => {
            if (!navigator.geolocation) {
                alert("Tu navegador no soporta geolocalizacion.");
                return;
            }

            navigator.geolocation.getCurrentPosition(
                (pos) => {
                    if (userCircle) {
                        map.removeLayer(userCircle);
                    }
                    userCircle = L.circle([pos.coords.latitude, pos.coords.longitude], {
                        radius: 800,
                        color: "#CA7842",
                        fillColor: "#CA7842",
                        fillOpacity: 0.12,
                        weight: 2
                    }).addTo(map);
                    map.setView([pos.coords.latitude, pos.coords.longitude], 11);
                },
                () => alert("No se pudo obtener tu ubicacion.")
            );
        });

        renderStableMarkers();
        window.addEventListener("resize", () => map.invalidateSize());
    }

    function initLikes() {
        document.addEventListener("click", function (e) {
            const btn = e.target.closest(".btn-like");
            if (!btn) return;
            e.preventDefault();
            e.stopPropagation();

            const id = btn.dataset.id;

            fetch("/src/controller/like.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: "id_animal=" + encodeURIComponent(id)
            })
                .then((r) => r.json())
                .then((data) => {
                    if (data.error === "not_logged_in") {
                        window.location.href = "/public/login.html";
                        return;
                    }

                    document.querySelectorAll(`.btn-like[data-id="${id}"]`).forEach((b) => {
                        const icon = b.querySelector("i");
                        if (data.liked) {
                            b.classList.add("liked");
                            icon.className = "zmdi zmdi-favorite";
                        } else {
                            b.classList.remove("liked");
                            icon.className = "zmdi zmdi-favorite-outline";
                        }
                    });
                })
                .catch(() => {});
        });
    }

    function initCarousel() {
        const track = document.getElementById("carousel-track");
        if (!track) return;

        const originals = Array.from(track.querySelectorAll(".animal-card"));
        if (!originals.length) return;

        const total = originals.length;
        const minCloneBuffer = 6;
        const cloneCount = Math.max(minCloneBuffer, total);
        const fragBefore = document.createDocumentFragment();
        const fragAfter = document.createDocumentFragment();

        buildClones(originals, cloneCount, true).forEach((card) => {
            const clone = card.cloneNode(true);
            clone.setAttribute("aria-hidden", "true");
            fragBefore.appendChild(clone);
        });
        track.insertBefore(fragBefore, track.firstChild);

        buildClones(originals, cloneCount, false).forEach((card) => {
            const clone = card.cloneNode(true);
            clone.setAttribute("aria-hidden", "true");
            fragAfter.appendChild(clone);
        });
        track.appendChild(fragAfter);

        let current = cloneCount;
        let autoId;

        function cardWidth() {
            const gap = parseFloat(getComputedStyle(track).gap) || 0;
            return track.children[0].offsetWidth + gap;
        }

        function buildClones(cards, count, fromEnd) {
            const clones = [];
            for (let i = 0; i < count; i += 1) {
                const sourceIndex = fromEnd
                    ? (cards.length - (count - i) % cards.length) % cards.length
                    : i % cards.length;
                clones.push(cards[sourceIndex]);
            }
            return clones;
        }

        function moveTo(index, animate) {
            current = index;
            if (animate !== false) {
                track.style.transition = "transform 0.45s cubic-bezier(0.4, 0, 0.2, 1)";
            } else {
                track.style.transition = "none";
                track.getBoundingClientRect();
            }
            track.style.transform = `translateX(-${current * cardWidth()}px)`;
        }

        function startAuto() {
            clearInterval(autoId);
            autoId = setInterval(() => moveTo(current + 1), 3800);
        }

        track.addEventListener("transitionend", () => {
            if (current >= cloneCount + total) moveTo(current - total, false);
            if (current < cloneCount) moveTo(current + total, false);
        });

        const prevBtn = document.getElementById("prev-btn");
        const nextBtn = document.getElementById("next-btn");
        if (prevBtn) prevBtn.addEventListener("click", () => { moveTo(current - 1); startAuto(); });
        if (nextBtn) nextBtn.addEventListener("click", () => { moveTo(current + 1); startAuto(); });

        window.addEventListener("resize", () => moveTo(current, false));

        moveTo(cloneCount, false);
        startAuto();
    }

    function razaDesdeUrl(url) {
        const match = url.match(/breeds\/([^/]+)\//);
        if (!match) return "Mestizo";
        return match[1].split("-").map((word) => word[0].toUpperCase() + word.slice(1)).join(" ");
    }

    const CIUDADES_DEMO = ["Sevilla", "Madrid", "Barcelona", "Valencia", "Malaga", "Granada", "Bilbao"];

    function ciudadDemo() {
        return CIUDADES_DEMO[Math.floor(Math.random() * CIUDADES_DEMO.length)];
    }

    function crearTarjeta(url, tipo, raza, ciudad) {
        return `<a class="animal-card" href="#">
            <div class="animal-foto">
                <img src="${url}" alt="${tipo} - ${raza}" loading="lazy">
            </div>
            <div class="animal-info">
                <p class="animal-nombre">${tipo} · ${raza}</p>
                <p class="animal-detalle">Disponible · ${ciudad}</p>
            </div>
        </a>`;
    }

    function initAnimals() {
        if (TIENE_DB_ANIMALES) {
            initCarousel();
            return;
        }

        Promise.all([
            fetch("https://dog.ceo/api/breeds/image/random/5").then((r) => r.json()),
            fetch("https://api.thecatapi.com/v1/images/search?limit=5").then((r) => r.json())
        ])
            .then(([dogs, cats]) => {
                const tarjetas = [];

                (dogs.message || []).forEach((url) => {
                    tarjetas.push(crearTarjeta(url, "Perro", razaDesdeUrl(url), ciudadDemo()));
                });

                (cats || []).forEach((cat) => {
                    tarjetas.push(crearTarjeta(cat.url, "Gato", "Domestico", ciudadDemo()));
                });

                tarjetas.sort(() => Math.random() - 0.5);

                const track = document.getElementById("carousel-track");
                if (!track) return;
                track.innerHTML = tarjetas.join("");
                initCarousel();
            })
            .catch(() => {
                const wrapper = document.getElementById("carousel-wrapper");
                if (!wrapper) return;
                wrapper.innerHTML = `
                    <div id="sin-animales">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 80 80" fill="currentColor"
                             style="width:56px;height:56px;opacity:.25;margin:0 auto 16px;display:block">
                            <ellipse cx="40" cy="54" rx="18" ry="15"/>
                            <ellipse cx="20" cy="36" rx="9" ry="11"/>
                            <ellipse cx="34" cy="27" rx="9" ry="11"/>
                            <ellipse cx="50" cy="27" rx="9" ry="11"/>
                            <ellipse cx="64" cy="36" rx="9" ry="11"/>
                        </svg>
                        <p>No hay animales disponibles en este momento.</p>
                        <a href="/src/view/index.php">Reintentar</a>
                    </div>`;
            });
    }

    function initDonations() {
        const donModal = document.getElementById("don-modal");
        if (!donModal) return;

        const donTitulo = document.getElementById("don-modal-titulo");
        const donNombre = document.getElementById("don-nombre");
        const donCantidad = document.getElementById("don-cantidad");
        const donFeedback = document.getElementById("don-feedback");
        const donSubmit = document.getElementById("don-submit");
        let donCasoId = null;

        document.querySelectorAll(".crowd-btn-donar").forEach((btn) => {
            btn.addEventListener("click", () => {
                donCasoId = btn.dataset.id;
                donTitulo.textContent = btn.dataset.titulo;
                donModal.style.display = "flex";
                donFeedback.style.display = "none";
                donSubmit.disabled = false;
                donNombre.value = "";
                donCantidad.value = "";
                document.querySelectorAll(".don-quick").forEach((quickBtn) => quickBtn.classList.remove("active"));
            });
        });

        document.querySelectorAll(".don-quick").forEach((btn) => {
            btn.addEventListener("click", () => {
                donCantidad.value = btn.dataset.v;
                document.querySelectorAll(".don-quick").forEach((quickBtn) => quickBtn.classList.remove("active"));
                btn.classList.add("active");
            });
        });

        function closeDonModal() {
            donModal.style.display = "none";
        }

        document.getElementById("don-modal-close").addEventListener("click", closeDonModal);
        document.getElementById("don-cancel").addEventListener("click", closeDonModal);
        donModal.addEventListener("click", (e) => {
            if (e.target === donModal) closeDonModal();
        });

        donSubmit.addEventListener("click", () => {
            const cantidad = parseFloat(donCantidad.value);
            if (!cantidad || cantidad <= 0) {
                donFeedback.style.cssText = "display:block;background:rgba(220,60,60,.12);color:#ffaaaa;padding:10px 14px;border-radius:6px;font-size:13px";
                donFeedback.textContent = "Introduce una cantidad valida.";
                return;
            }

            donSubmit.disabled = true;
            fetch("/src/controller/crowdfunding-donacion.php", {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: `id_caso=${encodeURIComponent(donCasoId)}&cantidad=${encodeURIComponent(cantidad)}&nombre=${encodeURIComponent(donNombre.value)}`
            })
                .then((r) => r.json())
                .then((data) => {
                    donFeedback.style.display = "block";
                    donFeedback.style.padding = "10px 14px";
                    donFeedback.style.borderRadius = "6px";
                    donFeedback.style.fontSize = "13px";

                    if (data.ok) {
                        donFeedback.style.background = "rgba(60,200,100,.12)";
                        donFeedback.style.color = "#7dffb0";
                        donFeedback.textContent = "Gracias. Tu intencion de donacion ha sido registrada. La pasarela de pago estara disponible proximamente.";

                        const pct = data.meta > 0 ? Math.min(100, Math.round(data.recaudado / data.meta * 100)) : 0;
                        const card = document.querySelector(`.crowd-btn-donar[data-id="${donCasoId}"]`)?.closest(".crowd-pub-card");
                        if (card) {
                            const fill = card.querySelector(".crowd-pub-fill");
                            const nums = card.querySelectorAll(".crowd-pub-nums span");
                            if (fill) fill.style.width = pct + "%";
                            if (nums[0]) nums[0].textContent = data.recaudado.toLocaleString("es-ES", { minimumFractionDigits: 0 }) + " €";
                            if (nums[1]) nums[1].textContent = pct + "% de " + data.meta.toLocaleString("es-ES", { minimumFractionDigits: 0 }) + " €";
                        }
                    } else {
                        donFeedback.style.background = "rgba(220,60,60,.12)";
                        donFeedback.style.color = "#ffaaaa";
                        donFeedback.textContent = data.error || "Error al registrar la donacion.";
                        donSubmit.disabled = false;
                    }
                })
                .catch(() => {
                    donFeedback.style.cssText = "display:block;background:rgba(220,60,60,.12);color:#ffaaaa;padding:10px 14px;border-radius:6px;font-size:13px";
                    donFeedback.textContent = "Error de conexion.";
                    donSubmit.disabled = false;
                });
        });
    }

    initMap();
    initLikes();
    initAnimals();
    initDonations();
})();
