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

    // ── SELECT DINÁMICO DE LOCALIDAD ─────────────────────────────
    const MUNICIPIOS = {
        almeria: ["Abla","Abrucena","Adra","Alboloduy","Albox","Aldeire","Alfaix","Alguazas","Alhama de Almería","Alicún","Almería","Almócita","Alsodux","Antas","Arboleas","Armuña de Almanzora","Bayárcal","Bayarque","Bédar","Berja","Benitagla","Benizalón","Bentarique","Berja","Carboneras","Canjáyar","Castro de Filabres","Chercos","Chirivel","Cobdar","Cuevas del Almanzora","Dalías","Ejido (El)","Enix","Felix","Fines","Fiñana","Fondón","Gádor","Garrucha","Gérgal","Huécija","Huércal de Almería","Huércal-Overa","Illar","Instinción","Laroya","Láujar de Andarax","Líjar","Lubrín","Lucainena de las Torres","Lúcar","Macael","María","Mojácar","Mojonera (La)","Nacimiento","Níjar","Ohanes","Olula de Castro","Olula del Río","Oria","Padules","Partaloa","Paterna del Río","Pechina","Pulpí","Purchena","Quirós","Rioja","Roquetas de Mar","Santa Cruz de Marchena","Santa Fe de Mondújar","Senés","Serón","Sierro","Somontín","Sorbas","Suflí","Tabernas","Taberno","Tahal","Terque","Tijola","Tíjola","Turre","Turrillas","Uleila del Campo","Urracal","Velefique","Vera","Vícar","Zurgena"],
        cadiz: ["Alcalá de los Gazules","Alcalá del Valle","Algeciras","Algodonales","Arcos de la Frontera","Barbate","Barrios (Los)","Benalup-Casas Viejas","Benaocaz","Bornos","Bosque (El)","Cádiz","Castellar de la Frontera","Chiclana de la Frontera","Chipiona","Conil de la Frontera","Espera","Gastor (El)","Grazalema","Jerez de la Frontera","Jimena de la Frontera","La Línea de la Concepción","Línea de la Concepción (La)","Medina Sidonia","Olvera","Pago del Humo","Paterna de Rivera","Prado del Rey","Puerto de Santa María (El)","Puerto Real","Puerto Serrano","Rota","San Fernando","San José del Valle","San Roque","Sanlúcar de Barrameda","Setenil de las Bodegas","Tarifa","Torre Alháquime","Trebujena","Ubrique","Vejer de la Frontera","Villaluenga del Rosario","Villamartín","Zahara","Zahara de los Atunes"],
        cordoba: ["Adamuz","Aguilar de la Frontera","Alcaracejos","Almedinilla","Almodóvar del Río","Añora","Baena","Belalcázar","Belmez","Benamejí","Bujalance","Cabra","Cañete de las Torres","Carcabuey","Carlota (La)","Carpio (El)","Castro del Río","Conquista","Córdoba","Doña Mencía","Dos Torres","Encinas Reales","Espejo","Espiel","Fernán-Núñez","Fuente la Lancha","Fuente Obejuna","Fuente Palmera","Fuente-Tójar","Granjuela (La)","Guadalcázar","Guijo (El)","Hinojosa del Duque","Hornachuelos","Iznájar","Lucena","Luque","Montalbán de Córdoba","Montemayor","Montilla","Montoro","Monturque","Moriles","Nueva Carteya","Obejo","Palenciana","Palma del Río","Pedro Abad","Pedroche","Peñarroya-Pueblonuevo","Posadas","Pozoblanco","Priego de Córdoba","Puente Genil","Rambla (La)","Rute","San Sebastián de los Ballesteros","Santaella","Torrecampo","Valenzuela","Valsequillo","Victoria (La)","Villaharta","Villanueva de Córdoba","Villanueva del Duque","Villanueva del Rey","Villaralto","Villaviciosa de Córdoba","Viso (El)","Zuheros"],
        granada: ["Agrón","Albuñán","Albuñol","Albuñuelas","Aldeire","Alfacar","Algarinejo","Alhama de Granada","Alhendín","Alicún de Ortega","Almuñécar","Alquife","Armilla","Atarfe","Baza","Beas de Guadix","Benalúa","Bérchules","Calahorra (La)","Cájar","Calicasas","Campotéjar","Caniles","Cañar","Capileira","Carataunas","Cástaras","Castilléjar","Castril","Cenes de la Vega","Cijuela","Cogollos de Guadix","Cogollos de la Vega","Colomera","Cúllar","Cúllar Vega","Darro","Deifontes","Diezma","Dílar","Dólar","Dúdar","Dúrcal","Escúzar","Ferreira","Fonelas","Frailes","Freila","Fuente Vaqueros","Gabias (Las)","Galera","Gobernador","Gójar","Gorafe","Granada","Guadahortuna","Guadix","Güéjar Sierra","Güevéjar","Huélago","Huéneja","Huéscar","Iznalloz","Jayena","Jerez del Marquesado","Jun","Juviles","Láchar","Lanjarón","Lanteira","Lecrín","Lentegí","Loja","Lugros","Lújar","Maracena","Marchal","Moclín","Molvízar","Monachil","Montefrío","Montejícar","Montillana","Moraleda de Zafayona","Morelábor","Motril","Murtas","Nevada","Nigüelas","Nívar","Noalejo","Ogíjares","Orce","Órgiva","Otívar","Padul","Pampaneira","Pedro Martínez","Peligros","Peza (La)","Pinar (El)","Pinos Genil","Pinos Puente","Píñar","Pobla de Don Fadrique (La)","Polopos","Pórtugos","Puebla de Don Fadrique","Pulianas","Purullena","Quéntar","Rubite","Salar","Salobreña","Santa Cruz del Comercio","Santa Fe","Soportújar","Sorvilán","Torvizcón","Trevélez","Turón","Ugíjar","Válor","Vegas del Genil","Vélez de Benaudalla","Ventas de Huelma","Villanueva de las Torres","Villanueva Mesía","Víznar","Zafarraya","Zagra","Zubia (La)","Zújar"],
        huelva: ["Alájar","Aljaraque","Almendro (El)","Almonaster la Real","Almonte","Alosno","Aracena","Aroche","Arroyomolinos de la Vera","Ayamonte","Beas","Berrocal","Bollullos Par del Condado","Bonares","Cabezas Rubias","Cala","Calañas","Campillo (El)","Campofrío","Cañaveral de León","Cartaya","Castaño del Robledo","Cerro de Andévalo (El)","Chucena","Corteconcepción","Cortegana","Cortelazor","Cumbres de Enmedio","Cumbres de San Bartolomé","Cumbres Mayores","Encinasola","Escacena del Campo","Fuenteheridos","Galaroza","Gibraleón","Granada de Riotinto (La)","Granado (El)","Hinojos","Huelva","Isla Cristina","Islas del Guadalquivir","Jabugo","Lepe","Linares de la Sierra","Lucena del Puerto","Manzanilla","Marines (Los)","Minas de Riotinto","Moguer","Nava (La)","Nerva","Niebla","Palma del Condado (La)","Palos de la Frontera","Paterna del Campo","Paymogo","Puebla de Guzmán","Puerto Moral","Punta Umbría","Rociana del Condado","Rosal de la Frontera","San Bartolomé de la Torre","San Juan del Puerto","San Silvestre de Guzmán","Santa Ana la Real","Santa Bárbara de Casa","Santa Olalla del Cala","Trigueros","Valdelarco","Valverde del Camino","Villablanca","Villalba del Alcor","Villanueva de las Cruces","Villanueva de los Castillejos","Villarrasa","Zalamea la Real","Zamora (La)","Zufre"],
        jaen: ["Albanchez de Mágina","Alcalá la Real","Alcaudete","Alcázar","Aldeaquemada","Andújar","Arjona","Arjonilla","Arquillos","Arroyo del Ojanco","Baeza","Bailén","Baños de la Encina","Beas de Segura","Begíjar","Bélmez de la Moraleda","Benatae","Cabra del Santo Cristo","Cambil","Campillo de Arenas","Canena","Carboneros","Cárcheles","Carolina (La)","Castellar","Castillo de Locubín","Cazalilla","Cazorla","Chiclana de Segura","Chilluévar","Escañuela","Espelúy","Frailes","Fuensanta de Martos","Fuerte del Rey","Génave","Guardia de Jaén (La)","Guarromán","Higuera de Calatrava","Hinojares","Hornos","Huelma","Huesa","Iruela (La)","Iznatoraf","Jabalquinto","Jaén","Jamilena","Jimena","Jódar","Lahiguera","Larva","Linares","Lopera","Lupión","Mancha Real","Marmolejo","Martos","Mengíbar","Montizón","Navas de San Juan","Noalejo","Orcera","Peal de Becerro","Pegalajar","Porcuna","Pozo Alcón","Puente de Génave","Puerta de Segura (La)","Quesada","Rus","Sabiote","Santiago de Calatrava","Santiago-Pontones","Santisteban del Puerto","Santo Tomé","Segura de la Sierra","Siles","Sorihuela del Guadalimar","Torreblascopedro","Torredelcampo","Torredonjimeno","Torreperogil","Torres","Torres de Albánchez","Úbeda","Valdepeñas de Jaén","Vilches","Villacarrillo","Villanueva de la Reina","Villanueva del Arzobispo","Villardompardo","Villares (Los)","Villarrodrigo","Villatorres"],
        malaga: ["Alameda","Alcaucín","Alfarnate","Alfarnatejo","Algarrobo","Algatocín","Alhaurín de la Torre","Alhaurín el Grande","Almargen","Almáchar","Almogia","Álora","Alozaina","Alpandeire","Antequera","Árchez","Archidona","Ardales","Arenas","Arriate","Atajate","Benadalid","Benahavís","Benalauría","Benalmádena","Benamargosa","Benamocarra","Benaoján","Benarrabá","Borge (El)","Burgo (El)","Campillos","Canillas de Aceituno","Canillas de Albaida","Cañete la Real","Carratraca","Cartajima","Cártama","Casabermeja","Casarabonela","Casares","Coín","Colmenar","Comares","Cómpeta","Cortes de la Frontera","Cuevas Bajas","Cuevas de San Marcos","Cuevas del Becerro","Cútar","Estepona","Faraján","Frigiliana","Fuengirola","Fuente de Piedra","Gaucín","Genalguacil","Guaro","Humilladero","Igualeja","Istán","Iznate","Jimera de Líbar","Jubrique","Júzcar","Macharaviaya","Málaga","Manilva","Marbella","Mijas","Moclinejo","Mollina","Monda","Montecorto","Montejaque","Nerja","Ojén","Parauta","Periana","Pizarra","Pujerra","Rincón de la Victoria","Riogordo","Ronda","Salares","Sayalonga","Sedella","Serrato","Sierra de Yeguas","Teba","Tolox","Torremolinos","Torrox","Totalán","Valle de Abdalajís","Vélez-Málaga","Villanueva de Algaidas","Villanueva de la Concepción","Villanueva de Tapia","Villanueva del Rosario","Villanueva del Trabuco","Viñuela","Yunquera"],
        sevilla: ["Aguadulce","Alanís","Albaida del Aljarafe","Alcalá de Guadaíra","Alcalá del Río","Alcalá de los Gazules","Alcolea del Río","Algaba (La)","Algámitas","Almadén de la Plata","Almensilla","Arahal","Aznalcázar","Aznalcóllar","Badolatosa","Benacazón","Bollullos de la Mitación","Bormujos","Brenes","Burguillos","Cabezas de San Juan (Las)","Camas","Cantillana","Cañada Rosal","Carmona","Carrión de los Céspedes","Casariche","Castilblanco de los Arroyos","Castilleja de Guzmán","Castilleja de la Cuesta","Castilleja del Campo","Castillo de las Guardas (El)","Cazalla de la Sierra","Constantina","Coria del Río","Coripe","Coronil (El)","Corrales (Los)","Dos Hermanas","Écija","Espartinas","Estepa","Fuentes de Andalucía","Garrobo (El)","Gelves","Gerena","Gilena","Gines","Guadalcanal","Guillena","Herrera","Huévar del Aljarafe","Isla Mayor","Lantejuela","Lebrija","Lora de Estepa","Lora del Río","Luisiana (La)","Madroño (El)","Mairena del Alcor","Mairena del Aljarafe","Marchena","Marinaleda","Martín de la Jara","Molares (Los)","Montellano","Moron de la Frontera","Morón de la Frontera","Navas de la Concepción (Las)","Olivares","Osuna","Palacios y Villafranca (Los)","Palomares del Río","Paradas","Pedrera","Pedroso (El)","Peñaflor","Pilas","Pruna","Puebla de Cazalla (La)","Puebla de los Infantes (La)","Puebla del Río (La)","Real de la Jara (El)","Rinconada (La)","Roda de Andalucía (La)","Ronquillo (El)","Rubio (El)","Salteras","San Juan de Aznalfarache","Sanlúcar la Mayor","San Nicolás del Puerto","Santiponce","Sevilla","Tocina","Tomares","Umbrete","Utrera","Valencina de la Concepción","Villanueva de San Juan","Villanueva del Ariscal","Villanueva del Río y Minas","Villaverde del Río","Viso del Alcor (El)"]
    };

    const selectCiudad = document.getElementById('prote-ciudad');
    const selectLocalidad = document.getElementById('prote-localidad');

    if (selectCiudad && selectLocalidad) {
        selectCiudad.addEventListener('change', function () {
            const provincia = this.value;
            const municipios = MUNICIPIOS[provincia] || [];

            selectLocalidad.innerHTML = '<option value="" disabled selected hidden>Selecciona localidad</option>';

            municipios.forEach(function (municipio) {
                const opt = document.createElement('option');
                opt.value = municipio;
                opt.textContent = municipio;
                selectLocalidad.appendChild(opt);
            });

            selectLocalidad.disabled = municipios.length === 0;
        });
    }

};