<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

$controller = new AlimentoController();
$datos      = $controller->manejar();

$alimentos = $datos['alimentos'];
$total     = $datos['total'];

$pageTitle = 'Catálogo de alimentos';
$activeNav = 'alimentos';

require __DIR__ . '/views/alimentos_view.php';
