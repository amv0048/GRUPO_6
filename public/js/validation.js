// Funciones Internas para la validacion en JS

export function mostrarError(input, mensaje) {
    eliminarError(input);
    const error = document.createElement("small");
    error.textContent = mensaje;
    error.style.color = "red";
    input.parentNode.insertBefore(error, input.nextSibling);
}

export function eliminarError(input) {
    if (input.nextSibling && input.nextSibling.tagName === "SMALL") {
        input.parentNode.removeChild(input.nextSibling);
    }
}

export function validarVacio(input) {
    if (input.value.trim() === "") {
        input.style.border = "2px solid red";
        mostrarError(input, "El campo no debe de estar vacio")
    } else {
        input.style.border = "2px solid green";
        eliminarError(input);
    }
}

export function validarEmail(input) {
    const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!regex.test(input.value)) {
        input.style.border = "2px solid red";
        mostrarError(input, "Email no valido")
    } else {
        input.style.border = "2px solid green";
        eliminarError(input)
    }
}

export function validarPass(input){
    const regex = /^(?=.*[A-Z])(?=.*[a-z])(?=.*[!@#$%^&*()_+\-=[\]{};':"\\|,.<>/?]).{8,}$/;
    if (!regex.test(input.value)){
        input.style.border = "2px solid red";
        mostrarError(input, "La contraseña debe tener 8 caracteres, incluyendo una mayuscula, una minuscula y un signo especial");
    }
    else {
        input.style.border = "2px solid green";
        eliminarError(input)
    }
}

export function validarCoincidencia(input1, input2) {
    if (input1.value !== input2.value || input1.value === "") {
        input2.style.border = "2px solid red";
        mostrarError(input2, "Los datos deben coincidir")
    } else {
        input2.style.border = "2px solid green";
        eliminarError(input2);
    }
}

export function antiXSS(input){
    if (input.value.includes("<script>") || input.value.includes("</script>"))
        return true;
}