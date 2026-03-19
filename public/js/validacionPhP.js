
window.addEventListener("load" , () =>{
    let params = new URLSearchParams(window.location.search);
    let error = params.get("error");
    let DescError = "";
    alert("hola")
    if(error){
        switch (error) {
            case "nombre":
                DescError = "No te saltes el front"
                break;
            case "apellido":
                DescError = "s"
                break;
            case "nombre":
                DescError = "s"
                break;
            case "email":
                DescError = "s"
                break;
            case "pass":
                DescError = "s"
                break;
            case "db":
                DescError = "Ha ocurrido un error, No se ha podido registrar el usuario en la bbdd"
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
