<?php
session_start();
require "../sesion/conexion.php";
require_once "../model/CrowdfundingModel.php";
header('Content-Type: application/json');

$id_caso  = (int)($_POST['id_caso']   ?? 0);
$cantidad = (float)($_POST['cantidad'] ?? 0);
$nombre   = trim($_POST['nombre']      ?? '');

if ($id_caso <= 0 || $cantidad <= 0) {
    echo json_encode(['ok' => false, 'error' => 'Datos inválidos.']);
    exit;
}

$crowdfundingModel = new CrowdfundingModel($_conexion);

$caso = $crowdfundingModel->getCasoById($id_caso);
if (!$caso || !$caso['activo']) {
    echo json_encode(['ok' => false, 'error' => 'Caso no encontrado o inactivo.']);
    exit;
}

$nombre_don   = $nombre !== '' ? $nombre : 'Anónimo';
$id_adoptante = (isset($_SESSION['id']) && isset($_SESSION['user'])) ? (int)$_SESSION['id'] : null;

if (!$crowdfundingModel->addDonacion($id_caso, $cantidad, $nombre_don, $id_adoptante)) {
    echo json_encode(['ok' => false, 'error' => 'Error al guardar la donación.']);
    exit;
}

$updated = $crowdfundingModel->getCasoById($id_caso);

echo json_encode([
    'ok'        => true,
    'recaudado' => (float)$updated['recaudado'],
    'meta'      => (float)$updated['meta_euros'],
]);
