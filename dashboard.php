<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

$controller = new DashboardController();
$datos      = $controller->manejar();

$stats         = $datos['stats'];
$ultimasDietas = $datos['ultimasDietas'];

$pageTitle = 'Dashboard';
$activeNav = 'dashboard';

require __DIR__ . '/views/dashboard_view.php';
