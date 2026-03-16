document.addEventListener('DOMContentLoaded', () => {


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

})