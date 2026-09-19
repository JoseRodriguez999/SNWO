<?php
/**
 * evaluacion.php — Controlador de entrada de la pantalla de evaluación antropométrica.
 * Delega toda la lógica a EvaluacionController; este archivo solo conecta
 * controlador -> vista.
 */
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

$controller = new EvaluacionController();
$datos      = $controller->manejar();

if ($datos['paciente'] === null) {
    $pageTitle = 'Evaluación antropométrica';
    $activeNav = 'pacientes';
    include __DIR__ . '/includes/header.php';
    echo '<div class="rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3">
            No se encontró el paciente indicado. <a href="pacientes.php" class="underline font-medium">Volver a pacientes</a>.
          </div>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

$paciente          = $datos['paciente'];
$edad              = $datos['edad'];
$idPaciente         = $datos['idPaciente'];
$error             = $datos['error'];
$csrf              = $datos['csrf'];
$datosCompletos    = $datos['datosCompletos'];
$factoresActividad = $datos['factoresActividad'];

$pageTitle = 'Evaluación antropométrica';
$activeNav = 'pacientes';

require __DIR__ . '/views/evaluacion_view.php';
