window.onload = function () {
    //Elegir Formulario
    const padreNuestro = document.querySelector('#padre-nuestro');
    const proteBtn = document.querySelector('#protectora');
    const adoptBtn = document.querySelector('#adoptantes');
   
    /*
    proteBtn.addEventListener('click', () => {
    padreNuestro.classList = "vProtectora";
    document.querySelector('input[name="tipo"]').value = "protectora";
});

adoptBtn.addEventListener('click', () => {
    padreNuestro.classList = "vAdoptante";
    document.querySelector('input[name="tipo"]').value = "usuario";
});
*/


    
    proteBtn.addEventListener('click', () => {
        padreNuestro.classList = "vProtectora";
    });
    
    adoptBtn.addEventListener('click', () => {
        padreNuestro.classList = "vAdoptante";
    });
    
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

    function validarVacio(input) {
        if (input.value.trim() === "") {
            input.style.border = "2px solid red";
            mostrarError(input, "El campo no debe de estar vacio")
        } else {
            input.style.border = "2px solid green";
            eliminarError(input);
        }
    }

    function validarEmail(input) {
        const regex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!regex.test(input.value)) {
            input.style.border = "2px solid red";
            mostrarError(input, "Email no valido")
        } else {
            input.style.border = "2px solid green";
            eliminarError(input)
        }
    }

    function validarPass(input){
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

    function validarCoincidencia(input1, input2) {
        if (input1.value !== input2.value || input1.value === "") {
            input2.style.border = "2px solid red";
            mostrarError(input2, "Los datos deben coincidir")
        } else {
            input2.style.border = "2px solid green";
            eliminarError(input2);
        }
    }

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

    pass.onblur = () => validarVacio(pass);
    pass.onblur = () => validarPass(pass);
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