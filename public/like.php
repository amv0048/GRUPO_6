<?php
session_start();
require "../src/sesion/conexion.php";

header('Content-Type: application/json');

// Solo adoptantes pueden dar likes
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

// ¿Ya existe el like?
$check = $_conexion->prepare(
    "SELECT 1 FROM Likes WHERE id_adoptante = ? AND id_animal = ?"
);
$check->bind_param("ii", $id_adoptante, $id_animal);
$check->execute();
$exists = $check->get_result()->num_rows > 0;
$check->close();

if ($exists) {
    $stmt = $_conexion->prepare(
        "DELETE FROM Likes WHERE id_adoptante = ? AND id_animal = ?"
    );
    $stmt->bind_param("ii", $id_adoptante, $id_animal);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['liked' => false]);
} else {
    $stmt = $_conexion->prepare(
        "INSERT INTO Likes (id_adoptante, id_animal) VALUES (?, ?)"
    );
    $stmt->bind_param("ii", $id_adoptante, $id_animal);
    $stmt->execute();
    $stmt->close();
    echo json_encode(['liked' => true]);
}
