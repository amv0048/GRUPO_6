<?php
session_start();
require "../sesion/conexion.php";
header('Content-Type: application/json');

if (!isset($_SESSION['id']) || !isset($_SESSION['user'])) {
    echo json_encode(['ok' => false, 'error' => 'Debes iniciar sesión como adoptante para donar.']);
    exit;
}

$id_protectora = (int)($_POST['id_protectora'] ?? 0);
$cantidad      = (float)($_POST['cantidad']     ?? 0);
$nombre        = trim($_POST['nombre']          ?? '');

if ($id_protectora <= 0 || $cantidad <= 0) {
    echo json_encode(['ok' => false, 'error' => 'Datos inválidos.']);
    exit;
}

$st = $_conexion->prepare("SELECT id_protectora FROM Protectora WHERE id_protectora = ?");
$st->bind_param('i', $id_protectora);
$st->execute();
if (!$st->get_result()->fetch_assoc()) {
    echo json_encode(['ok' => false, 'error' => 'Protectora no encontrada.']);
    exit;
}

$id_adoptante = (int)$_SESSION['id'];
$nombre_don   = $nombre !== '' ? $nombre : ($_SESSION['nombre'] ?? 'Anónimo');

$st2 = $_conexion->prepare(
    "INSERT INTO DonacionDirecta (id_protectora, id_adoptante, nombre_donante, cantidad)
     VALUES (?, ?, ?, ?)"
);
$st2->bind_param('iisd', $id_protectora, $id_adoptante, $nombre_don, $cantidad);

if ($st2->execute()) {
    echo json_encode(['ok' => true]);
} else {
    echo json_encode(['ok' => false, 'error' => 'Error al guardar la donación.']);
}
