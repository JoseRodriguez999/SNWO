<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

$controller = new ExportarController();
$datos      = $controller->manejar();

$pacienteNombre = $datos['pacienteNombre'];

$pageTitle = 'Exportación del plan en PDF';
$activeNav = 'exportar';

require __DIR__ . '/views/exportar_view.php';
