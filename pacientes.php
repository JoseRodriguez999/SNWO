<?php
/**
 * pacientes.php — Controlador de entrada de la pantalla de pacientes.
 * Delega toda la lógica a PacienteController; este archivo solo conecta
 * controlador -> vista.
 */
declare(strict_types=1);
require_once __DIR__ . '/bootstrap.php';

$controller = new PacienteController();
$datos      = $controller->manejar();

$error     = $datos['error'];
$exito     = $datos['exito'];
$csrf      = $datos['csrf'];
$pacientes = $datos['pacientes'];

$pageTitle = 'Gestión de pacientes';
$activeNav = 'pacientes';

require __DIR__ . '/views/pacientes_view.php';
