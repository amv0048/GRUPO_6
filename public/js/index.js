(function () {
    const pageDataNode = document.getElementById('index-page-data');
    if (!pageDataNode) return;

    const pageData = JSON.parse(pageDataNode.textContent || '{}');
    const PROTECTORAS = Array.isArray(pageData.protectoras) ? pageData.protectoras : [];
    const TIENE_DB_ANIMALES = Boolean(pageData.tieneDbAnimales);

    const CITY_COORDS = {
        madrid: [40.4168, -3.7038],
        sevilla: [37.3891, -5.9845],
        barcelona: [41.3874, 2.1686],
        valencia: [39.4699, -0.3763],
        malaga: [36.7213, -4.4214],
        "malaga": [36.7213, -4.4214],
        zaragoza: [41.6488, -0.8891],
        bilbao: [43.2630, -2.9350],
        alicante: [38.3452, -0.4810],
        cordoba: [37.8882, -4.7794],
        "cordoba": [37.8882, -4.7794],
        valladolid: [41.6523, -4.7245],
        vigo: [42.2406, -8.7207],
        gijon: [43.5322, -5.6611],
        "gijon": [43.5322, -5.6611],
        coruna: [43.3623, -8.4115],
        "coruna": [43.3623, -8.4115],
        granada: [37.1773, -3.5986],
        vitoria: [42.8467, -2.6716],
        "vitoria-gasteiz": [42.8467, -2.6716],
        elche: [38.2699, -0.7126],
        oviedo: [43.3614, -5.8494],
        cartagena: [37.6257, -0.9966],
        jerez: [36.6850, -6.1261],
        mostoles: [40.3223, -3.8650],
        "mostoles": [40.3223, -3.8650],
        "alcala de henares": [40.4818, -3.3649],
        pamplona: [42.8125, -1.6458],
        almeria: [36.8340, -2.4637],
        "almeria": [36.8340, -2.4637],
        fuenlabrada: [40.2839, -3.7942],
        leganes: [40.3282, -3.7635],
        "leganes": [40.3282, -3.7635],
        donostia: [43.3183, -1.9812],
        "san sebastian": [43.3183, -1.9812],
        burgos: [42.3439, -3.6969],
        santander: [43.4623, -3.8099],
        castellon: [39.9864, -0.0513],
        "castellon": [39.9864, -0.0513]
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

        async function geocodeProtectora(protectora) {
            const query = buildGeoQuery(protectora);
            if (!query) return null;

            try {
                const response = await fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${encodeURIComponent(query)}&limit=1`, {
                    headers: { "Accept-Language": "es" }
                });
                if (!response.ok) return null;
                const data = await response.json();
                if (!Array.isArray(data) || !data.length) return null;

                return [parseFloat(data[0].lat), parseFloat(data[0].lon)];
            } catch (error) {
                return null;
            }
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

        async function resolveAllCoords() {
            const resolved = [];
            for (const protectora of PROTECTORAS) {
                let coords = resolveCoords(protectora);
                if (!coords) {
                    coords = await geocodeProtectora(protectora);
                }
                if (coords) {
                    resolved.push({ protectora, coords });
                }
            }
            return resolved;
        }

        async function renderStableMarkers() {
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
                    renderStableMarkers();
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
        window.addEventListener("load", () => setTimeout(renderStableMarkers, 150));
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
        const cloneCount = Math.min(5, total);
        const fragBefore = document.createDocumentFragment();
        const fragAfter = document.createDocumentFragment();

        originals.slice(-cloneCount).forEach((card) => {
            const clone = card.cloneNode(true);
            clone.setAttribute("aria-hidden", "true");
            fragBefore.appendChild(clone);
        });
        track.insertBefore(fragBefore, track.firstChild);

        originals.slice(0, cloneCount).forEach((card) => {
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
