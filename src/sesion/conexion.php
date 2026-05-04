<?php

    // PRODUCCION
    //$_servidor  = "sql306.infinityfree.com";
    //$_usuario   = "if0_41458160";
    //$_contrasena = "On5pI1MXYFxNMhY";
    //$_db        = "if0_41458160_db";

    // DESARROLLO (local) — descomentar para trabajar en local
    $_servidor  = "localhost";
    $_usuario   = "root";
    $_contrasena = "";
    $_db        = "bd_protectora";

    $_conexion = new mysqli($_servidor, $_usuario, $_contrasena, $_db);

    if ($_conexion->connect_error) {
        die("Error en la conexion: " . $_conexion->connect_error);
    }

    $_conexion->set_charset("utf8mb4");
?>
