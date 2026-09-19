<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

$controller = new UsuarioController();
$datos      = $controller->manejar();

$usuarios = $datos['usuarios'];
$csrf     = $datos['csrf'];

$pageTitle = 'Gestión de usuarios y roles';
$activeNav = 'usuarios';

require __DIR__ . '/views/usuarios_view.php';
