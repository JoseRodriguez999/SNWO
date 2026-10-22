<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

$controller = new ReporteController();
$datos      = $controller->manejar();

$catalogo     = $datos['catalogo'];
$r            = $datos['r'];
$reporte      = $datos['reporte'];
$filtros      = $datos['filtros'];
$pacientes    = $datos['pacientes'];
$evaluaciones = $datos['evaluaciones'];
$error        = $datos['error'];
$exportar     = $datos['exportar'];

$pageTitle = $r !== '' ? $catalogo[$r][0] : 'Reportes';
$activeNav = 'reportes';

require __DIR__ . '/views/reportes_view.php';
