<?php
session_start();
require "../sesion/conexion.php";
require_once "../model/LikeModel.php";

header('Content-Type: application/json');

if (!isset($_SESSION['id']) || !isset($_SESSION['user'])) {
    echo json_encode(['error' => 'not_logged_in']);
    exit();
}

$id_adoptante = (int)$_SESSION['id'];
$id_animal    = isset($_POST['id_animal']) ? (int)$_POST['id_animal'] : 0;

if (!$id_animal) {
    echo json_encode(['error' => 'invalid']);
    exit();
}

$likeModel = new LikeModel($_conexion);
$liked = $likeModel->toggle($id_adoptante, $id_animal);
echo json_encode(['liked' => $liked]);
