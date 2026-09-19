<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

$controller = new PersonalizarController();
$datos      = $controller->manejar();

$pacienteNombre       = $datos['pacienteNombre'];
$alimentosActuales    = $datos['alimentosActuales'];
$catalogoAlternativas = $datos['catalogoAlternativas'];

$pageTitle = 'Personalización del plan';
$activeNav = 'personalizar';

require __DIR__ . '/views/personalizar_view.php';
