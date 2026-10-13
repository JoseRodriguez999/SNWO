<?php
/**
 * historial.php — Controlador de entrada del historial de evaluaciones de un paciente.
 */
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

$controller = new HistorialController();
$datos      = $controller->manejar();

if ($datos['paciente'] === null) {
    header('Location: pacientes.php?elegir=1');
    exit;
}

$paciente     = $datos['paciente'];
$idPaciente   = $datos['idPaciente'];
$evaluaciones = $datos['evaluaciones'];
$total        = $datos['total'];
$primera      = $datos['primera'];
$ultima       = $datos['ultima'];
$cambioTotal  = $datos['cambioTotal'];

$pageTitle = 'Historial de evaluaciones';
$activeNav = 'historial';

require __DIR__ . '/views/historial_view.php';
