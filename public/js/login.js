import { validarVacio, antiXSS } from "./validation";

document.addEventListener('DOMContentLoaded', () => {
    const email = document.getElementById("email");
    const pass = document.getElementById("pass");

    email.onblur = () => validarVacio(email);
    pass.onblur = () => validarVacio(pass);

    document.forms[0].addEventListener("submit", (e) => {
        e.preventDefault();
        email.validarVacio();
        pass.validarVacio();

        const errores = document.forms[0].querySelectorAll("small");
        if (errores.length == 0 && !antiXSS(email) && !antiXSS(pass)) document.forms[0].submit();
    })
});