<?php
session_start();
require "../sesion/conexion.php";
require_once "../model/CrowdfundingModel.php";
require_once "../model/ProtectoraModel.php";
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

$protectoraModel = new ProtectoraModel($_conexion);
if (!$protectoraModel->verificarExiste($id_protectora)) {
    echo json_encode(['ok' => false, 'error' => 'Protectora no encontrada.']);
    exit;
}

$id_adoptante      = (int)$_SESSION['id'];
$nombre_don        = $nombre !== '' ? $nombre : ($_SESSION['nombre'] ?? 'Anónimo');
$crowdfundingModel = new CrowdfundingModel($_conexion);

if ($crowdfundingModel->addDonacionDirecta($id_protectora, $id_adoptante, $nombre_don, $cantidad)) {
    echo json_encode(['ok' => true]);
} else {
    echo json_encode(['ok' => false, 'error' => 'Error al guardar la donación.']);
}
