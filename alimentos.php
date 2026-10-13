<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

$controller = new AlimentoController();
$datos      = $controller->manejar();

$alimentos = $datos['alimentos'];
$categorias = $datos['categorias'];
$csrf = $datos['csrf'];
$error = $datos['error'];
$exito = $datos['exito'];
$old = $datos['old'];
$puedeCrear = $datos['puedeCrear'];
$total     = $datos['total'];

$pageTitle = 'Catálogo de alimentos';
$activeNav = 'alimentos';

require __DIR__ . '/views/alimentos_view.php';
