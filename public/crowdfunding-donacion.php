<?php
session_start();
require "../src/sesion/conexion.php";
header('Content-Type: application/json');

$id_caso  = (int)($_POST['id_caso']   ?? 0);
$cantidad = (float)($_POST['cantidad'] ?? 0);
$nombre   = trim($_POST['nombre']      ?? '');

if ($id_caso <= 0 || $cantidad <= 0) {
    echo json_encode(['ok' => false, 'error' => 'Datos inválidos.']);
    exit;
}

$tbl = $_conexion->query("SHOW TABLES LIKE 'CrowdfundingCaso'");
if (!$tbl || $tbl->num_rows === 0) {
    echo json_encode(['ok' => false, 'error' => 'Funcionalidad no disponible.']);
    exit;
}

$st = $_conexion->prepare("SELECT id_caso FROM CrowdfundingCaso WHERE id_caso = ? AND activo = 1");
$st->bind_param('i', $id_caso);
$st->execute();
if (!$st->get_result()->fetch_assoc()) {
    echo json_encode(['ok' => false, 'error' => 'Caso no encontrado o inactivo.']);
    exit;
}

$nombre_don = $nombre !== '' ? $nombre : 'Anónimo';

if (isset($_SESSION['id']) && isset($_SESSION['user'])) {
    $id_adoptante = (int)$_SESSION['id'];
    $st2 = $_conexion->prepare(
        "INSERT INTO Donacion (id_caso, id_adoptante, nombre_donante, cantidad) VALUES (?, ?, ?, ?)"
    );
    $st2->bind_param('iisd', $id_caso, $id_adoptante, $nombre_don, $cantidad);
} else {
    $st2 = $_conexion->prepare(
        "INSERT INTO Donacion (id_caso, nombre_donante, cantidad) VALUES (?, ?, ?)"
    );
    $st2->bind_param('isd', $id_caso, $nombre_don, $cantidad);
}

if (!$st2->execute()) {
    echo json_encode(['ok' => false, 'error' => 'Error al guardar la donación.']);
    exit;
}

$st3 = $_conexion->prepare(
    "UPDATE CrowdfundingCaso SET recaudado = recaudado + ? WHERE id_caso = ?"
);
$st3->bind_param('di', $cantidad, $id_caso);
$st3->execute();

$st4 = $_conexion->prepare("SELECT recaudado, meta_euros FROM CrowdfundingCaso WHERE id_caso = ?");
$st4->bind_param('i', $id_caso);
$st4->execute();
$updated = $st4->get_result()->fetch_assoc();

echo json_encode([
    'ok'        => true,
    'recaudado' => (float)$updated['recaudado'],
    'meta'      => (float)$updated['meta_euros'],
]);
