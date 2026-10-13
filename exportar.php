<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

$controller = new ExportarController();
$datos      = $controller->manejar();

if ($datos['paciente'] === null) {
    $pageTitle = 'Exportación del plan en PDF';
    $activeNav = 'exportar';
    include __DIR__ . '/includes/header.php';
    echo '<div class="rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3">
            No se encontró el paciente indicado. <a href="pacientes.php" class="underline font-medium">Volver a pacientes</a>.
          </div>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

$paciente      = $datos['paciente'];
$idPaciente    = $datos['idPaciente'];
$dieta         = $datos['dieta'];
$nutricionista = $datos['nutricionista'];
$generado      = $datos['generado'];
$error         = $datos['error'];
$csrf          = $datos['csrf'];

$pageTitle = 'Exportación del plan en PDF';
$activeNav = 'exportar';

require __DIR__ . '/views/exportar_view.php';
