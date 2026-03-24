<?php
session_start();
require "conexion.php";
error_reporting(E_ALL);
ini_set("display_errors", 1);

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // ── EMAIL ────────────────────────────────────────────────
    $tmp_email = htmlspecialchars(trim($_POST["login-email"]));
    if ($tmp_email == "") {
        header("Location: ../../public/login.html?error=email");
        exit();
    } else {
        $email = $tmp_email;
    }

    // ── CONTRASEÑA ───────────────────────────────────────────
    $tmp_pass = trim($_POST["login-password"]); // HAY 
    if ($tmp_pass == "") {
        header("Location: ../../public/login.html?error=pass");
        exit();
    } else {
        $pass = $tmp_pass;
    }

    // ── CONSULTA ─────────────────────────────────────────────
    if (isset($email, $pass)) {

    // Busca primero en Usuario
    $consulta = $_conexion->prepare("SELECT * FROM Usuario WHERE email = ?");
    $consulta->bind_param("s", $email);
    $consulta->execute();
    $resultado = $consulta->get_result();

    // Si no está en Usuario busca en Protectora
    if ($resultado->num_rows === 0) {
        $consulta->close();
        $consulta = $_conexion->prepare("SELECT * FROM Protectora WHERE email = ?");
        $consulta->bind_param("s", $email);
        $consulta->execute();
        $resultado = $consulta->get_result();

        // Si tampoco está en Protectora, no existe
        if ($resultado->num_rows === 0) {
            $consulta->close();
            header("Location: ../../public/login.html?error=noexiste");
            exit();
        }
    }

    $info_usuario = $resultado->fetch_assoc();
    $consulta->close();

    if (!password_verify($pass, $info_usuario["contrasena"])) {
        header("Location: ../../public/login.html?error=passNoCoincide");
        exit();
    }

    if (isset($info_usuario["nombre_protectora"])) {
        $_SESSION["id"]         = $info_usuario["id_protectora"];
        $_SESSION["nombre"] = $info_usuario["nombre_protectora"]; // puede que esto este mal...
    } else {
        $_SESSION["id"]    = $info_usuario["id_adoptante"];
        $_SESSION["nombre"] = $info_usuario["nombre"];
        $_SESSION["admin"]  = $info_usuario["admin"];
    }

    $_SESSION["email"] = $info_usuario["email"];

    header("Location: ../../public/index.php");
    exit();
}
}
?>