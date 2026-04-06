
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
            case "db":
                DescError = "Ese Email ya pertenece a una cuenta"
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
        }
        

    }



   
})
