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

        $consulta = $_conexion->prepare("SELECT * FROM Usuario WHERE email = ?");
        $consulta->bind_param("s", $email);
        $consulta->execute();
        $resultado = $consulta->get_result();

        if ($resultado->num_rows === 0) {
            $consulta->close();
            header("Location: ../../public/login.html?error=noexiste");
            exit();
        }

        $info_usuario = $resultado->fetch_assoc();
        $consulta->close();

        if (!password_verify($pass, $info_usuario["contrasena"])) { // ESTO ????? Ñ?????
            header("Location: ../../public/login.html?error=passNoCoincide");
            exit();
        }

        $_SESSION["id"]     = $info_usuario["id"];
        $_SESSION["nombre"] = $info_usuario["nombre"];
        $_SESSION["email"]  = $info_usuario["email"];
        $_SESSION["admin"]  = $info_usuario["admin"];

        header("Location: ../index.php"); // ESTO NO ESTÁ AQUI
        exit();
    }
}
?>