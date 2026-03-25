<?php session_start()?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>De momento</title>
    <link rel="stylesheet" href="css/header.css">
    <link rel="stylesheet" href="css/registro.css">
</head>
<body>

    <header>
    <!--Logo que rederige al index-->
        <nav class="hBotones">
            <a class="hBoton" href="" target="_self">PROTECTORAS</a>
            <a class="hBoton" href="" target="_self">COLABORADORES</a>
        </nav>
        <nav id="header-izq">
            <a href="index.html" target="_self">
            <img src="" alt="logo" 
             width=""> <!--Todo esto está en blanco hasta que tengamos el logo listo y el index-->
        </a>
        </nav>
        
        <nav class="hBotones"> 
            <a class="hBoton" href="" target="_self">URGENTE</a>
            <a class="hBoton" href="registro.html" target="_self">REGISTRATE!</a>
            <a  href="" target="_self" id="boton-destacado">INICIA SESIÓN</a>

        </nav>
    </header>
    <br><br><br>

    <div id="estructura">
    <p>Tienes Cuenta ? <a href="login.html">Logearse</a></p>
    <p>No tienes Cuenta ? <a href="registro.html">Registrate</a></p>
    
    <?php 
    if(isset($_SESSION["id"])){
        echo "Hola, ".$_SESSION['nombre']." <br>";
        echo "<a href='../src/sesion/logout.php'>Cerrar Sesion... </a>";
        echo "<p> <a href='perfil.php'>Modificar Perfil</a></p>"; // lo mismo aqui renta mandar el name o algo perfil.php?name=""
    }
    ?>
    </div>
</body>
</html>