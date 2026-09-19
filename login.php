<?php
/**
 * login.php — Controlador de entrada (front controller) de la pantalla de login.
 * Delega toda la lógica a AuthController; este archivo solo conecta
 * controlador -> vista. Reemplaza al login.php anterior (todo-en-uno).
 */
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

$controller = new AuthController();
$resultado  = $controller->manejar();

$error = $resultado['error'];
$csrf  = $resultado['csrf'];

require __DIR__ . '/views/login_view.php';
