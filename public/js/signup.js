function esEmailValido(email) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    }

    function mostrarError(input, mensaje) {
        eliminarError(input);
        const error = document.createElement("small");
        error.textContent = mensaje;
        error.style.color = "red";
        input.parentNode.insertBefore(error, input.nextSibling);
    }

    function eliminarError(input) {
        if (input.nextSibling && input.nextSibling.tagName === "SMALL") {
            input.parentNode.removeChild(input.nextSibling);
        }
    }

    function validarCampo(input, condicion, mensaje) {
        if (!condicion) {
            mostrarError(input, mensaje);
            return false;
        } else {
            eliminarError(input);
            return true;
        }
    }


window.addEventListener("DOMContentLoaded", () => {

    //Elegir Formulario
    const padreNuestro = document.querySelector('#padre-nuestro');
    const proteBtn = document.querySelector('#protectora');
    const adoptBtn = document.querySelector('#adoptantes');
    
    proteBtn.addEventListener('click', () => {
        padreNuestro.classList = "vProtectora";
    });
    
    adoptBtn.addEventListener('click', () => {
        padreNuestro.classList = "vAdoptante";
    });

    const formAdopt = document.getElementById("registro");
    const formProte = document.getElementById("registro2");

    
    formAdopt.addEventListener("submit", (e) => {
        let valido = true;

        const nombre = document.getElementById("adopt-nombre");
        const apellido = document.getElementById("adopt-apellido");
        const email = document.getElementById("adopt-email");
        const email2 = document.getElementById("adopt-email2");
        const pass = document.getElementById("adopt-password");
        const pass2 = document.getElementById("adopt-password2");

        if (!validarCampo(nombre, nombre.value.trim() !== "", "Campo obligatorio")) valido = false;
        if (!validarCampo(apellido, apellido.value.trim() !== "", "Campo obligatorio")) valido = false;
        if (!validarCampo(email, esEmailValido(email.value), "Email no válido")) valido = false;
        if (!validarCampo(email2, email.value === email2.value && email2.value !== "", "Los correos no coinciden")) valido = false;
        if (!validarCampo(pass, pass.value.length >= 6, "Mínimo 6 caracteres")) valido = false;
        if (!validarCampo(pass2, pass.value === pass2.value && pass2.value !== "", "Las contraseñas no coinciden")) valido = false;

        if (!valido) e.preventDefault();
    });

    formProte.addEventListener("submit", (e) => {
        let valido = true;

        const nombre = document.getElementById("prote-nombre");
        const ciudad = document.getElementById("prote-ciudad");
        const localidad = document.getElementById("prote-localidad");
        const direccion = document.getElementById("prote-direccion");
        const email = document.getElementById("prote-email");
        const email2 = document.getElementById("prote-email2");
        const pass = document.getElementById("prote-password");
        const pass2 = document.getElementById("prote-password2");

        if (!validarCampo(nombre, nombre.value.trim() !== "", "Campo obligatorio")) valido = false;
        if (!validarCampo(ciudad, ciudad.value !== "", "Selecciona una ciudad")) valido = false;
        if (!validarCampo(localidad, localidad.value.trim() !== "", "Campo obligatorio")) valido = false;
        if (!validarCampo(direccion, direccion.value.trim() !== "", "Campo obligatorio")) valido = false;
        if (!validarCampo(email, esEmailValido(email.value), "Email no válido")) valido = false;
        if (!validarCampo(email2, email.value === email2.value && email2.value !== "", "Los correos no coinciden")) valido = false;
        if (!validarCampo(pass, pass.value.length >= 6, "Mínimo 6 caracteres")) valido = false;
        if (!validarCampo(pass2, pass.value === pass2.value && pass2.value !== "", "Las contraseñas no coinciden")) valido = false;

        if (!valido) e.preventDefault();
    });    
});