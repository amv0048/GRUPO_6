
window.onload = () =>{
    var params = new URLSearchParams(window.location.search);
    const error = params.get("error");
    let DescError = "";

    if(error){
        switch (error) {
            case "nombre":
                DescError = "No te saltes el front"
                break;
            case "apellido":
                DescError = ""
                break;
            case "nombre":
                DescError = ""
                break;
            case "email":
                DescError = ""
                break;
            case "pass":
                DescError = ""
                break;
            case "db":
                DescError = "Ha ocurrido un error, No se ha podido registrar el usuario en la bbdd"
                break;
            default:
                break;
        }

        let div = document.createElement("div")
        div.setAttribute("class" , "error")
        div.textContent = DescError;

        document.getElementById("estructura").prepend(div)

    }



   
}
