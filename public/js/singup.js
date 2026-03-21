import { validarVacio,  validarEmail, validarPass, validarCoincidencia } from "./validation.js";

window.onload = function () {
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

    // -------- ADOPTANTE --------
    const nombre = document.getElementById("adopt-nombre");
    const apellido = document.getElementById("adopt-apellido");
    const email = document.getElementById("adopt-email");
    const email2 = document.getElementById("adopt-email2");
    const pass = document.getElementById("adopt-password");
    const pass2 = document.getElementById("adopt-password2");

    nombre.onblur = () => validarVacio(nombre);
    apellido.onblur = () => validarVacio(apellido);

    email.onblur = () => validarEmail(email);
    email2.onblur = () => validarCoincidencia(email, email2);

    pass.addEventListener('blur', () => {
        validarVacio(pass);
        validarPass(pass);
    })
    
    pass2.onblur = () => validarCoincidencia(pass, pass2);


    // -------- PROTECTORA --------
    const pNombre = document.getElementById("prote-nombre");
    const pEmail = document.getElementById("prote-email");
    const pEmail2 = document.getElementById("prote-email2");
    const pPass = document.getElementById("prote-password");
    const pPass2 = document.getElementById("prote-password2");

    pNombre.onblur = () => validarVacio(pNombre);

    pEmail.onblur = () => validarEmail(pEmail);
    pEmail2.onblur = () => validarCoincidencia(pEmail, pEmail2);

    pPass.onblur = () => validarVacio(pPass);
    pPass.onblur = () => validarPass(pPass);
    pPass2.onblur = () => validarCoincidencia(pPass, pPass2);

    const formAdopt = document.getElementById("registro");
    const formProte = document.getElementById("registro2");

    formAdopt.addEventListener('submit', (e) => {
        e.preventDefault();
        validarVacio(nombre);
        validarVacio(apellido);
        validarEmail(email);
        validarCoincidencia(email, email2);
        validarVacio(pass);
        validarPass(pass);
        validarCoincidencia(pass, pass2);

        const errores = formAdopt.querySelectorAll("small");
        if (errores.length == 0) formAdopt.submit();
    });

    formProte.addEventListener('submit', (e) => {
        e.preventDefault();
        validarVacio(pNombre);
        validarVacio(pEmail);
        validarVacio(pEmail2);
        validarVacio(pPass);
        validarVacio(pPass2);
        const errores = formProte.querySelectorAll("small");
        if (errores.length == 0) formProte.submit();
    });

};