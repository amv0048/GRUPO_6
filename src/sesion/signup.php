<?php
require "conexion.php";
error_reporting(E_ALL);
ini_set("display_errors", 1);

if ($_SERVER["REQUEST_METHOD"] == "POST" && $_POST["tipo"] == "usuario") {

    $tmp_nombre = htmlspecialchars(trim($_POST["adopt-nombre"]));
    if ($tmp_nombre == "") {
        header("Location: ../signup.html?error=nombre");
        exit();
    } elseif (strlen($tmp_nombre) < 2) {
        header("Location: ../signup.html?error=nombre");
        exit();
    } else {
        $nombre = $tmp_nombre;
    }

    $tmp_apellido = htmlspecialchars(trim($_POST["adopt-apellido"]));
    if ($tmp_apellido == "") {
        header("Location: ../signup.html?error=apellido");
        exit();
    } else {
        $apellido = $tmp_apellido;
    }

    $tmp_email  = htmlspecialchars(trim($_POST["adopt-email"]));
    $tmp_email2 = htmlspecialchars(trim($_POST["adopt-email2"]));
    if ($tmp_email == "") {
        header("Location: ../signup.html?error=email");
        exit();
    } elseif (!preg_match("/^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/", $tmp_email)) {
        header("Location: ../signup.html?error=email");
        exit();
    } elseif ($tmp_email != $tmp_email2) {
        header("Location: ../signup.html?error=email");
        exit();
    } else {
        $email = $tmp_email;
    }

    $tmp_pass  = htmlspecialchars(trim($_POST["adopt-password"]));
    $tmp_pass2 = htmlspecialchars(trim($_POST["adopt-password2"]));
    if ($tmp_pass == "") {
        header("Location: ../signup.html?error=pass");
        exit();
    } elseif (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/", $tmp_pass)) {
        header("Location: ../signup.html?error=pass");
        exit();
    } elseif ($tmp_pass != $tmp_pass2) {
        header("Location: ../signup.html?error=pass");
        exit();
    } else {
        $pass = $tmp_pass;
    }

    if (isset($nombre, $apellido, $email, $pass)) {
        $pass_cifrada = password_hash($pass, PASSWORD_DEFAULT);
        $consulta = $_conexion->prepare(
            "INSERT INTO Usuario (nombre, apellido, email, contraseña, numero, fiabilidad, admin)
             VALUES (?, ?, ?, ?, NULL, 0, 0)"
        );
        $consulta->bind_param("ssss", $nombre, $apellido, $email, $pass_cifrada);
        if ($consulta->execute()) {
            $consulta->close();
            header("Location: ../login.html");
            exit();
        } else {
            $consulta->close();
            header("Location: ../signup.html?error=db");
            exit();
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && $_POST["tipo"] == "protectora") {

    $tmp_nombre = htmlspecialchars(trim($_POST["prote-nombre"]));
    if ($tmp_nombre == "" || strlen($tmp_nombre) < 2) {
        header("Location: ../signup.html?error=nombre");
        exit();
    } else {
        $nombre = $tmp_nombre;
    }

    $tmp_ciudad = htmlspecialchars(trim($_POST["ciudad"]));
    if ($tmp_ciudad == "") {
        header("Location: ../signup.html?error=ciudad");
        exit();
    } else {
        $ciudad = $tmp_ciudad;
    }

    $tmp_localidad = htmlspecialchars(trim($_POST["prote-localidad"]));
    if ($tmp_localidad == "") {
        header("Location: ../signup.html?error=localidad");
        exit();
    } else {
        $localidad = $tmp_localidad;
    }

    $tmp_direccion = htmlspecialchars(trim($_POST["prote-direccion"]));
    if ($tmp_direccion == "") {
        header("Location: ../signup.html?error=direccion");
        exit();
    } else {
        $direccion = $tmp_direccion;
    }

    $tmp_email  = htmlspecialchars(trim($_POST["prote-email"]));
    $tmp_email2 = htmlspecialchars(trim($_POST["prote-email2"]));
    if ($tmp_email == "") {
        header("Location: ../signup.html?error=email");
        exit();
    } elseif (!preg_match("/^[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}$/", $tmp_email)) {
        header("Location: ../signup.html?error=email");
        exit();
    } elseif ($tmp_email != $tmp_email2) {
        header("Location: ../signup.html?error=email");
        exit();
    } else {
        $email = $tmp_email;
    }

    $tmp_pass  = htmlspecialchars(trim($_POST["prote-password"]));
    $tmp_pass2 = htmlspecialchars(trim($_POST["prote-password2"]));
    if ($tmp_pass == "") {
        header("Location: ../signup.html?error=pass");
        exit();
    } elseif (!preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/", $tmp_pass)) {
        header("Location: ../signup.html?error=pass");
        exit();
    } elseif ($tmp_pass != $tmp_pass2) {
        header("Location: ../signup.html?error=pass");
        exit();
    } else {
        $pass = $tmp_pass;
    }

    if (isset($nombre, $ciudad, $localidad, $direccion, $email, $pass)) {
        $pass_cifrada = password_hash($pass, PASSWORD_DEFAULT);
        $consulta = $_conexion->prepare(
            "INSERT INTO Protectora (nombre_protectora, ciudad, localidad, direccion, email, contraseña, telefono, logo)
             VALUES (?, ?, ?, ?, ?, ?, NULL, NULL)"
        );
        $consulta->bind_param("ssssss", $nombre, $ciudad, $localidad, $direccion, $email, $pass_cifrada);
        if ($consulta->execute()) {
            $consulta->close();
            header("Location: ../login.html");
            exit();
        } else {
            $consulta->close();
            header("Location: ../signup.html?error=db");
            exit();
        }
    }
}
?>