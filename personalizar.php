<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

$controller = new PersonalizarController();
$datos      = $controller->manejar();

if ($datos['paciente'] === null) {
    $pageTitle = 'Personalizar plan';
    $activeNav = 'personalizar';
    include __DIR__ . '/includes/header.php';
    echo '<div class="rounded-lg bg-red-50 border border-red-200 text-red-700 text-sm px-4 py-3">
            No se encontró el paciente indicado. <a href="pacientes.php" class="underline font-medium">Volver a pacientes</a>.
          </div>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

$paciente    = $datos['paciente'];
$idPaciente  = $datos['idPaciente'];
$dieta       = $datos['dieta'];
$dias        = $datos['dias'];
$tiempos     = $datos['tiempos'];
$error       = $datos['error'];
$csrf        = $datos['csrf'];
$editandoMenu       = $datos['editandoMenu'];
$editandoAlimento   = $datos['editandoAlimento'];
$tiempoSlot         = $datos['tiempoSlot'];
$resultadosBusqueda = $datos['resultadosBusqueda'];
$terminoBusqueda    = $datos['terminoBusqueda'];
$cambios            = $datos['cambios'];

$pageTitle = 'Personalizar plan';
$activeNav = 'personalizar';

require __DIR__ . '/views/personalizar_view.php';
