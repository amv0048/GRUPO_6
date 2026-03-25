<?php session_start()?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>De momento</title>
</head>
<body>

    <p>Tienes Cuenta ? <a href="login.html">Logearse</a></p>
    <p>No tienes Cuenta ? <a href="registro.html">Registrate</a></p>
    <p><a href="perfil.php"> Modificar Perfil</a></p>
    
    <?php 
    if(isset($_SESSION["id"])){
        echo "<p> <a href='perfil.php'>Modificar Perfil</a></p>"; // lo mismo aqui renta mandar el name o algo perfil.php?name=""
    }
    ?>
</body>
</html>