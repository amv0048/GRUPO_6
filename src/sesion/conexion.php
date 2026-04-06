<?php 
/**
 * 
 * Vamos a crear una conexion entre php y la base de datos mysql usando la clase "mysqli"
 * 
 * new mysqli(..) es el constructor de la clase msqli que se utiliza para inicializar un objeto que representa 
 *                la conexion a la bbdd.
 * 
 * --si se produce la conexion, la variable donde guardemos el objeto contendrá un objeto de la clase mysqli 
 * que podemos usar con la bbdd ( realizar consultas, manejo de errores ...)
 * --si se produce un FALLO al concetarse, el metodo connect_error, contendrá info de porque no nos hemos
 * podido conectar
 * 
 * 
 * 
 */

    #error_reporting( E_ALL);
    #ini_set("display_errors", 1);

    $_servidor = "sql306.infinityfree.com";
    $_usuario = "if0_41458160";
    $_contrasena = "On5pI1MXYFxNMhY";
    $_db = "if0_41458160_db";

    $_conexion = new mysqli($_servidor, $_usuario, $_contrasena, $_db);


    if($_conexion -> connect_error){
        die("Error en la conexion: ".$_conexion -> connect_error);
    }


    #var_dump($_conexion -> connect_error);
    #echo "Conectaos..";
    #echo $_conexion -> host_info;
    #echo "<br>";
    #echo $_conexion -> server_info;

    // para cerrar sobre todo cuando trabajamos con dos bbdd simultaneas y queremos abrir y cerrar.
    //$_conexion -> close();

    #$_conexion -> host_info;
?>