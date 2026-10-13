<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

$controller = new MiPlanController();
$datos      = $controller->manejar();

$pageTitle = 'Mi plan';
$activeNav = 'miplan';

if ($datos['estado'] !== 'ok') {
    include __DIR__ . '/includes/header.php';
    $mensaje = $datos['estado'] === 'sin_vinculo'
        ? 'Tu cuenta todavía no está vinculada a un expediente de paciente. Pide a la clínica que la active.'
        : 'Tu expediente no está disponible en este momento. Comunícate con la clínica.';
    echo '<div class="rounded-lg bg-amber-50 border border-amber-200 text-amber-800 text-sm px-4 py-3">'
       . htmlspecialchars($mensaje, ENT_QUOTES, 'UTF-8') . '</div>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

$paciente         = $datos['paciente'];
$dieta            = $datos['dieta'];
$dias             = $datos['dias'];
$tiempos          = $datos['tiempos'];
$vista            = $datos['vista'];
$error            = $datos['error'];
$exito            = $datos['exito'];
$csrf             = $datos['csrf'];
$editandoMenu     = $datos['editandoMenu'];
$editandoAlimento = $datos['editandoAlimento'];
$tiempoSlot       = $datos['tiempoSlot'];
$resultados       = $datos['resultados'];
$termino          = $datos['termino'];
$catalogo         = $datos['catalogo'];

require __DIR__ . '/views/mi_plan_view.php';
