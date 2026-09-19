<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

$controller = new GeneradorController();
$datos      = $controller->manejar();

if ($datos['paciente'] === null) {
    $pageTitle = 'Generador de dietas';
    $activeNav = 'generador';
    include __DIR__ . '/includes/header.php';
    echo '<div class="rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3">
            No se encontró el paciente indicado. <a href="pacientes.php" class="underline font-medium">Volver a pacientes</a>.
          </div>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

$paciente   = $datos['paciente'];
$idPaciente = $datos['idPaciente'];
$dieta      = $datos['dieta'];
$dias       = $datos['dias'];
$tiempos    = $datos['tiempos'];
$error      = $datos['error'];
$csrf       = $datos['csrf'];

$pageTitle = 'Generador de dietas';
$activeNav = 'generador';

require __DIR__ . '/views/generador_view.php';
