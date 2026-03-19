window.addEventListener("load" , () =>{
    let params = new URLSearchParams(window.location.search);
    let error = params.get("error");
    let DescError = "";
    
    
    if(error){
        switch (error) {
            /*
            case "nombre":
                DescError = "#F54ASf927as10124GSAUI1"
                break;
            case "apellido":
                DescError = "#F54ASf927as10124GSAUI1"
                break;
            case "nombre":
                DescError = "#F54ASf927as10124GSAUI1"
                break;
            case "email":
                DescError = "#F54ASf927as10124GSAUI1"
                break;
            case "pass":
                DescError = "#F54ASf927as10124GSAUI1"
                break;
            */
            case "noexiste":
                DescError = "Ese email no pertenece a ninguna cuenta"
                break;
            case "passNoCoincide":
                DescError = "Usuario y Contraseña no coinciden"
                break;
            default:
                DescError = ""
                break;
        }

        if(DescError != ""){
            let div = document.createElement("div")
            div.setAttribute("class" , "error")
            div.textContent = DescError;
            document.getElementById("estructura").prepend(div)
            // HABRÁ QUE CAMBAR DONDE SE METE DEPENDIENDO DEL LOGIN
        }
        

    }



   
})