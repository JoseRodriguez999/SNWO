<?php
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

$controller = new UsuarioController();
$datos      = $controller->manejar();

$usuarios = $datos['usuarios'];
$csrf     = $datos['csrf'];
$error    = $datos['error'];
$exito    = $datos['exito'];
$old      = $datos['old'];
$esAdmin  = $datos['esAdmin'];
$esSuper  = $datos['esSuper'];
$errorGlobal = $datos['errorGlobal'];
$puedeCrear = $datos['puedeCrear'];
$pacientesLibres = $datos['pacientesLibres'];
$errorEdicion = $datos['errorEdicion'];
$oldEdit = $datos['oldEdit'];

$pageTitle = 'Gestión de usuarios y roles';
$activeNav = 'usuarios';

require __DIR__ . '/views/usuarios_view.php';
